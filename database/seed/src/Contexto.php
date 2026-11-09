<?php

/**
 * O que toda etapa do gerador recebe: o dia de hoje, o sorteador, o
 * calendário, o config e a Base onde as linhas vão sendo inseridas.
 */
final class Contexto
{
    /** A campanha de metas do CS começa em 18/05/2026; antes disso a base não faz sentido. */
    public const HOJE_MINIMO = '2026-06-01';

    /** Catálogo de campanhas de marketing, preenchido pela etapa Marketing. */
    public array $campanhas = [];

    /** Número do próximo cliente fictício (deixa cada e-mail único). */
    public int $proximoCliente = 1;

    public function __construct(
        public readonly DateTimeImmutable $hoje,
        public readonly Aleatorio $sorteio,
        public readonly Calendario $calendario,
        public readonly array $config,
        public readonly Base $base,
    ) {}

    public static function criar(string $hoje, array $config): self
    {
        $dataHoje = DateTimeImmutable::createFromFormat('!Y-m-d', $hoje);
        if ($dataHoje === false) {
            throw new InvalidArgumentException("Data inválida: $hoje (use AAAA-MM-DD).");
        }
        if ($hoje < self::HOJE_MINIMO) {
            throw new InvalidArgumentException("A data de hoje precisa ser a partir de " . self::HOJE_MINIMO . ".");
        }
        return new self(
            $dataHoje,
            new Aleatorio($config['semente']),
            new Calendario(new DateTimeImmutable($config['inicio']), $dataHoje, $config['calendario']),
            $config,
            new Base(),
        );
    }

    /** Volume ajustado pela escala do config (os testes usam escala 0.1). */
    public function escalar(float $quantidade): float
    {
        return $quantidade * $this->config['escala'];
    }
}
