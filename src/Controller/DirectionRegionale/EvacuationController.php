<?php

namespace App\Controller\DirectionRegionale;

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

#[Route('/dr/evacuations')]
#[IsGranted('ROLE_DIRECTION_REGIONALE')]
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

    private function getUserRegion(): ?Region
    {
        /** @var User $user */
        $user = $this->getUser();
        return $user->getRegion();
    }

    #[Route('/', name: 'app_dr_evacuations_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $region = $this->getUserRegion();
        
        if (!$region) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une région.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Statistiques régionales en temps réel
        $totalEvacuations = $this->evacuationRepository->count(['region' => $region]);
        $evacuationsEnCours = $this->evacuationRepository->count(['region' => $region, 'status' => Evacuation::STATUS_EN_COURS]);
        $evacuationsUrgentes = $this->evacuationRepository->count(['region' => $region, 'urgence' => Evacuation::URGENCY_HAUTE]);
        $evacuationsTerminees = $this->evacuationRepository->count(['region' => $region, 'status' => Evacuation::STATUS_TERMINE]);

        // Évacuations des dernières 24h dans la région
        $date24h = new \DateTimeImmutable('-24 hours');
        $evacuations24h = $this->evacuationRepository->createQueryBuilder('e')
            ->where('e.region = :region')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('region', $region)
            ->setParameter('date', $date24h)
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Évacuations urgentes en cours dans la région
        $urgentesEnCours = $this->evacuationRepository->createQueryBuilder('e')
            ->where('e.region = :region')
            ->andWhere('e.status = :status')
            ->andWhere('e.urgence = :urgence')
            ->setParameter('region', $region)
            ->setParameter('status', Evacuation::STATUS_EN_COURS)
            ->setParameter('urgence', Evacuation::URGENCY_HAUTE)
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Évacuations par brigade dans la région
        $evacuationsParBrigade = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.brigade', 'b')
            ->select('b.libelle as brigade, COUNT(e.id) as count')
            ->where('e.region = :region')
            ->andWhere('b.id IS NOT NULL')
            ->setParameter('region', $region)
            ->groupBy('b.id', 'b.libelle')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();

        // Répartition par type d'évacuation dans la région
        $repartitionType = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.typeEvacuation, COUNT(e.id) as count')
            ->where('e.region = :region')
            ->setParameter('region', $region)
            ->groupBy('e.typeEvacuation')
            ->getQuery()
            ->getResult();

        // Répartition par urgence dans la région
        $repartitionUrgence = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.urgence, COUNT(e.id) as count')
            ->where('e.region = :region')
            ->setParameter('region', $region)
            ->groupBy('e.urgence')
            ->getQuery()
            ->getResult();

        // Évolution mensuelle (6 derniers mois)
        $evolutionMensuelle = $this->evacuationRepository->createQueryBuilder('e')
            ->select('COUNT(e.id) as count, DATE_FORMAT(e.dateEvacuation, \'%Y-%m\') as month, MONTHNAME(e.dateEvacuation) as monthName')
            ->where('e.region = :region')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('region', $region)
            ->setParameter('date', new \DateTimeImmutable('-6 months'))
            ->groupBy('month', 'monthName')
            ->orderBy('month', 'DESC')
            ->getQuery()
            ->getResult();

        // Dernières évacuations urgentes dans la région
        $dernieresEvacuationsUrgentes = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.brigade', 'b')
            ->leftJoin('e.operateur', 'u')
            ->addSelect('a', 'b', 'u')
            ->where('e.region = :region')
            ->andWhere('e.urgence = :urgence')
            ->setParameter('region', $region)
            ->setParameter('urgence', Evacuation::URGENCY_HAUTE)
            ->orderBy('e.dateEvacuation', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Évacuations en attente de traitement dans la région
        $evacuationsEnAttente = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.brigade', 'b')
            ->addSelect('a', 'b')
            ->where('e.region = :region')
            ->andWhere('e.status = :status')
            ->setParameter('region', $region)
            ->setParameter('status', Evacuation::STATUS_EN_COURS)
            ->orderBy('e.urgence', 'DESC')
            ->addOrderBy('e.dateEvacuation', 'ASC')
            ->setMaxResults(15)
            ->getQuery()
            ->getResult();

        // Temps moyen d'évacuation dans la région (terminées)
        $tempsMoyenRegional = $this->evacuationRepository->createQueryBuilder('e')
            ->where('e.region = :region')
            ->andWhere('e.status = :status')
            ->andWhere('e.dateArrivee IS NOT NULL')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('region', $region)
            ->setParameter('status', Evacuation::STATUS_TERMINE)
            ->setParameter('date', new \DateTimeImmutable('-30 days'))
            ->select('AVG(TIMESTAMPDIFF(MINUTE, e.dateEvacuation, e.dateArrivee)) as tempsMoyen')
            ->getQuery()
            ->getSingleScalarResult();

        // Hôpitaux les plus sollicités dans la région
        $hopitauxFrequent = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.hopitalDestination, COUNT(e.id) as count')
            ->where('e.region = :region')
            ->andWhere('e.hopitalDestination IS NOT NULL')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('region', $region)
            ->setParameter('date', new \DateTimeImmutable('-30 days'))
            ->groupBy('e.hopitalDestination')
            ->orderBy('count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Performance des brigades
        $performanceBrigades = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.brigade', 'b')
            ->select('b.libelle as brigade, COUNT(e.id) as total,
                     SUM(CASE WHEN e.status = :termine THEN 1 ELSE 0 END) as terminees')
            ->where('e.region = :region')
            ->andWhere('b.id IS NOT NULL')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('region', $region)
            ->setParameter('termine', Evacuation::STATUS_TERMINE)
            ->setParameter('date', new \DateTimeImmutable('-30 days'))
            ->groupBy('b.id', 'b.libelle')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('direction_regionale/evacuations/dashboard.html.twig', [
            'region' => $region,
            'totalEvacuations' => $totalEvacuations,
            'evacuationsEnCours' => $evacuationsEnCours,
            'evacuationsUrgentes' => $evacuationsUrgentes,
            'evacuationsTerminees' => $evacuationsTerminees,
            'evacuations24h' => $evacuations24h,
            'urgentesEnCours' => $urgentesEnCours,
            'evacuationsParBrigade' => $evacuationsParBrigade,
            'repartitionType' => $repartitionType,
            'repartitionUrgence' => $repartitionUrgence,
            'evolutionMensuelle' => $evolutionMensuelle,
            'dernieresEvacuationsUrgentes' => $dernieresEvacuationsUrgentes,
            'evacuationsEnAttente' => $evacuationsEnAttente,
            'tempsMoyenRegional' => round($tempsMoyenRegional ?? 0, 2),
            'hopitauxFrequent' => $hopitauxFrequent,
            'performanceBrigades' => $performanceBrigades,
        ]);
    }

    #[Route('/supervision', name: 'app_dr_evacuations_supervision', methods: ['GET'])]
    public function supervision(): Response
    {
        $region = $this->getUserRegion();
        
        if (!$region) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une région.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Toutes les évacuations en cours dans la région avec détails complets
        $evacuationsEnCours = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.brigade', 'b')
            ->leftJoin('e.operateur', 'u')
            ->addSelect('a', 'b', 'u')
            ->where('e.region = :region')
            ->andWhere('e.status = :status')
            ->setParameter('region', $region)
            ->setParameter('status', Evacuation::STATUS_EN_COURS)
            ->orderBy('e.urgence', 'DESC')
            ->addOrderBy('e.dateEvacuation', 'ASC')
            ->getQuery()
            ->getResult();

        // Évacuations urgentes nécessitant une attention immédiate dans la région
        $evacuationsCritiques = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.brigade', 'b')
            ->addSelect('a', 'b')
            ->where('e.region = :region')
            ->andWhere('e.status = :status')
            ->andWhere('e.urgence = :urgence')
            ->andWhere('e.dateEvacuation < :dateLimite')
            ->setParameter('region', $region)
            ->setParameter('status', Evacuation::STATUS_EN_COURS)
            ->setParameter('urgence', Evacuation::URGENCY_HAUTE)
            ->setParameter('dateLimite', new \DateTimeImmutable('-2 hours'))
            ->orderBy('e.dateEvacuation', 'ASC')
            ->getQuery()
            ->getResult();

        // Ressources disponibles par brigade dans la région
        $ressourcesBrigades = $this->entityManager->getRepository(Brigade::class)->createQueryBuilder('b')
            ->where('b.region = :region')
            ->andWhere('b.actif = :actif')
            ->setParameter('region', $region)
            ->setParameter('actif', true)
            ->orderBy('b.libelle', 'ASC')
            ->getQuery()
            ->getResult();

        // Statistiques des ressources par brigade
        $ressourcesStats = [];
        foreach ($ressourcesBrigades as $brigade) {
            $evacuationsActives = $this->evacuationRepository->count([
                'brigade' => $brigade,
                'status' => Evacuation::STATUS_EN_COURS
            ]);

            $urgentesActives = $this->evacuationRepository->createQueryBuilder('e')
                ->where('e.brigade = :brigade')
                ->andWhere('e.status = :status')
                ->andWhere('e.urgence = :urgence')
                ->setParameter('brigade', $brigade)
                ->setParameter('status', Evacuation::STATUS_EN_COURS)
                ->setParameter('urgence', Evacuation::URGENCY_HAUTE)
                ->select('COUNT(e.id)')
                ->getQuery()
                ->getSingleScalarResult();

            $ressourcesStats[$brigade->getId()] = [
                'brigade' => $brigade,
                'evacuationsActives' => $evacuationsActives,
                'urgentesActives' => $urgentesActives
            ];
        }

        return $this->render('direction_regionale/evacuations/supervision.html.twig', [
            'region' => $region,
            'evacuationsEnCours' => $evacuationsEnCours,
            'evacuationsCritiques' => $evacuationsCritiques,
            'ressourcesBrigades' => $ressourcesBrigades,
            'ressourcesStats' => $ressourcesStats,
        ]);
    }

    #[Route('/{id}', name: 'app_dr_evacuations_show', methods: ['GET'])]
    public function show(Evacuation $evacuation): Response
    {
        $region = $this->getUserRegion();
        
        if (!$region || $evacuation->getBrigade()->getRegion()->getId() !== $region->getId()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_dr_evacuations_dashboard');
        }

        return $this->render('direction_regionale/evacuations/show.html.twig', [
            'region' => $region,
            'evacuation' => $evacuation,
        ]);
    }

    #[Route('/statistiques', name: 'app_dr_evacuations_statistiques', methods: ['GET'])]
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
        $evolutionTemporelle = $this->evacuationRepository->createQueryBuilder('e')
            ->select('COUNT(e.id) as count, DATE_FORMAT(e.dateEvacuation, \'%Y-%m\') as month')
            ->where('e.region = :region')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('region', $region)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        // Comparaison annuelle régionale
        $currentYear = date('Y');
        $previousYear = date('Y') - 1;
        
        $currentYearData = $this->evacuationRepository->createQueryBuilder('e')
            ->select('COUNT(e.id) as count, MONTH(e.dateEvacuation) as month')
            ->where('e.region = :region')
            ->andWhere('YEAR(e.dateEvacuation) = :year')
            ->setParameter('region', $region)
            ->setParameter('year', $currentYear)
            ->groupBy('month')
            ->getQuery()
            ->getResult();

        $previousYearData = $this->evacuationRepository->createQueryBuilder('e')
            ->select('COUNT(e.id) as count, MONTH(e.dateEvacuation) as month')
            ->where('e.region = :region')
            ->andWhere('YEAR(e.dateEvacuation) = :year')
            ->setParameter('region', $region)
            ->setParameter('year', $previousYear)
            ->groupBy('month')
            ->getQuery()
            ->getResult();

        // Statistiques par brigade détaillées
        $statsBrigades = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.brigade', 'b')
            ->select('b.libelle as brigade, COUNT(e.id) as total, 
                     SUM(CASE WHEN e.urgence = :haute THEN 1 ELSE 0 END) as urgentes,
                     SUM(CASE WHEN e.status = :termine THEN 1 ELSE 0 END) as terminees')
            ->where('e.region = :region')
            ->andWhere('b.id IS NOT NULL')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('region', $region)
            ->setParameter('haute', Evacuation::URGENCY_HAUTE)
            ->setParameter('termine', Evacuation::STATUS_TERMINE)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('b.id', 'b.libelle')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        // Temps moyen d'évacuation par brigade
        $tempsMoyenBrigade = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.brigade', 'b')
            ->select('b.libelle as brigade, AVG(TIMESTAMPDIFF(MINUTE, e.dateEvacuation, e.dateArrivee)) as tempsMoyen')
            ->where('e.region = :region')
            ->andWhere('e.status = :status')
            ->andWhere('e.dateArrivee IS NOT NULL')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('region', $region)
            ->setParameter('status', Evacuation::STATUS_TERMINE)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('b.id', 'b.libelle')
            ->orderBy('tempsMoyen', 'ASC')
            ->getQuery()
            ->getResult();

        // Performance par type d'évacuation
        $performanceType = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.typeEvacuation, COUNT(e.id) as total,
                     SUM(CASE WHEN e.status = :termine THEN 1 ELSE 0 END) as terminees,
                     AVG(TIMESTAMPDIFF(MINUTE, e.dateEvacuation, e.dateArrivee)) as tempsMoyen')
            ->where('e.region = :region')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('region', $region)
            ->setParameter('termine', Evacuation::STATUS_TERMINE)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('e.typeEvacuation')
            ->getQuery()
            ->getResult();

        // Pics horaires des évacuations
        $picsHoraires = $this->evacuationRepository->createQueryBuilder('e')
            ->select('HOUR(e.dateEvacuation) as hour, COUNT(e.id) as count')
            ->where('e.region = :region')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('region', $region)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('hour')
            ->orderBy('hour', 'ASC')
            ->getQuery()
            ->getResult();

        // Taux de résolution par urgence
        $tauxResolutionUrgence = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.urgence, COUNT(e.id) as total,
                     SUM(CASE WHEN e.status = :termine THEN 1 ELSE 0 END) as terminees')
            ->where('e.region = :region')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('region', $region)
            ->setParameter('termine', Evacuation::STATUS_TERMINE)
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('e.urgence')
            ->getQuery()
            ->getResult();

        return $this->render('direction_regionale/evacuations/statistiques.html.twig', [
            'region' => $region,
            'evolutionTemporelle' => $evolutionTemporelle,
            'currentYearData' => $currentYearData,
            'previousYearData' => $previousYearData,
            'currentYear' => $currentYear,
            'previousYear' => $previousYear,
            'statsBrigades' => $statsBrigades,
            'tempsMoyenBrigade' => $tempsMoyenBrigade,
            'performanceType' => $performanceType,
            'picsHoraires' => $picsHoraires,
            'tauxResolutionUrgence' => $tauxResolutionUrgence,
            'period' => $period,
        ]);
    }

    #[Route('/export', name: 'app_dr_evacuations_export', methods: ['GET'])]
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
        
        $evacuations = $this->evacuationRepository->createQueryBuilder('e')
            ->leftJoin('e.accident', 'a')
            ->leftJoin('e.brigade', 'b')
            ->leftJoin('e.operateur', 'u')
            ->addSelect('a', 'b', 'u')
            ->where('e.region = :region')
            ->andWhere('e.dateEvacuation >= :dateLimit')
            ->setParameter('region', $region)
            ->setParameter('dateLimit', $dateLimit)
            ->orderBy('e.dateEvacuation', 'DESC')
            ->getQuery()
            ->getResult();

        if ($format === 'csv') {
            $csv = "Référence;Date;Type;Statut;Urgence;Hôpital destination;Brigade;Opérateur;Accident;Date arrivée;Durée (min)\n";
            
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
                $csv .= ($evacuation->getBrigade() ? $evacuation->getBrigade()->getLibelle() : '') . ';';
                $csv .= ($evacuation->getCreatedBy() ? $evacuation->getCreatedBy()->getNom() . ' ' . $evacuation->getCreatedBy()->getPrenom() : '') . ';';
                $csv .= ($evacuation->getAccident() ? $evacuation->getAccident()->getReference() : '') . ';';
                $csv .= ($evacuation->getDateArrivee() ? $evacuation->getDateArrivee()->format('d/m/Y H:i') : '') . ';';
                $csv .= $duree . "\n";
            }

            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="evacuations_' . $region->getLibelle() . '_' . date('Y-m-d') . '.csv"');
            return $response;
        }

        $this->addFlash('error', 'Format d\'export non supporté.');
        return $this->redirectToRoute('app_dr_evacuations_dashboard');
    }

    #[Route('/{id}/coordonner', name: 'app_dr_evacuations_coordonner', methods: ['GET', 'POST'])]
    public function coordonner(Request $request, Evacuation $evacuation): Response
    {
        $region = $this->getUserRegion();
        
        if (!$region || $evacuation->getBrigade()->getRegion()->getId() !== $region->getId()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_dr_evacuations_dashboard');
        }

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
                        if ($brigade && $brigade->getRegion()->getId() === $region->getId()) {
                            $evacuation->setBrigade($brigade);
                            $this->addFlash('success', 'Évacuation réassignée avec succès.');
                        } else {
                            $this->addFlash('error', 'Brigade invalide ou hors région.');
                        }
                    }
                    break;
                case 'annuler':
                    $evacuation->setStatus(Evacuation::STATUS_ANNULE);
                    $this->addFlash('success', 'Évacuation annulée.');
                    break;
            }
            
            $this->entityManager->flush();
            return $this->redirectToRoute('app_dr_evacuations_supervision');
        }

        $brigades = $this->entityManager->getRepository(Brigade::class)->findBy(['region' => $region]);

        return $this->render('direction_regionale/evacuations/coordonner.html.twig', [
            'region' => $region,
            'evacuation' => $evacuation,
            'brigades' => $brigades,
        ]);
    }

    #[Route('/brigades/performance', name: 'app_dr_evacuations_brigades_performance', methods: ['GET'])]
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
            $total = $this->evacuationRepository->count(['brigade' => $brigade]);
            $enCours = $this->evacuationRepository->count(['brigade' => $brigade, 'status' => Evacuation::STATUS_EN_COURS]);
            $terminees = $this->evacuationRepository->count(['brigade' => $brigade, 'status' => Evacuation::STATUS_TERMINE]);
            $urgentes = $this->evacuationRepository->count(['brigade' => $brigade, 'urgence' => Evacuation::URGENCY_HAUTE]);
            
            // Temps moyen de traitement
            $tempsMoyen = $this->evacuationRepository->createQueryBuilder('e')
                ->where('e.brigade = :brigade')
                ->andWhere('e.status = :status')
                ->andWhere('e.dateArrivee IS NOT NULL')
                ->andWhere('e.dateEvacuation >= :date')
                ->setParameter('brigade', $brigade)
                ->setParameter('status', Evacuation::STATUS_TERMINE)
                ->setParameter('date', new \DateTimeImmutable('-30 days'))
                ->select('AVG(TIMESTAMPDIFF(MINUTE, e.dateEvacuation, e.dateArrivee))')
                ->getQuery()
                ->getSingleScalarResult();

            $performanceData[] = [
                'brigade' => $brigade,
                'total' => $total,
                'enCours' => $enCours,
                'terminees' => $terminees,
                'urgentes' => $urgentes,
                'tauxResolution' => $total > 0 ? round(($terminees / $total) * 100, 2) : 0,
                'tempsMoyen' => round($tempsMoyen ?? 0, 2)
            ];
        }

        return $this->render('direction_regionale/evacuations/brigades_performance.html.twig', [
            'region' => $region,
            'performanceData' => $performanceData,
        ]);
    }

    #[Route('/hopitaux', name: 'app_dr_evacuations_hopitaux', methods: ['GET'])]
    public function hopitaux(): Response
    {
        $region = $this->getUserRegion();
        
        if (!$region) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une région.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Statistiques des hôpitaux dans la région
        $hopitauxStats = $this->evacuationRepository->createQueryBuilder('e')
            ->select('e.hopitalDestination, COUNT(e.id) as total,
                     SUM(CASE WHEN e.urgence = :haute THEN 1 ELSE 0 END) as urgentes,
                     SUM(CASE WHEN e.status = :termine THEN 1 ELSE 0 END) as terminees,
                     AVG(TIMESTAMPDIFF(MINUTE, e.dateEvacuation, e.dateArrivee)) as tempsMoyen')
            ->where('e.region = :region')
            ->andWhere('e.hopitalDestination IS NOT NULL')
            ->andWhere('e.dateEvacuation >= :date')
            ->setParameter('region', $region)
            ->setParameter('haute', Evacuation::URGENCY_HAUTE)
            ->setParameter('termine', Evacuation::STATUS_TERMINE)
            ->setParameter('date', new \DateTimeImmutable('-30 days'))
            ->groupBy('e.hopitalDestination')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('direction_regionale/evacuations/hopitaux.html.twig', [
            'region' => $region,
            'hopitauxStats' => $hopitauxStats,
        ]);
    }
}
