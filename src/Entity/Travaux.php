<?php

namespace App\Entity;

use App\Repository\StatutActiviteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\DependencyInjection\Container;
use App\Entity\Factures\FactureTravauxLigne;


/**
 * @ORM\Entity(repositoryClass="App\Repository\TravauxRepository")
 */
class Travaux
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $ville;

    /**
     * @ORM\Column(type="integer")
     */
    private $departement;

    /**
     * @ORM\Column(type="text")
     */
    private $adresse;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Enseigne", inversedBy="travauxes")
     */
    private $enseigne;

    /**
     * @ORM\Column(type="text")
     */
    private $typeIntervention;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $reference;

    /**
     * @ORM\Column(type="integer")
     */
    private $nombreTechNecessaire;

    /**
     * @ORM\Column(type="integer")
     */
    private $nombreJourNecessaire;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $suiviTravaux;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="travauxes")
     */
    private $techniciens;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\StatutActivite", inversedBy="travauxes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $statut;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateSaisie;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateDebutSouhaitee;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateDebutRetenue;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TravauxHoraireTechnicien", mappedBy="travaux")
     */
    private $travauxHoraireTechniciens;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="travauxs")
     */
    private $chargeAffaire;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Factures\FactureTravauxLigne", mappedBy="travauxOrigine", cascade={"persist", "remove"})
     */
    private $factureTravauxLigne;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $motifAnnule;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $commentaires;


    public function __construct()
    {
        $this->techniciens = new ArrayCollection();
        $this->dateSaisie = new \DateTime();
        $this->travauxHoraireTechniciens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): self
    {
        $this->ville = $ville;

        return $this;
    }

    public function getDepartement(): ?int
    {
        return $this->departement;
    }

    public function setDepartement(int $departement): self
    {
        $this->departement = $departement;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): self
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getEnseigne(): ?Enseigne
    {
        return $this->enseigne;
    }

    public function setEnseigne(?Enseigne $enseigne): self
    {
        $this->enseigne = $enseigne;

        return $this;
    }

    public function getTypeIntervention(): ?string
    {
        return $this->typeIntervention;
    }

    public function setTypeIntervention(string $typeIntervention): self
    {
        $this->typeIntervention = $typeIntervention;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getNombreTechNecessaire(): ?int
    {
        return $this->nombreTechNecessaire;
    }

    public function setNombreTechNecessaire(int $nombreTechNecessaire): self
    {
        $this->nombreTechNecessaire = $nombreTechNecessaire;

        return $this;
    }

    public function getNombreJourNecessaire(): ?int
    {
        return $this->nombreJourNecessaire;
    }

    public function setNombreJourNecessaire(int $nombreJourNecessaire): self
    {
        $this->nombreJourNecessaire = $nombreJourNecessaire;

        return $this;
    }

    public function getSuiviTravaux(): ?string
    {
        return $this->suiviTravaux;
    }

    public function setSuiviTravaux(string $suiviTravaux): self
    {
        $this->suiviTravaux = $suiviTravaux;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getTechniciens(): Collection
    {
        return $this->techniciens;
    }

    public function addTechnicien(User $technicien): self
    {
        if (!$this->techniciens->contains($technicien)) {
            $this->techniciens[] = $technicien;
        }

        return $this;
    }

    public function removeTechnicien(User $technicien): self
    {
        if ($this->techniciens->contains($technicien)) {
            $this->techniciens->removeElement($technicien);
        }

        return $this;
    }

    public function getStatut(): ?StatutActivite
    {
        return $this->statut;
    }

    public function setStatut(?StatutActivite $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateSaisie(): ?\DateTimeInterface
    {
        return $this->dateSaisie;
    }

    public function setDateSaisie(\DateTimeInterface $dateSaisie): self
    {
        $this->dateSaisie = $dateSaisie;

        return $this;
    }

    public function getDateDebutSouhaitee(): ?\DateTimeInterface
    {
        return $this->dateDebutSouhaitee;
    }

    public function setDateDebutSouhaitee(\DateTimeInterface $dateDebutSouhaitee): self
    {
        $this->dateDebutSouhaitee = $dateDebutSouhaitee;

        return $this;
    }

    public function getDateDebutRetenue(): ?\DateTimeInterface
    {
        return $this->dateDebutRetenue;
    }

    public function setDateDebutRetenue(?\DateTimeInterface $dateDebutRetenue): self
    {
        $this->dateDebutRetenue = $dateDebutRetenue;

        return $this;
    }

    /**
     * @return Collection|TravauxHoraireTechnicien[]
     */
    public function getTravauxHoraireTechniciens(): Collection
    {
        return $this->travauxHoraireTechniciens;
    }

    public function addTravauxHoraireTechnicien(TravauxHoraireTechnicien $travauxHoraireTechnicien): self
    {
        if (!$this->travauxHoraireTechniciens->contains($travauxHoraireTechnicien)) {
            $this->travauxHoraireTechniciens[] = $travauxHoraireTechnicien;
            $travauxHoraireTechnicien->setTravaux($this);
        }

        return $this;
    }

    public function removeTravauxHoraireTechnicien(TravauxHoraireTechnicien $travauxHoraireTechnicien): self
    {
        if ($this->travauxHoraireTechniciens->contains($travauxHoraireTechnicien)) {
            $this->travauxHoraireTechniciens->removeElement($travauxHoraireTechnicien);
            // set the owning side to null (unless already changed)
            if ($travauxHoraireTechnicien->getTravaux() === $this) {
                $travauxHoraireTechnicien->setTravaux(null);
            }
        }

        return $this;
    }

    public function getChargeAffaire(): ?User
    {
        return $this->chargeAffaire;
    }

    public function setChargeAffaire(?User $chargeAffaire): self
    {
        $this->chargeAffaire = $chargeAffaire;

        return $this;
    }

    public function getFactureTravauxLigne(): ?FactureTravauxLigne
    {
        return $this->factureTravauxLigne;
    }

    public function setFactureTravauxLigne(?FactureTravauxLigne $factureTravauxLigne): self
    {
        $this->factureTravauxLigne = $factureTravauxLigne;

        // set (or unset) the owning side of the relation if necessary
        $newTravauxOrigine = null === $factureTravauxLigne ? null : $this;
        if ($factureTravauxLigne->getTravauxOrigine() !== $newTravauxOrigine) {
            $factureTravauxLigne->setTravauxOrigine($newTravauxOrigine);
        }

        return $this;
    }

    public function getMotifAnnule(): ?string
    {
        return $this->motifAnnule;
    }

    public function setMotifAnnule(?string $motifAnnule): self
    {
        $this->motifAnnule = $motifAnnule;

        return $this;
    }

    public function getCommentaires(): ?string
    {
        return $this->commentaires;
    }

    public function setCommentaires(?string $commentaires): self
    {
        $this->commentaires = $commentaires;

        return $this;
    }


}
