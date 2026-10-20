<?php
/* router.php – usado pelo servidor do PHP. Na pasta do projeto, rode:
       php -S localhost:8000 router.php
   e abra http://localhost:8000 no navegador.
   /api/... vai para a API; html, css, js e imagens são servidos normalmente;
   o .env, o .git e as pastas backend/ e database/ ficam bloqueados. */
$caminho = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');

if (preg_match('#(^|/)\.|^/(backend|database)(/|$)#', $caminho)) {
    http_response_code(404);
    return true;
}
if ($caminho === '/api' || str_starts_with($caminho, '/api/')) {
    require __DIR__ . '/api/index.php';
    return true;
}
return false;   // arquivo normal (index.html, css/..., js/...)
