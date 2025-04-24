<?php

namespace App\Repository;

use App\Entity\Visites;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VisitesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visites::class);
    }

    // src/Repository/VisitesRepository.php

public function findByFilters(string $search = '', ?string $sortBy = null, string $sortOrder = 'asc'): array
{
    $queryBuilder = $this->createQueryBuilder('v')
        ->join('v.id_user', 'u') // Utilisation de join au lieu de leftJoin pour plus de performance
        ->addSelect('u');

    if (!empty($search)) {
        $queryBuilder
            ->andWhere('LOWER(u.name) LIKE LOWER(:search) OR LOWER(u.surname) LIKE LOWER(:search)')
            ->setParameter('search', '%'.addcslashes($search, '%_').'%');
    }

    if (in_array($sortBy, ['date', 'heure'], true)) {
        $queryBuilder->orderBy('v.'.$sortBy, $sortOrder === 'desc' ? 'desc' : 'asc');
    } else {
        $queryBuilder->orderBy('v.date', 'asc');
    }

    try {
        return $queryBuilder->getQuery()->getResult();
    } catch (\Exception $e) {
        throw new \RuntimeException('Erreur lors de la récupération des visites: '.$e->getMessage());
    }
}
}