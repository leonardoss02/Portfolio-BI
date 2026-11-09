<?php

/**
 * Etapa 5: Comercial 2 (pipeline C5), retenção (C1) e combos de outros pipes.
 *
 * Toda consulta vendida (WON no evento ou C11:WON no digital) vira um card C5,
 * que percorre o funil até onde a data de hoje deixa:
 *
 *   agendada → realizada → sinal pago → FP agendado → FP realizado → combo
 *
 * Em cada etapa há uma chance de parar. Quem perde depois do FP como
 * "Comercial 3" pode ser recuperado pela retenção (C1). Alguns clientes de
 * indicação e B2B fecham combo direto em outros pipes (C29 e C31).
 *
 * Datas de DATE são guardadas como 'Y-m-d'; as de DATETIME, como objetos.
 */
final class Comercial2
{
    private const DISTRATO = ['C5:UC_CL1DBM', 'C5:UC_AQ2QYT'];

    public static function gerar(Contexto $ctx): void
    {
        foreach ($ctx->base->linhas('dealV2') as $venda) {
            if (in_array($venda['faseId_dealV2'], ['WON', 'C11:WON'], true)) {
                self::criarCardC5($ctx, $venda);
            }
        }
        self::retencao($ctx);
        self::combosDeOutrosPipes($ctx);
        self::garantirMesCorrente($ctx);
    }

    // -------------------------------------------------------------------------
    // C5
    // -------------------------------------------------------------------------

    private static function criarCardC5(Contexto $ctx, array $venda): void
    {
        $cfg = $ctx->config['c5'];
        $s = $ctx->sorteio;
        $hoje = $ctx->hoje->format('Y-m-d');
        $vendidaEm = $venda['dataVenda_dealV2'];

        $card = [
            'responsavel_dealV2' => $s->ponderado($cfg['responsaveis']),
            'criadoEm_dealV2' => $vendidaEm->modify('+' . $s->inteiro(5, 120) . ' minutes'),
            '_idVenda' => $venda['id_dealV2'],
        ];
        // Por referência: o card vai ganhando datas ao longo do funil antes de ser inserido.
        $inserir = function (string $faseId, string $fase, array $extra = []) use ($ctx, $venda, &$card): int {
            return Cards::copiar($ctx, $venda, array_merge($card, $extra, ['faseId_dealV2' => $faseId, 'fase_dealV2' => $fase]));
        };

        if ($s->chance($cfg['chanceSemAgendamento'])) {
            $inserir('C5:NEW', 'A agendar');
            return;
        }

        $agendada = self::maisDias($ctx, $vendidaEm->format('Y-m-d'), 1, $cfg['diasAteAgendamento']);
        $card['dataConsultaAgendada_dealV2'] = $agendada;

        $realizada = null;
        if ($s->chance($cfg['chanceRealizada'])) {
            $realizada = $s->chance($cfg['chanceRealizadaNoMesSeguinte'])
                ? self::maisDias($ctx, (new DateTimeImmutable($agendada))->modify('first day of next month')->format('Y-m-d'), 0, 2)
                : self::maisDias($ctx, $agendada, 0, 3);
            if ($realizada > $hoje) {
                $realizada = null;
            }
        }
        if ($realizada === null) {
            $semana = $ctx->hoje->modify('-7 days')->format('Y-m-d');
            if ($agendada < $semana) {
                $inserir('C5:LOSE', 'Não compareceu', [
                    'motivoDescarteComercial2_dealV2' => $s->chance($cfg['chanceMotivoNaoCompareceu']) ? $s->um($ctx->config['motivosDescarte']) : null,
                ]);
            } else {
                $inserir('C5:EXECUTING', 'Consulta agendada');
            }
            return;
        }
        $card['dataConsultaRealizada_dealV2'] = $realizada;

        // Sinal pago (DATETIME, no mesmo campo de data de venda).
        if ($s->chance($cfg['chanceSinal'])) {
            $diaSinal = self::maisDias($ctx, $realizada, 0, 5);
            if ($diaSinal <= $hoje) {
                $card['dataVenda_dealV2'] = $ctx->calendario->momento(new DateTimeImmutable($diaSinal), $s);
            }
        }

        if (!$s->chance($cfg['chanceFpAgendado'])) {
            $limite = $ctx->hoje->modify('-20 days')->format('Y-m-d');
            if ($realizada < $limite && $s->chance($cfg['chancePerdaSemFp'])) {
                $inserir('C5:LOSE', 'Sem interesse após consulta', [
                    'motivoDescarteComercial2_dealV2' => $s->um($ctx->config['motivosDescarte']),
                    'dataUltForm_dealV2' => $ctx->calendario->momento(new DateTimeImmutable($realizada), $s),
                ]);
            } else {
                $inserir('C5:UC_SEMFP', 'Consulta realizada');
            }
            return;
        }

        $fpAgendado = self::maisDias($ctx, $realizada, 3, 15);
        $card['dataFpAgendado_dealV2'] = $fpAgendado;
        $fpRealizado = $s->chance($cfg['chanceFpRealizado']) ? self::maisDias($ctx, $fpAgendado, 0, 5) : null;
        if ($fpRealizado === null || $fpRealizado > $hoje) {
            $inserir('C5:UC_FPAGEN', 'FP agendado');
            return;
        }
        $card['dataFpRealizado_dealV2'] = $fpRealizado;
        if ($s->chance($cfg['chanceSerasa'])) {
            $card += self::serasa($ctx);
        }

        self::desfechoDoFp($ctx, $card, $fpRealizado, $inserir);
    }

