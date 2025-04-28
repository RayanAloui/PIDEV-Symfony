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
    public function findByFilters($search, $sortBy, $sortOrder)
    {
        $qb = $this->createQueryBuilder('v')
                   ->leftJoin('v.id_user', 'u') // Assuming 'id_user' is the relationship with User
                   ->where('u.name LIKE :search OR u.surname LIKE :search OR v.motif LIKE :search')
                   ->setParameter('search', '%' . $search . '%');
    
        if ($sortBy && $sortOrder) {
            $qb->orderBy('v.' . $sortBy, $sortOrder);
        } else {
            $qb->orderBy('v.date', 'asc'); // Default sorting by date
        }
    
        return $qb->getQuery()->getResult();
    }
    
}