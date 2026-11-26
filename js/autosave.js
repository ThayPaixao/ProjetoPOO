/* autosave.js – liga a thread de auto-save (js/autosave-worker.js) nas páginas internas.
   - a cada 30 s a thread salva o jogo em arquivo no servidor;
   - ao sair da página (fechar a aba, trocar de página), salva mais uma vez (SALVAR NA SAÍDA).
   Elementos com data-autosave mostram o horário do último save. */
(() => {
  if (!window.Worker) return;
  const thread = new Worker('js/autosave-worker.js');

  thread.onmessage = (e) => {
    const r = e.data;
    if (!r.ok) return;                                  // ex.: sem login; tenta de novo no próximo ciclo
    const hora = new Date(r.salvoEm).toLocaleTimeString('pt-BR');
    document.querySelectorAll('[data-autosave]').forEach(el => { el.textContent = `Auto-save: ${hora}`; });
    console.info(`[auto-save] jogo salvo às ${hora} (${r.itens} itens no inventário)`);
  };

  addEventListener('pagehide', () => navigator.sendBeacon('api/usuario/salvar'));
  window.AutoSave = { salvarAgora: () => thread.postMessage('salvar-agora') };
})();
