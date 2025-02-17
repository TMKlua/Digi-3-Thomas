<?php

namespace App\Repository;

use App\Entity\Customers;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Customers>
 *
 * @method Customers|null find($id, $lockMode = null, $lockVersion = null)
 * @method Customers|null findOneBy(array $criteria, array $orderBy = null)
 * @method Customers[]    findAll()
 * @method Customers[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customers::class);
    }

    /**
     * Trouve les clients par nom
     */
    public function findByName(string $name): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.customerName LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('c.customerName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les clients actifs (avec des projets en cours)
     */
    public function findActiveCustomers(): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.projects', 'p')
            ->andWhere('p.projectStatus = :status')
            ->setParameter('status', Project::STATUS_IN_PROGRESS)
            ->orderBy('c.customerName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avancée de clients avec filtres multiples
     */
    public function searchCustomers(array $filters): array
    {
        $qb = $this->createQueryBuilder('c');

        if (isset($filters['name'])) {
            $qb->andWhere('c.customerName LIKE :name')
               ->setParameter('name', '%' . $filters['name'] . '%');
        }

        if (isset($filters['city'])) {
            $qb->andWhere('c.customerAddressCity LIKE :city')
               ->setParameter('city', '%' . $filters['city'] . '%');
        }

        if (isset($filters['country'])) {
            $qb->andWhere('c.customerAddressCountry = :country')
               ->setParameter('country', $filters['country']);
        }

        if (isset($filters['vat'])) {
            $qb->andWhere('c.customerVat = :vat')
               ->setParameter('vat', $filters['vat']);
        }

        if (isset($filters['siren'])) {
            $qb->andWhere('c.customerSiren = :siren')
               ->setParameter('siren', $filters['siren']);
        }

        if (isset($filters['reference'])) {
            $qb->andWhere('c.customerReference LIKE :reference')
               ->setParameter('reference', '%' . $filters['reference'] . '%');
        }

        if (isset($filters['hasProjects']) && $filters['hasProjects']) {
            $qb->join('c.projects', 'p');
        }

        $qb->orderBy('c.customerName', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les clients avec leurs projets
     */
    public function findCustomersWithProjects(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.projects', 'p')
            ->addSelect('p')
            ->orderBy('c.customerName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les clients par pays
     */
    public function findByCountry(string $country): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.customerAddressCountry = :country')
            ->setParameter('country', $country)
            ->orderBy('c.customerName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un client avec toutes ses relations
     */
    public function findCustomerWithFullData(int $id): ?Customers
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->setParameter('id', $id)
            ->leftJoin('c.projects', 'p')
            ->leftJoin('p.projectManager', 'pm')
            ->leftJoin('p.tasks', 't')
            ->leftJoin('t.taskAssignedTo', 'ta')
            ->addSelect('p', 'pm', 't', 'ta')
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return Customers[] Returns an array of Customers objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Customers
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
