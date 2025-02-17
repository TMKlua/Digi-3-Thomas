<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\Customers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Project>
 *
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * Trouve les projets par client
     */
    public function findByCustomer(Customers $customer): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.projectCustomer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('p.projectCreatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les projets par chef de projet
     */
    public function findByManager(User $manager): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.projectManager = :manager')
            ->setParameter('manager', $manager)
            ->orderBy('p.projectCreatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les projets par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.projectStatus = :status')
            ->setParameter('status', $status)
            ->orderBy('p.projectCreatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avancée de projets avec filtres multiples
     */
    public function searchProjects(array $filters): array
    {
        $qb = $this->createQueryBuilder('p');

        if (isset($filters['customer'])) {
            $qb->andWhere('p.projectCustomer = :customer')
               ->setParameter('customer', $filters['customer']);
        }

        if (isset($filters['manager'])) {
            $qb->andWhere('p.projectManager = :manager')
               ->setParameter('manager', $filters['manager']);
        }

        if (isset($filters['status'])) {
            $qb->andWhere('p.projectStatus = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['searchTerm'])) {
            $qb->andWhere('p.projectName LIKE :searchTerm OR p.projectDescription LIKE :searchTerm')
               ->setParameter('searchTerm', '%' . $filters['searchTerm'] . '%');
        }

        if (isset($filters['startDate'])) {
            $qb->andWhere('p.projectStartDate >= :startDate')
               ->setParameter('startDate', $filters['startDate']);
        }

        if (isset($filters['endDate'])) {
            $qb->andWhere('p.projectEndDate <= :endDate')
               ->setParameter('endDate', $filters['endDate']);
        }

        $qb->orderBy('p.projectCreatedAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les projets en retard
     */
    public function findOverdueProjects(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.projectStatus != :completedStatus')
            ->andWhere('p.projectStatus != :cancelledStatus')
            ->andWhere('p.projectTargetDate < :now')
            ->setParameter('completedStatus', Project::STATUS_COMPLETED)
            ->setParameter('cancelledStatus', Project::STATUS_CANCELLED)
            ->setParameter('now', $now)
            ->orderBy('p.projectTargetDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les projets actifs avec leurs tâches
     */
    public function findActiveProjectsWithTasks(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.projectStatus = :status')
            ->setParameter('status', Project::STATUS_IN_PROGRESS)
            ->leftJoin('p.tasks', 't')
            ->addSelect('t')
            ->orderBy('p.projectCreatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les projets avec toutes leurs relations
     */
    public function findProjectWithFullData(int $id): ?Project
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->leftJoin('p.projectCustomer', 'c')
            ->leftJoin('p.projectManager', 'm')
            ->leftJoin('p.tasks', 't')
            ->leftJoin('t.taskAssignedTo', 'ta')
            ->leftJoin('t.comments', 'tc')
            ->leftJoin('t.attachments', 'ta')
            ->addSelect('c', 'm', 't', 'ta', 'tc', 'ta')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Project[] Returns an array of Project objects
     */
    public function findByExampleField($value): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneBySomeField($value): ?Project
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
