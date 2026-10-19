/* ==========================================================
   api.js – CAMADA DE DADOS (hoje: dados de exemplo)
   Quando o banco de dados existir, troque o corpo de cada função por um
   fetch() para a sua rota. O resto do site NÃO precisa mudar, porque
   todas as telas só chamam estas funções.

   Formato de um item:
   { id, nome, preco, tipo, qualidade (0 normal | 1 bronze | 2 prata | 3 ouro), imagem (url ou null) }
   ========================================================== */
const API = (() => {
  const TIPOS = ['Cultivo', 'Peixe', 'Mineral', 'Artesanato'];
  const gerar = (qtd, base = 0) =>
    Array.from({ length: qtd }, (_, i) => ({
      id: base + i + 1,
      nome: `Item ${base + i + 1}`,
      preco: (base + i + 1) * 25,
      tipo: TIPOS[i % TIPOS.length],
      qualidade: i % 4,
      imagem: null,
    }));

  return {
    /* Exemplo real depois:  return (await fetch('/api/usuario')).json(); */
    async getUsuario() {
      return { id: 1, nome: 'Nome do jogador', dinheiro: 500, foto: null };
    },

    /* GET /api/usuario/inventario */
    async getInventario() { return gerar(12); },

    /* GET /api/usuario/vendas  (itens vendidos, mais recentes primeiro) */
    async getHistoricoVendas() { return gerar(10, 100); },

    /* GET /api/lojas/npcs?modo=comprar|vender
       comprar -> vendedores (você compra deles) | vender -> compradores (você vende para eles)
       id = identificador usado nas outras rotas, nome = texto do botão */
    async getNpcs(modo) {
      const npcs = {
        comprar: [
          { id: 'clint', nome: 'Clint' }, { id: 'gus', nome: 'Gus' },
          { id: 'pierre', nome: 'Pierre' }, { id: 'willy', nome: 'Willy' },
        ],
        vender: [
          { id: 'elliot', nome: 'Elliot' }, { id: 'harvey', nome: 'Harvey' },
          { id: 'sebastian', nome: 'Sebastian' }, { id: 'abigail', nome: 'Abigail' },
          { id: 'leah', nome: 'Leah' }, { id: 'penny', nome: 'Penny' },
        ],
      };
      return npcs[modo] ?? [];
    },

    /* GET /api/lojas/:npc/itens */
    async getItensLoja(npc) {
      const deslocamento = { clint: 200, gus: 300, pierre: 400, willy: 500 }[npc] ?? 0;  // só para variar os exemplos
      return gerar(12, deslocamento);
    },

    /* POST /api/lojas/:npc/comprar   body: { itemId } */
    async comprarItem(itemId, npc) { return { ok: true }; },

    /* POST /api/lojas/:npc/vender    body: { itemId } */
    async venderItem(itemId, npc) { return { ok: true }; },
  };
})();
