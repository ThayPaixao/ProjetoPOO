<?php
declare(strict_types=1);

/* Conquista – as medalhas do jogo. Cada uma tem uma META e diz quanto o jogador já tem
   (progresso). Está conquistada quando o progresso chega na meta.
   As metas de "todas as lojas" e "todos os moradores" vêm do banco (dentro das Estatisticas),
   então continuam certas quando entra uma loja ou um morador novo.
   O valor (texto) é o id que vai para o front. */
enum Conquista: string
{
    case PRIMEIRA_COMPRA    = 'primeira_compra';
    case PRIMEIRA_VENDA     = 'primeira_venda';
    case SORTE_DE_IRIDIO    = 'sorte_de_iridio';
    case PEIXE_DE_RESPEITO  = 'peixe_de_respeito';
    case FREGUES_DA_VILA    = 'fregues_da_vila';
    case ACERTOU_EM_CHEIO   = 'acertou_em_cheio';
    case CONHECE_TODO_MUNDO = 'conhece_todo_mundo';
    case MAO_BOA            = 'mao_boa';

    public function nome(): string
    {
        return match ($this) {
            self::PRIMEIRA_COMPRA    => 'Primeiras compras',
            self::PRIMEIRA_VENDA     => 'Negócio fechado',
            self::SORTE_DE_IRIDIO    => 'Sorte de irídio',
            self::PEIXE_DE_RESPEITO  => 'Peixe de respeito',
            self::FREGUES_DA_VILA    => 'Freguês da vila',
            self::ACERTOU_EM_CHEIO   => 'Acertou em cheio',
            self::CONHECE_TODO_MUNDO => 'Conhece todo mundo',
            self::MAO_BOA            => 'Mão boa para negócios',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::PRIMEIRA_COMPRA    => 'Compre o seu primeiro item.',
            self::PRIMEIRA_VENDA     => 'Venda o seu primeiro item.',
            self::SORTE_DE_IRIDIO    => 'Ganhe um item de qualidade Irídio.',
            self::PEIXE_DE_RESPEITO  => 'Compre um peixe de 45 cm ou mais.',
            self::FREGUES_DA_VILA    => 'Compre em todas as lojas da vila.',
            self::ACERTOU_EM_CHEIO   => 'Venda 5 itens para quem ama o item.',
            self::CONHECE_TODO_MUNDO => 'Venda para todos os moradores.',
            self::MAO_BOA            => 'Some 1.000 g de lucro nas vendas.',
        };
    }

    /* Quanto é preciso para conquistar. Lojas e moradores: o total que existe no banco. */
    public function meta(Estatisticas $e): int
    {
        return match ($this) {
            self::PRIMEIRA_COMPRA, self::PRIMEIRA_VENDA, self::SORTE_DE_IRIDIO => 1,
            self::PEIXE_DE_RESPEITO  => 45,
            self::FREGUES_DA_VILA    => $e->totalLojas,
            self::ACERTOU_EM_CHEIO   => 5,
            self::CONHECE_TODO_MUNDO => $e->totalClientes,
            self::MAO_BOA            => 1000,
        };
    }

    /* Quanto o jogador já tem (nunca passa da meta nem fica negativo, para a barra de progresso). */
    public function progresso(Estatisticas $e): int
    {
        $valor = match ($this) {
            self::PRIMEIRA_COMPRA    => $e->compras,
            self::PRIMEIRA_VENDA     => $e->vendas,
            self::SORTE_DE_IRIDIO    => $e->comprasIridio,
            self::PEIXE_DE_RESPEITO  => $e->maiorPeixeCm,
            self::FREGUES_DA_VILA    => count($e->lojas),
            self::ACERTOU_EM_CHEIO   => $e->vendasParaQuemAma,
            self::CONHECE_TODO_MUNDO => count($e->clientes),
            self::MAO_BOA            => $e->lucro,
        };
        return max(0, min($valor, $this->meta($e)));
    }

    /* Meta 0 = o total não é conhecido (ex.: Estatisticas criadas num teste): aí não dá para conquistar. */
    public function alcancada(Estatisticas $e): bool
    {
        $meta = $this->meta($e);
        return $meta > 0 && $this->progresso($e) >= $meta;
    }

    /* Formato do front: { id, nome, descricao, progresso, meta, conquistada } */
    public function paraArray(Estatisticas $e): array
    {
        return [
            'id'          => $this->value,
            'nome'        => $this->nome(),
            'descricao'   => $this->descricao(),
            'progresso'   => $this->progresso($e),
            'meta'        => $this->meta($e),
            'conquistada' => $this->alcancada($e),
        ];
    }
}
