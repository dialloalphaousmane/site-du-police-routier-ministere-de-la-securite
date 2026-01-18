<?php

namespace App\Controller\Brigade;

use App\Entity\Agent;
use App\Form\AgentType;
use App\Repository\AgentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/brigade/agents')]
#[IsGranted('ROLE_CHEF_BRIGADE')]
class AgentController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private AgentRepository $agentRepository;

    public function __construct(EntityManagerInterface $entityManager, AgentRepository $agentRepository)
    {
        $this->entityManager = $entityManager;
        $this->agentRepository = $agentRepository;
    }

    #[Route('/', name: 'app_agent_index')]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        $agents = $this->agentRepository->findBy(['brigade' => $brigade], ['nom' => 'ASC']);

        return $this->render('brigade/agent/index.html.twig', [
            'agents' => $agents,
            'user' => $user,
        ]);
    }

    #[Route('/new', name: 'app_agent_new')]
    public function new(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        $agent = new Agent();
        $form = $this->createForm(AgentType::class, $agent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($agent);
            $this->entityManager->flush();

            $this->addFlash('success', 'Agent enregistré avec succès.');
            return $this->redirectToRoute('app_agent_index');
        }

        return $this->render('brigade/agent/new.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/{id}/show', name: 'app_agent_show')]
    public function show(Agent $agent): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();

        if ($agent->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à voir cet agent.');
            return $this->redirectToRoute('app_agent_index');
        }

        return $this->render('brigade/agent/show.html.twig', [
            'agent' => $agent,
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_agent_edit')]
    public function edit(Request $request, Agent $agent): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();

        if ($agent->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à modifier cet agent.');
            return $this->redirectToRoute('app_agent_index');
        }

        $form = $this->createForm(AgentType::class, $agent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Agent modifié avec succès.');
            return $this->redirectToRoute('app_agent_index');
        }

        return $this->render('brigade/agent/edit.html.twig', [
            'form' => $form->createView(),
            'agent' => $agent,
            'user' => $user,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_agent_delete', methods: ['POST'])]
    public function delete(Request $request, Agent $agent): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();

        if ($agent->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer cet agent.');
            return $this->redirectToRoute('app_agent_index');
        }

        if ($this->isCsrfTokenValid('delete'.$agent->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($agent);
            $this->entityManager->flush();
            $this->addFlash('success', 'Agent supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_agent_index');
    }

    #[Route('/statistics', name: 'app_agent_statistics')]
    public function statistics(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        $agents = $this->agentRepository->findBy(['brigade' => $brigade]);

        // Calcul des statistiques
        $totalAgents = count($agents);
        $activeAgents = count(array_filter($agents, fn($a) => $a->isActif()));
        $inactiveAgents = $totalAgents - $activeAgents;
        $byGrade = [];
        $byRegion = [];

        foreach ($agents as $agent) {
            $grade = $agent->getGrade() ?? 'Non spécifié';
            $region = $agent->getRegion() ? $agent->getRegion()->getLibelle() : 'Non spécifiée';
            
            $byGrade[$grade] = ($byGrade[$grade] ?? 0) + 1;
            $byRegion[$region] = ($byRegion[$region] ?? 0) + 1;
        }

        return $this->render('brigade/agent/statistics.html.twig', [
            'totalAgents' => $totalAgents,
            'activeAgents' => $activeAgents,
            'inactiveAgents' => $inactiveAgents,
            'byGrade' => $byGrade,
            'byRegion' => $byRegion,
            'user' => $user,
        ]);
    }

    #[Route('/export', name: 'app_agent_export')]
    public function export(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        $agents = $this->agentRepository->findBy(['brigade' => $brigade], ['nom' => 'ASC']);

        $csv = "Matricule;Nom;Prénom;Grade;Date d'embauche;Région;Statut;Date de création\n";
        
        foreach ($agents as $agent) {
            $csv .= $agent->getMatricule() . ';';
            $csv .= ($agent->getNom() ?? '') . ';';
            $csv .= ($agent->getPrenom() ?? '') . ';';
            $csv .= ($agent->getGrade() ?? '') . ';';
            $csv .= $agent->getDateEmbauche() ? $agent->getDateEmbauche()->format('d/m/Y') : '' . ';';
            $csv .= ($agent->getRegion() ? $agent->getRegion()->getLibelle() : '') . ';';
            $csv .= ($agent->isActif() ? 'Actif' : 'Inactif') . ';';
            $csv .= $agent->getCreatedAt()->format('d/m/Y H:i') . "\n";
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="agents_' . date('Y-m-d') . '.csv"');
        
        return $response;
    }
}
