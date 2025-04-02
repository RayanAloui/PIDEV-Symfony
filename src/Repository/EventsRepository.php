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

    public function searchEvents(string $query)
    {
        return $this->createQueryBuilder('e')
            ->where('e.nom LIKE :query')
            ->orWhere('e.lieu LIKE :query')
            ->setParameter('query', "%$query%")
            ->getQuery()
            ->getResult();
    }
}