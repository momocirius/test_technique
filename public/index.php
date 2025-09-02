<?php

declare(strict_types=1);

use App\Command\ImportCommand;

require_once \dirname(__DIR__).'/vendor/autoload.php';

include_once(__DIR__.'/../config/parameters.php');

$command = new ImportCommand();

// Test import RegionsJob (XML)
echo "=== IMPORT REGIONSJOB (XML) ===\n";
$command(RESOURCES_DIR . 'regionsjob.xml');

echo "\n";

// Test import JobTeaser (JSON)
echo "=== IMPORT JOBTEASER (JSON) ===\n"; 
$command(RESOURCES_DIR . 'jobteaser.json');
