<?php

/**
 * Etapa 4: a primeira venda — cadeira no evento ou consulta.
 *
 * Caminho do evento presencial (On The Road):
 *   lead qualificado → compra a cadeira (C9:WON) → vai ao evento e faz
 *   check-in (card do funil de evento, sem prefixo) → parte compra a
 *   consulta ali mesmo (faseId 'WON').
 *
 * Caminho digital (e webinar, 1:1, orgânico, indicação):
 *   lead qualificado → compra a consulta (C11:WON).
 *
 * Quem comprou consulta segue para o Comercial 2 na etapa seguinte.
 */
final class VendaConsulta
{
    private const FASES_SEM_VENDA = [
        'LOSE' => 'Não comprou',
        'FINAL_INVOICE' => 'Aguardando pagamento',
        'UC_C07YVI' => 'Em negociação',
        'NEW' => 'Check-in feito',
    ];

    public static function gerar(Contexto $ctx): void
    {
        $cfg = $ctx->config['venda'];
        $eventos = $ctx->base->linhas('evento');
        $cambio = array_column($ctx->base->linhas('cambio'), 'valor_cambio', 'data_cambio');

        foreach ($ctx->base->linhas('leadV2') as $lead) {
            if ($lead['qualificado_leadV2'] !== 'Sim') {
                continue;
            }
            $onTheRoad = str_starts_with($lead['formPreenchido_leadV2'], 'OTR');
            if ($onTheRoad && $ctx->sorteio->chance($cfg['chanceCadeira'])) {
                $evento = $eventos[(int) $lead['idEvento_leadV2']];
                self::venderCadeira($ctx, $lead, $evento, $cambio);
            } elseif (!$onTheRoad && $ctx->sorteio->chance($cfg['chanceConsultaDigital'])) {
                self::venderConsultaDigital($ctx, $lead, $cambio);
            }
        }
    }

    private static function venderCadeira(Contexto $ctx, array $lead, array $evento, array $cambio): void
    {
        $cfg = $ctx->config['venda'];
        $equipe = $ctx->config['equipe'];
        $s = $ctx->sorteio;

        $dataEvento = new DateTimeImmutable($evento['data_evento']);
        $ultimoDia = min($dataEvento, $ctx->hoje);
        $diaVenda = self::diaEntre($ctx, new DateTimeImmutable($lead['_dia']), $ultimoDia);
        $venda = max($lead['dataUltForm_leadV2'], $ctx->calendario->momento($diaVenda, $s));

        $responsavel = match (true) {
            $s->chance($cfg['chanceCadeiraHelena']) => $equipe['helena'],
            $s->chance($cfg['chanceVendaDireta']) => $equipe['venda_direta'],
            default => $lead['responsavel_leadV2'],
        };

        $idCadeira = Cards::doLead($ctx, $lead, [
            'faseId_dealV2' => 'C9:WON',
            'fase_dealV2' => 'Cadeira vendida',
            'responsavel_dealV2' => $responsavel,
            'criadoEm_dealV2' => $lead['dataUltForm_leadV2'],
            'dataEvento_dealV2' => $evento['data_evento'],
            'dataVenda_dealV2' => $venda,
            'valorVenda_dealV2' => $cfg['valorCadeiraBrl'],
            'valorVendaDolar_dealV2' => round($cfg['valorCadeiraBrl'] / $cambio[$venda->format('Y-m-d')], 2),
        ]);

        // Check-in: só em evento que já aconteceu.
        if ($dataEvento <= $ctx->hoje && $s->chance($cfg['chanceCheckin'])) {
            self::fazerCheckin($ctx, $ctx->base->linha('dealV2', $idCadeira), $evento, $cambio);
        }
    }

    private static function fazerCheckin(Contexto $ctx, array $cadeira, array $evento, array $cambio): void
    {
        $cfg = $ctx->config['venda'];
        $s = $ctx->sorteio;
        $dataEvento = new DateTimeImmutable($evento['data_evento']);
        $chegada = $ctx->calendario->momento($dataEvento, $s, 9, 11);

        $comprou = $s->chance($cfg['chanceConsultaNoEvento']);
        $faseId = $comprou ? 'WON' : $s->um(array_keys(self::FASES_SEM_VENDA));
        $venda = $comprou ? $ctx->calendario->momento($dataEvento, $s, 12, 18) : null;

        Cards::copiar($ctx, $cadeira, [
            'faseId_dealV2' => $faseId,
            'fase_dealV2' => $comprou ? 'Consulta vendida' : self::FASES_SEM_VENDA[$faseId],
            'responsavel_dealV2' => $s->um($ctx->config['equipe']['comercial1_evento']),
            'criadoEm_dealV2' => $chegada,
            'checkin_dealV2' => '1',
            'dataEvento_dealV2' => $evento['data_evento'],
            // Valor do ingresso: o painel da Beatriz conta "possibilidade" quando é 100.
            'valorVenda_dealV2' => $s->chance($cfg['chanceValorIngresso']) ? $cfg['valorCadeiraBrl'] : null,
            'dataVenda_dealV2' => $venda,
            'valorVendaDolar_dealV2' => $comprou ? round($cfg['valorConsultaBrl'] / $cambio[$evento['data_evento']], 2) : null,
        ]);
    }

    private static function venderConsultaDigital(Contexto $ctx, array $lead, array $cambio): void
    {
        $cfg = $ctx->config['venda'];
        $equipe = $ctx->config['equipe'];
        $s = $ctx->sorteio;

        $diaLead = new DateTimeImmutable($lead['_dia']);
        $diaVenda = min($diaLead->modify('+' . $s->inteiro(0, $cfg['diasAteConsultaDigital']) . ' days'), $ctx->hoje);
        $venda = max($lead['dataUltForm_leadV2'], $ctx->calendario->momento($diaVenda, $s));

        // Lead orgânico e de indicação chega como "Sistema Admin"; quem vende é o Comercial 1 Digital.
        $responsavel = in_array($lead['responsavel_leadV2'], array_merge([$equipe['helena']], $equipe['comercial1_digital']), true)
            ? $lead['responsavel_leadV2']
            : $s->um($equipe['comercial1_digital']);

        Cards::doLead($ctx, $lead, [
            'faseId_dealV2' => 'C11:WON',
            'fase_dealV2' => 'Consulta vendida',
            'responsavel_dealV2' => $responsavel,
            'criadoEm_dealV2' => $lead['dataUltForm_leadV2'],
            'dataVenda_dealV2' => $venda,
            'valorVenda_dealV2' => $cfg['valorConsultaBrl'],
            'valorVendaDolar_dealV2' => round($cfg['valorConsultaBrl'] / $cambio[$venda->format('Y-m-d')], 2),
        ]);
    }

    /** Um dia sorteado entre $inicio e $fim (inclusive). */
    private static function diaEntre(Contexto $ctx, DateTimeImmutable $inicio, DateTimeImmutable $fim): DateTimeImmutable
    {
        $dias = max(0, (int) $inicio->diff($fim)->format('%r%a'));
        return $inicio->modify('+' . $ctx->sorteio->inteiro(0, $dias) . ' days');
    }
}
