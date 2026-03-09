<?php

namespace App\Controller\Admin;

use App\Entity\Accident;
use App\Entity\AccidentVictim;
use App\Entity\AccidentVehicle;
use App\Entity\Region;
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

#[Route('/admin/accident')]
#[IsGranted('ROLE_ADMIN')]
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

    #[Route('/', name: 'app_admin_accident_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $status = $request->query->get('status');
        $gravity = $request->query->get('gravity');
        $region = $request->query->get('region');
        $search = $request->query->get('search');

        $qb = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.region', 'r')
            ->leftJoin('a.brigade', 'b')
            ->leftJoin('a.createdBy', 'u')
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

        return $this->render('admin/accident/index.html.twig', [
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

    #[Route('/carte', name: 'app_admin_accident_carte', methods: ['GET'])]
    public function carte(Request $request): Response
    {
        $period = $request->query->get('period', '7');
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

        return $this->render('admin/accident/carte.html.twig', [
            'accidents' => $accidents,
            'regions' => $regions,
            'period' => $period,
            'currentFilters' => [
                'gravity' => $gravity,
                'region' => $region,
            ],
        ]);
    }

    #[Route('/dashboard', name: 'app_admin_accident_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $totalAccidents = $this->accidentRepository->count([]);
        $accidentsEnCours = $this->accidentRepository->count(['status' => Accident::STATUS_EN_COURS]);
        $accidentsMortels = $this->accidentRepository->count(['gravite' => Accident::GRAVITY_MORTEL]);
        $accidentsEvacuation = $this->accidentRepository->count(['status' => Accident::STATUS_EVACUATION]);

        $evolutionMensuelle = $this->accidentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id) as count, DATE_FORMAT(a.dateAccident, \'%Y-%m\') as month, MONTHNAME(a.dateAccident) as monthName')
            ->where('a.dateAccident >= :date')
            ->setParameter('date', new \DateTimeImmutable('-6 months'))
            ->groupBy('month', 'monthName')
            ->orderBy('month', 'DESC')
            ->getQuery()
            ->getResult();

        $repartitionGraviteRaw = $this->accidentRepository->createQueryBuilder('a')
            ->select('a.gravite as gravite, COUNT(a.id) as count')
            ->groupBy('a.gravite')
            ->getQuery()
            ->getResult();

        $repartitionGravite = [];
        foreach ($repartitionGraviteRaw as $row) {
            $repartitionGravite[$row['gravite'] ?? ''] = (int) $row['count'];
        }

        $derniersAccidents = $this->accidentRepository->createQueryBuilder('a')
            ->orderBy('a.dateAccident', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $accidentsParRegionRaw = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.region', 'r')
            ->select('r.libelle as region, COUNT(a.id) as count')
            ->where('r.id IS NOT NULL')
            ->groupBy('r.id', 'r.libelle')
            ->orderBy('count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $accidentsParRegion = [];
        foreach ($accidentsParRegionRaw as $row) {
            $accidentsParRegion[$row['region'] ?? ''] = (int) $row['count'];
        }

        return $this->render('admin/accident/dashboard.html.twig', [
            'totalAccidents' => $totalAccidents,
            'accidentsEnCours' => $accidentsEnCours,
            'accidentsMortels' => $accidentsMortels,
            'accidentsEvacuation' => $accidentsEvacuation,
            'evolutionMensuelle' => $evolutionMensuelle,
            'repartitionGravite' => $repartitionGravite,
            'derniersAccidents' => $derniersAccidents,
            'accidentsParRegion' => $accidentsParRegion,
        ]);
    }

    #[Route('/new', name: 'app_admin_accident_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $accident = new Accident();
        $form = $this->createForm(AccidentType::class, $accident);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération automatique de la référence
            $accident->setCreatedBy($this->getUser());
            $accident->setReference($this->generateReference());
            
            $this->entityManager->persist($accident);
            $this->entityManager->flush();

            $this->addFlash('success', 'Accident créé avec succès.');
            return $this->redirectToRoute('app_admin_accident_show', ['id' => $accident->getId()]);
        }

        return $this->render('admin/accident/new.html.twig', [
            'accident' => $accident,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_accident_show', methods: ['GET'])]
    public function show(Accident $accident): Response
    {
        $victims = $this->victimRepository->findBy(['accident' => $accident], ['id' => 'ASC']);
        $vehicles = $this->vehicleRepository->findBy(['accident' => $accident], ['id' => 'ASC']);

        return $this->render('admin/accident/show.html.twig', [
            'accident' => $accident,
            'victims' => $victims,
            'vehicles' => $vehicles,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_accident_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Accident $accident): Response
    {
        $form = $this->createForm(AccidentType::class, $accident);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Accident modifié avec succès.');
            return $this->redirectToRoute('app_admin_accident_show', ['id' => $accident->getId()]);
        }

        return $this->render('admin/accident/edit.html.twig', [
            'accident' => $accident,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_accident_delete', methods: ['POST'])]
    public function delete(Request $request, Accident $accident): Response
    {
        if ($this->isCsrfTokenValid('delete'.$accident->getId(), $request->request->get('_token'))) {
            // Suppression des victimes et véhicules associés
            foreach ($accident->getVictims() as $victim) {
                $this->entityManager->remove($victim);
            }
            foreach ($accident->getVehicles() as $vehicle) {
                $this->entityManager->remove($vehicle);
            }
            
            $this->entityManager->remove($accident);
            $this->entityManager->flush();
            $this->addFlash('success', 'Accident supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_admin_accident_index');
    }

    #[Route('/{id}/victim/add', name: 'app_admin_accident_victim_add', methods: ['GET', 'POST'])]
    public function addVictim(Request $request, Accident $accident): Response
    {
        $victim = new AccidentVictim();
        $victim->setAccident($accident);
        
        $form = $this->createForm(\App\Form\AccidentVictimType::class, $victim);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($victim);
            $this->entityManager->flush();
            $this->addFlash('success', 'Victime ajoutée avec succès.');
            return $this->redirectToRoute('app_admin_accident_show', ['id' => $accident->getId()]);
        }

        return $this->render('admin/accident/victim_add.html.twig', [
            'accident' => $accident,
            'victim' => $victim,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/vehicle/add', name: 'app_admin_accident_vehicle_add', methods: ['GET', 'POST'])]
    public function addVehicle(Request $request, Accident $accident): Response
    {
        $vehicle = new AccidentVehicle();
        $vehicle->setAccident($accident);
        
        $form = $this->createForm(\App\Form\AccidentVehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($vehicle);
            $this->entityManager->flush();
            $this->addFlash('success', 'Véhicule ajouté avec succès.');
            return $this->redirectToRoute('app_admin_accident_show', ['id' => $accident->getId()]);
        }

        return $this->render('admin/accident/vehicle_add.html.twig', [
            'accident' => $accident,
            'vehicle' => $vehicle,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{victimId}/victim/delete', name: 'app_admin_accident_victim_delete', methods: ['POST'])]
    public function deleteVictim(Request $request, AccidentVictim $victim): Response
    {
        $accident = $victim->getAccident();
        
        if ($this->isCsrfTokenValid('delete_victim'.$victim->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($victim);
            $this->entityManager->flush();
            $this->addFlash('success', 'Victime supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_admin_accident_show', ['id' => $accident->getId()]);
    }

    #[Route('/{vehicleId}/vehicle/delete', name: 'app_admin_accident_vehicle_delete', methods: ['POST'])]
    public function deleteVehicle(Request $request, AccidentVehicle $vehicle): Response
    {
        $accident = $vehicle->getAccident();
        
        if ($this->isCsrfTokenValid('delete_vehicle'.$vehicle->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($vehicle);
            $this->entityManager->flush();
            $this->addFlash('success', 'Véhicule supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_admin_accident_show', ['id' => $accident->getId()]);
    }

    #[Route('/statistics', name: 'app_admin_accident_statistics', methods: ['GET'])]
    public function statistics(): Response
    {
        $totalAccidents = $this->accidentRepository->count([]);
        
        $byStatus = [];
        $statuses = [Accident::STATUS_EN_COURS, Accident::STATUS_TRAITE, Accident::STATUS_ARCHIVE, Accident::STATUS_EVACUATION];
        foreach ($statuses as $status) {
            $byStatus[$status] = $this->accidentRepository->count(['status' => $status]);
        }

        $byGravity = [];
        $gravities = [Accident::GRAVITY_MORTEL, Accident::GRAVITY_GRAVE, Accident::GRAVITY_URGENT, Accident::GRAVITY_LEGER];
        foreach ($gravities as $gravity) {
            $byGravity[$gravity] = $this->accidentRepository->count(['gravite' => $gravity]);
        }

        $byRegion = [];
        $regions = $this->entityManager->getRepository(Region::class)->findAll();
        foreach ($regions as $region) {
            $count = $this->accidentRepository->createQueryBuilder('a')
                ->where('a.region = :region')
                ->setParameter('region', $region)
                ->select('COUNT(a.id)')
                ->getQuery()
                ->getSingleScalarResult();
            $byRegion[$region->getLibelle()] = $count;
        }

        $byMonth = $this->accidentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id) as count, DATE_FORMAT(a.dateAccident, \'%Y-%m\') as month')
            ->groupBy('month')
            ->orderBy('month', 'DESC')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();

        $byCause = [];
        foreach (Accident::CAUSES as $cause => $label) {
            $count = $this->accidentRepository->createQueryBuilder('a')
                ->where('a.causePrincipale = :cause')
                ->setParameter('cause', $cause)
                ->select('COUNT(a.id)')
                ->getQuery()
                ->getSingleScalarResult();
            $byCause[$label] = $count;
        }

        return $this->render('admin/accident/statistics.html.twig', [
            'totalAccidents' => $totalAccidents,
            'byStatus' => $byStatus,
            'byGravity' => $byGravity,
            'byRegion' => $byRegion,
            'byMonth' => $byMonth,
            'byCause' => $byCause,
        ]);
    }

    #[Route('/export', name: 'app_admin_accident_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $format = $request->query->get('format', 'csv');
        
        $accidents = $this->accidentRepository->createQueryBuilder('a')
            ->leftJoin('a.region', 'r')
            ->leftJoin('a.brigade', 'b')
            ->leftJoin('a.createdBy', 'u')
            ->addSelect('r', 'b', 'u')
            ->orderBy('a.dateAccident', 'DESC')
            ->getQuery()
            ->getResult();

        if ($format === 'csv') {
            $csv = "Référence;Date;Lieu;Statut;Gravité;Cause;Région;Brigade;Déclarant;Victimes;Véhicules\n";
            
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
                $csv .= count($accident->getVictims()) . ';';
                $csv .= count($accident->getVehicles()) . "\n";
            }

            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="accidents_' . date('Y-m-d') . '.csv"');
            return $response;
        }

        $this->addFlash('error', 'Format d\'export non supporté.');
        return $this->redirectToRoute('app_admin_accident_index');
    }

    private function generateReference(): string
    {
        $date = new \DateTime();
        $year = $date->format('Y');
        $month = $date->format('m');
        
        // Compter le nombre d'accidents ce mois
        $count = $this->accidentRepository->createQueryBuilder('a')
            ->where('DATE_FORMAT(a.dateAccident, \'%Y%m\') = :period')
            ->setParameter('period', $year . $month)
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        return 'ACC-' . $year . $month . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
