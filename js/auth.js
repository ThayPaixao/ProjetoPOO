/* auth.js – login e registro
   Por enquanto NÃO existe servidor: depois de validar, só vai para a home.
   Quando o back-end existir, descomente o bloco TODO e use a rota real. */
document.querySelectorAll('form[data-auth]').forEach(form => {
  const mensagem = form.querySelector('.form__mensagem');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();                       // (campos required/minlength já foram checados pelo navegador)
    mensagem.textContent = '';

    if (form.dataset.auth === 'registro') {
      const senha = form.elements['senha'].value;
      const confirmacao = form.elements['confirmacao_senha'].value;
      if (senha !== confirmacao) {
        mensagem.textContent = 'As senhas não são iguais.';
        form.elements['confirmacao_senha'].focus();
        return;
      }
    }

    /* TODO (banco de dados): enviar os dados para a API
    const dados = Object.fromEntries(new FormData(form));
    const resp = await fetch(form.getAttribute('action'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(dados),
    });
    if (!resp.ok) { mensagem.textContent = 'E-mail ou senha incorretos.'; return; }
    */

    window.location.href = 'home.html';
  });
});
