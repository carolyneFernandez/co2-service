<?php

namespace App\Controller;

use App\Entity\Depannage;
use App\Entity\DepannageBonIntervention;
use App\Entity\Factures\FactureDepannage;
use App\Entity\User;
use App\Form\DepannageBonInterventionType;
use App\Repository\DepannageBonInterventionRepository;
use App\Service\PDFService;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/depannage_bon_intervention")
 */
class DepannageBonInterventionController extends AbstractController
{
    /**
     * //     * @Route("/", name="depannage_bon_intervention_index", methods={"GET"})
     * //     */
//    public function index(DepannageBonInterventionRepository $depannageBonInterventionRepository): Response
//    {
//        return $this->render('depannage_bon_intervention/index.html.twig', [
//            'depannage_bon_interventions' => $depannageBonInterventionRepository->findAll(),
//        ]);
//    }

    /**
     * @Security("is_granted('ROLE_TECHNICIEN')")
     * @Route("/new", name="depannage_bon_intervention_new", methods={"GET","POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function new(Request $request): Response
    {
        if (!$request->query->get('depannage')) {
            $this->addFlash('danger', 'Aucun depannage n\'est sélectionné.');

            return $this->redirectToRoute('index');
        }

        $depannageId = $request->query->get('depannage');
        $depannage = $this->getDoctrine()
                          ->getRepository(Depannage::class)
                          ->find($depannageId)
        ;

        if (!$depannage) {
            $this->addFlash('danger', 'Cet depannage n\'existe pas.');

            return $this->redirectToRoute('index');
        }

        /** @var User $technicien */
        $technicien = $this->getUser();

        if (!$depannage->getTechniciens()
                       ->contains($technicien)) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à cet depannage.');

            return $this->redirectToRoute('index');
        }

        if ($depannage->getStatut()
                      ->getId() != $this->getParameter('STATUT_EN_COURS')) {
            $this->addFlash('danger', 'Cet depannage est terminé, vous ne pouvez plus remplir ce document.');

            return $this->redirectToRoute('depannage_show_admin', [
                'id' => $depannage->getId(),
                'client' => $depannage->getChargeAffaire()
                                      ->getClient()
                                      ->getId()
            ]);

        }


        $depannageBonIntervention = new DepannageBonIntervention();
        $depannageBonIntervention->setDepannage($depannage)
                                 ->setTechnicien($technicien)
                                 ->setDate(new \DateTime())
                                 ->setAdresse($depannage->getAdresse())
                                 ->setVille($depannage->getVille())
                                 ->setCodePostal($depannage->getCodePostal() ? $depannage->getCodePostal() : '')
                                 ->setMotifDepannage($depannage->getTypeIntervention())
        ;

        if ($depannage->getEnseigne())
            $depannageBonIntervention->setClient($depannage->getEnseigne()
                                                           ->getNom());


        $form = $this->createForm(DepannageBonInterventionType::class, $depannageBonIntervention);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()
                                  ->getManager()
            ;
            $entityManager->persist($depannageBonIntervention);
            $entityManager->flush();

            return $this->redirectToRoute('depannage_show_admin', [
                'id' => $depannage->getId(),
                'client' => $depannage->getChargeAffaire()
                                      ->getClient()
                                      ->getId()
            ]);
        }

        return $this->render('depannage_bon_intervention/new.html.twig', [
            'depannage_bon_intervention' => $depannageBonIntervention,
            'form' => $form->createView(),
            'depannage' => $depannage,
        ]);
    }

