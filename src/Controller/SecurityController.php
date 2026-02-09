<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Role;
use App\Form\LoginType;
use App\Form\RegistrationType;
use App\Form\ChangePasswordType;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use App\Repository\RegionRepository;
use App\Repository\BrigadeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class SecurityController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        RoleRepository $roleRepository,
        RegionRepository $regionRepository,
        BrigadeRepository $brigadeRepository,
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $roleCode = $form->get('roleCode')->getData();
            $role = $roleRepository->findOneBy(['code' => $roleCode]);
            
            if (!$role) {
                $this->addFlash('error', 'Rôle invalide');
                return $this->redirectToRoute('app_register');
            }

            $user->setRole($role);

            if (in_array($roleCode, ['ROLE_DIRECTION_REGIONALE', 'ROLE_CHEF_BRIGADE', 'ROLE_AGENT'])) {
                $regionId = $form->get('region')->getData();
                $region = $regionRepository->find($regionId);
                if ($region) {
                    $user->setRegion($region);
                }
            }

            if (in_array($roleCode, ['ROLE_CHEF_BRIGADE', 'ROLE_AGENT'])) {
                $brigadeId = $form->get('brigade')->getData();
                $brigade = $brigadeRepository->find($brigadeId);
                if ($brigade) {
                    $user->setBrigade($brigade);
                }
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Inscription réussie ! Connectez-vous maintenant.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        
        return $this->render('security/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/change-password', name: 'app_change_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
    ): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oldPassword = $form->get('oldPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            if (!$userPasswordHasher->isPasswordValid($user, $oldPassword)) {
                $this->addFlash('error', 'Ancien mot de passe incorrect');
                return $this->redirectToRoute('app_change_password');
            }

            $user->setPassword($userPasswordHasher->hashPassword($user, $newPassword));
            $entityManager->flush();

            $this->addFlash('success', 'Mot de passe modifié avec succès !');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('security/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        
        // Redirection selon le rôle de l'utilisateur
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_dashboard_admin');
        } elseif ($this->isGranted('ROLE_DIRECTION_GENERALE')) {
            return $this->redirectToRoute('app_dashboard_direction_generale');
        } elseif ($this->isGranted('ROLE_DIRECTION_REGIONALE')) {
            return $this->redirectToRoute('app_dashboard_direction_regionale');
        } elseif ($this->isGranted('ROLE_CHEF_BRIGADE')) {
            return $this->redirectToRoute('app_dashboard_chef_brigade');
        } elseif ($this->isGranted('ROLE_AGENT')) {
            return $this->redirectToRoute('app_dashboard_agent');
        }
        
        // Fallback si aucun rôle spécifique
        return $this->redirectToRoute('app_home');
    }

    // Routes spécifiques pour chaque dashboard
    #[Route('/dashboard/admin-security', name: 'app_dashboard_admin_security')]
    public function dashboardAdmin(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->redirectToRoute('app_dashboard_admin');
    }

    #[Route('/dashboard/direction-generale-security', name: 'app_dashboard_direction_generale_security')]
    public function dashboardDirectionGenerale(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DIRECTION_GENERALE');
        return $this->redirectToRoute('app_dashboard_direction_generale');
    }

    #[Route('/dashboard/direction-regionale-security', name: 'app_dashboard_direction_regionale_security')]
    public function dashboardDirectionRegionale(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DIRECTION_REGIONALE');
        return $this->redirectToRoute('app_dashboard_direction_regionale');
    }

    #[Route('/dashboard/chef-brigade-security', name: 'app_dashboard_chef_brigade_security')]
    public function dashboardChefBrigade(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CHEF_BRIGADE');
        return $this->redirectToRoute('app_dashboard_chef_brigade');
    }

    #[Route('/dashboard/agent-security', name: 'app_dashboard_agent_security')]
    public function dashboardAgent(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_AGENT');
        return $this->redirectToRoute('app_dashboard_agent');
    }
}
