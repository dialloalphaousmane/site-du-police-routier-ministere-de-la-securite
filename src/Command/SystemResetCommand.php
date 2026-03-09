<?php

namespace App\Command;

use App\Service\SystemResetService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:system:reset',
    description: 'Réinitialise le système selon une période (heure, mois, année)'
)]
class SystemResetCommand extends Command
{
    public function __construct(
        private SystemResetService $systemResetService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('period', InputArgument::REQUIRED, 'Période de réinitialisation (hour/month/year)')
            ->addArgument('value', InputArgument::REQUIRED, 'Valeur de la période (ex: 24, 12, 2023)')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Simuler la réinitialisation sans exécuter')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forcer la réinitialisation sans confirmation')
            ->addOption('backup', 'b', InputOption::VALUE_NONE, 'Créer une sauvegarde avant réinitialisation')
            ->addOption('role', 'r', InputOption::VALUE_OPTIONAL, 'Filtrer par rôle (ADMIN, DG, DR, CHEF_BRIGADE, AGENT)')
            ->addOption('entity', 'e', InputOption::VALUE_OPTIONAL, 'Filtrer par entité (controle, infraction, amende, accident)')
            ->setHelp('Cette commande réinitialise le système selon la période spécifiée');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $period = strtolower($input->getArgument('period'));
        $value = $input->getArgument('value');
        $dryRun = $input->getOption('dry-run');
        $force = $input->getOption('force');
        $backup = $input->getOption('backup');
        $role = $input->getOption('role');
        $entity = $input->getOption('entity');

        // Validation des arguments
        if (!in_array($period, ['hour', 'month', 'year'])) {
            $io->error('❌ Période invalide. Utilisez: hour, month, ou year');
            return Command::FAILURE;
        }

        $io->title('🔄 Réinitialisation du Système');
        $io->warning('⚠️  Cette opération est irréversible !');

        // Afficher les détails de la réinitialisation
        $this->displayResetDetails($io, $period, $value, $role, $entity);

        // Confirmation si pas en mode force
        if (!$force && !$dryRun) {
            if (!$io->confirm('❓ Êtes-vous sûr de vouloir continuer ?', false)) {
                $io->info('❌ Opération annulée');
                return Command::SUCCESS;
            }
        }

        try {
            // Créer backup si demandé
            if ($backup && !$dryRun) {
                $io->section('💾 Création de la sauvegarde');
                $backupPath = $this->systemResetService->createBackup();
                $io->success('✅ Sauvegarde créée: ' . $backupPath);
            }

            // Simulation ou exécution
            if ($dryRun) {
                $io->section('🔍 Simulation de la réinitialisation');
                $affectedData = $this->systemResetService->simulateReset($period, $value, $role, $entity);
                $this->displayAffectedData($io, $affectedData);
                $io->info('ℹ️  Mode simulation - aucune donnée n\'a été modifiée');
            } else {
                $io->section('⚡ Exécution de la réinitialisation');
                $result = $this->systemResetService->executeReset($period, $value, $role, $entity);
                $this->displayResult($io, $result);
                $io->success('✅ Réinitialisation terminée avec succès !');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors de la réinitialisation: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function displayResetDetails(SymfonyStyle $io, string $period, string $value, ?string $role, ?string $entity): void
    {
        $periodLabels = [
            'hour' => 'heure',
            'month' => 'mois',
            'year' => 'année'
        ];

        $io->writeln([
            '📋 Détails de la réinitialisation:',
            '',
            "🕐 Période: $periodLabels[$period]",
            "📊 Valeur: $value",
            "👤 Rôle: " . ($role ?? 'Tous les rôles'),
            "🗂️  Entité: " . ($entity ?? 'Toutes les entités'),
            ''
        ]);

        // Calculer la date limite
        $cutoffDate = $this->calculateCutoffDate($period, $value);
        $io->writeln("📅 Date limite: " . $cutoffDate->format('d/m/Y H:i:s'));
        $io->writeln("📅 Toutes les données antérieures à cette date seront supprimées");
        $io->newLine();
    }

    private function displayAffectedData(SymfonyStyle $io, array $affectedData): void
    {
        $io->writeln('📊 Données qui seront affectées:');
        
        foreach ($affectedData as $entity => $count) {
            $io->writeln("• $entity: $count enregistrements");
        }
        
        $total = array_sum($affectedData);
        $io->newLine();
        $io->writeln("📈 Total: $total enregistrements seront supprimés");
    }

    private function displayResult(SymfonyStyle $io, array $result): void
    {
        $io->writeln('📊 Résultats de la réinitialisation:');
        
        foreach ($result['deleted'] as $entity => $count) {
            $io->writeln("• $entity: $count enregistrements supprimés");
        }
        
        $totalDeleted = array_sum($result['deleted']);
        $io->newLine();
        $io->writeln("📈 Total: $totalDeleted enregistrements supprimés");
        
        if (!empty($result['errors'])) {
            $io->newLine();
            $io->warning('⚠️ Erreurs rencontrées:');
            foreach ($result['errors'] as $error) {
                $io->writeln("• $error");
            }
        }
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
}
