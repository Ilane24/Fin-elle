<?php

namespace App\DataFixtures;

use App\Entity\Departement;
use App\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const DEPARTEMENTS = [
        'ADMINISTRATION & LOGISTIQUE',
        'FINANCE & COMPTABILITÉ',
        'DIRECTION GÉNÉRALE',
        'RESSOURCES HUMAINES',
        "SYSTÈMES D'INFORMATION",
        'COMMERCIAL',
    ];

    private const UTILISATEURS = [
        ['mireille.gbagbo', 'Mireille', 'Gbagbo', 'Directrice administrative', 'ADMINISTRATION & LOGISTIQUE', 'ROLE_ADMIN', null, null],
        ['regis.kouame', 'Régis', 'Kouamé', 'Directeur Financier et Comptable', 'FINANCE & COMPTABILITÉ', 'ROLE_CFO', null, null],
        ['mariam.bamba', 'Mariam Yenita', 'Bamba', 'Directrice Générale', 'DIRECTION GÉNÉRALE', 'ROLE_DG', null, null],
        ['aminata.kone', 'Aminata', 'Koné', 'Responsable Ressources Humaines', 'RESSOURCES HUMAINES', 'ROLE_MANAGER', null, null],
        ['yves.brou', 'Yves', 'Brou', 'Responsable Informatique', "SYSTÈMES D'INFORMATION", 'ROLE_MANAGER', null, null],
        ['nadia.cisse', 'Nadia', 'Cissé', 'Directrice Commerciale', 'COMMERCIAL', 'ROLE_MANAGER', null, null],
        ['serge.kouadio', 'Serge', 'Kouadio', 'Responsable Facilities', 'ADMINISTRATION & LOGISTIQUE', 'ROLE_MANAGER', 'mireille.gbagbo', 'CMD-2026-003'],
        ['carine.toro', 'Carine', 'Toro', 'Responsable Achats et Moyens Généraux', 'ADMINISTRATION & LOGISTIQUE', 'ROLE_RAF', 'mireille.gbagbo', null],
        ['paul.yao', 'Paul', 'Yao', 'Comptable', 'FINANCE & COMPTABILITÉ', 'ROLE_COMPTA', 'regis.kouame', null],
        ['fatou.diallo', 'Fatou', 'Diallo', 'Assistante Facilities', 'ADMINISTRATION & LOGISTIQUE', 'ROLE_DEMANDEUR', 'serge.kouadio', 'CMD-2026-001'],
        ['awa.ndiaye', 'Awa', 'Ndiaye', 'Technicienne maintenance', 'ADMINISTRATION & LOGISTIQUE', 'ROLE_DEMANDEUR', 'serge.kouadio', null],
        ['ibrahim.toure', 'Ibrahim', 'Touré', 'Chef de la sécurité', 'ADMINISTRATION & LOGISTIQUE', 'ROLE_DEMANDEUR', 'serge.kouadio', 'CMD-2026-002'],
        ['koffi.nguessan', 'Koffi', "N'Guessan", 'Agent logistique (achats)', 'ADMINISTRATION & LOGISTIQUE', 'ROLE_DEMANDEUR', 'serge.kouadio', null],
        ['eric.konan', 'Éric', 'Konan', 'Chargé de clientèle', 'COMMERCIAL', 'ROLE_DEMANDEUR', 'nadia.cisse', null],
    ];

    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $departements = [];
        foreach (self::DEPARTEMENTS as $nom) {
            $departements[$nom] = new Departement($nom);
            $manager->persist($departements[$nom]);
        }

        $utilisateurs = [];
        foreach (self::UTILISATEURS as [$identifiant, $prenom, $nom, $fonction, $departement, $role, $n1, $compteMd]) {
            $utilisateur = new Utilisateur();
            $utilisateur
                ->setPrenom($prenom)
                ->setNom($nom)
                ->setEmail($identifiant.'@cofina-demo.com')
                ->setFonction($fonction)
                ->setDepartement($departements[$departement])
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
