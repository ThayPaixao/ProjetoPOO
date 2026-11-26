<?php
declare(strict_types=1);

/* Testa a Colecao<T> (TIPOS GENÉRICOS): só aceita o tipo escolhido e funciona como uma lista. */
class ColecaoTeste extends Teste
{
    /** @var Colecao<Item> */
    private Colecao $itens;

    protected function preparar(): void
    {
        $this->itens = new Colecao(Item::class);
        $this->itens->adicionar(new Peixe(1, 'Atum', 100), 1);
        $this->itens->adicionar(new Colheita(2, 'Abóbora', 320), 2);
        $this->itens->adicionar(new Minerio(3, 'Jade', 200), 3);
    }

    public function testeAceitaAsSubclassesDoTipo(): void
    {
        $this->verificarIgual(3, count($this->itens), 'quantidade');
    }

    public function testeRecusaOutroTipo(): void
    {
        $erro = $this->verificarErro(InvalidArgumentException::class,
            fn() => $this->itens->adicionar(new Jogador(1, 'Ana', 'ana@exemplo.com', 'x', 10)));
        $this->verificar(str_contains($erro->getMessage(), 'Jogador'), 'a mensagem deveria dizer o tipo recusado');
        $this->verificarIgual(3, count($this->itens), 'quantidade depois do erro');
    }

    public function testeTipoQueNaoExisteEhRecusado(): void
    {
        $this->verificarErro(InvalidArgumentException::class, fn() => new Colecao('Dragao'));
    }

    public function testeObterPelaChave(): void
    {
        $this->verificarIgual('Abóbora', $this->itens->obter(2)?->getNome(), 'item 2');
        $this->verificarIgual(null, $this->itens->obter(99), 'chave que não existe');
        $this->verificar($this->itens->contem(3) && !$this->itens->contem(99), 'contem()');
    }

    public function testeFiltrarDevolveColecaoDoMesmoTipo(): void
    {
        $caros = $this->itens->filtrar(fn(Item $item) => $item->getPrecoBase() >= 200);
        $this->verificarIgual(Item::class, $caros->getTipo(), 'tipo da coleção filtrada');
        $this->verificarIgual(['Abóbora', 'Jade'], $caros->mapear(fn(Item $item) => $item->getNome()), 'itens filtrados');
    }

    public function testeFuncionaNoForeach(): void
    {
        $nomes = [];
        foreach ($this->itens as $item) {
            $nomes[] = $item->getNome();
        }
        $this->verificarIgual(['Atum', 'Abóbora', 'Jade'], $nomes, 'nomes no foreach');
    }

    public function testeEncontrar(): void
    {
        $peixe = $this->itens->encontrar(fn(Item $item) => $item instanceof Peixe);
        $this->verificarIgual('Atum', $peixe?->getNome(), 'primeiro peixe');
        $this->verificarIgual(null, $this->itens->encontrar(fn(Item $item) => $item->getPrecoBase() > 1000), 'nenhum item');
    }

    public function testeVaiParaOJsonComoLista(): void
    {
        $json = json_decode(json_encode($this->itens), true);
        $this->verificar(array_is_list($json), 'o JSON deveria ser uma lista, não um objeto com chaves');
        $this->verificarIgual('Peixe', $json[0]['tipo'], 'tipo do primeiro item no JSON');
    }

    public function testeFuncionaComEnum(): void
    {
        $gostos = new Colecao(Sentimento::class);
        $gostos->adicionar(Sentimento::AMA, 10);
        $this->verificarIgual(Sentimento::AMA, $gostos->obter(10), 'sentimento guardado');
        $this->verificarErro(InvalidArgumentException::class, fn() => $gostos->adicionar(Qualidade::OURO));
    }
}
