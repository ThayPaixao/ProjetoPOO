<?php
declare(strict_types=1);

/* Teste – a base (abstrata) de todos os testes: o nosso mini PHPUnit, sem instalar nada.
   Como funciona:
   - cada classe de teste HERDA daqui (ex.: JogadorTeste extends Teste);
   - cada método público que começa com "teste" é um caso de teste;
   - o rodar() acha esses métodos sozinho (REFLEXÃO: o PHP lista os métodos da classe),
     roda um por um e conta quantos passaram;
   - as verificações (verificar, verificarIgual, verificarErro) lançam FalhaDeTeste quando algo
     não sai como esperado. Qualquer outra exceção também conta como falha.
   Para rodar todos: php testes/rodar.php */
abstract class Teste
{
    /* Roda ANTES de cada caso. As subclasses sobrescrevem para preparar o que precisam. */
    protected function preparar(): void
    {
    }

    /* Roda DEPOIS de cada caso, mesmo se ele falhar (ex.: apagar arquivos temporários). */
    protected function limpar(): void
    {
    }

    /** @return string[]  as falhas (vazio = tudo passou) */
    public function rodar(Saida $saida): array
    {
        $falhas = [];
        foreach ((new ReflectionClass($this))->getMethods(ReflectionMethod::IS_PUBLIC) as $metodo) {
            $nome = $metodo->getName();
            if (!str_starts_with($nome, 'teste')) {
                continue;
            }
            try {
                $this->preparar();
                $metodo->invoke($this);
                $saida->passou($nome);
            } catch (Throwable $erro) {
                $motivo = $erro instanceof FalhaDeTeste
                    ? $erro->getMessage()
                    : sprintf('%s inesperada: %s (%s:%d)', get_class($erro), $erro->getMessage(),
                              basename($erro->getFile()), $erro->getLine());
                $saida->falhou($nome, $motivo);
                $falhas[] = static::class . "::$nome – $motivo";
            } finally {
                $this->limpar();
            }
        }
        return $falhas;
    }

    // ---------- verificações (usadas dentro dos casos de teste) ----------

    protected function verificar(bool $condicao, string $mensagem): void
    {
        if (!$condicao) {
            throw new FalhaDeTeste($mensagem);
        }
    }

    protected function verificarIgual(mixed $esperado, mixed $obtido, string $oQue = 'valor'): void
    {
        if ($esperado !== $obtido) {
            throw new FalhaDeTeste(sprintf('%s: esperava %s, veio %s', $oQue,
                var_export($esperado, true), var_export($obtido, true)));
        }
    }

    /* Roda $codigo e confere que ele lança um erro da classe $classe (ou de uma filha dela).
       Devolve o erro, para o teste poder conferir a mensagem.
       @template E of Throwable  @param class-string<E> $classe  @return E */
    protected function verificarErro(string $classe, callable $codigo): Throwable
    {
        try {
            $codigo();
        } catch (Throwable $erro) {
            if ($erro instanceof $classe) {
                return $erro;
            }
            throw new FalhaDeTeste(sprintf('esperava %s, mas veio %s: %s', $classe, get_class($erro), $erro->getMessage()));
        }
        throw new FalhaDeTeste("esperava $classe, mas nenhum erro aconteceu");
    }
}
