<?php
declare(strict_types=1);

/* FotoRepositorio – guarda as fotos de perfil enviadas no cadastro.
   ENTRADA/SAÍDA: recebe o arquivo que veio do formulário e grava na pasta uploads/fotos. */
class FotoRepositorio extends RepositorioArquivo
{
    public const TAMANHO_MAXIMO = 2 * 1024 * 1024;     // 2 MB (o mesmo limite do PHP)
    private const PASTA = 'uploads/fotos';               // relativa à raiz do site
    private const TIPOS = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    protected function pasta(): string
    {
        return self::PASTA;
    }

    /* Recebe um item de $_FILES e devolve o caminho salvo (ex.: "uploads/fotos/3f9a….png"). */
    public function salvar(array $arquivo): string
    {
        $erro = $arquivo['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($erro === UPLOAD_ERR_INI_SIZE || $erro === UPLOAD_ERR_FORM_SIZE || ($arquivo['size'] ?? 0) > self::TAMANHO_MAXIMO) {
            throw new DadosInvalidosException('A foto pode ter no máximo 2 MB.');
        }
        if ($erro !== UPLOAD_ERR_OK || !is_uploaded_file($arquivo['tmp_name'])) {
            throw new DadosInvalidosException('Não foi possível receber a foto. Tente de novo.');
        }
        // confere o CONTEÚDO do arquivo, não o nome: um "foto.png" que não é imagem é recusado
        $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
        $extensao = self::TIPOS[$tipo] ?? throw new DadosInvalidosException('A foto precisa ser PNG, JPG, WEBP ou GIF.');

        $this->garantirPasta();
        $nome = bin2hex(random_bytes(16)) . ".$extensao";  // nome aleatório: quem envia não escolhe o nome no servidor
        if (!move_uploaded_file($arquivo['tmp_name'], $this->caminhoNoDisco($nome))) {
            throw new RuntimeException('Falha ao gravar a foto no disco.');
        }
        return self::PASTA . "/$nome";
    }

    /* Apaga uma foto salva (usado quando o cadastro falha depois de a foto já ter sido gravada).
       O basename() garante que só apaga dentro de uploads/fotos (nada de "../"). */
    public function apagar(string $caminho): void
    {
        $arquivo = $this->caminhoNoDisco(basename($caminho));
        if (str_starts_with($caminho, self::PASTA . '/') && is_file($arquivo)) {
            unlink($arquivo);
        }
    }
}
