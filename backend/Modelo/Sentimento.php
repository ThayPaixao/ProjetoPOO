<?php
declare(strict_types=1);

/* O que um NPC cliente sente por um item. Define quanto ele paga.
   O número é o mesmo id da tabela "sentimento" do banco. */
enum Sentimento: int
{
    case AMA    = 1;
    case NEUTRO = 2;
    case ODEIA  = 3;

    /* Ama: paga 50% a mais | Neutro: paga o mesmo | Odeia: paga 25% a menos */
    public function multiplicador(): float
    {
        return match ($this) {
            self::AMA    => 1.5,
            self::NEUTRO => 1.0,
            self::ODEIA  => 0.75,
        };
    }

    public function nome(): string
    {
        return match ($this) {
            self::AMA    => 'Ama',
            self::NEUTRO => 'Neutro',
            self::ODEIA  => 'Odeia',
        };
    }

    /* O que o NPC fala quando recebe o item. */
    public function fala(): string
    {
        return match ($this) {
            self::AMA    => 'Uau, eu amo isso! Muito obrigado!',
            self::NEUTRO => 'Obrigado.',
            self::ODEIA  => 'Hã... não gosto muito disso.',
        };
    }
}
