<?php

namespace App\Entity;

use App\Repository\UserRouletteGameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRouletteGameRepository::class)]
class UserRouletteGame
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Roulette $roulette = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isWin = null;

    #[ORM\Column]
    private ?float $value = null;

    #[ORM\Column(length: 255)]
    private ?string $side = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getRoulette(): ?Roulette
    {
        return $this->roulette;
    }

    public function setRoulette(?Roulette $roulette): self
    {
        $this->roulette = $roulette;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getIsWin(): ?bool
    {
        return $this->isWin;
    }

    public function setIsWin(?bool $isWin): self
    {
        $this->isWin = $isWin;

        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getSide(): ?string
    {
        return $this->side;
    }

    public function setSide(string $side): self
    {
        $this->side = $side;

        return $this;
    }
}
