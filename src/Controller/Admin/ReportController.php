<?php

namespace App\Controller\Admin;

use App\Entity\Rapport;
use App\Entity\User;
use App\Entity\Region;
use App\Entity\Brigade;
use App\Form\RapportType;
use App\Repository\RapportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/report')]
#[IsGranted('ROLE_ADMIN')]
class ReportController extends AbstractController
{
    private $entityManager;
    private $rapportRepository;

    public function __construct(EntityManagerInterface $entityManager, RapportRepository $rapportRepository)
    {
        $this->entityManager = $entityManager;
        $this->rapportRepository = $rapportRepository;
    }

    #[Route('/', name: 'app_admin_report_index', methods: ['GET'])]
    public function index(): Response
    {
        $rapports = $this->rapportRepository->findAll();
        
        return $this->render('admin/report/index.html.twig', [
            'rapports' => $rapports,
        ]);
    }

    #[Route('/new', name: 'app_admin_report_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $rapport = new Rapport();
        $rapport->setAuteur($this->getUser());
        
        $form = $this->createForm(RapportType::class, $rapport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($rapport);
            $this->entityManager->flush();

            $this->addFlash('success', 'Rapport créé avec succès !');
            return $this->redirectToRoute('app_admin_report_index');
        }

        return $this->render('admin/report/new.html.twig', [
            'rapport' => $rapport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_report_show', methods: ['GET'])]
    public function show(Rapport $rapport): Response
    {
        return $this->render('admin/report/show.html.twig', [
            'rapport' => $rapport,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_report_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Rapport $rapport): Response
    {
        $form = $this->createForm(RapportType::class, $rapport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Rapport modifié avec succès !');
            return $this->redirectToRoute('app_admin_report_index');
        }

        return $this->render('admin/report/edit.html.twig', [
            'rapport' => $rapport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_report_delete', methods: ['POST'])]
    public function delete(Request $request, Rapport $rapport): Response
    {
        if ($this->isCsrfTokenValid('delete'.$rapport->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($rapport);
            $this->entityManager->flush();

            $this->addFlash('success', 'Rapport supprimé avec succès !');
        }

        return $this->redirectToRoute('app_admin_report_index');
    }

    #[Route('/{id}/validate', name: 'app_admin_report_validate', methods: ['POST'])]
    public function validate(Request $request, Rapport $rapport): Response
    {
        if ($this->isCsrfTokenValid('validate'.$rapport->getId(), $request->request->get('_token'))) {
            $rapport->setStatut('VALIDE');
            $rapport->setValidateur($this->getUser());
            $rapport->setDateValidation(new \DateTimeImmutable());
            $this->entityManager->flush();

            $this->addFlash('success', 'Rapport validé avec succès !');
        }

        return $this->redirectToRoute('app_admin_report_index');
    }

    #[Route('/{id}/reject', name: 'app_admin_report_reject', methods: ['POST'])]
    public function reject(Request $request, Rapport $rapport): Response
    {
        if ($this->isCsrfTokenValid('reject'.$rapport->getId(), $request->request->get('_token'))) {
            $rapport->setStatut('REJETE');
            $rapport->setValidateur($this->getUser());
            $rapport->setDateValidation(new \DateTimeImmutable());
            $this->entityManager->flush();

            $this->addFlash('success', 'Rapport rejeté avec succès !');
        }

        return $this->redirectToRoute('app_admin_report_index');
    }

    #[Route('/{id}/export', name: 'app_admin_report_export', methods: ['GET'])]
    public function export(Rapport $rapport): Response
    {
        // Création du contenu CSV
        $csvContent = "Titre,Type,Statut,Auteur,Date Création,Date Validation\n";
        $csvContent .= '"' . $rapport->getTitre() . '",';
        $csvContent .= '"' . $rapport->getType() . '",';
        $csvContent .= '"' . $rapport->getStatut() . '",';
        $csvContent .= '"' . $rapport->getAuteur()->getEmail() . '",';
        $csvContent .= '"' . $rapport->getDateCreation()->format('d/m/Y H:i') . '",';
        $csvContent .= '"' . ($rapport->getDateValidation() ? $rapport->getDateValidation()->format('d/m/Y H:i') : '') . "\"\n\n";
        $csvContent .= "Contenu:\n" . $rapport->getContenu();

        // Création de la réponse
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="rapport_' . $rapport->getId() . '.csv"');

        return $response;
    }
}
