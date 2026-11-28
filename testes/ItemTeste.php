<?php
declare(strict_types=1);

/* Testa os itens: a fábrica (Item::criar), o preço por qualidade e o POLIMORFISMO do Peixe. */
class ItemTeste extends Teste
{
    public function testeFabricaCriaASubclasseCerta(): void
    {
        $tipos = ['Peixe' => Peixe::class, 'Colheita' => Colheita::class, 'Comida' => Comida::class, 'Minério' => Minerio::class,
                  'Coletável' => Coletavel::class, 'Produto Animal' => ProdutoAnimal::class, 'Artesanato' => Artesanato::class];
        foreach ($tipos as $tipo => $classe) {
            $item = Item::criar($tipo, 1, 'Teste', 10);
            $this->verificar($item instanceof $classe, "\"$tipo\" deveria virar $classe, virou " . get_class($item));
            $this->verificarIgual($tipo, $item->getTipo(), 'getTipo()');
        }
    }

    public function testeFabricaRecusaTipoDesconhecido(): void
    {
        $this->verificarErro(InvalidArgumentException::class, fn() => Item::criar('Ferramenta', 1, 'Machado', 10));
    }

    public function testePrecoZeroEhRecusado(): void
    {
        $this->verificarErro(InvalidArgumentException::class, fn() => new Colheita(1, 'Nabo', 0));
    }

    public function testeQualidadeMultiplicaOPreco(): void
    {
        $abobora = new Colheita(1, 'Abóbora', 100);
        $this->verificarIgual(100, $abobora->calcularPreco(Qualidade::NORMAL), 'Normal');
        $this->verificarIgual(125, $abobora->calcularPreco(Qualidade::PRATA), 'Prata');
        $this->verificarIgual(150, $abobora->calcularPreco(Qualidade::OURO), 'Ouro');
        $this->verificarIgual(200, $abobora->calcularPreco(Qualidade::IRIDIO), 'Irídio');
    }

    public function testePeixeSomaDoisDeOuroPorCentimetro(): void
    {
        $atum = new Peixe(1, 'Atum', 100);
        $this->verificarIgual(100, $atum->calcularPreco(Qualidade::NORMAL, 10), '10 cm (tamanho mínimo)');
        $this->verificarIgual(180, $atum->calcularPreco(Qualidade::NORMAL, 50), '50 cm');
        $this->verificarIgual(188, $atum->calcularPreco(Qualidade::PRATA, 35), 'Prata, 35 cm: (100 + 50) × 1,25');
    }

    public function testePeixeForaDoTamanhoEhRecusado(): void
    {
        $atum = new Peixe(1, 'Atum', 100);
        $this->verificarErro(InvalidArgumentException::class, fn() => $atum->calcularPreco(Qualidade::NORMAL, 9));
        $this->verificarErro(InvalidArgumentException::class, fn() => $atum->calcularPreco(Qualidade::NORMAL, 51));
    }

    /* POLIMORFISMO: o mesmo sortearUnidade() faz coisas diferentes no Peixe e na Colheita. */
    public function testeSoOPeixeSorteiaTamanho(): void
    {
        $atum = new Peixe(1, 'Atum', 100);
        $nabo = new Colheita(2, 'Nabo', 35);
        for ($i = 0; $i < 200; $i++) {
            $peixe = $atum->sortearUnidade();
            $tamanho = $peixe->getTamanhoCm();
            $this->verificar($tamanho !== null && $tamanho >= 10 && $tamanho <= 50, "tamanho fora da faixa: $tamanho");
            $this->verificarIgual($atum->calcularPreco($peixe->getQualidade(), $tamanho), $peixe->getPrecoPago(), 'preço do peixe sorteado');
            $this->verificarIgual(null, $nabo->sortearUnidade()->getTamanhoCm(), 'tamanho da colheita');
        }
    }

    /* 4000 sorteios: todas as qualidades aparecem e a Normal fica perto de 50%.
       (A chance de este teste falhar por azar é praticamente zero.) */
    public function testeSorteioDaQualidadeSegueAsChances(): void
    {
        $contagem = [0, 0, 0, 0];
        for ($i = 0; $i < 4000; $i++) {
            $contagem[Qualidade::sortear()->value]++;
        }
        $this->verificar(min($contagem) > 0, 'alguma qualidade nunca saiu: ' . json_encode($contagem));
        $normal = $contagem[0] / 4000;
        $this->verificar($normal > 0.45 && $normal < 0.55, "a Normal saiu em {$normal} dos sorteios (esperava perto de 0,5)");
        $this->verificar($contagem[3] < $contagem[2] && $contagem[2] < $contagem[1], 'quanto melhor a qualidade, mais rara: ' . json_encode($contagem));
    }
}
