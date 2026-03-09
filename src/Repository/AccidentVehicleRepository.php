<?php

namespace App\Repository;

use App\Entity\AccidentVehicle;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccidentVehicle>
 *
 * @method AccidentVehicle|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccidentVehicle|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccidentVehicle[]    findAll()
 * @method AccidentVehicle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccidentVehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccidentVehicle::class);
    }

    public function findByAccident(int $accidentId): array
    {
        return $this->createQueryBuilder('av')
            ->join('av.accident', 'a')
            ->where('a.id = :accidentId')
            ->setParameter('accidentId', $accidentId)
            ->orderBy('av.marque', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByMarque(string $marque): array
    {
        return $this->createQueryBuilder('av')
            ->where('av.marque LIKE :marque')
            ->setParameter('marque', '%' . $marque . '%')
            ->orderBy('av.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTypeVehicule(string $type): array
    {
        return $this->createQueryBuilder('av')
            ->where('av.typeVehicule = :type')
            ->setParameter('type', $type)
            ->orderBy('av.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByImmatriculation(string $immatriculation): array
    {
        return $this->createQueryBuilder('av')
            ->where('av.immatriculation LIKE :immatriculation')
            ->setParameter('immatriculation', '%' . $immatriculation . '%')
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

    public function countByTypeVehicule(string $type): int
    {
        return $this->createQueryBuilder('av')
            ->select('COUNT(av.id)')
            ->where('av.typeVehicule = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getStatisticsByYear(int $year): array
    {
        $startDate = new \DateTimeImmutable("$year-01-01");
        $endDate = new \DateTimeImmutable("$year-12-31");

        return $this->createQueryBuilder('av')
            ->select('COUNT(av.id) as total_vehicles')
            ->addSelect('COUNT(CASE WHEN av.typeVehicule = :voiture THEN 1 END) as cars')
            ->addSelect('COUNT(CASE WHEN av.typeVehicule = :moto THEN 1 END) as motorcycles')
            ->addSelect('COUNT(CASE WHEN av.typeVehicule = :camion THEN 1 END) as trucks')
            ->addSelect('COUNT(CASE WHEN av.typeVehicule = :bus THEN 1 END) as buses')
            ->addSelect('COUNT(CASE WHEN av.niveauDommage = :detruit THEN 1 END) as destroyed')
            ->addSelect('COUNT(CASE WHEN av.niveauDommage = :grave THEN 1 END) as heavily_damaged')
            ->where('av.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('voiture', 'VOITURE')
            ->setParameter('moto', 'MOTO')
            ->setParameter('camion', 'CAMION')
            ->setParameter('bus', 'BUS')
            ->setParameter('detruit', 'DETRUIT')
            ->setParameter('grave', 'GRAVE')
            ->getQuery()
            ->getSingleResult();
    }

    public function searchVehicles(string $query): array
    {
        return $this->createQueryBuilder('av')
            ->where('av.immatriculation LIKE :query')
            ->orWhere('av.marque LIKE :query')
            ->orWhere('av.modele LIKE :query')
            ->orWhere('av.proprietaireNom LIKE :query')
            ->orWhere('av.proprietairePrenom LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('av.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findVehiclesWithoutInsurance(): array
    {
        return $this->createQueryBuilder('av')
            ->where('av.assuranceValidite IS NULL OR av.assuranceValidite < :today')
            ->setParameter('today', new \DateTimeImmutable())
            ->orderBy('av.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findVehiclesByDamageLevel(string $damageLevel): array
    {
        return $this->createQueryBuilder('av')
            ->where('av.niveauDommage = :damageLevel')
            ->setParameter('damageLevel', $damageLevel)
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
                ->select('COUNT(av.id) as vehicles')
                ->addSelect('COUNT(CASE WHEN av.niveauDommage = :detruit THEN 1 END) as destroyed')
                ->where('av.createdAt BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->setParameter('detruit', 'DETRUIT')
                ->getQuery()
                ->getSingleResult();

            $monthlyStats[$month] = [
                'vehicles' => (int) $result['vehicles'],
                'destroyed' => (int) $result['destroyed'],
            ];
        }

        return $monthlyStats;
    }

    public function findVehiclesByUser(User $user): array
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

    public function getTopVehicleBrands(int $limit = 10): array
    {
        return $this->createQueryBuilder('av')
            ->select('av.marque, COUNT(av.id) as count')
            ->where('av.marque IS NOT NULL')
            ->groupBy('av.marque')
            ->orderBy('count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getVehicleAgeStatistics(): array
    {
        $currentYear = (int) date('Y');
        
        $stats = [
            '0-5_ans' => 0,
            '6-10_ans' => 0,
            '11-15_ans' => 0,
            '16+_ans' => 0,
            'inconnu' => 0
        ];

        $vehicles = $this->findAll();
        
        foreach ($vehicles as $vehicle) {
            $age = $vehicle->getAgeVehicule();
            
            if ($age === null) {
                $stats['inconnu']++;
            } elseif ($age <= 5) {
                $stats['0-5_ans']++;
            } elseif ($age <= 10) {
                $stats['6-10_ans']++;
            } elseif ($age <= 15) {
                $stats['11-15_ans']++;
            } else {
                $stats['16+_ans']++;
            }
        }

        return $stats;
    }
}
