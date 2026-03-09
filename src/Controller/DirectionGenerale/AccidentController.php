<?php

namespace App\Controller\DirectionGenerale;

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

#[Route('/dg/accidents')]
#[IsGranted('ROLE_DIRECTION_GENERALE')]
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

    #[Route('/', name: 'app_dg_accidents_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        // Statistiques nationales en temps réel
        $totalAccidents = $this->accidentRepository->count([]);
        $accidentsEnCours = $this->accidentRepository->count(['status' => Accident::STATUS_EN_COURS]);
        $accidentsMortels = $this->accidentRepository->count(['gravite' => Accident::GRAVITY_MORTEL]);
        $accidentsGraves = $this->accidentRepository->count(['gravite' => Accident::GRAVITY_GRAVE]);
        $accidentsEvacuation = $this->accidentRepository->count(['status' => Accident::STATUS_EVACUATION]);

        // Accidents des dernières 24h
        $date24h = new \DateTimeImmutable('-24 hours');
        $accidents24h = $this->accidentRepository->createQueryBuilder('a')
            ->where('a.dateAccident >= :date')
            ->setParameter('date', $date24h)
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Accidents par région (top 5)
        $accidentsParRegion = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.region', 'r')
            ->select('r.libelle as region, COUNT(a.id) as count')
            ->where('r.id IS NOT NULL')
            ->groupBy('r.id', 'r.libelle')
            ->orderBy('count', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Évolution mensuelle (6 derniers mois)
        $evolutionMensuelle = $this->accidentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id) as count, DATE_FORMAT(a.dateAccident, \'%Y-%m\') as month, MONTHNAME(a.dateAccident) as monthName')
            ->where('a.dateAccident >= :date')
            ->setParameter('date', new \DateTimeImmutable('-6 months'))
            ->groupBy('month', 'monthName')
            ->orderBy('month', 'DESC')
            ->getQuery()
            ->getResult();

        // Répartition par gravité
        $repartitionGravite = $this->accidentRepository->createQueryBuilder('a')
            ->select('a.gravite, COUNT(a.id) as count')
            ->groupBy('a.gravite')
            ->getQuery()
            ->getResult();

        // Répartition par cause
        $repartitionCauses = [];
        foreach (Accident::CAUSES as $cause => $label) {
            $count = $this->accidentRepository->createQueryBuilder('a')
                ->where('a.cause = :cause')
                ->setParameter('cause', $cause)
                ->select('COUNT(a.id)')
                ->getQuery()
                ->getSingleScalarResult();
            $repartitionCauses[$label] = $count;
        }
        arsort($repartitionCauses);

        // Derniers accidents graves
        $derniersAccidentsGraves = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.region', 'r')
            ->leftJoin('a.brigade', 'b')
            ->leftJoin('a.declarant', 'u')
            ->addSelect('r', 'b', 'u')
            ->where('a.gravite IN (:gravites)')
            ->setParameter('gravites', [Accident::GRAVITY_MORTEL, Accident::GRAVITY_GRAVE])
            ->orderBy('a.dateAccident', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Accidents en attente de traitement
        $accidentsEnAttente = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.region', 'r')
            ->leftJoin('a.brigade', 'b')
            ->addSelect('r', 'b')
            ->where('a.status = :status')
            ->setParameter('status', Accident::STATUS_EN_COURS)
            ->orderBy('a.dateAccident', 'ASC')
            ->setMaxResults(15)
            ->getQuery()
            ->getResult();

        // Statistiques des victimes
        $totalVictimes = $this->victimRepository->count([]);
        $victimesMortelles = $this->victimRepository->count(['gravite' => AccidentVictim::GRAVITY_MORTEL]);
        $victimesBlessesGraves = $this->victimRepository->count(['gravite' => AccidentVictim::GRAVITY_BLESSE_GRAVE]);

        return $this->render('direction_generale/accidents/dashboard.html.twig', [
            'totalAccidents' => $totalAccidents,
            'accidentsEnCours' => $accidentsEnCours,
            'accidentsMortels' => $accidentsMortels,
            'accidentsGraves' => $accidentsGraves,
            'accidentsEvacuation' => $accidentsEvacuation,
            'accidents24h' => $accidents24h,
            'accidentsParRegion' => $accidentsParRegion,
            'evolutionMensuelle' => $evolutionMensuelle,
            'repartitionGravite' => $repartitionGravite,
            'repartitionCauses' => $repartitionCauses,
            'derniersAccidentsGraves' => $derniersAccidentsGraves,
            'accidentsEnAttente' => $accidentsEnAttente,
            'totalVictimes' => $totalVictimes,
            'victimesMortelles' => $victimesMortelles,
            'victimesBlessesGraves' => $victimesBlessesGraves,
        ]);
    }

    #[Route('/liste', name: 'app_dg_accidents_liste', methods: ['GET'])]
    public function liste(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $status = $request->query->get('status');
        $gravity = $request->query->get('gravity');
        $region = $request->query->get('region');
        $search = $request->query->get('search');

        $qb = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.region', 'r')
            ->leftJoin('a.brigade', 'b')
            ->leftJoin('a.declarant', 'u')
            ->addSelect('r', 'b', 'u')
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

        if ($region) {
            $qb->andWhere('r.id = :region')
               ->setParameter('region', $region);
        }

        if ($search) {
            $qb->andWhere('a.reference LIKE :search OR a.localisation LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $total = count($qb->getQuery()->getResult());
        $accidents = $qb->setMaxResults($limit)
                        ->setFirstResult(($page - 1) * $limit)
                        ->getQuery()->getResult();

        $regions = $this->entityManager->getRepository(Region::class)->findAll();

        return $this->render('direction_generale/accidents/liste.html.twig', [
            'accidents' => $accidents,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit),
            'regions' => $regions,
            'currentFilters' => [
                'status' => $status,
                'gravity' => $gravity,
                'region' => $region,
                'search' => $search
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_dg_accidents_show', methods: ['GET'])]
    public function show(Accident $accident): Response
    {
        $victims = $this->victimRepository->findBy(['accident' => $accident], ['id' => 'ASC']);
        $vehicles = $this->vehicleRepository->findBy(['accident' => $accident], ['id' => 'ASC']);

        return $this->render('direction_generale/accidents/show.html.twig', [
            'accident' => $accident,
            'victims' => $victims,
            'vehicles' => $vehicles,
        ]);
    }

    #[Route('/carte', name: 'app_dg_accidents_carte', methods: ['GET'])]
    public function carte(Request $request): Response
    {
        $period = $request->query->get('period', '7'); // 7 jours par défaut
        $gravity = $request->query->get('gravity');
        $region = $request->query->get('region');

        $dateLimit = new \DateTimeImmutable("-{$period} days");
        
        $qb = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.region', 'r')
            ->leftJoin('a.brigade', 'b')
            ->addSelect('r', 'b')
            ->where('a.dateAccident >= :dateLimit')
            ->setParameter('dateLimit', $dateLimit);

        if ($gravity) {
            $qb->andWhere('a.gravite = :gravity')
               ->setParameter('gravity', $gravity);
        }

        if ($region) {
            $qb->andWhere('r.id = :region')
               ->setParameter('region', $region);
        }

        $accidents = $qb->orderBy('a.dateAccident', 'DESC')
                        ->getQuery()
                        ->getResult();

        $regions = $this->entityManager->getRepository(Region::class)->findAll();

        return $this->render('direction_generale/accidents/carte.html.twig', [
            'accidents' => $accidents,
            'regions' => $regions,
            'period' => $period,
            'currentFilters' => [
                'gravity' => $gravity,
                'region' => $region
            ]
        ]);
    }

    #[Route('/statistiques', name: 'app_dg_accidents_statistiques', methods: ['GET'])]
    public function statistiques(Request $request): Response
    {
        $period = $request->query->get('period', '12'); // 12 mois par défaut
        $dateLimit = new \DateTimeImmutable("-{$period} months");

        // Évolution temporelle
        $evolutionTemporelle = $this->accidentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id) as count, DATE_FORMAT(a.dateAccident, \'%Y-%m\') as month')
            ->where('a.dateAccident >= :dateLimit')
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        // Comparaison annuelle
        $currentYear = date('Y');
        $previousYear = date('Y') - 1;
        
        $currentYearData = $this->accidentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id) as count, MONTH(a.dateAccident) as month')
            ->where('YEAR(a.dateAccident) = :year')
            ->setParameter('year', $currentYear)
            ->groupBy('month')
            ->getQuery()
            ->getResult();

        $previousYearData = $this->accidentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id) as count, MONTH(a.dateAccident) as month')
            ->where('YEAR(a.dateAccident) = :year')
            ->setParameter('year', $previousYear)
            ->groupBy('month')
            ->getQuery()
            ->getResult();

        // Statistiques par région détaillées
        $statsRegions = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.region', 'r')
            ->select('r.libelle as region, COUNT(a.id) as total, 
                     SUM(CASE WHEN a.gravite = :mortel THEN 1 ELSE 0 END) as mortels,
                     SUM(CASE WHEN a.gravite = :grave THEN 1 ELSE 0 END) as graves')
            ->where('r.id IS NOT NULL')
            ->andWhere('a.dateAccident >= :dateLimit')
            ->setParameter('mortel', Accident::GRAVITY_MORTEL)
            ->setParameter('grave', Accident::GRAVITY_GRAVE)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('r.id', 'r.libelle')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        // Taux de résolution
        $tauxResolution = $this->accidentRepository->createQueryBuilder('a')
            ->select('a.status, COUNT(a.id) as count')
            ->where('a.dateAccident >= :dateLimit')
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('a.status')
            ->getQuery()
            ->getResult();

        // Pics horaires
        $picsHoraires = $this->accidentRepository->createQueryBuilder('a')
            ->select('HOUR(a.dateAccident) as hour, COUNT(a.id) as count')
            ->where('a.dateAccident >= :dateLimit')
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('hour')
            ->orderBy('hour', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('direction_generale/accidents/statistiques.html.twig', [
            'evolutionTemporelle' => $evolutionTemporelle,
            'currentYearData' => $currentYearData,
            'previousYearData' => $previousYearData,
            'currentYear' => $currentYear,
            'previousYear' => $previousYear,
            'statsRegions' => $statsRegions,
            'tauxResolution' => $tauxResolution,
            'picsHoraires' => $picsHoraires,
            'period' => $period,
        ]);
    }

    #[Route('/export', name: 'app_dg_accidents_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $format = $request->query->get('format', 'csv');
        $period = $request->query->get('period', '1'); // 1 mois par défaut
        $dateLimit = new \DateTimeImmutable("-{$period} month");
        
        $accidents = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.region', 'r')
            ->leftJoin('a.brigade', 'b')
            ->leftJoin('a.declarant', 'u')
            ->addSelect('r', 'b', 'u')
            ->where('a.dateAccident >= :dateLimit')
            ->setParameter('dateLimit', $dateLimit)
            ->orderBy('a.dateAccident', 'DESC')
            ->getQuery()
            ->getResult();

        if ($format === 'csv') {
            $csv = "Référence;Date;Localisation;Statut;Gravité;Cause;Région;Brigade;Déclarant;Description\n";
            
            foreach ($accidents as $accident) {
                $csv .= $accident->getReference() . ';';
                $csv .= $accident->getDateAccident()->format('d/m/Y H:i') . ';';
                $csv .= ($accident->getLocalisation() ?? '') . ';';
                $csv .= $accident->getStatus() . ';';
                $csv .= $accident->getGravite() . ';';
                $csv .= ($accident->getCausePrincipale() ? $accident->getCausePrincipaleLabel() : '') . ';';
                $csv .= ($accident->getRegion() ? $accident->getRegion()->getLibelle() : '') . ';';
                $csv .= ($accident->getBrigade() ? $accident->getBrigade()->getLibelle() : '') . ';';
                $csv .= ($accident->getCreatedBy() ? $accident->getCreatedBy()->getNom() . ' ' . $accident->getCreatedBy()->getPrenom() : '') . ';';
                $csv .= str_replace(';', ',', ($accident->getDescription() ?? '')) . "\n";
            }

            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="accidents_dg_' . date('Y-m-d') . '.csv"');
            return $response;
        }

        $this->addFlash('error', 'Format d\'export non supporté.');
        return $this->redirectToRoute('app_dg_accidents_dashboard');
    }

    #[Route('/{id}/valider', name: 'app_dg_accidents_valider', methods: ['POST'])]
    public function valider(Request $request, Accident $accident): Response
    {
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

        return $this->redirectToRoute('app_dg_accidents_show', ['id' => $accident->getId()]);
    }
}
