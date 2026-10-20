<?php
/* verificar_banco.php – confere se o .env está certo e se o banco tem os dados.
   Rode na pasta do projeto:   php backend/verificar_banco.php */
declare(strict_types=1);
require __DIR__ . '/autoload.php';

try {
    $total = Conexao::obter()->query(
        'SELECT (SELECT count(*) FROM item) AS itens, (SELECT count(*) FROM npc) AS npcs,
                (SELECT count(*) FROM npc_sentimento) AS gostos, (SELECT count(*) FROM jogador) AS jogadores'
    )->fetch();
    echo "Conectado! itens: {$total['itens']} | NPCs: {$total['npcs']} | gostos: {$total['gostos']} | jogadores: {$total['jogadores']}", PHP_EOL;
} catch (Throwable $erro) {
    echo 'Não conectou: ', $erro->getMessage(), PHP_EOL;
    exit(1);
}
