<?php

namespace Alura\DesignPattern\Log;

class StdOutLogManager extends LogManager
{
    public function criarLogWriter() : LogWriter
    {
        return new StdOutLogWriter();
    }
}
