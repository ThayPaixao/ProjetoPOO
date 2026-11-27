<?php
declare(strict_types=1);

/* Conexao – abre a conexão com o banco (Supabase) uma vez só e reaproveita.
   O endereço e a senha ficam no arquivo .env, que NÃO vai para o GitHub.
   CÓPIA DO BANCO: esta branch ("melhorias") usa o schema "melhorias", uma cópia separada dentro do
   mesmo banco (criada por database/copia-melhorias.sql). A versão entregue (branch "backend") usa o
   schema "public", que assim nunca é afetado pelas mudanças daqui. */
class Conexao
{
    public const SCHEMA_PADRAO = 'melhorias';     // dá para trocar no .env com DATABASE_SCHEMA=...

    private static ?PDO $pdo = null;

    public static function obter(): PDO
    {
        if (self::$pdo === null) {
            $env = self::lerEnv();
            $url = $env['DATABASE_URL'] ?? '';
            $partes = parse_url($url);
            if (!$partes || !isset($partes['host'], $partes['user'], $partes['pass']) || str_contains($url, 'SUA_SENHA')) {
                throw new ConfiguracaoException('DATABASE_URL não foi preenchida direito no arquivo .env.');
            }
            $schema = self::schema($env);
            // o search_path vai junto na conexão: todas as consultas usam a cópia, sem nenhuma viagem a mais
            $dsn = sprintf("pgsql:host=%s;port=%d;dbname=%s;sslmode=require;options='-c search_path=%s'",
                $partes['host'], $partes['port'] ?? 5432, ltrim($partes['path'] ?? '/postgres', '/'), $schema);
            self::$pdo = new PDO($dsn, urldecode($partes['user']), urldecode($partes['pass']), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // erro no SQL vira exceção
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // O banco fica longe (cada ida e volta leva ~0,2 s), então economizamos viagens:
                PDO::ATTR_PERSISTENT         => true,   // reaproveita a conexão entre requisições
                PDO::ATTR_EMULATE_PREPARES   => true,   // 1 viagem por consulta (em vez de 3); os valores continuam escapados
            ]);
        }
        return self::$pdo;
    }

    /* O schema em uso (o verificar_banco.php mostra qual é). */
    public static function schemaEmUso(): string
    {
        return self::schema(self::lerEnv());
    }

    private static function schema(array $env): string
    {
        $schema = $env['DATABASE_SCHEMA'] ?? self::SCHEMA_PADRAO;
        if (!preg_match('/^[a-z_][a-z0-9_]*$/', $schema)) {      // só letras minúsculas, números e _
            throw new ConfiguracaoException('DATABASE_SCHEMA inválido no .env.');
        }
        return $schema;
    }

    /* Lê as linhas CHAVE=valor do .env (ignora linhas vazias e comentários com #). */
    private static function lerEnv(): array
    {
        $arquivo = dirname(__DIR__, 2) . '/.env';
        if (!is_file($arquivo)) {
            throw new ConfiguracaoException('Arquivo .env não encontrado: copie o .env.example para .env e preencha.');
        }
        $env = [];
        foreach (file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
            $linha = trim($linha);
            if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
                continue;
            }
            [$chave, $valor] = explode('=', $linha, 2);
            $env[trim($chave)] = trim(trim($valor), '"\'');
        }
        return $env;
    }
}
