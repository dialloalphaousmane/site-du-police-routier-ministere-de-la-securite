<?php

namespace App\Service;

use App\Repository\AccidentRepository;
use App\Repository\ControleRepository;
use App\Repository\InfractionRepository;
use App\Repository\AmendeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SystemResetService
{
    public function __construct(
        private AccidentRepository $accidentRepository,
        private ControleRepository $controleRepository,
        private InfractionRepository $infractionRepository,
        private AmendeRepository $amendeRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $parameterBag
    ) {}

    public function createBackup(): string
    {
        $backupDir = $this->parameterBag->get('kernel.project_dir') . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . '/backup_' . $timestamp . '.sql';

        // Créer un backup SQL
        $command = sprintf(
            'mysqldump -u %s -p%s %s > %s',
            $_ENV['DATABASE_USER'] ?? 'root',
            $_ENV['DATABASE_PASSWORD'] ?? '',
            $_ENV['DATABASE_NAME'] ?? 'police_routiere_bd',
            $backupFile
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException('Impossible de créer la sauvegarde');
        }

        return $backupFile;
    }

    public function simulateReset(string $period, string $value, ?string $role, ?string $entity): array
    {
        $cutoffDate = $this->calculateCutoffDate($period, $value);
        $affectedData = [];

        if ($entity === null || $entity === 'accident') {
            $qb = $this->accidentRepository->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.createdAt < :cutoffDate')
                ->setParameter('cutoffDate', $cutoffDate);

            if ($role) {
                $this->applyRoleFilter($qb, $role, 'a');
            }

            $affectedData['accidents'] = (int) $qb->getQuery()->getSingleScalarResult();
        }

        if ($entity === null || $entity === 'controle') {
            $qb = $this->controleRepository->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.createdAt < :cutoffDate')
                ->setParameter('cutoffDate', $cutoffDate);

            if ($role) {
                $this->applyRoleFilter($qb, $role, 'c');
            }

            $affectedData['controles'] = (int) $qb->getQuery()->getSingleScalarResult();
        }

        if ($entity === null || $entity === 'infraction') {
            $qb = $this->infractionRepository->createQueryBuilder('i')
                ->select('COUNT(i.id)')
                ->where('i.createdAt < :cutoffDate')
                ->setParameter('cutoffDate', $cutoffDate);

            if ($role) {
                $this->applyRoleFilter($qb, $role, 'i');
            }

            $affectedData['infractions'] = (int) $qb->getQuery()->getSingleScalarResult();
        }

        if ($entity === null || $entity === 'amende') {
            $qb = $this->amendeRepository->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.createdAt < :cutoffDate')
                ->setParameter('cutoffDate', $cutoffDate);

            if ($role) {
                $this->applyRoleFilter($qb, $role, 'a');
            }

            $affectedData['amendes'] = (int) $qb->getQuery()->getSingleScalarResult();
        }

        return $affectedData;
    }

    public function executeReset(string $period, string $value, ?string $role, ?string $entity): array
    {
        $cutoffDate = $this->calculateCutoffDate($period, $value);
        $result = ['deleted' => [], 'errors' => []];

        $this->entityManager->beginTransaction();

        try {
            // Supprimer dans l'ordre pour respecter les contraintes de clé étrangère
            
            // 1. Supprimer les amendes
            if ($entity === null || $entity === 'amende') {
                $qb = $this->amendeRepository->createQueryBuilder('a')
                    ->delete()
                    ->where('a.createdAt < :cutoffDate')
                    ->setParameter('cutoffDate', $cutoffDate);

                if ($role) {
                    $this->applyRoleFilter($qb, $role, 'a');
                }

                $result['deleted']['amendes'] = $qb->getQuery()->execute();
            }

            // 2. Supprimer les infractions
            if ($entity === null || $entity === 'infraction') {
                $qb = $this->infractionRepository->createQueryBuilder('i')
                    ->delete()
                    ->where('i.createdAt < :cutoffDate')
                    ->setParameter('cutoffDate', $cutoffDate);

                if ($role) {
                    $this->applyRoleFilter($qb, $role, 'i');
                }

                $result['deleted']['infractions'] = $qb->getQuery()->execute();
            }

            // 3. Supprimer les contrôles
            if ($entity === null || $entity === 'controle') {
                $qb = $this->controleRepository->createQueryBuilder('c')
                    ->delete()
                    ->where('c.createdAt < :cutoffDate')
                    ->setParameter('cutoffDate', $cutoffDate);

                if ($role) {
                    $this->applyRoleFilter($qb, $role, 'c');
                }

                $result['deleted']['controles'] = $qb->getQuery()->execute();
            }

            // 4. Supprimer les accidents
            if ($entity === null || $entity === 'accident') {
                $qb = $this->accidentRepository->createQueryBuilder('a')
                    ->delete()
                    ->where('a.createdAt < :cutoffDate')
                    ->setParameter('cutoffDate', $cutoffDate);

                if ($role) {
                    $this->applyRoleFilter($qb, $role, 'a');
                }

                $result['deleted']['accidents'] = $qb->getQuery()->execute();
            }

            $this->entityManager->commit();

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    private function calculateCutoffDate(string $period, string $value): \DateTimeImmutable
    {
        $now = new \DateTimeImmutable();
        
        return match($period) {
            'hour' => $now->modify("-{$value} hours"),
            'month' => $now->modify("-{$value} months"),
            'year' => $now->modify("-{$value} years"),
            default => $now
        };
    }

    private function applyRoleFilter($qb, string $role, string $alias): void
    {
        $roleMap = [
            'ADMIN' => 'ROLE_ADMIN',
            'DG' => 'ROLE_DIRECTION_GENERALE',
            'DR' => 'ROLE_DIRECTION_REGIONALE',
            'CHEF_BRIGADE' => 'ROLE_CHEF_BRIGADE',
            'AGENT' => 'ROLE_AGENT'
        ];

        if (isset($roleMap[$role])) {
            $qb->join($alias . '.createdBy', 'u')
               ->join('u.role', 'r')
               ->where('r.code = :roleCode')
               ->setParameter('roleCode', $roleMap[$role]);
        }
    }

    public function getSystemStatistics(): array
    {
        return [
            'total_accidents' => $this->accidentRepository->count([]),
            'total_controles' => $this->controleRepository->count([]),
            'total_infractions' => $this->infractionRepository->count([]),
            'total_amendes' => $this->amendeRepository->count([]),
            'total_users' => $this->userRepository->count([]),
            'database_size' => $this->getDatabaseSize(),
            'last_reset' => $this->getLastResetDate(),
        ];
    }

    private function getDatabaseSize(): string
    {
        try {
            $connection = $this->entityManager->getConnection();
            $stmt = $connection->prepare("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema = :database");
            $result = $stmt->executeQuery(['database' => $_ENV['DATABASE_NAME'] ?? 'police_routiere_bd']);
            $size = $result->fetchOne();
            
            return $size ? $size . ' MB' : 'N/A';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    private function getLastResetDate(): ?string
    {
        // Implémenter la logique pour récupérer la date de dernière réinitialisation
        // Pourrait être stockée dans une table de logs ou un fichier
        return null;
    }
}
