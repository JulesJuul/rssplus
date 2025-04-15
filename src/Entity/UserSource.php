<?php

namespace App\Entity;

use App\Repository\UserSourceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSourceRepository::class)]
class UserSource
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userSources')]
    #[ORM\JoinColumn(name: "userId", referencedColumnName: "id", nullable: false)]
    private ?User $user = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Source::class, inversedBy: 'userSources')]
    #[ORM\JoinColumn(name: "sourceId", referencedColumnName: "id", nullable: false)]
    private ?Source $source = null;

    #[ORM\Column(length: 255)]
    private ?string $customName = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getSource(): ?Source
    {
        return $this->source;
    }

    public function setSource(?Source $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function getCustomName(): ?string
    {
        return $this->customName;
    }

    public function setCustomName(string $customName): static
    {
        $this->customName = $customName;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
