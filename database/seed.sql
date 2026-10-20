-- seed.sql – dados iniciais (rode DEPOIS do schema.sql)
-- Itens e gostos tirados dos documentos "Mudança nos Gostos" (versão final).
-- Preços base = preços de venda do jogo, em ouro (g).

INSERT INTO classificacao (id_classificacao, nome) VALUES
  (1, 'Peixe'), (2, 'Colheita'), (3, 'Comida'), (4, 'Minério');

INSERT INTO qualidade (id_qualidade, nome) VALUES
  (0, 'Normal'), (1, 'Prata'), (2, 'Ouro'), (3, 'Irídio');

INSERT INTO sentimento (id_sentimento, nome) VALUES
  (1, 'Ama'), (2, 'Neutro'), (3, 'Odeia');

INSERT INTO npc (id_npc, nome, tipo) VALUES
  ('pierre', 'Pierre', 'VENDEDOR'), ('gus', 'Gus', 'VENDEDOR'),
  ('willy', 'Willy', 'VENDEDOR'), ('clint', 'Clint', 'VENDEDOR'),
  ('elliott', 'Elliott', 'CLIENTE'), ('harvey', 'Harvey', 'CLIENTE'), ('sebastian', 'Sebastian', 'CLIENTE'),
  ('abigail', 'Abigail', 'CLIENTE'), ('leah', 'Leah', 'CLIENTE'), ('penny', 'Penny', 'CLIENTE');

INSERT INTO loja (id_loja, nome, id_npc) VALUES
  (1, 'Loja do Willy', 'willy'),
  (2, 'Loja do Pierre', 'pierre'),
  (3, 'Loja do Gus', 'gus'),
  (4, 'Loja do Clint', 'clint');

INSERT INTO item (nome, preco_base, id_classificacao, id_loja) VALUES
  -- Peixe
  ('Baiacu', 200, 1, 1),
  ('Lula', 80, 1, 1),
  ('Polvo', 150, 1, 1),
  ('Atum', 100, 1, 1),
  ('Sardinha', 40, 1, 1),
  ('Pargo', 50, 1, 1),
  ('Linguado', 80, 1, 1),
  ('Tilápia', 75, 1, 1),
  ('Pepino-do-mar', 75, 1, 1),
  ('Enguia', 85, 1, 1),
  ('Salmão', 75, 1, 1),
  ('Truta-tigre', 150, 1, 1),
  ('Truta Arco-Íris', 65, 1, 1),
  ('Achigã', 100, 1, 1),
  ('Bagre', 200, 1, 1),
  ('Esturjão', 200, 1, 1),
  ('Dourado', 100, 1, 1),
  ('Lúcio', 100, 1, 1),
  ('Peixe-sol', 30, 1, 1),
  ('Arenque', 30, 1, 1),
  -- Colheita
  ('Chirívia', 35, 2, 2),
  ('Batata', 80, 2, 2),
  ('Couve-flor', 175, 2, 2),
  ('Abóbora', 320, 2, 2),
  ('Milho', 50, 2, 2),
  ('Berinjela', 60, 2, 2),
  ('Repolho Vermelho', 260, 2, 2),
  ('Morango', 120, 2, 2),
  ('Melão', 250, 2, 2),
  ('Mirtilo', 50, 2, 2),
  ('Oxicoco', 75, 2, 2),
  ('Uva', 80, 2, 2),
  ('Maçã', 100, 2, 2),
  ('Romã', 140, 2, 2),
  ('Fruta Antiga', 550, 2, 2),
  ('Tulipa', 30, 2, 2),
  ('Girassol', 80, 2, 2),
  ('Papoula', 140, 2, 2),
  ('Rosa-fada', 290, 2, 2),
  ('Ervilha-de-cheiro', 50, 2, 2),
  -- Comida
  ('Ovo Frito', 35, 3, 3),
  ('Omelete', 125, 3, 3),
  ('Salada', 110, 3, 3),
  ('Sopa de Chirívia', 120, 3, 3),
  ('Peixe Assado', 100, 3, 3),
  ('Mexido de Legumes', 120, 3, 3),
  ('Bolo de Chocolate', 200, 3, 3),
  ('Bolo Rosa', 480, 3, 3),
  ('Hambúrguer de Sobrevivência', 180, 3, 3),
  ('Prato de Raízes', 100, 3, 3),
  ('Biscoito', 140, 3, 3),
  ('Almoço da Sorte', 250, 3, 3),
  ('Sashimi', 75, 3, 3),
  ('Café Expresso Triplo', 450, 3, 3),
  ('Pudim de Espuma do Mar', 300, 3, 3),
  ('Macarrão de Tinta de Lula', 150, 3, 3),
  ('Arroz Doce de Manga', 250, 3, 3),
  ('Curry Tropical', 500, 3, 3),
  ('Pudim de Ameixa', 260, 3, 3),
  ('Pizza', 300, 3, 3),
  -- Minério
  ('Ametista', 100, 4, 4),
  ('Diamante', 750, 4, 4),
  ('Esmeralda', 250, 4, 4),
  ('Rubi', 250, 4, 4),
  ('Topázio', 80, 4, 4),
  ('Jade', 200, 4, 4),
  ('Lágrima Congelada', 75, 4, 4),
  ('Quartzo', 25, 4, 4),
  ('Cristal de Terra', 50, 4, 4),
  ('Obsidiana', 200, 4, 4);

