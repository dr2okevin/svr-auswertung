<?php

namespace App\Entity;

use App\Enum\ScoringMode;
use App\Repository\DisciplineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DisciplineRepository::class)]
class Discipline
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Name = null;

    #[ORM\Column]
    private ?int $ShotsPerSeries = null;

    #[ORM\Column(enumType: ScoringMode::class)]
    private ?ScoringMode $ScoringMode = null;

    #[ORM\Column]
    private ?float $MaxScoresPerShot = null;

    /**
     * @var Collection<int, Series>
     */
    #[ORM\OneToMany(targetEntity: Series::class, mappedBy: 'Discipline', orphanRemoval: true)]
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

    public function getShotsPerSeries(): ?int
    {
        return $this->ShotsPerSeries;
    }

    public function setShotsPerSeries(int $ShotsPerSeries): static
    {
        $this->ShotsPerSeries = $ShotsPerSeries;

        return $this;
    }

    public function getScoringMode(): ?ScoringMode
    {
        return $this->ScoringMode;
    }

    public function setScoringMode(ScoringMode $ScoringMode): static
    {
        $this->ScoringMode = $ScoringMode;

        return $this;
    }

    public function getMaxScoresPerShot(): ?float
    {
        return $this->MaxScoresPerShot;
    }

    public function setMaxScoresPerShot(float $MaxScoresPerShot): static
    {
        $this->MaxScoresPerShot = $MaxScoresPerShot;

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
            $series->setDiscipline($this);
        }

        return $this;
    }

    public function removeSeries(Series $series): static
    {
        if ($this->series->removeElement($series)) {
            // set the owning side to null (unless already changed)
            if ($series->getDiscipline() === $this) {
                $series->setDiscipline(null);
            }
        }

        return $this;
    }
}
