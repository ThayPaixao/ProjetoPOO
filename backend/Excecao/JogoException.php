<?php
declare(strict_types=1);

/* JogoException – base dos erros de regra do jogo (saldo, dados inválidos...).
   São erros "esperados": a API devolve a mensagem para o usuário ver. */
class JogoException extends RuntimeException
{
    /* Código HTTP que a API devolve para este erro.
       POLIMORFISMO: as subclasses trocam (404 não encontrado, 401 sem login). */
    public function statusHttp(): int
    {
        return 400;
    }
}
