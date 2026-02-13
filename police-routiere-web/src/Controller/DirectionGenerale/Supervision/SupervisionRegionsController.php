<?php

namespace App\Controller\DirectionGenerale\Supervision;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Region;
use App\Entity\Agent;
use App\Entity\Brigade;
use App\Entity\Controle;
use App\Entity\Infraction;
use App\Entity\Amende;

#[Route('/dashboard/direction-generale/supervision')]
#[IsGranted('ROLE_DIRECTION_GENERALE')]
class SupervisionRegionsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('/', name: 'app_direction_generale_supervision')]
    public function index(Request $request): Response
    {
        $status = (string) $request->query->get('status', 'all');
        $q = trim((string) $request->query->get('q', ''));

        $qbRegions = $this->entityManager->getRepository(Region::class)->createQueryBuilder('r');
        if ($status === 'active') {
            $qbRegions->andWhere('r.actif = true');
        } elseif ($status === 'inactive') {
            $qbRegions->andWhere('r.actif = false');
        }

        if ($q !== '') {
            $qbRegions
                ->andWhere('LOWER(r.libelle) LIKE :q OR LOWER(r.code) LIKE :q OR LOWER(r.directeur) LIKE :q')
                ->setParameter('q', '%'.strtolower($q).'%');
        }

        $qbRegions
            ->orderBy('r.libelle', 'ASC');

        $regionsEntities = $qbRegions->getQuery()->getResult();

        $today = new \DateTimeImmutable('today');
        $monthStart = new \DateTimeImmutable('first day of this month 00:00:00');

        $regions = [];
        foreach ($regionsEntities as $region) {
            $regions[] = $this->buildRegionRow($region, $today, $monthStart);
        }

        return $this->render('direction_generale/supervision_regions.html.twig', [
            'user' => $this->getUser(),
            'regions' => $regions,
            'filters' => [
                'status' => $status,
                'q' => $q,
            ]
        ]);
    }

    #[Route('/export/csv', name: 'app_direction_generale_supervision_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request): Response
    {
        $status = (string) $request->query->get('status', 'all');
        $q = trim((string) $request->query->get('q', ''));

        $qbRegions = $this->entityManager->getRepository(Region::class)->createQueryBuilder('r');
        if ($status === 'active') {
            $qbRegions->andWhere('r.actif = true');
        } elseif ($status === 'inactive') {
            $qbRegions->andWhere('r.actif = false');
        }
        if ($q !== '') {
            $qbRegions
                ->andWhere('LOWER(r.libelle) LIKE :q OR LOWER(r.code) LIKE :q OR LOWER(r.directeur) LIKE :q')
                ->setParameter('q', '%'.strtolower($q).'%');
        }
        $qbRegions->orderBy('r.libelle', 'ASC');

        $regionsEntities = $qbRegions->getQuery()->getResult();

        $today = new \DateTimeImmutable('today');
        $monthStart = new \DateTimeImmutable('first day of this month 00:00:00');

        $csv = "Code;Nom;Directeur;Email;Téléphone;Statut;Brigades;Agents Actifs;Agents Total;Contrôles Jour;Contrôles Mois;Recettes Mois;Performance;Dernière Activité\n";
        foreach ($regionsEntities as $region) {
            $row = $this->buildRegionRow($region, $today, $monthStart);

            $csv .= sprintf(
                "%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s\n",
                (string) ($row['code'] ?? ''),
                (string) ($row['name'] ?? ''),
                (string) ($row['director'] ?? ''),
                (string) ($row['email'] ?? ''),
                (string) ($row['telephone'] ?? ''),
                (string) ($row['status'] ?? ''),
                (string) ($row['brigades_count'] ?? 0),
                (string) ($row['active_agents'] ?? 0),
                (string) ($row['agents_count'] ?? 0),
                (string) ($row['controls_today'] ?? 0),
                (string) ($row['controls_month'] ?? 0),
                (string) ($row['revenue_month'] ?? 0),
                (string) ($row['performance_rate'] ?? 0),
                $row['last_activity'] instanceof \DateTimeImmutable ? $row['last_activity']->format('Y-m-d') : ''
            );
        }

        $response = new Response("\xEF\xBB\xBF".$csv);
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'supervision_regions.csv');
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', $disposition);
        return $response;
    }

    #[Route('/region/{id}/edit', name: 'app_direction_generale_supervision_region_edit', methods: ['GET'])]
    public function editRegion(int $id): JsonResponse
    {
        $region = $this->entityManager->getRepository(Region::class)->find($id);
        if (!$region) {
            return new JsonResponse(['success' => false, 'message' => 'Région introuvable'], 404);
        }

        return new JsonResponse([
            'success' => true,
            'id' => $region->getId(),
            'name' => $region->getLibelle(),
            'code' => $region->getCode(),
            'director' => $region->getDirecteur(),
            'status' => $region->isActif() ? 'active' : 'inactive',
            'actif' => $region->isActif(),
        ]);
    }

    #[Route('/region/save', name: 'app_direction_generale_supervision_region_save', methods: ['POST'])]
    public function saveRegion(Request $request): JsonResponse
    {
        $csrf = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('dg_supervision_region', $csrf)) {
            return new JsonResponse(['success' => false, 'message' => 'CSRF token invalide'], 403);
        }

        $id = $request->request->get('id');
        $name = trim((string) $request->request->get('name', ''));
        $code = trim((string) $request->request->get('code', ''));
        $director = trim((string) $request->request->get('director', ''));

        $status = (string) $request->request->get('status', 'active');
        $actif = $status === 'active';

        if ($name === '' || $code === '') {
            return new JsonResponse(['success' => false, 'message' => 'Nom et code sont obligatoires'], 400);
        }

        if ($id) {
            $region = $this->entityManager->getRepository(Region::class)->find((int) $id);
            if (!$region) {
                return new JsonResponse(['success' => false, 'message' => 'Région introuvable'], 404);
            }
        } else {
            $region = new Region();
        }

        $existingByCode = $this->entityManager->getRepository(Region::class)->findOneBy(['code' => $code]);
        if ($existingByCode && $existingByCode->getId() !== $region->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'Ce code région existe déjà'], 400);
        }

        $region
            ->setLibelle($name)
            ->setCode($code)
            ->setDirecteur($director !== '' ? $director : null)
            ->setActif($actif);

        $this->entityManager->persist($region);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'id' => $region->getId()]);
    }

    #[Route('/region/{id}/toggle', name: 'app_direction_generale_supervision_region_toggle', methods: ['POST'])]
    public function toggleRegion(int $id, Request $request): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true);
        $csrf = is_array($payload) ? (string) ($payload['_token'] ?? '') : '';
        if (!$this->isCsrfTokenValid('dg_supervision_region', $csrf)) {
            return new JsonResponse(['success' => false, 'message' => 'CSRF token invalide'], 403);
        }

        $region = $this->entityManager->getRepository(Region::class)->find($id);
        if (!$region) {
            return new JsonResponse(['success' => false, 'message' => 'Région introuvable'], 404);
        }

        $active = is_array($payload) ? (string) ($payload['active'] ?? '') : '';
        $region->setActif($active === 'active');
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/region/{id}/delete', name: 'app_direction_generale_supervision_region_delete', methods: ['POST'])]
    public function deleteRegion(int $id, Request $request): JsonResponse
    {
        $csrf = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('dg_supervision_region', $csrf)) {
            return new JsonResponse(['success' => false, 'message' => 'CSRF token invalide'], 403);
        }

        $region = $this->entityManager->getRepository(Region::class)->find($id);
        if (!$region) {
            return new JsonResponse(['success' => false, 'message' => 'Région introuvable'], 404);
        }

        try {
            $this->entityManager->remove($region);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => 'Impossible de supprimer la région (dépendances existantes)'], 400);
        }

        return new JsonResponse(['success' => true]);
    }

    private function buildRegionRow(Region $region, \DateTimeImmutable $today, \DateTimeImmutable $monthStart): array
    {
        $brigadesCount = $this->entityManager->getRepository(Brigade::class)->count(['region' => $region]);

        $agentsCount = (int) $this->entityManager->getRepository(Agent::class)->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.region = :region')
            ->setParameter('region', $region)
            ->getQuery()
            ->getSingleScalarResult();

        $activeAgents = (int) $this->entityManager->getRepository(Agent::class)->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.region = :region')
            ->andWhere('a.isActif = true')
            ->setParameter('region', $region)
            ->getQuery()
            ->getSingleScalarResult();

        $controlsToday = (int) $this->entityManager->getRepository(Controle::class)->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->join('c.brigade', 'b')
            ->where('b.region = :region')
            ->andWhere('c.dateControle >= :today')
            ->setParameter('region', $region)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();

        $controlsMonth = (int) $this->entityManager->getRepository(Controle::class)->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->join('c.brigade', 'b')
            ->where('b.region = :region')
            ->andWhere('c.dateControle >= :monthStart')
            ->setParameter('region', $region)
            ->setParameter('monthStart', $monthStart)
            ->getQuery()
            ->getSingleScalarResult();

        $infractionsMonth = (int) $this->entityManager->getRepository(Infraction::class)->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->join('i.controle', 'c')
            ->join('c.brigade', 'b')
            ->where('b.region = :region')
            ->andWhere('c.dateControle >= :monthStart')
            ->setParameter('region', $region)
            ->setParameter('monthStart', $monthStart)
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
            ->setParameter('region', $region)
            ->setParameter('monthStart', $monthStart)
            ->setParameter('statut', 'PAYEE')
            ->getQuery()
            ->getSingleScalarResult();

        $lastActivity = $this->entityManager->getRepository(Controle::class)->createQueryBuilder('c')
            ->select('MAX(c.dateControle)')
            ->join('c.brigade', 'b')
            ->where('b.region = :region')
            ->setParameter('region', $region)
            ->getQuery()
            ->getSingleScalarResult();

        $performanceRate = $controlsMonth > 0 ? (($controlsMonth - $infractionsMonth) / $controlsMonth) * 100 : 0;

        return [
            'id' => $region->getId(),
            'code' => $region->getCode(),
            'name' => $region->getLibelle(),
            'director' => $region->getDirecteur() ?? '-',
            'telephone' => $region->getTelephone(),
            'email' => $region->getEmail(),
            'agents_count' => $agentsCount,
            'active_agents' => $activeAgents,
            'brigades_count' => $brigadesCount,
            'controls_today' => $controlsToday,
            'controls_month' => $controlsMonth,
            'revenue_month' => $revenueMonth,
            'performance_rate' => round($performanceRate, 1),
            'status' => $region->isActif() ? 'active' : 'inactive',
            'last_activity' => $lastActivity ? new \DateTimeImmutable((string) $lastActivity) : null,
        ];
    }

    #[Route('/region/{id}', name: 'app_direction_generale_supervision_region_details')]
    public function regionDetails(int $id): Response
    {
        $region = $this->entityManager->getRepository(Region::class)->find($id);
        if (!$region) {
            throw $this->createNotFoundException('Région introuvable');
        }

        $today = new \DateTimeImmutable('today');
        $monthStart = new \DateTimeImmutable('first day of this month 00:00:00');

        $brigadesCount = $this->entityManager->getRepository(Brigade::class)->count(['region' => $region]);

        $agentsCount = (int) $this->entityManager->getRepository(Agent::class)->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.region = :region')
            ->setParameter('region', $region)
            ->getQuery()
            ->getSingleScalarResult();

        $activeAgents = (int) $this->entityManager->getRepository(Agent::class)->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.region = :region')
            ->andWhere('a.isActif = true')
            ->setParameter('region', $region)
            ->getQuery()
            ->getSingleScalarResult();

        $controlsToday = (int) $this->entityManager->getRepository(Controle::class)->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->join('c.brigade', 'b')
            ->where('b.region = :region')
            ->andWhere('c.dateControle >= :today')
            ->setParameter('region', $region)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();

        $controlsMonth = (int) $this->entityManager->getRepository(Controle::class)->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->join('c.brigade', 'b')
            ->where('b.region = :region')
            ->andWhere('c.dateControle >= :monthStart')
            ->setParameter('region', $region)
            ->setParameter('monthStart', $monthStart)
            ->getQuery()
            ->getSingleScalarResult();

        $infractionsToday = (int) $this->entityManager->getRepository(Infraction::class)->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->join('i.controle', 'c')
            ->join('c.brigade', 'b')
            ->where('b.region = :region')
            ->andWhere('c.dateControle >= :today')
            ->setParameter('region', $region)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();

        $infractionsMonth = (int) $this->entityManager->getRepository(Infraction::class)->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->join('i.controle', 'c')
            ->join('c.brigade', 'b')
            ->where('b.region = :region')
            ->andWhere('c.dateControle >= :monthStart')
            ->setParameter('region', $region)
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
            ->setParameter('region', $region)
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
            ->setParameter('region', $region)
            ->setParameter('monthStart', $monthStart)
            ->setParameter('statut', 'PAYEE')
            ->getQuery()
            ->getSingleScalarResult();

        $performanceRate = $controlsMonth > 0 ? (($controlsMonth - $infractionsMonth) / $controlsMonth) * 100 : 0;

        $regionDetails = [
            'id' => $region->getId(),
            'name' => $region->getLibelle(),
            'code' => $region->getCode(),
            'director' => $region->getDirecteur() ?? '-',
            'agents_count' => $agentsCount,
            'active_agents' => $activeAgents,
            'brigades_count' => $brigadesCount,
            'controls_today' => $controlsToday,
            'controls_month' => $controlsMonth,
            'infractions_today' => $infractionsToday,
            'infractions_month' => $infractionsMonth,
            'revenue_today' => $revenueToday,
            'revenue_month' => $revenueMonth,
            'performance_rate' => round($performanceRate, 1),
            'status' => $region->isActif() ? 'Actif' : 'Inactif',
            'contact' => $region->getTelephone(),
            'email' => $region->getEmail(),
        ];

        $brigadeEntities = $this->entityManager->getRepository(Brigade::class)->findBy(['region' => $region]);
        $brigades = [];
        foreach ($brigadeEntities as $brigade) {
            $brigadeAgents = (int) $this->entityManager->getRepository(Agent::class)->count(['brigade' => $brigade]);

            $brigadeControlsToday = (int) $this->entityManager->getRepository(Controle::class)->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.brigade = :brigade')
                ->andWhere('c.dateControle >= :today')
                ->setParameter('brigade', $brigade)
                ->setParameter('today', $today)
                ->getQuery()
                ->getSingleScalarResult();

            $brigadeControlsMonth = (int) $this->entityManager->getRepository(Controle::class)->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.brigade = :brigade')
                ->andWhere('c.dateControle >= :monthStart')
                ->setParameter('brigade', $brigade)
                ->setParameter('monthStart', $monthStart)
                ->getQuery()
                ->getSingleScalarResult();

            $brigadeInfractionsMonth = (int) $this->entityManager->getRepository(Infraction::class)->createQueryBuilder('i')
                ->select('COUNT(i.id)')
                ->join('i.controle', 'c')
                ->where('c.brigade = :brigade')
                ->andWhere('c.dateControle >= :monthStart')
                ->setParameter('brigade', $brigade)
                ->setParameter('monthStart', $monthStart)
                ->getQuery()
                ->getSingleScalarResult();

            $brigadePerformance = $brigadeControlsMonth > 0 ? (($brigadeControlsMonth - $brigadeInfractionsMonth) / $brigadeControlsMonth) * 100 : 0;

            $brigades[] = [
                'id' => $brigade->getId(),
                'name' => $brigade->getLibelle(),
                'chief' => $brigade->getChef(),
                'agents_count' => $brigadeAgents,
                'controls_today' => $brigadeControlsToday,
                'controls_month' => $brigadeControlsMonth,
                'performance_rate' => round($brigadePerformance, 1),
            ];
        }

        return $this->render('direction_generale/supervision_region_details.html.twig', [
            'user' => $this->getUser(),
            'region' => $regionDetails,
            'brigades' => $brigades
        ]);
    }

    #[Route('/brigade/{id}', name: 'app_direction_generale_supervision_brigade_details')]
    public function brigadeDetails(int $id): Response
    {
        $brigade = $this->entityManager->getRepository(Brigade::class)->find($id);
        if (!$brigade) {
            throw $this->createNotFoundException('Brigade introuvable');
        }

        $today = new \DateTimeImmutable('today');
        $monthStart = new \DateTimeImmutable('first day of this month 00:00:00');

        $agentsCount = (int) $this->entityManager->getRepository(Agent::class)->count(['brigade' => $brigade]);

        $activeAgents = (int) $this->entityManager->getRepository(Agent::class)->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.brigade = :brigade')
            ->andWhere('a.isActif = true')
            ->setParameter('brigade', $brigade)
            ->getQuery()
            ->getSingleScalarResult();

        $controlsToday = (int) $this->entityManager->getRepository(Controle::class)->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.brigade = :brigade')
            ->andWhere('c.dateControle >= :today')
            ->setParameter('brigade', $brigade)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();

        $controlsMonth = (int) $this->entityManager->getRepository(Controle::class)->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.brigade = :brigade')
            ->andWhere('c.dateControle >= :monthStart')
            ->setParameter('brigade', $brigade)
            ->setParameter('monthStart', $monthStart)
            ->getQuery()
            ->getSingleScalarResult();

        $infractionsToday = (int) $this->entityManager->getRepository(Infraction::class)->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->join('i.controle', 'c')
            ->where('c.brigade = :brigade')
            ->andWhere('c.dateControle >= :today')
            ->setParameter('brigade', $brigade)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();

        $infractionsMonth = (int) $this->entityManager->getRepository(Infraction::class)->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->join('i.controle', 'c')
            ->where('c.brigade = :brigade')
            ->andWhere('c.dateControle >= :monthStart')
            ->setParameter('brigade', $brigade)
            ->setParameter('monthStart', $monthStart)
            ->getQuery()
            ->getSingleScalarResult();

        $revenueToday = (int) $this->entityManager->getRepository(Amende::class)->createQueryBuilder('a')
            ->select('COALESCE(SUM(a.montantPaye), 0)')
            ->join('a.infraction', 'i')
            ->join('i.controle', 'c')
            ->where('c.brigade = :brigade')
            ->andWhere('a.datePaiement >= :today')
            ->andWhere('a.statut = :statut')
            ->setParameter('brigade', $brigade)
            ->setParameter('today', $today)
            ->setParameter('statut', 'PAYEE')
            ->getQuery()
            ->getSingleScalarResult();

        $revenueMonth = (int) $this->entityManager->getRepository(Amende::class)->createQueryBuilder('a')
            ->select('COALESCE(SUM(a.montantPaye), 0)')
            ->join('a.infraction', 'i')
            ->join('i.controle', 'c')
            ->where('c.brigade = :brigade')
            ->andWhere('a.datePaiement >= :monthStart')
            ->andWhere('a.statut = :statut')
            ->setParameter('brigade', $brigade)
            ->setParameter('monthStart', $monthStart)
            ->setParameter('statut', 'PAYEE')
            ->getQuery()
            ->getSingleScalarResult();

        $performanceRate = $controlsMonth > 0 ? (($controlsMonth - $infractionsMonth) / $controlsMonth) * 100 : 0;

        $brigadeDetails = [
            'id' => $brigade->getId(),
            'name' => $brigade->getLibelle(),
            'chief' => $brigade->getChef(),
            'region' => $brigade->getRegion() ? $brigade->getRegion()->getLibelle() : null,
            'agents_count' => $agentsCount,
            'active_agents' => $activeAgents,
            'controls_today' => $controlsToday,
            'controls_month' => $controlsMonth,
            'infractions_today' => $infractionsToday,
            'infractions_month' => $infractionsMonth,
            'revenue_today' => $revenueToday,
            'revenue_month' => $revenueMonth,
            'performance_rate' => round($performanceRate, 1),
            'status' => $brigade->isActif() ? 'Actif' : 'Inactif',
            'coverage_area' => $brigade->getZoneCouverture(),
            'contact' => $brigade->getTelephone(),
            'email' => $brigade->getEmail(),
        ];

        $agentEntities = $this->entityManager->getRepository(Agent::class)->findBy(['brigade' => $brigade]);
        $agents = [];
        foreach ($agentEntities as $agent) {
            $agentControlsToday = (int) $this->entityManager->getRepository(Controle::class)->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.agent = :agent')
                ->andWhere('c.dateControle >= :today')
                ->setParameter('agent', $agent)
                ->setParameter('today', $today)
                ->getQuery()
                ->getSingleScalarResult();

            $agentControlsMonth = (int) $this->entityManager->getRepository(Controle::class)->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.agent = :agent')
                ->andWhere('c.dateControle >= :monthStart')
                ->setParameter('agent', $agent)
                ->setParameter('monthStart', $monthStart)
                ->getQuery()
                ->getSingleScalarResult();

            $agentInfractionsMonth = (int) $this->entityManager->getRepository(Infraction::class)->createQueryBuilder('i')
                ->select('COUNT(i.id)')
                ->join('i.controle', 'c')
                ->where('c.agent = :agent')
                ->andWhere('c.dateControle >= :monthStart')
                ->setParameter('agent', $agent)
                ->setParameter('monthStart', $monthStart)
                ->getQuery()
                ->getSingleScalarResult();

            $agentPerformance = $agentControlsMonth > 0 ? (($agentControlsMonth - $agentInfractionsMonth) / $agentControlsMonth) * 100 : 0;

            $agents[] = [
                'name' => trim(($agent->getPrenom() ?? '').' '.($agent->getNom() ?? '')),
                'grade' => $agent->getGrade(),
                'status' => $agent->isActif() ? 'Actif' : 'Inactif',
                'controls_today' => $agentControlsToday,
                'performance_rate' => round($agentPerformance, 1)
            ];
        }

        return $this->render('direction_generale/supervision_brigade_details.html.twig', [
            'user' => $this->getUser(),
            'brigade' => $brigadeDetails,
            'agents' => $agents
        ]);
    }
}
