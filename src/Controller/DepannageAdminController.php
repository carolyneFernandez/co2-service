<?php

namespace App\Controller;

use App\Data\FiltreDepannageData;
use App\Entity\Client;
use App\Entity\Depannage;
use App\Entity\DepannageHoraireTechnicien;
use App\Entity\Livraison;
use App\Entity\StatutActivite;
use App\Entity\User;
use App\Form\DepannageHoraireTechnicienDebutType;
use App\Form\DepannageHoraireTechnicienFinType;
use App\Form\DepannageHoraireTechnicienType;
use App\Form\FiltreDepannageDataType;
use App\Form\DepannageType;
use App\Form\UploadFileType;
use App\Repository\DepannageRepository;
use App\Service\FileUploader;
use App\Service\MailService;
use App\Service\SMSService;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;



class DepannageAdminController extends AbstractController
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
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/depannages/{client}/index", name="depannage_index_admin", methods={"GET","POST"})
     * @param Request $request
     * @param Client $client
     * @param DepannageRepository $depannageRepository
     * @param PaginatorInterface $paginator
     * @return Response
     * @throws Exception
     */
    public function index(Request $request, Client $client, DepannageRepository $depannageRepository, PaginatorInterface $paginator): Response
    {
        /*@var User $user*/
        $user = $this->getUser();

        $filtre = new FiltreDepannageData();
        $filtre->client = $client;
        $formFiltre = $this->createForm(FiltreDepannageDataType::class, $filtre);
        $formFiltre->handleRequest($request);

        if ($request->request->get('item_pagination'))
            $this->get('session')
                 ->set('itemPerPage', $request->request->get('item_pagination')['maxItemPerPage'])
            ;

        $depannagesQuery = $depannageRepository->getQuery($filtre);
//        if ($request->query->get('old')) {
//            $depannagesQuery = $depannageRepository->findOldByClient($client, $filtre);
//        } else {
//            $depannagesQuery = $depannageRepository->findNextByClient($client, $filtre);
//        }


        $depannages = $paginator->paginate($depannagesQuery, $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            $this->get('session')
                 ->get('itemPerPage', $this->getParameter('itemPerPage')), // Nombre de résultats par page
            [
                'defaultSortFieldName' => 'd.dateRetenue',
                'defaultSortDirection' => 'desc'
            ]);

        return $this->render('depannage/index.html.twig', [
            'depannages' => $depannages,
            'client' => $client,
            'formFiltre' => $formFiltre->createView()
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/depannages/{client}/new", name="depannage_new_admin", methods={"GET","POST"})
     * @param Client $client
     * @param Request $request
     * @param MailService $mailService
     * @return Response
     */
    public function new(Client $client, Request $request, MailService $mailService): Response
    {
        $depannage = new Depannage();

        $form = $this->createForm(DepannageType::class, $depannage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager()
            ;
            if ($depannage->getTechniciens()
                          ->count() > 0) {
                $statut = $this->getDoctrine()
                               ->getRepository(StatutActivite::class)
                               ->find($this->getParameter('STATUT_VALIDE'))
                ;
            } else {
                $statut = $this->getDoctrine()
                               ->getRepository(StatutActivite::class)
                               ->find($this->getParameter('STATUT_EN_ATTENTE'))
                ;
            }
            $depannage->setStatut($statut);
            $em->persist($depannage);
            $em->flush();

            $techniciens = $depannage->getTechniciens();

            /** @var User $technicien */
            foreach ($techniciens as $technicien) {

                $mailService->setAndSendMail($technicien->getEmailPro(), 'Affection à une depannage', 'mail/affectation_depannage.html.twig', [
                    'user' => $this->getUser(),
                    'depannage' => $depannage,
                ]);
                $this->sendSMS($depannage, $technicien);

            }

//            foreach ($depannage->getTechniciens() as $tech) {
//                $mailService->setAndSendMail($tech->getEmailPro(), 'Affection à une depannage', 'mail/affectation_depannage.html.twig',
//                    ['user' => $this->getUser(), 'depannage' => $depannage, 'date' => $dateRetenue]);
//            }

            return $this->redirectToRoute('depannage_show_admin', array(
                'client' => $client->getId(),
                'id' => $depannage->getId()
            ));
        }

        return $this->render('depannage/new.html.twig', [
            'depannage' => $depannage,
            'form' => $form->createView(),
            'client' => $client
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/depannages/{client}/edit/{id}", name="depannage_edit_admin", methods={"GET","POST"})
     * @param Client $client
     * @param Request $request
     * @param Depannage $depannage
     * @param MailService $mailService
     * @return Response
     */
    public function edit(Client $client, Request $request, Depannage $depannage, MailService $mailService): Response
    {
        $oldDepannage = clone $depannage;
        $oldTechniciens = clone $oldDepannage->getTechniciens();


        $form = $this->createForm(DepannageType::class, $depannage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $statut = $this->getDoctrine()
                           ->getRepository(StatutActivite::class)
                           ->find($this->getParameter('STATUT_VALIDE'))
            ;
            $depannage->setStatut($statut);
            $this->getDoctrine()
                 ->getManager()
                 ->flush()
            ;

            $techniciens = $depannage->getTechniciens();

            /** @var User $technicien */
            foreach ($techniciens as $technicien) {

                if (!$oldTechniciens->contains($technicien)) {
                    $mailService->setAndSendMail($technicien->getEmailPro(), 'Affection à un dépannage', 'mail/affectation_depannage.html.twig', [
                        'user' => $this->getUser(),
                        'depannage' => $depannage,
                    ]);
                    $this->sendSMS($depannage, $technicien);
                }

            }

//            foreach ($depannage->getTechniciens() as $tech) {
//                $mailService->setAndSendMail($tech->getEmailPro(), 'Affection à une depannage', 'mail/affectation_depannage.html.twig',
//                    ['user' => $this->getUser(), 'depannage' => $depannage, 'date' => $dateRetenue]);
//            }

            return $this->redirectToRoute('depannage_show_admin', array(
                'client' => $client->getId(),
                'id' => $depannage->getId()
            ));
        }

        return $this->render('depannage/edit.html.twig', [
            'depannage' => $depannage,
            //            'user' => $this->getUser(),
            'form' => $form->createView(),
            'client' => $client
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TECHNICIEN')")
     * @Route("/depannages/{client}/{id}", name="depannage_show_admin", methods={"GET","POST"})
     * @param Request $request
     * @param Client $client
     * @param Depannage $depannage
     * @param MailService $mailService
     * @param FileUploader $fileUploader
     * @return Response
     * @throws Exception
     */
    public function show(Request $request, Client $client, Depannage $depannage, MailService $mailService, FileUploader $fileUploader): Response
    {

        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) && !$depannage->getTechniciens()
                                                                                   ->contains($this->getUser())) {
            $this->addFlash('danger', 'Vous n\'êtes pas affectué à cette depannage.');

            return $this->redirectToRoute('depannage_index', []);
        }


        $params = [
            'depannage' => $depannage,
            'user' => $this->getUser(),
            'client' => $client
        ];

        $now = new \DateTime();
        $em = $this->getDoctrine()
                   ->getManager()
        ;

        // Formulaires d'horaires pour les techniciens
        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) && in_array($depannage->getStatut()
                                                                                           ->getId(), [
                $this->getParameter('STATUT_VALIDE'),
                $this->getParameter('STATUT_EN_COURS')
            ]) && $depannage->getDateRetenue() && $depannage->getDateRetenue() >= $now->format('Y-m-d')) {


            /** @var DepannageHoraireTechnicien $horaire */
            $horaire = $em->getRepository(DepannageHoraireTechnicien::class)
                          ->findOneByDepannageAndTechnicien($depannage, $this->getUser())
            ;

            if (!$horaire) {
                $horaire = new DepannageHoraireTechnicien();
                $horaire->setDepannage($depannage)
                        ->setDateDebut(new \DateTime())
                        ->setTechnicien($this->getUser())
                ;
                $formHoraire = $this->createForm(DepannageHoraireTechnicienDebutType::class, $horaire);
            } else {
                $formHoraire = $this->createForm(DepannageHoraireTechnicienFinType::class, $horaire);
            }

            $formHoraire->handleRequest($request);
            if ($formHoraire->isSubmitted() && $formHoraire->isValid()) {

                if (!$horaire->getId()) {
                    $em->persist($horaire);
                }

                $em->flush();

                return $this->redirectToRoute('depannage_show_admin', [
                    'id' => $depannage->getId(),
                    'client' => $client->getId()
                ]);

            }


            $params['formHoraire'] = $formHoraire->createView();
        }


        // Affichages des horaires saisies
        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN'))) {
            $horaires = $em->getRepository(DepannageHoraireTechnicien::class)
                           ->findBy([
                               'technicien' => $this->getUser(),
                               'depannage' => $depannage
                           ], ['dateDebut' => 'ASC'])
            ;

        } else {
            $horaires = $em->getRepository(DepannageHoraireTechnicien::class)
                           ->findBy(['depannage' => $depannage], ['dateDebut' => 'ASC'])
            ;
        }


        // Formulaire ajout horaire de l'admin
        if ($this->isGranted($this->getParameter('ROLE_ADMIN')) && in_array($depannage->getStatut()
                                                                                      ->getId(), [
                $this->getParameter('STATUT_VALIDE'),
                $this->getParameter('STATUT_EN_COURS'),
                $this->getParameter('STATUT_TERMINE'),
            ])) {

            $horaire = new DepannageHoraireTechnicien();
            $horaire->setDepannage($depannage)
                    ->setDateDebut(new \DateTime())
            ;
            $formHoraireNew = $this->createForm(DepannageHoraireTechnicienType::class, $horaire);
            $formHoraireNew->handleRequest($request);

            if ($formHoraireNew->isSubmitted() && $formHoraireNew->isValid()) {
                $em->persist($horaire);
                $em->flush();

                return $this->redirectToRoute('depannage_show_admin', [
                    'id' => $depannage->getId(),
                    'client' => $client->getId()
                ]);
            }

            $params['formHoraireNew'] = $formHoraireNew->createView();

        }

        if ($depannage->getDepannageHoraireTechniciens()
                      ->count() > 0 && $depannage->getStatut()
                                                 ->getId() == $this->getParameter('STATUT_VALIDE')) {
            $depannage->setStatut($em->getRepository(StatutActivite::class)
                                     ->find($this->getParameter('STATUT_EN_COURS')));
            $em->flush();
        }

        $dir = $this->getParameter('dossier_fichier_depannages') . $depannage->getId();
        $dir = $fileUploader->replaceVariableClientDir($dir, $client->getId(), $depannage->getChargeAffaire()
                                                                                         ->getId());

        $isPaye = false;
        if ($depannage->getFactureDepannageLigne() && $depannage->getFactureDepannageLigne()
                                                                ->getFacture() && $depannage->getFactureDepannageLigne()
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

                return $this->redirectToRoute('depannage_show_admin', [
                    'id' => $depannage->getId(),
                    'client' => $client->getId(),
                ]);
            }

            $params['formUploadFile'] = $formUploadFile->createView();
        }

        $files = $fileUploader->scanDir($dir);
        $params['files'] = $files;

        $params['horaires'] = $horaires;


        return $this->render('depannage/show.html.twig', $params);
    }


    /**
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TECHNICIEN')")
     * @Route("/depannages/{client}/{id}/cloturer", name="depannage_cloture", methods={"GET"})
     * @param Depannage $depannage
     * @param Client $client
     * @param MailService $mailService
     * @return RedirectResponse
     */
    public function clotureDepannage(Depannage $depannage, Client $client, MailService $mailService)
    {


        if (!in_array($depannage->getStatut()
                                ->getId(), [
            $this->getParameter('STATUT_EN_ATTENTE'),
            $this->getParameter('STATUT_VALIDE'),
            $this->getParameter('STATUT_EN_COURS')
        ])) {
            $this->addFlash('danger', 'Le dépannage a déjà été clôturé.');

            return $this->redirectToRoute('depannage_show_admin', [
                'id' => $depannage->getId(),
                'client' => $client->getId()
            ]);

        } elseif ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) && !in_array($this->getUser(), $depannage->getTechniciens()
                                                                                                                    ->toArray())) {
            $this->addFlash('danger', 'Vous n\'êtes pas sur ce dépannage.');

            return $this->redirectToRoute('depannage_index', [
                'id' => $depannage->getId(),
                'client' => $client->getId()
            ]);
        }

        $em = $this->getDoctrine()
                   ->getManager()
        ;

        $horairesNonTermine = $em->getRepository(DepannageHoraireTechnicien::class)
                                 ->findBy([
                                     'dateFin' => null,
                                     'depannage' => $depannage
                                 ])
        ;

        if (count($horairesNonTermine) > 0) {
            foreach ($horairesNonTermine as $horaireNonTermine) {
                $this->addFlash('danger', $horaireNonTermine->getTechnicien()
                                                            ->getNomPrenom() . ' n\' pas remplit sont horaire de fin.');

            }

            return $this->redirectToRoute('depannage_show_admin', [
                'id' => $depannage->getId(),
                'client' => $client->getId()
            ]);
        }

        $depannage->setStatut($em->getRepository(StatutActivite::class)
                                 ->find($this->getParameter('STATUT_TERMINE')));
        $em->flush();
        $this->addFlash('success', 'Le dépannage a été clôturé.');

        $mailService->setAndSendMail($depannage->getChargeAffaire()
                                               ->getEmailPro(), 'Fin de dépannage', 'mail/depannage_termine.html.twig', ['depannage' => $depannage]);

        return $this->redirectToRoute('depannage_show_admin', [
            'id' => $depannage->getId(),
            'client' => $client->getId()
        ]);

    }

    private function sendSMS(Depannage $depannage, User $technicien): void
    {
        if ($technicien->getTelephone() != null) {

            $message = "Vous avez été affecté au dépannage n°" . $depannage->getId() . " qui aura lieu le " . $depannage->getDateRetenue()
                                                                                                                        ->format('d/m/Y \\à H\\hi') . ". \nLien vers la demande de dépannage : " . $this->generateUrl('depannage_show_admin', ['id' => $depannage->getId(),
                                                                                                                                                                                                                                               'client' => $depannage->getChargeAffaire()
                                                                                                                                                                                                                                                                     ->getClient()
                                                                                                                                                                                                                                                                     ->getId()
                ], UrlGeneratorInterface::ABSOLUTE_URL);

            $this->SMSService->sendSMS($technicien->getTelephone(), $message);

        }

    }


    /**
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TECHNICIEN')")
     * @Route("/depannages/{client}/{id}/delete_file", name="depannage_admin_delete_file", methods={"GET","POST"})
     * @param Request $request
     * @param Client $client
     * @param depannage $depannage
     * @param MailService $mailService
     * @param FileUploader $fileUploader
     * @return RedirectResponse
     */
    public function deleteFile(Request $request, Client $client, depannage $depannage, MailService $mailService, FileUploader $fileUploader)
    {

        /** @var User $technicien */
        $technicien = $this->getUser();

        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN'))) {

            if (!$depannage->getTechniciens()
                           ->contains($technicien)) {
                $this->addFlash('danger', 'Vous n\'avez pas accès à ce depannage.');

                return $this->redirectToRoute('index');
            }

            if (in_array($depannage->getStatut()
                                   ->getId(), [
                $this->getParameter('STATUT_TERMINE'),
            ])) {
                $this->addFlash('danger', 'Ce depannage est terminé, vous ne pouvez plus supprimer de fichier.');

                return $this->redirectToRoute('depannage_show_admin', [
                    'id' => $depannage->getId(),
                    'client' => $depannage->getChargeAffaire()
                                          ->getClient()
                                          ->getId()
                ]);

            }

        }

        if (in_array($depannage->getStatut()
                               ->getId(), [
            $this->getParameter('STATUT_FACTURE'),
        ])) {
            $this->addFlash('danger', 'Ce depannage est terminé, vous ne pouvez plus supprimer de fichier.');

            return $this->redirectToRoute('depannage_show_admin', [
                'id' => $depannage->getId(),
                'client' => $depannage->getChargeAffaire()
                                      ->getClient()
                                      ->getId()
            ]);

        }

        $file = $request->query->get('file');

        if (!$file) {
            $this->addFlash('danger', 'Aucun fichier a supprimer.');
        }


        $dir = $this->getParameter('dossier_fichier_depannages') . $depannage->getId();
        $dir = $fileUploader->replaceVariableClientDir($dir, $client->getId(), $depannage->getChargeAffaire()
                                                                                         ->getId());

        $filename = $dir . '/' . $file;

        if (is_file($filename)) {
            unlink($filename);
            $this->addFlash('success', 'Le fichier a été supprimé.');
        } else {
            $this->addFlash('danger', 'Ce fichier n\'existe pas.');
        }

        return $this->redirectToRoute('depannage_show_admin', [
            'client' => $client->getId(),
            'id' => $depannage->getId(),
        ]);


    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/depannages-horaire-edit/{id}", name="depannage_horaire_edit", methods={"GET","POST"})
     * @param Request $request
     * @param DepannageHoraireTechnicien $depannageHoraireTechnicien
     * @return RedirectResponse|Response
     */
    public function editHoraire(Request $request, DepannageHoraireTechnicien $depannageHoraireTechnicien)
    {
        $depannage = $depannageHoraireTechnicien->getDepannage();

        if ($depannage->getStatut()
                      ->getId() == $this->getParameter('STATUT_FACTURE')) {
            $this->addFlash('danger', 'Ce depannage a été facturé. Aucune modification possible.');

            return $this->redirectToRoute('depannage_show_admin', [
                'id' => $depannage->getId(),
                'client' => $depannage->getChargeAffaire()
                                      ->getClient()
                                      ->getId(),
            ]);
        }

        $form = $this->createForm(DepannageHoraireTechnicienType::class, $depannageHoraireTechnicien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->getDoctrine()
                 ->getManager()
                 ->flush()
            ;

            $this->addFlash('success', 'L\'horaire a bien été modifié.');

            return $this->redirectToRoute('depannage_show_admin', [
                'id' => $depannage->getId(),
                'client' => $depannage->getChargeAffaire()
                                      ->getClient()
                                      ->getId(),
            ]);

        }

        return $this->render('depannage/horaire_edit.html.twig', [
            'form' => $form->createView(),
            'client' => $depannage->getChargeAffaire()
                                  ->getClient(),
            'depannage' => $depannage,
            'horaire' => $depannageHoraireTechnicien,
        ]);

    }


}
