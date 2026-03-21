<?php

use Robotateme\ControlledRandom\ControlledRNG;

require_once 'vendor/autoload.php';

$rng = new ControlledRNG(seed: 123, entropyLevel: 0.3);
$items = ['a', 'b', 'c', 'd'];

$i = 0;
while (true) {
    $i++;
    $rng->shuffle($items);
    if ($items === ['a', 'b', 'c', 'd']) {
        dd('Exit on '. $i);
    }
    dump(implode($items));
    sleep(1);
}
