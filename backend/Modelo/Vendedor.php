<?php
declare(strict_types=1);

/* Vendedor – NPC dono de loja (Pierre, Gus, Willy, Clint). Você compra dele. */
class Vendedor extends NPC
{
    private string $nomeLoja;
    /** @var Colecao<Item>  id do item => item (coleção genérica: só aceita Item) */
    private Colecao $catalogo;

    public function __construct(string $id, string $nome, string $nomeLoja, ?string $imagem = null)
    {
        parent::__construct($id, $nome, $imagem);
        $this->catalogo = new Colecao(Item::class);
        $this->nomeLoja = $nomeLoja;
    }

    public function getCategoria(): string
    {
        return 'Vendedor';
    }

    /* O vendedor só vende o que está no catálogo da loja dele. */
    public function podeNegociar(Item $item): bool
    {
        return $this->catalogo->contem($item->getId());
    }

    public function adicionarAoCatalogo(Item $item): void
    {
        $this->catalogo->adicionar($item, $item->getId());
    }

    /** @return Item[] */
    public function getCatalogo(): array
    {
        return $this->catalogo->paraLista();
    }

    /* O que cada vendedor diz ao entregar a compra. */
    private const FALAS = [
        'pierre' => 'Ótima escolha! Fresquinho da Loja do Pierre.',
        'gus' => 'Saindo quentinho da cozinha do Saloon! Bom apetite.',
        'willy' => 'Fisgado hoje cedinho! Cuide bem dele, viu?',
        'clint' => 'Hã... aqui está. Brilha bonito, né?',
        'marnie' => 'Fresquinho do rancho! Os bichos mandaram lembranças.',
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
