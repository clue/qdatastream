<?php

use Clue\QDataStream\Writer;
use Clue\QDataStream\Reader;

require __DIR__ . '/../vendor/autoload.php';

if (extension_loaded('xdebug')) {
    echo 'NOTICE: The "xdebug" extension is loaded, this has a major impact on performance.' . PHP_EOL;
}

$n = isset($argv[1]) ? (int)$argv[1] : 10000;
$data = array('name' => 'hello', 'on' => true);
$data = array_fill(0, $n, $data);

$writer = new Writer();
$writer->writeQVariant($data);

$time = microtime(true);

$reader = new Reader((string)$writer);
$data = $reader->readQVariant();

$time = microtime(true) - $time;
echo sprintf('%.3f', $time) . 's to parse ' . $n . ' entries' . PHP_EOL;
