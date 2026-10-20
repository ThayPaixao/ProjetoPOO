<?php
declare(strict_types=1);

/* JogoException – base dos erros de regra do jogo (saldo, dados inválidos...).
   São erros "esperados": a API devolve a mensagem para o usuário ver. */
class JogoException extends RuntimeException
{
}
