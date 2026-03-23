<?php

use PHPUnit\Framework\TestCase;
use Robotateme\ControlledRandom\ControlledRNG;


class ControlledRNGTest extends TestCase
{
    public function testDeterministicWithZeroEntropy(): void
    {
        $rng1 = new ControlledRNG(123, 0.0);
        $rng2 = new ControlledRNG(123, 0.0);

        $values1 = [];
        $values2 = [];

        for ($i = 0; $i < 10; $i++) {
            $values1[] = $rng1->random();
            $values2[] = $rng2->random();
        }

        $this->assertEquals($values1, $values2);
    }

    public function testDifferentSeedsProduceDifferentSequences(): void
    {
        $rng1 = new ControlledRNG(123, 0.0);
        $rng2 = new ControlledRNG(456, 0.0);

        $this->assertNotEquals(
            $rng1->random(),
            $rng2->random()
        );
    }

    public function testEntropyBreaksDeterminism(): void
    {
        $rng1 = new ControlledRNG(123, 1.0);
        $rng2 = new ControlledRNG(123, 1.0);

        $this->assertNotEquals(
            $rng1->random(),
            $rng2->random()
        );
    }

    public function testRandomRange(): void
    {
        $rng = new ControlledRNG(123, 0.0);

        for ($i = 0; $i < 1000; $i++) {
            $val = $rng->random();
            $this->assertGreaterThanOrEqual(0.0, $val);
            $this->assertLessThan(1.0, $val);
        }
    }

    public function testIntRange(): void
    {
        $rng = new ControlledRNG(123, 0.0);

        for ($i = 0; $i < 1000; $i++) {
            $val = $rng->int(5, 10);
            $this->assertGreaterThanOrEqual(5, $val);
            $this->assertLessThanOrEqual(10, $val);
        }
    }

    public function testBoolProbability(): void
    {
        $rng = new ControlledRNG(123, 1);

        $trueCount = 0;
        $n = 10000;

        for ($i = 0; $i < $n; $i++) {
            if ($rng->bool(0.3)) {
                $trueCount++;
            }
        }

        $ratio = $trueCount / $n;
        $this->assertTrue($ratio > 0.25 && $ratio < 0.35);
    }

    public function testShufflePreservesElements(): void
    {
        $rng = new ControlledRNG(123, 1);

        $original = range(1, 10);
        $shuffled = $original;

        $rng->shuffle($shuffled);

        sort($original);
        sort($shuffled);

        $this->assertEquals($original, $shuffled);
    }

    public function testShuffleChangesOrder(): void
    {
        $rng = new ControlledRNG(123, 0.0);

        $array = range(1, 10);
        $original = $array;

        $rng->shuffle($array);

        $this->assertNotEquals($original, $array);
    }

    public function testChoiceReturnsValidItem(): void
    {
        $rng = new ControlledRNG(123, 0.0);

        $items = ['a', 'b', 'c'];

        for ($i = 0; $i < 100; $i++) {
            $choice = $rng->choice($items);
            $this->assertContains($choice, $items);
        }
    }

    public function testGaussianDistributionMean(): void
    {
        $rng = new ControlledRNG(123, 0.1);

        $sum = 0;
        $n = 10000;

        for ($i = 0; $i < $n; $i++) {
            $sum += $rng->gaussian(10, 2);
        }

        $mean = $sum / $n;

        $this->assertTrue($mean > 9.5 && $mean < 10.5);
    }

    public function testStateRestore(): void
    {
        $rng = new ControlledRNG(123, 0.0);
        $state = $rng->getState();

        $values1 = [];
        for ($i = 0; $i < 5; $i++) {
            $values1[] = $rng->random();
        }

        $rng->setState($state);

        $values2 = [];
        for ($i = 0; $i < 5; $i++) {
            $values2[] = $rng->random();
        }

        $this->assertEquals($values1, $values2);
    }
}