<?php

namespace App\Repository;

use App\Entity\Infraction;
use App\Entity\Region;
use App\Entity\Brigade;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Infraction>
 */
class InfractionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Infraction::class);
    }

    public function countByRegion(int|Region $region): int
    {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->join('i.controle', 'c')
            ->join('c.brigade', 'b')
            ->join('b.region', 'r');

        if (is_int($region)) {
            $qb->andWhere('r.id = :regionId')
                ->setParameter('regionId', $region);
        } else {
            $qb->andWhere('b.region = :region')
                ->setParameter('region', $region);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findByRegion(Region $region): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.controle', 'c')
            ->join('c.brigade', 'b')
            ->where('b.region = :region')
            ->setParameter('region', $region)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByBrigade(Brigade $brigade): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.controle', 'c')
            ->where('c.brigade = :brigade')
            ->setParameter('brigade', $brigade)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByAgentEmail(string $email): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.controle', 'c')
            ->join('c.agent', 'a')
            ->where('a.email = :email')
            ->setParameter('email', $email)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
