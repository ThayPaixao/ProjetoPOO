<?php
declare(strict_types=1);

/* Minerio – vendido na loja do Clint. Usa as regras padrão de Item. */
class Minerio extends Item
{
    public function getTipo(): string
    {
        return 'Minério';
    }
}
