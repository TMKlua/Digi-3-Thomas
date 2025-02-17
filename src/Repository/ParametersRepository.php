<?php

namespace App\Repository;

use App\Entity\Parameters;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Parameters>
 *
 * @method Parameters|null find($id, $lockMode = null, $lockVersion = null)
 * @method Parameters|null findOneBy(array $criteria, array $orderBy = null)
 * @method Parameters[]    findAll()
 * @method Parameters[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParametersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parameters::class);
    }

    /**
     * Trouve un paramètre par sa clé
     */
    public function findOneByKey(string $key): ?Parameters
    {
        return $this->createQueryBuilder('p')
            ->where('p.paramKey = :key')
            ->setParameter('key', $key)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Recherche de paramètres avec filtres multiples
     */
    public function searchParameters(array $filters): array
    {
        $qb = $this->createQueryBuilder('p');

        if (isset($filters['searchTerm'])) {
            $qb->andWhere('p.paramKey LIKE :searchTerm OR p.paramValue LIKE :searchTerm OR p.paramDescription LIKE :searchTerm')
               ->setParameter('searchTerm', '%' . $filters['searchTerm'] . '%');
        }

        if (isset($filters['key'])) {
            $qb->andWhere('p.paramKey LIKE :key')
               ->setParameter('key', '%' . $filters['key'] . '%');
        }

        if (isset($filters['updatedBy'])) {
            $qb->andWhere('p.paramUpdatedBy = :updatedBy')
               ->setParameter('updatedBy', $filters['updatedBy']);
        }

        if (isset($filters['updatedAfter'])) {
            $qb->andWhere('p.paramUpdatedAt >= :updatedAfter')
               ->setParameter('updatedAfter', $filters['updatedAfter']);
        }

        $qb->orderBy('p.paramKey', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les paramètres par préfixe de clé
     */
    public function findByKeyPrefix(string $prefix): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.paramKey LIKE :prefix')
            ->setParameter('prefix', $prefix . '_%')
            ->orderBy('p.paramKey', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les paramètres récemment mis à jour
     */
    public function findRecentlyUpdated(\DateTime $since): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.paramUpdatedAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('p.paramUpdatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les paramètres avec pagination
     */
    public function findParametersPaginated(int $page = 1, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->orderBy('p.paramKey', 'ASC')
            ->getQuery()
            ->getResult();
    }
}