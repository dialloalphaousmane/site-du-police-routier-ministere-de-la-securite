<?php

namespace App\Repository;

use App\Entity\AccidentVictim;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccidentVictim>
 *
 * @method AccidentVictim|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccidentVictim|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccidentVictim[]    findAll()
 * @method AccidentVictim[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccidentVictimRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccidentVictim::class);
    }

    public function findByAccident(int $accidentId): array
    {
        return $this->createQueryBuilder('av')
            ->join('av.accident', 'a')
            ->where('a.id = :accidentId')
            ->setParameter('accidentId', $accidentId)
            ->orderBy('av.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByGravite(string $gravite): array
    {
        return $this->createQueryBuilder('av')
            ->where('av.gravite = :gravite')
            ->setParameter('gravite', $gravite)
            ->orderBy('av.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFatalVictims(): array
    {
        return $this->createQueryBuilder('av')
            ->where('av.gravite = :mortel OR av.decede = true')
            ->setParameter('mortel', 'MORTEL')
            ->orderBy('av.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findEvacuatedVictims(): array
    {
        return $this->createQueryBuilder('av')
            ->where('av.evacue = true')
            ->orderBy('av.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByAccident(int $accidentId): int
    {
        return $this->createQueryBuilder('av')
            ->select('COUNT(av.id)')
            ->join('av.accident', 'a')
            ->where('a.id = :accidentId')
            ->setParameter('accidentId', $accidentId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByGravite(string $gravite): int
    {
        return $this->createQueryBuilder('av')
            ->select('COUNT(av.id)')
            ->where('av.gravite = :gravite')
            ->setParameter('gravite', $gravite)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getStatisticsByYear(int $year): array
    {
        $startDate = new \DateTimeImmutable("$year-01-01");
        $endDate = new \DateTimeImmutable("$year-12-31");

        return $this->createQueryBuilder('av')
            ->select('COUNT(av.id) as total_victims')
            ->addSelect('COUNT(CASE WHEN av.gravite = :mortel THEN 1 END) as fatal_victims')
            ->addSelect('COUNT(CASE WHEN av.gravite = :grave THEN 1 END) as seriously_injured')
            ->addSelect('COUNT(CASE WHEN av.gravite = :leger THEN 1 END) as slightly_injured')
            ->addSelect('COUNT(CASE WHEN av.evacue = true THEN 1 END) as evacuated_victims')
            ->where('av.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('mortel', 'MORTEL')
            ->setParameter('grave', 'BLESSE_GRAVE')
            ->setParameter('leger', 'BLESSE_LEGER')
            ->getQuery()
            ->getSingleResult();
    }

    public function searchVictims(string $query): array
    {
        return $this->createQueryBuilder('av')
            ->where('av.nom LIKE :query')
            ->orWhere('av.prenom LIKE :query')
            ->orWhere('av.telephone LIKE :query')
            ->orWhere('av.nationalite LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('av.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTypeVictime(string $type): array
    {
        return $this->createQueryBuilder('av')
            ->where('av.typeVictime = :type')
            ->setParameter('type', $type)
            ->orderBy('av.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findVictimsWithoutEvacuation(): array
    {
        return $this->createQueryBuilder('av')
            ->where('av.evacue = false OR av.evacue IS NULL')
            ->andWhere('av.gravite IN (:gravites)')
            ->setParameter('gravites', ['MORTEL', 'BLESSE_GRAVE'])
            ->orderBy('av.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getMonthlyStatistics(int $year): array
    {
        $monthlyStats = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = new \DateTimeImmutable("$year-$month-01");
            $endDate = $startDate->modify('last day of 23:59:59');

            $result = $this->createQueryBuilder('av')
                ->select('COUNT(av.id) as victims')
                ->addSelect('COUNT(CASE WHEN av.gravite = :mortel THEN 1 END) as morts')
                ->addSelect('COUNT(CASE WHEN av.evacue = true THEN 1 END) as evacues')
                ->where('av.createdAt BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->setParameter('mortel', 'MORTEL')
                ->getQuery()
                ->getSingleResult();

            $monthlyStats[$month] = [
                'victims' => (int) $result['victims'],
                'morts' => (int) $result['morts'],
                'evacues' => (int) $result['evacues'],
            ];
        }

        return $monthlyStats;
    }

    public function findVictimsByUser(User $user): array
    {
        $qb = $this->createQueryBuilder('av')
            ->join('av.accident', 'a');

        if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_DIRECTION_GENERALE', $user->getRoles())) {
            return $this->findAll();
        }

        if (in_array('ROLE_DIRECTION_REGIONALE', $user->getRoles()) && $user->getRegion()) {
            $qb->join('a.region', 'r')
               ->where('r.id = :regionId')
               ->setParameter('regionId', $user->getRegion()->getId());
        }

        if (in_array('ROLE_CHEF_BRIGADE', $user->getRoles()) && $user->getBrigade()) {
            $qb->join('a.brigade', 'b')
               ->where('b.id = :brigadeId')
               ->setParameter('brigadeId', $user->getBrigade()->getId());
        }

        if (in_array('ROLE_AGENT', $user->getRoles()) && $user->getBrigade()) {
            $qb->join('a.brigade', 'b')
               ->where('b.id = :brigadeId')
               ->setParameter('brigadeId', $user->getBrigade()->getId());
        }

        return $qb->orderBy('av.createdAt', 'DESC')
                   ->getQuery()
                   ->getResult();
    }
}
