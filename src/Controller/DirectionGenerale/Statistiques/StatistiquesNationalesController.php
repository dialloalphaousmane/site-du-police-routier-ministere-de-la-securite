<?php

namespace App\Controller\DirectionGenerale\Statistiques;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\DirectionGenerale\StatisticsService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Region;
use App\Entity\Brigade;
use App\Entity\Agent;
use App\Entity\Controle;
use App\Entity\Infraction;
use App\Entity\Amende;

#[Route('/dashboard/direction-generale/statistiques')]
#[IsGranted('ROLE_DIRECTION_GENERALE')]
class StatistiquesNationalesController extends AbstractController
{
    public function __construct(
        private readonly StatisticsService $statisticsService,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('/', name: 'app_direction_generale_statistiques')]
    public function index(): Response
    {
        $nationalStats = $this->statisticsService->getNationalStats();
        $regionsStats = $this->statisticsService->getRegionsStats();
        $monthlyEvolution = $this->statisticsService->getMonthlyEvolution();

        return $this->render('direction_generale/statistiques_nationales.html.twig', [
            'user' => $this->getUser(),
            'nationalStats' => $nationalStats,
            'regionsStats' => $regionsStats,
            'monthlyEvolution' => $monthlyEvolution
        ]);
    }

    #[Route('/details/{region}', name: 'app_direction_generale_statistiques_region')]
    public function regionDetails(string $region): Response
    {
        $regionEntity = $this->entityManager->getRepository(Region::class)->findOneBy(['code' => $region]);
        if (!$regionEntity) {
            $regionEntity = $this->entityManager->getRepository(Region::class)->findOneBy(['libelle' => $region]);
        }

        if (!$regionEntity) {
            throw $this->createNotFoundException('Région introuvable');
        }

        $today = new \DateTimeImmutable('today');
        $monthStart = new \DateTimeImmutable('first day of this month 00:00:00');

        $brigadesCount = $this->entityManager->getRepository(Brigade::class)->count(['region' => $regionEntity]);

        $agentsCount = (int) $this->entityManager->getRepository(Agent::class)->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.region = :region')
            ->setParameter('region', $regionEntity)
            ->getQuery()
            ->getSingleScalarResult();

        $activeAgents = (int) $this->entityManager->getRepository(Agent::class)->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.region = :region')
            ->andWhere('a.isActif = true')
            ->setParameter('region', $regionEntity)
            ->getQuery()
            ->getSingleScalarResult();

        $controlsToday = (int) $this->entityManager->getRepository(Controle::class)->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->join('c.brigade', 'b')
            ->where('b.region = :region')
            ->andWhere('c.dateControle >= :today')
            ->setParameter('region', $regionEntity)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();

        $controlsMonth = (int) $this->entityManager->getRepository(Controle::class)->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->join('c.brigade', 'b')
            ->where('b.region = :region')
            ->andWhere('c.dateControle >= :monthStart')
            ->setParameter('region', $regionEntity)
            ->setParameter('monthStart', $monthStart)
            ->getQuery()
            ->getSingleScalarResult();

        $infractionsToday = (int) $this->entityManager->getRepository(Infraction::class)->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->join('i.controle', 'c')
            ->join('c.brigade', 'b')
            ->where('b.region = :region')
            ->andWhere('c.dateControle >= :today')
            ->setParameter('region', $regionEntity)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();

        $infractionsMonth = (int) $this->entityManager->getRepository(Infraction::class)->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->join('i.controle', 'c')
            ->join('c.brigade', 'b')
            ->where('b.region = :region')
            ->andWhere('c.dateControle >= :monthStart')
            ->setParameter('region', $regionEntity)
            ->setParameter('monthStart', $monthStart)
            ->getQuery()
            ->getSingleScalarResult();

        $revenueToday = (int) $this->entityManager->getRepository(Amende::class)->createQueryBuilder('a')
            ->select('COALESCE(SUM(a.montantPaye), 0)')
            ->join('a.infraction', 'i')
            ->join('i.controle', 'c')
            ->join('c.brigade', 'b')
            ->where('b.region = :region')
            ->andWhere('a.datePaiement >= :today')
            ->andWhere('a.statut = :statut')
            ->setParameter('region', $regionEntity)
            ->setParameter('today', $today)
            ->setParameter('statut', 'PAYEE')
            ->getQuery()
            ->getSingleScalarResult();

        $revenueMonth = (int) $this->entityManager->getRepository(Amende::class)->createQueryBuilder('a')
            ->select('COALESCE(SUM(a.montantPaye), 0)')
            ->join('a.infraction', 'i')
            ->join('i.controle', 'c')
            ->join('c.brigade', 'b')
            ->where('b.region = :region')
            ->andWhere('a.datePaiement >= :monthStart')
            ->andWhere('a.statut = :statut')
            ->setParameter('region', $regionEntity)
            ->setParameter('monthStart', $monthStart)
            ->setParameter('statut', 'PAYEE')
            ->getQuery()
            ->getSingleScalarResult();

        $performanceRate = $controlsMonth > 0 ? (($controlsMonth - $infractionsMonth) / $controlsMonth) * 100 : 0;

        $regionDetails = [
            'id' => $regionEntity->getId(),
            'code' => $regionEntity->getCode(),
            'name' => $regionEntity->getLibelle(),
            'director' => $regionEntity->getDirecteur(),
            'email' => $regionEntity->getEmail(),
            'telephone' => $regionEntity->getTelephone(),
            'agents' => $agentsCount,
            'active_agents' => $activeAgents,
            'brigades' => $brigadesCount,
            'controls_today' => $controlsToday,
            'controls_month' => $controlsMonth,
            'infractions_today' => $infractionsToday,
            'infractions_month' => $infractionsMonth,
            'revenue_today' => $revenueToday,
            'revenue_month' => $revenueMonth,
            'performance_rate' => round($performanceRate, 1)
        ];

        return $this->render('direction_generale/statistiques_region_details.html.twig', [
            'user' => $this->getUser(),
            'region' => $regionDetails
        ]);
    }

