<?php
/**
 * Parâmetros do gerador de dados fictícios.
 *
 * É o único lugar para ajustar volumes, taxas de conversão, pessoas e
 * sazonalidade. O código em src/ só lê daqui.
 */

return [
    // Mesma semente = mesma base (no mesmo dia).
    'semente' => 2026,

    // Primeiro dia com dados. Cobre as datas de negócio fixas no código
    // (ciclo do Jurídico em 19/05/2025, campanha de metas em 18/05/2026).
    'inicio' => '2025-01-01',

    // Multiplica todos os volumes. Os testes usam 0.1 para rodar rápido.
    'escala' => 1.0,

    'calendario' => [
        // Quanto o movimento cresce em 24 meses (0.35 = 35% a mais).
        'crescimento24Meses' => 0.35,
        // Peso por mês (1 = janeiro). Meses ausentes valem 1.0.
        'pesoMes' => [12 => 0.75, 1 => 0.80],
        // Sábado e domingo têm menos da metade do movimento.
        'pesoFimDeSemana' => 0.45,
    ],
];
