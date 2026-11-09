<?php

/**
 * Etapa 7: pós-venda (pipeline C3), Jurídico, tarefas e orçamentos de upsell.
 *
 * Todo combo assinado abre um card C3, que avança com o tempo: arquitetura do
 * caso, milestones, aplicação no USCIS, RFE, aprovação e NVC. O card tem um
 * case manager (Jurídico), ADVs e uma CSM (Customer Success).
 *
 * Campanha de metas do CS ("Liga"): a carteira são os 430 clientes mais
 * recentes criados antes do início da campanha, divididos entre 4 CSMs. Como a
 * janela de dados começa em 2025, a carteira é completada com clientes
 * "legados", que fecharam em 2024. As constantes da campanha (início, tamanho
 * da carteira, produtos e IDs de status) são lidas do próprio controller.
 */
final class PosVenda
{
    private const FASES_POR_IDADE = [
        // [até N dias, faseId, fase]
        [10, 'C3:NEW', 'Arquitetura a agendar'],
        [25, 'C3:PREPARATION', 'Arquitetura agendada'],
        [60, 'C3:UC_ML1', 'Milestone 1'],
        [100, 'C3:UC_ML2', 'Milestone 2'],
        [140, 'C3:UC_ML3', 'Milestone 3'],
        [200, 'C3:UC_APLIC', 'Aplicados'],
    ];
    private const FASES_FINAIS = [
        'C3:UC_RFEREC' => 'RFE/Noid Recebida',
        'C3:UC_RFERESP' => 'RFE/Noid Respondida',
        'C3:UC_RFEAJ' => 'RFE Ajuste de status',
        'C3:UC_APROV' => 'Aprovado USCIS',
        'C3:UC_NVC' => 'NVC',
    ];
    private const FASES_JURIDICO = ['Milestone 1', 'Milestone 2', 'Milestone 3', 'Aplicados', 'Aprovado USCIS', 'NVC'];
    private const ETAPAS_ORCAMENTO = ['Vendido' => 'APPROVED', 'Em negociação' => 'NEGOTIATION', 'Perdido' => 'DECLINED'];

    public static function gerar(Contexto $ctx): void
    {
        self::criarCardsDosCombos($ctx);
        self::completarCarteiraComLegados($ctx);
        self::distribuirCsms($ctx);
        self::criarTarefas($ctx);
        self::criarOrcamentos($ctx);
        self::garantirVendasDaSemana($ctx);
    }

    // -------------------------------------------------------------------------
    // Cards C3
    // -------------------------------------------------------------------------

    private static function criarCardsDosCombos(Contexto $ctx): void
    {
        $s = $ctx->sorteio;
        foreach ($ctx->base->linhas('dealV2') as $id => $combo) {
            $distrato = in_array($combo['faseId_dealV2'], ['C5:UC_CL1DBM', 'C5:UC_AQ2QYT'], true);
            $ehCombo = in_array($combo['faseId_dealV2'], ['C5:WON', 'C1:WON', 'C29:WON', 'C31:WON'], true)
                || ($distrato && !empty($combo['dataContratoComboAssinado_dealV2']));
            if (!$ehCombo) {
                continue;
            }

            $dia = min((new DateTimeImmutable($combo['dataContratoComboAssinado_dealV2']))->modify('+' . $s->inteiro(1, 7) . ' days'), $ctx->hoje);
            $criadoEm = $ctx->calendario->momento($dia, $s);
            [$faseId, $fase] = $distrato ? ['C3:UC_DIST', 'Distrato'] : self::faseDoCard($ctx, $criadoEm);

            $herdados = [];
            // C31 nunca recebe dataFpRealizado (LEFT(faseId,2)='C3' o contaria como Comercial 4 no Serasa).
            foreach (['dataFpRealizado_dealV2', 'serasaRange_dealV2', 'serasaScore_dealV2', 'statusCpf_dealV2', 'nomeSujo_dealV2',
                'socioEmpresaNegativa_dealV2', 'valorTotalDividas_dealV2', 'valorCartorioTotal_dealV2'] as $campo) {
                if (isset($combo[$campo]) && !str_starts_with($combo['faseId_dealV2'], 'C31:')) {
                    $herdados[$campo] = $combo[$campo];
                }
            }

            Cards::copiar($ctx, $combo, array_merge($herdados, self::camposDoCaso($ctx), [
                'faseId_dealV2' => $faseId,
                'fase_dealV2' => $fase,
                'criadoEm_dealV2' => $criadoEm,
                '_idCombo' => $id,
            ]));
        }
    }

