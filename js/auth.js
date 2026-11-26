/* auth.js – login e registro
   Valida no navegador, manda para a API (js/api.js) e, se der certo, vai para a home.
   Se der errado, mostra a mensagem que o servidor devolveu (ex.: "E-mail ou senha incorretos."). */
const FOTO_MAXIMA = 2 * 1024 * 1024;   // 2 MB, o mesmo limite do servidor
const FOTO_PADRAO = 'assets/img/avatar-placeholder.svg';

document.querySelectorAll('form[data-auth]').forEach(form => {
  const mensagem = form.querySelector('.form__mensagem');
  const botao = form.querySelector('[type="submit"]');

  /* registro: mostra a foto escolhida no círculo antes de enviar */
  const foto = form.elements['foto'];
  const previa = form.querySelector('#foto-previa');
  if (foto && previa) {
    foto.addEventListener('change', () => {
      const arquivo = foto.files[0];
      mensagem.textContent = '';
      if (arquivo && arquivo.size > FOTO_MAXIMA) {
        mensagem.textContent = 'A foto pode ter no máximo 2 MB.';
        foto.value = '';
      }
      previa.src = foto.files[0] ? URL.createObjectURL(foto.files[0]) : FOTO_PADRAO;
    });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();                       // (campos required/minlength já foram checados pelo navegador)
    mensagem.textContent = '';
    const registro = form.dataset.auth === 'registro';

    if (registro) {
      const senha = form.elements['senha'].value;
      const confirmacao = form.elements['confirmacao_senha'].value;
      if (senha !== confirmacao) {
        mensagem.textContent = 'As senhas não são iguais.';
        form.elements['confirmacao_senha'].focus();
        return;
      }
    }

    // o registro vai como formulário (por causa da foto); o login vai como JSON
    const dados = registro ? new FormData(form) : Object.fromEntries(new FormData(form));
    botao.disabled = true;                    // evita enviar duas vezes
    const resposta = registro ? await API.registrar(dados) : await API.entrar(dados);
    botao.disabled = false;

    if (!resposta.ok) {
      mensagem.textContent = resposta.erro;
      return;
    }
    window.location.href = 'home.html';
  });
});
