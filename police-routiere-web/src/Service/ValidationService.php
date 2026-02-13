<?php

namespace App\Service;

class ValidationService
{
    /**
     * Valider une immatriculation guinéenne
     * Format: RRIICC (Région, 2 chiffres, 2 chiffres, code zone)
     */
    public static function isValidPlateNumber(string $plate): bool
    {
        $plate = strtoupper(str_replace([' ', '-'], '', $plate));
        return preg_match('/^[A-Z]{2}\d{4}[A-Z]{2}$/', $plate) === 1;
    }

    /**
     * Nettoyer une immatriculation
     */
    public static function cleanPlateNumber(string $plate): string
    {
        $plate = strtoupper(str_replace([' ', '-', '.'], '', $plate));
        return substr($plate, 0, 8); // AA0000BB format
    }

    /**
     * Valider une immatriculation technique (CARTES GRISES)
     */
    public static function isValidRegistrationNumber(string $number): bool
    {
        return preg_match('/^[0-9]{3,10}$/', str_replace(['-', ' '], '', $number)) === 1;
    }

    /**
     * Valider un montant d'amende
     */
    public static function isValidAmount(float $amount): bool
    {
        return $amount > 0 && $amount <= 10000000; // Plafond 10M GNF
    }

    /**
     * Valider une vitesse enregistrée
     */
    public static function isValidSpeed(int $speed): bool
    {
        return $speed > 0 && $speed <= 280; // Plafond réaliste
    }

    /**
     * Valider un code de région
     */
    public static function isValidRegionCode(string $code): bool
    {
        $validCodes = array_keys([
            'CONAKRY' => 'Conakry',
            'KINDIA' => 'Kindia',
            'LABE' => 'Labé',
            'FARANAH' => 'Faranah',
            'MAMOU' => 'Mamou',
            'BOKE' => 'Boké',
            'NZEREKORE' => 'N\'Zérékoré',
            'KANKAN' => 'Kankan',
            'SIGUIRI' => 'Siguiri'
        ]);
        
        return in_array(strtoupper($code), $validCodes);
    }

    /**
     * Valider un code de brigade
     */
    public static function isValidBrigadeCode(string $code): bool
    {
        // Format: RR-BBB (Région - Groupe de 3 chiffres)
        return preg_match('/^[A-Z]{2}-\d{3}$/', strtoupper($code)) === 1;
    }

    /**
     * Valider une capacité de véhicule
     */
    public static function isValidCapacity(int $capacity): bool
    {
        return $capacity > 0 && $capacity <= 100; // 1 à 100 places
    }

    /**
     * Valider une année de mise en circulation
     */
    public static function isValidManufactureYear(int $year): bool
    {
        $currentYear = (int) date('Y');
        return $year >= 1950 && $year <= $currentYear;
    }

    /**
     * Extraire les informations d'une immatriculation
     */
    public static function parsePlateNumber(string $plate): array
    {
        $plate = self::cleanPlateNumber($plate);
        
        return [
            'region_code' => substr($plate, 0, 2),
            'serial' => substr($plate, 2, 4),
            'zone_code' => substr($plate, 6, 2),
            'full' => $plate
        ];
    }

    /**
     * Générer une immatriculation valide
     */
    public static function generatePlateNumber(string $regionCode = 'CKY'): string
    {
        $serial = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $zoneCode = chr(65 + random_int(0, 25)) . chr(65 + random_int(0, 25));
        
        return strtoupper($regionCode) . $serial . $zoneCode;
    }

    /**
     * Valider une adresse
     */
    public static function isValidAddress(string $address): bool
    {
        return strlen(trim($address)) >= 5 && strlen($address) <= 255;
    }

    /**
     * Valider un véhicule (ensemble de données)
     */
    public static function isValidVehicleData(array $data): array
    {
        $errors = [];
        
        if (!isset($data['immatriculation']) || !self::isValidPlateNumber($data['immatriculation'])) {
            $errors[] = 'Immatriculation invalide';
        }
        
        if (!isset($data['marque']) || strlen($data['marque']) < 2) {
            $errors[] = 'Marque invalide';
        }
        
        if (!isset($data['modele']) || strlen($data['modele']) < 2) {
            $errors[] = 'Modèle invalide';
        }
        
        if (!isset($data['annee']) || !self::isValidManufactureYear($data['annee'])) {
            $errors[] = 'Année invalide';
        }
        
        if (isset($data['capacite']) && !self::isValidCapacity($data['capacite'])) {
            $errors[] = 'Capacité invalide';
        }
        
        return $errors;
    }

    /**
     * Valider une infraction (ensemble de données)
     */
    public static function isValidInfractionData(array $data): array
    {
        $errors = [];
        
        if (!isset($data['code']) || strlen($data['code']) < 2) {
            $errors[] = 'Code d\'infraction invalide';
        }
        
        if (!isset($data['description']) || strlen($data['description']) < 10) {
            $errors[] = 'Description invalide (minimum 10 caractères)';
        }
        
        if (!isset($data['montant']) || !self::isValidAmount($data['montant'])) {
            $errors[] = 'Montant invalide';
        }
        
        return $errors;
    }

    /**
     * Dupliquer un objet (validation de cohérence)
     */
    public static function validateConsistency(array $original, array $duplicate): array
    {
        $errors = [];
        
        foreach ($original as $key => $value) {
            if (!isset($duplicate[$key])) {
                $errors[] = "Clé manquante: $key";
            } elseif ($duplicate[$key] !== $value) {
                $errors[] = "Valeur modifiée: $key";
            }
        }
        
        return $errors;
    }
}