//    /**
//     * @Route("/{id}", name="depannage_bon_intervention_show", methods={"GET"})
//     * @param DepannageBonIntervention $depannageBonIntervention
//     * @return Response
//     */
//    public function show(DepannageBonIntervention $depannageBonIntervention): Response
//    {
//        return $this->render('depannage_bon_intervention/show.html.twig', [
//            'depannage_bon_intervention' => $depannageBonIntervention,
//        ]);
//    }

    /**
     * @Security("is_granted('ROLE_TECHNICIEN') or is_granted('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="depannage_bon_intervention_edit", methods={"GET","POST"})
     * @param Request $request
     * @param DepannageBonIntervention $depannageBonIntervention
     * @return Response
     */
    public function edit(Request $request, DepannageBonIntervention $depannageBonIntervention): Response
    {

        $depannage = $depannageBonIntervention->getDepannage();

        if (!$depannage) {
            $this->addFlash('danger', 'Cet depannage n\'existe pas.');

            return $this->redirectToRoute('index');
        }

        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN'))) {
            /** @var User $technicien */
            $technicien = $this->getUser();

            if (!$depannage->getTechniciens()
                           ->contains($technicien)) {
                $this->addFlash('danger', 'Vous n\'avez pas accès à cet depannage.');

                return $this->redirectToRoute('index');
            }
        }

        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) and $depannage->getStatut()
                                                                                   ->getId() != $this->getParameter('STATUT_EN_COURS')) {
            $this->addFlash('danger', 'Cet depannage est terminé, vous ne pouvez plus modifier ce document.');

            return $this->redirectToRoute('depannage_show_admin', [
                'id' => $depannage->getId(),
                'client' => $depannage->getChargeAffaire()
                                      ->getClient()
                                      ->getId()
            ]);

        }

        if ($this->isGranted($this->getParameter('ROLE_ADMIN')) && !in_array($depannage->getStatut()
                                                                                       ->getId(), [
                $this->getParameter('STATUT_EN_COURS'),
                $this->getParameter('STATUT_TERMINE')
            ])) {
            $this->addFlash('danger', 'Cet depannage est facturé, vous ne pouvez plus modifier ce document.');

            return $this->redirectToRoute('depannage_show_admin', [
                'id' => $depannage->getId(),
                'client' => $depannage->getChargeAffaire()
                                      ->getClient()
                                      ->getId()
            ]);
        }


        $form = $this->createForm(DepannageBonInterventionType::class, $depannageBonIntervention);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()
                 ->getManager()
                 ->flush()
            ;

            return $this->redirectToRoute('depannage_show_admin', [
                'id' => $depannage->getId(),
                'client' => $depannage->getChargeAffaire()
                                      ->getClient()
                                      ->getId()
            ]);
        }

        return $this->render('depannage_bon_intervention/edit.html.twig', [
            'depannage_bon_intervention' => $depannageBonIntervention,
            'form' => $form->createView(),
            'depannage' => $depannage,
            'client' => $depannage->getChargeAffaire()
                                  ->getClient()
        ]);
    }


    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/{id}/pdf", name="depannage_bon_intervention_pdf", methods={"GET"})
     * @param DepannageBonIntervention $depannageBonIntervention
     * @param PDFService $PDFService
     * @return Response
     */
    public function pdf(DepannageBonIntervention $depannageBonIntervention, PDFService $PDFService): Response
    {
        $chargeAffaire = $this->getUser();
        $depannage = $depannageBonIntervention->getDepannage();
        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $chargeAffaire != $depannage->getChargeAffaire()) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à ce bon d\'intervention.');

            return $this->redirectToRoute('index');
        }

        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN'))) {

            /** @var User $technicien */
            $technicien = $this->getUser();

            if (!$depannage->getTechniciens()
                           ->contains($technicien)) {
                $this->addFlash('danger', 'Vous n\'avez pas accès à cet depannage.');

                return $this->redirectToRoute('index');
            }
        }


        $client = $depannage->getChargeAffaire()
                            ->getClient()
        ;

        $params = [
            'depannageBonIntervention' => $depannageBonIntervention,
            'client' => $client,
            'depannage' => $depannage,
        ];

        $template = $this->renderView('pdf/depannage/bon_intervention.html.twig', $params);

        $pdf_name = 'bon_intervention_depannage_' . $depannage->getId();


        $PDFService->create('P', 'A4', 'fr', true, 'UTF-8', array(
            -1,
            5,
            0,
            5
        ));

        return $PDFService->generatePdf($template, $pdf_name, 'I');


    }

//    /**
//     * @Route("/{id}", name="depannage_bon_intervention_delete", methods={"DELETE"})
//     * @param Request $request
//     * @param DepannageBonIntervention $depannageBonIntervention
//     * @return Response
//     */
//    public function delete(Request $request, DepannageBonIntervention $depannageBonIntervention): Response
//    {
//        if ($this->isCsrfTokenValid('delete' . $depannageBonIntervention->getId(), $request->request->get('_token'))) {
//            $entityManager = $this->getDoctrine()
//                                  ->getManager()
//            ;
//            $entityManager->remove($depannageBonIntervention);
//            $entityManager->flush();
//        }
//
//        return $this->redirectToRoute('depannage_bon_intervention_index');
//    }

}