    private static function desfechoDoFp(Contexto $ctx, array $card, string $fpRealizado, callable $inserir): void
    {
        $cfg = $ctx->config['c5'];
        $s = $ctx->sorteio;
        $hoje = $ctx->hoje->format('Y-m-d');
        $desfecho = $s->ponderado($cfg['desfechoFp']);

        if ($desfecho === 'combo') {
            $contrato = self::maisDias($ctx, $fpRealizado, 2, 20);
            if ($contrato <= $hoje) {
                $combo = [
                    'dataContratoComboAssinado_dealV2' => $contrato,
                    'valorComboLiquido_dealV2' => round(min(max($s->normal(20000, 2500), 15000), 25000), 2),
                    'vistoEscolhido_dealV2' => $s->um($ctx->config['vistos']),
                ];
                if ($s->chance($cfg['chanceDistratoAposCombo'])) {
                    $inserir($s->um(self::DISTRATO), 'Distrato', $combo);
                } else {
                    $inserir('C5:WON', 'Contrato assinado', $combo);
                }
                return;
            }
            $desfecho = 'aguardando';
        }

        if ($desfecho === 'perdido') {
            $inserir('C5:LOSE', $s->ponderado($cfg['fasesDePerda']), [
                'motivoDescarteComercial2_dealV2' => $s->um($ctx->config['motivosDescarte']),
                'dataUltForm_dealV2' => $ctx->calendario->momento(new DateTimeImmutable($fpRealizado), $s),
            ]);
            return;
        }

        // Sem contrato: parte desiste depois de pagar o sinal (churn de FP).
        if (isset($card['dataVenda_dealV2']) && $s->chance($cfg['chanceDistratoSemContrato'])) {
            $inserir($s->um(self::DISTRATO), 'Distrato');
            return;
        }
        $desfecho === 'enviado'
            ? $inserir('C5:UC_0Q30H1', 'Contrato enviado')
            : $inserir('C5:UC_FPREAL', 'FP realizado');
    }

    /** Dados de consulta de crédito (Serasa), preenchidos no FP. Dívidas em reais. */
    private static function serasa(Contexto $ctx): array
    {
        $s = $ctx->sorteio;
        $faixa = $s->ponderado(['A' => 18, 'B' => 22, 'C' => 22, 'D' => 15, 'E' => 10, 'F' => 8, 'G' => 5]);
        $base = ['A' => 850, 'B' => 760, 'C' => 670, 'D' => 580, 'E' => 490, 'F' => 400, 'G' => 320][$faixa];
        $nomeSujo = $s->chance(in_array($faixa, ['F', 'G'], true) ? 0.6 : 0.08);
        return [
            'serasaRange_dealV2' => $faixa,
            'serasaScore_dealV2' => $base + $s->inteiro(-30, 30),
            'statusCpf_dealV2' => $s->ponderado(['Regular' => 85, 'Pendente' => 10, '' => 5]),
            'nomeSujo_dealV2' => $nomeSujo ? 'Sim' : 'Não',
            'socioEmpresaNegativa_dealV2' => $s->chance(0.08) ? 'Sim' : 'Não',
            'valorTotalDividas_dealV2' => $nomeSujo ? $s->decimal(5000, 80000) : $s->decimal(0, 8000),
            'valorCartorioTotal_dealV2' => $nomeSujo ? $s->decimal(0, 20000) : 0.0,
        ];
    }

