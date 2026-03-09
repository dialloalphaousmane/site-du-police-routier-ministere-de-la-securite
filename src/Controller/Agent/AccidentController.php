<?php

namespace App\Controller\Agent;

use App\Entity\Accident;
use App\Entity\AccidentVictim;
use App\Entity\AccidentVehicle;
use App\Entity\Brigade;
use App\Entity\User;
use App\Form\AccidentType;
use App\Repository\AccidentRepository;
use App\Repository\AccidentVictimRepository;
use App\Repository\AccidentVehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/agent/accidents')]
#[IsGranted('ROLE_AGENT')]
class AccidentController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private AccidentRepository $accidentRepository;
    private AccidentVictimRepository $victimRepository;
    private AccidentVehicleRepository $vehicleRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        AccidentRepository $accidentRepository,
        AccidentVictimRepository $victimRepository,
        AccidentVehicleRepository $vehicleRepository
    ) {
        $this->entityManager = $entityManager;
        $this->accidentRepository = $accidentRepository;
        $this->victimRepository = $victimRepository;
        $this->vehicleRepository = $vehicleRepository;
    }

    private function getUserBrigade(): ?Brigade
    {
        /** @var User $user */
        $user = $this->getUser();
        return $user->getBrigade();
    }

    #[Route('/', name: 'app_agent_accidents_dashboard', methods: ['GET'])]
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
        $totalAccidents = $this->accidentRepository->count(['createdBy' => $user]);
        $accidentsEnCours = $this->accidentRepository->count(['createdBy' => $user, 'status' => Accident::STATUS_EN_COURS]);
        $accidentsMortels = $this->accidentRepository->count(['createdBy' => $user, 'gravite' => Accident::GRAVITY_MORTEL]);
        $accidentsGraves = $this->accidentRepository->count(['createdBy' => $user, 'gravite' => Accident::GRAVITY_GRAVE]);
        $accidentsEvacuation = $this->accidentRepository->count(['createdBy' => $user, 'status' => Accident::STATUS_EVACUATION]);

        // Accidents des dernières 24h de l'agent
        $date24h = new \DateTimeImmutable('-24 hours');
        $accidents24h = $this->accidentRepository->createQueryBuilder('a')
            ->where('a.createdBy = :user')
            ->andWhere('a.dateAccident >= :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date24h)
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Évolution mensuelle personnelle (6 derniers mois)
        $evolutionMensuelle = $this->accidentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id) as count, DATE_FORMAT(a.dateAccident, \'%Y-%m\') as month, MONTHNAME(a.dateAccident) as monthName')
            ->where('a.createdBy = :user')
            ->andWhere('a.dateAccident >= :date')
            ->setParameter('user', $user)
            ->setParameter('date', new \DateTimeImmutable('-6 months'))
            ->groupBy('month', 'monthName')
            ->orderBy('month', 'DESC')
            ->getQuery()
            ->getResult();

        // Répartition par gravité personnelle
        $repartitionGravite = $this->accidentRepository->createQueryBuilder('a')
            ->select('a.gravite, COUNT(a.id) as count')
            ->where('a.createdBy = :user')
            ->setParameter('user', $user)
            ->groupBy('a.gravite')
            ->getQuery()
            ->getResult();

        // Répartition par cause personnelle
        $repartitionCauses = [];
        foreach (Accident::CAUSES as $cause => $label) {
            $count = $this->accidentRepository->createQueryBuilder('a')
                ->where('a.createdBy = :user')
                ->andWhere('a.causePrincipale = :cause')
                ->setParameter('user', $user)
                ->setParameter('cause', $cause)
                ->select('COUNT(a.id)')
                ->getQuery()
                ->getSingleScalarResult();
            $repartitionCauses[$label] = $count;
        }
        arsort($repartitionCauses);

        // Derniers accidents graves de l'agent
        $derniersAccidentsGraves = $this->accidentRepository->createQueryBuilder('a')
            ->where('a.createdBy = :user')
            ->andWhere('a.gravite IN (:gravites)')
            ->setParameter('gravites', [Accident::GRAVITY_MORTEL, Accident::GRAVITY_GRAVE])
            ->orderBy('a.dateAccident', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Accidents en attente de traitement de l'agent
        $accidentsEnAttente = $this->accidentRepository->createQueryBuilder('a')
            ->where('a.createdBy = :user')
            ->andWhere('a.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Accident::STATUS_EN_COURS)
            ->orderBy('a.dateAccident', 'ASC')
            ->setMaxResults(15)
            ->getQuery()
            ->getResult();

        // Statistiques des victimes de l'agent
        $totalVictimes = $this->victimRepository->createQueryBuilder('v')
            ->join('v.accident', 'a')
            ->where('a.createdBy = :user')
            ->setParameter('user', $user)
            ->select('COUNT(v.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $victimesMortelles = $this->victimRepository->createQueryBuilder('v')
            ->join('v.accident', 'a')
            ->where('a.createdBy = :user')
            ->andWhere('v.gravite = :gravite')
            ->setParameter('user', $user)
            ->setParameter('gravite', AccidentVictim::GRAVITY_MORTEL)
            ->select('COUNT(v.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $victimesBlessesGraves = $this->victimRepository->createQueryBuilder('v')
            ->join('v.accident', 'a')
            ->where('a.createdBy = :user')
            ->andWhere('v.gravite = :gravite')
            ->setParameter('user', $user)
            ->setParameter('gravite', AccidentVictim::GRAVITY_BLESSE_GRAVE)
            ->select('COUNT(v.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Performance personnelle (temps moyen de traitement)
        $tempsMoyenTraitement = $this->accidentRepository->createQueryBuilder('a')
            ->where('a.createdBy = :user')
            ->andWhere('a.status IN (:statuses)')
            ->andWhere('a.dateAccident >= :date')
            ->setParameter('user', $user)
            ->setParameter('statuses', [Accident::STATUS_TRAITE, Accident::STATUS_ARCHIVE])
            ->setParameter('date', new \DateTimeImmutable('-30 days'))
            ->select('AVG(TIMESTAMPDIFF(MINUTE, a.dateAccident, COALESCE(a.dateValidation, a.dateAccident)))')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('agent/accidents/dashboard.html.twig', [
            'user' => $user,
            'brigade' => $brigade,
            'totalAccidents' => $totalAccidents,
            'accidentsEnCours' => $accidentsEnCours,
            'accidentsMortels' => $accidentsMortels,
            'accidentsGraves' => $accidentsGraves,
            'accidentsEvacuation' => $accidentsEvacuation,
            'accidents24h' => $accidents24h,
            'evolutionMensuelle' => $evolutionMensuelle,
            'repartitionGravite' => $repartitionGravite,
            'repartitionCauses' => $repartitionCauses,
            'derniersAccidentsGraves' => $derniersAccidentsGraves,
            'accidentsEnAttente' => $accidentsEnAttente,
            'totalVictimes' => $totalVictimes,
            'victimesMortelles' => $victimesMortelles,
            'victimesBlessesGraves' => $victimesBlessesGraves,
            'tempsMoyenTraitement' => round($tempsMoyenTraitement ?? 0, 2),
        ]);
    }

    #[Route('/new', name: 'app_agent_accidents_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        /** @var User $user */
        $user = $this->getUser();
        $accident = new Accident();
        
        $form = $this->createForm(AccidentType::class, $accident);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Assignation automatique
            $accident->setBrigade($brigade);
            $accident->setCreatedBy($this->getUser());
            $accident->setReference($this->generateReference());
            
            $this->entityManager->persist($accident);
            $this->entityManager->flush();

            $this->addFlash('success', 'Accident déclaré avec succès.');
            return $this->redirectToRoute('app_agent_accidents_show', ['id' => $accident->getId()]);
        }

        return $this->render('agent/accidents/new.html.twig', [
            'user' => $user,
            'brigade' => $brigade,
            'accident' => $accident,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mes-accidents', name: 'app_agent_accidents_liste', methods: ['GET'])]
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
        $gravity = $request->query->get('gravity');
        $search = $request->query->get('search');

        $qb = $this->accidentRepository->createQueryBuilder('a')
            ->where('a.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('a.dateAccident', 'DESC');

        // Filtres
        if ($status) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        if ($gravity) {
            $qb->andWhere('a.gravite = :gravity')
               ->setParameter('gravity', $gravity);
        }

        if ($search) {
            $qb->andWhere('a.reference LIKE :search OR a.localisation LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $total = count($qb->getQuery()->getResult());
        $accidents = $qb->setMaxResults($limit)
                        ->setFirstResult(($page - 1) * $limit)
                        ->getQuery()
                        ->getResult();

        return $this->render('agent/accidents/liste.html.twig', [
            'user' => $user,
            'brigade' => $brigade,
            'accidents' => $accidents,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit),
            'currentFilters' => [
                'status' => $status,
                'gravity' => $gravity,
                'search' => $search
            ]
        ]);
    }

    #[Route('/carte', name: 'app_agent_accidents_carte', methods: ['GET'])]
    public function carte(Request $request): Response
    {
        $brigade = $this->getUserBrigade();

        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        /** @var User $user */
        $user = $this->getUser();

        $period = $request->query->get('period', '7');
        $gravity = $request->query->get('gravity');

        $dateLimit = new \DateTimeImmutable("-{$period} days");

        $qb = $this->accidentRepository->createQueryBuilder('a')
            ->where('a.createdBy = :user')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('user', $user)
            ->setParameter('dateLimit', $dateLimit);

        if ($gravity) {
            $qb->andWhere('a.gravite = :gravity')
                ->setParameter('gravity', $gravity);
        }

        $accidents = $qb->orderBy('a.dateAccident', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('agent/accidents/carte.html.twig', [
            'user' => $user,
            'brigade' => $brigade,
            'accidents' => $accidents,
            'period' => $period,
            'currentFilters' => [
                'gravity' => $gravity,
            ],
        ]);
    }

    #[Route('/{id}', name: 'app_agent_accidents_show', methods: ['GET'])]
    public function show(Accident $accident): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($accident->getCreatedBy() !== $user) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_agent_accidents_dashboard');
        }

        $victims = $this->victimRepository->findBy(['accident' => $accident], ['id' => 'ASC']);
        $vehicles = $this->vehicleRepository->findBy(['accident' => $accident], ['id' => 'ASC']);

        return $this->render('agent/accidents/show.html.twig', [
            'user' => $user,
            'accident' => $accident,
            'victims' => $victims,
            'vehicles' => $vehicles,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_agent_accidents_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Accident $accident): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($accident->getCreatedBy() !== $user) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_agent_accidents_dashboard');
        }

        // Seuls les accidents en cours peuvent être modifiés
        if ($accident->getStatus() !== Accident::STATUS_EN_COURS) {
            $this->addFlash('error', 'Cet accident ne peut plus être modifié.');
            return $this->redirectToRoute('app_agent_accidents_show', ['id' => $accident->getId()]);
        }

        $form = $this->createForm(AccidentType::class, $accident);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Accident modifié avec succès.');
            return $this->redirectToRoute('app_agent_accidents_show', ['id' => $accident->getId()]);
        }

        return $this->render('agent/accidents/edit.html.twig', [
            'user' => $user,
            'accident' => $accident,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/victims', name: 'app_agent_accidents_victims', methods: ['GET', 'POST'])]
    public function victims(Request $request, Accident $accident): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($accident->getCreatedBy() !== $user) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_agent_accidents_dashboard');
        }

        $victims = $this->victimRepository->findBy(['accident' => $accident], ['id' => 'ASC']);

        return $this->render('agent/accidents/victims.html.twig', [
            'user' => $user,
            'accident' => $accident,
            'victims' => $victims,
        ]);
    }

    #[Route('/{id}/vehicles', name: 'app_agent_accidents_vehicles', methods: ['GET', 'POST'])]
    public function vehicles(Request $request, Accident $accident): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($accident->getCreatedBy() !== $user) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_agent_accidents_dashboard');
        }

        $vehicles = $this->vehicleRepository->findBy(['accident' => $accident], ['id' => 'ASC']);

        return $this->render('agent/accidents/vehicles.html.twig', [
            'user' => $user,
            'accident' => $accident,
            'vehicles' => $vehicles,
        ]);
    }

    #[Route('/{id}/victim/add', name: 'app_agent_accidents_victim_add', methods: ['GET', 'POST'])]
    public function addVictim(Request $request, Accident $accident): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($accident->getCreatedBy() !== $user) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_agent_accidents_dashboard');
        }

        // Seuls les accidents en cours peuvent recevoir des victimes
        if ($accident->getStatus() !== Accident::STATUS_EN_COURS) {
            $this->addFlash('error', 'Impossible d\'ajouter une victime à cet accident.');
            return $this->redirectToRoute('app_agent_accidents_show', ['id' => $accident->getId()]);
        }

        $victim = new AccidentVictim();
        $victim->setAccident($accident);
        
        $form = $this->createForm(\App\Form\AccidentVictimType::class, $victim);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($victim);
            $this->entityManager->flush();
            $this->addFlash('success', 'Victime ajoutée avec succès.');
            return $this->redirectToRoute('app_agent_accidents_victims', ['id' => $accident->getId()]);
        }

        return $this->render('agent/accidents/victim_add.html.twig', [
            'user' => $user,
            'accident' => $accident,
            'victim' => $victim,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/vehicle/add', name: 'app_agent_accidents_vehicle_add', methods: ['GET', 'POST'])]
    public function addVehicle(Request $request, Accident $accident): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($accident->getCreatedBy() !== $user) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_agent_accidents_dashboard');
        }

        // Seuls les accidents en cours peuvent recevoir des véhicules
        if ($accident->getStatus() !== Accident::STATUS_EN_COURS) {
            $this->addFlash('error', 'Impossible d\'ajouter un véhicule à cet accident.');
            return $this->redirectToRoute('app_agent_accidents_show', ['id' => $accident->getId()]);
        }

        $vehicle = new AccidentVehicle();
        $vehicle->setAccident($accident);
        
        $form = $this->createForm(\App\Form\AccidentVehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($vehicle);
            $this->entityManager->flush();
            $this->addFlash('success', 'Véhicule ajouté avec succès.');
            return $this->redirectToRoute('app_agent_accidents_vehicles', ['id' => $accident->getId()]);
        }

        return $this->render('agent/accidents/vehicle_add.html.twig', [
            'user' => $user,
            'accident' => $accident,
            'vehicle' => $vehicle,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/demander-evacuation', name: 'app_agent_accidents_evacuation', methods: ['GET', 'POST'])]
    public function evacuation(Request $request, Accident $accident): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($accident->getCreatedBy() !== $user) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_agent_accidents_dashboard');
        }

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            
            switch ($action) {
                case 'demander_evacuation':
                    $accident->setStatus(Accident::STATUS_EVACUATION);
                    $this->addFlash('success', 'Demande d\'évacuation envoyée.');
                    break;
                case 'finaliser':
                    $accident->setStatus(Accident::STATUS_TRAITE);
                    $this->addFlash('success', 'Accident finalisé.');
                    break;
            }
            
            $this->entityManager->flush();
            return $this->redirectToRoute('app_agent_accidents_show', ['id' => $accident->getId()]);
        }

        return $this->render('agent/accidents/evacuation.html.twig', [
            'user' => $user,
            'accident' => $accident,
        ]);
    }

    #[Route('/statistiques', name: 'app_agent_accidents_statistiques', methods: ['GET'])]
    public function statistiques(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $period = $request->query->get('period', '12'); // 12 mois par défaut
        $dateLimit = new \DateTimeImmutable("-{$period} months");

        // Évolution temporelle personnelle
        $evolutionTemporelle = $this->accidentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id) as count, DATE_FORMAT(a.dateAccident, \'%Y-%m\') as month')
            ->where('a.createdBy = :user')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('user', $user)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        // Pics horaires personnels
        $picsHoraires = $this->accidentRepository->createQueryBuilder('a')
            ->select('HOUR(a.dateAccident) as hour, COUNT(a.id) as count')
            ->where('a.createdBy = :user')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('user', $user)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('hour')
            ->orderBy('hour', 'ASC')
            ->getQuery()
            ->getResult();

        // Taux de résolution personnel
        $tauxResolution = $this->accidentRepository->createQueryBuilder('a')
            ->select('a.status, COUNT(a.id) as count')
            ->where('a.createdBy = :user')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('user', $user)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('a.status')
            ->getQuery()
            ->getResult();

        // Performance mensuelle
        $performanceMensuelle = $this->accidentRepository->createQueryBuilder('a')
            ->select('DATE_FORMAT(a.dateAccident, \'%Y-%m\') as month, COUNT(a.id) as total,
                     SUM(CASE WHEN a.status = :traite THEN 1 ELSE 0 END) as traites')
            ->where('a.createdBy = :user')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('user', $user)
            ->setParameter('traite', Accident::STATUS_TRAITE)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('month')
            ->orderBy('month', 'DESC')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();

        return $this->render('agent/accidents/statistiques.html.twig', [
            'user' => $user,
            'evolutionTemporelle' => $evolutionTemporelle,
            'picsHoraires' => $picsHoraires,
            'tauxResolution' => $tauxResolution,
            'performanceMensuelle' => $performanceMensuelle,
            'period' => $period,
        ]);
    }

    #[Route('/export', name: 'app_agent_accidents_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $format = $request->query->get('format', 'csv');
        $period = $request->query->get('period', '1'); // 1 mois par défaut
        $dateLimit = new \DateTimeImmutable("-{$period} month");
        
        $accidents = $this->accidentRepository->createQueryBuilder('a')
            ->where('a.createdBy = :user')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('user', $user)
            ->setParameter('dateLimit', $dateLimit)
            ->orderBy('a.dateAccident', 'DESC')
            ->getQuery()
            ->getResult();

        if ($format === 'csv') {
            $csv = "Référence;Date;Localisation;Statut;Gravité;Cause;Description\n";
            
            foreach ($accidents as $accident) {
                $csv .= $accident->getReference() . ';';
                $csv .= $accident->getDateAccident()->format('d/m/Y H:i') . ';';
                $csv .= ($accident->getLocalisation() ?? '') . ';';
                $csv .= $accident->getStatus() . ';';
                $csv .= $accident->getGravite() . ';';
                $csv .= (Accident::CAUSES[$accident->getCause()] ?? $accident->getCause()) . ';';
                $csv .= str_replace(';', ',', ($accident->getDescription() ?? '')) . "\n";
            }

            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="accidents_' . $user->getNom() . '_' . date('Y-m-d') . '.csv"');
            return $response;
        }

        $this->addFlash('error', 'Format d\'export non supporté.');
        return $this->redirectToRoute('app_agent_accidents_dashboard');
    }

    private function generateReference(): string
    {
        $date = new \DateTime();
        $year = $date->format('Y');
        $month = $date->format('m');
        
        // Compter le nombre d'accidents ce mois pour l'agent
        /** @var User $user */
        $user = $this->getUser();
        $count = $this->accidentRepository->createQueryBuilder('a')
            ->where('a.createdBy = :user')
            ->andWhere('DATE_FORMAT(a.dateAccident, \'%Y%m\') = :period')
            ->setParameter('user', $user)
            ->setParameter('period', $year . $month)
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        return 'ACC-' . $user->getId() . '-' . $year . $month . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }
}
