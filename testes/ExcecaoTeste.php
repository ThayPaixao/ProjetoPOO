<?php
declare(strict_types=1);

/* Testa os erros do jogo: todos herdam de JogoException e cada um diz o seu
   código HTTP e o seu código para o front (POLIMORFISMO). */
class ExcecaoTeste extends Teste
{
    /** @return array<string, JogoException> */
    private function erros(): array
    {
        return [
            'dados_invalidos'     => new DadosInvalidosException('x'),
            'nao_autenticado'     => new NaoAutenticadoException(),
            'nao_encontrado'      => new NaoEncontradoException('x'),
            'negociacao_invalida' => new NegociacaoInvalidaException('x'),
            'saldo_insuficiente'  => new SaldoInsuficienteException(500, 100),
        ];
    }

    public function testeTodosHerdamDeJogoException(): void
    {
        foreach ($this->erros() as $erro) {
            $this->verificar($erro instanceof JogoException, get_class($erro) . ' deveria herdar de JogoException');
        }
    }

    public function testeCadaUmTemOSeuCodigo(): void
    {
        foreach ($this->erros() as $codigo => $erro) {
            $this->verificarIgual($codigo, $erro->codigo(), get_class($erro) . '::codigo()');
        }
    }

    public function testeCadaUmTemOSeuStatusHttp(): void
    {
        $esperado = ['dados_invalidos' => 400, 'nao_autenticado' => 401, 'nao_encontrado' => 404,
                     'negociacao_invalida' => 400, 'saldo_insuficiente' => 400];
        foreach ($this->erros() as $codigo => $erro) {
            $this->verificarIgual($esperado[$codigo], $erro->statusHttp(), get_class($erro) . '::statusHttp()');
        }
    }

    public function testeNaoAutenticadoTemMensagemPadrao(): void
    {
        $this->verificarIgual('Faça login para continuar.', (new NaoAutenticadoException())->getMessage(), 'mensagem');
    }
}
