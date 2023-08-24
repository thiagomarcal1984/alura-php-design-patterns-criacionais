<?php

use Alura\DesignPattern\Log\{StdOutLogManager, FileLogManager};

require 'vendor/autoload.php';

$logManager = new StdOutLogManager();
$logManager->log('INFO', 'Escrito na tela.');

$logManager = new FileLogManager(__DIR__ . '/log');
$logManager->log('INFO', 'Escrito em arquivo.');
