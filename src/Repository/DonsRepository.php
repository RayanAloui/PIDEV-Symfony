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
        ->leftJoin('d.id_event', 'e')
        ->addSelect('e');
    
    // Si le champ de tri est l'événement, utilisez le nom de l'événement
    if ($sortField === 'event') {
        $qb->orderBy('e.nom', $sortOrder);
    } else {
        $qb->orderBy("d.$sortField", $sortOrder);
    }
    
    // Pour recherche numérique, convertir la requête en nombre pour comparaison
    $numericQuery = is_numeric($query) ? (float)$query : null;
    
    // Recherche textuelle standard
    $qb->where('d.description LIKE :query')
        ->orWhere('d.type_don LIKE :query')
        ->orWhere('e.nom LIKE :query')
        ->setParameter('query', "%$query%");
    
    // Si la requête est numérique, ajouter une condition pour le montant
    if ($numericQuery !== null) {
        $qb->orWhere('d.montant = :numericQuery')
            ->setParameter('numericQuery', $numericQuery);
    }
    
    return $qb->getQuery()->getResult();
}

public function countDonsByEvent(): array
{
    return $this->createQueryBuilder('d')
        ->select('e.nom, COUNT(d.id_don) as donCount, SUM(d.montant) as totalMontant')
        ->leftJoin('d.id_event', 'e')  // Corriger ici avec le bon nom de propriété
        ->groupBy('e.id_event')
        ->orderBy('donCount', 'DESC')
        ->getQuery()
        ->getResult();
}
    // src/Repository/DonRepository.php

public function findAllOrderByMontant(string $order = 'ASC'): array
{
    return $this->createQueryBuilder('d')
        ->orderBy('d.montant', $order)
        ->getQuery()
        ->getResult();
}

public function countDonsByType()
    {
        return $this->createQueryBuilder('d')
            ->select('d.type_don as type_don, COUNT(d.id_don) as count')
            ->groupBy('d.type_don')
            ->getQuery()
            ->getResult();
    }
    
    public function sumDonsByMonth()
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT 
                EXTRACT(MONTH FROM date_don) as month, 
                SUM(montant) as total
            FROM 
                dons
            GROUP BY 
                month
            ORDER BY 
                month ASC
        ';
        $stmt = $conn->executeQuery($sql);
        return $stmt->fetchAllAssociative();
    }
    
    public function getDonsEvolution()
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT 
                date_don as date, 
                (
                    SELECT SUM(d2.montant) 
                    FROM dons d2 
                    WHERE d2.date_don <= d1.date_don
                ) as cumulative_amount
            FROM 
                dons d1
            GROUP BY 
                date_don
            ORDER BY 
                date_don ASC
        ';
        $stmt = $conn->executeQuery($sql);
        return $stmt->fetchAllAssociative();
    }
    
    public function getDonsByEvent()
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT 
                e.nom as event_name, 
                SUM(d.montant) as total
            FROM 
                dons d
            LEFT JOIN 
                events e ON d.id_event = e.id_event
            GROUP BY 
                d.id_event
            ORDER BY 
                total DESC
        ';
        $stmt = $conn->executeQuery($sql);
        return $stmt->fetchAllAssociative();
    }


}
