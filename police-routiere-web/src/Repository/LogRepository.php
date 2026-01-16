<?php

namespace App\Repository;

use App\Entity\Log;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Log>
 */
class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }

    public function save(Log $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Log $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUser(User $user, int $limit = 100): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByEntity(string $entity, int $limit = 100): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.entity = :entity')
            ->setParameter('entity', $entity)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByAction(string $action, int $limit = 100): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.action = :action')
            ->setParameter('action', $action)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByLevel(string $level, int $limit = 100): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.level = :level')
            ->setParameter('level', $level)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTimeInterface $start, \DateTimeInterface $end, int $limit = 100): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.createdAt >= :start')
            ->andWhere('l.createdAt <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findRecent(int $limit = 50): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findErrors(int $limit = 100): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.level IN (:levels)')
            ->setParameter('levels', ['ERROR', 'CRITICAL'])
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByLevel(): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.level', 'COUNT(l.id) as count')
            ->groupBy('l.level')
            ->getQuery()
            ->getResult();
    }

    public function countByAction(): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.action', 'COUNT(l.id) as count')
            ->groupBy('l.action')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByEntity(): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.entity', 'COUNT(l.id) as count')
            ->groupBy('l.entity')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function cleanupOldLogs(int $days = 90): int
    {
        $date = new \DateTimeImmutable("-{$days} days");
        
        return $this->createQueryBuilder('l')
            ->delete()
            ->where('l.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    public function search(string $query, int $limit = 100): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.action LIKE :query')
            ->orWhere('l.description LIKE :query')
            ->orWhere('l.entity LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
