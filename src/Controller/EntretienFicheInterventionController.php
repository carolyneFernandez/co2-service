<?php

namespace App\Controller;

use App\Entity\Entretien;
use App\Entity\EntretienBonIntervention;
use App\Entity\EntretienFicheIntervention;
use App\Entity\User;
use App\Form\EntretienFicheInterventionType;
use App\Repository\EntretienFicheInterventionRepository;
use App\Service\PDFService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/entretien_fiche_intervention")
 */
class EntretienFicheInterventionController extends AbstractController
{
//    /**
//     * @Route("/", name="entretien_fiche_intervention_index", methods={"GET"})
//     */
//    public function index(EntretienFicheInterventionRepository $entretienFicheInterventionRepository): Response
//    {
//        return $this->render('entretien_fiche_intervention/index.html.twig', [
//            'entretien_fiche_interventions' => $entretienFicheInterventionRepository->findAll(),
//        ]);
//    }

    /**
     * @Security("is_granted('ROLE_TECHNICIEN')")
     * @Route("/new", name="entretien_fiche_intervention_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        if (!$request->query->get('entretien')) {
            $this->addFlash('danger', 'Aucun entretien n\'est sélectionné.');

            return $this->redirectToRoute('index');
        }

        $entretienId = $request->query->get('entretien');
        $entretien = $this->getDoctrine()
                          ->getRepository(Entretien::class)
                          ->find($entretienId)
        ;

        if (!$entretien) {
            $this->addFlash('danger', 'Cet entretien n\'existe pas.');

            return $this->redirectToRoute('index');
        }

        /** @var User $technicien */
        $technicien = $this->getUser();

        if (!$entretien->getTechniciens()
                       ->contains($technicien)) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à cet entretien.');

            return $this->redirectToRoute('index');
        }

        if ($entretien->getStatut()
                      ->getId() != $this->getParameter('STATUT_EN_COURS')) {
            $this->addFlash('danger', 'Cet entretien est terminé, vous ne pouvez plus remplir ce document.');

            return $this->redirectToRoute('entretien_admin_show', [
                'id' => $entretien->getId(),
                'client' => $entretien->getChargeAffaire()
                                      ->getClient()
                                      ->getId()
            ]);

        }
        $detendeur = "";
        if ($entretien->getEnseigne())
            $detendeur .= $entretien->getEnseigne()
                                    ->getNom() . "\r\n";
        if ($entretien->getAdresse())
            $detendeur .= $entretien->getAdresse() . "\r\n";
        if ($entretien->getCodePostal())
            $detendeur .= $entretien->getCodePostal() . ' ';
        if ($entretien->getVille())
            $detendeur .= $entretien->getVille();

        $entretienFicheIntervention = new EntretienFicheIntervention();
        $entretienFicheIntervention->setEntretien($entretien);
        $entretienFicheIntervention->setNumero($entretien->getEntretienFicheInterventions()
                                                         ->count() + 1)
                                   ->setDetendeur($detendeur)
        ;
        /** @var User $user */
        $user = $this->getUser();
        $entretienFicheIntervention->setSIGNOpeDate(new \DateTime())
                                   ->setSIGNDetDate(new \DateTime())
                                   ->setSIGNOpeNom($user->getNomPrenom())
                                   ->setSIGNOpeQual('Technicien')
        ;

        $form = $this->createForm(EntretienFicheInterventionType::class, $entretienFicheIntervention);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()
                                  ->getManager()
            ;
            $entityManager->persist($entretienFicheIntervention);
            $entityManager->flush();

            return $this->redirectToRoute('entretien_admin_show', [
                'id' => $entretien->getId(),
                'client' => $entretien->getChargeAffaire()
                                      ->getClient()
                                      ->getId()
            ]);
        }

        return $this->render('entretien_fiche_intervention/new.html.twig', [
            'entretien_fiche_intervention' => $entretienFicheIntervention,
            'form' => $form->createView(),
            'entretien' => $entretien,
            'client' => $entretien->getChargeAffaire()
                                  ->getClient(),
        ]);
    }

