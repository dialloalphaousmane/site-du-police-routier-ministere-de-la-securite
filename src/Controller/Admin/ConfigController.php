<?php

namespace App\Controller\Admin;

use App\Entity\Configuration;
use App\Form\ConfigurationType;
use App\Repository\ConfigurationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/config')]
#[IsGranted('ROLE_ADMIN')]
class ConfigController extends AbstractController
{
    private $entityManager;
    private $configRepository;

    public function __construct(EntityManagerInterface $entityManager, ConfigurationRepository $configRepository)
    {
        $this->entityManager = $entityManager;
        $this->configRepository = $configRepository;
    }

    #[Route('/', name: 'app_admin_config_index', methods: ['GET'])]
    public function index(): Response
    {
        $categories = $this->configRepository->getCategories();
        $configurations = [];
        
        foreach ($categories as $categorie) {
            $configurations[$categorie] = $this->configRepository->findByCategorie($categorie);
        }

        return $this->render('admin/config/index.html.twig', [
            'configurations' => $configurations,
            'categories' => $categories
        ]);
    }

    #[Route('/new', name: 'app_admin_config_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $configuration = new Configuration();
        $configuration->setCreatedBy($this->getUser());
        
        $form = $this->createForm(ConfigurationType::class, $configuration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($configuration);
            $this->entityManager->flush();

            $this->addFlash('success', 'Configuration créée avec succès !');
            return $this->redirectToRoute('app_admin_config_index');
        }

        return $this->render('admin/config/new.html.twig', [
            'configuration' => $configuration,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_config_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Configuration $configuration): Response
    {
        $form = $this->createForm(ConfigurationType::class, $configuration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $configuration->setUpdatedBy($this->getUser());
            $this->entityManager->flush();

            $this->addFlash('success', 'Configuration modifiée avec succès !');
            return $this->redirectToRoute('app_admin_config_index');
        }

        return $this->render('admin/config/edit.html.twig', [
            'configuration' => $configuration,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_config_delete', methods: ['POST'])]
    public function delete(Request $request, Configuration $configuration): Response
    {
        if ($this->isCsrfTokenValid('delete'.$configuration->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($configuration);
            $this->entityManager->flush();

            $this->addFlash('success', 'Configuration supprimée avec succès !');
        }

        return $this->redirectToRoute('app_admin_config_index');
    }

    #[Route('/{id}/toggle', name: 'app_admin_config_toggle', methods: ['POST'])]
    public function toggle(Request $request, Configuration $configuration): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$configuration->getId(), $request->request->get('_token'))) {
            $configuration->setActif(!$configuration->isActif());
            $configuration->setUpdatedBy($this->getUser());
            $this->entityManager->flush();

            $status = $configuration->isActif() ? 'activée' : 'désactivée';
            $this->addFlash('success', "Configuration {$status} avec succès !");
        }

        return $this->redirectToRoute('app_admin_config_index');
    }

    #[Route('/api/{cle}', name: 'app_admin_config_api', methods: ['GET', 'POST'])]
    public function apiConfig(Request $request, string $cle): JsonResponse
    {
        $configuration = $this->configRepository->findByCle($cle);

        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true);
            
            if (!$configuration) {
                $configuration = new Configuration();
                $configuration->setCle($cle);
                $configuration->setCreatedBy($this->getUser());
            }

            if (isset($data['valeur'])) {
                $configuration->setValeur($data['valeur']);
            }
            if (isset($data['type'])) {
                $configuration->setType($data['type']);
            }
            if (isset($data['categorie'])) {
                $configuration->setCategorie($data['categorie']);
            }
            if (isset($data['description'])) {
                $configuration->setDescription($data['description']);
            }
            
            $configuration->setUpdatedBy($this->getUser());
            $this->entityManager->persist($configuration);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Configuration mise à jour avec succès',
                'cle' => $cle,
                'valeur' => $configuration->getValeur()
            ]);
        }

        if ($configuration) {
            return new JsonResponse([
                'cle' => $configuration->getCle(),
                'valeur' => $configuration->getValeur(),
                'type' => $configuration->getType(),
                'categorie' => $configuration->getCategorie(),
                'description' => $configuration->getDescription(),
                'actif' => $configuration->isActif()
            ]);
        }

        return new JsonResponse([
            'error' => 'Configuration non trouvée',
            'cle' => $cle
        ], 404);
    }

    #[Route('/init', name: 'app_admin_config_init', methods: ['POST'])]
    public function initialize(): Response
    {
        try {
            $this->configRepository->createDefaultConfigurations();
            $this->addFlash('success', 'Configurations par défaut initialisées avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'initialisation: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_config_index');
    }

    #[Route('/export', name: 'app_admin_config_export', methods: ['GET'])]
    public function export(): Response
    {
        $configurations = $this->configRepository->findAllActifs();
        
        $csvContent = "Clé;Valeur;Type;Catégorie;Description;Actif;Date création;Date modification\n";
        
        foreach ($configurations as $config) {
            $csvContent .= sprintf(
                "%s;%s;%s;%s;%s;%s;%s;%s\n",
                $config->getCle(),
                $config->getValeur(),
                $config->getType(),
                $config->getCategorie(),
                $config->getDescription() ?? '',
                $config->isActif() ? 'Oui' : 'Non',
                $config->getCreatedAt()->format('d/m/Y H:i'),
                $config->getUpdatedAt()->format('d/m/Y H:i')
            );
        }

        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="configuration_' . date('Y-m-d') . '.csv"');
        
        return $response;
    }

    #[Route('/reset', name: 'app_admin_config_reset', methods: ['POST'])]
    public function reset(Request $request): Response
    {
        if ($this->isCsrfTokenValid('reset', $request->request->get('_token'))) {
            // Supprimer toutes les configurations existantes
            $qb = $this->entityManager->createQueryBuilder();
            $qb->delete('App\Entity\Configuration')
               ->getQuery()
               ->execute();

            // Recréer les configurations par défaut
            $this->configRepository->createDefaultConfigurations();

            $this->addFlash('success', 'Configuration réinitialisée avec succès !');
        }

        return $this->redirectToRoute('app_admin_config_index');
    }

    #[Route('/search', name: 'app_admin_config_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q');
        
        if (!$query) {
            return new JsonResponse(['results' => []]);
        }

        $configurations = $this->configRepository->search($query);
        
        $results = [];
        foreach ($configurations as $config) {
            $results[] = [
                'id' => $config->getId(),
                'cle' => $config->getCle(),
                'valeur' => $config->getValeur(),
                'type' => $config->getType(),
                'categorie' => $config->getCategorie(),
                'description' => $config->getDescription(),
                'actif' => $config->isActif()
            ];
        }

        return new JsonResponse(['results' => $results]);
    }
}
