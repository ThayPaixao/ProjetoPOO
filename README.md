# Site Stardew Valley – estrutura

```
index.html      tela principal (Entrar / Criar)
login.html      login
register.html   registro
home.html       home (depois do login)
perfil.html     perfil + inventário + histórico de vendas
lojas.html      comprar (NPCs) e vender
css/            base, components, auth, menu, home, itens, perfil, lojas
js/             menu, auth, api (dados), render (templates), perfil, lojas
assets/img/     placeholders (troque pelos arquivos reais)
```

## O que trocar depois
| O quê | Onde |
|---|---|
| Cores, fonte, imagem de fundo | `css/base.css` (bloco `:root`) |
| Logo | `index.html` (`src` da imagem) |
| Imagem do item / avatar / NPC | campo `imagem` vindo do banco (ou os `.svg` de `assets/img`) |
| Textos da home | `home.html` (`<p class="bloco__texto">`) |

## Preparado para o banco de dados
- Todas as telas buscam dados **só** em `js/api.js`. Cada função tem a rota sugerida
  no comentário. Troque o corpo por `fetch(...)` e o resto continua funcionando.
- Formulários já têm `action`, `method` e `name` (`email`, `senha`, `nome`, `confirmacao_senha`).
- Itens usam `<template>` (em `perfil.html` e `lojas.html`); `data-slot="campo"` marca onde cada coluna do banco entra.
- Modelo de item: `{ id, nome, preco, tipo, qualidade (0-3), imagem }`.

## Para abrir
Dê dois cliques em `index.html` (funciona sem servidor).
