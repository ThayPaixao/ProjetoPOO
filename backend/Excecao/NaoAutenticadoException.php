<?php
declare(strict_types=1);

/* Sem login, ou e-mail/senha errados. */
class NaoAutenticadoException extends JogoException
{
    public function __construct(string $mensagem = 'Faça login para continuar.')
    {
        parent::__construct($mensagem);
    }

    public function statusHttp(): int
    {
        return 401;
    }

    public function codigo(): string
    {
        return 'nao_autenticado';
    }
}
