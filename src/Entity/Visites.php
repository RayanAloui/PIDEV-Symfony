<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use App\Entity\Incidents;

#[ORM\Entity]
class Visites
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "visitess")]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Assert\NotBlank(message: "L'utilisateur est requis.")]
    private User $id_user;

    #[ORM\Column(type: "date")]
    #[Assert\NotBlank(message: "La date de la visite est requise.")]
    #[Assert\Type(type: \DateTimeInterface::class, message: "Format de date invalide.")]
    private \DateTimeInterface $date;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "L'heure est requise.")]
    #[Assert\Regex(pattern: "/^([01]\d|2[0-3]):([0-5]\d)$/", message: "Format d'heure invalide (HH:MM).")]
    private string $heure;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le motif ne peut pas être vide.")]
    #[Assert\Length(min: 5, minMessage: "Le motif doit contenir au moins 5 caractères.")]
    private string $motif;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\Choice(choices: ["En attente", "Confirmée", "Annulée", "Terminée"], message: "Statut invalide.")]
    private string $statut;

    #[ORM\OneToMany(mappedBy: "id_visite", targetEntity: Incidents::class)]
    private Collection $incidentss;

    // Getters et Setters
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

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($value)
    {
        $this->date = $value;
    }

    public function getHeure()
    {
        return $this->heure;
    }

    public function setHeure($value)
    {
        $this->heure = $value;
    }

    public function getMotif()
    {
        return $this->motif;
    }

    public function setMotif($value)
    {
        $this->motif = $value;
    }

    public function getStatut()
    {
        return $this->statut;
    }

    public function setStatut($value)
    {
        $this->statut = $value;
    }
}
