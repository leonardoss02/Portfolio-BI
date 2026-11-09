<?php

require_once __DIR__ . '/apoio.php';

return [
    'toda data de custoMkt em Conta BRL tem câmbio no mesmo dia' => function () {
        $ctx = gerarAte('marketing');
        $datasCambio = array_flip(array_column($ctx->base->linhas('cambio'), 'data_cambio'));
        foreach ($ctx->base->linhas('custoMkt') as $c) {
            if (!in_array($c['conta_custoMkt'], ['Dollar 1', 'Dollar 2'], true)) {
                afirmar(isset($datasCambio[$c['data_custoMkt']]), "sem câmbio em {$c['data_custoMkt']}");
            }
        }
    },

    'moedas seguem as duas regras do código (2025 por origem, 2026 por conta)' => function () {
        $ctx = gerarAte('marketing');
        foreach ($ctx->base->linhas('custoMkt') as $c) {
            $usd = in_array($c['conta_custoMkt'], ['Dollar 1', 'Dollar 2'], true);
            afirmarIgual($usd, $c['origem_custoMkt'] === 'meta', "origem {$c['origem_custoMkt']} com conta {$c['conta_custoMkt']}");
        }
    },

    'todo evento que já começou a divulgar tem campanha com custo' => function () {
        $ctx = gerarAte('marketing');
        $comCusto = array_flip(array_column($ctx->base->linhas('custoMkt'), 'nomeCampanha_custoMkt'));
        foreach ($ctx->base->linhas('evento') as $e) {
            $inicioDivulgacao = (new DateTimeImmutable($e['data_evento']))->modify('-30 days');
            if ($inicioDivulgacao <= $ctx->hoje) {
                afirmar(isset($comCusto[$e['nomeForm_evento']]), "evento sem custo: {$e['nomeForm_evento']}");
            }
        }
    },

    'campanhaPara(digital) encontra campanha em qualquer dia da janela' => function () {
        $ctx = gerarAte('marketing');
        foreach ($ctx->calendario->dias() as $dia) {
            $campanha = Marketing::campanhaPara($ctx, $dia, 'digital');
            afirmar($campanha !== null, 'sem campanha digital em ' . $dia->format('Y-m-d'));
        }
    },

    'campanhaPara(evento) só devolve campanha ativa no dia' => function () {
        $ctx = gerarAte('marketing');
        $dia = new DateTimeImmutable('2026-03-10');
        $campanha = Marketing::campanhaPara($ctx, $dia, 'evento');
        afirmar($campanha !== null, 'sem campanha de evento em 2026-03-10');
        afirmar($campanha['inicio'] <= '2026-03-10' && $campanha['fim'] >= '2026-03-10', 'campanha fora do período');
        afirmar($campanha['idEvento'] !== null, 'campanha de evento sem idEvento');
    },

    'há linhas de evento com identificador vazio (fallback pelo nome da campanha)' => function () {
        $ctx = gerarAte('marketing');
        $vazios = filtrar($ctx, 'custoMkt', fn($c) => $c['identificador_custoMkt'] === '' && str_starts_with($c['nomeCampanha_custoMkt'], 'OTR'));
        afirmar(count($vazios) > 0, 'nenhuma linha OTR com identificador vazio');
    },

    'volume de custoMkt entre 14.000 e 26.000 linhas' => function () {
        afirmarEntre(14000, 26000, gerarAte('marketing')->base->total('custoMkt'));
    },
];
