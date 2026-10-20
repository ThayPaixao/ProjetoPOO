/* lojas.js – abas Comprar/Vender, lista de NPCs (muda conforme a aba) e botões de ação */
(() => {
  const estado = { modo: 'comprar', npc: null };

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

  async function desenhar() {
    const comprando = estado.modo === 'comprar';
    const itens = comprando ? await API.getItensLoja(estado.npc) : await API.getInventario();
    Render.lista(grade, itens, 'tpl-item-card', {
      textoAcao: comprando ? 'Comprar' : 'Vender',
      vazio: comprando ? 'Esta loja não tem itens.' : 'Você não tem nada para vender.',
    });
  }

  abas.forEach(aba => aba.addEventListener('click', async () => {
    estado.modo = aba.dataset.modo;
    abas.forEach(a => a.setAttribute('aria-selected', String(a === aba)));
    aviso.textContent = '';
    await carregarNpcs();
    desenhar();
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
    const resposta = estado.modo === 'comprar'
      ? await API.comprarItem(id, estado.npc)
      : await API.venderItem(id, estado.npc);
    aviso.textContent = resposta.ok ? 'Pronto!' : 'Não foi possível concluir.';
    /* TODO (banco): atualizar o dinheiro e redesenhar a lista com os dados novos */
  });

  (async () => {
    const usuario = await API.getUsuario();
    dinheiro.textContent = Render.preco(usuario.dinheiro);
    await carregarNpcs();
    desenhar();
  })();
})();
