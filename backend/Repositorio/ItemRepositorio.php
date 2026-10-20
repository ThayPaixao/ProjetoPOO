<?php
declare(strict_types=1);

/* ItemRepositorio – busca os itens do catálogo no banco e transforma em objetos Item. */
class ItemRepositorio
{
    private const SELECT = 'SELECT i.id_item, i.nome, i.preco_base, i.foto, c.nome AS tipo
                            FROM item i
                            JOIN classificacao c ON c.id_classificacao = i.id_classificacao';

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** @return Item[] */
    public function listarPorLoja(int $idLoja): array
    {
        $consulta = $this->pdo->prepare(self::SELECT . ' WHERE i.id_loja = ? ORDER BY i.id_item');
        $consulta->execute([$idLoja]);
        return array_map([self::class, 'criarDaLinha'], $consulta->fetchAll());
    }

    public function buscar(int $id): Item
    {
        $consulta = $this->pdo->prepare(self::SELECT . ' WHERE i.id_item = ?');
        $consulta->execute([$id]);
        $linha = $consulta->fetch();
        if (!$linha) {
            throw new NaoEncontradoException('Item não encontrado.');
        }
        return self::criarDaLinha($linha);
    }

    /* Uma linha do banco vira o objeto da subclasse certa (Peixe, Colheita...). */
    public static function criarDaLinha(array $linha): Item
    {
        return Item::criar($linha['tipo'], (int) $linha['id_item'], $linha['nome'], (int) $linha['preco_base'], $linha['foto']);
    }
}
