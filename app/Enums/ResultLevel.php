<?php

namespace App\Enums;

enum ResultLevel: string
{
    case ToDevelop = 'to_develop';
    case Developing = 'developing';
    case Strength = 'strength';

    /**
     * Clasifica un puntaje de 0 a 100: < 50 por desarrollar, 50 a < 75 en desarrollo, ≥ 75 fortaleza.
     */
    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= 75 => self::Strength,
            $score >= 50 => self::Developing,
            default => self::ToDevelop,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ToDevelop => 'Por desarrollar',
            self::Developing => 'En desarrollo',
            self::Strength => 'Fortaleza',
        };
    }
}
