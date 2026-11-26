<?php
declare(strict_types=1);

/* Peixe – vendido na loja do Willy.
   É o item que mais mostra POLIMORFISMO: sobrescreve sortearUnidade() e
   calcularPreco(), porque cada peixe tem um tamanho que muda o preço. */
class Peixe extends Item
{
    public const TAMANHO_MIN_CM = 10;
    public const TAMANHO_MAX_CM = 50;
    public const OURO_POR_CM = 2;     // cada cm acima do mínimo vale 2 g a mais

    public function getTipo(): string
    {
        return 'Peixe';
    }

    /* Além da qualidade, sorteia o tamanho (de 10 a 50 cm). */
    public function sortearUnidade(): UnidadeItem
    {
        $qualidade = Qualidade::sortear();
        $tamanho = random_int(self::TAMANHO_MIN_CM, self::TAMANHO_MAX_CM);
        return new UnidadeItem($this, $qualidade, $tamanho, $this->calcularPreco($qualidade, $tamanho));
    }

    /* (preço base + 2 g por cm acima de 10) × qualidade.
       O peixe de 10 cm custa o preço base; o de 50 cm custa o base + 80 g. */
    public function calcularPreco(Qualidade $qualidade, ?int $tamanhoCm = null): int
    {
        $tamanho = $tamanhoCm ?? self::TAMANHO_MIN_CM;
        if ($tamanho < self::TAMANHO_MIN_CM || $tamanho > self::TAMANHO_MAX_CM) {
            throw new InvalidArgumentException("Tamanho de peixe inválido: $tamanho cm.");
        }
        $bonusTamanho = ($tamanho - self::TAMANHO_MIN_CM) * self::OURO_POR_CM;
        return (int) round(($this->precoBase + $bonusTamanho) * $qualidade->multiplicador());
    }
}
