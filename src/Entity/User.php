<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: "App\Repository\UserRepository")]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est déjà utilisée.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(type: "json")]
    private array $roles = [];

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: "boolean")]
    private bool $isVerified = false;

    #[ORM\Column(type: "datetime_immutable")]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: "datetime_immutable")]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: "integer")]
    private int $failedAttempts = 0;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $lockedUntil = null;

    #[ORM\Column(type: "string", length: 6, nullable: true)]
    private ?string $verificationCode = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $verificationCodeExpiresAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $lastLoginIp = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastResetRequestAt = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: NewsletterSubscriber::class, cascade: ['persist', 'remove'])]
    private ?NewsletterSubscriber $newsletterSubscription = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $licenceNumber = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true, options: ["default" => "Inactive"])]
    private ?string $licenceStatus = 'Inactive';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $licenceEndDate = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Payment::class, orphanRemoval: true)]
    private Collection $payments;

    #[ORM\Column(type: "blob", nullable: true)]
    private $profileImage;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $profileImageMime = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $profileImageUpdatedAt = null;

    #[ORM\Column(type: "boolean")]
    private bool $acceptedTerms = false;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PasswordHistory::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $passwordHistories;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: SecurityLog::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $securityLogs;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Licence::class, orphanRemoval: true)]
    private Collection $licences;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->payments = new ArrayCollection();
        $this->licences = new ArrayCollection();
        $this->passwordHistories = new ArrayCollection();
        $this->securityLogs = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ========== GETTERS / SETTERS ==========

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void {}

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getFailedAttempts(): int
    {
        return $this->failedAttempts;
    }

    public function setFailedAttempts(int $failedAttempts): self
    {
        $this->failedAttempts = $failedAttempts;
        return $this;
    }

    public function getLockedUntil(): ?\DateTimeInterface
    {
        return $this->lockedUntil;
    }

    public function setLockedUntil(?\DateTimeInterface $lockedUntil): self
    {
        $this->lockedUntil = $lockedUntil;
        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeInterface $expiresAt): self
    {
        $this->resetTokenExpiresAt = $expiresAt;
        return $this;
    }

    public function getVerificationCode(): ?string
    {
        return $this->verificationCode;
    }

    public function setVerificationCode(?string $code): self
    {
        $this->verificationCode = $code;
        return $this;
    }

    public function getVerificationCodeExpiresAt(): ?\DateTimeInterface
    {
        return $this->verificationCodeExpiresAt;
    }

    public function setVerificationCodeExpiresAt(?\DateTimeInterface $expiresAt): self
    {
        $this->verificationCodeExpiresAt = $expiresAt;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $date): self
    {
        $this->lastLoginAt = $date;
        return $this;
    }

    public function getLastLoginIp(): ?string
    {
        return $this->lastLoginIp;
    }

    public function setLastLoginIp(?string $ip): self
    {
        $this->lastLoginIp = $ip;
        return $this;
    }

    public function getAcceptedTerms(): bool
    {
        return $this->acceptedTerms;
    }

    public function setAcceptedTerms(bool $acceptedTerms): self
    {
        $this->acceptedTerms = $acceptedTerms;
        return $this;
    }

    public function __toString(): string
    {
        return $this->email;
    }

    public function getLastResetRequestAt(): ?\DateTimeImmutable
    {
        return $this->lastResetRequestAt;
    }

    public function setLastResetRequestAt(?\DateTimeImmutable $lastResetRequestAt): self
    {
        $this->lastResetRequestAt = $lastResetRequestAt;
        return $this;
    }

    public function getNewsletterSubscription(): ?NewsletterSubscriber
    {
        return $this->newsletterSubscription;
    }

    public function setNewsletterSubscription(?NewsletterSubscriber $newsletterSubscription): self
    {
        if ($newsletterSubscription && $newsletterSubscription->getUser() !== $this) {
            $newsletterSubscription->setUser($this);
        }
        $this->newsletterSubscription = $newsletterSubscription;
        return $this;
    }

    public function getLicenceNumber(): ?string
    {
        return $this->licenceNumber;
    }

    public function setLicenceNumber(?string $licenceNumber): self
    {
        $this->licenceNumber = $licenceNumber;
        return $this;
    }

    public function getLicenceStatus(): ?string
    {
        return $this->licenceStatus;
    }

    public function setLicenceStatus(?string $licenceStatus): self
    {
        $this->licenceStatus = $licenceStatus;
        return $this;
    }

    public function getLicenceEndDate(): ?\DateTimeInterface
    {
        return $this->licenceEndDate;
    }

    public function setLicenceEndDate(?\DateTimeInterface $licenceEndDate): self
    {
        $this->licenceEndDate = $licenceEndDate;
        return $this;
    }

    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments[] = $payment;
            $payment->setUser($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payments->removeElement($payment)) {
            if ($payment->getUser() === $this) {
                $payment->setUser(null);
            }
        }

        return $this;
    }

    public function getProfileImage(): ?string
    {
        if ($this->profileImage === null) {
            return null;
        }

        if (is_resource($this->profileImage)) {
            return stream_get_contents($this->profileImage);
        }

        return $this->profileImage;
    }

    public function setProfileImage(?string $binary): self
    {
        $this->profileImage = $binary;
        return $this;
    }

    public function getProfileImageMime(): ?string
    {
        return $this->profileImageMime;
    }

    public function setProfileImageMime(?string $mime): self
    {
        $this->profileImageMime = $mime;
        return $this;
    }

    public function getProfileImageUpdatedAt(): ?\DateTimeInterface
    {
        return $this->profileImageUpdatedAt;
    }

    public function setProfileImageUpdatedAt(?\DateTimeInterface $date): self
    {
        $this->profileImageUpdatedAt = $date;
        return $this;
    }

    public function getProfileImageDataUrl(): ?string
    {
        if (!$this->profileImage || !$this->profileImageMime) {
            return null;
        }

        $data = null;

        // ✅ Si le champ est un flux (BLOB)
        if (is_resource($this->profileImage)) {
            // On revient au début du flux au cas où il aurait déjà été lu
            rewind($this->profileImage);
            $data = stream_get_contents($this->profileImage);
        } else {
            // Sinon, c’est déjà une chaîne binaire (VARBINARY)
            $data = $this->profileImage;
        }

        if (!$data) {
            return null;
        }

        return sprintf('data:%s;base64,%s', $this->profileImageMime, base64_encode($data));
    }

    public function getLicences(): Collection
    {
        return $this->licences;
    }

    public function addLicence(Licence $licence): self
    {
        if (!$this->licences->contains($licence)) {
            $this->licences[] = $licence;
            $licence->setUser($this);
        }

        return $this;
    }

    public function removeLicence(Licence $licence): self
    {
        if ($this->licences->removeElement($licence)) {
            if ($licence->getUser() === $this) {
                $licence->setUser(null);
            }
        }

        return $this;
    }

    public function getPasswordHistories(): Collection
    {
        return $this->passwordHistories;
    }

    public function addPasswordHistory(PasswordHistory $passwordHistory): self
    {
        if (!$this->passwordHistories->contains($passwordHistory)) {
            $this->passwordHistories[] = $passwordHistory;
            $passwordHistory->setUser($this);
        }

        return $this;
    }

    public function removePasswordHistory(PasswordHistory $passwordHistory): self
    {
        if ($this->passwordHistories->removeElement($passwordHistory)) {
            // set the owning side to null (unless already changed)
            if ($passwordHistory->getUser() === $this) {
                $passwordHistory->setUser(null);
            }
        }

        return $this;
    }

    public function getSecurityLogs(): Collection
    {
        return $this->securityLogs;
    }

    public function addSecurityLog(SecurityLog $log): self
    {
        if (!$this->securityLogs->contains($log)) {
            $this->securityLogs->add($log);
            $log->setUser($this);
        }

        return $this;
    }

    public function removeSecurityLog(SecurityLog $log): self
    {
        if ($this->securityLogs->removeElement($log)) {
            if ($log->getUser() === $this) {
                $log->setUser(null);
            }
        }

        return $this;
    }
}
