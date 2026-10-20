/* auth.js – login e registro
   Valida no navegador, manda para a API (js/api.js) e, se der certo, vai para a home.
   Se der errado, mostra a mensagem que o servidor devolveu (ex.: "E-mail ou senha incorretos."). */
document.querySelectorAll('form[data-auth]').forEach(form => {
  const mensagem = form.querySelector('.form__mensagem');
  const botao = form.querySelector('[type="submit"]');

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

    const dados = Object.fromEntries(new FormData(form));
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
