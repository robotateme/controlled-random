<?php

use Robotateme\ControlledRandom\ControlledRNG;

require_once 'vendor/autoload.php';

$rng = new ControlledRNG(123, 1);

$trueCount = 0;
$n = 10000;

for ($i = 0; $i < $n; $i++) {
    if ($rng->bool(0.3)) {
        $trueCount++;
    }
}

$ratio = $trueCount / $n;
dump($ratio * 100);