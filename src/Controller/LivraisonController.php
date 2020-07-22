<?php

namespace App\Controller;

use App\Data\FiltreLivraisonData;
use App\Entity\Entretien;
use App\Entity\Livraison;
use App\Entity\StatutActivite;
use App\Entity\User;
use App\Form\AnnuleActiviteType;
use App\Form\FiltreLivraisonDataType;
use App\Form\LivraisonType;
use App\Repository\LivraisonRepository;
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
 * @Route("/livraison")
 */
class LivraisonController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_CLIENT') or is_granted('ROLE_TECHNICIEN')")
     * @Route("/", name="livraison_index", methods={"GET","POST"})
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, LivraisonRepository $livraisonRepository, PaginatorInterface $paginator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $filtre = new FiltreLivraisonData();
        $formFiltre = $this->createForm(FiltreLivraisonDataType::class, $filtre);
        $formFiltre->handleRequest($request);

        if ($request->request->get('item_pagination'))
            $this->get('session')
                 ->set('itemPerPage', $request->request->get('item_pagination')['maxItemPerPage'])
            ;

        if ($this->isGranted($this->getParameter('ROLE_CLIENT'))) {
            $filtre->chargeAffaires[] = $user;
            $livraisonsQuery = $livraisonRepository->getQuery($filtre);
        } else {
            if ($request->query->get('old')) {
                $livraisonsQuery = $livraisonRepository->getQueryOldLivraisonsByTechnicien($user, $filtre);
            } else {
                $livraisonsQuery = $livraisonRepository->getQueryNextLivraisonsByTechnicien($user, $filtre);
            }


        }

        $livraisons = $paginator->paginate($livraisonsQuery, $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            $this->get('session')
                 ->get('itemPerPage', $this->getParameter('itemPerPage')), // Nombre de résultats par page
            [
                'defaultSortFieldName' => 'l.dateRetenue',
                'defaultSortDirection' => 'desc'
            ]);


        return $this->render('livraison/index.html.twig', [
            'livraisons' => $livraisons,
            'formFiltre' => $formFiltre->createView()
        ]);
    }

    /**
     * @Security("is_granted('ROLE_CLIENT')")
     * @Route("/new", name="livraison_new", methods={"GET","POST"})
     * @param Request $request
     * @param MailService $mailService
     * @param UserRepository $userRepository
     * @return Response
     * @throws Exception
     */
    public function new(Request $request, MailService $mailService, UserRepository $userRepository): Response
    {
        $livraison = new Livraison();
        /*        $enseignes = array();
                foreach($this->getUser()->getEnseignes() as $ens) {
                    $enseignes = $ens->getNom();
                }*/
        $form = $this->createForm(LivraisonType::class, $livraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $statut = $this->getDoctrine()
                           ->getRepository(StatutActivite::class)
                           ->find($this->getParameter('STATUT_EN_ATTENTE'))
            ;
            $livraison->setDateSaisie(new \dateTime());
            $livraison->setDateRetenue($livraison->getDateSouhaitee());
            $livraison->setStatut($statut);
            $livraison->setIsFacture(false);
            $livraison->setChargeAffaire($this->getUser());
            $entityManager = $this->getDoctrine()
                                  ->getManager()
            ;
            $entityManager->persist($livraison);
            $entityManager->flush();

            $mailService->setAndSendMail($this->getParameter('MAIL_ADMIN'), 'Demande de livraison', 'mail/new_livraison.html.twig', [
                'user' => $this->getUser(),
                'livraison' => $livraison
            ]);

            $this->addFlash('success', "Votre demande de livraison a bien été prise en compte et est en attente.");

            return $this->redirectToRoute('livraison_index');
        }

        return $this->render('livraison/new.html.twig', [
            'livraison' => $livraison,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Security("is_granted('ROLE_CLIENT') or is_granted('ROLE_TECHNICIEN')")
     * @Route("/{id}", name="livraison_show", methods={"GET"})
     * @param Livraison $livraison
     * @param FileUploader $fileUploader
     * @return Response
     */
    public function show(Livraison $livraison, FileUploader $fileUploader): Response
    {

        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $livraison->getChargeAffaire() != $this->getUser()) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à cette livraison.');

            return $this->redirectToRoute('livraison_index');
        }

        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) && !$livraison->getTechniciens()
                                                                                   ->contains($this->getUser())) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à cette livraison.');

            return $this->redirectToRoute('livraison_index');
        }


        /*@var User $user*/
        $user = $this->getUser();

        $dir = $this->getParameter('dossier_fichier_livraisons') . $livraison->getId();
        $dir = $fileUploader->replaceVariableClientDir($dir, $livraison->getChargeAffaire()
                                                                       ->getClient()
                                                                       ->getId(), $livraison->getChargeAffaire()
                                                                                            ->getId());

        $files = $fileUploader->scanDir($dir);

        return $this->render('livraison/show.html.twig', [
            'livraison' => $livraison,
            'user' => $user,
            'files' => $files
        ]);
    }

    /**
     * @Security("is_granted('ROLE_CLIENT')")
     * @Route("/{id}/edit", name="livraison_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Livraison $livraison
     * @return Response
     */
    public function edit(Request $request, Livraison $livraison): Response
    {
        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $livraison->getChargeAffaire() != $this->getUser()) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à cette livraison.');

            return $this->redirectToRoute('livraison_index');
        }

        if (!in_array($livraison->getStatut()
                                ->getId(), [
            $this->getParameter('STATUT_EN_ATTENTE'),
            $this->getParameter('STATUT_VALIDE'),
            $this->getParameter('STATUT_EN_COURS')
        ])) {

            $this->addFlash('danger', 'La livraison est terminée. Vous ne pouvez plus la modifier.');

            return $this->redirectToRoute('entretien_show', [
                'id' => $livraison->getId()
            ]);
        }


        $form = $this->createForm(LivraisonType::class, $livraison, array(
            'statut' => $livraison->getStatut()
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (in_array($this->getParameter('ROLE_TECHNICIEN'), $this->getUser()
                                                                      ->getRoles())) {
                $statut = $this->getDoctrine()
                               ->getRepository(StatutActivite::class)
                               ->find($this->getParameter('STATUT_EN_COURS'))
                ;
                if ($livraison->getStatut() == $statut) {
                    $statutTermine = $this->getDoctrine()
                                          ->getRepository(StatutActivite::class)
                                          ->find($this->getParameter('STATUT_TERMINE'))
                    ;
                    $livraison->setStatut($statutTermine);
                } else {
                    $livraison->setStatut($statut);
                }
            }
            $this->getDoctrine()
                 ->getManager()
                 ->flush()
            ;

            return $this->redirectToRoute('livraison_index');
        }

        return $this->render('livraison/edit.html.twig', [
            'livraison' => $livraison,
            'form' => $form->createView(),
            'user' => $this->getUser()
        ]);
    }

