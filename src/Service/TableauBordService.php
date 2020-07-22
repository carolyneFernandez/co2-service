<?php


namespace App\Service;


use App\Data\FiltreFactureDepannageData;
use App\Data\FiltreRappelBouteilleData;
use App\Entity\Client;
use App\Entity\Depannage;
use App\Entity\Entretien;
use App\Entity\Factures\Facture;
use App\Entity\Livraison;
use App\Entity\RappelBouteille;
use App\Entity\Travaux;
use App\Entity\User;
use Doctrine\ORM\Query;
use Swift_Mailer;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TableauBordService extends AbstractController
{

    /**
     * @param int $statut
     * @return array
     */
    public function findActiviteByStatut($statut, Client $client = null)
    {
        $activites = [];

        $em = $this->getDoctrine()
            ->getManager()
        ;

        /** @var Travaux[] $travauxes */
        $travauxes = $this->getTravaux($statut, $client);

        foreach ($travauxes as $travaux) {
            $activite = [
                'type' => 'travaux',
                'id' => $travaux->getId(),
                'chargeAffaire' => $travaux->getChargeAffaire(),
                'date' => $travaux->getDateDebutSouhaitee(),
                'dateRetenue' => $travaux->getDateDebutRetenue(),
                'enseigneOrigine' => $travaux->getEnseigne(),
                'dateDemande' => $travaux->getDateSaisie(),
                'adresse' => $travaux->getAdresse(),
            ];

            $activites[] = $activite;
        }

        // Dépannages
        /** @var Depannage[] $depannages */
        $depannages = $this->getDepannages($statut, $client);

        foreach ($depannages as $depannage) {
            $activite = [
                'type' => 'dépannage',
                'id' => $depannage->getId(),
                'chargeAffaire' => $depannage->getChargeAffaire(),
                'date' => $depannage->getDateSouhaitee(),
                'dateRetenue' => $depannage->getDateRetenue(),
                'enseigneOrigine' => $depannage->getEnseigne(),
                'dateDemande' => $depannage->getDateSaisie(),
                'adresse' => $depannage->getVille() . ', ' . $depannage->getAdresse(),
            ];

            $activites[] = $activite;
        }

        // Entretiens
        /** @var Entretien[] $entretiens */
        $entretiens = $this->getEntretiens($statut, $client);

        foreach ($entretiens as $entretien) {
            $activite = [
                'type' => 'entretien',
                'id' => $entretien->getId(),
                'chargeAffaire' => $entretien->getChargeAffaire(),
                'date' => $entretien->getDateDebut(),
                'dateRetenue' => $entretien->getDateDebut(),
                'enseigneOrigine' => $entretien->getEnseigne(),
                'dateDemande' => $entretien->getDateSaisie(),
                'adresse' => $entretien->getAdresse(),
            ];

            $activites[] = $activite;
        }


//        Livraisons
        /** @var Livraison[] $livraisons */
        $livraisons = $this->getLivraisons($statut, $client);

        foreach ($livraisons as $livraison) {
            $activite = [
                'type' => 'livraison',
                'id' => $livraison->getId(),
                'chargeAffaire' => $livraison->getChargeAffaire(),
                'date' => $livraison->getDateSouhaitee(),
                'dateRetenue' => $livraison->getDateRetenue(),
                'enseigneOrigine' => $livraison->getEnseigneDepart(),
                'dateDemande' => $livraison->getDateSaisie(),
                'adresse' => $livraison->getAdresseDepart(),
            ];

            $activites[] = $activite;
        }

        usort($activites, array(
            $this,
            'usortByDate'
        ));

//        dump($activites);

        return $activites;


    }


    public function getFactureImpayee(Client $client = null)
    {
        $factures = [];

        $em = $this->getDoctrine()
            ->getManager()
        ;


        if ($this->isGranted($this->getParameter('ROLE_ADMIN'))) {

            if ($client) {
                $chargesAffairesId = $client->getChargeAffairesId();
                $factures = $em->getRepository(Facture::class)
                    ->findBy([
                        'isPaye' => false,
                        'chargeAffaire' => $chargesAffairesId
                    ], ['date' => 'DESC'])
                ;
            } else {


                $factures = $em->getRepository(Facture::class)
                               ->findBy(['isPaye' => false], ['date' => 'DESC'])
                ;
            }

        } elseif ($this->isGranted($this->getParameter('ROLE_CLIENT'))) {
            $chargeAffaire = $this->getUser();
            $factures = $em->getRepository(Facture::class)
                           ->findBy([
                               'isPaye' => false,
                               'chargeAffaire' => $chargeAffaire
                           ], ['date' => 'DESC'])
            ;
        }

        return $factures;

    }

    public function getRappelBouteilleNonFactureByClient(Client $client)
    {
        $filtre = new FiltreRappelBouteilleData();
        $filtre->client = $client;
        $filtre->isFacture = false;

        /** @var Query $rappelsBouteilles */
        $rappelsBouteilles = $this->getDoctrine()
                                  ->getRepository(RappelBouteille::class)
                                  ->getQuery($filtre)
        ;

        return $rappelsBouteilles->getResult();

    }


    private function usortByDate($a, $b)
    {
        if ($a['date'] == $b['date']) {
            return 0;
        }

        return ($a['date'] < $b['date']) ? -1 : 1;

    }


    public function getTravaux($statut, Client $client = null)
    {
        $em = $this->getDoctrine()
                   ->getManager()
        ;

        // Travaux
        /** @var Travaux[] $travauxes */
        $travauxes = [];
        if ($this->isGranted($this->getParameter('ROLE_ADMIN'))) {

            if ($statut == $this->getParameter('STATUT_VALIDE')) {
                $travauxes = $em->getRepository(Travaux::class)
                    ->findValides($client)
                ;

            } elseif ($statut == $this->getParameter('STATUT_TERMINE')) {
                $travauxes = $em->getRepository(Travaux::class)
                    ->findTermines($client)
                ;
            } elseif ($statut == 'a facturer') {
                if ($client) {
                    $chargesId = $client->getChargeAffairesId();
                    $travauxes = $em->getRepository(Travaux::class)
                        ->findBy([
                            'statut' => $this->getParameter('STATUT_TERMINE'),
                            'chargeAffaire' => $chargesId
                        ])
                    ;

                } else {
                    $travauxes = $em->getRepository(Travaux::class)
                        ->findBy(['statut' => $this->getParameter('STATUT_TERMINE')])
                    ;

                }
            } else {
                if ($client) {
                    $chargesId = $client->getChargeAffairesId();
                    $travauxes = $em->getRepository(Travaux::class)
                        ->findBy([
                            'statut' => $statut,
                            'chargeAffaire' => $chargesId
                        ])
                    ;

                } else {
                    $travauxes = $em->getRepository(Travaux::class)
                        ->findBy(['statut' => $statut])
                    ;

                }
            }

        } elseif ($this->isGranted($this->getParameter('ROLE_CLIENT'))) {

            if ($statut == $this->getParameter('STATUT_VALIDE')) {
                $travauxes = $em->getRepository(Travaux::class)
                    ->findValidesByCA($this->getUser())
                ;

            } elseif ($statut == $this->getParameter('STATUT_TERMINE')) {
                $travauxes = $em->getRepository(Travaux::class)
                    ->findTerminesByCA($this->getUser())
                ;
            } elseif ($statut == 'a facturer') {
                $travauxes = $em->getRepository(Travaux::class)
                    ->findBy([
                        'statut' => $this->getParameter('STATUT_TERMINE'),
                        'chargeAffaire' => $this->getUser()
                    ])
                ;
            } else {
                $travauxes = $em->getRepository(Travaux::class)
                    ->findBy([
                        'statut' => $statut,
                        'chargeAffaire' => $this->getUser()
                    ])
                ;
            }

        } elseif ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) && $statut != $this->getParameter('STATUT_EN_ATTENTE')) {
            /** @var User $user */
            $user = $this->getUser();

            if ($statut == $this->getParameter('STATUT_VALIDE')) {
                $travauxes = $em->getRepository(Travaux::class)
                    ->findValidesByTech($user)
                ;
            } elseif ($statut == $this->getParameter('STATUT_TERMINE')) {
                $travauxes = $em->getRepository(Travaux::class)
                    ->findTerminesByTech($user)
                ;
            } elseif ($statut == 'a facturer') {
                $travauxes = $em->getRepository(Travaux::class)
                    ->findByTechnicienAndStatut($this->getParameter('STATUT_TERMINE'), $user)
                ;
            } else {
                $travauxes = $em->getRepository(Travaux::class)
                    ->findByTechnicienAndStatut($statut, $user)
                ;
            }
        }

        return $travauxes;

    }


    public function getDepannages($statut, Client $client = null)
    {
        $em = $this->getDoctrine()
            ->getManager()
        ;

        /** @var Depannage[] $depannages */
        $depannages = [];
        if ($this->isGranted($this->getParameter('ROLE_ADMIN'))) {

            if ($statut == $this->getParameter('STATUT_VALIDE')) {
                $depannages = $em->getRepository(Depannage::class)
                    ->findValides()
                ;

            } elseif ($statut == $this->getParameter('STATUT_TERMINE')) {
                $depannages = $em->getRepository(Depannage::class)
                    ->findTermines()
                ;
            } elseif ($statut == 'a facturer') {
                $depannages = $em->getRepository(Depannage::class)
                    ->findBy(['statut' => $this->getParameter('STATUT_TERMINE')])
                ;
            } else {
                $depannages = $em->getRepository(Depannage::class)
                    ->findBy(['statut' => $statut])
                ;
            }

        } elseif ($this->isGranted($this->getParameter('ROLE_CLIENT'))) {

            if ($statut == $this->getParameter('STATUT_VALIDE')) {
                $depannages = $em->getRepository(Depannage::class)
                    ->findValidesByCA($this->getUser())
                ;

            } elseif ($statut == $this->getParameter('STATUT_TERMINE')) {
                $depannages = $em->getRepository(Depannage::class)
                    ->findTerminesByCA($this->getUser())
                ;
            } elseif ($statut == 'a facturer') {
                $depannages = $em->getRepository(Depannage::class)
                    ->findBy([
                        'statut' => $this->getParameter('STATUT_TERMINE'),
                        'chargeAffaire' => $this->getUser()
                    ])
                ;
            } else {
                $depannages = $em->getRepository(Depannage::class)
                    ->findBy([
                        'statut' => $statut,
                        'chargeAffaire' => $this->getUser()
                    ])
                ;
            }

        } elseif ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) && $statut != $this->getParameter('STATUT_EN_ATTENTE')) {
            /** @var User $user */
            $user = $this->getUser();

            if ($statut == $this->getParameter('STATUT_VALIDE')) {
                $depannages = $em->getRepository(Depannage::class)
                    ->findValidesByTech($user)
                ;
            } elseif ($statut == $this->getParameter('STATUT_TERMINE')) {
                $depannages = $em->getRepository(Depannage::class)
                    ->findTerminesByTech($user)
                ;
            } elseif ($statut == 'a facturer') {
                $depannages = $em->getRepository(Depannage::class)
                    ->findByTechnicienAndStatut($this->getParameter('STATUT_TERMINE'), $user)
                ;
            } else {
                $depannages = $em->getRepository(Depannage::class)
                    ->findByTechnicienAndStatut($statut, $user)
                ;
            }
        }

        return $depannages;
    }


    public function getEntretiens($statut, Client $client = null)
    {
        $em = $this->getDoctrine()
            ->getManager()
        ;

        /** @var Entretien[] $entretiens */
        $entretiens = [];
        if ($this->isGranted($this->getParameter('ROLE_ADMIN'))) {

            if ($statut == $this->getParameter('STATUT_VALIDE')) {
                $entretiens = $em->getRepository(Entretien::class)
                    ->findValides()
                ;

            } elseif ($statut == $this->getParameter('STATUT_TERMINE')) {
                $entretiens = $em->getRepository(Entretien::class)
                    ->findTermines()
                ;
            } elseif ($statut == 'a facturer') {
                $entretiens = $em->getRepository(Entretien::class)
                    ->findBy(['statut' => $this->getParameter('STATUT_TERMINE')])
                ;
            } else {
                $entretiens = $em->getRepository(Entretien::class)
                    ->findBy(['statut' => $statut])
                ;
            }

        } elseif ($this->isGranted($this->getParameter('ROLE_CLIENT'))) {

            if ($statut == $this->getParameter('STATUT_VALIDE')) {
                $entretiens = $em->getRepository(Entretien::class)
                    ->findValidesByCA($this->getUser())
                ;

            } elseif ($statut == $this->getParameter('STATUT_TERMINE')) {
                $entretiens = $em->getRepository(Entretien::class)
                    ->findTerminesByCA($this->getUser())
                ;
            } elseif ($statut == 'a facturer') {
                $entretiens = $em->getRepository(Entretien::class)
                    ->findBy([
                        'statut' => $this->getParameter('STATUT_TERMINE'),
                        'chargeAffaire' => $this->getUser()
                    ])
                ;
            } else {
                $entretiens = $em->getRepository(Entretien::class)
                    ->findBy([
                        'statut' => $statut,
                        'chargeAffaire' => $this->getUser()
                    ])
                ;
            }

        } elseif ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) && $statut != $this->getParameter('STATUT_EN_ATTENTE')) {
            /** @var User $user */
            $user = $this->getUser();

            if ($statut == $this->getParameter('STATUT_VALIDE')) {
                $entretiens = $em->getRepository(Entretien::class)
                    ->findValidesByTech($user)
                ;
            } elseif ($statut == $this->getParameter('STATUT_TERMINE')) {
                $entretiens = $em->getRepository(Entretien::class)
                    ->findTerminesByTech($user)
                ;
            } elseif ($statut == 'a facturer') {
                $entretiens = $em->getRepository(Entretien::class)
                    ->findByTechnicienAndStatut($this->getParameter('STATUT_TERMINE'), $user)
                ;
            } else {
                $entretiens = $em->getRepository(Entretien::class)
                    ->findByTechnicienAndStatut($statut, $user)
                ;
            }
        }

        return $entretiens;
    }


    public function getLivraisons($statut, Client $client = null)
    {
        $em = $this->getDoctrine()
            ->getManager()
        ;

        $livraisons = [];
        if ($this->isGranted($this->getParameter('ROLE_ADMIN'))) {

            if ($statut == $this->getParameter('STATUT_VALIDE')) {
                $livraisons = $em->getRepository(Livraison::class)
                    ->findValides()
                ;

            } elseif ($statut == $this->getParameter('STATUT_TERMINE')) {
                $livraisons = $em->getRepository(Livraison::class)
                    ->findTermines()
                ;
            } elseif ($statut == 'a facturer') {
                $livraisons = $em->getRepository(Livraison::class)
                    ->findBy(['statut' => $this->getParameter('STATUT_TERMINE')])
                ;
            } else {
                $livraisons = $em->getRepository(Livraison::class)
                    ->findBy(['statut' => $statut])
                ;
            }

        } elseif ($this->isGranted($this->getParameter('ROLE_CLIENT'))) {

            if ($statut == $this->getParameter('STATUT_VALIDE')) {
                $livraisons = $em->getRepository(Livraison::class)
                    ->findValidesByCA($this->getUser())
                ;

            } elseif ($statut == $this->getParameter('STATUT_TERMINE')) {
                $livraisons = $em->getRepository(Livraison::class)
                    ->findTerminesByCA($this->getUser())
                ;
            } elseif ($statut == 'a facturer') {
                $livraisons = $em->getRepository(Livraison::class)
                    ->findBy([
                        'statut' => $this->getParameter('STATUT_TERMINE'),
                        'chargeAffaire' => $this->getUser()
                    ])
                ;
            } else {
                $livraisons = $em->getRepository(Livraison::class)
                    ->findBy([
                        'statut' => $statut,
                        'chargeAffaire' => $this->getUser()
                    ])
                ;
            }

        } elseif ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) && $statut != $this->getParameter('STATUT_EN_ATTENTE')) {
            /** @var User $user */
            $user = $this->getUser();

            if ($statut == $this->getParameter('STATUT_VALIDE')) {
                $livraisons = $em->getRepository(Livraison::class)
                    ->findValidesByTech($user)
                ;
            } elseif ($statut == $this->getParameter('STATUT_TERMINE')) {
                $livraisons = $em->getRepository(Livraison::class)
                    ->findTerminesByTech($user)
                ;
            } elseif ($statut == 'a facturer') {
                $livraisons = $em->getRepository(Livraison::class)
                    ->findByTechnicienAndStatut($this->getParameter('STATUT_TERMINE'), $user)
                ;
            } else {
                $livraisons = $em->getRepository(Livraison::class)
                    ->findByTechnicienAndStatut($statut, $user)
                ;
            }
        }

        return $livraisons;

    }


}