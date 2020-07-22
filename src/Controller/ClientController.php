<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\FileUploader;

/**
 * @Route("/client")
 */
class ClientController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_ADMIN')", statusCode=403, message="Vous n'êtes pas autorisé à accéder à cette section.")
     * @Route("/", name="client_index", methods={"GET","POST"})
     * @param Request $request
     * @param ClientRepository $clientRepository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, ClientRepository $clientRepository, PaginatorInterface $paginator): Response
    {
        if ($request->request->get('item_pagination'))
            $this->get('session')
                 ->set('itemPerPage', $request->request->get('item_pagination')['maxItemPerPage'])
            ;

        $clientQuery = $clientRepository->getQueryAll();

        $clients = $paginator->paginate($clientQuery, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            $this->get('session')
                 ->get('itemPerPage', $this->getParameter('itemPerPage')), // Nombre de résultats par page
            [
                'defaultSortFieldName' => 'c.nom',
                'defaultSortDirection' => 'asc'
            ]);

        return $this->render('client/index.html.twig', [
            'clients' => $clients,
        ]);
    }

    /**
     * @Route("/new", name="client_new", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')", statusCode=403, message="Vous n'êtes pas autorisé à créer un client.")
     * @param Request $request
     * @param FileUploader $fileUploader
     * @return Response
     */
    public function new(Request $request, FileUploader $fileUploader): Response
    {
        $client = new Client();
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()
                                  ->getManager()
            ;
            $entityManager->persist($client);
            $entityManager->flush();

            // le file uploadé dans le form
            /** @var UploadedFile $enteteFile */
            $enteteFile = $form['entete']->getData();
            if ($enteteFile) {
                $enteteFileName = $fileUploader->upload($enteteFile, $this->getParameter('dossier_entete_pdf'));
                $client->setEntete($enteteFileName);
            }

            $this->addFlash('success', 'Le client a bien été ajouté');


            return $this->redirectToRoute('client_show', [
                'id' => $client->getId()
            ]);
        }

        return $this->render('client/new.html.twig', [
            'client' => $client,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')", statusCode=403, message="Vous n'êtes pas autorisé à accéder à cette section.")
     * @Route("/{id}", name="client_show", methods={"GET"})
     * @param Client $client
     * @return Response
     */
    public function show(Client $client): Response
    {
        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="client_edit", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')", statusCode=403, message="Vous n'êtes pas autorisé à modifier un client.")
     * @param Request $request
     * @param Client $client
     * @param FileUploader $fileUploader
     * @return Response
     */
    public function edit(Request $request, Client $client, FileUploader $fileUploader): Response
    {
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // le file uploadé dans le form
            /** @var UploadedFile $enteteFile */
            $enteteFile = $form['entete']->getData();
            if ($enteteFile) {
                $enteteFileName = $fileUploader->upload($enteteFile, $this->getParameter('dossier_entete_pdf'));
                $client->setEntete($enteteFileName);

            }

            $this->getDoctrine()
                 ->getManager()
                 ->flush()
            ;

            $this->addFlash('success', 'Le client a bien été modifié');

            return $this->redirectToRoute('client_show', [
                'id' => $client->getId()
            ]);
        }

        return $this->render('client/edit.html.twig', [
            'client' => $client,
            'form' => $form->createView(),
        ]);
    }

}
