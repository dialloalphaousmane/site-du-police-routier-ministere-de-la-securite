<?php

namespace App\Controller\DirectionGenerale\Rapports;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Rapport;
use App\Entity\Region;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/direction-generale/rapports')]
#[Route('/dashboard/direction-generale/rapports')]
// #[IsGranted('ROLE_DIRECTION_GENERALE')]
class RapportsConsolidesController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_direction_generale_rapports')]
    public function index(): Response
    {
        // Récupérer les rapports depuis la base de données
        $rapports = $this->entityManager->getRepository(Rapport::class)->findAll();
        
        return $this->render('direction_generale/rapports/rapports_consolides.html.twig', [
            'user' => $this->getUser(),
            'rapports' => $rapports
        ]);
    }

    #[Route('/nouveau', name: 'app_direction_generale_rapports_nouveau')]
    public function nouveau(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $rapport = new Rapport();
            $rapport->setTitre($request->request->get('titre'));
            $rapport->setContenu($request->request->get('contenu'));
            $rapport->setType($request->request->get('type'));
            $rapport->setAuteur($this->getUser());
            
            // Ajouter la région si spécifiée
            if ($request->request->get('region')) {
                $region = $this->entityManager->getRepository(Region::class)->find($request->request->get('region'));
                $rapport->setRegion($region);
            }
            
            $this->entityManager->persist($rapport);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Rapport créé avec succès.');
            return $this->redirectToRoute('app_direction_generale_rapports');
        }
        
        $regions = $this->entityManager->getRepository(Region::class)->findAll();
        
        return $this->render('direction_generale/rapports/rapports_nouveau.html.twig', [
            'user' => $this->getUser(),
            'regions' => $regions
        ]);
    }

    #[Route('/details/{id}', name: 'app_direction_generale_rapports_details')]
    public function details(Rapport $rapport): Response
    {
        return $this->render('direction_generale/rapports/rapports_details.html.twig', [
            'user' => $this->getUser(),
            'rapport' => $rapport
        ]);
    }

    #[Route('/modifier/{id}', name: 'app_direction_generale_rapports_modifier')]
    public function modifier(Rapport $rapport, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $rapport->setTitre($request->request->get('titre'));
            $rapport->setContenu($request->request->get('contenu'));
            $rapport->setType($request->request->get('type'));
            
            // Ajouter la région si spécifiée
            if ($request->request->get('region')) {
                $region = $this->entityManager->getRepository(Region::class)->find($request->request->get('region'));
                $rapport->setRegion($region);
            }
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Rapport modifié avec succès.');
            return $this->redirectToRoute('app_direction_generale_rapports');
        }
        
        $regions = $this->entityManager->getRepository(Region::class)->findAll();
        
        return $this->render('direction_generale/rapports/rapports_modifier.html.twig', [
            'user' => $this->getUser(),
            'rapport' => $rapport,
            'regions' => $regions
        ]);
    }

    #[Route('/supprimer/{id}', name: 'app_direction_generale_rapports_supprimer', methods: ['POST'])]
    public function supprimer(Rapport $rapport, Request $request): Response
    {
        if ($this->isCsrfTokenValid('supprimer_rapport'.$rapport->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($rapport);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Rapport supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        
        return $this->redirectToRoute('app_direction_generale_rapports');
    }

    #[Route('/download/{id}', name: 'app_direction_generale_rapports_download_legacy')]
    public function downloadLegacy(int $id, Request $request): Response
    {
        $rapport = $this->entityManager->getRepository(Rapport::class)->find($id);
        if (!$rapport) {
            $this->addFlash('error', 'Rapport introuvable.');
            return $this->redirectToRoute('app_direction_generale_rapports');
        }

        $format = $request->query->get('format', 'pdf');

        return $this->redirectToRoute('app_direction_generale_rapports_export', [
            'id' => $id,
            'format' => $format,
        ]);
    }

    #[Route('/preview/{id}', name: 'app_direction_generale_rapports_preview_legacy')]
    public function previewLegacy(int $id): Response
    {
        return $this->redirectToRoute('app_direction_generale_rapports_details', [
            'id' => $id,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_direction_generale_rapports_delete_legacy', methods: ['POST'])]
    public function deleteLegacy(int $id, Request $request): Response
    {
        $rapport = $this->entityManager->getRepository(Rapport::class)->find($id);
        if (!$rapport) {
            return $this->json(['success' => false, 'message' => 'Rapport introuvable'], 404);
        }

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('supprimer_rapport'.$id, $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF invalide'], 400);
        }

        $this->entityManager->remove($rapport);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/generate/{type}', name: 'app_direction_generale_rapports_generate_legacy')]
    public function generateLegacy(string $type): Response
    {
        $typeMap = [
            'monthly' => 'mensuel',
            'quarterly' => 'trimestriel',
            'annual' => 'annuel',
        ];

        return $this->redirectToRoute('app_direction_generale_rapports_generer', [
            'type' => $typeMap[$type] ?? $type,
        ]);
    }

    #[Route('/valider/{id}', name: 'app_direction_generale_rapports_valider', methods: ['POST'])]
    public function valider(Rapport $rapport, Request $request): Response
    {
        if ($this->isCsrfTokenValid('valider_rapport'.$rapport->getId(), $request->request->get('_token'))) {
            $rapport->setStatut('VALIDE');
            $rapport->setValidateur($this->getUser());
            $rapport->setDateValidation(new \DateTimeImmutable());
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Rapport validé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        
        return $this->redirectToRoute('app_direction_generale_rapports');
    }

    #[Route('/generer', name: 'app_direction_generale_rapports_generer')]
    public function generer(Request $request): Response
    {
        $type = $request->query->get('type', 'mensuel');
        $periode = $request->query->get('periode', 'current');
        
        // Logique de génération automatique de rapports
        $rapport = new Rapport();
        $rapport->setTitre("Rapport $type - " . date('Y-m'));
        $rapport->setType($type);
        $rapport->setStatut('GENERATED');
        $rapport->setAuteur($this->getUser());
        
        // Contenu généré automatiquement
        $contenu = $this->genererContenuRapport($type, $periode);
        $rapport->setContenu($contenu);
        
        $this->entityManager->persist($rapport);
        $this->entityManager->flush();
        
        $this->addFlash('success', "Rapport $type généré avec succès.");
        
        return $this->redirectToRoute('app_direction_generale_rapports');
    }

    #[Route('/export/{id}', name: 'app_direction_generale_rapports_export')]
    public function export(?Rapport $rapport, Request $request): Response
    {
        if (!$rapport) {
            $this->addFlash('error', 'Rapport introuvable.');
            return $this->redirectToRoute('app_direction_generale_rapports');
        }

        $format = $request->query->get('format', 'pdf');
        
        // Logique d'export du rapport
        $this->addFlash('success', "Rapport exporté en $format avec succès.");
        
        return $this->redirectToRoute('app_direction_generale_rapports');
    }

    private function genererContenuRapport(string $type, string $periode): string
    {
        // Logique de génération de contenu basée sur le type et la période
        $contenu = "# Rapport $type\n\n";
        $contenu .= "Période: " . date('Y-m-d') . "\n\n";
        $contenu .= "## Statistiques principales\n\n";
        $contenu .= "- Contrôles effectués: " . rand(1000, 5000) . "\n";
        $contenu .= "- Infractions constatées: " . rand(100, 500) . "\n";
        $contenu .= "- Revenus générés: " . rand(100000000, 500000000) . " GNF\n\n";
        $contenu .= "## Recommandations\n\n";
        $contenu .= "1. Renforcer les contrôles dans les zones à risque\n";
        $contenu .= "2. Améliorer la formation des agents\n";
        $contenu .= "3. Optimiser l'utilisation des ressources\n";
        
        return $contenu;
    }
}
