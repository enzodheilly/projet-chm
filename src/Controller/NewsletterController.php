<?php

namespace App\Controller;

use App\Entity\NewsletterSubscriber;
use App\Entity\NewsletterCampaign;
use App\Service\SystemLoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NewsletterController extends AbstractController
{
    #[Route('/newsletter', name: 'newsletter_subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        SystemLoggerService $logger
    ): Response {
        usleep(400000); // Anti-bot léger

        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('info', 'Veuillez vous connecter pour vous abonner à la newsletter.');
            return $this->redirectToRoute('app_login', ['newsletter_required' => 1]);
        }

        // CSRF + honeypot
        $submittedToken = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('newsletter', $submittedToken)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF invalide.'], 400);
        }
        if (!empty($request->request->get('nickname'))) {
            $logger->add('Tentative spam newsletter', sprintf('Bot détecté depuis IP %s.', $request->getClientIp()));
            return $this->json(['success' => false, 'message' => 'Requête refusée (spam détecté).'], 400);
        }

        $emailInput = $user->getEmail();
        $lastSubscription = $em->getRepository(NewsletterSubscriber::class)->findOneBy(['user' => $user]);
        if ($lastSubscription && $lastSubscription->getSubscribedAt() > new \DateTimeImmutable('-1 minute')) {
            return $this->json(['success' => false, 'message' => 'Veuillez patienter avant de réessayer.'], 429);
        }

        if ($lastSubscription && $lastSubscription->getIsConfirmed()) {
            return $this->json(['success' => false, 'message' => 'Vous êtes déjà abonné.'], 400);
        }

        $subscriber = $lastSubscription ?? new NewsletterSubscriber();
        $subscriber->setUser($user);
        $subscriber->setEmail($emailInput);
        $subscriber->setIsConfirmed(false);
        $subscriber->setSubscribedAt(new \DateTimeImmutable());
        $subscriber->setConfirmationToken(bin2hex(random_bytes(32)));

        $em->persist($subscriber);
        $em->flush();

        try {
            $email = (new TemplatedEmail())
                ->from('no-reply@monsite.com')
                ->to($subscriber->getEmail())
                ->subject('Confirmez votre inscription à la newsletter')
                ->htmlTemplate('emails/confirm.html.twig')
                ->context([
                    'subscriber' => $subscriber,
                    'confirmUrl' => $this->generateUrl(
                        'newsletter_confirm',
                        ['token' => $subscriber->getConfirmationToken()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ]);

            $mailer->send($email);
        } catch (\Exception $e) {
            $logger->add('Erreur envoi e-mail newsletter', $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Inscription enregistrée, mais e-mail non envoyé.'], 500);
        }

        return $this->json(['success' => true, 'message' => 'Un e-mail de confirmation vient de vous être envoyé.']);
    }

    #[Route('/newsletter/confirm/{token}', name: 'newsletter_confirm')]
    public function confirm(string $token, EntityManagerInterface $em, SystemLoggerService $logger): Response
    {
        $subscriber = $em->getRepository(NewsletterSubscriber::class)->findOneBy(['confirmationToken' => $token]);

        if (!$subscriber) {
            $this->addFlash('error', 'Lien de confirmation invalide ou expiré.');
            return $this->redirectToRoute('home');
        }

        $subscriber->setIsConfirmed(true);
        $subscriber->setConfirmationToken(null);
        $em->flush();

        $logger->add('Confirmation newsletter', sprintf('Inscription confirmée pour %s', $subscriber->getEmail()));
        $this->addFlash('success', 'Votre abonnement à la newsletter a bien été confirmé !');

        return $this->redirectToRoute('home');
    }

    #[Route('/newsletter/unsubscribe/{id}', name: 'newsletter_unsubscribe')]
    public function unsubscribe(
        NewsletterSubscriber $subscriber,
        EntityManagerInterface $em
    ): Response {
        if (!$subscriber->getIsConfirmed()) {
            return $this->render('newsletter/unsubscribe.html.twig', [
                'alreadyUnsubscribed' => true,
                'email' => $subscriber->getEmail(),
            ]);
        }

        $subscriber->setIsConfirmed(false);
        $em->flush();

        return $this->render('newsletter/unsubscribe.html.twig', [
            'alreadyUnsubscribed' => false,
            'email' => $subscriber->getEmail(),
        ]);
    }

    #[Route('/test-email', name: 'test_email')]
    public function testEmail(MailerInterface $mailer, SystemLoggerService $logger): Response
    {
        $htmlContent = $this->renderView('emails/confirm.html.twig', [
            'subscriber' => ['id' => 123],
            'confirmUrl' => 'https://example.com/newsletter/confirm/testtoken'
        ]);

        $email = (new Email())
            ->from('no-reply@monsite.com')
            ->to('enzodheilly134@gmail.com')
            ->subject('Newsletter - Test')
            ->html($htmlContent);

        $mailer->send($email);
        $logger->add('Test newsletter', 'Email de test envoyé à enzodheilly134@gmail.com');

        return new Response('Mail test envoyé !');
    }

    // 🟢 Tracking d’ouverture (pixel invisible)
    #[Route('/newsletter/open/{campaignId}/{subscriberId}', name: 'newsletter_open')]
    public function trackOpen(
        int $campaignId,
        int $subscriberId,
        EntityManagerInterface $em
    ): Response {
        $campaign = $em->getRepository(NewsletterCampaign::class)->find($campaignId);
        if ($campaign) {
            $campaign->setOpenCount(($campaign->getOpenCount() ?? 0) + 1);
            $em->flush();
        }

        // Pixel GIF transparent 1x1
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
        $response = new Response($pixel);
        $response->headers->set('Content-Type', 'image/gif');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        return $response;
    }

    // 🟣 Tracking des clics
    #[Route('/newsletter/click/{campaignId}/{subscriberId}', name: 'newsletter_click')]
    public function trackClick(
        Request $request,
        int $campaignId,
        int $subscriberId,
        EntityManagerInterface $em
    ): Response {
        $campaign = $em->getRepository(NewsletterCampaign::class)->find($campaignId);
        $url = $request->query->get('url');

        if ($campaign && $url) {
            $campaign->setClickCount(($campaign->getClickCount() ?? 0) + 1);
            $em->flush();
            return $this->redirect($url);
        }

        return new Response('Invalid click', 400);
    }
}
