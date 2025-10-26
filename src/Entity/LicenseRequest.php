<?php

namespace App\Entity;

use App\Repository\LicenseRequestRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LicenseRequestRepository::class)]
class LicenseRequest
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_EXPIRED = 'EXPIRED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // L’utilisateur trouvable par nom/prénom/email
    #[ORM\ManyToOne(inversedBy: 'licenseRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?\App\Entity\User $user = null;

    #[ORM\Column(length: 128, unique: true)]
    private string $token;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $requesterIp = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        // Expire dans 15 minutes
        $this->expiresAt = (new \DateTimeImmutable())->modify('+15 minutes');
        $this->token = bin2hex(random_bytes(32));
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUser(): ?\App\Entity\User
    {
        return $this->user;
    }
    public function setUser(\App\Entity\User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }
    public function setToken(string $t): self
    {
        $this->token = $t;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
    public function setStatus(string $s): self
    {
        $this->status = $s;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }
    public function setConfirmedAt(?\DateTimeImmutable $d): self
    {
        $this->confirmedAt = $d;
        return $this;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }
    public function setExpiresAt(\DateTimeImmutable $d): self
    {
        $this->expiresAt = $d;
        return $this;
    }

    public function isExpired(): bool
    {
        return (new \DateTimeImmutable()) > $this->expiresAt;
    }

    public function getRequesterIp(): ?string
    {
        return $this->requesterIp;
    }
    public function setRequesterIp(?string $ip): self
    {
        $this->requesterIp = $ip;
        return $this;
    }
}
