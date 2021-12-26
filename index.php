<?php   
declare(strict_types=1);

use App\Controllers\LogParser;

require_once 'vendor/autoload.php';

if ($argc < 2) {
    exit("Error. NO file \n");
}

$logParser = new LogParser();
echo $logParser->parseFile($argv[1]) . PHP_EOL;
