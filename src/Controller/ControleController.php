<?php

namespace App\Controller;

use App\Entity\Controle;
use App\Entity\Agent;
use App\Entity\Brigade;
use App\Entity\User;
use App\Form\ControleType;
use App\Repository\ControleRepository;
use App\Repository\AgentRepository;
use App\Repository\BrigadeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/controle')]
#[IsGranted('ROLE_AGENT')]
class ControleController extends AbstractController
{
    public function __construct(
        private ControleRepository $controleRepository,
        private AgentRepository $agentRepository,
        private BrigadeRepository $brigadeRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'app_controle_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Récupérer les paramètres de recherche et de filtrage
        $search = $request->query->get('search');
        $dateStart = $request->query->get('date_start');
        $dateEnd = $request->query->get('date_end');
        $brigade = $request->query->get('brigade');
        $agent = $request->query->get('agent');
        
        // Si export demandé, rediriger vers l'export
        if ($request->query->get('export')) {
            return $this->redirectToRoute('app_admin_export_controls', $request->query->all());
        }
        
        // Créer le query builder
        $qb = $this->controleRepository->createQueryBuilder('c')
            ->leftJoin('c.agent', 'a')
            ->leftJoin('c.brigade', 'b')
            ->leftJoin('b.region', 'r')
            ->addSelect('a', 'b', 'r')
            ->orderBy('c.dateControle', 'DESC');
        
        // Appliquer les filtres
        if ($search) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('c.marqueVehicule', ':search'),
                $qb->expr()->like('c.immatriculation', ':search'),
                $qb->expr()->like('c.nomConducteur', ':search'),
                $qb->expr()->like('c.prenomConducteur', ':search'),
                $qb->expr()->like('c.lieuControle', ':search')
            ))->setParameter('search', '%' . $search . '%');
        }
        
        if ($dateStart) {
            $qb->andWhere('c.dateControle >= :dateStart')
               ->setParameter('dateStart', new \DateTime($dateStart));
        }
        
        if ($dateEnd) {
            $qb->andWhere('c.dateControle <= :dateEnd')
               ->setParameter('dateEnd', new \DateTime($dateEnd . ' 23:59:59'));
        }
        
        if ($brigade) {
            $qb->andWhere('b.id = :brigade')
               ->setParameter('brigade', $brigade);
        }
        
        if ($agent) {
            $qb->andWhere('a.id = :agent')
               ->setParameter('agent', $agent);
        }
        
        // Filtrer selon le rôle de l'utilisateur
        $user = $this->getUser();
        if ($user) {
            if (in_array('ROLE_AGENT', $user->getRoles())) {
                // Pour l'agent, on filtre par sa brigade si elle existe
                // Temporairement, on ne filtre pas par agent spécifique
                // TODO: Implémenter la relation User-Agent
            } elseif (in_array('ROLE_CHEF_BRIGADE', $user->getRoles())) {
                // Pour le chef de brigade, on filtre par sa brigade
                // TODO: Implémenter la relation User-Brigade
            } elseif (in_array('ROLE_DIRECTION_REGIONALE', $user->getRoles())) {
                // Pour la direction régionale, on filtre par sa région
                // TODO: Implémenter la relation User-Region
            }
        }
        
        // Pagination
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $qb->setMaxResults($limit)
           ->setFirstResult($offset);
        
        $controles = $qb->getQuery()->getResult();
        
        // Compter le total pour la pagination
        $totalQb = clone $qb;
        $totalQb->select('COUNT(c.id)')
               ->setMaxResults(null)
               ->setFirstResult(null);
        $total = $totalQb->getQuery()->getSingleScalarResult();
        
        $totalPages = ceil($total / $limit);
        
        return $this->render('controle/index.html.twig', [
            'controles' => $controles,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'search' => $search,
            'filters' => [
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'brigade' => $brigade,
                'agent' => $agent
            ]
        ]);
    }

    #[Route('/new', name: 'app_controle_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $controle = new Controle();
        /** @var User $user */
        $user = $this->getUser();

        // Pré-remplir la brigade selon l'utilisateur
        if ($user->getBrigade()) {
            $controle->setBrigade($user->getBrigade());
        }

        // Trouver l'agent correspondant à l'utilisateur (basé sur la brigade)
        if ($user->getBrigade()) {
            $agent = $this->agentRepository->findOneBy(['brigade' => $user->getBrigade()]);
            if ($agent) {
                $controle->setAgent($agent);
            }
        }

        $form = $this->createForm(ControleType::class, $controle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($controle);
            $this->entityManager->flush();

            $this->addFlash('success', 'Contrôle enregistré avec succès !');
            return $this->redirectToRoute('app_controle_index');
        }

        return $this->render('controle/new.html.twig', [
            'controle' => $controle,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_controle_show', methods: ['GET'])]
    public function show(Controle $controle): Response
    {
        // Vérifier les permissions
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION_GENERALE')) {
            if ($this->isGranted('ROLE_DIRECTION_REGIONALE') && $controle->getBrigade()->getRegion() !== $user->getRegion()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
            if ($this->isGranted('ROLE_CHEF_BRIGADE') && $controle->getBrigade() !== $user->getBrigade()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
            if ($this->isGranted('ROLE_AGENT') && $controle->getBrigade() !== $user->getBrigade()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
        }

        return $this->render('controle/show.html.twig', [
            'controle' => $controle,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_controle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Controle $controle): Response
    {
        // Vérifier les permissions (seul l'agent qui a créé ou les supérieurs peuvent modifier)
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION_GENERALE')) {
            if ($this->isGranted('ROLE_DIRECTION_REGIONALE') && $controle->getBrigade()->getRegion() !== $user->getRegion()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
            if ($this->isGranted('ROLE_CHEF_BRIGADE') && $controle->getBrigade() !== $user->getBrigade()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
            if ($this->isGranted('ROLE_AGENT') && $controle->getBrigade() !== $user->getBrigade()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
        }

        $form = $this->createForm(ControleType::class, $controle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Contrôle modifié avec succès !');
            return $this->redirectToRoute('app_controle_index');
        }

        return $this->render('controle/edit.html.twig', [
            'controle' => $controle,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_controle_delete', methods: ['POST'])]
    public function delete(Request $request, Controle $controle): Response
    {
        // Vérifier les permissions (seul admin ou direction peuvent supprimer)
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION_GENERALE') && !$this->isGranted('ROLE_DIRECTION_REGIONALE')) {
            throw $this->createAccessDeniedException('Accès non autorisé');
        }

        if ($this->isCsrfTokenValid('delete'.$controle->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($controle);
            $this->entityManager->flush();
            $this->addFlash('success', 'Contrôle supprimé avec succès !');
        }

        return $this->redirectToRoute('app_controle_index');
    }

    #[Route('/{id}/add-infraction', name: 'app_controle_add_infraction', methods: ['GET', 'POST'])]
    public function addInfraction(Request $request, Controle $controle): Response
    {
        // Vérifier les permissions
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION_GENERALE')) {
            if ($this->isGranted('ROLE_DIRECTION_REGIONALE') && $controle->getBrigade()->getRegion() !== $user->getRegion()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
            if ($this->isGranted('ROLE_CHEF_BRIGADE') && $controle->getBrigade() !== $user->getBrigade()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
            if ($this->isGranted('ROLE_AGENT') && $controle->getBrigade() !== $user->getBrigade()) {
                throw $this->createAccessDeniedException('Accès non autorisé');
            }
        }

        return $this->redirectToRoute('app_infraction_new', ['controleId' => $controle->getId()]);
    }
}
