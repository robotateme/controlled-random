<?php
declare(strict_types=1);

namespace Robotateme\ControlledRandom;

enum SmsCodePattern: string
{
    case Repeat = 'repeat';
    case Pair = 'pair';
    case Triple = 'triple';
    case Palindrome = 'palindrome';
    case Ascending = 'ascending';
    case Descending = 'descending';
}
