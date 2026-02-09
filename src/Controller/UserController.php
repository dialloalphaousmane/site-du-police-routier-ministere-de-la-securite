<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Role;
use App\Form\UserType;
use App\Form\RegistrationType;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use App\Repository\RegionRepository;
use App\Repository\BrigadeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/user')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
        private RegionRepository $regionRepository,
        private BrigadeRepository $brigadeRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(): Response
    {
        $users = $this->userRepository->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hasher le mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            }

            // Gérer le rôle
            $roleCode = $form->get('roleCode')->getData();
            $role = $this->roleRepository->findOneBy(['code' => $roleCode]);
            if ($role) {
                $user->setRole($role);
            }

            // Gérer la région et la brigade selon le rôle
            if (in_array($roleCode, ['ROLE_DIRECTION_REGIONALE', 'ROLE_CHEF_BRIGADE', 'ROLE_AGENT'])) {
                $regionId = $form->get('region')->getData();
                if ($regionId) {
                    $region = $this->regionRepository->find($regionId);
                    $user->setRegion($region);
                }
            }

            if (in_array($roleCode, ['ROLE_CHEF_BRIGADE', 'ROLE_AGENT'])) {
                $brigadeId = $form->get('brigade')->getData();
                if ($brigadeId) {
                    $brigade = $this->brigadeRepository->find($brigadeId);
                    $user->setBrigade($brigade);
                }
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès !');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer le changement de mot de passe si fourni
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            }

            // Gérer le rôle
            $roleCode = $form->get('roleCode')->getData();
            $role = $this->roleRepository->findOneBy(['code' => $roleCode]);
            if ($role) {
                $user->setRole($role);
            }

            // Gérer la région et la brigade selon le rôle
            if (in_array($roleCode, ['ROLE_DIRECTION_REGIONALE', 'ROLE_CHEF_BRIGADE', 'ROLE_AGENT'])) {
                $regionId = $form->get('region')->getData();
                if ($regionId) {
                    $region = $this->regionRepository->find($regionId);
                    $user->setRegion($region);
                } else {
                    $user->setRegion(null);
                }
            } else {
                $user->setRegion(null);
            }

            if (in_array($roleCode, ['ROLE_CHEF_BRIGADE', 'ROLE_AGENT'])) {
                $brigadeId = $form->get('brigade')->getData();
                if ($brigadeId) {
                    $brigade = $this->brigadeRepository->find($brigadeId);
                    $user->setBrigade($brigade);
                } else {
                    $user->setBrigade(null);
                }
            } else {
                $user->setBrigade(null);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès !');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        // Empêcher la suppression de son propre compte
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte !');
            return $this->redirectToRoute('app_user_index');
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès !');
        }

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/{id}/toggle-active', name: 'app_user_toggle_active', methods: ['POST'])]
    public function toggleActive(Request $request, User $user): Response
    {
        // Empêcher la désactivation de son propre compte
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas désactiver votre propre compte !');
            return $this->redirectToRoute('app_user_index');
        }

        if ($this->isCsrfTokenValid('toggle'.$user->getId(), $request->request->get('_token'))) {
            $user->setIsActive(!$user->isActive());
            $this->entityManager->flush();
            
            $status = $user->isActive() ? 'activé' : 'désactivé';
            $this->addFlash('success', "Utilisateur {$status} avec succès !");
        }

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/{id}/reset-password', name: 'app_user_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('reset'.$user->getId(), $request->request->get('_token'))) {
            // Générer un mot de passe temporaire
            $tempPassword = 'Temp@' . rand(100000, 999999);
            $user->setPassword($this->passwordHasher->hashPassword($user, $tempPassword));
            $this->entityManager->flush();
            
            $this->addFlash('success', "Mot de passe réinitialisé : {$tempPassword}");
        }

        return $this->redirectToRoute('app_user_show', ['id' => $user->getId()]);
    }

    #[Route('/stats', name: 'app_user_stats', methods: ['GET'])]
    public function stats(): Response
    {
        $stats = [
            'total' => $this->userRepository->count([]),
            'actifs' => $this->userRepository->count(['isActive' => true]),
            'inactifs' => $this->userRepository->count(['isActive' => false]),
            'by_role' => [],
        ];

        // Statistiques par rôle
        $roles = $this->roleRepository->findAll();
        foreach ($roles as $role) {
            $stats['by_role'][$role->getCode()] = $this->userRepository->count(['role' => $role]);
        }

        return $this->render('user/stats.html.twig', [
            'stats' => $stats,
        ]);
    }
}