//    /**
//     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_CLIENT')")
//     * @Route("/{id}", name="livraison_delete", methods={"DELETE"})
//     * @param Request $request
//     * @param Livraison $livraison
//     * @return Response
//     */
//    public function delete(Request $request, Livraison $livraison): Response
//    {
//        if ($this->isCsrfTokenValid('delete' . $livraison->getId(), $request->request->get('_token'))) {
//            $entityManager = $this->getDoctrine()
//                                  ->getManager()
//            ;
//
//            if ($livraison->getStatut()
//                          ->getId() != $this->getParameter('STATUT_EN_ATTENTE')) {
//                $this->addFlash('danger', 'La livraison a déjà été validée. Vous ne pouvez plus la supprimer.');
//
//                return $this->redirectToRoute('livraison_show', [
//                    'id' => $livraison->getId()
//                ]);
//            } else if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $livraison->getChargeAffaire() != $this->getUser()) {
//                $this->addFlash('danger', 'Vous n\'avez pas accès à cette livraison.');
//
//                return $this->redirectToRoute('livraison_index');
//            }
//
//
//            $entityManager->remove($livraison);
//            $entityManager->flush();
//        }
//
//        return $this->redirectToRoute('livraison_index');
//    }


    /**
     * @Security("is_granted('ROLE_CLIENT') or is_granted('ROLE_ADMIN')")
     * @Route("/{id}/annule", name="livraison_annule", methods={"GET","POST"})
     * @param Request $request
     * @param Livraison $livraison
     * @return Response
     */
    public function annule(Request $request, Livraison $livraison)
    {
        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $livraison->getChargeAffaire() != $this->getUser()) {
            $this->addFlash('danger', 'Vous n\'êtes pas sur cette livraison.');

            return $this->redirectToRoute('livraison_index');
        }

        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && !in_array($livraison->getStatut()
                                                                                        ->getId(), [
                $this->getParameter('STATUT_EN_ATTENTE'),
                $this->getParameter('STATUT_VALIDE'),
            ])) {
            $this->addFlash('danger', 'Vous ne pouvez plus annuler cette livraison.');

            return $this->redirectToRoute('livraison_show', [
                'id' => $livraison->getId()
            ]);
        }

        if ($this->isGranted($this->getParameter('ROLE_ADMIN')) && !in_array($livraison->getStatut()
                                                                                       ->getId(), [
                $this->getParameter('STATUT_EN_ATTENTE'),
                $this->getParameter('STATUT_VALIDE'),
                $this->getParameter('STATUT_EN_COURS'),
            ])) {
            $this->addFlash('danger', 'Vous ne pouvez plus annuler cette livraison.');

            return $this->redirectToRoute('livraison_show_admin', [
                'id' => $livraison->getId(),
                'client' => $livraison->getChargeAffaire()
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

            $livraison->setStatut($em->getRepository(StatutActivite::class)
                                     ->find($this->getParameter('STATUT_ANNULE')))
                      ->setMotifAnnule($motifAnnule)
            ;

            $em->flush();

            $this->addFlash('success', 'L\'livraison a été annulé.');

            if ($this->isGranted($this->getParameter('ROLE_ADMIN'))) {

                return $this->redirectToRoute('livraison_show_admin', [
                    'id' => $livraison->getId(),
                    'client' => $livraison->getChargeAffaire()
                                          ->getClient()
                                          ->getId(),
                ]);

            } else {
                return $this->redirectToRoute('livraison_show', [
                    'id' => $livraison->getId(),
                ]);
            }
        }

        if ($this->isGranted($this->getParameter('ROLE_ADMIN'))) {
            $urlRetour = $this->generateUrl('livraison_show_admin', [
                'id' => $livraison->getId(),
                'client' => $livraison->getChargeAffaire()
                                      ->getClient()
                                      ->getId(),
            ]);
        } else {
            $urlRetour = $this->generateUrl('livraison_show', [
                'id' => $livraison->getId(),
            ]);
        }


        return $this->render('activite/annule.html.twig', [
            'activite' => $livraison,
            'form' => $form->createView(),
            'client' => $livraison->getChargeAffaire()
                                  ->getClient(),
            'titre' => 'Annulation de la livraison n°' . $livraison->getId(),
            'urlRetour' => $urlRetour,
        ]);

    }


}
