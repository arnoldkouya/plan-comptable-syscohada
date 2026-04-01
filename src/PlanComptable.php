<?php

declare(strict_types=1);

namespace Syscohada;

use Syscohada\Contracts\CompteInterface;
use Syscohada\Contracts\PlanComptableInterface;
use Syscohada\Exceptions\CompteNotFoundException;

/**
 * Registre du Plan Comptable SYSCOHADA.
 *
 * Usage :
 *   $plan = PlanComptable::getInstance();
 *   $compte = $plan->find('6011');
 *   $charges = $plan->classe(6);
 *   $resultats = $plan->search('résultat');
 */
class PlanComptable implements PlanComptableInterface
{
    /** @var array<string, Compte> */
    private array $index = [];

    private static ?self $instance = null;

    private function __construct()
    {
        $this->charger();
    }

    /** Singleton — une seule instance en mémoire. */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Réinitialise le singleton (utile en tests). */
    public static function reset(): void
    {
        self::$instance = null;
    }

    // -------------------------------------------------------------------------
    // Chargement des données
    // -------------------------------------------------------------------------

    private function charger(): void
    {
        $donnees = require __DIR__ . '/Data/comptes.php';

        foreach ($donnees as [$code, $libelle, $classe, $niveau, $parent, $nature, $sens]) {
            $this->index[$code] = new Compte(
                code:       $code,
                libelle:    $libelle,
                classe:     $classe,
                niveau:     $niveau,
                codeParent: $parent,
                actif:      true,
                nature:     $nature,
                sens:       $sens,
            );
        }
    }

    // -------------------------------------------------------------------------
    // Implémentation de PlanComptableInterface
    // -------------------------------------------------------------------------

    /**
     * Recherche un compte par son code exact.
     */
    public function find(string $code): ?CompteInterface
    {
        return $this->index[$code] ?? null;
    }

    /**
     * Retourne le compte ou lance CompteNotFoundException.
     */
    public function findOrFail(string $code): CompteInterface
    {
        return $this->find($code) ?? throw new CompteNotFoundException($code);
    }

    /**
     * Retourne tous les comptes d'une classe (1-9).
     *
     * @return Compte[]
     */
    public function classe(int $classe): array
    {
        return array_values(
            array_filter($this->index, fn (Compte $c) => $c->getClasse() === $classe)
        );
    }

    /**
     * Retourne les comptes d'un niveau précis dans une classe donnée.
     *
     * @return Compte[]
     */
    public function classeNiveau(int $classe, int $niveau): array
    {
        return array_values(
            array_filter(
                $this->index,
                fn (Compte $c) => $c->getClasse() === $classe && $c->getNiveau() === $niveau
            )
        );
    }

    /**
     * Retourne les enfants directs d'un compte parent.
     *
     * @return Compte[]
     */
    public function enfants(string $codeParent): array
    {
        return array_values(
            array_filter($this->index, fn (Compte $c) => $c->getCodeParent() === $codeParent)
        );
    }

    /**
     * Retourne tous les descendants (récursif) d'un compte.
     *
     * @return Compte[]
     */
    public function descendants(string $code): array
    {
        $result = [];
        foreach ($this->enfants($code) as $enfant) {
            $result[] = $enfant;
            foreach ($this->descendants($enfant->getCode()) as $descendant) {
                $result[] = $descendant;
            }
        }
        return $result;
    }

    /**
     * Recherche plein texte sur le code ou le libellé (insensible à la casse et aux accents).
     *
     * @return Compte[]
     */
    public function search(string $terme): array
    {
        $termeNormalise = $this->normaliser($terme);

        return array_values(
            array_filter(
                $this->index,
                fn (Compte $c) =>
                    str_contains($c->getCode(), $terme) ||
                    str_contains($this->normaliser($c->getLibelle()), $termeNormalise)
            )
        );
    }

    /**
     * Filtre par nature : 'bilan' | 'gestion' | 'hors-bilan' | 'cage'
     *
     * @return Compte[]
     */
    public function nature(string $nature): array
    {
        return array_values(
            array_filter($this->index, fn (Compte $c) => $c->getNature() === $nature)
        );
    }

    /**
     * Filtre par sens : 'debit' | 'credit' | 'mixte'
     *
     * @return Compte[]
     */
    public function sens(string $sens): array
    {
        return array_values(
            array_filter($this->index, fn (Compte $c) => $c->getSens() === $sens)
        );
    }

    /**
     * Retourne tous les comptes du plan.
     *
     * @return Compte[]
     */
    public function all(): array
    {
        return array_values($this->index);
    }

    /**
     * Vérifie l'existence d'un code.
     */
    public function exists(string $code): bool
    {
        return isset($this->index[$code]);
    }

    /**
     * Valide qu'un code est un vrai compte SYSCOHADA.
     */
    public function valider(string $code): bool
    {
        return $this->exists($code);
    }

    /**
     * Retourne le chemin complet (arbre des parents) jusqu'à la racine de classe.
     *
     * @return Compte[]   du plus haut ancêtre au compte lui-même
     */
    public function chemin(string $code): array
    {
        $compte = $this->find($code);
        if ($compte === null) {
            return [];
        }

        $chemin = [$compte];
        $courant = $compte;

        while ($courant->getCodeParent() !== null) {
            $parent = $this->find($courant->getCodeParent());
            if ($parent === null) {
                break;
            }
            array_unshift($chemin, $parent);
            $courant = $parent;
        }

        return $chemin;
    }

    /**
     * Résumé statistique du plan.
     *
     * @return array{
     *   total: int,
     *   par_classe: array<int,int>,
     *   par_niveau: array<int,int>,
     *   par_nature: array<string,int>
     * }
     */
    public function statistiques(): array
    {
        $parClasse = [];
        $parNiveau = [];
        $parNature = [];

        foreach ($this->index as $compte) {
            $parClasse[$compte->getClasse()] = ($parClasse[$compte->getClasse()] ?? 0) + 1;
            $parNiveau[$compte->getNiveau()] = ($parNiveau[$compte->getNiveau()] ?? 0) + 1;
            $nature = $compte->getNature() ?? 'inconnu';
            $parNature[$nature] = ($parNature[$nature] ?? 0) + 1;
        }

        ksort($parClasse);
        ksort($parNiveau);

        return [
            'total'      => count($this->index),
            'par_classe' => $parClasse,
            'par_niveau' => $parNiveau,
            'par_nature' => $parNature,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function normaliser(string $texte): string
    {
        $texte = mb_strtolower($texte, 'UTF-8');

        // Supprime les accents
        $map = [
            'à'=>'a','â'=>'a','ä'=>'a','á'=>'a',
            'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
            'î'=>'i','ï'=>'i','ì'=>'i','í'=>'i',
            'ô'=>'o','ö'=>'o','ò'=>'o','ó'=>'o',
            'ù'=>'u','û'=>'u','ü'=>'u','ú'=>'u',
            'ç'=>'c','ñ'=>'n',
        ];

        return strtr($texte, $map);
    }
}
