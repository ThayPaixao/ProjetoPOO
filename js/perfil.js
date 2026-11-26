/* perfil.js – preenche nome, dinheiro, foto, inventário e histórico; botões editar e sair */
const FOTO_PADRAO = 'assets/img/avatar-placeholder.svg';
let usuarioAtual = null;

function mostrarUsuario(usuario) {
  usuarioAtual = usuario;
  document.getElementById('perfil-nome').textContent = usuario.nome;
  document.getElementById('perfil-dinheiro').textContent = Render.preco(usuario.dinheiro);
  document.getElementById('perfil-foto').src = usuario.foto || FOTO_PADRAO;
}

document.getElementById('btn-sair').addEventListener('click', async (e) => {
  e.preventDefault();
  await API.sair();                        // encerra a sessão no servidor
  window.location.href = 'index.html';
});

/* ---------- editar perfil (foto, nome e senha) ---------- */
const dialogoPerfil = document.getElementById('editar-perfil');
const formPerfil = document.getElementById('form-perfil');
const msgPerfil = formPerfil.querySelector('.form__mensagem');
const previaPerfil = formPerfil.querySelector('#foto-previa');
const escolhaFoto = FotoPerfil.ligar(formPerfil.elements['foto'], previaPerfil, (m) => { msgPerfil.textContent = m; });

document.getElementById('btn-editar').addEventListener('click', () => {
  formPerfil.reset();
  msgPerfil.textContent = '';
  escolhaFoto.arquivo = null;
  formPerfil.elements['nome'].value = usuarioAtual?.nome ?? '';
  previaPerfil.src = usuarioAtual?.foto || FOTO_PADRAO;
  dialogoPerfil.showModal();
});
formPerfil.querySelector('[data-fechar]').addEventListener('click', () => dialogoPerfil.close());

formPerfil.addEventListener('submit', async (e) => {
  e.preventDefault();
  const dados = new FormData(formPerfil);
  if (dados.get('nova_senha') && dados.get('nova_senha') !== dados.get('confirmacao_senha')) {
    msgPerfil.textContent = 'A nova senha e a confirmação não são iguais.';
    return;
  }
  if (escolhaFoto.arquivo) dados.set('foto', escolhaFoto.arquivo, 'foto.jpg');
  else dados.delete('foto');

  const salvar = formPerfil.querySelector('[type="submit"]');
  salvar.disabled = true;
  const resposta = await API.editarPerfil(dados);
  salvar.disabled = false;
  if (!resposta.ok) {
    msgPerfil.textContent = resposta.erro;   // ex.: "A senha atual está errada."
    return;
  }
  mostrarUsuario(resposta.usuario);
  dialogoPerfil.close();
});

/* ---------- carrega a página ---------- */
(async () => {
  try {
    const [usuario, inventario, historico] = await Promise.all([
      API.getUsuario(), API.getInventario(), API.getHistoricoVendas(),
    ]);
    mostrarUsuario(usuario);
    Render.lista(document.getElementById('inventario-lista'), inventario, 'tpl-item-card',
      { vazio: 'Seu inventário está vazio.' });
    Render.lista(document.getElementById('historico-lista'),
      historico.map(venda => ({ ...venda, npc: `para ${venda.npc}` })), 'tpl-historico-item',
      { vazio: 'Você ainda não vendeu nada.' });
  } catch (erro) {
    console.error('Erro ao carregar o perfil:', erro);
  }
})();
