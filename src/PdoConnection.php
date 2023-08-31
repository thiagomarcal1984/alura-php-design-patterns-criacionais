<?php

namespace Alura\DesignPattern;

class PdoConnection extends \PDO
{
    private static ?self $instance = null;

    private function __construct(
        $dsn, 
        $username = null, 
        $password = null, 
        $options = null
    ) {
        parent::__construct($dsn, $username, $password, $options);
    }

    public static function getInstance(
        $dsn, 
        $username = null, 
        $password = null, 
        $options = null
    ) : self {
        if (is_null(self::$instance)){
            self::$instance = new static($dsn, $username, $password, $options);
        }
        return self::$instance;
    }
}
