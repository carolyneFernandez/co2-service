<?php

namespace App\Controller;

use App\Data\FiltreTravauxData;
use App\Entity\Client;
use App\Entity\Depannage;
use App\Entity\Travaux;
use App\Entity\TravauxHoraireTechnicien;
use App\Entity\StatutActivite;
use App\Entity\User;
use App\Form\AnnuleActiviteType;
use App\Form\TravauxHoraireTechnicienType;
use App\Form\FiltreTravauxType;
use App\Form\TravauxHoraireTechnicienDebutType;
use App\Form\TravauxHoraireTechnicienFinType;
use App\Form\TravauxType;
use App\Form\UploadFileType;
use App\Repository\StatutActiviteRepository;
use App\Repository\TravauxRepository;
use App\Service\FileUploader;
use App\Service\MailService;
use App\Service\SMSService;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use phpDocumentor\Reflection\Types\Void_;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TravauxController extends AbstractController
{
    private $SMSService;

    /**
     * TravauxController constructor.
     * @param $SMSService
     */
    public function __construct(SMSService $SMSService)
    {
        $this->SMSService = $SMSService;
    }


    /**
     * @Route("/travaux", name="travaux_index", methods={"GET","POST"})
     * @Security("is_granted('ROLE_CLIENT') or is_granted('ROLE_TECHNICIEN')")
     * @param Request $request
     * @param TravauxRepository $travauxRepository
     * @param PaginatorInterface $paginator
     * @return Response
     * @throws Exception
     */
    public function index(Request $request, TravauxRepository $travauxRepository, PaginatorInterface $paginator): Response
    {
        /**@var User $user */
        $user = $this->getUser();

        $filtre = new FiltreTravauxData();
        $formFiltre = $this->createForm(FiltreTravauxType::class, $filtre);
        $formFiltre->handleRequest($request);

        if ($request->request->get('item_pagination'))
            $this->get('session')
                 ->set('itemPerPage', $request->request->get('item_pagination')['maxItemPerPage'])
            ;


        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN'))) {

            if ($request->query->get('old')) {
                $travauxesQuery = $travauxRepository->findOldByTechnicien($user, $filtre);
            } else {
                $travauxesQuery = $travauxRepository->findNextByTechnicien($user, $filtre);
            }

        } elseif ($this->isGranted($this->getParameter('ROLE_CLIENT'))) {
            $filtre->chargeAffaires[] = $user;
            $travauxesQuery = $travauxRepository->getQuery($filtre);
        }

        $travaux = $paginator->paginate($travauxesQuery, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            $this->get('session')
                 ->get('itemPerPage', $this->getParameter('itemPerPage')), // Nombre de résultats par page
            [
                'defaultSortFieldName' => 't.dateDebutRetenue',
                'defaultSortDirection' => 'desc'
            ]);

        return $this->render('travaux/index.html.twig', [
            'travauxes' => $travaux,
            'role' => $user->getRoles(),
            'formFiltre' => $formFiltre->createView(),
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_CLIENT')")
     * @Route("/travaux/new", name="travaux_new", methods={"GET","POST"})
     * @param Request $request
     * @param MailService $mailService
     * @return Response
     */
    public function new(Request $request, MailService $mailService): Response
    {
        /**@var User $user */
        $user = $this->getUser();
        $travaux = new Travaux();
        if (in_array($this->getParameter('ROLE_CLIENT'), $user->getRoles())) {
            $travaux->setChargeAffaire($user);
        }
        /**@var StatutActivite $statut */
        $statut = $this->getDoctrine()
                       ->getRepository(StatutActivite::class)
                       ->find($this->getParameter('STATUT_EN_ATTENTE'))
        ;
        $travaux->setStatut($statut);
        $form = $this->createForm(TravauxType::class, $travaux);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()
                                  ->getManager()
            ;
            $entityManager->persist($travaux);
            $entityManager->flush();


            if ($this->isGranted($this->getParameter('ROLE_CLIENT'))) {

                $mailService->setAndSendMail($this->getParameter('MAIL_ADMIN'), "Nouvelle demande de travaux", 'mail/demande_travaux.html.twig', [
                    'travaux' => $travaux,
                    'user' => $user
                ]);

            }

            $techniciens = $travaux->getTechniciens();
            $valide = false;

            if (in_array($this->getParameter('ROLE_ADMIN'), $user->getRoles())) {
                /** @var User $technicien */
                foreach ($techniciens as $technicien) {
                    $mailService->setAndSendMail($technicien->getEmailPro(), 'Affectation travaux', 'mail/affectation_travaux.html.twig', [
                        'travaux' => $travaux,
                        'technicien' => $technicien
                    ]);
                    $this->sendSMS($travaux, $technicien);
                    $valide = true;
                }

                if ($valide == true) {
                    $statutValide = $entityManager->getRepository(StatutActivite::class)
                                                  ->find($this->getParameter('STATUT_VALIDE'))
                    ;
                    $travaux->setStatut($statutValide);
                    $entityManager->flush();
                }

                return $this->redirectToRoute('travaux_client_list', [
                    'client' => $travaux->getChargeAffaire()
                                        ->getClient()
                                        ->getId()
                ]);
            }


            return $this->redirectToRoute('travaux_index');
        }

        $client = null;
        if ($request->query->get('client')) {
            $client = $this->getDoctrine()
                           ->getRepository(Client::class)
                           ->find($request->query->get('client'))
            ;
        }

        return $this->render('travaux/new.html.twig', [
            'travaux' => $travaux,
            'form' => $form->createView(),
            'client' => $client
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_CLIENT') or is_granted('ROLE_TECHNICIEN')")
     * @Route("/travaux/{id}", name="travaux_show", methods={"GET","POST"})
     * @param Travaux $travaux
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function show(Travaux $travaux, Request $request, FileUploader $fileUploader): Response
    {

        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $travaux->getChargeAffaire() != $this->getUser()) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à ce travaux.');

            return $this->redirectToRoute('travaux_index');
        }

        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) && !$travaux->getTechniciens()
                                                                                 ->contains($this->getUser())) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à ce travaux.');

            return $this->redirectToRoute('travaux_index');
        }


        $params = [
            'travaux' => $travaux,
            'client' => $travaux->getChargeAffaire()
                                ->getClient()
        ];

        $now = new \DateTime();
        $em = $this->getDoctrine()
                   ->getManager()
        ;


        // Formulaires d'horaires pour les techniciens
        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) && in_array($travaux->getStatut()
                                                                                         ->getId(), [
                $this->getParameter('STATUT_VALIDE'),
                $this->getParameter('STATUT_EN_COURS')
            ]) && $travaux->getDateDebutRetenue() && $travaux->getDateDebutRetenue()
                                                             ->format('Y-m-d') <= $now->format('Y-m-d')) {


            /** @var TravauxHoraireTechnicien $horaire */
            $horaire = $em->getRepository(TravauxHoraireTechnicien::class)
                          ->findOneByTravauxAndTechnicien($travaux, $this->getUser())
            ;

            if (!$horaire) {
                $horaire = new TravauxHoraireTechnicien();
                $horaire->setTravaux($travaux)
                        ->setDateDebut(new \DateTime())
                        ->setTechnicien($this->getUser())
                ;
                $formHoraire = $this->createForm(TravauxHoraireTechnicienDebutType::class, $horaire);
            } else {
                $formHoraire = $this->createForm(TravauxHoraireTechnicienFinType::class, $horaire);
            }

            $formHoraire->handleRequest($request);
            if ($formHoraire->isSubmitted() && $formHoraire->isValid()) {

                if (!$horaire->getId()) {
                    $em->persist($horaire);
                }

                $em->flush();

                return $this->redirectToRoute('travaux_show', ['id' => $travaux->getId()]);

            }


            $params['formHoraire'] = $formHoraire->createView();
        }


        // Affichages des horaires saisies
        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN'))) {
            $horaires = $em->getRepository(TravauxHoraireTechnicien::class)
                           ->findBy([
                               'technicien' => $this->getUser(),
                               'travaux' => $travaux
                           ], ['dateDebut' => 'ASC'])
            ;

        } else {
            $horaires = $em->getRepository(TravauxHoraireTechnicien::class)
                           ->findBy(['travaux' => $travaux], ['dateDebut' => 'ASC'])
            ;
        }


        // Formulaire ajout horaire de l'admin
        if ($this->isGranted($this->getParameter('ROLE_ADMIN')) && in_array($travaux->getStatut()
                                                                                    ->getId(), [
                $this->getParameter('STATUT_VALIDE'),
                $this->getParameter('STATUT_EN_COURS'),
                $this->getParameter('STATUT_TERMINE'),
            ])) {

            $horaire = new TravauxHoraireTechnicien();
            $horaire->setTravaux($travaux)
                    ->setDateDebut(new \DateTime())
            ;
            $formHoraireNew = $this->createForm(TravauxHoraireTechnicienType::class, $horaire);
            $formHoraireNew->handleRequest($request);

            if ($formHoraireNew->isSubmitted() && $formHoraireNew->isValid()) {
                $em->persist($horaire);
                $em->flush();

                return $this->redirectToRoute('travaux_show', ['id' => $travaux->getId()]);
            }

            $params['formHoraireNew'] = $formHoraireNew->createView();

        }

        $dir = $this->getParameter('dossier_fichier_travaux') . $travaux->getId();
        $dir = $fileUploader->replaceVariableClientDir($dir, $travaux->getChargeAffaire()
                                                                     ->getClient()
                                                                     ->getId(), $travaux->getChargeAffaire()
                                                                                        ->getId());

        $isPaye = false;


        if ($this->isGranted($this->getParameter('ROLE_ADMIN')) || $this->isGranted($this->getParameter('ROLE_TECHNICIEN'))) {
            if ($travaux->getFactureTravauxLigne() && $travaux->getFactureTravauxLigne()
                                                              ->getFacture() && $travaux->getFactureTravauxLigne()
                                                                                        ->getFacture()
                                                                                        ->getIsPaye()) {
                $isPaye = true;
            }

            if (!$isPaye) {

                $formUploadFile = $this->createForm(UploadFileType::class);
                $formUploadFile->handleRequest($request);


                if ($formUploadFile->isSubmitted() && $formUploadFile->isValid()) {
                    $files = $formUploadFile['files']->getData();
                    foreach ($files as $file) {
                        $fileUploader->upload($file, $dir, false);
                    }

                    return $this->redirectToRoute('travaux_show', [
                        'id' => $travaux->getId(),
                        //                        'client' => $travaux->getId(),
                    ]);
                }

                $params['formUploadFile'] = $formUploadFile->createView();
            }

        }

        if ($travaux->getTravauxHoraireTechniciens()
                    ->count() > 0 && $travaux->getStatut()
                                             ->getId() == $this->getParameter('STATUT_VALIDE')) {
            $travaux->setStatut($em->getRepository(StatutActivite::class)
                                   ->find($this->getParameter('STATUT_EN_COURS')));
            $em->flush();
        }


        $files = $fileUploader->scanDir($dir);
        $params['files'] = $files;


        $params['horaires'] = $horaires;


        return $this->render('travaux/show.html.twig', $params);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_CLIENT')")
     * @Route("/travaux/{id}/edit", name="travaux_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Travaux $travaux
     * @param MailService $mailService
     * @param TravauxRepository $travauxRepository
     * @return Response
     */
    public function edit(Request $request, Travaux $travaux, MailService $mailService, TravauxRepository $travauxRepository): Response
    {

        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $travaux->getChargeAffaire() != $this->getUser()) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à ce travaux.');

            return $this->redirectToRoute('travaux_index');
        }

        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && !in_array($travaux->getStatut()
                                                                                      ->getId(), [
                $this->getParameter('STATUT_EN_ATTENTE'),
                $this->getParameter('STATUT_VALIDE'),
                $this->getParameter('STATUT_EN_COURS'),
            ])) {

            $this->addFlash('danger', 'Vous ne pouvez plus modifier ce travaux.');

            return $this->redirectToRoute('travaux_show', [
                'id' => $travaux->getId()
            ]);
        }

        if (!in_array($travaux->getStatut()
                              ->getId(), [
            $this->getParameter('STATUT_EN_ATTENTE'),
            $this->getParameter('STATUT_VALIDE'),
            $this->getParameter('STATUT_EN_COURS'),
            $this->getParameter('STATUT_TERMINE')
        ])) {

            $this->addFlash('danger', 'Vous ne pouvez plus modifier ce travaux.');

            return $this->redirectToRoute('travaux_show', [
                'id' => $travaux->getId()
            ]);
        }


        /** @var User $user */
        $user = $this->getUser();
        $oldTravaux = clone $travaux;
        $oldTechniciens = clone $oldTravaux->getTechniciens();

        if ($this->isGranted($this->getParameter('ROLE_ADMIN')) && $travaux->getDateDebutRetenue() == null) {
            $travaux->setDateDebutRetenue($travaux->getDateDebutSouhaitee());
        }

        $form = $this->createForm(TravauxType::class, $travaux);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($this->isGranted($this->getParameter('ROLE_ADMIN')) && $travaux->getTechniciens()
                                                                               ->count() > 0 && $travaux->getStatut()
                                                                                                        ->getId() == $this->getParameter('STATUT_EN_ATTENTE')) {
                /**@var StatutActivite $statut */
                $statut = $this->getDoctrine()
                               ->getRepository(StatutActivite::class)
                               ->find($this->getParameter('STATUT_VALIDE'))
                ;
                $travaux->setStatut($statut);
            } elseif ($travaux->getTechniciens()
                              ->count() == 0 && $travaux->getStatut()
                                                        ->getId() == $this->getParameter('STATUT_VALIDE')) {
                $statut = $this->getDoctrine()
                               ->getRepository(StatutActivite::class)
                               ->find($this->getParameter('STATUT_EN_ATTENTE'))
                ;
                $travaux->setStatut($statut);
            }


            $this->getDoctrine()
                 ->getManager()
                 ->flush()
            ;

            if ($this->isGranted($this->getParameter('ROLE_ADMIN'))) {

                $techniciens = $travaux->getTechniciens();

                /** @var User $technicien */
                foreach ($techniciens as $technicien) {

                    if (!$oldTechniciens->contains($technicien)) {
                        $mailService->setAndSendMail($technicien->getEmailPro(), 'Affectation travaux', 'mail/affectation_travaux.html.twig', [
                            'travaux' => $travaux,
                            'technicien' => $technicien
                        ]);
                        $this->sendSMS($travaux, $technicien);
                    }

                }

            }

            return $this->redirectToRoute('travaux_show', [
                'id' => $travaux->getId()
            ]);
        }

        return $this->render('travaux/edit.html.twig', [
            'travaux' => $travaux,
            'form' => $form->createView(),
            'client' => $travaux->getChargeAffaire()
                                ->getClient()
        ]);
    }

