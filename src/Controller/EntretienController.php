<?php

namespace App\Controller;

use App\Data\FiltreTravauxData;
use App\Data\FiltreEntretienData;
use App\Entity\Client;
use App\Entity\Entretien;
use App\Entity\EntretienHoraireTechnicien;
use App\Entity\StatutActivite;
use App\Entity\User;
use App\Form\AnnuleActiviteType;
use App\Form\EntretienType;
use App\Form\FiltreDataType;
//use App\Form\FiltreType;
use App\Form\FiltreEntretienDataType;
use App\Repository\ClientRepository;
use App\Repository\EnseigneRepository;
use App\Repository\EntretienRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use App\Service\MailService;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/entretien")
 */
class EntretienController extends AbstractController
{

    /**
     * @Security("is_granted('ROLE_CLIENT') or is_granted('ROLE_TECHNICIEN')")
     * @Route("/liste", name="entretien_list", methods={"GET","POST"})
     * @param Request $request
     * @param ClientRepository $clientRepository
     * @param PaginatorInterface $paginator
     * @return Response
     * @throws Exception
     */
    public function listTableau(Request $request, ClientRepository $clientRepository, PaginatorInterface $paginator, EnseigneRepository $enseigneRepository, UserRepository $userRepository, EntretienRepository $entretienRepository): Response
    {
        $filtre = new FiltreEntretienData();
        $formFilter = $this->createForm(FiltreEntretienDataType::class, $filtre);
        $formFilter->handleRequest($request);

        if ($request->request->get('item_pagination'))
            $this->get('session')
                 ->set('itemPerPage', $request->request->get('item_pagination')['maxItemPerPage'])
            ;

        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted($this->getParameter('ROLE_CLIENT'))) {
            $filtre->chargeAffaires[] = $user;
            $entretiensQuery = $entretienRepository->getQuery($filtre);
//            if ($request->query->get('old')) {
//                $entretiensQuery = $entretienRepository->getOldByUser($user, $filtre);
//            } else {
//                $entretiensQuery = $entretienRepository->getNextByUser($user, $filtre);
//            }
        } else {
            if ($request->query->get('old')) {
                $entretiensQuery = $entretienRepository->findOldByTechnicien($user, $filtre);
            } else {
                $entretiensQuery = $entretienRepository->findNextByTechnicien($user, $filtre);
            }
        }


        if ($request->request->get('item_pagination'))
            $this->get('session')
                 ->set('itemPerPage', $request->request->get('item_pagination')['maxItemPerPage'])
            ;


        $entretiens = $paginator->paginate($entretiensQuery, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            $this->get('session')
                 ->get('itemPerPage', $this->getParameter('itemPerPage')), // Nombre de résultats par page
            [
                'defaultSortFieldName' => 'e.dateDebut',
                'defaultSortDirection' => 'desc'
            ]);

//        $entretien = new Entretien();
//        // $entretien->setChargeAffaire($this->getUser());
//        $form = $this->createForm(EntretienType::class, $entretien);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $entityManager = $this->getDoctrine()
//                                  ->getManager()
//            ;
//            $entityManager->persist($entretien);
//            $entityManager->flush();
//
//            return $this->redirectToRoute('entretien_list');
//        }

        return $this->render('entretien/listTableau.html.twig', [

            'entretiens' => $entretiens,
            //            'form' => $form->createView(),
            'formFiltre' => $formFilter->createView(),
            'role' => $this->getParameter('ROLE_CLIENT'),
        ]);
    }

    /**
     * @Security("is_granted('ROLE_CLIENT') or is_granted('ROLE_TECHNICIEN')")
     * @Route("/", name="entretien_index", methods={"GET","POST"})
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param EnseigneRepository $enseigneRepository
     * @param UserRepository $userRepository
     * @param EntretienRepository $entretienRepository
     * @return Response
     */
    public function index(Request $request, PaginatorInterface $paginator, EnseigneRepository $enseigneRepository, UserRepository $userRepository, EntretienRepository $entretienRepository): Response
    {


        $entretien = new Entretien();
        $filtre = new FiltreEntretienData();

        $formFilter = $this->createForm(FiltreEntretienDataType::class, $filtre);
        $formFilter->handleRequest($request);


        $enseignements = $enseigneRepository->findAll();
        $entretiens = $entretienRepository->findAll();

        $entretiens = $entretienRepository->getQuery($filtre);

        if ($this->isGranted($this->getParameter('ROLE_CLIENT'))) {
            $entretien->setChargeAffaire($this->getUser());
        }

        $entretien->setStatut($this->getDoctrine()
                                   ->getRepository(StatutActivite::class)
                                   ->find($this->getParameter('STATUT_EN_ATTENTE')));
        $form = $this->createForm(EntretienType::class, $entretien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()
                                  ->getManager()
            ;
            $entityManager->persist($entretien);
            $entityManager->flush();

            return $this->redirectToRoute('entretien_index');
        }


        return $this->render('entretien/index.html.twig', [
            'formFiltre' => $formFilter->createView(),
            'entretiens' => $entretiens,
            'users' => $userRepository->getQueryUsersByRole('ROLE_CLIENT'),
            'form' => $form->createView(),
            'role' => $this->getParameter('ROLE_CLIENT'),
        ]);


    }

