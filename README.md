# Padrões criacionais (Gang of Four)

- [ ] Abstract Factory
- [ ] Builder
- [ ] Factory Method
- [x] Flyweight (?Ele é estrutural, mas controla a criação de flyweights também.)
- [ ] Prototype
- [ ] Singleton

Fonte: https://pt.wikipedia.org/wiki/Padr%C3%A3o_de_projeto_de_software

# Criando Flyweights
## Revisão
Renomeamos a classe `DadosExtrinsecosPedido` para `TemplatePedido` para facilitar a leitura do nome da classe extrínseca do Flyweight.

## Cache de objetos
Criaremos uma classe que vai criar/recuperar Flyweights: a `CriadorDePedido`. Se houver algum dado extrínseco dentro do cache dessa classe, ele será reusado, senão ele será criado.

Classe `Pedido`:
```php
<?php

namespace Alura\DesignPattern\Pedido;

use Alura\DesignPattern\Orcamento;

class Pedido
{
    public TemplatePedido $template;
    public Orcamento $orcamento;
}
```

Classe `CriadorDePedido`:
```php
<?php

namespace Alura\DesignPattern\Pedido;

use Alura\DesignPattern\Orcamento;

class CriadorDePedido
{
    private array $templates = [];

    public function criaPedido(
        string $nomeCliente,
        string $dataFormatada,
        Orcamento $orcamento,
    ) : Pedido {
        $template = $this->gerarTemplateDePedido($nomeCliente, $dataFormatada);
        $pedido = new Pedido();
        $pedido->template = $template;
        $pedido->orcamento = $orcamento;

        return $pedido;
    }

    private function gerarTemplateDePedido(
        string $nomeCliente,
        string $dataFormatada
    ) : TemplatePedido {
        $hash = md5($nomeCliente . $dataFormatada);
        if (!array_key_exists($hash, $this->templates)) {
            $template = new TemplatePedido(
                $nomeCliente, 
                new \DateTimeImmutable($dataFormatada)
            );
            $this->templates[$hash] = $template;
        }
        return $this->templates[$hash];
    }
}
```
Invocação do criador de pedidos (arquivo `pedidos.php`): 
```php
<?php

use Alura\DesignPattern\Orcamento;
use Alura\DesignPattern\Pedido\CriadorDePedido;

require 'vendor/autoload.php';

$pedidos = [];
$criadorDePedido = new CriadorDePedido();

for ($i = 0; $i < 10000; $i++) {
    $orcamento = new Orcamento();
    $pedido = $criadorDePedido->criaPedido(
        'a',
        date('Y-m-d'),
        $orcamento
    );

    $pedidos[] = $pedido;
}

echo memory_get_peak_usage();
```

Leitura complementar sobre o padrão Flyweight: https://refactoring.guru/design-patterns/flyweight

# Fabricando diferentes objetos

## Gerando log de ações
Criaremos uma interface para qualquer tipo de loggers chamada `LogWriter`:

```php
<?php

namespace Alura\DesignPattern\Log;

interface LogWriter
{
    public function escreve(string $mensagemFormatada) : void;
}
```

E também mais duas implementações (uma para log em tela e outra para log em arquivo):
```php
<?php

namespace Alura\DesignPattern\Log;

class StdOutLogWriter implements LogWriter
{
    public function escreve(string $mensagemFormatada) : void
    {
        echo $mensagemFormatada;
    }
}
```

```php
<?php

namespace Alura\DesignPattern\Log;

class FileLogWriter implements LogWriter
{
    private $arquivo;

    public function __construct(string $caminhoArquivo)
    {
        $this->arquivo = fopen($caminhoArquivo, 'a+');
    }
    
    public function escreve(string $mensagemFormatada) : void
    {
        fwrite($this->arquivo, $mensagemFormatada . PHP_EOL);
    }

    public function __destruct()
    {
        fclose($this->arquivo);
    }
}
```
Na próxima aula será implementado o gerenciador de loggers.

## Executando a ação de log
Problema: como dispensar a adaptação do código para escolher qual logger usar? Note que cada construtor tem número de parâmetros diferentes:

```php
//  return new StdOutLogWriter();
//  return new FileLogWriter('caminho');
```

A solução é abstrair a fabricação para um método abstrato (classe abstrata `LogManager`):

```php
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

    abstract public function criarLogWriter() : LogWriter;
}
```
Ainda faltam as classes concretas. Na próxima aula elas serão criadas.

## Factory Method
Desenvolvimento das classes concretas de fabricação (classe `FileLogManager`):
```php
<?php

namespace Alura\DesignPattern\Log;

class FileLogManager extends LogManager
{
    private string $caminhoArquivo;

    public function __construct(string $caminhoArquivo)
    {
        $this->caminhoArquivo = $caminhoArquivo;
    }

    public function criarLogWriter() : LogWriter
    {
        return new FileLogWriter($this->caminhoArquivo);
    }
}
```
Classe `StdOutLogManager`: 
```php
<?php

namespace Alura\DesignPattern\Log;

class StdOutLogManager extends LogManager
{
    public function criarLogWriter() : LogWriter
    {
        return new StdOutLogWriter();
    }
}
```
Arquivo de teste `log.php`:
```php
<?php

use Alura\DesignPattern\Log\{StdOutLogManager, FileLogManager};

require 'vendor/autoload.php';

$logManager = new StdOutLogManager();
$logManager->log('INFO', 'Escrito na tela.');

$logManager = new FileLogManager(__DIR__ . '/log');
$logManager->log('INFO', 'Escrito em arquivo.');
```

> Note que o método de fabricação `criarLogWriter` é reescrito, enquanto a lógica do método `log` permanece a mesma.
