/* render.js – transforma dados (api.js) em HTML usando os <template> das páginas.
   Usa textContent (e não innerHTML) de propósito: evita que texto vindo do
   banco seja interpretado como HTML. */
const Render = (() => {
  const QUALIDADES = ['Normal', 'Bronze', 'Prata', 'Ouro'];
  const IMG_PADRAO = 'assets/img/item-placeholder.svg';

  const preco = (v) => `${Number(v).toLocaleString('pt-BR')} g`;

  function preencher(no, item) {
    no.dataset.itemId = item.id;
    no.querySelectorAll('[data-slot]').forEach(el => {
      const campo = el.dataset.slot;
      if (campo === 'preco') el.textContent = preco(item.preco);
      else if (item[campo] !== undefined) el.textContent = item[campo];
    });
    const img = no.querySelector('img');
    if (img) img.src = item.imagem || IMG_PADRAO;
    const estrela = no.querySelector('.estrela');
    if (estrela) {
      const q = item.qualidade ?? 0;
      estrela.dataset.quality = q;
      estrela.setAttribute('aria-label', `Qualidade: ${QUALIDADES[q] ?? 'Normal'}`);
    }
  }

  /* container: elemento que recebe os itens | tplId: id do <template>
     textoAcao: texto do botão (ex.: "Comprar"); omita para esconder o botão */
  function lista(container, itens, tplId, { textoAcao, vazio = 'Nada por aqui ainda.' } = {}) {
    const tpl = document.getElementById(tplId);
    container.replaceChildren();
    if (!itens.length) {
      const p = document.createElement('p');
      p.className = 'vazio';
      p.textContent = vazio;
      container.append(p);
      return;
    }
    itens.forEach(item => {
      const no = tpl.content.firstElementChild.cloneNode(true);
      preencher(no, item);
      const botao = no.querySelector('[data-acao]');
      if (botao) {
        if (textoAcao) botao.textContent = textoAcao;
        else botao.remove();
      }
      container.append(no);
    });
  }

  return { lista, preco };
})();
