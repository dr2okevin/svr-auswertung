<?php

namespace App\Entity;

use App\Repository\SeriesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use \App\Entity\Person;
use \App\Entity\Discipline;
use \App\Entity\Round;
use \App\Entity\Team;

#[ORM\Entity(repositoryClass: SeriesRepository::class)]
class Series
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'series')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Person $Person = null;

    #[ORM\ManyToOne(inversedBy: 'series')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Discipline $Discipline = null;

    #[ORM\ManyToOne(inversedBy: 'series')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Round $Round = null;

    #[ORM\ManyToOne(inversedBy: 'series')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Team $Team = null;

    #[ORM\Column]
    private ?int $ShotsCount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ImportFile = null;

    /**
     * @var Collection<int, Shot>
     */
    #[ORM\OneToMany(targetEntity: Shot::class, mappedBy: 'Series', orphanRemoval: true)]
    private Collection $shots;

    public function __construct()
    {
        $this->shots = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPerson(): ?Person
    {
        return $this->Person;
    }

    public function setPerson(?Person $Person): static
    {
        $this->Person = $Person;

        return $this;
    }

    public function getDiscipline(): ?Discipline
    {
        return $this->Discipline;
    }

    public function setDiscipline(?Discipline $Discipline): static
    {
        $this->Discipline = $Discipline;

        return $this;
    }

    public function getRound(): ?Round
    {
        return $this->Round;
    }

    public function setRound(?Round $Round): static
    {
        $this->Round = $Round;

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->Team;
    }

    public function setTeam(?Team $Team): static
    {
        $this->Team = $Team;

        return $this;
    }

    public function getShotsCount(): ?int
    {
        return $this->ShotsCount;
    }

    public function setShotsCount(int $ShotsCount): static
    {
        $this->ShotsCount = $ShotsCount;

        return $this;
    }

    public function getImportFile(): ?string
    {
        return $this->ImportFile;
    }

    public function setImportFile(?string $ImportFile): static
    {
        $this->ImportFile = $ImportFile;

        return $this;
    }

    /**
     * @return Collection<int, Shot>
     */
    public function getShots(): Collection
    {
        return $this->shots;
    }

    public function addShot(Shot $shot): static
    {
        if (!$this->shots->contains($shot)) {
            $this->shots->add($shot);
            $shot->setSeries($this);
        }

        return $this;
    }

    public function removeShot(Shot $shot): static
    {
        if ($this->shots->removeElement($shot)) {
            // set the owning side to null (unless already changed)
            if ($shot->getSeries() === $this) {
                $shot->setSeries(null);
            }
        }

        return $this;
    }
}