-- Gostos: só 'Ama' (1) e 'Odeia' (3). Item que não aparece aqui = 'Neutro' para aquele NPC.
INSERT INTO npc_sentimento (id_npc, id_item, id_sentimento)
SELECT g.id_npc, i.id_item, g.id_sentimento
FROM (VALUES
  -- elliott: ama
  ('elliott', 'Lula', 1), ('elliott', 'Enguia', 1), ('elliott', 'Atum', 1), ('elliott', 'Polvo', 1),
  ('elliott', 'Romã', 1), ('elliott', 'Tulipa', 1), ('elliott', 'Rosa-fada', 1), ('elliott', 'Macarrão de Tinta de Lula', 1),
  ('elliott', 'Café Expresso Triplo', 1), ('elliott', 'Topázio', 1),
  -- elliott: odeia
  ('elliott', 'Pepino-do-mar', 3), ('elliott', 'Papoula', 3), ('elliott', 'Pizza', 3), ('elliott', 'Lágrima Congelada', 3),
  ('elliott', 'Quartzo', 3), ('elliott', 'Cristal de Terra', 3), ('elliott', 'Obsidiana', 3),
  -- harvey: ama
  ('harvey', 'Salmão', 1), ('harvey', 'Sardinha', 1), ('harvey', 'Chirívia', 1), ('harvey', 'Batata', 1),
  ('harvey', 'Ovo Frito', 1), ('harvey', 'Omelete', 1), ('harvey', 'Diamante', 1), ('harvey', 'Quartzo', 1),
  -- harvey: odeia
  ('harvey', 'Baiacu', 3), ('harvey', 'Lula', 3), ('harvey', 'Polvo', 3), ('harvey', 'Atum', 3),
  ('harvey', 'Pargo', 3), ('harvey', 'Linguado', 3), ('harvey', 'Tilápia', 3), ('harvey', 'Pepino-do-mar', 3),
  ('harvey', 'Enguia', 3), ('harvey', 'Truta-tigre', 3), ('harvey', 'Truta Arco-Íris', 3), ('harvey', 'Achigã', 3),
  ('harvey', 'Bagre', 3), ('harvey', 'Esturjão', 3), ('harvey', 'Dourado', 3), ('harvey', 'Lúcio', 3),
  ('harvey', 'Peixe-sol', 3), ('harvey', 'Arenque', 3), ('harvey', 'Papoula', 3), ('harvey', 'Bolo Rosa', 3),
  ('harvey', 'Pizza', 3), ('harvey', 'Pudim de Ameixa', 3), ('harvey', 'Lágrima Congelada', 3), ('harvey', 'Cristal de Terra', 3),
  ('harvey', 'Obsidiana', 3),
  -- sebastian: ama
  ('sebastian', 'Linguado', 1), ('sebastian', 'Pepino-do-mar', 1), ('sebastian', 'Bagre', 1), ('sebastian', 'Esturjão', 1),
  ('sebastian', 'Berinjela', 1), ('sebastian', 'Mirtilo', 1), ('sebastian', 'Sashimi', 1), ('sebastian', 'Hambúrguer de Sobrevivência', 1),
  ('sebastian', 'Lágrima Congelada', 1), ('sebastian', 'Obsidiana', 1), ('sebastian', 'Jade', 1),
  -- sebastian: odeia
  ('sebastian', 'Morango', 3), ('sebastian', 'Melão', 3), ('sebastian', 'Oxicoco', 3), ('sebastian', 'Uva', 3),
  ('sebastian', 'Maçã', 3), ('sebastian', 'Romã', 3), ('sebastian', 'Fruta Antiga', 3), ('sebastian', 'Tulipa', 3),
  ('sebastian', 'Girassol', 3), ('sebastian', 'Papoula', 3), ('sebastian', 'Rosa-fada', 3), ('sebastian', 'Ervilha-de-cheiro', 3),
  ('sebastian', 'Omelete', 3), ('sebastian', 'Prato de Raízes', 3), ('sebastian', 'Cristal de Terra', 3),
  -- abigail: ama
  ('abigail', 'Baiacu', 1), ('abigail', 'Truta Arco-Íris', 1), ('abigail', 'Achigã', 1), ('abigail', 'Abóbora', 1),
  ('abigail', 'Fruta Antiga', 1), ('abigail', 'Oxicoco', 1), ('abigail', 'Bolo de Chocolate', 1), ('abigail', 'Biscoito', 1),
  ('abigail', 'Ametista', 1), ('abigail', 'Obsidiana', 1),
  -- abigail: odeia
  ('abigail', 'Lula', 3), ('abigail', 'Polvo', 3), ('abigail', 'Atum', 3), ('abigail', 'Sardinha', 3),
  ('abigail', 'Pargo', 3), ('abigail', 'Linguado', 3), ('abigail', 'Tilápia', 3), ('abigail', 'Pepino-do-mar', 3),
  ('abigail', 'Enguia', 3), ('abigail', 'Salmão', 3), ('abigail', 'Truta-tigre', 3), ('abigail', 'Bagre', 3),
  ('abigail', 'Esturjão', 3), ('abigail', 'Dourado', 3), ('abigail', 'Lúcio', 3), ('abigail', 'Peixe-sol', 3),
  ('abigail', 'Arenque', 3), ('abigail', 'Chirívia', 3), ('abigail', 'Batata', 3), ('abigail', 'Couve-flor', 3),
  ('abigail', 'Milho', 3), ('abigail', 'Berinjela', 3), ('abigail', 'Repolho Vermelho', 3), ('abigail', 'Papoula', 3),
  ('abigail', 'Lágrima Congelada', 3), ('abigail', 'Cristal de Terra', 3),
  -- leah: ama
  ('leah', 'Lúcio', 1), ('leah', 'Peixe-sol', 1), ('leah', 'Arenque', 1), ('leah', 'Milho', 1),
  ('leah', 'Couve-flor', 1), ('leah', 'Maçã', 1), ('leah', 'Salada', 1), ('leah', 'Mexido de Legumes', 1),
  ('leah', 'Sopa de Chirívia', 1), ('leah', 'Peixe Assado', 1), ('leah', 'Cristal de Terra', 1),
  -- leah: odeia
  ('leah', 'Baiacu', 3), ('leah', 'Lula', 3), ('leah', 'Polvo', 3), ('leah', 'Atum', 3),
  ('leah', 'Sardinha', 3), ('leah', 'Pargo', 3), ('leah', 'Linguado', 3), ('leah', 'Tilápia', 3),
  ('leah', 'Pepino-do-mar', 3), ('leah', 'Enguia', 3), ('leah', 'Salmão', 3), ('leah', 'Truta-tigre', 3),
  ('leah', 'Truta Arco-Íris', 3), ('leah', 'Achigã', 3), ('leah', 'Bagre', 3), ('leah', 'Esturjão', 3),
  ('leah', 'Dourado', 3), ('leah', 'Papoula', 3), ('leah', 'Bolo Rosa', 3), ('leah', 'Pizza', 3),
  ('leah', 'Hambúrguer de Sobrevivência', 3), ('leah', 'Ovo Frito', 3), ('leah', 'Ametista', 3), ('leah', 'Lágrima Congelada', 3),
  ('leah', 'Esmeralda', 3), ('leah', 'Rubi', 3), ('leah', 'Quartzo', 3), ('leah', 'Obsidiana', 3),
  ('leah', 'Topázio', 3), ('leah', 'Jade', 3),
  -- penny: ama
  ('penny', 'Tilápia', 1), ('penny', 'Pargo', 1), ('penny', 'Truta-tigre', 1), ('penny', 'Dourado', 1),
  ('penny', 'Melão', 1), ('penny', 'Repolho Vermelho', 1), ('penny', 'Papoula', 1), ('penny', 'Morango', 1),
  ('penny', 'Uva', 1), ('penny', 'Girassol', 1), ('penny', 'Ervilha-de-cheiro', 1), ('penny', 'Prato de Raízes', 1),
  ('penny', 'Biscoito', 1), ('penny', 'Pudim de Ameixa', 1), ('penny', 'Pudim de Espuma do Mar', 1), ('penny', 'Diamante', 1),
  ('penny', 'Esmeralda', 1),
  -- penny: odeia
  ('penny', 'Baiacu', 3), ('penny', 'Lula', 3), ('penny', 'Polvo', 3), ('penny', 'Atum', 3),
  ('penny', 'Sardinha', 3), ('penny', 'Linguado', 3), ('penny', 'Pepino-do-mar', 3), ('penny', 'Enguia', 3),
  ('penny', 'Salmão', 3), ('penny', 'Truta Arco-Íris', 3), ('penny', 'Achigã', 3), ('penny', 'Bagre', 3),
  ('penny', 'Esturjão', 3), ('penny', 'Lúcio', 3), ('penny', 'Peixe-sol', 3), ('penny', 'Arenque', 3),
  ('penny', 'Lágrima Congelada', 3), ('penny', 'Quartzo', 3), ('penny', 'Cristal de Terra', 3), ('penny', 'Obsidiana', 3)
) AS g(id_npc, nome_item, id_sentimento)
JOIN item i ON i.nome = g.nome_item;

-- Conferência: deve mostrar 70 itens e 190 gostos.
SELECT (SELECT count(*) FROM item) AS itens, (SELECT count(*) FROM npc_sentimento) AS gostos;
