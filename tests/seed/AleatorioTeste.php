<?php

return [
    'mesma semente gera a mesma sequência' => function () {
        $a = new Aleatorio(2026);
        $b = new Aleatorio(2026);
        $seqA = [];
        $seqB = [];
        for ($i = 0; $i < 20; $i++) {
            $seqA[] = $a->inteiro(1, 1000);
            $seqB[] = $b->inteiro(1, 1000);
        }
        afirmarIgual($seqA, $seqB);
    },

    'sementes diferentes geram sequências diferentes' => function () {
        $a = new Aleatorio(2026);
        $b = new Aleatorio(7);
        $seqA = [];
        $seqB = [];
        for ($i = 0; $i < 20; $i++) {
            $seqA[] = $a->inteiro(1, 1000);
            $seqB[] = $b->inteiro(1, 1000);
        }
        afirmar($seqA !== $seqB, 'as duas sequências saíram iguais');
    },

    'ponderado respeita pesos' => function () {
        $s = new Aleatorio(2026);
        $contagem = ['a' => 0, 'b' => 0];
        for ($i = 0; $i < 10000; $i++) {
            $contagem[$s->ponderado(['a' => 3, 'b' => 1])]++;
        }
        afirmarEntre(0.70, 0.80, $contagem['a'] / 10000, 'proporção de "a"');
    },

    'chance(0) nunca e chance(1) sempre' => function () {
        $s = new Aleatorio(2026);
        for ($i = 0; $i < 200; $i++) {
            afirmar(!$s->chance(0.0), 'chance(0) deu verdadeiro');
            afirmar($s->chance(1.0), 'chance(1) deu falso');
        }
    },

    'decimal fica no intervalo e respeita as casas' => function () {
        $s = new Aleatorio(2026);
        for ($i = 0; $i < 500; $i++) {
            $v = $s->decimal(5.2, 5.9, 4);
            afirmarEntre(5.2, 5.9, $v);
            afirmarIgual(round($v, 4), $v, 'mais de 4 casas');
        }
    },

    'normal tem média próxima da pedida' => function () {
        $s = new Aleatorio(2026);
        $soma = 0.0;
        for ($i = 0; $i < 5000; $i++) {
            $soma += $s->normal(20000, 2500);
        }
        afirmarEntre(19800, 20200, $soma / 5000, 'média');
    },
];
