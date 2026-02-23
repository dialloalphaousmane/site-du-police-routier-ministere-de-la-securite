<?php

namespace App\Controller;

use App\Entity\Controle;
use App\Entity\Infraction;
use App\Entity\Amende;
use App\Entity\Brigade;
use App\Entity\User;
use App\Repository\AgentRepository;
use App\Repository\ControleRepository;
use App\Repository\InfractionRepository;
use App\Repository\AmendeRepository;
use App\Repository\BrigadeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/direction-regionale')]
#[IsGranted('ROLE_DIRECTION_REGIONALE')]
class DirectionRegionaleController extends AbstractController
{
    public function __construct(
        private ControleRepository $controleRepository,
        private InfractionRepository $infractionRepository,
        private AmendeRepository $amendeRepository,
        private BrigadeRepository $brigadeRepository,
        private AgentRepository $agentRepository
    ) {}

    #[Route('/dashboard', name: 'app_direction_regionale_dashboard')]
    #[Route('/dashboard', name: 'app_dashboard_direction_regionale')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not found');
        }
        $region = $user->getRegion();

        if (!$region) {
            throw $this->createAccessDeniedException('Region not found');
        }

        $brigades = $this->brigadeRepository->findBy(['region' => $region]);
        $controls = $this->controleRepository->countByRegion($region->getId());
        $infractions = $this->infractionRepository->countByRegion($region->getId());
        $amendes = $this->amendeRepository->countByRegion($region);

        $stats = [
            'region' => $region->getLibelle(),
            'brigades_count' => count($brigades),
            'controls_count' => $controls,
            'infractions_count' => $infractions,
            'amendes_count' => $amendes,
            'amendes_pending' => $this->amendeRepository->countPendingByRegion($region->getId()),
            'amendes_paid' => $this->amendeRepository->countPaidByRegion($region->getId()),
        ];

        $recentControls = $this->controleRepository->createQueryBuilder('c')
            ->leftJoin('c.brigade', 'b')
            ->where('b.region = :region')
            ->setParameter('region', $region)
            ->orderBy('c.dateControle', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->render('direction_regionale/dashboard.html.twig', [
            'region' => $region,
            'stats' => $stats,
            'brigades' => $brigades,
            'recent_controls' => $recentControls,
        ]);
    }

    #[Route('/brigades', name: 'app_direction_regionale_brigades')]
    public function brigades(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not found');
        }
        $region = $user->getRegion();

        if (!$region) {
            throw $this->createAccessDeniedException('Region not found');
        }

        $brigades = $this->brigadeRepository->findBy(['region' => $region]);

        return $this->render('direction_regionale/brigades.html.twig', [
            'region' => $region,
            'brigades' => $brigades,
        ]);
    }

    #[Route('/brigades/{id}', name: 'app_direction_regionale_brigade_show', methods: ['GET'])]
    public function brigadeShow(Brigade $brigade): Response
    {
        /**
         * Sécurité: DR ne peut accéder qu'aux données de SA région.
         */
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not found');
        }

        $region = $user->getRegion();
        if (!$region) {
            throw $this->createAccessDeniedException('Region not found');
        }

        if ($brigade->getRegion()?->getId() !== $region->getId()) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        return $this->render('direction_regionale/brigade_show.html.twig', [
            'region' => $region,
            'brigade' => $brigade,
        ]);
    }

    #[Route('/brigades/{id}/controls', name: 'app_direction_regionale_brigade_controls', methods: ['GET'])]
    public function brigadeControls(Brigade $brigade, Request $request): Response
    {
        /**
         * Sécurité: DR ne peut accéder qu'aux données de SA région.
         */
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not found');
        }

        $region = $user->getRegion();
        if (!$region) {
            throw $this->createAccessDeniedException('Region not found');
        }

        if ($brigade->getRegion()?->getId() !== $region->getId()) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        $page = $request->query->getInt('page', 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $qb = $this->controleRepository->createQueryBuilder('c')
            ->andWhere('c.brigade = :brigade')
            ->setParameter('brigade', $brigade)
            ->orderBy('c.dateControle', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $controls = $qb->getQuery()->getResult();
        $total = (int) ($this->controleRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.brigade = :brigade')
            ->setParameter('brigade', $brigade)
            ->getQuery()
            ->getSingleScalarResult() ?? 0);

        $totalPages = (int) ceil($total / $limit);

        return $this->render('direction_regionale/controls.html.twig', [
            'region' => $region,
            'brigade' => $brigade,
            'controls' => $controls,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    #[Route('/brigades/{id}/agents', name: 'app_direction_regionale_brigade_agents', methods: ['GET'])]
    public function brigadeAgents(Brigade $brigade): Response
    {
        /**
         * Sécurité: DR ne peut accéder qu'aux données de SA région.
         */
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not found');
        }

        $region = $user->getRegion();
        if (!$region) {
            throw $this->createAccessDeniedException('Region not found');
        }

        if ($brigade->getRegion()?->getId() !== $region->getId()) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        $agents = $this->agentRepository->findBy(['brigade' => $brigade], ['nom' => 'ASC']);

        return $this->render('direction_regionale/brigade_agents.html.twig', [
            'region' => $region,
            'brigade' => $brigade,
            'agents' => $agents,
        ]);
    }

    #[Route('/controls', name: 'app_direction_regionale_controls')]
    public function controls(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not found');
        }
        $region = $user->getRegion();

        if (!$region) {
            throw $this->createAccessDeniedException('Region not found');
        }

        $page = $request->query->getInt('page', 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $qb = $this->controleRepository->createQueryBuilder('c')
            ->leftJoin('c.brigade', 'b')
            ->where('b.region = :region')
            ->setParameter('region', $region)
            ->orderBy('c.dateControle', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $controls = $qb->getQuery()->getResult();
        $total = $this->controleRepository->countByRegion($region->getId());
        $totalPages = (int) ceil($total / $limit);

        return $this->render('direction_regionale/controls.html.twig', [
            'region' => $region,
            'controls' => $controls,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    #[Route('/controls/{id}', name: 'app_direction_regionale_control_show', methods: ['GET'])]
    public function controlShow(Controle $controle): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not found');
        }

        $region = $user->getRegion();
        if (!$region) {
            throw $this->createAccessDeniedException('Region not found');
        }

        $brigade = $controle->getBrigade();
        if (!$brigade || $brigade->getRegion()?->getId() !== $region->getId()) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        return $this->render('direction_regionale/control_show.html.twig', [
            'region' => $region,
            'control' => $controle,
        ]);
    }

    #[Route('/infractions/{id}', name: 'app_direction_regionale_infraction_show', methods: ['GET'])]
    public function infractionShow(Infraction $infraction): Response
    {
        /**
         * Sécurité: DR ne peut accéder qu'aux données de SA région.
         */
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not found');
        }

        $region = $user->getRegion();
        if (!$region) {
            throw $this->createAccessDeniedException('Region not found');
        }

        $brigade = $infraction->getControle()?->getBrigade();
        if (!$brigade || $brigade->getRegion()?->getId() !== $region->getId()) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        return $this->render('direction_regionale/infraction_show.html.twig', [
            'region' => $region,
            'infraction' => $infraction,
        ]);
    }

    #[Route('/infractions', name: 'app_direction_regionale_infractions')]
    public function infractions(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not found');
        }
        $region = $user->getRegion();

        if (!$region) {
            throw $this->createAccessDeniedException('Region not found');
        }

        $page = $request->query->getInt('page', 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $qb = $this->infractionRepository->createQueryBuilder('i')
            ->leftJoin('i.controle', 'c')
            ->leftJoin('c.brigade', 'b')
            ->where('b.region = :region')
            ->setParameter('region', $region)
            ->orderBy('i.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $infractions = $qb->getQuery()->getResult();
        $total = $this->infractionRepository->countByRegion($region->getId());
        $totalPages = (int) ceil($total / $limit);

        return $this->render('direction_regionale/infractions.html.twig', [
            'region' => $region,
            'infractions' => $infractions,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    #[Route('/amendes', name: 'app_direction_regionale_amendes')]
    public function amendes(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not found');
        }
        $region = $user->getRegion();

        if (!$region) {
            throw $this->createAccessDeniedException('Region not found');
        }

        $page = $request->query->getInt('page', 1);
        $statut = $request->query->get('statut');
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $qb = $this->amendeRepository->createQueryBuilder('a')
            ->leftJoin('a.infraction', 'i')
            ->leftJoin('i.controle', 'c')
            ->leftJoin('c.brigade', 'b')
            ->where('b.region = :region')
            ->setParameter('region', $region);

        if ($statut) {
            $qb->andWhere('a.statut = :statut')
                ->setParameter('statut', $statut);
        }

        $qb->orderBy('a.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $amendes = $qb->getQuery()->getResult();

        $totalQb = $this->amendeRepository->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->join('a.infraction', 'i')
            ->join('i.controle', 'c')
            ->join('c.brigade', 'b')
            ->andWhere('b.region = :region')
            ->setParameter('region', $region);

        if ($statut) {
            $totalQb->andWhere('a.statut = :statut')
                ->setParameter('statut', $statut);
        }

        $total = (int) ($totalQb->getQuery()->getSingleScalarResult() ?? 0);
        $totalPages = (int) ceil($total / $limit);

        return $this->render('direction_regionale/amendes.html.twig', [
            'region' => $region,
            'amendes' => $amendes,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    #[Route('/amendes/{id}', name: 'app_direction_regionale_amende_show', methods: ['GET'])]
    public function amendeShow(Amende $amende): Response
    {
        /**
         * Sécurité: DR ne peut accéder qu'aux données de SA région.
         */
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not found');
        }

        $region = $user->getRegion();
        if (!$region) {
            throw $this->createAccessDeniedException('Region not found');
        }

        $brigade = $amende->getInfraction()?->getControle()?->getBrigade();
        if (!$brigade || $brigade->getRegion()?->getId() !== $region->getId()) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        return $this->render('direction_regionale/amende_show.html.twig', [
            'region' => $region,
            'amende' => $amende,
        ]);
    }
}