//    /**
//     * @Route("/{id}", name="entretien_fiche_intervention_show", methods={"GET"})
//     */
//    public function show(EntretienFicheIntervention $entretienFicheIntervention): Response
//    {
//        return $this->render('entretien_fiche_intervention/show.html.twig', [
//            'entretien_fiche_intervention' => $entretienFicheIntervention,
//        ]);
//    }

    /**
     * @Security("is_granted('ROLE_TECHNICIEN') or is_granted('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="entretien_fiche_intervention_edit", methods={"GET","POST"})
     * @param Request $request
     * @param EntretienFicheIntervention $entretienFicheIntervention
     * @return Response
     */
    public function edit(Request $request, EntretienFicheIntervention $entretienFicheIntervention): Response
    {
        $entretien = $entretienFicheIntervention->getEntretien();

        if (!$entretien) {
            $this->addFlash('danger', 'Cet entretien n\'existe pas.');

            return $this->redirectToRoute('index');
        }

        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN'))) {
            /** @var User $technicien */
            $technicien = $this->getUser();

            if (!$entretien->getTechniciens()
                           ->contains($technicien)) {
                $this->addFlash('danger', 'Vous n\'avez pas accès à cet entretien.');

                return $this->redirectToRoute('index');
            }
        }


        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) and $entretien->getStatut()
                                                                                   ->getId() != $this->getParameter('STATUT_EN_COURS')) {
            $this->addFlash('danger', 'Cet entretien est terminé, vous ne pouvez plus modifier ce document.');

            return $this->redirectToRoute('entretien_admin_show', [
                'id' => $entretien->getId(),
                'client' => $entretien->getChargeAffaire()
                                      ->getClient()
                                      ->getId()
            ]);

        }

        if ($this->isGranted($this->getParameter('ROLE_ADMIN')) && !in_array($entretien->getStatut()
                                                                                       ->getId(), [
                $this->getParameter('STATUT_EN_COURS'),
                $this->getParameter('STATUT_TERMINE')
            ])) {
            $this->addFlash('danger', 'Cet entretien est facturé, vous ne pouvez plus modifier ce document.');

            return $this->redirectToRoute('entretien_admin_show', [
                'id' => $entretien->getId(),
                'client' => $entretien->getChargeAffaire()
                                      ->getClient()
                                      ->getId()
            ]);
        }

        $form = $this->createForm(EntretienFicheInterventionType::class, $entretienFicheIntervention);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()
                 ->getManager()
                 ->flush()
            ;

            return $this->redirectToRoute('entretien_admin_show', [
                'id' => $entretien->getId(),
                'client' => $entretien->getChargeAffaire()
                                      ->getClient()
                                      ->getId()
            ]);
        }

        return $this->render('entretien_fiche_intervention/edit.html.twig', [
            'entretien_fiche_intervention' => $entretienFicheIntervention,
            'form' => $form->createView(),
            'entretien' => $entretien,
            'client' => $entretien->getChargeAffaire()
                                  ->getClient(),
        ]);
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/{id}/pdf", name="entretien_fiche_intervention_pdf", methods={"GET"})
     * @param EntretienFicheIntervention $entretienFicheIntervention
     * @param PDFService $PDFService
     * @return Response
     */
    public function pdf(EntretienFicheIntervention $entretienFicheIntervention, PDFService $PDFService): Response
    {
        $chargeAffaire = $this->getUser();
        $entretien = $entretienFicheIntervention->getEntretien();
        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $chargeAffaire != $entretien->getChargeAffaire()) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à ce bon d\'intervention.');

            return $this->redirectToRoute('index');
        }

        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN'))) {

            /** @var User $technicien */
            $technicien = $this->getUser();

            if (!$entretien->getTechniciens()
                           ->contains($technicien)) {
                $this->addFlash('danger', 'Vous n\'avez pas accès à cet entretien.');

                return $this->redirectToRoute('index');
            }
        }


        $client = $entretien->getChargeAffaire()
                            ->getClient()
        ;

        $params = [
            'entretienFicheIntervention' => $entretienFicheIntervention,
            'client' => $client,
            'entretien' => $entretien,
        ];

        $template = $this->renderView('pdf/entretien/fiche_intervention.html.twig', $params);

        $pdf_name = 'fiche_intervention_entretien_' . $entretien->getId();


        $PDFService->create('P', 'A4', 'fr', 'UTF-8', 'UTF-8', array(
            -1,
            5,
            0,
            5
        ));

        return $PDFService->generatePdf($template, $pdf_name, 'I');


    }




//    /**
//     * @Route("/{id}", name="entretien_fiche_intervention_delete", methods={"DELETE"})
//     */
//    public function delete(Request $request, EntretienFicheIntervention $entretienFicheIntervention): Response
//    {
//        if ($this->isCsrfTokenValid('delete'.$entretienFicheIntervention->getId(), $request->request->get('_token'))) {
//            $entityManager = $this->getDoctrine()->getManager();
//            $entityManager->remove($entretienFicheIntervention);
//            $entityManager->flush();
//        }
//
//        return $this->redirectToRoute('entretien_fiche_intervention_index');
//    }
}
