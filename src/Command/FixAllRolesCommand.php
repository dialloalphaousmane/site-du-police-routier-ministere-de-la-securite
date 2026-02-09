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
    name: 'app:fix-all-roles',
    description: 'Fix security roles for all users'
)]
class FixAllRolesCommand extends Command
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

        // Get all users
        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        $updatedCount = 0;
        $tableData = [];

        foreach ($users as $user) {
            $role = $user->getRole();
            
            if ($role) {
                $roleCode = $role->getCode();
                $user->setRoles([$roleCode]);
                $updatedCount++;
                
                $tableData[] = [
                    $user->getEmail(),
                    $roleCode,
                    $user->isActive() ? 'Yes' : 'No'
                ];
            }
        }

        $this->entityManager->flush();

        $io->success("Updated security roles for {$updatedCount} users!");
        
        $io->table(
            ['Email', 'Role Code', 'Active'],
            $tableData
        );

        return Command::SUCCESS;
    }
}
