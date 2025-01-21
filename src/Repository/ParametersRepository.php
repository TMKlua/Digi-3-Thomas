<?php

namespace App\Repository;

use App\Entity\Parameters;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class ParametersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parameters::class);
    }

    /**
     * Trouve les paramètres actifs pour une catégorie donnée
     * 
     * @param string $category Catégorie des paramètres
     * @param \DateTime $currentDate Date de référence
     * @return array Liste des paramètres actifs
     */
    public function findActiveParametersByCategory(string $category, \DateTime $currentDate): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.paramCategory = :category')
            ->andWhere('p.paramDateFrom <= :currentDate')
            ->andWhere('p.paramDateTo >= :currentDate')
            ->setParameter('category', $category)
            ->setParameter('currentDate', $currentDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un paramètre par sa clé
     * 
     * @param string $key Clé du paramètre
     * @return Parameters|null Le paramètre trouvé
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
     * 
     * @param array $filters Filtres de recherche
     * @return array Liste des paramètres correspondants
     */
    public function searchParameters(array $filters): array
    {
        $qb = $this->createQueryBuilder('p');

        // Filtrage par catégorie
        if (isset($filters['category'])) {
            $qb->andWhere('p.paramCategory = :category')
               ->setParameter('category', $filters['category']);
        }

        // Filtrage par terme de recherche
        if (isset($filters['searchTerm'])) {
            $qb->andWhere('p.paramKey LIKE :searchTerm OR p.paramValue LIKE :searchTerm')
               ->setParameter('searchTerm', '%' . $filters['searchTerm'] . '%');
        }

        // Filtrage par période
        if (isset($filters['dateFrom']) && isset($filters['dateTo'])) {
            $qb->andWhere('p.paramDateFrom >= :dateFrom')
               ->andWhere('p.paramDateTo <= :dateTo')
               ->setParameter('dateFrom', $filters['dateFrom'])
               ->setParameter('dateTo', $filters['dateTo']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les paramètres expirés
     * 
     * @param \DateTime $currentDate Date de référence
     * @return array Liste des paramètres expirés
     */
    public function findExpiredParameters(\DateTime $currentDate): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.paramDateTo < :currentDate')
            ->setParameter('currentDate', $currentDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de paramètres par catégorie
     * 
     * @return array Nombre de paramètres par catégorie
     */
    public function countParametersByCategory(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.paramCategory, COUNT(p) as paramCount')
            ->groupBy('p.paramCategory')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les paramètres avec pagination
     * 
     * @param int $page Numéro de page
     * @param int $limit Nombre de résultats par page
     * @return array Liste des paramètres paginés
     */
    public function findParametersPaginated(int $page = 1, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findActiveParameters(\DateTime $currentDate): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.paramDateFrom <= :currentDate')
            ->andWhere('p.paramDateTo >= :currentDate')
            ->setParameter('currentDate', $currentDate)
            ->getQuery()
            ->getResult();
    }
}