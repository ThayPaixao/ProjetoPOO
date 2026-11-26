/* autosave-worker.js – a THREAD de auto-save (Web Worker).
   Um Web Worker roda numa thread separada da thread da tela: o site continua respondendo
   enquanto esta thread, a cada 30 segundos, pede ao servidor para salvar o jogo em arquivo
   (saves/jogador-<id>.json) e avisa a página quando terminou. Quem liga a thread é js/autosave.js. */
const INTERVALO = 30 * 1000;
const ROTA = new URL('../api/usuario/salvar', self.location);   // caminho a partir desta pasta (js/)

async function salvar() {
  try {
    const resp = await fetch(ROTA, { method: 'POST', credentials: 'same-origin' });
    const dados = await resp.json();
    postMessage(resp.ok ? { ok: true, salvoEm: dados.salvo_em, itens: dados.itens }
                        : { ok: false, erro: dados.erro });
  } catch {
    postMessage({ ok: false, erro: 'Sem conexão com o servidor.' });
  }
}

setInterval(salvar, INTERVALO);
self.onmessage = (e) => { if (e.data === 'salvar-agora') salvar(); };
