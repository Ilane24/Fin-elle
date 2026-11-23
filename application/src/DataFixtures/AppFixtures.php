<?php

namespace App\DataFixtures;

use App\Entity\Departement;
use App\Entity\Service;
use App\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const DEPARTEMENTS = [
        'ADMINISTRATION & LOGISTIQUE' => [
            'DIRECTION_ADMIN' => 'Direction administrative',
            'FACILITIES' => 'Facilities',
            'ACHATS_MG' => 'Achats & moyens généraux',
        ],
        'FINANCE & COMPTABILITÉ' => [
            'FINANCE' => 'Finance',
            'COMPTABILITE' => 'Comptabilité',
        ],
        'DIRECTION GÉNÉRALE' => ['DIRECTION_GENERALE' => 'Direction générale'],
        'RESSOURCES HUMAINES' => ['RH' => 'Ressources humaines'],
        "SYSTÈMES D'INFORMATION" => ['INFORMATIQUE' => 'Informatique'],
        'COMMERCIAL' => ['COMMERCIAL' => 'Commercial'],
    ];

    private const UTILISATEURS = [
        ['mireille.gbagbo', 'Mireille', 'Gbagbo', 'Directrice administrative', 'DIRECTION_ADMIN', 'ROLE_ADMIN', null, null],
        ['regis.kouame', 'Régis', 'Kouamé', 'Directeur Financier et Comptable', 'FINANCE', 'ROLE_CFO', null, null],
        ['mariam.bamba', 'Mariam Yenita', 'Bamba', 'Directrice Générale', 'DIRECTION_GENERALE', 'ROLE_DG', null, null],
        ['aminata.kone', 'Aminata', 'Koné', 'Responsable Ressources Humaines', 'RH', 'ROLE_MANAGER', null, null],
        ['yves.brou', 'Yves', 'Brou', 'Responsable Informatique', 'INFORMATIQUE', 'ROLE_MANAGER', null, null],
        ['nadia.cisse', 'Nadia', 'Cissé', 'Directrice Commerciale', 'COMMERCIAL', 'ROLE_MANAGER', null, null],
        ['serge.kouadio', 'Serge', 'Kouadio', 'Responsable Facilities', 'FACILITIES', 'ROLE_MANAGER', 'mireille.gbagbo', 'CMD-2026-003'],
        ['carine.toro', 'Carine', 'Toro', 'Responsable Achats et Moyens Généraux', 'ACHATS_MG', 'ROLE_RAF', 'mireille.gbagbo', null],
        ['paul.yao', 'Paul', 'Yao', 'Comptable', 'COMPTABILITE', 'ROLE_COMPTA', 'regis.kouame', null],
        ['fatou.diallo', 'Fatou', 'Diallo', 'Assistante Facilities', 'FACILITIES', 'ROLE_DEMANDEUR', 'serge.kouadio', 'CMD-2026-001'],
        ['awa.ndiaye', 'Awa', 'Ndiaye', 'Technicienne maintenance', 'FACILITIES', 'ROLE_DEMANDEUR', 'serge.kouadio', null],
        ['ibrahim.toure', 'Ibrahim', 'Touré', 'Chef de la sécurité', 'FACILITIES', 'ROLE_DEMANDEUR', 'serge.kouadio', 'CMD-2026-002'],
        ['koffi.nguessan', 'Koffi', "N'Guessan", 'Agent logistique (achats)', 'FACILITIES', 'ROLE_DEMANDEUR', 'serge.kouadio', null],
        ['eric.konan', 'Éric', 'Konan', 'Chargé de clientèle', 'COMMERCIAL', 'ROLE_DEMANDEUR', 'nadia.cisse', null],
    ];

    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $services = [];
        foreach (self::DEPARTEMENTS as $nomDepartement => $listeServices) {
            $departement = new Departement($nomDepartement);
            $manager->persist($departement);
            foreach ($listeServices as $code => $libelle) {
                $services[$code] = new Service($code, $libelle, $departement);
                $manager->persist($services[$code]);
            }
        }

        $utilisateurs = [];
        foreach (self::UTILISATEURS as $numero => [$identifiant, $prenom, $nom, $fonction, $codeService, $role, $n1, $compteMd]) {
            $utilisateur = new Utilisateur();
            $utilisateur
                ->setMatricule(sprintf('CF-%04d', $numero + 1))
                ->setPrenom($prenom)
                ->setNom($nom)
                ->setEmail($identifiant.'@cofina-demo.com')
                ->setFonction($fonction)
                ->setService($services[$codeService])
                ->setRole($role)
                ->setN1($n1 ? $utilisateurs[$n1] : null)
                ->setMotDePasse($this->hasher->hashPassword($utilisateur, 'demo2026'));
            if ($compteMd) {
                $utilisateur->ouvrirCompteMenuesDepenses($compteMd);
            }
            $manager->persist($utilisateur);
            $utilisateurs[$identifiant] = $utilisateur;
        }

        $manager->flush();
    }
}
