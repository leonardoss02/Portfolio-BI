<?php

require_once __DIR__ . '/apoio.php';

return [
    'lead com venda termina pago; sem venda nunca paga; toda tag tem hora' => function () {
        $ctx = gerarAte('jornada');
        $comVenda = [];
        foreach ($ctx->base->linhas('dealV2') as $d) {
            if (isset($d['_idLead']) && in_array($d['faseId_dealV2'], ['C9:WON', 'C11:WON'], true)) {
                $comVenda[$d['_idLead']] = true;
            }
        }
        $tagsPorLead = [];
        foreach ($ctx->base->linhas('lead_tag') as $t) {
            afirmar($t['dataCadastro'] instanceof DateTimeInterface, 'tag sem data e hora');
            if ($t['id_leadV2'] !== null) {
                $tagsPorLead[$t['id_leadV2']][] = $t['tag'];
            }
        }
        afirmar(count($tagsPorLead) > 0, 'nenhuma tag');
        foreach ($tagsPorLead as $idLead => $tags) {
            $pagou = in_array('pagamento-confirmado', $tags, true);
            afirmarIgual(isset($comVenda[$idLead]), $pagou, "lead $idLead: venda e pagamento não combinam");
        }
    },

    'existem leads só na jornada, tag fora do catálogo e o primeiro lead tem jornada' => function () {
        $ctx = gerarAte('jornada');
        $tags = $ctx->base->linhas('lead_tag');
        afirmar(count(array_filter($tags, fn($t) => $t['id_leadV2'] === null)) > 0, 'nenhum lead só na jornada');
        afirmar(count(array_filter($tags, fn($t) => $t['tag'] === 'webinar-assistido')) > 0, 'nenhuma tag fora do catálogo');
        afirmar(count(array_filter($tags, fn($t) => $t['idBitrix_lead'] === 500001)) > 0, 'o lead 500001 não tem jornada');
    },
];
