<?php

namespace App\Entity;

use App\Repository\NewsletterCampaignRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsletterCampaignRepository::class)]
class NewsletterCampaign
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $subject;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $sentAt;

    #[ORM\Column(type: 'integer')]
    private int $recipientCount = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $isTest = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $sentBy = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $openCount = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $clickCount = 0;


    public function __construct()
    {
        $this->sentAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }
    public function setSentAt(\DateTimeImmutable $sentAt): self
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function getRecipientCount(): int
    {
        return $this->recipientCount;
    }
    public function setRecipientCount(int $count): self
    {
        $this->recipientCount = $count;
        return $this;
    }

    public function isTest(): bool
    {
        return $this->isTest;
    }
    public function setIsTest(bool $isTest): self
    {
        $this->isTest = $isTest;
        return $this;
    }

    public function getSentBy(): ?string
    {
        return $this->sentBy;
    }
    public function setSentBy(?string $sentBy): self
    {
        $this->sentBy = $sentBy;
        return $this;
    }

    public function getOpenCount(): int
    {
        return $this->openCount;
    }

    public function setOpenCount(int $openCount): self
    {
        $this->openCount = $openCount;
        return $this;
    }

    public function incrementOpenCount(): self
    {
        $this->openCount++;
        return $this;
    }

    public function getClickCount(): int
    {
        return $this->clickCount;
    }

    public function setClickCount(int $clickCount): self
    {
        $this->clickCount = $clickCount;
        return $this;
    }

    public function incrementClickCount(): self
    {
        $this->clickCount++;
        return $this;
    }
}
