<?php
declare(strict_types=1);

/* Vendedor – NPC dono de loja (Pierre, Gus, Willy, Clint). Você compra dele. */
class Vendedor extends NPC
{
    private string $nomeLoja;
    /** @var array<int, Item>  id do item => item */
    private array $catalogo = [];

    public function __construct(string $id, string $nome, string $nomeLoja, ?string $imagem = null)
    {
        parent::__construct($id, $nome, $imagem);
        $this->nomeLoja = $nomeLoja;
    }

    public function getCategoria(): string
    {
        return 'Vendedor';
    }

    /* O vendedor só vende o que está no catálogo da loja dele. */
    public function podeNegociar(Item $item): bool
    {
        return isset($this->catalogo[$item->getId()]);
    }

    public function adicionarAoCatalogo(Item $item): void
    {
        $this->catalogo[$item->getId()] = $item;
    }

    /** @return Item[] */
    public function getCatalogo(): array
    {
        return array_values($this->catalogo);
    }

    /* O que cada vendedor diz ao entregar a compra. */
    private const FALAS = [
        'pierre' => 'Ótima escolha! Fresquinho da Loja do Pierre.',
        'gus' => 'Saindo quentinho da cozinha do Saloon! Bom apetite.',
        'willy' => 'Fisgado hoje cedinho! Cuide bem dele, viu?',
        'clint' => 'Hã... aqui está. Brilha bonito, né?',
    ];

    public function fala(): string
    {
        return self::FALAS[$this->id] ?? 'Obrigado pela compra!';
    }

    public function getNomeLoja(): string
    {
        return $this->nomeLoja;
    }

    /* Entrega uma unidade nova do item. O que é sorteado depende do tipo do item
       (POLIMORFISMO: o Peixe também sorteia o tamanho). */
    public function vender(Item $item): UnidadeItem
    {
        if (!$this->podeNegociar($item)) {
            throw new NegociacaoInvalidaException("{$this->nome} não vende {$item->getNome()}.");
        }
        return $item->sortearUnidade();
    }
}
