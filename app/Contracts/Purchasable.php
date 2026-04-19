<?php

namespace App\Contracts;

interface Purchasable {
    public function getPriceInCents(): int;
    public function getDisplayName(): string;
    public function getMetadata(): array;
}
