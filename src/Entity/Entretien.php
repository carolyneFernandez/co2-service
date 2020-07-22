<?php

namespace App\Entity;

use App\Entity\Factures\FactureEntretienLigne;
use App\Entity\Factures\FactureLivraisonLigne;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EntretienRepository")
 */
class Entretien
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateDebut;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateFin;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $numeroContrat;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $adresse;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\StatutActivite", inversedBy="entretiens")
     * @ORM\JoinColumn(nullable=false)
     */
    private $statut;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Enseigne", inversedBy="entretiens")
     * @ORM\JoinColumn(nullable=false)
     */
    private $enseigne;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="entretiens")
     * @ORM\JoinColumn(nullable=false)
     */
    private $chargeAffaire;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="entretiensTechnichiens")
     */
    private $techniciens;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\EntretienHoraireTechnicien", mappedBy="entretien")
     */
    private $entretienHoraireTechniciens;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Factures\FactureEntretienLigne", mappedBy="entretienOrigine", cascade={"persist", "remove"})
     */
    private $factureEntretienLigne;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $motifAnnule;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateSaisie;

    /**
     * @ORM\OneToMany(targetEntity=EntretienBonIntervention::class, mappedBy="entretien", orphanRemoval=true)
     */
    private $entretienBonInterventions;

    /**
     * @ORM\OneToMany(targetEntity=EntretienFicheIntervention::class, mappedBy="entretien", orphanRemoval=true)
     */
    private $entretienFicheInterventions;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $commentaires;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $codePostal;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ville;


    public function __construct()
    {
        $this->techniciens = new ArrayCollection();
        $this->entretienHoraireTechniciens = new ArrayCollection();
        $this->dateSaisie = new \DateTime();
        $this->entretienBonInterventions = new ArrayCollection();
        $this->entretienFicheInterventions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getNumeroContrat(): ?string
    {
        return $this->numeroContrat;
    }

    public function setNumeroContrat(string $numeroContrat): self
    {
        $this->numeroContrat = $numeroContrat;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;

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

    public function getEnseigne(): ?Enseigne
    {
        return $this->enseigne;
    }

    public function setEnseigne(?Enseigne $enseigne): self
    {
        $this->enseigne = $enseigne;

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

    /**
     * @return Collection|EntretienHoraireTechnicien[]
     */
    public function getEntretienHoraireTechniciens(): Collection
    {
        return $this->entretienHoraireTechniciens;
    }

    public function addEntretienHoraireTechnicien(EntretienHoraireTechnicien $entretienHoraireTechnicien): self
    {
        if (!$this->entretienHoraireTechniciens->contains($entretienHoraireTechnicien)) {
            $this->entretienHoraireTechniciens[] = $entretienHoraireTechnicien;
            $entretienHoraireTechnicien->setEntretien($this);
        }

        return $this;
    }

    public function removeEntretienHoraireTechnicien(EntretienHoraireTechnicien $entretienHoraireTechnicien): self
    {
        if ($this->entretienHoraireTechniciens->contains($entretienHoraireTechnicien)) {
            $this->entretienHoraireTechniciens->removeElement($entretienHoraireTechnicien);
            // set the owning side to null (unless already changed)
            if ($entretienHoraireTechnicien->getEntretien() === $this) {
                $entretienHoraireTechnicien->setEntretien(null);
            }
        }

        return $this;
    }

    public function getFactureEntretienLigne(): ?FactureEntretienLigne
    {
        return $this->factureEntretienLigne;
    }

    public function setFactureEntretienLigne(?FactureEntretienLigne $factureEntretienLigne): self
    {
        $this->factureEntretienLigne = $factureEntretienLigne;

        // set (or unset) the owning side of the relation if necessary
        $newEntretienOrigine = null === $factureEntretienLigne ? null : $this;
        if ($factureEntretienLigne->getEntretienOrigine() !== $newEntretienOrigine) {
            $factureEntretienLigne->setEntretienOrigine($newEntretienOrigine);
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

    public function getDateSaisie(): ?\DateTimeInterface
    {
        return $this->dateSaisie;
    }

    public function setDateSaisie(?\DateTimeInterface $dateSaisie): self
    {
        $this->dateSaisie = $dateSaisie;

        return $this;
    }

    /**
     * @return Collection|EntretienBonIntervention[]
     */
    public function getEntretienBonInterventions(): Collection
    {
        return $this->entretienBonInterventions;
    }

    public function addEntretienBonIntervention(EntretienBonIntervention $entretienBonIntervention): self
    {
        if (!$this->entretienBonInterventions->contains($entretienBonIntervention)) {
            $this->entretienBonInterventions[] = $entretienBonIntervention;
            $entretienBonIntervention->setEntretien($this);
        }

        return $this;
    }

    public function removeEntretienBonIntervention(EntretienBonIntervention $entretienBonIntervention): self
    {
        if ($this->entretienBonInterventions->contains($entretienBonIntervention)) {
            $this->entretienBonInterventions->removeElement($entretienBonIntervention);
            // set the owning side to null (unless already changed)
            if ($entretienBonIntervention->getEntretien() === $this) {
                $entretienBonIntervention->setEntretien(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|EntretienFicheIntervention[]
     */
    public function getEntretienFicheInterventions(): Collection
    {
        return $this->entretienFicheInterventions;
    }

    public function addEntretienFicheIntervention(EntretienFicheIntervention $entretienFicheIntervention): self
    {
        if (!$this->entretienFicheInterventions->contains($entretienFicheIntervention)) {
            $this->entretienFicheInterventions[] = $entretienFicheIntervention;
            $entretienFicheIntervention->setEntretien($this);
        }

        return $this;
    }

    public function removeEntretienFicheIntervention(EntretienFicheIntervention $entretienFicheIntervention): self
    {
        if ($this->entretienFicheInterventions->contains($entretienFicheIntervention)) {
            $this->entretienFicheInterventions->removeElement($entretienFicheIntervention);
            // set the owning side to null (unless already changed)
            if ($entretienFicheIntervention->getEntretien() === $this) {
                $entretienFicheIntervention->setEntretien(null);
            }
        }

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

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): self
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): self
    {
        $this->ville = $ville;

        return $this;
    }


}
