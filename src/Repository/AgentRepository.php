<?php

namespace App\Repository;

use App\Entity\Agent;
use App\Entity\Region;
use App\Entity\Brigade;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Agent>
 */
class AgentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Agent::class);
    }

    public function findByRegion(Region $region): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.region = :region')
            ->setParameter('region', $region)
            ->orderBy('a.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByBrigade(Brigade $brigade): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.brigade = :brigade')
            ->setParameter('brigade', $brigade)
            ->orderBy('a.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByEmail(string $email): ?Agent
    {
        return $this->createQueryBuilder('a')
            ->where('a.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
