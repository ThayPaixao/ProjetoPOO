# Site Stardew Valley – estrutura

```
index.html      tela principal (Entrar / Criar)
login.html      login
register.html   registro
home.html       home (depois do login)
perfil.html     perfil + inventário + histórico de vendas
lojas.html      comprar (NPCs) e vender
vila.html       ranking da vila, conquistas e diário de gostos
css/            base, components, auth, menu, home, itens, perfil, lojas, popup, vila...
js/             menu, auth, api (fala com o back-end), render (templates), perfil, lojas, vila,
                autosave + autosave-worker (thread)
assets/img/     itens/ (342 imagens) e npcs/ (34 retratos) do jogo + placeholders

api/index.php   API: recebe as chamadas do js/api.js e devolve JSON
backend/        classes PHP (POO)
  Modelo/         Item → Peixe, Colheita, Comida, Minerio, Coletavel, ProdutoAnimal, Artesanato | NPC → Vendedor, Cliente
                  Transacao → Compra, Venda | Jogador | UnidadeItem | Qualidade, Sentimento
                  Colecao<T> | Conquista, Estatisticas | QuadroDeAvisos + ObservadorDeTransacao (Observer)
  Excecao/        erros do jogo (JogoException → SaldoInsuficiente, NaoEncontrado...) e SaveCorrompido
  Repositorio/    conexão com o banco e as consultas SQL; RepositorioArquivo → FotoRepositorio, SaveRepositorio
  Controle/       Controlador: uma função para cada rota da API
  demo.php        mostra herança/polimorfismo/encapsulamento no terminal
testes/         testes automáticos (mini framework próprio): php testes/rodar.php
database/       schema.sql (tabelas), seed.sql (342 itens, 34 NPCs e gostos) e GOSTOS.md (quem ama/odeia o quê)
router.php      roteador do servidor do PHP
uploads/fotos/  fotos de perfil enviadas no cadastro (criada sozinha; não vai para o GitHub)
saves/          saves do jogo em JSON, um por jogador (criada sozinha; não vai para o GitHub)
```

## Branches e banco
| Branch | O que é | Banco (schema no Supabase) |
|---|---|---|
| `backend` | a versão **entregue ao professor** (tag `entrega-parte-2`); não mexer | `public` (o original) |
| `melhorias` | onde o trabalho continua | `melhorias` (uma cópia do original, feita em 27/11) |

As duas usam o mesmo Supabase e o mesmo `.env`, mas cada uma lê o seu schema: o que acontece na `melhorias`
(compras, contas, tabelas novas) não aparece na versão entregue. Quem escolhe o schema é o `Conexao.php`.
Nesta branch, o `schema.sql` e o `seed.sql` também só mexem na cópia. A cópia foi criada pelo
`database/copia-melhorias.sql` (rodar uma vez só; para refazer do zero, `DROP SCHEMA melhorias CASCADE` apaga só a cópia).
O `php backend/verificar_banco.php` mostra qual schema está em uso.

## Para abrir
Precisa do PHP (`sudo apt install php-cli php-pgsql php-mbstring`) e do arquivo `.env`.

1. Copie `.env.example` para `.env` e cole a URL do banco (Supabase → Connect → Direct → Session pooler).
   O `.env` tem a senha do banco: **não vai para o GitHub**.
2. Na pasta do projeto: `php -S localhost:8000 router.php`
3. Abra **http://localhost:8000** (dois cliques no html não funcionam: o navegador não chama a API assim).

Outros comandos:
- `php backend/verificar_banco.php` – confere a conexão com o banco e as regras dos gostos
- `php backend/demo.php` – demonstração das classes no terminal (não precisa de banco)
- `php testes/rodar.php` – roda os testes automáticos (não precisa de banco; veja "Testes" abaixo)

## Regras do jogo
- Na compra, a **qualidade** é sorteada (Normal 50%, Prata 30%, Ouro 15%, Irídio 5%) e multiplica o preço
  (×1, ×1,25, ×1,5, ×2). O **peixe** também sorteia o tamanho (10 a 50 cm): +2 g por cm acima de 10.
- Na venda, o NPC paga em cima do que **você pagou**: ama = +50%, neutro = igual, odeia = −25%.
  Como no jogo, o site não mostra o gosto antes: você descobre vendendo (e o histórico do perfil lembra).
- Todo jogador começa com 1000 g.
- **Lojas (5):** Pierre (colheitas, coletáveis, conservas), Gus (comidas e bebidas), Willy (peixes e coisas da praia),
  Clint (minerais, minérios e barras) e Marnie (produtos de animais, queijos, maionese e tecido). Lojista só vende.
- **Moradores (29)** compram de você; cada um tem seus gostos (versão 4 do `GOSTOS.md`).
  Na aba **Comprar**, a loja tem **busca** (sem acento e sem maiúscula) e **filtro por categoria**; na aba Vender, não.
- **Ranking da vila**: quem mais lucrou vendendo (lucro = o que recebeu − o que pagou). Contas de teste
  (e-mail `@exemplo.com`) ficam de fora.
- **Conquistas**: 8 medalhas (primeira compra, irídio, peixe de 45 cm, comprar em todas as lojas, vender para todos os
  moradores, 1.000 g de lucro...). As metas de lojas e moradores vêm do banco: crescem junto com a vila. Quando uma compra/venda libera uma, o pop-up da loja avisa.
- **Diário de gostos**: na página da vila, o que você já descobriu sobre cada morador (só o que já vendeu).

