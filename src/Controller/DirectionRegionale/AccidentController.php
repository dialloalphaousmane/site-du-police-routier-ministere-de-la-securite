<?php

namespace App\Controller\DirectionRegionale;

use App\Entity\Accident;
use App\Entity\AccidentVictim;
use App\Entity\AccidentVehicle;
use App\Entity\Region;
use App\Entity\Brigade;
use App\Entity\User;
use App\Repository\AccidentRepository;
use App\Repository\AccidentVictimRepository;
use App\Repository\AccidentVehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dr/accidents')]
#[IsGranted('ROLE_DIRECTION_REGIONALE')]
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

    private function getUserRegion(): ?Region
    {
        /** @var User $user */
        $user = $this->getUser();
        return $user->getRegion();
    }

    #[Route('/', name: 'app_dr_accidents_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $region = $this->getUserRegion();
        
        if (!$region) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une région.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Statistiques régionales en temps réel
        $totalAccidents = $this->accidentRepository->count(['region' => $region]);
        $accidentsEnCours = $this->accidentRepository->count(['region' => $region, 'status' => Accident::STATUS_EN_COURS]);
        $accidentsMortels = $this->accidentRepository->count(['region' => $region, 'gravite' => Accident::GRAVITY_MORTEL]);
        $accidentsGraves = $this->accidentRepository->count(['region' => $region, 'gravite' => Accident::GRAVITY_GRAVE]);
        $accidentsEvacuation = $this->accidentRepository->count(['region' => $region, 'status' => Accident::STATUS_EVACUATION]);

        // Accidents des dernières 24h dans la région
        $date24h = new \DateTimeImmutable('-24 hours');
        $accidents24h = $this->accidentRepository->createQueryBuilder('a')
            ->where('a.region = :region')
            ->andWhere('a.dateAccident >= :date')
            ->setParameter('region', $region)
            ->setParameter('date', $date24h)
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Accidents par brigade dans la région
        $accidentsParBrigade = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.brigade', 'b')
            ->select('b.libelle as brigade, COUNT(a.id) as count')
            ->where('a.region = :region')
            ->andWhere('b.id IS NOT NULL')
            ->setParameter('region', $region)
            ->groupBy('b.id', 'b.libelle')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();

        // Évolution mensuelle (6 derniers mois)
        $evolutionMensuelle = $this->accidentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id) as count, DATE_FORMAT(a.dateAccident, \'%Y-%m\') as month, MONTHNAME(a.dateAccident) as monthName')
            ->where('a.region = :region')
            ->andWhere('a.dateAccident >= :date')
            ->setParameter('region', $region)
            ->setParameter('date', new \DateTimeImmutable('-6 months'))
            ->groupBy('month', 'monthName')
            ->orderBy('month', 'DESC')
            ->getQuery()
            ->getResult();

        // Répartition par gravité dans la région
        $repartitionGravite = $this->accidentRepository->createQueryBuilder('a')
            ->select('a.gravite, COUNT(a.id) as count')
            ->where('a.region = :region')
            ->setParameter('region', $region)
            ->groupBy('a.gravite')
            ->getQuery()
            ->getResult();

        // Répartition par cause dans la région
        $repartitionCauses = [];
        foreach (Accident::CAUSES as $cause => $label) {
            $count = $this->accidentRepository->createQueryBuilder('a')
                ->where('a.region = :region')
                ->andWhere('a.cause = :cause')
                ->setParameter('region', $region)
                ->setParameter('cause', $cause)
                ->select('COUNT(a.id)')
                ->getQuery()
                ->getSingleScalarResult();
            $repartitionCauses[$label] = $count;
        }
        arsort($repartitionCauses);

        // Derniers accidents graves dans la région
        $derniersAccidentsGraves = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.brigade', 'b')
            ->leftJoin('a.declarant', 'u')
            ->addSelect('b', 'u')
            ->where('a.region = :region')
            ->andWhere('a.gravite IN (:gravites)')
            ->setParameter('region', $region)
            ->setParameter('gravites', [Accident::GRAVITY_MORTEL, Accident::GRAVITY_GRAVE])
            ->orderBy('a.dateAccident', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Accidents en attente de traitement dans la région
        $accidentsEnAttente = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.brigade', 'b')
            ->addSelect('b')
            ->where('a.region = :region')
            ->andWhere('a.status = :status')
            ->setParameter('region', $region)
            ->setParameter('status', Accident::STATUS_EN_COURS)
            ->orderBy('a.dateAccident', 'ASC')
            ->setMaxResults(15)
            ->getQuery()
            ->getResult();

        // Statistiques des victimes dans la région
        $totalVictimes = $this->victimRepository->createQueryBuilder('v')
            ->join('v.accident', 'a')
            ->where('a.region = :region')
            ->setParameter('region', $region)
            ->select('COUNT(v.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $victimesMortelles = $this->victimRepository->createQueryBuilder('v')
            ->join('v.accident', 'a')
            ->where('a.region = :region')
            ->andWhere('v.gravite = :gravite')
            ->setParameter('region', $region)
            ->setParameter('gravite', AccidentVictim::GRAVITY_MORTEL)
            ->select('COUNT(v.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $victimesBlessesGraves = $this->victimRepository->createQueryBuilder('v')
            ->join('v.accident', 'a')
            ->where('a.region = :region')
            ->andWhere('v.gravite = :gravite')
            ->setParameter('region', $region)
            ->setParameter('gravite', AccidentVictim::GRAVITY_BLESSE_GRAVE)
            ->select('COUNT(v.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Performance des brigades
        $performanceBrigades = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.brigade', 'b')
            ->select('b.libelle as brigade, COUNT(a.id) as total,
                     SUM(CASE WHEN a.status = :traite THEN 1 ELSE 0 END) as traites')
            ->where('a.region = :region')
            ->andWhere('b.id IS NOT NULL')
            ->andWhere('a.dateAccident >= :date')
            ->setParameter('region', $region)
            ->setParameter('traite', Accident::STATUS_TRAITE)
            ->setParameter('date', new \DateTimeImmutable('-30 days'))
            ->groupBy('b.id', 'b.libelle')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('direction_regionale/accidents/dashboard.html.twig', [
            'region' => $region,
            'totalAccidents' => $totalAccidents,
            'accidentsEnCours' => $accidentsEnCours,
            'accidentsMortels' => $accidentsMortels,
            'accidentsGraves' => $accidentsGraves,
            'accidentsEvacuation' => $accidentsEvacuation,
            'accidents24h' => $accidents24h,
            'accidentsParBrigade' => $accidentsParBrigade,
            'evolutionMensuelle' => $evolutionMensuelle,
            'repartitionGravite' => $repartitionGravite,
            'repartitionCauses' => $repartitionCauses,
            'derniersAccidentsGraves' => $derniersAccidentsGraves,
            'accidentsEnAttente' => $accidentsEnAttente,
            'totalVictimes' => $totalVictimes,
            'victimesMortelles' => $victimesMortelles,
            'victimesBlessesGraves' => $victimesBlessesGraves,
            'performanceBrigades' => $performanceBrigades,
        ]);
    }

    #[Route('/liste', name: 'app_dr_accidents_liste', methods: ['GET'])]
    public function liste(Request $request): Response
    {
        $region = $this->getUserRegion();
        
        if (!$region) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une région.');
            return $this->redirectToRoute('app_dashboard');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $status = $request->query->get('status');
        $gravity = $request->query->get('gravity');
        $brigade = $request->query->get('brigade');
        $search = $request->query->get('search');

        $qb = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.brigade', 'b')
            ->leftJoin('a.declarant', 'u')
            ->addSelect('b', 'u')
            ->where('a.region = :region')
            ->setParameter('region', $region)
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

        if ($brigade) {
            $qb->andWhere('b.id = :brigade')
               ->setParameter('brigade', $brigade);
        }

        if ($search) {
            $qb->andWhere('a.reference LIKE :search OR a.localisation LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $total = count($qb->getQuery()->getResult());
        $accidents = $qb->setMaxResults($limit)
                        ->setFirstResult(($page - 1) * $limit)
                        ->getQuery()->getResult();

        $brigades = $this->entityManager->getRepository(Brigade::class)->findBy(['region' => $region]);

        return $this->render('direction_regionale/accidents/liste.html.twig', [
            'region' => $region,
            'accidents' => $accidents,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit),
            'brigades' => $brigades,
            'currentFilters' => [
                'status' => $status,
                'gravity' => $gravity,
                'brigade' => $brigade,
                'search' => $search
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_dr_accidents_show', methods: ['GET'])]
    public function show(Accident $accident): Response
    {
        $region = $this->getUserRegion();
        
        if (!$region || $accident->getRegion() !== $region) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_dr_accidents_dashboard');
        }

        $victims = $this->victimRepository->findBy(['accident' => $accident], ['id' => 'ASC']);
        $vehicles = $this->vehicleRepository->findBy(['accident' => $accident], ['id' => 'ASC']);

        return $this->render('direction_regionale/accidents/show.html.twig', [
            'region' => $region,
            'accident' => $accident,
            'victims' => $victims,
            'vehicles' => $vehicles,
        ]);
    }

    #[Route('/carte', name: 'app_dr_accidents_carte', methods: ['GET'])]
    public function carte(Request $request): Response
    {
        $region = $this->getUserRegion();
        
        if (!$region) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une région.');
            return $this->redirectToRoute('app_dashboard');
        }

        $period = $request->query->get('period', '7'); // 7 jours par défaut
        $gravity = $request->query->get('gravity');
        $brigade = $request->query->get('brigade');

        $dateLimit = new \DateTimeImmutable("-{$period} days");
        
        $qb = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.brigade', 'b')
            ->addSelect('b')
            ->where('a.region = :region')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('region', $region)
            ->setParameter('dateLimit', $dateLimit);

        if ($gravity) {
            $qb->andWhere('a.gravite = :gravity')
               ->setParameter('gravity', $gravity);
        }

        if ($brigade) {
            $qb->andWhere('b.id = :brigade')
               ->setParameter('brigade', $brigade);
        }

        $accidents = $qb->orderBy('a.dateAccident', 'DESC')
                        ->getQuery()
                        ->getResult();

        $brigades = $this->entityManager->getRepository(Brigade::class)->findBy(['region' => $region]);

        return $this->render('direction_regionale/accidents/carte.html.twig', [
            'region' => $region,
            'accidents' => $accidents,
            'brigades' => $brigades,
            'period' => $period,
            'currentFilters' => [
                'gravity' => $gravity,
                'brigade' => $brigade
            ]
        ]);
    }

    #[Route('/statistiques', name: 'app_dr_accidents_statistiques', methods: ['GET'])]
    public function statistiques(Request $request): Response
    {
        $region = $this->getUserRegion();
        
        if (!$region) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une région.');
            return $this->redirectToRoute('app_dashboard');
        }

        $period = $request->query->get('period', '12'); // 12 mois par défaut
        $dateLimit = new \DateTimeImmutable("-{$period} months");

        // Évolution temporelle régionale
        $evolutionTemporelle = $this->accidentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id) as count, DATE_FORMAT(a.dateAccident, \'%Y-%m\') as month')
            ->where('a.region = :region')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('region', $region)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        // Comparaison annuelle régionale
        $currentYear = date('Y');
        $previousYear = date('Y') - 1;
        
        $currentYearData = $this->accidentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id) as count, MONTH(a.dateAccident) as month')
            ->where('a.region = :region')
            ->andWhere('YEAR(a.dateAccident) = :year')
            ->setParameter('region', $region)
            ->setParameter('year', $currentYear)
            ->groupBy('month')
            ->getQuery()
            ->getResult();

        $previousYearData = $this->accidentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id) as count, MONTH(a.dateAccident) as month')
            ->where('a.region = :region')
            ->andWhere('YEAR(a.dateAccident) = :year')
            ->setParameter('region', $region)
            ->setParameter('year', $previousYear)
            ->groupBy('month')
            ->getQuery()
            ->getResult();

        // Statistiques par brigade détaillées
        $statsBrigades = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.brigade', 'b')
            ->select('b.libelle as brigade, COUNT(a.id) as total, 
                     SUM(CASE WHEN a.gravite = :mortel THEN 1 ELSE 0 END) as mortels,
                     SUM(CASE WHEN a.gravite = :grave THEN 1 ELSE 0 END) as graves')
            ->where('a.region = :region')
            ->andWhere('b.id IS NOT NULL')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('region', $region)
            ->setParameter('mortel', Accident::GRAVITY_MORTEL)
            ->setParameter('grave', Accident::GRAVITY_GRAVE)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('b.id', 'b.libelle')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        // Taux de résolution régional
        $tauxResolution = $this->accidentRepository->createQueryBuilder('a')
            ->select('a.status, COUNT(a.id) as count')
            ->where('a.region = :region')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('region', $region)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('a.status')
            ->getQuery()
            ->getResult();

        // Pics horaires régionaux
        $picsHoraires = $this->accidentRepository->createQueryBuilder('a')
            ->select('HOUR(a.dateAccident) as hour, COUNT(a.id) as count')
            ->where('a.region = :region')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('region', $region)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('hour')
            ->orderBy('hour', 'ASC')
            ->getQuery()
            ->getResult();

        // Performance des brigades (temps de traitement)
        $performanceBrigades = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.brigade', 'b')
            ->select('b.libelle as brigade, 
                     AVG(TIMESTAMPDIFF(MINUTE, a.dateAccident, COALESCE(a.dateValidation, a.dateAccident))) as tempsMoyenTraitement')
            ->where('a.region = :region')
            ->andWhere('b.id IS NOT NULL')
            ->andWhere('a.status IN (:statuses)')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('region', $region)
            ->setParameter('statuses', [Accident::STATUS_TRAITE, Accident::STATUS_ARCHIVE])
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('b.id', 'b.libelle')
            ->orderBy('tempsMoyenTraitement', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('direction_regionale/accidents/statistiques.html.twig', [
            'region' => $region,
            'evolutionTemporelle' => $evolutionTemporelle,
            'currentYearData' => $currentYearData,
            'previousYearData' => $previousYearData,
            'currentYear' => $currentYear,
            'previousYear' => $previousYear,
            'statsBrigades' => $statsBrigades,
            'tauxResolution' => $tauxResolution,
            'picsHoraires' => $picsHoraires,
            'performanceBrigades' => $performanceBrigades,
            'period' => $period,
        ]);
    }

    #[Route('/export', name: 'app_dr_accidents_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $region = $this->getUserRegion();
        
        if (!$region) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une région.');
            return $this->redirectToRoute('app_dashboard');
        }

        $format = $request->query->get('format', 'csv');
        $period = $request->query->get('period', '1'); // 1 mois par défaut
        $dateLimit = new \DateTimeImmutable("-{$period} month");
        
        $accidents = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.brigade', 'b')
            ->leftJoin('a.declarant', 'u')
            ->addSelect('b', 'u')
            ->where('a.region = :region')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('region', $region)
            ->setParameter('dateLimit', $dateLimit)
            ->orderBy('a.dateAccident', 'DESC')
            ->getQuery()
            ->getResult();

        if ($format === 'csv') {
            $csv = "Référence;Date;Localisation;Statut;Gravité;Cause;Brigade;Déclarant;Description\n";
            
            foreach ($accidents as $accident) {
                $csv .= $accident->getReference() . ';';
                $csv .= $accident->getDateAccident()->format('d/m/Y H:i') . ';';
                $csv .= ($accident->getLocalisation() ?? '') . ';';
                $csv .= $accident->getStatus() . ';';
                $csv .= $accident->getGravite() . ';';
                $csv .= ($accident->getCausePrincipale() ? $accident->getCausePrincipaleLabel() : '') . ';';
                $csv .= ($accident->getBrigade() ? $accident->getBrigade()->getLibelle() : '') . ';';
                $csv .= ($accident->getCreatedBy() ? $accident->getCreatedBy()->getNom() . ' ' . $accident->getCreatedBy()->getPrenom() : '') . ';';
                $csv .= str_replace(';', ',', ($accident->getDescription() ?? '')) . "\n";
            }

            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="accidents_' . $region->getLibelle() . '_' . date('Y-m-d') . '.csv"');
            return $response;
        }

        $this->addFlash('error', 'Format d\'export non supporté.');
        return $this->redirectToRoute('app_dr_accidents_dashboard');
    }

    #[Route('/{id}/valider', name: 'app_dr_accidents_valider', methods: ['POST'])]
    public function valider(Request $request, Accident $accident): Response
    {
        $region = $this->getUserRegion();
        
        if (!$region || $accident->getRegion() !== $region) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_dr_accidents_dashboard');
        }

        if ($accident->getStatus() !== Accident::STATUS_EN_COURS) {
            $this->addFlash('error', 'Cet accident ne peut plus être validé.');
            return $this->redirectToRoute('app_dr_accidents_show', ['id' => $accident->getId()]);
        }

        if ($accident->getDateValidationBrigade() === null) {
            $this->addFlash('error', 'Validation brigade requise avant validation DR.');
            return $this->redirectToRoute('app_dr_accidents_show', ['id' => $accident->getId()]);
        }

        if ($this->isCsrfTokenValid('valider'.$accident->getId(), $request->request->get('_token'))) {
            $accident->setStatus(Accident::STATUS_TRAITE);
            $accident->setDateValidation(new \DateTimeImmutable());
            $accident->setValidatedBy($this->getUser());
            $accident->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();
            $this->addFlash('success', 'Accident validé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_dr_accidents_show', ['id' => $accident->getId()]);
    }

    #[Route('/brigades/performance', name: 'app_dr_accidents_brigades_performance', methods: ['GET'])]
    public function brigadesPerformance(): Response
    {
        $region = $this->getUserRegion();
        
        if (!$region) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une région.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Performance détaillée des brigades
        $brigades = $this->entityManager->getRepository(Brigade::class)->findBy(['region' => $region]);
        
        $performanceData = [];
        foreach ($brigades as $brigade) {
            $total = $this->accidentRepository->count(['brigade' => $brigade]);
            $enCours = $this->accidentRepository->count(['brigade' => $brigade, 'status' => Accident::STATUS_EN_COURS]);
            $traites = $this->accidentRepository->count(['brigade' => $brigade, 'status' => Accident::STATUS_TRAITE]);
            
            // Temps moyen de traitement
            $tempsMoyen = $this->accidentRepository->createQueryBuilder('a')
                ->where('a.brigade = :brigade')
                ->andWhere('a.status IN (:statuses)')
                ->andWhere('a.dateAccident >= :date')
                ->setParameter('brigade', $brigade)
                ->setParameter('statuses', [Accident::STATUS_TRAITE, Accident::STATUS_ARCHIVE])
                ->setParameter('date', new \DateTimeImmutable('-30 days'))
                ->select('AVG(TIMESTAMPDIFF(MINUTE, a.dateAccident, COALESCE(a.dateValidation, a.dateAccident)))')
                ->getQuery()
                ->getSingleScalarResult();

            $performanceData[] = [
                'brigade' => $brigade,
                'total' => $total,
                'enCours' => $enCours,
                'traites' => $traites,
                'tauxResolution' => $total > 0 ? round(($traites / $total) * 100, 2) : 0,
                'tempsMoyen' => round($tempsMoyen ?? 0, 2)
            ];
        }

        return $this->render('direction_regionale/accidents/brigades_performance.html.twig', [
            'region' => $region,
            'performanceData' => $performanceData,
        ]);
    }
}
