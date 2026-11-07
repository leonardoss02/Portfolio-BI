<?php

/**
 * Dashboard Comercial — funil completo (Evento × Digital × Retenção).
 *
 * Porte do relatório Power BI "Html_Principal" (medidas DAX + HTML embutido)
 * para o BI próprio. Mesmo padrão dos demais dashboards do projeto: toda a
 * lógica de SQL/cálculo vive em métodos privados desta classe, e os helpers
 * (cache, filtros, JSON) são cópia própria do controller — sem camada
 * compartilhada (convenção do projeto, ver memória cache-copia-por-controller).
 *
 * Diferença conceitual em relação a executive/marketing: aqui a separação
 * Evento × Digital é feita por tipoLead_dealV2 / tipoLead_leadV2, e NÃO pelo
 * prefixo de utmCampaign/formPreenchido.
 */
class comercial extends MRPHP
{
    /** tipoLead que caracteriza o canal Evento (leadV2 e dealV2 usam os mesmos valores). */
    private const SQL_TIPOS_EVENTO = "('OTR', 'OTW')";

    /** faseId_dealV2 aceitas como "combo vendido" (= Visto na UI). Inclui a retenção C1:WON. */
    private const SQL_FASES_COMBO = "('C1:WON', 'C5:WON', 'C29:won', 'C31:WON')";

    /** Mesmo conjunto sem C1:WON — C1:WON é venda de retenção, não deve contar
     *  nos cortes por canal (Evento/Digital), só no total. */
    private const SQL_FASES_COMBO_CANAL = "('C5:WON', 'C29:won', 'C31:WON')";

    /** faseId_dealV2 de distrato (regra DAX [Distrato], janela sobre a data do contrato de combo). */
    private const SQL_FASES_DISTRATO = "('C5:UC_CL1DBM', 'C5:UC_AQ2QYT')";

    /** Token enviado pela UI para o tipoLead vazio — CSV não transporta string vazia. */
    private const TIPO_LEAD_VAZIO = '(vazio)';

    /**
     * Time de cadeiras (3ª view). Lista fixa: o filtro de vendedor da UI só
     * restringe dentro dela, nunca amplia — mesmo com o parâmetro
     * manipulado na URL. Entrar ou sair alguém do time exige mudar aqui.
     */
    private const EQUIPE_CADEIRAS = ['Patrícia Lopes', 'Débora Fontes', 'Marina Viana'];

    /** Quantos motivos de descarte a 3ª view mostra (TOPN do relatório de origem). */
    private const TOP_DESCARTES = 10;

    /**
     * Time Comercial 1 (4ª view). Vende consulta por DOIS canais com KPI
     * próprio cada: Evento (cadeiras -> consulta) e Digital (leads ->
     * consulta). A UI filtra dentro dos dois, nunca amplia.
     */
    private const EQUIPE_COMERCIAL1_EVENTO  = ['Fernanda Matos', 'Priscila Rangel'];
    private const EQUIPE_COMERCIAL1_DIGITAL = ['Eduardo Pires', 'Larissa Campos'];
    /**
     * Time Comercial 2 (5ª view). 5 vendedores, pipe C5 (combo
     * pós-consulta), sempre reportado Evento × Digital pela MESMA regra
     * de canal da Visão Geral (SQL_TIPOS_EVENTO) — diferente da aba
     * Cadeiras, que usa só 'OTR'. Lista única (não dupla como Comercial1):
     * aqui Evento/Digital são um corte do MESMO vendedor, não sub-times.
     *
     * Time restrito de 9 para 5 em 10/09/2026 a pedido do usuário — Ana
     * Moraes, Ricardo Albuquerque, Sônia Teles e Cláudia Rocha saíram
     * (não pertencem ao time, mesmo tendo atividade real no CRM).
     */
    private const EQUIPE_COMERCIAL2 = [
        'Paulo Nogueira', 'Rafael Torres', 'Camila Duarte', 'Beatriz Lemos', 'Juliana Prado',
    ];

    /**
     * Time Comercial 3 / Retenção (6ª view). Recupera cancelamentos,
     * pipe C1 (faseId_dealV2 com prefixo 'C1:'). Só 2 vendedores — a
     * UI filtra dentro dela, nunca amplia, mesmo padrão das 3 listas
     * acima. Outros responsáveis com atividade real no pipe C1
     * (ex.: Paulo Nogueira) não pertencem ao time formal — confirmado
     * com o usuário.
     */
    private const EQUIPE_COMERCIAL3 = ['Thiago Dos Santos Ramos', 'Aline Barros'];


    public function index()
    {
        $this->setTemplate('comercial');
    }

    public function dicionario()
    {
        $this->setTemplate('comercial');
    }

    /** GET /comercial/yoy — 2ª view: comparativo ano × mesmo período do ano anterior. */
    public function yoy()
    {
        $this->setTemplate('comercial');
    }

    /** GET /comercial/cadeiras — 3ª view: performance do time de cadeiras. */
    public function cadeiras()
    {
        // O dropdown de vendedor é montado com esta lista, mas quem manda é
        // o resolverEquipeCadeiras() do endpoint: a UI não é a autoridade.
        $this->set(self::EQUIPE_CADEIRAS, 'equipeCadeiras');
        $this->setTemplate('comercial');
    }

