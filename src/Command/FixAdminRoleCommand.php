<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-admin-role',
    description: 'Fix admin user security roles'
)]
class FixAdminRoleCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get admin user
        $adminUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@police-routiere.gn']);

        if (!$adminUser) {
            $io->error('Admin user not found!');
            return Command::FAILURE;
        }

        // Set security roles
        $adminUser->setRoles(['ROLE_ADMIN']);
        
        $this->entityManager->flush();

        $io->success('Admin user roles updated successfully!');
        $io->table(
            ['Property', 'Value'],
            [
                ['Email', $adminUser->getEmail()],
                ['Security Roles', implode(', ', $adminUser->getRoles())],
                ['ORM Role', $adminUser->getRole() ? $adminUser->getRole()->getCode() : 'NULL'],
                ['Is Active', $adminUser->isActive() ? 'Yes' : 'No']
            ]
        );

        return Command::SUCCESS;
    }
}
