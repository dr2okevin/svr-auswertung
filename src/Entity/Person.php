<?php

namespace App\Entity;

use App\Repository\PersonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonRepository::class)]
class Person
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $FristName = null;

    #[ORM\Column(length: 255)]
    private ?string $LastName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $Birthdate = null;

    #[ORM\Column]
    private ?bool $Professional = null;

    /**
     * @var Collection<int, Series>
     */
    #[ORM\OneToMany(targetEntity: Series::class, mappedBy: 'Person', orphanRemoval: true)]
    private Collection $series;

    public function __construct()
    {
        $this->series = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFristName(): ?string
    {
        return $this->FristName;
    }

    public function setFristName(string $FristName): static
    {
        $this->FristName = $FristName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->LastName;
    }

    public function setLastName(string $LastName): static
    {
        $this->LastName = $LastName;

        return $this;
    }

    public function getBirthdate(): ?\DateTime
    {
        return $this->Birthdate;
    }

    public function setBirthdate(?\DateTime $Birthdate): static
    {
        $this->Birthdate = $Birthdate;

        return $this;
    }

    public function isProfessional(): ?bool
    {
        return $this->Professional;
    }

    public function setProfessional(bool $Professional): static
    {
        $this->Professional = $Professional;

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
            $series->setPerson($this);
        }

        return $this;
    }

    public function removeSeries(Series $series): static
    {
        if ($this->series->removeElement($series)) {
            // set the owning side to null (unless already changed)
            if ($series->getPerson() === $this) {
                $series->setPerson(null);
            }
        }

        return $this;
    }
}
