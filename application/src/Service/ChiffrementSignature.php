<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ChiffrementSignature
{
    private string $cle;

    public function __construct(#[Autowire('%env(APP_SIGNATURE_KEY)%')] string $cleBase64)
    {
        $this->cle = base64_decode($cleBase64);
    }

    public function chiffrer(string $texte, Utilisateur $proprietaire): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $chiffre = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($texte, $this->lien($proprietaire), $nonce, $this->cle);

        return base64_encode($nonce.$chiffre);
    }

    public function dechiffrer(string $donnees, Utilisateur $proprietaire): ?string
    {
        $brut = base64_decode($donnees);
        $nonce = substr($brut, 0, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $chiffre = substr($brut, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $texte = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($chiffre, $this->lien($proprietaire), $nonce, $this->cle);

        return $texte === false ? null : $texte;
    }

    private function lien(Utilisateur $proprietaire): string
    {
        return 'utilisateur-'.$proprietaire->getId();
    }
}
