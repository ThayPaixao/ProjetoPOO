<?php
declare(strict_types=1);

/* Conexao – abre a conexão com o banco (Supabase) uma vez só e reaproveita.
   O endereço e a senha ficam no arquivo .env, que NÃO vai para o GitHub. */
class Conexao
{
    private static ?PDO $pdo = null;

    public static function obter(): PDO
    {
        if (self::$pdo === null) {
            $url = self::lerEnv()['DATABASE_URL'] ?? '';
            $partes = parse_url($url);
            if (!$partes || !isset($partes['host'], $partes['user'], $partes['pass']) || str_contains($url, 'SUA_SENHA')) {
                throw new ConfiguracaoException('DATABASE_URL não foi preenchida direito no arquivo .env.');
            }
            $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s;sslmode=require',
                $partes['host'], $partes['port'] ?? 5432, ltrim($partes['path'] ?? '/postgres', '/'));
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
