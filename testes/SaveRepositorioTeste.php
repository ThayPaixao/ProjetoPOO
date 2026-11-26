<?php
declare(strict_types=1);

/* Testa o save em arquivo (PERSISTÊNCIA e ENTRADA/SAÍDA) numa pasta temporária,
   para nunca mexer nos saves de verdade. */
class SaveRepositorioTeste extends Teste
{
    private string $pasta;
    private string $log;
    private string|false $logAntigo;
    private SaveRepositorio $saves;
    private Jogador $jogador;

    protected function preparar(): void
    {
        $this->pasta = sys_get_temp_dir() . '/vale-testes-' . bin2hex(random_bytes(4));
        mkdir($this->pasta);
        // os erros que o SaveRepositorio registra vão para um arquivo nosso, para o teste poder ler
        $this->log = "{$this->pasta}/erros.log";
        $this->logAntigo = ini_set('error_log', $this->log);
        $this->saves = new SaveRepositorio($this->pasta);
        $this->jogador = new Jogador(7, 'Ana', 'ana@exemplo.com', 'x', 850);
    }

    protected function limpar(): void
    {
        ini_set('error_log', $this->logAntigo === false ? '' : $this->logAntigo);
        foreach (glob("{$this->pasta}/{,saves/}*", GLOB_BRACE) as $arquivo) {
            if (is_file($arquivo)) {
                unlink($arquivo);
            }
        }
        @rmdir("{$this->pasta}/saves");
        @rmdir($this->pasta);
    }

    private function inventario(): Colecao
    {
        return new Colecao(UnidadeItem::class, [
            new UnidadeItem(new Peixe(1, 'Atum', 100), Qualidade::PRATA, 35, 188, 11),
            new UnidadeItem(new Colheita(2, 'Abóbora', 320), Qualidade::IRIDIO, null, 640, 12),
        ]);
    }

    private function escreverSave(string $conteudo): void
    {
        mkdir("{$this->pasta}/saves");
        file_put_contents("{$this->pasta}/saves/jogador-7.json", $conteudo);
    }

    public function testeSalvarECarregarDevolveOsMesmosDados(): void
    {
        $this->saves->salvar($this->jogador, $this->inventario(), [['nome' => 'Jade']]);
        $save = $this->saves->carregar(7);
        $this->verificar($save !== null, 'o save deveria ter carregado');
        $this->verificarIgual(850, $save['dinheiro'], 'dinheiro');
        $this->verificarIgual('Ana', $save['nome'], 'nome');
        $this->verificarIgual(2, count($save['inventario']), 'itens no inventário');
        $this->verificarIgual(1, count($save['vendas']), 'vendas');
        $atum = $save['inventario']->paraLista()[0];
        $this->verificar($atum instanceof UnidadeItem && $atum->getItem() instanceof Peixe, 'o atum deveria voltar como objeto Peixe');
        $this->verificarIgual('Atum (Prata, 35 cm)', $atum->descricao(), 'descrição do atum');
    }

    public function testeNaoDeixaArquivoTemporarioParaTras(): void
    {
        $this->saves->salvar($this->jogador, $this->inventario(), []);
        $this->verificarIgual(['jogador-7.json'], array_map('basename', glob("{$this->pasta}/saves/*")), 'arquivos na pasta');
    }

    public function testeSemSaveDevolveNull(): void
    {
        $this->verificarIgual(null, $this->saves->carregar(7), 'carregar sem save');
    }

    public function testeArquivoQueNaoEhJsonEhIgnoradoERegistrado(): void
    {
        $this->escreverSave('{ isto não é json');
        $this->verificarIgual(null, $this->saves->carregar(7), 'carregar save quebrado');
        $this->verificar(str_contains((string) @file_get_contents($this->log), 'Save corrompido'), 'o erro deveria ir para o log');
    }

    public function testeSaveSemAsPartesObrigatoriasEhIgnorado(): void
    {
        $this->escreverSave('{"versao": 1, "jogador": {"nome": "Ana"}}');
        $this->verificarIgual(null, $this->saves->carregar(7), 'carregar save incompleto');
    }

    public function testeSaveDeOutraVersaoEhIgnorado(): void
    {
        $this->escreverSave('{"versao": 99, "salvo_em": "2026-11-26T10:00:00-03:00", "jogador": {"nome": "Ana", "dinheiro": 5},'
                          . ' "inventario": [], "vendas": []}');
        $this->verificarIgual(null, $this->saves->carregar(7), 'carregar save de outra versão');
    }

    public function testeItemEstragadoNoInventarioEhIgnorado(): void
    {
        $this->escreverSave('{"versao": 1, "salvo_em": "2026-11-26T10:00:00-03:00", "jogador": {"nome": "Ana", "dinheiro": 5},'
                          . ' "inventario": [{"item": {"tipo": "Dragão", "id": 1, "nome": "X", "preco": 1}, "qualidade": 0, "preco_pago": 1}],'
                          . ' "vendas": []}');
        $this->verificarIgual(null, $this->saves->carregar(7), 'carregar save com item de tipo desconhecido');
    }
}
