<?php

namespace App\Repository;

use App\Entity\Configuration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Configuration>
 */
class ConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Configuration::class);
    }

    public function save(Configuration $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Configuration $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByCle(string $cle): ?Configuration
    {
        return $this->createQueryBuilder('c')
            ->where('c.cle = :cle')
            ->andWhere('c.actif = :actif')
            ->setParameter('cle', $cle)
            ->setParameter('actif', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByCategorie(string $categorie): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.categorie = :categorie')
            ->andWhere('c.actif = :actif')
            ->setParameter('categorie', $categorie)
            ->setParameter('actif', true)
            ->orderBy('c.cle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllActifs(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('c.categorie', 'ASC')
            ->addOrderBy('c.cle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.type = :type')
            ->andWhere('c.actif = :actif')
            ->setParameter('type', $type)
            ->setParameter('actif', true)
            ->orderBy('c.cle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function search(string $query): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.cle LIKE :query')
            ->orWhere('c.valeur LIKE :query')
            ->orWhere('c.description LIKE :query')
            ->orWhere('c.categorie LIKE :query')
            ->andWhere('c.actif = :actif')
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('actif', true)
            ->orderBy('c.categorie', 'ASC')
            ->addOrderBy('c.cle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->select('DISTINCT(c.categorie)')
            ->where('c.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('c.categorie', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function getTypes(): array
    {
        return $this->createQueryBuilder('c')
            ->select('DISTINCT(c.type)')
            ->where('c.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('c.type', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function createDefaultConfigurations(): void
    {
        $defaults = [
            // Paramètres généraux
            'app_nom' => ['valeur' => 'Police Routière Guinée', 'type' => 'string', 'categorie' => 'général', 'description' => 'Nom de l\'application'],
            'app_version' => ['valeur' => '1.0.0', 'type' => 'string', 'categorie' => 'général', 'description' => 'Version de l\'application'],
            'app_email_contact' => ['valeur' => 'contact@police-routiere.gn', 'type' => 'email', 'categorie' => 'général', 'description' => 'Email de contact'],
            'app_telephone_contact' => ['valeur' => '+224 622 12 34 56', 'type' => 'string', 'categorie' => 'général', 'description' => 'Téléphone de contact'],
            
            // Paramètres de sécurité
            'session_timeout' => ['valeur' => '3600', 'type' => 'integer', 'categorie' => 'sécurité', 'description' => 'Durée de la session en secondes'],
            'max_login_attempts' => ['valeur' => '5', 'type' => 'integer', 'categorie' => 'sécurité', 'description' => 'Nombre maximal de tentatives de connexion'],
            'password_min_length' => ['valeur' => '8', 'type' => 'integer', 'categorie' => 'sécurité', 'description' => 'Longueur minimale du mot de passe'],
            'password_require_uppercase' => ['valeur' => 'true', 'type' => 'boolean', 'categorie' => 'sécurité', 'description' => 'Exiger une majuscule dans le mot de passe'],
            'password_require_lowercase' => ['valeur' => 'true', 'type' => 'boolean', 'categorie' => 'sécurité', 'description' => 'Exiger une minuscule dans le mot de passe'],
            'password_require_numbers' => ['valeur' => 'true', 'type' => 'boolean', 'categorie' => 'sécurité', 'description' => 'Exiger des chiffres dans le mot de passe'],
            'password_require_symbols' => ['valeur' => 'true', 'type' => 'boolean', 'categorie' => 'sécurité', 'description' => 'Exiger des symboles dans le mot de passe'],
            
            // Paramètres d'amendes
            'amende_vitesse_base' => ['valeur' => '50000', 'type' => 'integer', 'categorie' => 'amendes', 'description' => 'Montant de base pour excès de vitesse'],
            'amende_documentation_base' => ['valeur' => '25000', 'type' => 'integer', 'categorie' => 'amendes', 'description' => 'Montant de base pour défaut de documentation'],
            'amende_alcool_base' => ['valeur' => '100000', 'type' => 'integer', 'categorie' => 'amendes', 'description' => 'Montant de base pour alcoolémie'],
            'amende_equipement_base' => ['valeur' => '30000', 'type' => 'integer', 'categorie' => 'amendes', 'description' => 'Montant de base pour défaut d\'équipement'],
            'amende_chargement_base' => ['valeur' => '75000', 'type' => 'integer', 'categorie' => 'amendes', 'description' => 'Montant de base pour défaut de chargement'],
            
            // Paramètres de notification
            'email_notifications_enabled' => ['valeur' => 'true', 'type' => 'boolean', 'categorie' => 'notifications', 'description' => 'Activer les notifications par email'],
            'notification_rapport_validation' => ['valeur' => 'true', 'type' => 'boolean', 'categorie' => 'notifications', 'description' => 'Notifier lors de la validation d\'un rapport'],
            'notification_nouveau_controle' => ['valeur' => 'false', 'type' => 'boolean', 'categorie' => 'notifications', 'description' => 'Notifier lors d\'un nouveau contrôle'],
            'notification_seuil_alerte' => ['valeur' => '10', 'type' => 'integer', 'categorie' => 'notifications', 'description' => 'Seuil d\'alerte pour les notifications'],
            
            // Paramètres d'export
            'export_max_rows' => ['valeur' => '1000', 'type' => 'integer', 'categorie' => 'export', 'description' => 'Nombre maximum de lignes par export'],
            'export_retention_days' => ['valeur' => '90', 'type' => 'integer', 'categorie' => 'export', 'description' => 'Durée de rétention des exports en jours'],
            'export_auto_cleanup' => ['valeur' => 'true', 'type' => 'boolean', 'categorie' => 'export', 'description' => 'Nettoyage automatique des anciens exports'],
            
            // Paramètres de rapport
            'rapport_auto_generation' => ['valeur' => 'false', 'type' => 'boolean', 'categorie' => 'rapports', 'description' => 'Génération automatique des rapports'],
            'rapport_frequency' => ['valeur' => 'monthly', 'type' => 'string', 'categorie' => 'rapports', 'description' => 'Fréquence des rapports automatiques'],
            'rapport_retention_days' => ['valeur' => '365', 'type' => 'integer', 'categorie' => 'rapports', 'description' => 'Durée de rétention des rapports en jours'],
            
            // Paramètres système
            'log_retention_days' => ['valeur' => '90', 'type' => 'integer', 'categorie' => 'système', 'description' => 'Durée de rétention des logs en jours'],
            'backup_enabled' => ['valeur' => 'true', 'type' => 'boolean', 'categorie' => 'système', 'description' => 'Activer les sauvegardes automatiques'],
            'backup_frequency' => ['valeur' => 'daily', 'type' => 'string', 'categorie' => 'système', 'description' => 'Fréquence des sauvegardes'],
            'maintenance_mode' => ['valeur' => 'false', 'type' => 'boolean', 'categorie' => 'système', 'description' => 'Mode maintenance'],
        ];

        $entityManager = $this->getEntityManager();
        
        foreach ($defaults as $cle => $params) {
            $existing = $this->findByCle($cle);
            
            if (!$existing) {
                $config = new Configuration();
                $config->setCle($cle);
                $config->setValeur($params['valeur']);
                $config->setType($params['type']);
                $config->setCategorie($params['categorie']);
                $config->setDescription($params['description']);
                $config->setActif(true);
                
                $entityManager->persist($config);
            }
        }
        
        $entityManager->flush();
    }
}
