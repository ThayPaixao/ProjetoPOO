/* lojas.js – abas Comprar/Vender, lista de NPCs (muda conforme a aba) e botões de ação */
(() => {
  const estado = { modo: 'comprar', npc: null, pedido: 0, itens: [] };

  const grade = document.getElementById('loja-itens');
  const busca = document.getElementById('loja-busca');
  const categoria = document.getElementById('loja-categoria');
  const contagem = document.getElementById('loja-contagem');
  const aviso = document.getElementById('loja-aviso');
  const dinheiro = document.getElementById('loja-dinheiro');
  const listaNpcs = document.getElementById('npcs-lista');
  const sprite = document.getElementById('npc-sprite');
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
      btn.dataset.imagem = npc.imagem ?? '';
      btn.textContent = npc.nome;
      btn.setAttribute('aria-pressed', String(i === 0));
      listaNpcs.append(btn);
    });
    estado.npc = npcs[0]?.id ?? null;
    listaNpcs.scrollTop = 0;
    mostrarRetrato(listaNpcs.querySelector('.npc'));
  }

  /* Mostra o retrato do NPC selecionado no quadro da esquerda */
  function mostrarRetrato(btn) {
    sprite.src = btn?.dataset.imagem || 'assets/img/npc-placeholder.svg';
    sprite.alt = btn ? `Retrato de ${btn.textContent}` : '';
  }

  /* Comprar: catálogo do vendedor. Vender: seu inventário com o que você pagou.
     Como no jogo, você só descobre o que o NPC sente pelo item na hora de vender. */
  async function desenhar() {
    const comprando = estado.modo === 'comprar';
    grade.dataset.modo = estado.modo;        // o CSS pinta o botão: verde (comprar) ou vermelho (vender)
    const pedido = ++estado.pedido;          // se trocar de NPC/aba antes de chegar, esta resposta é ignorada
    Render.lista(grade, [], 'tpl-item-card', { vazio: 'Carregando…' });   // tira os itens antigos na hora
    try {
      const itens = comprando ? await API.getItensLoja(estado.npc) : await API.getInventario();
      if (pedido !== estado.pedido) return;
      estado.itens = itens;
      montarCategorias(itens);
      filtrar();
    } catch (erro) {
      aviso.textContent = erro.message;
    }
  }

  /* ---------- busca e filtro (só nos itens já carregados) ---------- */

  /* "Cogumelo Comum" e "cogumelo comum" e "cogumélo" acham a mesma coisa: sem acento e sem maiúscula */
  const simplificar = (texto) => texto.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase().trim();

  /* As opções do filtro são as categorias que existem nesta lista (a escolhida continua, se ainda existir) */
  function montarCategorias(itens) {
    const escolhida = categoria.value;
    const tipos = [...new Set(itens.map(item => item.tipo))].sort((a, b) => a.localeCompare(b, 'pt-BR'));
    categoria.replaceChildren(new Option('Todas as categorias', ''), ...tipos.map(tipo => new Option(tipo, tipo)));
    categoria.value = tipos.includes(escolhida) ? escolhida : '';
    categoria.hidden = tipos.length < 2;               // uma categoria só: o filtro não ajuda
  }

  function filtrar() {
    const comprando = estado.modo === 'comprar';
    const termo = simplificar(busca.value);
    const visiveis = estado.itens.filter(item =>
      (!categoria.value || item.tipo === categoria.value) && simplificar(item.nome).includes(termo));
    const filtrando = termo !== '' || categoria.value !== '';
    contagem.textContent = filtrando ? `${visiveis.length} de ${estado.itens.length} itens` : `${estado.itens.length} itens`;
    Render.lista(grade, visiveis, 'tpl-item-card', {
      textoAcao: comprando ? 'Comprar' : 'Vender',
      vazio: filtrando ? 'Nenhum item com esse filtro.'
           : comprando ? 'Esta loja não tem itens.' : 'Você não tem nada para vender.',
      prefixoPreco: comprando ? 'a partir de ' : 'pagou ',   // na compra, qualidade (e tamanho do peixe) são sorteados
    });
  }

  busca.addEventListener('input', filtrar);
  categoria.addEventListener('change', filtrar);

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
    mostrarRetrato(btn);
    aviso.textContent = '';
    desenhar();
  });

  /* ---------- pop-up de compra/venda ---------- */
  const popup = document.getElementById('popup');
  const VEREDITO = { Ama: 'amou isso!', Neutro: 'achou sem graça (neutro).', Odeia: 'odiou isso!' };
  const ESTRELA = [                                         // na compra: qual estrela (qualidade) foi sorteada
    'Qualidade normal: sem estrela desta vez.',
    '★ Você conseguiu a estrela de Prata!',
    '★ Você conseguiu a estrela de Ouro!',
    '★ Uau! Você conseguiu a estrela de Irídio!',
  ];

  function mostrarPopup(modo, r) {
    const { item, npc } = r;
    document.getElementById('popup-retrato').src = npc?.imagem || 'assets/img/npc-placeholder.svg';
    document.getElementById('popup-nome').textContent = npc?.nome ?? '';
    document.getElementById('popup-fala').textContent = r.fala ?? '';
    const veredito = document.getElementById('popup-veredito');
    veredito.textContent = modo === 'vender' ? `${npc.nome} ${VEREDITO[r.sentimento] ?? ''}` : ESTRELA[item.qualidade] ?? '';
    veredito.dataset.sentimento = r.sentimento ?? '';
    veredito.dataset.qualidade = modo === 'comprar' ? item.qualidade : '';
    document.getElementById('popup-item-img').src = item?.imagem || 'assets/img/item-placeholder.svg';
    const detalhes = [Render.nomeQualidade(item.qualidade), item.tamanho_cm ? `${item.tamanho_cm} cm` : null].filter(Boolean).join(', ');
    document.getElementById('popup-item-nome').textContent = `${item.nome} (${detalhes})`;
    document.getElementById('popup-resultado').textContent = modo === 'comprar'
      ? `Você pagou ${Render.preco(item.preco)}`
      : `Recebeu ${Render.preco(r.valor)} (${r.lucro >= 0 ? 'lucro' : 'prejuízo'} de ${Render.preco(Math.abs(r.lucro))})`;
    mostrarConquistas(r.conquistas ?? []);
    popup.dataset.tipo = 'ok';
    popup.showModal();
  }

  /* conquistas liberadas por esta compra/venda (o link leva para a página da vila) */
  function mostrarConquistas(conquistas) {
    const aviso = document.getElementById('popup-conquista');
    aviso.hidden = conquistas.length === 0;
    aviso.textContent = conquistas.length === 1
      ? `🏆 Conquista liberada: ${conquistas[0].nome}!`
      : `🏆 Conquistas liberadas: ${conquistas.map(c => c.nome).join(', ')}!`;
  }

  /* pop-up de ERRO (ex.: saldo insuficiente): mesmo visual, em vermelho, com a mensagem do servidor.
     resposta = { ok: false, erro: 'mensagem', codigo: 'saldo_insuficiente' | ... } */
  function mostrarErro(modo, idNpc, card, resposta) {
    const btnNpc = listaNpcs.querySelector(`[data-npc="${idNpc}"]`);
    const semDinheiro = resposta.codigo === 'saldo_insuficiente';
    document.getElementById('popup-retrato').src = btnNpc?.dataset.imagem || 'assets/img/npc-placeholder.svg';
    document.getElementById('popup-nome').textContent = btnNpc?.textContent ?? '';
    document.getElementById('popup-fala').textContent = semDinheiro
      ? 'Hmm... parece que falta ouro para levar isso. Volte quando tiver mais!'
      : 'Ops, alguma coisa deu errado. Vamos tentar de novo?';
    const veredito = document.getElementById('popup-veredito');
    veredito.textContent = modo === 'comprar' ? 'Compra não realizada' : 'Venda não realizada';
    veredito.dataset.sentimento = '';
    veredito.dataset.qualidade = '';
    document.getElementById('popup-item-img').src = card?.querySelector('img')?.src || 'assets/img/item-placeholder.svg';
    document.getElementById('popup-item-nome').textContent = card?.querySelector('.item-card__nome')?.textContent ?? '';
    document.getElementById('popup-resultado').textContent = resposta.erro;
    mostrarConquistas([]);
    popup.dataset.tipo = 'erro';
    popup.showModal();
  }
  popup.addEventListener('click', (e) => {                // clique fora da caixa fecha
    const r = popup.getBoundingClientRect();
    if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) popup.close();
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
      aviso.textContent = '';
      mostrarErro(modo, npc, botao.closest('.item-card'), resposta);   // ex.: saldo insuficiente
      botao.disabled = false;
      return;
    }
    aviso.textContent = '';
    mostrarPopup(modo, resposta);
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
