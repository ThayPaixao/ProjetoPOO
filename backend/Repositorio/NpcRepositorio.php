<?php
declare(strict_types=1);

/* NpcRepositorio – busca os NPCs no banco. Vendedor vem com o catálogo da loja;
   Cliente vem com os gostos (o que ama e o que odeia). */
class NpcRepositorio
{
    private const SELECT = 'SELECT n.id_npc, n.nome, n.foto, n.tipo, l.id_loja, l.nome AS loja
                            FROM npc n
                            LEFT JOIN loja l ON l.id_npc = n.id_npc';

    private PDO $pdo;
    private ItemRepositorio $itens;

    public function __construct(PDO $pdo, ItemRepositorio $itens)
    {
        $this->pdo = $pdo;
        $this->itens = $itens;
    }

    /* Só para listar os botões: sem catálogo e sem gostos.
       @return NPC[] */
    public function listar(string $tipo): array
    {
        $consulta = $this->pdo->prepare(self::SELECT . ' WHERE n.tipo = ? ORDER BY n.nome');
        $consulta->execute([$tipo]);
        return array_map(fn(array $linha) => $this->criar($linha), $consulta->fetchAll());
    }

    public function buscarVendedor(string $id): Vendedor
    {
        $linha = $this->buscarLinha($id, 'VENDEDOR');
        $vendedor = $this->criar($linha);
        foreach ($this->itens->listarPorLoja((int) $linha['id_loja']) as $item) {
            $vendedor->adicionarAoCatalogo($item);
        }
        return $vendedor;
    }

    public function buscarCliente(string $id): Cliente
    {
        $cliente = $this->criar($this->buscarLinha($id, 'CLIENTE'));
        $consulta = $this->pdo->prepare('SELECT id_item, id_sentimento FROM npc_sentimento WHERE id_npc = ?');
        $consulta->execute([$id]);
        foreach ($consulta->fetchAll() as $gosto) {
            $cliente->definirSentimento((int) $gosto['id_item'], Sentimento::from((int) $gosto['id_sentimento']));
        }
        return $cliente;
    }

    private function buscarLinha(string $id, string $tipo): array
    {
        $consulta = $this->pdo->prepare(self::SELECT . ' WHERE n.id_npc = ? AND n.tipo = ?');
        $consulta->execute([$id, $tipo]);
        $linha = $consulta->fetch();
        if (!$linha) {
            throw new NaoEncontradoException($tipo === 'VENDEDOR' ? 'Loja não encontrada.' : 'NPC não encontrado.');
        }
        return $linha;
    }

    /* Fábrica: VENDEDOR vira Vendedor, CLIENTE vira Cliente. */
    private function criar(array $linha): NPC
    {
        return match ($linha['tipo']) {
            'VENDEDOR' => new Vendedor($linha['id_npc'], $linha['nome'], $linha['loja'], $linha['foto']),
            'CLIENTE'  => new Cliente($linha['id_npc'], $linha['nome'], $linha['foto']),
        };
    }
}
