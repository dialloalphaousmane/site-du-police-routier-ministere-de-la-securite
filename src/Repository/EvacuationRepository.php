<?php

namespace App\Repository;

use App\Entity\Evacuation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evacuation>
 *
 * @method Evacuation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Evacuation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Evacuation[]    findAll()
 * @method Evacuation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvacuationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evacuation::class);
    }

    public function countByBrigade(int $brigadeId): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->join('e.brigade', 'b')
            ->where('b.id = :brigadeId')
            ->setParameter('brigadeId', $brigadeId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.dateEvacuation BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('e.dateEvacuation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveEvacuations(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->setParameter('status', 'EN_COURS')
            ->orderBy('e.dateEvacuation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findHighPriorityEvacuations(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.urgence = :urgence')
            ->andWhere('e.status = :status')
            ->setParameter('urgence', 'HAUTE')
            ->setParameter('status', 'EN_COURS')
            ->orderBy('e.dateEvacuation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByAccident(int $accidentId): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.accident', 'a')
            ->where('a.id = :accidentId')
            ->setParameter('accidentId', $accidentId)
            ->orderBy('e.dateEvacuation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getStatisticsByYear(int $year): array
    {
        $startDate = new \DateTimeImmutable("$year-01-01");
        $endDate = new \DateTimeImmutable("$year-12-31");

        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id) as total_evacuations')
            ->addSelect('SUM(e.nbVictimesEvacuees) as total_victimes_evacuees')
            ->addSelect('AVG(e.dureeMinutes) as avg_duree')
            ->addSelect('AVG(e.distanceKm) as avg_distance')
            ->where('e.dateEvacuation BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleResult();
    }

    public function getMonthlyStatistics(int $year): array
    {
        $monthlyStats = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = new \DateTimeImmutable("$year-$month-01");
            $endDate = $startDate->modify('last day of 23:59:59');

            $result = $this->createQueryBuilder('e')
                ->select('COUNT(e.id) as evacuations')
                ->addSelect('SUM(e.nbVictimesEvacuees) as victimes')
                ->where('e.dateEvacuation BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->getQuery()
                ->getSingleResult();

            $monthlyStats[$month] = [
                'evacuations' => (int) $result['evacuations'],
                'victimes' => (int) $result['victimes'],
            ];
        }

        return $monthlyStats;
    }

    public function findByHopital(string $hopital): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.hopitalDestination LIKE :hopital')
            ->setParameter('hopital', '%' . $hopital . '%')
            ->orderBy('e.dateEvacuation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTypeEvacuation(string $type): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.typeEvacuation = :type')
            ->setParameter('type', $type)
            ->orderBy('e.dateEvacuation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function searchEvacuations(string $query): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.reference LIKE :query')
            ->orWhere('e.hopitalDestination LIKE :query')
            ->orWhere('e.observations LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('e.dateEvacuation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findEvacuationsByUser(User $user): array
    {
        $qb = $this->createQueryBuilder('e');

        if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_DIRECTION_GENERALE', $user->getRoles())) {
            return $this->findAll();
        }

        if (in_array('ROLE_DIRECTION_REGIONALE', $user->getRoles()) && $user->getRegion()) {
            $qb->join('e.brigade', 'b')
               ->join('b.region', 'r')
               ->where('r.id = :regionId')
               ->setParameter('regionId', $user->getRegion()->getId());
        }

        if (in_array('ROLE_CHEF_BRIGADE', $user->getRoles()) && $user->getBrigade()) {
            $qb->where('e.brigade = :brigade')
               ->setParameter('brigade', $user->getBrigade());
        }

        if (in_array('ROLE_AGENT', $user->getRoles()) && $user->getBrigade()) {
            $qb->where('e.brigade = :brigade')
               ->setParameter('brigade', $user->getBrigade());
        }

        return $qb->orderBy('e.dateEvacuation', 'DESC')
                   ->getQuery()
                   ->getResult();
    }
}
