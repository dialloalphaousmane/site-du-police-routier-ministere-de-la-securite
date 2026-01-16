<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Role;

#[AsCommand(
    name: 'app:create-test-users',
    description: 'Crée des utilisateurs de test avec différents rôles'
)]
class CreateTestUsersCommand extends Command
{
    private $passwordHasher;
    private $entityManager;

    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager)
    {
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('role', InputArgument::OPTIONAL, 'Rôle spécifique à créer (admin, direction-generale, direction-regionale, chef-brigade, agent)')
            ->setHelp('Cette commande crée des utilisateurs de test pour tester les tableaux de bord');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $specificRole = $input->getArgument('role');

        // Récupérer les rôles existants ou les créer
        $roleEntities = [];
        $roleCodes = [
            'ROLE_ADMIN' => 'Administrateur',
            'ROLE_DIRECTION_GENERALE' => 'Direction Générale',
            'ROLE_DIRECTION_REGIONALE' => 'Direction Régionale',
            'ROLE_CHEF_BRIGADE' => 'Chef de Brigade',
            'ROLE_AGENT' => 'Agent'
        ];

        foreach ($roleCodes as $code => $libelle) {
            $role = $this->entityManager->getRepository(Role::class)->findOneBy(['code' => $code]);
            if (!$role) {
                $role = new Role();
                $role->setCode($code);
                $role->setLibelle($libelle);
                $role->setDescription("Rôle de $libelle");
                $role->setCreatedAt(new \DateTimeImmutable());
                $this->entityManager->persist($role);
            }
            $roleEntities[$code] = $role;
        }
        $this->entityManager->flush();

        $testUsers = [
            [
                'email' => 'admin@police.gn',
                'password' => 'admin123',
                'roles' => ['ROLE_ADMIN'],
                'firstName' => 'Administrateur',
                'lastName' => 'Système'
            ],
            [
                'email' => 'dg@police.gn',
                'password' => 'dg123',
                'roles' => ['ROLE_DIRECTION_GENERALE'],
                'firstName' => 'Directeur',
                'lastName' => 'Général'
            ],
            [
                'email' => 'dr@police.gn',
                'password' => 'dr123',
                'roles' => ['ROLE_DIRECTION_REGIONALE'],
                'firstName' => 'Directeur',
                'lastName' => 'Régional'
            ],
            [
                'email' => 'chef@police.gn',
                'password' => 'chef123',
                'roles' => ['ROLE_CHEF_BRIGADE'],
                'firstName' => 'Chef',
                'lastName' => 'Brigade'
            ],
            [
                'email' => 'agent@police.gn',
                'password' => 'agent123',
                'roles' => ['ROLE_AGENT'],
                'firstName' => 'Agent',
                'lastName' => 'Test'
            ]
        ];

        $createdCount = 0;

        foreach ($testUsers as $userData) {
            // Si un rôle spécifique est demandé, ne créer que celui-ci
            if ($specificRole) {
                $userRole = $userData['roles'][0];
                $roleMap = [
                    'admin' => 'ROLE_ADMIN',
                    'direction-generale' => 'ROLE_DIRECTION_GENERALE',
                    'direction-regionale' => 'ROLE_DIRECTION_REGIONALE',
                    'chef-brigade' => 'ROLE_CHEF_BRIGADE',
                    'agent' => 'ROLE_AGENT'
                ];

                if (!isset($roleMap[$specificRole]) || $userRole !== $roleMap[$specificRole]) {
                    continue;
                }
            }

            // Vérifier si l'utilisateur existe déjà
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userData['email']]);
            
            if ($existingUser) {
                $io->warning("L'utilisateur {$userData['email']} existe déjà.");
                continue;
            }

            // Créer le nouvel utilisateur
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $userData['password']));
            $user->setRoles($userData['roles']);
            $user->setPrenom($userData['firstName']);
            $user->setNom($userData['lastName']);
            $user->setIsActive(true);
            $user->setCreatedAt(new \DateTimeImmutable());
            
            // Associer le rôle approprié
            $userRole = $userData['roles'][0];
            if (isset($roleEntities[$userRole])) {
                $user->setRole($roleEntities[$userRole]);
            }

            $this->entityManager->persist($user);
            $createdCount++;

            $io->success("Utilisateur créé : {$userData['email']} (Rôle: {$userData['roles'][0]})");
        }

        $this->entityManager->flush();

        $io->section('Résumé');
        $io->success("$createdCount utilisateur(s) de test créé(s) avec succès!");

        $io->section('Identifiants de connexion');
        $io->table(
            ['Email', 'Mot de passe', 'Rôle', 'Nom Complet'],
            array_map(function($user) {
                return [
                    $user['email'],
                    $user['password'],
                    $user['roles'][0],
                    $user['firstName'] . ' ' . $user['lastName']
                ];
            }, $testUsers)
        );

        return Command::SUCCESS;
    }
}
