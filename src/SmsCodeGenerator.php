<?php
declare(strict_types=1);

namespace Robotateme\ControlledRandom;

final class SmsCodeGenerator
{
    public function __construct(private ControlledRNG $rng)
    {
    }

    public function generate(): string
    {
        $patterns = [
            'repeat',
            'pair',
            'triple',
            'palindrome',
            'ascending',
            'descending',
        ];
        $pattern = $patterns[$this->int(0, count($patterns) - 1)];

        return match ($pattern) {
            'repeat' => $this->repeatCode(),
            'pair' => $this->pairCode(),
            'triple' => $this->tripleCode(),
            'palindrome' => $this->palindromeCode(),
            'ascending' => $this->ascendingCode(),
            'descending' => $this->descendingCode(),
        };
    }

    private function int(int $min, int $max): int
    {
        return $this->rng->int($min, $max);
    }

    private function repeatCode(): string
    {
        $digit = (string) $this->int(0, 9);
        return str_repeat($digit, 6);
    }

    private function pairCode(): string
    {
        $a = (string) $this->int(0, 9);
        $b = (string) $this->int(0, 9);

        return $a . $b . $a . $b . $a . $b;
    }

    private function tripleCode(): string
    {
        $a = (string) $this->int(0, 9);
        $b = (string) $this->int(0, 9);
        $c = (string) $this->int(0, 9);

        return $a . $b . $c . $a . $b . $c;
    }

    private function palindromeCode(): string
    {
        $a = (string) $this->int(0, 9);
        $b = (string) $this->int(0, 9);
        $c = (string) $this->int(0, 9);

        return $a . $b . $c . $c . $b . $a;
    }

    private function ascendingCode(): string
    {
        $start = $this->int(0, 4);

        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= (string) ($start + $i);
        }

        return $code;
    }

    private function descendingCode(): string
    {
        $start = $this->int(5, 9);

        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= (string) ($start - $i);
        }

        return $code;
    }
}
