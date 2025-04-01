<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Visites;

#[ORM\Entity]
class User
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $name;

    #[ORM\Column(type: "string", length: 255)]
    private string $surname;

    #[ORM\Column(type: "string", length: 255)]
    private string $telephone;

    #[ORM\Column(type: "string", length: 255)]
    private string $email;

    #[ORM\Column(type: "string", length: 255)]
    private string $password;

    #[ORM\Column(type: "string", length: 255)]
    private string $role;

    #[ORM\Column(type: "integer")]
    private int $token;

    #[ORM\Column(type: "string", length: 255)]
    private string $image;

    #[ORM\Column(type: "integer")]
    private int $is_blocked;

    #[ORM\Column(type: "integer")]
    private int $is_confirmed;

    #[ORM\Column(type: "integer")]
    private int $number_verification;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($value)
    {
        $this->name = $value;
    }

    public function getSurname()
    {
        return $this->surname;
    }

    public function setSurname($value)
    {
        $this->surname = $value;
    }

    public function getTelephone()
    {
        return $this->telephone;
    }

    public function setTelephone($value)
    {
        $this->telephone = $value;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($value)
    {
        $this->email = $value;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($value)
    {
        $this->password = $value;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setRole($value)
    {
        $this->role = $value;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($value)
    {
        $this->token = $value;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($value)
    {
        $this->image = $value;
    }

    public function getIs_blocked()
    {
        return $this->is_blocked;
    }

    public function setIs_blocked($value)
    {
        $this->is_blocked = $value;
    }

    public function getIs_confirmed()
    {
        return $this->is_confirmed;
    }

    public function setIs_confirmed($value)
    {
        $this->is_confirmed = $value;
    }

    public function getNumber_verification()
    {
        return $this->number_verification;
    }

    public function setNumber_verification($value)
    {
        $this->number_verification = $value;
    }

    #[ORM\OneToMany(mappedBy: "id_user", targetEntity: Incidents::class)]
    private Collection $incidentss;

        public function getIncidentss(): Collection
        {
            return $this->incidentss;
        }
    
        public function addIncidents(Incidents $incidents): self
        {
            if (!$this->incidentss->contains($incidents)) {
                $this->incidentss[] = $incidents;
                $incidents->setId_user($this);
            }
    
            return $this;
        }
    
        public function removeIncidents(Incidents $incidents): self
        {
            if ($this->incidentss->removeElement($incidents)) {
                // set the owning side to null (unless already changed)
                if ($incidents->getId_user() === $this) {
                    $incidents->setId_user(null);
                }
            }
    
            return $this;
        }

    #[ORM\OneToMany(mappedBy: "id_user", targetEntity: Visites::class)]
    private Collection $visitess;
}
