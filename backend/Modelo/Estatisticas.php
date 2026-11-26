<?php
declare(strict_types=1);

/* Estatisticas – os números do jogador que as conquistas usam (quantas compras, maior peixe...).
   OBJETO IMUTÁVEL: as propriedades são "readonly" (só recebem valor no construtor). Para "mudar",
   comCompra() e comVenda() devolvem um objeto NOVO; o antigo continua igual. É isso que deixa o
   ObservadorDeConquistas comparar o ANTES e o DEPOIS de uma transação. */
final class Estatisticas
{
    /**
     * @param string[] $lojas     ids dos vendedores de quem já comprou (sem repetir)
     * @param string[] $clientes  ids dos moradores para quem já vendeu (sem repetir)
     */
    public function __construct(
        public readonly int $compras = 0,
        public readonly int $comprasIridio = 0,
        public readonly int $maiorPeixeCm = 0,
        public readonly array $lojas = [],
        public readonly int $vendas = 0,
        public readonly int $vendasParaQuemAma = 0,
        public readonly array $clientes = [],
        public readonly int $lucro = 0,
    ) {
    }

    /* As estatísticas depois de mais uma compra na loja $idVendedor. */
    public function comCompra(string $idVendedor, UnidadeItem $unidade): self
    {
        return new self(
            $this->compras + 1,
            $this->comprasIridio + ($unidade->getQualidade() === Qualidade::IRIDIO ? 1 : 0),
            max($this->maiorPeixeCm, $unidade->getTamanhoCm() ?? 0),
            self::semRepetir([...$this->lojas, $idVendedor]),
            $this->vendas, $this->vendasParaQuemAma, $this->clientes, $this->lucro);
    }

    /* As estatísticas depois de mais uma venda para o morador $idCliente. */
    public function comVenda(string $idCliente, Sentimento $sentimento, int $lucro): self
    {
        return new self(
            $this->compras, $this->comprasIridio, $this->maiorPeixeCm, $this->lojas,
            $this->vendas + 1,
            $this->vendasParaQuemAma + ($sentimento === Sentimento::AMA ? 1 : 0),
            self::semRepetir([...$this->clientes, $idCliente]),
            $this->lucro + $lucro);
    }

    /** @param string[] $ids  @return string[] */
    private static function semRepetir(array $ids): array
    {
        return array_values(array_unique($ids));
    }
}
