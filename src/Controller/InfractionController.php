<?php

namespace App\Controller;

use App\Entity\Infraction;
use App\Entity\Controle;
use App\Form\InfractionType;
use App\Repository\InfractionRepository;
use App\Repository\ControleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/infraction')]
#[IsGranted('ROLE_AGENT')]
class InfractionController extends AbstractController
{
    public function __construct(
        private InfractionRepository $infractionRepository,
        private ControleRepository $controleRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'app_infraction_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $infractions = [];

        if ($this->isGranted('ROLE_ADMIN')) {
            $infractions = $this->infractionRepository->findAll();
        } elseif ($this->isGranted('ROLE_DIRECTION_GENERALE')) {
            $infractions = $this->infractionRepository->findAll();
        } elseif ($this->isGranted('ROLE_DIRECTION_REGIONALE')) {
            $infractions = $this->infractionRepository->findByRegion($user->getRegion());
        } elseif ($this->isGranted('ROLE_CHEF_BRIGADE')) {
            $infractions = $this->infractionRepository->findByBrigade($user->getBrigade());
        } else {
            // ROLE_AGENT - voir les infractions de ses contrôles
            $infractions = $this->infractionRepository->findByAgentEmail($user->getEmail());
        }

        return $this->render('infraction/index.html.twig', [
            'infractions' => $infractions,
        ]);
    }

    #[Route('/new', name: 'app_infraction_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $infraction = new Infraction();
        $controleId = $request->query->get('controleId');
        
        if ($controleId) {
            $controle = $this->controleRepository->find($controleId);
            if ($controle) {
                // Vérifier les permissions
                $user = $this->getUser();
                
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
                
                $infraction->setControle($controle);
            }
        }

        $form = $this->createForm(InfractionType::class, $infraction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Générer une référence unique
            $infraction->setReference('INF-' . date('Y') . '-' . strtoupper(uniqid()));
            
            $this->entityManager->persist($infraction);
            $this->entityManager->flush();

            $this->addFlash('success', 'Infraction enregistrée avec succès !');
            
            if ($controleId) {
                return $this->redirectToRoute('app_controle_show', ['id' => $controleId]);
            }
            return $this->redirectToRoute('app_infraction_index');
        }

        return $this->render('infraction/new.html.twig', [
            'infraction' => $infraction,
            'form' => $form->createView(),
            'controleId' => $controleId,
        ]);
    }

    #[Route('/{id}', name: 'app_infraction_show', methods: ['GET'])]
    public function show(Infraction $infraction): Response
    {
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

        return $this->render('infraction/show.html.twig', [
            'infraction' => $infraction,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_infraction_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Infraction $infraction): Response
    {
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

        $form = $this->createForm(InfractionType::class, $infraction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Infraction modifiée avec succès !');
            return $this->redirectToRoute('app_infraction_index');
        }

        return $this->render('infraction/edit.html.twig', [
            'infraction' => $infraction,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_infraction_delete', methods: ['POST'])]
    public function delete(Request $request, Infraction $infraction): Response
    {
        // Vérifier les permissions (seul admin ou direction peuvent supprimer)
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION_GENERALE') && !$this->isGranted('ROLE_DIRECTION_REGIONALE')) {
            throw $this->createAccessDeniedException('Accès non autorisé');
        }

        if ($this->isCsrfTokenValid('delete'.$infraction->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($infraction);
            $this->entityManager->flush();
            $this->addFlash('success', 'Infraction supprimée avec succès !');
        }

        return $this->redirectToRoute('app_infraction_index');
    }

    #[Route('/{id}/payer', name: 'app_infraction_payer', methods: ['GET', 'POST'])]
    public function payer(Request $request, Infraction $infraction): Response
    {
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
        }

        if ($request->isMethod('POST')) {
            $montant = $request->request->get('montant');
            $modePaiement = $request->request->get('modePaiement');
            
            if ($montant && $modePaiement) {
                // Créer un paiement
                $paiement = new \App\Entity\Paiement();
                $paiement->setInfraction($infraction);
                $paiement->setMontant($montant);
                $paiement->setModePaiement($modePaiement);
                $paiement->setDatePaiement(new \DateTimeImmutable());
                $paiement->setReference('PAY-' . date('Y') . '-' . strtoupper(uniqid()));
                
                // Marquer l'infraction comme payée
                $infraction->setStatut('PAYEE');
                
                $this->entityManager->persist($paiement);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Paiement enregistré avec succès !');
                return $this->redirectToRoute('app_infraction_show', ['id' => $infraction->getId()]);
            }
        }

        return $this->render('infraction/payer.html.twig', [
            'infraction' => $infraction,
        ]);
    }
}
