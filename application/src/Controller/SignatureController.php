<?php

namespace App\Controller;

use App\Entity\SignatureReference;
use App\Entity\Utilisateur;
use App\Service\ChiffrementSignature;
use App\Service\ComparaisonSignatures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SignatureController extends AbstractController
{
    private const TAILLE_MAX_IMAGE = 500000;

    #[Route('/ma-signature', name: 'signature', methods: ['GET'])]
    public function afficher(ChiffrementSignature $chiffrement): Response
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();
        $signature = $utilisateur->getSignature();

        if ($signature === null) {
            return $this->render('signature/enregistrer.html.twig');
        }

        return $this->render('signature/afficher.html.twig', [
            'image' => $chiffrement->dechiffrer($signature->getImageChiffree(), $utilisateur),
            'signature' => $signature,
        ]);
    }

    #[Route('/ma-signature', name: 'signature_enregistrer', methods: ['POST'])]
    public function enregistrer(
        Request $request,
        ComparaisonSignatures $comparaison,
        ChiffrementSignature $chiffrement,
        EntityManagerInterface $em,
    ): Response {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();

        if ($utilisateur->getSignature() !== null) {
            return $this->redirectToRoute('signature');
        }
        if (!$this->isCsrfTokenValid('signature', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Le formulaire a expiré. Recommencez.');

            return $this->redirectToRoute('signature');
        }

        $essais = json_decode((string) $request->request->get('essais'), true);
        if (!$this->essaisValides($essais)) {
            $this->addFlash('error', 'Les trois essais sont obligatoires. Recommencez.');

            return $this->redirectToRoute('signature');
        }

        $retenu = $comparaison->choisirEssai(array_column($essais, 'traits'));
        $essai = $essais[$retenu];

        $signature = new SignatureReference(
            $utilisateur,
            $chiffrement->chiffrer($essai['image'], $utilisateur),
            $chiffrement->chiffrer(json_encode($essai['traits']), $utilisateur),
            $retenu + 1,
        );
        $em->persist($signature);
        $em->flush();

        $this->addFlash('success', sprintf('Signature enregistrée : nous avons gardé votre essai %d, le plus représentatif.', $retenu + 1));

        return $this->redirectToRoute('accueil');
    }

    private function essaisValides(mixed $essais): bool
    {
        if (!is_array($essais) || count($essais) !== 3) {
            return false;
        }
        foreach ($essais as $essai) {
            $image = $essai['image'] ?? '';
            $traits = $essai['traits'] ?? [];
            if (!is_string($image) || !str_starts_with($image, 'data:image/png;base64,') || strlen($image) > self::TAILLE_MAX_IMAGE) {
                return false;
            }
            if (!is_array($traits) || $traits === []) {
                return false;
            }
            foreach ($traits as $trait) {
                if (!is_array($trait) || $trait === []) {
                    return false;
                }
                foreach ($trait as $point) {
                    if (!is_array($point) || count($point) !== 2 || !is_numeric($point[0]) || !is_numeric($point[1])) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
