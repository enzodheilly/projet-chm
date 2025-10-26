<?php

namespace App\Entity;

use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsletterSubscriberRepository::class)]
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

    #[ORM\Column(type: "boolean")]
    private bool $isConfirmed = false;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $subscribedAt;

    #[ORM\OneToOne(inversedBy: 'newsletterSubscription', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    public function __construct()
    {
        $this->subscribedAt = new \DateTime();
    }

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
        $this->email = $email;
        return $this;
    }

    public function getSubscribedAt(): ?\DateTimeInterface
    {
        return $this->subscribedAt;
    }
    public function setSubscribedAt(\DateTimeInterface $date): self
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

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /* ===========================================================
     * 🔽 Ajouts pour personnalisation des newsletters 🔽
     * ===========================================================
     */

    public function getFirstname(): ?string
    {
        // Si le User lié existe, on retourne son prénom
        return $this->user?->getFirstname();
    }

    public function getLastname(): ?string
    {
        return $this->user?->getLastname();
    }

    public function getSubscriptionDate(): string
    {
        return $this->subscribedAt->format('d/m/Y');
    }

    public function getUnsubscribeUrl(): string
    {
        return sprintf('https://tondomaine.fr/newsletter/unsubscribe/%d', $this->id);
    }
}