    #[Route('/export/pdf', name: 'app_direction_generale_statistiques_export_pdf')]
    public function exportPdf(): Response
    {
        $nationalStats = $this->statisticsService->getNationalStats();
        $regionsStats = $this->statisticsService->getRegionsStats();

        $content = "Export Statistiques Nationales (PDF placeholder)\n";
        $content .= 'Date: '.date('Y-m-d H:i:s')."\n\n";
        $content .= 'Total contrôles: '.($nationalStats['total_controls'] ?? 0)."\n";
        $content .= 'Total infractions: '.($nationalStats['total_infractions'] ?? 0)."\n";
        $content .= 'Total revenus: '.($nationalStats['total_revenue'] ?? 0)."\n\n";
        $content .= "Région;Contrôles;Infractions;Revenus\n";
        foreach ($regionsStats as $row) {
            $content .= sprintf(
                "%s;%s;%s;%s\n",
                $row['name'] ?? '',
                $row['controls'] ?? 0,
                $row['infractions'] ?? 0,
                $row['revenue'] ?? 0,
            );
        }

        $response = new Response($content);
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'statistiques_nationales.pdf.txt');
        $response->headers->set('Content-Type', 'text/plain; charset=utf-8');
        $response->headers->set('Content-Disposition', $disposition);
        return $response;
    }

    #[Route('/export/excel', name: 'app_direction_generale_statistiques_export_excel')]
    public function exportExcel(): Response
    {
        $nationalStats = $this->statisticsService->getNationalStats();
        $regionsStats = $this->statisticsService->getRegionsStats();

        $csv = "Metric;Value\n";
        foreach ($nationalStats as $k => $v) {
            $csv .= sprintf("%s;%s\n", $k, (string) $v);
        }
        $csv .= "\nRégion;Contrôles;Infractions;Revenus;Agents;Performance\n";
        foreach ($regionsStats as $row) {
            $csv .= sprintf(
                "%s;%s;%s;%s;%s;%s\n",
                $row['name'] ?? '',
                $row['controls'] ?? 0,
                $row['infractions'] ?? 0,
                $row['revenue'] ?? 0,
                $row['agents'] ?? 0,
                isset($row['performance']) ? round((float) $row['performance'], 1) : 0,
            );
        }

        $response = new Response("\xEF\xBB\xBF".$csv);
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'statistiques_nationales.csv');
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', $disposition);
        return $response;
    }
}
