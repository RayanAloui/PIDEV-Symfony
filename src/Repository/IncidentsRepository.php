<?php

namespace App\Repository;

use App\Entity\Incidents;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class IncidentsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Incidents::class);
    }

    // Méthode pour effectuer la recherche et le tri
    public function findByFilters($search, $sortBy, $sortOrder)
    {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.id_user', 'u')
            ->leftJoin('i.id_visite', 'v');
    
        // Filtrage par recherche
        if ($search) {
            $qb->andWhere('u.name LIKE :search OR u.surname LIKE :search OR i.description LIKE :search OR i.dateincident LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }
    
        // Tri
        if ($sortBy) {
            $qb->orderBy('i.' . $sortBy, $sortOrder); 
        } else {
            $qb->orderBy('i.dateincident', 'asc');
        }
    
        return $qb->getQuery()->getResult();
    }
    

}
