<?php

namespace App\Controller;

use App\Entity\Subscriber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Annotation\Route;

class NewsletterController extends AbstractController
{
    #[Route('/newsletter', name: 'newsletter_subscribe', methods: ['POST'])]
    public function subscribe(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $submittedToken = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('newsletter', $submittedToken)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF invalide.'], 400);
        }

        $emailInput = $request->request->get('email');
        if (!$emailInput) {
            return $this->json(['success' => false, 'message' => 'Veuillez entrer une adresse e-mail.'], 400);
        }

        // Vérification si l'email existe déjà
        $existing = $em->getRepository(Subscriber::class)->findOneBy(['email' => $emailInput]);
        if ($existing) {
            return $this->json(['success' => false, 'message' => 'Cette adresse est déjà inscrite.'], 400);
        }

        // Création du nouvel abonné
        $subscriber = new Subscriber();
        $subscriber->setEmail($emailInput);
        $subscriber->setIsConfirmed(false);

        try {
            $em->persist($subscriber);
            $em->flush();
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l’enregistrement en base : ' . $e->getMessage()
            ], 500);
        }

        // Envoi de l'email après insertion réussie
        try {
            $email = (new TemplatedEmail())
                ->from('no-reply@monsite.com')
                ->to($subscriber->getEmail())
                ->subject('Confirmez votre inscription')
                ->htmlTemplate('emails/confirm.html.twig')
                ->context(['subscriber' => $subscriber]);

            $mailer->send($email);
        } catch (\Exception $e) {
            // Si l'email échoue, l'inscription reste en base
            return $this->json([
                'success' => false,
                'message' => 'Inscription enregistrée mais impossible d’envoyer l’email : ' . $e->getMessage()
            ], 500);
        }

        return $this->json(['success' => true]);
    }

    #[Route('/newsletter/confirm/{id}', name: 'newsletter_confirm')]
    public function confirm(int $id, EntityManagerInterface $em): Response
    {
        $subscriber = $em->getRepository(Subscriber::class)->find($id);

        if (!$subscriber) {
            throw $this->createNotFoundException('Abonné non trouvé.');
        }

        $subscriber->setIsConfirmed(true);
        $em->flush();

        $this->addFlash('success', 'Votre inscription à la newsletter a été confirmée !');

        return $this->redirectToRoute('homepage');
    }

    #[Route('/test-email', name: 'test_email')]
    public function testEmail(MailerInterface $mailer): Response
    {
        $htmlContent = $this->renderView('emails/confirm.html.twig', [
            'subscriber' => ['id' => 123], // données factices
        ]);

        $email = (new Email())
            ->from('enzodheilly134@gmail.com')
            ->to('enzodheilly134@gmail.com') // peu importe, ça arrive dans ta boîte Ethereal
            ->subject('Newsletter - Test')
            ->html($htmlContent);

        $mailer->send($email);

        return new Response('Mail test envoyé !');
    }
}
