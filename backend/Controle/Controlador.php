<?php
declare(strict_types=1);

/* Controlador – a ponte entre a API (api/index.php) e as classes do jogo.
   Cada método público é uma rota do js/api.js: lê os dados da requisição,
   usa os modelos e repositórios, e devolve um array que vira JSON. */
class Controlador
{
    private PDO $pdo;
    private array $corpo;       // dados enviados pelo front (JSON ou formulário)
    private array $consulta;    // parâmetros da URL (?modo=...)
    private array $arquivos;    // arquivos enviados ($_FILES), ex.: a foto do registro
    private ItemRepositorio $itens;
    private NpcRepositorio $npcs;
    private JogadorRepositorio $jogadores;
    private TransacaoRepositorio $transacoes;
    private FotoRepositorio $fotos;
    private SaveRepositorio $saves;
    private QuadroDeAvisos $avisos;                  // PADRÃO OBSERVER: toda compra e venda é publicada aqui
    private ObservadorDeConquistas $conquistas;      // ... e este observador vê quais conquistas ela liberou

    public function __construct(PDO $pdo, array $corpo, array $consulta, array $arquivos = [])
    {
        $this->pdo = $pdo;
        $this->corpo = $corpo;
        $this->consulta = $consulta;
        $this->arquivos = $arquivos;
        $this->fotos = new FotoRepositorio();
        $this->saves = new SaveRepositorio();
        $this->itens = new ItemRepositorio($pdo);
        $this->npcs = new NpcRepositorio($pdo, $this->itens);
        $this->jogadores = new JogadorRepositorio($pdo);
        $this->transacoes = new TransacaoRepositorio($pdo);
        $this->conquistas = new ObservadorDeConquistas(fn(int $id) => $this->transacoes->estatisticas($id));
        $this->avisos = new QuadroDeAvisos();
        $this->avisos->inscrever($this->conquistas);
    }

    // ---------- conta ----------

    /* POST /api/registro   { nome, email, senha, confirmacao_senha, foto? }  (formulário com arquivo) */
    public function registrar(): array
    {
        if ($this->texto('senha') !== $this->texto('confirmacao_senha')) {
            throw new DadosInvalidosException('As senhas não são iguais.');
        }
        $jogador = Jogador::registrar($this->texto('nome'), $this->texto('email'), $this->texto('senha'));   // valida antes de gravar qualquer arquivo
        $this->gravarComFoto($jogador, fn() => $this->jogadores->inserir($jogador));
        $this->iniciarSessao($jogador);
        return ['ok' => true, 'usuario' => $jogador->paraArray()];
    }

    /* POST /api/login   { email, senha } */
    public function entrar(): array
    {
        $jogador = $this->jogadores->buscarPorEmail($this->texto('email'));
        if ($jogador === null || !$jogador->verificarSenha($this->texto('senha'))) {
            throw new NaoAutenticadoException('E-mail ou senha incorretos.');
        }
        $this->iniciarSessao($jogador);
        $save = $this->saves->carregar((int) $jogador->getId());          // CARREGAR NA VOLTA
        return [
            'ok'          => true,
            'usuario'     => $jogador->paraArray(),
            'ultimo_save' => $save ? $save['salvo_em']->format(DATE_ATOM) : null,
        ];
    }

    /* POST /api/logout – salva o jogo antes de sair (SALVAR NA SAÍDA) */
    public function sair(): array
    {
        if (is_int($_SESSION['id_jogador'] ?? null)) {
            try {
                $this->salvarJogo();
            } catch (Throwable $erro) {
                error_log('Falha no save da saída: ' . $erro->getMessage());   // não impede o logout
            }
        }
        $_SESSION = [];
        session_destroy();
        return ['ok' => true];
    }

    // ---------- save do jogo (persistência em arquivo JSON) ----------

    /* POST /api/usuario/salvar – chamado pela THREAD de auto-save (a cada 30 s) e na saída da página */
    public function salvarJogo(): array
    {
        $jogador = $this->jogadorLogado();
        $id = (int) $jogador->getId();
        $resultado = $this->saves->salvar($jogador, $this->transacoes->inventario($id), $this->transacoes->vendas($id));
        return ['ok' => true, ...$resultado];
    }

    /* GET /api/usuario/save – lê o último save do arquivo (objetos reconstruídos a partir do JSON) */
    public function carregarJogo(): array
    {
        $save = $this->saves->carregar($this->idJogadorLogado());
        if ($save === null) {
            return ['existe' => false];
        }
        return [
            'existe'     => true,
            'salvo_em'   => $save['salvo_em']->format(DATE_ATOM),
            'arquivo'    => $save['arquivo'],
            'dinheiro'   => $save['dinheiro'],
            'inventario' => $save['inventario'],     // Colecao<UnidadeItem>: o json_encode usa o jsonSerialize()
            'vendas'     => count($save['vendas']),
        ];
    }

