<?php

declare(strict_types=1);

use App\Command\ImportCommand;

require_once \dirname(__DIR__).'/vendor/autoload.php';

include_once(__DIR__.'/../config/parameters.php');

$command = (new ImportCommand())(RESOURCES_DIR . 'regionsjob.xml');
