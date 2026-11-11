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
        public readonly DateTimeImmutable $agora,
    ) {}

    /**
     * $agora limita os horários de hoje. Sem ele (testes), vale o fim do dia;
     * o gerar.php passa a hora real quando a data é hoje.
     */
    public static function criar(string $hoje, array $config, ?DateTimeImmutable $agora = null): self
    {
        $dataHoje = DateTimeImmutable::createFromFormat('!Y-m-d', $hoje);
        if ($dataHoje === false) {
            throw new InvalidArgumentException("Data inválida: $hoje (use AAAA-MM-DD).");
        }
        if ($hoje < self::HOJE_MINIMO) {
            throw new InvalidArgumentException("A data de hoje precisa ser a partir de " . self::HOJE_MINIMO . ".");
        }
        $agora ??= $dataHoje->setTime(23, 59, 59);
        return new self(
            $dataHoje,
            new Aleatorio($config['semente']),
            new Calendario(new DateTimeImmutable($config['inicio']), $dataHoje, $config['calendario'], $agora),
            $config,
            new Base(),
            $agora,
        );
    }

    /**
     * Um horário que não passa de agora. Quando passaria, recua de 1 a 59
     * minutos conforme $variacao (ex.: o id do registro), para os horários
     * não se empilharem todos no mesmo instante. Não gasta sorteio.
     */
    public function limitar(DateTimeImmutable $momento, int $variacao): DateTimeImmutable
    {
        if ($momento <= $this->agora) {
            return $momento;
        }
        $recuado = $this->agora->modify('-' . (abs($variacao) % 59 + 1) . ' minutes');
        return max($recuado, $this->agora->setTime(0, 0));
    }

    /** Volume ajustado pela escala do config (os testes usam escala 0.1). */
    public function escalar(float $quantidade): float
    {
        return $quantidade * $this->config['escala'];
    }
}
