<?php

namespace App\Controller\Brigade;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/brigade-legacy')]
#[IsGranted('ROLE_CHEF_BRIGADE')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_brigade_legacy_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('dashboard/chef_brigade.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/agents', name: 'app_brigade_legacy_agent_index')]
    public function agents(): Response
    {
        return $this->render('brigade/agent/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/controles', name: 'app_brigade_legacy_controle_index')]
    public function controles(): Response
    {
        return $this->render('brigade/controle/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/controle/new', name: 'app_brigade_legacy_controle_new')]
    public function newControle(Request $request): Response
    {
        return $this->render('brigade/controle/new.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/infractions', name: 'app_brigade_legacy_infraction_index')]
    public function infractions(): Response
    {
        return $this->render('brigade/infraction/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/amendes', name: 'app_brigade_legacy_amende_index')]
    public function amendes(): Response
    {
        return $this->render('brigade/amende/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/export/controls', name: 'app_admin_export_controls')]
    public function exportControls(): Response
    {
        // Logique d'export
        return new Response('Export des contrôles - Fonctionnalité à implémenter');
    }
}
