<?php

declare(strict_types=1);

namespace Syscohada;

use Syscohada\Contracts\CompteInterface;

/**
 * Représente un compte du Plan Comptable SYSCOHADA.
 */
final class Compte implements CompteInterface
{
    public function __construct(
        private readonly string  $code,
        private readonly string  $libelle,
        private readonly int     $classe,
        private readonly int     $niveau,
        private readonly ?string $codeParent = null,
        private readonly bool    $actif = true,
        private readonly ?string $nature = null,   // 'bilan' | 'gestion' | 'hors-bilan' | 'cage'
        private readonly ?string $sens = null,     // 'debit' | 'credit' | 'mixte'
    ) {}

    public function getCode(): string     { return $this->code; }
    public function getLibelle(): string  { return $this->libelle; }
    public function getClasse(): int      { return $this->classe; }
    public function getNiveau(): int      { return $this->niveau; }
    public function getCodeParent(): ?string { return $this->codeParent; }
    public function isActif(): bool       { return $this->actif; }
    public function getNature(): ?string  { return $this->nature; }
    public function getSens(): ?string    { return $this->sens; }

    /** Indique si ce compte est une racine (compte à 1 ou 2 chiffres) */
    public function isGroupe(): bool { return $this->niveau <= 2; }

    /** Indique si ce compte est divisionnaire (4 chiffres) */
    public function isDivisionnaire(): bool { return $this->niveau === 4; }

    /** Préfixe du code parent (pour navigation) */
    public function getPrefixeParent(): string
    {
        return substr($this->code, 0, max(1, strlen($this->code) - 1));
    }

    public function toArray(): array
    {
        return [
            'code'        => $this->code,
            'libelle'     => $this->libelle,
            'classe'      => $this->classe,
            'niveau'      => $this->niveau,
            'code_parent' => $this->codeParent,
            'actif'       => $this->actif,
            'nature'      => $this->nature,
            'sens'        => $this->sens,
        ];
    }

    public function __toString(): string
    {
        return "{$this->code} — {$this->libelle}";
    }
}