//    /**
//     * @Security("is_granted('ROLE_ADMIN')")
//     * @Route("/{id}", name="travaux_delete", methods={"DELETE"})
//     * @param Request $request
//     * @param Travaux $travaux
//     * @return Response
//     */
//    public function delete(Request $request, Travaux $travaux): Response
//    {
//        if ($this->isCsrfTokenValid('delete' . $travaux->getId(), $request->request->get('_token'))) {
//            $entityManager = $this->getDoctrine()
//                                  ->getManager()
//            ;
//            $entityManager->remove($travaux);
//            $entityManager->flush();
//        }
//
//        return $this->redirectToRoute('travaux_index');
//    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/travaux/{client}/travaux", name="travaux_client_list", methods={"GET"})
     * @param Request $request
     * @param TravauxRepository $travauxRepository
     * @param Client $client
     * @return Response
     * @throws Exception
     */
    public function clientTravauxList(Request $request, TravauxRepository $travauxRepository, Client $client, PaginatorInterface $paginator): Response
    {

        $filtre = new FiltreTravauxData();
        $filtre->client = $client;
        $formFiltre = $this->createForm(FiltreTravauxType::class, $filtre);
        $formFiltre->handleRequest($request);

        $travauxesQuery = $travauxRepository->getQuery($filtre);

        if ($request->request->get('item_pagination'))
            $this->get('session')
                 ->set('itemPerPage', $request->request->get('item_pagination')['maxItemPerPage'])
            ;

//        if ($request->query->get('old')) {
//            $travauxesQuery = $travauxRepository->findOldByClient($client, $filtre);
//        } else {
//            $travauxesQuery = $travauxRepository->findNextByClient($client, $filtre);
//        }

        $travaux = $paginator->paginate($travauxesQuery, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            $this->get('session')
                 ->get('itemPerPage', $this->getParameter('itemPerPage')), // Nombre de résultats par page
            [
                'defaultSortFieldName' => 't.dateDebutRetenue',
                'defaultSortDirection' => 'desc'
            ]);

        return $this->render('travaux/index.html.twig', [
            'travauxes' => $travaux,
            'client' => $client,
            'formFiltre' => $formFiltre->createView(),
        ]);
    }


    /**
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TECHNICIEN')")
     * @Route("/travaux/{client}/{id}/cloturer", name="travaux_cloture", methods={"GET"})
     * @param Travaux $travaux
     * @param Client $client
     * @return RedirectResponse
     */
    public function clotureTravaux(Travaux $travaux, Client $client, MailService $mailService)
    {


        if (!in_array($travaux->getStatut()
                              ->getId(), [
            $this->getParameter('STATUT_EN_ATTENTE'),
            $this->getParameter('STATUT_VALIDE'),
            $this->getParameter('STATUT_EN_COURS')
        ])) {
            $this->addFlash('danger', 'L\'travaux a déjà été clôturé.');

            return $this->redirectToRoute('travaux_show', [
                'id' => $travaux->getId(),
            ]);

        } elseif ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) && !in_array($this->getUser(), $travaux->getTechniciens()
                                                                                                                  ->toArray())) {
            $this->addFlash('danger', 'Vous n\'êtes pas sur ce dépannage.');

            return $this->redirectToRoute('depannage_index', [
                'id' => $travaux->getId(),
                'client' => $client->getId()
            ]);
        }

        $em = $this->getDoctrine()
                   ->getManager()
        ;

        $horairesNonTermine = $em->getRepository(TravauxHoraireTechnicien::class)
                                 ->findBy([
                                     'dateFin' => null,
                                     'travaux' => $travaux
                                 ])
        ;

        if (count($horairesNonTermine) > 0) {
            foreach ($horairesNonTermine as $horaireNonTermine) {
                $this->addFlash('danger', $horaireNonTermine->getTechnicien()
                                                            ->getNomPrenom() . ' n\' pas remplit sont horaire de fin.');

            }

            return $this->redirectToRoute('travaux_show', [
                'id' => $travaux->getId(),
                'client' => $client->getId()
            ]);
        }

        $travaux->setStatut($em->getRepository(StatutActivite::class)
                               ->find($this->getParameter('STATUT_TERMINE')));
        $em->flush();
        $this->addFlash('success', 'Le travaux a été clôturé.');

        $mailService->setAndSendMail($travaux->getChargeAffaire()
                                             ->getEmailPro(), 'Fin des travaux', 'mail/travaux_termine.html.twig', ['travaux' => $travaux]);

        return $this->redirectToRoute('travaux_show', [
            'id' => $travaux->getId(),
            'client' => $client->getId()
        ]);

    }


    /**
     * @Security("is_granted('ROLE_CLIENT') or is_granted('ROLE_ADMIN')")
     * @Route("/travaux/{id}/annule", name="travaux_annule", methods={"GET","POST"})
     * @param Request $request
     * @param Travaux $travaux
     * @return Response
     */
    public function annule(Request $request, Travaux $travaux)
    {
        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $travaux->getChargeAffaire() != $this->getUser()) {
            $this->addFlash('danger', 'Vous n\'êtes pas sur ce travaux.');

            return $this->redirectToRoute('travaux_index');
        }

        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && !in_array($travaux->getStatut()
                                                                                      ->getId(), [
                $this->getParameter('STATUT_EN_ATTENTE'),
                $this->getParameter('STATUT_VALIDE'),
            ])) {
            $this->addFlash('danger', 'Vous ne pouvez plus annuler ce travaux.');

            return $this->redirectToRoute('travaux_show', [
                'id' => $travaux->getId()
            ]);
        }

        if ($this->isGranted($this->getParameter('ROLE_ADMIN')) && !in_array($travaux->getStatut()
                                                                                     ->getId(), [
                $this->getParameter('STATUT_EN_ATTENTE'),
                $this->getParameter('STATUT_VALIDE'),
                $this->getParameter('STATUT_EN_COURS'),
            ])) {
            $this->addFlash('danger', 'Vous ne pouvez plus annuler ce travaux.');

            return $this->redirectToRoute('travaux_show', [
                'id' => $travaux->getId(),
                //                'client' => $travaux->getChargeAffaire()
                //                                      ->getClient()
                //                                      ->getId(),
            ]);
        }


        $form = $this->createForm(AnnuleActiviteType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $em = $this->getDoctrine()
                       ->getManager()
            ;

            $motifAnnule = $form['motifAnnulation']->getData();

            $travaux->setStatut($em->getRepository(StatutActivite::class)
                                   ->find($this->getParameter('STATUT_ANNULE')))
                    ->setMotifAnnule($motifAnnule)
            ;

            $em->flush();

            $this->addFlash('success', 'Le travaux a été annulé.');

            if ($this->isGranted($this->getParameter('ROLE_ADMIN'))) {

                return $this->redirectToRoute('travaux_show', [
                    'id' => $travaux->getId(),
                    //                    'client' => $travaux->getChargeAffaire()
                    //                                          ->getClient()
                    //                                          ->getId(),
                ]);

            } else {
                return $this->redirectToRoute('travaux_show', [
                    'id' => $travaux->getId(),
                ]);
            }
        }

        if ($this->isGranted($this->getParameter('ROLE_ADMIN'))) {
            $urlRetour = $this->generateUrl('travaux_show', [
                'id' => $travaux->getId(),
                //                'client' => $travaux->getChargeAffaire()
                //                                      ->getClient()
                //                                      ->getId(),
            ]);
        } else {
            $urlRetour = $this->generateUrl('travaux_show', [
                'id' => $travaux->getId(),
            ]);
        }


        return $this->render('activite/annule.html.twig', [
            'activite' => $travaux,
            'form' => $form->createView(),
            'client' => $travaux->getChargeAffaire()
                                ->getClient(),
            'titre' => 'Annulation de du travaux n°' . $travaux->getId(),
            'urlRetour' => $urlRetour,
        ]);

    }


    private function sendSMS(Travaux $travaux, User $technicien): void
    {
        if ($technicien->getTelephone() != null) {

            $message = "Vous avez été affecté au Travaux n°" . $travaux->getId() . " qui aura lieu le " . $travaux->getDateDebutRetenue()
                                                                                                                  ->format('d/m/Y \\à H\\hi') . ". \nLien vers le travaux : " . $this->generateUrl('travaux_show', ['id' => $travaux->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            $this->SMSService->sendSMS($technicien->getTelephone(), $message);

        }

    }

    /**
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TECHNICIEN')")
     * @Route("/travaux/{client}/{id}/delete_file", name="travaux_admin_delete_file", methods={"GET","POST"})
     * @param Request $request
     * @param Client $client
     * @param travaux $travaux
     * @param MailService $mailService
     * @param FileUploader $fileUploader
     * @return RedirectResponse
     */
    public function deleteFile(Request $request, Client $client, travaux $travaux, MailService $mailService, FileUploader $fileUploader)
    {

        /** @var User $technicien */
        $technicien = $this->getUser();

        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN'))) {

            if (!$travaux->getTechniciens()
                         ->contains($technicien)) {
                $this->addFlash('danger', 'Vous n\'avez pas accès à ce travaux.');

                return $this->redirectToRoute('index');
            }

            if (in_array($travaux->getStatut()
                                 ->getId(), [
                $this->getParameter('STATUT_TERMINE'),
            ])) {
                $this->addFlash('danger', 'Ce travaux est terminé, vous ne pouvez plus supprimer de fichier.');

                return $this->redirectToRoute('travaux_show', [
                    'id' => $travaux->getId()
                ]);

            }

        }

        if (in_array($travaux->getStatut()
                             ->getId(), [
            $this->getParameter('STATUT_FACTURE'),
        ])) {
            $this->addFlash('danger', 'Ce travaux est terminé, vous ne pouvez plus supprimer de fichier.');

            return $this->redirectToRoute('travaux_show', [
                'id' => $travaux->getId()
            ]);

        }

        $file = $request->query->get('file');

        if (!$file) {
            $this->addFlash('danger', 'Aucun fichier a supprimer.');
        }


        $dir = $this->getParameter('dossier_fichier_travaux') . $travaux->getId();
        $dir = $fileUploader->replaceVariableClientDir($dir, $client->getId(), $travaux->getChargeAffaire()
                                                                                       ->getId());

        $filename = $dir . '/' . $file;

        if (is_file($filename)) {
            unlink($filename);
            $this->addFlash('success', 'Le fichier a été supprimé.');
        } else {
            $this->addFlash('danger', 'Ce fichier n\'existe pas.');
        }

        return $this->redirectToRoute('travaux_show', [
            'id' => $travaux->getId(),
        ]);


    }


    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/travaux-horaire-edit/{id}", name="travaux_horaire_edit", methods={"GET","POST"})
     * @param Request $request
     * @param TravauxHoraireTechnicien $travauxHoraireTechnicien
     * @return RedirectResponse|Response
     */
    public function editHoraire(Request $request, TravauxHoraireTechnicien $travauxHoraireTechnicien)
    {
        $travaux = $travauxHoraireTechnicien->getTravaux();

        if ($travaux->getStatut()
                    ->getId() == $this->getParameter('STATUT_FACTURE')) {
            $this->addFlash('danger', 'Ce travaux a été facturé. Aucune modification possible.');

            return $this->redirectToRoute('travaux_show', [
                'id' => $travaux->getId(),
                'client' => $travaux->getChargeAffaire()
                                    ->getClient()
                                    ->getId(),
            ]);
        }

        $form = $this->createForm(TravauxHoraireTechnicienType::class, $travauxHoraireTechnicien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->getDoctrine()
                 ->getManager()
                 ->flush()
            ;

            $this->addFlash('success', 'L\'horaire a bien été modifié.');

            return $this->redirectToRoute('travaux_show', [
                'id' => $travaux->getId(),
                'client' => $travaux->getChargeAffaire()
                                    ->getClient()
                                    ->getId(),
            ]);

        }

        return $this->render('travaux/horaire_edit.html.twig', [
            'form' => $form->createView(),
            'client' => $travaux->getChargeAffaire()
                                ->getClient(),
            'travaux' => $travaux,
            'horaire' => $travauxHoraireTechnicien,
        ]);

    }


}
