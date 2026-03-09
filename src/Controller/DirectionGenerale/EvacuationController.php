<?php

namespace App\Controller\DirectionGenerale;

use App\Entity\Evacuation;
use App\Entity\Accident;
use App\Entity\Region;
use App\Entity\Brigade;
use App\Entity\User;
use App\Repository\EvacuationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dg/evacuations')]
#[IsGranted('ROLE_DIRECTION_GENERALE')]
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

    #[Route('/', name: 'app_dg_evacuations_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        // Statistiques nationales en temps réel
        $totalEvacuations = $this->evacuationRepository->count([]);
        $evacuationsEnCours = $this->evacuationRepository->count(['status' => Evacuation::STATUS_EN_COURS]);
        $evacuationsUrgentes = $this->evacuationRepository->count(['urgence' => Evacuation::URGENCY_HAUTE]);
        $evacuationsTerminees = $this->evacuationRepository->count(['status' => Evacuation::STATUS_TERMINE]);

        // Évacuations des dernières 24h
        $date24h = new \DateTimeImmutable('-24 hours');
        $evacuations24h = $this->evacuationRepository->createQueryBuilder('e')
            ->where('e.dateEvacuation >= :date')
            ->setParameter('date', $date24h)
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Évacuations urgentes en cours
        $urgentesEnCours = $this->evacuationRepository->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.urgence = :urgence')
            ->setParameter('status', Evacuation::STATUS_EN_COURS)
            ->setParameter('urgence', Evacuation::URGENCY_HAUTE)
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Évacuations par région (top 5)
        $evacuationsParRegion = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.region', 'r')
            ->select('r.libelle as region, COUNT(e.id) as count')
            ->where('r.id IS NOT NULL')
            ->groupBy('r.id', 'r.libelle')
            ->orderBy('count', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Répartition par type d'évacuation
        $repartitionType = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.typeEvacuation, COUNT(e.id) as count')
            ->groupBy('e.typeEvacuation')
            ->getQuery()
            ->getResult();

        // Répartition par urgence
        $repartitionUrgence = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.urgence, COUNT(e.id) as count')
            ->groupBy('e.urgence')
            ->getQuery()
            ->getResult();

        // Évolution mensuelle (6 derniers mois)
        $evolutionMensuelle = $this->evacuationRepository->createQueryBuilder('e')
            ->select('COUNT(e.id) as count, DATE_FORMAT(e.dateEvacuation, \'%Y-%m\') as month, MONTHNAME(e.dateEvacuation) as monthName')
            ->where('e.dateEvacuation >= :date')
            ->setParameter('date', new \DateTimeImmutable('-6 months'))
            ->groupBy('month', 'monthName')
            ->orderBy('month', 'DESC')
            ->getQuery()
            ->getResult();

        // Dernières évacuations urgentes
        $dernieresEvacuationsUrgentes = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.region', 'r')
            ->leftJoin('e.brigade', 'b')
            ->leftJoin('e.operateur', 'u')
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

        // Temps moyen d'évacuation (terminées)
        $tempsMoyenGlobal = $this->evacuationRepository->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.dateArrivee IS NOT NULL')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('status', Evacuation::STATUS_TERMINE)
            ->setParameter('date', new \DateTimeImmutable('-30 days'))
            ->select('AVG(TIMESTAMPDIFF(MINUTE, e.dateEvacuation, e.dateArrivee)) as tempsMoyen')
            ->getQuery()
            ->getSingleScalarResult();

        // Hôpitaux les plus sollicités
        $hopitauxFrequent = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.hopitalDestination, COUNT(e.id) as count')
            ->where('e.hopitalDestination IS NOT NULL')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('date', new \DateTimeImmutable('-30 days'))
            ->groupBy('e.hopitalDestination')
            ->orderBy('count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->render('direction_generale/evacuations/dashboard.html.twig', [
            'totalEvacuations' => $totalEvacuations,
            'evacuationsEnCours' => $evacuationsEnCours,
            'evacuationsUrgentes' => $evacuationsUrgentes,
            'evacuationsTerminees' => $evacuationsTerminees,
            'evacuations24h' => $evacuations24h,
            'urgentesEnCours' => $urgentesEnCours,
            'evacuationsParRegion' => $evacuationsParRegion,
            'repartitionType' => $repartitionType,
            'repartitionUrgence' => $repartitionUrgence,
            'evolutionMensuelle' => $evolutionMensuelle,
            'dernieresEvacuationsUrgentes' => $dernieresEvacuationsUrgentes,
            'evacuationsEnAttente' => $evacuationsEnAttente,
            'tempsMoyenGlobal' => round($tempsMoyenGlobal ?? 0, 2),
            'hopitauxFrequent' => $hopitauxFrequent,
        ]);
    }

    #[Route('/supervision', name: 'app_dg_evacuations_supervision', methods: ['GET'])]
    public function supervision(): Response
    {
        // Toutes les évacuations en cours avec détails complets
        $evacuationsEnCours = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.region', 'r')
            ->leftJoin('e.brigade', 'b')
            ->leftJoin('e.operateur', 'u')
            ->addSelect('a', 'r', 'b', 'u')
            ->where('e.status = :status')
            ->setParameter('status', Evacuation::STATUS_EN_COURS)
            ->orderBy('e.urgence', 'DESC')
            ->addOrderBy('e.dateEvacuation', 'ASC')
            ->getQuery()
            ->getResult();

        // Évacuations urgentes nécessitant une attention immédiate
        $evacuationsCritiques = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.region', 'r')
            ->leftJoin('e.brigade', 'b')
            ->addSelect('a', 'r', 'b')
            ->where('e.status = :status')
            ->andWhere('e.urgence = :urgence')
            ->setParameter('status', Evacuation::STATUS_EN_COURS)
            ->setParameter('urgence', Evacuation::URGENCY_HAUTE)
            ->andWhere('e.dateEvacuation < :dateLimite')
            ->setParameter('dateLimite', new \DateTimeImmutable('-2 hours'))
            ->orderBy('e.dateEvacuation', 'ASC')
            ->getQuery()
            ->getResult();

        // Ressources disponibles par région
        $ressourcesRegion = $this->entityManager->getRepository(Region::class)->createQueryBuilder('r')
            ->leftJoin('r.brigades', 'b')
            ->addSelect('b')
            ->where('r.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('r.libelle', 'ASC')
            ->getQuery()
            ->getResult();

        // Statistiques des ressources
        $ressourcesStats = [];
        foreach ($ressourcesRegion as $region) {
            $evacuationsActives = $this->evacuationRepository->createQueryBuilder('e')
                ->where('e.region = :region')
                ->andWhere('e.status = :status')
                ->setParameter('region', $region)
                ->setParameter('status', Evacuation::STATUS_EN_COURS)
                ->select('COUNT(e.id)')
                ->getQuery()
                ->getSingleScalarResult();

            $ressourcesStats[$region->getId()] = [
                'region' => $region,
                'evacuationsActives' => $evacuationsActives,
                'brigades' => count($region->getBrigades())
            ];
        }

        return $this->render('direction_generale/evacuations/supervision.html.twig', [
            'evacuationsEnCours' => $evacuationsEnCours,
            'evacuationsCritiques' => $evacuationsCritiques,
            'ressourcesRegion' => $ressourcesRegion,
            'ressourcesStats' => $ressourcesStats,
        ]);
    }

    #[Route('/carte', name: 'app_dg_evacuations_carte', methods: ['GET'])]
    public function carte(Request $request): Response
    {
        $period = $request->query->get('period', '24'); // 24 heures par défaut
        $urgence = $request->query->get('urgence');
        $status = $request->query->get('status');
        $region = $request->query->get('region');

        $dateLimit = new \DateTimeImmutable("-{$period} hours");
        
        $qb = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.region', 'r')
            ->leftJoin('e.brigade', 'b')
            ->addSelect('a', 'r', 'b')
            ->where('e.dateEvacuation >= :dateLimit')
            ->setParameter('dateLimit', $dateLimit);

        if ($urgence) {
            $qb->andWhere('e.urgence = :urgence')
               ->setParameter('urgence', $urgence);
        }

        if ($status) {
            $qb->andWhere('e.status = :status')
               ->setParameter('status', $status);
        }

        if ($region) {
            $qb->andWhere('r.id = :region')
               ->setParameter('region', $region);
        }

        $evacuations = $qb->orderBy('e.dateEvacuation', 'DESC')
                          ->getQuery()
                          ->getResult();

        $regions = $this->entityManager->getRepository(Region::class)->findAll();

        return $this->render('direction_generale/evacuations/carte.html.twig', [
            'evacuations' => $evacuations,
            'regions' => $regions,
            'period' => $period,
            'currentFilters' => [
                'urgence' => $urgence,
                'status' => $status,
                'region' => $region
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_dg_evacuations_show', methods: ['GET'])]
    public function show(Evacuation $evacuation): Response
    {
        return $this->render('direction_generale/evacuations/show.html.twig', [
            'evacuation' => $evacuation,
        ]);
    }

    #[Route('/statistiques', name: 'app_dg_evacuations_statistiques', methods: ['GET'])]
    public function statistiques(Request $request): Response
    {
        $period = $request->query->get('period', '12'); // 12 mois par défaut
        $dateLimit = new \DateTimeImmutable("-{$period} months");

        // Évolution temporelle
        $evolutionTemporelle = $this->evacuationRepository->createQueryBuilder('e')
            ->select('COUNT(e.id) as count, DATE_FORMAT(e.dateEvacuation, \'%Y-%m\') as month')
            ->where('e.dateEvacuation >= :dateLimit')
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        // Comparaison annuelle
        $currentYear = date('Y');
        $previousYear = date('Y') - 1;
        
        $currentYearData = $this->evacuationRepository->createQueryBuilder('e')
            ->select('COUNT(e.id) as count, MONTH(e.dateEvacuation) as month')
            ->where('YEAR(e.dateEvacuation) = :year')
            ->setParameter('year', $currentYear)
            ->groupBy('month')
            ->getQuery()
            ->getResult();

        $previousYearData = $this->evacuationRepository->createQueryBuilder('e')
            ->select('COUNT(e.id) as count, MONTH(e.dateEvacuation) as month')
            ->where('YEAR(e.dateEvacuation) = :year')
            ->setParameter('year', $previousYear)
            ->groupBy('month')
            ->getQuery()
            ->getResult();

        // Statistiques par région détaillées
        $statsRegions = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.region', 'r')
            ->select('r.libelle as region, COUNT(e.id) as total, 
                     SUM(CASE WHEN e.urgence = :haute THEN 1 ELSE 0 END) as urgentes,
                     SUM(CASE WHEN e.status = :termine THEN 1 ELSE 0 END) as terminees')
            ->where('r.id IS NOT NULL')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('haute', Evacuation::URGENCY_HAUTE)
            ->setParameter('termine', Evacuation::STATUS_TERMINE)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('r.id', 'r.libelle')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        // Temps moyen d'évacuation par région
        $tempsMoyenRegion = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.region', 'r')
            ->select('r.libelle as region, AVG(TIMESTAMPDIFF(MINUTE, e.dateEvacuation, e.dateArrivee)) as tempsMoyen')
            ->where('e.status = :status')
            ->andWhere('e.dateArrivee IS NOT NULL')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('status', Evacuation::STATUS_TERMINE)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('r.id', 'r.libelle')
            ->orderBy('tempsMoyen', 'ASC')
            ->getQuery()
            ->getResult();

        // Performance par type d'évacuation
        $performanceType = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.typeEvacuation, COUNT(e.id) as total,
                     SUM(CASE WHEN e.status = :termine THEN 1 ELSE 0 END) as terminees,
                     AVG(TIMESTAMPDIFF(MINUTE, e.dateEvacuation, e.dateArrivee)) as tempsMoyen')
            ->where('e.dateEvacuation >= :dateLimit')
            ->setParameter('termine', Evacuation::STATUS_TERMINE)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('e.typeEvacuation')
            ->getQuery()
            ->getResult();

        // Pics horaires des évacuations
        $picsHoraires = $this->evacuationRepository->createQueryBuilder('e')
            ->select('HOUR(e.dateEvacuation) as hour, COUNT(e.id) as count')
            ->where('e.dateEvacuation >= :dateLimit')
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('hour')
            ->orderBy('hour', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('direction_generale/evacuations/statistiques.html.twig', [
            'evolutionTemporelle' => $evolutionTemporelle,
            'currentYearData' => $currentYearData,
            'previousYearData' => $previousYearData,
            'currentYear' => $currentYear,
            'previousYear' => $previousYear,
            'statsRegions' => $statsRegions,
            'tempsMoyenRegion' => $tempsMoyenRegion,
            'performanceType' => $performanceType,
            'picsHoraires' => $picsHoraires,
            'period' => $period,
        ]);
    }

    #[Route('/export', name: 'app_dg_evacuations_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $format = $request->query->get('format', 'csv');
        $period = $request->query->get('period', '1'); // 1 mois par défaut
        $dateLimit = new \DateTimeImmutable("-{$period} month");
        
        $evacuations = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.region', 'r')
            ->leftJoin('e.brigade', 'b')
            ->leftJoin('e.operateur', 'u')
            ->addSelect('a', 'r', 'b', 'u')
            ->where('e.dateEvacuation >= :dateLimit')
            ->setParameter('dateLimit', $dateLimit)
            ->orderBy('e.dateEvacuation', 'DESC')
            ->getQuery()
            ->getResult();

        if ($format === 'csv') {
            $csv = "Référence;Date;Type;Statut;Urgence;Hôpital destination;Région;Brigade;Opérateur;Accident;Date arrivée;Durée (min)\n";
            
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
                $csv .= ($evacuation->getRegion() ? $evacuation->getRegion()->getLibelle() : '') . ';';
                $csv .= ($evacuation->getBrigade() ? $evacuation->getBrigade()->getLibelle() : '') . ';';
                $csv .= ($evacuation->getCreatedBy() ? $evacuation->getCreatedBy()->getNom() . ' ' . $evacuation->getCreatedBy()->getPrenom() : '') . ';';
                $csv .= ($evacuation->getAccident() ? $evacuation->getAccident()->getReference() : '') . ';';
                $csv .= ($evacuation->getDateArrivee() ? $evacuation->getDateArrivee()->format('d/m/Y H:i') : '') . ';';
                $csv .= $duree . "\n";
            }

            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="evacuations_dg_' . date('Y-m-d') . '.csv"');
            return $response;
        }

        $this->addFlash('error', 'Format d\'export non supporté.');
        return $this->redirectToRoute('app_dg_evacuations_dashboard');
    }

    #[Route('/{id}/coordonner', name: 'app_dg_evacuations_coordonner', methods: ['GET', 'POST'])]
    public function coordonner(Request $request, Evacuation $evacuation): Response
    {
        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            
            switch ($action) {
                case 'prioriser':
                    $evacuation->setUrgence(Evacuation::URGENCY_HAUTE);
                    $this->addFlash('success', 'Évacuation priorisée avec succès.');
                    break;
                case 'reassigner':
                    $nouvelleBrigade = $request->request->get('brigade');
                    if ($nouvelleBrigade) {
                        $brigade = $this->entityManager->getRepository(Brigade::class)->find($nouvelleBrigade);
                        $evacuation->setBrigade($brigade);
                        $this->addFlash('success', 'Évacuation réassignée avec succès.');
                    }
                    break;
                case 'annuler':
                    $evacuation->setStatus(Evacuation::STATUS_ANNULE);
                    $this->addFlash('success', 'Évacuation annulée.');
                    break;
            }
            
            $this->entityManager->flush();
            return $this->redirectToRoute('app_dg_evacuations_supervision');
        }

        $brigades = $this->entityManager->getRepository(Brigade::class)->findAll();

        return $this->render('direction_generale/evacuations/coordonner.html.twig', [
            'evacuation' => $evacuation,
            'brigades' => $brigades,
        ]);
    }
}
