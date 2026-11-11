<?php

require_once __DIR__ . '/apoio.php';

return [
    'um hotel por evento presencial e custoEvento igual à soma dos fornecedores' => function () {
        $ctx = gerarAte('fornecedores');
        $presenciais = filtrar($ctx, 'evento', fn($e) => str_starts_with($e['nome_evento'], 'On The Road'));
        $hoteis = array_count_values(array_column(filtrar($ctx, 'fornecedorEvento', fn($f) => $f['categoria_fornecedorEvento'] === 'hotel'), 'idEvento_fornecedorEvento'));
        foreach (array_keys($presenciais) as $id) {
            afirmarIgual(1, $hoteis[$id] ?? 0, "evento $id sem exatamente um hotel");
        }
        $soma = [];
        foreach ($ctx->base->linhas('fornecedorEvento') as $f) {
            $soma[$f['idEvento_fornecedorEvento']] = ($soma[$f['idEvento_fornecedorEvento']] ?? 0) + $f['custo_fornecedorEvento'];
            afirmarIgual(round($f['custo_fornecedorEvento'], 2), $f['custo_fornecedorEvento'], 'custo com mais de 2 casas');
        }
        foreach ($ctx->base->linhas('custoEvento') as $c) {
            afirmarEntre($soma[$c['id_evento']] - 0.01, $soma[$c['id_evento']] + 0.01, $c['valor_custoEvento'], "custo do evento {$c['id_evento']}");
        }
        afirmarIgual(count($presenciais), $ctx->base->total('custoEvento'), 'um custoEvento por evento presencial');
    },

    'transporte usa companhias fictícias, nunca marcas reais' => function () {
        $ctx = gerarAte('fornecedores');
        $reais = ['Azul', 'Gol', 'Latam', 'LATAM', 'Cometa', 'Avianca'];
        $companhias = array_unique(array_column(filtrar($ctx, 'fornecedorEvento', fn($f) => $f['categoria_fornecedorEvento'] === 'transporte'), 'companhia_fornecedorEvento'));
        afirmar(count($companhias) > 0, 'nenhum transporte');
        foreach ($companhias as $companhia) {
            afirmar(!in_array($companhia, $reais, true), "marca real no transporte: $companhia");
        }
    },

    'há hotel nos próximos 7 dias e entre 8 e 30 dias' => function () {
        $ctx = gerarAte('fornecedores');
        $hoje = $ctx->hoje;
        $dias = array_map(fn($f) => (int) $hoje->diff(new DateTimeImmutable($f['data_fornecedorEvento']))->format('%r%a'),
            filtrar($ctx, 'fornecedorEvento', fn($f) => $f['categoria_fornecedorEvento'] === 'hotel'));
        afirmar(count(array_filter($dias, fn($d) => $d >= 0 && $d <= 7)) > 0, 'nenhum hotel nos próximos 7 dias');
        afirmar(count(array_filter($dias, fn($d) => $d >= 8 && $d <= 30)) > 0, 'nenhum hotel entre 8 e 30 dias');
    },
];
