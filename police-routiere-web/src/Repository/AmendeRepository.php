<?php

namespace App\Repository;

use App\Entity\Amende;
use App\Entity\Region;
use App\Entity\Brigade;
use App\Util\PoliceConstants;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Amende>
 */
class AmendeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Amende::class);
    }

    public function findByRegion(Region $region): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.infraction', 'i')
            ->join('i.controle', 'c')
            ->join('c.brigade', 'b')
            ->where('b.region = :region')
            ->setParameter('region', $region)
            ->orderBy('a.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByBrigade(Brigade $brigade): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.infraction', 'i')
            ->join('i.controle', 'c')
            ->where('c.brigade = :brigade')
            ->setParameter('brigade', $brigade)
            ->orderBy('a.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByAgentEmail(string $email): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.infraction', 'i')
            ->join('i.controle', 'c')
            ->join('c.agent', 'ag')
            ->where('ag.email = :email')
            ->setParameter('email', $email)
            ->orderBy('a.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalMontant(): string
    {
        return $this->createQueryBuilder('a')
            ->select('SUM(a.montant)')
            ->getQuery()
            ->getSingleScalarResult() ?? '0';
    }

    public function getTotalMontantByRegion(Region $region): string
    {
        return $this->createQueryBuilder('a')
            ->join('a.infraction', 'i')
            ->join('i.controle', 'c')
            ->join('c.brigade', 'b')
            ->select('SUM(a.montant)')
            ->where('b.region = :region')
            ->setParameter('region', $region)
            ->getQuery()
            ->getSingleScalarResult() ?? '0';
    }

    public function getTotalMontantByBrigade(Brigade $brigade): string
    {
        return $this->createQueryBuilder('a')
            ->join('a.infraction', 'i')
            ->join('i.controle', 'c')
            ->select('SUM(a.montant)')
            ->where('c.brigade = :brigade')
            ->setParameter('brigade', $brigade)
            ->getQuery()
            ->getSingleScalarResult() ?? '0';
    }

    public function getTotalMontantThisMonth(): string
    {
        return $this->createQueryBuilder('a')
            ->select('SUM(a.montant)')
            ->where('a.datePaiement >= :firstDay')
            ->setParameter('firstDay', new \DateTime('first day of this month'))
            ->getQuery()
            ->getSingleScalarResult() ?? '0';
    }

    public function getTotalMontantByRegionThisMonth(Region $region): string
    {
        return $this->createQueryBuilder('a')
            ->join('a.infraction', 'i')
            ->join('i.controle', 'c')
            ->join('c.brigade', 'b')
            ->select('SUM(a.montant)')
            ->where('b.region = :region')
            ->andWhere('a.datePaiement >= :firstDay')
            ->setParameter('region', $region)
            ->setParameter('firstDay', new \DateTime('first day of this month'))
            ->getQuery()
            ->getSingleScalarResult() ?? '0';
    }

    public function getTotalMontantByBrigadeThisMonth(Brigade $brigade): string
    {
        return $this->createQueryBuilder('a')
            ->join('a.infraction', 'i')
            ->join('i.controle', 'c')
            ->select('SUM(a.montant)')
            ->where('c.brigade = :brigade')
            ->andWhere('a.datePaiement >= :firstDay')
            ->setParameter('brigade', $brigade)
            ->setParameter('firstDay', new \DateTime('first day of this month'))
            ->getQuery()
            ->getSingleScalarResult() ?? '0';
    }

    public function countByRegion(Region $region): int
    {
        return $this->createQueryBuilder('a')
            ->join('a.infraction', 'i')
            ->join('i.controle', 'c')
            ->join('c.brigade', 'b')
            ->select('COUNT(a.id)')
            ->where('b.region = :region')
            ->setParameter('region', $region)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    public function countByBrigade(Brigade $brigade): int
    {
        return $this->createQueryBuilder('a')
            ->join('a.infraction', 'i')
            ->join('i.controle', 'c')
            ->select('COUNT(a.id)')
            ->where('c.brigade = :brigade')
            ->setParameter('brigade', $brigade)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    public function countPendingByRegion(int|Region $region): int
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.infraction', 'i')
            ->join('i.controle', 'c')
            ->join('c.brigade', 'b')
            ->join('b.region', 'r')
            ->select('COUNT(a.id)')
            ->andWhere('a.statut = :statut')
            ->setParameter('statut', PoliceConstants::AMENDE_STATUS_PENDING);

        if (is_int($region)) {
            $qb->andWhere('r.id = :regionId')
                ->setParameter('regionId', $region);
        } else {
            $qb->andWhere('b.region = :region')
                ->setParameter('region', $region);
        }

        return (int) ($qb->getQuery()->getSingleScalarResult() ?? 0);
    }

    public function countPaidByRegion(int|Region $region): int
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.infraction', 'i')
            ->join('i.controle', 'c')
            ->join('c.brigade', 'b')
            ->join('b.region', 'r')
            ->select('COUNT(a.id)')
            ->andWhere('a.statut = :statut')
            ->setParameter('statut', PoliceConstants::AMENDE_STATUS_PAID);

        if (is_int($region)) {
            $qb->andWhere('r.id = :regionId')
                ->setParameter('regionId', $region);
        } else {
            $qb->andWhere('b.region = :region')
                ->setParameter('region', $region);
        }

        return (int) ($qb->getQuery()->getSingleScalarResult() ?? 0);
    }
}
