# Padrões criacionais (Gang of Four)

- [x] Abstract Factory
- [x] Builder
- [x] Factory Method
- [x] Flyweight (?Ele é estrutural, mas controla a criação de flyweights também.)
- [x] Prototype
- [x] Singleton

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

## Falando sobre o padrão
O padrão Factory Method é uma especialização do padrão Template Method: apenas a criação do objeto (Factory Method) varia, mas o template (uso do objeto criado, na forma da superclasse) é o mesmo.

Leitura complementar sobre o padrão Factory Method: 
- https://refactoring.guru/design-patterns/factory-method
- https://refactoring.guru/design-patterns/factory-comparison

# Fabricando objetos relacionados
## Gerando venda
Queremos fazer com que vendas de serviços calcule o ISS, e que a venda de produtos calcule ICMS.

Classe abstrata `Venda`:
```php
<?php

namespace Alura\DesignPattern\Venda;

abstract class Venda
{
    public \DateTimeInterface $dataRealizacao;

    public function __construct(\DateTimeInterface $dataRealizacao)
    {
        $this->dataRealizacao = $dataRealizacao;
    }
}
```
Classe concreta `VendaServico`:
```php
<?php

namespace Alura\DesignPattern\Venda;

class VendaServico extends Venda
{
    private string $nomePrestador;

    public function __construct(\DateTimeInterface $dataRealizacao, string $nomePrestador)
    {
        parent::__construct($dataRealizacao);
        $this->nomePrestador = $nomePrestador;
    }
}
```
Classe concreta `VendaProduto`:
```php
<?php

namespace Alura\DesignPattern\Venda;

class VendaProduto extends Venda
{
    /**
     * @var int Peso do produto em gramas.
     */
    private int $pesoProduto;

    public function __construct(\DateTimeInterface $dataRealizacao, int $pesoProduto)
    {
        parent::__construct($dataRealizacao);
        $this->pesoProduto = $pesoProduto;
    }
}
```
## Impostos e fábricas
Criação da interface de fábrica de uma família de objetos relacionados a vendas:

```php
<?php

namespace Alura\DesignPattern\Venda;

use Alura\DesignPattern\Impostos\Imposto;

interface VendaFactory
{
    public function criarVenda(): Venda;
    public function imposto(): Imposto;
}
```
Classe para fábrica de objetos relacionados a serviços:
```php
<?php

namespace Alura\DesignPattern\Venda;

use Alura\DesignPattern\Impostos\Iss;
use Alura\DesignPattern\Impostos\Imposto;

class VendaServicoFactory implements VendaFactory
{
    private string $nomePrestador;
    
    public function __construct(string $nomePrestador)
    {
        $this->nomePrestador = $nomePrestador;
    }

    public function criarVenda(): Venda {
        return new VendaServico(new \DateTimeImmutable(), $this->nomePrestador);
    }
    
    public function imposto(): Imposto {
        return new Iss();
    }
}
```
Classe para fábrica de objetos relacionados a produtos:
```php
<?php

namespace Alura\DesignPattern\Venda;

use Alura\DesignPattern\Impostos\Icms;
use Alura\DesignPattern\Impostos\Imposto;

class VendaProdutoFactory implements VendaFactory
{
    private int $pesoProduto;
    
    public function __construct(int $pesoProduto)
    {
        $this->pesoProduto = $pesoProduto;
    }

    public function criarVenda(): Venda {
        return new VendaProduto(new \DateTimeImmutable(), $this->pesoProduto);
    }
    
    public function imposto(): Imposto {
        return new Icms();
    }
}
```
Invocação das classes (arquivo `venda.php`):
```php
<?php

use Alura\DesignPattern\Venda\{VendaProdutoFactory, VendaServicoFactory};

require 'vendor/autoload.php';

// $fabricaVenda = new VendaServicoFactory('Thiago');
$fabricaVenda = new VendaProdutoFactory(500);
$venda = $fabricaVenda->criarVenda();
$imposto = $fabricaVenda->imposto();

var_dump($venda, $imposto);
```
## Falando sobre o padrão
O exemplo das duas fábricas implementadas a partir da interface `VendaFactory` foca dois métodos: criar uma venda e um imposto. A classe usuário não precisa saber se a venda/imposto são de produto ou de serviço: só precisa usar o produto da fábrica abstrata para criar uma venda e um imposto de acordo com o seu tipo.

