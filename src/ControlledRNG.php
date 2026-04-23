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
    private int $counter = 0;
    private ?float $gaussianSpare = null;

    public function __construct(int $seed = 42, private float $entropyLevel = 0.0)
    {
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

        $noiseBytes = max(1, (int) floor(32 * $this->entropyLevel));
        $noise = random_bytes($noiseBytes);

        $data = pack('J', $value) . $noise . pack('J', $this->counter++);
        $hash = hash('sha256', $data, true);

        $unpacked = unpack('J', substr($hash, 0, 8));
        if ($unpacked === false) {
            throw new \RuntimeException('Failed to unpack hash.');
        }

        return $unpacked[1];
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
     *
     * @template T
     * @param array<T> $items
     * @return T
     */
    public function choice(array $items): mixed
    {
        if (empty($items)) {
            throw new InvalidArgumentException("Empty array");
        }

        return $items[$this->int(0, count($items) - 1)];
    }

    /**
     * Shuffle (Fisher–Yates)
     *
     * @param array<mixed> $array
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
     *
     * @return array{state: int, counter: int}
     */
    public function getState(): array
    {
        return [
            'state' => $this->state,
            'counter' => $this->counter
        ];
    }

    /**
     * @param array{state: int, counter: int} $state
     */
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

    /**
     * Выбор по весам
     * ['a' => 10, 'b' => 1]
     *
     * @param array<int|string, int|float> $weights
     */
    public function weightedChoice(array $weights): int|string|null
    {
        $total = array_sum($weights);

        if ($total <= 0) {
            throw new InvalidArgumentException("Weights must be > 0");
        }

        $r = $this->random() * $total;

        foreach ($weights as $item => $weight) {
            $r -= $weight;
            if ($r <= 0) {
                return $item;
            }
        }

        return array_key_last($weights);
    }

    /**
     * λ = rate (событий в единицу времени)
     */
    public function exponential(float $lambda = 1.0): float
    {
        $u = max($this->random(), 1e-12);
        return -log($u) / $lambda;
    }

    public function poisson(float $lambda): int
    {
        $L = exp(-$lambda);
        $k = 0;
        $p = 1.0;

        do {
            $k++;
            $p *= $this->random();
        } while ($p > $L);

        return $k - 1;
    }

    public function bernoulli(float $p): bool
    {
        return $this->random() < $p;
    }

    /**
     * @template T
     * @param array<T> $items
     * @return array<T>
     */
    public function sample(array $items, int $k): array
    {
        if ($k > count($items)) {
            throw new InvalidArgumentException("k > size");
        }

        $this->shuffle($items);
        return array_slice($items, 0, $k);
    }

    public function temperature(float $temperature = 1.0): float
    {
        $val = $this->random();

        if ($temperature <= 0) {
            return 0.0;
        }

        return $val ** (1 / $temperature);
    }
}
