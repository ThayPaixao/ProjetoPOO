<?php
declare(strict_types=1);

/* O arquivo de save existe, mas não dá para usar (JSON quebrado, campo faltando, versão desconhecida...).
   Não chega ao usuário: o SaveRepositorio captura, registra no log e segue sem o save. */
class SaveCorrompidoException extends RuntimeException
{
}
