<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\TuteurRepository;

#[ORM\Entity(repositoryClass: TuteurRepository::class)]
#[ORM\Table(name: 'tuteurs')]
class Tuteur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "idT",type: 'integer')]
    private ?int $idT = null;

    public function getIdT(): ?int
    {
        return $this->idT;
    }

    public function setIdT(int $idT): self
    {
        $this->idT = $idT;
        return $this;
    }

    #[ORM\Column(name: "cinT", type: "string", length: 8, nullable: false)]
    #[Assert\NotBlank(message: "Le CIN est obligatoire.")]
    #[Assert\Regex(
        pattern: "/^\d{8}$/",
        message: "Le CIN doit contenir exactement 8 chiffres."
    )]
    private ?string $cinT = null;

    public function getCinT(): ?string
    {
        return $this->cinT;
    }

    public function setCinT(string $cinT): self
    {
        $this->cinT = $cinT;
        return $this;
    }

    #[ORM\Column(name: "nomT", type: "string", length: 100, nullable: false)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(
        max: 100,
        maxMessage: "Le nom ne peut pas dépasser 100 caractères."
    )]
    private ?string $nomT = null;

    public function getNomT(): ?string
    {
        return $this->nomT;
    }

    public function setNomT(string $nomT): self
    {
        $this->nomT = $nomT;
        return $this;
    }

    #[ORM\Column(name: "prenomT", type: "string", length: 100, nullable: false)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire.")]
    #[Assert\Length(
        max: 100,
        maxMessage: "Le prénom ne peut pas dépasser 100 caractères."
    )]
    private ?string $prenomT = null;

    public function getPrenomT(): ?string
    {
        return $this->prenomT;
    }

    public function setPrenomT(string $prenomT): self
    {
        $this->prenomT = $prenomT;
        return $this;
    }

    #[ORM\Column(name: "telephoneT", type: "string", length: 8, nullable: true)]
    #[Assert\Regex(
        pattern: "/^\d{8}$/",
        message: "Le numéro de téléphone doit contenir exactement 8 chiffres."
    )]
    private ?string $telephoneT = null;

    public function getTelephoneT(): ?string
    {
        return $this->telephoneT;
    }

    public function setTelephoneT(?string $telephoneT): self
    {
        $this->telephoneT = $telephoneT;
        return $this;
    }

    #[ORM\Column(name: "adresseT", type: "string", length: 100, nullable: true)]
    #[Assert\Length(
        max: 100,
        maxMessage: "L'adresse ne peut pas dépasser 100 caractères."
    )]
    private ?string $adresseT = null;

    public function getAdresseT(): ?string
    {
        return $this->adresseT;
    }

    public function setAdresseT(?string $adresseT): self
    {
        $this->adresseT = $adresseT;
        return $this;
    }

    #[ORM\Column(name: "disponibilite", type: "string", columnDefinition: "ENUM('oui', 'non')", options: ["default" => "oui"], nullable: false)]
    #[Assert\Choice(choices: ["oui", "non"], message: "La disponibilité doit être 'oui' ou 'non'.")]
    private ?string $disponibilite = 'oui';

    public function getDisponibilite(): ?string
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(string $disponibilite): self
    {
        $this->disponibilite = $disponibilite;
        return $this;
    }

    #[ORM\Column(name: "email", type: "string", length: 255, nullable: false)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'email n'est pas valide.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "L'email ne peut pas dépasser 255 caractères."
    )]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Cour::class, mappedBy: 'tuteur')]
    private Collection $cours;

    /**
     * @return Collection<int, Cour>
     */
    public function getCours(): Collection
    {
        if (!$this->cours instanceof Collection) {
            $this->cours = new ArrayCollection();
        }
        return $this->cours;
    }

    public function addCour(Cour $cour): self
    {
        if (!$this->getCours()->contains($cour)) {
            $this->getCours()->add($cour);
        }
        return $this;
    }

    public function removeCour(Cour $cour): self
    {
        $this->getCours()->removeElement($cour);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Orphelin::class, mappedBy: 'tuteur')]
    private Collection $orphelins;

    public function __construct()
    {
        $this->cours = new ArrayCollection();
        $this->orphelins = new ArrayCollection();
    }

    /**
     * @return Collection<int, Orphelin>
     */
    public function getOrphelins(): Collection
    {
        if (!$this->orphelins instanceof Collection) {
            $this->orphelins = new ArrayCollection();
        }
        return $this->orphelins;
    }

    public function addOrphelin(Orphelin $orphelin): self
    {
        if (!$this->getOrphelins()->contains($orphelin)) {
            $this->getOrphelins()->add($orphelin);
        }
        return $this;
    }

    public function removeOrphelin(Orphelin $orphelin): self
    {
        $this->getOrphelins()->removeElement($orphelin);
        return $this;
    }

}
