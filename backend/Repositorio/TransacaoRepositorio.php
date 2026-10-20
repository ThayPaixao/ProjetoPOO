<?php
declare(strict_types=1);

/* TransacaoRepositorio – salva compras e vendas e monta o inventário e o histórico. */
class TransacaoRepositorio
{
    private const SELECT_UNIDADE = 'SELECT inv.id_compra, inv.id_qualidade, inv.tamanho_cm, inv.preco_pago,
                                           i.id_item, i.nome, i.preco_base, i.foto, c.nome AS tipo
                                    FROM inventario inv
                                    JOIN item i ON i.id_item = inv.id_item
                                    JOIN classificacao c ON c.id_classificacao = i.id_classificacao';

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function salvarCompra(Compra $compra): void
    {
        $unidade = $compra->getUnidade();
        $consulta = $this->pdo->prepare(
            'INSERT INTO compra (id_jogador, id_item, id_qualidade, tamanho_cm, preco_pago)
             VALUES (?, ?, ?, ?, ?) RETURNING id_compra');
        $consulta->execute([$compra->getJogador()->getId(), $unidade->getItem()->getId(),
                            $unidade->getQualidade()->value, $unidade->getTamanhoCm(), $unidade->getPrecoPago()]);
        $id = (int) $consulta->fetchColumn();
        $compra->definirId($id);
        $unidade->definirId($id);       // a unidade no inventário é identificada pela compra
    }

    public function salvarVenda(Venda $venda): void
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO venda (id_compra, id_npc, id_sentimento, preco_venda) VALUES (?, ?, ?, ?) RETURNING id_venda');
        $consulta->execute([$venda->getUnidade()->getId(), $venda->getNpc()->getId(),
                            $venda->getSentimento()->value, $venda->getValor()]);
        $venda->definirId((int) $consulta->fetchColumn());
    }

    /* @return UnidadeItem[]  mais recentes primeiro */
    public function inventario(int $idJogador): array
    {
        $consulta = $this->pdo->prepare(self::SELECT_UNIDADE . ' WHERE inv.id_jogador = ? ORDER BY inv.id_compra DESC');
        $consulta->execute([$idJogador]);
        return array_map(fn(array $linha) => $this->criarUnidade($linha), $consulta->fetchAll());
    }

    /* Só acha a unidade se ela for DESTE jogador e ainda não tiver sido vendida. */
    public function buscarNoInventario(int $idJogador, int $idCompra): UnidadeItem
    {
        $consulta = $this->pdo->prepare(self::SELECT_UNIDADE . ' WHERE inv.id_jogador = ? AND inv.id_compra = ?');
        $consulta->execute([$idJogador, $idCompra]);
        $linha = $consulta->fetch();
        if (!$linha) {
            throw new NaoEncontradoException('Este item não está no seu inventário.');
        }
        return $this->criarUnidade($linha);
    }

    /* Vendas do jogador (mais recentes primeiro), no formato de item do front + detalhes da venda. */
    public function vendas(int $idJogador): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT v.id_venda, v.preco_venda, v.id_sentimento, v.data, n.nome AS npc,
                    co.id_qualidade, co.tamanho_cm, co.preco_pago, i.nome, i.foto, c.nome AS tipo
             FROM venda v
             JOIN compra co ON co.id_compra = v.id_compra
             JOIN item i ON i.id_item = co.id_item
             JOIN classificacao c ON c.id_classificacao = i.id_classificacao
             JOIN npc n ON n.id_npc = v.id_npc
             WHERE co.id_jogador = ?
             ORDER BY v.id_venda DESC');
        $consulta->execute([$idJogador]);
        return array_map(fn(array $linha) => [
            'id'         => (int) $linha['id_venda'],
            'nome'       => $linha['nome'],
            'preco'      => (int) $linha['preco_venda'],
            'tipo'       => $linha['tipo'],
            'qualidade'  => (int) $linha['id_qualidade'],
            'imagem'     => $linha['foto'],
            'tamanho_cm' => $linha['tamanho_cm'] === null ? null : (int) $linha['tamanho_cm'],
            'npc'        => $linha['npc'],
            'sentimento' => Sentimento::from((int) $linha['id_sentimento'])->nome(),
            'preco_pago' => (int) $linha['preco_pago'],
            'lucro'      => (int) $linha['preco_venda'] - (int) $linha['preco_pago'],
            'data'       => self::data($linha['data']),
        ], $consulta->fetchAll());
    }

    /* Compras e vendas juntas (mais recentes primeiro), usando a view "historico". */
    public function historico(int $idJogador): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT h.tipo AS operacao, h.id_compra, h.valor, h.data, h.id_qualidade, h.tamanho_cm, h.id_sentimento,
                    i.nome, i.foto, c.nome AS tipo, n.nome AS npc
             FROM historico h
             JOIN item i ON i.id_item = h.id_item
             JOIN classificacao c ON c.id_classificacao = i.id_classificacao
             JOIN npc n ON n.id_npc = h.id_npc
             WHERE h.id_jogador = ?
             ORDER BY h.data DESC');
        $consulta->execute([$idJogador]);
        return array_map(fn(array $linha) => [
            'id'         => strtolower($linha['operacao']) . '-' . $linha['id_compra'],
            'operacao'   => $linha['operacao'] === 'COMPRA' ? 'Compra' : 'Venda',
            'nome'       => $linha['nome'],
            'preco'      => (int) $linha['valor'],
            'tipo'       => $linha['tipo'],
            'qualidade'  => (int) $linha['id_qualidade'],
            'imagem'     => $linha['foto'],
            'tamanho_cm' => $linha['tamanho_cm'] === null ? null : (int) $linha['tamanho_cm'],
            'npc'        => $linha['npc'],
            'sentimento' => $linha['id_sentimento'] === null ? null : Sentimento::from((int) $linha['id_sentimento'])->nome(),
            'data'       => self::data($linha['data']),
        ], $consulta->fetchAll());
    }

    private function criarUnidade(array $linha): UnidadeItem
    {
        return new UnidadeItem(
            ItemRepositorio::criarDaLinha($linha),
            Qualidade::from((int) $linha['id_qualidade']),
            $linha['tamanho_cm'] === null ? null : (int) $linha['tamanho_cm'],
            (int) $linha['preco_pago'],
            (int) $linha['id_compra']);
    }

    /* Data do banco → formato ISO, que o JavaScript entende com new Date(...) */
    private static function data(string $valor): string
    {
        return (new DateTimeImmutable($valor))->format(DATE_ATOM);
    }
}
