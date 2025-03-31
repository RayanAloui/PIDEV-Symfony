<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\DonRepository;

#[ORM\Entity(repositoryClass: DonRepository::class)]
#[ORM\Table(name: 'dons')]
class Don
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Donateur::class, inversedBy: 'dons')]
    #[ORM\JoinColumn(name: 'donateur_id', referencedColumnName: 'id')]
    private ?Donateur $donateur = null;

    public function getDonateur(): ?Donateur
    {
        return $this->donateur;
    }

    public function setDonateur(?Donateur $donateur): self
    {
        $this->donateur = $donateur;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $montant = null;

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): self
    {
        $this->montant = $montant;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date_don = null;

    public function getDate_don(): ?\DateTimeInterface
    {
        return $this->date_don;
    }

    public function setDate_don(\DateTimeInterface $date_don): self
    {
        $this->date_don = $date_don;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $type_don = null;

    public function getType_don(): ?string
    {
        return $this->type_don;
    }

    public function setType_don(string $type_don): self
    {
        $this->type_don = $type_don;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $statut = null;

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getDateDon(): ?\DateTimeInterface
    {
        return $this->date_don;
    }

    public function setDateDon(\DateTimeInterface $date_don): static
    {
        $this->date_don = $date_don;

        return $this;
    }

    public function getTypeDon(): ?string
    {
        return $this->type_don;
    }

    public function setTypeDon(string $type_don): static
    {
        $this->type_don = $type_don;

        return $this;
    }

}
