<?php
declare(strict_types=1);

/* Testa a SERIALIZAÇÃO: objeto → JSON → objeto de novo, sem perder nada. */
class SerializacaoTeste extends Teste
{
    /* Faz o mesmo caminho do save: json_encode (usa jsonSerialize) e json_decode + deArray. */
    private function idaEVolta(UnidadeItem $unidade): UnidadeItem
    {
        $json = json_encode($unidade, JSON_THROW_ON_ERROR);
        return UnidadeItem::deArray(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
    }

    public function testePeixeVoltaIgualEContinuaPeixe(): void
    {
        $original = new UnidadeItem(new Peixe(7, 'Atum', 100, 'assets/img/itens/atum.png'), Qualidade::OURO, 42, 213, 15);
        $copia = $this->idaEVolta($original);
        $this->verificar($copia->getItem() instanceof Peixe, 'voltou como ' . get_class($copia->getItem()));
        $this->verificarIgual($original->paraArray(), $copia->paraArray(), 'dados da unidade');
        $this->verificarIgual(Qualidade::OURO, $copia->getQualidade(), 'qualidade (enum)');
    }

    public function testeItemSemTamanhoESemIdVoltaIgual(): void
    {
        $original = new UnidadeItem(new Comida(3, 'Biscoito', 140), Qualidade::NORMAL, null, 140);
        $copia = $this->idaEVolta($original);
        $this->verificarIgual(null, $copia->getTamanhoCm(), 'tamanho');
        $this->verificarIgual(null, $copia->getId(), 'id');
        $this->verificar($copia->getItem() instanceof Comida, 'voltou como ' . get_class($copia->getItem()));
    }

    public function testeUnidadeIncompletaDaSaveCorrompido(): void
    {
        $this->verificarErro(SaveCorrompidoException::class, fn() => UnidadeItem::deArray([]));
        $this->verificarErro(SaveCorrompidoException::class, fn() => UnidadeItem::deArray(['item' => ['tipo' => 'Peixe']]));
        $this->verificarErro(SaveCorrompidoException::class, fn() => UnidadeItem::deArray(
            ['item' => ['tipo' => 'Peixe', 'id' => '7', 'nome' => 'Atum', 'preco' => 100], 'qualidade' => 0, 'preco_pago' => 100]));
    }

    public function testeQualidadeQueNaoExisteDaErro(): void
    {
        $this->verificarErro(ValueError::class, fn() => UnidadeItem::deArray(
            ['item' => ['tipo' => 'Peixe', 'id' => 7, 'nome' => 'Atum', 'preco' => 100], 'qualidade' => 9, 'preco_pago' => 100]));
    }

    public function testeTransacaoVaiParaOJsonComOsDadosDaFilha(): void
    {
        $jogador = new Jogador(1, 'Ana', 'ana@exemplo.com', 'x', 1000);
        $unidade = new UnidadeItem(new Peixe(7, 'Atum', 100), Qualidade::NORMAL, 10, 100, 1);
        $venda = new Venda($jogador, $unidade, new Cliente('leah', 'Leah'));
        $json = json_decode(json_encode($venda), true);
        $this->verificarIgual('Venda', $json['tipo'], 'tipo');
        $this->verificarIgual(100, $json['valor'], 'valor (neutro paga o mesmo)');
        $this->verificarIgual('Leah', $json['npc']['nome'], 'NPC');
        $this->verificarIgual('Atum', $json['unidade']['item']['nome'], 'item dentro da unidade');
    }
}
