<?php

namespace App\Controller\Admin;

use App\Entity\Log;
use App\Repository\LogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/log')]
#[IsGranted('ROLE_ADMIN')]
class LogController extends AbstractController
{
    private $entityManager;
    private $logRepository;

    public function __construct(EntityManagerInterface $entityManager, LogRepository $logRepository)
    {
        $this->entityManager = $entityManager;
        $this->logRepository = $logRepository;
    }

    #[Route('/', name: 'app_admin_log_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        // Filtres
        $level = $request->query->get('level');
        $action = $request->query->get('action');
        $entity = $request->query->get('entity');
        $search = $request->query->get('search');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');

        $qb = $this->logRepository->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u')
            ->orderBy('l.createdAt', 'DESC');

        // Appliquer les filtres
        if ($level) {
            $qb->andWhere('l.level = :level')
               ->setParameter('level', $level);
        }

        if ($action) {
            $qb->andWhere('l.action = :action')
               ->setParameter('action', $action);
        }

        if ($entity) {
            $qb->andWhere('l.entity = :entity')
               ->setParameter('entity', $entity);
        }

        if ($search) {
            $qb->andWhere('l.action LIKE :search OR l.description LIKE :search OR l.entity LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($dateFrom) {
            $qb->andWhere('l.createdAt >= :dateFrom')
               ->setParameter('dateFrom', new \DateTimeImmutable($dateFrom));
        }

        if ($dateTo) {
            $qb->andWhere('l.createdAt <= :dateTo')
               ->setParameter('dateTo', new \DateTimeImmutable($dateTo . ' 23:59:59'));
        }

        // Compter le total
        $totalLogs = count($qb->getQuery()->getResult());
        $totalPages = ceil($totalLogs / $limit);

        // Paginer
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        $logs = $qb->getQuery()->getResult();

        // Statistiques
        $stats = $this->logRepository->countByLevel();
        $actionStats = $this->logRepository->countByAction();
        $entityStats = $this->logRepository->countByEntity();

        return $this->render('admin/log/index.html.twig', [
            'logs' => $logs,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalLogs' => $totalLogs,
            'stats' => $stats,
            'actionStats' => $actionStats,
            'entityStats' => $entityStats,
            'filters' => [
                'level' => $level,
                'action' => $action,
                'entity' => $entity,
                'search' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]
        ]);
    }

    #[Route('/errors', name: 'app_admin_log_errors', methods: ['GET'])]
    public function errors(): Response
    {
        $logs = $this->logRepository->findErrors(100);

        return $this->render('admin/log/errors.html.twig', [
            'logs' => $logs
        ]);
    }

    #[Route('/cleanup', name: 'app_admin_log_cleanup', methods: ['POST'])]
    public function cleanup(Request $request): Response
    {
        $days = $request->request->get('days', 90);
        
        if ($days < 7) {
            $this->addFlash('error', 'La période de conservation minimale est de 7 jours.');
            return $this->redirectToRoute('app_admin_log_index');
        }

        $deletedCount = $this->logRepository->cleanupOldLogs($days);
        
        $this->addFlash('success', "{$deletedCount} logs ont été supprimés (plus de {$days} jours).");
        
        return $this->redirectToRoute('app_admin_log_index');
    }

    #[Route('/export', name: 'app_admin_log_export', methods: ['GET'])]
    public function export(Request $request): StreamedResponse
    {
        // Récupérer les logs avec les filtres
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 1000;
        $offset = ($page - 1) * $limit;

        $qb = $this->logRepository->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u')
            ->orderBy('l.createdAt', 'DESC');

        // Appliquer les mêmes filtres que l'index
        $level = $request->query->get('level');
        $action = $request->query->get('action');
        $entity = $request->query->get('entity');
        $search = $request->query->get('search');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');

        if ($level) {
            $qb->andWhere('l.level = :level')
               ->setParameter('level', $level);
        }

        if ($action) {
            $qb->andWhere('l.action = :action')
               ->setParameter('action', $action);
        }

        if ($entity) {
            $qb->andWhere('l.entity = :entity')
               ->setParameter('entity', $entity);
        }

        if ($search) {
            $qb->andWhere('l.action LIKE :search OR l.description LIKE :search OR l.entity LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($dateFrom) {
            $qb->andWhere('l.createdAt >= :dateFrom')
               ->setParameter('dateFrom', new \DateTimeImmutable($dateFrom));
        }

        if ($dateTo) {
            $qb->andWhere('l.createdAt <= :dateTo')
               ->setParameter('dateTo', new \DateTimeImmutable($dateTo . ' 23:59:59'));
        }

        $qb->setMaxResults($limit);
        $logs = $qb->getQuery()->getResult();

        // Créer le contenu CSV
        $csvContent = "ID;Date;Action;Entité;ID Entité;Utilisateur;Niveau;Description;IP;User Agent;Ancienne valeur;Nouvelle valeur\n";
        
        foreach ($logs as $log) {
            $csvContent .= sprintf(
                "%d;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s\n",
                $log->getId(),
                $log->getCreatedAt()->format('d/m/Y H:i:s'),
                $log->getAction(),
                $log->getEntity(),
                $log->getEntityId() ?? '',
                $log->getUser() ? $log->getUser()->getEmail() : '',
                $log->getLevel(),
                str_replace(["\n", "\r", ";"], " ", $log->getDescription()),
                $log->getIpAddress(),
                str_replace(["\n", "\r", ";"], " ", $log->getUserAgent()),
                str_replace(["\n", "\r", ";"], " ", $log->getOldValue() ?? ''),
                str_replace(["\n", "\r", ";"], " ", $log->getNewValue() ?? '')
            );
        }

        return $this->createCsvResponse('logs', $csvContent);
    }

    #[Route('/{id}', name: 'app_admin_log_show', methods: ['GET'])]
    public function show(Log $log): Response
    {
        return $this->render('admin/log/show.html.twig', [
            'log' => $log
        ]);
    }

    #[Route('/{id}', name: 'app_admin_log_delete', methods: ['POST'])]
    public function delete(Request $request, Log $log): Response
    {
        if ($this->isCsrfTokenValid('delete'.$log->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($log);
            $this->entityManager->flush();

            $this->addFlash('success', 'Log supprimé avec succès !');
        }

        return $this->redirectToRoute('app_admin_log_index');
    }

    private function createCsvResponse(string $filename, string $content): StreamedResponse
    {
        $response = new StreamedResponse();
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '_' . date('Y-m-d') . '.csv"');
        $response->headers->set('Content-Length', strlen($content));
        
        $response->setCallback(function() use ($content) {
            echo "\xEF\xBB\xBF"; // BOM UTF-8
            echo $content;
        });

        return $response;
    }
}
