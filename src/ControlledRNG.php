<?php
declare(strict_types=1);
namespace Robotateme\ControlledRandom;


use InvalidArgumentException;
/**
 *
 * $rng = new ControlledRNG(seed: 123, entropyLevel: 0.3);
 *
 * echo $rng->random();      // float
 * echo $rng->int(1, 10);    // int
 * var_dump($rng->bool());   // bool
 *
 * $items = ['a', 'b', 'c'];
 * echo $rng->choice($items);
 *
*/
class ControlledRNG
{
    private int $state;
    private float $entropyLevel;
    private string $buffer = '';
    private int $counter = 0;
    private int|null|float $gaussianSpare;

    public function __construct(int $seed = 42, float $entropyLevel = 0.0)
    {
        $this->setEntropyLevel($entropyLevel);
        $this->state = $seed;
    }

    public function setEntropyLevel(float $level): void
    {
        $this->entropyLevel = max(0.0, min(1.0, $level));
    }

    /**
     * Детерминированный PRNG (xorshift32*)
     */
    private function prng(): int
    {
        $x = $this->state;

        $x ^= ($x << 13) & 0xFFFFFFFF;
        $x ^= ($x >> 17);
        $x ^= ($x << 5) & 0xFFFFFFFF;

        $this->state = $x & 0xFFFFFFFF;

        return $this->state;
    }
    /**
     * Подмешивание энтропии через hash (правильный способ)
     */
    private function mix(int $value): int
    {
        if (0.0 === $this->entropyLevel) {
            return $value;
        }

        $noiseBytes = (int)(32 * $this->entropyLevel);
        $noise = random_bytes($noiseBytes);

        $data = pack('J', $value) . $noise . pack('J', $this->counter++);
        $hash = hash('sha256', $data, true);

        return unpack('J', substr($hash, 0, 8))[1];
    }

    /**
     * Основной метод — float [0,1)
     */
    public function random(): float
    {
        $val = $this->prng();
        $mixed = $this->mix($val);

        return ($mixed & 0x7FFFFFFFFFFFFFFF) / 0x7FFFFFFFFFFFFFFF;
    }

    /**
     * int диапазон
     */
    public function int(int $min, int $max): int
    {
        return $min + (int)floor($this->random() * ($max - $min + 1));
    }

    /**
     * bool
     */
    public function bool(float $probability = 0.5): bool
    {
        return $this->random() < $probability;
    }

    /**
     * Выбор элемента
     */
    public function choice(array $items)
    {
        if (empty($items)) {
            throw new InvalidArgumentException("Empty array");
        }

        return $items[$this->int(0, count($items) - 1)];
    }

    /**
     * Shuffle (Fisher–Yates)
     */
    public function shuffle(array &$array): void
    {
        for ($i = count($array) - 1; $i > 0; $i--) {
            $j = $this->int(0, $i);
            [$array[$i], $array[$j]] = [$array[$j], $array[$i]];
        }
    }

    /**
     * Для replay/debug
     */
    public function getState(): array
    {
        return [
            'state' => $this->state,
            'counter' => $this->counter
        ];
    }

    public function setState(array $state): void
    {
        $this->state = $state['state'];
        $this->counter = $state['counter'];
    }

    public function gaussian(float $mean = 0.0, float $stdDev = 1.0): float
    {
        // используем кэш (оптимизация Box-Muller)
        if ($this->gaussianSpare !== null) {
            $val = $this->gaussianSpare;
            $this->gaussianSpare = null;
            return $mean + $stdDev * $val;
        }

        $u1 = $this->random();
        $u2 = $this->random();

        // защита от log(0)
        $u1 = max($u1, 1e-12);

        $r = sqrt(-2.0 * log($u1));
        $theta = 2.0 * M_PI * $u2;

        $z0 = $r * cos($theta);
        $z1 = $r * sin($theta);

        $this->gaussianSpare = $z1;

        return $mean + $stdDev * $z0;
    }
}