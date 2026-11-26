<?php
declare(strict_types=1);

/* ObservadorDeConquistas – escuta o QuadroDeAvisos (PADRÃO OBSERVER) e descobre quais
   conquistas a compra ou venda acabou de liberar, para o pop-up da loja comemorar.
   Como ele é avisado ANTES de a transação ir para o banco, as estatísticas lidas do banco
   são as de antes; as de depois vêm de transacao->registrarEm() (POLIMORFISMO: a Compra
   e a Venda entram nas estatísticas cada uma do seu jeito). */
class ObservadorDeConquistas implements ObservadorDeTransacao
{
    /** @var Conquista[] */
    private array $novas = [];

    /** @param Closure(int): Estatisticas $buscarEstatisticas  como ler as estatísticas de um jogador */
    public function __construct(private Closure $buscarEstatisticas)
    {
    }

    public function notificar(Transacao $transacao): void
    {
        $antes = ($this->buscarEstatisticas)((int) $transacao->getJogador()->getId());
        $depois = $transacao->registrarEm($antes);
        foreach (Conquista::cases() as $conquista) {
            if (!$conquista->alcancada($antes) && $conquista->alcancada($depois)) {
                $this->novas[] = $conquista;
            }
        }
    }

    /** @return Conquista[]  as conquistas liberadas desde que este observador foi criado */
    public function novas(): array
    {
        return $this->novas;
    }
}
