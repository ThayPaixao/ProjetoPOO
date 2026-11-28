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
    echo "Conectado ao schema \"", Conexao::schemaEmUso(), "\"! itens: {$total['itens']} | NPCs: {$total['npcs']}",
         " | gostos: {$total['gostos']} | jogadores: {$total['jogadores']}", PHP_EOL;

    // As regras dos gostos (GOSTOS.md): todo item é amado por alguém e nenhum é odiado por todos os clientes.
    $regras = Conexao::obter()->query(
        "SELECT (SELECT count(*) FROM item i WHERE NOT EXISTS
                    (SELECT 1 FROM npc_sentimento s WHERE s.id_item = i.id_item AND s.id_sentimento = 1)) AS sem_amor,
                (SELECT count(*) FROM item i WHERE (SELECT count(*) FROM npc_sentimento s
                    WHERE s.id_item = i.id_item AND s.id_sentimento = 3) = (SELECT count(*) FROM npc WHERE tipo = 'CLIENTE')) AS odiados"
    )->fetch();
    $ok = $regras['sem_amor'] == 0 && $regras['odiados'] == 0;
    echo $ok ? 'Regras dos gostos: ok (todo item tem quem ame, nenhum é odiado por todos).'
             : "Regras dos gostos QUEBRADAS: {$regras['sem_amor']} itens sem quem ame, {$regras['odiados']} odiados por todos.", PHP_EOL;
} catch (Throwable $erro) {
    echo 'Não conectou: ', $erro->getMessage(), PHP_EOL;
    if (str_contains($erro->getMessage(), 'does not exist')) {
        echo 'Dica: a cópia do banco desta branch ainda não existe. Rode o database/copia-melhorias.sql no Supabase.', PHP_EOL;
    }
    exit(1);
}
