<?php
declare(strict_types=1);

/* Venda – o jogador vende uma unidade do inventário para um Cliente. */
class Venda extends Transacao
{
    private Sentimento $sentimento;
    private int $valor;

    public function __construct(Jogador $jogador, UnidadeItem $unidade, Cliente $cliente)
    {
        parent::__construct($jogador, $unidade, $cliente);
        $this->sentimento = $cliente->sentimentoPor($unidade->getItem());
        $this->valor = $cliente->oferta($unidade);
    }

    public function getTipo(): string
    {
        return 'Venda';
    }

    /* Na venda, o valor é o que o cliente oferece (depende do sentimento dele). */
    public function getValor(): int
    {
        return $this->valor;
    }

    public function getSentimento(): Sentimento
    {
        return $this->sentimento;
    }

    /* Coloca o dinheiro no saldo do jogador. */
    public function executar(): void
    {
        $this->jogador->creditar($this->valor);
    }
}
