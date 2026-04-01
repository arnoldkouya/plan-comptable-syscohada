<?php

declare(strict_types=1);

namespace Syscohada\Contracts;

interface PlanComptableInterface
{
    public function find(string $code): ?CompteInterface;
    public function findOrFail(string $code): CompteInterface;
    public function classe(int $classe): array;
    public function search(string $terme): array;
    public function enfants(string $codeParent): array;
    public function all(): array;
    public function exists(string $code): bool;
}
