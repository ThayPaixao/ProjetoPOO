<?php
declare(strict_types=1);

/* QuadroDeAvisos – PADRÃO OBSERVER, o lado de quem "publica" (o sujeito).
   Como o quadro de avisos da vila: quem se inscreve recebe cada aviso publicado.
   O Controlador publica toda compra e venda aqui; o quadro repassa para cada observador. */
class QuadroDeAvisos
{
    /** @var ObservadorDeTransacao[] */
    private array $observadores = [];

    public function inscrever(ObservadorDeTransacao $observador): void
    {
        $this->observadores[] = $observador;
    }

    public function publicar(Transacao $transacao): void
    {
        foreach ($this->observadores as $observador) {
            $observador->notificar($transacao);
        }
    }
}
