<?php

namespace App\Controller;

use App\Data\FiltreDepannageData;
use App\Entity\DepannageHoraireTechnicien;
use App\Entity\Entretien;
use App\Entity\Depannage;
use App\Entity\StatutActivite;
use App\Entity\User;
use App\Form\AnnuleActiviteType;
use App\Form\FiltreDepannageDataType;
use App\Form\DepannageType;
use App\Repository\DepannageRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use App\Service\MailService;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/depannage")
 */
class DepannageController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_CLIENT') or is_granted('ROLE_TECHNICIEN')")
     * @Route("/", name="depannage_index", methods={"GET","POST"})
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     * @throws Exception
     */
    public function index(Request $request, DepannageRepository $depannageRepository, PaginatorInterface $paginator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $filtre = new FiltreDepannageData();
        $formFiltre = $this->createForm(FiltreDepannageDataType::class, $filtre);
        $formFiltre->handleRequest($request);

        if ($request->request->get('item_pagination'))
            $this->get('session')
                 ->set('itemPerPage', $request->request->get('item_pagination')['maxItemPerPage'])
            ;

        if ($this->isGranted($this->getParameter('ROLE_CLIENT'))) {
            $filtre->chargeAffaires[] = $user;
            $depannagesQuery = $depannageRepository->getQuery($filtre);

        } else {
            if ($request->query->get('old')) {
                $depannagesQuery = $depannageRepository->findOldByTechnicien($user, $filtre);
            } else {
                $depannagesQuery = $depannageRepository->findNextByTechnicien($user, $filtre);
            }
        }

        $depannages = $paginator->paginate($depannagesQuery, $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            $this->get('session')
                 ->get('itemPerPage', $this->getParameter('itemPerPage')), // Nombre de résultats par page
            [
                'defaultSortFieldName' => 'd.dateRetenue',
                'defaultSortDirection' => 'desc'
            ]);


        return $this->render('depannage/index.html.twig', [
            'depannages' => $depannages,
            'formFiltre' => $formFiltre->createView()
        ]);
    }

    /**
     * @Security("is_granted('ROLE_CLIENT')")
     * @Route("/new", name="depannage_new", methods={"GET","POST"})
     * @param Request $request
     * @param MailService $mailService
     * @param UserRepository $userRepository
     * @return Response
     * @throws Exception
     */
    public function new(Request $request, MailService $mailService, UserRepository $userRepository): Response
    {
        $depannage = new Depannage();
        /*        $enseignes = array();
                foreach($this->getUser()->getEnseignes() as $ens) {
                    $enseignes = $ens->getNom();
                }*/
        $form = $this->createForm(DepannageType::class, $depannage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $statut = $this->getDoctrine()
                           ->getRepository(StatutActivite::class)
                           ->find($this->getParameter('STATUT_EN_ATTENTE'))
            ;
            $depannage->setDateSaisie(new \dateTime());
            $depannage->setDateRetenue($depannage->getDateSouhaitee());
            $depannage->setStatut($statut);
            $depannage->setIsFacture(false);
            $depannage->setChargeAffaire($this->getUser());
            $entityManager = $this->getDoctrine()
                                  ->getManager()
            ;
            $entityManager->persist($depannage);
            $entityManager->flush();

//            $admin = $userRepository->getUsersByRole('ROLE_ADMIN');
//            $adminMail = $admin[0]->getEmailPro();
            $mailService->setAndSendMail($this->getParameter('MAIL_ADMIN'), 'Demande de depannage', 'mail/demande_depannage.html.twig', [
                'user' => $this->getUser(),
                'depannage' => $depannage
            ]);

            $this->addFlash('success', "Votre demande de depannage a bien été prise en compte et est en attente.");

            return $this->redirectToRoute('depannage_index');
        }

        return $this->render('depannage/new.html.twig', [
            'depannage' => $depannage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Security("is_granted('ROLE_CLIENT')")
     * @Route("/{id}", name="depannage_show", methods={"GET"})
     * @param Depannage $depannage
     * @param FileUploader $fileUploader
     * @return Response
     */
    public function show(Depannage $depannage, FileUploader $fileUploader): Response
    {

        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $depannage->getChargeAffaire() != $this->getUser()) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à cette depannage.');

            return $this->redirectToRoute('depannage_index');
        }

        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) && !$depannage->getTechniciens()
                                                                                   ->contains($this->getUser())) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à cette depannage.');

            return $this->redirectToRoute('depannage_index');
        }

        $horaires = $this->getDoctrine()
                         ->getRepository(DepannageHoraireTechnicien::class)
                         ->findBy(['depannage' => $depannage], ['dateDebut' => 'ASC'])
        ;


        /*@var User $user*/
        $user = $this->getUser();

        $dir = $this->getParameter('dossier_fichier_depannages') . $depannage->getId();
        $dir = $fileUploader->replaceVariableClientDir($dir, $depannage->getChargeAffaire()
                                                                       ->getClient()
                                                                       ->getId(), $depannage->getChargeAffaire()
                                                                                            ->getId());

        $files = $fileUploader->scanDir($dir);

        return $this->render('depannage/show.html.twig', [
            'depannage' => $depannage,
            'user' => $user,
            'files' => $files,
            'horaires' => $horaires
        ]);
    }

    /**
     * @Security("is_granted('ROLE_CLIENT')")
     * @Route("/{id}/edit", name="depannage_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Depannage $depannage
     * @return Response
     */
    public function edit(Request $request, Depannage $depannage): Response
    {
        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $depannage->getChargeAffaire() != $this->getUser()) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à cette depannage.');

            return $this->redirectToRoute('depannage_index');
        }

        if (!in_array($depannage->getStatut()
                                ->getId(), [
            $this->getParameter('STATUT_EN_ATTENTE'),
            $this->getParameter('STATUT_VALIDE'),
            $this->getParameter('STATUT_EN_COURS')
        ])) {

            $this->addFlash('danger', 'La depannage est terminée. Vous ne pouvez plus la modifier.');

            return $this->redirectToRoute('entretien_show', [
                'id' => $depannage->getId()
            ]);
        }


        $form = $this->createForm(DepannageType::class, $depannage, array(
            'statut' => $depannage->getStatut()
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (in_array($this->getParameter('ROLE_TECHNICIEN'), $this->getUser()
                                                                      ->getRoles())) {
                $statut = $this->getDoctrine()
                               ->getRepository(StatutActivite::class)
                               ->find($this->getParameter('STATUT_EN_COURS'))
                ;
                if ($depannage->getStatut() == $statut) {
                    $statutTermine = $this->getDoctrine()
                                          ->getRepository(StatutActivite::class)
                                          ->find($this->getParameter('STATUT_TERMINE'))
                    ;
                    $depannage->setStatut($statutTermine);
                } else {
                    $depannage->setStatut($statut);
                }
            }
            $this->getDoctrine()
                 ->getManager()
                 ->flush()
            ;

            return $this->redirectToRoute('depannage_index');
        }

        return $this->render('depannage/edit.html.twig', [
            'depannage' => $depannage,
            'form' => $form->createView(),
            'user' => $this->getUser()
        ]);
    }

