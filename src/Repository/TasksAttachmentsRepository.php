<?php

namespace App\Repository;

use App\Entity\Tasks;
use App\Entity\TasksAttachments;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<TasksAttachments>
 *
 * @method TasksAttachments|null find($id, $lockMode = null, $lockVersion = null)
 * @method TasksAttachments|null findOneBy(array $criteria, array $orderBy = null)
 * @method TasksAttachments[]    findAll()
 * @method TasksAttachments[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TasksAttachmentsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TasksAttachments::class);
    }

    /**
     * Trouve les pièces jointes d'une tâche
     */
    public function findByTask(Tasks $task): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.task = :task')
            ->setParameter('task', $task)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les pièces jointes téléchargées par un utilisateur
     */
    public function findByUploader(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.uploadedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les pièces jointes par type de fichier
     */
    public function findByFileType(string $type): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.type = :type')
            ->setParameter('type', $type)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les pièces jointes récentes
     */
    public function findRecentAttachments(int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche dans les pièces jointes
     */
    public function searchAttachments(array $criteria): array
    {
        $qb = $this->createQueryBuilder('a');

        if (isset($criteria['name'])) {
            $qb->andWhere('a.name LIKE :name')
               ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        if (isset($criteria['type'])) {
            $qb->andWhere('a.type = :type')
               ->setParameter('type', $criteria['type']);
        }

        if (isset($criteria['minSize'])) {
            $qb->andWhere('a.size >= :minSize')
               ->setParameter('minSize', $criteria['minSize']);
        }

        if (isset($criteria['maxSize'])) {
            $qb->andWhere('a.size <= :maxSize')
               ->setParameter('maxSize', $criteria['maxSize']);
        }

        $qb->orderBy('a.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return TasksAttachments[] Returns an array of TasksAttachments objects
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

    //    public function findOneBySomeField($value): ?TasksAttachments
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
