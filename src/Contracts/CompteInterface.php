<?php

declare(strict_types=1);

namespace Syscohada\Contracts;

interface CompteInterface
{
    public function getCode(): string;
    public function getLibelle(): string;
    public function getClasse(): int;
    public function getNiveau(): int;
    public function getCodeParent(): ?string;
    public function isActif(): bool;
    public function toArray(): array;
}
