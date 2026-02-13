<?php

namespace App\Service;

use DateTime;
use App\Util\PoliceConstants;

class FormatterService
{
    /**
     * Formater une date en format français
     */
    public static function formatDate(?DateTime $date, string $format = 'd/m/Y'): string
    {
        if (!$date) {
            return '-';
        }
        return $date->format($format);
    }

    /**
     * Formater une date et heure en français
     */
    public static function formatDateTime(?DateTime $dateTime, string $format = 'd/m/Y H:i'): string
    {
        if (!$dateTime) {
            return '-';
        }
        return $dateTime->format($format);
    }

    /**
     * Formater une heure
     */
    public static function formatTime(?DateTime $time): string
    {
        if (!$time) {
            return '-';
        }
        return $time->format('H:i');
    }

    /**
     * Formater un montant en GNF avec espaces
     */
    public static function formatMoney(?float $amount): string
    {
        if ($amount === null) {
            return '-';
        }
        return PoliceConstants::formatMoney($amount);
    }

    /**
     * Formater un pourcentage
     */
    public static function formatPercent(?float $percent, int $decimals = 1): string
    {
        if ($percent === null) {
            return '-';
        }
        return number_format($percent, $decimals, ',', ' ') . ' %';
    }

    /**
     * Capitaliser une chaîne de caractères
     */
    public static function capitalize(string $text): string
    {
        return ucfirst(strtolower($text));
    }

    /**
     * Formater un statut d'amende
     */
    public static function formatAmendeStatus(string $status): string
    {
        return PoliceConstants::getAmendeStatusLabel($status);
    }

    /**
     * Obtenir une classe CSS pour un statut d'amende
     */
    public static function getStatusClass(string $status): string
    {
        return match($status) {
            PoliceConstants::AMENDE_STATUS_PAID => 'success',
            PoliceConstants::AMENDE_STATUS_PENDING => 'warning',
            PoliceConstants::AMENDE_STATUS_REJECTED => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Tronquer un texte
     */
    public static function truncate(string $text, int $length = 50, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . $suffix;
    }

    /**
     * Formater un nom complet
     */
    public static function formatFullName(?string $firstName, ?string $lastName): string
    {
        if (!$firstName && !$lastName) {
            return '-';
        }
        return trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
    }

    /**
     * Obtenir le nombre de jours depuis une date
     */
    public static function getDaysAgo(DateTime $date): int
    {
        $now = new DateTime();
        $interval = $now->diff($date);
        return $interval->days;
    }

    /**
     * Formater "il y a X jours"
     */
    public static function formatTimeAgo(DateTime $date): string
    {
        $days = self::getDaysAgo($date);
        
        if ($days === 0) {
            return 'Aujourd\'hui';
        } elseif ($days === 1) {
            return 'Hier';
        } elseif ($days < 7) {
            return 'Il y a ' . $days . ' jours';
        } elseif ($days < 30) {
            return 'Il y a ' . floor($days / 7) . ' semaines';
        } else {
            return 'Il y a ' . floor($days / 30) . ' mois';
        }
    }

    /**
     * Générer un numéro ou code aléatoire
     */
    public static function generateCode(int $length = 8): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }

    /**
     * Valider une adresse email
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valider un numéro de téléphone guinéen
     */
    public static function isValidPhoneGuinea(string $phone): bool
    {
        // Format: +224 XXX XX XX XX ou 224 XXX XX XX XX
        return preg_match('/^(\+?224|00224)?[67][0-9]{7}$/', preg_replace('/\s|-/', '', $phone)) === 1;
    }

    /**
     * Nettoyer un numéro de téléphone
     */
    public static function cleanPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        if (strpos($phone, '+224') === 0) {
            return $phone;
        } elseif (strpos($phone, '00224') === 0) {
            return '+' . substr($phone, 2);
        } elseif (strlen($phone) === 9) {
            return '+224' . $phone;
        }
        
        return $phone;
    }
}
