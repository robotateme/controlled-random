<?php
declare(strict_types=1);

namespace Robotateme\ControlledRandom\Examples\Roulette;

use InvalidArgumentException;
use Robotateme\ControlledRandom\Base\ControlledRNG;

final class RouletteNumberGuesser
{
    public function __construct(
        private ControlledRNG $rng,
        private float $baseHitPercent = 2.7
    ) {
    }

    public function spin(int $guessedNumber, bool $vip = false, float $vipBonusPercent = 0.0): int
    {
        $this->assertValidNumber($guessedNumber);

        $hitPercent = $this->baseHitPercent;
        if ($vip) {
            $hitPercent += max(0.0, $vipBonusPercent);
        }

        $probability = $this->clampPercent($hitPercent) / 100.0;
        if ($this->rng->bool($probability)) {
            return $guessedNumber;
        }

        return $this->randomNonGuessedNumber($guessedNumber);
    }

    private function randomNonGuessedNumber(int $guessedNumber): int
    {
        $number = $this->rng->int(0, 36);
        if ($number !== $guessedNumber) {
            return $number;
        }

        return ($guessedNumber + 1) % 37;
    }

    private function assertValidNumber(int $number): void
    {
        if ($number < 0 || $number > 36) {
            throw new InvalidArgumentException('Roulette number must be in range 0..36.');
        }
    }

    private function clampPercent(float $percent): float
    {
        return max(0.0, min(100.0, $percent));
    }
}
