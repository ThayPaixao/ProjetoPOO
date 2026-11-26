<?php
/* rodar.php – roda TODOS os testes. Na pasta do projeto:
       php testes/rodar.php
   Acha sozinho toda classe *Teste.php desta pasta que herda de Teste, roda cada uma e mostra o resultado.
   Não precisa do banco nem de internet: os testes usam só as classes e uma pasta temporária.
   Termina com código 0 se tudo passou e 1 se algo falhou (útil para automatizar). */
declare(strict_types=1);
require dirname(__DIR__) . '/backend/autoload.php';

spl_autoload_register(function (string $classe): void {      // as classes desta pasta (Teste, Saida...)
    $arquivo = __DIR__ . "/$classe.php";
    if (is_file($arquivo)) {
        require $arquivo;
    }
});

$saida = new Saida();
$total = 0;
$falhas = [];
foreach (glob(__DIR__ . '/*Teste.php') as $arquivo) {
    $classe = basename($arquivo, '.php');
    if (!is_subclass_of($classe, Teste::class)) {
        continue;                                  // a própria base "Teste" e a FalhaDeTeste não são testes
    }
    $saida->titulo($classe);
    $teste = new $classe();
    $falhas = [...$falhas, ...$teste->rodar($saida)];
    $total += count(array_filter(get_class_methods($teste), fn($m) => str_starts_with($m, 'teste')));
}
$saida->resumo($total, count($falhas));
exit($falhas === [] ? 0 : 1);
