<?php

/**
 * A linha do tempo da base: de 01/01/2025 até "hoje".
 *
 * O peso de cada dia controla quanto movimento ele tem. Três efeitos se
 * multiplicam: a empresa cresce com o tempo, dezembro e janeiro são mais
 * fracos e o fim de semana tem menos gente preenchendo formulário.
 */
final class Calendario
{
    public function __construct(
        private DateTimeImmutable $inicio,
        private DateTimeImmutable $hoje,
        private array $config,
    ) {
        $this->inicio = $inicio->setTime(0, 0);
        $this->hoje = $hoje->setTime(0, 0);
    }

    public function inicio(): DateTimeImmutable
    {
        return $this->inicio;
    }

    public function hoje(): DateTimeImmutable
    {
        return $this->hoje;
    }

    /** Cada dia da janela, do início até hoje (inclusive). */
    public function dias(): Generator
    {
        for ($dia = $this->inicio; $dia <= $this->hoje; $dia = $dia->modify('+1 day')) {
            yield $dia;
        }
    }

    public function peso(DateTimeImmutable $dia): float
    {
        $meses = ((int) $dia->format('Y') - (int) $this->inicio->format('Y')) * 12
            + (int) $dia->format('n') - (int) $this->inicio->format('n');
        $crescimento = 1 + $this->config['crescimento24Meses'] * $meses / 24;

        $mes = $this->config['pesoMes'][(int) $dia->format('n')] ?? 1.0;

        $fimDeSemana = (int) $dia->format('N') >= 6 ? $this->config['pesoFimDeSemana'] : 1.0;

        return $crescimento * $mes * $fimDeSemana;
    }

    /** Um horário do dia, entre $horaMin:00 e $horaMax:59. */
    public function momento(DateTimeImmutable $dia, Aleatorio $s, int $horaMin = 8, int $horaMax = 21): DateTimeImmutable
    {
        return $dia->setTime($s->inteiro($horaMin, $horaMax), $s->inteiro(0, 59), $s->inteiro(0, 59));
    }
}
