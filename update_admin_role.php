<?php

require_once 'vendor/autoload.php';

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

// Bootstrap Symfony
$kernel = new \App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get(EntityManagerInterface::class);

// Get admin user
$adminUser = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@police-routiere.gn']);

if ($adminUser) {
    // Set both the ORM role and the security roles
    $adminUser->setRoles(['ROLE_ADMIN']);
    
    $entityManager->flush();
    
    echo "Admin user updated successfully!\n";
    echo "Email: " . $adminUser->getEmail() . "\n";
    echo "Security Roles: " . json_encode($adminUser->getRoles()) . "\n";
    echo "ORM Role: " . ($adminUser->getRole() ? $adminUser->getRole()->getCode() : 'NULL') . "\n";
} else {
    echo "Admin user not found!\n";
}
