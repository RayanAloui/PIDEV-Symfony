<?php
namespace App\Repository;


use App\Entity\Events;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Events::class);
    }

    public function searchEvents(string $query, string $sortField = 'nom', string $sortOrder = 'asc')
    {
        $qb = $this->createQueryBuilder('e')
        ->where('e.nom LIKE :query')
        ->orWhere('e.lieu LIKE :query')
        ->orWhere('e.description LIKE :query')
        ->setParameter('query', "%$query%")
        ->orderBy("e.$sortField", $sortOrder);
        
    return $qb->getQuery()->getResult();
    }
}