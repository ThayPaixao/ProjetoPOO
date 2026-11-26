/* menu.js – a bolinha abre/fecha o menu (que mostra só os ícones) */
(() => {
  const menu = document.getElementById('menu');
  if (!menu) return;
  const botoes = document.querySelectorAll('[data-menu-toggle]');
  const fundo = document.querySelector('.menu-fundo');

  function definir(aberto) {
    menu.classList.toggle('is-open', aberto);
    document.body.classList.toggle('menu-aberto', aberto);   // empurra a página no desktop
    if (fundo) fundo.classList.toggle('is-visivel', aberto);
    botoes.forEach(b => {
      b.setAttribute('aria-expanded', String(aberto));
      b.setAttribute('aria-label', aberto ? 'Fechar menu' : 'Abrir menu');
    });
  }

  /* posição do item ativo: a barra recorta a "aba" transparente ali (css/menu.css) */
  function medirAba() {
    const ativo = menu.querySelector('.menu__item.is-active');
    if (ativo) menu.style.setProperty('--aba-top', `${ativo.offsetTop}px`);
  }
  medirAba();
  addEventListener('resize', medirAba);

  botoes.forEach(b => b.addEventListener('click', () => definir(!menu.classList.contains('is-open'))));
  if (fundo) fundo.addEventListener('click', () => definir(false));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') definir(false); });
})();
