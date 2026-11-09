<?php

require_once __DIR__ . '/apoio.php';

return [
    'leads entre 20.000 e 30.000 na escala 1.0' => function () {
        // Estimado a partir da escala 0.1, para o teste rodar rápido.
        afirmarEntre(20000, 30000, gerarAte('leads')->base->total('leadV2') * 10);
    },

    'proporção de canais dentro de ±5 pontos' => function () {
        $ctx = gerarAte('leads');
        $total = $ctx->base->total('leadV2');
        $porCanal = array_count_values(array_column($ctx->base->linhas('leadV2'), '_canal'));
        foreach (['evento' => 0.45, 'digital' => 0.40, 'organico' => 0.08, 'indicacao' => 0.07] as $canal => $alvo) {
            afirmarEntre($alvo - 0.05, $alvo + 0.05, ($porCanal[$canal] ?? 0) / $total, "canal $canal");
        }
    },

    'qualificados entre 40% e 50%' => function () {
        $ctx = gerarAte('leads');
        $qualificados = count(filtrar($ctx, 'leadV2', fn($l) => $l['qualificado_leadV2'] === 'Sim'));
        afirmarEntre(0.40, 0.50, $qualificados / $ctx->base->total('leadV2'));
    },

    'todo lead de evento tem idEvento e formulário do evento' => function () {
        $ctx = gerarAte('leads');
        foreach (filtrar($ctx, 'leadV2', fn($l) => $l['_canal'] === 'evento') as $l) {
            afirmar(ctype_digit((string) $l['idEvento_leadV2']), 'lead de evento sem idEvento');
            afirmar(in_array(substr($l['formPreenchido_leadV2'], 0, 3), ['OTR', 'WEB', 'INA'], true), "form inválido: {$l['formPreenchido_leadV2']}");
            $evento = $ctx->base->linha('evento', (int) $l['idEvento_leadV2']);
            afirmarIgual($evento['nomeForm_evento'], $l['formPreenchido_leadV2'], 'form diferente do evento');
        }
    },

    'utms do lead existem no catálogo de campanhas' => function () {
        $ctx = gerarAte('leads');
        $catalogo = array_column($ctx->campanhas, null, 'nome');
        foreach ($ctx->base->linhas('leadV2') as $l) {
            $campanha = $catalogo[$l['utmCampaign_leadV2']] ?? null;
            afirmar($campanha !== null, "campanha fora do catálogo: {$l['utmCampaign_leadV2']}");
            if ($campanha['conjuntos']) {
                afirmar(in_array($l['utmContent_leadV2'], array_column($campanha['conjuntos'], 'nome'), true), 'conjunto fora da campanha');
            }
        }
    },

    'idBitrix_leadV2 é único e só tem dígitos; todo lead tem contato' => function () {
        $ctx = gerarAte('leads');
        $ids = array_column($ctx->base->linhas('leadV2'), 'idBitrix_leadV2');
        afirmarIgual(count($ids), count(array_unique($ids)), 'idBitrix repetido');
        foreach ($ids as $id) {
            afirmar(ctype_digit($id), "idBitrix com caractere não numérico: $id");
        }
        $contatos = array_flip(array_column($ctx->base->linhas('contactV2'), 'idBitrix_contactV2'));
        foreach ($ctx->base->linhas('leadV2') as $l) {
            afirmar(isset($contatos[$l['_idContato']]), 'lead sem contato');
        }
    },

    'existem leads de afiliado, descartados e tipoLead vazio e nulo' => function () {
        $ctx = gerarAte('leads');
        afirmar(count(filtrar($ctx, 'leadV2', fn($l) => ($l['idAfiliado_leadV2'] ?? '') !== '')) > 0, 'nenhum afiliado');
        afirmar(count(filtrar($ctx, 'leadV2', fn($l) => $l['etapa_leadV2'] === 'Lead Descartado' && $l['motivoDescarte_leadV2'] !== null)) > 0, 'nenhum descartado com motivo');
        afirmar(count(filtrar($ctx, 'leadV2', fn($l) => $l['tipoLead_leadV2'] === '')) > 0, 'nenhum tipoLead vazio');
        afirmar(count(filtrar($ctx, 'leadV2', fn($l) => $l['tipoLead_leadV2'] === null)) > 0, 'nenhum tipoLead nulo');
        afirmar(count(filtrar($ctx, 'leadV2', fn($l) => $l['formacaoAcademica_leadV2'] === '')) > 0, 'nenhuma formação vazia');
    },

    'responsáveis seguem o canal' => function () {
        $ctx = gerarAte('leads');
        $equipe = $ctx->config['equipe'];
        foreach ($ctx->base->linhas('leadV2') as $l) {
            $esperados = match ($l['_canal']) {
                'evento' => $equipe['cadeiras'],
                'digital' => array_merge([$equipe['helena']], $equipe['comercial1_digital']),
                default => [$equipe['jornada_extra']],
            };
            afirmar(in_array($l['responsavel_leadV2'], $esperados, true), "responsável {$l['responsavel_leadV2']} no canal {$l['_canal']}");
        }
    },

    'nenhum lead depois de hoje' => function () {
        $ctx = gerarAte('leads');
        $fimDeHoje = $ctx->hoje->format('Y-m-d') . ' 23:59:59';
        foreach ($ctx->base->linhas('leadV2') as $l) {
            afirmar($l['dataUltForm_leadV2']->format('Y-m-d H:i:s') <= $fimDeHoje, 'lead no futuro');
        }
    },
];
