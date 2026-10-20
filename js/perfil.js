/* perfil.js – preenche nome, dinheiro, foto, inventário e histórico; botão sair */
document.getElementById('btn-sair').addEventListener('click', async (e) => {
  e.preventDefault();
  await API.sair();                        // encerra a sessão no servidor
  window.location.href = 'index.html';
});

(async () => {
  try {
    const [usuario, inventario, historico] = await Promise.all([
      API.getUsuario(), API.getInventario(), API.getHistoricoVendas(),
    ]);

    document.getElementById('perfil-nome').textContent = usuario.nome;
    document.getElementById('perfil-dinheiro').textContent = Render.preco(usuario.dinheiro);
    if (usuario.foto) document.getElementById('perfil-foto').src = usuario.foto;

    Render.lista(document.getElementById('inventario-lista'), inventario, 'tpl-item-card',
      { vazio: 'Seu inventário está vazio.' });
    Render.lista(document.getElementById('historico-lista'),
      historico.map(venda => ({ ...venda, npc: `para ${venda.npc}` })), 'tpl-historico-item',
      { vazio: 'Você ainda não vendeu nada.' });
  } catch (erro) {
    console.error('Erro ao carregar o perfil:', erro);
  }
})();
