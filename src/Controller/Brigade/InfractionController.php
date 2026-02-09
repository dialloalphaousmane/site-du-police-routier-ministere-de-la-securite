<?php

namespace App\Controller\Brigade;

use App\Entity\Infraction;
use App\Entity\Controle;
use App\Form\InfractionType;
use App\Repository\InfractionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/brigade/infractions')]
#[IsGranted('ROLE_CHEF_BRIGADE')]
class InfractionController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private InfractionRepository $infractionRepository;

    public function __construct(EntityManagerInterface $entityManager, InfractionRepository $infractionRepository)
    {
        $this->entityManager = $entityManager;
        $this->infractionRepository = $infractionRepository;
    }

    #[Route('/', name: 'app_infraction_index')]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        $infractions = $this->infractionRepository->findBy(['controle' => ['brigade' => $brigade]], ['id' => 'DESC']);

        return $this->render('brigade/infraction/index.html.twig', [
            'infractions' => $infractions,
            'user' => $user,
        ]);
    }

    #[Route('/new', name: 'app_infraction_new')]
    public function new(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        $infraction = new Infraction();
        $form = $this->createForm(InfractionType::class, $infraction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($infraction);
            $this->entityManager->flush();

            $this->addFlash('success', 'Infraction enregistrée avec succès.');
            return $this->redirectToRoute('app_infraction_index');
        }

        return $this->render('brigade/infraction/new.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/{id}/show', name: 'app_infraction_show')]
    public function show(Infraction $infraction): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();

        if ($infraction->getControle()->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à voir cette infraction.');
            return $this->redirectToRoute('app_infraction_index');
        }

        return $this->render('brigade/infraction/show.html.twig', [
            'infraction' => $infraction,
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_infraction_edit')]
    public function edit(Request $request, Infraction $infraction): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();

        if ($infraction->getControle()->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à modifier cette infraction.');
            return $this->redirectToRoute('app_infraction_index');
        }

        $form = $this->createForm(InfractionType::class, $infraction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Infraction modifiée avec succès.');
            return $this->redirectToRoute('app_infraction_index');
        }

        return $this->render('brigade/infraction/edit.html.twig', [
            'form' => $form->createView(),
            'infraction' => $infraction,
            'user' => $user,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_infraction_delete', methods: ['POST'])]
    public function delete(Request $request, Infraction $infraction): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();

        if ($infraction->getControle()->getBrigade() !== $brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer cette infraction.');
            return $this->redirectToRoute('app_infraction_index');
        }

        if ($this->isCsrfTokenValid('delete'.$infraction->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($infraction);
            $this->entityManager->flush();
            $this->addFlash('success', 'Infraction supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_infraction_index');
    }

    #[Route('/statistics', name: 'app_infraction_statistics')]
    public function statistics(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        $infractions = $this->infractionRepository->findBy(['controle' => ['brigade' => $brigade]]);

        // Calcul des statistiques
        $totalInfractions = count($infractions);
        $totalAmount = array_sum(array_map(fn($i) => (float)($i->getMontantAmende() ?? 0), $infractions));
        $byType = [];
        $byMonth = [];

        foreach ($infractions as $infraction) {
            $type = $infraction->getLibelle() ?? 'Autre';
            $month = $infraction->getCreatedAt()->format('Y-m');
            
            $byType[$type] = ($byType[$type] ?? 0) + 1;
            $byMonth[$month] = ($byMonth[$month] ?? 0) + 1;
        }

        return $this->render('brigade/infraction/statistics.html.twig', [
            'totalInfractions' => $totalInfractions,
            'totalAmount' => $totalAmount,
            'byType' => $byType,
            'byMonth' => $byMonth,
            'user' => $user,
        ]);
    }

    #[Route('/export', name: 'app_infraction_export')]
    public function export(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $brigade = $user->getBrigade();
        
        if (!$brigade) {
            $this->addFlash('error', 'Vous n\'êtes pas affecté à une brigade.');
            return $this->redirectToRoute('app_dashboard');
        }

        $infractions = $this->infractionRepository->findBy(['controle' => ['brigade' => $brigade]], ['id' => 'DESC']);

        $csv = "Date;Type;Montant;Conducteur;Véhicule;Agent;Observations\n";
        
        foreach ($infractions as $infraction) {
            $csv .= $infraction->getCreatedAt()->format('d/m/Y H:i') . ';';
            $csv .= ($infraction->getLibelle() ?? 'Non spécifié') . ';';
            $csv .= ($infraction->getMontantAmende() ?? 0) . ';';
            $csv .= ($infraction->getControle()->getNomConducteur() ?? '') . ' ' . ($infraction->getControle()->getPrenomConducteur() ?? '') . ';';
            $csv .= ($infraction->getControle()->getMarqueVehicule() ?? '') . ' ' . ($infraction->getControle()->getImmatriculation() ?? '') . ';';
            $csv .= $infraction->getControle()->getAgent()->getNom() . ' ' . $infraction->getControle()->getAgent()->getPrenom() . ';';
            $csv .= str_replace(';', ',', $infraction->getDescription() ?? '') . "\n";
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="infractions_' . date('Y-m-d') . '.csv"');
        
        return $response;
    }
}
