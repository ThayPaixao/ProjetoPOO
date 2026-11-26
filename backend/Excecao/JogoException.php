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

    /* Código fixo que o front usa para decidir o que mostrar (ex.: "saldo_insuficiente").
       Assim o JavaScript não depende do texto da mensagem, que pode mudar.
       POLIMORFISMO: cada subclasse devolve o seu. */
    public function codigo(): string
    {
        return 'erro_do_jogo';
    }
}
