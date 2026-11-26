-- schema.sql – estrutura do banco (PostgreSQL / Supabase)
-- Como usar: Supabase → SQL Editor → cole este arquivo → Run. Depois faça o mesmo com o seed.sql.
-- Rodar de novo APAGA tudo e recria do zero.

DROP VIEW IF EXISTS historico, inventario;
DROP TABLE IF EXISTS venda, compra, jogador, npc_sentimento, item, loja, npc,
                     sentimento, qualidade, classificacao CASCADE;

-- ---------- tabelas de apoio ----------

CREATE TABLE classificacao (
    id_classificacao SMALLINT PRIMARY KEY,
    nome             TEXT NOT NULL UNIQUE               -- Peixe, Colheita, Comida, Minério
);

-- As regras (quanto cada qualidade/sentimento multiplica o preço) ficam nas classes PHP.
CREATE TABLE qualidade (
    id_qualidade SMALLINT PRIMARY KEY,                  -- 0 a 3, o mesmo número que o front usa
    nome         TEXT NOT NULL UNIQUE                   -- Normal, Prata, Ouro, Irídio
);

CREATE TABLE sentimento (
    id_sentimento SMALLINT PRIMARY KEY,
    nome          TEXT NOT NULL UNIQUE                  -- Ama, Neutro, Odeia
);

-- ---------- NPCs, lojas e itens ----------

CREATE TABLE npc (
    id_npc TEXT PRIMARY KEY,                            -- 'pierre', 'elliott'... (é o id das rotas do front)
    nome   TEXT NOT NULL,
    foto   TEXT,
    tipo   TEXT NOT NULL CHECK (tipo IN ('VENDEDOR', 'CLIENTE'))
);

CREATE TABLE loja (
    id_loja SMALLINT PRIMARY KEY,
    nome    TEXT NOT NULL,
    foto    TEXT,
    id_npc  TEXT NOT NULL UNIQUE REFERENCES npc (id_npc)    -- dono da loja
);

CREATE TABLE item (
    id_item          SERIAL PRIMARY KEY,
    nome             TEXT NOT NULL UNIQUE,
    preco_base       INTEGER NOT NULL CHECK (preco_base > 0),
    foto             TEXT,
    id_classificacao SMALLINT NOT NULL REFERENCES classificacao (id_classificacao),
    id_loja          SMALLINT NOT NULL REFERENCES loja (id_loja)
);

-- Só guarda 'Ama' e 'Odeia'. Se não tem linha para (npc, item), o NPC é neutro.
CREATE TABLE npc_sentimento (
    id_npc        TEXT     NOT NULL REFERENCES npc (id_npc),
    id_item       INTEGER  NOT NULL REFERENCES item (id_item),
    id_sentimento SMALLINT NOT NULL REFERENCES sentimento (id_sentimento) CHECK (id_sentimento <> 2),
    PRIMARY KEY (id_npc, id_item)
);

-- ---------- jogador e transações ----------

CREATE TABLE jogador (
    id_jogador SERIAL PRIMARY KEY,
    nome       TEXT NOT NULL,
    email      TEXT NOT NULL UNIQUE,
    senha_hash TEXT NOT NULL,                           -- nunca a senha pura (password_hash do PHP)
    saldo      INTEGER NOT NULL CHECK (saldo >= 0),
    foto       TEXT,
    criado_em  TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Cada linha é UMA unidade comprada, com o que foi sorteado nela.
CREATE TABLE compra (
    id_compra    SERIAL PRIMARY KEY,
    id_jogador   INTEGER  NOT NULL REFERENCES jogador (id_jogador),
    id_item      INTEGER  NOT NULL REFERENCES item (id_item),
    id_qualidade SMALLINT NOT NULL REFERENCES qualidade (id_qualidade),
    tamanho_cm   SMALLINT CHECK (tamanho_cm BETWEEN 10 AND 50),   -- só peixe; nos outros fica NULL
    preco_pago   INTEGER  NOT NULL CHECK (preco_pago > 0),
    data         TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- A venda aponta para a compra: assim sabemos qual unidade saiu e quanto foi pago nela.
CREATE TABLE venda (
    id_venda      SERIAL PRIMARY KEY,
    id_compra     INTEGER  NOT NULL UNIQUE REFERENCES compra (id_compra),  -- cada unidade só é vendida 1 vez
    id_npc        TEXT     NOT NULL REFERENCES npc (id_npc),               -- quem comprou de você
    id_sentimento SMALLINT NOT NULL REFERENCES sentimento (id_sentimento), -- o que ele sentia pelo item
    preco_venda   INTEGER  NOT NULL CHECK (preco_venda > 0),
    data          TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX ON compra (id_jogador);

-- ---------- views (consultas salvas) ----------

-- Inventário = tudo que o jogador comprou e ainda não vendeu.
CREATE VIEW inventario WITH (security_invoker = true) AS
SELECT c.*
FROM compra c
LEFT JOIN venda v ON v.id_compra = c.id_compra
WHERE v.id_venda IS NULL;

-- Histórico = compras e vendas juntas, cada uma com o NPC envolvido.
CREATE VIEW historico WITH (security_invoker = true) AS
SELECT c.id_jogador, 'COMPRA' AS tipo, c.id_compra, c.id_item, c.id_qualidade, c.tamanho_cm,
       l.id_npc, NULL::SMALLINT AS id_sentimento, c.preco_pago AS valor, c.data
FROM compra c
JOIN item i ON i.id_item = c.id_item
JOIN loja l ON l.id_loja = i.id_loja
UNION ALL
SELECT c.id_jogador, 'VENDA', c.id_compra, c.id_item, c.id_qualidade, c.tamanho_cm,
       v.id_npc, v.id_sentimento, v.preco_venda, v.data
FROM venda v
JOIN compra c ON c.id_compra = v.id_compra;

-- ---------- segurança ----------
-- Liga o RLS sem nenhuma regra: a chave pública do Supabase (a que vai no navegador)
-- não consegue ler nem alterar nada. Só o PHP, que conecta com a senha do banco, acessa.
ALTER TABLE classificacao  ENABLE ROW LEVEL SECURITY;
ALTER TABLE qualidade      ENABLE ROW LEVEL SECURITY;
ALTER TABLE sentimento     ENABLE ROW LEVEL SECURITY;
ALTER TABLE npc            ENABLE ROW LEVEL SECURITY;
ALTER TABLE loja           ENABLE ROW LEVEL SECURITY;
ALTER TABLE item           ENABLE ROW LEVEL SECURITY;
ALTER TABLE npc_sentimento ENABLE ROW LEVEL SECURITY;
ALTER TABLE jogador        ENABLE ROW LEVEL SECURITY;
ALTER TABLE compra         ENABLE ROW LEVEL SECURITY;
ALTER TABLE venda          ENABLE ROW LEVEL SECURITY;
