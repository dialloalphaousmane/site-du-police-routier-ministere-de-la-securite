<?php

namespace App\Controller\Admin;

use App\Entity\Brigade;
use App\Form\BrigadeType;
use App\Repository\BrigadeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/brigade')]
#[IsGranted('ROLE_ADMIN')]
class AdminBrigadeController extends AbstractController
{
    public function __construct(
        private BrigadeRepository $brigadeRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'app_admin_brigade_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $region = $request->query->get('region');
        
        if ($region) {
            $brigades = $this->brigadeRepository->findBy(['region' => $region]);
        } else {
            $brigades = $this->brigadeRepository->findAll();
        }

        return $this->render('admin/brigade/index.html.twig', [
            'brigades' => $brigades,
        ]);
    }

    #[Route('/new', name: 'app_admin_brigade_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $brigade = new Brigade();
        $form = $this->createForm(BrigadeType::class, $brigade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($brigade);
            $this->entityManager->flush();

            $this->addFlash('success', 'Brigade créée avec succès.');
            return $this->redirectToRoute('app_admin_brigade_index');
        }

        return $this->render('admin/brigade/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_brigade_show', methods: ['GET'])]
    public function show(Brigade $brigade): Response
    {
        return $this->render('admin/brigade/show.html.twig', [
            'brigade' => $brigade,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_brigade_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Brigade $brigade): Response
    {
        $form = $this->createForm(BrigadeType::class, $brigade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Brigade modifiée avec succès.');
            return $this->redirectToRoute('app_admin_brigade_show', ['id' => $brigade->getId()]);
        }

        return $this->render('admin/brigade/edit.html.twig', [
            'form' => $form,
            'brigade' => $brigade,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_brigade_delete', methods: ['POST'])]
    public function delete(Request $request, Brigade $brigade): Response
    {
        if ($this->isCsrfTokenValid('delete' . $brigade->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($brigade);
            $this->entityManager->flush();

            $this->addFlash('success', 'Brigade supprimée avec succès.');
        }

        return $this->redirectToRoute('app_admin_brigade_index');
    }

    #[Route('/{id}/toggle', name: 'app_admin_brigade_toggle', methods: ['POST'])]
    public function toggle(Request $request, Brigade $brigade): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $brigade->getId(), $request->request->get('_token'))) {
            $brigade->setActive(!$brigade->isActive());
            $this->entityManager->flush();

            $this->addFlash('success', 'Statut de la brigade modifié.');
        }

        return $this->redirectToRoute('app_admin_brigade_index');
    }
}
