<?php
/* demo.php – mostra as classes funcionando, sem banco de dados.
   Rode no terminal, na pasta do projeto:   php backend/demo.php
   (os itens são sorteados, então cada execução dá um resultado diferente) */
declare(strict_types=1);
require __DIR__ . '/autoload.php';

function titulo(string $texto): void
{
    echo PHP_EOL, "=== $texto ===", PHP_EOL;
}

// ---------- montando o "mundo" ----------
// Item::criar escolhe a subclasse certa pelo tipo (é assim que o banco vai criar os itens)
$atum     = Item::criar('Peixe', 4, 'Atum', 100);
$abobora  = Item::criar('Colheita', 24, 'Abóbora', 320);
$biscoito = Item::criar('Comida', 51, 'Biscoito', 140);
$jade     = Item::criar('Minério', 66, 'Jade', 200);

$willy = new Vendedor('willy', 'Willy', 'Loja do Willy');
$willy->adicionarAoCatalogo($atum);
$pierre = new Vendedor('pierre', 'Pierre', 'Loja do Pierre');
$pierre->adicionarAoCatalogo($abobora);

$elliott = new Cliente('elliott', 'Elliott');
$elliott->definirSentimento($atum->getId(), Sentimento::AMA);
$sebastian = new Cliente('sebastian', 'Sebastian');           // neutro com o atum
$leah = new Cliente('leah', 'Leah');
$leah->definirSentimento($atum->getId(), Sentimento::ODEIA);

$jogador = Jogador::registrar('Jogador de Teste', 'teste@exemplo.com', 'senha123');

// ---------- HERANÇA ----------
titulo('HERANÇA: todos são Item, cada um da sua subclasse');
foreach ([$atum, $abobora, $biscoito, $jade] as $item) {
    printf("%s classe %-9s tipo %s preço base %4d g   é Item? %s\n",
        mb_str_pad($item->getNome(), 9), get_class($item), mb_str_pad($item->getTipo(), 9), $item->getPrecoBase(),
        $item instanceof Item ? 'sim' : 'não');
}
foreach ([$willy, $elliott] as $npc) {
    printf("%-9s classe %-9s é NPC? %s\n", $npc->getNome(), get_class($npc), $npc instanceof NPC ? 'sim' : 'não');
}

// ---------- POLIMORFISMO ----------
titulo('POLIMORFISMO: o mesmo calcularPreco(), cada classe do seu jeito');
foreach ([10, 30, 50] as $cm) {
    printf("Atum (Peixe) de %d cm, qualidade Ouro: %d g\n", $cm, $atum->calcularPreco(Qualidade::OURO, $cm));
}
printf("Abóbora (Colheita), qualidade Ouro:  %d g  (não tem tamanho)\n", $abobora->calcularPreco(Qualidade::OURO));

titulo('POLIMORFISMO: o mesmo podeNegociar(), regras diferentes');
foreach ([$willy, $pierre, $elliott] as $npc) {
    printf("%-8s (%s) aceita Atum? %s\n", $npc->getNome(), $npc->getCategoria(), $npc->podeNegociar($atum) ? 'sim' : 'não');
}

// ---------- COMPRA ----------
titulo('COMPRA: o Willy sorteia qualidade e tamanho');
printf("Saldo antes: %d g\n", $jogador->getSaldo());
$unidade = $willy->vender($atum);
$compra = new Compra($jogador, $unidade, $willy);
$compra->executar();
echo $compra->descricao(), PHP_EOL;
printf("Saldo depois: %d g\n", $jogador->getSaldo());

titulo('Quanto cada cliente paga por esse atum');
foreach ([$elliott, $sebastian, $leah] as $cliente) {
    printf("%-9s %-6s -> %3d g   \"%s\"\n",
        $cliente->getNome(), $cliente->sentimentoPor($atum)->nome(), $cliente->oferta($unidade), $cliente->reacao($atum));
}

// ---------- VENDA ----------
titulo('VENDA: vendendo para quem ama');
$venda = new Venda($jogador, $unidade, $elliott);
$venda->executar();
echo $venda->descricao(), PHP_EOL;
printf("Saldo final: %d g  (lucro de %d g)\n", $jogador->getSaldo(), $venda->getValor() - $compra->getValor());

titulo('POLIMORFISMO: uma lista de Transacao, cada uma descreve a si mesma');
foreach ([$compra, $venda] as $transacao) {
    echo ' - ', $transacao->descricao(), PHP_EOL;
}

// ---------- TIPOS GENÉRICOS ----------
titulo('TIPOS GENÉRICOS: Colecao<Item> só aceita Item (≈ List<Item> no Java)');
$itens = new Colecao(Item::class, [$atum, $abobora, $biscoito, $jade]);
printf("Colecao<%s> com %d itens: %s\n", $itens->getTipo(), count($itens),
    implode(', ', $itens->mapear(fn(Item $i) => $i->getNome())));
$caros = $itens->filtrar(fn(Item $i) => $i->getPrecoBase() >= 200);      // devolve outra Colecao<Item>
printf("filtrar(preço >= 200) → Colecao<%s>: %s\n", $caros->getTipo(),
    implode(', ', $caros->mapear(fn(Item $i) => $i->getNome())));
try {
    $itens->adicionar($jogador);                                           // Jogador não é Item
} catch (InvalidArgumentException $e) {
    echo 'Bloqueado: ', $e->getMessage(), PHP_EOL;
}

// ---------- ENCAPSULAMENTO ----------
titulo('ENCAPSULAMENTO: o saldo só muda pelas regras da classe');
try {
    $jogador->debitar(999999);
} catch (SaldoInsuficienteException $e) {
    echo 'Bloqueado: ', $e->getMessage(), PHP_EOL;
}
try {
    $pierre->vender($atum);
} catch (NegociacaoInvalidaException $e) {
    echo 'Bloqueado: ', $e->getMessage(), PHP_EOL;
}
echo 'E $jogador->saldo = 999999; dá erro: a propriedade é private.', PHP_EOL;
echo 'O jogador como o front recebe (sem a senha): ', json_encode($jogador->paraArray(), JSON_UNESCAPED_UNICODE), PHP_EOL;

// ---------- PADRÃO OBSERVER ----------
titulo('PADRÃO OBSERVER: cada compra/venda é publicada no QuadroDeAvisos');
$quadro = new QuadroDeAvisos();
$conquistas = new ObservadorDeConquistas(fn(int $id) => new Estatisticas());   // jogador novo (no site, vem do banco)
$quadro->inscrever($conquistas);
$quadro->inscrever(new class implements ObservadorDeTransacao {                // um 2º observador, só para mostrar
    public function notificar(Transacao $transacao): void
    {
        echo '  [registro] ', $transacao->descricao(), PHP_EOL;
    }
});
$novato = new Jogador(99, 'Novato', 'novato@exemplo.com', 'x', 1000);
$sorte = new Compra($novato, new UnidadeItem($atum, Qualidade::IRIDIO, 47, $atum->calcularPreco(Qualidade::IRIDIO, 47)), $willy);
$sorte->executar();
$quadro->publicar($sorte);                     // o quadro não sabe quem está ouvindo: só avisa todo mundo
echo 'Conquistas liberadas: ', implode(', ', array_map(fn(Conquista $c) => $c->nome(), $conquistas->novas())), PHP_EOL;
