<?php

require_once __DIR__ . '/apoio.php';

function cardsC33(Contexto $ctx): array
{
    return filtrar($ctx, 'dealV2', fn($d) => str_starts_with($d['faseId_dealV2'], 'C33:'));
}

return [
    'nenhum card C33 antes de 2026-08-31' => function () {
        $c33 = cardsC33(gerarAte('dentistas', '2026-11-09', 1.0));
        afirmar(count($c33) > 0, 'nenhum C33');
        foreach ($c33 as $d) {
            afirmar($d['criadoEm_dealV2']->format('Y-m-d') >= '2026-08-31', 'C33 antes do início do segmento');
        }
    },

    'existem check-ins, consultas realizadas e contratos' => function () {
        $c33 = cardsC33(gerarAte('dentistas', '2026-11-09', 1.0));
        afirmar(count(array_filter($c33, fn($d) => ($d['checkin_dealV2'] ?? null) === '1')) > 0, 'nenhum check-in');
        afirmar(count(array_filter($c33, fn($d) => !empty($d['dataConsultaRealizada_dealV2']))) > 0, 'nenhuma consulta realizada');
        afirmar(count(array_filter($c33, fn($d) => $d['faseId_dealV2'] === 'C33:WON')) > 0, 'nenhum contrato');
    },

    'todo C33:WON tem contrato e todo C33 tem contato e perfil de dentista' => function () {
        $ctx = gerarAte('dentistas', '2026-11-09', 1.0);
        $contatos = array_flip(array_column($ctx->base->linhas('contactV2'), 'idBitrix_contactV2'));
        foreach (cardsC33($ctx) as $d) {
            afirmar(isset($contatos[$d['contactId_dealV2']]), 'C33 sem contato');
            afirmar(str_starts_with($d['dentistaEvento_dealV2'], 'Encontro de Dentistas '), 'C33 sem evento de dentistas');
            afirmar(in_array($d['profissao_dealV2'], $ctx->config['dentistas']['profissoes'], true), 'profissão fora da lista');
            if ($d['faseId_dealV2'] === 'C33:WON') {
                afirmar(!empty($d['dataContratoComboAssinado_dealV2']), 'C33:WON sem data de contrato');
                afirmar($d['dataContratoComboAssinado_dealV2'] <= $ctx->hoje->format('Y-m-d'), 'contrato no futuro');
            }
        }
    },
];
