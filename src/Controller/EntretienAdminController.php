<?php

namespace App\Controller;

use App\Data\FiltreTravauxData;
use App\Data\FiltreEntretienData;
use App\Entity\Client;
use App\Entity\Depannage;
use App\Entity\Entretien;
use App\Entity\EntretienHoraireTechnicien;
use App\Entity\Livraison;
use App\Entity\StatutActivite;
use App\Entity\TravauxHoraireTechnicien;
use App\Entity\User;
use App\Form\EntretienHoraireTechnicienDebutType;
use App\Form\EntretienHoraireTechnicienFinType;
use App\Form\EntretienHoraireTechnicienType;
use App\Form\EntretienType;
use App\Form\FiltreDataType;
//use App\Form\FiltreType;
use App\Form\FiltreEntretienDataType;
use App\Form\TravauxHoraireTechnicienType;
use App\Form\UploadFileType;
use App\Repository\ClientRepository;
use App\Repository\EnseigneRepository;
use App\Repository\EntretienRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use App\Service\MailService;
use App\Service\SMSService;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class EntretienAdminController extends AbstractController
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
     * @Route("/entretiens/liste/{client}", name="entretien_admin_list", methods={"GET","POST"})
     * @param Request $request
     * @param Client $client
     * @param PaginatorInterface $paginator
     * @param EntretienRepository $entretienRepository
     * @return Response
     * @throws Exception
     */
    public function listTableau(Request $request, Client $client, PaginatorInterface $paginator, EntretienRepository $entretienRepository): Response
    {

        $filtre = new FiltreEntretienData();
        $filtre->client = $client;
        $formFilter = $this->createForm(FiltreEntretienDataType::class, $filtre);
        $formFilter->handleRequest($request);
//        $EntretienQuery = $entretienRepository->getQueryAll();

//        if ($request->query->get('old')) {
//            $EntretienQuery = $entretienRepository->getOldByClient($client, $filtre);
//        } else {
//            $EntretienQuery = $entretienRepository->getNextByClient($client, $filtre);
//        }
        $EntretienQuery = $entretienRepository->getQuery($filtre);

        if ($request->request->get('item_pagination'))
            $this->get('session')
                 ->set('itemPerPage', $request->request->get('item_pagination')['maxItemPerPage'])
            ;


        $entretiens = $paginator->paginate($EntretienQuery, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            $this->get('session')
                 ->get('itemPerPage', $this->getParameter('itemPerPage')), // Nombre de résultats par page
            [
                'defaultSortFieldName' => 'e.dateDebut',
                'defaultSortDirection' => 'desc'
            ]);


        return $this->render('entretien/listTableau.html.twig', [

            'entretiens' => $entretiens,
            'formFiltre' => $formFilter->createView(),
            'role' => $this->getParameter('ROLE_CLIENT'),
            'client' => $client,
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/entretiens/{client}", name="entretien_admin_index", methods={"GET","POST"})
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param EnseigneRepository $enseigneRepository
     * @param UserRepository $userRepository
     * @param EntretienRepository $entretienRepository
     * @return Response
     */
    public function index(Request $request, PaginatorInterface $paginator, Client $client, EnseigneRepository $enseigneRepository, UserRepository $userRepository, EntretienRepository $entretienRepository): Response
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
            'client' => $client,
        ]);


    }


    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/entretiens/{client}/new", name="entretien_admin_new", methods={"GET","POST"})
     */
    public function new(Request $request, Client $client, MailService $mailService): Response
    {
        $entretien = new Entretien();
        $form = $this->createForm(EntretienType::class, $entretien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()
                                  ->getManager()
            ;


            if ($entretien->getTechniciens()
                          ->count() > 0) {

                $entretien->setStatut($this->getDoctrine()
                                           ->getRepository(StatutActivite::class)
                                           ->find($this->getParameter('STATUT_VALIDE')));

            } else {
                $entretien->setStatut($this->getDoctrine()
                                           ->getRepository(StatutActivite::class)
                                           ->find($this->getParameter('STATUT_EN_ATTENTE')));


            }


            $entityManager->persist($entretien);
            $entityManager->flush();

            $techniciens = $entretien->getTechniciens();

            /** @var User $technicien */
            foreach ($techniciens as $technicien) {

                $mailService->setAndSendMail($technicien->getEmailPro(), 'Affection à un entretien', 'mail/affectation_entretien.html.twig', [
                    'user' => $this->getUser(),
                    'entretien' => $entretien,
                ]);
                $this->sendSMS($entretien, $technicien);

            }

            return $this->redirectToRoute('entretien_admin_index', [
                'client' => $entretien->getChargeAffaire()
                                      ->getClient()
                                      ->getId()
            ]);
        }

        return $this->render('entretien/new.html.twig', [
            'entretien' => $entretien,
            'form' => $form->createView(),
            'client' => $client,

        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TECHNICIEN')")
     * @Route("/entretiens/{client}/{id}", name="entretien_admin_show", methods={"GET","POST"})
     * @param Request $request
     * @param Entretien $entretien
     * @param Client $client
     * @param MailService $mailService
     * @param FileUploader $fileUploader
     * @return Response
     * @throws Exception
     */
    public function show(Request $request, Entretien $entretien, Client $client, MailService $mailService, FileUploader $fileUploader): Response
    {
        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) && !$entretien->getTechniciens()
                                                                                   ->contains($this->getUser())) {
            $this->addFlash('danger', 'Vous n\'êtes pas affectué à cet entretien.');

            return $this->redirectToRoute('entretien_index', []);
        }

        $params = [
            'entretien' => $entretien,
            'client' => $client,
        ];

        $now = new \DateTime();
        $em = $this->getDoctrine()
                   ->getManager()
        ;


        // Formulaires d'horaires pour les techniciens
        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) && in_array($entretien->getStatut()
                                                                                           ->getId(), [
                $this->getParameter('STATUT_VALIDE'),
                $this->getParameter('STATUT_EN_COURS')
            ]) && $entretien->getDateDebut() && $entretien->getDateFin()
                                                          ->format('Y-m-d') <= $now->format('Y-m-d')) {


            /** @var EntretienHoraireTechnicien $horaire */
            $horaire = $em->getRepository(EntretienHoraireTechnicien::class)
                          ->findOneByEntretienAndTechnicien($entretien, $this->getUser())
            ;

            if (!$horaire) {
                $horaire = new EntretienHoraireTechnicien();
                $horaire->setEntretien($entretien)
                        ->setDateDebut(new \DateTime())
                        ->setTechnicien($this->getUser())
                ;
                $formHoraire = $this->createForm(EntretienHoraireTechnicienDebutType::class, $horaire);
            } else {
                $formHoraire = $this->createForm(EntretienHoraireTechnicienFinType::class, $horaire);
            }

            $formHoraire->handleRequest($request);
            if ($formHoraire->isSubmitted() && $formHoraire->isValid()) {

                if (!$horaire->getId()) {
                    $em->persist($horaire);
                }

                $em->flush();

                return $this->redirectToRoute('entretien_admin_show', [
                    'id' => $entretien->getId(),
                    'client' => $client->getId()
                ]);

            }


            $params['formHoraire'] = $formHoraire->createView();
        }


        // Affichages des horaires saisies
        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN'))) {
            $horaires = $em->getRepository(EntretienHoraireTechnicien::class)
                           ->findBy([
                               'technicien' => $this->getUser(),
                               'entretien' => $entretien
                           ], ['dateDebut' => 'ASC'])
            ;

        } else {
            $horaires = $em->getRepository(EntretienHoraireTechnicien::class)
                           ->findBy(['entretien' => $entretien], ['dateDebut' => 'ASC'])
            ;
        }


        // Formulaire ajout horaire de l'admin
        if ($this->isGranted($this->getParameter('ROLE_ADMIN')) && in_array($entretien->getStatut()
                                                                                      ->getId(), [
                $this->getParameter('STATUT_VALIDE'),
                $this->getParameter('STATUT_EN_COURS'),
                $this->getParameter('STATUT_TERMINE'),
            ])) {

            $horaire = new EntretienHoraireTechnicien();
            $horaire->setEntretien($entretien)
                    ->setDateDebut(new \DateTime())
            ;
            $formHoraireNew = $this->createForm(EntretienHoraireTechnicienType::class, $horaire);
            $formHoraireNew->handleRequest($request);

            if ($formHoraireNew->isSubmitted() && $formHoraireNew->isValid()) {
                $em->persist($horaire);
                $em->flush();

                return $this->redirectToRoute('entretien_admin_show', [
                    'id' => $entretien->getId(),
                    'client' => $client->getId()
                ]);
            }

            $params['formHoraireNew'] = $formHoraireNew->createView();

        }

        if ($entretien->getEntretienHoraireTechniciens()
                      ->count() > 0 && $entretien->getStatut()
                                                 ->getId() == $this->getParameter('STATUT_VALIDE')) {
            $entretien->setStatut($em->getRepository(StatutActivite::class)
                                     ->find($this->getParameter('STATUT_EN_COURS')));
            $em->flush();
        }


        $dir = $this->getParameter('dossier_fichier_entretiens') . $entretien->getId();
        $dir = $fileUploader->replaceVariableClientDir($dir, $client->getId(), $entretien->getChargeAffaire()
                                                                                         ->getId());

        $isPaye = false;

        if ($entretien->getFactureEntretienLigne() && $entretien->getFactureEntretienLigne()
                                                                ->getFacture() && $entretien->getFactureEntretienLigne()
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

                return $this->redirectToRoute('entretien_admin_show', [
                    'id' => $entretien->getId(),
                    'client' => $client->getId(),
                ]);
            }

            $params['formUploadFile'] = $formUploadFile->createView();
        }

        $files = $fileUploader->scanDir($dir);
        $params['files'] = $files;

        $params['horaires'] = $horaires;


        return $this->render('entretien/show.html.twig', $params);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/entretiens/{client}/{id}/edit", name="entretien_admin_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Entretien $entretien
     * @param Client $client
     * @param MailService $mailService
     * @return Response
     */
    public function edit(Request $request, Entretien $entretien, Client $client, MailService $mailService): Response
    {
        if (!in_array($entretien->getStatut()
                                ->getId(), [
            $this->getParameter('STATUT_EN_ATTENTE'),
            $this->getParameter('STATUT_VALIDE'),
            $this->getParameter('STATUT_EN_COURS'),
        ])) {

            $this->addFlash('danger', 'Vous ne pouvez plus le modifier cet entretien.');

            return $this->redirectToRoute('entretien_admin_show', [
                'id' => $entretien->getId(),
                'client' => $client->getId(),
            ]);

        }

        $oldEntretien = clone $entretien;
        $oldTechniciens = clone $oldEntretien->getTechniciens();

        $form = $this->createForm(EntretienType::class, $entretien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($entretien->getTechniciens()
                          ->count() > 0 && $entretien->getStatut()
                                                     ->getId() == $this->getParameter('STATUT_EN_ATTENTE'))
                $entretien->setStatut($this->getDoctrine()
                                           ->getRepository(StatutActivite::class)
                                           ->find($this->getParameter('STATUT_VALIDE')));

            $this->getDoctrine()
                 ->getManager()
                 ->flush()
            ;

            $techniciens = $entretien->getTechniciens();

            /** @var User $technicien */
            foreach ($techniciens as $technicien) {

                if (!$oldTechniciens->contains($technicien)) {
                    $mailService->setAndSendMail($technicien->getEmailPro(), 'Affection à un entretien', 'mail/affectation_entretien.html.twig', [
                        'user' => $this->getUser(),
                        'entretien' => $entretien,
                    ]);
                    $this->sendSMS($entretien, $technicien);
                }

            }


            return $this->redirectToRoute('entretien_admin_show', [
                'client' => $client->getId(),
                'id' => $entretien->getId()
            ]);
        }

        return $this->render('entretien/edit.html.twig', [
            'entretien' => $entretien,
            'form' => $form->createView(),
            'client' => $client,
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TECHNICIEN')")
     * @Route("/entretiens/{client}/{id}/cloturer", name="entretien_cloture", methods={"GET"})
     * @param Entretien $entretien
     * @param Client $client
     * @param MailService $mailService
     * @return RedirectResponse
     */
    public function clotureEntretien(Entretien $entretien, Client $client, MailService $mailService)
    {


        if (!in_array($entretien->getStatut()
                                ->getId(), [
            $this->getParameter('STATUT_EN_ATTENTE'),
            $this->getParameter('STATUT_VALIDE'),
            $this->getParameter('STATUT_EN_COURS')
        ])) {
            $this->addFlash('danger', 'L\'entretien a déjà été clôturé.');

            return $this->redirectToRoute('entretien_admin_show', [
                'id' => $entretien->getId(),
                'client' => $client->getId()
            ]);

        } elseif ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) && !in_array($this->getUser(), $entretien->getTechniciens()
                                                                                                                    ->toArray())) {
            $this->addFlash('danger', 'Vous n\'êtes pas sur ce dépannage.');

            return $this->redirectToRoute('depannage_index', [
                'id' => $entretien->getId(),
                'client' => $client->getId()
            ]);
        }

        $em = $this->getDoctrine()
                   ->getManager()
        ;

        $horairesNonTermine = $em->getRepository(EntretienHoraireTechnicien::class)
                                 ->findBy([
                                     'dateFin' => null,
                                     'entretien' => $entretien
                                 ])
        ;

        if (count($horairesNonTermine) > 0) {
            foreach ($horairesNonTermine as $horaireNonTermine) {
                $this->addFlash('danger', $horaireNonTermine->getTechnicien()
                                                            ->getNomPrenom() . ' n\' pas remplit sont horaire de fin.');

            }

            return $this->redirectToRoute('entretien_admin_show', [
                'id' => $entretien->getId(),
                'client' => $client->getId()
            ]);
        }

        $entretien->setStatut($em->getRepository(StatutActivite::class)
                                 ->find($this->getParameter('STATUT_TERMINE')));
        $em->flush();
        $this->addFlash('success', 'L\'entretien a été clôturé.');

        $mailService->setAndSendMail($entretien->getChargeAffaire()
                                               ->getEmailPro(), 'Fin de l\'entretien', 'mail/entretien_termine.html.twig', ['entretien' => $entretien]);

        return $this->redirectToRoute('entretien_admin_show', [
            'id' => $entretien->getId(),
            'client' => $client->getId()
        ]);

    }


    private function sendSMS(Entretien $entretien, User $technicien): void
    {
        if ($technicien->getTelephone() != null) {

            $message = "Vous avez été affecté à l'entretien n°" . $entretien->getId() . " qui aura lieu le " . $entretien->getDateDebut()
                                                                                                                         ->format('d/m/Y \\à H\\hi') . ". \nLien vers la demande d'entretien : " . $this->generateUrl('entretien_admin_show', [
                    'id' => $entretien->getId(),
                    'client' => $entretien->getChargeAffaire()
                                          ->getClient()
                                          ->getId()
                ], UrlGeneratorInterface::ABSOLUTE_URL);

            $this->SMSService->sendSMS($technicien->getTelephone(), $message);

        }

    }


    /**
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TECHNICIEN')")
     * @Route("/entretiens/{client}/{id}/delete_file", name="entretien_admin_delete_file", methods={"GET","POST"})
     * @param Request $request
     * @param Client $client
     * @param entretien $entretien
     * @param MailService $mailService
     * @param FileUploader $fileUploader
     * @return RedirectResponse
     */
    public function deleteFile(Request $request, Client $client, entretien $entretien, MailService $mailService, FileUploader $fileUploader)
    {

        /** @var User $technicien */
        $technicien = $this->getUser();

        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN'))) {

            if (!$entretien->getTechniciens()
                           ->contains($technicien)) {
                $this->addFlash('danger', 'Vous n\'avez pas accès à cet entretien.');

                return $this->redirectToRoute('index');
            }

            if (in_array($entretien->getStatut()
                                   ->getId(), [
                $this->getParameter('STATUT_TERMINE'),
            ])) {
                $this->addFlash('danger', 'Cet entretien est terminé, vous ne pouvez plus supprimer de fichier.');

                return $this->redirectToRoute('entretien_admin_show', [
                    'id' => $entretien->getId(),
                    'client' => $entretien->getChargeAffaire()
                                          ->getClient()
                                          ->getId()
                ]);

            }


        }

        if (in_array($entretien->getStatut()
                               ->getId(), [
            $this->getParameter('STATUT_FACTURE'),
        ])) {
            $this->addFlash('danger', 'Cet entretien est terminé, vous ne pouvez plus supprimer de fichier.');

            return $this->redirectToRoute('entretien_admin_show', [
                'id' => $entretien->getId(),
                'client' => $entretien->getChargeAffaire()
                                      ->getClient()
                                      ->getId()
            ]);

        }

        $file = $request->query->get('file');

        if (!$file) {
            $this->addFlash('danger', 'Aucun fichier a supprimer.');
        }


        $dir = $this->getParameter('dossier_fichier_entretiens') . $entretien->getId();
        $dir = $fileUploader->replaceVariableClientDir($dir, $client->getId(), $entretien->getChargeAffaire()
                                                                                         ->getId());

        $filename = $dir . '/' . $file;

        if (is_file($filename)) {
            unlink($filename);
            $this->addFlash('success', 'Le fichier a été supprimé.');
        } else {
            $this->addFlash('danger', 'Ce fichier n\'existe pas.');
        }

        return $this->redirectToRoute('entretien_admin_show', [
            'client' => $client->getId(),
            'id' => $entretien->getId(),
        ]);


    }


    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/entretiens-horaire-edit/{id}", name="entretien_horaire_edit", methods={"GET","POST"})
     * @param Request $request
     * @param EntretienHoraireTechnicien $entretienHoraireTechnicien
     * @return RedirectResponse|Response
     */
    public function editHoraire(Request $request, EntretienHoraireTechnicien $entretienHoraireTechnicien)
    {
        $entretien = $entretienHoraireTechnicien->getEntretien();

        if ($entretien->getStatut()
                      ->getId() == $this->getParameter('STATUT_FACTURE')) {
            $this->addFlash('danger', 'Cet entretien a été facturé. Aucune modification possible.');

            return $this->redirectToRoute('entretien_admin_show', [
                'id' => $entretien->getId(),
                'client' => $entretien->getChargeAffaire()
                                      ->getClient()
                                      ->getId(),
            ]);
        }

        $form = $this->createForm(EntretienHoraireTechnicienType::class, $entretienHoraireTechnicien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->getDoctrine()
                 ->getManager()
                 ->flush()
            ;

            $this->addFlash('success', 'L\'horaire a bien été modifié.');

            return $this->redirectToRoute('entretien_admin_show', [
                'id' => $entretien->getId(),
                'client' => $entretien->getChargeAffaire()
                                      ->getClient()
                                      ->getId(),
            ]);

        }

        return $this->render('entretien/horaire_edit.html.twig', [
            'form' => $form->createView(),
            'client' => $entretien->getChargeAffaire()
                                  ->getClient(),
            'entretien' => $entretien,
            'horaire' => $entretienHoraireTechnicien,
        ]);

    }


//    /**
//     * @Route("/entretiens/{id}", name="entretien_admin_delete", methods={"DELETE"})
//     */
//    public function delete(Request $request, Entretien $entretien, Client $client): Response
//    {
//        if ($this->isCsrfTokenValid('delete' . $entretien->getId(), $request->request->get('_token'))) {
//            $entityManager = $this->getDoctrine()
//                                  ->getManager()
//            ;
//            $entityManager->remove($entretien);
//            $entityManager->flush();
//        }
//
//        return $this->redirectToRoute('entretien_index');
//    }

}
