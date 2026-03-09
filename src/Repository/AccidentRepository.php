<?php

namespace App\Repository;

use App\Entity\Accident;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Accident>
 *
 * @method Accident|null find($id, $lockMode = null, $lockVersion = null)
 * @method Accident|null findOneBy(array $criteria, array $orderBy = null)
 * @method Accident[]    findAll()
 * @method Accident[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccidentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Accident::class);
    }

    public function countByRegion(int $regionId): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->join('a.region', 'r')
            ->where('r.id = :regionId')
            ->setParameter('regionId', $regionId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByBrigade(int $brigadeId): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->join('a.brigade', 'b')
            ->where('b.id = :brigadeId')
            ->setParameter('brigadeId', $brigadeId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.dateAccident BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('a.dateAccident', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByGravite(string $gravite): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.gravite = :gravite')
            ->setParameter('gravite', $gravite)
            ->orderBy('a.dateAccident', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCause(string $cause): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.causePrincipale = :cause')
            ->setParameter('cause', $cause)
            ->orderBy('a.dateAccident', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentAccidents(int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.dateAccident', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findUrgentAccidents(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.gravite IN (:gravites)')
            ->setParameter('gravites', ['MORTEL', 'GRAVE', 'URGENT'])
            ->andWhere('a.status = :status')
            ->setParameter('status', 'EN_COURS')
            ->orderBy('a.dateAccident', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAccidentsWithoutEvacuation(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.evacuations', 'e')
            ->where('e.id IS NULL')
            ->andWhere('a.gravite IN (:gravites)')
            ->setParameter('gravites', ['MORTEL', 'GRAVE'])
            ->orderBy('a.dateAccident', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getStatisticsByYear(int $year): array
    {
        $startDate = new \DateTimeImmutable("$year-01-01");
        $endDate = new \DateTimeImmutable("$year-12-31");

        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id) as total_accidents')
            ->addSelect('SUM(a.nbVictimes) as total_victimes')
            ->addSelect('SUM(a.nbMorts) as total_morts')
            ->addSelect('SUM(a.nbBlessesGraves) as total_blesses_graves')
            ->addSelect('SUM(a.nbBlessesLegers) as total_blesses_legers')
            ->addSelect('COUNT(CASE WHEN a.gravite = :mortel THEN 1 END) as accidents_mortels')
            ->where('a.dateAccident BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('mortel', 'MORTEL')
            ->getQuery()
            ->getSingleResult();
    }

    public function getMonthlyStatistics(int $year): array
    {
        $monthlyStats = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = new \DateTimeImmutable("$year-$month-01");
            $endDate = $startDate->modify('last day of 23:59:59');

            $result = $this->createQueryBuilder('a')
                ->select('COUNT(a.id) as accidents')
                ->addSelect('SUM(a.nbVictimes) as victimes')
                ->addSelect('SUM(a.nbMorts) as morts')
                ->where('a.dateAccident BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->getQuery()
                ->getSingleResult();

            $monthlyStats[$month] = [
                'accidents' => (int) $result['accidents'],
                'victimes' => (int) $result['victimes'],
                'morts' => (int) $result['morts'],
            ];
        }

        return $monthlyStats;
    }

    public function getTopBlackspots(int $limit = 10): array
    {
        $startDate = new \DateTimeImmutable("-2 years");

        return $this->createQueryBuilder('a')
            ->select('a.localisation, COUNT(a.id) as accident_count, SUM(a.nbVictimes) as total_victimes')
            ->where('a.dateAccident >= :startDate')
            ->groupBy('a.localisation')
            ->having('accident_count > 1')
            ->orderBy('accident_count', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('startDate', $startDate)
            ->getQuery()
            ->getResult();
    }

    public function searchAccidents(string $query, ?string $region = null, ?string $brigade = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.localisation LIKE :query')
            ->orWhere('a.description LIKE :query')
            ->orWhere('a.reference LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.dateAccident', 'DESC');

        if ($region) {
            $qb->join('a.region', 'r')
               ->andWhere('r.code = :regionCode')
               ->setParameter('regionCode', $region);
        }

        if ($brigade) {
            $qb->join('a.brigade', 'b')
               ->andWhere('b.code = :brigadeCode')
               ->setParameter('brigadeCode', $brigade);
        }

        return $qb->getQuery()->getResult();
    }

    public function findAccidentsByUser(User $user): array
    {
        $qb = $this->createQueryBuilder('a');

        if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_DIRECTION_GENERALE', $user->getRoles())) {
            return $this->findAll();
        }

        if (in_array('ROLE_DIRECTION_REGIONALE', $user->getRoles()) && $user->getRegion()) {
            $qb->where('a.region = :region')
               ->setParameter('region', $user->getRegion());
        }

        if (in_array('ROLE_CHEF_BRIGADE', $user->getRoles()) && $user->getBrigade()) {
            $qb->where('a.brigade = :brigade')
               ->setParameter('brigade', $user->getBrigade());
        }

        if (in_array('ROLE_AGENT', $user->getRoles()) && $user->getBrigade()) {
            $qb->where('a.brigade = :brigade')
               ->setParameter('brigade', $user->getBrigade());
        }

        return $qb->orderBy('a.dateAccident', 'DESC')
                   ->getQuery()
                   ->getResult();
    }
}
