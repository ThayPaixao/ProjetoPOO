<?php
declare(strict_types=1);

/* Testa o PADRÃO OBSERVER: o QuadroDeAvisos entrega cada transação publicada a todos os
   inscritos. Os observadores aqui são "espiões": classes anônimas que só anotam o que receberam. */
class QuadroDeAvisosTeste extends Teste
{
    private function espiao(): ObservadorDeTransacao
    {
        return new class implements ObservadorDeTransacao {
            /** @var Transacao[] */
            public array $recebidas = [];

            public function notificar(Transacao $transacao): void
            {
                $this->recebidas[] = $transacao;
            }
        };
    }

    private function compra(): Compra
    {
        $jogador = new Jogador(1, 'Ana', 'ana@exemplo.com', 'x', 1000);
        $unidade = new UnidadeItem(new Colheita(2, 'Abóbora', 320), Qualidade::NORMAL, null, 320, 1);
        return new Compra($jogador, $unidade, new Vendedor('pierre', 'Pierre', 'Armazém'));
    }

    public function testeTodosOsInscritosRecebemOAviso(): void
    {
        $quadro = new QuadroDeAvisos();
        $primeiro = $this->espiao();
        $segundo = $this->espiao();
        $quadro->inscrever($primeiro);
        $quadro->inscrever($segundo);
        $compra = $this->compra();
        $quadro->publicar($compra);
        $this->verificarIgual([$compra], $primeiro->recebidas, 'o que o 1º observador recebeu');
        $this->verificarIgual([$compra], $segundo->recebidas, 'o que o 2º observador recebeu');
    }

    public function testeQuemNaoSeInscreveuNaoRecebe(): void
    {
        $quadro = new QuadroDeAvisos();
        $fora = $this->espiao();
        $quadro->publicar($this->compra());
        $this->verificarIgual([], $fora->recebidas, 'o que o observador de fora recebeu');
    }

    public function testeQuadroSemInscritosNaoDaErro(): void
    {
        (new QuadroDeAvisos())->publicar($this->compra());
        $this->verificar(true, '');
    }
}
