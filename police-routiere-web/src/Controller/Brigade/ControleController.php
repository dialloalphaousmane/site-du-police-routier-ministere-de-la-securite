<?php

namespace App\Controller\Brigade;

use App\Entity\Controle;
use App\Entity\Infraction;
use App\Entity\Agent;
use App\Form\ControleType;
use App\Repository\ControleRepository;
use App\Repository\AgentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/brigade/controles')]
#[IsGranted('ROLE_CHEF_BRIGADE')]
class ControleController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ControleRepository $controleRepository;
    private AgentRepository $agentRepository;

    public function __construct(EntityManagerInterface $entityManager, ControleRepository $controleRepository, AgentRepository $agentRepository)
    {
        $this->entityManager = $entityManager;
        $this->controleRepository = $controleRepository;
        $this->agentRepository = $agentRepository;
    }

    #[Route('/', name: 'app_controle_index')]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        $controles = $this->controleRepository->findBy(['brigade' => $brigade], ['dateControle' => 'DESC']);

        return $this->render('brigade/controle/index.html.twig', [
            'controles' => $controles,
            'user' => $user,
        ]);
    }

    #[Route('/new', name: 'app_controle_new')]
    public function new(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Récupérer l'agent correspondant à l'utilisateur
        $agent = $this->agentRepository->findOneBy(['email' => $user->getEmail()]);
        
        if (!$agent) {
            $this->addFlash('error', 'Aucun agent trouvé pour votre compte.');
            return $this->redirectToRoute('app_dashboard');
        }

        $controle = new Controle();
        $controle->setAgent($agent);
        $controle->setBrigade($brigade);

        $form = $this->createForm(ControleType::class, $controle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($controle);
            $this->entityManager->flush();

            $this->addFlash('success', 'Contrôle enregistré avec succès.');
            return $this->redirectToRoute('app_controle_index');
        }

        return $this->render('brigade/controle/new.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/{id}/show', name: 'app_controle_show')]
    public function show(Controle $controle): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();

        if ($controle->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à voir ce contrôle.');
            return $this->redirectToRoute('app_controle_index');
        }

        return $this->render('brigade/controle/show.html.twig', [
            'controle' => $controle,
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_controle_edit')]
    public function edit(Request $request, Controle $controle): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();

        if ($controle->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à modifier ce contrôle.');
            return $this->redirectToRoute('app_controle_index');
        }

        $form = $this->createForm(ControleType::class, $controle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Contrôle modifié avec succès.');
            return $this->redirectToRoute('app_controle_index');
        }

        return $this->render('brigade/controle/edit.html.twig', [
            'form' => $form->createView(),
            'controle' => $controle,
            'user' => $user,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_controle_delete', methods: ['POST'])]
    public function delete(Request $request, Controle $controle): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();

        if ($controle->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer ce contrôle.');
            return $this->redirectToRoute('app_controle_index');
        }

        if ($this->isCsrfTokenValid('delete'.$controle->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($controle);
            $this->entityManager->flush();
            $this->addFlash('success', 'Contrôle supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_controle_index');
    }

    #[Route('/export', name: 'app_controle_export')]
    public function export(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        $controles = $this->controleRepository->findBy(['brigade' => $brigade], ['dateControle' => 'DESC']);

        $csv = "Date du contrôle;Lieu;Marque véhicule;Immatriculation;Nom conducteur;Prénom conducteur;Agent;Observations\n";
        
        foreach ($controles as $controle) {
            $csv .= $controle->getDateControle()->format('d/m/Y H:i') . ';';
            $csv .= $controle->getLieuControle() . ';';
            $csv .= $controle->getMarqueVehicule() . ';';
            $csv .= $controle->getImmatriculation() . ';';
            $csv .= $controle->getNomConducteur() . ';';
            $csv .= $controle->getPrenomConducteur() . ';';
            $csv .= $controle->getAgent()->getNom() . ' ' . $controle->getAgent()->getPrenom() . ';';
            $csv .= str_replace(';', ',', $controle->getObservation() ?? '') . "\n";
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="controles_' . date('Y-m-d') . '.csv"');
        
        return $response;
    }
}
