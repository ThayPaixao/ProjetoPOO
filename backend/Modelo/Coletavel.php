<?php
declare(strict_types=1);

/* Coletavel – o que se acha no chão (flores silvestres, cogumelos, coco, gengibre, conchas...).
   Vendido no Pierre (os da terra) e no Willy (os da praia). Usa as regras padrão de Item. */
class Coletavel extends Item
{
    public function getTipo(): string
    {
        return 'Coletável';
    }
}