    /** Clientes que fecharam em 2024, antes da janela de dados, para a carteira chegar a 430. */
    private static function completarCarteiraComLegados(Contexto $ctx): void
    {
        $inicio = ConstantesDoCodigo::ler('comercial4', 'LIGA_INICIO');
        $carteira = ConstantesDoCodigo::ler('comercial4', 'LIGA_CARTEIRA');
        $antes = count(array_filter(self::cardsC3($ctx), fn($d) => $d['criadoEm_dealV2']->format('Y-m-d') < $inicio));
        $s = $ctx->sorteio;

        for ($i = $antes; $i < $carteira; $i++) {
            $criadoEm = $ctx->calendario->momento((new DateTimeImmutable('2024-01-01'))->modify('+' . $s->inteiro(0, 365) . ' days'), $s);
            $cliente = Pessoas::novoCliente($ctx);
            $idContato = $ctx->base->novoIdBitrix('contato');
            $ctx->base->inserir('contactV2', [
                'idBitrix_contactV2' => $idContato,
                'primeiroNome_contactV2' => $cliente['primeiroNome'],
                'sobrenome_contactV2' => $cliente['sobrenome'],
            ]);
            [$faseId, $fase] = self::faseDoCard($ctx, $criadoEm);
            Cards::novo($ctx, array_merge(self::camposDoCaso($ctx), [
                'faseId_dealV2' => $faseId,
                'fase_dealV2' => $fase,
                'criadoEm_dealV2' => $criadoEm,
                'contactId_dealV2' => $idContato,
                'nome_dealV2' => $cliente['nome'],
                'email_dealV2' => $cliente['email'],
                'telefone_dealV2' => $cliente['telefone'],
            ]));
        }
    }

    /** Case manager, ADVs e valor da assessoria (BRL). */
    private static function camposDoCaso(Contexto $ctx): array
    {
        $s = $ctx->sorteio;
        $equipe = $ctx->config['equipe'];
        return [
            'advogadoAtendimento_dealV2' => $s->chance(0.95) ? $s->um($equipe['case_managers']) : '',
            'advMl1_dealV2' => $s->um($equipe['advs']),
            'advMl2_dealV2' => $s->um($equipe['advs']),
            'advMl3_dealV2' => $s->um($equipe['advs']),
            'valorAssessoria_dealV2' => $s->decimal(8000, 30000),
        ];
    }

    /** Fase pela idade do card, com uma pequena parte em Distrato, Inadimplente e Legado. */
    private static function faseDoCard(Contexto $ctx, DateTimeImmutable $criadoEm): array
    {
        $s = $ctx->sorteio;
        $extra = $s->ponderado(['nenhuma' => 93, 'distrato' => 3, 'inadimplente' => 2, 'legado' => 2]);
        if ($extra !== 'nenhuma') {
            return ['distrato' => ['C3:UC_DIST', 'Distrato'], 'inadimplente' => ['C3:UC_INAD', 'Inadimplente'], 'legado' => ['C3:UC_LEG', 'Legado']][$extra];
        }
        $idade = (int) $criadoEm->setTime(0, 0)->diff($ctx->hoje)->format('%a');
        foreach (self::FASES_POR_IDADE as [$ate, $faseId, $fase]) {
            if ($idade <= $ate) {
                return [$faseId, $fase];
            }
        }
        $faseId = $s->um(array_keys(self::FASES_FINAIS));
        return [$faseId, self::FASES_FINAIS[$faseId]];
    }

    // -------------------------------------------------------------------------
    // CSMs e carteira da campanha de metas
    // -------------------------------------------------------------------------