    // -------------------------------------------------------------------------
    // Retenção (C1) e combos de outros pipes (C29, C31)
    // -------------------------------------------------------------------------

    private static function retencao(Contexto $ctx): void
    {
        $cfg = $ctx->config['c1'];
        $s = $ctx->sorteio;
        $hoje = $ctx->hoje->format('Y-m-d');

        foreach ($ctx->base->linhas('dealV2') as $perdido) {
            if ($perdido['faseId_dealV2'] !== 'C5:LOSE' || $perdido['fase_dealV2'] !== 'Comercial 3' || !$s->chance($cfg['chanceRetencao'])) {
                continue;
            }
            $criadoEm = $perdido['dataUltForm_dealV2']->modify('+' . $s->inteiro(1, 5) . ' days');
            if ($criadoEm->format('Y-m-d') > $hoje) {
                continue;
            }
            $card = [
                'faseId_dealV2' => 'C1:NEW',
                'fase_dealV2' => 'Retenção iniciada',
                'responsavel_dealV2' => $s->um($ctx->config['equipe']['comercial3']),
                'criadoEm_dealV2' => $criadoEm,
                'dataUltForm_dealV2' => $criadoEm,
                '_idVenda' => $perdido['_idVenda'],
            ];

            $fecha = $s->chance($cfg['chanceCombo']);
            $agendada = ($fecha || $s->chance($cfg['chanceAgendada'])) ? self::maisDias($ctx, $criadoEm->format('Y-m-d'), 1, 10) : null;
            $realizada = ($agendada !== null && ($fecha || $s->chance($cfg['chanceRealizada']))) ? self::maisDias($ctx, $agendada, 0, 3) : null;
            if ($agendada !== null && $agendada <= $hoje) {
                $card['dataConsultaAgendada_dealV2'] = $agendada;
            }
            if ($realizada !== null && $realizada <= $hoje) {
                $card['dataConsultaRealizada_dealV2'] = $realizada;
                $contrato = self::maisDias($ctx, $realizada, 3, 20);
                if ($fecha && $contrato <= $hoje) {
                    $card = array_merge($card, [
                        'faseId_dealV2' => 'C1:WON',
                        'fase_dealV2' => 'Combo recuperado',
                        'dataContratoComboAssinado_dealV2' => $contrato,
                        'valorComboLiquido_dealV2' => round(min(max($s->normal(18000, 2500), 12000), 25000), 2),
                        'vistoEscolhido_dealV2' => $s->um($ctx->config['vistos']),
                    ]);
                }
            }
            if ($card['faseId_dealV2'] === 'C1:NEW' && $criadoEm < $ctx->hoje->modify('-15 days')) {
                $card['faseId_dealV2'] = 'C1:LOSE';
                $card['fase_dealV2'] = 'Retenção perdida';
                $card['motivoDescarte_dealV2'] = $s->um($ctx->config['motivosDescarte']);
            }
            Cards::copiar($ctx, $perdido, $card);
        }
    }

