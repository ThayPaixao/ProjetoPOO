# Site Stardew Valley – estrutura

```
index.html      tela principal (Entrar / Criar)
login.html      login
register.html   registro
home.html       home (depois do login)
perfil.html     perfil + inventário + histórico de vendas
lojas.html      comprar (NPCs) e vender
css/            base, components, auth, menu, home, itens, perfil, lojas
js/             menu, auth, api (fala com o back-end), render (templates), perfil, lojas
assets/img/     placeholders (troque pelos arquivos reais)

api/index.php   API: recebe as chamadas do js/api.js e devolve JSON
backend/        classes PHP (POO)
  Modelo/         Item → Peixe, Colheita, Comida, Minerio | NPC → Vendedor, Cliente
                  Transacao → Compra, Venda | Jogador | UnidadeItem | Qualidade, Sentimento
  Excecao/        erros do jogo (JogoException → SaldoInsuficiente, NaoEncontrado...)
  Repositorio/    conexão com o banco e as consultas SQL
  Controle/       Controlador: uma função para cada rota da API
  demo.php        mostra herança/polimorfismo/encapsulamento no terminal
database/       schema.sql (tabelas) e seed.sql (itens, NPCs e gostos) para o Supabase
router.php      roteador do servidor do PHP
```

## Para abrir
Precisa do PHP (`sudo apt install php-cli php-pgsql php-mbstring`) e do arquivo `.env`.

1. Copie `.env.example` para `.env` e cole a URL do banco (Supabase → Connect → Direct → Session pooler).
   O `.env` tem a senha do banco: **não vai para o GitHub**.
2. Na pasta do projeto: `php -S localhost:8000 router.php`
3. Abra **http://localhost:8000** (dois cliques no html não funcionam: o navegador não chama a API assim).

Outros comandos:
- `php backend/verificar_banco.php` – confere se a conexão com o banco está certa
- `php backend/demo.php` – demonstração das classes no terminal (não precisa de banco)

## Regras do jogo
- Na compra, a **qualidade** é sorteada (Normal 50%, Prata 30%, Ouro 15%, Irídio 5%) e multiplica o preço
  (×1, ×1,25, ×1,5, ×2). O **peixe** também sorteia o tamanho (10 a 50 cm): +2 g por cm acima de 10.
- Na venda, o NPC paga em cima do que **você pagou**: ama = +50%, neutro = igual, odeia = −25%.
- Todo jogador começa com 1000 g.

## API (usada pelo `js/api.js`)
| Rota | O que faz |
|---|---|
| `POST /api/registro`, `/api/login`, `/api/logout` | conta (sessão com cookie) |
| `GET /api/usuario` | `{ id, nome, dinheiro, foto }` |
| `GET /api/usuario/inventario`, `/vendas`, `/historico` | itens do jogador |
| `GET /api/lojas/npcs?modo=comprar\|vender` | vendedores ou clientes |
| `GET /api/lojas/:npc/itens` | catálogo do vendedor |
| `GET /api/lojas/:npc/ofertas` | seu inventário com o preço que esse NPC paga |
| `POST /api/lojas/:npc/comprar` e `/vender` | `{ itemId }` |

Erros voltam como `{ ok: false, erro: "mensagem" }`.

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
- Modelo de item: `{ id, nome, preco, tipo, qualidade (0-3), imagem, tamanho_cm }`
  (+ `sentimento` e `preco_pago` na aba Vender).
