<?php
declare(strict_types=1);

/* Transacao – classe base (abstrata) de Compra e Venda.
   As duas têm jogador, unidade, NPC e data; o que muda é o valor e o efeito no saldo. */
abstract class Transacao
{
    protected ?int $id = null;
    protected Jogador $jogador;
    protected UnidadeItem $unidade;
    protected NPC $npc;
    protected DateTimeImmutable $data;

    public function __construct(Jogador $jogador, UnidadeItem $unidade, NPC $npc)
    {
        $this->jogador = $jogador;
        $this->unidade = $unidade;
        $this->npc = $npc;
        $this->data = new DateTimeImmutable();
    }

    abstract public function getTipo(): string;     // 'Compra' ou 'Venda'

    abstract public function getValor(): int;       // em ouro (g)

    /* Aplica a transação no saldo do jogador: a Compra debita, a Venda credita. */
    abstract public function executar(): void;

    /* Este método é igual para as duas, mas usa getTipo() e getValor(),
       que cada subclasse implementa do seu jeito (POLIMORFISMO). */
    public function descricao(): string
    {
        return sprintf('%s: %s por %d g (%s)',
            $this->getTipo(), $this->unidade->descricao(), $this->getValor(), $this->npc->getNome());
    }

    /* Usado pelo banco depois de salvar. Só pode ser chamado uma vez. */
    public function definirId(int $id): void
    {
        if ($this->id !== null) {
            throw new LogicException('Esta transação já tem id.');
        }
        $this->id = $id;
    }

    public function getId(): ?int { return $this->id; }
    public function getJogador(): Jogador { return $this->jogador; }
    public function getUnidade(): UnidadeItem { return $this->unidade; }
    public function getNpc(): NPC { return $this->npc; }
    public function getData(): DateTimeImmutable { return $this->data; }
}