    private static function distribuirCsms(Contexto $ctx): void
    {
        $s = $ctx->sorteio;
        $inicio = ConstantesDoCodigo::ler('comercial4', 'LIGA_INICIO');
        $tamanho = ConstantesDoCodigo::ler('comercial4', 'LIGA_CARTEIRA');
        $csmLiga = $ctx->config['equipe']['csm'];
        $csmOutros = $ctx->config['equipe']['csm_outros'];
        $idsLiga = array_keys($csmLiga);

        $antes = array_filter(self::cardsC3($ctx), fn($d) => $d['criadoEm_dealV2']->format('Y-m-d') < $inicio);
        uasort($antes, fn($a, $b) => $b['criadoEm_dealV2'] <=> $a['criadoEm_dealV2']);
        $carteira = array_slice(array_keys($antes), 0, $tamanho);
        $naoMapeados = array_flip((array) array_rand(array_flip($carteira), $tamanho - $ctx->config['liga']['mapeados']));

        foreach ($carteira as $i => $id) {
            $csm = $idsLiga[$i % count($idsLiga)];
            $ctx->base->atualizar('dealV2', $id, [
                'responsavelComercial4Id_dealV2' => (string) $csm,
                'responsavelComercial4_dealV2' => $csmLiga[$csm],
                'responsavel_dealV2' => $csmLiga[$csm],
                'dataJson_dealV2' => self::dataJson($ctx, !isset($naoMapeados[$id])),
            ]);
        }

        $naCarteira = array_flip($carteira);
        foreach (self::cardsC3($ctx) as $id => $card) {
            if (isset($naCarteira[$id])) {
                continue;
            }
            $depoisDoInicio = $card['criadoEm_dealV2']->format('Y-m-d') >= $inicio;
            $csm = $depoisDoInicio && $s->chance(0.8) ? $s->um($idsLiga) : $s->um(array_keys($csmOutros));
            $nome = $csmLiga[$csm] ?? $csmOutros[$csm];
            $ctx->base->atualizar('dealV2', $id, [
                'responsavelComercial4Id_dealV2' => (string) $csm,
                'responsavelComercial4_dealV2' => $nome,
                'responsavel_dealV2' => $nome,
            ]);
        }
    }

    /**
     * Status de cada produto de parceiro, no formato do Bitrix: cada campo
     * UF_CRM_* guarda o ID da opção escolhida. Um cliente "mapeado" tem todos os
     * produtos com status conhecido e nenhum em "iniciar".
     */
    private static function dataJson(Contexto $ctx, bool $mapeado): string
    {
        $s = $ctx->sorteio;
        $idsPorStatus = ConstantesDoCodigo::ler('comercial4', 'LIGA_STATUS_IDS');
        $campos = array_keys(ConstantesDoCodigo::ler('comercial4', 'LIGA_PRODUTOS'));
        $aIniciar = $mapeado ? [] : array_flip((array) array_rand(array_flip($campos), $s->inteiro(1, 3)));

        $json = [];
        foreach ($campos as $campo) {
            $status = isset($aIniciar[$campo]) ? 'iniciar' : $s->ponderado($ctx->config['liga']['pesosStatus']);
            $json[$campo] = $s->um($idsPorStatus[$status]);
        }
        return json_encode($json);
    }

    // -------------------------------------------------------------------------
    // Tarefas e orçamentos
    // -------------------------------------------------------------------------

    private static function criarTarefas(Contexto $ctx): void
    {
        $s = $ctx->sorteio;
        foreach (self::cardsC3($ctx) as $card) {
            if (!in_array($card['fase_dealV2'], self::FASES_JURIDICO, true) || !$s->chance(0.35)) {
                continue;
            }
            for ($i = $s->inteiro(1, 3); $i > 0; $i--) {
                $ctx->base->inserir('taskV2', [
                    'idDeal_taskV2' => $card['idBitrix_dealV2'],
                    'priority_taskV2' => $s->chance(0.30) ? 2 : 1,
                    'status_taskV2' => $s->ponderado(['Concluída' => 60, 'Pendente' => 25, 'Em andamento' => 15]),
                ]);
            }
        }
    }

    private static function criarOrcamentos(Contexto $ctx): void
    {
        $s = $ctx->sorteio;
        $inicio = new DateTimeImmutable(ConstantesDoCodigo::ler('comercial4', 'LIGA_INICIO'));
        $anoAtual = $ctx->hoje->format('Y');

        foreach (self::cardsC3($ctx) as $card) {
            $naCarteira = $card['dataJson_dealV2'] ?? null;
            if ($naCarteira !== null) {
                $quantidade = $s->ponderado([0 => 25, 1 => 60, 2 => 15]);
                $desde = $inicio;
                $ate = $ctx->hoje;
            } elseif ($card['criadoEm_dealV2']->format('Y') === $anoAtual) {
                // Cliente novo recebe a oferta de 10 a 90 dias depois de entrar no pós-venda.
                $quantidade = $s->chance(0.5) ? 1 : 0;
                $desde = $card['criadoEm_dealV2']->setTime(0, 0)->modify('+10 days');
                $ate = min($desde->modify('+80 days'), $ctx->hoje);
                if ($desde > $ctx->hoje) {
                    continue;
                }
            } else {
                continue;
            }
            for ($i = 0; $i < $quantidade; $i++) {
                self::novoOrcamento($ctx, $card, $desde, $ate);
            }
        }
    }

