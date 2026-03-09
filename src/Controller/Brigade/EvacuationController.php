<?php

namespace App\Controller\Brigade;

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

#[Route('/brigade/evacuations')]
#[IsGranted('ROLE_CHEF_BRIGADE')]
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

    #[Route('/', name: 'app_brigade_evacuations_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Statistiques de la brigade en temps réel
        $totalEvacuations = $this->evacuationRepository->count(['brigade' => $brigade]);
        $evacuationsEnCours = $this->evacuationRepository->count(['brigade' => $brigade, 'status' => Evacuation::STATUS_EN_COURS]);
        $evacuationsUrgentes = $this->evacuationRepository->count(['brigade' => $brigade, 'urgence' => Evacuation::URGENCY_HAUTE]);
        $evacuationsTerminees = $this->evacuationRepository->count(['brigade' => $brigade, 'status' => Evacuation::STATUS_TERMINE]);

        // Évacuations des dernières 24h de la brigade
        $date24h = new \DateTimeImmutable('-24 hours');
        $evacuations24h = $this->evacuationRepository->createQueryBuilder('e')
            ->where('e.brigade = :brigade')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('brigade', $brigade)
            ->setParameter('date', $date24h)
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Évacuations urgentes en cours dans la brigade
        $urgentesEnCours = $this->evacuationRepository->createQueryBuilder('e')
            ->where('e.brigade = :brigade')
            ->andWhere('e.status = :status')
            ->andWhere('e.urgence = :urgence')
            ->setParameter('brigade', $brigade)
            ->setParameter('status', Evacuation::STATUS_EN_COURS)
            ->setParameter('urgence', Evacuation::URGENCY_HAUTE)
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Répartition par type d'évacuation dans la brigade
        $repartitionType = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.typeEvacuation, COUNT(e.id) as count')
            ->where('e.brigade = :brigade')
            ->setParameter('brigade', $brigade)
            ->groupBy('e.typeEvacuation')
            ->getQuery()
            ->getResult();

        // Répartition par urgence dans la brigade
        $repartitionUrgence = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.urgence, COUNT(e.id) as count')
            ->where('e.brigade = :brigade')
            ->setParameter('brigade', $brigade)
            ->groupBy('e.urgence')
            ->getQuery()
            ->getResult();

        // Évolution mensuelle (6 derniers mois)
        $evolutionMensuelle = $this->evacuationRepository->createQueryBuilder('e')
            ->select('COUNT(e.id) as count, DATE_FORMAT(e.dateEvacuation, \'%Y-%m\') as month, MONTHNAME(e.dateEvacuation) as monthName')
            ->where('e.brigade = :brigade')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('brigade', $brigade)
            ->setParameter('date', new \DateTimeImmutable('-6 months'))
            ->groupBy('month', 'monthName')
            ->orderBy('month', 'DESC')
            ->getQuery()
            ->getResult();

        // Dernières évacuations urgentes de la brigade
        $dernieresEvacuationsUrgentes = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.createdBy', 'u')
            ->addSelect('a', 'u')
            ->where('e.brigade = :brigade')
            ->andWhere('e.urgence = :urgence')
            ->setParameter('brigade', $brigade)
            ->setParameter('urgence', Evacuation::URGENCY_HAUTE)
            ->orderBy('e.dateEvacuation', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Évacuations en attente de traitement dans la brigade
        $evacuationsEnAttente = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.createdBy', 'u')
            ->addSelect('a', 'u')
            ->where('e.brigade = :brigade')
            ->andWhere('e.status = :status')
            ->setParameter('brigade', $brigade)
            ->setParameter('status', Evacuation::STATUS_EN_COURS)
            ->orderBy('e.urgence', 'DESC')
            ->addOrderBy('e.dateEvacuation', 'ASC')
            ->setMaxResults(15)
            ->getQuery()
            ->getResult();

        // Temps moyen d'évacuation dans la brigade (terminées)
        $tempsMoyenBrigade = $this->evacuationRepository->createQueryBuilder('e')
            ->where('e.brigade = :brigade')
            ->andWhere('e.status = :status')
            ->andWhere('e.dateArrivee IS NOT NULL')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('brigade', $brigade)
            ->setParameter('status', Evacuation::STATUS_TERMINE)
            ->setParameter('date', new \DateTimeImmutable('-30 days'))
            ->select('AVG(TIMESTAMPDIFF(MINUTE, e.dateEvacuation, e.dateArrivee)) as tempsMoyen')
            ->getQuery()
            ->getSingleScalarResult();

        // Hôpitaux les plus sollicités par la brigade
        $hopitauxFrequent = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.hopitalDestination, COUNT(e.id) as count')
            ->where('e.brigade = :brigade')
            ->andWhere('e.hopitalDestination IS NOT NULL')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('brigade', $brigade)
            ->setParameter('date', new \DateTimeImmutable('-30 days'))
            ->groupBy('e.hopitalDestination')
            ->orderBy('count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Performance des opérateurs de la brigade
        $performanceOperateurs = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.operateur', 'u')
            ->select('u.nom as nom, u.prenom as prenom, COUNT(e.id) as total,
                     SUM(CASE WHEN e.status = :termine THEN 1 ELSE 0 END) as terminees')
            ->where('e.brigade = :brigade')
            ->andWhere('u.id IS NOT NULL')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('brigade', $brigade)
            ->setParameter('termine', Evacuation::STATUS_TERMINE)
            ->setParameter('date', new \DateTimeImmutable('-30 days'))
            ->groupBy('u.id', 'u.nom', 'u.prenom')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('brigade/evacuations/dashboard.html.twig', [
            'brigade' => $brigade,
            'totalEvacuations' => $totalEvacuations,
            'evacuationsEnCours' => $evacuationsEnCours,
            'evacuationsUrgentes' => $evacuationsUrgentes,
            'evacuationsTerminees' => $evacuationsTerminees,
            'evacuations24h' => $evacuations24h,
            'urgentesEnCours' => $urgentesEnCours,
            'repartitionType' => $repartitionType,
            'repartitionUrgence' => $repartitionUrgence,
            'evolutionMensuelle' => $evolutionMensuelle,
            'dernieresEvacuationsUrgentes' => $dernieresEvacuationsUrgentes,
            'evacuationsEnAttente' => $evacuationsEnAttente,
            'tempsMoyenBrigade' => round($tempsMoyenBrigade ?? 0, 2),
            'hopitauxFrequent' => $hopitauxFrequent,
            'performanceOperateurs' => $performanceOperateurs,
        ]);
    }

    #[Route('/new', name: 'app_brigade_evacuations_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

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
            return $this->redirectToRoute('app_brigade_evacuations_show', ['id' => $evacuation->getId()]);
        }

        return $this->render('brigade/evacuations/new.html.twig', [
            'brigade' => $brigade,
            'evacuation' => $evacuation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/liste', name: 'app_brigade_evacuations_liste', methods: ['GET'])]
    public function liste(Request $request): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $status = $request->query->get('status');
        $urgence = $request->query->get('urgence');
        $type = $request->query->get('type');
        $operateur = $request->query->get('operateur');
        $search = $request->query->get('search');

        $qb = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.createdBy', 'u')
            ->addSelect('a', 'u')
            ->where('e.brigade = :brigade')
            ->setParameter('brigade', $brigade)
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

        if ($operateur) {
            $qb->andWhere('u.id = :operateur')
               ->setParameter('operateur', $operateur);
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

        // Opérateurs de la brigade pour le filtre
        $operateurs = $this->entityManager->getRepository(User::class)->findBy(['brigade' => $brigade]);

        return $this->render('brigade/evacuations/liste.html.twig', [
            'brigade' => $brigade,
            'evacuations' => $evacuations,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit),
            'operateurs' => $operateurs,
            'currentFilters' => [
                'status' => $status,
                'urgence' => $urgence,
                'type' => $type,
                'operateur' => $operateur,
                'search' => $search
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_brigade_evacuations_show', methods: ['GET'])]
    public function show(Evacuation $evacuation): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade || $evacuation->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_brigade_evacuations_dashboard');
        }

        return $this->render('brigade/evacuations/show.html.twig', [
            'brigade' => $brigade,
            'evacuation' => $evacuation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_brigade_evacuations_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evacuation $evacuation): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade || $evacuation->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_brigade_evacuations_dashboard');
        }

        $form = $this->createForm(EvacuationType::class, $evacuation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Évacuation modifiée avec succès.');
            return $this->redirectToRoute('app_brigade_evacuations_show', ['id' => $evacuation->getId()]);
        }

        return $this->render('brigade/evacuations/edit.html.twig', [
            'brigade' => $brigade,
            'evacuation' => $evacuation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/complete', name: 'app_brigade_evacuations_complete', methods: ['POST'])]
    public function complete(Request $request, Evacuation $evacuation): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade || $evacuation->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_brigade_evacuations_dashboard');
        }

        if ($this->isCsrfTokenValid('complete'.$evacuation->getId(), $request->request->get('_token'))) {
            $evacuation->setStatus(Evacuation::STATUS_TERMINE);
            $evacuation->setDateArrivee(new \DateTimeImmutable());
            $this->entityManager->flush();
            $this->addFlash('success', 'Évacuation marquée comme terminée.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_brigade_evacuations_show', ['id' => $evacuation->getId()]);
    }

    #[Route('/{id}/cancel', name: 'app_brigade_evacuations_cancel', methods: ['POST'])]
    public function cancel(Request $request, Evacuation $evacuation): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade || $evacuation->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_brigade_evacuations_dashboard');
        }

        if ($this->isCsrfTokenValid('cancel'.$evacuation->getId(), $request->request->get('_token'))) {
            $evacuation->setStatus(Evacuation::STATUS_ANNULE);
            $this->entityManager->flush();
            $this->addFlash('success', 'Évacuation annulée.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_brigade_evacuations_show', ['id' => $evacuation->getId()]);
    }

    #[Route('/urgentes', name: 'app_brigade_evacuations_urgentes', methods: ['GET'])]
    public function urgentes(): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Toutes les évacuations urgentes en cours de la brigade
        $evacuationsUrgentes = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.createdBy', 'u')
            ->addSelect('a', 'u')
            ->where('e.brigade = :brigade')
            ->andWhere('e.status = :status')
            ->andWhere('e.urgence = :urgence')
            ->setParameter('brigade', $brigade)
            ->setParameter('status', Evacuation::STATUS_EN_COURS)
            ->setParameter('urgence', Evacuation::URGENCY_HAUTE)
            ->orderBy('e.dateEvacuation', 'ASC')
            ->getQuery()
            ->getResult();

        // Évacuations critiques (plus de 2 heures)
        $evacuationsCritiques = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.createdBy', 'u')
            ->addSelect('a', 'u')
            ->where('e.brigade = :brigade')
            ->andWhere('e.status = :status')
            ->andWhere('e.urgence = :urgence')
            ->andWhere('e.dateEvacuation < :dateLimite')
            ->setParameter('brigade', $brigade)
            ->setParameter('status', Evacuation::STATUS_EN_COURS)
            ->setParameter('urgence', Evacuation::URGENCY_HAUTE)
            ->setParameter('dateLimite', new \DateTimeImmutable('-2 hours'))
            ->orderBy('e.dateEvacuation', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('brigade/evacuations/urgentes.html.twig', [
            'brigade' => $brigade,
            'evacuationsUrgentes' => $evacuationsUrgentes,
            'evacuationsCritiques' => $evacuationsCritiques,
        ]);
    }

    #[Route('/statistiques', name: 'app_brigade_evacuations_statistiques', methods: ['GET'])]
    public function statistiques(Request $request): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        $period = $request->query->get('period', '12'); // 12 mois par défaut
        $dateLimit = new \DateTimeImmutable("-{$period} months");

        // Évolution temporelle de la brigade
        $evolutionTemporelle = $this->evacuationRepository->createQueryBuilder('e')
            ->select('COUNT(e.id) as count, DATE_FORMAT(e.dateEvacuation, \'%Y-%m\') as month')
            ->where('e.brigade = :brigade')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('brigade', $brigade)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        // Performance des opérateurs
        $performanceOperateurs = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.operateur', 'u')
            ->select('u.nom as nom, u.prenom as prenom, COUNT(e.id) as total,
                     SUM(CASE WHEN e.status = :termine THEN 1 ELSE 0 END) as terminees,
                     AVG(TIMESTAMPDIFF(MINUTE, e.dateEvacuation, e.dateArrivee)) as tempsMoyen')
            ->where('e.brigade = :brigade')
            ->andWhere('u.id IS NOT NULL')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('brigade', $brigade)
            ->setParameter('termine', Evacuation::STATUS_TERMINE)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('u.id', 'u.nom', 'u.prenom')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        // Pics horaires
        $picsHoraires = $this->evacuationRepository->createQueryBuilder('e')
            ->select('HOUR(e.dateEvacuation) as hour, COUNT(e.id) as count')
            ->where('e.brigade = :brigade')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('brigade', $brigade)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('hour')
            ->orderBy('hour', 'ASC')
            ->getQuery()
            ->getResult();

        // Taux de résolution
        $tauxResolution = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.status, COUNT(e.id) as count')
            ->where('e.brigade = :brigade')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('brigade', $brigade)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('e.status')
            ->getQuery()
            ->getResult();

        // Performance par type
        $performanceType = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.typeEvacuation, COUNT(e.id) as total,
                     SUM(CASE WHEN e.status = :termine THEN 1 ELSE 0 END) as terminees,
                     AVG(TIMESTAMPDIFF(MINUTE, e.dateEvacuation, e.dateArrivee)) as tempsMoyen')
            ->where('e.brigade = :brigade')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('brigade', $brigade)
            ->setParameter('termine', Evacuation::STATUS_TERMINE)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('e.typeEvacuation')
            ->getQuery()
            ->getResult();

        return $this->render('brigade/evacuations/statistiques.html.twig', [
            'brigade' => $brigade,
            'evolutionTemporelle' => $evolutionTemporelle,
            'performanceOperateurs' => $performanceOperateurs,
            'picsHoraires' => $picsHoraires,
            'tauxResolution' => $tauxResolution,
            'performanceType' => $performanceType,
            'period' => $period,
        ]);
    }

    #[Route('/export', name: 'app_brigade_evacuations_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        $format = $request->query->get('format', 'csv');
        $period = $request->query->get('period', '1'); // 1 mois par défaut
        $dateLimit = new \DateTimeImmutable("-{$period} month");
        
        $evacuations = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.createdBy', 'u')
            ->addSelect('a', 'u')
            ->where('e.brigade = :brigade')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('brigade', $brigade)
            ->setParameter('dateLimit', $dateLimit)
            ->orderBy('e.dateEvacuation', 'DESC')
            ->getQuery()
            ->getResult();

        if ($format === 'csv') {
            $csv = "Référence;Date;Type;Statut;Urgence;Hôpital destination;Opérateur;Accident;Date arrivée;Durée (min)\n";
            
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
                $csv .= ($evacuation->getCreatedBy() ? $evacuation->getCreatedBy()->getNom() . ' ' . $evacuation->getCreatedBy()->getPrenom() : '') . ';';
                $csv .= ($evacuation->getAccident() ? $evacuation->getAccident()->getReference() : '') . ';';
                $csv .= ($evacuation->getDateArrivee() ? $evacuation->getDateArrivee()->format('d/m/Y H:i') : '') . ';';
                $csv .= $duree . "\n";
            }

            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="evacuations_' . $brigade->getLibelle() . '_' . date('Y-m-d') . '.csv"');
            return $response;
        }

        $this->addFlash('error', 'Format d\'export non supporté.');
        return $this->redirectToRoute('app_brigade_evacuations_dashboard');
    }

    private function generateReference(): string
    {
        $date = new \DateTime();
        $year = $date->format('Y');
        $month = $date->format('m');
        
        // Compter le nombre d'évacuations ce mois pour la brigade
        $brigade = $this->getUserBrigade();
        $count = $this->evacuationRepository->createQueryBuilder('e')
            ->where('e.brigade = :brigade')
            ->andWhere('DATE_FORMAT(e.dateEvacuation, \'%Y%m\') = :period')
            ->setParameter('brigade', $brigade)
            ->setParameter('period', $year . $month)
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        return 'EVA-' . $brigade->getCode() . '-' . $year . $month . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }
}
