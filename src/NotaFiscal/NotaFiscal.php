<?php

namespace Alura\DesignPattern\NotaFiscal;

class NotaFiscal
{
    public string $cnpj;
    public string $razaoSocial;
    public array $itens;
    public string $observacoes;
    public \DateTimeInterface $dataEmissao;
    public float $valorImpostos;

    public function __construct(
        string $cnpj,
        string $razaoSocial,
        array $itens,
        string $observacoes,
        \DateTimeInterface $dataEmissao,
        float $valorImpostos,
    )
    {
        $this->cnpj = $cnpj;
        $this->razaoSocial = $razaoSocial;
        $this->itens = $itens;
        $this->observacoes = $observacoes;
        $this->dataEmissao = $dataEmissao;
        $this->valorImpostos = $valorImpostos;
    }

    public function valor(): float
    {
        return 0;
    }
}
