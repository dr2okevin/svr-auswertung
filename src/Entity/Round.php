<?php

namespace App\Entity;

use App\Repository\RoundRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoundRepository::class)]
class Round
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Name = null;

    #[ORM\Column]
    private ?\DateTime $StartDate = null;

    #[ORM\Column]
    private ?\DateTime $EndDate = null;

    #[ORM\ManyToOne(inversedBy: 'rounds')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Competition $Competition = null;

    /**
     * @var Collection<int, Series>
     */
    #[ORM\OneToMany(targetEntity: Series::class, mappedBy: 'Round', orphanRemoval: true)]
    private Collection $series;

    public function __construct()
    {
        $this->series = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->Name;
    }

    public function setName(string $Name): static
    {
        $this->Name = $Name;

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->StartDate;
    }

    public function setStartDate(\DateTime $StartDate): static
    {
        $this->StartDate = $StartDate;

        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->EndDate;
    }

    public function setEndDate(\DateTime $EndDate): static
    {
        $this->EndDate = $EndDate;

        return $this;
    }

    public function getCompetition(): ?Competition
    {
        return $this->Competition;
    }

    public function setCompetition(?Competition $Competition): static
    {
        $this->Competition = $Competition;

        return $this;
    }

    /**
     * @return Collection<int, Series>
     */
    public function getSeries(): Collection
    {
        return $this->series;
    }

    public function addSeries(Series $series): static
    {
        if (!$this->series->contains($series)) {
            $this->series->add($series);
            $series->setRound($this);
        }

        return $this;
    }

    public function removeSeries(Series $series): static
    {
        if ($this->series->removeElement($series)) {
            // set the owning side to null (unless already changed)
            if ($series->getRound() === $this) {
                $series->setRound(null);
            }
        }

        return $this;
    }
}
