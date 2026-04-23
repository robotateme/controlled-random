# Примеры использования `controlled-random`

Ниже собраны практические сценарии работы с `Robotateme\ControlledRandom\ControlledRNG`.

Во всех примерах:

```php
<?php

use Robotateme\ControlledRandom\ControlledRNG;

require_once __DIR__ . '/../vendor/autoload.php';
```

## 1) Воспроизводимая последовательность

```php
$rng = new ControlledRNG(seed: 123, entropyLevel: 0.0);

for ($i = 0; $i < 5; $i++) {
    echo $rng->random() . PHP_EOL;
}
```

При одинаковом `seed` и `entropyLevel = 0.0` результат между запусками повторяется.

## 2) Частичная энтропия

```php
$rng = new ControlledRNG(seed: 123, entropyLevel: 0.3);

echo $rng->int(1, 100) . PHP_EOL;
echo $rng->int(1, 100) . PHP_EOL;
```

Здесь часть энтропии добавляется через `random_bytes`, поэтому точное повторение уже не гарантируется.

## 3) Случайный булев результат с заданной вероятностью

```php
$rng = new ControlledRNG(42, 0.1);

$n = 10000;
$success = 0;

for ($i = 0; $i < $n; $i++) {
    if ($rng->bool(0.2)) {
        $success++;
    }
}

echo 'ratio=' . ($success / $n) . PHP_EOL; // около 0.2
```

## 4) Выбор по весам (например, лут)

```php
$rng = new ControlledRNG(777, 0.0);

$drop = $rng->weightedChoice([
    'common' => 80,
    'rare' => 15,
    'epic' => 5,
]);

echo $drop . PHP_EOL;
```

## 5) Перемешивание и сэмплирование

```php
$rng = new ControlledRNG(555, 0.0);

$deck = range(1, 52);
$rng->shuffle($deck);

$hand = $rng->sample($deck, 5);
print_r($hand);
```

## 6) Сохранение/восстановление состояния (replay)

```php
$rng = new ControlledRNG(123, 0.0);

$state = $rng->getState();
$a = $rng->random();
$b = $rng->random();

$rng->setState($state);
$a2 = $rng->random();
$b2 = $rng->random();

var_dump($a === $a2, $b === $b2); // true, true
```

## 7) Нормальное распределение (Gaussian)

```php
$rng = new ControlledRNG(123, 0.2);

$mean = 10.0;
$stdDev = 2.0;
$values = [];

for ($i = 0; $i < 1000; $i++) {
    $values[] = $rng->gaussian($mean, $stdDev);
}

echo 'first=' . $values[0] . PHP_EOL;
```

## 8) Пуассоновские события во времени

```php
$rng = new ControlledRNG(123, 0.0);

$lambda = 4.0; // среднее число событий
$events = $rng->poisson($lambda);

echo $events . PHP_EOL;
```

## 9) Температура случайности

```php
$rng = new ControlledRNG(321, 0.0);

$cold = $rng->temperature(0.5);
$neutral = $rng->temperature(1.0);
$hot = $rng->temperature(2.0);

var_dump($cold, $neutral, $hot);
```

Интерпретация:

- `temperature < 1.0`: распределение более концентрированное;
- `temperature = 1.0`: без изменения;
- `temperature > 1.0`: распределение более «плоское».
