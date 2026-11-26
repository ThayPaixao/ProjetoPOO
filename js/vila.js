/* vila.js – página da vila: ranking, conquistas e diário de gostos (dados em api.js) */
(() => {
  const aviso = document.getElementById('vila-aviso');
  const FOTO_PADRAO = 'assets/img/avatar-placeholder.svg';
  const SENTIMENTO = { Ama: '♥', Neutro: '', Odeia: '✗' };

  /* cópia de um <template>; preencher(no, campo, texto) põe o texto em [data-campo="..."] */
  const copiar = (id) => document.getElementById(id).content.firstElementChild.cloneNode(true);
  const preencher = (no, campo, texto) => { no.querySelector(`[data-campo="${campo}"]`).textContent = texto; };

  function vazio(container, texto) {
    const p = document.createElement('p');
    p.className = 'vazio';
    p.textContent = texto;
    container.replaceChildren(p);
  }

  /* ---------- ranking ---------- */
  function mostrarRanking(ranking) {
    const lista = document.getElementById('ranking-lista');
    if (!ranking.length) return vazio(lista, 'Ninguém vendeu nada ainda. Seja o primeiro!');
    lista.replaceChildren(...ranking.map(jogador => {
      const linha = copiar('tpl-ranking');
      linha.classList.toggle('ranking__linha--voce', jogador.voce);
      preencher(linha, 'posicao', jogador.posicao);
      preencher(linha, 'nome', jogador.voce ? `${jogador.nome} (você)` : jogador.nome);
      preencher(linha, 'vendas', `${jogador.vendas} ${jogador.vendas === 1 ? 'venda' : 'vendas'}`);
      preencher(linha, 'lucro', `${jogador.lucro >= 0 ? '+' : '−'}${Render.preco(Math.abs(jogador.lucro))}`);
      linha.querySelector('img').src = jogador.foto || FOTO_PADRAO;
      return linha;
    }));
  }

  /* ---------- conquistas ---------- */
  function mostrarConquistas(conquistas) {
    const feitas = conquistas.filter(c => c.conquistada).length;
    document.getElementById('conquistas-contador').textContent = `(${feitas} de ${conquistas.length})`;
    document.getElementById('conquistas-lista').replaceChildren(...conquistas.map(c => {
      const cartao = copiar('tpl-conquista');
      cartao.classList.toggle('conquista--feita', c.conquistada);
      preencher(cartao, 'nome', c.nome);
      preencher(cartao, 'descricao', c.descricao);
      preencher(cartao, 'progresso', c.conquistada ? 'Conquistada!' : `${c.progresso} / ${c.meta}`);
      cartao.querySelector('.conquista__preenchido').style.width = `${(c.progresso / c.meta) * 100}%`;
      return cartao;
    }));
  }

  /* ---------- diário de gostos ---------- */
  function mostrarDiario(moradores) {
    document.getElementById('diario-lista').replaceChildren(...moradores.map(morador => {
      const cartao = copiar('tpl-morador');
      preencher(cartao, 'nome', morador.nome);
      const n = morador.itens.length;
      preencher(cartao, 'contador', n === 1 ? '1 item descoberto' : `${n} itens descobertos`);
      const retrato = cartao.querySelector('img');
      retrato.src = morador.imagem || 'assets/img/npc-placeholder.svg';
      retrato.alt = `Retrato de ${morador.nome}`;

      const lista = cartao.querySelector('.morador__itens');
      if (!n) vazio(lista, `Você ainda não vendeu nada para ${morador.nome}.`);
      else lista.replaceChildren(...morador.itens.map(item => {
        const gosto = document.createElement('li');
        gosto.className = 'gosto';
        gosto.dataset.sentimento = item.sentimento;
        gosto.title = `${morador.nome}: ${item.sentimento}`;
        const img = document.createElement('img');
        img.src = item.imagem || 'assets/img/item-placeholder.svg';
        img.alt = '';
        gosto.append(img, `${SENTIMENTO[item.sentimento] ?? ''} ${item.nome}`.trim());
        return gosto;
      }));
      return cartao;
    }));
  }

  /* as três partes carregam juntas; se uma falhar, as outras aparecem mesmo assim */
  (async () => {
    const partes = [
      [API.getRanking, mostrarRanking],
      [API.getConquistas, mostrarConquistas],
      [API.getDiario, mostrarDiario],
    ];
    const resultados = await Promise.allSettled(partes.map(async ([buscar, mostrar]) => mostrar(await buscar())));
    const erro = resultados.find(r => r.status === 'rejected');
    if (erro) aviso.textContent = erro.reason.message;
  })();
})();
