<?php

namespace App\Repository;

use App\Entity\Controle;
use App\Entity\Region;
use App\Entity\Brigade;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Controle>
 */
class ControleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Controle::class);
    }

    public function findByRegion(Region $region): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.brigade', 'b')
            ->where('b.region = :region')
            ->setParameter('region', $region)
            ->orderBy('c.dateControle', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByBrigade(Brigade $brigade): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.brigade = :brigade')
            ->setParameter('brigade', $brigade)
            ->orderBy('c.dateControle', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByAgentEmail(string $email): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.agent', 'a')
            ->where('a.email = :email')
            ->setParameter('email', $email)
            ->orderBy('c.dateControle', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
