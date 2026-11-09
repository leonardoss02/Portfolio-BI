<?php

require_once __DIR__ . '/apoio.php';

return [
    'conversas apontam para leads e cobrem status e respostas ausentes' => function () {
        $ctx = gerarAte('atendimento');
        $leads = array_flip(array_column($ctx->base->linhas('leadV2'), 'idBitrix_leadV2'));
        $conversas = $ctx->base->linhas('conversaCW');
        afirmar(count($conversas) > 0, 'nenhuma conversa');
        foreach ($conversas as $c) {
            afirmar(isset($leads[(string) $c['idLeadBitrix_conversaCW']]), 'conversa sem lead');
        }
        $status = array_unique(array_column($conversas, 'status_conversaCW'));
        foreach (['open', 'resolved', 'pending', 'snoozed'] as $esperado) {
            afirmar(in_array($esperado, $status, true), "nenhuma conversa '$esperado'");
        }
        afirmar(count(array_filter($conversas, fn($c) => $c['primeiraRespostaEm_conversaCW'] === null)) > 0, 'nenhuma sem resposta');
        afirmar(count(array_filter($conversas, fn($c) => $c['primeiraRespostaEm_conversaCW'] === '1969-12-31 21:00:00')) > 0, 'nenhuma com o sentinela');
        afirmar($ctx->base->total('msgCW') > count($conversas), 'poucas mensagens');
    },

    'dia 1º: há conversas do time no próprio dia' => function () {
        $ctx = gerarAte('atendimento', '2026-12-01');
        $time = $ctx->config['equipe']['atendimento'];
        $doDia = array_filter($ctx->base->linhas('conversaCW'), fn($c) => in_array($c['responsavel_conversaCW'], $time, true)
            && $c['criadoEm_conversaCW']->format('Y-m-d') === '2026-12-01'
            && $c['primeiraRespostaEm_conversaCW'] instanceof DateTimeInterface);
        afirmar(count($doDia) >= 5, 'só ' . count($doDia) . ' conversas válidas em 2026-12-01');
    },
];
