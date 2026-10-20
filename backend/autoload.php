<?php
/* autoload.php – carrega as classes sozinho.
   Quando o código usa "new Peixe(...)", o PHP procura Modelo/Peixe.php ou Excecao/Peixe.php.
   Assim nenhum arquivo precisa de require para cada classe. */
spl_autoload_register(function (string $classe): void {
    foreach (['Modelo', 'Excecao'] as $pasta) {
        $arquivo = __DIR__ . "/$pasta/$classe.php";
        if (is_file($arquivo)) {
            require $arquivo;
            return;
        }
    }
});
