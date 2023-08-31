<?php

use Alura\DesignPattern\PdoConnection;

require 'vendor/autoload.php';

$pdo = new PdoConnection('sqlite::memory:');

$pdo2 = new PdoConnection('sqlite::memory:'); // Repetição da instanciação.

var_dump($pdo, $pdo2); // São dois objetos diferentes, com ids diferentes.
