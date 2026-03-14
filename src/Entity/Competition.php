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

    #[ORM\Column(nullable: true)]
    private ?int $MaxTeamSize = null;

    /**
     * @var Collection<int, Round>
     */
    #[ORM\OneToMany(targetEntity: Round::class, mappedBy: 'Competition', orphanRemoval: true)]
    private Collection $rounds;

    /**
     * @var Collection<int, Discipline>
     */
    #[ORM\ManyToMany(targetEntity: Discipline::class, inversedBy: 'competitions')]
    #[ORM\JoinTable(name: 'competitions_disciplines_mm')]
    #[ORM\JoinColumn(name: 'competition', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'discipline', referencedColumnName: 'id')]
    private Collection $disciplines;

    /**
     * @var Collection<int, Team>
     */
    #[ORM\OneToMany(targetEntity: Team::class, mappedBy: 'Competition', orphanRemoval: true)]
    private Collection $teams;

    public function __construct()
    {
        $this->rounds = new ArrayCollection();
        $this->disciplines = new ArrayCollection();
        $this->teams = new ArrayCollection();
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

    public function getMaxTeamSize(): ?int
    {
        return $this->MaxTeamSize;
    }

    public function setMaxTeamSize(?int $MaxTeamSize): static
    {
        $this->MaxTeamSize = $MaxTeamSize;

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

    /**
     * @return Collection<int, Discipline>
     */
    public function getDisciplines(): Collection
    {
        return $this->disciplines;
    }

    public function addDiscipline(Discipline $discipline): static
    {
        if (!$this->disciplines->contains($discipline)) {
            $this->disciplines->add($discipline);
        }

        return $this;
    }

    public function removeDiscipline(Discipline $discipline): static
    {
        $this->disciplines->removeElement($discipline);

        return $this;
    }

    /**
     * @return Collection<int, Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): static
    {
        if (!$this->teams->contains($team)) {
            $this->teams->add($team);
            $team->setCompetition($this);
        }

        return $this;
    }

    public function removeTeam(Team $team): static
    {
        if ($this->teams->removeElement($team)) {
            if ($team->getCompetition() === $this) {
                $team->setCompetition(null);
            }
        }

        return $this;
    }
}
