<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use App\Repository\ControleRepository;
use App\Repository\InfractionRepository;
use App\Repository\AmendeRepository;
use App\Repository\RegionRepository;
use App\Repository\BrigadeRepository;
use App\Repository\RapportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/export')]
#[IsGranted('ROLE_ADMIN')]
class AdminExportController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private ControleRepository $controleRepository,
        private InfractionRepository $infractionRepository,
        private AmendeRepository $amendeRepository,
        private RegionRepository $regionRepository,
        private BrigadeRepository $brigadeRepository,
        private RapportRepository $rapportRepository
    ) {}

    private function generateCSV(array $data, string $filename): Response
    {
        $response = new Response();
        $response->setContent($this->arrayToCSV($data));
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    private function arrayToCSV(array $data, string $delimiter = ';'): string
    {
        if (empty($data)) {
            return '';
        }

        $f = fopen('php://memory', 'r+');
        fwrite($f, "\xEF\xBB\xBF"); // BOM UTF-8

        // Headers
        $headers = array_keys((array)$data[0]);
        fputcsv($f, $headers, $delimiter);

        // Data
        foreach ($data as $row) {
            fputcsv($f, (array)$row, $delimiter);
        }

        rewind($f);
        $csv = stream_get_contents($f);
        fclose($f);

        return $csv;
    }

    #[Route('/users', name: 'app_admin_export_users')]
    public function exportUsers(): Response
    {
        $users = $this->userRepository->findAll();
        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'Email' => $user->getEmail(),
                'Nom' => $user->getNom(),
                'Prénom' => $user->getPrenom(),
                'Téléphone' => is_callable([$user, 'getTelephone'])
                    ? (string) call_user_func([$user, 'getTelephone'])
                    : (is_callable([$user, 'getPhone'])
                        ? (string) call_user_func([$user, 'getPhone'])
                        : ''),
                'Rôles' => implode(', ', $user->getRoles()),
                'Actif' => $user->isActive() ? 'Oui' : 'Non',
                'Créé le' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        return $this->generateCSV($data, 'utilisateurs_' . date('Y-m-d_H-i-s') . '.csv');
    }

    #[Route('/controls', name: 'app_admin_export_controls')]
    public function exportControls(): Response
    {
        $controls = $this->controleRepository->findAll();
        $data = [];

        foreach ($controls as $control) {
            $data[] = [
                'ID' => $control->getId(),
                'Agent' => $control->getAgent()
                    ? trim(($control->getAgent()?->getNom() ?? '') . ' ' . ($control->getAgent()?->getPrenom() ?? ''))
                    : '',
                'Date' => $control->getDateControle()?->format('Y-m-d H:i:s'),
                'Lieu' => $control->getLieuControle(),
                'Marque Véhicule' => $control->getMarqueVehicule(),
                'Immatriculation' => $control->getImmatriculation(),
                'Conducteur' => $control->getNomConducteur(),
                'Observations' => $control->getObservation(),
            ];
        }

        return $this->generateCSV($data, 'controles_' . date('Y-m-d_H-i-s') . '.csv');
    }

    #[Route('/infractions', name: 'app_admin_export_infractions')]
    public function exportInfractions(): Response
    {
        $infractions = $this->infractionRepository->findAll();
        $data = [];

        foreach ($infractions as $infraction) {
            $data[] = [
                'ID' => $infraction->getId(),
                'Contôle' => $infraction->getControle()?->getId(),
                'Code' => $infraction->getCode(),
                'Description' => $infraction->getDescription(),
                'Montant Amende' => $infraction->getMontantAmende(),
                'Date' => $infraction->getCreatedAt()?->format('Y-m-d'),
            ];
        }

        return $this->generateCSV($data, 'infractions_' . date('Y-m-d_H-i-s') . '.csv');
    }

    #[Route('/amendes', name: 'app_admin_export_amendes')]
    public function exportAmendes(): Response
    {
        $amendes = $this->amendeRepository->findAll();
        $data = [];

        foreach ($amendes as $amende) {
            $data[] = [
                'Référence' => $amende->getReference(),
                'Infraction' => $amende->getInfraction()?->getCode(),
                'Montant Total' => $amende->getMontantTotal(),
                'Montant Payé' => $amende->getMontantPaye(),
                'Statut' => $amende->getStatut(),
                'Date Création' => $amende->getCreatedAt()?->format('Y-m-d'),
                'Date Paiement' => $amende->getDatePaiement()?->format('Y-m-d'),
            ];
        }

        return $this->generateCSV($data, 'amendes_' . date('Y-m-d_H-i-s') . '.csv');
    }

    #[Route('/regions', name: 'app_admin_export_regions')]
    public function exportRegions(): Response
    {
        $regions = $this->regionRepository->findAll();
        $data = [];

        foreach ($regions as $region) {
            $data[] = [
                'Code' => $region->getCode(),
                'Nom' => $region->getLibelle(),
                'Description' => $region->getDescription(),
                'Directeur' => $region->getDirecteur(),
                'Email' => $region->getEmail(),
                'Téléphone' => $region->getTelephone(),
                'Adresse' => $region->getAdresse(),
                'Actif' => $region->isActif() ? 'Oui' : 'Non',
            ];
        }

        return $this->generateCSV($data, 'regions_' . date('Y-m-d_H-i-s') . '.csv');
    }

    #[Route('/brigades', name: 'app_admin_export_brigades')]
    public function exportBrigades(): Response
    {
        $brigades = $this->brigadeRepository->findAll();
        $data = [];

        foreach ($brigades as $brigade) {
            $data[] = [
                'Code' => $brigade->getCode(),
                'Nom' => $brigade->getLibelle(),
                'Région' => $brigade->getRegion()?->getLibelle(),
                'Chef' => $brigade->getChef(),
                'Email' => $brigade->getEmail(),
                'Téléphone' => $brigade->getTelephone(),
                'Localité' => $brigade->getLocalite(),
                'Actif' => $brigade->isActif() ? 'Oui' : 'Non',
            ];
        }

        return $this->generateCSV($data, 'brigades_' . date('Y-m-d_H-i-s') . '.csv');
    }

    #[Route('/rapports', name: 'app_admin_export_rapports')]
    public function exportRapports(): Response
    {
        $rapports = $this->rapportRepository->findAll();
        $data = [];

        foreach ($rapports as $rapport) {
            $data[] = [
                'ID' => $rapport->getId(),
                'Titre' => $rapport->getTitre(),
                'Auteur' => $rapport->getAuteur()?->getEmail(),
                'Statut' => $rapport->getStatut(),
                'Date Création' => $rapport->getDateCreation()?->format('Y-m-d'),
                'Date Validation' => $rapport->getDateValidation()?->format('Y-m-d'),
            ];
        }

        return $this->generateCSV($data, 'rapports_' . date('Y-m-d_H-i-s') . '.csv');
    }
}
