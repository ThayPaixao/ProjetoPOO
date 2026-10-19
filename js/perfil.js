/* perfil.js – preenche nome, dinheiro, foto, inventário e histórico */
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
    Render.lista(document.getElementById('historico-lista'), historico, 'tpl-historico-item',
      { vazio: 'Você ainda não vendeu nada.' });
  } catch (erro) {
    console.error('Erro ao carregar o perfil:', erro);
  }
})();
