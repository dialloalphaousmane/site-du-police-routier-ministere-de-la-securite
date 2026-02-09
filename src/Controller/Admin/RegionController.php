<?php

namespace App\Controller\Admin;

use App\Entity\Region;
use App\Form\RegionType;
use App\Repository\RegionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/region')]
#[IsGranted('ROLE_ADMIN')]
class RegionController extends AbstractController
{
    private $entityManager;
    private $regionRepository;

    public function __construct(EntityManagerInterface $entityManager, RegionRepository $regionRepository)
    {
        $this->entityManager = $entityManager;
        $this->regionRepository = $regionRepository;
    }

    #[Route('/', name: 'app_admin_region_index', methods: ['GET'])]
    public function index(): Response
    {
        $regions = $this->regionRepository->findAll();
        
        return $this->render('admin/region/index.html.twig', [
            'regions' => $regions,
        ]);
    }

    #[Route('/new', name: 'app_admin_region_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $region = new Region();
        $form = $this->createForm(RegionType::class, $region);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($region);
            $this->entityManager->flush();

            $this->addFlash('success', 'Région créée avec succès !');
            return $this->redirectToRoute('app_admin_region_index');
        }

        return $this->render('admin/region/new.html.twig', [
            'region' => $region,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_region_show', methods: ['GET'])]
    public function show(Region $region): Response
    {
        return $this->render('admin/region/show.html.twig', [
            'region' => $region,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_region_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Region $region): Response
    {
        $form = $this->createForm(RegionType::class, $region);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Région modifiée avec succès !');
            return $this->redirectToRoute('app_admin_region_index');
        }

        return $this->render('admin/region/edit.html.twig', [
            'region' => $region,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_region_delete', methods: ['POST'])]
    public function delete(Request $request, Region $region): Response
    {
        if ($this->isCsrfTokenValid('delete'.$region->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($region);
            $this->entityManager->flush();

            $this->addFlash('success', 'Région supprimée avec succès !');
        }

        return $this->redirectToRoute('app_admin_region_index');
    }

    #[Route('/{id}/toggle', name: 'app_admin_region_toggle', methods: ['POST'])]
    public function toggle(Request $request, Region $region): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$region->getId(), $request->request->get('_token'))) {
            $region->setActif(!$region->isActif());
            $this->entityManager->flush();

            $status = $region->isActif() ? 'activée' : 'désactivée';
            $this->addFlash('success', "Région {$status} avec succès !");
        }

        return $this->redirectToRoute('app_admin_region_index');
    }
}
