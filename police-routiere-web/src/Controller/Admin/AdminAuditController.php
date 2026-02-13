<?php

namespace App\Controller\Admin;

use App\Repository\AuditLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/audit')]
#[IsGranted('ROLE_ADMIN')]
class AdminAuditController extends AbstractController
{
    public function __construct(
        private AuditLogRepository $auditLogRepository
    ) {}

    #[Route('/', name: 'app_admin_audit_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $logs = $this->auditLogRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
        $total = $this->auditLogRepository->count([]);
        $totalPages = ceil($total / $limit);

        return $this->render('admin/audit/index.html.twig', [
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
            ],
        ]);
    }

    #[Route('/{id}', name: 'app_admin_audit_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $log = $this->auditLogRepository->find($id);

        if (!$log) {
            throw $this->createNotFoundException('Log d\'audit non trouvé');
        }

        return $this->render('admin/audit/show.html.twig', [
            'log' => $log,
        ]);
    }

    #[Route('/search', name: 'app_admin_audit_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $user = $request->query->get('user');
        $action = $request->query->get('action');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');

        $query = $this->auditLogRepository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        if ($user) {
            $query->andWhere('a.user LIKE :user')
                ->setParameter('user', '%' . $user . '%');
        }

        if ($action) {
            $query->andWhere('a.action = :action')
                ->setParameter('action', $action);
        }

        if ($dateFrom) {
            $query->andWhere('a.createdAt >= :dateFrom')
                ->setParameter('dateFrom', \DateTime::createFromFormat('Y-m-d', $dateFrom));
        }

        if ($dateTo) {
            $query->andWhere('a.createdAt <= :dateTo')
                ->setParameter('dateTo', \DateTime::createFromFormat('Y-m-d', $dateTo . ' 23:59:59'));
        }

        $logs = $query->getQuery()->getResult();

        return $this->render('admin/audit/search.html.twig', [
            'logs' => $logs,
            'search' => [
                'user' => $user,
                'action' => $action,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }
}