//    /**
//     * @Security("is_granted('ROLE_TECHNICIEN')")
//     * @Route("/entretienTecnichien", name="entretien_tecnichien",methods={"GET"})
//     * @param EnseigneRepository $enseigneRepository
//     * @param UserRepository $userRepository
//     * @param PaginatorInterface $paginator
//     * @param EntretienRepository $entretienRepository
//     * @param Request $request
//     * @return Response
//     */
//    public function tenichienindex(EnseigneRepository $enseigneRepository, UserRepository $userRepository, PaginatorInterface $paginator, EntretienRepository $entretienRepository, Request $request): Response
//    {
//        $entretien = new Entretien();
//        $entretien->setChargeAffaire($this->getUser());
//        $this->getUser()
//             ->getId()
//        ;
//
//        if ($request->request->get('item_pagination'))
//            $this->get('session')
//                 ->set('itemPerPage', $request->request->get('item_pagination')['maxItemPerPage'])
//            ;
//
//        $entretienQuery = $this->getUser()
//                               ->getEntretiensTechnichiens()
//        ;
//
//        $entretiens = $paginator->paginate($entretienQuery, // Requête contenant les données à paginer (ici nos articles)
//            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
//            $this->get('session')
//                 ->get('itemPerPage', $this->getParameter('itemPerPage')), // Nombre de résultats par page
//            [
//                'defaultSortFieldName' => 'statut',
//                'defaultSortDirection' => 'asc'
//            ]);
//
//
//        return $this->render('entretien/listTecnichien.html.twig', [
//            //'enseignements' =>$enseigneRepository->findAll(),//getLivraisonsByUser
//            'entretiens' => $entretiens,
//            //  'users'=>$userRepository->getQueryUsersByRole('ROLE_CLIENT'),
//        ]);
//    }

    /**
     * @Security("is_granted('ROLE_CLIENT')")
     * @Route("/new", name="entretien_new", methods={"GET","POST"})
     * @param Request $request
     * @return Response
     */
    public function new(Request $request, MailService $mailService): Response
    {
        $entretien = new Entretien();
        $entretien->setStatut($this->getDoctrine()
                                   ->getRepository(StatutActivite::class)
                                   ->find($this->getParameter('STATUT_EN_ATTENTE')));

        if ($this->isGranted($this->getParameter('ROLE_CLIENT'))) {
            $entretien->setChargeAffaire($this->getUser());
        }

        $form = $this->createForm(EntretienType::class, $entretien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()
                                  ->getManager()
            ;
            $entityManager->persist($entretien);
            $entityManager->flush();

            if ($this->isGranted($this->getParameter('ROLE_CLIENT'))) {

                $mailService->setAndSendMail($this->getParameter('MAIL_ADMIN'), "Nouvelle demande d'entretien", 'mail/demande_entretien.html.twig', [
                    'entretien' => $entretien,
                    'user' => $this->getUser()
                ]);

            }


            return $this->redirectToRoute('entretien_index');
        }

        return $this->render('entretien/new.html.twig', [
            'entretien' => $entretien,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Security("is_granted('ROLE_CLIENT')")
     * @Route("/{id}", name="entretien_show", methods={"GET"})
     * @param Entretien $entretien
     * @param FileUploader $fileUploader
     * @return Response
     */
    public function show(Entretien $entretien, FileUploader $fileUploader): Response
    {
        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $entretien->getChargeAffaire() != $this->getUser()) {
            $this->addFlash('danger', 'Vous n\'êtes pas sur cet entretien.');

            return $this->redirectToRoute('entretien_index');
        }

        $params = [
            'entretien' => $entretien,
        ];

        $em = $this->getDoctrine()
                   ->getManager()
        ;

        $dir = $this->getParameter('dossier_fichier_entretiens') . $entretien->getId();
        $dir = $fileUploader->replaceVariableClientDir($dir, $entretien->getChargeAffaire()
                                                                       ->getClient()
                                                                       ->getId(), $entretien->getChargeAffaire()
                                                                                            ->getId());

        $files = $fileUploader->scanDir($dir);
        $params['files'] = $files;

        $horaires = $em->getRepository(EntretienHoraireTechnicien::class)
                       ->findBy(['entretien' => $entretien], ['dateDebut' => 'ASC'])
        ;

        $params['horaires'] = $horaires;


        return $this->render('entretien/show.html.twig', $params);
    }

    /**
     * @Security("is_granted('ROLE_CLIENT')")
     * @Route("/{id}/edit", name="entretien_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Entretien $entretien
     * @return Response
     */
    public function edit(Request $request, Entretien $entretien): Response
    {
        if (!in_array($entretien->getStatut()
                                ->getId(), [
            $this->getParameter('STATUT_EN_ATTENTE'),
            $this->getParameter('STATUT_VALIDE'),
            $this->getParameter('STATUT_EN_COURS')
        ])) {

            $this->addFlash('danger', 'Vous ne pouvez plus le modifier cet entretien.');

            return $this->redirectToRoute('entretien_show', [
                'id' => $entretien->getId()
            ]);

        } elseif ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $entretien->getChargeAffaire() != $this->getUser()) {
            $this->addFlash('danger', 'Vous n\'êtes pas sur cet entretien.');

            return $this->redirectToRoute('entretien_index');
        }

        $form = $this->createForm(EntretienType::class, $entretien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()
                 ->getManager()
                 ->flush()
            ;

            return $this->redirectToRoute('entretien_index');
        }

        return $this->render('entretien/edit.html.twig', [
            'entretien' => $entretien,
            'form' => $form->createView(),
        ]);
    }


//    /**
//     * @Security("is_granted('ROLE_CLIENT') or is_granted('ROLE_ADMIN')")
//     * @Route("/{id}", name="entretien_delete", methods={"DELETE"})
//     */
//    public function delete(Request $request, Entretien $entretien, Client $client): Response
//    {
//        if ($this->isCsrfTokenValid('delete' . $entretien->getId(), $request->request->get('_token'))) {
//            $entityManager = $this->getDoctrine()
//                                  ->getManager()
//            ;
//
//            if ($entretien->getStatut()
//                          ->getId() != $this->getParameter('STATUT_EN_ATTENTE')) {
//                $this->addFlash('danger', 'L\'entretien a déjà été validé. Vous ne pouvez plus le supprimer.');
//
//                return $this->redirectToRoute('entretien_show', [
//                    'id' => $entretien->getId()
//                ]);
//            }
//            if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $entretien->getChargeAffaire() != $this->getUser()) {
//                $this->addFlash('danger', 'Vous n\'êtes pas sur cet entretien.');
//
//                return $this->redirectToRoute('entretien_index');
//            }
//
//            $entityManager->remove($entretien);
//            $entityManager->flush();
//        }
//
//        return $this->redirectToRoute('entretien_index');
//    }

    /**
     * @Security("is_granted('ROLE_CLIENT') or is_granted('ROLE_ADMIN')")
     * @Route("/{id}/annule", name="entretien_annule", methods={"GET","POST"})
     * @param Request $request
     * @param Entretien $entretien
     * @return Response
     */
    public function annule(Request $request, Entretien $entretien)
    {
        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $entretien->getChargeAffaire() != $this->getUser()) {
            $this->addFlash('danger', 'Vous n\'êtes pas sur cet entretien.');

            return $this->redirectToRoute('entretien_index');
        }

        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && !in_array($entretien->getStatut()
                                                                                        ->getId(), [
                $this->getParameter('STATUT_EN_ATTENTE'),
                $this->getParameter('STATUT_VALIDE'),
            ])) {
            $this->addFlash('danger', 'Vous ne pouvez plus annuler cet entretien.');

            return $this->redirectToRoute('entretien_show', [
                'id' => $entretien->getId()
            ]);
        }

        if ($this->isGranted($this->getParameter('ROLE_ADMIN')) && !in_array($entretien->getStatut()
                                                                                       ->getId(), [
                $this->getParameter('STATUT_EN_ATTENTE'),
                $this->getParameter('STATUT_VALIDE'),
                $this->getParameter('STATUT_EN_COURS'),
            ])) {
            $this->addFlash('danger', 'Vous ne pouvez plus annuler cet entretien.');

            return $this->redirectToRoute('entretien_admin_show', [
                'id' => $entretien->getId(),
                'client' => $entretien->getChargeAffaire()
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

            $entretien->setStatut($em->getRepository(StatutActivite::class)
                                     ->find($this->getParameter('STATUT_ANNULE')))
                      ->setMotifAnnule($motifAnnule)
            ;

            $em->flush();

            $this->addFlash('success', 'L\'entretien a été annulé.');

            if ($this->isGranted($this->getParameter('ROLE_ADMIN'))) {

                return $this->redirectToRoute('entretien_admin_show', [
                    'id' => $entretien->getId(),
                    'client' => $entretien->getChargeAffaire()
                                          ->getClient()
                                          ->getId(),
                ]);

            } else {
                return $this->redirectToRoute('entretien_show', [
                    'id' => $entretien->getId(),
                ]);
            }
        }

        if ($this->isGranted($this->getParameter('ROLE_ADMIN'))) {
            $urlRetour = $this->generateUrl('entretien_admin_show', [
                'id' => $entretien->getId(),
                'client' => $entretien->getChargeAffaire()
                                      ->getClient()
                                      ->getId(),
            ]);
        } else {
            $urlRetour = $this->generateUrl('entretien_show', [
                'id' => $entretien->getId(),
            ]);
        }


        return $this->render('activite/annule.html.twig', [
            'activite' => $entretien,
            'form' => $form->createView(),
            'client' => $entretien->getChargeAffaire()
                                  ->getClient(),
            'titre' => 'Annulation de l\'entretien n°' . $entretien->getId(),
            'urlRetour' => $urlRetour,
        ]);

    }


}
