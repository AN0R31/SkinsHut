<?php

namespace App\Entity;

use App\Repository\RouletteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RouletteRepository::class)]
class Roulette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $filledAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column]
    private ?float $income = null;

    #[ORM\Column]
    private ?float $outcome = null;

    #[ORM\Column(nullable: true)]
    private ?int $winner = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFilledAt(): ?\DateTimeImmutable
    {
        return $this->filledAt;
    }

    public function setFilledAt(?\DateTimeImmutable $filledAt): self
    {
        $this->filledAt = $filledAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getIncome(): ?float
    {
        return $this->income;
    }

    public function setIncome(float $income): self
    {
        $this->income = $income;

        return $this;
    }

    public function getOutcome(): ?int
    {
        return $this->outcome;
    }

    public function setOutcome(int $outcome): self
    {
        $this->outcome = $outcome;

        return $this;
    }

    public function getWinner(): ?int
    {
        return $this->winner;
    }

    public function setWinner(?int $winner): self
    {
        $this->winner = $winner;

        return $this;
    }
}
