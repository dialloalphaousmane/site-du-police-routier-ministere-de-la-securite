<?php

namespace App\Command;

use App\Service\AccidentStatisticsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:accident:statistics',
    description: 'Génère les statistiques des accidents sur 10 ans'
)]
class AccidentStatisticsCommand extends Command
{
    public function __construct(
        private AccidentStatisticsService $statisticsService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('years', InputArgument::OPTIONAL, 'Nombre d\'années à analyser', 10)
            ->addOption('region', 'r', InputOption::VALUE_OPTIONAL, 'Filtrer par région')
            ->addOption('brigade', 'b', InputOption::VALUE_OPTIONAL, 'Filtrer par brigade')
            ->addOption('export', 'e', InputOption::VALUE_OPTIONAL, 'Exporter vers fichier (json/csv)')
            ->setHelp('Cette commande génère des statistiques détaillées sur les accidents');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $years = (int) $input->getArgument('years');
        $region = $input->getOption('region');
        $brigade = $input->getOption('brigade');
        $export = $input->getOption('export');

        $io->title('📊 Statistiques des Accidents - ' . $years . ' dernières années');

        try {
            // Statistiques générales
            $stats = $this->statisticsService->getGeneralStatistics($years, $region, $brigade);
            
            $io->section('📈 Statistiques Générales');
            $io->table(
                ['Indicateur', 'Valeur'],
                [
                    ['Total accidents', $stats['total_accidents']],
                    ['Total victimes', $stats['total_victimes']],
                    ['Total morts', $stats['total_morts']],
                    ['Total blessés graves', $stats['total_blesses_graves']],
                    ['Total blessés légers', $stats['total_blesses_legers']],
                    ['Taux de mortalité', $stats['taux_mortalite'] . '%'],
                    ['Accidents mortels', $stats['accidents_mortels']],
                    ['Évacuations', $stats['total_evacuations']],
                ]
            );

            // Statistiques par année
            $io->section('📅 Évolution par Année');
            $yearStats = $this->statisticsService->getYearlyStatistics($years, $region, $brigade);
            
            $yearTable = [];
            foreach ($yearStats as $year => $data) {
                $yearTable[] = [
                    $year,
                    $data['accidents'],
                    $data['victimes'],
                    $data['morts'],
                    $data['blesses_graves'],
                    $data['blesses_legers'],
                    $data['taux_mortalite'] . '%'
                ];
            }
            
            $io->table(
                ['Année', 'Accidents', 'Victimes', 'Morts', 'Blessés graves', 'Blessés légers', 'Taux mortalité'],
                $yearTable
            );

            // Causes principales
            $io->section('🔍 Causes Principales');
            $causes = $this->statisticsService->getTopCauses($years, $region, $brigade);
            
            $causeTable = [];
            foreach ($causes as $cause => $count) {
                $causeTable[] = [$cause, $count, round(($count / $stats['total_accidents']) * 100, 2) . '%'];
            }
            
            $io->table(['Cause', 'Nombre', 'Pourcentage'], $causeTable);

            // Solutions proposées
            $io->section('💡 Solutions Recommandées');
            $solutions = $this->statisticsService->getRecommendedSolutions($years, $region, $brigade);
            
            foreach ($solutions as $solution) {
                $io->writeln('• ' . $solution);
            }

            // Export si demandé
            if ($export) {
                $this->exportStatistics($stats, $yearStats, $causes, $solutions, $export, $io);
            }

            $io->success('✅ Statistiques générées avec succès !');
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors de la génération des statistiques: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function exportStatistics(array $stats, array $yearStats, array $causes, array $solutions, string $format, SymfonyStyle $io): void
    {
        $data = [
            'generated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'general_statistics' => $stats,
            'yearly_statistics' => $yearStats,
            'top_causes' => $causes,
            'recommended_solutions' => $solutions
        ];

        $filename = 'accident_statistics_' . date('Y-m-d_H-i-s');

        if ($format === 'json') {
            file_put_contents($filename . '.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $io->success('📁 Export JSON créé: ' . $filename . '.json');
        } elseif ($format === 'csv') {
            $this->exportToCsv($data, $filename);
            $io->success('📁 Export CSV créé: ' . $filename . '.csv');
        }
    }

    private function exportToCsv(array $data, string $filename): void
    {
        $file = fopen($filename . '.csv', 'w');
        
        // En-tête CSV
        fputcsv($file, ['Type', 'Année', 'Catégorie', 'Valeur']);
        
        // Statistiques générales
        foreach ($data['general_statistics'] as $key => $value) {
            fputcsv($file, ['Général', 'N/A', $key, $value]);
        }
        
        // Statistiques annuelles
        foreach ($data['yearly_statistics'] as $year => $stats) {
            foreach ($stats as $key => $value) {
                fputcsv($file, ['Annuel', $year, $key, $value]);
            }
        }
        
        // Causes
        foreach ($data['top_causes'] as $cause => $count) {
            fputcsv($file, ['Causes', 'N/A', $cause, $count]);
        }
        
        fclose($file);
    }
}
