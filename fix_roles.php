<?php

require_once 'vendor/autoload.php';

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Config\FileLocator;

// Bootstrap Symfony
$kernel = new \App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get(EntityManagerInterface::class);

// Get admin user
$adminUser = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@police-routiere.gn']);

if ($adminUser) {
    $adminUser->setRoles(['ROLE_ADMIN']);
    $entityManager->flush();
    echo "Admin user roles updated successfully!\n";
} else {
    echo "Admin user not found!\n";
}

// Update all users with their corresponding roles
$users = $entityManager->getRepository(User::class)->findAll();

foreach ($users as $user) {
    $role = $user->getRole();
    if ($role) {
        $roleCode = $role->getCode();
        $user->setRoles([$roleCode]);
        echo "Updated roles for user: " . $user->getEmail() . " -> " . $roleCode . "\n";
    }
}

$entityManager->flush();
echo "All user roles updated successfully!\n";
