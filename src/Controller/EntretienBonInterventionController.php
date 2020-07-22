<?php

namespace App\Controller;

use App\Entity\Entretien;
use App\Entity\EntretienBonIntervention;
use App\Entity\Factures\FactureDepannage;
use App\Entity\User;
use App\Form\EntretienBonInterventionType;
use App\Repository\EntretienBonInterventionRepository;
use App\Service\PDFService;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/entretien_bon_intervention")
 */
class EntretienBonInterventionController extends AbstractController
{
    /**
     * //     * @Route("/", name="entretien_bon_intervention_index", methods={"GET"})
     * //     */
//    public function index(EntretienBonInterventionRepository $entretienBonInterventionRepository): Response
//    {
//        return $this->render('entretien_bon_intervention/index.html.twig', [
//            'entretien_bon_interventions' => $entretienBonInterventionRepository->findAll(),
//        ]);
//    }

    /**
     * @Security("is_granted('ROLE_TECHNICIEN')")
     * @Route("/new", name="entretien_bon_intervention_new", methods={"GET","POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
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


        $entretienBonIntervention = new EntretienBonIntervention();
        $entretienBonIntervention->setEntretien($entretien)
                                 ->setTechnicien($technicien)
                                 ->setCodePostal($entretien->getCodePostal() ? $entretien->getCodePostal() : '')
                                 ->setVille($entretien->getVille() ? $entretien->getVille() : '')
                                 ->setAdresse($entretien->getAdresse() ? $entretien->getAdresse() : '')
                                 ->setClient($entretien->getEnseigne() ? $entretien->getEnseigne()
                                                                                   ->getNom() : '')
                                 ->setNumeroContrat($entretien->getNumeroContrat() ? $entretien->getNumeroContrat() : '')
        ;
        $form = $this->createForm(EntretienBonInterventionType::class, $entretienBonIntervention);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()
                                  ->getManager()
            ;
            $entityManager->persist($entretienBonIntervention);
            $entityManager->flush();

            return $this->redirectToRoute('entretien_admin_show', [
                'id' => $entretien->getId(),
                'client' => $entretien->getChargeAffaire()
                                      ->getClient()
                                      ->getId()
            ]);
        }

        return $this->render('entretien_bon_intervention/new.html.twig', [
            'entretien_bon_intervention' => $entretienBonIntervention,
            'form' => $form->createView(),
            'entretien' => $entretien,
        ]);
    }

//    /**
//     * @Route("/{id}", name="entretien_bon_intervention_show", methods={"GET"})
//     * @param EntretienBonIntervention $entretienBonIntervention
//     * @return Response
//     */
//    public function show(EntretienBonIntervention $entretienBonIntervention): Response
//    {
//        return $this->render('entretien_bon_intervention/show.html.twig', [
//            'entretien_bon_intervention' => $entretienBonIntervention,
//        ]);
//    }

    /**
     * @Security("is_granted('ROLE_TECHNICIEN') or is_granted('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="entretien_bon_intervention_edit", methods={"GET","POST"})
     * @param Request $request
     * @param EntretienBonIntervention $entretienBonIntervention
     * @return Response
     */
    public function edit(Request $request, EntretienBonIntervention $entretienBonIntervention): Response
    {

        $entretien = $entretienBonIntervention->getEntretien();

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


        $form = $this->createForm(EntretienBonInterventionType::class, $entretienBonIntervention);
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

        return $this->render('entretien_bon_intervention/edit.html.twig', [
            'entretien_bon_intervention' => $entretienBonIntervention,
            'form' => $form->createView(),
            'entretien' => $entretien,
            'client' => $entretien->getChargeAffaire()
                                  ->getClient()
        ]);
    }


    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/{id}/pdf", name="entretien_bon_intervention_pdf", methods={"GET"})
     * @param EntretienBonIntervention $entretienBonIntervention
     * @param PDFService $PDFService
     * @return Response
     */
    public function pdf(EntretienBonIntervention $entretienBonIntervention, PDFService $PDFService): Response
    {
        $chargeAffaire = $this->getUser();
        $entretien = $entretienBonIntervention->getEntretien();
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
            'entretienBonIntervention' => $entretienBonIntervention,
            'client' => $client,
            'entretien' => $entretien,
        ];

        $template = $this->renderView('pdf/entretien/bon_intervention.html.twig', $params);

        $pdf_name = 'bon_intervention_entretien_' . $entretien->getId();


        $PDFService->create('P', 'A4', 'fr', true, 'UTF-8', array(
            -1,
            5,
            0,
            5
        ));

        return $PDFService->generatePdf($template, $pdf_name, 'I');


    }

//    /**
//     * @Route("/{id}", name="entretien_bon_intervention_delete", methods={"DELETE"})
//     * @param Request $request
//     * @param EntretienBonIntervention $entretienBonIntervention
//     * @return Response
//     */
//    public function delete(Request $request, EntretienBonIntervention $entretienBonIntervention): Response
//    {
//        if ($this->isCsrfTokenValid('delete' . $entretienBonIntervention->getId(), $request->request->get('_token'))) {
//            $entityManager = $this->getDoctrine()
//                                  ->getManager()
//            ;
//            $entityManager->remove($entretienBonIntervention);
//            $entityManager->flush();
//        }
//
//        return $this->redirectToRoute('entretien_bon_intervention_index');
//    }

}
