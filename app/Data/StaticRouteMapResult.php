<?php

namespace App\Data;

final readonly class StaticRouteMapResult
{
    /**
     * @param  array{type: string, coordinates: list<array{0: float|int, 1: float|int}>}|null  $geometry
     */
    public function __construct(
        public bool $configured,
        public bool $eligible,
        public ?array $geometry = null,
        public ?float $distanceKm = null,
        public ?int $durationMinutes = null,
        public ?string $message = null,
        public ?float $pickupLng = null,
        public ?float $pickupLat = null,
        public ?float $dropoffLng = null,
        public ?float $dropoffLat = null,
    ) {}

    public function hasRoute(): bool
    {
        return is_array($this->geometry)
            && ($this->geometry['type'] ?? null) === 'LineString'
            && is_array($this->geometry['coordinates'] ?? null)
            && $this->geometry['coordinates'] !== [];
    }
}
