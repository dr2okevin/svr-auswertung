<?php

namespace App\Entity;

use App\Repository\TeamMemberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamMemberRepository::class)]
#[ORM\Table(name: 'team_member', uniqueConstraints: [new ORM\UniqueConstraint(name: 'team_member_unique_assignment', columns: ['team_id', 'person_id', 'discipline_id'])])]
class TeamMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'teamMembers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Team $Team = null;

    #[ORM\ManyToOne(inversedBy: 'teamMembers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Person $Person = null;

    #[ORM\ManyToOne(inversedBy: 'teamMembers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Discipline $Discipline = null;

    public function getId(): ?int
    {
        return $this->id;
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
}
