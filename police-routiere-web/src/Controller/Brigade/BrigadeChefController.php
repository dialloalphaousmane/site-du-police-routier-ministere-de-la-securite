<?php

namespace App\Controller\Brigade;

use App\Repository\ControleRepository;
use App\Repository\InfractionRepository;
use App\Repository\AmendeRepository;
use App\Repository\AgentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/brigade')]
#[IsGranted('ROLE_CHEF_BRIGADE')]
class BrigadeChefController extends AbstractController
{
    public function __construct(
        private ControleRepository $controleRepository,
        private InfractionRepository $infractionRepository,
        private AmendeRepository $amendeRepository,
        private AgentRepository $agentRepository
    ) {}

    #[Route('/dashboard', name: 'app_brigade_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        $brigade = $user->getBrigade();

        if (!$brigade) {
            throw $this->createAccessDeniedException('Brigade not found');
        }

        $agents = $this->agentRepository->findBy(['brigade' => $brigade]);
        $controls = $this->controleRepository->findBy(['brigade' => $brigade], ['dateControle' => 'DESC'], 10);
        $infractions = $this->infractionRepository->countByBrigade($brigade->getId());
        $amendes = $this->amendeRepository->countByBrigade($brigade->getId());

        $stats = [
            'agents_count' => count($agents),
            'controls_count' => $this->controleRepository->countByBrigade($brigade->getId()),
            'infractions_count' => $infractions,
            'amendes_count' => $amendes,
            'amendes_pending' => $this->amendeRepository->countPendingByBrigade($brigade->getId()),
        ];

        return $this->render('brigade/dashboard.html.twig', [
            'brigade' => $brigade,
            'stats' => $stats,
            'agents' => $agents,
            'recent_controls' => $controls,
        ]);
    }

    #[Route('/agents', name: 'app_brigade_agents')]
    public function agents(): Response
    {
        $user = $this->getUser();
        $brigade = $user->getBrigade();

        if (!$brigade) {
            throw $this->createAccessDeniedException('Brigade not found');
        }

        $agents = $this->agentRepository->findBy(['brigade' => $brigade]);

        return $this->render('brigade/agents.html.twig', [
            'brigade' => $brigade,
            'agents' => $agents,
        ]);
    }

    #[Route('/controls', name: 'app_brigade_controls')]
    public function controls(Request $request): Response
    {
        $user = $this->getUser();
        $brigade = $user->getBrigade();

        if (!$brigade) {
            throw $this->createAccessDeniedException('Brigade not found');
        }

        $page = $request->query->getInt('page', 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $controls = $this->controleRepository->findBy(
            ['brigade' => $brigade],
            ['dateControle' => 'DESC'],
            $limit,
            $offset
        );

        $total = $this->controleRepository->countByBrigade($brigade->getId());
        $totalPages = ceil($total / $limit);

        return $this->render('brigade/controls.html.twig', [
            'brigade' => $brigade,
            'controls' => $controls,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    #[Route('/infractions', name: 'app_brigade_infractions')]
    public function infractions(Request $request): Response
    {
        $user = $this->getUser();
        $brigade = $user->getBrigade();

        if (!$brigade) {
            throw $this->createAccessDeniedException('Brigade not found');
        }

        $page = $request->query->getInt('page', 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $qb = $this->infractionRepository->createQueryBuilder('i')
            ->leftJoin('i.controle', 'c')
            ->where('c.brigade = :brigade')
            ->setParameter('brigade', $brigade)
            ->orderBy('i.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $infractions = $qb->getQuery()->getResult();
        $total = $this->infractionRepository->countByBrigade($brigade->getId());
        $totalPages = ceil($total / $limit);

        return $this->render('brigade/infractions.html.twig', [
            'brigade' => $brigade,
            'infractions' => $infractions,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    #[Route('/amendes', name: 'app_brigade_amendes')]
    public function amendes(Request $request): Response
    {
        $user = $this->getUser();
        $brigade = $user->getBrigade();

        if (!$brigade) {
            throw $this->createAccessDeniedException('Brigade not found');
        }

        $page = $request->query->getInt('page', 1);
        $statut = $request->query->get('statut');
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $qb = $this->amendeRepository->createQueryBuilder('a')
            ->leftJoin('a.infraction', 'i')
            ->leftJoin('i.controle', 'c')
            ->where('c.brigade = :brigade')
            ->setParameter('brigade', $brigade);

        if ($statut) {
            $qb->andWhere('a.statutPaiement = :statut')
                ->setParameter('statut', $statut);
        }

        $qb->orderBy('a.dateEmission', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $amendes = $qb->getQuery()->getResult();
        $total = $this->amendeRepository->countByBrigade($brigade->getId());
        $totalPages = ceil($total / $limit);

        return $this->render('brigade/amendes.html.twig', [
            'brigade' => $brigade,
            'amendes' => $amendes,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
            ],
        ]);
    }
}
