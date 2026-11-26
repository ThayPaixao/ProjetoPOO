<?php
declare(strict_types=1);

/* Colecao – uma coleção GENÉRICA: guarda só objetos de UM tipo, escolhido na hora de criar.
   TIPOS GENÉRICOS: o PHP não tem genéricos como o Java (List<Item>). Então a classe recebe o
   tipo como parâmetro e confere cada elemento que entra. As anotações @template são o jeito
   padrão de escrever genéricos em PHP (ferramentas como PHPStan e o VS Code entendem).
   Ex.:  $itens = new Colecao(Item::class);    // ≈ List<Item> no Java
         $itens->adicionar($atum);              // ok: Peixe é um Item
         $itens->adicionar($jogador);           // erro: Jogador não é Item
   Usada no catálogo do Vendedor (Colecao<Item>), nos gostos do Cliente (Colecao<Sentimento>)
   e no inventário (Colecao<UnidadeItem>).

   @template T of object */
class Colecao implements IteratorAggregate, Countable, JsonSerializable
{
    /** @var class-string<T> */
    private string $tipo;

    /** @var array<int|string, T> */
    private array $elementos = [];

    /**
     * @param class-string<T> $tipo
     * @param iterable<T> $elementos
     */
    public function __construct(string $tipo, iterable $elementos = [])
    {
        if (!class_exists($tipo) && !interface_exists($tipo) && !enum_exists($tipo)) {
            throw new InvalidArgumentException("Tipo desconhecido: $tipo");
        }
        $this->tipo = $tipo;
        foreach ($elementos as $elemento) {
            $this->adicionar($elemento);
        }
    }

    /**
     * Coloca um elemento (opcionalmente com uma chave, ex.: o id). Só aceita o tipo da coleção.
     * @param T $elemento
     */
    public function adicionar(object $elemento, int|string|null $chave = null): void
    {
        if (!is_a($elemento, $this->tipo)) {
            throw new InvalidArgumentException(sprintf(
                'Esta coleção só aceita %s, mas recebeu %s.', $this->tipo, get_class($elemento)));
        }
        if ($chave === null) {
            $this->elementos[] = $elemento;
        } else {
            $this->elementos[$chave] = $elemento;
        }
    }

    /** @return T|null */
    public function obter(int|string $chave): ?object
    {
        return $this->elementos[$chave] ?? null;
    }

    public function contem(int|string $chave): bool
    {
        return isset($this->elementos[$chave]);
    }

    /**
     * Uma nova coleção, do MESMO tipo, só com os elementos que passam na condição.
     * @param callable(T): bool $condicao
     * @return Colecao<T>
     */
    public function filtrar(callable $condicao): self
    {
        return new self($this->tipo, array_filter($this->elementos, $condicao));
    }

    /**
     * Transforma cada elemento em outra coisa.
     * @template R
     * @param callable(T): R $transformar
     * @return list<R>
     */
    public function mapear(callable $transformar): array
    {
        return array_values(array_map($transformar, $this->elementos));
    }

    /**
     * @param callable(T): bool $condicao
     * @return T|null
     */
    public function encontrar(callable $condicao): ?object
    {
        foreach ($this->elementos as $elemento) {
            if ($condicao($elemento)) {
                return $elemento;
            }
        }
        return null;
    }

    /** @return class-string<T> */
    public function getTipo(): string
    {
        return $this->tipo;
    }

    /** @return list<T> */
    public function paraLista(): array
    {
        return array_values($this->elementos);
    }

    public function count(): int
    {
        return count($this->elementos);
    }

    /** @return ArrayIterator<int|string, T>  (permite usar a coleção num foreach) */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->elementos);
    }

    /* SERIALIZAÇÃO: vira uma lista JSON com cada elemento serializado. */
    public function jsonSerialize(): array
    {
        return $this->paraLista();
    }
}
