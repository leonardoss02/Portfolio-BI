<?php

function calendarioDeTeste(string $hoje = '2026-11-09'): Calendario
{
    $config = require dirname(__DIR__, 2) . '/database/seed/config.php';
    return new Calendario(
        new DateTimeImmutable($config['inicio']),
        new DateTimeImmutable($hoje),
        $config['calendario']
    );
}

return [
    'fim de semana pesa menos que dia útil' => function () {
        $c = calendarioDeTeste();
        // 2026-11-07 é sábado; 2026-11-09 é segunda.
        afirmar($c->peso(new DateTimeImmutable('2026-11-07')) < $c->peso(new DateTimeImmutable('2026-11-09')), 'sábado não pesou menos');
    },

    'dezembro pesa menos que novembro' => function () {
        $c = calendarioDeTeste();
        // Duas quartas-feiras, para isolar o efeito do mês.
        afirmar($c->peso(new DateTimeImmutable('2025-12-10')) < $c->peso(new DateTimeImmutable('2025-11-12')), 'dezembro não pesou menos');
    },

    'o mesmo dia de 2026 pesa mais que o de 2025' => function () {
        $c = calendarioDeTeste();
        afirmar($c->peso(new DateTimeImmutable('2026-06-10')) > $c->peso(new DateTimeImmutable('2025-06-11')), 'não houve crescimento');
    },

    'dias() começa em 2025-01-01 e termina em hoje' => function () {
        $c = calendarioDeTeste('2026-11-09');
        $dias = iterator_to_array($c->dias(), false);
        afirmarIgual('2025-01-01', $dias[0]->format('Y-m-d'));
        afirmarIgual('2026-11-09', end($dias)->format('Y-m-d'));
        afirmarIgual(678, count($dias), 'quantidade de dias');
    },

    'momento() fica entre 08:00 e 21:59 do mesmo dia' => function () {
        $c = calendarioDeTeste();
        $s = new Aleatorio(2026);
        $dia = new DateTimeImmutable('2026-03-15');
        for ($i = 0; $i < 300; $i++) {
            $m = $c->momento($dia, $s);
            afirmarIgual('2026-03-15', $m->format('Y-m-d'));
            $hora = (int) $m->format('H');
            afirmar($hora >= 8 && $hora <= 21, "hora fora do intervalo: $hora");
        }
    },
];
