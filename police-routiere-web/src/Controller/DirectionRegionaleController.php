<?php

namespace App\Controller;

use App\Entity\User;
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
        private BrigadeRepository $brigadeRepository
    ) {}

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
        $totalPages = ceil($total / $limit);

        return $this->render('direction_regionale/controls.html.twig', [
            'region' => $region,
            'controls' => $controls,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
            ],
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
        $totalPages = ceil($total / $limit);

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
            $qb->andWhere('a.statutPaiement = :statut')
                ->setParameter('statut', $statut);
        }

        $qb->orderBy('a.dateEmission', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $amendes = $qb->getQuery()->getResult();
        $total = $this->amendeRepository->countByRegion($region);
        $totalPages = ceil($total / $limit);

        return $this->render('direction_regionale/amendes.html.twig', [
            'region' => $region,
            'amendes' => $amendes,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
            ],
        ]);
    }
}
