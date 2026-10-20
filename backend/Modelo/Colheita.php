<?php
declare(strict_types=1);

/* Colheita – vendido na loja do Pierre. Usa as regras padrão de Item. */
class Colheita extends Item
{
    public function getTipo(): string
    {
        return 'Colheita';
    }
}
