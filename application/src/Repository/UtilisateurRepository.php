<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Utilisateur> */
class UtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /** @return Utilisateur[] */
    public function findResponsables(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.actif = true')
            ->andWhere('u.role != :demandeur')
            ->setParameter('demandeur', 'ROLE_DEMANDEUR')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function prochainNumeroCompteMd(): string
    {
        $nombre = $this->count(['compteMenuesDepenses' => true]);

        return sprintf('CMD-%s-%03d', date('Y'), $nombre + 1);
    }
}
