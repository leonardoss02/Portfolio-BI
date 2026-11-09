<?php

require_once __DIR__ . '/apoio.php';

return [
    'respostas de NPS dos dois tipos, com notas entre 0 e 10' => function () {
        $ctx = gerarAte('pesquisas');
        $respostas = $ctx->base->linhas('npsEvento');
        $tipos = array_count_values(array_column($respostas, 'tipo_npsEvento'));
        afirmar(($tipos['comprou'] ?? 0) > 0 && ($tipos['nao_comprou'] ?? 0) > 0, 'faltam respostas de um dos tipos');
        foreach ($respostas as $r) {
            foreach (['expGeral', 'local', 'coffee', 'palestrante', 'recepcao', 'brindes', 'abordagem', 'indicacao'] as $nota) {
                afirmarEntre(0, 10, $r["{$nota}_npsEvento"], $nota);
            }
        }
    },

    'planilha casa por e-mail, só pelo nome e tem linhas fora do CRM' => function () {
        $ctx = gerarAte('pesquisas');
        $porEmail = [];
        $porNome = [];
        foreach ($ctx->base->linhas('dealV2') as $d) {
            if (str_starts_with($d['faseId_dealV2'], 'C5:')) {
                $porEmail[$d['email_dealV2']] = true;
                $porNome[$d['nome_dealV2']] = true;
            }
        }
        $tipos = ['email' => 0, 'nome' => 0, 'fora' => 0];
        foreach ($ctx->base->linhas('planilhaConsulta') as $p) {
            $tipo = isset($porEmail[$p['email_planilhaConsulta']]) ? 'email' : (isset($porNome[$p['nomeCliente_planilhaConsulta']]) ? 'nome' : 'fora');
            $tipos[$tipo]++;
        }
        foreach ($tipos as $tipo => $qtd) {
            afirmar($qtd > 0, "nenhuma linha do tipo '$tipo'");
        }
    },

    'dia 1º: a planilha tem consulta do próprio dia' => function () {
        $ctx = gerarAte('pesquisas', '2026-12-01');
        $hoje = filtrar($ctx, 'planilhaConsulta', fn($p) => $p['dataConsulta_planilhaConsulta'] === '2026-12-01');
        afirmar(count($hoje) > 0, 'nenhuma linha em 2026-12-01');
    },
];
