<?php

namespace App\Entity;

use App\Repository\RatingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: "ratings")]  // Indique que cette entité correspond à la table "ratings"
class Rating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Orphelin::class)]
    #[ORM\JoinColumn(name: "id_orphelin", referencedColumnName: "idO", nullable: false)]
    private ?Orphelin $orphelin = null;

    #[ORM\ManyToOne(targetEntity: Cour::class)]
    #[ORM\JoinColumn(name: "id_cours", referencedColumnName: "idC", nullable: false)]
    private ?Cour $cours = null;

    #[ORM\Column(type: "integer")]
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $note = null;

    // Getters et setters
    public function getId(): ?int { return $this->id; }
    public function getOrphelin(): ?Orphelin { return $this->orphelin; }
    public function setOrphelin(?Orphelin $orphelin): self { $this->orphelin = $orphelin; return $this; }
    public function getCours(): ?Cour { return $this->cours; }
    public function setCours(?Cour $cours): self { $this->cours = $cours; return $this; }
    public function getNote(): ?int { return $this->note; }
    public function setNote(int $note): self { $this->note = $note; return $this; }
}

