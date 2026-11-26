<?php
declare(strict_types=1);

/* Ex.: tentar comprar um peixe na loja do Pierre. */
class NegociacaoInvalidaException extends JogoException
{
    public function codigo(): string
    {
        return 'negociacao_invalida';
    }
}
