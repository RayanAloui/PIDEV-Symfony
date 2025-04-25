<?php

namespace App\Repository;

use App\Entity\Reclamation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reclamation>
 */
class ReclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reclamation::class);
    }

    public function searchReclamations(
        ?string $query = null,
        string $sortField = 'date',
        string $sortOrder = 'DESC',
        ?string $type = null
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.'.$sortField, $sortOrder);
    
        if ($query) {
            $qb->andWhere('r.mail LIKE :query OR r.description LIKE :query OR r.typereclamation LIKE :query')
               ->setParameter('query', '%'.$query.'%');
        }
    
        if ($type) {
            $qb->andWhere('r.typereclamation = :type')
               ->setParameter('type', $type);
        }
    
        return $qb->getQuery()->getResult();
    }
    

    public function countReclamationsByType(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.typereclamation as type, COUNT(r.id) as count')
            ->groupBy('r.typereclamation')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getReclamationsByDateRange(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?string $type = null
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->where('r.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('r.date', 'ASC');

        if ($type) {
            $qb->andWhere('r.typereclamation = :type')
               ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    public function findRecentReclamations(int $maxResults = 5): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.date', 'DESC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }
}