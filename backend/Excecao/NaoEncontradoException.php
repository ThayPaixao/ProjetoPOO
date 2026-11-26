<?php
declare(strict_types=1);

/* Ex.: loja que não existe ou item que não está no inventário. */
class NaoEncontradoException extends JogoException
{
    public function statusHttp(): int
    {
        return 404;
    }
}
