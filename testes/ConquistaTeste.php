<?php
declare(strict_types=1);

/* Testa as conquistas: as Estatisticas (objeto imutável), o progresso de cada Conquista
   e o ObservadorDeConquistas, que descobre quais conquistas uma transação liberou. */
class ConquistaTeste extends Teste
{
    private Jogador $jogador;
    private Vendedor $willy;
    private Cliente $elliott;
    private Peixe $atum;

    protected function preparar(): void
    {
        $this->jogador = new Jogador(1, 'Ana', 'ana@exemplo.com', 'x', 1000);
        $this->atum = new Peixe(1, 'Atum', 100);
        $this->willy = new Vendedor('willy', 'Willy', 'Peixaria');
        $this->elliott = new Cliente('elliott', 'Elliott');
        $this->elliott->definirSentimento($this->atum->getId(), Sentimento::AMA);
    }

    /* Um observador que recebe sempre as mesmas estatísticas "de antes" (no site, elas vêm do banco). */
    private function observadorCom(Estatisticas $antes): ObservadorDeConquistas
    {
        return new ObservadorDeConquistas(fn(int $idJogador) => $antes);
    }

    public function testeEstatisticasNaoMudamOObjetoAntigo(): void
    {
        $antes = new Estatisticas();
        $depois = $antes->comCompra('willy', new UnidadeItem($this->atum, Qualidade::IRIDIO, 47, 260, 1));
        $this->verificarIgual(0, $antes->compras, 'compras no objeto antigo');
        $this->verificarIgual(1, $depois->compras, 'compras no objeto novo');
        $this->verificarIgual(1, $depois->comprasIridio, 'compras de irídio');
        $this->verificarIgual(47, $depois->maiorPeixeCm, 'maior peixe');
    }

    public function testeLojasEClientesNaoSeRepetem(): void
    {
        $unidade = new UnidadeItem($this->atum, Qualidade::NORMAL, 10, 100, 1);
        $e = (new Estatisticas())->comCompra('willy', $unidade)->comCompra('willy', $unidade)
            ->comVenda('leah', Sentimento::NEUTRO, 0)->comVenda('leah', Sentimento::AMA, 50);
        $this->verificarIgual(['willy'], $e->lojas, 'lojas');
        $this->verificarIgual(['leah'], $e->clientes, 'clientes');
        $this->verificarIgual(1, $e->vendasParaQuemAma, 'vendas para quem ama');
        $this->verificarIgual(50, $e->lucro, 'lucro');
    }

    public function testeProgressoNuncaPassaDaMetaNemFicaNegativo(): void
    {
        $this->verificarIgual(1000, Conquista::MAO_BOA->progresso(new Estatisticas(lucro: 5000)), 'lucro acima da meta');
        $this->verificarIgual(0, Conquista::MAO_BOA->progresso(new Estatisticas(lucro: -80)), 'lucro negativo');
    }

    public function testeCadaConquistaTemNomeDescricaoEMetaPositiva(): void
    {
        foreach (Conquista::cases() as $conquista) {
            $this->verificar($conquista->nome() !== '' && $conquista->descricao() !== '', "$conquista->value sem texto");
            $this->verificar($conquista->meta() > 0, "$conquista->value com meta inválida");
            $this->verificar(!$conquista->alcancada(new Estatisticas()), "$conquista->value não pode vir conquistada no começo");
        }
    }

    public function testePrimeiraCompraLiberaAConquista(): void
    {
        $observador = $this->observadorCom(new Estatisticas());
        $observador->notificar(new Compra($this->jogador, new UnidadeItem($this->atum, Qualidade::NORMAL, 20, 120, 1), $this->willy));
        $this->verificarIgual([Conquista::PRIMEIRA_COMPRA], $observador->novas(), 'conquistas novas');
    }

    public function testeUmaCompraPodeLiberarVariasConquistas(): void
    {
        $observador = $this->observadorCom(new Estatisticas());
        $observador->notificar(new Compra($this->jogador, new UnidadeItem($this->atum, Qualidade::IRIDIO, 50, 360, 1), $this->willy));
        $this->verificarIgual([Conquista::PRIMEIRA_COMPRA, Conquista::SORTE_DE_IRIDIO, Conquista::PEIXE_DE_RESPEITO],
            $observador->novas(), 'conquistas novas');
    }

    public function testeConquistaQueJaTinhaNaoApareceDeNovo(): void
    {
        $observador = $this->observadorCom(new Estatisticas(compras: 3, comprasIridio: 1));
        $observador->notificar(new Compra($this->jogador, new UnidadeItem($this->atum, Qualidade::IRIDIO, 10, 200, 1), $this->willy));
        $this->verificarIgual([], $observador->novas(), 'conquistas novas');
    }

    public function testeVendaParaOSextoMoradorLiberaConhecerTodoMundo(): void
    {
        $antes = new Estatisticas(compras: 6, vendas: 5, clientes: ['abigail', 'harvey', 'leah', 'penny', 'sebastian']);
        $observador = $this->observadorCom($antes);
        $observador->notificar(new Venda($this->jogador, new UnidadeItem($this->atum, Qualidade::NORMAL, 10, 100, 1), $this->elliott));
        $this->verificar(in_array(Conquista::CONHECE_TODO_MUNDO, $observador->novas(), true),
            'vender para o 6º morador deveria liberar "Conhece todo mundo"');
    }
}
