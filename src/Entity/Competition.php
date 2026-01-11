<?php

namespace App\Entity;

use App\Repository\CompetitionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use \App\Enum\CompetitionType;

#[ORM\Entity(repositoryClass: CompetitionRepository::class)]
class Competition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Name = null;

    #[ORM\Column(enumType: CompetitionType::class)]
    private ?CompetitionType $Type = null;

    #[ORM\Column]
    private ?\DateTime $StartTime = null;

    #[ORM\Column]
    private ?\DateTime $EndTime = null;

    /**
     * @var Collection<int, Round>
     */
    #[ORM\OneToMany(targetEntity: Round::class, mappedBy: 'Competition', orphanRemoval: true)]
    private Collection $rounds;

    public function __construct()
    {
        $this->rounds = new ArrayCollection();
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

    public function getType(): ?CompetitionType
    {
        return $this->Type;
    }

    public function setType(CompetitionType $Type): static
    {
        $this->Type = $Type;

        return $this;
    }

    public function getStartTime(): ?\DateTime
    {
        return $this->StartTime;
    }

    public function setStartTime(\DateTime $StartTime): static
    {
        $this->StartTime = $StartTime;

        return $this;
    }

    public function getEndTime(): ?\DateTime
    {
        return $this->EndTime;
    }

    public function setEndTime(\DateTime $EndTime): static
    {
        $this->EndTime = $EndTime;

        return $this;
    }

    /**
     * @return Collection<int, Round>
     */
    public function getRounds(): Collection
    {
        return $this->rounds;
    }

    public function addRound(Round $round): static
    {
        if (!$this->rounds->contains($round)) {
            $this->rounds->add($round);
            $round->setCompetition($this);
        }

        return $this;
    }

    public function removeRound(Round $round): static
    {
        if ($this->rounds->removeElement($round)) {
            // set the owning side to null (unless already changed)
            if ($round->getCompetition() === $this) {
                $round->setCompetition(null);
            }
        }

        return $this;
    }
}
