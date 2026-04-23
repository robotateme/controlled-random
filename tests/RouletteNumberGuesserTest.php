<?php
declare(strict_types=1);

namespace Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Robotateme\ControlledRandom\ControlledRNG;
use Robotateme\ControlledRandom\RouletteNumberGuesser;

final class RouletteNumberGuesserTest extends TestCase
{
    public function testThrowsOnInvalidGuessedNumber(): void
    {
        $guesser = new RouletteNumberGuesser(new ControlledRNG(1, 0.0));

        $this->expectException(InvalidArgumentException::class);
        $guesser->spin(37, false);
    }

    public function testAlwaysHitsWithHundredPercentChance(): void
    {
        $guesser = new RouletteNumberGuesser(new ControlledRNG(2, 0.0), 100.0);

        for ($i = 0; $i < 200; $i++) {
            $this->assertSame(17, $guesser->spin(17, false));
        }
    }

    public function testLosingResultNeverEqualsGuessedNumber(): void
    {
        $guesser = new RouletteNumberGuesser(new ControlledRNG(3, 0.0), 0.0);

        for ($i = 0; $i < 2000; $i++) {
            $result = $guesser->spin(12, false);

            $this->assertGreaterThanOrEqual(0, $result);
            $this->assertLessThanOrEqual(36, $result);
            $this->assertNotSame(12, $result);
        }
    }

    public function testVipBonusIncreasesHitRateByConfiguredPercent(): void
    {
        $basePercent = 5.0;
        $vipBonusPercent = 10.0;

        $normal = new RouletteNumberGuesser(new ControlledRNG(42, 1.0), $basePercent);
        $vip = new RouletteNumberGuesser(new ControlledRNG(42, 1.0), $basePercent);

        $rounds = 10000;
        $normalHits = 0;
        $vipHits = 0;

        for ($i = 0; $i < $rounds; $i++) {
            if ($normal->spin(7, false, $vipBonusPercent) === 7) {
                $normalHits++;
            }
            if ($vip->spin(7, true, $vipBonusPercent) === 7) {
                $vipHits++;
            }
        }

        $normalRate = $normalHits / $rounds;
        $vipRate = $vipHits / $rounds;

        $this->assertGreaterThan($normalRate + 0.07, $vipRate);
    }

    public function testVipChanceIsClampedToHundredPercent(): void
    {
        $guesser = new RouletteNumberGuesser(new ControlledRNG(777, 0.0), 80.0);

        for ($i = 0; $i < 200; $i++) {
            $this->assertSame(3, $guesser->spin(3, true, 50.0));
        }
    }
}