//    /**
//     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_CLIENT')")
//     * @Route("/{id}", name="depannage_delete", methods={"DELETE"})
//     * @param Request $request
//     * @param Depannage $depannage
//     * @return Response
//     */
//    public function delete(Request $request, Depannage $depannage): Response
//    {
//        if ($this->isCsrfTokenValid('delete' . $depannage->getId(), $request->request->get('_token'))) {
//            $entityManager = $this->getDoctrine()
//                                  ->getManager()
//            ;
//
//            if ($depannage->getStatut()
//                          ->getId() != $this->getParameter('STATUT_EN_ATTENTE')) {
//                $this->addFlash('danger', 'La depannage a déjà été validée. Vous ne pouvez plus la supprimer.');
//
//                return $this->redirectToRoute('depannage_show', [
//                    'id' => $depannage->getId()
//                ]);
//            } else if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $depannage->getChargeAffaire() != $this->getUser()) {
//                $this->addFlash('danger', 'Vous n\'avez pas accès à cette depannage.');
//
//                return $this->redirectToRoute('depannage_index');
//            }
//
//
//            $entityManager->remove($depannage);
//            $entityManager->flush();
//        }
//
//        return $this->redirectToRoute('depannage_index');
//    }


    /**
     * @Security("is_granted('ROLE_CLIENT') or is_granted('ROLE_ADMIN')")
     * @Route("/{id}/annule", name="depannage_annule", methods={"GET","POST"})
     * @param Request $request
     * @param Depannage $depannage
     * @return Response
     */
    public function annule(Request $request, Depannage $depannage)
    {
        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $depannage->getChargeAffaire() != $this->getUser()) {
            $this->addFlash('danger', 'Vous n\'êtes pas sur cette depannage.');

            return $this->redirectToRoute('depannage_index');
        }

        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && !in_array($depannage->getStatut()
                                                                                        ->getId(), [
                $this->getParameter('STATUT_EN_ATTENTE'),
                $this->getParameter('STATUT_VALIDE'),
            ])) {
            $this->addFlash('danger', 'Vous ne pouvez plus annuler cette depannage.');

            return $this->redirectToRoute('depannage_show', [
                'id' => $depannage->getId()
            ]);
        }

        if ($this->isGranted($this->getParameter('ROLE_ADMIN')) && !in_array($depannage->getStatut()
                                                                                       ->getId(), [
                $this->getParameter('STATUT_EN_ATTENTE'),
                $this->getParameter('STATUT_VALIDE'),
                $this->getParameter('STATUT_EN_COURS'),
            ])) {
            $this->addFlash('danger', 'Vous ne pouvez plus annuler cette depannage.');

            return $this->redirectToRoute('depannage_show_admin', [
                'id' => $depannage->getId(),
                'client' => $depannage->getChargeAffaire()
                                      ->getClient()
                                      ->getId(),
            ]);
        }


        $form = $this->createForm(AnnuleActiviteType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $em = $this->getDoctrine()
                       ->getManager()
            ;

            $motifAnnule = $form['motifAnnulation']->getData();

            $depannage->setStatut($em->getRepository(StatutActivite::class)
                                     ->find($this->getParameter('STATUT_ANNULE')))
                      ->setMotifAnnule($motifAnnule)
            ;

            $em->flush();

            $this->addFlash('success', 'Le dépannage a été annulé.');

            if ($this->isGranted($this->getParameter('ROLE_ADMIN'))) {

                return $this->redirectToRoute('depannage_show_admin', [
                    'id' => $depannage->getId(),
                    'client' => $depannage->getChargeAffaire()
                                          ->getClient()
                                          ->getId(),
                ]);

            } else {
                return $this->redirectToRoute('depannage_show', [
                    'id' => $depannage->getId(),
                ]);
            }
        }

        if ($this->isGranted($this->getParameter('ROLE_ADMIN'))) {
            $urlRetour = $this->generateUrl('depannage_show_admin', [
                'id' => $depannage->getId(),
                'client' => $depannage->getChargeAffaire()
                                      ->getClient()
                                      ->getId(),
            ]);
        } else {
            $urlRetour = $this->generateUrl('depannage_show', [
                'id' => $depannage->getId(),
            ]);
        }


        return $this->render('activite/annule.html.twig', [
            'activite' => $depannage,
            'form' => $form->createView(),
            'client' => $depannage->getChargeAffaire()
                                  ->getClient(),
            'titre' => 'Annulation de la depannage n°' . $depannage->getId(),
            'urlRetour' => $urlRetour,
        ]);

    }


}
