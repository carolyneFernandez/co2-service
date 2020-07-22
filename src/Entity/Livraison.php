<?php

namespace App\Entity;

use App\Entity\Factures\FactureLivraisonLigne;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LivraisonRepository")
 */
class Livraison
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\VilleLivraison")
     * @ORM\JoinColumn(nullable=false)
     */
    private $villeDepart;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\VilleLivraison")
     * @ORM\JoinColumn(nullable=false)
     */
    private $villeArrivee;

    /**
     * @ORM\Column(type="text")
     */
    private $adresseDepart;

    /**
     * @ORM\Column(type="text")
     */
    private $adresseArrivee;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $materielTransporte;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="livraisons")
     * @ORM\JoinColumn(nullable=false)
     */
    private $chargeAffaire;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\StatutActivite", inversedBy="livraisons")
     * @ORM\JoinColumn(nullable=false)
     */
    private $statut;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isFacture;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Enseigne", inversedBy="departLivraison")
     * @ORM\JoinColumn(nullable=false)
     */
    private $enseigneDepart;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Enseigne", inversedBy="receptionLivraison")
     * @ORM\JoinColumn(nullable=false)
     */
    private $enseigneArrivee;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="livraisonsTechnicien")
     */
    private $techniciens;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateReleve;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateLivraison;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Factures\FactureLivraisonLigne", mappedBy="livraisonOrigine", cascade={"persist", "remove"})
     */
    private $factureLivraisonLigne;

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
        $this->isFacture = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVilleDepart(): ?VilleLivraison
    {
        return $this->villeDepart;
    }

    public function setVilleDepart(?VilleLivraison $villeDepart): self
    {
        $this->villeDepart = $villeDepart;

        return $this;
    }

    public function getVilleArrivee(): ?VilleLivraison
    {
        return $this->villeArrivee;
    }

    public function setVilleArrivee(?VilleLivraison $villeArrivee): self
    {
        $this->villeArrivee = $villeArrivee;

        return $this;
    }

    public function getAdresseDepart(): ?string
    {
        return $this->adresseDepart;
    }

    public function setAdresseDepart(string $adresseDepart): self
    {
        $this->adresseDepart = $adresseDepart;

        return $this;
    }

    public function getAdresseArrivee(): ?string
    {
        return $this->adresseArrivee;
    }

    public function setAdresseArrivee(string $adresseArrivee): self
    {
        $this->adresseArrivee = $adresseArrivee;

        return $this;
    }

    public function getMaterielTransporte(): ?string
    {
        return $this->materielTransporte;
    }

    public function setMaterielTransporte(?string $materielTransporte): self
    {
        $this->materielTransporte = $materielTransporte;

        return $this;
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

    public function getStatut(): ?StatutActivite
    {
        return $this->statut;
    }

    public function setStatut(?StatutActivite $statut): self
    {
        $this->statut = $statut;

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

    public function getEnseigneDepart(): ?Enseigne
    {
        return $this->enseigneDepart;
    }

    public function setEnseigneDepart(?Enseigne $enseigneDepart): self
    {
        $this->enseigneDepart = $enseigneDepart;

        return $this;
    }

    public function getEnseigneArrivee(): ?Enseigne
    {
        return $this->enseigneArrivee;
    }

    public function setEnseigneArrivee(?Enseigne $enseigneArrivee): self
    {
        $this->enseigneArrivee = $enseigneArrivee;

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

    public function getDateReleve(): ?\DateTimeInterface
    {
        return $this->dateReleve;
    }

    public function setDateReleve(?\DateTimeInterface $dateReleve): self
    {
        $this->dateReleve = $dateReleve;

        return $this;
    }

    public function getFactureLivraisonLigne(): ?FactureLivraisonLigne
    {
        return $this->factureLivraisonLigne;
    }

    public function setFactureLivraisonLigne(?FactureLivraisonLigne $factureLivraisonLigne): self
    {
        $this->factureLivraisonLigne = $factureLivraisonLigne;

        // set (or unset) the owning side of the relation if necessary
        $newLivraisonOrigine = null === $factureLivraisonLigne ? null : $this;
        if ($factureLivraisonLigne->getLivraisonOrigine() !== $newLivraisonOrigine) {
            $factureLivraisonLigne->setLivraisonOrigine($newLivraisonOrigine);
        }

        return $this;
    }

    public function getDateLivraison(): ?\DateTimeInterface
    {
        return $this->dateLivraison;
    }

    public function setDateLivraison(?\DateTimeInterface $dateLivraison): self
    {
        $this->dateLivraison = $dateLivraison;

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
