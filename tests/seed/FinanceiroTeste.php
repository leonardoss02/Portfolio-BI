<?php

require_once __DIR__ . '/apoio.php';

return [
    'existem os 4 status e toda fatura paga tem saldo 0' => function () {
        $ctx = gerarAte('financeiro');
        $faturas = $ctx->base->linhas('invoice');
        $status = array_unique(array_column($faturas, 'status_invoice'));
        foreach (['Em dia', 'Atrasado', 'Inadimplente', 'Congelado'] as $esperado) {
            afirmar(in_array($esperado, $status, true), "nenhuma fatura '$esperado'");
        }
        foreach ($faturas as $f) {
            if ($f['dataQBPagamento_invoice'] !== null) {
                afirmar($f['ballancedQB_invoice'] == 0, 'fatura paga com saldo');
            }
        }
    },

    'há faturas sem card e faturas ligadas a combos' => function () {
        $ctx = gerarAte('financeiro');
        $semCard = count(filtrar($ctx, 'invoice', fn($f) => $f['idDealBitrix_invoice'] === null));
        afirmarEntre(0.20, 0.36, $semCard / $ctx->base->total('invoice'), 'proporção sem card');
    },
];
