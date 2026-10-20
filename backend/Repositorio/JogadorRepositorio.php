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

    public function atualizarSaldo(Jogador $jogador): void
    {
        $consulta = $this->pdo->prepare('UPDATE jogador SET saldo = ? WHERE id_jogador = ?');
        $consulta->execute([$jogador->getSaldo(), $jogador->getId()]);
    }

    private function criar(array $linha): Jogador
    {
        return new Jogador((int) $linha['id_jogador'], $linha['nome'], $linha['email'],
                           $linha['senha_hash'], (int) $linha['saldo'], $linha['foto']);
    }
}
