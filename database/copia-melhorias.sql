-- copia-melhorias.sql – cria a CÓPIA do banco usada pela branch "melhorias" (feita em 27/11/2026).
--
-- Por quê: as duas branches usam o mesmo Supabase. A versão entregue ao professor (branch "backend")
-- usa o schema "public" e NÃO pode mudar. A branch "melhorias" usa o schema "melhorias" (uma área
-- separada dentro do mesmo banco), então dá para mudar tabelas e dados à vontade sem afetar a entrega.
--
-- O que faz: cria o schema "melhorias", cria nele as mesmas tabelas do schema.sql e copia TODOS os
-- dados do original (itens, gostos, jogadores, compras e vendas). Só tem CREATE e INSERT: o "public"
-- é apenas lido. Tudo roda numa transação: se algo der errado, nada fica pela metade.
--
-- Rodar UMA vez (Supabase → SQL Editor → colar → Run). Se o schema "melhorias" já existir, dá erro e
-- nada é feito. Para refazer a cópia do zero: DROP SCHEMA melhorias CASCADE;  (apaga SÓ a cópia).

BEGIN;

CREATE SCHEMA melhorias;

-- ---------- tabelas (iguais às do schema.sql) ----------

CREATE TABLE melhorias.classificacao (
    id_classificacao SMALLINT PRIMARY KEY,
    nome             TEXT NOT NULL UNIQUE
);

CREATE TABLE melhorias.qualidade (
    id_qualidade SMALLINT PRIMARY KEY,
    nome         TEXT NOT NULL UNIQUE
);

CREATE TABLE melhorias.sentimento (
    id_sentimento SMALLINT PRIMARY KEY,
    nome          TEXT NOT NULL UNIQUE
);

CREATE TABLE melhorias.npc (
    id_npc TEXT PRIMARY KEY,
    nome   TEXT NOT NULL,
    foto   TEXT,
    tipo   TEXT NOT NULL CHECK (tipo IN ('VENDEDOR', 'CLIENTE'))
);

CREATE TABLE melhorias.loja (
    id_loja SMALLINT PRIMARY KEY,
    nome    TEXT NOT NULL,
    foto    TEXT,
    id_npc  TEXT NOT NULL UNIQUE REFERENCES melhorias.npc (id_npc)
);

CREATE TABLE melhorias.item (
    id_item          SERIAL PRIMARY KEY,
    nome             TEXT NOT NULL UNIQUE,
    preco_base       INTEGER NOT NULL CHECK (preco_base > 0),
    foto             TEXT,
    id_classificacao SMALLINT NOT NULL REFERENCES melhorias.classificacao (id_classificacao),
    id_loja          SMALLINT NOT NULL REFERENCES melhorias.loja (id_loja)
);

CREATE TABLE melhorias.npc_sentimento (
    id_npc        TEXT     NOT NULL REFERENCES melhorias.npc (id_npc),
    id_item       INTEGER  NOT NULL REFERENCES melhorias.item (id_item),
    id_sentimento SMALLINT NOT NULL REFERENCES melhorias.sentimento (id_sentimento) CHECK (id_sentimento <> 2),
    PRIMARY KEY (id_npc, id_item)
);

