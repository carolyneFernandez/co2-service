<?php

namespace App\Entity;

use App\Entity\Factures\FactureDepannageLigne;
use App\Entity\Factures\FactureLivraisonLigne;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DepannageRepository")
 */
class Depannage
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
     * @ORM\Column(type="text")
     */
    private $adresse;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $typeIntervention;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $reference;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateSaisie;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateSouhaitee;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateRetenue;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="depannages")
     * @ORM\JoinColumn(nullable=false)
     */
    private $chargeAffaire;

    /**
     * @ORM\Column(type="boolean", options={"default"=0})
     */
    private $isFacture = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Enseigne", inversedBy="depannages")
     * @ORM\JoinColumn(nullable=false)
     */
    private $enseigne;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="depannagesTechnichiens")
     */
    private $techniciens;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Factures\FactureDepannageLigne", mappedBy="depannageOrigine", cascade={"persist", "remove"})
     */
    private $factureDepannageLigne;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\StatutActivite", inversedBy="depannages")
     * @ORM\JoinColumn(nullable=false)
     */
    private $statut;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $motifAnnule;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\DepannageHoraireTechnicien", mappedBy="depannage")
     */
    private $depannageHoraireTechniciens;

    /**
     * @ORM\OneToMany(targetEntity=DepannageBonIntervention::class, mappedBy="depannage", orphanRemoval=true)
     */
    private $depannageBonInterventions;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $commentaires;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $codePostal;


    public function __construct()
    {
        $this->techniciens = new ArrayCollection();
        $this->depannageHoraireTechniciens = new ArrayCollection();
        $this->dateSaisie = new \DateTime();
        $this->depannageBonInterventions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

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

    public function getDateSouhaitee(): ?\DateTimeInterface
    {
        return $this->dateSouhaitee;
    }

    public function setDateSouhaitee(\DateTimeInterface $dateSouhaitee): self
    {
        $this->dateSouhaitee = $dateSouhaitee;

        return $this;
    }

    public function getDateRetenue(): ?\DateTimeInterface
    {
        return $this->dateRetenue;
    }

    public function setDateRetenue(?\DateTimeInterface $dateRetenue): self
    {
        $this->dateRetenue = $dateRetenue;

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

    public function getIsFacture(): ?bool
    {
        return $this->isFacture;
    }

    public function setIsFacture(bool $isFacture): self
    {
        $this->isFacture = $isFacture;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVille()
    {
        return $this->ville;
    }

    /**
     * @param mixed $ville
     */
    public function setVille($ville): void
    {
        $this->ville = $ville;
    }

    /**
     * @return mixed
     */
    public function getAdresse()
    {
        return $this->adresse;
    }

    /**
     * @param mixed $adresse
     */
    public function setAdresse($adresse): void
    {
        $this->adresse = $adresse;
    }

    /**
     * @return mixed
     */
    public function getEnseigne()
    {
        return $this->enseigne;
    }

    /**
     * @param mixed $enseigne
     */
    public function setEnseigne($enseigne): void
    {
        $this->enseigne = $enseigne;
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
     * @return mixed
     */
    public function getTypeIntervention()
    {
        return $this->typeIntervention;
    }

    /**
     * @param mixed $typeIntervention
     * @return Depannage
     */
    public function setTypeIntervention($typeIntervention)
    {
        $this->typeIntervention = $typeIntervention;

        return $this;
    }

    public function getFactureDepannageLigne(): ?FactureDepannageLigne
    {
        return $this->factureDepannageLigne;
    }

    public function setFactureDepannageLigne(?FactureDepannageLigne $factureDepannageLigne): self
    {
        $this->factureDepannageLigne = $factureDepannageLigne;

        // set (or unset) the owning side of the relation if necessary
        $newDepannageOrigine = null === $factureDepannageLigne ? null : $this;
        if ($factureDepannageLigne->getDepannageOrigine() !== $newDepannageOrigine) {
            $factureDepannageLigne->setDepannageOrigine($newDepannageOrigine);
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

    public function getMotifAnnule(): ?string
    {
        return $this->motifAnnule;
    }

    public function setMotifAnnule(?string $motifAnnule): self
    {
        $this->motifAnnule = $motifAnnule;

        return $this;
    }

    /**
     * @return Collection|DepannageHoraireTechnicien[]
     */
    public function getDepannageHoraireTechniciens(): Collection
    {
        return $this->depannageHoraireTechniciens;
    }

    public function addDepannageHoraireTechnicien(DepannageHoraireTechnicien $depannageHoraireTechnicien): self
    {
        if (!$this->depannageHoraireTechniciens->contains($depannageHoraireTechnicien)) {
            $this->depannageHoraireTechniciens[] = $depannageHoraireTechnicien;
            $depannageHoraireTechnicien->setDepannage($this);
        }

        return $this;
    }

    public function removeDepannageHoraireTechnicien(DepannageHoraireTechnicien $depannageHoraireTechnicien): self
    {
        if ($this->depannageHoraireTechniciens->contains($depannageHoraireTechnicien)) {
            $this->depannageHoraireTechniciens->removeElement($depannageHoraireTechnicien);
            // set the owning side to null (unless already changed)
            if ($depannageHoraireTechnicien->getDepannage() === $this) {
                $depannageHoraireTechnicien->setDepannage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DepannageBonIntervention[]
     */
    public function getDepannageBonInterventions(): Collection
    {
        return $this->depannageBonInterventions;
    }

    public function addDepannageBonIntervention(DepannageBonIntervention $depannageBonIntervention): self
    {
        if (!$this->depannageBonInterventions->contains($depannageBonIntervention)) {
            $this->depannageBonInterventions[] = $depannageBonIntervention;
            $depannageBonIntervention->setDepannage($this);
        }

        return $this;
    }

    public function removeDepannageBonIntervention(DepannageBonIntervention $depannageBonIntervention): self
    {
        if ($this->depannageBonInterventions->contains($depannageBonIntervention)) {
            $this->depannageBonInterventions->removeElement($depannageBonIntervention);
            // set the owning side to null (unless already changed)
            if ($depannageBonIntervention->getDepannage() === $this) {
                $depannageBonIntervention->setDepannage(null);
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


}
