<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Visites;

#[ORM\Entity]
class Incidents
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue(strategy: "AUTO")] 
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "incidentss")]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "L'utilisateur est obligatoire")]
    private User $id_user;

    #[ORM\ManyToOne(targetEntity: Visites::class, inversedBy: "incidentss")]
    #[ORM\JoinColumn(name: 'id_visite', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "La visite est obligatoire")]
    private Visites $id_visite;

    #[ORM\Column(type: "text")]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[Assert\Length(max: 255, maxMessage: "La description ne peut pas dépasser 255 caractères")]
    private string $description='';

    #[ORM\Column(type: "date")]
    #[Assert\NotNull(message: "La date de l'incident est obligatoire")]
    private ?\DateTimeInterface $dateincident = null;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getId_user()
    {
        return $this->id_user;
    }

    public function setId_user($value)
    {
        $this->id_user = $value;
    }

    public function getId_visite()
    {
        return $this->id_visite;
    }

    public function setId_visite($value)
    {
        $this->id_visite = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getDateincident()
    {
        return $this->dateincident;
    }

    public function setDateincident($value)
    {
        $this->dateincident = $value;
    }
}
