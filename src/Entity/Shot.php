<?php

namespace App\Entity;

use App\Repository\ShotRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShotRepository::class)]
class Shot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'shots')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Series $Series = null;

    #[ORM\Column]
    private ?int $ShotIndex = null;

    #[ORM\Column]
    private ?float $value = null;

    #[ORM\Column(nullable: true)]
    private ?float $XPosition = null;

    #[ORM\Column(nullable: true)]
    private ?float $YPosition = null;

    #[ORM\Column]
    private ?\DateTime $RecordTime = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeries(): ?Series
    {
        return $this->Series;
    }

    public function setSeries(?Series $Series): static
    {
        $this->Series = $Series;

        return $this;
    }

    public function getShotIndex(): ?int
    {
        return $this->ShotIndex;
    }

    public function setShotIndex(int $ShotIndex): static
    {
        $this->ShotIndex = $ShotIndex;

        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getXPosition(): ?float
    {
        return $this->XPosition;
    }

    public function setXPosition(?float $XPosition): static
    {
        $this->XPosition = $XPosition;

        return $this;
    }

    public function getYPosition(): ?float
    {
        return $this->YPosition;
    }

    public function setYPosition(?float $YPosition): static
    {
        $this->YPosition = $YPosition;

        return $this;
    }

    public function getRecordTime(): ?\DateTime
    {
        return $this->RecordTime;
    }

    public function setRecordTime(\DateTime $RecordTime): static
    {
        $this->RecordTime = $RecordTime;

        return $this;
    }
}
