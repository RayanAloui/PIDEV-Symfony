<?php

namespace App\Repository;

use App\Entity\Reponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reponse>
 */
class ReponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reponse::class);
    }

    /**
     * Search responses by text query with optional sorting.
     */
    public function searchReponses(
        string $query = '', 
        string $sortField = 'date', 
        string $sortOrder = 'DESC'
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->orderBy("r.$sortField", $sortOrder);

        if (!empty($query)) {
            $qb->andWhere('r.description LIKE :query OR r.statut LIKE :query')
               ->setParameter('query', '%'.$query.'%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count responses grouped by status with average index.
     */
    public function countReponsesByStatut(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.statut AS statut, COUNT(r.id) AS reponseCount, AVG(r.indice) AS averageIndice')
            ->groupBy('r.statut')
            ->orderBy('reponseCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get responses filtered by status.
     */
    public function getReponsesByStatut(string $statut): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('r.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get high-priority responses based on minimum indice.
     */
    public function findHighPriorityReponses(int $minIndice = 70): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.indice >= :minIndice')
            ->setParameter('minIndice', $minIndice)
            ->orderBy('r.indice', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get the most recent responses (default limit: 5).
     */
    public function getRecentReponses(int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
