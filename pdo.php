<?php

use Alura\DesignPattern\PdoConnection;

require 'vendor/autoload.php';

$pdo = PdoConnection::getInstance('sqlite::memory:');

$pdo2 = PdoConnection::getInstance('sqlite::memory:'); // Obtendo o Singleton, sem recriá-lo.

var_dump($pdo, $pdo2); // Agora as duas referências apontam para o mesmo objeto.