    private static function combosDeOutrosPipes(Contexto $ctx): void
    {
        $cfg = $ctx->config['c5'];
        $s = $ctx->sorteio;
        $hoje = $ctx->hoje->format('Y-m-d');

        foreach ($ctx->base->linhas('leadV2') as $lead) {
            $elegivel = $lead['_canal'] === 'indicacao' || $lead['tipoLead_leadV2'] === 'B2B';
            if (!$elegivel || !$s->chance($cfg['chanceComboOutroPipe'])) {
                continue;
            }
            $contrato = self::maisDias($ctx, $lead['_dia'], 15, 60);
            if ($contrato > $hoje) {
                continue;
            }
            $pipe = $s->um(['C29', 'C31']);
            // Sem dataFpRealizado: LEFT(faseId, 2) = 'C3' contaria um C31 como Comercial 4 no Serasa.
            Cards::doLead($ctx, $lead, [
                'faseId_dealV2' => "$pipe:WON",
                'fase_dealV2' => 'Contrato assinado',
                'responsavel_dealV2' => $s->um($ctx->config['equipe']['especialistas']),
                'criadoEm_dealV2' => $lead['dataUltForm_leadV2'],
                'dataContratoComboAssinado_dealV2' => $contrato,
                'valorComboLiquido_dealV2' => round(min(max($s->normal(20000, 2500), 15000), 25000), 2),
                'vistoEscolhido_dealV2' => $s->um($ctx->config['vistos']),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Garantia do mês corrente
    // -------------------------------------------------------------------------

    /**
     * Os painéis por vendedor abrem "do dia 1º até hoje". No começo do mês pode
     * não haver nada no período por puro acaso. Para a demonstração nunca abrir
     * vazia, cada vendedor com painel recebe pelo menos uma consulta realizada e
     * uma agendada (não realizada) no período. O card escolhido é um C5 recente,
     * reatribuído ao vendedor com as datas em hoje e sem as etapas seguintes.
     */
    private static function garantirMesCorrente(Contexto $ctx): void
    {
        $hoje = $ctx->hoje->format('Y-m-d');
        $inicioMes = $ctx->hoje->format('Y-m-01');
        $usados = [];

        foreach ($ctx->config['equipe']['vendedoresComPainel'] as $vendedor) {
            $doMes = array_filter($ctx->base->linhas('dealV2'), fn($d) => $d['responsavel_dealV2'] === $vendedor
                && str_starts_with($d['faseId_dealV2'], 'C5:'));
            $temRealizada = (bool) array_filter($doMes, fn($d) => ($d['dataConsultaRealizada_dealV2'] ?? '') >= $inicioMes
                && ($d['dataConsultaRealizada_dealV2'] ?? '') <= $hoje);
            $temAgendada = (bool) array_filter($doMes, fn($d) => ($d['dataConsultaAgendada_dealV2'] ?? '') >= $inicioMes
                && ($d['dataConsultaAgendada_dealV2'] ?? '') <= $hoje && empty($d['dataConsultaRealizada_dealV2']));

            if (!$temRealizada) {
                $id = self::cardParaGarantia($ctx, $usados);
                $usados[] = $id;
                self::reiniciarCard($ctx, $id, $vendedor, 'C5:UC_SEMFP', 'Consulta realizada', ['dataConsultaRealizada_dealV2' => $hoje]);
            }
            if (!$temAgendada) {
                $id = self::cardParaGarantia($ctx, $usados);
                $usados[] = $id;
                self::reiniciarCard($ctx, $id, $vendedor, 'C5:EXECUTING', 'Consulta agendada', []);
            }
        }
    }

    /** O C5 mais recente ainda sem FP (ou, se faltar, qualquer C5 que não seja combo). */
    private static function cardParaGarantia(Contexto $ctx, array $usados): int
    {
        $candidatos = array_filter($ctx->base->linhas('dealV2'), fn($d) => str_starts_with($d['faseId_dealV2'], 'C5:')
            && !in_array($d['id_dealV2'], $usados, true)
            && !in_array($d['faseId_dealV2'], ['C5:WON', ...self::DISTRATO], true));
        uasort($candidatos, function ($a, $b) {
            $semFp = fn($d) => in_array($d['faseId_dealV2'], ['C5:EXECUTING', 'C5:NEW', 'C5:UC_SEMFP'], true) ? 0 : 1;
            return [$semFp($a), $b['criadoEm_dealV2']] <=> [$semFp($b), $a['criadoEm_dealV2']];
        });
        if (!$candidatos) {
            throw new RuntimeException('Não há card C5 para garantir o mês corrente.');
        }
        return array_key_first($candidatos);
    }

    private static function reiniciarCard(Contexto $ctx, int $id, string $vendedor, string $faseId, string $fase, array $datas): void
    {
        $ctx->base->atualizar('dealV2', $id, array_merge([
            'responsavel_dealV2' => $vendedor,
            'faseId_dealV2' => $faseId,
            'fase_dealV2' => $fase,
            'dataConsultaAgendada_dealV2' => $ctx->hoje->format('Y-m-d'),
            'dataConsultaRealizada_dealV2' => null,
            'dataVenda_dealV2' => null,
            'dataFpAgendado_dealV2' => null,
            'dataFpRealizado_dealV2' => null,
            'motivoDescarteComercial2_dealV2' => null,
        ], $datas));
    }

    /** $data ('Y-m-d') mais um número sorteado de dias entre $min e $max. */
    private static function maisDias(Contexto $ctx, string $data, int $min, int $max): string
    {
        return (new DateTimeImmutable($data))->modify('+' . $ctx->sorteio->inteiro($min, $max) . ' days')->format('Y-m-d');
    }
}
