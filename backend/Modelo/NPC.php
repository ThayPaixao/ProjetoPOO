<?php
declare(strict_types=1);

/* NPC – classe base (abstrata) dos personagens do jogo.
   HERANÇA: Vendedor (você compra dele) e Cliente (você vende para ele). */
abstract class NPC
{
    protected string $id;         // 'pierre', 'elliott'... é o id usado nas rotas do front
    protected string $nome;
    protected ?string $imagem;

    public function __construct(string $id, string $nome, ?string $imagem = null)
    {
        $this->id = $id;
        $this->nome = $nome;
        $this->imagem = $imagem;
    }

    /* 'Vendedor' ou 'Cliente' */
    abstract public function getCategoria(): string;

    /* Diz se este NPC aceita negociar o item. Cada tipo de NPC tem a sua regra. */
    abstract public function podeNegociar(Item $item): bool;

    public function getId(): string { return $this->id; }
    public function getNome(): string { return $this->nome; }
    public function getImagem(): ?string { return $this->imagem; }

    /* Formato do front: { id, nome, imagem } */
    public function paraArray(): array
    {
        return ['id' => $this->id, 'nome' => $this->nome, 'imagem' => $this->imagem];
    }
}
