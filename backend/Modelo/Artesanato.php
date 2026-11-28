<?php
declare(strict_types=1);

/* Artesanato – o que sai das máquinas: queijo, vinho, geleia, mel, tecido...
   Um item de cada tipo (um "Vinho", e não um vinho para cada fruta).
   Vendido na Marnie (laticínios e maionese), no Gus (bebidas), no Pierre e no Willy. */
class Artesanato extends Item
{
    public function getTipo(): string
    {
        return 'Artesanato';
    }
}
