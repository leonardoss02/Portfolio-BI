<?php

/**
 * Sorteios determinísticos.
 *
 * Toda aleatoriedade do gerador passa por aqui. Com a mesma semente, a base
 * sai sempre igual — por isso nunca se chama rand() ou mt_rand() direto.
 */
final class Aleatorio
{
    private Random\Randomizer $sorteador;

    public function __construct(int $semente)
    {
        $this->sorteador = new Random\Randomizer(new Random\Engine\Mt19937($semente));
    }

    public function inteiro(int $min, int $max): int
    {
        return $this->sorteador->getInt($min, $max);
    }

    public function decimal(float $min, float $max, int $casas = 2): float
    {
        $fator = 10 ** $casas;
        return $this->inteiro((int) ceil($min * $fator), (int) floor($max * $fator)) / $fator;
    }

    /** Verdadeiro com probabilidade $p (0 = nunca, 1 = sempre). */
    public function chance(float $p): bool
    {
        if ($p <= 0) {
            return false;
        }
        if ($p >= 1) {
            return true;
        }
        return $this->sorteador->nextFloat() < $p;
    }

    public function um(array $itens): mixed
    {
        if (!$itens) {
            throw new InvalidArgumentException('Não há itens para sortear.');
        }
        $itens = array_values($itens);
        return $itens[$this->inteiro(0, count($itens) - 1)];
    }

    /** N chaves distintas do array (no lugar de array_rand, que não respeita a semente). */
    public function chaves(array $itens, int $quantidade): array
    {
        return $this->sorteador->pickArrayKeys($itens, $quantidade);
    }

    /** Sorteia uma chave de acordo com o peso: ['a' => 3, 'b' => 1] dá "a" 75% das vezes. */
    public function ponderado(array $pesos): int|string
    {
        $total = array_sum($pesos);
        if ($total <= 0) {
            throw new InvalidArgumentException('A soma dos pesos precisa ser positiva.');
        }
        $alvo = $this->sorteador->nextFloat() * $total;
        foreach ($pesos as $chave => $peso) {
            $alvo -= $peso;
            if ($alvo < 0) {
                return $chave;
            }
        }
        return array_key_last($pesos);
    }

    /** Distribuição normal (Box-Muller). */
    public function normal(float $media, float $desvio): float
    {
        $u1 = max($this->sorteador->nextFloat(), 1e-12);
        $u2 = $this->sorteador->nextFloat();
        return $media + $desvio * sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);
    }
}
