<?php

namespace App\Repository;

use App\Entity\Tasks;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Tasks>
 *
 * @method Tasks|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tasks|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tasks[]    findAll()
 * @method Tasks[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TasksRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tasks::class);
    }

    /**
     * Trouve les tâches par projet
     */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.taskProject = :project')
            ->setParameter('project', $project)
            ->orderBy('t.taskCreatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tâches assignées à un utilisateur
     */
    public function findByAssignedUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.taskAssignedTo = :user')
            ->setParameter('user', $user)
            ->orderBy('t.taskCreatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tâches par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.taskStatus = :status')
            ->setParameter('status', $status)
            ->orderBy('t.taskCreatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tâches par priorité
     */
    public function findByPriority(string $priority): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.taskPriority = :priority')
            ->setParameter('priority', $priority)
            ->orderBy('t.taskCreatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avancée de tâches avec filtres multiples
     */
    public function searchTasks(array $filters): array
    {
        $qb = $this->createQueryBuilder('t');

        if (isset($filters['project'])) {
            $qb->andWhere('t.taskProject = :project')
               ->setParameter('project', $filters['project']);
        }

        if (isset($filters['assignedTo'])) {
            $qb->andWhere('t.taskAssignedTo = :assignedTo')
               ->setParameter('assignedTo', $filters['assignedTo']);
        }

        if (isset($filters['status'])) {
            $qb->andWhere('t.taskStatus = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $qb->andWhere('t.taskPriority = :priority')
               ->setParameter('priority', $filters['priority']);
        }

        if (isset($filters['complexity'])) {
            $qb->andWhere('t.taskComplexity = :complexity')
               ->setParameter('complexity', $filters['complexity']);
        }

        if (isset($filters['searchTerm'])) {
            $qb->andWhere('t.taskName LIKE :searchTerm OR t.taskDescription LIKE :searchTerm')
               ->setParameter('searchTerm', '%' . $filters['searchTerm'] . '%');
        }

        if (isset($filters['startDate'])) {
            $qb->andWhere('t.taskStartDate >= :startDate')
               ->setParameter('startDate', $filters['startDate']);
        }

        if (isset($filters['endDate'])) {
            $qb->andWhere('t.taskEndDate <= :endDate')
               ->setParameter('endDate', $filters['endDate']);
        }

        $qb->orderBy('t.taskCreatedAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les tâches en retard
     */
    public function findOverdueTasks(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('t')
            ->andWhere('t.taskStatus != :completedStatus')
            ->andWhere('t.taskTargetDate < :now')
            ->setParameter('completedStatus', Tasks::STATUS_COMPLETED)
            ->setParameter('now', $now)
            ->orderBy('t.taskTargetDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une tâche avec toutes ses relations
     */
    public function findTaskWithFullData(int $id): ?Tasks
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.id = :id')
            ->setParameter('id', $id)
            ->leftJoin('t.taskProject', 'p')
            ->leftJoin('t.taskAssignedTo', 'u')
            ->leftJoin('t.taskUpdatedBy', 'ub')
            ->leftJoin('t.comments', 'c')
            ->leftJoin('c.user', 'cu')
            ->leftJoin('t.attachments', 'a')
            ->leftJoin('a.uploadedBy', 'au')
            ->addSelect('p', 'u', 'ub', 'c', 'cu', 'a', 'au')
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return Tasks[] Returns an array of Tasks objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Tasks
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
