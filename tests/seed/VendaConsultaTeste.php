<?php

require_once __DIR__ . '/apoio.php';

return [
    'todo card tem contato existente, fase preenchida e motivoDescarte não nulo' => function () {
        $ctx = gerarAte('vendaConsulta');
        $contatos = array_flip(array_column($ctx->base->linhas('contactV2'), 'idBitrix_contactV2'));
        foreach ($ctx->base->linhas('dealV2') as $d) {
            afirmar(isset($contatos[$d['contactId_dealV2']]), 'card sem contato');
            afirmar($d['fase_dealV2'] !== '', "card {$d['faseId_dealV2']} sem fase");
            afirmarIgual('', $d['motivoDescarte_dealV2'], 'motivoDescarte deveria ser vazio');
        }
    },

    'check-in só em cards sem prefixo e de evento passado' => function () {
        $ctx = gerarAte('vendaConsulta');
        $hoje = $ctx->hoje->format('Y-m-d');
        foreach (filtrar($ctx, 'dealV2', fn($d) => ($d['checkin_dealV2'] ?? null) === '1') as $d) {
            afirmar(!str_contains($d['faseId_dealV2'], ':'), "check-in em {$d['faseId_dealV2']}");
            afirmar($d['dataEvento_dealV2'] <= $hoje, 'check-in em evento futuro');
        }
    },

    'um único card do funil de evento por contato e evento' => function () {
        $ctx = gerarAte('vendaConsulta');
        $vistos = [];
        foreach (filtrar($ctx, 'dealV2', fn($d) => !str_contains($d['faseId_dealV2'], ':')) as $d) {
            $chave = $d['contactId_dealV2'] . '|' . $d['idEvento_dealV2'];
            afirmar(!isset($vistos[$chave]), "card de evento duplicado: $chave");
            $vistos[$chave] = true;
        }
    },

    'consultas e cadeiras vendidas têm dataVenda até hoje' => function () {
        $ctx = gerarAte('vendaConsulta');
        $fimDeHoje = $ctx->hoje->format('Y-m-d') . ' 23:59:59';
        $vendidos = filtrar($ctx, 'dealV2', fn($d) => in_array($d['faseId_dealV2'], ['WON', 'C11:WON', 'C9:WON'], true));
        afirmar(count($vendidos) > 0, 'nenhuma venda');
        foreach ($vendidos as $d) {
            afirmar($d['dataVenda_dealV2'] instanceof DateTimeInterface, "{$d['faseId_dealV2']} sem dataVenda");
            afirmar($d['dataVenda_dealV2']->format('Y-m-d H:i:s') <= $fimDeHoje, 'venda no futuro');
            afirmar($d['valorVendaDolar_dealV2'] > 0, 'venda sem valor em dólar');
        }
    },

    'taxa de cadeira por qualificado do On The Road entre 26% e 34%' => function () {
        $ctx = gerarAte('vendaConsulta');
        $qualificados = count(filtrar($ctx, 'leadV2', fn($l) => $l['qualificado_leadV2'] === 'Sim' && str_starts_with($l['formPreenchido_leadV2'], 'OTR')));
        $cadeiras = count(filtrar($ctx, 'dealV2', fn($d) => $d['faseId_dealV2'] === 'C9:WON'));
        afirmarEntre(0.26, 0.34, $cadeiras / $qualificados);
    },

    'consultas vendidas na escala 1.0 por volta de 1.500 a 2.600' => function () {
        $ctx = gerarAte('vendaConsulta');
        $consultas = count(filtrar($ctx, 'dealV2', fn($d) => in_array($d['faseId_dealV2'], ['WON', 'C11:WON'], true)));
        // Estimado a partir da escala 0.1: o piso tem folga para o ruído da amostra menor.
        afirmarEntre(1500, 2600, $consultas * 10);
    },

    'Helena Castro tem cadeira e consulta vendidas nos últimos 30 dias' => function () {
        $ctx = gerarAte('vendaConsulta', '2026-11-09', 1.0);
        $limite = $ctx->hoje->modify('-29 days')->format('Y-m-d');
        $daHelena = filtrar($ctx, 'dealV2', fn($d) => $d['responsavel_dealV2'] === 'Helena Castro'
            && $d['dataVenda_dealV2'] instanceof DateTimeInterface
            && $d['dataVenda_dealV2']->format('Y-m-d') >= $limite);
        $fases = array_count_values(array_column($daHelena, 'faseId_dealV2'));
        afirmar(($fases['C9:WON'] ?? 0) >= 1, 'Helena sem cadeira nos últimos 30 dias');
        afirmar(($fases['C11:WON'] ?? 0) >= 1, 'Helena sem consulta nos últimos 30 dias');
    },
];