Leitura complementar sobre o padrão Abstract Factory: https://refactoring.guru/design-patterns/abstract-factory

# Expressividade pelo Builder
## Criando a classe de nota fiscal
Implementação inicial da classe `NotaFiscal`:
```php
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
```
Perceba o quanto o construtor está grande e confuso.

## Extraindo o construtor
Removendo o construtor complexo da classe `NotaFiscal`:
```php
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

    public function valor(): float
    {
        return 0;
    }
}
```
Criando a classe `ConstrutorNotaFiscal`. Note que, com exceção do método `__construct` e `constroi`, todos os métodos retornam o próprio objeto da classe `ConstrutorNotaFiscal`. O nome disso é Fluid Interface: cada método do construtor de nota fiscal retorna um novo construtor de nota fiscal:
```php
<?php

namespace Alura\DesignPattern\NotaFiscal;

use Alura\DesignPattern\ItemOrcamento;

class ConstrutorNotaFiscal
{
    private NotaFiscal $notaFiscal;

    public function __construct()
    {
        $this->notaFiscal = new NotaFiscal();
        $this->notaFiscal->dataEmissao = new \DateTimeImmutable();
    }

    public function paraEmpresa(string $cnpj, string $razaoSocial) : ConstrutorNotaFiscal
    {
        $this->notaFiscal->cnpj = $cnpj;
        $this->notaFiscal->razaoSocial = $razaoSocial;
        return $this;
    }

    public function comItem(ItemOrcamento $item) : ConstrutorNotaFiscal
    {
        $this->notaFiscal->itens[] = $item;
        return $this;
    }

    public function comObservacoes(string $observacoes) : ConstrutorNotaFiscal
    {
        $this->notaFiscal->observacoes = $observacoes;
        return $this;
    }

    public function comDataEmissao(\DateTimeInterface $dataEmissao) : ConstrutorNotaFiscal
    {
        $this->notaFiscal->dataEmissao = $dataEmissao;
        return $this;
    }

    public function constroi() : NotaFiscal
    {
        return $this->notaFiscal;
    }
}
```
Invocação do construtor de notas fiscais no arquivo `nota-fiscal.php`:
```php
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
```
> A nota fiscal só é retornada de fato ao invocarmos o método `constroi`. Antes disso, apenas chamamos os métodos para compor a nota fiscal, de maneira mais encadeada e fluida.

## Impostos
Transformando a classe `ConstrutorNotaFiscal` em uma classe abstrata:
```php
<?php

namespace Alura\DesignPattern\NotaFiscal;

use Alura\DesignPattern\ItemOrcamento;

abstract class ConstrutorNotaFiscal
{
    protected NotaFiscal $notaFiscal;

    public function __construct()
    {
        $this->notaFiscal = new NotaFiscal();
        $this->notaFiscal->dataEmissao = new \DateTimeImmutable();
    }

    public function paraEmpresa(string $cnpj, string $razaoSocial) : ConstrutorNotaFiscal
    {
        $this->notaFiscal->cnpj = $cnpj;
        $this->notaFiscal->razaoSocial = $razaoSocial;
        return $this;
    }

    public function comItem(ItemOrcamento $item) : ConstrutorNotaFiscal
    {
        $this->notaFiscal->itens[] = $item;
        return $this;
    }

    public function comObservacoes(string $observacoes) : ConstrutorNotaFiscal
    {
        $this->notaFiscal->observacoes = $observacoes;
        return $this;
    }

    public function comDataEmissao(\DateTimeInterface $dataEmissao) : ConstrutorNotaFiscal
    {
        $this->notaFiscal->dataEmissao = $dataEmissao;
        return $this;
    }

    abstract public function constroi() : NotaFiscal;
}
```
Classe concreta `ConstrutorNotaFiscalServico`, com cálculo de 6% de imposto na nota:
```php
<?php

namespace Alura\DesignPattern\NotaFiscal;

class ConstrutorNotaFiscalServico extends ConstrutorNotaFiscal
{
    public function constroi() : NotaFiscal
    {
        $valorNotaFiscal = $this->notaFiscal->valor();
        $this->notaFiscal->valorImpostos = $valorNotaFiscal * 0.06;
        return $this->notaFiscal;
    }
}
```

