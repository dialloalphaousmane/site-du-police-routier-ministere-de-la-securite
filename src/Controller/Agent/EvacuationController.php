<?php

namespace App\Controller\Agent;

use App\Entity\Evacuation;
use App\Entity\Accident;
use App\Entity\Brigade;
use App\Entity\User;
use App\Form\EvacuationType;
use App\Repository\EvacuationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/agent/evacuations')]
#[IsGranted('ROLE_AGENT')]
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

    private function getUserBrigade(): ?Brigade
    {
        /** @var User $user */
        $user = $this->getUser();
        return $user->getBrigade();
    }

    #[Route('/', name: 'app_agent_evacuations_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        /** @var User $user */
        $user = $this->getUser();

        // Statistiques personnelles de l'agent
        $totalEvacuations = $this->evacuationRepository->count(['createdBy' => $user]);
        $evacuationsEnCours = $this->evacuationRepository->count(['createdBy' => $user, 'status' => Evacuation::STATUS_EN_COURS]);
        $evacuationsUrgentes = $this->evacuationRepository->count(['createdBy' => $user, 'urgence' => Evacuation::URGENCY_HAUTE]);
        $evacuationsTerminees = $this->evacuationRepository->count(['createdBy' => $user, 'status' => Evacuation::STATUS_TERMINE]);

        // Évacuations des dernières 24h de l'agent
        $date24h = new \DateTimeImmutable('-24 hours');
        $evacuations24h = $this->evacuationRepository->createQueryBuilder('e')
            ->where('e.createdBy = :user')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date24h)
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Évolution mensuelle personnelle (6 derniers mois)
        $evolutionMensuelle = $this->evacuationRepository->createQueryBuilder('e')
            ->select('COUNT(e.id) as count, DATE_FORMAT(e.dateEvacuation, \'%Y-%m\') as month, MONTHNAME(e.dateEvacuation) as monthName')
            ->where('e.createdBy = :user')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('user', $user)
            ->setParameter('date', new \DateTimeImmutable('-6 months'))
            ->groupBy('month', 'monthName')
            ->orderBy('month', 'DESC')
            ->getQuery()
            ->getResult();

        // Répartition par type d'évacuation personnelle
        $repartitionType = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.typeEvacuation, COUNT(e.id) as count')
            ->where('e.createdBy = :user')
            ->setParameter('user', $user)
            ->groupBy('e.typeEvacuation')
            ->getQuery()
            ->getResult();

        // Répartition par urgence personnelle
        $repartitionUrgence = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.urgence, COUNT(e.id) as count')
            ->where('e.createdBy = :user')
            ->setParameter('user', $user)
            ->groupBy('e.urgence')
            ->getQuery()
            ->getResult();

        // Dernières évacuations urgentes de l'agent
        $dernieresEvacuationsUrgentes = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->addSelect('a')
            ->where('e.createdBy = :user')
            ->andWhere('e.urgence = :urgence')
            ->setParameter('user', $user)
            ->setParameter('urgence', Evacuation::URGENCY_HAUTE)
            ->orderBy('e.dateEvacuation', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Évacuations en attente de traitement de l'agent
        $evacuationsEnAttente = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->addSelect('a')
            ->where('e.createdBy = :user')
            ->andWhere('e.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Evacuation::STATUS_EN_COURS)
            ->orderBy('e.urgence', 'DESC')
            ->addOrderBy('e.dateEvacuation', 'ASC')
            ->setMaxResults(15)
            ->getQuery()
            ->getResult();

        // Temps moyen d'évacuation personnel (terminées)
        $tempsMoyenPersonnel = $this->evacuationRepository->createQueryBuilder('e')
            ->where('e.createdBy = :user')
            ->andWhere('e.status = :status')
            ->andWhere('e.dateArrivee IS NOT NULL')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('user', $user)
            ->setParameter('status', Evacuation::STATUS_TERMINE)
            ->setParameter('date', new \DateTimeImmutable('-30 days'))
            ->select('AVG(TIMESTAMPDIFF(MINUTE, e.dateEvacuation, e.dateArrivee)) as tempsMoyen')
            ->getQuery()
            ->getSingleScalarResult();

        // Hôpitaux les plus sollicités par l'agent
        $hopitauxFrequent = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.hopitalDestination, COUNT(e.id) as count')
            ->where('e.createdBy = :user')
            ->andWhere('e.hopitalDestination IS NOT NULL')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('user', $user)
            ->setParameter('date', new \DateTimeImmutable('-30 days'))
            ->groupBy('e.hopitalDestination')
            ->orderBy('count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Performance personnelle par type
        $performanceType = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.typeEvacuation, COUNT(e.id) as total,
                     SUM(CASE WHEN e.status = :termine THEN 1 ELSE 0 END) as terminees,
                     AVG(TIMESTAMPDIFF(MINUTE, e.dateEvacuation, e.dateArrivee)) as tempsMoyen')
            ->where('e.createdBy = :user')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('user', $user)
            ->setParameter('termine', Evacuation::STATUS_TERMINE)
            ->setParameter('date', new \DateTimeImmutable('-30 days'))
            ->groupBy('e.typeEvacuation')
            ->getQuery()
            ->getResult();

        return $this->render('agent/evacuations/dashboard.html.twig', [
            'user' => $user,
            'brigade' => $brigade,
            'totalEvacuations' => $totalEvacuations,
            'evacuationsEnCours' => $evacuationsEnCours,
            'evacuationsUrgentes' => $evacuationsUrgentes,
            'evacuationsTerminees' => $evacuationsTerminees,
            'evacuations24h' => $evacuations24h,
            'evolutionMensuelle' => $evolutionMensuelle,
            'repartitionType' => $repartitionType,
            'repartitionUrgence' => $repartitionUrgence,
            'dernieresEvacuationsUrgentes' => $dernieresEvacuationsUrgentes,
            'evacuationsEnAttente' => $evacuationsEnAttente,
            'tempsMoyenPersonnel' => round($tempsMoyenPersonnel ?? 0, 2),
            'hopitauxFrequent' => $hopitauxFrequent,
            'performanceType' => $performanceType,
        ]);
    }

    #[Route('/new', name: 'app_agent_evacuations_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        /** @var User $user */
        $user = $this->getUser();
        $evacuation = new Evacuation();
        
        $form = $this->createForm(EvacuationType::class, $evacuation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Assignation automatique
            $evacuation->setBrigade($brigade);
            $evacuation->setCreatedBy($this->getUser());
            $evacuation->setReference($this->generateReference());
            
            $this->entityManager->persist($evacuation);
            $this->entityManager->flush();

            $this->addFlash('success', 'Évacuation créée avec succès.');
            return $this->redirectToRoute('app_agent_evacuations_show', ['id' => $evacuation->getId()]);
        }

        return $this->render('agent/evacuations/new.html.twig', [
            'user' => $user,
            'brigade' => $brigade,
            'evacuation' => $evacuation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mes-evacuations', name: 'app_agent_evacuations_liste', methods: ['GET'])]
    public function liste(Request $request): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        /** @var User $user */
        $user = $this->getUser();
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $status = $request->query->get('status');
        $urgence = $request->query->get('urgence');
        $type = $request->query->get('type');
        $search = $request->query->get('search');

        $qb = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->addSelect('a')
            ->where('e.createdBy = :user')
            ->setParameter('user', $user)
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

        if ($search) {
            $qb->andWhere('e.reference LIKE :search OR e.hopitalDestination LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $total = count($qb->getQuery()->getResult());
        $evacuations = $qb->setMaxResults($limit)
                          ->setFirstResult(($page - 1) * $limit)
                          ->getQuery()
                          ->getResult();

        return $this->render('agent/evacuations/liste.html.twig', [
            'user' => $user,
            'brigade' => $brigade,
            'evacuations' => $evacuations,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit),
            'currentFilters' => [
                'status' => $status,
                'urgence' => $urgence,
                'type' => $type,
                'search' => $search
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_agent_evacuations_show', methods: ['GET'])]
    public function show(Evacuation $evacuation): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($evacuation->getCreatedBy() !== $user) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_agent_evacuations_dashboard');
        }

        return $this->render('agent/evacuations/show.html.twig', [
            'user' => $user,
            'evacuation' => $evacuation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_agent_evacuations_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evacuation $evacuation): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($evacuation->getCreatedBy() !== $user) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_agent_evacuations_dashboard');
        }

        // Seules les évacuations en cours peuvent être modifiées
        if ($evacuation->getStatus() !== Evacuation::STATUS_EN_COURS) {
            $this->addFlash('error', 'Cette évacuation ne peut plus être modifiée.');
            return $this->redirectToRoute('app_agent_evacuations_show', ['id' => $evacuation->getId()]);
        }

        $form = $this->createForm(EvacuationType::class, $evacuation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Évacuation modifiée avec succès.');
            return $this->redirectToRoute('app_agent_evacuations_show', ['id' => $evacuation->getId()]);
        }

        return $this->render('agent/evacuations/edit.html.twig', [
            'user' => $user,
            'evacuation' => $evacuation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/complete', name: 'app_agent_evacuations_complete', methods: ['POST'])]
    public function complete(Request $request, Evacuation $evacuation): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($evacuation->getCreatedBy() !== $user) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_agent_evacuations_dashboard');
        }

        if ($this->isCsrfTokenValid('complete'.$evacuation->getId(), $request->request->get('_token'))) {
            $evacuation->setStatus(Evacuation::STATUS_TERMINE);
            $evacuation->setDateArrivee(new \DateTimeImmutable());
            $this->entityManager->flush();
            $this->addFlash('success', 'Évacuation marquée comme terminée.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_agent_evacuations_show', ['id' => $evacuation->getId()]);
    }

    #[Route('/{id}/cancel', name: 'app_agent_evacuations_cancel', methods: ['POST'])]
    public function cancel(Request $request, Evacuation $evacuation): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($evacuation->getCreatedBy() !== $user) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_agent_evacuations_dashboard');
        }

        if ($this->isCsrfTokenValid('cancel'.$evacuation->getId(), $request->request->get('_token'))) {
            $evacuation->setStatus(Evacuation::STATUS_ANNULE);
            $this->entityManager->flush();
            $this->addFlash('success', 'Évacuation annulée.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_agent_evacuations_show', ['id' => $evacuation->getId()]);
    }

    #[Route('/urgentes', name: 'app_agent_evacuations_urgentes', methods: ['GET'])]
    public function urgentes(): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        /** @var User $user */
        $user = $this->getUser();

        // Toutes les évacuations urgentes en cours de l'agent
        $evacuationsUrgentes = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->addSelect('a')
            ->where('e.createdBy = :user')
            ->andWhere('e.status = :status')
            ->andWhere('e.urgence = :urgence')
            ->setParameter('user', $user)
            ->setParameter('status', Evacuation::STATUS_EN_COURS)
            ->setParameter('urgence', Evacuation::URGENCY_HAUTE)
            ->orderBy('e.dateEvacuation', 'ASC')
            ->getQuery()
            ->getResult();

        // Évacuations critiques personnelles (plus de 2 heures)
        $evacuationsCritiques = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->addSelect('a')
            ->where('e.createdBy = :user')
            ->andWhere('e.status = :status')
            ->andWhere('e.urgence = :urgence')
            ->andWhere('e.dateEvacuation < :dateLimite')
            ->setParameter('user', $user)
            ->setParameter('status', Evacuation::STATUS_EN_COURS)
            ->setParameter('urgence', Evacuation::URGENCY_HAUTE)
            ->setParameter('dateLimite', new \DateTimeImmutable('-2 hours'))
            ->orderBy('e.dateEvacuation', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('agent/evacuations/urgentes.html.twig', [
            'user' => $user,
            'brigade' => $brigade,
            'evacuationsUrgentes' => $evacuationsUrgentes,
            'evacuationsCritiques' => $evacuationsCritiques,
        ]);
    }

    #[Route('/statistiques', name: 'app_agent_evacuations_statistiques', methods: ['GET'])]
    public function statistiques(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $period = $request->query->get('period', '12'); // 12 mois par défaut
        $dateLimit = new \DateTimeImmutable("-{$period} months");

        // Évolution temporelle personnelle
        $evolutionTemporelle = $this->evacuationRepository->createQueryBuilder('e')
            ->select('COUNT(e.id) as count, DATE_FORMAT(e.dateEvacuation, \'%Y-%m\') as month')
            ->where('e.createdBy = :user')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('user', $user)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        // Pics horaires personnels
        $picsHoraires = $this->evacuationRepository->createQueryBuilder('e')
            ->select('HOUR(e.dateEvacuation) as hour, COUNT(e.id) as count')
            ->where('e.createdBy = :user')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('user', $user)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('hour')
            ->orderBy('hour', 'ASC')
            ->getQuery()
            ->getResult();

        // Taux de résolution personnel
        $tauxResolution = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.status, COUNT(e.id) as count')
            ->where('e.createdBy = :user')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('user', $user)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('e.status')
            ->getQuery()
            ->getResult();

        // Performance mensuelle personnelle
        $performanceMensuelle = $this->evacuationRepository->createQueryBuilder('e')
            ->select('DATE_FORMAT(e.dateEvacuation, \'%Y-%m\') as month, COUNT(e.id) as total,
                     SUM(CASE WHEN e.status = :termine THEN 1 ELSE 0 END) as terminees')
            ->where('e.createdBy = :user')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('user', $user)
            ->setParameter('termine', Evacuation::STATUS_TERMINE)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('month')
            ->orderBy('month', 'DESC')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();

        return $this->render('agent/evacuations/statistiques.html.twig', [
            'user' => $user,
            'evolutionTemporelle' => $evolutionTemporelle,
            'picsHoraires' => $picsHoraires,
            'tauxResolution' => $tauxResolution,
            'performanceMensuelle' => $performanceMensuelle,
            'period' => $period,
        ]);
    }

    #[Route('/export', name: 'app_agent_evacuations_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $format = $request->query->get('format', 'csv');
        $period = $request->query->get('period', '1'); // 1 mois par défaut
        $dateLimit = new \DateTimeImmutable("-{$period} month");
        
        $evacuations = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->addSelect('a')
            ->where('e.createdBy = :user')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('user', $user)
            ->setParameter('dateLimit', $dateLimit)
            ->orderBy('e.dateEvacuation', 'DESC')
            ->getQuery()
            ->getResult();

        if ($format === 'csv') {
            $csv = "Référence;Date;Type;Statut;Urgence;Hôpital destination;Accident;Date arrivée;Durée (min)\n";
            
            foreach ($evacuations as $evacuation) {
                $duree = '';
                if ($evacuation->getDateArrivee()) {
                    $duree = $evacuation->getDateEvacuation()->diff($evacuation->getDateArrivee())->format('%i');
                }
                
                $csv .= $evacuation->getReference() . ';';
                $csv .= $evacuation->getDateEvacuation()->format('d/m/Y H:i') . ';';
                $csv .= $evacuation->getTypeEvacuation() . ';';
                $csv .= $evacuation->getStatus() . ';';
                $csv .= $evacuation->getUrgence() . ';';
                $csv .= ($evacuation->getHopitalDestination() ?? '') . ';';
                $csv .= ($evacuation->getAccident() ? $evacuation->getAccident()->getReference() : '') . ';';
                $csv .= ($evacuation->getDateArrivee() ? $evacuation->getDateArrivee()->format('d/m/Y H:i') : '') . ';';
                $csv .= $duree . "\n";
            }

            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="evacuations_' . $user->getNom() . '_' . date('Y-m-d') . '.csv"');
            return $response;
        }

        $this->addFlash('error', 'Format d\'export non supporté.');
        return $this->redirectToRoute('app_agent_evacuations_dashboard');
    }

    private function generateReference(): string
    {
        $date = new \DateTime();
        $year = $date->format('Y');
        $month = $date->format('m');
        
        // Compter le nombre d'évacuations ce mois pour l'agent
        /** @var User $user */
        $user = $this->getUser();
        $count = $this->evacuationRepository->createQueryBuilder('e')
            ->where('e.createdBy = :user')
            ->andWhere('DATE_FORMAT(e.dateEvacuation, \'%Y%m\') = :period')
            ->setParameter('user', $user)
            ->setParameter('period', $year . $month)
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        return 'EVA-' . $user->getId() . '-' . $year . $month . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }
}
