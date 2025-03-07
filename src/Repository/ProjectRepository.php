<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\Customers;
use App\Enum\ProjectStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Join;

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
     * Crée un QueryBuilder de base avec les jointures communes
     * 
     * @return QueryBuilder
     */
    private function createBaseQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.projectCustomer', 'c')
            ->leftJoin('p.projectManager', 'm')
            ->addSelect('c', 'm');
    }

    /**
     * Trouve les projets par client avec les relations chargées
     * 
     * @param Customers $customer Le client dont on veut les projets
     * @return Project[] Tableau de projets
     */
    public function findByCustomer(Customers $customer): array
    {
        return $this->createBaseQueryBuilder()
            ->andWhere('p.projectCustomer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('p.projectCreatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les projets par chef de projet avec les relations chargées
     * 
     * @param User $manager Le chef de projet
     * @return Project[] Tableau de projets
     */
    public function findByManager(User $manager): array
    {
        return $this->createBaseQueryBuilder()
            ->andWhere('p.projectManager = :manager')
            ->setParameter('manager', $manager)
            ->orderBy('p.projectCreatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les projets par statut avec les relations chargées
     * 
     * @param ProjectStatus $status Le statut des projets à trouver
     * @return Project[] Tableau de projets
     */
    public function findByStatus(ProjectStatus $status): array
    {
        return $this->createBaseQueryBuilder()
            ->andWhere('p.projectStatus = :status')
            ->setParameter('status', $status)
            ->orderBy('p.projectCreatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avancée de projets avec filtres multiples et relations chargées
     * 
     * @param array $filters Tableau de filtres (customer, manager, status, searchTerm, startDate, endDate)
     * @return Project[] Tableau de projets
     */
    public function searchProjects(array $filters): array
    {
        $qb = $this->createBaseQueryBuilder();

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

        if (isset($filters['searchTerm']) && !empty($filters['searchTerm'])) {
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

        // Ajouter une pagination si nécessaire
        if (isset($filters['limit']) && isset($filters['offset'])) {
            $qb->setMaxResults($filters['limit'])
               ->setFirstResult($filters['offset']);
        }

        $qb->orderBy('p.projectCreatedAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les projets en retard avec les relations chargées
     * 
     * @return Project[] Tableau de projets en retard
     */
    public function findOverdueProjects(): array
    {
        $now = new \DateTime();
        
        return $this->createBaseQueryBuilder()
            ->andWhere('p.projectStatus != :completedStatus')
            ->andWhere('p.projectStatus != :cancelledStatus')
            ->andWhere('p.projectTargetDate < :now')
            ->setParameter('completedStatus', ProjectStatus::COMPLETED)
            ->setParameter('cancelledStatus', ProjectStatus::CANCELLED)
            ->setParameter('now', $now)
            ->orderBy('p.projectTargetDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les projets actifs avec leurs tâches et relations chargées
     * 
     * @return Project[] Tableau de projets actifs avec leurs tâches
     */
    public function findActiveProjectsWithTasks(): array
    {
        return $this->createBaseQueryBuilder()
            ->andWhere('p.projectStatus = :status')
            ->setParameter('status', ProjectStatus::IN_PROGRESS)
            ->leftJoin('p.tasks', 't')
            ->leftJoin('t.taskAssignedTo', 'ta')
            ->addSelect('t', 'ta')
            ->orderBy('p.projectCreatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un projet avec toutes ses relations chargées
     * 
     * @param int $id L'identifiant du projet
     * @return Project|null Le projet ou null si non trouvé
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
            ->leftJoin('tc.commentUser', 'tcu')
            ->leftJoin('t.attachments', 'tat')
            ->addSelect('c', 'm', 't', 'ta', 'tc', 'tcu', 'tat')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte le nombre de projets par statut
     * 
     * @return array Tableau associatif [statut => nombre]
     */
    public function countByStatus(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('p.projectStatus as status, COUNT(p.id) as count')
            ->groupBy('p.projectStatus')
            ->getQuery()
            ->getResult();
        
        // Transformer le résultat en tableau associatif
        $counts = [];
        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }
        
        return $counts;
    }

    /**
     * Trouve les projets récents avec pagination
     * 
     * @param int $limit Nombre maximum de résultats
     * @param int $offset Décalage pour la pagination
     * @return Project[] Tableau de projets récents
     */
    public function findRecentProjects(int $limit = 10, int $offset = 0): array
    {
        return $this->createBaseQueryBuilder()
            ->orderBy('p.projectCreatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
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
