<?php

namespace App\Controller\Admin;

use App\Repository\SecurityLogRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/security', name: 'admin_security_')]
class AdminSecurityController extends AbstractController
{
    /**
     * 📋 Journal des connexions (affiche les 100 dernières)
     */
    #[Route('/logs', name: 'logs')]
    public function logs(SecurityLogRepository $repo): Response
    {
        $logs = $repo->findBy([], ['createdAt' => 'DESC'], 100);

        return $this->render('admin/security/logs.html.twig', [
            'logs' => $logs,
        ]);
    }

    /**
     * 🚫 Liste les utilisateurs actuellement bloqués
     */
    #[Route('/blocklist', name: 'blocklist')]
    public function blocklist(UserRepository $userRepository): Response
    {
        $now = new \DateTimeImmutable();

        // 🔍 Récupère tous les utilisateurs dont le compte est encore verrouillé
        $blockedUsers = $userRepository->createQueryBuilder('u')
            ->where('u.lockedUntil IS NOT NULL')
            ->andWhere('u.lockedUntil > :now')
            ->setParameter('now', $now)
            ->orderBy('u.lockedUntil', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/security/blocklist.html.twig', [
            'blockedIps' => array_map(function ($user) {
                return [
                    'ip' => $user->getLastLoginIp() ?? '—',
                    'reason' => 'Trop d’échecs de connexion',
                    'blockedAt' => $user->getLockedUntil(),
                ];
            }, $blockedUsers),
        ]);
    }

    /**
     * 🔓 Débloquer un utilisateur manuellement
     */
    #[Route('/blocklist/unlock/{id}', name: 'unlock_user')]
    public function unlockUser(UserRepository $userRepository, EntityManagerInterface $em, int $id): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('admin_security_blocklist');
        }

        $user->setLockedUntil(null);
        $user->setFailedAttempts(0);
        $em->flush();

        $this->addFlash('success', "✅ L’utilisateur <strong>{$user->getEmail()}</strong> a été débloqué avec succès.");
        return $this->redirectToRoute('admin_security_blocklist');
    }

    /**
     * 🧹 Purge tous les logs de connexion
     */
    #[Route('/purge', name: 'purge_logs')]
    public function purge(SecurityLogRepository $repo, EntityManagerInterface $em): Response
    {
        $logs = $repo->findAll();
        foreach ($logs as $log) {
            $em->remove($log);
        }
        $em->flush();

        $this->addFlash('success', '🧹 Tous les journaux de connexion ont été supprimés.');
        return $this->redirectToRoute('admin_security_logs');
    }
}
