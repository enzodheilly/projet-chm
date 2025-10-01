<?php

// src/Entity/Subscriber.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Subscriber
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 180, unique: true)]
    private $email;

    #[ORM\Column(type: "boolean")]
    private $isConfirmed = false;


    // Getters et setters
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

    public function getIsConfirmed(): bool
    {
        return $this->isConfirmed;
    }

    public function setIsConfirmed(bool $status): self
    {
        $this->isConfirmed = $status;
        return $this;
    }
}