    private static function novoOrcamento(Contexto $ctx, array $card, DateTimeImmutable $desde, DateTimeImmutable $ate): void
    {
        $s = $ctx->sorteio;
        $diaCriacao = self::diaEntre($ctx, $desde, $ate);
        $etapa = $s->ponderado(['Vendido' => 45, 'Em negociação' => 35, 'Perdido' => 20]);
        $usd = $s->chance(0.2);

        $id = $ctx->base->inserir('orcamentoV2', [
            'idBitrix_orcamentoV2' => $ctx->base->novoIdBitrix('orcamento'),
            'dealId_orcamentoV2' => $card['idBitrix_dealV2'],
            'contactId_orcamentoV2' => $s->chance(0.95) ? $card['contactId_dealV2'] : '',
            'nome_orcamentoV2' => $card['nome_dealV2'],
            'etapa_orcamentoV2' => $etapa,
            'etapaId_orcamentoV2' => self::ETAPAS_ORCAMENTO[$etapa],
            // Fecha em até 30 dias depois de criado (sorteado até hoje concentraria as vendas no fim).
            'dataVenda_orcamentoV2' => $etapa === 'Vendido'
                ? self::diaEntre($ctx, $diaCriacao, min($diaCriacao->modify('+30 days'), $ctx->hoje))->format('Y-m-d')
                : null,
            'criadoEm_orcamentoV2' => $ctx->calendario->momento($diaCriacao, $s),
            'oportunidade_orcamentoV2' => $usd ? $s->decimal(300, 800) : $s->decimal(1500, 4000),
            'moedaId_orcamentoV2' => $usd ? 'USD' : 'BRL',
        ]);

        $produtos = array_values(ConstantesDoCodigo::ler('comercial4', 'LIGA_PRODUTOS'));
        for ($i = $s->inteiro(1, 2); $i > 0; $i--) {
            $ctx->base->inserir('itemOrcamentoV2', [
                'id_orcamentoV2' => $id,
                'nomeOriginalProduto_itemOrcamentoV2' => $s->chance(0.95) ? $s->um($produtos) : 'Visto de turismo',
            ]);
        }
    }

    /** A campanha de metas mostra as vendas da semana: garante pelo menos 8 nos últimos 7 dias. */
    private static function garantirVendasDaSemana(Contexto $ctx): void
    {
        $minimo = $ctx->config['liga']['vendasMinimasNaSemana'];
        $inicioSemana = $ctx->hoje->modify('-6 days');
        $naSemana = fn() => count(array_filter($ctx->base->linhas('orcamentoV2'),
            fn($o) => ($o['dataVenda_orcamentoV2'] ?? '') >= $inicioSemana->format('Y-m-d')));

        $candidatos = array_filter($ctx->base->linhas('orcamentoV2'), fn($o) => ($o['dataVenda_orcamentoV2'] ?? '') < $inicioSemana->format('Y-m-d'));
        uasort($candidatos, fn($a, $b) => $b['criadoEm_orcamentoV2'] <=> $a['criadoEm_orcamentoV2']);
        foreach (array_keys($candidatos) as $id) {
            if ($naSemana() >= $minimo) {
                return;
            }
            $criado = $ctx->base->linha('orcamentoV2', $id)['criadoEm_orcamentoV2']->setTime(0, 0);
            $ctx->base->atualizar('orcamentoV2', $id, [
                'etapa_orcamentoV2' => 'Vendido',
                'etapaId_orcamentoV2' => 'APPROVED',
                'dataVenda_orcamentoV2' => self::diaEntre($ctx, max($criado, $inicioSemana), $ctx->hoje)->format('Y-m-d'),
            ]);
        }
    }

    private static function cardsC3(Contexto $ctx): array
    {
        return array_filter($ctx->base->linhas('dealV2'), fn($d) => str_starts_with($d['faseId_dealV2'], 'C3:'));
    }

    private static function diaEntre(Contexto $ctx, DateTimeImmutable $inicio, DateTimeImmutable $fim): DateTimeImmutable
    {
        $dias = max(0, (int) $inicio->setTime(0, 0)->diff($fim->setTime(0, 0))->format('%r%a'));
        return $inicio->setTime(0, 0)->modify('+' . $ctx->sorteio->inteiro(0, $dias) . ' days');
    }
}
