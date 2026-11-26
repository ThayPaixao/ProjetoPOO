<?php
declare(strict_types=1);

/* RepositorioArquivo – base (abstrata) dos repositórios que guardam ARQUIVOS no disco.
   HERANÇA: FotoRepositorio (uploads/fotos) e SaveRepositorio (saves) herdam daqui o
   "onde fica a pasta" e o "cria a pasta se ainda não existir", que antes estavam repetidos.
   A raiz pode ser trocada no construtor: os testes usam uma pasta temporária,
   assim nunca mexem nas fotos e nos saves de verdade. */
abstract class RepositorioArquivo
{
    private string $raiz;

    public function __construct(?string $raiz = null)
    {
        $this->raiz = rtrim($raiz ?? dirname(__DIR__, 2), '/');    // padrão: a raiz do site
    }

    /* A pasta deste repositório, relativa à raiz (ex.: "saves"). Cada subclasse diz a sua. */
    abstract protected function pasta(): string;

    /* Caminho completo, no disco, de um arquivo desta pasta. */
    protected function caminhoNoDisco(string $nomeArquivo): string
    {
        return "{$this->raiz}/{$this->pasta()}/$nomeArquivo";
    }

    /* Cria a pasta na primeira vez (ela não vai para o git). */
    protected function garantirPasta(): void
    {
        $pasta = "{$this->raiz}/{$this->pasta()}";
        if (!is_dir($pasta) && !mkdir($pasta, 0775, true)) {
            throw new RuntimeException("Não foi possível criar a pasta $pasta.");
        }
    }
}
