/* lojas.js – abas Comprar/Vender, lista de NPCs (muda conforme a aba) e botões de ação */
(() => {
  const estado = { modo: 'comprar', npc: null, pedido: 0 };

  const grade = document.getElementById('loja-itens');
  const aviso = document.getElementById('loja-aviso');
  const dinheiro = document.getElementById('loja-dinheiro');
  const listaNpcs = document.getElementById('npcs-lista');
  const abas = document.querySelectorAll('.aba');

  /* Monta os botões de NPC da aba atual e seleciona o primeiro */
  async function carregarNpcs() {
    const npcs = await API.getNpcs(estado.modo);
    listaNpcs.replaceChildren();
    npcs.forEach((npc, i) => {
      const btn = document.createElement('button');
      btn.className = 'npc';
      btn.type = 'button';
      btn.dataset.npc = npc.id;
      btn.textContent = npc.nome;
      btn.setAttribute('aria-pressed', String(i === 0));
      listaNpcs.append(btn);
    });
    estado.npc = npcs[0]?.id ?? null;
    listaNpcs.scrollTop = 0;
  }

  /* Comprar: catálogo do vendedor. Vender: seu inventário com o preço que ESTE NPC paga. */
  async function desenhar() {
    const comprando = estado.modo === 'comprar';
    const pedido = ++estado.pedido;          // se trocar de NPC/aba antes de chegar, esta resposta é ignorada
    Render.lista(grade, [], 'tpl-item-card', { vazio: 'Carregando…' });   // tira os itens antigos na hora
    try {
      const itens = comprando ? await API.getItensLoja(estado.npc) : await API.getOfertas(estado.npc);
      if (pedido !== estado.pedido) return;
      Render.lista(grade, itens, 'tpl-item-card', {
        textoAcao: comprando ? 'Comprar' : 'Vender',
        vazio: comprando ? 'Esta loja não tem itens.' : 'Você não tem nada para vender.',
        prefixoPreco: comprando ? 'a partir de ' : '',     // qualidade (e tamanho do peixe) são sorteados na compra
      });
    } catch (erro) {
      aviso.textContent = erro.message;
    }
  }

  abas.forEach(aba => aba.addEventListener('click', async () => {
    estado.modo = aba.dataset.modo;
    abas.forEach(a => a.setAttribute('aria-selected', String(a === aba)));
    aviso.textContent = '';
    try {
      await carregarNpcs();
      desenhar();
    } catch (erro) {
      aviso.textContent = erro.message;
    }
  }));

  /* um listener para todos os NPCs (os botões são recriados a cada troca de aba) */
  listaNpcs.addEventListener('click', (e) => {
    const btn = e.target.closest('.npc');
    if (!btn) return;
    estado.npc = btn.dataset.npc;
    listaNpcs.querySelectorAll('.npc').forEach(b => b.setAttribute('aria-pressed', String(b === btn)));
    aviso.textContent = '';
    desenhar();
  });

  /* um único listener para todos os botões "Comprar/Vender" */
  grade.addEventListener('click', async (e) => {
    const botao = e.target.closest('[data-acao]');
    if (!botao) return;
    const id = botao.closest('.item-card').dataset.itemId;
    const { modo, npc } = estado;                            // guarda onde o clique aconteceu
    botao.disabled = true;                                   // evita clique duplo
    aviso.textContent = modo === 'comprar' ? 'Comprando…' : 'Vendendo…';
    const resposta = modo === 'comprar' ? await API.comprarItem(id, npc) : await API.venderItem(id, npc);

    if (!resposta.ok) {
      aviso.textContent = resposta.erro;                     // ex.: "Saldo insuficiente: ..."
      botao.disabled = false;
      return;
    }
    // compra: "Compra: Atum (Prata, 34 cm) por 168 g (Willy)" | venda: fala do NPC + resumo
    aviso.textContent = resposta.fala ? `“${resposta.fala}” ${resposta.mensagem}` : resposta.mensagem;
    dinheiro.textContent = Render.preco(resposta.dinheiro);
    if (modo === 'vender' && estado.modo === 'vender' && estado.npc === npc) desenhar();   // o item vendido sai da lista
    else botao.disabled = false;
  });

  (async () => {
    try {
      const usuario = await API.getUsuario();
      dinheiro.textContent = Render.preco(usuario.dinheiro);
      await carregarNpcs();
      desenhar();
    } catch (erro) {
      aviso.textContent = erro.message;
    }
  })();
})();
