<?php
declare(strict_types=1);

/* Qualidade (estrelas) de uma unidade de item.
   O número (0 a 3) é o mesmo que o front usa no campo "qualidade". */
enum Qualidade: int
{
    case NORMAL = 0;
    case PRATA  = 1;
    case OURO   = 2;
    case IRIDIO = 3;

    /* Quanto a qualidade multiplica o preço (valores do jogo). */
    public function multiplicador(): float
    {
        return match ($this) {
            self::NORMAL => 1.0,
            self::PRATA  => 1.25,
            self::OURO   => 1.5,
            self::IRIDIO => 2.0,
        };
    }

    public function nome(): string
    {
        return match ($this) {
            self::NORMAL => 'Normal',
            self::PRATA  => 'Prata',
            self::OURO   => 'Ouro',
            self::IRIDIO => 'Irídio',
        };
    }

    /* Sorteio na compra: quanto melhor a qualidade, mais rara ela é. */
    public static function sortear(): self
    {
        $n = random_int(1, 100);
        return match (true) {
            $n <= 50 => self::NORMAL,   // 50%
            $n <= 80 => self::PRATA,    // 30%
            $n <= 95 => self::OURO,     // 15%
            default  => self::IRIDIO,   //  5%
        };
    }
}
