<?php
declare(strict_types=1);

/* Cliente – NPC que compra os seus itens (Elliott, Harvey, Sebastian, Abigail, Leah, Penny).
   O preço que ele paga depende do que ele sente pelo item. */
class Cliente extends NPC
{
    /** @var Colecao<Sentimento>  id do item => sentimento (só Ama e Odeia; o resto é Neutro) */
    private Colecao $sentimentos;

    public function __construct(string $id, string $nome, ?string $imagem = null)
    {
        parent::__construct($id, $nome, $imagem);
        $this->sentimentos = new Colecao(Sentimento::class);   // coleção genérica: só aceita Sentimento
    }

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
        $this->sentimentos->adicionar($sentimento, $idItem);
    }

    public function sentimentoPor(Item $item): Sentimento
    {
        return $this->sentimentos->obter($item->getId()) ?? Sentimento::NEUTRO;
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
        // ---- moradores que chegaram na versão 4 ----
        'alex' => ['AMA' => 'Isso é demais! Vou mostrar para o pessoal do time.',
                   'NEUTRO' => 'Valeu, beleza.',
                   'ODEIA' => 'Hã... isso não é bem a minha praia.'],
        'caroline' => ['AMA' => 'Que gentileza! Vai ficar lindo na minha estufa.',
                       'NEUTRO' => 'Obrigada, que gentil.',
                       'ODEIA' => 'Ah... acho que vou passar essa adiante.'],
        'demetrius' => ['AMA' => 'Fascinante! Isso vai direto para o meu laboratório.',
                        'NEUTRO' => 'Interessante. Vou anotar.',
                        'ODEIA' => 'Hmm. Os dados dizem que eu não gosto disso.'],
        'anao' => ['AMA' => 'Hm! Coisa boa. Anão gosta.',
                   'NEUTRO' => 'Hm. Aceito.',
                   'ODEIA' => 'Humano estranho. Leva isso embora.'],
        'emily' => ['AMA' => 'Que energia linda! Senti uma aura incrível vindo disso!',
                    'NEUTRO' => 'Obrigada! As vibrações são boas.',
                    'ODEIA' => 'Ai... isso tem uma energia pesada.'],
        'evelyn' => ['AMA' => 'Oh, que amor! Vou contar para o George.',
                     'NEUTRO' => 'Obrigada, meu bem.',
                     'ODEIA' => 'Oh, céus... não é bem do meu gosto, mas obrigada.'],
        'george' => ['AMA' => 'Hmph. ...Tá bom, isso é muito bom. Obrigado.',
                     'NEUTRO' => 'Hmph. Obrigado.',
                     'ODEIA' => 'O que é isso? Leva de volta!'],
        'haley' => ['AMA' => 'Ai, que lindo! Vai ficar perfeito nas minhas fotos!',
                    'NEUTRO' => 'Ah, legal. Obrigada.',
                    'ODEIA' => 'Eca! Tira isso de perto de mim.'],
        'jas' => ['AMA' => 'Uau! É para mim? Que legal!',
                  'NEUTRO' => 'Obrigada...',
                  'ODEIA' => 'Eu não gosto disso...'],
        'jodi' => ['AMA' => 'Que maravilha! Isso vai deixar o jantar especial.',
                   'NEUTRO' => 'Obrigada, isso ajuda lá em casa.',
                   'ODEIA' => 'Hmm, acho que ninguém lá em casa vai querer isso.'],
        'kent' => ['AMA' => '...Obrigado. De verdade, significa muito.',
                   'NEUTRO' => 'Obrigado.',
                   'ODEIA' => 'Isso me traz lembranças ruins. Não, obrigado.'],
        'krobus' => ['AMA' => 'Para mim? Os humanos são mesmo gentis... obrigado, amigo.',
                     'NEUTRO' => 'Obrigado. Vou guardar aqui no esgoto.',
                     'ODEIA' => 'Hmm... as sombras não gostam disso.'],
        'leo' => ['AMA' => 'Que incrível! Os papagaios vão adorar ver isso!',
                  'NEUTRO' => 'Ah, legal! Obrigado!',
                  'ODEIA' => 'Eca... ainda bem que isso não tem na ilha.'],
        'lewis' => ['AMA' => 'Excelente! Isso é ótimo para a vila. Muito obrigado!',
                    'NEUTRO' => 'Obrigado! A vila agradece.',
                    'ODEIA' => 'Ahem... vou fingir que isso não aconteceu.'],
        'linus' => ['AMA' => 'Coisa da natureza... você entende. Obrigado.',
                    'NEUTRO' => 'Obrigado. Vou levar para a minha tenda.',
                    'ODEIA' => 'A natureza não fez isso. Prefiro não aceitar.'],
        'maru' => ['AMA' => 'Que incrível! Posso usar isso num projeto novo!',
                   'NEUTRO' => 'Obrigada! Vou achar uma utilidade.',
                   'ODEIA' => 'Hm... isso não serve para nenhuma experiência minha.'],
        'pam' => ['AMA' => 'Opa, agora sim! Você sabe o que é bom!',
                  'NEUTRO' => 'Valeu. Já é alguma coisa.',
                  'ODEIA' => 'Que é isso? Nem de graça.'],
        'robin' => ['AMA' => 'Que demais! Me deu até vontade de começar um projeto novo.',
                    'NEUTRO' => 'Obrigada! Vou guardar na oficina.',
                    'ODEIA' => 'Hmm... acho que isso não serve nem para lenha.'],
        'sam' => ['AMA' => 'Que irado! Valeu demais!',
                  'NEUTRO' => 'Ah, valeu!',
                  'ODEIA' => 'Ih, isso não é a minha vibe.'],
        'sandy' => ['AMA' => 'Ai, que lindo! Vou colocar na vitrine do Oásis!',
                    'NEUTRO' => 'Obrigada! Volte sempre ao deserto.',
                    'ODEIA' => 'Ugh, isso não combina nada com o deserto.'],
        'shane' => ['AMA' => '...Sério? Isso é bom. Valeu, mesmo.',
                    'NEUTRO' => 'Tá. Valeu.',
                    'ODEIA' => 'Para que eu ia querer isso?'],
        'vincent' => ['AMA' => 'Uau! É a melhor coisa do mundo!',
                      'NEUTRO' => 'Legal! Obrigado!',
                      'ODEIA' => 'Eca! Isso é nojento!'],
        'feiticeiro' => ['AMA' => 'Ah... sinto uma energia arcana poderosa nisto. Muito bem.',
                         'NEUTRO' => 'Hm. Aceitarei sua oferenda.',
                         'ODEIA' => 'Que energia lamentável. Leve isso embora.'],
    ];

    public function reacao(Item $item): string
    {
        $sentimento = $this->sentimentoPor($item);
        return self::FALAS[$this->id][$sentimento->name] ?? $sentimento->fala();
    }
}
