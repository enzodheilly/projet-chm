<?php

namespace App\Entity;

use App\Repository\BlockedIpRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BlockedIpRepository::class)]
class BlockedIp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 45)]
    private ?string $ip = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column]
    private \DateTimeImmutable $blockedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    public function __construct(string $ip, ?string $reason = null, ?\DateTimeImmutable $expiresAt = null)
    {
        $this->ip = $ip;
        $this->reason = $reason;
        $this->blockedAt = new \DateTimeImmutable();
        $this->expiresAt = $expiresAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }
    public function setIp(string $ip): self
    {
        $this->ip = $ip;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }
    public function setReason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getBlockedAt(): \DateTimeImmutable
    {
        return $this->blockedAt;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }
    public function setExpiresAt(?\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt && $this->expiresAt < new \DateTimeImmutable();
    }
}
