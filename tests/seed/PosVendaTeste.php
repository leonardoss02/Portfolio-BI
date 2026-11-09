<?php

require_once __DIR__ . '/apoio.php';

function cardsC3(Contexto $ctx): array
{
    return filtrar($ctx, 'dealV2', fn($d) => str_starts_with($d['faseId_dealV2'], 'C3:'));
}

function carteiraDaLiga(Contexto $ctx): array
{
    $inicio = ConstantesDoCodigo::ler('comercial4', 'LIGA_INICIO');
    $ids = array_map('strval', array_keys(ConstantesDoCodigo::ler('comercial4', 'LIGA_CSM')));
    return array_filter(cardsC3($ctx), fn($d) => in_array($d['responsavelComercial4Id_dealV2'] ?? null, $ids, true)
        && $d['criadoEm_dealV2']->format('Y-m-d') < $inicio);
}

/** Status de cada campo de produto do dataJson, pelo mapa de IDs do código. */
function statusDosProdutos(array $card): array
{
    $mapa = [];
    foreach (ConstantesDoCodigo::ler('comercial4', 'LIGA_STATUS_IDS') as $status => $ids) {
        foreach ($ids as $id) {
            $mapa[$id] = $status;
        }
    }
    $json = json_decode($card['dataJson_dealV2'], true);
    $status = [];
    foreach (array_keys(ConstantesDoCodigo::ler('comercial4', 'LIGA_PRODUTOS')) as $campo) {
        $status[] = $mapa[(string) ($json[$campo] ?? '')] ?? null;
    }
    return $status;
}

return [
    'todo combo tem exatamente um card C3' => function () {
        $ctx = gerarAte('posVenda');
        $porOrigem = array_count_values(array_column(array_filter(cardsC3($ctx), fn($d) => isset($d['_idCombo'])), '_idCombo'));
        foreach ($ctx->base->linhas('dealV2') as $id => $d) {
            $combo = in_array($d['faseId_dealV2'], ['C5:WON', 'C1:WON', 'C29:WON', 'C31:WON'], true)
                || (in_array($d['faseId_dealV2'], ['C5:UC_CL1DBM', 'C5:UC_AQ2QYT'], true) && !empty($d['dataContratoComboAssinado_dealV2']));
            if ($combo) {
                afirmarIgual(1, $porOrigem[$id] ?? 0, "combo {$d['idBitrix_dealV2']} sem exatamente um C3");
            }
        }
    },

    'cards legados existem só para completar a carteira e são de 2024' => function () {
        $ctx = gerarAte('posVenda');
        $legados = array_filter(cardsC3($ctx), fn($d) => !isset($d['_idCombo']));
        $inicio = ConstantesDoCodigo::ler('comercial4', 'LIGA_INICIO');
        $daJanela = array_filter(cardsC3($ctx), fn($d) => isset($d['_idCombo']) && $d['criadoEm_dealV2']->format('Y-m-d') < $inicio);
        afirmarIgual(max(0, ConstantesDoCodigo::ler('comercial4', 'LIGA_CARTEIRA') - count($daJanela)), count($legados), 'quantidade de legados');
        foreach ($legados as $d) {
            afirmarIgual('2024', $d['criadoEm_dealV2']->format('Y'), 'legado fora de 2024');
        }
    },

    'carteira da Liga tem 430 cards das 4 CSMs, distribuídos igualmente' => function () {
        $carteira = carteiraDaLiga(gerarAte('posVenda'));
        afirmarIgual(ConstantesDoCodigo::ler('comercial4', 'LIGA_CARTEIRA'), count($carteira));
        foreach (array_count_values(array_column($carteira, 'responsavelComercial4Id_dealV2')) as $id => $qtd) {
            afirmarEntre(100, 115, $qtd, "CSM $id");
        }
    },

    '390 cards da carteira estão totalmente mapeados' => function () {
        $mapeados = 0;
        foreach (carteiraDaLiga(gerarAte('posVenda')) as $card) {
            $status = statusDosProdutos($card);
            if (!in_array(null, $status, true) && !in_array('iniciar', $status, true)) {
                $mapeados++;
            }
        }
        afirmarIgual(390, $mapeados);
    },

    'dataJson é JSON válido ou NULL' => function () {
        foreach (cardsC3(gerarAte('posVenda')) as $d) {
            if (($d['dataJson_dealV2'] ?? null) !== null) {
                afirmar(is_array(json_decode($d['dataJson_dealV2'], true)), 'dataJson inválido');
            }
        }
    },

    'vendidos têm Vendido + APPROVED + dataVenda; os demais, dataVenda NULL' => function () {
        $ctx = gerarAte('posVenda');
        afirmar($ctx->base->total('orcamentoV2') > 0, 'nenhum orçamento');
        foreach ($ctx->base->linhas('orcamentoV2') as $o) {
            $vendido = $o['etapa_orcamentoV2'] === 'Vendido';
            afirmarIgual($vendido, $o['etapaId_orcamentoV2'] === 'APPROVED', 'etapa e etapaId não combinam');
            afirmarIgual($vendido, $o['dataVenda_orcamentoV2'] !== null, 'dataVenda não combina com a etapa');
            afirmar(in_array($o['moedaId_orcamentoV2'], ['BRL', 'USD'], true), 'moeda inválida');
        }
        foreach ($ctx->base->linhas('itemOrcamentoV2') as $item) {
            $ctx->base->linha('orcamentoV2', $item['id_orcamentoV2']); // lança se não existir
        }
    },

    'há pelo menos 8 vendas nos últimos 7 dias' => function () {
        $ctx = gerarAte('posVenda');
        $limite = $ctx->hoje->modify('-6 days')->format('Y-m-d');
        $semana = filtrar($ctx, 'orcamentoV2', fn($o) => ($o['dataVenda_orcamentoV2'] ?? '') >= $limite);
        afirmar(count($semana) >= 8, 'só ' . count($semana) . ' vendas na semana');
    },

    'há tarefa de prioridade alta não concluída em caso do Jurídico' => function () {
        $ctx = gerarAte('posVenda');
        $fasesJuridico = ['Milestone 1', 'Milestone 2', 'Milestone 3', 'Aplicados', 'Aprovado USCIS', 'NVC'];
        $casos = array_flip(array_column(array_filter(cardsC3($ctx), fn($d) => in_array($d['fase_dealV2'], $fasesJuridico, true)), 'idBitrix_dealV2'));
        $altas = filtrar($ctx, 'taskV2', fn($t) => $t['priority_taskV2'] === 2 && $t['status_taskV2'] !== 'Concluída' && isset($casos[$t['idDeal_taskV2']]));
        afirmar(count($altas) > 0, 'nenhuma tarefa de alta prioridade aberta');
    },
];