Classe concreta `ConstrutorNotaFiscalProduto`, com cálculo de dois por cento de imposto na nota:
```php
<?php

namespace Alura\DesignPattern\NotaFiscal;

class ConstrutorNotaFiscalProduto extends ConstrutorNotaFiscal
{
    public function constroi() : NotaFiscal
    {
        $valorNotaFiscal = $this->notaFiscal->valor();
        $this->notaFiscal->valorImpostos = $valorNotaFiscal * 0.02;
        return $this->notaFiscal;
    }
}
```
Mudança na classe `NotaFiscal` para calcular o valor total da nota baseado na soma de cada item nela:
```php
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
```

Invocação dos builders concretos no arquivo `nota-fiscal.php`:
```php
<?php

use Alura\DesignPattern\ItemOrcamento;
use Alura\DesignPattern\NotaFiscal\{ConstrutorNotaFiscalProduto, ConstrutorNotaFiscalServico};

require 'vendor/autoload.php';

$item1 = new ItemOrcamento();
$item1->valor = 500; // Se não houver inicialização, o cálculo do valor dos impostos falha.
$item2 = new ItemOrcamento();
$item2->valor = 1000; // Se não houver inicialização, o cálculo do valor dos impostos falha.
$item3 = new ItemOrcamento();
$item3->valor = 1500; // Se não houver inicialização, o cálculo do valor dos impostos falha.

// Fluent Interface: cada método do construtor de nota fiscal
// retorna um novo construtor de nota fiscal.
$notaFiscal = (new ConstrutorNotaFiscalProduto())
// $notaFiscal = (new ConstrutorNotaFiscalServico())
    ->paraEmpresa('12345', 'Balão Apagado SA')
    ->comItem($item1)
    ->comItem($item2)
    ->comItem($item3)
    ->comObservacoes('Esta nota fiscal foi construída com um construtor')
    ->constroi();

echo $notaFiscal->valorImpostos;
```

## Falando sobre o padrão
O padrão Builder serve para construir um objeto passo a passo. A classe do builder pode ter métodos abstratos que são redefinidos por cada builder concreto.

Leitura complementar sobre o padrão Builder: 
- https://refactoring.guru/design-patterns/builder
- https://en.wikipedia.org/wiki/Fluent_interface
- https://martinfowler.com/bliki/FluentInterface.html

# Clonando objetos
## Gerando uma cópia
Inclusão do método `clonar()` no código da nota fiscal:
```php
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

    public function clonar() : NotaFiscal
    {
        $cloneNotaFiscal = new NotaFiscal();
        $cloneNotaFiscal->cnpj = $this->cnpj;
        $cloneNotaFiscal->razaoSocial = $this->razaoSocial;
        $cloneNotaFiscal->itens = $this->itens;
        $cloneNotaFiscal->observacoes = $this->observacoes;
        $cloneNotaFiscal->dataEmissao = $this->dataEmissao;
        $cloneNotaFiscal->valorImpostos = $this->valorImpostos;
        return $cloneNotaFiscal;
    }
}
```
Script para testar a clonagem (arquivo `clone.php`):
```php
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

$notaFiscal2 = $notaFiscal->clonar();
$notaFiscal2->itens[] = new ItemOrcamento();

var_dump($notaFiscal2);
```
## Clonando no PHP
A palavra reservada `clone` é usada antes do objeto PHP que se deseja clonar. Com isso, o PHP instancia um novo objeto e copia cada valor primitivo e cada referência (não o valor) de cada propriedade do objeto clonado:
```php
$notaFiscal2 = clone $notaFiscal;
var_dump($notaFiscal->dataEmissao)

// Saída:
/*
object(DateTimeImmutable)#7 (3) {
  ["date"]=>
  string(26) "2023-08-31 19:40:54.632245"
  ["timezone_type"]=>
  int(3)
  ["timezone"]=>
  string(3) "UTC"
}
*/
var_dump($notaFiscal2->dataEmissao)

// Saída (note que o ID é o mesmo, #7 (3)):
/*
object(DateTimeImmutable)#7 (3) {
  ["date"]=>
  string(26) "2023-08-31 19:40:54.632245"
  ["timezone_type"]=>
  int(3)
  ["timezone"]=>
  string(3) "UTC"
}
*/
```
Se for necessário criar uma nova instância da data de emissão, você precisará reescrever o método mágico `__clone()`. Esse método  não pode ser chamado explicitamente: ele é chamado automaticamente após o uso da palavra reservada `clone`. O método mágico `__clone()` permite a exclusão do método `clonar()` que desenvolvemos na aula passada.

