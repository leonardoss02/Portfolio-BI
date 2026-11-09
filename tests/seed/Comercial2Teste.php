<?php

require_once __DIR__ . '/apoio.php';

function canalDoCard(array $d): string
{
    return in_array($d['tipoLead_dealV2'] ?? null, ['OTR', 'OTW'], true) ? 'evento' : 'digital';
}

function preenchida(mixed $data): bool
{
    return $data !== null && $data !== '';
}

return [
    'todo C5 tem fase preenchida e vem de uma consulta vendida' => function () {
        $ctx = gerarAte('comercial2', '2026-11-09', 1.0);
        $c5 = filtrar($ctx, 'dealV2', fn($d) => str_starts_with($d['faseId_dealV2'], 'C5:'));
        afirmar(count($c5) > 0, 'nenhum C5');
        foreach ($c5 as $d) {
            afirmar($d['fase_dealV2'] !== '', 'C5 sem fase');
            afirmar(isset($d['_idVenda']), 'C5 sem card de venda de origem');
        }
    },

    'realizadas ≤ vendidas e combos ≤ realizadas, por canal' => function () {
        $ctx = gerarAte('comercial2', '2026-11-09', 1.0);
        $conta = ['evento' => ['vendidas' => 0, 'realizadas' => 0, 'combos' => 0], 'digital' => ['vendidas' => 0, 'realizadas' => 0, 'combos' => 0]];
        foreach ($ctx->base->linhas('dealV2') as $d) {
            $canal = canalDoCard($d);
            if (in_array($d['faseId_dealV2'], ['WON', 'C11:WON'], true)) {
                $conta[$canal]['vendidas']++;
            }
            if (str_starts_with($d['faseId_dealV2'], 'C5:') && preenchida($d['dataConsultaRealizada_dealV2'] ?? null)) {
                $conta[$canal]['realizadas']++;
            }
            if (in_array($d['faseId_dealV2'], ['C5:WON', 'C29:WON', 'C31:WON'], true)) {
                $conta[$canal]['combos']++;
            }
        }
        foreach ($conta as $canal => $c) {
            afirmar($c['realizadas'] <= $c['vendidas'], "$canal: realizadas {$c['realizadas']} > vendidas {$c['vendidas']}");
            afirmar($c['combos'] <= $c['realizadas'], "$canal: combos {$c['combos']} > realizadas {$c['realizadas']}");
        }
    },

    'datas do C5 seguem a ordem do funil' => function () {
        $ctx = gerarAte('comercial2', '2026-11-09', 1.0);
        foreach (filtrar($ctx, 'dealV2', fn($d) => str_starts_with($d['faseId_dealV2'], 'C5:')) as $d) {
            $ordem = array_filter([
                $d['dataConsultaRealizada_dealV2'] ?? null,
                $d['dataFpAgendado_dealV2'] ?? null,
                $d['dataFpRealizado_dealV2'] ?? null,
                $d['dataContratoComboAssinado_dealV2'] ?? null,
            ]);
            $ordenada = $ordem;
            sort($ordenada);
            afirmarIgual(array_values($ordenada), array_values($ordem), "datas fora de ordem no card {$d['idBitrix_dealV2']}");
        }
    },

    'nenhum C31 tem dataFpRealizado' => function () {
        $ctx = gerarAte('comercial2', '2026-11-09', 1.0);
        $c31 = filtrar($ctx, 'dealV2', fn($d) => str_starts_with($d['faseId_dealV2'], 'C31:'));
        afirmar(count($c31) > 0, 'nenhum C31');
        foreach ($c31 as $d) {
            afirmar(!preenchida($d['dataFpRealizado_dealV2'] ?? null), 'C31 com dataFpRealizado');
        }
    },

    'existem os casos de borda do Comercial 2' => function () {
        $ctx = gerarAte('comercial2', '2026-11-09', 1.0);
        $c5 = filtrar($ctx, 'dealV2', fn($d) => str_starts_with($d['faseId_dealV2'], 'C5:'));
        $existe = fn(callable $f) => count(array_filter($c5, $f)) > 0;
        $distrato = ['C5:UC_CL1DBM', 'C5:UC_AQ2QYT'];
        afirmar($existe(fn($d) => $d['faseId_dealV2'] === 'C5:UC_0Q30H1'), 'nenhum contrato enviado');
        afirmar($existe(fn($d) => in_array($d['faseId_dealV2'], $distrato, true) && preenchida($d['dataContratoComboAssinado_dealV2'] ?? null)), 'nenhum distrato com contrato');
        afirmar($existe(fn($d) => in_array($d['faseId_dealV2'], $distrato, true) && !preenchida($d['dataContratoComboAssinado_dealV2'] ?? null) && ($d['dataVenda_dealV2'] ?? null) !== null), 'nenhum distrato sem contrato (churn de FP)');
        foreach (['Comercial 3', 'Distrato', 'Lead sem qualificação'] as $fase) {
            afirmar($existe(fn($d) => $d['fase_dealV2'] === $fase && preenchida($d['dataFpRealizado_dealV2'] ?? null)), "nenhum card com fase '$fase' e FP realizado");
        }
        afirmar($existe(fn($d) => preenchida($d['dataConsultaRealizada_dealV2'] ?? null) && preenchida($d['dataConsultaAgendada_dealV2'] ?? null)
            && substr($d['dataConsultaRealizada_dealV2'], 0, 7) !== substr($d['dataConsultaAgendada_dealV2'], 0, 7)), 'nenhuma realizada em mês diferente do agendado');
        afirmar($existe(fn($d) => ($d['serasaRange_dealV2'] ?? '') !== ''), 'nenhum card com Serasa');
        afirmar($existe(fn($d) => ($d['motivoDescarteComercial2_dealV2'] ?? '') !== ''), 'nenhum motivo de descarte do Comercial 2');
    },

    'existem C1:WON e C1:LOSE com motivo de descarte' => function () {
        $ctx = gerarAte('comercial2', '2026-11-09', 1.0);
        afirmar(count(filtrar($ctx, 'dealV2', fn($d) => $d['faseId_dealV2'] === 'C1:WON' && preenchida($d['dataContratoComboAssinado_dealV2'] ?? null))) > 0, 'nenhum C1:WON');
        afirmar(count(filtrar($ctx, 'dealV2', fn($d) => $d['faseId_dealV2'] === 'C1:LOSE' && $d['motivoDescarte_dealV2'] !== '')) > 0, 'nenhum C1:LOSE com motivo');
        foreach (filtrar($ctx, 'dealV2', fn($d) => str_starts_with($d['faseId_dealV2'], 'C1:')) as $d) {
            afirmar(in_array($d['responsavel_dealV2'], $ctx->config['equipe']['comercial3'], true), 'C1 fora do Comercial 3');
        }
    },

    'dia 1º: cada vendedor com painel tem consulta realizada e agendada no dia' => function () {
        $ctx = gerarAte('comercial2', '2026-12-01');
        foreach (['Rafael Torres', 'Juliana Prado', 'Camila Duarte', 'Beatriz Lemos'] as $vendedor) {
            $doDia = filtrar($ctx, 'dealV2', fn($d) => $d['responsavel_dealV2'] === $vendedor && str_starts_with($d['faseId_dealV2'], 'C5:'));
            $realizada = array_filter($doDia, fn($d) => ($d['dataConsultaRealizada_dealV2'] ?? null) === '2026-12-01');
            $agendada = array_filter($doDia, fn($d) => ($d['dataConsultaAgendada_dealV2'] ?? null) === '2026-12-01' && !preenchida($d['dataConsultaRealizada_dealV2'] ?? null));
            afirmar(count($realizada) >= 1, "$vendedor sem consulta realizada em 2026-12-01");
            afirmar(count($agendada) >= 1, "$vendedor sem consulta agendada e não realizada em 2026-12-01");
        }
    },

    'combos entre 380 e 600 na escala 1.0' => function () {
        $ctx = gerarAte('comercial2', '2026-11-09', 1.0);
        $combos = count(filtrar($ctx, 'dealV2', fn($d) => in_array($d['faseId_dealV2'], ['C5:WON', 'C1:WON', 'C29:WON', 'C31:WON'], true)
            || (in_array($d['faseId_dealV2'], ['C5:UC_CL1DBM', 'C5:UC_AQ2QYT'], true) && preenchida($d['dataContratoComboAssinado_dealV2'] ?? null))));
        afirmarEntre(380, 600, $combos);
    },
];
