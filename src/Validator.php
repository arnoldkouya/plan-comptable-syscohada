<?php

declare(strict_types=1);

namespace Syscohada;

/**
 * Utilitaires de validation des codes SYSCOHADA.
 */
class Validator
{
    /**
     * Vérifie qu'un code respecte le format SYSCOHADA (1 à 4 chiffres).
     */
    public static function formatValide(string $code): bool
    {
        return (bool) preg_match('/^\d{1,4}$/', $code);
    }

    /**
     * Vérifie qu'un code existe dans le plan officiel.
     */
    public static function existeDansPlan(string $code): bool
    {
        return PlanComptable::getInstance()->exists($code);
    }

    /**
     * Retourne la classe d'un code (premier chiffre).
     */
    public static function classeDeCode(string $code): ?int
    {
        if (empty($code) || !ctype_digit($code)) {
            return null;
        }
        return (int) $code[0];
    }

    /**
     * Détermine le niveau (longueur) d'un code.
     */
    public static function niveauDeCode(string $code): int
    {
        return strlen($code);
    }

    /**
     * Indique si le code est un compte de bilan (classes 1-5).
     */
    public static function estBilan(string $code): bool
    {
        $classe = self::classeDeCode($code);
        return $classe !== null && $classe >= 1 && $classe <= 5;
    }

    /**
     * Indique si le code est un compte de gestion (classes 6-8).
     */
    public static function estGestion(string $code): bool
    {
        $classe = self::classeDeCode($code);
        return $classe !== null && $classe >= 6 && $classe <= 8;
    }

    /**
     * Indique si le code est un compte de charges (classe 6).
     */
    public static function estCharge(string $code): bool
    {
        return self::classeDeCode($code) === 6;
    }

    /**
     * Indique si le code est un compte de produits (classe 7).
     */
    public static function estProduit(string $code): bool
    {
        return self::classeDeCode($code) === 7;
    }

    /**
     * Indique si le code est un compte de trésorerie (classe 5).
     */
    public static function estTresorerie(string $code): bool
    {
        return self::classeDeCode($code) === 5;
    }

    /**
     * Valide une liste de codes et retourne ceux qui sont invalides.
     *
     * @param string[] $codes
     * @return string[]
     */
    public static function invalides(array $codes): array
    {
        $plan = PlanComptable::getInstance();
        return array_values(
            array_filter($codes, fn (string $c) => !$plan->exists($c))
        );
    }
}
