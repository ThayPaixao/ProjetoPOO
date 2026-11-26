<?php
declare(strict_types=1);

/* SaveRepositorio – o "save" do jogo em arquivo JSON (o nosso próprio banco em arquivo).
   PERSISTÊNCIA: salvar() grava o estado do jogador em saves/jogador-<id>.json;
                 carregar() lê o arquivo de volta quando o jogador volta.
   SERIALIZAÇÃO: os objetos viram JSON pela interface JsonSerializable (json_encode);
                 na volta, o JSON vira objetos de novo (UnidadeItem::deArray, Item::criar).
   ENTRADA/SAÍDA + TRATAMENTO DE ERROS: grava num arquivo temporário e troca de nome no fim
   (se der erro no meio, o save antigo continua inteiro); arquivo corrompido não derruba o site. */
class SaveRepositorio extends RepositorioArquivo
{
    private const PASTA = 'saves';
    private const VERSAO = 1;

    protected function pasta(): string
    {
        return self::PASTA;
    }

    /** @param Colecao<UnidadeItem> $inventario  @param array[] $vendas */
    public function salvar(Jogador $jogador, Colecao $inventario, array $vendas): array
    {
        $this->garantirPasta();
        $agora = new DateTimeImmutable();
        $save = [
            'versao'     => self::VERSAO,
            'salvo_em'   => $agora->format(DATE_ATOM),
            'jogador'    => $jogador,          // JsonSerializable
            'inventario' => $inventario,       // Colecao<UnidadeItem>: vira uma lista JSON (JsonSerializable)
            'vendas'     => $vendas,
        ];
        $json = json_encode($save, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $arquivo = $this->caminho((int) $jogador->getId());
        $temporario = "$arquivo.tmp";
        if (file_put_contents($temporario, $json, LOCK_EX) === false || !rename($temporario, $arquivo)) {
            throw new RuntimeException('Falha ao gravar o save no disco.');
        }
        return ['salvo_em' => $save['salvo_em'], 'arquivo' => self::PASTA . '/' . basename($arquivo), 'itens' => count($inventario)];
    }

    /* Lê o save e reconstrói os objetos. Devolve null se ainda não existe (ou se estiver corrompido). */
    public function carregar(int $idJogador): ?array
    {
        $arquivo = $this->caminho($idJogador);
        if (!is_file($arquivo)) {
            return null;
        }
        try {
            $dados = json_decode((string) file_get_contents($arquivo), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($dados) || ($dados['versao'] ?? null) !== self::VERSAO) {
                throw new SaveCorrompidoException('Não é um save desta versão do jogo.');
            }
            if (!is_string($dados['salvo_em'] ?? null) || !is_string($dados['jogador']['nome'] ?? null)
                || !is_int($dados['jogador']['dinheiro'] ?? null)
                || !is_array($dados['inventario'] ?? null) || !is_array($dados['vendas'] ?? null)) {
                throw new SaveCorrompidoException('Faltam partes do save.');
            }
            return [
                'salvo_em'   => new DateTimeImmutable($dados['salvo_em']),
                'arquivo'    => self::PASTA . '/' . basename($arquivo),
                'nome'       => $dados['jogador']['nome'],
                'dinheiro'   => $dados['jogador']['dinheiro'],
                'inventario' => new Colecao(UnidadeItem::class, array_map(                       // DESSERIALIZAÇÃO
                                    fn($unidade) => UnidadeItem::deArray(is_array($unidade) ? $unidade : []),
                                    $dados['inventario'])),
                'vendas'     => $dados['vendas'],
            ];
        } catch (SaveCorrompidoException | JsonException | DateMalformedStringException | ValueError | InvalidArgumentException $erro) {
            // SaveCorrompido: faltou campo | Json: não é JSON | DateMalformed: data inválida
            // ValueError: qualidade fora de 0-3 | InvalidArgument: tipo de item desconhecido ou preço <= 0
            error_log("Save corrompido ($arquivo): " . $erro->getMessage());
            return null;                      // trata o erro: segue sem o save, sem quebrar a página
        }
    }

    private function caminho(int $idJogador): string
    {
        return $this->caminhoNoDisco("jogador-$idJogador.json");
    }
}
