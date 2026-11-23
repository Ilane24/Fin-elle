<?php

namespace App\Service;

class ComparaisonSignatures
{
    private const TAILLE_GRILLE = 24;

    /**
     * @param array<int, array<int, array<int, array{0: float, 1: float}>>> $essais
     */
    public function choisirEssai(array $essais): int
    {
        $grilles = array_map(fn (array $traits) => $this->grille($traits), $essais);

        $meilleurEssai = 0;
        $meilleurScore = -1.0;
        foreach ($grilles as $i => $grille) {
            $score = 0.0;
            foreach ($grilles as $j => $autre) {
                if ($i !== $j) {
                    $score += $this->ressemblance($grille, $autre);
                }
            }
            if ($score > $meilleurScore) {
                $meilleurScore = $score;
                $meilleurEssai = $i;
            }
        }

        return $meilleurEssai;
    }

    private function grille(array $traits): array
    {
        $points = array_merge(...$traits);
        $xs = array_column($points, 0);
        $ys = array_column($points, 1);
        $minX = min($xs);
        $minY = min($ys);
        $taille = max(max($xs) - $minX, max($ys) - $minY, 1);

        $cases = [];
        foreach ($traits as $trait) {
            foreach ($trait as $k => [$x, $y]) {
                [$xPrecedent, $yPrecedent] = $k > 0 ? $trait[$k - 1] : [$x, $y];
                $pas = max(1, (int) ceil(hypot($x - $xPrecedent, $y - $yPrecedent) / $taille * self::TAILLE_GRILLE * 2));
                for ($p = 0; $p <= $pas; $p++) {
                    $px = $xPrecedent + ($x - $xPrecedent) * $p / $pas;
                    $py = $yPrecedent + ($y - $yPrecedent) * $p / $pas;
                    $colonne = min(self::TAILLE_GRILLE - 1, (int) (($px - $minX) / $taille * self::TAILLE_GRILLE));
                    $ligne = min(self::TAILLE_GRILLE - 1, (int) (($py - $minY) / $taille * self::TAILLE_GRILLE));
                    $cases[$ligne.'-'.$colonne] = true;
                }
            }
        }

        return $cases;
    }

    private function ressemblance(array $a, array $b): float
    {
        $communes = count(array_intersect_key($a, $b));

        return 2 * $communes / (count($a) + count($b));
    }
}
