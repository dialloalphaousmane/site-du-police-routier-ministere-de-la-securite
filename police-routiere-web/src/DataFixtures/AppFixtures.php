<?php

namespace App\DataFixtures;

use App\Entity\Role;
use App\Entity\Region;
use App\Entity\Brigade;
use App\Entity\User;
use App\Entity\Agent;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Create Roles
        $roleAdmin = new Role();
        $roleAdmin->setCode('ROLE_ADMIN');
        $roleAdmin->setLibelle('Administrateur Système');
        $roleAdmin->setDescription('Accès complet au système');
        $manager->persist($roleAdmin);

        $roleDirectionG = new Role();
        $roleDirectionG->setCode('ROLE_DIRECTION_GENERALE');
        $roleDirectionG->setLibelle('Direction Générale');
        $roleDirectionG->setDescription('Supervision nationale');
        $manager->persist($roleDirectionG);

        $roleDirectionR = new Role();
        $roleDirectionR->setCode('ROLE_DIRECTION_REGIONALE');
        $roleDirectionR->setLibelle('Direction Régionale');
        $roleDirectionR->setDescription('Supervision régionale');
        $manager->persist($roleDirectionR);

        $roleChefBrigade = new Role();
        $roleChefBrigade->setCode('ROLE_CHEF_BRIGADE');
        $roleChefBrigade->setLibelle('Chef de Brigade');
        $roleChefBrigade->setDescription('Gestion des agents et contrôles');
        $manager->persist($roleChefBrigade);

        $roleAgent = new Role();
        $roleAgent->setCode('ROLE_AGENT');
        $roleAgent->setLibelle('Agent Routier');
        $roleAgent->setDescription('Enregistrement des contrôles');
        $manager->persist($roleAgent);

        // Create Regions (Guinée)
        $regions = [
            ['code' => 'CKY', 'libelle' => 'Conakry', 'description' => 'Région de Conakry - Capitale'],
            ['code' => 'KND', 'libelle' => 'Kindia', 'description' => 'Région de Kindia'],
            ['code' => 'LAB', 'libelle' => 'Labé', 'description' => 'Région de Labé'],
            ['code' => 'FRN', 'libelle' => 'Faranah', 'description' => 'Région de Faranah'],
            ['code' => 'MZK', 'libelle' => 'Mamou', 'description' => 'Région de Mamou'],
            ['code' => 'BOK', 'libelle' => 'Boké', 'description' => 'Région de Boké'],
            ['code' => 'NZK', 'libelle' => 'N\'Zérékoré', 'description' => 'Région de N\'Zérékoré'],
            ['code' => 'KAN', 'libelle' => 'Kankan', 'description' => 'Région de Kankan'],
            ['code' => 'SIG', 'libelle' => 'Siguiri', 'description' => 'Région de Siguiri']
        ];

        $regionObjects = [];
        foreach ($regions as $regionData) {
            $region = new Region();
            $region->setCode($regionData['code']);
            $region->setLibelle($regionData['libelle']);
            $region->setDescription($regionData['description']);
            $manager->persist($region);
            $regionObjects[$regionData['code']] = $region;
        }

        // Create Brigades
        $brigades = [
            ['code' => 'CKY-BR1', 'libelle' => 'Brigade Conakry Centre', 'localite' => 'Kaloum', 'region' => 'CKY'],
            ['code' => 'CKY-BR2', 'libelle' => 'Brigade Conakry Nord', 'localite' => 'Ratoma', 'region' => 'CKY'],
            ['code' => 'CKY-BR3', 'libelle' => 'Brigade Conakry Sud', 'localite' => 'Almamya', 'region' => 'CKY'],
            ['code' => 'KND-BR1', 'libelle' => 'Brigade Kindia Centre', 'localite' => 'Kindia Ville', 'region' => 'KND'],
            ['code' => 'LAB-BR1', 'libelle' => 'Brigade Labé Centre', 'localite' => 'Labé Ville', 'region' => 'LAB'],
            ['code' => 'FRN-BR1', 'libelle' => 'Brigade Faranah Centre', 'localite' => 'Faranah Ville', 'region' => 'FRN'],
            ['code' => 'MZK-BR1', 'libelle' => 'Brigade Mamou Centre', 'localite' => 'Mamou Ville', 'region' => 'MZK'],
            ['code' => 'BOK-BR1', 'libelle' => 'Brigade Boké Centre', 'localite' => 'Boké Ville', 'region' => 'BOK'],
            ['code' => 'NZK-BR1', 'libelle' => 'Brigade N\'Zérékoré Centre', 'localite' => 'N\'Zérékoré Ville', 'region' => 'NZK'],
            ['code' => 'KAN-BR1', 'libelle' => 'Brigade Kankan Centre', 'localite' => 'Kankan Ville', 'region' => 'KAN'],
            ['code' => 'SIG-BR1', 'libelle' => 'Brigade Siguiri Centre', 'localite' => 'Siguiri Ville', 'region' => 'SIG']
        ];

        $brigadeObjects = [];
        foreach ($brigades as $brigadeData) {
            $brigade = new Brigade();
            $brigade->setCode($brigadeData['code']);
            $brigade->setLibelle($brigadeData['libelle']);
            $brigade->setLocalite($brigadeData['localite']);
            $brigade->setRegion($regionObjects[$brigadeData['region']]);
            $manager->persist($brigade);
            $brigadeObjects[$brigadeData['code']] = $brigade;
        }

        // Create Admin User
        $userAdmin = new User();
        $userAdmin->setEmail('admin@police-routiere.gn');
        $userAdmin->setNom('Admin');
        $userAdmin->setPrenom('Système');
        $userAdmin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $userAdmin->setIsActive(true);
        $hashedPassword = $this->passwordHasher->hashPassword($userAdmin, 'Admin@123456');
        $userAdmin->setPassword($hashedPassword);
        $manager->persist($userAdmin);

        // Create Direction Générale User
        $userDG = new User();
        $userDG->setEmail('direction-generale@police-routiere.gn');
        $userDG->setNom('diallo');
        $userDG->setPrenom('mamadou');
        $userDG->setRoles(['ROLE_DIRECTION_GENERALE', 'ROLE_USER']);
        $userDG->setIsActive(true);
        $hashedPassword = $this->passwordHasher->hashPassword($userDG, 'DG@123456');
        $userDG->setPassword($hashedPassword);
        $manager->persist($userDG);

        // Create Direction Régionale Users
        foreach (['CKY', 'KND', 'LAB', 'FRN', 'MZK'] as $regionCode) {
            $userDR = new User();
            $userDR->setEmail('direction-' . strtolower($regionCode) . '@police-routiere.gn');
            $userDR->setNom('Directeur');
            $userDR->setPrenom($regionObjects[$regionCode]->getLibelle());
            $userDR->setRoles(['ROLE_DIRECTION_REGIONALE', 'ROLE_USER']);
            $userDR->setRegion($regionObjects[$regionCode]);
            $userDR->setIsActive(true);
            $userDR->setPassword($this->passwordHasher->hashPassword($userDR, 'DR@123456'));
            $manager->persist($userDR);
        }

        // Create Chef de Brigade Users
        foreach ($brigadeObjects as $code => $brigade) {
            $userChef = new User();
            $userChef->setEmail('chef-' . strtolower($code) . '@police-routiere.gn');
            $userChef->setNom('Chef');
            $userChef->setPrenom($brigade->getLibelle());
            $userChef->setRoles(['ROLE_CHEF_BRIGADE', 'ROLE_USER']);
            $userChef->setRegion($brigade->getRegion());
            $userChef->setBrigade($brigade);
            $userChef->setIsActive(true);
            $userChef->setPassword($this->passwordHasher->hashPassword($userChef, 'Chef@123456'));
            $manager->persist($userChef);
        }

        // Create Agent Users with corresponding Agents
        $agentCounter = 0;
        foreach ($brigadeObjects as $brigadeCode => $brigade) {
            for ($i = 1; $i <= 3; $i++) {
                $agentCounter++;
                // Create Agent entity
                $agent = new Agent();
                $agent->setMatricule('AG-' . strtoupper($brigade->getRegion()->getCode()) . '-' . str_pad($agentCounter, 4, '0', STR_PAD_LEFT));
                $agent->setNom('Agent');
                $agent->setPrenom('Test ' . $i);
                $agent->setGrade('Officier');
                $agent->setDateEmbauche(new \DateTimeImmutable('-1 year'));
                $agent->setRegion($brigade->getRegion());
                $agent->setBrigade($brigade);
                $agent->setIsActif(true);
                $manager->persist($agent);

                // Create User for Agent
                $userAgent = new User();
                $userAgent->setEmail('agent-' . strtolower($brigadeCode) . '-' . $i . '@police-routiere.gn');
                $userAgent->setNom('Agent');
                $userAgent->setPrenom('Test ' . $i);
                $userAgent->setRoles(['ROLE_AGENT', 'ROLE_USER']);
                $userAgent->setRegion($brigade->getRegion());
                $userAgent->setBrigade($brigade);
                $userAgent->setIsActive(true);
                $userAgent->setPassword($this->passwordHasher->hashPassword($userAgent, 'Agent@123456'));
                $manager->persist($userAgent);
            }
        }

        $manager->flush();
    }
}
