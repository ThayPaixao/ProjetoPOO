<?php
declare(strict_types=1);

/* Compra – o jogador compra uma unidade de um Vendedor. */
class Compra extends Transacao
{
    public function __construct(Jogador $jogador, UnidadeItem $unidade, Vendedor $vendedor)
    {
        parent::__construct($jogador, $unidade, $vendedor);
    }

    public function getTipo(): string
    {
        return 'Compra';
    }

    /* Na compra, o valor é o preço sorteado da unidade. */
    public function getValor(): int
    {
        return $this->unidade->getPrecoPago();
    }

    /* Tira o dinheiro do jogador (lança SaldoInsuficienteException se não tiver). */
    public function executar(): void
    {
        $this->jogador->debitar($this->getValor());
    }
}
