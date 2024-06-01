<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Tuupola\Base58;

$b58 = new Base58([
    "characters" => Base58::BITCOIN,
]);

$string = 'F16EMaNw3pkn3v6f2BgnSSs53zAKH4Q8YJg';

$decoded = $b58->decode($string);

echo "Encoded: " . $string . PHP_EOL;

echo "Decoded: " . bin2hex($decoded) . PHP_EOL;
