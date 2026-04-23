<?php

use Robotateme\ControlledRandom\Base\ControlledRNG;

require_once 'vendor/autoload.php';

$rng = new ControlledRNG(123, 1);

$trueCount = 0;
$n = 100;
$testArray = [];
for ($i = 0; $i < $n; $i++) {
    $bool = $rng->bool(0.3);
    if ($bool) {
        $trueCount++;
    }
    $testArray[] = $bool;
}
$ratio = $trueCount / $n;
$rng->shuffle($testArray);
dump($testArray, [
    'ratio' => $ratio,
]);