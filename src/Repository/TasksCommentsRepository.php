<?php

namespace App\Repository;

use App\Entity\Tasks;
use App\Entity\TasksComments;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<TasksComments>
 *
 * @method TasksComments|null find($id, $lockMode = null, $lockVersion = null)
 * @method TasksComments|null findOneBy(array $criteria, array $orderBy = null)
 * @method TasksComments[]    findAll()
 * @method TasksComments[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TasksCommentsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TasksComments::class);
    }

    /**
     * Trouve les commentaires d'une tâche
     */
    public function findByTask(Tasks $task): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.task = :task')
            ->setParameter('task', $task)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les commentaires d'un utilisateur
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les commentaires récents
     */
    public function findRecentComments(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche dans les commentaires
     */
    public function searchComments(string $term): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.content LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return TasksComments[] Returns an array of TasksComments objects
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

    //    public function findOneBySomeField($value): ?TasksComments
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
