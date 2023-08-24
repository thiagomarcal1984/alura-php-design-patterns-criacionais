<?php

namespace Alura\DesignPattern\Log;

class FileLogWriter implements LogWriter
{
    private string $arquivo;

    public function __construct(string $caminhoArquivo)
    {
        $this->arquivo = fopen($caminhoArquivo, 'a+');
    }
    
    public function escreve(string $mensagemFormatada) : void
    {
        echo $mensagemFormatada;
    }

    public function __destruct()
    {
        fclose($this->arquivo);
    }
}
