<?php

namespace App\Controller\Factures;

use App\Data\FiltreFactureDepannageData;
use App\Data\FiltreDepannageAFacturerData;
use App\Entity\Client;
use App\Entity\Factures\Facture;
use App\Entity\Factures\FactureDepannage;
use App\Entity\Factures\FactureDepannageLigne;
use App\Entity\Depannage;
use App\Entity\StatutActivite;
use App\Entity\User;
use App\Form\Factures\Depannage\FactureDepannageLigneType;
use App\Form\Factures\Depannage\FactureDepannageType;
use App\Form\Factures\Depannage\FiltreFactureDepannageDataType;
use App\Form\Factures\Depannage\FiltreDepannageAFacturerDataType;
use App\Form\Factures\Depannage\DepannagesAFacturerType;
use App\Repository\Factures\FactureDepannageRepository;
use App\Service\PDFService;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_CLIENT')")
 * @Route("/facture/depannage")
 */
class FactureDepannageController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_CLIENT')")
     * @Route("/", name="factures_facture_depannage_index", methods={"GET"})
     * @param Request $request
     * @param Client $client
     * @param FactureDepannageRepository $factureDepannageRepository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, FactureDepannageRepository $factureDepannageRepository, PaginatorInterface $paginator): Response
    {
        /** @var User $chargeAffaire */
        $chargeAffaire = $this->getUser();
        $client = $chargeAffaire->getClient();

        $filtreFactureDepannage = new FiltreFactureDepannageData();
        $filtreFactureDepannage->chargeAffaires[] = $chargeAffaire;


        $formFiltre = $this->createForm(FiltreFactureDepannageDataType::class, $filtreFactureDepannage);
        $formFiltre->handleRequest($request);

        $facturesDepannageQuery = $this->getDoctrine()
                                       ->getRepository(FactureDepannage::class)
                                       ->getQuery($filtreFactureDepannage)
        ;


        $factureDepannages = $paginator->paginate($facturesDepannageQuery, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            $this->get('session')
                 ->get('itemPerPage', $this->getParameter('itemPerPage')), // Nombre de résultats par page
            [
                'defaultSortFieldName' => 'f.date',
                'defaultSortDirection' => 'desc'
            ]);


        return $this->render('factures/facture_depannage/index.html.twig', [
            'facture_depannages' => $factureDepannages,
            'client' => $client,
            'formFiltre' => $formFiltre->createView()
        ]);
    }


    /**
     * @Security("is_granted('ROLE_CLIENT')")
     * @Route("/{id}", name="factures_facture_depannage_show", methods={"GET"})
     * @param Request $request
     * @param FactureDepannage $factureDepannage
     * @return Response
     */
    public function show(FactureDepannage $factureDepannage): Response
    {
        $chargeAffaire = $this->getUser();
        if ($chargeAffaire != $factureDepannage->getChargeAffaire()) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à cette facture.');

            return $this->redirectToRoute('factures_facture_depannage_index');
        }
        /** @var User $chargeAffaire */
        $client = $factureDepannage->getChargeAffaire()
                                   ->getClient()
        ;

        $params = [
            'facture_depannage' => $factureDepannage,
            'client' => $factureDepannage->getChargeAffaire()
                                         ->getClient(),
        ];


        return $this->render('factures/facture_depannage/show.html.twig', $params);
    }


    /**
     * @Route("/{id}/pdf", name="factures_facture_depannage_pdf", methods={"GET"})
     * @param FactureDepannage $factureDepannage
     * @param PDFService $PDFService
     * @return Response
     */
    public function pdf(FactureDepannage $factureDepannage, PDFService $PDFService): Response
    {
        $chargeAffaire = $this->getUser();
        if ($this->isGranted($this->getParameter('ROLE_CLIENT')) && $chargeAffaire != $factureDepannage->getChargeAffaire()) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à cette facture.');

            return $this->redirectToRoute('factures_facture_depannage_index');
        }


        $client = $factureDepannage->getChargeAffaire()
                                   ->getClient()
        ;

        $params = [
            'facture_depannage' => $factureDepannage,
            'client' => $factureDepannage->getChargeAffaire()
                                         ->getClient(),
            'facture' => $factureDepannage,
        ];

        $template = $this->renderView('pdf/facture/depannage.html.twig', $params);

        $pdf_name = 'facture_' . $factureDepannage->getNumero() . '_depannage';


        $PDFService->create('P', 'A4', 'fr', true, 'UTF-8', array(
            10,
            15,
            10,
            15
        ));

        return $PDFService->generatePdf($template, $pdf_name, 'D');


    }


}
