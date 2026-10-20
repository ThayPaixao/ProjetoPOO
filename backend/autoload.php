<?php
/* autoload.php – carrega as classes sozinho.
   Quando o código usa "new Peixe(...)", o PHP procura Peixe.php nas pastas Modelo, Excecao, Repositorio e Controle.
   Assim nenhum arquivo precisa de require para cada classe. */
spl_autoload_register(function (string $classe): void {
    foreach (['Modelo', 'Excecao', 'Repositorio', 'Controle'] as $pasta) {
        $arquivo = __DIR__ . "/$pasta/$classe.php";
        if (is_file($arquivo)) {
            require $arquivo;
            return;
        }
    }
});
