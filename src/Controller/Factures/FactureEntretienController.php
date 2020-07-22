<?php

namespace App\Controller\Factures;

use App\Data\FiltreFactureEntretienData;
use App\Data\FiltreEntretienAFacturerData;
use App\Entity\Client;
use App\Entity\Factures\Facture;
use App\Entity\Factures\FactureEntretien;
use App\Entity\Factures\FactureEntretienLigne;
use App\Entity\Entretien;
use App\Entity\StatutActivite;
use App\Entity\User;
use App\Form\Factures\Entretien\FactureEntretienLigneType;
use App\Form\Factures\Entretien\FactureEntretienType;
use App\Form\Factures\Entretien\FiltreFactureEntretienDataType;
use App\Form\Factures\Entretien\FiltreEntretienAFacturerDataType;
use App\Form\Factures\Entretien\EntretiensAFacturerType;
use App\Repository\Factures\FactureEntretienRepository;
use App\Service\PDFService;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_CLIENT')")
 * @Route("/facture/entretien")
 */
class FactureEntretienController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_CLIENT')")
     * @Route("/", name="factures_facture_entretien_index", methods={"GET"})
     * @param Request $request
     * @param Client $client
     * @param FactureEntretienRepository $factureEntretienRepository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, FactureEntretienRepository $factureEntretienRepository, PaginatorInterface $paginator): Response
    {
        /** @var User $chargeAffaire */
        $chargeAffaire = $this->getUser();
        $client = $chargeAffaire->getClient();

        $filtreFactureEntretien = new FiltreFactureEntretienData();
        $filtreFactureEntretien->chargeAffaires[] = $chargeAffaire;


        $formFiltre = $this->createForm(FiltreFactureEntretienDataType::class, $filtreFactureEntretien);
        $formFiltre->handleRequest($request);

        $facturesEntretienQuery = $this->getDoctrine()
                                       ->getRepository(FactureEntretien::class)
                                       ->getQuery($filtreFactureEntretien)
        ;


        $factureEntretiens = $paginator->paginate($facturesEntretienQuery, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            $this->get('session')
                 ->get('itemPerPage', $this->getParameter('itemPerPage')), // Nombre de résultats par page
            [
                'defaultSortFieldName' => 'f.date',
                'defaultSortDirection' => 'desc'
            ]);


        return $this->render('factures/facture_entretien/index.html.twig', [
            'facture_entretiens' => $factureEntretiens,
            'client' => $client,
            'formFiltre' => $formFiltre->createView()
        ]);
    }


    /**
     * @Security("is_granted('ROLE_CLIENT')")
     * @Route("/{id}", name="factures_facture_entretien_show", methods={"GET"})
     * @param Request $request
     * @param FactureEntretien $factureEntretien
     * @return Response
     */
    public function show(FactureEntretien $factureEntretien): Response
    {
        $chargeAffaire = $this->getUser();
        if ($chargeAffaire != $factureEntretien->getChargeAffaire()) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à cette facture.');

            return $this->redirectToRoute('factures_facture_entretien_index');
        }
        /** @var User $chargeAffaire */
        $client = $factureEntretien->getChargeAffaire()
                                   ->getClient()
        ;

        $params = [
            'facture_entretien' => $factureEntretien,
            'client' => $factureEntretien->getChargeAffaire()
                                         ->getClient(),
        ];


        return $this->render('factures/facture_entretien/show.html.twig', $params);
    }


    /**
     * @Route("/{id}/pdf", name="factures_facture_entretien_pdf", methods={"GET"})
     * @param FactureEntretien $factureEntretien
     * @param PDFService $PDFService
     * @return Response
     */
    public function pdf(FactureEntretien $factureEntretien, PDFService $PDFService): Response
    {
        $chargeAffaire = $this->getUser();
        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $chargeAffaire != $factureEntretien->getChargeAffaire()) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à cette facture.');

            return $this->redirectToRoute('factures_facture_entretien_index');
        }


        $client = $factureEntretien->getChargeAffaire()
                                   ->getClient()
        ;

        $params = [
            'facture_entretien' => $factureEntretien,
            'client' => $factureEntretien->getChargeAffaire()
                                         ->getClient(),
            'facture' => $factureEntretien,
        ];

        $template = $this->renderView('pdf/facture/entretien.html.twig', $params);

        $pdf_name = 'facture_' . $factureEntretien->getNumero() . '_entretien';


        $PDFService->create('P', 'A4', 'fr', true, 'UTF-8', array(
            10,
            15,
            10,
            15
        ));

        return $PDFService->generatePdf($template, $pdf_name, 'D');


    }


}
