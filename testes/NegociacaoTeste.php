<?php
declare(strict_types=1);

/* Testa a compra e a venda: Vendedor, Cliente, Compra, Venda e o POLIMORFISMO de Transacao. */
class NegociacaoTeste extends Teste
{
    private Jogador $jogador;
    private Vendedor $willy;
    private Cliente $elliott;
    private Peixe $atum;
    private Colheita $abobora;
    private Minerio $jade;

    protected function preparar(): void
    {
        $this->jogador = new Jogador(1, 'Ana', 'ana@exemplo.com', 'x', 1000);
        $this->atum = new Peixe(1, 'Atum', 100);
        $this->abobora = new Colheita(2, 'Abóbora', 320);
        $this->jade = new Minerio(3, 'Jade', 200);

        $this->willy = new Vendedor('willy', 'Willy', 'Peixaria');
        $this->willy->adicionarAoCatalogo($this->atum);

        $this->elliott = new Cliente('elliott', 'Elliott');
        $this->elliott->definirSentimento($this->atum->getId(), Sentimento::AMA);
        $this->elliott->definirSentimento($this->jade->getId(), Sentimento::ODEIA);
    }

    /* Uma unidade com preço conhecido (sem sorteio), para as contas do teste serem exatas. */
    private function unidade(Item $item, int $precoPago): UnidadeItem
    {
        return new UnidadeItem($item, Qualidade::NORMAL, $item instanceof Peixe ? 10 : null, $precoPago, 1);
    }

    public function testeClienteQueAmaPaga50PorCentoAMais(): void
    {
        $this->verificarIgual(150, $this->elliott->oferta($this->unidade($this->atum, 100)), 'oferta');
        $this->verificarIgual(Sentimento::AMA, $this->elliott->sentimentoPor($this->atum), 'sentimento');
    }

    public function testeClienteNeutroPagaOMesmo(): void
    {
        $this->verificarIgual(320, $this->elliott->oferta($this->unidade($this->abobora, 320)), 'oferta');
        $this->verificarIgual(Sentimento::NEUTRO, $this->elliott->sentimentoPor($this->abobora), 'sem gosto cadastrado = neutro');
    }

    public function testeClienteQueOdeiaPaga25PorCentoAMenos(): void
    {
        $this->verificarIgual(150, $this->elliott->oferta($this->unidade($this->jade, 200)), 'oferta');
    }

    public function testeVendedorSoVendeOQueTemNoCatalogo(): void
    {
        $this->verificar($this->willy->podeNegociar($this->atum), 'o Willy vende atum');
        $erro = $this->verificarErro(NegociacaoInvalidaException::class, fn() => $this->willy->vender($this->abobora));
        $this->verificarIgual('Willy não vende Abóbora.', $erro->getMessage(), 'mensagem');
    }

    public function testeCompraDebitaOPrecoPago(): void
    {
        $compra = new Compra($this->jogador, $this->unidade($this->atum, 188), $this->willy);
        $compra->executar();
        $this->verificarIgual(812, $this->jogador->getSaldo(), 'saldo depois da compra');
    }

    public function testeCompraSemDinheiroNaoMexeNoSaldo(): void
    {
        $compra = new Compra($this->jogador, $this->unidade($this->atum, 5000), $this->willy);
        $this->verificarErro(SaldoInsuficienteException::class, fn() => $compra->executar());
        $this->verificarIgual(1000, $this->jogador->getSaldo(), 'saldo');
    }

    public function testeVendaCreditaOQueOClienteOferece(): void
    {
        $venda = new Venda($this->jogador, $this->unidade($this->atum, 188), $this->elliott);
        $venda->executar();
        $this->verificarIgual(282, $venda->getValor(), 'valor da venda (188 × 1,5)');
        $this->verificarIgual(1282, $this->jogador->getSaldo(), 'saldo depois da venda');
    }

    /* POLIMORFISMO: descricao() é a mesma na classe base, mas usa getTipo() e getValor() de cada filha. */
    public function testeCadaTransacaoSeDescreveDoSeuJeito(): void
    {
        $unidade = new UnidadeItem($this->atum, Qualidade::PRATA, 35, 188, 1);
        /** @var Transacao[] $transacoes */
        $transacoes = [new Compra($this->jogador, $unidade, $this->willy), new Venda($this->jogador, $unidade, $this->elliott)];
        $this->verificarIgual(
            ['Compra: Atum (Prata, 35 cm) por 188 g (Willy)', 'Venda: Atum (Prata, 35 cm) por 282 g (Elliott)'],
            array_map(fn(Transacao $t) => $t->descricao(), $transacoes),
            'descrições');
    }
}
