<?php

namespace Alura\DesignPattern\Log;

abstract class LogManager
{
    public function log(string $severidade, string $mensagemFormatada) : void
    {
        $logWriter = $this->criarLogWriter();

        $dataHoje = date('d/m/Y');
        $mensagemFormatada = "[$dataHoje][$severidade]: $mensagemFormatada";

        $logWriter->escreve($mensagemFormatada);
    }

    /**
     * Problema: como dispensar a adaptação do código para escolher 
     * qual logger usar? Note que cada construtor tem número de
     * parâmetros diferentes.
     *  */ 

    //  return new StdOutLogWriter();
    //  return new FileLogWriter('caminho');

    abstract public function criarLogWriter() : LogWriter;
}
