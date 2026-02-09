<?php

namespace App\Controller;

use App\Entity\Amende;
use App\Entity\Infraction;
use App\Form\AmendeType;
use App\Repository\AmendeRepository;
use App\Repository\InfractionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/amende')]
#[IsGranted('ROLE_AGENT')]
class AmendeController extends AbstractController
{
    public function __construct(
        private AmendeRepository $amendeRepository,
        private InfractionRepository $infractionRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'app_amende_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $amendes = [];

        if ($this->isGranted('ROLE_ADMIN')) {
            $amendes = $this->amendeRepository->findAll();
        } elseif ($this->isGranted('ROLE_DIRECTION_GENERALE')) {
            $amendes = $this->amendeRepository->findAll();
        } elseif ($this->isGranted('ROLE_DIRECTION_REGIONALE')) {
            $amendes = $this->amendeRepository->findByRegion($user->getRegion());
        } elseif ($this->isGranted('ROLE_CHEF_BRIGADE')) {
            $amendes = $this->amendeRepository->findByBrigade($user->getBrigade());
        } else {
            // ROLE_AGENT - voir les amendes de ses infractions
            $amendes = $this->amendeRepository->findByAgentEmail($user->getEmail());
        }

        return $this->render('amende/index.html.twig', [
            'amendes' => $amendes,
        ]);
    }

    #[Route('/new', name: 'app_amende_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $amende = new Amende();
        $infractionId = $request->query->get('infractionId');
        
