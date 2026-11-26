/* ==========================================================
   api.js – CAMADA DE DADOS: conversa com o back-end (PHP, pasta api/)
   Todas as telas só chamam estas funções.
   O site precisa ser aberto pelo servidor do PHP (php -S localhost:8000 router.php),
   e não com dois cliques no html, senão o navegador não consegue chamar a API.

   Formato de um item:
   { id, nome, preco, tipo, qualidade (0 normal | 1 prata | 2 ouro | 3 irídio), imagem (url ou null),
     tamanho_cm (só peixe; null nos outros) }
   ========================================================== */
const API = (() => {
  /* Faz a requisição para api/<rota> e devolve { status, dados }. Nunca lança erro.
     corpo: um objeto (vai como JSON) ou um FormData (formulário com arquivo, ex.: foto do registro). */
  async function requisitar(metodo, rota, corpo) {
    const formulario = corpo instanceof FormData;
    try {
      const resp = await fetch(`api/${rota}`, {
        method: metodo,
        headers: corpo && !formulario ? { 'Content-Type': 'application/json' } : undefined,
        body: formulario ? corpo : (corpo ? JSON.stringify(corpo) : undefined),
      });
      const dados = await resp.json().catch(() => ({ ok: false, erro: 'Resposta inválida do servidor.' }));
      return { status: resp.status, dados };
    } catch {
      return { status: 0, dados: { ok: false, erro: 'Sem conexão com o servidor. Ele está rodando?' } };
    }
  }

  /* GET: devolve os dados ou lança um erro com a mensagem do servidor.
     Sem login (401), manda para a tela de login. */
  async function buscar(rota) {
    const { status, dados } = await requisitar('GET', rota);
    if (status === 401) window.location.href = 'login.html';
    if (status !== 200) throw new Error(dados.erro);
    return dados;
  }

  /* POST: sempre devolve { ok: true, ... } ou { ok: false, erro: 'mensagem para o usuário' } */
  async function enviar(rota, corpo = {}) {
    const { status, dados } = await requisitar('POST', rota, corpo);
    if (status === 401 && rota !== 'login') window.location.href = 'login.html';
    return dados;
  }

  const npcNaRota = (npc) => `lojas/${encodeURIComponent(npc)}`;

  return {
    /* POST /api/login      { email, senha } */
    async entrar(dados) { return enviar('login', dados); },

    /* POST /api/registro   FormData { nome, email, senha, confirmacao_senha, foto (arquivo, opcional) } */
    async registrar(dados) { return enviar('registro', dados); },

    /* POST /api/usuario/perfil   FormData { nome, senha_atual?, nova_senha?, confirmacao_senha?, foto? } */
    async editarPerfil(dados) { return enviar('usuario/perfil', dados); },

    /* POST /api/logout */
    async sair() { return enviar('logout'); },

    /* GET /api/usuario → { id, nome, dinheiro, foto } */
    async getUsuario() { return buscar('usuario'); },

    /* GET /api/usuario/inventario */
    async getInventario() { return buscar('usuario/inventario'); },

    /* GET /api/usuario/vendas  (itens vendidos, mais recentes primeiro; também tem npc, sentimento e lucro) */
    async getHistoricoVendas() { return buscar('usuario/vendas'); },

    /* GET /api/usuario/historico  (compras e vendas juntas; campo "operacao": Compra | Venda) */
    async getHistorico() { return buscar('usuario/historico'); },

    /* GET /api/lojas/npcs?modo=comprar|vender
       comprar -> vendedores (você compra deles) | vender -> clientes (você vende para eles)
       → [{ id, nome, imagem }]  (id = identificador usado nas outras rotas) */
    async getNpcs(modo) { return buscar(`lojas/npcs?modo=${encodeURIComponent(modo)}`); },

    /* GET /api/lojas/:npc/itens  → catálogo do vendedor (preco = preço base) */
    async getItensLoja(npc) { return buscar(`${npcNaRota(npc)}/itens`); },

    /* POST /api/lojas/:npc/comprar   body: { itemId }  → { ok, item, mensagem, dinheiro } */
    async comprarItem(itemId, npc) { return enviar(`${npcNaRota(npc)}/comprar`, { itemId }); },

    /* POST /api/lojas/:npc/vender    body: { itemId }  → { ok, valor, sentimento, fala, mensagem, dinheiro } */
    async venderItem(itemId, npc) { return enviar(`${npcNaRota(npc)}/vender`, { itemId }); },
  };
})();
