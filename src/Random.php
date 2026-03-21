<?php
declare(strict_types=1);

namespace Robotateme\ControlledRandom;

use Random\RandomException;

class Random
{
    /**
     * @param int $seed
     * @param float $entropyLevel
     * @return float
     * @throws RandomException
     *
     *  Using:
     *  echo controlled_random(42, 0.5);
     *
     */
    public static function hashRandom(int $seed, float $entropyLevel): float
    {
        $entropyLevel = max(0.0, min(1.0, $entropyLevel));

        $base = hash('sha256', (string) $seed, true);
        $noise = random_bytes(32);

        $mixLength =  (32 * $entropyLevel);

        $mixed = hash(
            'sha256',
            $base . substr($noise, 0, $mixLength),
            true
        );

        $value = unpack('J', substr($mixed, 0, 8))[1];

        return $value / PHP_INT_MAX;
    }


    /**
     * @param float $value
     * @param float $temperature
     * @return float
     *
     * // usage
     * $base = mt_rand() / mt_getrandmax();
     *
     * echo temperature_random($base, 0.5); // более "жадный"
     * echo temperature_random($base, 2.0); // более "хаотичный"
     *
     *
     */
    public static function temperatureRandom(float $value, float $temperature): float
    {
        if ($temperature <= 0) return 0.0;

        return $value ** (1 / $temperature);
    }



}