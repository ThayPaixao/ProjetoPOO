/* foto.js – escolher e ajustar a foto de perfil (cadastro e editar perfil)
   Clicar no círculo abre a escolha do arquivo; depois abre uma janela onde a pessoa
   ARRASTA a foto e usa o ZOOM para escolher o que aparece no círculo. O recorte
   (um quadrado de 400 x 400) é o que vai para o servidor.
   Uso: const escolha = FotoPerfil.ligar(inputFile, imgPrevia, aoErro);   →   escolha.arquivo (Blob ou null) */
const FotoPerfil = (() => {
  const SAIDA = 400;                  // tamanho da foto salva (px)
  let dialogo, janela, img, zoom;
  let estado = null;                  // { S, w, h, base, z, x, y }
  let terminar = null;                // resolve da janela aberta

  function montar() {
    if (dialogo) return;
    dialogo = document.createElement('dialog');
    dialogo.className = 'popup foto-editor';
    dialogo.innerHTML = `
      <p class="foto-editor__titulo">Ajuste sua foto</p>
      <div class="foto-editor__janela"><img alt="" draggable="false"></div>
      <label class="foto-editor__zoom">Zoom <input type="range" min="1" max="3" step="0.01" value="1"></label>
      <p class="foto-editor__dica">Arraste a foto para escolher o que aparece no círculo.</p>
      <div class="popup__acoes">
        <button type="button" class="btn btn--pequeno" data-foto="cancelar">Cancelar</button>
        <button type="button" class="btn btn--ok btn--pequeno" data-foto="usar">Usar foto</button>
      </div>`;
    document.body.append(dialogo);
    janela = dialogo.querySelector('.foto-editor__janela');
    img = janela.querySelector('img');
    zoom = dialogo.querySelector('input[type="range"]');

    // arrastar (mouse e dedo)
    let inicio = null;
    janela.addEventListener('pointerdown', (e) => {
      janela.setPointerCapture(e.pointerId);
      inicio = { px: e.clientX, py: e.clientY, x: estado.x, y: estado.y };
    });
    janela.addEventListener('pointermove', (e) => {
      if (!inicio) return;
      estado.x = inicio.x + (e.clientX - inicio.px);
      estado.y = inicio.y + (e.clientY - inicio.py);
      desenhar();
    });
    janela.addEventListener('pointerup', () => { inicio = null; });

    // zoom mantendo o centro do círculo no lugar
    zoom.addEventListener('input', () => {
      const antes = estado.base * estado.z;
      estado.z = Number(zoom.value);
      const depois = estado.base * estado.z;
      const c = estado.S / 2;
      estado.x = c - (c - estado.x) * depois / antes;
      estado.y = c - (c - estado.y) * depois / antes;
      desenhar();
    });

    dialogo.querySelector('[data-foto="cancelar"]').addEventListener('click', () => fechar(null));
    dialogo.addEventListener('cancel', (e) => { e.preventDefault(); fechar(null); });   // tecla Esc
    dialogo.querySelector('[data-foto="usar"]').addEventListener('click', () => {
      const { S, base, z, x, y } = estado;
      const escala = base * z;
      const tela = document.createElement('canvas');
      tela.width = tela.height = SAIDA;
      const ctx = tela.getContext('2d');
      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, SAIDA, SAIDA);
      ctx.drawImage(img, -x / escala, -y / escala, S / escala, S / escala, 0, 0, SAIDA, SAIDA);
      tela.toBlob((blob) => fechar(blob), 'image/jpeg', 0.9);
    });
  }

  /* a foto sempre cobre o círculo inteiro: não deixa arrastar para fora */
  function desenhar() {
    const { S, w, h, base, z } = estado;
    const escala = base * z;
    estado.x = Math.min(0, Math.max(S - w * escala, estado.x));
    estado.y = Math.min(0, Math.max(S - h * escala, estado.y));
    img.style.transform = `translate(${estado.x}px, ${estado.y}px) scale(${escala})`;
  }

  function fechar(resultado) {
    dialogo.close();
    if (terminar) { terminar(resultado); terminar = null; }
  }

  /* Abre a janela de ajuste para um arquivo. Devolve o recorte (Blob) ou null se cancelar. */
  function ajustar(arquivo) {
    montar();
    return new Promise((resolve, reject) => {
      img.onload = () => {
        dialogo.showModal();
        const S = janela.clientWidth;
        const w = img.naturalWidth, h = img.naturalHeight;
        img.style.width = `${w}px`;
        img.style.height = `${h}px`;
        const base = Math.max(S / w, S / h);            // começa cobrindo o círculo, centralizada
        estado = { S, w, h, base, z: 1, x: (S - w * base) / 2, y: (S - h * base) / 2 };
        zoom.value = 1;
        desenhar();
        terminar = resolve;
      };
      img.onerror = () => reject(new Error('Não deu para abrir essa imagem. Tente uma foto JPG ou PNG.'));
      img.src = URL.createObjectURL(arquivo);
    });
  }

  /* Liga um <input type="file"> e a imagem de prévia do círculo. */
  function ligar(input, previa, aoErro = () => {}) {
    const escolha = { arquivo: null };
    input.addEventListener('change', async () => {
      const original = input.files[0];
      input.value = '';                                 // deixa escolher o mesmo arquivo de novo
      if (!original) return;
      try {
        aoErro('');
        const recorte = await ajustar(original);
        if (!recorte) return;                           // cancelou: mantém a foto anterior
        escolha.arquivo = recorte;
        previa.src = URL.createObjectURL(recorte);
      } catch (erro) {
        aoErro(erro.message);
      }
    });
    return escolha;
  }

  return { ligar, ajustar };
})();
