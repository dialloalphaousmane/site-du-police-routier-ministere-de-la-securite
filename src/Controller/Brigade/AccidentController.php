<?php

namespace App\Controller\Brigade;

use App\Entity\Accident;
use App\Entity\AccidentVictim;
use App\Entity\AccidentVehicle;
use App\Entity\Brigade;
use App\Entity\User;
use App\Form\AccidentType;
use App\Form\AccidentVictimType;
use App\Form\AccidentVehicleType;
use App\Repository\AccidentRepository;
use App\Repository\AccidentVictimRepository;
use App\Repository\AccidentVehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/brigade/accidents')]
#[IsGranted('ROLE_CHEF_BRIGADE')]
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

    #[Route('/', name: 'app_brigade_accidents_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Statistiques de la brigade en temps réel
        $totalAccidents = $this->accidentRepository->count(['brigade' => $brigade]);
        $accidentsEnCours = $this->accidentRepository->count(['brigade' => $brigade, 'status' => Accident::STATUS_EN_COURS]);
        $accidentsMortels = $this->accidentRepository->count(['brigade' => $brigade, 'gravite' => Accident::GRAVITY_MORTEL]);
        $accidentsGraves = $this->accidentRepository->count(['brigade' => $brigade, 'gravite' => Accident::GRAVITY_GRAVE]);
        $accidentsEvacuation = $this->accidentRepository->count(['brigade' => $brigade, 'status' => Accident::STATUS_EVACUATION]);

        // Accidents des dernières 24h de la brigade
        $date24h = new \DateTimeImmutable('-24 hours');
        $accidents24h = $this->accidentRepository->createQueryBuilder('a')
            ->where('a.brigade = :brigade')
            ->andWhere('a.dateAccident >= :date')
            ->setParameter('brigade', $brigade)
            ->setParameter('date', $date24h)
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Évolution mensuelle (6 derniers mois)
        $evolutionMensuelle = $this->accidentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id) as count, DATE_FORMAT(a.dateAccident, \'%Y-%m\') as month, MONTHNAME(a.dateAccident) as monthName')
            ->where('a.brigade = :brigade')
            ->andWhere('a.dateAccident >= :date')
            ->setParameter('brigade', $brigade)
            ->setParameter('date', new \DateTimeImmutable('-6 months'))
            ->groupBy('month', 'monthName')
            ->orderBy('month', 'DESC')
            ->getQuery()
            ->getResult();

        // Répartition par gravité
        $repartitionGravite = $this->accidentRepository->createQueryBuilder('a')
            ->select('a.gravite, COUNT(a.id) as count')
            ->where('a.brigade = :brigade')
            ->setParameter('brigade', $brigade)
            ->groupBy('a.gravite')
            ->getQuery()
            ->getResult();

        // Répartition par cause
        $repartitionCauses = [];
        foreach (Accident::CAUSES as $cause => $label) {
            $count = $this->accidentRepository->createQueryBuilder('a')
                ->where('a.brigade = :brigade')
                ->andWhere('a.causePrincipale = :cause')
                ->setParameter('brigade', $brigade)
                ->setParameter('cause', $cause)
                ->select('COUNT(a.id)')
                ->getQuery()
                ->getSingleScalarResult();
            $repartitionCauses[$label] = $count;
        }
        arsort($repartitionCauses);

        // Derniers accidents graves de la brigade
        $derniersAccidentsGraves = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.createdBy', 'u')
            ->addSelect('u')
            ->where('a.brigade = :brigade')
            ->andWhere('a.gravite IN (:gravites)')
            ->setParameter('brigade', $brigade)
            ->setParameter('gravites', [Accident::GRAVITY_MORTEL, Accident::GRAVITY_GRAVE])
            ->orderBy('a.dateAccident', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Accidents en attente de traitement de la brigade
        $accidentsEnAttente = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.createdBy', 'u')
            ->addSelect('u')
            ->where('a.brigade = :brigade')
            ->andWhere('a.status = :status')
            ->setParameter('brigade', $brigade)
            ->setParameter('status', Accident::STATUS_EN_COURS)
            ->orderBy('a.dateAccident', 'ASC')
            ->setMaxResults(15)
            ->getQuery()
            ->getResult();

        // Statistiques des victimes de la brigade
        $totalVictimes = $this->victimRepository->createQueryBuilder('v')
            ->join('v.accident', 'a')
            ->where('a.brigade = :brigade')
            ->setParameter('brigade', $brigade)
            ->select('COUNT(v.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $victimesMortelles = $this->victimRepository->createQueryBuilder('v')
            ->join('v.accident', 'a')
            ->where('a.brigade = :brigade')
            ->andWhere('v.gravite = :gravite')
            ->setParameter('brigade', $brigade)
            ->setParameter('gravite', AccidentVictim::GRAVITY_MORTEL)
            ->select('COUNT(v.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $victimesBlessesGraves = $this->victimRepository->createQueryBuilder('v')
            ->join('v.accident', 'a')
            ->where('a.brigade = :brigade')
            ->andWhere('v.gravite = :gravite')
            ->setParameter('brigade', $brigade)
            ->setParameter('gravite', AccidentVictim::GRAVITY_BLESSE_GRAVE)
            ->select('COUNT(v.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Performance des agents de la brigade
        $performanceAgents = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.createdBy', 'u')
            ->select('u.nom as nom, u.prenom as prenom, COUNT(a.id) as total,
                     SUM(CASE WHEN a.status = :traite THEN 1 ELSE 0 END) as traites')
            ->where('a.brigade = :brigade')
            ->andWhere('u.id IS NOT NULL')
            ->andWhere('a.dateAccident >= :date')
            ->setParameter('brigade', $brigade)
            ->setParameter('traite', Accident::STATUS_TRAITE)
            ->setParameter('date', new \DateTimeImmutable('-30 days'))
            ->groupBy('u.id', 'u.nom', 'u.prenom')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('brigade/accidents/dashboard.html.twig', [
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
            'performanceAgents' => $performanceAgents,
        ]);
    }

    #[Route('/new', name: 'app_brigade_accidents_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

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
            return $this->redirectToRoute('app_brigade_accidents_show', ['id' => $accident->getId()]);
        }

        return $this->render('brigade/accidents/new.html.twig', [
            'brigade' => $brigade,
            'accident' => $accident,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/liste', name: 'app_brigade_accidents_liste', methods: ['GET'])]
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
        $gravity = $request->query->get('gravity');
        $agent = $request->query->get('agent');
        $search = $request->query->get('search');

        $qb = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.createdBy', 'u')
            ->addSelect('u')
            ->where('a.brigade = :brigade')
            ->setParameter('brigade', $brigade)
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

        if ($agent) {
            $qb->andWhere('u.id = :agent')
               ->setParameter('agent', $agent);
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

        // Agents de la brigade pour le filtre
        $agents = $this->entityManager->getRepository(User::class)->findBy(['brigade' => $brigade]);

        return $this->render('brigade/accidents/liste.html.twig', [
            'brigade' => $brigade,
            'accidents' => $accidents,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit),
            'agents' => $agents,
            'currentFilters' => [
                'status' => $status,
                'gravity' => $gravity,
                'agent' => $agent,
                'search' => $search
            ]
        ]);
    }

    #[Route('/carte', name: 'app_brigade_accidents_carte', methods: ['GET'])]
    public function carte(Request $request): Response
    {
        $brigade = $this->getUserBrigade();

        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        $period = $request->query->get('period', '7');
        $gravity = $request->query->get('gravity');

        $dateLimit = new \DateTimeImmutable("-{$period} days");

        $qb = $this->accidentRepository->createQueryBuilder('a')
            ->where('a.brigade = :brigade')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('brigade', $brigade)
            ->setParameter('dateLimit', $dateLimit);

        if ($gravity) {
            $qb->andWhere('a.gravite = :gravity')
                ->setParameter('gravity', $gravity);
        }

        $accidents = $qb->orderBy('a.dateAccident', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('brigade/accidents/carte.html.twig', [
            'brigade' => $brigade,
            'accidents' => $accidents,
            'period' => $period,
            'currentFilters' => [
                'gravity' => $gravity,
            ],
        ]);
    }

    public function __invoke(): Response
    {
        return $this->redirectToRoute('app_brigade_accidents_dashboard');
    }

    #[Route('/{id}', name: 'app_brigade_accidents_show', methods: ['GET'])]
    public function show(Accident $accident): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade || $accident->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_brigade_accidents_dashboard');
        }

        $victims = $this->victimRepository->findBy(['accident' => $accident], ['id' => 'ASC']);
        $vehicles = $this->vehicleRepository->findBy(['accident' => $accident], ['id' => 'ASC']);

        return $this->render('brigade/accidents/show.html.twig', [
            'brigade' => $brigade,
            'accident' => $accident,
            'victims' => $victims,
            'vehicles' => $vehicles,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_brigade_accidents_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Accident $accident): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade || $accident->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_brigade_accidents_dashboard');
        }

        // Seuls les accidents en cours peuvent être modifiés
        if ($accident->getStatus() !== Accident::STATUS_EN_COURS) {
            $this->addFlash('error', 'Cet accident ne peut plus être modifié.');
            return $this->redirectToRoute('app_brigade_accidents_show', ['id' => $accident->getId()]);
        }

        $form = $this->createForm(AccidentType::class, $accident);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Accident modifié avec succès.');
            return $this->redirectToRoute('app_brigade_accidents_show', ['id' => $accident->getId()]);
        }

        return $this->render('brigade/accidents/edit.html.twig', [
            'brigade' => $brigade,
            'accident' => $accident,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/victims', name: 'app_brigade_accidents_victims', methods: ['GET', 'POST'])]
    public function victims(Request $request, Accident $accident): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade || $accident->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_brigade_accidents_dashboard');
        }

        $victims = $this->victimRepository->findBy(['accident' => $accident], ['id' => 'ASC']);

        return $this->render('brigade/accidents/victims.html.twig', [
            'brigade' => $brigade,
            'accident' => $accident,
            'victims' => $victims,
        ]);
    }

    #[Route('/{id}/vehicles', name: 'app_brigade_accidents_vehicles', methods: ['GET', 'POST'])]
    public function vehicles(Request $request, Accident $accident): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade || $accident->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_brigade_accidents_dashboard');
        }

        $vehicles = $this->vehicleRepository->findBy(['accident' => $accident], ['id' => 'ASC']);

        return $this->render('brigade/accidents/vehicles.html.twig', [
            'brigade' => $brigade,
            'accident' => $accident,
            'vehicles' => $vehicles,
        ]);
    }

    #[Route('/{id}/victim/add', name: 'app_brigade_accidents_victim_add', methods: ['GET', 'POST'])]
    public function addVictim(Request $request, Accident $accident): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade || $accident->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_brigade_accidents_dashboard');
        }

        // Seuls les accidents en cours peuvent recevoir des victimes
        if ($accident->getStatus() !== Accident::STATUS_EN_COURS) {
            $this->addFlash('error', 'Impossible d\'ajouter une victime à cet accident.');
            return $this->redirectToRoute('app_brigade_accidents_show', ['id' => $accident->getId()]);
        }

        $victim = new AccidentVictim();
        $victim->setAccident($accident);
        
        $form = $this->createForm(AccidentVictimType::class, $victim);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($victim);
            $this->entityManager->flush();
            $this->addFlash('success', 'Victime ajoutée avec succès.');
            return $this->redirectToRoute('app_brigade_accidents_victims', ['id' => $accident->getId()]);
        }

        return $this->render('brigade/accidents/victim_add.html.twig', [
            'brigade' => $brigade,
            'accident' => $accident,
            'victim' => $victim,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/vehicle/add', name: 'app_brigade_accidents_vehicle_add', methods: ['GET', 'POST'])]
    public function addVehicle(Request $request, Accident $accident): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade || $accident->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_brigade_accidents_dashboard');
        }

        // Seuls les accidents en cours peuvent recevoir des véhicules
        if ($accident->getStatus() !== Accident::STATUS_EN_COURS) {
            $this->addFlash('error', 'Impossible d\'ajouter un véhicule à cet accident.');
            return $this->redirectToRoute('app_brigade_accidents_show', ['id' => $accident->getId()]);
        }

        $vehicle = new AccidentVehicle();
        $vehicle->setAccident($accident);
        
        $form = $this->createForm(AccidentVehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($vehicle);
            $this->entityManager->flush();
            $this->addFlash('success', 'Véhicule ajouté avec succès.');
            return $this->redirectToRoute('app_brigade_accidents_vehicles', ['id' => $accident->getId()]);
        }

        return $this->render('brigade/accidents/vehicle_add.html.twig', [
            'brigade' => $brigade,
            'accident' => $accident,
            'vehicle' => $vehicle,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/evacuation', name: 'app_brigade_accidents_evacuation', methods: ['GET', 'POST'])]
    public function evacuation(Request $request, Accident $accident): Response
    {
        $brigade = $this->getUserBrigade();
        
        if (!$brigade || $accident->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_brigade_accidents_dashboard');
        }

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            
            switch ($action) {
                case 'demander_evacuation':
                    $accident->setStatus(Accident::STATUS_EVACUATION);
                    $this->addFlash('success', 'Demande d\'évacuation envoyée.');
                    break;
                case 'traiter':
                    $accident->setStatus(Accident::STATUS_TRAITE);
                    $this->addFlash('success', 'Accident marqué comme traité.');
                    break;
            }
            
            $this->entityManager->flush();
            return $this->redirectToRoute('app_brigade_accidents_show', ['id' => $accident->getId()]);
        }

        return $this->render('brigade/accidents/evacuation.html.twig', [
            'brigade' => $brigade,
            'accident' => $accident,
        ]);
    }

    #[Route('/statistiques', name: 'app_brigade_accidents_statistiques', methods: ['GET'])]
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
        $evolutionTemporelle = $this->accidentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id) as count, DATE_FORMAT(a.dateAccident, \'%Y-%m\') as month')
            ->where('a.brigade = :brigade')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('brigade', $brigade)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        // Performance des agents
        $performanceAgents = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.createdBy', 'u')
            ->select('u.nom as nom, u.prenom as prenom, COUNT(a.id) as total,
                     SUM(CASE WHEN a.status = :traite THEN 1 ELSE 0 END) as traites')
            ->where('a.brigade = :brigade')
            ->andWhere('u.id IS NOT NULL')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('brigade', $brigade)
            ->setParameter('traite', Accident::STATUS_TRAITE)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('u.id', 'u.nom', 'u.prenom')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        // Pics horaires
        $picsHoraires = $this->accidentRepository->createQueryBuilder('a')
            ->select('HOUR(a.dateAccident) as hour, COUNT(a.id) as count')
            ->where('a.brigade = :brigade')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('brigade', $brigade)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('hour')
            ->orderBy('hour', 'ASC')
            ->getQuery()
            ->getResult();

        // Taux de résolution
        $tauxResolution = $this->accidentRepository->createQueryBuilder('a')
            ->select('a.status, COUNT(a.id) as count')
            ->where('a.brigade = :brigade')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('brigade', $brigade)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('a.status')
            ->getQuery()
            ->getResult();

        // Performance par type
        $performanceType = $this->accidentRepository->createQueryBuilder('a')
            ->select('a.causePrincipale, COUNT(a.id) as total,
                     SUM(CASE WHEN a.status = :traite THEN 1 ELSE 0 END) as traites')
            ->where('a.brigade = :brigade')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('brigade', $brigade)
            ->setParameter('traite', Accident::STATUS_TRAITE)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('a.causePrincipale')
            ->getQuery()
            ->getResult();

        return $this->render('brigade/accidents/statistiques.html.twig', [
            'brigade' => $brigade,
            'evolutionTemporelle' => $evolutionTemporelle,
            'performanceAgents' => $performanceAgents,
            'picsHoraires' => $picsHoraires,
            'tauxResolution' => $tauxResolution,
            'performanceType' => $performanceType,
            'period' => $period,
        ]);
    }

    #[Route('/export', name: 'app_brigade_accidents_export', methods: ['GET'])]
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
        
        $accidents = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.createdBy', 'u')
            ->addSelect('u')
            ->where('a.brigade = :brigade')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('brigade', $brigade)
            ->setParameter('dateLimit', $dateLimit)
            ->orderBy('a.dateAccident', 'DESC')
            ->getQuery()
            ->getResult();

        if ($format === 'csv') {
            $csv = "Référence;Date;Localisation;Statut;Gravité;Cause;Déclarant;Description\n";
            
            foreach ($accidents as $accident) {
                $csv .= $accident->getReference() . ';';
                $csv .= $accident->getDateAccident()->format('d/m/Y H:i') . ';';
                $csv .= ($accident->getLocalisation() ?? '') . ';';
                $csv .= $accident->getStatus() . ';';
                $csv .= $accident->getGravite() . ';';
                $csv .= ($accident->getCausePrincipale() ? $accident->getCausePrincipaleLabel() : '') . ';';
                $csv .= ($accident->getCreatedBy() ? $accident->getCreatedBy()->getNom() . ' ' . $accident->getCreatedBy()->getPrenom() : '') . ';';
                $csv .= str_replace(';', ',', ($accident->getDescription() ?? '')) . "\n";
            }

            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="accidents_' . $brigade->getLibelle() . '_' . date('Y-m-d') . '.csv"');
            return $response;
        }

        $this->addFlash('error', 'Format d\'export non supporté.');
        return $this->redirectToRoute('app_brigade_accidents_dashboard');
    }

    #[Route('/{id}/valider', name: 'app_brigade_accidents_valider', methods: ['POST'])]
    public function valider(Request $request, Accident $accident): Response
    {
        $brigade = $this->getUserBrigade();

        if (!$brigade || $accident->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_brigade_accidents_dashboard');
        }

        if ($accident->getStatus() !== Accident::STATUS_EN_COURS) {
            $this->addFlash('error', 'Cet accident ne peut plus être validé.');
            return $this->redirectToRoute('app_brigade_accidents_show', ['id' => $accident->getId()]);
        }

        if ($accident->getDateValidationBrigade() !== null) {
            $this->addFlash('info', 'Cet accident est déjà validé par la brigade.');
            return $this->redirectToRoute('app_brigade_accidents_show', ['id' => $accident->getId()]);
        }

        if ($this->isCsrfTokenValid('valider' . $accident->getId(), (string) $request->request->get('_token'))) {
            $accident->setDateValidationBrigade(new \DateTimeImmutable());
            $accident->setValidatedByBrigade($this->getUser());
            $accident->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->flush();
            $this->addFlash('success', 'Accident validé par la brigade avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_brigade_accidents_show', ['id' => $accident->getId()]);
    }

    private function generateReference(): string
    {
        $date = new \DateTime();
        $year = $date->format('Y');
        $month = $date->format('m');
        
        // Compter le nombre d'accidents ce mois pour la brigade
        $brigade = $this->getUserBrigade();
        $count = $this->accidentRepository->createQueryBuilder('a')
            ->where('a.brigade = :brigade')
            ->andWhere('DATE_FORMAT(a.dateAccident, \'%Y%m\') = :period')
            ->setParameter('brigade', $brigade)
            ->setParameter('period', $year . $month)
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        return 'ACC-' . $brigade->getCode() . '-' . $year . $month . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }
}
