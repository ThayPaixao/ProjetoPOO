<?php
declare(strict_types=1);

/* JogadorRepositorio – salva e busca jogadores no banco. */
class JogadorRepositorio
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /* $travar = true usa FOR UPDATE: segura a linha do jogador até o fim da transação,
       para duas compras ao mesmo tempo não gastarem o mesmo dinheiro. */
    public function buscarPorId(int $id, bool $travar = false): ?Jogador
    {
        $consulta = $this->pdo->prepare('SELECT * FROM jogador WHERE id_jogador = ?' . ($travar ? ' FOR UPDATE' : ''));
        $consulta->execute([$id]);
        $linha = $consulta->fetch();
        return $linha ? $this->criar($linha) : null;
    }

    public function buscarPorEmail(string $email): ?Jogador
    {
        $consulta = $this->pdo->prepare('SELECT * FROM jogador WHERE email = ?');
        $consulta->execute([mb_strtolower(trim($email))]);
        $linha = $consulta->fetch();
        return $linha ? $this->criar($linha) : null;
    }

    public function inserir(Jogador $jogador): void
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO jogador (nome, email, senha_hash, saldo, foto) VALUES (?, ?, ?, ?, ?) RETURNING id_jogador');
        try {
            $consulta->execute([$jogador->getNome(), $jogador->getEmail(), $jogador->getSenhaHash(),
                                $jogador->getSaldo(), $jogador->getFoto()]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {     // 23505 = valor repetido numa coluna UNIQUE
                throw new DadosInvalidosException('Este e-mail já está cadastrado.');
            }
            throw $e;
        }
        $jogador->definirId((int) $consulta->fetchColumn());
    }

    public function atualizarPerfil(Jogador $jogador): void
    {
        $consulta = $this->pdo->prepare('UPDATE jogador SET nome = ?, senha_hash = ?, foto = ? WHERE id_jogador = ?');
        $consulta->execute([$jogador->getNome(), $jogador->getSenhaHash(), $jogador->getFoto(), $jogador->getId()]);
    }

    public function atualizarSaldo(Jogador $jogador): void
    {
        $consulta = $this->pdo->prepare('UPDATE jogador SET saldo = ? WHERE id_jogador = ?');
        $consulta->execute([$jogador->getSaldo(), $jogador->getId()]);
    }

    /* As contas criadas para testar o site usam e-mails @exemplo.com e ficam fora do ranking. */
    private const EMAIL_DE_TESTE = '%@exemplo.com';

    /* Ranking da vila: quem mais lucrou vendendo (só entra quem já vendeu alguma coisa).
       @return array[]  [{ id, nome, foto, vendas, lucro }], do maior lucro para o menor */
    public function ranking(int $limite = 10): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT j.id_jogador, j.nome, j.foto, count(*) AS vendas, sum(v.preco_venda - c.preco_pago) AS lucro
             FROM jogador j
             JOIN compra c ON c.id_jogador = j.id_jogador
             JOIN venda v ON v.id_compra = c.id_compra
             WHERE j.email NOT LIKE ?
             GROUP BY j.id_jogador
             ORDER BY lucro DESC, vendas DESC, j.id_jogador
             LIMIT ?');
        $consulta->execute([self::EMAIL_DE_TESTE, $limite]);
        return array_map(fn(array $linha) => [
            'id'     => (int) $linha['id_jogador'],
            'nome'   => $linha['nome'],
            'foto'   => $linha['foto'],
            'vendas' => (int) $linha['vendas'],
            'lucro'  => (int) $linha['lucro'],
        ], $consulta->fetchAll());
    }

    private function criar(array $linha): Jogador
    {
        return new Jogador((int) $linha['id_jogador'], $linha['nome'], $linha['email'],
                           $linha['senha_hash'], (int) $linha['saldo'], $linha['foto']);
    }
}
