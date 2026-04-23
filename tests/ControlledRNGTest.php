<?php
declare(strict_types=1);

namespace Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Robotateme\ControlledRandom\Base\ControlledRNG;
use Robotateme\ControlledRandom\Examples\Sms\SmsCodeGenerator;


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

    public function testChoiceThrowsOnEmptyArray(): void
    {
        $rng = new ControlledRNG(123, 0.0);

        $this->expectException(InvalidArgumentException::class);
        $rng->choice([]);
    }

    public function testWeightedChoiceWithSinglePositiveWeightAlwaysReturnsThatItem(): void
    {
        $rng = new ControlledRNG(42, 0.0);

        for ($i = 0; $i < 500; $i++) {
            $picked = $rng->weightedChoice([
                'only' => 10,
                'zeroA' => 0,
                'zeroB' => 0,
            ]);

            $this->assertSame('only', $picked);
        }
    }

    public function testWeightedChoiceThrowsWhenTotalWeightIsNotPositive(): void
    {
        $rng = new ControlledRNG(123, 0.0);

        $this->expectException(InvalidArgumentException::class);
        $rng->weightedChoice([
            'a' => 0,
            'b' => 0,
        ]);
    }

    public function testSampleThrowsWhenKIsGreaterThanCollectionSize(): void
    {
        $rng = new ControlledRNG(123, 0.0);

        $this->expectException(InvalidArgumentException::class);
        $rng->sample([1, 2, 3], 4);
    }

    public function testTemperatureReturnsZeroForNonPositiveTemperature(): void
    {
        $rng = new ControlledRNG(123, 0.0);

        $this->assertSame(0.0, $rng->temperature(0.0));
        $this->assertSame(0.0, $rng->temperature(-1.0));
    }

    public function testExponentialIsAlwaysNonNegative(): void
    {
        $rng = new ControlledRNG(123, 0.0);

        for ($i = 0; $i < 2000; $i++) {
            $value = $rng->exponential(1.5);
            $this->assertGreaterThanOrEqual(0.0, $value);
        }
    }

    public function testPoissonIsAlwaysNonNegativeInteger(): void
    {
        $rng = new ControlledRNG(123, 0.0);

        for ($i = 0; $i < 2000; $i++) {
            $value = $rng->poisson(3.5);
            $this->assertGreaterThanOrEqual(0, $value);
        }
    }

    public function testSetEntropyLevelClampsToZeroForNegativeValues(): void
    {
        $rng = new ControlledRNG(123, 0.0);
        $control = new ControlledRNG(123, 0.0);

        $rng->setEntropyLevel(-10.0);

        $values1 = [];
        $values2 = [];
        for ($i = 0; $i < 20; $i++) {
            $values1[] = $rng->random();
            $values2[] = $control->random();
        }

        $this->assertSame($values2, $values1);
    }

    public function testSmsCodeGenerationProducesMemorableSixDigitStrings(): void
    {
        $generator = new SmsCodeGenerator(new ControlledRNG(2026, 0.0));

        for ($i = 0; $i < 1000; $i++) {
            $smsCode = $generator->generate();

            $this->assertMatchesRegularExpression('/^\d{6}$/', $smsCode);
            $this->assertTrue($this->isMemorableSmsCode($smsCode));
        }
    }

    public function testSmsCodeGenerationCanProduceLeadingZero(): void
    {
        $generator = new SmsCodeGenerator(new ControlledRNG(123, 0.0));
        $foundLeadingZero = false;

        for ($i = 0; $i < 2000; $i++) {
            $smsCode = $generator->generate();
            if (str_starts_with($smsCode, '0')) {
                $foundLeadingZero = true;
                break;
            }
        }

        $this->assertTrue($foundLeadingZero);
    }

    private function isMemorableSmsCode(string $code): bool
    {
        if (preg_match('/^(\d)\1{5}$/', $code) === 1) {
            return true;
        }

        if (preg_match('/^(\d)(\d)\1\2\1\2$/', $code) === 1) {
            return true;
        }

        if (preg_match('/^(\d)(\d)(\d)\1\2\3$/', $code) === 1) {
            return true;
        }

        if (preg_match('/^(\d)(\d)(\d)\3\2\1$/', $code) === 1) {
            return true;
        }

        return in_array($code, [
            '012345',
            '123456',
            '234567',
            '345678',
            '456789',
            '987654',
            '876543',
            '765432',
            '654321',
            '543210',
        ], true);
    }
}
