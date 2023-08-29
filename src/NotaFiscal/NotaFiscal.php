<?php

namespace Alura\DesignPattern\NotaFiscal;

use Alura\DesignPattern\ItemOrcamento;

class NotaFiscal
{
    public string $cnpj;
    public string $razaoSocial;
    public array $itens;
    public string $observacoes;
    public \DateTimeInterface $dataEmissao;
    public float $valorImpostos;

    public function valor(): float
    {
        // Lembre-se de atribuir o valor antes de invocar este método.
        return array_reduce(
            $this->itens, 
            function(float $valorAcumulado, ItemOrcamento $item) { 
                return $item->valor + $valorAcumulado;
            },
            0);
    }
}
