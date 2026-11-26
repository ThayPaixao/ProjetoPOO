<?php
declare(strict_types=1);

/* Testa o Jogador: saldo protegido (ENCAPSULAMENTO), validações do cadastro e a senha. */
class JogadorTeste extends Teste
{
    private Jogador $jogador;
    private static ?Jogador $modelo = null;

    /* O password_hash é lento de propósito (dificulta adivinhar senhas), então o jogador é
       registrado uma vez só e cada caso de teste recebe uma CÓPIA (clone) dele. */
    protected function preparar(): void
    {
        self::$modelo ??= Jogador::registrar('  Ana  ', ' ANA@Exemplo.com ', 'segredo123');
        $this->jogador = clone self::$modelo;
    }

    public function testeComecaCom1000DeOuro(): void
    {
        $this->verificarIgual(1000, $this->jogador->getSaldo(), 'saldo inicial');
    }

    public function testeRegistroLimpaNomeEEmail(): void
    {
        $this->verificarIgual('Ana', $this->jogador->getNome(), 'nome');
        $this->verificarIgual('ana@exemplo.com', $this->jogador->getEmail(), 'e-mail');
    }

    public function testeDebitarTiraDoSaldo(): void
    {
        $this->jogador->debitar(300);
        $this->verificarIgual(700, $this->jogador->getSaldo(), 'saldo');
    }

    public function testeDebitarMaisDoQueTemDaSaldoInsuficienteENaoMexeNoSaldo(): void
    {
        $erro = $this->verificarErro(SaldoInsuficienteException::class, fn() => $this->jogador->debitar(1001));
        $this->verificar(str_contains($erro->getMessage(), '1001'), 'a mensagem deveria dizer quanto custa');
        $this->verificarIgual(1000, $this->jogador->getSaldo(), 'saldo depois do erro');
    }

    public function testeValorZeroOuNegativoEhRecusado(): void
    {
        $this->verificarErro(InvalidArgumentException::class, fn() => $this->jogador->debitar(0));
        $this->verificarErro(InvalidArgumentException::class, fn() => $this->jogador->creditar(-5));
    }

    public function testeCreditarSomaNoSaldo(): void
    {
        $this->jogador->creditar(250);
        $this->verificarIgual(1250, $this->jogador->getSaldo(), 'saldo');
    }

    public function testeNomeGrandeDemaisEhRecusadoNoRegistro(): void
    {
        $this->verificarErro(DadosInvalidosException::class,
            fn() => Jogador::registrar(str_repeat('a', Jogador::NOME_MAXIMO + 1), 'b@exemplo.com', 'segredo123'));
    }

    public function testeNomeGrandeDemaisEhRecusadoNaEdicao(): void
    {
        $this->verificarErro(DadosInvalidosException::class,
            fn() => $this->jogador->alterarNome(str_repeat('a', Jogador::NOME_MAXIMO + 1)));
        $this->verificarIgual('Ana', $this->jogador->getNome(), 'nome depois do erro');
    }

    public function testeNomeComAcentoContaLetrasENaoBytes(): void
    {
        $nome = str_repeat('é', Jogador::NOME_MAXIMO);      // 40 letras, mas 80 bytes
        $this->jogador->alterarNome($nome);
        $this->verificarIgual($nome, $this->jogador->getNome(), 'nome');
    }

    public function testeEmailESenhaInvalidosSaoRecusados(): void
    {
        $this->verificarErro(DadosInvalidosException::class, fn() => Jogador::registrar('Bia', 'nao-e-email', 'segredo123'));
        $this->verificarErro(DadosInvalidosException::class, fn() => Jogador::registrar('Bia', 'bia@exemplo.com', '123'));
    }

    public function testeSenhaFicaGuardadaComoHash(): void
    {
        $this->verificar($this->jogador->getSenhaHash() !== 'segredo123', 'a senha não pode ficar pura');
        $this->verificar($this->jogador->verificarSenha('segredo123'), 'a senha certa deveria passar');
        $this->verificar(!$this->jogador->verificarSenha('errada'), 'a senha errada não deveria passar');
    }

    public function testeTrocarSenhaExigeASenhaAtual(): void
    {
        $this->verificarErro(DadosInvalidosException::class, fn() => $this->jogador->alterarSenha('errada', 'novasenha'));
        $this->jogador->alterarSenha('segredo123', 'novasenha');
        $this->verificar($this->jogador->verificarSenha('novasenha'), 'a senha nova deveria valer');
    }

    public function testeSenhaNuncaVaiParaOJson(): void
    {
        $json = json_encode($this->jogador);
        $this->verificar(!str_contains($json, 'senha') && !str_contains($json, '$2y$'), "o JSON vazou a senha: $json");
    }
}
