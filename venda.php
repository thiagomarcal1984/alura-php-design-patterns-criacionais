<?php

use Alura\DesignPattern\Venda\{VendaProdutoFactory, VendaServicoFactory};

require 'vendor/autoload.php';

// $fabricaVenda = new VendaServicoFactory('Thiago');
$fabricaVenda = new VendaProdutoFactory(500);
$venda = $fabricaVenda->criarVenda();
$imposto = $fabricaVenda->imposto();

var_dump($venda, $imposto);
