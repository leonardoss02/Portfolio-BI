<?php

require_once __DIR__ . '/apoio.php';

return [
    'há eventos futuros entre 1 e 45 dias e pelo menos 2 passados' => function () {
        $ctx = gerarAte('eventos');
        $hoje = $ctx->hoje->format('Y-m-d');
        $limite = $ctx->hoje->modify('+45 days')->format('Y-m-d');
        $passados = filtrar($ctx, 'evento', fn($e) => $e['data_evento'] <= $hoje);
        $futuros = filtrar($ctx, 'evento', fn($e) => $e['data_evento'] > $hoje);
        afirmar(count($passados) >= 2, 'menos de 2 eventos passados');
        afirmar(count($futuros) >= 1, 'nenhum evento futuro');
        foreach ($futuros as $e) {
            afirmar($e['data_evento'] <= $limite, "evento além de 45 dias: {$e['data_evento']}");
        }
    },

    'eventos presenciais são semanais, no sábado, desde 2025-01-04' => function () {
        $ctx = gerarAte('eventos');
        $presenciais = filtrar($ctx, 'evento', fn($e) => str_starts_with($e['nome_evento'], 'On The Road'));
        $datas = array_column($presenciais, 'data_evento');
        sort($datas);
        afirmarIgual('2025-01-04', $datas[0]);
        foreach ($datas as $data) {
            afirmarIgual('6', (new DateTimeImmutable($data))->format('N'), "$data não é sábado");
        }
        afirmarIgual(count($datas), count(array_unique($datas)), 'dois presenciais na mesma data');
    },

    'nomeForm_evento é único e começa com OTR, WEB ou INA' => function () {
        $ctx = gerarAte('eventos');
        $forms = array_column($ctx->base->linhas('evento'), 'nomeForm_evento');
        afirmarIgual(count($forms), count(array_unique($forms)), 'nomeForm repetido');
        foreach ($forms as $form) {
            afirmar(in_array(substr($form, 0, 3), ['OTR', 'WEB', 'INA'], true), "prefixo inválido: $form");
        }
        afirmar(count(array_filter($forms, fn($f) => str_starts_with($f, 'WEB'))) >= 1, 'nenhum webinar');
        afirmar(count(array_filter($forms, fn($f) => str_starts_with($f, 'INA'))) >= 1, 'nenhum 1:1');
    },

    'cidade não se repete em menos de 8 semanas' => function () {
        $ctx = gerarAte('eventos');
        $ultimaVez = [];
        $presenciais = filtrar($ctx, 'evento', fn($e) => str_starts_with($e['nome_evento'], 'On The Road'));
        usort($presenciais, fn($a, $b) => strcmp($a['data_evento'], $b['data_evento']));
        foreach ($presenciais as $e) {
            $data = new DateTimeImmutable($e['data_evento']);
            if (isset($ultimaVez[$e['cidade_evento']])) {
                $dias = $ultimaVez[$e['cidade_evento']]->diff($data)->days;
                afirmar($dias >= 56, "{$e['cidade_evento']} repetiu em $dias dias");
            }
            $ultimaVez[$e['cidade_evento']] = $data;
        }
    },

    'palestrante sempre é um dos 4 palestrantes' => function () {
        $ctx = gerarAte('eventos');
        $palestrantes = $ctx->config['equipe']['palestrantes'];
        foreach ($ctx->base->linhas('evento') as $e) {
            afirmar(in_array($e['palestrante_evento'], $palestrantes, true), "palestrante inválido: {$e['palestrante_evento']}");
        }
    },

    'cidades do config existem no mapa legado de cidades' => function () {
        $legado = require dirname(__DIR__, 2) . '/lib/hotel/cidades-legado.php';
        foreach (array_keys(configDeTeste()['eventos']['cidades']) as $cidade) {
            afirmar(isset($legado[$cidade]), "$cidade não está em lib/hotel/cidades-legado.php");
        }
    },

    'câmbio tem uma linha por dia sem buracos e fica entre 5.20 e 5.90' => function () {
        $ctx = gerarAte('eventos');
        $linhas = array_values($ctx->base->linhas('cambio'));
        afirmarIgual('2025-01-01', $linhas[0]['data_cambio']);
        afirmarIgual($ctx->hoje->modify('+45 days')->format('Y-m-d'), end($linhas)['data_cambio']);
        $esperado = new DateTimeImmutable('2025-01-01');
        foreach ($linhas as $linha) {
            afirmarIgual($esperado->format('Y-m-d'), $linha['data_cambio'], 'buraco no câmbio');
            afirmarEntre(5.20, 5.90, $linha['valor_cambio']);
            $esperado = $esperado->modify('+1 day');
        }
    },
];