## API (usada pelo `js/api.js`)
| Rota | O que faz |
|---|---|
| `POST /api/registro`, `/api/login`, `/api/logout` | conta (sessão com cookie) |
| `GET /api/usuario` | `{ id, nome, dinheiro, foto }` |
| `GET /api/usuario/inventario`, `/vendas`, `/historico` | itens do jogador |
| `GET /api/lojas/npcs?modo=comprar\|vender` | vendedores ou clientes |
| `GET /api/lojas/:npc/itens` | catálogo do vendedor |
| `POST /api/lojas/:npc/comprar` e `/vender` | `{ itemId }` → resposta inclui `conquistas` (as que foram liberadas) |
| `GET /api/usuario/conquistas` | todas as conquistas, com `progresso` e `meta` |
| `GET /api/usuario/diario` | cada morador com os gostos já descobertos |
| `GET /api/ranking` | os 10 que mais lucraram (`voce: true` marca o jogador logado) |

Erros voltam como `{ ok: false, erro: "mensagem para o usuário", codigo: "saldo_insuficiente" }`.
O front decide o que fazer pelo `codigo` (que não muda), nunca pelo texto da mensagem.

## O que trocar depois
| O quê | Onde |
|---|---|
| Cores, fonte, imagem de fundo | `css/base.css` (bloco `:root`) |
| Logo | `index.html` (`src` da imagem) |
| Imagem do item / avatar / NPC | coluna `foto` no banco (ou os `.svg` de `assets/img`) |
| Textos da home | `home.html` (`<p class="bloco__texto">`) |

## Como o front usa os dados
- Todas as telas buscam dados **só** em `js/api.js`.
- Itens usam `<template>` (em `perfil.html` e `lojas.html`); `data-slot="campo"` marca onde cada campo entra
  (campo que o item não tem, como `tamanho_cm` fora dos peixes, fica escondido).
- Modelo de item: `{ id, nome, preco, tipo, qualidade (0-3), imagem, tamanho_cm }`.

## Requisitos do trabalho – onde está cada um
| Requisito | Onde |
|---|---|
| Classes, herança, polimorfismo, encapsulamento | `backend/Modelo/` (`Item` → `Peixe`/`Colheita`/`Comida`/`Minerio`, `NPC` → `Vendedor`/`Cliente`, `Transacao` → `Compra`/`Venda`, `Jogador`); `php backend/demo.php` mostra tudo |
| **Persistência** (salvar na saída, carregar na volta) | `backend/Repositorio/SaveRepositorio.php` grava o jogo em `saves/jogador-<id>.json`; o logout salva antes de sair e o login/perfil carregam o último save |
| **Serialização** | as classes implementam `JsonSerializable` (`jsonSerialize()`); na volta, `UnidadeItem::deArray()` + `Item::criar()` reconstroem os objetos a partir do JSON |
| **Threads** (auto-save) | `js/autosave-worker.js`: um Web Worker (thread separada da tela) salva a cada 30 s; `js/autosave.js` liga a thread e salva também ao sair da página |
| Entrada/Saída | save em arquivo, upload da foto (`FotoRepositorio`), leitura do `.env`; os dois repositórios de arquivo herdam de `RepositorioArquivo` |
| Tratamento de erros | exceções do jogo (`backend/Excecao/`), cada uma com `statusHttp()` e `codigo()` (polimorfismo); `try/catch` da API; transação com rollback na compra/venda; save corrompido vira `SaveCorrompidoException`, vai para o log e não derruba o site |
| Padrões de projeto | **Factory** (`Item::criar`, `NpcRepositorio::criar`), **Repository** (`backend/Repositorio/`), **Singleton** (`Conexao`), **Template Method** (`Transacao::descricao()`), **Front Controller** (`api/index.php` → `Controlador`), **Observer** (`QuadroDeAvisos` publica cada compra/venda; `ObservadorDeConquistas` escuta e descobre as conquistas novas) |
| Objeto imutável | `Estatisticas` (propriedades `readonly`): `comCompra()`/`comVenda()` devolvem um objeto novo, o que deixa comparar o antes e o depois de uma transação |
| **Testes automáticos** | `testes/`: mini framework próprio (`Teste` é a classe base; acha os métodos `teste...` por reflexão). Rode `php testes/rodar.php` |
| **Tipos genéricos** | `backend/Modelo/Colecao.php`: `Colecao<T>` (anotações `@template`) só aceita objetos do tipo escolhido; usada como `Colecao<Item>` (catálogo), `Colecao<Sentimento>` (gostos) e `Colecao<UnidadeItem>` (inventário) |
| Collections | a `Colecao` (implementa `IteratorAggregate`, `Countable`, `JsonSerializable`: dá para usar em `foreach`, `count()` e `json_encode`) + mapas e listas do histórico |

## Testes
`php testes/rodar.php` roda todos os testes em ~2 s, sem banco e sem internet (o save é testado numa pasta
temporária). Cada arquivo `testes/*Teste.php` é uma classe que herda de `Teste`; cada método que começa com
`teste` é um caso. Para criar um teste novo, basta escrever outro método (ou outro arquivo `XxxTeste.php`):

```php
public function testeDebitarTiraDoSaldo(): void
{
    $jogador = new Jogador(1, 'Ana', 'ana@exemplo.com', 'x', 1000);
    $jogador->debitar(300);
    $this->verificarIgual(700, $jogador->getSaldo(), 'saldo');
}
```

Verificações disponíveis: `verificar(condição, mensagem)`, `verificarIgual(esperado, obtido)` e
`verificarErro(Classe::class, fn() => ...)`. O comando termina com erro (código 1) se algum teste falhar.

## Créditos
Imagens dos itens e retratos dos NPCs: [Stardew Valley Wiki](https://stardewvalleywiki.com/) — Stardew Valley © ConcernedApe. Uso apenas educacional.
