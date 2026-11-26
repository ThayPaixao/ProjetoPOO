<?php
declare(strict_types=1);

/* ObservadorDeTransacao – PADRÃO OBSERVER, o lado de quem "escuta".
   Qualquer classe que queira saber quando uma compra ou venda acontece implementa esta
   INTERFACE e se inscreve no QuadroDeAvisos. Quem publica não precisa saber quem está ouvindo.
   Hoje quem escuta é o ObservadorDeConquistas; para criar outro efeito (ex.: um log de
   movimentações), basta outra classe com este método, sem mexer na compra nem na venda. */
interface ObservadorDeTransacao
{
    /* Chamado logo depois de a transação ser executada (saldo já alterado), antes de ir para o banco. */
    public function notificar(Transacao $transacao): void;
}
