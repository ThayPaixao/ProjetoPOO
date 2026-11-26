<?php
/* api/index.php – a API que o front chama (js/api.js).
   Toda requisição para /api/... cai aqui: descobre a rota, chama o método
   certo do Controlador e devolve JSON. Os erros também viram JSON. */
declare(strict_types=1);
require dirname(__DIR__) . '/backend/autoload.php';

const ROTAS = [
    // método  caminho (depois de /api)          método do Controlador
    ['POST', '#^/registro$#',                    'registrar'],
    ['POST', '#^/login$#',                       'entrar'],
    ['POST', '#^/logout$#',                      'sair'],
    ['GET',  '#^/usuario$#',                     'usuario'],
    ['POST', '#^/usuario/perfil$#',              'editarPerfil'],
    ['POST', '#^/usuario/salvar$#',              'salvarJogo'],
    ['GET',  '#^/usuario/save$#',                'carregarJogo'],
    ['GET',  '#^/usuario/inventario$#',          'inventario'],
    ['GET',  '#^/usuario/vendas$#',              'vendas'],
    ['GET',  '#^/usuario/historico$#',           'historico'],
    ['GET',  '#^/usuario/conquistas$#',          'conquistas'],
    ['GET',  '#^/usuario/diario$#',              'diario'],
    ['GET',  '#^/ranking$#',                     'ranking'],
    ['GET',  '#^/lojas/npcs$#',                  'npcs'],
    ['GET',  '#^/lojas/([a-z]+)/itens$#',        'itensDaLoja'],
    ['POST', '#^/lojas/([a-z]+)/comprar$#',      'comprar'],
    ['POST', '#^/lojas/([a-z]+)/vender$#',       'vender'],
];

function responder(array $dados, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

/* O front manda JSON (fetch) ou um formulário com arquivo (registro com foto). Aceita os dois. */
function lerCorpo(): array
{
    $json = json_decode(file_get_contents('php://input') ?: 'null', true);
    return is_array($json) ? $json : $_POST;
}

session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);

// "/api/lojas/willy/itens" → "/lojas/willy/itens" (funciona também se o site estiver numa subpasta)
$caminho = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '', '/');
$caminho = preg_replace('#^.*?/api(?=/|$)#', '', $caminho) ?: '/';

try {
    foreach (ROTAS as [$metodo, $padrao, $acao]) {
        if ($_SERVER['REQUEST_METHOD'] === $metodo && preg_match($padrao, $caminho, $partes)) {
            $controlador = new Controlador(Conexao::obter(), lerCorpo(), $_GET, $_FILES);
            responder($controlador->$acao(...array_slice($partes, 1)));
        }
    }
    responder(['ok' => false, 'erro' => 'Rota não encontrada.'], 404);
} catch (JogoException $erro) {
    // erro de regra do jogo (saldo, login...): a mensagem é para o usuário ver
    responder(['ok' => false, 'erro' => $erro->getMessage(), 'codigo' => $erro->codigo()], $erro->statusHttp());
} catch (ConfiguracaoException $erro) {
    error_log($erro->getMessage());
    responder(['ok' => false, 'erro' => $erro->getMessage()], 500);
} catch (Throwable $erro) {
    // erro inesperado: os detalhes vão para o terminal do servidor, não para o usuário
    error_log((string) $erro);
    responder(['ok' => false, 'erro' => 'Erro interno no servidor.'], 500);
}
