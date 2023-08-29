<?php

use Alura\DesignPattern\ItemOrcamento;
use Alura\DesignPattern\NotaFiscal\ConstrutorNotaFiscal;

require 'vendor/autoload.php';

// Fluent Interface: cada método do construtor de nota fiscal
// retorna um novo construtor de nota fiscal.
$notaFiscal = (new ConstrutorNotaFiscal())
    ->paraEmpresa('12345', 'Balão Apagado SA')
    ->comItem(new ItemOrcamento())
    ->comItem(new ItemOrcamento())
    ->comItem(new ItemOrcamento())
    ->comObservacoes('Esta nota fiscal foi construída com um construtor')
    ->constroi();
 