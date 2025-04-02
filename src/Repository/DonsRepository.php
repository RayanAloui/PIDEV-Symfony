<?php

namespace App\Repository;

use App\Entity\Dons;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dons>
 */
class DonsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dons::class);
    }

    public function searchDons(string $query, string $sortField = 'montant', string $sortOrder = 'desc')
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.event', 'e') // Joindre la table Events
            ->addSelect('e') // Récupérer les données de l'événement
            ->orderBy(
                $sortField === 'event' ? 'e.nom' : "d.$sortField",
                $sortOrder
            );

        // Recherche par description, montant ou nom d'événement
        $qb->where('d.description LIKE :query')
            ->orWhere('d.montant LIKE :query')
            ->orWhere('e.nom LIKE :query')
            ->setParameter('query', "%$query%");

        return $qb->getQuery()->getResult();
    }

    public function countDonsByEvent(): array
    {
        return $this->createQueryBuilder('d')
            ->select('e.nom, COUNT(d.id) as donCount, SUM(d.montant) as totalMontant')
            ->leftJoin('d.event', 'e')
            ->groupBy('e.id')
            ->orderBy('donCount', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
