<?php

namespace App\Controller;

use App\Repository\DepartementRepository;
use App\Repository\ServiceRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccueilController extends AbstractController
{
    #[Route('/', name: 'accueil')]
    public function index(
        UtilisateurRepository $utilisateurs,
        DepartementRepository $departements,
        ServiceRepository $services,
    ): Response {
        return $this->render('accueil/index.html.twig', [
            'nbUtilisateurs' => $utilisateurs->count(['actif' => true]),
            'nbDepartements' => $departements->count([]),
            'nbServices' => $services->count([]),
            'nbComptesMd' => $utilisateurs->count(['compteMenuesDepenses' => true]),
        ]);
    }
}
