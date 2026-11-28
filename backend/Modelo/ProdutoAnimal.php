<?php
declare(strict_types=1);

/* ProdutoAnimal – ovos, leites, lã, trufa... Vendido no rancho da Marnie. Usa as regras padrão de Item. */
class ProdutoAnimal extends Item
{
    public function getTipo(): string
    {
        return 'Produto Animal';
    }
}
