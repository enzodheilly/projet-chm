<?php

namespace App\Entity;

use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsletterSubscriberRepository::class)]
#[ORM\Table(name: 'newsletter_subscriber')]
#[ORM\UniqueConstraint(name: 'uniq_newsletter_email', columns: ['email'])]
class NewsletterSubscriber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $confirmationToken = null;

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private ?string $unsubscribeToken = null; // ✅ token dédié au désabonnement

    #[ORM\Column(type: "boolean")]
    private bool $isConfirmed = false;

    #[ORM\Column(type: "datetime_immutable")]
    private \DateTimeImmutable $subscribedAt;

    #[ORM\OneToOne(inversedBy: 'newsletterSubscription', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    public function __construct()
    {
        $this->subscribedAt = new \DateTimeImmutable();
        $this->generateTokens();
    }

    // =========================================
    // ✅ Getters / Setters
    // =========================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = mb_strtolower(trim($email));
        return $this;
    }

    public function getSubscribedAt(): \DateTimeImmutable
    {
        return $this->subscribedAt;
    }

    public function setSubscribedAt(\DateTimeImmutable $date): self
    {
        $this->subscribedAt = $date;
        return $this;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(?string $confirmationToken): self
    {
        $this->confirmationToken = $confirmationToken;
        return $this;
    }

    public function getUnsubscribeToken(): ?string
    {
        return $this->unsubscribeToken;
    }

    public function setUnsubscribeToken(string $token): self
    {
        $this->unsubscribeToken = $token;
        return $this;
    }

    public function getIsConfirmed(): bool
    {
        return $this->isConfirmed;
    }

    public function setIsConfirmed(bool $isConfirmed): self
    {
        $this->isConfirmed = $isConfirmed;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    // =========================================
    // 🧩 Helpers / Tokens
    // =========================================

    public function generateTokens(): self
    {
        $this->confirmationToken = bin2hex(random_bytes(32));
        $this->unsubscribeToken = bin2hex(random_bytes(32));
        return $this;
    }

    public function getUnsubscribeUrl(): string
    {
        // ✅ Corrigé : on utilise bien le token de désabonnement
        return sprintf('http://127.0.0.1:8000/newsletter/unsubscribe/%s', $this->unsubscribeToken);
    }

    public function __toString(): string
    {
        return $this->email ?? 'Abonné sans email';
    }
}
