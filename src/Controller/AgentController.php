<?php

namespace App\Controller;

use App\Entity\Agent;
use App\Form\AgentType;
use App\Repository\AgentRepository;
use App\Repository\RegionRepository;
use App\Repository\BrigadeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/agent')]
#[IsGranted('ROLE_CHEF_BRIGADE')]
class AgentController extends AbstractController
{
    public function __construct(
        private AgentRepository $agentRepository,
        private RegionRepository $regionRepository,
        private BrigadeRepository $brigadeRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'app_agent_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $agents = [];

        if ($this->isGranted('ROLE_ADMIN')) {
            $agents = $this->agentRepository->findAll();
        } elseif ($this->isGranted('ROLE_DIRECTION_GENERALE')) {
            $agents = $this->agentRepository->findAll();
        } elseif ($this->isGranted('ROLE_DIRECTION_REGIONALE')) {
            $agents = $this->agentRepository->findBy(['region' => $user->getRegion()]);
        } elseif ($this->isGranted('ROLE_CHEF_BRIGADE')) {
            $agents = $this->agentRepository->findBy(['brigade' => $user->getBrigade()]);
        }

        return $this->render('agent/index.html.twig', [
            'agents' => $agents,
        ]);
    }

    #[Route('/new', name: 'app_agent_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        // Seuls admin, direction régionale et chef de brigade peuvent créer des agents
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION_REGIONALE') && !$this->isGranted('ROLE_CHEF_BRIGADE')) {
            throw $this->createAccessDeniedException('Accès non autorisé');
        }

        $agent = new Agent();
        $user = $this->getUser();

        // Pré-remplir selon le rôle
        if ($this->isGranted('ROLE_CHEF_BRIGADE')) {
            $agent->setBrigade($user->getBrigade());
            $agent->setRegion($user->getBrigade()->getRegion());
        } elseif ($this->isGranted('ROLE_DIRECTION_REGIONALE')) {
            $agent->setRegion($user->getRegion());
        }

        $form = $this->createForm(AgentType::class, $agent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Générer un matricule unique
            $regionCode = $agent->getRegion()->getCode();
            $lastAgent = $this->agentRepository->findOneBy([], ['id' => 'DESC']);
            $matriculeNumber = $lastAgent ? $lastAgent->getId() + 1 : 1;
            $agent->setMatricule('AG-' . $regionCode . '-' . str_pad($matriculeNumber, 4, '0', STR_PAD_LEFT));
            
            $this->entityManager->persist($agent);
            $this->entityManager->flush();

            $this->addFlash('success', 'Agent créé avec succès !');
            return $this->redirectToRoute('app_agent_index');
        }

        return $this->render('agent/new.html.twig', [
            'agent' => $agent,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_agent_show', methods: ['GET'])]
    public function show(Agent $agent): Response
    {
        // Vérifier les permissions
        $user = $this->getUser();
        
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION_GENERALE')) {
            if ($this->isGranted('ROLE_DIRECTION_REGIONALE') && $agent->getRegion() !== $user->getRegion()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
            if ($this->isGranted('ROLE_CHEF_BRIGADE') && $agent->getBrigade() !== $user->getBrigade()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
        }

        return $this->render('agent/show.html.twig', [
            'agent' => $agent,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_agent_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Agent $agent): Response
    {
        // Vérifier les permissions
        $user = $this->getUser();
        
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION_GENERALE')) {
            if ($this->isGranted('ROLE_DIRECTION_REGIONALE') && $agent->getRegion() !== $user->getRegion()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
            if ($this->isGranted('ROLE_CHEF_BRIGADE') && $agent->getBrigade() !== $user->getBrigade()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
        }

        $form = $this->createForm(AgentType::class, $agent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Agent modifié avec succès !');
            return $this->redirectToRoute('app_agent_index');
        }

        return $this->render('agent/edit.html.twig', [
            'agent' => $agent,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_agent_delete', methods: ['POST'])]
    public function delete(Request $request, Agent $agent): Response
    {
        // Seul admin ou direction peuvent supprimer des agents
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION_GENERALE') && !$this->isGranted('ROLE_DIRECTION_REGIONALE')) {
            throw $this->createAccessDeniedException('Accès non autorisé');
        }

        if ($this->isCsrfTokenValid('delete'.$agent->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($agent);
            $this->entityManager->flush();
            $this->addFlash('success', 'Agent supprimé avec succès !');
        }

        return $this->redirectToRoute('app_agent_index');
    }

    #[Route('/{id}/toggle-actif', name: 'app_agent_toggle_actif', methods: ['POST'])]
    public function toggleActif(Request $request, Agent $agent): Response
    {
        // Vérifier les permissions
        $user = $this->getUser();
        
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION_GENERALE')) {
            if ($this->isGranted('ROLE_DIRECTION_REGIONALE') && $agent->getRegion() !== $user->getRegion()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
            if ($this->isGranted('ROLE_CHEF_BRIGADE') && $agent->getBrigade() !== $user->getBrigade()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
        }

        if ($this->isCsrfTokenValid('toggle'.$agent->getId(), $request->request->get('_token'))) {
            $agent->setIsActif(!$agent->isActif());
            $this->entityManager->flush();
            
            $status = $agent->isActif() ? 'activé' : 'désactivé';
            $this->addFlash('success', "Agent {$status} avec succès !");
        }

        return $this->redirectToRoute('app_agent_index');
    }

    #[Route('/stats', name: 'app_agent_stats', methods: ['GET'])]
    public function stats(): Response
    {
        $user = $this->getUser();
        $stats = [];

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_DIRECTION_GENERALE')) {
            $stats = [
                'total' => $this->agentRepository->count([]),
                'actifs' => $this->agentRepository->count(['isActif' => true]),
                'inactifs' => $this->agentRepository->count(['isActif' => false]),
            ];
        } elseif ($this->isGranted('ROLE_DIRECTION_REGIONALE')) {
            $stats = [
                'total' => $this->agentRepository->count(['region' => $user->getRegion()]),
                'actifs' => $this->agentRepository->count(['region' => $user->getRegion(), 'isActif' => true]),
                'inactifs' => $this->agentRepository->count(['region' => $user->getRegion(), 'isActif' => false]),
            ];
        } elseif ($this->isGranted('ROLE_CHEF_BRIGADE')) {
            $stats = [
                'total' => $this->agentRepository->count(['brigade' => $user->getBrigade()]),
                'actifs' => $this->agentRepository->count(['brigade' => $user->getBrigade(), 'isActif' => true]),
                'inactifs' => $this->agentRepository->count(['brigade' => $user->getBrigade(), 'isActif' => false]),
            ];
        }

        return $this->render('agent/stats.html.twig', [
            'stats' => $stats,
        ]);
    }
}