    // ---------- perfil ----------

    /* POST /api/usuario/perfil   FormData { nome?, senha_atual?, nova_senha?, confirmacao_senha?, foto? } */
    public function editarPerfil(): array
    {
        $jogador = $this->jogadorLogado();
        if ($this->texto('nome') !== '') {
            $jogador->alterarNome($this->texto('nome'));
        }
        if ($this->texto('nova_senha') !== '') {
            if ($this->texto('nova_senha') !== $this->texto('confirmacao_senha')) {
                throw new DadosInvalidosException('A nova senha e a confirmação não são iguais.');
            }
            $jogador->alterarSenha($this->texto('senha_atual'), $this->texto('nova_senha'));
        }
        $fotoAntiga = $jogador->getFoto();
        $fotoNova = $this->gravarComFoto($jogador, fn() => $this->jogadores->atualizarPerfil($jogador));
        if ($fotoNova !== null && $fotoAntiga !== null) {
            $this->fotos->apagar($fotoAntiga);        // a foto velha não é mais usada
        }
        return ['ok' => true, 'usuario' => $jogador->paraArray()];
    }

    /* GET /api/usuario   → { id, nome, dinheiro, foto } */
    public function usuario(): array
    {
        return $this->jogadorLogado()->paraArray();
    }

    /* GET /api/usuario/inventario */
    public function inventario(): array
    {
        $unidades = $this->transacoes->inventario($this->idJogadorLogado());   // Colecao<UnidadeItem>
        return $unidades->mapear(fn(UnidadeItem $unidade) => $unidade->paraArray());
    }

    /* GET /api/usuario/vendas */
    public function vendas(): array
    {
        return $this->transacoes->vendas($this->idJogadorLogado());
    }

    /* GET /api/usuario/historico   (compras e vendas) */
    public function historico(): array
    {
        return $this->transacoes->historico($this->idJogadorLogado());
    }

    // ---------- lojas ----------

    /* GET /api/lojas/npcs?modo=comprar|vender */
    public function npcs(): array
    {
        $tipo = match ($this->consulta['modo'] ?? '') {
            'comprar' => 'VENDEDOR',
            'vender'  => 'CLIENTE',
            default   => throw new DadosInvalidosException('Use modo=comprar ou modo=vender.'),
        };
        return array_map(fn(NPC $npc) => $npc->paraArray(), $this->npcs->listar($tipo));
    }

    /* GET /api/lojas/:npc/itens   → catálogo de um vendedor */
    public function itensDaLoja(string $idNpc): array
    {
        $vendedor = $this->npcs->buscarVendedor($idNpc);
        return array_map(fn(Item $item) => $item->paraArray(), $vendedor->getCatalogo());
    }

    /* POST /api/lojas/:npc/comprar   { itemId }  (id do item no catálogo) */
    public function comprar(string $idNpc): array
    {
        $idItem = $this->inteiro('itemId');
        return $this->emTransacao(function () use ($idNpc, $idItem) {
            $jogador = $this->jogadorLogado(travar: true);
            $vendedor = $this->npcs->buscarVendedor($idNpc);
            $unidade = $vendedor->vender($this->itens->buscar($idItem));   // sorteia qualidade (e tamanho)
            $compra = new Compra($jogador, $unidade, $vendedor);
            $compra->executar();                                           // debita ou lança SaldoInsuficiente
            $this->avisos->publicar($compra);                              // avisa os observadores (conquistas)
            $this->jogadores->atualizarSaldo($jogador);
            $this->transacoes->salvarCompra($compra);
            return [
                'ok'         => true,
                'item'       => $unidade->paraArray(),
                'npc'        => $vendedor->paraArray(),
                'fala'       => $vendedor->fala(),
                'mensagem'   => $compra->descricao(),
                'dinheiro'   => $jogador->getSaldo(),
                'conquistas' => $this->conquistasNovas(),
            ];
        });
    }

    /* POST /api/lojas/:npc/vender   { itemId }  (id da unidade no inventário) */
    public function vender(string $idNpc): array
    {
        $idUnidade = $this->inteiro('itemId');
        return $this->emTransacao(function () use ($idNpc, $idUnidade) {
            $jogador = $this->jogadorLogado(travar: true);
            $cliente = $this->npcs->buscarCliente($idNpc);
            $unidade = $this->transacoes->buscarNoInventario($jogador->getId(), $idUnidade);
            $venda = new Venda($jogador, $unidade, $cliente);
            $venda->executar();
            $this->avisos->publicar($venda);
            $this->jogadores->atualizarSaldo($jogador);
            $this->transacoes->salvarVenda($venda);
            return [
                'ok'         => true,
                'item'       => $unidade->paraArray(),
                'npc'        => $cliente->paraArray(),
                'valor'      => $venda->getValor(),
                'lucro'      => $venda->getValor() - $unidade->getPrecoPago(),
                'sentimento' => $venda->getSentimento()->nome(),
                'fala'       => $cliente->reacao($unidade->getItem()),
                'mensagem'   => $venda->descricao(),
                'dinheiro'   => $jogador->getSaldo(),
                'conquistas' => $this->conquistasNovas(),
            ];
        });
    }

