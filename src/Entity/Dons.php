<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Dons
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_don;

    #[ORM\ManyToOne(targetEntity: Events::class, inversedBy: "dons")]
    #[ORM\JoinColumn(name: "id_event", referencedColumnName: "id_event", onDelete: "CASCADE")]
    private ?Events $id_event = null;

    #[ORM\Column(type: "float")]
    #[Assert\NotBlank(message: "Le montant du don est obligatoire.")]
    #[Assert\Positive(message: "Le montant doit être un nombre positif.")]
    private float $montant;

    #[ORM\Column(type: "string", length: 50)]
    #[Assert\NotBlank(message: "Le type de don est obligatoire.")]
    #[Assert\Length(
        max: 50,
        maxMessage: "Le type de don ne doit pas dépasser {{ limit }} caractères."
    )]
    private string $type_don;

    #[ORM\Column(type: "date")]
    #[Assert\NotBlank(message: "La date du don est obligatoire.")]
    #[Assert\LessThanOrEqual("today", message: "La date du don ne peut pas être dans le futur.")]
   private \DateTimeInterface $date_don;

    #[ORM\Column(type: "text")]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(
        min: 10,
        minMessage: "La description doit contenir au moins {{ limit }} caractères."
    )]
    
    private string $description;

    

    

    public function getIdDon(): int
    {
        return $this->id_don;
    }

    public function setIdDon(int $id_don): self
    {
        $this->id_don = $id_don;
        return $this;
    }

    public function getIdEvent(): ?Events
    {
        return $this->id_event;
    }

    public function setIdEvent(?Events $id_event): self
    {
        $this->id_event = $id_event;
        return $this;
    }

    public function getMontant(): float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): self
    {
        $this->montant = $montant;
        return $this;
    }

    public function getTypeDon(): string
    {
        return $this->type_don;
    }

    public function setTypeDon(string $type_don): self
    {
        $this->type_don = $type_don;
        return $this;
    }

    public function getDateDon(): \DateTimeInterface
    {
        return $this->date_don;
    }

    public function setDateDon(\DateTimeInterface $date_don): self
    {
        $this->date_don = $date_don;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }
    public function __construct()
    {
        $this->date_don = new \DateTime();
         // Valeur par défaut pour éviter le null
    }

    
    
}