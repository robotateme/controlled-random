# controlled-random

PHP-библиотека для управляемой генерации случайных значений с балансом между:

- детерминизмом (повторяемые последовательности по `seed`);
- энтропией (добавление криптографического шума через `random_bytes`);
- удобными высокоуровневыми методами (`int`, `bool`, `choice`, `shuffle`, распределения и т.д.).

## Что умеет

- Генерировать `float` в диапазоне `[0, 1)`.
- Генерировать целые числа в диапазоне.
- Генерировать булевы значения с заданной вероятностью.
- Выбирать случайный элемент из массива.
- Перемешивать массив (Fisher-Yates).
- Сохранять/восстанавливать внутреннее состояние генератора.
- Генерировать значения из распределений:
  - нормальное (Gaussian),
  - экспоненциальное (Exponential),
  - пуассоновское (Poisson),
  - бернуллиевское (Bernoulli).
- Выбирать элементы по весам.
- Сэмплировать `k` элементов без повторений.
- Управлять «температурой» случайности.

## Требования

- PHP `>= 8.4` (в проекте проверено на `PHP 8.4.1`).
- Composer.

## Установка

### 1) Установить зависимости проекта

```bash
composer install
```

### 2) Подключить автозагрузку

```php
require_once 'vendor/autoload.php';
```

## Быстрый старт

```php
<?php

use Robotateme\ControlledRandom\Base\ControlledRNG;

require_once 'vendor/autoload.php';

$rng = new ControlledRNG(seed: 123, entropyLevel: 0.0);

echo $rng->random() . PHP_EOL;      // float [0,1)
echo $rng->int(1, 10) . PHP_EOL;    // int [1..10]
var_dump($rng->bool(0.3));          // true примерно в 30% случаев

$items = ['a', 'b', 'c'];
echo $rng->choice($items) . PHP_EOL;
```

## Концепция детерминизма и энтропии

`ControlledRNG` использует два шага:

1. Базовый детерминированный PRNG (`xorshift32*`) от `seed`.
2. Опциональное подмешивание энтропии через `random_bytes` + `sha256`.

Параметр `entropyLevel`:

- `0.0` -> полностью повторяемая последовательность;
- `1.0` -> максимальная энтропия (последовательность между запусками обычно различается);
- `0.0..1.0` -> промежуточный режим.

## API: `Robotateme\ControlledRandom\Base\ControlledRNG`

### `__construct(int $seed = 42, float $entropyLevel = 0.0)`

Создаёт генератор с начальными параметрами.

### `setEntropyLevel(float $level): void`

Устанавливает уровень энтропии с ограничением в диапазон `[0.0, 1.0]`.

### `random(): float`

Возвращает число в диапазоне `[0, 1)`.

### `int(int $min, int $max): int`

Возвращает целое число в диапазоне `[min, max]`.

### `bool(float $probability = 0.5): bool`

Возвращает `true` с вероятностью `probability`.

### `choice(array $items): mixed`

Возвращает случайный элемент из массива.  
Бросает `InvalidArgumentException`, если массив пустой.

### `shuffle(array &$array): void`

Перемешивает массив на месте.

### `getState(): array`

Возвращает текущее состояние генератора:

```php
[
    'state' => int,
    'counter' => int
]
```

### `setState(array $state): void`

Восстанавливает состояние из `getState()`.

### `gaussian(float $mean = 0.0, float $stdDev = 1.0): float`

Генерирует значение из нормального распределения (Box-Muller).

### `weightedChoice(array $weights): int|string|null`

Выбор по весам, например:

```php
['a' => 10, 'b' => 1]
```

Бросает `InvalidArgumentException`, если сумма весов `<= 0`.

### `exponential(float $lambda = 1.0): float`

Генерирует значение из экспоненциального распределения.

### `poisson(float $lambda): int`

Генерирует значение из распределения Пуассона.

### `bernoulli(float $p): bool`

Бинарный случайный исход с вероятностью `p`.

### `sample(array $items, int $k): array`

Возвращает `k` случайных элементов без повторений.  
Бросает `InvalidArgumentException`, если `k > count($items)`.

### `temperature(float $temperature = 1.0): float`

Трансформирует случайность:

- `< 1.0` -> более «жадное» распределение;
- `> 1.0` -> более «плоское/хаотичное»;
- `<= 0` -> возвращает `0.0`.

## Дополнительный класс: `Robotateme\ControlledRandom\Base\Random`

В `src/Random.php` есть статические хелперы:

- `hashRandom(int $seed, float $entropyLevel): float`
- `temperatureRandom(float $value, float $temperature): float`

Можно использовать отдельно от `ControlledRNG`, если нужна точечная генерация/трансформация.

## Примеры

### Реплеи и воспроизводимость

```php
$rng = new ControlledRNG(123, 0.0);

$snapshot = $rng->getState();
$a = $rng->random();
$b = $rng->random();

$rng->setState($snapshot);
$a2 = $rng->random();
$b2 = $rng->random();

// $a === $a2 и $b === $b2
```

### Выбор по весам

```php
$rng = new ControlledRNG(123, 0.2);
$item = $rng->weightedChoice([
    'common' => 80,
    'rare' => 15,
    'epic' => 5,
]);
```

### Сэмплирование без повторений

```php
$rng = new ControlledRNG(123, 0.0);
$cards = range(1, 52);
$hand = $rng->sample($cards, 5);
```

## Тесты

В проекте используется PHPUnit 13.

Запуск:

```bash
composer test
```

Локально в этом репозитории тесты проходят:

- `26` тестов
- `28017` assertions

## Статический анализ

Для статического анализа подключён PHPStan (`level 8`) для каталогов `src` и `tests`.

Запуск:

```bash
composer stan
```

## Отдельный документ с примерами

Расширенные практические сценарии вынесены в файл:

- [docs/EXAMPLES_RU.md](docs/EXAMPLES_RU.md)

## Структура проекта

```text
src/
  Base/
    ControlledRNG.php       # базовый генератор
    Random.php              # статические вспомогательные методы
  Examples/
    Sms/
      SmsCodeGenerator.php  # генератор красивых SMS-кодов
      SmsCodePattern.php    # enum шаблонов SMS-кодов
    Roulette/
      RouletteNumberGuesser.php # пример механики рулетки с VIP-шансом
tests/
  ControlledRNGTest.php # unit-тесты
  RouletteNumberGuesserTest.php
random.php              # пример использования
```

## Ограничения и заметки

- При `entropyLevel > 0` генерация перестаёт быть строго воспроизводимой между запусками.
- Для методов с вероятностями/параметрами распределений валидация значений минимальная: проверяйте корректность входных данных на своей стороне.
- Для реальной криптографии используйте специализированные криптографические API; данная библиотека ориентирована на управляемую случайность и моделирование.