    /**
     * GET /comercial/cadeirasDados — ranking do time, performance por
     * vendedor, série mensal de cadeiras e top 10 de motivos de descarte.
     *
     * Duas queries, não quatro: cada tabela-fonte serve a dois blocos.
     * leadV2 agrupada por (responsável, motivo) dá os leads qualificados do
     * ranking E os descartes; dealV2 agrupada por (responsável, mês) dá as
     * cadeiras do ranking E a série temporal. É o padrão de consolidação já
     * usado nos outros dashboards, e aqui corta uma varredura inteira de
     * leadV2 — a mais cara das duas (~2,5 s contra ~0,25 s da dealV2).
     */
    public function cadeirasDados()
    {
        set_time_limit(180);
        try {
            $cacheKey = $this->cacheKeyFromRequest('comercial_cadeiras');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f          = $this->lerFiltros();
            $vendedores = $this->resolverEquipeCadeiras($f['responsavel']);

            $lead = $this->calcularLeadsCadeiras($f, $vendedores);
            $deal = $this->calcularVendasCadeiras($f, $vendedores);

            $ranking = $this->montarRankingCadeiras($vendedores, $lead['porVendedor'], $deal['porVendedor']);

            $totLeads = array_sum(array_column($ranking, 'leads'));
            $totCad   = array_sum(array_column($ranking, 'cadeiras'));

            $payload = [
                'meta' => $f + [
                    'equipe'    => $vendedores,
                    'generated' => date('Y-m-d H:i:s'),
                ],
                'ranking' => $ranking,
                'totais'  => [
                    'leads'    => $totLeads,
                    'cadeiras' => $totCad,
                    'conv'     => $totLeads > 0 ? round($totCad / $totLeads * 100, 2) : 0.0,
                ],
                'tempo'     => $deal['serie'],
                'descartes' => $lead['descartes'],
            ];

            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * Resolve o filtro de vendedor contra a lista fixa do time.
     *
     * O parâmetro da URL só RESTRINGE: um nome de fora da equipe é
     * descartado pelo array_intersect, e uma seleção vazia devolve o time
     * inteiro. É o que impede a rota de virar um relatório de qualquer
     * responsável do CRM só trocando a query string.
     */
    private function resolverEquipeCadeiras(?string $csv): array
    {
        if ($csv === null || trim($csv) === '') {
            return self::EQUIPE_CADEIRAS;
        }
        $sel = array_values(array_intersect(
            array_map('trim', explode(',', $csv)),
            self::EQUIPE_CADEIRAS
        ));
        return $sel ?: self::EQUIPE_CADEIRAS;
    }


    /** GET /comercial/comercial1 — 4ª view: time Comercial 1 (Evento × Digital). */
    public function comercial1()
    {
        // O dropdown de vendedor é montado com esta lista; quem manda é o
        // resolverEquipeComercial1() do endpoint — a UI não é a autoridade.
        $this->set(
            array_merge(self::EQUIPE_COMERCIAL1_EVENTO, self::EQUIPE_COMERCIAL1_DIGITAL),
            'equipeComercial1'
        );
        $this->setTemplate('comercial');
    }

    /**
     * GET /comercial/comercial1Dados — ranking e série mensal dos dois
     * sub-times do Comercial 1: Evento (cadeiras → consulta) e Digital
     * (leads → consulta). "Consulta Vendida" é comum aos dois lados.
     *
     * 3 queries, não 4: 2 em dealV2 (ranking via cv+cad por responsável;
     * série mensal via UNION ALL cv+cad por responsável+mês, cada ramo na
     * SUA coluna de data) + 1 em leadV2 (leads qualificados digital,
     * ranking+série na mesma passada — mesmo padrão de
     * calcularLeadsCadeiras()).
     */
    public function comercial1Dados()
    {
        set_time_limit(180);
        try {
            $cacheKey = $this->cacheKeyFromRequest('comercial_c1');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();
            [$vendedoresEvento, $vendedoresDigital] = $this->resolverEquipeComercial1($f['responsavel']);
            $vendedoresTodos = array_merge($vendedoresEvento, $vendedoresDigital);

            // Se o usuário filtrou só um dos lados, o outro fica com array
            // vazio — pular a query em vez de montar um "IN ()" inválido.
            $rankingDeal = $vendedoresTodos ? $this->calcularRankingDealComercial1($f, $vendedoresTodos) : [];
            $serieDeal   = $vendedoresTodos ? $this->calcularSerieDealComercial1($f, $vendedoresTodos) : [];
            $leadsDg     = $vendedoresDigital ? $this->calcularLeadsDigitalComercial1($f, $vendedoresDigital) : ['porVendedor' => [], 'porMes' => []];

            $rankingEvento  = $this->montarRankingEventoComercial1($vendedoresEvento, $rankingDeal);
            $rankingDigital = $this->montarRankingDigitalComercial1($vendedoresDigital, $leadsDg['porVendedor'], $rankingDeal);

            $series = $this->montarSeriesComercial1($serieDeal, $leadsDg['porMes'], $vendedoresEvento, $vendedoresDigital, $f['granularidade']);

            $payload = [
                'meta' => $f + [
                    'equipe_evento'  => $vendedoresEvento,
                    'equipe_digital' => $vendedoresDigital,
                    'generated'      => date('Y-m-d H:i:s'),
                ],
                'evento'  => ['ranking' => $rankingEvento,  'tempo' => $series['evento']],
                'digital' => ['ranking' => $rankingDigital, 'tempo' => $series['digital']],
            ];

            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * Resolve o filtro de vendedor contra os dois sub-times fixos.
     *
     * O parâmetro da URL só RESTRINGE, nunca amplia: um nome de fora dos
     * dois times é descartado pelo array_intersect. Devolve os dois arrays
     * já separados — é o que permite pular a query de um lado quando o
     * usuário filtrou só o outro.
     */
    private function resolverEquipeComercial1(?string $csv): array
    {
        $todos = array_merge(self::EQUIPE_COMERCIAL1_EVENTO, self::EQUIPE_COMERCIAL1_DIGITAL);

        if ($csv === null || trim($csv) === '') {
            return [self::EQUIPE_COMERCIAL1_EVENTO, self::EQUIPE_COMERCIAL1_DIGITAL];
        }
        $sel = array_values(array_intersect(array_map('trim', explode(',', $csv)), $todos));
        if (!$sel) {
            return [self::EQUIPE_COMERCIAL1_EVENTO, self::EQUIPE_COMERCIAL1_DIGITAL];
        }

        return [
            array_values(array_intersect($sel, self::EQUIPE_COMERCIAL1_EVENTO)),
            array_values(array_intersect($sel, self::EQUIPE_COMERCIAL1_DIGITAL)),
        ];
    }


    /** GET /comercial/comercial2 — 5ª view: time Comercial 2 (Evento × Digital). */
    public function comercial2()
    {
        $this->set(self::EQUIPE_COMERCIAL2, 'equipeComercial2');
        $this->setTemplate('comercial');
    }

    /**
     * GET /comercial/comercial2Dados — ranking (1 tabela, 9 colunas
     * numéricas: Evento/Digital/Total × Cons./Combos/Conv%) e série mensal
     * do time Comercial 2.
     *
     * 2 queries, não 3+: nenhuma toca leadV2 (diferente de Cadeiras e
     * Comercial1) — as 4 métricas são todas de dealV2. Query A (GROUP BY
     * resp) dá o ranking; Query B (UNION ALL de 4 ramos, GROUP BY
     * resp+mês) dá a série.
     */
    public function comercial2Dados()
    {
        set_time_limit(180);
        try {
            $cacheKey = $this->cacheKeyFromRequest('comercial_c2');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f          = $this->lerFiltros();
            $vendedores = $this->resolverEquipeComercial2($f['responsavel']);

            $rankingRaw = $this->calcularRankingComercial2($f, $vendedores);
            $serieRaw   = $this->calcularSerieComercial2($f, $vendedores);

            $ranking = $this->montarRankingComercial2($vendedores, $rankingRaw);
            $tempo   = $this->montarSerieComercial2($serieRaw, $f['granularidade']);

            $payload = [
                'meta' => $f + [
                    'equipe'    => $vendedores,
                    'generated' => date('Y-m-d H:i:s'),
                ],
                'ranking' => $ranking,
                'tempo'   => $tempo,
            ];

            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * Resolve o filtro de vendedor contra a lista fixa do time. O
     * parâmetro da URL só RESTRINGE, nunca amplia — mesmo padrão de
     * resolverEquipeCadeiras().
     */
    private function resolverEquipeComercial2(?string $csv): array
    {
        if ($csv === null || trim($csv) === '') {
            return self::EQUIPE_COMERCIAL2;
        }
        $sel = array_values(array_intersect(
            array_map('trim', explode(',', $csv)),
            self::EQUIPE_COMERCIAL2
        ));
        return $sel ?: self::EQUIPE_COMERCIAL2;
    }


    /** GET /comercial/comercial3 — 6ª view: time Comercial 3 / Retenção (pipe C1). */
    public function comercial3()
    {
        $this->set(self::EQUIPE_COMERCIAL3, 'equipeComercial3');
        $this->setTemplate('comercial');
    }

    /**
     * GET /comercial/comercial3Dados — dois rankings (por Consulta
     * Agendada e por Consulta Realizada, ambos culminando em Combo Em
     * Retenção), série mensal (agendada × combo) e top 10 de motivos
     * de descarte do time Comercial 3.
     *
     * 3 queries: ranking (1 query, GROUP BY resp, 3 SUM(CASE) — agend/
     * realizada/combos, mesmo padrão de calcularRankingComercial2());
     * série mensal (UNION ALL de 2 ramos, mesmo padrão de
     * calcularVendasCadeiras()); descartes (GROUP BY motivo, própria
     * query — não compartilha GROUP BY com nenhuma das outras duas,
     * diferente do truque de consolidação usado em
     * calcularLeadsCadeiras()).
     */
    public function comercial3Dados()
    {
        set_time_limit(180);
        try {
            $cacheKey = $this->cacheKeyFromRequest('comercial_c3');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f          = $this->lerFiltros();
            $vendedores = $this->resolverEquipeComercial3($f['responsavel']);

            $rankingRaw = $this->calcularRankingComercial3($f, $vendedores);
            $serieRaw   = $this->calcularSerieComercial3($f, $vendedores);

            $ranking   = $this->montarRankingComercial3($vendedores, $rankingRaw);
            $tempo     = $this->montarSerieComercial3($serieRaw, $f['granularidade']);
            $descartes = $this->calcularDescartesComercial3($f, $vendedores);

            $payload = [
                'meta' => $f + [
                    'equipe'    => $vendedores,
                    'generated' => date('Y-m-d H:i:s'),
                ],
                'ranking'   => $ranking,
                'tempo'     => $tempo,
                'descartes' => $descartes,
            ];

            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * Resolve o filtro de vendedor contra a lista fixa do time. O
     * parâmetro da URL só RESTRINGE, nunca amplia — mesmo padrão de
     * resolverEquipeComercial2().
     */
    private function resolverEquipeComercial3(?string $csv): array
    {
        if ($csv === null || trim($csv) === '') {
            return self::EQUIPE_COMERCIAL3;
        }
        $sel = array_values(array_intersect(
            array_map('trim', explode(',', $csv)),
            self::EQUIPE_COMERCIAL3
        ));
        return $sel ?: self::EQUIPE_COMERCIAL3;
    }

    // ===================================================================
    // ENDPOINTS JSON
    // ===================================================================

    /**
     * GET /comercial/dados — payload único, espelhando o JSON que a medida DAX
     * montava: totais, evento, digital, retencao e a série mensal.
     */
    public function dados()
    {
        set_time_limit(180);
        try {
            $cacheKey = $this->cacheKeyFromRequest('comercial_dados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();

            $deals = $this->calcularDeals($f);
            $leads = $this->calcularLeads($f);

            $payload = [
                'meta' => $f + ['generated' => date('Y-m-d H:i:s')],

                'totais' => [
                    'leads'      => $leads['leads_total'],
                    'leadsQuali' => $leads['quali_total'],
                    'cad'        => $deals['cadeira_total'],
                    'cv'         => $deals['cv_total'],
                    'cr'         => $deals['cr_total'],
                    'cb'         => $deals['cb_total'],
                    'dist'       => $deals['dist_total'],
                    'valor'      => $deals['valor_total'],
                ],
                'evento' => [
                    'totalLeads' => $leads['leads_ev'],
                    'quali'      => $leads['quali_ev'],
                    // Fiel ao DAX: [cadeira vendida com] no bloco Evento é a mesma
                    // medida do totalizador, sem recorte por tipoLead.
                    'ing'        => $deals['cadeira_total'],
                    'cv'         => $deals['cv_ev'],
                    'cr'         => $deals['cr_ev'],
                    'cb'         => $deals['cb_ev'],
                    'dist'       => $deals['dist_ev'],
                    'valor'      => $deals['valor_ev'],
                ],
                'digital' => [
                    'totalLeads' => $leads['leads_dg'],
                    'quali'      => $leads['quali_dg'],
                    'cv'         => $deals['cv_dg'],
                    'cr'         => $deals['cr_dg'],
                    'cb'         => $deals['cb_dg'],
                    'dist'       => $deals['dist_dg'],
                    'valor'      => $deals['valor_dg'],
                ],
                'retencao' => [
                    'cb'    => $deals['cb_ret'],
                    'valor' => $deals['valor_ret'],
                ],

                'tempo' => $this->calcularSerieMensal($f),
            ];

            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * GET /comercial/yoyDados — período corrente contra o MESMO período do
     * ano anterior (deslocado 12 meses, com o fim limitado a hoje).
     *
     * Reaproveita calcularDeals() sem alteração nenhuma, chamado duas vezes
     * com $f já resolvido em datas literais — é o que garante que os blocos
     * *_ly desta rota batam número a número com /comercial/dados apontado
     * para o ano anterior. Nenhuma fórmula de métrica é reescrita aqui.
     *
     * Esta view não tem leads: são as 6 métricas do relatório de origem
     * (ingressos, consultas vendidas/realizadas, combos, distratos, valor),
     * então calcularLeads() não é chamado.
     *
     * Custo: 3 queries (2 x calcularDeals + 1 da série, que cobre os dois
     * períodos e as duas métricas de uma vez).
     */
    public function yoyDados()
    {
        set_time_limit(180);
        try {
            // Prefixo próprio: cacheKeyFromRequest usa só a query string, e
            // com o mesmo prefixo de dados() as duas rotas colidiriam.
            $cacheKey = $this->cacheKeyFromRequest('comercial_yoy');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f  = $this->lerFiltros();
            $pa = $this->periodoEfetivo($f);
            $fa = $this->comFiltroDeData($f, $pa);   // período atual, já em datas literais
            $fl = $this->deslocarPeriodoUmAno($fa);  // mesmo intervalo, -12 meses
            $pl = ['inicio' => $fl['data_inicio'], 'fim' => $fl['data_fim']];

            $a = $this->calcularDeals($fa);
            $l = $this->calcularDeals($fl);

            $payload = [
                'meta' => $f + [
                    'periodo_atual' => $pa,
                    'periodo_ly'    => $pl,
                    'ano_atual'     => substr($pa['fim'], 0, 4),
                    'ano_ly'        => substr($pl['fim'], 0, 4),
                    'generated'     => date('Y-m-d H:i:s'),
                ],

                'totais'    => $this->linhaYoy($a, 'total'),
                'totais_ly' => $this->linhaYoy($l, 'total'),

                // 'ing' aqui é cadeira_total (global, sem recorte de tipoLead):
                // fidelidade ao relatório de origem, onde a medida do bloco
                // Evento e a do totalizador são a mesma. Igual à view 1.
                'evento'    => $this->linhaYoy($a, 'ev'),
                'evento_ly' => $this->linhaYoy($l, 'ev'),

                'digital'    => $this->linhaYoy($a, 'dg'),
                'digital_ly' => $this->linhaYoy($l, 'dg'),

                'retencao'    => ['cb' => $a['cb_ret'], 'valor' => $a['valor_ret']],
                'retencao_ly' => ['cb' => $l['cb_ret'], 'valor' => $l['valor_ret']],

                'mensal' => $this->calcularSerieYoy($fa, $fl, $pa),
            ];

            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * Recorta a saída de calcularDeals() num dos três escopos da view YoY.
     * O bloco Digital não tem ingressos (a métrica só existe global).
     */
    private function linhaYoy(array $d, string $escopo): array
    {
        if ($escopo === 'dg') {
            return [
                'cv'    => $d['cv_dg'],
                'cr'    => $d['cr_dg'],
                'cb'    => $d['cb_dg'],
                'dist'  => $d['dist_dg'],
                'valor' => $d['valor_dg'],
            ];
        }

        $sufixo = $escopo === 'ev' ? '_ev' : '_total';

        return [
            'ing'   => $d['cadeira_total'],
            'cv'    => $d['cv' . $sufixo],
            'cr'    => $d['cr' . $sufixo],
            'cb'    => $d['cb' . $sufixo],
            'dist'  => $d['dist' . $sufixo],
            'valor' => $d['valor' . $sufixo],
        ];
    }

    /** GET /comercial/forms — dropdown de busca (valores brutos de formPreenchido). */
    public function forms()
    {
        try {
            $cached = $this->cacheGet('comercial_forms', self::CACHE_TTL_DROPDOWN);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $sql = "
                SELECT DISTINCT form FROM (
                    SELECT formPreenchido_leadV2 AS form FROM leadV2
                        WHERE formPreenchido_leadV2 IS NOT NULL AND formPreenchido_leadV2 != ''
                    UNION
                    SELECT formPreenchido_dealV2 AS form FROM dealV2
                        WHERE formPreenchido_dealV2 IS NOT NULL AND formPreenchido_dealV2 != ''
                ) t ORDER BY form
            ";
            $payload = array_column($this->queryAll($sql), 'form');
            $this->cacheSet('comercial_forms', $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /comercial/responsaveis — dropdown de busca (responsável de lead + deal). */
    public function responsaveis()
    {
        try {
            $cached = $this->cacheGet('comercial_responsaveis', self::CACHE_TTL_DROPDOWN);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $sql = "
                SELECT DISTINCT nome FROM (
                    SELECT responsavel_leadV2 AS nome FROM leadV2
                        WHERE responsavel_leadV2 IS NOT NULL AND responsavel_leadV2 != ''
                    UNION
                    SELECT responsavel_dealV2 AS nome FROM dealV2
                        WHERE responsavel_dealV2 IS NOT NULL AND responsavel_dealV2 != ''
                ) t ORDER BY nome
            ";
            $payload = array_column($this->queryAll($sql), 'nome');
            $this->cacheSet('comercial_responsaveis', $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    // ===================================================================
    // CÁLCULO: dealV2 (funil, valores, retenção, distrato)
    // ===================================================================

    /**
     * Uma única query em dealV2 com todos os SUM(CASE WHEN ...) — global,
     * Evento e Digital. Os filtros de dimensão (form/tipoLead/responsável) e o
     * bound de período vão no WHERE externo (valem para todas as métricas);
     * dentro de cada CASE fica só o que varia por métrica (coluna de data,
     * fase e escopo de canal).
     *
     * bind_param é posicional: os params dos CASE (SELECT) entram antes dos
     * params do WHERE, na mesma ordem em que os fragmentos aparecem no texto.
     */
    private function calcularDeals(array $f): array
    {
        $pVenda = $this->buildPeriodFilter('d.dataVenda_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pReal  = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pCombo = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);

        $preenchidaVenda = "d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'";
        $preenchidaReal  = "d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'";
        $preenchidaCombo = "d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'";

        $evento  = "d.tipoLead_dealV2 IN " . self::SQL_TIPOS_EVENTO;
        // Null-safe: NOT(x IN {...}) em DAX é verdadeiro para BLANK; em SQL puro
        // NULL NOT IN (...) resulta em NULL e o registro sumiria da contagem.
        $digital = "(d.tipoLead_dealV2 IS NULL OR d.tipoLead_dealV2 NOT IN " . self::SQL_TIPOS_EVENTO . ")";

        $selects = [];
        $params  = [];

        // Cada entrada: [alias, período, condição da métrica, escopo de canal, expressão somada]
        $metricas = [
            ['cadeira_total', $pVenda, "$preenchidaVenda AND d.faseId_dealV2 = 'C9:WON'", '1=1', '1'],

            ['cv_total', $pVenda, "$preenchidaVenda AND d.faseId_dealV2 IN ('WON', 'C11:WON')", '1=1',     '1'],
            ['cv_ev',    $pVenda, "$preenchidaVenda AND d.faseId_dealV2 IN ('WON', 'C11:WON')", $evento,   '1'],
            ['cv_dg',    $pVenda, "$preenchidaVenda AND d.faseId_dealV2 IN ('WON', 'C11:WON')", $digital,  '1'],

            ['cr_total', $pReal, "$preenchidaReal AND LEFT(d.faseId_dealV2, 2) = 'C5'", '1=1',    '1'],
            ['cr_ev',    $pReal, "$preenchidaReal AND LEFT(d.faseId_dealV2, 2) = 'C5'", $evento,  '1'],
            ['cr_dg',    $pReal, "$preenchidaReal AND LEFT(d.faseId_dealV2, 2) = 'C5'", $digital, '1'],

            ['cb_total', $pCombo, "$preenchidaCombo AND d.faseId_dealV2 IN " . self::SQL_FASES_COMBO, '1=1',    '1'],
            ['cb_ev',    $pCombo, "$preenchidaCombo AND d.faseId_dealV2 IN " . self::SQL_FASES_COMBO_CANAL, $evento,  '1'],
            ['cb_dg',    $pCombo, "$preenchidaCombo AND d.faseId_dealV2 IN " . self::SQL_FASES_COMBO_CANAL, $digital, '1'],

            ['valor_total', $pCombo, "$preenchidaCombo AND d.faseId_dealV2 IN " . self::SQL_FASES_COMBO, '1=1',    'COALESCE(d.valorComboLiquido_dealV2, 0)'],
            ['valor_ev',    $pCombo, "$preenchidaCombo AND d.faseId_dealV2 IN " . self::SQL_FASES_COMBO_CANAL, $evento,  'COALESCE(d.valorComboLiquido_dealV2, 0)'],
            ['valor_dg',    $pCombo, "$preenchidaCombo AND d.faseId_dealV2 IN " . self::SQL_FASES_COMBO_CANAL, $digital, 'COALESCE(d.valorComboLiquido_dealV2, 0)'],

            // Retenção: recorte de C1:WON dentro do total de combos (não é um conjunto à parte).
            ['cb_ret',    $pCombo, "$preenchidaCombo AND d.faseId_dealV2 = 'C1:WON'", '1=1', '1'],
            ['valor_ret', $pCombo, "$preenchidaCombo AND d.faseId_dealV2 = 'C1:WON'", '1=1', 'COALESCE(d.valorComboLiquido_dealV2, 0)'],

            ['dist_total', $pCombo, $this->condDistrato(), '1=1',    '1'],
            ['dist_ev',    $pCombo, $this->condDistrato(), $evento,  '1'],
            ['dist_dg',    $pCombo, $this->condDistrato(), $digital, '1'],
        ];

        foreach ($metricas as [$alias, $periodo, $cond, $canal, $expr]) {
            $selects[] = "SUM(CASE WHEN {$periodo['sql']} AND $cond AND $canal THEN $expr ELSE 0 END) AS $alias";
            $params    = array_merge($params, $periodo['params']);
        }

        $dim = $this->filtrosDimensaoDeal($f);

        // Bound de scan: sem isso o período vive só dentro dos CASE e a query
        // varre a tabela inteira mesmo com índice disponível.
        $bound = $this->buildBoundMultiCol(
            ['d.dataVenda_dealV2', 'd.dataConsultaRealizada_dealV2', 'd.dataContratoComboAssinado_dealV2'],
            array_merge($pVenda['params'], $pReal['params'], $pCombo['params'])
        );

        $sql = "SELECT " . implode(",\n               ", $selects) . "
                FROM dealV2 d
                WHERE {$bound['sql']} {$dim['sql']}";

        $params = array_merge($params, $bound['params'], $dim['params']);
        $row    = $this->queryOne($sql, $params);

        $out = [];
        foreach ($metricas as [$alias]) {
            $out[$alias] = str_starts_with($alias, 'valor_')
                ? round((float)($row[$alias] ?? 0), 2)
                : (int)($row[$alias] ?? 0);
        }
        return $out;
    }

    /** Condição de distrato (regra DAX): fase de distrato + FP realizado + contrato de combo assinado. */
    private function condDistrato(): string
    {
        return "d.faseId_dealV2 IN " . self::SQL_FASES_DISTRATO . "
                AND d.dataFpRealizado_dealV2 IS NOT NULL AND d.dataFpRealizado_dealV2 > '0000-00-00'
                AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'";
    }

    // ===================================================================
    // CÁLCULO: leadV2 (leads e qualificados por canal)
    // ===================================================================

    private function calcularLeads(array $f): array
    {
        $periodo = $this->buildPeriodFilter('l.dataUltForm_leadV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);

        $evento  = "l.tipoLead_leadV2 IN " . self::SQL_TIPOS_EVENTO;
        $digital = "(l.tipoLead_leadV2 IS NULL OR l.tipoLead_leadV2 NOT IN " . self::SQL_TIPOS_EVENTO . ")";
        $quali   = "l.qualificado_leadV2 = 'Sim'";

        $dim = $this->filtrosDimensaoLead($f);

        $sql = "
            SELECT
                COUNT(*)                                                    AS leads_total,
                SUM(CASE WHEN $evento  THEN 1 ELSE 0 END)                   AS leads_ev,
                SUM(CASE WHEN $digital THEN 1 ELSE 0 END)                   AS leads_dg,
                SUM(CASE WHEN $quali   THEN 1 ELSE 0 END)                   AS quali_total,
                SUM(CASE WHEN $quali AND $evento  THEN 1 ELSE 0 END)        AS quali_ev,
                SUM(CASE WHEN $quali AND $digital THEN 1 ELSE 0 END)        AS quali_dg
            FROM leadV2 l
            WHERE {$periodo['sql']}
              AND l.dataUltForm_leadV2 IS NOT NULL AND l.dataUltForm_leadV2 > '0000-00-00'
              {$dim['sql']}
        ";

        $row = $this->queryOne($sql, array_merge($periodo['params'], $dim['params']));

        return [
            'leads_total' => (int)($row['leads_total'] ?? 0),
            'leads_ev'    => (int)($row['leads_ev'] ?? 0),
            'leads_dg'    => (int)($row['leads_dg'] ?? 0),
            'quali_total' => (int)($row['quali_total'] ?? 0),
            'quali_ev'    => (int)($row['quali_ev'] ?? 0),
            'quali_dg'    => (int)($row['quali_dg'] ?? 0),
        ];
    }

    // ===================================================================
    // CÁLCULO: série mensal (ingressos · consultas vendidas · vistos)
    // ===================================================================

    /**
     * Cada métrica tem coluna de data própria, então não dá para agrupar as três
     * numa passada só — UNION ALL de 3 ramos, cada um agrupado pela sua coluna,
     * e o pivot por mês é feito em PHP.
     */
    private function calcularSerieMensal(array $f): array
    {
        $pVenda = $this->buildPeriodFilter('d.dataVenda_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pCombo = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $dim    = $this->filtrosDimensaoDeal($f);
        $grVenda = $this->exprAgrupamentoPeriodo($f['granularidade'], 'd.dataVenda_dealV2');
        $grCombo = $this->exprAgrupamentoPeriodo($f['granularidade'], 'd.dataContratoComboAssinado_dealV2');

        $sql = "
            SELECT $grVenda AS mes, 'ing' AS serie, COUNT(*) AS qtd
            FROM dealV2 d
            WHERE {$pVenda['sql']}
              AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
              AND d.faseId_dealV2 = 'C9:WON'
              {$dim['sql']}
            GROUP BY mes

            UNION ALL

            SELECT $grVenda AS mes, 'cv' AS serie, COUNT(*) AS qtd
            FROM dealV2 d
            WHERE {$pVenda['sql']}
              AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
              AND d.faseId_dealV2 IN ('WON', 'C11:WON')
              {$dim['sql']}
            GROUP BY mes

            UNION ALL

            SELECT $grCombo AS mes, 'cb' AS serie, COUNT(*) AS qtd
            FROM dealV2 d
            WHERE {$pCombo['sql']}
              AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
              AND d.faseId_dealV2 IN " . self::SQL_FASES_COMBO . "
              {$dim['sql']}
            GROUP BY mes
        ";

        $params = array_merge(
            $pVenda['params'], $dim['params'],
            $pVenda['params'], $dim['params'],
            $pCombo['params'], $dim['params']
        );

        $porMes = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $mes = (string)$r['mes'];
            if ($mes === '') {
                continue;
            }
            $porMes[$mes] = $porMes[$mes] ?? ['ing' => 0, 'cv' => 0, 'cb' => 0];
            $porMes[$mes][$r['serie']] = (int)$r['qtd'];
        }
        ksort($porMes);

        $serie = [];
        foreach ($porMes as $chave => $v) {
            if ($v['ing'] === 0 && $v['cv'] === 0 && $v['cb'] === 0) {
                continue;
            }
            $serie[] = [
                'mes' => $this->labelPeriodo($f['granularidade'], $chave),
                'ing' => $v['ing'],
                'cv'  => $v['cv'],
                'cb'  => $v['cb'],
            ];
        }
        return $serie;
    }

    // ===================================================================
    // CÁLCULO: série mensal da 2ª view (YoY)
    // ===================================================================

    /**
     * Eixo de meses da janela atual + os dois índices (ano-mês => posição).
     *
     * Separado de calcularSerieYoy() de propósito: é aqui que moram os erros
     * de YoY (desalinhamento entre os lados, mês parcial, 29/02) e este é o
     * único método do par que dá para exercitar sem banco.
     */
    private function eixoMesesYoy(array $pa): array
    {
        $NOMES = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        $y  = (int)substr($pa['inicio'], 0, 4);
        $m  = (int)substr($pa['inicio'], 5, 2);
        $yF = (int)substr($pa['fim'], 0, 4);
        $mF = (int)substr($pa['fim'], 5, 2);
        $multiAno = $y !== $yF;

        $meses = [];
        // Cap de 36 (mesma guarda do marketing): sem ela um filtro de 10 anos
        // gera 120 colunas e o gráfico fica ilegível.
        while (($y < $yF || ($y === $yF && $m <= $mF)) && count($meses) < 36) {
            $meses[] = [
                'y'     => $y,
                'm'     => $m,
                'label' => $NOMES[$m] . ($multiAno ? '/' . substr((string)$y, 2) : ''),
            ];
            if (++$m > 12) {
                $m = 1;
                $y++;
            }
        }

        $posAtual = [];
        $posLy    = [];
        foreach ($meses as $i => $mes) {
            $posAtual[$mes['y'] . '-' . $mes['m']]       = $i;
            $posLy[($mes['y'] - 1) . '-' . $mes['m']]    = $i;
        }

        return ['labels' => array_column($meses, 'label'), 'posAtual' => $posAtual, 'posLy' => $posLy];
    }

    /**
     * Série mês a mês das duas janelas, para os 2 gráficos da view YoY.
     *
     * UMA query: UNION ALL de 2 ramos (ingressos ancorados em dataVenda,
     * combos em dataContratoComboAssinado), cada ramo cobrindo os DOIS
     * períodos ((range atual) OR (range LY)) e devolvendo ano+mês, que o PHP
     * distribui nos dois lados.
     *
     * Diferente de calcularSerieMensal(), NÃO descarta mês zerado: o eixo é
     * gerado em PHP a partir do período e cada valor é posicionado por
     * (ano,mês) — nunca pela ordem das linhas. É o que mantém as duas séries
     * alinhadas mês a mês.
     *
     * Combos usa SQL_FASES_COMBO (COM C1:WON), não SQL_FASES_COMBO_CANAL: o
     * gráfico tem de somar exatamente o totalizador de Combos. Se trocar, o
     * déficit é exatamente a retenção.
     */
    private function calcularSerieYoy(array $fa, array $fl, array $pa): array
    {
        $eixo = $this->eixoMesesYoy($pa);
        $n    = count($eixo['labels']);
        $zero = array_fill(0, $n, 0);

        $out = [
            'meses' => $eixo['labels'],
            'atual' => ['ing' => $zero, 'cb' => $zero],
            'ly'    => ['ing' => $zero, 'cb' => $zero],
        ];
        if ($n === 0) {
            return $out;
        }

        $periodoDuplo = function (string $col) use ($fa, $fl) {
            $pA = $this->buildPeriodFilter($col, null, null, $fa['data_inicio'], $fa['data_fim']);
            $pL = $this->buildPeriodFilter($col, null, null, $fl['data_inicio'], $fl['data_fim']);
            return [
                'sql'    => "(({$pA['sql']}) OR ({$pL['sql']}))",
                'params' => array_merge($pA['params'], $pL['params']),
            ];
        };

        $perIng = $periodoDuplo('d.dataVenda_dealV2');
        $perCb  = $periodoDuplo('d.dataContratoComboAssinado_dealV2');
        $dim    = $this->filtrosDimensaoDeal($fa);   // idêntico nos dois lados

        $sql = "
            SELECT 'ing' AS serie,
                   YEAR(d.dataVenda_dealV2)  AS y,
                   MONTH(d.dataVenda_dealV2) AS m,
                   COUNT(*)                  AS qtd
            FROM dealV2 d
            WHERE d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
              AND d.faseId_dealV2 = 'C9:WON'
              AND {$perIng['sql']}
              {$dim['sql']}
            GROUP BY y, m

            UNION ALL

            SELECT 'cb' AS serie,
                   YEAR(d.dataContratoComboAssinado_dealV2)  AS y,
                   MONTH(d.dataContratoComboAssinado_dealV2) AS m,
                   COUNT(*)                                  AS qtd
            FROM dealV2 d
            WHERE d.dataContratoComboAssinado_dealV2 IS NOT NULL
              AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
              AND d.faseId_dealV2 IN " . self::SQL_FASES_COMBO . "
              AND {$perCb['sql']}
              {$dim['sql']}
            GROUP BY y, m
        ";

        // bind_param é posicional, na ordem em que os fragmentos aparecem no
        // TEXTO da query — e os params de $dim entram DUAS vezes, uma por ramo
        // do UNION ALL.
        $params = array_merge(
            $perIng['params'], $dim['params'],
            $perCb['params'],  $dim['params']
        );

        // Guarda barata: um array_merge fora de ordem desloca todos os params
        // e devolve número errado SEM erro nenhum (tudo é bindado como 's').
        if (substr_count($sql, '?') !== count($params)) {
            throw new \Exception(
                'calcularSerieYoy: placeholders e params não batem ('
                . substr_count($sql, '?') . ' vs ' . count($params) . ')'
            );
        }

        foreach ($this->queryAll($sql, $params) as $r) {
            $chave = (int)$r['y'] . '-' . (int)$r['m'];
            $k     = $r['serie'] === 'ing' ? 'ing' : 'cb';
            $qtd   = (int)$r['qtd'];

            // Dois ifs independentes, nunca elseif: numa janela maior que 12
            // meses o mesmo (ano,mês) é "atual" de uma posição e "LY" de outra.
            if (isset($eixo['posAtual'][$chave])) {
                $out['atual'][$k][$eixo['posAtual'][$chave]] = $qtd;
            }
            if (isset($eixo['posLy'][$chave])) {
                $out['ly'][$k][$eixo['posLy'][$chave]] = $qtd;
            }
        }

        return $out;
    }


    // ===================================================================
    // CÁLCULO: time de cadeiras (3ª view)
    // ===================================================================

    /**
     * Uma passada em leadV2 servindo DOIS blocos da view.
     *
     * Agrupar por (responsável, motivo) permite ler os dois de uma vez:
     *  - leads qualificados por vendedor = soma de 'quali' de TODAS as
     *    linhas dele, inclusive a de motivo vazio (o lead qualificado não
     *    precisa ter sido descartado);
     *  - descartes por motivo = soma de 'total' das linhas com motivo
     *    preenchido, SEM recorte de qualificado — é a regra da medida de
     *    origem, que só exige motivo não-vazio e tipoLead OTR.
     *
     * TRIM(COALESCE(...)) colapsa NULL e string vazia no mesmo bucket, que
     * o bloco de descartes ignora e o de leads soma. Sem isso os dois viriam
     * separados e o gráfico ganharia uma barra sem rótulo.
     *
     * tipoLead = 'OTR' sozinho (sem OTW): é a regra desta aba, diferente do
     * canal Evento das outras duas. Está no dicionário.
     */
    private function calcularLeadsCadeiras(array $f, array $vendedores): array
    {
        $periodo = $this->buildPeriodFilter('l.dataUltForm_leadV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $form    = $this->buildInFilter('l.formPreenchido_leadV2', $f['form']);
        $ph      = implode(',', array_fill(0, count($vendedores), '?'));

        $sql = "
            SELECT l.responsavel_leadV2                       AS resp,
                   TRIM(COALESCE(l.motivoDescarte_leadV2, '')) AS motivo,
                   COUNT(*)                                    AS total,
                   SUM(CASE WHEN l.qualificado_leadV2 = 'Sim' THEN 1 ELSE 0 END) AS quali
            FROM leadV2 l
            WHERE l.responsavel_leadV2 IN ($ph)
              AND l.tipoLead_leadV2 = 'OTR'
              AND l.dataUltForm_leadV2 IS NOT NULL AND l.dataUltForm_leadV2 > '0000-00-00'
              AND ({$periodo['sql']})
              {$form['sql']}
            GROUP BY resp, motivo
        ";

        $params = array_merge($vendedores, $periodo['params'], $form['params']);
        $this->conferirParams($sql, $params, 'calcularLeadsCadeiras');

        $porVendedor = [];
        $porMotivo   = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $resp   = (string)$r['resp'];
            $motivo = (string)$r['motivo'];

            $porVendedor[$resp] = ($porVendedor[$resp] ?? 0) + (int)$r['quali'];

            if ($motivo !== '') {
                $porMotivo[$motivo] = ($porMotivo[$motivo] ?? 0) + (int)$r['total'];
            }
        }

        arsort($porMotivo);
        $descartes = [];
        foreach (array_slice($porMotivo, 0, self::TOP_DESCARTES, true) as $motivo => $qtd) {
            $descartes[] = ['motivo' => $motivo, 'qtd' => $qtd];
        }

        return ['porVendedor' => $porVendedor, 'descartes' => $descartes];
    }

    /**
     * Uma passada em dealV2 servindo o ranking E a série mensal.
     *
     * 'C9:WON' é a mesma condição de cadeira vendida da Visão Geral, aqui
     * recortada pelos responsáveis do time.
     */
    private function calcularVendasCadeiras(array $f, array $vendedores): array
    {
        $periodo = $this->buildPeriodFilter('d.dataVenda_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $form    = $this->buildInFilter('d.formPreenchido_dealV2', $f['form']);
        $ph      = implode(',', array_fill(0, count($vendedores), '?'));
        $grupo   = $this->exprAgrupamentoPeriodo($f['granularidade'], 'd.dataVenda_dealV2');

        $sql = "
            SELECT d.responsavel_dealV2 AS resp,
                   $grupo               AS mes,
                   COUNT(*)             AS cadeiras
            FROM dealV2 d
            WHERE d.responsavel_dealV2 IN ($ph)
              AND d.faseId_dealV2 = 'C9:WON'
              AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
              AND ({$periodo['sql']})
              {$form['sql']}
            GROUP BY resp, mes
        ";

        $params = array_merge($vendedores, $periodo['params'], $form['params']);
        $this->conferirParams($sql, $params, 'calcularVendasCadeiras');

        $porVendedor = [];
        $porMes      = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $resp = (string)$r['resp'];
            $mes  = (string)$r['mes'];
            $qtd  = (int)$r['cadeiras'];

            $porVendedor[$resp] = ($porVendedor[$resp] ?? 0) + $qtd;

            if ($mes !== '') {
                $porMes[$mes] = ($porMes[$mes] ?? 0) + $qtd;
            }
        }

        ksort($porMes);

        // Período sem venda não entra na série (regra do relatório de
        // origem) — ao contrário da aba Comparativo, onde o mês vazio
        // precisa ficar para os dois anos continuarem alinhados.
        $serie = [];
        foreach ($porMes as $ym => $qtd) {
            if ($qtd <= 0) {
                continue;
            }
            $serie[] = [
                'mes'      => $this->labelPeriodo($f['granularidade'], $ym),
                'ym'       => $ym,
                'cadeiras' => $qtd,
            ];
        }

        return ['porVendedor' => $porVendedor, 'serie' => $serie];
    }

    /**
     * Junta as duas queries no ranking.
     *
     * Parte da lista de vendedores, não do que as queries devolveram: um
     * vendedor sem lead e sem venda no período tem de aparecer zerado, não
     * sumir da tabela.
     */
    private function montarRankingCadeiras(array $vendedores, array $leadsPorVendedor, array $cadeirasPorVendedor): array
    {
        $ranking = [];
        foreach ($vendedores as $nome) {
            $leads    = (int)($leadsPorVendedor[$nome] ?? 0);
            $cadeiras = (int)($cadeirasPorVendedor[$nome] ?? 0);
            $ranking[] = [
                'nome'     => $nome,
                'leads'    => $leads,
                'cadeiras' => $cadeiras,
                'conv'     => $leads > 0 ? round($cadeiras / $leads * 100, 2) : 0.0,
            ];
        }

        // Desempate explícito: cadeiras, depois conversão, depois nome. Sem
        // ele a ordem de dois vendedores empatados dependeria da ordem em
        // que o banco devolveu as linhas, e as medalhas ficariam instáveis
        // entre uma carga e outra.
        usort($ranking, function ($a, $b) {
            return ($b['cadeiras'] <=> $a['cadeiras'])
                ?: ($b['conv'] <=> $a['conv'])
                ?: strcmp($a['nome'], $b['nome']);
        });

        return $ranking;
    }

    /**
     * Guarda barata contra params fora de ordem: bind_param é posicional e
     * manda tudo como 's', então um array_merge trocado devolve número
     * errado sem erro nenhum.
     */
    private function conferirParams(string $sql, array $params, string $onde): void
    {
        if (substr_count($sql, '?') !== count($params)) {
            throw new \Exception(
                $onde . ': placeholders e params nao batem ('
                . substr_count($sql, '?') . ' vs ' . count($params) . ')'
            );
        }
    }


    // ===================================================================
    // CÁLCULO: time Comercial 1 (4ª view)
    // ===================================================================

    /**
     * Ranking (GROUP BY resp): cv e "Total de cadeiras" por vendedor, numa
     * única query — mesmo padrão de calcularDeals() (período dentro do
     * CASE, bound de scan no WHERE externo). Cobre SEMPRE os vendedores
     * dos dois lados juntos, porque cv é a métrica compartilhada.
     *
     * "Total de cadeiras" ≠ "cadeira vendida com" (C9:WON) da Visão Geral:
     * aqui a fase é "solta" (sem ":", ex. WON/LOSE/NEW) com
     * dataEvento_dealV2 preenchida — outra métrica, outra coluna de data.
     */
    private function calcularRankingDealComercial1(array $f, array $vendedores): array
    {
        $pVenda  = $this->buildPeriodFilter('d.dataVenda_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pEvento = $this->buildPeriodFilter('d.dataEvento_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);

        $preenchidaVenda  = "d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'";
        $preenchidaEvento = "d.dataEvento_dealV2 IS NOT NULL AND d.dataEvento_dealV2 > '0000-00-00'";

        $ph    = implode(',', array_fill(0, count($vendedores), '?'));
        $form  = $this->buildInFilter('d.formPreenchido_dealV2', $f['form']);
        // Bound de scan: sem isso o período vive só dentro do CASE e a
        // query varre a tabela inteira mesmo com índice disponível.
        $bound = $this->buildBoundMultiCol(
            ['d.dataVenda_dealV2', 'd.dataEvento_dealV2'],
            array_merge($pVenda['params'], $pEvento['params'])
        );

        $sql = "
            SELECT d.responsavel_dealV2 AS resp,
                   SUM(CASE WHEN {$pVenda['sql']} AND $preenchidaVenda
                             AND d.faseId_dealV2 IN ('WON', 'C11:WON')
                            THEN 1 ELSE 0 END) AS cv,
                   SUM(CASE WHEN {$pEvento['sql']} AND $preenchidaEvento
                             AND d.faseId_dealV2 NOT LIKE '%:%'
                            THEN 1 ELSE 0 END) AS cad
            FROM dealV2 d
            WHERE d.responsavel_dealV2 IN ($ph)
              AND ({$bound['sql']})
              {$form['sql']}
            GROUP BY resp
        ";

        $params = array_merge($pVenda['params'], $pEvento['params'], $vendedores, $bound['params'], $form['params']);
        $this->conferirParams($sql, $params, 'calcularRankingDealComercial1');

        $out = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $out[(string)$r['resp']] = ['cv' => (int)$r['cv'], 'cad' => (int)$r['cad']];
        }
        return $out;
    }

    /**
     * Série mensal (UNION ALL de 2 ramos, cada um GROUP BY resp+mês na SUA
     * coluna de data) — mesmo modelo de calcularSerieYoy(). Devolve linhas
     * cruas (serie/resp/mes/qtd); a agregação por lado (Evento soma cad+cv
     * dos 2 vendedores de Evento, Digital soma só cv dos 2 de Digital)
     * acontece em montarSeriesComercial1(), porque os dois lados somam
     * subconjuntos DIFERENTES de vendedores para a mesma métrica cv.
     */
    private function calcularSerieDealComercial1(array $f, array $vendedores): array
    {
        $pVenda  = $this->buildPeriodFilter('d.dataVenda_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pEvento = $this->buildPeriodFilter('d.dataEvento_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $form    = $this->buildInFilter('d.formPreenchido_dealV2', $f['form']);
        $ph      = implode(',', array_fill(0, count($vendedores), '?'));
        $grVenda  = $this->exprAgrupamentoPeriodo($f['granularidade'], 'd.dataVenda_dealV2');
        $grEvento = $this->exprAgrupamentoPeriodo($f['granularidade'], 'd.dataEvento_dealV2');

        $sql = "
            SELECT 'cv' AS serie, d.responsavel_dealV2 AS resp,
                   $grVenda AS mes,
                   COUNT(*) AS qtd
            FROM dealV2 d
            WHERE d.responsavel_dealV2 IN ($ph)
              AND d.faseId_dealV2 IN ('WON', 'C11:WON')
              AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
              AND ({$pVenda['sql']})
              {$form['sql']}
            GROUP BY resp, mes

            UNION ALL

            SELECT 'cad' AS serie, d.responsavel_dealV2 AS resp,
                   $grEvento AS mes,
                   COUNT(*) AS qtd
            FROM dealV2 d
            WHERE d.responsavel_dealV2 IN ($ph)
              AND d.faseId_dealV2 NOT LIKE '%:%'
              AND d.dataEvento_dealV2 IS NOT NULL AND d.dataEvento_dealV2 > '0000-00-00'
              AND ({$pEvento['sql']})
              {$form['sql']}
            GROUP BY resp, mes
        ";

        // vendedores e form entram DUAS vezes (uma por ramo do UNION ALL).
        $params = array_merge(
            $vendedores, $pVenda['params'], $form['params'],
            $vendedores, $pEvento['params'], $form['params']
        );
        $this->conferirParams($sql, $params, 'calcularSerieDealComercial1');

        return $this->queryAll($sql, $params);
    }

    /**
     * leadV2: leads qualificados digital, ranking (colapsa por resp) e
     * série (colapsa por mês) na MESMA passada — mesmo padrão de
     * calcularLeadsCadeiras(). Só os vendedores do lado Digital.
     *
     * tipoLead <> 'OTR' null-safe: NULL <> 'OTR' é NULL em SQL puro (o
     * registro sumiria), mas NOT("otr") em DAX é verdadeiro pra BLANK.
     */
    private function calcularLeadsDigitalComercial1(array $f, array $vendedoresDigital): array
    {
        $periodo = $this->buildPeriodFilter('l.dataUltForm_leadV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $form    = $this->buildInFilter('l.formPreenchido_leadV2', $f['form']);
        $ph      = implode(',', array_fill(0, count($vendedoresDigital), '?'));
        $grupo   = $this->exprAgrupamentoPeriodo($f['granularidade'], 'l.dataUltForm_leadV2');

        $sql = "
            SELECT l.responsavel_leadV2 AS resp,
                   $grupo AS mes,
                   SUM(CASE WHEN (l.tipoLead_leadV2 <> 'OTR' OR l.tipoLead_leadV2 IS NULL)
                             AND l.qualificado_leadV2 = 'Sim'
                            THEN 1 ELSE 0 END) AS leads_dg
            FROM leadV2 l
            WHERE l.responsavel_leadV2 IN ($ph)
              AND l.dataUltForm_leadV2 IS NOT NULL AND l.dataUltForm_leadV2 > '0000-00-00'
              AND ({$periodo['sql']})
              {$form['sql']}
            GROUP BY resp, mes
        ";

        $params = array_merge($vendedoresDigital, $periodo['params'], $form['params']);
        $this->conferirParams($sql, $params, 'calcularLeadsDigitalComercial1');

        $porVendedor = [];
        $porMes      = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $resp = (string)$r['resp'];
            $mes  = (string)$r['mes'];
            $qtd  = (int)$r['leads_dg'];

            $porVendedor[$resp] = ($porVendedor[$resp] ?? 0) + $qtd;
            if ($mes !== '') {
                $porMes[$mes] = ($porMes[$mes] ?? 0) + $qtd;
            }
        }

        return ['porVendedor' => $porVendedor, 'porMes' => $porMes];
    }

    /**
     * Ranking Evento: parte da lista de vendedores (não do que a query
     * devolveu) — vendedor sem atividade no período continua na tabela,
     * zerado, em vez de sumir.
     */
    private function montarRankingEventoComercial1(array $vendedoresEvento, array $rankingDeal): array
    {
        $out = [];
        foreach ($vendedoresEvento as $nome) {
            $cad = $rankingDeal[$nome]['cad'] ?? 0;
            $cv  = $rankingDeal[$nome]['cv']  ?? 0;
            $out[] = [
                'nome' => $nome,
                'cad'  => $cad,
                'cv'   => $cv,
                'conv' => $cad > 0 ? round($cv / $cad * 100, 2) : 0.0,
            ];
        }
        usort($out, fn($a, $b) => ($b['cad'] <=> $a['cad']) ?: ($b['conv'] <=> $a['conv']) ?: strcmp($a['nome'], $b['nome']));
        return $out;
    }

    /** Ranking Digital: mesmo princípio, cv vem da MESMA query de dealV2 (nunca recalculado por lado). */
    private function montarRankingDigitalComercial1(array $vendedoresDigital, array $leadsDgPorVendedor, array $rankingDeal): array
    {
        $out = [];
        foreach ($vendedoresDigital as $nome) {
            $leads = $leadsDgPorVendedor[$nome] ?? 0;
            $cv    = $rankingDeal[$nome]['cv'] ?? 0;
            $out[] = [
                'nome'     => $nome,
                'leads_dg' => $leads,
                'cv'       => $cv,
                'conv'     => $leads > 0 ? round($cv / $leads * 100, 2) : 0.0,
            ];
        }
        usort($out, fn($a, $b) => ($b['leads_dg'] <=> $a['leads_dg']) ?: ($b['conv'] <=> $a['conv']) ?: strcmp($a['nome'], $b['nome']));
        return $out;
    }

    /**
     * Pivota as linhas cruas de calcularSerieDealComercial1() (+ o porMes
     * de leads digital) nas duas séries mensais da view.
     *
     * cv soma subconjuntos DIFERENTES de vendedores em cada lado (só os de
     * Evento no lado Evento, só os de Digital no lado Digital) — por isso
     * não dá pra reaproveitar uma soma global de cv calculada uma vez só.
     *
     * Mês sem nenhuma métrica do seu lado é omitido (fiel ao filtro
     * COALESCE(...)>0 do DAX, aplicado por lado) — diferente da aba
     * Comparativo, onde o mês vazio tem de ficar pra alinhar os dois anos.
     */
    private function montarSeriesComercial1(array $linhasDeal, array $leadsDgPorMes, array $vendedoresEvento, array $vendedoresDigital, string $granularidade): array
    {
        $eventoPorMes  = [];
        $digitalCvPorMes = [];

        foreach ($linhasDeal as $r) {
            $resp   = (string)$r['resp'];
            $mes    = (string)$r['mes'];
            $serie  = (string)$r['serie'];
            $qtd    = (int)$r['qtd'];
            if ($mes === '') {
                continue;
            }

            if (in_array($resp, $vendedoresEvento, true)) {
                $eventoPorMes[$mes] = $eventoPorMes[$mes] ?? ['cad' => 0, 'cv' => 0];
                $eventoPorMes[$mes][$serie] += $qtd;
            }
            // cv também é somado pro lado Digital, quando o responsável é
            // do time Digital — 'cad' nunca entra aqui (não é exibido lá).
            if ($serie === 'cv' && in_array($resp, $vendedoresDigital, true)) {
                $digitalCvPorMes[$mes] = ($digitalCvPorMes[$mes] ?? 0) + $qtd;
            }
        }

        $rotulo = fn(string $ym) => $this->labelPeriodo($granularidade, $ym);

        ksort($eventoPorMes);
        $serieEvento = [];
        foreach ($eventoPorMes as $ym => $v) {
            if ($v['cad'] === 0 && $v['cv'] === 0) {
                continue;
            }
            $serieEvento[] = ['mes' => $rotulo($ym), 'ym' => $ym, 'cad' => $v['cad'], 'cv' => $v['cv']];
        }

        $mesesDigital = array_unique(array_merge(array_keys($digitalCvPorMes), array_keys($leadsDgPorMes)));
        sort($mesesDigital);
        $serieDigital = [];
        foreach ($mesesDigital as $ym) {
            $cv   = $digitalCvPorMes[$ym] ?? 0;
            $lead = $leadsDgPorMes[$ym] ?? 0;
            if ($cv === 0 && $lead === 0) {
                continue;
            }
            $serieDigital[] = ['mes' => $rotulo($ym), 'ym' => $ym, 'leads_dg' => $lead, 'cv' => $cv];
        }

        return ['evento' => $serieEvento, 'digital' => $serieDigital];
    }


    // ===================================================================
    // CÁLCULO: time Comercial 2 (5ª view)
    // ===================================================================

    /**
     * Ranking (GROUP BY resp): cr_ev, cr_dg, cb_ev, cb_dg numa única
     * query, mesmo padrão de calcularRankingDealComercial1() — período
     * dentro do CASE, bound de scan no WHERE externo (2 colunas de data).
     *
     * $evento/$digital são a MESMA condição de canal de calcularDeals()
     * (Visão Geral) — tipoLead IN ('OTR','OTW'), null-safe no lado
     * digital. Fases de combo usam SQL_FASES_COMBO_CANAL (sem C1:WON,
     * que é retenção e não pertence a nenhum canal).
     */
    private function calcularRankingComercial2(array $f, array $vendedores): array
    {
        $pReal  = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pCombo = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);

        $preenchidaReal  = "d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'";
        $preenchidaCombo = "d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'";

        $evento  = "d.tipoLead_dealV2 IN " . self::SQL_TIPOS_EVENTO;
        $digital = "(d.tipoLead_dealV2 IS NULL OR d.tipoLead_dealV2 NOT IN " . self::SQL_TIPOS_EVENTO . ")";

        $ph    = implode(',', array_fill(0, count($vendedores), '?'));
        $form  = $this->buildInFilter('d.formPreenchido_dealV2', $f['form']);
        $bound = $this->buildBoundMultiCol(
            ['d.dataConsultaRealizada_dealV2', 'd.dataContratoComboAssinado_dealV2'],
            array_merge($pReal['params'], $pCombo['params'])
        );

        $sql = "
            SELECT d.responsavel_dealV2 AS resp,
                   SUM(CASE WHEN {$pReal['sql']} AND $preenchidaReal
                             AND LEFT(d.faseId_dealV2, 2) = 'C5' AND $evento
                            THEN 1 ELSE 0 END) AS cr_ev,
                   SUM(CASE WHEN {$pReal['sql']} AND $preenchidaReal
                             AND LEFT(d.faseId_dealV2, 2) = 'C5' AND $digital
                            THEN 1 ELSE 0 END) AS cr_dg,
                   SUM(CASE WHEN {$pCombo['sql']} AND $preenchidaCombo
                             AND d.faseId_dealV2 IN " . self::SQL_FASES_COMBO_CANAL . " AND $evento
                            THEN 1 ELSE 0 END) AS cb_ev,
                   SUM(CASE WHEN {$pCombo['sql']} AND $preenchidaCombo
                             AND d.faseId_dealV2 IN " . self::SQL_FASES_COMBO_CANAL . " AND $digital
                            THEN 1 ELSE 0 END) AS cb_dg
            FROM dealV2 d
            WHERE d.responsavel_dealV2 IN ($ph)
              AND ({$bound['sql']})
              {$form['sql']}
            GROUP BY resp
        ";

        // Params do SELECT (período de cada CASE, na ordem do texto) antes
        // dos do WHERE — cr_ev e cr_dg repetem $pReal (2x), cb_ev e cb_dg
        // repetem $pCombo (2x), mesma convenção de calcularDeals().
        $params = array_merge(
            $pReal['params'], $pReal['params'], $pCombo['params'], $pCombo['params'],
            $vendedores, $bound['params'], $form['params']
        );
        $this->conferirParams($sql, $params, 'calcularRankingComercial2');

        $out = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $out[(string)$r['resp']] = [
                'cr_ev' => (int)$r['cr_ev'], 'cr_dg' => (int)$r['cr_dg'],
                'cb_ev' => (int)$r['cb_ev'], 'cb_dg' => (int)$r['cb_dg'],
            ];
        }
        return $out;
    }

    /**
     * Série mensal (UNION ALL de 4 ramos, cada um GROUP BY resp+mês na
     * sua própria coluna de data) — mesmo padrão de
     * calcularSerieDealComercial1(), estendido de 2 para 4 ramos porque
     * aqui canal (Evento/Digital) é um WHERE, não uma soma condicional
     * separada por linha.
     */
    private function calcularSerieComercial2(array $f, array $vendedores): array
    {
        $pReal  = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pCombo = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $form   = $this->buildInFilter('d.formPreenchido_dealV2', $f['form']);
        $ph     = implode(',', array_fill(0, count($vendedores), '?'));
        $grReal  = $this->exprAgrupamentoPeriodo($f['granularidade'], 'd.dataConsultaRealizada_dealV2');
        $grCombo = $this->exprAgrupamentoPeriodo($f['granularidade'], 'd.dataContratoComboAssinado_dealV2');

        $evento  = "d.tipoLead_dealV2 IN " . self::SQL_TIPOS_EVENTO;
        $digital = "(d.tipoLead_dealV2 IS NULL OR d.tipoLead_dealV2 NOT IN " . self::SQL_TIPOS_EVENTO . ")";

        $sql = "
            SELECT 'cr_ev' AS serie, d.responsavel_dealV2 AS resp,
                   $grReal AS mes, COUNT(*) AS qtd
            FROM dealV2 d
            WHERE d.responsavel_dealV2 IN ($ph)
              AND LEFT(d.faseId_dealV2, 2) = 'C5'
              AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
              AND ({$pReal['sql']}) AND $evento
              {$form['sql']}
            GROUP BY resp, mes

            UNION ALL

            SELECT 'cr_dg' AS serie, d.responsavel_dealV2 AS resp,
                   $grReal AS mes, COUNT(*) AS qtd
            FROM dealV2 d
            WHERE d.responsavel_dealV2 IN ($ph)
              AND LEFT(d.faseId_dealV2, 2) = 'C5'
              AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
              AND ({$pReal['sql']}) AND $digital
              {$form['sql']}
            GROUP BY resp, mes

            UNION ALL

            SELECT 'cb_ev' AS serie, d.responsavel_dealV2 AS resp,
                   $grCombo AS mes, COUNT(*) AS qtd
            FROM dealV2 d
            WHERE d.responsavel_dealV2 IN ($ph)
              AND d.faseId_dealV2 IN " . self::SQL_FASES_COMBO_CANAL . "
              AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
              AND ({$pCombo['sql']}) AND $evento
              {$form['sql']}
            GROUP BY resp, mes

            UNION ALL

            SELECT 'cb_dg' AS serie, d.responsavel_dealV2 AS resp,
                   $grCombo AS mes, COUNT(*) AS qtd
            FROM dealV2 d
            WHERE d.responsavel_dealV2 IN ($ph)
              AND d.faseId_dealV2 IN " . self::SQL_FASES_COMBO_CANAL . "
              AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
              AND ({$pCombo['sql']}) AND $digital
              {$form['sql']}
            GROUP BY resp, mes
        ";

        // vendedores e form entram 4x (uma por ramo); pReal 2x (ramos
        // cr_ev/cr_dg), pCombo 2x (ramos cb_ev/cb_dg).
        $params = array_merge(
            $vendedores, $pReal['params'],  $form['params'],
            $vendedores, $pReal['params'],  $form['params'],
            $vendedores, $pCombo['params'], $form['params'],
            $vendedores, $pCombo['params'], $form['params']
        );
        $this->conferirParams($sql, $params, 'calcularSerieComercial2');

        return $this->queryAll($sql, $params);
    }

    /**
     * Ranking final: parte da lista de vendedores (não do que a query
     * devolveu) — vendedor sem atividade continua na tabela, zerado. Os
     * 5 campos derivados (conv_ev/conv_dg/total_cr/total_cb/conv_t) são
     * calculados aqui, não em JS — mesma convenção server-side de
     * Cadeiras/Comercial1.
     *
     * SEM usort: ao contrário de Cadeiras/Comercial1, esta view não tem
     * pódio fixo — a posição na tabela é sempre a da ordenação escolhida
     * pelo usuário (decisão documentada no plano, fiel ao HTML de
     * origem). A ordenação acontece no JS.
     */
    private function montarRankingComercial2(array $vendedores, array $porVendedor): array
    {
        $out = [];
        foreach ($vendedores as $nome) {
            $d = $porVendedor[$nome] ?? ['cr_ev' => 0, 'cr_dg' => 0, 'cb_ev' => 0, 'cb_dg' => 0];
            $totalCr = $d['cr_ev'] + $d['cr_dg'];
            $totalCb = $d['cb_ev'] + $d['cb_dg'];
            $out[] = [
                'nome'     => $nome,
                'cr_ev'    => $d['cr_ev'], 'cr_dg' => $d['cr_dg'],
                'cb_ev'    => $d['cb_ev'], 'cb_dg' => $d['cb_dg'],
                'conv_ev'  => $d['cr_ev'] > 0 ? round($d['cb_ev'] / $d['cr_ev'] * 100, 2) : 0.0,
                'conv_dg'  => $d['cr_dg'] > 0 ? round($d['cb_dg'] / $d['cr_dg'] * 100, 2) : 0.0,
                'total_cr' => $totalCr, 'total_cb' => $totalCb,
                'conv_t'   => $totalCr > 0 ? round($totalCb / $totalCr * 100, 2) : 0.0,
            ];
        }
        return $out;
    }

    /**
     * Pivota as linhas cruas da série em meses, com as 4 métricas juntas
     * por (mês). Mês sem nenhuma das 4 é omitido — mesmo padrão de
     * calcularVendasCadeiras()/montarSeriesComercial1(). Sem conversão
     * aqui: os gráficos "ao longo do tempo" desta view mostram só as
     * contagens brutas (Evento × Digital), a conversão fica só nos
     * gráficos de performance por canal.
     */
    private function montarSerieComercial2(array $linhas, string $granularidade): array
    {
        $porMes = [];
        foreach ($linhas as $r) {
            $mes = (string)$r['mes'];
            if ($mes === '') {
                continue;
            }
            $porMes[$mes] = $porMes[$mes] ?? ['cr_ev' => 0, 'cr_dg' => 0, 'cb_ev' => 0, 'cb_dg' => 0];
            $porMes[$mes][(string)$r['serie']] += (int)$r['qtd'];
        }
        ksort($porMes);

        $serie = [];
        foreach ($porMes as $ym => $v) {
            if ($v['cr_ev'] === 0 && $v['cr_dg'] === 0 && $v['cb_ev'] === 0 && $v['cb_dg'] === 0) {
                continue;
            }
            $serie[] = [
                'mes'   => $this->labelPeriodo($granularidade, $ym),
                'ym'    => $ym,
                'cr_ev' => $v['cr_ev'], 'cr_dg' => $v['cr_dg'],
                'cb_ev' => $v['cb_ev'], 'cb_dg' => $v['cb_dg'],
            ];
        }
        return $serie;
    }


    // ===================================================================
    // CÁLCULO: time Comercial 3 / Retenção (6ª view)
    // ===================================================================

    /**
     * Ranking (GROUP BY resp): agend, realizada e combos numa única
     * query — mesmo padrão de calcularRankingComercial2(), estendido
     * de 2 para 3 métricas/3 colunas de data. Todas as 3 são recortes
     * do MESMO pipe (LEFT(faseId,3)='C1:'), sem split de canal
     * Evento/Digital — Retenção não usa tipoLead.
     */
    private function calcularRankingComercial3(array $f, array $vendedores): array
    {
        $pAgend = $this->buildPeriodFilter('d.dataConsultaAgendada_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pReal  = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pCombo = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);

        $preenchidaAgend = "d.dataConsultaAgendada_dealV2 IS NOT NULL AND d.dataConsultaAgendada_dealV2 > '0000-00-00'";
        $preenchidaReal  = "d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'";
        $preenchidaCombo = "d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'";
        $pipeC1          = "LEFT(d.faseId_dealV2, 3) = 'C1:'";

        $ph    = implode(',', array_fill(0, count($vendedores), '?'));
        $form  = $this->buildInFilter('d.formPreenchido_dealV2', $f['form']);
        $bound = $this->buildBoundMultiCol(
            ['d.dataConsultaAgendada_dealV2', 'd.dataConsultaRealizada_dealV2', 'd.dataContratoComboAssinado_dealV2'],
            array_merge($pAgend['params'], $pReal['params'], $pCombo['params'])
        );

        $sql = "
            SELECT d.responsavel_dealV2 AS resp,
                   SUM(CASE WHEN {$pAgend['sql']} AND $preenchidaAgend AND $pipeC1
                            THEN 1 ELSE 0 END) AS agend,
                   SUM(CASE WHEN {$pReal['sql']} AND $preenchidaReal AND $pipeC1
                            THEN 1 ELSE 0 END) AS realizada,
                   SUM(CASE WHEN {$pCombo['sql']} AND $preenchidaCombo AND d.faseId_dealV2 = 'C1:WON'
                            THEN 1 ELSE 0 END) AS combos
            FROM dealV2 d
            WHERE d.responsavel_dealV2 IN ($ph)
              AND ({$bound['sql']})
              {$form['sql']}
            GROUP BY resp
        ";

        // Params do SELECT (período de cada CASE, na ordem do texto) antes
        // dos do WHERE — mesma convenção de calcularRankingComercial2().
        $params = array_merge(
            $pAgend['params'], $pReal['params'], $pCombo['params'],
            $vendedores, $bound['params'], $form['params']
        );
        $this->conferirParams($sql, $params, 'calcularRankingComercial3');

        $out = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $out[(string)$r['resp']] = [
                'agend'     => (int)$r['agend'],
                'realizada' => (int)$r['realizada'],
                'combos'    => (int)$r['combos'],
            ];
        }
        return $out;
    }

    /**
     * Ranking final: parte da lista de vendedores (não do que a query
     * devolveu — time inteiro aparece mesmo zerado). Calcula as 2
     * conversões (Agendada→Combo, Realizada→Combo) em PHP. Um único
     * array serve os 2 rankings da view (por Agendada e por
     * Realizada): a view escolhe a coluna de ordenação, não a query.
     */
    private function montarRankingComercial3(array $vendedores, array $porVendedor): array
    {
        $out = [];
        foreach ($vendedores as $nome) {
            $d = $porVendedor[$nome] ?? ['agend' => 0, 'realizada' => 0, 'combos' => 0];
            $out[] = [
                'nome'       => $nome,
                'agend'      => $d['agend'],
                'realizada'  => $d['realizada'],
                'combos'     => $d['combos'],
                'conv_agend' => $d['agend']     > 0 ? round($d['combos'] / $d['agend']     * 100, 2) : 0.0,
                'conv_real'  => $d['realizada'] > 0 ? round($d['combos'] / $d['realizada'] * 100, 2) : 0.0,
            ];
        }
        return $out;
    }

    /**
     * Série mensal (UNION ALL de 2 ramos, cada um GROUP BY resp+mês na
     * sua própria coluna de data) — mesmo padrão de
     * calcularSerieDealComercial1(). Só agend × combos (a "Evolução ao
     * Longo do Tempo" do relatório de origem não inclui realizada).
     */
    private function calcularSerieComercial3(array $f, array $vendedores): array
    {
        $pAgend = $this->buildPeriodFilter('d.dataConsultaAgendada_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pCombo = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $form   = $this->buildInFilter('d.formPreenchido_dealV2', $f['form']);
        $ph     = implode(',', array_fill(0, count($vendedores), '?'));
        $grAgend = $this->exprAgrupamentoPeriodo($f['granularidade'], 'd.dataConsultaAgendada_dealV2');
        $grCombo = $this->exprAgrupamentoPeriodo($f['granularidade'], 'd.dataContratoComboAssinado_dealV2');

        $sql = "
            SELECT 'agend' AS serie, d.responsavel_dealV2 AS resp,
                   $grAgend AS mes, COUNT(*) AS qtd
            FROM dealV2 d
            WHERE d.responsavel_dealV2 IN ($ph)
              AND LEFT(d.faseId_dealV2, 3) = 'C1:'
              AND d.dataConsultaAgendada_dealV2 IS NOT NULL AND d.dataConsultaAgendada_dealV2 > '0000-00-00'
              AND ({$pAgend['sql']})
              {$form['sql']}
            GROUP BY resp, mes

            UNION ALL

            SELECT 'combos' AS serie, d.responsavel_dealV2 AS resp,
                   $grCombo AS mes, COUNT(*) AS qtd
            FROM dealV2 d
            WHERE d.responsavel_dealV2 IN ($ph)
              AND d.faseId_dealV2 = 'C1:WON'
              AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
              AND ({$pCombo['sql']})
              {$form['sql']}
            GROUP BY resp, mes
        ";

        // vendedores e form entram 2x (uma por ramo do UNION ALL).
        $params = array_merge(
            $vendedores, $pAgend['params'], $form['params'],
            $vendedores, $pCombo['params'], $form['params']
        );
        $this->conferirParams($sql, $params, 'calcularSerieComercial3');

        return $this->queryAll($sql, $params);
    }

    /**
     * Pivota as linhas cruas em meses (agend + combos por mês, somando
     * os 2 vendedores). Mês sem nenhuma das 2 métricas é omitido —
     * mesma convenção de montarSerieComercial2().
     */
    private function montarSerieComercial3(array $linhas, string $granularidade): array
    {
        $porMes = [];
        foreach ($linhas as $r) {
            $mes = (string)$r['mes'];
            if ($mes === '') {
                continue;
            }
            $porMes[$mes] = $porMes[$mes] ?? ['agend' => 0, 'combos' => 0];
            $porMes[$mes][(string)$r['serie']] += (int)$r['qtd'];
        }
        ksort($porMes);

        $serie = [];
        foreach ($porMes as $ym => $v) {
            if ($v['agend'] === 0 && $v['combos'] === 0) {
                continue;
            }
            $serie[] = [
                'mes'    => $this->labelPeriodo($granularidade, $ym),
                'ym'     => $ym,
                'agend'  => $v['agend'],
                'combos' => $v['combos'],
            ];
        }
        return $serie;
    }

    /**
     * Top 10 motivos de descarte do time, pipe C1. O DAX de origem não
     * amarra esta medida a nenhuma coluna de data explícita (herda o
     * contexto de filtro da página no Power BI); para manter esta view
     * respondendo ao filtro de período como todas as outras, o período
     * precisa de uma coluna de data preenchida em praticamente todo
     * registro descartado.
     *
     * dataConsultaAgendada_dealV2 NÃO serve para isso: medido contra
     * produção, 790 dos 847 descartes do time (93%) nunca chegaram a
     * ter consulta agendada — foram descartados antes dessa etapa. Com
     * esse anchor, o filtro do ano corrente devolvia só 6 registros
     * (o card "mostrava menos do que realmente existe", achado do
     * usuário ao validar). criadoEm_dealV2 (data de criação do card,
     * indexada) está preenchida em 100% dos 847 — é o anchor correto
     * aqui, mesmo sendo uma coluna diferente da usada no 1º ranking.
     * motivoDescarte_dealV2 é varchar NOT NULL (nunca NULL, só ''),
     * então "não vazio" é só "<> ''" — sem a variante null-safe usada
     * para motivoDescarte_leadV2.
     */
    private function calcularDescartesComercial3(array $f, array $vendedores): array
    {
        $periodo = $this->buildPeriodFilter('d.criadoEm_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $form    = $this->buildInFilter('d.formPreenchido_dealV2', $f['form']);
        $ph      = implode(',', array_fill(0, count($vendedores), '?'));

        $sql = "
            SELECT d.motivoDescarte_dealV2 AS motivo, COUNT(*) AS qtd
            FROM dealV2 d
            WHERE d.responsavel_dealV2 IN ($ph)
              AND LEFT(d.faseId_dealV2, 3) = 'C1:'
              AND d.motivoDescarte_dealV2 <> ''
              AND d.criadoEm_dealV2 IS NOT NULL AND d.criadoEm_dealV2 > '0000-00-00'
              AND ({$periodo['sql']})
              {$form['sql']}
            GROUP BY motivo
        ";

        $params = array_merge($vendedores, $periodo['params'], $form['params']);
        $this->conferirParams($sql, $params, 'calcularDescartesComercial3');

        $porMotivo = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $porMotivo[(string)$r['motivo']] = (int)$r['qtd'];
        }
        arsort($porMotivo);

        $descartes = [];
        foreach (array_slice($porMotivo, 0, self::TOP_DESCARTES, true) as $motivo => $qtd) {
            $descartes[] = ['motivo' => $motivo, 'qtd' => $qtd];
        }
        return $descartes;
    }


    // ===================================================================
    // HELPERS: FILTROS
    // ===================================================================

    private function lerFiltros(): array
    {
        $granularidade = $this->g('granularidade');
        return [
            'year'          => $this->g('year'),
            'months'        => $this->g('months'),
            'data_inicio'   => $this->g('data_inicio'),
            'data_fim'      => $this->g('data_fim'),
            'form'          => $this->g('form'),
            'tipo_lead'     => $this->g('tipo_lead'),
            'responsavel'   => $this->g('responsavel'),
            // A URL só restringe: um valor fora da whitelist cai no default
            // 'mes' em vez de propagar lixo pros gráficos de série.
            'granularidade' => in_array($granularidade, ['semana', 'dia'], true) ? $granularidade : 'mes',
        ];
    }

    /**
     * Grão de agrupamento dos 5 gráficos de "evolução no tempo" do dashboard
     * (Visão Geral, Cadeiras, Comercial 1/2/3 — o YoY fica fora, sempre
     * mensal). 'dia' é data completa (não dia-do-mês agregado), mesmo
     * padrão já usado em law_controle.php::exprAgrupamento().
     */
    private function exprAgrupamentoPeriodo(string $granularidade, string $col): string
    {
        return match ($granularidade) {
            'dia'    => "DATE($col)",
            'semana' => "YEARWEEK($col, 1)",
            default  => "DATE_FORMAT($col, '%Y-%m')",
        };
    }

    /** Rótulo de exibição do valor de período, conforme a granularidade — par de exprAgrupamentoPeriodo(). */
    private function labelPeriodo(string $granularidade, string $valor): string
    {
        static $abrev = ['01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr', '05' => 'Mai', '06' => 'Jun',
                          '07' => 'Jul', '08' => 'Ago', '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'];

        if ($granularidade === 'dia') {
            return date('d/m', strtotime($valor));
        }
        if ($granularidade === 'semana') {
            return 'S' . substr($valor, -2) . '/' . substr($valor, 2, 2);
        }
        $mm = substr($valor, 5, 2);
        return ($abrev[$mm] ?? $mm) . '/' . substr($valor, 2, 2);
    }

    /** Filtros de dimensão aplicáveis a dealV2 (fragmento já prefixado com AND). */
    private function filtrosDimensaoDeal(array $f): array
    {
        $form = $this->buildInFilter('d.formPreenchido_dealV2', $f['form']);
        $tipo = $this->buildTipoLeadFilter('d.tipoLead_dealV2', $f['tipo_lead']);
        $resp = $this->buildInFilter('d.responsavel_dealV2', $f['responsavel']);

        return [
            'sql'    => $form['sql'] . ' ' . $tipo['sql'] . ' ' . $resp['sql'],
            'params' => array_merge($form['params'], $tipo['params'], $resp['params']),
        ];
    }

    /** Filtros de dimensão aplicáveis a leadV2 (fragmento já prefixado com AND). */
    private function filtrosDimensaoLead(array $f): array
    {
        $form = $this->buildInFilter('l.formPreenchido_leadV2', $f['form']);
        $tipo = $this->buildTipoLeadFilter('l.tipoLead_leadV2', $f['tipo_lead']);
        $resp = $this->buildInFilter('l.responsavel_leadV2', $f['responsavel']);

        return [
            'sql'    => $form['sql'] . ' ' . $tipo['sql'] . ' ' . $resp['sql'],
            'params' => array_merge($form['params'], $tipo['params'], $resp['params']),
        ];
    }

    /**
     * Filtro de tipoLead. O CSV não transporta string vazia, então a UI manda o
     * token '(vazio)' quando o usuário seleciona os leads sem tipo preenchido —
     * aqui ele volta a ser ''.
     */
    private function buildTipoLeadFilter(string $col, ?string $csv): array
    {
        if ($csv === null || trim($csv) === '') {
            return ['sql' => '', 'params' => []];
        }
        $vals = array_values(array_filter(array_map('trim', explode(',', $csv)), fn($v) => $v !== ''));
        if (!$vals) {
            return ['sql' => '', 'params' => []];
        }
        $vals = array_map(fn($v) => $v === self::TIPO_LEAD_VAZIO ? '' : $v, $vals);

        return ['sql' => "AND $col IN (" . implode(',', array_fill(0, count($vals), '?')) . ")", 'params' => $vals];
    }

    /**
     * Bound de scan para uma query cujo filtro de período vive dentro dos CASE:
     * restringe o WHERE ao intervalo que engloba todos os períodos usados, em
     * qualquer uma das colunas de data.
     *
     * Só considera params que sejam de fato data (YYYY-MM-DD...) — quando o
     * usuário escolhe mês sem ano, buildDateFilter devolve params numéricos de
     * MONTH() e não existe intervalo contínuo para limitar.
     */
    private function buildBoundMultiCol(array $cols, array $paramsDeData): array
    {
        $datas = array_values(array_filter($paramsDeData, fn($v) => (bool)preg_match('/^\d{4}-\d{2}-\d{2}/', (string)$v)));
        if (!$datas) {
            return ['sql' => '1=1', 'params' => []];
        }

        $min = min($datas);
        $max = max($datas);

        $conds  = [];
        $params = [];
        foreach ($cols as $col) {
            $conds[]  = "($col >= ? AND $col <= ?)";
            $params[] = $min;
            $params[] = $max;
        }
        return ['sql' => '(' . implode(' OR ', $conds) . ')', 'params' => $params];
    }

    /**
     * Filtro ano/mês como intervalo de data literal (sargável) em vez de
     * YEAR($col)/MONTH($col) — envolver a coluna em função anula o índice mesmo
     * dentro de um WHERE. Guarda de coluna-expressão mantida por consistência
     * com os demais controllers.
     */
    private function buildDateFilter(string $col, $year, ?string $months): array
    {
        if (!preg_match('/^[A-Za-z_]\w*\.\w+$/', $col)) {
            return $this->buildDateFilterLegacy($col, $year, $months);
        }

        $years = ($year !== null && $year !== '')
            ? array_values(array_filter(array_map('trim', explode(',', (string)$year)), fn($v) => $v !== ''))
            : [];
        $ms = ($months !== null && $months !== '')
            ? array_values(array_filter(array_map('trim', explode(',', $months)), fn($v) => $v !== ''))
            : [];

        if (!$years) {
            if (!$ms) {
                return ['sql' => '1=1', 'params' => []];
            }
            return [
                'sql'    => "MONTH($col) IN (" . implode(',', array_fill(0, count($ms), '?')) . ")",
                'params' => $ms,
            ];
        }

        $monthInts = [];
        foreach ($ms as $m) {
            $monthInts[] = (int)$m;
        }
        $monthInts = array_values(array_unique($monthInts));
        sort($monthInts);

        $ranges = [];
        $params = [];
        foreach ($years as $yStr) {
            $y = (int)$yStr;
            if (!$monthInts) {
                $ranges[] = "($col >= ? AND $col <= ?)";
                $params[] = sprintf('%04d-01-01 00:00:00', $y);
                $params[] = sprintf('%04d-12-31 23:59:59', $y);
                continue;
            }
            foreach ($this->agruparMesesContiguos($monthInts) as [$mStart, $mEnd]) {
                $ranges[] = "($col >= ? AND $col <= ?)";
                $params[] = sprintf('%04d-%02d-01 00:00:00', $y, $mStart);
                $params[] = sprintf('%04d-%02d-%02d 23:59:59', $y, $mEnd, $this->ultimoDiaDoMes($y, $mEnd));
            }
        }

        return ['sql' => '(' . implode(' OR ', $ranges) . ')', 'params' => $params];
    }

    /** Versão antiga (YEAR()/MONTH(), não-sargável) — usada só se $col não for coluna simples. */
    private function buildDateFilterLegacy(string $col, $year, ?string $months): array
    {
        $conds  = [];
        $params = [];

        if ($year !== null && $year !== '') {
            $years = array_values(array_filter(array_map('trim', explode(',', (string)$year)), fn($v) => $v !== ''));
            if (count($years) === 1) {
                $conds[]  = "YEAR($col) = ?";
                $params[] = $years[0];
            } elseif (count($years) > 1) {
                $conds[] = "YEAR($col) IN (" . implode(',', array_fill(0, count($years), '?')) . ")";
                array_push($params, ...$years);
            }
        }

        if ($months !== null && $months !== '') {
            $ms = array_values(array_filter(array_map('trim', explode(',', $months)), fn($v) => $v !== ''));
            if ($ms) {
                $conds[] = "MONTH($col) IN (" . implode(',', array_fill(0, count($ms), '?')) . ")";
                array_push($params, ...$ms);
            }
        }

        return ['sql' => $conds ? implode(' AND ', $conds) : '1=1', 'params' => $params];
    }

    /** Agrupa uma lista ordenada de meses (1-12) em pares [inicio,fim] de sequências contíguas. */
    private function agruparMesesContiguos(array $monthIntsOrdenados): array
    {
        $runs  = [];
        $start = $end = null;
        foreach ($monthIntsOrdenados as $m) {
            if ($start === null) {
                $start = $end = $m;
            } elseif ($m === $end + 1) {
                $end = $m;
            } else {
                $runs[] = [$start, $end];
                $start = $end = $m;
            }
        }
        if ($start !== null) {
            $runs[] = [$start, $end];
        }
        return $runs;
    }

    /** Último dia do mês $m no ano $y, sem depender de timezone (mktime/date). */
    private function ultimoDiaDoMes(int $y, int $m): int
    {
        if (in_array($m, [1, 3, 5, 7, 8, 10, 12], true)) {
            return 31;
        }
        if (in_array($m, [4, 6, 9, 11], true)) {
            return 30;
        }
        return ($y % 4 === 0 && ($y % 100 !== 0 || $y % 400 === 0)) ? 29 : 28;
    }

    /** Filtro de período: range (data_inicio/data_fim) OU year/months. */
    private function buildPeriodFilter(string $col, $year, ?string $months, ?string $dataInicio = null, ?string $dataFim = null): array
    {
        if (($dataInicio !== null && $dataInicio !== '') || ($dataFim !== null && $dataFim !== '')) {
            $conds  = [];
            $params = [];
            if ($dataInicio !== null && $dataInicio !== '') {
                $conds[]  = "$col >= ?";
                $params[] = $dataInicio . ' 00:00:00';
            }
            if ($dataFim !== null && $dataFim !== '') {
                $conds[]  = "$col <= ?";
                $params[] = $dataFim . ' 23:59:59';
            }
            return ['sql' => $conds ? '(' . implode(' AND ', $conds) . ')' : '1=1', 'params' => $params];
        }
        return $this->buildDateFilter($col, $year, $months);
    }

    // ===================================================================
    // HELPERS: PERÍODO DA 2ª VIEW — YoY (ano × mesmo período do ano-1)
    // Cópia de marketing_controle.php (cópia por controller é a convenção
    // do projeto — não há trait compartilhado).
    // ===================================================================

    /**
     * Resolve o filtro do request num intervalo literal [inicio, fim].
     *
     * O fim é SEMPRE limitado a hoje. Sem isso, "ano = 2026" compararia 8
     * meses de 2026 contra 12 meses de 2025 e toda métrica de volume
     * apareceria despencando — que é exatamente o erro que a comparação por
     * "mesmo período" existe para evitar. Como a barra de período da view
     * mostra as datas resolvidas, o corte fica visível para o usuário.
     */
    private function periodoEfetivo(array $f): array
    {
        $hoje = date('Y-m-d');

        if (!empty($f['data_inicio']) || !empty($f['data_fim'])) {
            $fim    = !empty($f['data_fim']) ? substr($f['data_fim'], 0, 10) : $hoje;
            $inicio = !empty($f['data_inicio']) ? substr($f['data_inicio'], 0, 10) : substr($fim, 0, 4) . '-01-01';
        } else {
            $anos = array_values(array_filter(array_map('trim', explode(',', (string)($f['year'] ?? ''))), fn($v) => $v !== ''));
            $anos = $anos ? array_map('intval', $anos) : [(int)date('Y')];
            sort($anos);

            $meses = array_values(array_filter(array_map('trim', explode(',', (string)($f['months'] ?? ''))), fn($v) => $v !== ''));
            $meses = $meses ? array_map('intval', $meses) : [1, 12];
            sort($meses);

            $mIni = $meses[0];
            $mFim = $meses[count($meses) - 1];
            $inicio = sprintf('%04d-%02d-01', $anos[0], $mIni);
            $aFim   = $anos[count($anos) - 1];
            $fim    = sprintf('%04d-%02d-%02d', $aFim, $mFim, $this->ultimoDiaDoMes($aFim, $mFim));
        }

        if ($fim > $hoje) {
            $fim = $hoje;
        }
        if ($inicio > $fim) {
            $inicio = $fim;
        }
        return ['inicio' => $inicio, 'fim' => $fim];
    }

    /** Troca year/months por um intervalo literal, para os dois lados da comparação usarem exatamente a mesma janela. */
    private function comFiltroDeData(array $f, array $periodo): array
    {
        $f['year']        = null;
        $f['months']      = null;
        $f['data_inicio'] = $periodo['inicio'];
        $f['data_fim']    = $periodo['fim'];
        return $f;
    }

    /** Mesmo intervalo, 12 meses atrás. Espera receber os filtros já resolvidos por comFiltroDeData(). */
    private function deslocarPeriodoUmAno(array $f): array
    {
        $f['data_inicio'] = $this->menosUmAno($f['data_inicio']);
        $f['data_fim']    = $this->menosUmAno($f['data_fim']);
        return $f;
    }

    /** 'YYYY-MM-DD' menos um ano, com guarda de 29/02 (que não existe no ano anterior). */
    private function menosUmAno(?string $data): ?string
    {
        if ($data === null || $data === '') {
            return $data;
        }
        $y = (int)substr($data, 0, 4) - 1;
        $m = (int)substr($data, 5, 2);
        $d = (int)substr($data, 8, 2);
        $d = min($d, $this->ultimoDiaDoMes($y, $m));
        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    }

    private function buildInFilter(string $col, ?string $csv): array
    {
        if ($csv === null || trim($csv) === '') {
            return ['sql' => '', 'params' => []];
        }
        $vals = array_values(array_filter(array_map('trim', explode(',', $csv)), fn($v) => $v !== ''));
        if (!$vals) {
            return ['sql' => '', 'params' => []];
        }
        return ['sql' => "AND $col IN (" . implode(',', array_fill(0, count($vals), '?')) . ")", 'params' => $vals];
    }

    // ===================================================================
    // HELPERS: HTTP / JSON
    // ===================================================================

    private function g(string $key): ?string
    {
        $v = $_GET[$key] ?? null;
        return ($v === null || $v === '') ? null : trim($v);
    }

    private function jsonOut($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function jsonError(\Throwable $e): void
    {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        $isDev = strtoupper(self::$config['app']['env'] ?? 'DEV') === 'DEV';
        echo json_encode(
            $isDev
                ? ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]
                : ['error' => 'Erro interno do servidor.'],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    // ===================================================================
    // HELPERS: EXECUÇÃO DE QUERY (mysqli, sempre ? posicional + bind_param)
    // ===================================================================

    private function queryAll(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new \Exception('Erro na preparação da consulta: ' . $this->db->error);
        }
        if ($params) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }

    private function queryOne(string $sql, array $params = []): array
    {
        return $this->queryAll($sql, $params)[0] ?? [];
    }

    // ===================================================================
    // HELPERS: CACHE EM ARQUIVO (.mrphp/cache)
    // Cópia própria do controller, sem camada compartilhada — convenção do
    // projeto (mesmo bloco de executive/rafaeltorres/marketing).
    // ===================================================================

    private const CACHE_TTL_DROPDOWN = 600;
    private const CACHE_TTL_DADOS = 600;

    private function cacheDir(): string
    {
        return dirname(__DIR__) . '/.mrphp/cache';
    }

    private function cacheGet(string $key, int $ttlSeconds)
    {
        $file = $this->cacheDir() . '/' . $key . '.json';
        if (!is_file($file) || (time() - filemtime($file)) > $ttlSeconds) {
            return null;
        }
        $raw = @file_get_contents($file);
        if ($raw === false || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    private function cacheSet(string $key, $data): void
    {
        $file = $this->cacheDir() . '/' . $key . '.json';
        @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        $this->cacheGc();
    }

    private function cacheKeyFromRequest(string $endpoint): string
    {
        return $endpoint . '_' . md5($_SERVER['QUERY_STRING'] ?? '');
    }

    private function cacheGc(): void
    {
        if (mt_rand(1, 100) > 2) {
            return;
        }
        foreach (glob($this->cacheDir() . '/*.json') ?: [] as $file) {
            if ((time() - filemtime($file)) > 7200) {
                @unlink($file);
            }
        }
    }
}