CREATE TABLE melhorias.jogador (
    id_jogador SERIAL PRIMARY KEY,
    nome       TEXT NOT NULL,
    email      TEXT NOT NULL UNIQUE,
    senha_hash TEXT NOT NULL,
    saldo      INTEGER NOT NULL CHECK (saldo >= 0),
    foto       TEXT,
    criado_em  TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE melhorias.compra (
    id_compra    SERIAL PRIMARY KEY,
    id_jogador   INTEGER  NOT NULL REFERENCES melhorias.jogador (id_jogador),
    id_item      INTEGER  NOT NULL REFERENCES melhorias.item (id_item),
    id_qualidade SMALLINT NOT NULL REFERENCES melhorias.qualidade (id_qualidade),
    tamanho_cm   SMALLINT CHECK (tamanho_cm BETWEEN 10 AND 50),
    preco_pago   INTEGER  NOT NULL CHECK (preco_pago > 0),
    data         TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE melhorias.venda (
    id_venda      SERIAL PRIMARY KEY,
    id_compra     INTEGER  NOT NULL UNIQUE REFERENCES melhorias.compra (id_compra),
    id_npc        TEXT     NOT NULL REFERENCES melhorias.npc (id_npc),
    id_sentimento SMALLINT NOT NULL REFERENCES melhorias.sentimento (id_sentimento),
    preco_venda   INTEGER  NOT NULL CHECK (preco_venda > 0),
    data          TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX ON melhorias.compra (id_jogador);

-- ---------- dados: copiados do original, na ordem das chaves estrangeiras ----------

INSERT INTO melhorias.classificacao (id_classificacao, nome)
    SELECT id_classificacao, nome FROM public.classificacao;
INSERT INTO melhorias.qualidade (id_qualidade, nome)
    SELECT id_qualidade, nome FROM public.qualidade;
INSERT INTO melhorias.sentimento (id_sentimento, nome)
    SELECT id_sentimento, nome FROM public.sentimento;
INSERT INTO melhorias.npc (id_npc, nome, foto, tipo)
    SELECT id_npc, nome, foto, tipo FROM public.npc;
INSERT INTO melhorias.loja (id_loja, nome, foto, id_npc)
    SELECT id_loja, nome, foto, id_npc FROM public.loja;
INSERT INTO melhorias.item (id_item, nome, preco_base, foto, id_classificacao, id_loja)
    SELECT id_item, nome, preco_base, foto, id_classificacao, id_loja FROM public.item;
INSERT INTO melhorias.npc_sentimento (id_npc, id_item, id_sentimento)
    SELECT id_npc, id_item, id_sentimento FROM public.npc_sentimento;
INSERT INTO melhorias.jogador (id_jogador, nome, email, senha_hash, saldo, foto, criado_em)
    SELECT id_jogador, nome, email, senha_hash, saldo, foto, criado_em FROM public.jogador;
INSERT INTO melhorias.compra (id_compra, id_jogador, id_item, id_qualidade, tamanho_cm, preco_pago, data)
    SELECT id_compra, id_jogador, id_item, id_qualidade, tamanho_cm, preco_pago, data FROM public.compra;
INSERT INTO melhorias.venda (id_venda, id_compra, id_npc, id_sentimento, preco_venda, data)
    SELECT id_venda, id_compra, id_npc, id_sentimento, preco_venda, data FROM public.venda;

-- Os ids foram copiados à mão, então os contadores (SERIAL) precisam continuar depois do maior id.
SELECT setval(pg_get_serial_sequence('melhorias.item', 'id_item'),       coalesce(max(id_item), 0) + 1, false)    FROM melhorias.item;
SELECT setval(pg_get_serial_sequence('melhorias.jogador', 'id_jogador'), coalesce(max(id_jogador), 0) + 1, false) FROM melhorias.jogador;
SELECT setval(pg_get_serial_sequence('melhorias.compra', 'id_compra'),   coalesce(max(id_compra), 0) + 1, false)  FROM melhorias.compra;
SELECT setval(pg_get_serial_sequence('melhorias.venda', 'id_venda'),     coalesce(max(id_venda), 0) + 1, false)   FROM melhorias.venda;

-- ---------- views (iguais às do schema.sql, mas apontando para as tabelas da cópia) ----------

CREATE VIEW melhorias.inventario WITH (security_invoker = true) AS
SELECT c.*
FROM melhorias.compra c
LEFT JOIN melhorias.venda v ON v.id_compra = c.id_compra
WHERE v.id_venda IS NULL;

CREATE VIEW melhorias.historico WITH (security_invoker = true) AS
SELECT c.id_jogador, 'COMPRA' AS tipo, c.id_compra, c.id_item, c.id_qualidade, c.tamanho_cm,
       l.id_npc, NULL::SMALLINT AS id_sentimento, c.preco_pago AS valor, c.data
FROM melhorias.compra c
JOIN melhorias.item i ON i.id_item = c.id_item
JOIN melhorias.loja l ON l.id_loja = i.id_loja
UNION ALL
SELECT c.id_jogador, 'VENDA', c.id_compra, c.id_item, c.id_qualidade, c.tamanho_cm,
       v.id_npc, v.id_sentimento, v.preco_venda, v.data
FROM melhorias.venda v
JOIN melhorias.compra c ON c.id_compra = v.id_compra;

-- ---------- segurança: igual ao original (a chave pública do Supabase não lê nada) ----------
ALTER TABLE melhorias.classificacao  ENABLE ROW LEVEL SECURITY;
ALTER TABLE melhorias.qualidade      ENABLE ROW LEVEL SECURITY;
ALTER TABLE melhorias.sentimento     ENABLE ROW LEVEL SECURITY;
ALTER TABLE melhorias.npc            ENABLE ROW LEVEL SECURITY;
ALTER TABLE melhorias.loja           ENABLE ROW LEVEL SECURITY;
ALTER TABLE melhorias.item           ENABLE ROW LEVEL SECURITY;
ALTER TABLE melhorias.npc_sentimento ENABLE ROW LEVEL SECURITY;
ALTER TABLE melhorias.jogador        ENABLE ROW LEVEL SECURITY;
ALTER TABLE melhorias.compra         ENABLE ROW LEVEL SECURITY;
ALTER TABLE melhorias.venda          ENABLE ROW LEVEL SECURITY;

COMMIT;
