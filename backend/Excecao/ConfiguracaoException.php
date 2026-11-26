<?php
declare(strict_types=1);

/* Problema na configuração do servidor (ex.: falta o arquivo .env).
   Não é erro do usuário, mas a mensagem é segura para mostrar. */
class ConfiguracaoException extends RuntimeException
{
}
