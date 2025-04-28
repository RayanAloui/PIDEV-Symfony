<?php

namespace App\Repository;

use App\Entity\Orphelin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Orphelin>
 */
class OrphelinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Orphelin::class);
    }

    public function searchOrphelins(string $query, string $sortField = 'nomO', string $sortOrder = 'asc')
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.tuteur', 't')
            ->addSelect('t');

        // Gestion du tri
        if ($sortField === 'tuteur') {
            $qb->orderBy('t.nomT', $sortOrder)
                ->addOrderBy('t.prenomT', $sortOrder);
        } else {
            // Assurez-vous que le champ de tri existe dans l'entité
            if (property_exists('App\Entity\Orphelin', $sortField)) {
                $qb->orderBy("o.$sortField", $sortOrder);
            } else {
                // Tri par défaut
                $qb->orderBy('o.nomO', $sortOrder);
            }
        }

        // Si la requête n'est pas vide
        if (!empty($query)) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('o.nomO', ':query'),
                $qb->expr()->like('o.prenomO', ':query'),
                $qb->expr()->like('t.nomT', ':query'),
                $qb->expr()->like('t.prenomT', ':query'),
                // Recherche par nom prénom du tuteur
                $qb->expr()->like('CONCAT(t.nomT, \' \', t.prenomT)', ':query')
            ))
                ->setParameter('query', "%$query%");

            // Vérifier si la recherche est une date au format JJ/MM/AAAA
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $query)) {
                try {
                    $dateParts = explode('/', $query);
                    $formattedDate = "{$dateParts[2]}-{$dateParts[1]}-{$dateParts[0]}";

                    // Ajouter la condition de recherche par date
                    $qb->orWhere('o.dateNaissance = :dateNaissance')
                        ->setParameter('dateNaissance', new \DateTime($formattedDate));
                } catch (\Exception $e) {
                    // La date n'est pas valide, on ignore cette partie de la recherche
                }
            }
        }

        return $qb->getQuery()->getResult();
    }

    public function countOrphelinsByTuteur(): array
    {
        return $this->createQueryBuilder('o')
            ->select('t.nomT, COUNT(o.idO) as orphelinCount')
            ->leftJoin('o.tuteur', 't')
            ->groupBy('t.idT')
            ->orderBy('orphelinCount', 'DESC')
            ->getQuery()
            ->getResult();
    }





    //    /**
    //     * @return Orphelin[] Returns an array of Orphelin objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Orphelin
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
