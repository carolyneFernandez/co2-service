<?php

namespace App\Controller;

use App\Data\FiltreLivraisonData;
use App\Entity\Client;
use App\Entity\Livraison;
use App\Entity\StatutActivite;
use App\Entity\User;
use App\Form\FiltreLivraisonDataType;
use App\Form\LivraisonDebutType;
use App\Form\LivraisonFinType;
use App\Form\LivraisonType;
use App\Form\UploadFileType;
use App\Repository\LivraisonRepository;
use App\Service\FileUploader;
use App\Service\MailService;
use App\Service\SMSService;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


/**
 * @Route("/livraison")
 */
class LivraisonControllerAdmin extends AbstractController
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
     * @Route("/{client}/index", name="livraison_index_admin", methods={"GET","POST"})
     * @param Request $request
     * @param Client $client
     * @param LivraisonRepository $livraisonRepository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, Client $client, LivraisonRepository $livraisonRepository, PaginatorInterface $paginator): Response
    {
        /*@var User $user*/
        $user = $this->getUser();

        $filtre = new FiltreLivraisonData();
        $filtre->client = $client;
        $formFiltre = $this->createForm(FiltreLivraisonDataType::class, $filtre);
        $formFiltre->handleRequest($request);

        if ($request->request->get('item_pagination'))
            $this->get('session')
                 ->set('itemPerPage', $request->request->get('item_pagination')['maxItemPerPage'])
            ;

        $livraisonsQuery = $livraisonRepository->getQuery($filtre);

        $livraisons = $paginator->paginate($livraisonsQuery, $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            $this->get('session')
                 ->get('itemPerPage', $this->getParameter('itemPerPage')), // Nombre de résultats par page
            [
                'defaultSortFieldName' => 'l.dateRetenue',
                'defaultSortDirection' => 'desc'
            ]);

        return $this->render('livraison/index.html.twig', [
            'livraisons' => $livraisons,
            'client' => $client,
            'formFiltre' => $formFiltre->createView()
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{client}/new", name="livraison_new_admin", methods={"GET","POST"})
     * @param Client $client
     * @param Request $request
     * @param MailService $mailService
     * @return Response
     */
    public function new(Client $client, Request $request, MailService $mailService): Response
    {
        $livraison = new Livraison();

        $form = $this->createForm(LivraisonType::class, $livraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager()
            ;
            if ($livraison->getTechniciens()
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
            $livraison->setStatut($statut);
            $em->persist($livraison);
            $em->flush();

            $techniciens = $livraison->getTechniciens();

            /** @var User $technicien */
            foreach ($techniciens as $technicien) {

                $mailService->setAndSendMail($technicien->getEmailPro(), 'Affection à une livraison', 'mail/affectation_livraison.html.twig', [
                    'user' => $this->getUser(),
                    'livraison' => $livraison,
                ]);
                $this->sendSMS($livraison, $technicien);

            }

//            foreach ($livraison->getTechniciens() as $tech) {
//                $mailService->setAndSendMail($tech->getEmailPro(), 'Affection à une livraison', 'mail/affectation_livraison.html.twig',
//                    ['user' => $this->getUser(), 'livraison' => $livraison, 'date' => $dateRetenue]);
//            }

            return $this->redirectToRoute('livraison_show_admin', array(
                'client' => $client->getId(),
                'id' => $livraison->getId()
            ));
        }

        return $this->render('livraison/new.html.twig', [
            'livraison' => $livraison,
            'form' => $form->createView(),
            'client' => $client
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{client}/edit/{id}", name="livraison_edit_admin", methods={"GET","POST"})
     * @param Client $client
     * @param Request $request
     * @param Livraison $livraison
     * @param MailService $mailService
     * @return Response
     */
    public function edit(Client $client, Request $request, Livraison $livraison, MailService $mailService): Response
    {
        $oldLivraison = clone $livraison;
        $oldTechniciens = clone $oldLivraison->getTechniciens();


        $form = $this->createForm(LivraisonType::class, $livraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $statut = $this->getDoctrine()
                           ->getRepository(StatutActivite::class)
                           ->find($this->getParameter('STATUT_VALIDE'))
            ;
            $livraison->setStatut($statut);
            $this->getDoctrine()
                 ->getManager()
                 ->flush()
            ;

            $techniciens = $livraison->getTechniciens();

            /** @var User $technicien */
            foreach ($techniciens as $technicien) {

                if (!$oldTechniciens->contains($technicien)) {
                    $mailService->setAndSendMail($technicien->getEmailPro(), 'Affection à une livraison', 'mail/affectation_livraison.html.twig', [
                        'user' => $this->getUser(),
                        'livraison' => $livraison,
                    ]);
                    $this->sendSMS($livraison, $technicien);
                }

            }

//            foreach ($livraison->getTechniciens() as $tech) {
//                $mailService->setAndSendMail($tech->getEmailPro(), 'Affection à une livraison', 'mail/affectation_livraison.html.twig',
//                    ['user' => $this->getUser(), 'livraison' => $livraison, 'date' => $dateRetenue]);
//            }

            return $this->redirectToRoute('livraison_show_admin', array(
                'client' => $client->getId(),
                'id' => $livraison->getId()
            ));
        }

        return $this->render('livraison/edit.html.twig', [
            'livraison' => $livraison,
            //            'user' => $this->getUser(),
            'form' => $form->createView(),
            'client' => $client
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TECHNICIEN')")
     * @Route("/{client}/{id}", name="livraison_show_admin", methods={"GET","POST"})
     * @param Request $request
     * @param Client $client
     * @param Livraison $livraison
     * @param MailService $mailService
     * @param FileUploader $fileUploader
     * @return Response
     */
    public function show(Request $request, Client $client, Livraison $livraison, MailService $mailService, FileUploader $fileUploader): Response
    {

        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN')) && !$livraison->getTechniciens()
                                                                                   ->contains($this->getUser())) {
            $this->addFlash('danger', 'Vous n\'êtes pas affectué à cette livraison.');

            return $this->redirectToRoute('livraison_index', []);
        }


        $params = [
            'livraison' => $livraison,
            'user' => $this->getUser(),
            'client' => $client
        ];

        $em = $this->getDoctrine()
                   ->getManager()
        ;

        $dir = $this->getParameter('dossier_fichier_livraisons') . $livraison->getId();
        $dir = $fileUploader->replaceVariableClientDir($dir, $client->getId(), $livraison->getChargeAffaire()
                                                                                         ->getId());

        $isPaye = false;
        if ($livraison->getFactureLivraisonLigne() && $livraison->getFactureLivraisonLigne()
                                                                ->getFacture() && $livraison->getFactureLivraisonLigne()
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

                return $this->redirectToRoute('livraison_show_admin', [
                    'id' => $livraison->getId(),
                    'client' => $client->getId(),
                ]);
            }

            $params['formUploadFile'] = $formUploadFile->createView();
        }

        $files = $fileUploader->scanDir($dir);
        $params['files'] = $files;


        if (($livraison->getDateLivraison() == null || $livraison->getDateReleve() == null) && in_array($livraison->getStatut()
                                                                                                                  ->getId(), [
                $this->getParameter('STATUT_VALIDE'),
                $this->getParameter('STATUT_EN_COURS'),
                $this->getParameter('STATUT_TERMINE'),
            ])) {

            if ($livraison->getDateReleve() == null) {
                $form = $this->createForm(LivraisonDebutType::class, $livraison);
            } else {
                $form = $this->createForm(LivraisonFinType::class, $livraison);
            }

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
//                $em->flush();
                if ($livraison->getDateLivraison() == null) {
                    $statutEnCours = $em->getRepository(StatutActivite::class)
                                        ->find($this->getParameter('STATUT_EN_COURS'))
                    ;
                    $livraison->setStatut($statutEnCours);
                    $mailService->setAndSendMail($livraison->getChargeAffaire()
                                                           ->getEmailPro(), 'Votre livraison est en chemin', 'mail/livraison_en_cours.html.twig', ['livraison' => $livraison]);
                } else {
                    $statutLivre = $em->getRepository(StatutActivite::class)
                                      ->find($this->getParameter('STATUT_TERMINE'))
                    ;
                    $livraison->setStatut($statutLivre);
                    $mailService->setAndSendMail($livraison->getChargeAffaire()
                                                           ->getEmailPro(), 'Votre livraison a été livrée', 'mail/livraison_termine.html.twig', ['livraison' => $livraison]);
                }
                $em->flush();

                return $this->redirectToRoute('livraison_show_admin', [
                    'client' => $client->getId(),
                    'id' => $livraison->getId()
                ]);
            }

            $params['formHoraire'] = $form->createView();
        }

        return $this->render('livraison/show.html.twig', $params);
    }

    private function sendSMS(Livraison $livraison, User $technicien): void
    {
        if ($technicien->getTelephone() != null) {

            $message = "Vous avez été affecté à la livraison n°" . $livraison->getId() . " qui aura lieu le " . $livraison->getDateRetenue()
                                                                                                                          ->format('d/m/Y \\à H\\hi') . ". \nLien vers la demande de livraison : " . $this->generateUrl('livraison_show_admin', [
                    'id' => $livraison->getId(),
                    'client' => $livraison->getChargeAffaire()
                                          ->getClient()
                                          ->getId()
                ], UrlGeneratorInterface::ABSOLUTE_URL);

            $this->SMSService->sendSMS($technicien->getTelephone(), $message);

        }

    }


    /**
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TECHNICIEN')")
     * @Route("/{client}/{id}/delete_file", name="livraison_admin_delete_file", methods={"GET","POST"})
     * @param Request $request
     * @param Client $client
     * @param Livraison $livraison
     * @param MailService $mailService
     * @param FileUploader $fileUploader
     * @return RedirectResponse
     */
    public function deleteFile(Request $request, Client $client, Livraison $livraison, MailService $mailService, FileUploader $fileUploader)
    {

        /** @var User $technicien */
        $technicien = $this->getUser();

        if ($this->isGranted($this->getParameter('ROLE_TECHNICIEN'))) {

            if (!$livraison->getTechniciens()
                           ->contains($technicien)) {
                $this->addFlash('danger', 'Vous n\'avez pas accès à cette livraison.');

                return $this->redirectToRoute('index');
            }

        }

        if (in_array($livraison->getStatut()
                               ->getId(), [
            $this->getParameter('STATUT_FACTURE'),
        ])) {
            $this->addFlash('danger', 'Cette livraison est terminée, vous ne pouvez plus supprimer de fichier.');

            return $this->redirectToRoute('livraison_show_admin', [
                'id' => $livraison->getId(),
                'client' => $livraison->getChargeAffaire()
                                      ->getClient()
                                      ->getId()
            ]);

        }

        $file = $request->query->get('file');

        if (!$file) {
            $this->addFlash('danger', 'Aucun fichier a supprimer.');
        }


        $dir = $this->getParameter('dossier_fichier_livraisons') . $livraison->getId();
        $dir = $fileUploader->replaceVariableClientDir($dir, $client->getId(), $livraison->getChargeAffaire()
                                                                                         ->getId());

        $filename = $dir . '/' . $file;

        if (is_file($filename)) {
            unlink($filename);
            $this->addFlash('success', 'Le fichier a été supprimé.');
        } else {
            $this->addFlash('danger', 'Ce fichier n\'existe pas.');
        }

        return $this->redirectToRoute('livraison_show_admin', [
            'client' => $client->getId(),
            'id' => $livraison->getId(),
        ]);


    }

}