Código alterado da classe `NotaFiscal`:
```php
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

    public function __clone()
    {
        $this->dataEmissao = new \DateTimeImmutable();
    }
}
```
> Repare que métodos mágicos sempre se iniciam com dois sublinhados `__`.

Execução do mesmo script (arquivo `clone.php`):
```php
$notaFiscal2 = clone $notaFiscal;
$notaFiscal2->itens[] = new ItemOrcamento();

var_dump($notaFiscal->dataEmissao);
/*
object(DateTimeImmutable)#7 (3) {
  ["date"]=>
  string(26) "2023-08-31 19:47:21.230574"
  ["timezone_type"]=>
  int(3)
  ["timezone"]=>
  string(3) "UTC"
}
*/
var_dump($notaFiscal2->dataEmissao);
/*
object(DateTimeImmutable)#8 (3) {
  ["date"]=>
  string(26) "2023-08-31 19:47:21.230639"
  ["timezone_type"]=>
  int(3)
  ["timezone"]=>
  string(3) "UTC"
}
*/
```
> Note que o id das datas está diferente agora (#7 e #8).

## Falando sobre o padrão
A ideia do Prototype é copiar objetos existentes **sem que a classe usuária precise depender dos métodos de acesso ao objeto**. O próprio objeto deve saber como se copiar. Isso diminui o acoplamento.

Leitura complementar:
- Leitura complementar sobre o padrão Prototype: https://refactoring.guru/design-patterns/prototype
- Documentação do PHP sobre clonagem: https://www.php.net/manual/en/language.oop5.cloning.php

# Usando Singleton
## Se conectando ao banco
Problema: cada interação com o banco de dados exige a comunicação com um objeto da classe PDO. O problema é que acabamos correndo o risco de instanciar muitos objetos dessa classe, o que impacta no uso de memória.

Exemplo de uma classe filha de PDO (`PdoConnection.php`):
```php
<?php

namespace Alura\DesignPattern;

class PdoConnection extends \PDO
{
}
```
Exemplo de código instanciando mais de um objeto PDO (`pdo.php`):
```php
<?php

use Alura\DesignPattern\PdoConnection;

require 'vendor/autoload.php';

$pdo = new PdoConnection('sqlite::memory:');

$pdo2 = new PdoConnection('sqlite::memory:'); // Repetição da instanciação.

var_dump($pdo, $pdo2); // São dois objetos diferentes, com ids diferentes.
```
## Única instância
A solução é deixar o construtor do objeto privado e delegar para uma classe a criação e recuperação da instância única (Singleton).

Adaptação da classe `PdoConnection`:
```php
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
```
Criando e recuperando o Singleton no arquivo `pdo.php`:
```php
<?php

use Alura\DesignPattern\PdoConnection;

require 'vendor/autoload.php';

$pdo = PdoConnection::getInstance('sqlite::memory:');

$pdo2 = PdoConnection::getInstance('sqlite::memory:'); // Obtendo o Singleton, sem recriá-lo.

var_dump($pdo, $pdo2); // Agora as duas referências apontam para o mesmo objeto.
```
## Falando sobre o padrão
Problemas com o Singleton:
1) Testes unitários em singletons são difíceis, por causa dos métodos estáticos.
2) Por causa do construtor privado, a herança de Singletons fica mais difícil.
3) O princípio da responsabilidade única é quebrado: Singletons representam o objeto e fabricam o objeto (duas responsabilidades).

Leitura complementar sobre o padrão Singleton: https://refactoring.guru/design-patterns/singleton
