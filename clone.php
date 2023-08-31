<?php

use Alura\DesignPattern\ItemOrcamento;
use Alura\DesignPattern\NotaFiscal\ConstrutorNotaFiscalProduto;
use Alura\DesignPattern\NotaFiscal\ConstrutorNotaFiscalServico;

require 'vendor/autoload.php';

$item1 = new ItemOrcamento();
$item1->valor = 500; // Se não houver inicialização, o cálculo do valor dos impostos falha.
$item2 = new ItemOrcamento();
$item2->valor = 1000; // Se não houver inicialização, o cálculo do valor dos impostos falha.
$item3 = new ItemOrcamento();
$item3->valor = 1500; // Se não houver inicialização, o cálculo do valor dos impostos falha.

$notaFiscal = (new ConstrutorNotaFiscalProduto())
// $notaFiscal = (new ConstrutorNotaFiscalServico())
    ->paraEmpresa('12345', 'Balão Apagado SA')
    ->comItem($item1)
    ->comItem($item2)
    ->comItem($item3)
    ->comObservacoes('Esta nota fiscal foi construída com um construtor')
    ->constroi();

$notaFiscal2 = clone $notaFiscal;
$notaFiscal2->itens[] = new ItemOrcamento();

var_dump($notaFiscal->dataEmissao);
var_dump($notaFiscal2->dataEmissao);
