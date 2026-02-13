<?php

namespace App\Controller\DirectionGenerale;

use App\Repository\UserRepository;
use App\Repository\ControleRepository;
use App\Repository\InfractionRepository;
use App\Repository\AmendeRepository;
use App\Repository\RegionRepository;
use App\Service\StatisticsService;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/direction-generale')]
#[IsGranted('ROLE_DIRECTION_GENERALE')]
class DirectionGeneraleController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private ControleRepository $controleRepository,
        private InfractionRepository $infractionRepository,
        private AmendeRepository $amendeRepository,
        private RegionRepository $regionRepository,
        private StatisticsService $statisticsService
    ) {}

    #[Route('/dashboard', name: 'app_direction_generale_dashboard')]
    public function dashboard(): Response
    {
        $stats = [
            'total_users' => $this->userRepository->count([]),
            'total_agents' => $this->userRepository->count(['roles' => 'ROLE_AGENT']),
            'total_controls' => $this->controleRepository->count([]),
            'total_infractions' => $this->infractionRepository->count([]),
            'total_amendes' => $this->amendeRepository->count([]),
            'total_regions' => $this->regionRepository->count([]),
            'pending_amendes' => $this->amendeRepository->count(['statutPaiement' => 'EN_ATTENTE']),
        ];

        $recentControls = $this->controleRepository->findBy([], ['dateControle' => 'DESC'], 10);
        $recentInfractions = $this->infractionRepository->findBy([], ['createdAt' => 'DESC'], 10);

        return $this->render('direction_generale/dashboard.html.twig', [
            'stats' => $stats,
            'recent_controls' => $recentControls,
            'recent_infractions' => $recentInfractions,
        ]);
    }

    #[Route('/reports', name: 'app_direction_generale_reports')]
    public function reports(Request $request): Response
    {
        $period = $request->query->get('period', 'month');
        $region = $request->query->get('region');

        $reportData = $this->statisticsService->getReportData($period, $region);

        $regions = $this->regionRepository->findAll();

        return $this->render('direction_generale/reports.html.twig', [
            'report_data' => $reportData,
            'regions' => $regions,
            'selected_period' => $period,
            'selected_region' => $region,
        ]);
    }

    #[Route('/statistics', name: 'app_direction_generale_statistics')]
    public function statistics(): Response
    {
        $stats = $this->statisticsService->getComprehensiveStatistics();
        $regionalStats = $this->statisticsService->getRegionalStatistics();

        return $this->render('direction_generale/statistics.html.twig', [
            'stats' => $stats,
            'regional_stats' => $regionalStats,
        ]);
    }

    #[Route('/controls', name: 'app_direction_generale_controls')]
    public function controls(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $region = $request->query->get('region');
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $query = $this->controleRepository->createQueryBuilder('c')
            ->leftJoin('c.brigade', 'b')
            ->leftJoin('b.region', 'r')
            ->orderBy('c.dateControle', 'DESC');

        if ($region) {
            $query->andWhere('r.id = :region')
                ->setParameter('region', $region);
        }

        $controls = $query->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $total = $this->controleRepository->count([]);
        $totalPages = ceil($total / $limit);

        $regions = $this->regionRepository->findAll();

        return $this->render('direction_generale/controls.html.twig', [
            'controls' => $controls,
            'regions' => $regions,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    #[Route('/infractions', name: 'app_direction_generale_infractions')]
    public function infractions(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $region = $request->query->get('region');
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $query = $this->infractionRepository->createQueryBuilder('i')
            ->leftJoin('i.controle', 'c')
            ->leftJoin('c.brigade', 'b')
            ->leftJoin('b.region', 'r')
            ->orderBy('i.createdAt', 'DESC');

        if ($region) {
            $query->andWhere('r.id = :region')
                ->setParameter('region', $region);
        }

        $infractions = $query->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $total = $this->infractionRepository->count([]);
        $totalPages = ceil($total / $limit);

        $regions = $this->regionRepository->findAll();

        return $this->render('direction_generale/infractions.html.twig', [
            'infractions' => $infractions,
            'regions' => $regions,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    #[Route('/amendes', name: 'app_direction_generale_amendes')]
    public function amendes(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $region = $request->query->get('region');
        $statut = $request->query->get('statut');
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $query = $this->amendeRepository->createQueryBuilder('a')
            ->leftJoin('a.infraction', 'i')
            ->leftJoin('i.controle', 'c')
            ->leftJoin('c.brigade', 'b')
            ->leftJoin('b.region', 'r')
            ->orderBy('a.dateEmission', 'DESC');

        if ($region) {
            $query->andWhere('r.id = :region')
                ->setParameter('region', $region);
        }

        if ($statut) {
            $query->andWhere('a.statutPaiement = :statut')
                ->setParameter('statut', $statut);
        }

        $amendes = $query->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $total = $this->amendeRepository->count([]);
        $totalPages = ceil($total / $limit);

        $regions = $this->regionRepository->findAll();

        return $this->render('direction_generale/amendes.html.twig', [
            'amendes' => $amendes,
            'regions' => $regions,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    #[Route('/controls/{id}/validate', name: 'app_direction_generale_control_validate', methods: ['POST'])]
    public function validateControl(Request $request, \App\Entity\Controle $controle, EntityManagerInterface $entityManager, AuditService $auditService): Response
    {
        // CSRF protection
        if (!$this->isCsrfTokenValid('validate_controle' . $controle->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_direction_generale_controls');
        }

        // Marquer comme validé
        $controle->setStatut('VALIDE');
        $controle->setValidatedBy($this->getUser());
        $controle->setDateValidation(new \DateTimeImmutable());

        $entityManager->flush();

        // Journaliser l'action
        try {
            $auditService->logUpdate('Controle', (string)$controle->getId(), [], 'Validation par Direction Générale');
        } catch (\Throwable $e) {
            // Ne pas empêcher la suite si l'audit échoue
        }

        $this->addFlash('success', 'Contrôle validé avec succès.');
        return $this->redirectToRoute('app_direction_generale_controls');
    }
}
