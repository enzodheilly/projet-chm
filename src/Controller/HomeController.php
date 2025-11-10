<?php

namespace App\Controller;

use App\Entity\NewsletterSubscriber;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ArticleRepository;
use App\Service\NewsletterService;
use Doctrine\ORM\EntityManagerInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        ArticleRepository $articleRepository,
        NewsletterService $newsletterService,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        $isSubscribed = false;
        $subscriber = null;

        if ($user) {
            // 🔹 On récupère l’abonné newsletter lié à l’email du user, confirmé
            $subscriber = $em->getRepository(NewsletterSubscriber::class)
                ->findOneBy([
                    'email' => $user->getEmail(),
                    'isConfirmed' => true
                ]);

            $isSubscribed = $subscriber !== null;

            // 🔹 Redirection des admins
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_dashboard');
            }

            // 🔹 ROLE_USER -> tu gardes la home pour l’instant
            if ($this->isGranted('ROLE_USER')) {
                // return $this->redirectToRoute('user_dashboard');
            }
        }

        $articles = $articleRepository->findBy([], ['publishedAt' => 'DESC']);

        return $this->render('0_home/index.html.twig', [
            'articles'     => $articles,
            'isSubscribed' => $isSubscribed,
            'subscriber'   => $subscriber, // 👈 utilisé dans le footer
        ]);
    }
}