        if ($infractionId) {
            $infraction = $this->infractionRepository->find($infractionId);
            if ($infraction) {
                // Vérifier les permissions
                $user = $this->getUser();
                $controle = $infraction->getControle();
                
                if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION_GENERALE')) {
                    if ($this->isGranted('ROLE_DIRECTION_REGIONALE') && $controle->getBrigade()->getRegion() !== $user->getRegion()) {
                        throw $this->createAccessDeniedException('Accès non autorisé');
                    }
                    if ($this->isGranted('ROLE_CHEF_BRIGADE') && $controle->getBrigade() !== $user->getBrigade()) {
                        throw $this->createAccessDeniedException('Accès non autorisé');
                    }
                    if ($this->isGranted('ROLE_AGENT') && $controle->getAgent()->getEmail() !== $user->getEmail()) {
                        throw $this->createAccessDeniedException('Accès non autorisé');
                    }
                }
                
                $amende->setInfraction($infraction);
                $amende->setMontant($infraction->getMontantAmende());
            }
        }

        $form = $this->createForm(AmendeType::class, $amende);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Générer une référence unique
            $amende->setReference('AMD-' . date('Y') . '-' . strtoupper(uniqid()));
            
            // Marquer l'infraction comme payée si c'est un paiement complet
            $infraction = $amende->getInfraction();
            if ($amende->getMontant() >= $infraction->getMontantAmende()) {
                $infraction->setStatut('PAYEE');
            }
            
            $this->entityManager->persist($amende);
            $this->entityManager->flush();

            $this->addFlash('success', 'Amende enregistrée avec succès !');
            
            if ($infractionId) {
                return $this->redirectToRoute('app_infraction_show', ['id' => $infractionId]);
            }
            return $this->redirectToRoute('app_amende_index');
        }

        return $this->render('amende/new.html.twig', [
            'amende' => $amende,
            'form' => $form->createView(),
            'infractionId' => $infractionId,
        ]);
    }

    #[Route('/{id}', name: 'app_amende_show', methods: ['GET'])]
    public function show(Amende $amende): Response
    {
        // Vérifier les permissions
        $user = $this->getUser();
        $infraction = $amende->getInfraction();
        $controle = $infraction->getControle();
        
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION_GENERALE')) {
            if ($this->isGranted('ROLE_DIRECTION_REGIONALE') && $controle->getBrigade()->getRegion() !== $user->getRegion()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
            if ($this->isGranted('ROLE_CHEF_BRIGADE') && $controle->getBrigade() !== $user->getBrigade()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
            if ($this->isGranted('ROLE_AGENT') && $controle->getAgent()->getEmail() !== $user->getEmail()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
        }

        return $this->render('amende/show.html.twig', [
            'amende' => $amende,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_amende_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Amende $amende): Response
    {
        // Vérifier les permissions
        $user = $this->getUser();
        $infraction = $amende->getInfraction();
        $controle = $infraction->getControle();
        
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION_GENERALE')) {
            if ($this->isGranted('ROLE_DIRECTION_REGIONALE') && $controle->getBrigade()->getRegion() !== $user->getRegion()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
            if ($this->isGranted('ROLE_CHEF_BRIGADE') && $controle->getBrigade() !== $user->getBrigade()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
            if ($this->isGranted('ROLE_AGENT') && $controle->getAgent()->getEmail() !== $user->getEmail()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
        }

        $form = $this->createForm(AmendeType::class, $amende);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour le statut de l'infraction
            $infraction = $amende->getInfraction();
            $totalAmendes = 0;
            foreach ($infraction->getAmendes() as $otherAmende) {
                $totalAmendes += (float) $otherAmende->getMontant();
            }
            
            if ($totalAmendes >= (float) $infraction->getMontantAmende()) {
                $infraction->setStatut('PAYEE');
            } else {
                $infraction->setStatut('PARTIELLEMENT_PAYEE');
            }
            
            $this->entityManager->flush();

            $this->addFlash('success', 'Amende modifiée avec succès !');
            return $this->redirectToRoute('app_amende_index');
        }

        return $this->render('amende/edit.html.twig', [
            'amende' => $amende,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_amende_delete', methods: ['POST'])]
    public function delete(Request $request, Amende $amende): Response
    {
        // Seul admin ou direction peuvent supprimer des amendes
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION_GENERALE') && !$this->isGranted('ROLE_DIRECTION_REGIONALE')) {
            throw $this->createAccessDeniedException('Accès non autorisé');
        }

        if ($this->isCsrfTokenValid('delete'.$amende->getId(), $request->request->get('_token'))) {
            $infraction = $amende->getInfraction();
            
            $this->entityManager->remove($amende);
            $this->entityManager->flush();
            
            // Mettre à jour le statut de l'infraction
            $totalAmendes = 0;
            foreach ($infraction->getAmendes() as $otherAmende) {
                $totalAmendes += (float) $otherAmende->getMontant();
            }
            
            if ($totalAmendes >= (float) $infraction->getMontantAmende()) {
                $infraction->setStatut('PAYEE');
            } elseif ($totalAmendes > 0) {
                $infraction->setStatut('PARTIELLEMENT_PAYEE');
            } else {
                $infraction->setStatut('NON_PAYEE');
            }
            
            $this->entityManager->flush();
            $this->addFlash('success', 'Amende supprimée avec succès !');
        }

        return $this->redirectToRoute('app_amende_index');
    }

    #[Route('/{id}/recu', name: 'app_amende_recu', methods: ['GET'])]
    public function recu(Amende $amende): Response
    {
        // Vérifier les permissions
        $user = $this->getUser();
        $infraction = $amende->getInfraction();
        $controle = $infraction->getControle();
        
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION_GENERALE')) {
            if ($this->isGranted('ROLE_DIRECTION_REGIONALE') && $controle->getBrigade()->getRegion() !== $user->getRegion()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
            if ($this->isGranted('ROLE_CHEF_BRIGADE') && $controle->getBrigade() !== $user->getBrigade()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
            if ($this->isGranted('ROLE_AGENT') && $controle->getAgent()->getEmail() !== $user->getEmail()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
        }

        return $this->render('amende/recu.html.twig', [
            'amende' => $amende,
        ]);
    }

    #[Route('/stats', name: 'app_amende_stats', methods: ['GET'])]
    public function stats(): Response
    {
        $user = $this->getUser();
        $stats = [];

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_DIRECTION_GENERALE')) {
            $stats = [
                'total' => $this->amendeRepository->count([]),
                'total_montant' => $this->amendeRepository->getTotalMontant(),
                'mois_cours' => $this->amendeRepository->getTotalMontantThisMonth(),
            ];
        } elseif ($this->isGranted('ROLE_DIRECTION_REGIONALE')) {
            $stats = [
                'total' => $this->amendeRepository->countByRegion($user->getRegion()),
                'total_montant' => $this->amendeRepository->getTotalMontantByRegion($user->getRegion()),
                'mois_cours' => $this->amendeRepository->getTotalMontantByRegionThisMonth($user->getRegion()),
            ];
        } elseif ($this->isGranted('ROLE_CHEF_BRIGADE')) {
            $stats = [
                'total' => $this->amendeRepository->countByBrigade($user->getBrigade()),
                'total_montant' => $this->amendeRepository->getTotalMontantByBrigade($user->getBrigade()),
                'mois_cours' => $this->amendeRepository->getTotalMontantByBrigadeThisMonth($user->getBrigade()),
            ];
        }

        return $this->render('amende/stats.html.twig', [
            'stats' => $stats,
        ]);
    }
}
