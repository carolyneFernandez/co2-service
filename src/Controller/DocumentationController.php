<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DocumentationController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/documentation/admin", name="documentation_admin")
     */
    public function index()
    {
        return $this->render('documentation/admin/index.html.twig', []);
    }

    /**
     * @Security("is_granted('ROLE_TECHNICIEN')")
     * @Route("/documentation/technicien", name="documentation_technicien")
     */
    public function docTechnicien()
    {
        return $this->render('documentation/technicien/index.html.twig', []);
    }

    /**
     * @Security("is_granted('ROLE_CLIENT')")
     * @Route("/documentation/charge-affaires", name="documentation_charge_affaires")
     */
    public function docChargeAffaires()
    {
        return $this->render('documentation/charge_affaires/index.html.twig', []);
    }

}
