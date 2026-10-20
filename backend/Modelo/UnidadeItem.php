<?php
declare(strict_types=1);

/* UnidadeItem – uma unidade concreta que o jogador tem no inventário.
   Guarda o que foi sorteado na compra (qualidade e tamanho) e quanto custou,
   porque o preço que os NPCs pagam depende do valor pago. */
class UnidadeItem
{
    private ?int $id;             // id da compra no banco (null enquanto não foi salva)
    private Item $item;
    private Qualidade $qualidade;
    private ?int $tamanhoCm;      // só peixe
    private int $precoPago;

    public function __construct(Item $item, Qualidade $qualidade, ?int $tamanhoCm, int $precoPago, ?int $id = null)
    {
        $this->item = $item;
        $this->qualidade = $qualidade;
        $this->tamanhoCm = $tamanhoCm;
        $this->precoPago = $precoPago;
        $this->id = $id;
    }

    /* Usado pelo banco depois de salvar a compra. Só pode ser chamado uma vez. */
    public function definirId(int $id): void
    {
        if ($this->id !== null) {
            throw new LogicException('Esta unidade já tem id.');
        }
        $this->id = $id;
    }

    public function getId(): ?int { return $this->id; }
    public function getItem(): Item { return $this->item; }
    public function getQualidade(): Qualidade { return $this->qualidade; }
    public function getTamanhoCm(): ?int { return $this->tamanhoCm; }
    public function getPrecoPago(): int { return $this->precoPago; }

    /* Ex.: "Atum (Ouro, 34 cm)" ou "Abóbora (Prata)" */
    public function descricao(): string
    {
        $detalhes = $this->qualidade->nome();
        if ($this->tamanhoCm !== null) {
            $detalhes .= ", {$this->tamanhoCm} cm";
        }
        return "{$this->item->getNome()} ($detalhes)";
    }

    /* Mesmo formato do Item, mas com o preço pago e a qualidade desta unidade.
       O "id" é o da unidade (compra): é ele que o front manda de volta para vender. */
    public function paraArray(): array
    {
        return [
            'id'         => $this->id,
            'nome'       => $this->item->getNome(),
            'preco'      => $this->precoPago,
            'tipo'       => $this->item->getTipo(),
            'qualidade'  => $this->qualidade->value,
            'imagem'     => $this->item->getImagem(),
            'tamanho_cm' => $this->tamanhoCm,
        ];
    }
}
