<?php
declare(strict_types=1);

/* Cliente – NPC que compra os seus itens (Elliott, Harvey, Sebastian, Abigail, Leah, Penny).
   O preço que ele paga depende do que ele sente pelo item. */
class Cliente extends NPC
{
    /** @var array<int, Sentimento>  id do item => sentimento (só Ama e Odeia; o resto é Neutro) */
    private array $sentimentos = [];

    public function getCategoria(): string
    {
        return 'Cliente';
    }

    /* Os clientes aceitam qualquer item; o que muda é o preço. */
    public function podeNegociar(Item $item): bool
    {
        return true;
    }

    public function definirSentimento(int $idItem, Sentimento $sentimento): void
    {
        $this->sentimentos[$idItem] = $sentimento;
    }

    public function sentimentoPor(Item $item): Sentimento
    {
        return $this->sentimentos[$item->getId()] ?? Sentimento::NEUTRO;
    }

    /* Quanto ele paga: o que o jogador pagou × multiplicador do sentimento. */
    public function oferta(UnidadeItem $unidade): int
    {
        $sentimento = $this->sentimentoPor($unidade->getItem());
        return (int) round($unidade->getPrecoPago() * $sentimento->multiplicador());
    }

    /* O que cada cliente diz ao receber o item, do jeito dele. */
    private const FALAS = [
        'elliott' => ['AMA' => 'Que maravilha! Isso vai inspirar o próximo capítulo do meu romance.',
                      'NEUTRO' => 'Hm, obrigado. Vou deixar perto da minha escrivaninha.',
                      'ODEIA' => 'Ah... não combina muito com a minha cabana, mas agradeço.'],
        'harvey' => ['AMA' => 'Que presente ótimo! Como seu médico, aprovo totalmente.',
                     'NEUTRO' => 'Obrigado. Sempre bom lembrar de se cuidar.',
                     'ODEIA' => 'Hmm... isso não faz muito bem para a saúde, sabia?'],
        'sebastian' => ['AMA' => '...Sério? Isso é muito legal. Valeu mesmo.',
                        'NEUTRO' => 'Ah. Valeu, eu acho.',
                        'ODEIA' => 'Eca. Por que você me daria isso?'],
        'abigail' => ['AMA' => 'Uau! Como você sabia? Perfeito para a minha próxima aventura!',
                      'NEUTRO' => 'Ah, legal. Obrigada!',
                      'ODEIA' => 'Argh, que nojo! Nem pensar.'],
        'leah' => ['AMA' => 'Que lindo! Me deu vontade de pintar agora mesmo.',
                   'NEUTRO' => 'Obrigada. Vou achar um uso para isso.',
                   'ODEIA' => 'Hm... prefiro coisas mais naturais, sabe?'],
        'penny' => ['AMA' => 'Ah, que gentileza! As crianças vão adorar ver isso.',
                    'NEUTRO' => 'Obrigada, é muito gentil da sua parte.',
                    'ODEIA' => 'Oh... desculpa, mas isso não é para mim.'],
    ];

    public function reacao(Item $item): string
    {
        $sentimento = $this->sentimentoPor($item);
        return self::FALAS[$this->id][$sentimento->name] ?? $sentimento->fala();
    }
}
