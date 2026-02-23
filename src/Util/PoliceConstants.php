<?php

namespace App\Util;

class PoliceConstants
{
    // Statuts de contrôle
    public const CONTROLE_STATUS_COMPLETED = 'EFFECTUE';
    public const CONTROLE_STATUS_PENDING = 'EN_COURS';
    public const CONTROLE_STATUS_CANCELLED = 'ANNULE';

    public const CONTROLE_STATUSES = [
        self::CONTROLE_STATUS_COMPLETED => 'Effectué',
        self::CONTROLE_STATUS_PENDING => 'En cours',
        self::CONTROLE_STATUS_CANCELLED => 'Annulé'
    ];

    // Statuts d'amende
    public const AMENDE_STATUS_PENDING = 'EN_ATTENTE';
    public const AMENDE_STATUS_PAID = 'PAYEE';
    public const AMENDE_STATUS_REJECTED = 'REJETEE';

    public const AMENDE_STATUSES = [
        self::AMENDE_STATUS_PENDING => 'En attente',
        self::AMENDE_STATUS_PAID => 'Payée',
        self::AMENDE_STATUS_REJECTED => 'Rejetée'
    ];

    // Catégories d'infractions
    public const INFRACTION_CATEGORY_SPEED = 'VITESSE';
    public const INFRACTION_CATEGORY_PARKING = 'STATIONNEMENT';
    public const INFRACTION_CATEGORY_DOCUMENTATION = 'DOCUMENTATION';
    public const INFRACTION_CATEGORY_SAFETY = 'SECURITE';
    public const INFRACTION_CATEGORY_POLLUTION = 'POLLUTION';

    public const INFRACTION_CATEGORIES = [
        self::INFRACTION_CATEGORY_SPEED => 'Vitesse excessive',
        self::INFRACTION_CATEGORY_PARKING => 'Stationnement interdit',
        self::INFRACTION_CATEGORY_DOCUMENTATION => 'Documentation manquante',
        self::INFRACTION_CATEGORY_SAFETY => 'Défaut de sécurité',
        self::INFRACTION_CATEGORY_POLLUTION => 'Pollution excessive'
    ];

    // Montants standards des amendes (en GNF)
    public const FINE_AMOUNTS = [
        'VITESSE_50_60' => 100000,
        'VITESSE_60_80' => 150000,
        'VITESSE_PLUS_80' => 250000,
        'STATIONNEMENT' => 50000,
        'DOCUMENTATION' => 200000,
        'SECURITE' => 150000,
        'POLLUTION' => 100000,
        'ECLAIRAGE' => 80000,
        'CEINTURE' => 120000
    ];

    // Rôles utilisateurs
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_DIRECTION_GENERALE = 'ROLE_DIRECTION_GENERALE';
    public const ROLE_DIRECTION_REGIONALE = 'ROLE_DIRECTION_REGIONALE';
    public const ROLE_CHEF_BRIGADE = 'ROLE_CHEF_BRIGADE';
    public const ROLE_AGENT = 'ROLE_AGENT';

    public const USER_ROLES = [
        self::ROLE_ADMIN => 'Administrateur',
        self::ROLE_DIRECTION_GENERALE => 'Direction Générale',
        self::ROLE_DIRECTION_REGIONALE => 'Direction Régionale',
        self::ROLE_CHEF_BRIGADE => 'Chef de Brigade',
        self::ROLE_AGENT => 'Agent'
    ];

    // Grades des agents
    public const GRADES = [
        'BRIGADIER' => 'Brigadier',
        'BRIGADIER_CHEF' => 'Brigadier-Chef',
        'SERGEANT' => 'Sergent',
        'SERGEANT_CHEF' => 'Sergent-Chef',
        'LIEUTENANT' => 'Lieutenant',
        'CAPITAINE' => 'Capitaine'
    ];

    // Régions de Guinée
    public const REGIONS = [
        'CONAKRY' => 'Conakry',
        'KINDIA' => 'Kindia',
        'LABE' => 'Labé',
        'FARANAH' => 'Faranah',
        'MAMOU' => 'Mamou',
        'BOKE' => 'Boké',
        'NZEREKORE' => 'N\'Zérékoré',
        'KANKAN' => 'Kankan',
        'SIGUIRI' => 'Siguiri'
    ];

    // Types de véhicules
    public const VEHICLE_TYPES = [
        'VOITURE' => 'Voiture',
        'CAMION' => 'Camion',
        'MOTO' => 'Motocyclette',
        'BUS' => 'Bus/Minibus',
        'TAXI' => 'Taxi',
        'TRICYCLE' => 'Tricycle'
    ];

    // Pagination
    public const ITEMS_PER_PAGE = 50;
    public const ADMIN_ITEMS_PER_PAGE = 100;

    /**
     * Obtenir le label d'un statut d'amende
     */
    public static function getAmendeStatusLabel(string $status): string
    {
        return self::AMENDE_STATUSES[$status] ?? $status;
    }

    /**
     * Obtenir la badges HTML pour un statut d'amende
     */
    public static function getAmendeStatusBadge(string $status): string
    {
        return match($status) {
            self::AMENDE_STATUS_PAID => 'success',
            self::AMENDE_STATUS_PENDING => 'warning',
            self::AMENDE_STATUS_REJECTED => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Formater un montant en GNF
     */
    public static function formatMoney(float $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' GNF';
    }

    /**
     * Obtenir le label d'une infraction
     */
    public static function getInfractionLabel(string $category): string
    {
        return self::INFRACTION_CATEGORIES[$category] ?? $category;
    }
}
