<?php
/* router.php – usado pelo servidor do PHP. Na pasta do projeto, rode:
       php -S localhost:8000 router.php
   e abra http://localhost:8000 no navegador.
   /api/... vai para a API; html, css, js e imagens são servidos normalmente;
   o .env, o .git e as pastas backend/, database/, saves/ e testes/ ficam bloqueados (e nada em uploads/ roda como PHP). */
$caminho = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');

if (preg_match('#(^|/)\.|^/(backend|database|saves|testes)(/|$)|^/uploads/.*\.php#i', $caminho)) {
    http_response_code(404);
    return true;
}
if ($caminho === '/api' || str_starts_with($caminho, '/api/')) {
    require __DIR__ . '/api/index.php';
    return true;
}
return false;   // arquivo normal (index.html, css/..., js/...)
