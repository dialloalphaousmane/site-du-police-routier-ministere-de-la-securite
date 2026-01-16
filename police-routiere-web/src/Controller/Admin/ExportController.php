<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use App\Repository\ControleRepository;
use App\Repository\InfractionRepository;
use App\Repository\RegionRepository;
use App\Repository\BrigadeRepository;
use App\Repository\AmendeRepository;
use App\Repository\RapportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/export')]
#[IsGranted('ROLE_ADMIN')]
class ExportController extends AbstractController
{
    private $userRepository;
    private $controleRepository;
    private $infractionRepository;
    private $regionRepository;
    private $brigadeRepository;
    private $amendeRepository;
    private $rapportRepository;
    private $entityManager;

    public function __construct(
        UserRepository $userRepository,
        ControleRepository $controleRepository,
        InfractionRepository $infractionRepository,
        RegionRepository $regionRepository,
        BrigadeRepository $brigadeRepository,
        AmendeRepository $amendeRepository,
        RapportRepository $rapportRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->userRepository = $userRepository;
        $this->controleRepository = $controleRepository;
        $this->infractionRepository = $infractionRepository;
        $this->regionRepository = $regionRepository;
        $this->brigadeRepository = $brigadeRepository;
        $this->amendeRepository = $amendeRepository;
        $this->rapportRepository = $rapportRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/users', name: 'app_admin_export_users', methods: ['GET'])]
    public function exportUsers(): StreamedResponse
    {
        $users = $this->userRepository->findAll();
        
        $csvContent = "ID;Email;Nom;Prénom;Rôle(s);Actif;Date création\n";
        
        foreach ($users as $user) {
            $roles = implode(', ', $user->getRoles());
            $csvContent .= sprintf(
                "%d;%s;%s;%s;%s;%s;%s\n",
                $user->getId(),
                $user->getEmail(),
                $user->getNom() ?? '',
                $user->getPrenom() ?? '',
                $roles,
                $user->isActive() ? 'Oui' : 'Non',
                $user->getCreatedAt() ? $user->getCreatedAt()->format('d/m/Y H:i') : ''
            );
        }

        return $this->createCsvResponse('utilisateurs', $csvContent);
    }

    #[Route('/controls', name: 'app_admin_export_controls', methods: ['GET'])]
    public function exportControls(): StreamedResponse
    {
        $controls = $this->controleRepository->findAll();
        
        $csvContent = "ID;Date;Lieu;Marque véhicule;Immatriculation;Nom conducteur;Prénom conducteur;N° conducteur;Agent;Brigade;Région;Observations;Date création\n";
        
        foreach ($controls as $control) {
            $csvContent .= sprintf(
                "%d;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s\n",
                $control->getId(),
                $control->getDateControle() ? $control->getDateControle()->format('d/m/Y H:i') : '',
                $control->getLieuControle() ?? '',
                $control->getMarqueVehicule() ?? '',
                $control->getImmatriculation() ?? '',
                $control->getNomConducteur() ?? '',
                $control->getPrenomConducteur() ?? '',
                $control->getNoConducteur() ?? '',
                $control->getAgent() ? $control->getAgent()->getNom() . ' ' . $control->getAgent()->getPrenom() : '',
                $control->getBrigade() ? $control->getBrigade()->getLibelle() : '',
                $control->getBrigade() && $control->getBrigade()->getRegion() ? $control->getBrigade()->getRegion()->getLibelle() : '',
                $control->getObservation() ?? '',
                $control->getCreatedAt() ? $control->getCreatedAt()->format('d/m/Y H:i') : ''
            );
        }

        return $this->createCsvResponse('controles', $csvContent);
    }

    #[Route('/infractions', name: 'app_admin_export_infractions', methods: ['GET'])]
    public function exportInfractions(): StreamedResponse
    {
        $infractions = $this->infractionRepository->findAll();
        
        $csvContent = "ID;Référence;Libellé;Code;Description;Montant amende;Statut;Date création;Date mise à jour;Contrôle ID\n";
        
        foreach ($infractions as $infraction) {
            $csvContent .= sprintf(
                "%d;%s;%s;%s;%s;%s;%s;%s;%s;%d\n",
                $infraction->getId(),
                $infraction->getReference() ?? '',
                $infraction->getLibelle() ?? '',
                $infraction->getCode() ?? '',
                $infraction->getDescription() ?? '',
                $infraction->getMontantAmende() ?? '0',
                $infraction->getStatut() ?? 'NON_PAYEE',
                $infraction->getCreatedAt() ? $infraction->getCreatedAt()->format('d/m/Y H:i') : '',
                $infraction->getUpdatedAt() ? $infraction->getUpdatedAt()->format('d/m/Y H:i') : '',
                $infraction->getControle() ? $infraction->getControle()->getId() : 0
            );
        }

        return $this->createCsvResponse('infractions', $csvContent);
    }

    #[Route('/amendes', name: 'app_admin_export_amendes', methods: ['GET'])]
    public function exportAmendes(): StreamedResponse
    {
        $amendes = $this->amendeRepository->findAll();
        
        $csvContent = "ID;Référence;Montant total;Montant payé;Statut;Date création;Date paiement;Infraction ID;Infraction libellé\n";
        
        foreach ($amendes as $amende) {
            $csvContent .= sprintf(
                "%d;%s;%s;%s;%s;%s;%s;%d;%s\n",
                $amende->getId(),
                $amende->getReference() ?? '',
                $amende->getMontantTotal() ?? '0',
                $amende->getMontantPaye() ?? '0',
                $amende->getStatut() ?? 'EN_ATTENTE',
                $amende->getCreatedAt() ? $amende->getCreatedAt()->format('d/m/Y H:i') : '',
                $amende->getDatePaiement() ? $amende->getDatePaiement()->format('d/m/Y H:i') : '',
                $amende->getInfraction() ? $amende->getInfraction()->getId() : 0,
                $amende->getInfraction() ? $amende->getInfraction()->getLibelle() : ''
            );
        }

        return $this->createCsvResponse('amendes', $csvContent);
    }

    #[Route('/regions', name: 'app_admin_export_regions', methods: ['GET'])]
    public function exportRegions(): StreamedResponse
    {
        $regions = $this->regionRepository->findAll();
        
        $csvContent = "ID;Code;Nom;Directeur;Email;Téléphone;Adresse;Actif;Date création;Nombre de brigades;Nombre d'agents\n";
        
        foreach ($regions as $region) {
            try {
                $csvContent .= sprintf(
                    "%d;%s;%s;%s;%s;%s;%s;%s;%s;%d;%d\n",
                    $region->getId(),
                    $region->getCode() ?? '',
                    $region->getLibelle() ?? '',
                    $region->getDirecteur() ?? '',
                    $region->getEmail() ?? '',
                    $region->getTelephone() ?? '',
                    $region->getAdresse() ?? '',
                    method_exists($region, 'isActif') ? ($region->isActif() ? 'Oui' : 'Non') : 'Inconnu',
                    $region->getCreatedAt() ? $region->getCreatedAt()->format('d/m/Y H:i') : '',
                    method_exists($region, 'getBrigades') ? count($region->getBrigades()) : 0,
                    method_exists($region, 'getAgents') ? count($region->getAgents()) : 0
                );
            } catch (\Exception $e) {
                // En cas d'erreur, ajouter une ligne avec des valeurs par défaut
                $csvContent .= sprintf(
                    "%d;%s;%s;%s;%s;%s;%s;%s;%s;%d;%d\n",
                    $region->getId() ?? 0,
                    'ERREUR',
                    'ERREUR',
                    'ERREUR',
                    'ERREUR',
                    'ERREUR',
                    'ERREUR',
                    'ERREUR',
                    'ERREUR',
                    0,
                    0
                );
            }
        }

        return $this->createCsvResponse('regions', $csvContent);
    }

    #[Route('/brigades', name: 'app_admin_export_brigades', methods: ['GET'])]
    public function exportBrigades(): StreamedResponse
    {
        $brigades = $this->brigadeRepository->findAll();
        
        $csvContent = "ID;Code;Nom;Chef;Email;Téléphone;Localité;Région;Actif;Date création;Nombre d'agents\n";
        
        foreach ($brigades as $brigade) {
            try {
                $csvContent .= sprintf(
                    "%d;%s;%s;%s;%s;%s;%s;%s;%s;%s;%d\n",
                    $brigade->getId(),
                    $brigade->getCode() ?? '',
                    $brigade->getLibelle() ?? '',
                    $brigade->getChef() ?? '',
                    $brigade->getEmail() ?? '',
                    $brigade->getTelephone() ?? '',
                    $brigade->getLocalite() ?? '',
                    $brigade->getRegion() ? $brigade->getRegion()->getLibelle() : '',
                    method_exists($brigade, 'isActif') ? ($brigade->isActif() ? 'Oui' : 'Non') : 'Inconnu',
                    $brigade->getCreatedAt() ? $brigade->getCreatedAt()->format('d/m/Y H:i') : '',
                    method_exists($brigade, 'getAgents') ? count($brigade->getAgents()) : 0
                );
            } catch (\Exception $e) {
                // En cas d'erreur, ajouter une ligne avec des valeurs par défaut
                $csvContent .= sprintf(
                    "%d;%s;%s;%s;%s;%s;%s;%s;%s;%s;%d\n",
                    $brigade->getId() ?? 0,
                    'ERREUR',
                    'ERREUR',
                    'ERREUR',
                    'ERREUR',
                    'ERREUR',
                    'ERREUR',
                    'ERREUR',
                    'ERREUR',
                    'ERREUR',
                    0
                );
            }
        }

        return $this->createCsvResponse('brigades', $csvContent);
    }

    #[Route('/rapports', name: 'app_admin_export_rapports', methods: ['GET'])]
    public function exportRapports(): StreamedResponse
    {
        $rapports = $this->rapportRepository->findAll();
        
        $csvContent = "ID;Titre;Type;Auteur;Statut;Date création;Date validation;Région;Brigade;Observations\n";
        
        foreach ($rapports as $rapport) {
            $csvContent .= sprintf(
                "%d;%s;%s;%s;%s;%s;%s;%s;%s;%s\n",
                $rapport->getId(),
                $rapport->getTitre(),
                $rapport->getType(),
                $rapport->getAuteur() ? $rapport->getAuteur()->getEmail() : '',
                $rapport->getStatut(),
                $rapport->getDateCreation()->format('d/m/Y H:i'),
                $rapport->getDateValidation() ? $rapport->getDateValidation()->format('d/m/Y H:i') : '',
                $rapport->getRegion() ? $rapport->getRegion()->getLibelle() : '',
                $rapport->getBrigade() ? $rapport->getBrigade()->getLibelle() : '',
                $rapport->getObservations() ?? ''
            );
        }

        return $this->createCsvResponse('rapports', $csvContent);
    }

    #[Route('/statistics', name: 'app_admin_export_statistics', methods: ['GET'])]
    public function exportStatistics(): StreamedResponse
    {
        $csvContent = "Rapport d'export des statistiques - Police Routière Guinée\n";
        $csvContent .= "Date de génération: " . date('d/m/Y H:i:s') . "\n\n";
        
        // Statistiques utilisateurs
        $totalUsers = $this->userRepository->count([]);
        $activeUsers = $this->userRepository->count(['isActive' => true]);
        $csvContent .= "STATISTIQUES UTILISATEURS\n";
        $csvContent .= "Total utilisateurs;{$totalUsers}\n";
        $csvContent .= "Utilisateurs actifs;{$activeUsers}\n";
        $csvContent .= "Utilisateurs inactifs;" . ($totalUsers - $activeUsers) . "\n\n";
        
        // Statistiques contrôles
        $totalControls = $this->controleRepository->count([]);
        $todayControls = $this->controleRepository->createQueryBuilder('c')
            ->where('c.date >= :today')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getSingleScalarResult();
        
        $csvContent .= "STATISTIQUES CONTRÔLES\n";
        $csvContent .= "Total contrôles;{$totalControls}\n";
        $csvContent .= "Contrôles aujourd'hui;{$todayControls}\n\n";
        
        // Statistiques infractions
        $totalInfractions = $this->infractionRepository->count([]);
        $csvContent .= "STATISTIQUES INFRACTIONS\n";
        $csvContent .= "Total infractions;{$totalInfractions}\n\n";
        
        // Statistiques régions
        $totalRegions = $this->regionRepository->count([]);
        $activeRegions = $this->entityManager->createQuery('SELECT COUNT(r.id) FROM App\Entity\Region r WHERE r.actif = true')
            ->getSingleScalarResult();
        
        $csvContent .= "STATISTIQUES RÉGIONS\n";
        $csvContent .= "Total régions;{$totalRegions}\n";
        $csvContent .= "Régions actives;{$activeRegions}\n\n";
        
        // Statistiques brigades
        $totalBrigades = $this->brigadeRepository->count([]);
        $activeBrigades = $this->entityManager->createQuery('SELECT COUNT(b.id) FROM App\Entity\Brigade b WHERE b.actif = true')
            ->getSingleScalarResult();
        
        $csvContent .= "STATISTIQUES BRIGADES\n";
        $csvContent .= "Total brigades;{$totalBrigades}\n";
        $csvContent .= "Brigades actives;{$activeBrigades}\n\n";
        
        // Statistiques revenus
        $totalRevenue = $this->amendeRepository->createQueryBuilder('a')
            ->select('SUM(a.montant)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        $csvContent .= "STATISTIQUES REVENUS\n";
        $csvContent .= "Total revenus;{$totalRevenue} GNF\n\n";
        
        // Statistiques rapports
        $totalRapports = $this->rapportRepository->count([]);
        $rapportsByStatus = $this->entityManager->createQuery('SELECT r.statut, COUNT(r.id) FROM App\Entity\Rapport r GROUP BY r.statut')
            ->getResult();
        
        $csvContent .= "STATISTIQUES RAPPORTS\n";
        $csvContent .= "Total rapports;{$totalRapports}\n";
        
        foreach ($rapportsByStatus as $stat) {
            $csvContent .= "Rapports {$stat['statut']};{$stat[1]}\n";
        }

        return $this->createCsvResponse('statistiques', $csvContent);
    }

    #[Route('/excel', name: 'app_admin_export_excel', methods: ['GET'])]
    public function exportExcel(): Response
    {
        // Création d'un fichier Excel (format CSV compatible Excel)
        $data = [
            ['Type', 'Total', 'Actifs', 'Inactifs', 'Date'],
            ['Utilisateurs', $this->userRepository->count([]), $this->userRepository->count(['isActive' => true]), $this->userRepository->count(['isActive' => false]), date('d/m/Y')],
            ['Régions', $this->regionRepository->count([]), $this->entityManager->createQuery('SELECT COUNT(r.id) FROM App\Entity\Region r WHERE r.actif = true')->getSingleScalarResult(), $this->entityManager->createQuery('SELECT COUNT(r.id) FROM App\Entity\Region r WHERE r.actif = false')->getSingleScalarResult(), date('d/m/Y')],
            ['Brigades', $this->brigadeRepository->count([]), $this->entityManager->createQuery('SELECT COUNT(b.id) FROM App\Entity\Brigade b WHERE b.actif = true')->getSingleScalarResult(), $this->entityManager->createQuery('SELECT COUNT(b.id) FROM App\Entity\Brigade b WHERE b.actif = false')->getSingleScalarResult(), date('d/m/Y')],
            ['Contrôles', $this->controleRepository->count([]), '-', '-', '-', date('d/m/Y')],
            ['Infractions', $this->infractionRepository->count([]), '-', '-', '-', date('d/m/Y')],
            ['Amendes', $this->amendeRepository->count([]), '-', '-', '-', date('d/m/Y')],
            ['Rapports', $this->rapportRepository->count([]), '-', '-', '-', date('d/m/Y')]
        ];

        $csvContent = '';
        foreach ($data as $row) {
            $csvContent .= implode(';', $row) . "\n";
        }

        return $this->createCsvResponse('export_general', $csvContent);
    }

    private function createCsvResponse(string $filename, string $content): StreamedResponse
    {
        $response = new StreamedResponse();
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '_' . date('Y-m-d') . '.csv"');
        $response->headers->set('Content-Length', strlen($content));
        
        $response->setCallback(function() use ($content) {
            echo "\xEF\xBB\xBF"; // BOM UTF-8
            echo $content;
        });

        return $response;
    }
}
