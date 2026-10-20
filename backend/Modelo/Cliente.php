<?php
declare(strict_types=1);

/* Cliente – NPC que compra os seus itens (Elliott, Harvey, Sebastian, Abigail, Leah, Penny).
   O preço que ele paga depende do que ele sente pelo item. */
class Cliente extends NPC
{
    /** @var array<int, Sentimento>  id do item => sentimento (só Ama e Odeia; o resto é Neutro) */
    private array $sentimentos = [];

    public function getCategoria(): string
    {
        return 'Cliente';
    }

    /* Os clientes aceitam qualquer item; o que muda é o preço. */
    public function podeNegociar(Item $item): bool
    {
        return true;
    }

    public function definirSentimento(int $idItem, Sentimento $sentimento): void
    {
        $this->sentimentos[$idItem] = $sentimento;
    }

    public function sentimentoPor(Item $item): Sentimento
    {
        return $this->sentimentos[$item->getId()] ?? Sentimento::NEUTRO;
    }

    /* Quanto ele paga: o que o jogador pagou × multiplicador do sentimento. */
    public function oferta(UnidadeItem $unidade): int
    {
        $sentimento = $this->sentimentoPor($unidade->getItem());
        return (int) round($unidade->getPrecoPago() * $sentimento->multiplicador());
    }

    public function reacao(Item $item): string
    {
        return $this->sentimentoPor($item)->fala();
    }
}
