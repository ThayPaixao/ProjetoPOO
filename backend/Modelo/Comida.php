<?php
declare(strict_types=1);

/* Comida – vendido na loja do Gus. Usa as regras padrão de Item. */
class Comida extends Item
{
    public function getTipo(): string
    {
        return 'Comida';
    }
}
