<?php
declare(strict_types=1);

/* Saida – escreve o resultado dos testes no terminal (verde ✓ passou, vermelho ✗ falhou).
   As cores só aparecem num terminal de verdade; se a saída for para um arquivo, sai sem cor. */
class Saida
{
    private bool $cores;

    public function __construct()
    {
        $this->cores = stream_isatty(STDOUT);
    }

    public function titulo(string $texto): void
    {
        echo "\n" . $this->pintar("1", $texto) . "\n";
    }

    public function passou(string $caso): void
    {
        echo '  ' . $this->pintar('32', '✓') . ' ' . self::legivel($caso) . "\n";
    }

    public function falhou(string $caso, string $motivo): void
    {
        echo '  ' . $this->pintar('31', '✗ ' . self::legivel($caso)) . "\n      $motivo\n";
    }

    public function resumo(int $total, int $falhas): void
    {
        $texto = $falhas === 0
            ? "Todos os $total testes passaram!"
            : "$falhas de $total testes falharam.";
        echo "\n" . $this->pintar($falhas === 0 ? '1;32' : '1;31', $texto) . "\n";
    }

    /* "testeDebitarTiraDoSaldo" → "debitar tira do saldo" (mais fácil de ler no terminal) */
    private static function legivel(string $caso): string
    {
        $semPrefixo = (string) preg_replace('/^teste/', '', $caso);
        // espaço antes de cada maiúscula e entre letra e número ("Paga50" → "Paga 50")
        return mb_strtolower((string) preg_replace('/(?<!^)(?=[A-Z])|(?<=[a-z])(?=[0-9])/', ' ', $semPrefixo));
    }

    private function pintar(string $cor, string $texto): string
    {
        return $this->cores ? "\033[{$cor}m$texto\033[0m" : $texto;
    }
}