    // ---------- vila: conquistas, diário de gostos e ranking ----------

    /* GET /api/usuario/conquistas   → todas as conquistas, com o progresso do jogador */
    public function conquistas(): array
    {
        $estatisticas = $this->transacoes->estatisticas($this->idJogadorLogado());
        return array_map(fn(Conquista $conquista) => $conquista->paraArray($estatisticas), Conquista::cases());
    }

    /* GET /api/usuario/diario   → cada morador com os gostos que o jogador já descobriu vendendo */
    public function diario(): array
    {
        $descobertas = $this->transacoes->diario($this->idJogadorLogado());
        return array_map(fn(NPC $npc) => [...$npc->paraArray(), 'itens' => $descobertas[$npc->getId()] ?? []],
                         $this->npcs->listar('CLIENTE'));
    }

    /* GET /api/ranking   → os 10 que mais lucraram vendendo; "voce" marca o jogador logado */
    public function ranking(): array
    {
        $idLogado = $this->idJogadorLogado();
        $ranking = $this->jogadores->ranking();
        return array_map(fn(array $linha, int $i) => [
            'posicao' => $i + 1,
            'nome'    => $linha['nome'],
            'foto'    => $linha['foto'],
            'vendas'  => $linha['vendas'],
            'lucro'   => $linha['lucro'],
            'voce'    => $linha['id'] === $idLogado,
        ], $ranking, array_keys($ranking));
    }

    // ---------- ajudantes ----------

    /* As conquistas que a compra/venda desta requisição liberou, no formato do front. */
    private function conquistasNovas(): array
    {
        return array_map(fn(Conquista $c) => ['id' => $c->value, 'nome' => $c->nome(), 'descricao' => $c->descricao()],
                         $this->conquistas->novas());
    }

    /* Ou tudo dá certo (saldo + compra/venda salvos), ou nada é salvo. */
    private function emTransacao(callable $acao): array
    {
        $this->pdo->beginTransaction();
        try {
            $resultado = $acao();
            $this->pdo->commit();
            return $resultado;
        } catch (Throwable $erro) {
            $this->pdo->rollBack();
            throw $erro;
        }
    }

    /* Usado no registro e na edição do perfil. Se veio uma foto no formulário, grava no disco e
       coloca no jogador; depois roda $gravar (que salva o jogador no banco). Se o banco falhar,
       apaga a foto nova, para não deixar arquivo sobrando na pasta. Devolve a foto nova (ou null). */
    private function gravarComFoto(Jogador $jogador, callable $gravar): ?string
    {
        $foto = null;
        $arquivo = $this->arquivos['foto'] ?? null;
        if (is_array($arquivo) && ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $foto = $this->fotos->salvar($arquivo);
            $jogador->definirFoto($foto);
        }
        try {
            $gravar();
        } catch (Throwable $erro) {
            if ($foto !== null) {
                $this->fotos->apagar($foto);
            }
            throw $erro;
        }
        return $foto;
    }

    private function iniciarSessao(Jogador $jogador): void
    {
        session_regenerate_id(true);     // novo id de sessão a cada login (segurança)
        $_SESSION['id_jogador'] = $jogador->getId();
    }

    private function idJogadorLogado(): int
    {
        $id = $_SESSION['id_jogador'] ?? null;
        if (!is_int($id)) {
            throw new NaoAutenticadoException();
        }
        return $id;
    }

    private function jogadorLogado(bool $travar = false): Jogador
    {
        $jogador = $this->jogadores->buscarPorId($this->idJogadorLogado(), $travar);
        if ($jogador === null) {          // a conta não existe mais (ex.: o banco foi recriado)
            $_SESSION = [];
            throw new NaoAutenticadoException();
        }
        return $jogador;
    }

    private function texto(string $campo): string
    {
        $valor = $this->corpo[$campo] ?? '';
        return is_string($valor) ? $valor : '';
    }

    private function inteiro(string $campo): int
    {
        $valor = filter_var($this->corpo[$campo] ?? null, FILTER_VALIDATE_INT);
        if ($valor === false) {
            throw new DadosInvalidosException("Campo \"$campo\" inválido.");
        }
        return $valor;
    }
}
