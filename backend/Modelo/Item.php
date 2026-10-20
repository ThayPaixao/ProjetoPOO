<?php
declare(strict_types=1);

/* Item – classe base (abstrata) de tudo que se compra e vende no site.
   HERANÇA: Peixe, Colheita, Comida e Minerio herdam daqui.
   Um objeto Item é o item do catálogo (ex.: "Atum, 100 g"). A unidade que o
   jogador compra, com a qualidade e o tamanho sorteados, é um UnidadeItem. */
abstract class Item
{
    protected int $id;
    protected string $nome;
    protected int $precoBase;
    protected ?string $imagem;

    public function __construct(int $id, string $nome, int $precoBase, ?string $imagem = null)
    {
        if ($precoBase <= 0) {
            throw new InvalidArgumentException("O preço de \"$nome\" precisa ser maior que zero.");
        }
        $this->id = $id;
        $this->nome = $nome;
        $this->precoBase = $precoBase;
        $this->imagem = $imagem;
    }

    /* Cada subclasse diz qual é o seu tipo (Peixe, Colheita...). */
    abstract public function getTipo(): string;

    /* Preço de uma unidade: preço base × qualidade.
       POLIMORFISMO: o Peixe sobrescreve este método para somar o bônus de tamanho. */
    public function calcularPreco(Qualidade $qualidade, ?int $tamanhoCm = null): int
    {
        return (int) round($this->precoBase * $qualidade->multiplicador());
    }

    /* Cria a unidade que o jogador leva ao comprar. Por padrão só a qualidade é sorteada.
       POLIMORFISMO: o Peixe sobrescreve para sortear o tamanho também. */
    public function sortearUnidade(): UnidadeItem
    {
        $qualidade = Qualidade::sortear();
        return new UnidadeItem($this, $qualidade, null, $this->calcularPreco($qualidade));
    }

    /* Fábrica: cria a subclasse certa a partir da classificação que vem do banco. */
    public static function criar(string $tipo, int $id, string $nome, int $precoBase, ?string $imagem = null): Item
    {
        return match ($tipo) {
            'Peixe'    => new Peixe($id, $nome, $precoBase, $imagem),
            'Colheita' => new Colheita($id, $nome, $precoBase, $imagem),
            'Comida'   => new Comida($id, $nome, $precoBase, $imagem),
            'Minério'  => new Minerio($id, $nome, $precoBase, $imagem),
            default    => throw new InvalidArgumentException("Tipo de item desconhecido: $tipo"),
        };
    }

    public function getId(): int { return $this->id; }
    public function getNome(): string { return $this->nome; }
    public function getPrecoBase(): int { return $this->precoBase; }
    public function getImagem(): ?string { return $this->imagem; }

    /* Formato que o front espera (js/api.js): { id, nome, preco, tipo, qualidade, imagem } */
    public function paraArray(): array
    {
        return [
            'id'        => $this->id,
            'nome'      => $this->nome,
            'preco'     => $this->precoBase,
            'tipo'      => $this->getTipo(),
            'qualidade' => Qualidade::NORMAL->value,
            'imagem'    => $this->imagem,
        ];
    }
}
