<?php
declare(strict_types=1);

class SaldoInsuficienteException extends JogoException
{
    public function __construct(int $valor, int $saldo)
    {
        parent::__construct("Saldo insuficiente: custa $valor g e você tem $saldo g.");
    }
}
