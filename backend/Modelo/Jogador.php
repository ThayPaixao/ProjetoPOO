<?php
declare(strict_types=1);

/* Jogador – quem usa o site.
   ENCAPSULAMENTO: o saldo e a senha são privados. O saldo só muda por
   debitar() e creditar(), que validam o valor; a senha nunca sai da classe
   (só dá para conferir com verificarSenha()). */
class Jogador
{
    public const SALDO_INICIAL = 1000;
    public const SENHA_MINIMA = 6;      // igual ao minlength do register.html

    private ?int $id;
    private string $nome;
    private string $email;
    private string $senhaHash;
    private int $saldo;
    private ?string $foto;

    public function __construct(?int $id, string $nome, string $email, string $senhaHash, int $saldo, ?string $foto = null)
    {
        $this->id = $id;
        $this->nome = $nome;
        $this->email = $email;
        $this->senhaHash = $senhaHash;
        $this->saldo = $saldo;
        $this->foto = $foto;
    }

    /* Cria um jogador novo a partir do formulário de registro. */
    public static function registrar(string $nome, string $email, string $senha): self
    {
        $nome = trim($nome);
        $email = mb_strtolower(trim($email));
        if ($nome === '') {
            throw new DadosInvalidosException('Informe um nome.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DadosInvalidosException('E-mail inválido.');
        }
        if (mb_strlen($senha) < self::SENHA_MINIMA) {
            throw new DadosInvalidosException('A senha precisa ter pelo menos ' . self::SENHA_MINIMA . ' caracteres.');
        }
        return new self(null, $nome, $email, password_hash($senha, PASSWORD_DEFAULT), self::SALDO_INICIAL);
    }

    public function verificarSenha(string $senha): bool
    {
        return password_verify($senha, $this->senhaHash);
    }

    public function debitar(int $valor): void
    {
        if ($valor <= 0) {
            throw new InvalidArgumentException('O valor precisa ser positivo.');
        }
        if ($valor > $this->saldo) {
            throw new SaldoInsuficienteException($valor, $this->saldo);
        }
        $this->saldo -= $valor;
    }

    public function creditar(int $valor): void
    {
        if ($valor <= 0) {
            throw new InvalidArgumentException('O valor precisa ser positivo.');
        }
        $this->saldo += $valor;
    }

    /* Usado pelo banco depois de salvar o jogador novo. Só pode ser chamado uma vez. */
    public function definirId(int $id): void
    {
        if ($this->id !== null) {
            throw new LogicException('Este jogador já tem id.');
        }
        $this->id = $id;
    }

    public function getId(): ?int { return $this->id; }
    public function getNome(): string { return $this->nome; }
    public function getEmail(): string { return $this->email; }
    public function getSaldo(): int { return $this->saldo; }
    public function getFoto(): ?string { return $this->foto; }
    public function getSenhaHash(): string { return $this->senhaHash; }   // só para salvar no banco

    /* Formato do front: { id, nome, dinheiro, foto }. A senha nunca vai junto. */
    public function paraArray(): array
    {
        return ['id' => $this->id, 'nome' => $this->nome, 'dinheiro' => $this->saldo, 'foto' => $this->foto];
    }
}
