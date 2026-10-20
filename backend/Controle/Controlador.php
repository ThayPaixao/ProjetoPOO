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
    private ItemRepositorio $itens;
    private NpcRepositorio $npcs;
    private JogadorRepositorio $jogadores;
    private TransacaoRepositorio $transacoes;

    public function __construct(PDO $pdo, array $corpo, array $consulta)
    {
        $this->pdo = $pdo;
        $this->corpo = $corpo;
        $this->consulta = $consulta;
        $this->itens = new ItemRepositorio($pdo);
        $this->npcs = new NpcRepositorio($pdo, $this->itens);
        $this->jogadores = new JogadorRepositorio($pdo);
        $this->transacoes = new TransacaoRepositorio($pdo);
    }

    // ---------- conta ----------

    /* POST /api/registro   { nome, email, senha, confirmacao_senha } */
    public function registrar(): array
    {
        if ($this->texto('senha') !== $this->texto('confirmacao_senha')) {
            throw new DadosInvalidosException('As senhas não são iguais.');
        }
        $jogador = Jogador::registrar($this->texto('nome'), $this->texto('email'), $this->texto('senha'));
        $this->jogadores->inserir($jogador);
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
        return ['ok' => true, 'usuario' => $jogador->paraArray()];
    }

    /* POST /api/logout */
    public function sair(): array
    {
        $_SESSION = [];
        session_destroy();
        return ['ok' => true];
    }

    // ---------- perfil ----------

    /* GET /api/usuario   → { id, nome, dinheiro, foto } */
    public function usuario(): array
    {
        return $this->jogadorLogado()->paraArray();
    }

    /* GET /api/usuario/inventario */
    public function inventario(): array
    {
        $unidades = $this->transacoes->inventario($this->idJogadorLogado());
        return array_map(fn(UnidadeItem $unidade) => $unidade->paraArray(), $unidades);
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

    /* GET /api/lojas/:npc/ofertas   → seu inventário com o preço que ESTE cliente paga */
    public function ofertas(string $idNpc): array
    {
        $cliente = $this->npcs->buscarCliente($idNpc);
        $unidades = $this->transacoes->inventario($this->idJogadorLogado());
        return array_map(fn(UnidadeItem $unidade) => [
            ...$unidade->paraArray(),
            'preco'      => $cliente->oferta($unidade),
            'preco_pago' => $unidade->getPrecoPago(),
            'sentimento' => $cliente->sentimentoPor($unidade->getItem())->nome(),
        ], $unidades);
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
            $this->jogadores->atualizarSaldo($jogador);
            $this->transacoes->salvarCompra($compra);
            return [
                'ok'       => true,
                'item'     => $unidade->paraArray(),
                'mensagem' => $compra->descricao(),
                'dinheiro' => $jogador->getSaldo(),
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
            $this->jogadores->atualizarSaldo($jogador);
            $this->transacoes->salvarVenda($venda);
            return [
                'ok'         => true,
                'valor'      => $venda->getValor(),
                'sentimento' => $venda->getSentimento()->nome(),
                'fala'       => $venda->getSentimento()->fala(),
                'mensagem'   => $venda->descricao(),
                'dinheiro'   => $jogador->getSaldo(),
            ];
        });
    }

    // ---------- ajudantes ----------

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
