<?php

namespace App\Data;

final readonly class StaticRouteMapResult
{
    public function __construct(
        public bool $configured,
        public bool $eligible,
        public ?string $imageUrl = null,
        public ?float $distanceKm = null,
        public ?int $durationMinutes = null,
        public ?string $message = null,
    ) {}

    public function hasImage(): bool
    {
        return $this->imageUrl !== null;
    }
}
