<?php
// src/Service/NewsletterService.php
namespace App\Service;

use App\Repository\NewsletterSubscriberRepository;

class NewsletterService
{
    public function __construct(private NewsletterSubscriberRepository $repo) {}

    public function isUserSubscribed(?string $email): bool
    {
        if (!$email) return false;
        return $this->repo->findOneBy(['email' => $email]) !== null;
    }
}
