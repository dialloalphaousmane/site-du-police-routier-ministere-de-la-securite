<?php

namespace App\Controller\Admin;

use App\Entity\Evacuation;
use App\Form\EvacuationType;
use App\Repository\EvacuationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/evacuation')]
#[IsGranted('ROLE_ADMIN')]
class EvacuationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private EvacuationRepository $evacuationRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        EvacuationRepository $evacuationRepository
    ) {
        $this->entityManager = $entityManager;
        $this->evacuationRepository = $evacuationRepository;
    }

    #[Route('/', name: 'app_admin_evacuation_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $status = $request->query->get('status');
        $urgence = $request->query->get('urgence');
        $type = $request->query->get('type');
        $region = $request->query->get('region');
        $search = $request->query->get('search');

        $qb = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.region', 'r')
            ->leftJoin('e.brigade', 'b')
            ->leftJoin('e.createdBy', 'u')
            ->addSelect('a', 'r', 'b', 'u')
            ->orderBy('e.dateEvacuation', 'DESC');

        // Filtres
        if ($status) {
            $qb->andWhere('e.status = :status')
               ->setParameter('status', $status);
        }

        if ($urgence) {
            $qb->andWhere('e.urgence = :urgence')
               ->setParameter('urgence', $urgence);
        }

        if ($type) {
            $qb->andWhere('e.typeEvacuation = :type')
               ->setParameter('type', $type);
        }

        if ($region) {
            $qb->andWhere('r.id = :region')
               ->setParameter('region', $region);
        }

        if ($search) {
            $qb->andWhere('e.reference LIKE :search OR e.hopitalDestination LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $total = count($qb->getQuery()->getResult());
        $evacuations = $qb->setMaxResults($limit)
                          ->setFirstResult(($page - 1) * $limit)
                          ->getQuery()
                          ->getResult();

        $regions = $this->entityManager->getRepository(\App\Entity\Region::class)->findAll();

        return $this->render('admin/evacuation/index.html.twig', [
            'evacuations' => $evacuations,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit),
            'regions' => $regions,
            'currentFilters' => [
                'status' => $status,
                'urgence' => $urgence,
                'type' => $type,
                'region' => $region,
                'search' => $search
            ]
        ]);
    }

    #[Route('/new', name: 'app_admin_evacuation_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $evacuation = new Evacuation();
        $form = $this->createForm(EvacuationType::class, $evacuation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération automatique de la référence
            $evacuation->setReference($this->generateReference());
            $evacuation->setCreatedBy($this->getUser());
            
            $this->entityManager->persist($evacuation);
            $this->entityManager->flush();

            $this->addFlash('success', 'Évacuation créée avec succès.');
            return $this->redirectToRoute('app_admin_evacuation_show', ['id' => $evacuation->getId()]);
        }

        return $this->render('admin/evacuation/new.html.twig', [
            'evacuation' => $evacuation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_evacuation_show', methods: ['GET'])]
    public function show(Evacuation $evacuation): Response
    {
        return $this->render('admin/evacuation/show.html.twig', [
            'evacuation' => $evacuation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_evacuation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evacuation $evacuation): Response
    {
        $form = $this->createForm(EvacuationType::class, $evacuation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Évacuation modifiée avec succès.');
            return $this->redirectToRoute('app_admin_evacuation_show', ['id' => $evacuation->getId()]);
        }

        return $this->render('admin/evacuation/edit.html.twig', [
            'evacuation' => $evacuation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_evacuation_delete', methods: ['POST'])]
    public function delete(Request $request, Evacuation $evacuation): Response
    {
        if ($this->isCsrfTokenValid('delete'.$evacuation->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($evacuation);
            $this->entityManager->flush();
            $this->addFlash('success', 'Évacuation supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_admin_evacuation_index');
    }

    #[Route('/{id}/complete', name: 'app_admin_evacuation_complete', methods: ['POST'])]
    public function complete(Request $request, Evacuation $evacuation): Response
    {
        if ($this->isCsrfTokenValid('complete'.$evacuation->getId(), $request->request->get('_token'))) {
            $evacuation->setStatus(Evacuation::STATUS_TERMINE);
            $evacuation->setDateArrivee(new \DateTimeImmutable());
            $this->entityManager->flush();
            $this->addFlash('success', 'Évacuation marquée comme terminée.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_admin_evacuation_show', ['id' => $evacuation->getId()]);
    }

    #[Route('/{id}/cancel', name: 'app_admin_evacuation_cancel', methods: ['POST'])]
    public function cancel(Request $request, Evacuation $evacuation): Response
    {
        if ($this->isCsrfTokenValid('cancel'.$evacuation->getId(), $request->request->get('_token'))) {
            $evacuation->setStatus(Evacuation::STATUS_ANNULE);
            $this->entityManager->flush();
            $this->addFlash('success', 'Évacuation annulée.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_admin_evacuation_show', ['id' => $evacuation->getId()]);
    }

    #[Route('/statistics', name: 'app_admin_evacuation_statistics', methods: ['GET'])]
    public function statistics(): Response
    {
        $totalEvacuations = $this->evacuationRepository->count([]);
        
        $byStatus = [];
        $statuses = [Evacuation::STATUS_EN_COURS, Evacuation::STATUS_TERMINE, Evacuation::STATUS_ANNULE];
        foreach ($statuses as $status) {
            $byStatus[$status] = $this->evacuationRepository->count(['status' => $status]);
        }

        $byUrgency = [];
        $urgencies = [Evacuation::URGENCY_BASSE, Evacuation::URGENCY_MOYENNE, Evacuation::URGENCY_HAUTE];
        foreach ($urgencies as $urgence) {
            $byUrgency[$urgence] = $this->evacuationRepository->count(['urgence' => $urgence]);
        }

        $byType = [];
        $types = [Evacuation::TYPE_AMBULANCE, Evacuation::TYPE_HELI, Evacuation::TYPE_VEHICULE_PERSONNEL, Evacuation::TYPE_CAMION];
        foreach ($types as $type) {
            $byType[$type] = $this->evacuationRepository->count(['typeEvacuation' => $type]);
        }

        $byRegion = [];
        $regions = $this->entityManager->getRepository(\App\Entity\Region::class)->findAll();
        foreach ($regions as $region) {
            $count = $this->evacuationRepository->createQueryBuilder('e')
                ->where('e.region = :region')
                ->setParameter('region', $region)
                ->select('COUNT(e.id)')
                ->getQuery()
                ->getSingleScalarResult();
            $byRegion[$region->getLibelle()] = $count;
        }

        $byMonth = $this->evacuationRepository->createQueryBuilder('e')
            ->select('COUNT(e.id) as count, DATE_FORMAT(e.dateEvacuation, \'%Y-%m\') as month')
            ->groupBy('month')
            ->orderBy('month', 'DESC')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();

        // Évacuations en cours par urgence
        $urgentEnCours = $this->evacuationRepository->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.urgence = :urgence')
            ->setParameter('status', Evacuation::STATUS_EN_COURS)
            ->setParameter('urgence', Evacuation::URGENCY_HAUTE)
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Temps moyen d'évacuation (terminées)
        $tempsMoyen = $this->evacuationRepository->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.dateArrivee IS NOT NULL')
            ->select('AVG(TIMESTAMPDIFF(MINUTE, e.dateEvacuation, e.dateArrivee)) as tempsMoyen')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/evacuation/statistics.html.twig', [
            'totalEvacuations' => $totalEvacuations,
            'byStatus' => $byStatus,
            'byUrgency' => $byUrgency,
            'byType' => $byType,
            'byRegion' => $byRegion,
            'byMonth' => $byMonth,
            'urgentEnCours' => $urgentEnCours,
            'tempsMoyen' => round($tempsMoyen ?? 0, 2),
        ]);
    }

    #[Route('/export', name: 'app_admin_evacuation_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $format = $request->query->get('format', 'csv');
        
        $evacuations = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.region', 'r')
            ->leftJoin('e.brigade', 'b')
            ->leftJoin('e.createdBy', 'u')
            ->addSelect('a', 'r', 'b', 'u')
            ->orderBy('e.dateEvacuation', 'DESC')
            ->getQuery()
            ->getResult();

        if ($format === 'csv') {
            $csv = "Référence;Date;Type;Statut;Urgence;Hôpital destination;Région;Brigade;Opérateur;Accident;Date arrivée\n";
            
            foreach ($evacuations as $evacuation) {
                $csv .= $evacuation->getReference() . ';';
                $csv .= $evacuation->getDateEvacuation()->format('d/m/Y H:i') . ';';
                $csv .= $evacuation->getTypeEvacuation() . ';';
                $csv .= $evacuation->getStatus() . ';';
                $csv .= $evacuation->getUrgence() . ';';
                $csv .= ($evacuation->getHopitalDestination() ?? '') . ';';
                $csv .= ($evacuation->getRegion() ? $evacuation->getRegion()->getLibelle() : '') . ';';
                $csv .= ($evacuation->getBrigade() ? $evacuation->getBrigade()->getLibelle() : '') . ';';
                $csv .= ($evacuation->getCreatedBy() ? $evacuation->getCreatedBy()->getNom() . ' ' . $evacuation->getCreatedBy()->getPrenom() : '') . ';';
                $csv .= ($evacuation->getAccident() ? $evacuation->getAccident()->getReference() : '') . ';';
                $csv .= ($evacuation->getDateArrivee() ? $evacuation->getDateArrivee()->format('d/m/Y H:i') : '') . "\n";
            }

            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="evacuations_' . date('Y-m-d') . '.csv"');
            return $response;
        }

        $this->addFlash('error', 'Format d\'export non supporté.');
        return $this->redirectToRoute('app_admin_evacuation_index');
    }

    #[Route('/dashboard', name: 'app_admin_evacuation_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        // Statistiques en temps réel
        $totalEnCours = $this->evacuationRepository->count(['status' => Evacuation::STATUS_EN_COURS]);
        $urgentEnCours = $this->evacuationRepository->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.urgence = :urgence')
            ->setParameter('status', Evacuation::STATUS_EN_COURS)
            ->setParameter('urgence', Evacuation::URGENCY_HAUTE)
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Dernières évacuations urgentes
        $dernieresEvacuationsUrgentes = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.region', 'r')
            ->leftJoin('e.brigade', 'b')
            ->leftJoin('e.createdBy', 'u')
            ->addSelect('a', 'r', 'b', 'u')
            ->where('e.urgence = :urgence')
            ->setParameter('urgence', Evacuation::URGENCY_HAUTE)
            ->orderBy('e.dateEvacuation', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Évacuations en attente de traitement
        $evacuationsEnAttente = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.region', 'r')
            ->leftJoin('e.brigade', 'b')
            ->addSelect('a', 'r', 'b')
            ->where('e.status = :status')
            ->setParameter('status', Evacuation::STATUS_EN_COURS)
            ->orderBy('e.urgence', 'DESC')
            ->addOrderBy('e.dateEvacuation', 'ASC')
            ->setMaxResults(15)
            ->getQuery()
            ->getResult();

        return $this->render('admin/evacuation/dashboard.html.twig', [
            'totalEnCours' => $totalEnCours,
            'urgentEnCours' => $urgentEnCours,
            'dernieresEvacuationsUrgentes' => $dernieresEvacuationsUrgentes,
            'evacuationsEnAttente' => $evacuationsEnAttente,
        ]);
    }

    private function generateReference(): string
    {
        $date = new \DateTime();
        $year = $date->format('Y');
        $month = $date->format('m');
        
        // Compter le nombre d'évacuations ce mois
        $count = $this->evacuationRepository->createQueryBuilder('e')
            ->where('DATE_FORMAT(e.dateEvacuation, \'%Y%m\') = :period')
            ->setParameter('period', $year . $month)
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        return 'EVA-' . $year . $month . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
