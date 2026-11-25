<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\DepartementRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class InscriptionController extends AbstractController
{
    #[Route('/inscription', name: 'inscription')]
    public function inscription(
        Request $request,
        DepartementRepository $departements,
        UtilisateurRepository $utilisateurs,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        Security $security,
        #[Autowire('%env(APP_DOMAINE_EMAIL)%')] string $domaine,
    ): Response {
        $saisie = $request->request->all();
        $erreurs = [];

        if ($request->isMethod('POST')) {
            $champ = fn (string $nom): string => trim((string) ($saisie[$nom] ?? ''));
            $departement = $departements->find((int) $champ('departement'));
            $n1 = $utilisateurs->find((int) $champ('n1'));
            $email = mb_strtolower($champ('email'));

            if (!$this->isCsrfTokenValid('inscription', $champ('_token'))) {
                $erreurs['formulaire'] = 'Le formulaire a expiré. Rechargez la page et recommencez.';
            }
            if ($champ('nom') === '') {
                $erreurs['nom'] = 'Saisissez votre nom.';
            }
            if ($champ('prenom') === '') {
                $erreurs['prenom'] = 'Saisissez votre prénom.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erreurs['email'] = 'Saisissez une adresse e-mail valide.';
            } elseif (!str_ends_with($email, '@'.$domaine)) {
                $erreurs['email'] = 'Utilisez votre adresse @'.$domaine.'.';
            } elseif ($utilisateurs->findOneBy(['email' => $email])) {
                $erreurs['email'] = 'Un compte existe déjà avec cette adresse.';
            }
            if ($champ('fonction') === '') {
                $erreurs['fonction'] = 'Saisissez votre fonction.';
            }
            if (!$departement) {
                $erreurs['departement'] = 'Choisissez votre département.';
            }
            if (!$n1 || !$departement || $n1->getDepartement() !== $departement || $n1->getRole() === 'ROLE_DEMANDEUR') {
                $erreurs['n1'] = 'Choisissez un N+1 de votre département.';
            }
            if (!preg_match('/^(?=.*\d).{8,}$/', $champ('motDePasse'))) {
                $erreurs['motDePasse'] = '8 caractères minimum, dont au moins 1 chiffre.';
            } elseif ($champ('motDePasse') !== $champ('confirmation')) {
                $erreurs['confirmation'] = 'Les deux mots de passe ne correspondent pas.';
            }

            if (!$erreurs) {
                $utilisateur = new Utilisateur();
                $utilisateur
                    ->setNom($champ('nom'))
                    ->setPrenom($champ('prenom'))
                    ->setTelephone($champ('telephone') ?: null)
                    ->setEmail($email)
                    ->setFonction($champ('fonction'))
                    ->setDepartement($departement)
                    ->setN1($n1)
                    ->setMotDePasse($hasher->hashPassword($utilisateur, $champ('motDePasse')));
                if (isset($saisie['compteMenuesDepenses'])) {
                    $utilisateur->ouvrirCompteMenuesDepenses($utilisateurs->prochainNumeroCompteMd());
                }

                $em->persist($utilisateur);
                $em->flush();

                $security->login($utilisateur, 'form_login', 'main');
                $this->addFlash('success', 'Compte créé. Enregistrez maintenant votre signature.');

                return $this->redirectToRoute('signature');
            }
        }

        return $this->render('inscription/index.html.twig', [
            'departements' => $departements->findBy([], ['nom' => 'ASC']),
            'responsables' => $utilisateurs->findResponsables(),
            'saisie' => $saisie,
            'erreurs' => $erreurs,
            'domaine' => $domaine,
        ]);
    }
}
