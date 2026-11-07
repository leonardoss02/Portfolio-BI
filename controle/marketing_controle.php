<?php

/**
 * Dashboard "Marketing" (Dashboard ADS) — MRPHP.
 *
 * Mesmo padrão de rafaeltorres_controle.php/eventos_controle.php (sem camada
 * calculos/): toda a lógica de SQL/cálculo fica em métodos privados desta
 * classe. Reaproveita o funil comercial de executive_controle.php, mas sem
 * custo físico de evento (custoEvento) — só investimento de marketing
 * (custoMkt) — e adiciona a métrica "Oportunidades" (engajamento no
 * Chatwoot, mesma lógica de eventos_controle.php::calcularLeadsPorEvento(),
 * aqui aplicada globalmente em vez de por evento) e granularidade de
 * Conjunto de Anúncios/Anúncio (via UTM content/term), inexistente nos
 * demais dashboards.
 *
 * "Cliques" (custoMkt) usa a coluna custoMkt.clicks_custoMkt (confirmada via
 * SHOW COLUMNS no banco de dev — nome em inglês, não "cliques_custoMkt").
 */
class marketing extends MRPHP
{
    public function index()
    {
        $this->setTemplate('marketing');
    }

    public function dicionario()
    {
        $this->setTemplate('marketing');
    }

    public function comparativo()
    {
        $this->setTemplate('marketing');
    }

    public function eventos()
    {
        $this->setTemplate('marketing');
    }

    public function desempenho()
    {
        $this->setTemplate('marketing');
    }

    public function descartes()
    {
        $this->setTemplate('marketing');
    }

    public function classificacao()
    {
        $this->setTemplate('marketing');
    }

    /**
     * GET /marketing/comparativoDados — 2ª view: ano corrente contra o MESMO
     * período do ano anterior (deslocado 12 meses, nunca ano civil inteiro).
     *
     * Reaproveita as mesmas funções de dados(), chamadas duas vezes — uma com
     * os filtros do request, outra com os filtros deslocados. Nenhuma fórmula
     * de métrica é reescrita aqui: é o que garante que kpis_ly desta rota bata
     * número a número com os kpis de /marketing/dados no ano anterior.
     *
     * Custo: 6 queries (3 fontes × 2 períodos) + 4 do bloco mensal. As 3
     * funções *GlobalFormClasse() já devolvem global+Evento+Digital numa query
     * cada, e como a tabela desta view tem exatamente essas 3 linhas, elas
     * servem KPIs E tabela — as *PorDimensoes() não são chamadas.
     */
    public function comparativoDados()
    {
        set_time_limit(180);
        try {
            $cacheKey = $this->cacheKeyFromRequest('marketing_comparativo');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f  = $this->lerFiltros();
            $pa = $this->periodoEfetivo($f);
            $fa = $this->comFiltroDeData($f, $pa);          // período atual, já resolvido em datas
            $fl = $this->deslocarPeriodoUmAno($fa);          // mesmo intervalo, -12 meses
            $pl = ['inicio' => $fl['data_inicio'], 'fim' => $fl['data_fim']];

            $bids = $this->engajadosBidsList();              // cache próprio, chave fixa: 1× para os 2 períodos

            $lado = function (array $ff) use ($bids) {
                $custo = $this->custoMktGlobalFormClasse($ff, 'campanha');
                $leads = $this->leadsOportunidadesGlobalFormClasse($ff, $bids, 'campanha');
                $funil = $this->funilVendasGlobalFormClasse($ff, 'campanha');
                return [
                    'kpis'   => $this->linhaComparativo($custo['global'], $leads['global'], $funil['global']),
                    'evento' => $this->linhaComparativo($custo['Evento'], $leads['Evento'], $funil['Evento']),
                    'digital'=> $this->linhaComparativo($custo['Digital'], $leads['Digital'], $funil['Digital']),
                ];
            };

            $atual = $lado($fa);
            $ly    = $lado($fl);

            $payload = [
                'meta' => $f + [
                    'periodo_atual' => $pa,
                    'periodo_ly'    => $pl,
                    'ano_atual'     => substr($pa['fim'], 0, 4),
                    'ano_ly'        => substr($pl['fim'], 0, 4),
                ],
                'kpis'     => $atual['kpis'],
                'kpis_ly'  => $ly['kpis'],
                'funil'    => $this->montarFunilMarketing($atual['kpis']),
                'funil_ly' => $this->montarFunilMarketing($ly['kpis']),
                // Pareado no PHP: as 3 linhas são fixas e conhecidas, então não
                // há merge por chave no front para dar errado.
                'tabela' => [
                    ['classe' => 'Evento',  'atual' => $atual['evento'],  'ly' => $ly['evento']],
                    ['classe' => 'Digital', 'atual' => $atual['digital'], 'ly' => $ly['digital']],
                    ['classe' => 'Total',   'atual' => $atual['kpis'],    'ly' => $ly['kpis']],
                ],
                'mensal' => $this->seriesMensais($fa, $fl, $pa),
            ];

            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * GET /marketing/dados — payload único: 7 KPIs, funil de vendas e as 4
     * tabelas de breakdown (Campanha / Conjunto de Anúncios / Anúncios /
     * Classificação de Form).
     */
    public function dados()
    {
        // Fase 1B (item 3): consolidado de 19 queries (global + 3 dimensões + 2
        // form-classe, cada combinação chamando custoMkt/leadsOportunidades/funilVendas
        // separadamente) para 6 — ver custoMktGlobalFormClasse()/leadsOportunidadesGlobalFormClasse()/
        // funilVendasGlobalFormClasse() (global+Evento+Digital numa query) e
        // custoMktPorDimensoes()/leadsPorDimensoes()/funilPorDimensoes() (Campanha+Conjunto+
        // Anúncio(+Origem) numa query, GROUP BY multi-coluna). Chegou a medir ~52-55s numa
        // chamada fria antes da Fase 1 (índices); mantido o set_time_limit(180) por segurança.
        set_time_limit(180);
        try {
            $cacheKey = $this->cacheKeyFromRequest('marketing_dados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();
            $bidsIntList = $this->engajadosBidsList();

            // Fase 1B (item 3): global + Evento + Digital numa única query por tabela-fonte
            // (custoMkt/leadV2/dealV2) em vez de 9 chamadas separadas (3 funções x 3 variantes).
            $custoGFC = $this->custoMktGlobalFormClasse($f);
            $leadsGFC = $this->leadsOportunidadesGlobalFormClasse($f, $bidsIntList);
            $funilGFC = $this->funilVendasGlobalFormClasse($f);

            $custo = $custoGFC['global'];
            $leads = $leadsGFC['global'];
            $funil = $funilGFC['global'];

            $kpis = $this->montarKpisMarketing($leads, $funil, $custo['custo_mkt']);

            // Fase 1B (item 3, parte B): Campanha + Conjunto + Anúncio (+ Origem no caso do
            // custoMkt) numa única query por tabela-fonte, em vez de 9+1 chamadas separadas
            // (3 dimensões x 3 funções, mais a de investimento_por_origem à parte).
            $custoPorDim = $this->custoMktPorDimensoes($f);
            $leadsPorDim = $this->leadsPorDimensoes($f, $bidsIntList);
            $funilPorDim = $this->funilPorDimensoes($f);

            $tabelas = [
                'campanha'    => $this->montarTabelaDimensao($custoPorDim['campanha'], $leadsPorDim['campanha'], $funilPorDim['campanha'], '(sem campanha)'),
                'conjunto'    => $this->montarTabelaDimensao($custoPorDim['conjunto'], $leadsPorDim['conjunto'], $funilPorDim['conjunto'], '(sem conjunto)'),
                'anuncio'     => $this->montarTabelaDimensao($custoPorDim['anuncio'], $leadsPorDim['anuncio'], $funilPorDim['anuncio'], '(sem anúncio)'),
                'form_classe' => $this->calcularTabelaFormClasse($custoGFC, $leadsGFC, $funilGFC, $this->classeFormSelecionada($f)),
            ];
            $investimentoPorOrigem = $custoPorDim['origem'];

            $payload = [
                'meta'                    => $f,
                'kpis'                    => $kpis,
                'funil'                   => $this->montarFunilMarketing($kpis),
                'investimento_por_origem' => $investimentoPorOrigem,
                'tabelas'                 => $tabelas,
            ];
            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * GET /marketing/eventosDados — 3ª view: só o universo "Evento" (prefixo
     * OTR/INA/OTW/WEB via condFormClasse()/condFormClasseCustoMkt(), SEMPRE
     * ativo aqui — diferente do form_classe da 1ª view, não é um GET pra
     * alternar). KPIs + card financeiro reaproveitam a fatia 'Evento' que
     * *GlobalFormClasse() já calcula (nenhuma fórmula nova). A tabela por
     * formulário é a única coisa nova: agrupa por formPreenchido/
     * identificador_custoMkt em vez de campanha/conjunto/anúncio, mas
     * alimenta montarTabelaDimensao()/montarLinhaTabela() de sempre — os
     * mesmos 17 valores por linha, incluindo a linha Total.
     *
     * Sem campos novos pros gráficos: representatividade (%), ranking de
     * investimento e ranking por métrica do toggle são todos derivados no
     * JS a partir de 'tabela_form' (a própria linha Total já dá os
     * denominadores) — mesma filosofia de "troca de métrica sem novo fetch"
     * de paginas/dentistas/index.php.
     */
    public function eventosDados()
    {
        set_time_limit(180);
        try {
            $cacheKey = $this->cacheKeyFromRequest('marketing_eventos');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();
            $bidsIntList = $this->engajadosBidsList();

            $custoGFC = $this->custoMktGlobalFormClasse($f);
            $leadsGFC = $this->leadsOportunidadesGlobalFormClasse($f, $bidsIntList);
            $funilGFC = $this->funilVendasGlobalFormClasse($f);

            $kpis = $this->montarKpisMarketing($leadsGFC['Evento'], $funilGFC['Evento'], $custoGFC['Evento']['custo_mkt']);

            $custoPorForm = $this->custoMktPorFormulario($f);
            $leadsPorForm = $this->leadsPorFormulario($f, $bidsIntList);
            $funilPorForm = $this->funilPorFormulario($f);

            $payload = [
                'meta'        => $f,
                'kpis'        => $kpis,
                'tabela_form' => $this->montarTabelaDimensao($custoPorForm, $leadsPorForm, $funilPorForm, '(sem formulário)'),
            ];
            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * GET /marketing/desempenhoDados — 5ª aba: reaproveita os mesmos 7 KPIs +
     * card financeiro de dados() (zero fórmula nova — mesmas
     * *GlobalFormClasse()/montarKpisMarketing() de sempre) e acrescenta 4
     * blocos novos, todos sobre colunas de leadV2 não documentadas no
     * CLAUDE.md antes deste dashboard (profissao_leadV2,
     * formacaoAcademica_leadV2, tempoExp_leadV2, motivoDescarte_leadV2 —
     * confirmadas via SHOW COLUMNS em produção):
     *   - rankings: top motivos de descarte / profissões / tempo de experiência
     *   - formacao: distribuição completa (não Top-N) por nível de formação
     *   - serie: séries mensal/semanal/diária das 9 métricas do seletor
     */
    public function desempenhoDados()
    {
        set_time_limit(180);
        try {
            $cacheKey = $this->cacheKeyFromRequest('marketing_desempenho');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();
            $bidsIntList = $this->engajadosBidsList();

            $custo = $this->custoMktGlobalFormClasse($f)['global'];
            $leads = $this->leadsOportunidadesGlobalFormClasse($f, $bidsIntList)['global'];
            $funil = $this->funilVendasGlobalFormClasse($f)['global'];
            $kpis  = $this->montarKpisMarketing($leads, $funil, $custo['custo_mkt']);

            $payload = [
                'meta'     => $f,
                'kpis'     => $kpis,
                'rankings' => [
                    'motivos'    => $this->topMotivosDescarte($f, 9),
                    'profissoes' => $this->topProfissoes($f, 9),
                    'tempo_exp'  => $this->topTempoExperiencia($f, 10),
                ],
                'formacao' => $this->distribuicaoFormacao($f),
                'serie'    => $this->seriePerformance($f),
            ];
            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * GET /marketing/classificacaoDados — 7ª aba: os mesmos KPIs de dados()
     * (zero fórmula nova — mesmas *GlobalFormClasse()/montarKpisMarketing()
     * de sempre), sem tabelas por dimensão — a série Digital×Evento fica em
     * classificacaoGrafico(), endpoint separado (ver doc lá).
     */
    public function classificacaoDados()
    {
        set_time_limit(180);
        try {
            $cacheKey = $this->cacheKeyFromRequest('marketing_classificacao');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();
            $bidsIntList = $this->engajadosBidsList();

            $custo = $this->custoMktGlobalFormClasse($f)['global'];
            $leads = $this->leadsOportunidadesGlobalFormClasse($f, $bidsIntList)['global'];
            $funil = $this->funilVendasGlobalFormClasse($f)['global'];
            $kpis  = $this->montarKpisMarketing($leads, $funil, $custo['custo_mkt']);

            $payload = [
                'meta' => $f,
                'kpis' => $kpis,
            ];
            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * GET /marketing/classificacaoGrafico — série temporal Digital×Evento
     * (classificação por FORM, condFormClasse()) das 3 métricas do funil de
     * vendas (Combos/Consultas Vendidas/Cadeiras Vendidas), na granularidade
     * pedida em `modo` (anoMes|dia_mes|semana_ano — whitelist, default
     * anoMes). Endpoint dedicado e separado de classificacaoDados() de
     * propósito: trocar o toggle de granularidade não deve reconsultar os
     * KPIs, só refazer este fetch — mesmo padrão de
     * helenacastro_controle.php::grafico().
     */
    public function classificacaoGrafico()
    {
        set_time_limit(180);
        try {
            $modo = $this->g('modo');
            if (!in_array($modo, ['anoMes', 'dia_mes', 'semana_ano'], true)) {
                $modo = 'anoMes';
            }

            $cacheKey = $this->cacheKeyFromRequest('marketing_classificacao_grafico');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();
            $payload = $this->serieClassificacaoCampanha($f, $modo);
            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * GET /marketing/descartesDados — motivos de descarte dos Comerciais
     * 1, 2 e 3, mais as mesmas métricas em séries mensal/semanal/diária.
     *
     * Regras portadas do Power BI:
     *  - Com1: etapa = "Lead Descartado" e tipoLead <> OTR;
     *  - Com2: motivoDescarteComercial2 preenchido;
     *  - Com3: motivoDescarte preenchido e pipe C1:;
     *  - %Com1 = Com1 / Qualificados;
     *  - %Com2 = Com2 / Consultas Vendidas;
     *  - %Com3 = Com3 / Com2.
     *
     * Os três descartes são ancorados em dataUltForm. Só o denominador de
     * %Com2 usa dataVenda_dealV2, reproduzindo o USERELATIONSHIP da medida
     * ConsultasVendidas2 no modelo de origem. Os denominadores nunca recebem
     * filtro de motivo — equivalente ao ALL() das medidas DAX.
     */
    public function descartesDados()
    {
        set_time_limit(180);
        try {
            $cacheKey = $this->cacheKeyFromRequest('marketing_descartes');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();

            $lead = $this->descartesLeadPorDia($f);
            $deal = $this->descartesDealPorDia($f);
            $vendas = $this->consultasVendidasPorDiaDescartes($f);

            $porDia = [];
            $novoDia = static fn() => [
                'total_leads'       => 0,
                'qualificados'      => 0,
                'descarte_com1'     => 0,
                'descarte_com2'     => 0,
                'descarte_com3'     => 0,
                'consultas_vendidas'=> 0,
            ];

            foreach ($lead['dias'] as $dia => $valores) {
                $porDia[$dia] = $porDia[$dia] ?? $novoDia();
                $porDia[$dia]['total_leads']   += $valores['total_leads'];
                $porDia[$dia]['qualificados']  += $valores['qualificados'];
                $porDia[$dia]['descarte_com1'] += $valores['descarte_com1'];
            }
            foreach ($deal['dias'] as $dia => $valores) {
                $porDia[$dia] = $porDia[$dia] ?? $novoDia();
                $porDia[$dia]['descarte_com2'] += $valores['descarte_com2'];
                $porDia[$dia]['descarte_com3'] += $valores['descarte_com3'];
            }
            foreach ($vendas['dias'] as $dia => $quantidade) {
                $porDia[$dia] = $porDia[$dia] ?? $novoDia();
                $porDia[$dia]['consultas_vendidas'] += $quantidade;
            }
            ksort($porDia);

            $funil = $this->funilVendasGlobalFormClasse($f)['global'];
            $leadsKpi = [
                'total_leads'  => $lead['totais']['total_leads'],
                'qualificados' => $lead['totais']['qualificados'],
                // A view não exibe Oportunidades, mas montarKpisMarketing()
                // mantém o mesmo contrato usado pelas demais abas.
                'oportunidades'=> 0,
            ];
            $kpis = $this->montarKpisMarketing($leadsKpi, $funil, 0.0);
            $kpis['faturamento_cadeiras'] = $vendas['faturamento_cadeiras'];

            $payload = [
                'meta'    => $f,
                'kpis'    => $kpis,
                'totais'  => [
                    'com1' => $lead['totais']['descarte_com1'],
                    'com2' => $deal['totais']['descarte_com2'],
                    'com3' => $deal['totais']['descarte_com3'],
                ],
                'motivos' => [
                    'com1' => $this->ordenarMotivosDescarte($lead['motivos']),
                    'com2' => $this->ordenarMotivosDescarte($deal['motivos_com2']),
                    'com3' => $this->ordenarMotivosDescarte($deal['motivos_com3']),
                ],
                'serie' => $this->montarSeriesDescartes($porDia),
            ];

            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /marketing/campanhas — dropdown de busca (mesmo padrão de executive_controle.php::campanhas()). */
    public function campanhas()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('marketing_campanhas');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DROPDOWN);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $sql = "
                SELECT DISTINCT nome FROM (
                    SELECT nomeCampanha_custoMkt AS nome FROM custoMkt
                        WHERE nomeCampanha_custoMkt IS NOT NULL AND nomeCampanha_custoMkt != ''
                    UNION
                    SELECT utmCampaign_dealV2 AS nome FROM dealV2
                        WHERE utmCampaign_dealV2 IS NOT NULL AND utmCampaign_dealV2 != ''
                    UNION
                    SELECT utmCampaign_leadV2 AS nome FROM leadV2
                        WHERE utmCampaign_leadV2 IS NOT NULL AND utmCampaign_leadV2 != ''
                ) t ORDER BY nome
            ";
            $payload = array_column($this->queryAll($sql), 'nome');
            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /marketing/forms — dropdown de busca (valores brutos de formPreenchido). */
    public function forms()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('marketing_forms');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DROPDOWN);
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
            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /marketing/conjuntosAnuncio — dropdown de busca (Conjunto de Anúncios, via UTM content). */
    public function conjuntosAnuncio()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('marketing_conjuntosAnuncio');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DROPDOWN);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $sql = "
                SELECT DISTINCT nome FROM (
                    SELECT nomeConjunto_custoMkt AS nome FROM custoMkt
                        WHERE nomeConjunto_custoMkt IS NOT NULL AND nomeConjunto_custoMkt != ''
                    UNION
                    SELECT utmContent_dealV2 AS nome FROM dealV2
                        WHERE utmContent_dealV2 IS NOT NULL AND utmContent_dealV2 != ''
                    UNION
                    SELECT utmContent_leadV2 AS nome FROM leadV2
                        WHERE utmContent_leadV2 IS NOT NULL AND utmContent_leadV2 != ''
                ) t ORDER BY nome
            ";
            $payload = array_column($this->queryAll($sql), 'nome');
            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /marketing/anuncios — dropdown de busca (Anúncio, via UTM term). */
    public function anuncios()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('marketing_anuncios');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DROPDOWN);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $sql = "
                SELECT DISTINCT nome FROM (
                    SELECT nomeAnuncio_custoMkt AS nome FROM custoMkt
                        WHERE nomeAnuncio_custoMkt IS NOT NULL AND nomeAnuncio_custoMkt != ''
                    UNION
                    SELECT utmTerm_dealV2 AS nome FROM dealV2
                        WHERE utmTerm_dealV2 IS NOT NULL AND utmTerm_dealV2 != ''
                    UNION
                    SELECT utmTerm_leadV2 AS nome FROM leadV2
                        WHERE utmTerm_leadV2 IS NOT NULL AND utmTerm_leadV2 != ''
                ) t ORDER BY nome
            ";
            $payload = array_column($this->queryAll($sql), 'nome');
            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
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
    // HELPERS: CACHE EM ARQUIVO (.mrphp/cache) — TTL curto, sem tocar no banco
    // Mesmo padrão de executive_controle.php:502-546 (cópia própria do
    // controller, sem camada compartilhada — convenção do projeto).
    // ===================================================================

    /** Segundos de validade do cache dos dropdowns (campanhas/forms/conjuntosAnuncio/anuncios). */
    private const CACHE_TTL_DROPDOWN = 600;

    /** Segundos de validade do cache da resposta agregada de dados().
     *  Cache-miss custava ~52-55s antes da Fase 1 (índices) — melhorado desde então
     *  (índices + WHERE-pruning + consolidação do fan-out de 19 para 6 queries em
     *  custoMktGlobalFormClasse()/custoMktPorDimensoes() e equivalentes). TTL de
     *  10min mantido mesmo assim, para reduzir a frequência com que esse custo é pago. */
    private const CACHE_TTL_DADOS = 600;

    private function cacheDir(): string
    {
        return dirname(__DIR__) . '/.mrphp/cache';
    }

    /** Lê um valor do cache de arquivo; retorna null em caso de miss ou expiração. */
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

    /** Grava um valor no cache de arquivo (JSON), sobrescrevendo o anterior. */
    private function cacheSet(string $key, $data): void
    {
        $file = $this->cacheDir() . '/' . $key . '.json';
        @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        $this->cacheGc();
    }

    /** Chave de cache determinística a partir do nome do endpoint + querystring completa. */
    private function cacheKeyFromRequest(string $endpoint): string
    {
        return $endpoint . '_' . md5($_SERVER['QUERY_STRING'] ?? '');
    }

    /** Lê do cache; em miss, executa $fn(), grava o resultado e retorna.
     *  Pensado para computações caras e INDEPENDENTES do filtro do usuário
     *  (ex.: joins que não variam com o período pedido) — usar chave fixa e
     *  TTL bem mais longo que o dos endpoints de dados (ver Fase 1C.2). */
    private function cacheRemember(string $key, int $ttlSeconds, callable $fn)
    {
        $cached = $this->cacheGet($key, $ttlSeconds);
        if ($cached !== null) {
            return $cached;
        }
        $value = $fn();
        $this->cacheSet($key, $value);
        return $value;
    }

    /** Remove arquivos de cache parados há muito tempo (2h — bem acima de
     *  qualquer TTL em uso hoje). Nada limpa isso automaticamente hoje (nem
     *  deploy, nem cron), então os arquivos acumulariam pra sempre sem isso.
     *  Chamada com baixa probabilidade a cada escrita para não pesar no
     *  caminho comum (mesmo padrão do gc probabilístico de sessão do PHP). */
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
    // HELPERS: FILTROS
    // ===================================================================

    private function lerFiltros(): array
    {
        return [
            'year'        => $this->g('year'),
            'months'      => $this->g('months'),
            'data_inicio' => $this->g('data_inicio'),
            'data_fim'    => $this->g('data_fim'),
            'campanha'    => $this->g('campanha'),
            'form'        => $this->g('form'),
            'conjunto'    => $this->g('conjunto'),
            'anuncio'     => $this->g('anuncio'),
            // Cross-filter da tabela de Classificação: 'Evento' | 'Digital'.
            // Diferente de 'form' (que filtra o NOME do form preenchido) —
            // aqui é a classe derivada do prefixo. Whitelist em
            // classeFormSelecionada(): o valor do request nunca entra no SQL.
            'form_classe' => $this->g('form_classe'),
        ];
    }

    /**
     * Filtro ano/mês reescrito como intervalos de data literais (`$col >= ? AND $col <= ?`)
     * em vez de `YEAR($col)`/`MONTH($col)` — a versão anterior envolvia a coluna em função,
     * o que anula qualquer índice nela mesmo estando dentro de um `WHERE` (ver Fase 1B do
     * plano de performance). Sem ano selecionado, mantém `MONTH($col) IN (...)` (não dá pra
     * expressar "mês X em qualquer ano" como um intervalo contínuo sargável).
     */
    private function buildDateFilter(string $col, $year, ?string $months): array
    {
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

    /** Agrupa uma lista ordenada de meses (1-12) em pares [inicio,fim] de sequências contíguas. */
    private function agruparMesesContiguos(array $monthIntsOrdenados): array
    {
        $runs = [];
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

    /** Filtro de período: range (data_inicio/data_fim, aceita half-open) OU year/months. */
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
            return ['sql' => $conds ? implode(' AND ', $conds) : '1=1', 'params' => $params];
        }
        return $this->buildDateFilter($col, $year, $months);
    }

    // ===================================================================
    // HELPERS: PERÍODO DA 2ª VIEW (comparativo ano × ano-1)
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

    /**
     * Combina os 4 filtros de dimensão (campanha/form/conjunto/anúncio) para uma
     * tabela específica, cada um sobre a coluna correspondente daquela tabela.
     * Usado tanto na agregação global (dados()) quanto nas tabelas de breakdown —
     * quando o próprio filtro da dimensão em GROUP BY está ativo, a tabela
     * simplesmente reduz a 1 linha (comportamento padrão de cross-filter, sem
     * necessidade de excluir o filtro da própria dimensão).
     */
    private function filtrosDimensao(array $f, string $colCampanha, string $colForm, string $colConjunto, string $colAnuncio): array
    {
        $c1 = $this->buildInFilter($colCampanha, $f['campanha']);
        $c2 = $this->buildInFilter($colForm, $f['form']);
        $c3 = $this->buildInFilter($colConjunto, $f['conjunto']);
        $c4 = $this->buildInFilter($colAnuncio, $f['anuncio']);
        return [
            'sql'    => "{$c1['sql']} {$c2['sql']} {$c3['sql']} {$c4['sql']}",
            'params' => array_merge($c1['params'], $c2['params'], $c3['params'], $c4['params']),
        ];
    }

    private function filtrosDeal(array $f): array
    {
        return $this->filtrosDimensao($f, 'd.utmCampaign_dealV2', 'd.formPreenchido_dealV2', 'd.utmContent_dealV2', 'd.utmTerm_dealV2');
    }

    private function filtrosLead(array $f): array
    {
        return $this->filtrosDimensao($f, 'l.utmCampaign_leadV2', 'l.formPreenchido_leadV2', 'l.utmContent_leadV2', 'l.utmTerm_leadV2');
    }

    /** Filtro de Form em custoMkt usa identificador_custoMkt, não um "form" literal — mesma convenção de executive_controle.php. */
    private function filtrosCusto(array $f): array
    {
        return $this->filtrosDimensao($f, 'cm.nomeCampanha_custoMkt', 'cm.identificador_custoMkt', 'cm.nomeConjunto_custoMkt', 'cm.nomeAnuncio_custoMkt');
    }

    /**
     * Classificação Evento vs. Digital por prefixo do NOME DA CAMPANHA —
     * eixo da 2ª view (/marketing/comparativo). A view 1 classifica por FORM
     * (condFormClasse, logo abaixo); são réguas diferentes de propósito, então
     * o "Evento" de uma view não bate com o da outra — está no dicionário.
     *
     * Duas classes só, e MECE: tudo que não tem prefixo de evento cai em
     * Digital, inclusive ORG/IND/'Negado Cliente'/'F&F'/'MGM'/NULL/''. É o que
     * garante Evento + Digital = Total e a linha Total da tabela fechando com
     * os KPIs.
     *
     * Difere DE PROPÓSITO da condClasseCampanha() do executive_controle.php,
     * que tem 4 classes (Evento/Digital/Orgânico/Indicação) e deixa campanha
     * vazia fora de todas. Cópia por controller é a convenção do projeto.
     */
    private function condClasseCampanha(string $col, string $classe): string
    {
        return $classe === 'Evento'
            ? "($col IS NOT NULL AND $col != '' AND LEFT(UPPER($col), 3) IN ('OTR', 'INA', 'OTW', 'WEB'))"
            : "($col IS NULL OR $col = '' OR LEFT(UPPER($col), 3) NOT IN ('OTR', 'INA', 'OTW', 'WEB'))";
    }

    /**
     * Condição de classificação para uma fonte ('lead'|'deal'|'custo') no eixo
     * pedido ('form' = view 1, 'campanha' = view 2). Existe para que as três
     * funções *GlobalFormClasse() sirvam as duas views sem duplicar ~120
     * linhas de SQL; o default 'form' preserva dados() intacto.
     */
    private function condClasseEixo(string $eixo, string $fonte, string $classe): string
    {
        if ($eixo === 'campanha') {
            $col = ['lead' => 'l.utmCampaign_leadV2', 'deal' => 'd.utmCampaign_dealV2', 'custo' => 'cm.nomeCampanha_custoMkt'][$fonte];
            return $this->condClasseCampanha($col, $classe);
        }
        if ($fonte === 'custo') {
            return $this->condFormClasseCustoMkt($classe);
        }
        return $this->condFormClasse($fonte === 'lead' ? 'l.formPreenchido_leadV2' : 'd.formPreenchido_dealV2', $classe);
    }

    /** Classificação Evento vs. Digital por prefixo do form (dealV2/leadV2) — copiado de executive_controle.php::condFormClasse(). */
    private function condFormClasse(string $formCol, string $classe): string
    {
        return $classe === 'Evento'
            ? "($formCol IS NOT NULL AND $formCol != '' AND LEFT(UPPER($formCol), 3) IN ('OTR', 'INA', 'OTW', 'WEB'))"
            : "($formCol IS NULL OR $formCol = '' OR LEFT(UPPER($formCol), 3) NOT IN ('OTR', 'INA', 'OTW', 'WEB'))";
    }

    /** Mesma classificação, para custoMkt (usa identificador_custoMkt, com fallback pro nome da campanha) — copiado de executive_controle.php::condFormClasseCustoMkt(). */
    private function condFormClasseCustoMkt(string $classe): string
    {
        $expr = "(
            LEFT(UPPER(COALESCE(cm.identificador_custoMkt, '')), 3) IN ('OTR', 'INA', 'OTW', 'WEB')
            OR (
                (cm.identificador_custoMkt IS NULL OR cm.identificador_custoMkt = '')
                AND LEFT(UPPER(cm.nomeCampanha_custoMkt), 3) = 'OTR'
            )
        )";
        return $classe === 'Evento' ? $expr : "NOT $expr";
    }

    /**
     * Classe de form selecionada no filtro, ou null = sem restrição.
     *
     * O valor cru do request NUNCA é interpolado no SQL: ele apenas escolhe
     * qual das duas constantes de condFormClasse()/condFormClasseCustoMkt()
     * usar. Valor fora da whitelist = filtro inativo (falha aberta, sem 500),
     * mesmo contrato de buildInFilter() com CSV vazio.
     *
     * As duas classes juntas também devolvem null: condFormClasse() é MECE
     * sobre formPreenchido, então Evento+Digital == global e filtrar seria só
     * custo de CPU.
     */
    private function classeFormSelecionada(array $f): ?string
    {
        $aceitas = ['evento' => 'Evento', 'digital' => 'Digital'];
        $escolhidas = [];
        foreach (explode(',', (string)($f['form_classe'] ?? '')) as $v) {
            $k = strtolower(trim($v));
            if (isset($aceitas[$k])) {
                $escolhidas[$aceitas[$k]] = true;
            }
        }
        return count($escolhidas) === 1 ? (string)array_key_first($escolhidas) : null;
    }

    /**
     * Fragmento " AND (...)" pronto para concatenar no fim de um WHERE, ou ''
     * quando inativo (mesmo contrato de buildInFilter()).
     *
     * Não consome nenhum placeholder — condFormClasse() devolve SQL com
     * literais constantes. Por isso pode entrar em qualquer posição de
     * qualquer WHERE sem alterar um único array_merge de params, inclusive
     * nas queries do funil, onde os params dos SELECT vêm antes dos do WHERE.
     * Se algum dia esse filtro virar parametrizado, os params têm de entrar no
     * FIM do array_merge, depois de filtros['params'].
     */
    private function filtroFormClasseLead(array $f): string
    {
        $c = $this->classeFormSelecionada($f);
        return $c === null ? '' : ' AND (' . $this->condFormClasse('l.formPreenchido_leadV2', $c) . ')';
    }

    private function filtroFormClasseDeal(array $f): string
    {
        $c = $this->classeFormSelecionada($f);
        return $c === null ? '' : ' AND (' . $this->condFormClasse('d.formPreenchido_dealV2', $c) . ')';
    }

    /** custoMkt classifica por identificador_custoMkt (com fallback pro nome da
     *  campanha), não por formPreenchido — coluna diferente, mesma intenção.
     *  O parêntese extra é necessário: condFormClasseCustoMkt('Digital')
     *  devolve "NOT (...)". */
    private function filtroFormClasseCusto(array $f): string
    {
        $c = $this->classeFormSelecionada($f);
        return $c === null ? '' : ' AND (' . $this->condFormClasseCustoMkt($c) . ')';
    }

    // ===================================================================
    // CÁLCULOS: CUSTO DE MARKETING (custoMkt → USD, sem custo físico)
    // ===================================================================

    /**
     * Investimento de marketing em USD, global + Evento + Digital numa ÚNICA
     * query (Fase 1B, item 3 — substitui a antiga custoMkt(), que fazia 3
     * chamadas separadas: uma sem filtro de classe e uma por classe via
     * $extraWhere). Mesma condição de classificação de condFormClasseCustoMkt(),
     * só que ANDada dentro de cada CASE em vez de aplicada no WHERE externo
     * (resultado numérico idêntico: uma linha que falha a condição cai no
     * ELSE 0 de qualquer forma).
     *
     * Aliases 'custo_dollar'/'custo_real' (não 'dollar'/'real' — REAL é
     * palavra reservada do MySQL/MariaDB; usada sem crase como alias de
     * coluna, quebra o prepare() com erro de sintaxe).
     */
    private function custoMktGlobalFormClasse(array $f, string $eixoClasse = 'form'): array
    {
        $date    = $this->buildPeriodFilter('cm.data_custoMkt', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $filtros = $this->filtrosCusto($f);
        $classe  = $this->filtroFormClasseCusto($f);

        $condEvento  = $this->condClasseEixo($eixoClasse, 'custo', 'Evento');
        $condDigital = $this->condClasseEixo($eixoClasse, 'custo', 'Digital');

        $variante = function (string $sufixo, ?string $cond) {
            $prefix = $cond !== null ? "($cond) AND " : '';
            return "
                SUM(CASE
                    WHEN {$prefix}YEAR(cm.data_custoMkt) = 2025 AND cm.origem_custoMkt = 'meta' THEN cm.valor_custoMkt
                    WHEN {$prefix}YEAR(cm.data_custoMkt) = 2026 AND cm.conta_custoMkt IN ('Dollar 1', 'Dollar 2') THEN cm.valor_custoMkt
                    ELSE 0
                END) AS custo_dollar_$sufixo,
                SUM(CASE
                    WHEN {$prefix}YEAR(cm.data_custoMkt) = 2025 AND cm.origem_custoMkt = 'google' THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio, 0)
                    WHEN {$prefix}YEAR(cm.data_custoMkt) = 2026 AND cm.conta_custoMkt <> 'Dollar 1' AND cm.conta_custoMkt <> 'Dollar 2' THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio, 0)
                    ELSE 0
                END) AS custo_real_$sufixo,
                SUM(CASE WHEN {$prefix}1=1 THEN cm.clicks_custoMkt ELSE 0 END) AS cliques_$sufixo";
        };

        $sql = "
            SELECT
                {$variante('global', null)},
                {$variante('evento', $condEvento)},
                {$variante('digital', $condDigital)}
            FROM custoMkt cm
            LEFT JOIN (SELECT data_cambio, MAX(valor_cambio) AS valor_cambio FROM cambio GROUP BY data_cambio) ca
                ON ca.data_cambio = cm.data_custoMkt
            WHERE {$date['sql']} {$filtros['sql']}{$classe}
        ";
        $params = array_merge($date['params'], $filtros['params']);
        $row    = $this->queryOne($sql, $params);

        $mk = fn(string $sufixo) => [
            'custo_mkt' => (float)($row["custo_dollar_$sufixo"] ?? 0) + (float)($row["custo_real_$sufixo"] ?? 0),
            'cliques'   => (int)($row["cliques_$sufixo"] ?? 0),
        ];
        return ['global' => $mk('global'), 'Evento' => $mk('evento'), 'Digital' => $mk('digital')];
    }

    /**
     * Fase 1B (item 3, parte B): custo por Campanha + Conjunto + Anúncio + Origem
     * numa ÚNICA query, agrupando pelas 4 colunas de uma vez (mesmo truque de
     * calcularVistos() do executive_controle.php) em vez de 4 chamadas separadas
     * de custoMkt() com um $colDim cada. Re-soma em PHP em 4 mapas independentes
     * — cada mapa só acumula linhas onde AQUELA dimensão específica é não-nula/
     * não-vazia, replicando o `$dimWhere` que cada chamada antiga aplicava no
     * WHERE (uma linha com campanha nula nunca aparecia no breakdown por
     * campanha, mas podia aparecer no de conjunto/anúncio/origem).
     */
    private function custoMktPorDimensoes(array $f): array
    {
        $date    = $this->buildPeriodFilter('cm.data_custoMkt', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $filtros = $this->filtrosCusto($f);
        $classe  = $this->filtroFormClasseCusto($f);

        $sql = "
            SELECT
                cm.nomeCampanha_custoMkt AS campanha,
                cm.nomeConjunto_custoMkt AS conjunto,
                cm.nomeAnuncio_custoMkt AS anuncio,
                cm.origem_custoMkt AS origem,
                SUM(CASE
                    WHEN YEAR(cm.data_custoMkt) = 2025 AND cm.origem_custoMkt = 'meta' THEN cm.valor_custoMkt
                    WHEN YEAR(cm.data_custoMkt) = 2026 AND cm.conta_custoMkt IN ('Dollar 1', 'Dollar 2') THEN cm.valor_custoMkt
                    ELSE 0
                END) AS custo_dollar,
                SUM(CASE
                    WHEN YEAR(cm.data_custoMkt) = 2025 AND cm.origem_custoMkt = 'google' THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio, 0)
                    WHEN YEAR(cm.data_custoMkt) = 2026 AND cm.conta_custoMkt <> 'Dollar 1' AND cm.conta_custoMkt <> 'Dollar 2' THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio, 0)
                    ELSE 0
                END) AS custo_real,
                SUM(cm.clicks_custoMkt) AS cliques
            FROM custoMkt cm
            LEFT JOIN (SELECT data_cambio, MAX(valor_cambio) AS valor_cambio FROM cambio GROUP BY data_cambio) ca
                ON ca.data_cambio = cm.data_custoMkt
            WHERE {$date['sql']} {$filtros['sql']}{$classe}
            GROUP BY cm.nomeCampanha_custoMkt, cm.nomeConjunto_custoMkt, cm.nomeAnuncio_custoMkt, cm.origem_custoMkt
        ";
        $params = array_merge($date['params'], $filtros['params']);

        $porCampanha = $porConjunto = $porAnuncio = $porOrigem = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $val = [
                'custo_mkt' => (float)$r['custo_dollar'] + (float)$r['custo_real'],
                'cliques'   => (int)($r['cliques'] ?? 0),
            ];
            $this->acumularCusto($porCampanha, $this->chaveDim($r['campanha']), $val);
            $this->acumularCusto($porConjunto, $this->chaveDim($r['conjunto']), $val);
            $this->acumularCusto($porAnuncio,  $this->chaveDim($r['anuncio']),  $val);
            // origem continua descartando vazio: não há linha sem origem (medido
            // em 2026) e este mapa alimenta o rótulo de % do resumo financeiro,
            // não uma tabela com linha de Total.
            if ($r['origem'] !== null && $r['origem'] !== '') $this->acumularCusto($porOrigem, $r['origem'], $val);
        }
        return ['campanha' => $porCampanha, 'conjunto' => $porConjunto, 'anuncio' => $porAnuncio, 'origem' => $porOrigem];
    }

    /**
     * Chave de agrupamento de uma dimensão: NULL e '' viram a mesma chave vazia.
     *
     * Antes as três funções *PorDimensoes() DESCARTAVAM a linha quando a
     * dimensão era vazia, e como a linha Total é somada sobre o mapa inteiro,
     * o Total dessas tabelas ficava menor que os KPIs sem nenhum aviso — em
     * 2026 eram 143 leads sem utmCampaign, 488 sem utmContent e 977 sem
     * utmTerm (e ~$1.287 de custoMkt sem nomeAnuncio). Agora o grupo vazio
     * existe e montarTabelaDimensao() lhe dá um rótulo "(sem X)", então o
     * Total fecha com os KPIs e nada fica escondido.
     *
     * Sem trim(): não existe valor com espaço nas pontas em leadV2/custoMkt
     * (medido), e normalizar espaço mudaria agrupamento sem defeito
     * correspondente.
     */
    private function chaveDim(?string $valor): string
    {
        return $valor === null ? '' : $valor;
    }

    private function acumularCusto(array &$mapa, string $chave, array $val): void
    {
        if (!isset($mapa[$chave])) {
            $mapa[$chave] = ['custo_mkt' => 0.0, 'cliques' => 0];
        }
        $mapa[$chave]['custo_mkt'] += $val['custo_mkt'];
        $mapa[$chave]['cliques']   += $val['cliques'];
    }

    /**
     * Custo de marketing por FORMULÁRIO (3ª view/Eventos), só universo Evento
     * (condFormClasseCustoMkt('Evento') sempre ativo, não é um GET). Mesmo
     * corpo de custoMktPorDimensoes(), trocando a coluna de agrupamento:
     * custoMkt não tem formPreenchido, então a chave é
     * identificador_custoMkt com fallback pro nome da campanha — mesma
     * convenção de condFormClasseCustoMkt() (docblock acima), agora também
     * como GROUP BY, não só como teste de classificação.
     */
    private function custoMktPorFormulario(array $f): array
    {
        $date    = $this->buildPeriodFilter('cm.data_custoMkt', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $filtros = $this->filtrosCusto($f);
        $classe  = $this->condFormClasseCustoMkt('Evento');
        $chave   = "COALESCE(NULLIF(cm.identificador_custoMkt, ''), cm.nomeCampanha_custoMkt)";

        $sql = "
            SELECT
                $chave AS form,
                SUM(CASE
                    WHEN YEAR(cm.data_custoMkt) = 2025 AND cm.origem_custoMkt = 'meta' THEN cm.valor_custoMkt
                    WHEN YEAR(cm.data_custoMkt) = 2026 AND cm.conta_custoMkt IN ('Dollar 1', 'Dollar 2') THEN cm.valor_custoMkt
                    ELSE 0
                END) AS custo_dollar,
                SUM(CASE
                    WHEN YEAR(cm.data_custoMkt) = 2025 AND cm.origem_custoMkt = 'google' THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio, 0)
                    WHEN YEAR(cm.data_custoMkt) = 2026 AND cm.conta_custoMkt <> 'Dollar 1' AND cm.conta_custoMkt <> 'Dollar 2' THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio, 0)
                    ELSE 0
                END) AS custo_real,
                SUM(cm.clicks_custoMkt) AS cliques
            FROM custoMkt cm
            LEFT JOIN (SELECT data_cambio, MAX(valor_cambio) AS valor_cambio FROM cambio GROUP BY data_cambio) ca
                ON ca.data_cambio = cm.data_custoMkt
            WHERE {$date['sql']} {$filtros['sql']} AND {$classe}
            GROUP BY $chave
        ";
        $params = array_merge($date['params'], $filtros['params']);

        $porForm = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $val = [
                'custo_mkt' => (float)$r['custo_dollar'] + (float)$r['custo_real'],
                'cliques'   => (int)($r['cliques'] ?? 0),
            ];
            $this->acumularCusto($porForm, $this->chaveDim($r['form']), $val);
        }
        return $porForm;
    }

    // ===================================================================
    // CÁLCULOS: LEADS + OPORTUNIDADES (leadV2 + engajamento CW, global)
    // ===================================================================

    /**
     * Lista de bids do Bitrix com ao menos 1 mensagem recebida no Chatwoot —
     * mesma lógica de eventos_controle.php::calcularLeadsPorEvento(), mas
     * calculada UMA vez por request em vez de recomputada a cada chamada de
     * leadsOportunidades() (que roda 6x por dados(): global + 3 dimensões +
     * 2 form-classe). O join com msgCW (845 mil linhas, sem índice em
     * id_conversaCW) mede ~7,7s sozinho — recomputá-lo 6x era ~87% do tempo
     * total de dados() (~46s dos ~52-55s medidos). Independe de $f (não tem
     * filtro de data/dimensão), então um único cálculo serve para todas as
     * chamadas do request.
     *
     * Retorna os bids já formatados como lista de inteiros literais
     * (ex.: "123,456,789") em vez de bind params — os valores vêm do próprio
     * banco (BIGINT via CAST), nunca de entrada do usuário, então interpolar
     * como literal é seguro e evita milhares de placeholders via bind_param.
     */
    private function engajadosBidsList(): string
    {
        // Join com msgCW (~845 mil linhas) independente de qualquer filtro do
        // request — Fase 1C.2 do plano de performance: cacheado à parte, chave
        // fixa, TTL 30min, em vez de recalculado a cada combinação de filtro.
        return $this->cacheRemember('marketing_engajados_bids', 1800, function () {
            $sql = "
                SELECT DISTINCT c.idLeadBitrix_conversaCW AS bid
                FROM conversaCW c
                JOIN msgCW m ON m.id_conversaCW = c.id_conversaCW
                WHERE m.tipo_msgCW IN ('0','incoming')
                  AND c.idLeadBitrix_conversaCW > 0
            ";
            $bids = array_column($this->queryAll($sql), 'bid');
            return implode(',', array_map('intval', $bids));
        });
    }

    /** Fragmento SUM(CASE) de "oportunidade", com condição de classificação opcional ANDada. */
    private function oportunidadeExpr(string $bidsIntList, ?string $extraCond = null): string
    {
        if ($bidsIntList === '') {
            return '0';
        }
        $cond = "l.idBitrix_leadV2 REGEXP '^[0-9]+\$' AND CAST(l.idBitrix_leadV2 AS UNSIGNED) IN ($bidsIntList)";
        if ($extraCond !== null) {
            $cond = "($extraCond) AND $cond";
        }
        return "SUM(CASE WHEN $cond THEN 1 ELSE 0 END)";
    }

    /**
     * Fase 1B (item 3): versão consolidada de leadsOportunidades() que computa
     * global + Evento + Digital numa ÚNICA query — mesmo raciocínio de
     * custoMktGlobalFormClasse().
     */
    private function leadsOportunidadesGlobalFormClasse(array $f, string $bidsIntList, string $eixoClasse = 'form'): array
    {
        $periodo = $this->buildPeriodFilter('l.dataUltForm_leadV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $filtros = $this->filtrosLead($f);
        $classe  = $this->filtroFormClasseLead($f);

        $condEvento  = $this->condClasseEixo($eixoClasse, 'lead', 'Evento');
        $condDigital = $this->condClasseEixo($eixoClasse, 'lead', 'Digital');

        $oportGlobal  = $this->oportunidadeExpr($bidsIntList);
        $oportEvento  = $this->oportunidadeExpr($bidsIntList, $condEvento);
        $oportDigital = $this->oportunidadeExpr($bidsIntList, $condDigital);

        $sql = "
            SELECT
                COUNT(l.id_leadV2) AS total_global,
                SUM(CASE WHEN l.qualificado_leadV2 = 'Sim' THEN 1 ELSE 0 END) AS qual_global,
                $oportGlobal AS opor_global,

                SUM(CASE WHEN ($condEvento) THEN 1 ELSE 0 END) AS total_evento,
                SUM(CASE WHEN ($condEvento) AND l.qualificado_leadV2 = 'Sim' THEN 1 ELSE 0 END) AS qual_evento,
                $oportEvento AS opor_evento,

                SUM(CASE WHEN ($condDigital) THEN 1 ELSE 0 END) AS total_digital,
                SUM(CASE WHEN ($condDigital) AND l.qualificado_leadV2 = 'Sim' THEN 1 ELSE 0 END) AS qual_digital,
                $oportDigital AS opor_digital
            FROM leadV2 l
            WHERE l.dataUltForm_leadV2 IS NOT NULL AND l.dataUltForm_leadV2 > '0000-00-00'
              AND ({$periodo['sql']})
              {$filtros['sql']}{$classe}
        ";
        $params = array_merge($periodo['params'], $filtros['params']);
        $row    = $this->queryOne($sql, $params);

        $mk = fn(string $sufixo) => [
            'total_leads'   => (int)($row["total_$sufixo"] ?? 0),
            'qualificados'  => (int)($row["qual_$sufixo"] ?? 0),
            'oportunidades' => (int)($row["opor_$sufixo"] ?? 0),
        ];
        return ['global' => $mk('global'), 'Evento' => $mk('evento'), 'Digital' => $mk('digital')];
    }

    /**
     * Fase 1B (item 3, parte B): leads/qualificados/oportunidades por
     * Campanha + Conjunto + Anúncio numa ÚNICA query (mesmo raciocínio de
     * custoMktPorDimensoes()).
     */
    private function leadsPorDimensoes(array $f, string $bidsIntList): array
    {
        $periodo   = $this->buildPeriodFilter('l.dataUltForm_leadV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $filtros   = $this->filtrosLead($f);
        $classe    = $this->filtroFormClasseLead($f);
        $oportExpr = $this->oportunidadeExpr($bidsIntList);

        $sql = "
            SELECT
                l.utmCampaign_leadV2 AS campanha,
                l.utmContent_leadV2 AS conjunto,
                l.utmTerm_leadV2 AS anuncio,
                COUNT(l.id_leadV2) AS total_leads,
                SUM(CASE WHEN l.qualificado_leadV2 = 'Sim' THEN 1 ELSE 0 END) AS qualificados,
                $oportExpr AS oportunidades
            FROM leadV2 l
            WHERE l.dataUltForm_leadV2 IS NOT NULL AND l.dataUltForm_leadV2 > '0000-00-00'
              AND ({$periodo['sql']})
              {$filtros['sql']}{$classe}
            GROUP BY l.utmCampaign_leadV2, l.utmContent_leadV2, l.utmTerm_leadV2
        ";
        $params = array_merge($periodo['params'], $filtros['params']);

        $porCampanha = $porConjunto = $porAnuncio = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $val = [
                'total_leads'   => (int)$r['total_leads'],
                'qualificados'  => (int)$r['qualificados'],
                'oportunidades' => (int)$r['oportunidades'],
            ];
            $this->acumularLeads($porCampanha, $this->chaveDim($r['campanha']), $val);
            $this->acumularLeads($porConjunto, $this->chaveDim($r['conjunto']), $val);
            $this->acumularLeads($porAnuncio,  $this->chaveDim($r['anuncio']),  $val);
        }
        return ['campanha' => $porCampanha, 'conjunto' => $porConjunto, 'anuncio' => $porAnuncio];
    }

    private function acumularLeads(array &$mapa, string $chave, array $val): void
    {
        if (!isset($mapa[$chave])) {
            $mapa[$chave] = ['total_leads' => 0, 'qualificados' => 0, 'oportunidades' => 0];
        }
        $mapa[$chave]['total_leads']   += $val['total_leads'];
        $mapa[$chave]['qualificados']  += $val['qualificados'];
        $mapa[$chave]['oportunidades'] += $val['oportunidades'];
    }

    /**
     * Leads/qualificados/oportunidades por FORMULÁRIO (3ª view/Eventos), só
     * universo Evento (condFormClasse('Evento') sempre ativo). Mesmo corpo de
     * leadsPorDimensoes(), trocando o GROUP BY de utmCampaign/utmContent/
     * utmTerm para formPreenchido_leadV2.
     */
    private function leadsPorFormulario(array $f, string $bidsIntList): array
    {
        $periodo   = $this->buildPeriodFilter('l.dataUltForm_leadV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $filtros   = $this->filtrosLead($f);
        $classe    = $this->condFormClasse('l.formPreenchido_leadV2', 'Evento');
        $oportExpr = $this->oportunidadeExpr($bidsIntList);

        $sql = "
            SELECT
                l.formPreenchido_leadV2 AS form,
                COUNT(l.id_leadV2) AS total_leads,
                SUM(CASE WHEN l.qualificado_leadV2 = 'Sim' THEN 1 ELSE 0 END) AS qualificados,
                $oportExpr AS oportunidades
            FROM leadV2 l
            WHERE l.dataUltForm_leadV2 IS NOT NULL AND l.dataUltForm_leadV2 > '0000-00-00'
              AND ({$periodo['sql']})
              {$filtros['sql']} AND {$classe}
            GROUP BY l.formPreenchido_leadV2
        ";
        $params = array_merge($periodo['params'], $filtros['params']);

        $porForm = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $val = [
                'total_leads'   => (int)$r['total_leads'],
                'qualificados'  => (int)$r['qualificados'],
                'oportunidades' => (int)$r['oportunidades'],
            ];
            $this->acumularLeads($porForm, $this->chaveDim($r['form']), $val);
        }
        return $porForm;
    }

    // ===================================================================
    // CÁLCULOS: FUNIL DE VENDAS (dealV2 — cadeira/consultas/realizadas/combos)
    // ===================================================================

    /**
     * Monta os 6 fragmentos SUM(CASE WHEN...) do funil (cadeira, consultas
     * vendidas + receita, consultas realizadas, combos + receita) — mesmas
     * condições de executive_controle.php::calcularFunilUnificado(), com a
     * receita de consulta nova (valorVendaDolar_dealV2, confirmada via DAX do
     * usuário — VERIFY nome exato da coluna quando houver acesso ao schema).
     * Compartilhado entre funilPorDimensoes() (GROUP BY Campanha/Conjunto/Anúncio)
     * e funilVendasGlobalFormClasse()/funilSelectsVariante() (variantes global/
     * Evento/Digital).
     */
    private function funilSelects(array $f): array
    {
        $params = [];

        $p1 = $this->buildPeriodFilter('d.dataVenda_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $s1 = "SUM(CASE WHEN {$p1['sql']}
                 AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
                 AND d.faseId_dealV2 = 'C9:Won'
            THEN 1 ELSE 0 END) AS cadeira_vendida";
        $params = array_merge($params, $p1['params']);

        $p2 = $this->buildPeriodFilter('d.dataVenda_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $s2 = "SUM(CASE WHEN {$p2['sql']}
                 AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
                 AND d.faseId_dealV2 IN ('Won', 'C11:WON')
            THEN 1 ELSE 0 END) AS consultas_vendidas";
        $params = array_merge($params, $p2['params']);

        $p3 = $this->buildPeriodFilter('d.dataVenda_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $s3 = "SUM(CASE WHEN {$p3['sql']}
                 AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
                 AND d.faseId_dealV2 IN ('Won', 'C11:WON')
            THEN COALESCE(d.valorVendaDolar_dealV2, 0) ELSE 0 END) AS faturamento_consultas";
        $params = array_merge($params, $p3['params']);

        $p4 = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $s4 = "SUM(CASE WHEN {$p4['sql']}
                 AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
                 AND LEFT(d.faseId_dealV2, 2) = 'C5'
            THEN 1 ELSE 0 END) AS consultas_realizadas";
        $params = array_merge($params, $p4['params']);

        $p5 = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $s5 = "SUM(CASE WHEN {$p5['sql']}
                 AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
                 AND d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON')
            THEN 1 ELSE 0 END) AS combos_vendidos";
        $params = array_merge($params, $p5['params']);

        $p6 = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $s6 = "SUM(CASE WHEN {$p6['sql']}
                 AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
                 AND d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON')
            THEN COALESCE(d.valorComboLiquido_dealV2, 0) ELSE 0 END) AS faturamento_combos";
        $params = array_merge($params, $p6['params']);

        return ['selects' => [$s1, $s2, $s3, $s4, $s5, $s6], 'params' => $params];
    }

    /**
     * Fase 1B (item 3): mesmos 6 fragmentos de funilSelects(), com um sufixo
     * de coluna e uma condição de classificação opcional ANDada em cada CASE
     * (null = sem restrição, variante "global") — permite montar
     * global+Evento+Digital como 18 colunas de uma ÚNICA query em vez de 3
     * chamadas separadas de funilVendas(). Ver funilVendasGlobalFormClasse().
     */
    private function funilSelectsVariante(array $f, string $sufixo, ?string $cond): array
    {
        $prefix = $cond !== null ? "($cond) AND " : '';
        $params = [];

        $p1 = $this->buildPeriodFilter('d.dataVenda_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $s1 = "SUM(CASE WHEN {$prefix}{$p1['sql']}
                 AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
                 AND d.faseId_dealV2 = 'C9:Won'
            THEN 1 ELSE 0 END) AS cadeira_vendida_$sufixo";
        $params = array_merge($params, $p1['params']);

        $p2 = $this->buildPeriodFilter('d.dataVenda_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $s2 = "SUM(CASE WHEN {$prefix}{$p2['sql']}
                 AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
                 AND d.faseId_dealV2 IN ('Won', 'C11:WON')
            THEN 1 ELSE 0 END) AS consultas_vendidas_$sufixo";
        $params = array_merge($params, $p2['params']);

        $p3 = $this->buildPeriodFilter('d.dataVenda_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $s3 = "SUM(CASE WHEN {$prefix}{$p3['sql']}
                 AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
                 AND d.faseId_dealV2 IN ('Won', 'C11:WON')
            THEN COALESCE(d.valorVendaDolar_dealV2, 0) ELSE 0 END) AS faturamento_consultas_$sufixo";
        $params = array_merge($params, $p3['params']);

        $p4 = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $s4 = "SUM(CASE WHEN {$prefix}{$p4['sql']}
                 AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
                 AND LEFT(d.faseId_dealV2, 2) = 'C5'
            THEN 1 ELSE 0 END) AS consultas_realizadas_$sufixo";
        $params = array_merge($params, $p4['params']);

        $p5 = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $s5 = "SUM(CASE WHEN {$prefix}{$p5['sql']}
                 AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
                 AND d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON')
            THEN 1 ELSE 0 END) AS combos_vendidos_$sufixo";
        $params = array_merge($params, $p5['params']);

        $p6 = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $s6 = "SUM(CASE WHEN {$prefix}{$p6['sql']}
                 AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
                 AND d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON')
            THEN COALESCE(d.valorComboLiquido_dealV2, 0) ELSE 0 END) AS faturamento_combos_$sufixo";
        $params = array_merge($params, $p6['params']);

        $p7 = $this->buildPeriodFilter('d.dataVenda_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $s7 = "SUM(CASE WHEN {$prefix}{$p7['sql']}
                 AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
                 AND d.faseId_dealV2 = 'C9:Won'
            THEN COALESCE(d.valorVendaDolar_dealV2, 0) ELSE 0 END) AS faturamento_cadeiras_$sufixo";
        $params = array_merge($params, $p7['params']);

        return ['selects' => [$s1, $s2, $s3, $s4, $s5, $s6, $s7], 'params' => $params];
    }

    private function mapFunilRow(array $r): array
    {
        return [
            'cadeira_vendida'       => (int)($r['cadeira_vendida'] ?? 0),
            'consultas_vendidas'    => (int)($r['consultas_vendidas'] ?? 0),
            'faturamento_consultas' => (float)($r['faturamento_consultas'] ?? 0),
            'consultas_realizadas'  => (int)($r['consultas_realizadas'] ?? 0),
            'combos_vendidos'       => (int)($r['combos_vendidos'] ?? 0),
            'faturamento_combos'    => (float)($r['faturamento_combos'] ?? 0),
        ];
    }

    /** Mesma forma de mapFunilRow(), lendo as colunas com sufixo de funilSelectsVariante(). */
    private function mapFunilRowSuf(array $r, string $sufixo): array
    {
        return [
            'cadeira_vendida'       => (int)($r["cadeira_vendida_$sufixo"] ?? 0),
            'consultas_vendidas'    => (int)($r["consultas_vendidas_$sufixo"] ?? 0),
            'faturamento_consultas' => (float)($r["faturamento_consultas_$sufixo"] ?? 0),
            'consultas_realizadas'  => (int)($r["consultas_realizadas_$sufixo"] ?? 0),
            'combos_vendidos'       => (int)($r["combos_vendidos_$sufixo"] ?? 0),
            'faturamento_combos'    => (float)($r["faturamento_combos_$sufixo"] ?? 0),
            'faturamento_cadeiras'  => (float)($r["faturamento_cadeiras_$sufixo"] ?? 0),
        ];
    }

    /**
     * Fase 1B (item 3): versão consolidada de funilVendas() que computa
     * global + Evento + Digital numa ÚNICA query (18 colunas: 6 métricas × 3
     * variantes) — mesmo raciocínio de custoMktGlobalFormClasse().
     */
    private function funilVendasGlobalFormClasse(array $f, string $eixoClasse = 'form'): array
    {
        $condEvento  = $this->condClasseEixo($eixoClasse, 'deal', 'Evento');
        $condDigital = $this->condClasseEixo($eixoClasse, 'deal', 'Digital');

        $vGlobal  = $this->funilSelectsVariante($f, 'global', null);
        $vEvento  = $this->funilSelectsVariante($f, 'evento', $condEvento);
        $vDigital = $this->funilSelectsVariante($f, 'digital', $condDigital);

        $filtros = $this->filtrosDeal($f);
        $classe  = $this->filtroFormClasseDeal($f);
        $pVenda  = $this->buildPeriodFilter('d.dataVenda_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pRealiz = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pCombo  = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $periodoWhere  = "(({$pVenda['sql']}) OR ({$pRealiz['sql']}) OR ({$pCombo['sql']}))";
        $periodoParams = array_merge($pVenda['params'], $pRealiz['params'], $pCombo['params']);

        $selects = array_merge($vGlobal['selects'], $vEvento['selects'], $vDigital['selects']);
        $sql = "SELECT " . implode(",\n", $selects) . "
            FROM dealV2 d
            WHERE $periodoWhere {$filtros['sql']}{$classe}";
        $params = array_merge($vGlobal['params'], $vEvento['params'], $vDigital['params'], $periodoParams, $filtros['params']);

        $row = $this->queryOne($sql, $params);

        $mk = fn(string $sufixo) => $this->mapFunilRowSuf($row, $sufixo);
        return ['global' => $mk('global'), 'Evento' => $mk('evento'), 'Digital' => $mk('digital')];
    }

    // ===================================================================
    // 7ª VIEW (Classificação de Campanha): série temporal Digital × Evento
    // ===================================================================

    /**
     * Expressão de agrupamento por período, para a 7ª view (Classificação de
     * Campanha) — cópia enxuta (só os 3 modos que essa view usa, sem
     * dia_semana) de helenacastro_controle.php::exprAgrupamento(), mesma
     * convenção de "cópia por controller" já adotada em todo o projeto.
     */
    private function exprAgrupamentoClassificacao(string $modo, string $col): string
    {
        return match ($modo) {
            'dia_mes'    => "DAYOFMONTH($col)",
            // Modo ISO 8601 (semana começa na segunda, 1-53).
            'semana_ano' => "WEEK($col, 3)",
            default      => "DATE_FORMAT($col, '%Y-%m')",
        };
    }

    /**
     * Série temporal das 3 métricas do funil de vendas (Cadeiras/Consultas
     * Vendidas/Combos), cada uma dividida em Digital×Evento (condFormClasse()
     * sobre formPreenchido_dealV2), agrupada conforme $modo.
     *
     * Cadeira e Consulta Vendida compartilham a mesma coluna de data
     * (dataVenda_dealV2), então saem de uma única branch da UNION ALL; Combo
     * usa dataContratoComboAssinado_dealV2, branch própria — mesmo raciocínio
     * de calcularGraficoLeadsVendas() (helenacastro_controle.php), que junta
     * fontes com colunas de data diferentes num único round-trip.
     *
     * Cada branch filtra no WHERE só a SUA coluna de data (buildPeriodFilter
     * + guarda de zero-date), por isso as condições dentro do CASE WHEN não
     * precisam repetir o filtro de período (diferente de funilSelects(), que
     * junta métricas de colunas de data DIFERENTES numa query só e por isso
     * precisa do filtro de período dentro de cada CASE WHEN).
     */
    private function serieClassificacaoCampanha(array $f, string $modo): array
    {
        $colVenda = 'd.dataVenda_dealV2';
        $colCombo = 'd.dataContratoComboAssinado_dealV2';

        $grupoVenda = $this->exprAgrupamentoClassificacao($modo, $colVenda);
        $grupoCombo = $this->exprAgrupamentoClassificacao($modo, $colCombo);

        $condEvento  = $this->condFormClasse('d.formPreenchido_dealV2', 'Evento');
        $condDigital = $this->condFormClasse('d.formPreenchido_dealV2', 'Digital');

        $filtros = $this->filtrosDeal($f);
        $pVenda  = $this->buildPeriodFilter($colVenda, $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pCombo  = $this->buildPeriodFilter($colCombo, $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);

        $sql = "
            SELECT
              periodo,
              SUM(cadeira_evento)  AS cadeira_evento,  SUM(cadeira_digital)  AS cadeira_digital,
              SUM(consulta_evento) AS consulta_evento, SUM(consulta_digital) AS consulta_digital,
              SUM(combo_evento)    AS combo_evento,    SUM(combo_digital)    AS combo_digital
            FROM (
                SELECT
                  $grupoVenda AS periodo,
                  SUM(CASE WHEN ($condEvento)  AND d.faseId_dealV2 = 'C9:Won' THEN 1 ELSE 0 END) AS cadeira_evento,
                  SUM(CASE WHEN ($condDigital) AND d.faseId_dealV2 = 'C9:Won' THEN 1 ELSE 0 END) AS cadeira_digital,
                  SUM(CASE WHEN ($condEvento)  AND d.faseId_dealV2 IN ('Won', 'C11:WON') THEN 1 ELSE 0 END) AS consulta_evento,
                  SUM(CASE WHEN ($condDigital) AND d.faseId_dealV2 IN ('Won', 'C11:WON') THEN 1 ELSE 0 END) AS consulta_digital,
                  0 AS combo_evento, 0 AS combo_digital
                FROM dealV2 d
                WHERE {$pVenda['sql']}
                  AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
                  {$filtros['sql']}
                GROUP BY periodo

                UNION ALL

                SELECT
                  $grupoCombo AS periodo,
                  0 AS cadeira_evento, 0 AS cadeira_digital, 0 AS consulta_evento, 0 AS consulta_digital,
                  SUM(CASE WHEN ($condEvento)  AND d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON') THEN 1 ELSE 0 END) AS combo_evento,
                  SUM(CASE WHEN ($condDigital) AND d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON') THEN 1 ELSE 0 END) AS combo_digital
                FROM dealV2 d
                WHERE {$pCombo['sql']}
                  AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
                  {$filtros['sql']}
                GROUP BY periodo
            ) t
            GROUP BY periodo
        ";

        $params = array_merge(
            $pVenda['params'], $filtros['params'],
            $pCombo['params'], $filtros['params']
        );

        $rows = $this->queryAll($sql, $params);

        $porPeriodo = [];
        foreach ($rows as $r) {
            $porPeriodo[(string)$r['periodo']] = [
                'cadeira_evento'   => (int)$r['cadeira_evento'],
                'cadeira_digital'  => (int)$r['cadeira_digital'],
                'consulta_evento'  => (int)$r['consulta_evento'],
                'consulta_digital' => (int)$r['consulta_digital'],
                'combo_evento'     => (int)$r['combo_evento'],
                'combo_digital'    => (int)$r['combo_digital'],
            ];
        }

        if ($modo === 'dia_mes') {
            $keys   = range(1, 31);
            $labels = array_map('strval', $keys);
        } elseif ($modo === 'semana_ano') {
            $keys = array_values(array_unique(array_map('intval', array_keys($porPeriodo))));
            sort($keys);
            $labels = array_map(fn($k) => 'Sem ' . $k, $keys);
        } else {
            $keys = array_values(array_unique(array_keys($porPeriodo)));
            sort($keys);
            $MESES_ABREV = ['01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr', '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago', '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'];
            $labels = array_map(function ($k) use ($MESES_ABREV) {
                [$ano, $mes] = explode('-', $k);
                return ($MESES_ABREV[$mes] ?? $mes) . '/' . substr($ano, 2);
            }, $keys);
        }

        $vazio = [
            'cadeira_evento' => 0, 'cadeira_digital' => 0,
            'consulta_evento' => 0, 'consulta_digital' => 0,
            'combo_evento' => 0, 'combo_digital' => 0,
        ];

        $combos = $consultas = $cadeiras = [];
        foreach ($keys as $i => $key) {
            $b     = $porPeriodo[(string)$key] ?? $vazio;
            $label = (string)$labels[$i];
            $cadeiras[]  = ['label' => $label, 'evento' => $b['cadeira_evento'],  'digital' => $b['cadeira_digital']];
            $consultas[] = ['label' => $label, 'evento' => $b['consulta_evento'], 'digital' => $b['consulta_digital']];
            $combos[]    = ['label' => $label, 'evento' => $b['combo_evento'],    'digital' => $b['combo_digital']];
        }

        return ['combos' => $combos, 'consultas' => $consultas, 'cadeiras' => $cadeiras];
    }

    /**
     * Fase 1B (item 3, parte B): funil (cadeira/consultas/realizadas/combos)
     * por Campanha + Conjunto + Anúncio numa ÚNICA query (mesmo raciocínio de
     * custoMktPorDimensoes()) — reaproveita os 6 fragmentos de funilSelects()
     * como estão (sem variante de classificação, só GROUP BY multi-coluna).
     */
    private function funilPorDimensoes(array $f): array
    {
        $blocos  = $this->funilSelects($f);
        $filtros = $this->filtrosDeal($f);
        $classe  = $this->filtroFormClasseDeal($f);
        $pVenda  = $this->buildPeriodFilter('d.dataVenda_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pRealiz = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pCombo  = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $periodoWhere  = "(({$pVenda['sql']}) OR ({$pRealiz['sql']}) OR ({$pCombo['sql']}))";
        $periodoParams = array_merge($pVenda['params'], $pRealiz['params'], $pCombo['params']);

        $sql = "SELECT
                d.utmCampaign_dealV2 AS campanha,
                d.utmContent_dealV2 AS conjunto,
                d.utmTerm_dealV2 AS anuncio,
                " . implode(",\n", $blocos['selects']) . "
            FROM dealV2 d
            WHERE $periodoWhere {$filtros['sql']}{$classe}
            GROUP BY d.utmCampaign_dealV2, d.utmContent_dealV2, d.utmTerm_dealV2";
        $params = array_merge($blocos['params'], $periodoParams, $filtros['params']);

        $porCampanha = $porConjunto = $porAnuncio = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $val = $this->mapFunilRow($r);
            $this->acumularFunil($porCampanha, $this->chaveDim($r['campanha']), $val);
            $this->acumularFunil($porConjunto, $this->chaveDim($r['conjunto']), $val);
            $this->acumularFunil($porAnuncio,  $this->chaveDim($r['anuncio']),  $val);
        }
        return ['campanha' => $porCampanha, 'conjunto' => $porConjunto, 'anuncio' => $porAnuncio];
    }

    private function acumularFunil(array &$mapa, string $chave, array $val): void
    {
        if (!isset($mapa[$chave])) {
            $mapa[$chave] = [
                'cadeira_vendida' => 0, 'consultas_vendidas' => 0, 'faturamento_consultas' => 0.0,
                'consultas_realizadas' => 0, 'combos_vendidos' => 0, 'faturamento_combos' => 0.0,
            ];
        }
        $mapa[$chave]['cadeira_vendida']       += $val['cadeira_vendida'];
        $mapa[$chave]['consultas_vendidas']    += $val['consultas_vendidas'];
        $mapa[$chave]['faturamento_consultas'] += $val['faturamento_consultas'];
        $mapa[$chave]['consultas_realizadas']  += $val['consultas_realizadas'];
        $mapa[$chave]['combos_vendidos']       += $val['combos_vendidos'];
        $mapa[$chave]['faturamento_combos']    += $val['faturamento_combos'];
    }

    /**
     * Funil (cadeira/consultas/realizadas/combos) por FORMULÁRIO (3ª
     * view/Eventos), só universo Evento (condFormClasse('Evento') sempre
     * ativo). Mesmo corpo de funilPorDimensoes(), trocando o GROUP BY de
     * utmCampaign/utmContent/utmTerm para formPreenchido_dealV2.
     */
    private function funilPorFormulario(array $f): array
    {
        $blocos  = $this->funilSelects($f);
        $filtros = $this->filtrosDeal($f);
        $classe  = $this->condFormClasse('d.formPreenchido_dealV2', 'Evento');
        $pVenda  = $this->buildPeriodFilter('d.dataVenda_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pRealiz = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pCombo  = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $periodoWhere  = "(({$pVenda['sql']}) OR ({$pRealiz['sql']}) OR ({$pCombo['sql']}))";
        $periodoParams = array_merge($pVenda['params'], $pRealiz['params'], $pCombo['params']);

        $sql = "SELECT
                d.formPreenchido_dealV2 AS form,
                " . implode(",\n", $blocos['selects']) . "
            FROM dealV2 d
            WHERE $periodoWhere {$filtros['sql']} AND {$classe}
            GROUP BY d.formPreenchido_dealV2";
        $params = array_merge($blocos['params'], $periodoParams, $filtros['params']);

        $porForm = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $val = $this->mapFunilRow($r);
            $this->acumularFunil($porForm, $this->chaveDim($r['form']), $val);
        }
        return $porForm;
    }

    // ===================================================================
    // MONTAGEM: KPIS + FUNIL VISUAL (fórmulas confirmadas contra o print do usuário)
    // ===================================================================

    /**
     * Fórmulas de conversão confirmadas número-a-número contra o dashboard
     * atual (ver plano): oportunidades/cadeira/consultas-vendidas são 3
     * ramos paralelos medidos contra o pool de QUALIFICADOS (não estágios
     * sequenciais entre si); só realizada→combo é sequencial de verdade.
     * ROI/CAC/CPL usam só faturamento de combo (não soma consulta).
     */
    private function montarKpisMarketing(array $leads, array $funil, float $custoMkt): array
    {
        $totalLeads          = $leads['total_leads'];
        $qualificados        = $leads['qualificados'];
        $oportunidades       = $leads['oportunidades'];
        $cadeira             = $funil['cadeira_vendida'];
        $consultasVendidas   = $funil['consultas_vendidas'];
        $consultasRealizadas = $funil['consultas_realizadas'];
        $combos              = $funil['combos_vendidos'];
        $faturCombos         = $funil['faturamento_combos'];
        $faturCadeiras       = $funil['faturamento_cadeiras'];

        $pct = fn($num, $den) => $den > 0 ? $num / $den : 0.0;

        return [
            'total_leads'            => $totalLeads,
            'qualificados'           => $qualificados,
            'pct_qualificados'       => $pct($qualificados, $totalLeads),
            'oportunidades'          => $oportunidades,
            'pct_oportunidades'      => $pct($oportunidades, $qualificados),
            'cadeira_vendida'        => $cadeira,
            'conv_cadeira'           => $pct($cadeira, $qualificados),
            'faturamento_cadeiras'   => $faturCadeiras,
            'consultas_vendidas'     => $consultasVendidas,
            'faturamento_consultas'  => $funil['faturamento_consultas'],
            'conv_consultas'         => $pct($consultasVendidas, $qualificados),
            'consultas_realizadas'   => $consultasRealizadas,
            'conv_realizadas'        => $pct($consultasRealizadas, $consultasVendidas),
            'combos_vendidos'        => $combos,
            'faturamento_combos'     => $faturCombos,
            'conv_combo'             => $pct($combos, $consultasRealizadas),
            'investimento'           => $custoMkt,
            'roi'                    => $pct($faturCombos, $custoMkt),
            'cpl'                    => $pct($custoMkt, $qualificados),
            'cac_cadeira'            => $pct($custoMkt, $cadeira),
            'cac_consulta'           => $pct($custoMkt, $consultasVendidas),
            'cac_combos'             => $pct($custoMkt, $combos),
        ];
    }

    /**
     * Linha da 2ª view (um lado da comparação, uma classe). Reaproveita
     * montarKpisMarketing() de propósito: é o que garante que a linha Total da
     * tabela e os KPIs do topo não possam divergir — são literalmente a mesma
     * função. Só 'cliques' é acrescentado, por não fazer parte do funil.
     */
    private function linhaComparativo(array $custo, array $leads, array $funil): array
    {
        return $this->montarKpisMarketing($leads, $funil, $custo['custo_mkt'])
             + ['cliques' => $custo['cliques']];
    }

    // ===================================================================
    // SÉRIES MENSAIS (2ª view — gráfico de evolução)
    // ===================================================================

    /**
     * Série mês a mês das duas janelas, para o gráfico da 2ª view.
     *
     * 4 queries no total (não 8): cada uma cobre os DOIS períodos de uma vez
     * (WHERE = range atual OR range do ano anterior) e devolve ano+mês, que o
     * PHP distribui nos dois lados. São 4 e não 1 porque cada métrica é
     * ancorada na SUA coluna de data — agrupar tudo por um mês só misturaria
     * combo assinado com cadeira vendida.
     */
    private function seriesMensais(array $fa, array $fl, array $pa): array
    {
        $NOMES = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        $y  = (int)substr($pa['inicio'], 0, 4);
        $m  = (int)substr($pa['inicio'], 5, 2);
        $yF = (int)substr($pa['fim'], 0, 4);
        $mF = (int)substr($pa['fim'], 5, 2);
        $multiAno = $y !== $yF;

        $meses = [];
        while (($y < $yF || ($y === $yF && $m <= $mF)) && count($meses) < 36) {
            $meses[] = ['y' => $y, 'm' => $m, 'label' => $NOMES[$m] . ($multiAno ? '/' . substr((string)$y, 2) : '')];
            if (++$m > 12) { $m = 1; $y++; }
        }

        $vazio = array_fill(0, count($meses), 0);
        $out = [
            'meses' => array_column($meses, 'label'),
            'atual' => [],
            'ly'    => [],
        ];
        foreach (['investimento', 'total_leads', 'qualificados', 'cadeira_vendida', 'consultas_vendidas', 'combos_vendidos', 'faturamento_combos'] as $k) {
            $out['atual'][$k] = $vazio;
            $out['ly'][$k]    = $vazio;
        }

        // Índice (ano-mês) => posição na série, para cada lado.
        $posAtual = $posLy = [];
        foreach ($meses as $i => $mes) {
            $posAtual[$mes['y'] . '-' . $mes['m']] = $i;
            $posLy[($mes['y'] - 1) . '-' . $mes['m']] = $i;
        }

        $despeja = function (array $rows, array $mapa) use (&$out, $posAtual, $posLy) {
            foreach ($rows as $r) {
                $chave = (int)$r['y'] . '-' . (int)$r['m'];
                foreach ([['atual', $posAtual], ['ly', $posLy]] as [$lado, $pos]) {
                    if (!isset($pos[$chave])) {
                        continue;
                    }
                    foreach ($mapa as $destino => $coluna) {
                        $out[$lado][$destino][$pos[$chave]] = is_callable($coluna) ? $coluna($r) : (float)($r[$coluna] ?? 0);
                    }
                }
            }
        };

        $periodoDuplo = function (string $col) use ($fa, $fl) {
            $pA = $this->buildPeriodFilter($col, null, null, $fa['data_inicio'], $fa['data_fim']);
            $pL = $this->buildPeriodFilter($col, null, null, $fl['data_inicio'], $fl['data_fim']);
            return [
                'sql'    => "(({$pA['sql']}) OR ({$pL['sql']}))",
                'params' => array_merge($pA['params'], $pL['params']),
            ];
        };

        // 1) Investimento (custoMkt) — mesma regra bimodal por ano de custoMktGlobalFormClasse()
        $per = $periodoDuplo('cm.data_custoMkt');
        $fil = $this->filtrosCusto($fa);
        $sql = "
            SELECT YEAR(cm.data_custoMkt) AS y, MONTH(cm.data_custoMkt) AS m,
                SUM(CASE
                    WHEN YEAR(cm.data_custoMkt) = 2025 AND cm.origem_custoMkt = 'meta' THEN cm.valor_custoMkt
                    WHEN YEAR(cm.data_custoMkt) = 2026 AND cm.conta_custoMkt IN ('Dollar 1', 'Dollar 2') THEN cm.valor_custoMkt
                    ELSE 0
                END) AS custo_dollar,
                SUM(CASE
                    WHEN YEAR(cm.data_custoMkt) = 2025 AND cm.origem_custoMkt = 'google' THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio, 0)
                    WHEN YEAR(cm.data_custoMkt) = 2026 AND cm.conta_custoMkt <> 'Dollar 1' AND cm.conta_custoMkt <> 'Dollar 2' THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio, 0)
                    ELSE 0
                END) AS custo_real
            FROM custoMkt cm
            LEFT JOIN (SELECT data_cambio, MAX(valor_cambio) AS valor_cambio FROM cambio GROUP BY data_cambio) ca
                ON ca.data_cambio = cm.data_custoMkt
            WHERE {$per['sql']} {$fil['sql']}
            GROUP BY YEAR(cm.data_custoMkt), MONTH(cm.data_custoMkt)
        ";
        $despeja(
            $this->queryAll($sql, array_merge($per['params'], $fil['params'])),
            ['investimento' => fn($r) => (float)$r['custo_dollar'] + (float)$r['custo_real']]
        );

        // 2) Leads e qualificados (leadV2)
        $per = $periodoDuplo('l.dataUltForm_leadV2');
        $fil = $this->filtrosLead($fa);
        $sql = "
            SELECT YEAR(l.dataUltForm_leadV2) AS y, MONTH(l.dataUltForm_leadV2) AS m,
                COUNT(l.id_leadV2) AS total_leads,
                SUM(CASE WHEN l.qualificado_leadV2 = 'Sim' THEN 1 ELSE 0 END) AS qualificados
            FROM leadV2 l
            WHERE l.dataUltForm_leadV2 IS NOT NULL AND l.dataUltForm_leadV2 > '0000-00-00'
              AND {$per['sql']} {$fil['sql']}
            GROUP BY YEAR(l.dataUltForm_leadV2), MONTH(l.dataUltForm_leadV2)
        ";
        $despeja(
            $this->queryAll($sql, array_merge($per['params'], $fil['params'])),
            ['total_leads' => 'total_leads', 'qualificados' => 'qualificados']
        );

        // 3) Cadeiras e consultas vendidas — ancoradas em dataVenda_dealV2
        $per = $periodoDuplo('d.dataVenda_dealV2');
        $fil = $this->filtrosDeal($fa);
        $sql = "
            SELECT YEAR(d.dataVenda_dealV2) AS y, MONTH(d.dataVenda_dealV2) AS m,
                SUM(CASE WHEN d.faseId_dealV2 = 'C9:Won' THEN 1 ELSE 0 END) AS cadeira_vendida,
                SUM(CASE WHEN d.faseId_dealV2 IN ('Won', 'C11:WON') THEN 1 ELSE 0 END) AS consultas_vendidas
            FROM dealV2 d
            WHERE d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
              AND {$per['sql']} {$fil['sql']}
            GROUP BY YEAR(d.dataVenda_dealV2), MONTH(d.dataVenda_dealV2)
        ";
        $despeja(
            $this->queryAll($sql, array_merge($per['params'], $fil['params'])),
            ['cadeira_vendida' => 'cadeira_vendida', 'consultas_vendidas' => 'consultas_vendidas']
        );

        // 4) Combos e faturamento — ancorados em dataContratoComboAssinado_dealV2
        $per = $periodoDuplo('d.dataContratoComboAssinado_dealV2');
        $fil = $this->filtrosDeal($fa);
        $sql = "
            SELECT YEAR(d.dataContratoComboAssinado_dealV2) AS y, MONTH(d.dataContratoComboAssinado_dealV2) AS m,
                SUM(CASE WHEN d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON') THEN 1 ELSE 0 END) AS combos_vendidos,
                SUM(CASE WHEN d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON')
                         THEN COALESCE(d.valorComboLiquido_dealV2, 0) ELSE 0 END) AS faturamento_combos
            FROM dealV2 d
            WHERE d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
              AND {$per['sql']} {$fil['sql']}
            GROUP BY YEAR(d.dataContratoComboAssinado_dealV2), MONTH(d.dataContratoComboAssinado_dealV2)
        ";
        $despeja(
            $this->queryAll($sql, array_merge($per['params'], $fil['params'])),
            ['combos_vendidos' => 'combos_vendidos', 'faturamento_combos' => 'faturamento_combos']
        );

        return $out;
    }

    /**
     * 7 etapas do funil visual — mesmos números do KPI (não recalcula nada) +
     * lucro ACUMULADO por etapa (cascata como no Dashboard ADS do PBI):
     * faturamento realizado até ali menos o investimento total. Nenhuma
     * etapa antes de "Cadeiras Vendidas" tem receita própria no schema, então
     * o lucro fica negativo e estável até lá, melhora com faturamento_cadeiras,
     * melhora de novo com faturamento_consultas, e vira positivo em "Combos
     * Vendidos" com faturamento_combos.
     */
    private function montarFunilMarketing(array $k): array
    {
        $lucroSemVenda    = -$k['investimento'];
        $lucroPosCadeira  = $lucroSemVenda + $k['faturamento_cadeiras'];
        $lucroPosConsulta = $lucroPosCadeira + $k['faturamento_consultas'];
        $lucroPosCombo    = $lucroPosConsulta + $k['faturamento_combos'];

        return [
            ['etapa' => 'Total de Leads',      'valor' => $k['total_leads'],                                                'lucro' => $lucroSemVenda],
            ['etapa' => 'Qualificados',        'valor' => $k['qualificados'],         'conversao' => $k['pct_qualificados'],'lucro' => $lucroSemVenda],
            ['etapa' => 'Oportunidades',       'valor' => $k['oportunidades'],        'conversao' => $k['pct_oportunidades'],'lucro' => $lucroSemVenda],
            ['etapa' => 'Cadeiras Vendidas',   'valor' => $k['cadeira_vendida'],      'conversao' => $k['conv_cadeira'],    'lucro' => $lucroPosCadeira],
            ['etapa' => 'Consultas Vendidas',  'valor' => $k['consultas_vendidas'],   'conversao' => $k['conv_consultas'],  'lucro' => $lucroPosConsulta],
            ['etapa' => 'Consultas Realizadas','valor' => $k['consultas_realizadas'], 'conversao' => $k['conv_realizadas'], 'lucro' => $lucroPosConsulta],
            ['etapa' => 'Combos Vendidos',     'valor' => $k['combos_vendidos'],      'conversao' => $k['conv_combo'],      'lucro' => $lucroPosCombo],
        ];
    }

    // ===================================================================
    // TABELAS DE BREAKDOWN (Campanha / Conjunto de Anúncios / Anúncios / Form)
    // ===================================================================

    /** Uma linha das 17 colunas (mesmas fórmulas de montarKpisMarketing(), por dimensão). */
    private function montarLinhaTabela(string $dim, array $custo, array $leads, array $funil, bool $vazio = false): array
    {
        $custoMkt          = $custo['custo_mkt'] ?? 0.0;
        $totalLeads        = $leads['total_leads'] ?? 0;
        $qualificados      = $leads['qualificados'] ?? 0;
        $oportunidades     = $leads['oportunidades'] ?? 0;
        $cadeira           = $funil['cadeira_vendida'] ?? 0;
        $consultasVendidas = $funil['consultas_vendidas'] ?? 0;
        $combos            = $funil['combos_vendidos'] ?? 0;
        $faturCombos       = $funil['faturamento_combos'] ?? 0.0;

        $pct = fn($num, $den) => $den > 0 ? $num / $den : 0.0;

        // 'vazio' entra SÓ na linha "(sem X)" — as demais mantêm exatamente as
        // 18 chaves de sempre. É aditivo: nada itera as chaves da linha (o
        // frontend renderiza por coluna, lendo r[c.k]).
        $linha = [
            'dimensao'           => $dim,
            'invest'             => $custoMkt,
            'roi'                => $pct($faturCombos, $custoMkt),
            'cliques'            => $custo['cliques'] ?? 0,
            'leads'              => $totalLeads,
            'cpl'                => $pct($custoMkt, $qualificados),
            'qualificados'       => $qualificados,
            'pct_qualificados'   => $pct($qualificados, $totalLeads),
            'oportunidades'      => $oportunidades,
            'cadeira_vendida'    => $cadeira,
            'conv_cadeira'       => $pct($cadeira, $qualificados),
            'cac_cadeira'        => $pct($custoMkt, $cadeira),
            'consultas_vendidas' => $consultasVendidas,
            'conv_consultas'     => $pct($consultasVendidas, $qualificados),
            'cac_consulta'       => $pct($custoMkt, $consultasVendidas),
            'combos_vendidos'    => $combos,
            // Sem coluna "Consultas Realizadas" na tabela — a base de conversão do
            // combo aqui é consultas_vendidas (não realizadas, como no funil global).
            'conv_combo'         => $pct($combos, $consultasVendidas),
            'cac_combos'         => $pct($custoMkt, $combos),
        ];

        if ($vazio) {
            $linha['vazio'] = true;
        }

        return $linha;
    }

    /**
     * Tabela por dimensão (Campanha/Conjunto/Anúncio) — roda 3 GROUP BY
     * independentes (custo, leads+oportunidades, funil) e une as chaves em
     * PHP (equivalente ao UNION+DISTINCT do DAX original: uma campanha com
     * investimento mas zero leads, ou vice-versa, ainda aparece como linha).
     */
    /**
     * Fase 1B (item 3, parte B): recebe os 3 mapas já calculados por
     * custoMktPorDimensoes()/leadsPorDimensoes()/funilPorDimensoes() (chave =
     * valor da dimensão, ex.: nome da campanha) em vez de rodar suas próprias
     * queries — a lógica de montagem das linhas/totais é exatamente a mesma
     * de antes, só a fonte dos dados mudou.
     */
    private function montarTabelaDimensao(array $custoPorDim, array $leadsPorDim, array $funilPorDim, string $rotuloVazio): array
    {
        $dims = array_unique(array_merge(
            array_keys($custoPorDim),
            array_keys($leadsPorDim),
            array_keys($funilPorDim)
        ));

        $linhas = [];
        foreach ($dims as $dim) {
            // Chave vazia = registros sem essa dimensão (ver chaveDim()). Ganha
            // rótulo próprio e a flag 'vazio', que o frontend usa para não
            // deixar a linha clicável: o filtro do backend é IN (...) e não
            // sabe expressar "sem valor", então um clique casaria zero linhas.
            $vazio = ($dim === '');
            $linhas[] = $this->montarLinhaTabela(
                $vazio ? $rotuloVazio : $dim,
                $custoPorDim[$dim] ?? ['custo_mkt' => 0.0, 'cliques' => 0],
                $leadsPorDim[$dim] ?? ['total_leads' => 0, 'qualificados' => 0, 'oportunidades' => 0],
                $funilPorDim[$dim] ?? [],
                $vazio
            );
        }
        usort($linhas, fn($a, $b) => $b['invest'] <=> $a['invest']);

        $totalCusto = [
            'custo_mkt' => array_sum(array_column($custoPorDim, 'custo_mkt')),
            'cliques'   => array_sum(array_column($custoPorDim, 'cliques')),
        ];
        $totalLeads = [
            'total_leads'   => array_sum(array_column($leadsPorDim, 'total_leads')),
            'qualificados'  => array_sum(array_column($leadsPorDim, 'qualificados')),
            'oportunidades' => array_sum(array_column($leadsPorDim, 'oportunidades')),
        ];
        $totalFunil = [
            'cadeira_vendida'    => array_sum(array_column($funilPorDim, 'cadeira_vendida')),
            'consultas_vendidas' => array_sum(array_column($funilPorDim, 'consultas_vendidas')),
            'combos_vendidos'    => array_sum(array_column($funilPorDim, 'combos_vendidos')),
            'faturamento_combos' => array_sum(array_column($funilPorDim, 'faturamento_combos')),
        ];
        $linhas[] = $this->montarLinhaTabela('Total', $totalCusto, $totalLeads, $totalFunil);

        return $linhas;
    }

    /**
     * Tabela de Classificação de Form (Evento × Digital) — 2 linhas fixas via
     * condFormClasse()/condFormClasseCustoMkt() como filtro adicional em vez
     * de GROUP BY (mesmo padrão de executive_controle.php para a aba Digital
     * × Evento, sem custo físico).
     */
    /**
     * Fase 1B (item 3): recebe os mapas já calculados por
     * custoMktGlobalFormClasse()/leadsOportunidadesGlobalFormClasse()/
     * funilVendasGlobalFormClasse() (chave 'Evento'/'Digital') em vez de
     * rodar suas próprias queries — a lógica de montagem das linhas/totais é
     * exatamente a mesma de antes, só a fonte dos dados mudou.
     */
    private function calcularTabelaFormClasse(array $custoGFC, array $leadsGFC, array $funilGFC, ?string $classeFiltrada = null): array
    {
        // Com o filtro de classe ativo, a query já devolve zero na outra classe.
        // Emitir a linha zerada mostraria "Evento — $0 / ROI 0,00x", que parece
        // dado e não filtro: a tabela reduz a 1 linha + Total, igual às 3
        // tabelas de dimensão (ver docblock de filtrosDimensao()).
        $classes = $classeFiltrada === null ? ['Evento', 'Digital'] : [$classeFiltrada];

        $linhas = [];
        foreach ($classes as $classe) {
            $linhas[] = $this->montarLinhaTabela($classe, $custoGFC[$classe], $leadsGFC[$classe], $funilGFC[$classe]);
        }

        // condFormClasse() é MECE (toda linha cai em Evento OU Digital) — Total = soma das classes exibidas.
        $somar = function (array $gfc, string $campo) use ($classes) {
            $t = 0;
            foreach ($classes as $c) {
                $t += $gfc[$c][$campo];
            }
            return $t;
        };
        $totalCusto = [
            'custo_mkt' => $somar($custoGFC, 'custo_mkt'),
            'cliques'   => $somar($custoGFC, 'cliques'),
        ];
        $totalLeads = [
            'total_leads'   => $somar($leadsGFC, 'total_leads'),
            'qualificados'  => $somar($leadsGFC, 'qualificados'),
            'oportunidades' => $somar($leadsGFC, 'oportunidades'),
        ];
        $totalFunil = [
            'cadeira_vendida'    => $somar($funilGFC, 'cadeira_vendida'),
            'consultas_vendidas' => $somar($funilGFC, 'consultas_vendidas'),
            'combos_vendidos'    => $somar($funilGFC, 'combos_vendidos'),
            'faturamento_combos' => $somar($funilGFC, 'faturamento_combos'),
        ];
        $linhas[] = $this->montarLinhaTabela('Total', $totalCusto, $totalLeads, $totalFunil);

        return $linhas;
    }

    // ===================================================================
    // DESEMPENHO (5ª aba): rankings de perfil do lead + rosca de formação +
    // séries temporais. Colunas de leadV2 usadas aqui (motivoDescarte_leadV2,
    // profissao_leadV2, tempoExp_leadV2, formacaoAcademica_leadV2) não
    // constavam no CLAUDE.md antes deste dashboard — confirmadas via
    // SHOW COLUMNS em produção (ver plano desta sessão).
    // ===================================================================

    /**
     * Ranking Top-N por uma coluna de perfil do lead — mesma
     * população/filtros de leadsOportunidadesGlobalFormClasse()
     * (dataUltForm_leadV2 no período + campanha/form/conjunto/anúncio +
     * form_classe), sem cross-filter da própria dimensão (não faz sentido
     * filtrar "Profissão" pela profissão pedida). Valores em branco/
     * placeholder ficam de fora — é um Top-N, não uma tabela de breakdown
     * que precise reconciliar 100% com o total de leads (ver plano).
     * $limit nunca vem do usuário (só 9/10 fixos abaixo) — interpolado como
     * int, não como parâmetro.
     */
    private function topPorColuna(array $f, string $col, array $excluir, int $limit): array
    {
        $periodo = $this->buildPeriodFilter('l.dataUltForm_leadV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $filtros = $this->filtrosLead($f);
        $classe  = $this->filtroFormClasseLead($f);

        $excl         = array_merge([''], $excluir);
        $placeholders = implode(',', array_fill(0, count($excl), '?'));

        $sql = "
            SELECT $col AS label, COUNT(*) AS qtd
            FROM leadV2 l
            WHERE l.dataUltForm_leadV2 IS NOT NULL AND l.dataUltForm_leadV2 > '0000-00-00'
              AND ({$periodo['sql']})
              {$filtros['sql']}{$classe}
              AND $col NOT IN ($placeholders)
            GROUP BY $col
            ORDER BY qtd DESC
            LIMIT " . (int)$limit . "
        ";
        $params = array_merge($periodo['params'], $filtros['params'], $excl);
        return array_map(
            fn($r) => ['label' => $r['label'], 'qtd' => (int)$r['qtd']],
            $this->queryAll($sql, $params)
        );
    }

    /** Top Motivos de Descarte — "10 anos ou +"/"10 anos ou mais" (tipo) aparecendo como
     *  linhas separadas em tempo de experiência é proposital: campo de texto livre no
     *  CRM, este ranking não normaliza grafia (ver plano). */
    private function topMotivosDescarte(array $f, int $limit): array
    {
        return $this->topPorColuna($f, 'l.motivoDescarte_leadV2', [], $limit);
    }

    /** Top Profissões — campo de texto livre no CRM (16 mil+ valores distintos medidos em
     *  produção); "-" é o placeholder de vazio observado além da própria string vazia. */
    private function topProfissoes(array $f, int $limit): array
    {
        return $this->topPorColuna($f, 'l.profissao_leadV2', ['-'], $limit);
    }

    private function topTempoExperiencia(array $f, int $limit): array
    {
        return $this->topPorColuna($f, 'l.tempoExp_leadV2', ['-'], $limit);
    }

    /**
     * Distribuição COMPLETA (não Top-N) por nível de formação acadêmica —
     * mesma população/filtros dos rankings acima. Branco vira rótulo
     * "(não informado)" em vez de descartado — mesma convenção de
     * chaveDim()/montarTabelaDimensao() pras tabelas de breakdown da 1ª view.
     */
    private function distribuicaoFormacao(array $f): array
    {
        $periodo = $this->buildPeriodFilter('l.dataUltForm_leadV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $filtros = $this->filtrosLead($f);
        $classe  = $this->filtroFormClasseLead($f);

        $sql = "
            SELECT l.formacaoAcademica_leadV2 AS label, COUNT(*) AS qtd
            FROM leadV2 l
            WHERE l.dataUltForm_leadV2 IS NOT NULL AND l.dataUltForm_leadV2 > '0000-00-00'
              AND ({$periodo['sql']})
              {$filtros['sql']}{$classe}
            GROUP BY l.formacaoAcademica_leadV2
            ORDER BY qtd DESC
        ";
        $params = array_merge($periodo['params'], $filtros['params']);
        return array_map(
            fn($r) => ['label' => $r['label'] === '' ? '(não informado)' : $r['label'], 'qtd' => (int)$r['qtd']],
            $this->queryAll($sql, $params)
        );
    }

    /**
     * Base diária do Comercial 1. A mesma consulta alimenta total de leads,
     * qualificados, quantidade descartada e a distribuição por motivo.
     */
    private function descartesLeadPorDia(array $f): array
    {
        $periodo = $this->buildPeriodFilter('l.dataUltForm_leadV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $filtros = $this->filtrosLead($f);
        $condCom1 = "l.etapa_leadV2 = 'Lead Descartado' AND LOWER(TRIM(l.tipoLead_leadV2)) <> 'otr'";

        $sql = "
            SELECT
                DATE(l.dataUltForm_leadV2) AS dia,
                CASE WHEN {$condCom1} THEN TRIM(l.motivoDescarte_leadV2) ELSE '' END AS motivo_com1,
                COUNT(*) AS total_leads,
                SUM(CASE WHEN l.qualificado_leadV2 = 'Sim' THEN 1 ELSE 0 END) AS qualificados,
                SUM(CASE WHEN {$condCom1} THEN 1 ELSE 0 END) AS descarte_com1
            FROM leadV2 l
            WHERE l.dataUltForm_leadV2 IS NOT NULL
              AND l.dataUltForm_leadV2 > '0000-00-00'
              AND ({$periodo['sql']})
              {$filtros['sql']}
            GROUP BY DATE(l.dataUltForm_leadV2), motivo_com1
            ORDER BY dia
        ";

        $dias = [];
        $motivos = [];
        $totais = ['total_leads' => 0, 'qualificados' => 0, 'descarte_com1' => 0];
        foreach ($this->queryAll($sql, array_merge($periodo['params'], $filtros['params'])) as $r) {
            $dia = $r['dia'];
            if (!isset($dias[$dia])) {
                $dias[$dia] = ['total_leads' => 0, 'qualificados' => 0, 'descarte_com1' => 0];
            }

            $qtdTotal = (int)$r['total_leads'];
            $qtdQualificados = (int)$r['qualificados'];
            $qtdDescarte = (int)$r['descarte_com1'];
            $dias[$dia]['total_leads'] += $qtdTotal;
            $dias[$dia]['qualificados'] += $qtdQualificados;
            $dias[$dia]['descarte_com1'] += $qtdDescarte;
            $totais['total_leads'] += $qtdTotal;
            $totais['qualificados'] += $qtdQualificados;
            $totais['descarte_com1'] += $qtdDescarte;

            if ($qtdDescarte > 0) {
                $motivo = trim((string)$r['motivo_com1']);
                $motivo = $motivo === '' ? '(não informado)' : $motivo;
                $motivos[$motivo] = ($motivos[$motivo] ?? 0) + $qtdDescarte;
            }
        }

        return ['dias' => $dias, 'motivos' => $motivos, 'totais' => $totais];
    }

    /**
     * Bases diárias dos Comerciais 2 e 3, ambas ancoradas em dataUltForm.
     * Com2 usa motivoDescarteComercial2; Com3 usa motivoDescarte e pipe C1:.
     */
    private function descartesDealPorDia(array $f): array
    {
        $periodo = $this->buildPeriodFilter('d.dataUltForm_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $filtros = $this->filtrosDeal($f);
        $condCom2 = "TRIM(d.motivoDescarteComercial2_dealV2) <> ''";
        $condCom3 = "TRIM(d.motivoDescarte_dealV2) <> '' AND LEFT(d.faseId_dealV2, 3) = 'C1:'";

        $sql = "
            SELECT
                DATE(d.dataUltForm_dealV2) AS dia,
                TRIM(d.motivoDescarteComercial2_dealV2) AS motivo_com2,
                TRIM(d.motivoDescarte_dealV2) AS motivo_com3,
                SUM(CASE WHEN {$condCom2} THEN 1 ELSE 0 END) AS descarte_com2,
                SUM(CASE WHEN {$condCom3} THEN 1 ELSE 0 END) AS descarte_com3
            FROM dealV2 d
            WHERE d.dataUltForm_dealV2 IS NOT NULL
              AND d.dataUltForm_dealV2 > '0000-00-00'
              AND ({$periodo['sql']})
              {$filtros['sql']}
              AND (({$condCom2}) OR ({$condCom3}))
            GROUP BY DATE(d.dataUltForm_dealV2), motivo_com2, motivo_com3
            ORDER BY dia
        ";

        $dias = [];
        $motivosCom2 = [];
        $motivosCom3 = [];
        $totais = ['descarte_com2' => 0, 'descarte_com3' => 0];
        foreach ($this->queryAll($sql, array_merge($periodo['params'], $filtros['params'])) as $r) {
            $dia = $r['dia'];
            if (!isset($dias[$dia])) {
                $dias[$dia] = ['descarte_com2' => 0, 'descarte_com3' => 0];
            }

            $qtdCom2 = (int)$r['descarte_com2'];
            $qtdCom3 = (int)$r['descarte_com3'];
            $dias[$dia]['descarte_com2'] += $qtdCom2;
            $dias[$dia]['descarte_com3'] += $qtdCom3;
            $totais['descarte_com2'] += $qtdCom2;
            $totais['descarte_com3'] += $qtdCom3;

            if ($qtdCom2 > 0) {
                $motivo = trim((string)$r['motivo_com2']);
                $motivosCom2[$motivo] = ($motivosCom2[$motivo] ?? 0) + $qtdCom2;
            }
            if ($qtdCom3 > 0) {
                $motivo = trim((string)$r['motivo_com3']);
                $motivosCom3[$motivo] = ($motivosCom3[$motivo] ?? 0) + $qtdCom3;
            }
        }

        return [
            'dias' => $dias,
            'motivos_com2' => $motivosCom2,
            'motivos_com3' => $motivosCom3,
            'totais' => $totais,
        ];
    }

    /**
     * Denominador de %Com2 por dataVenda (USERELATIONSHIP do PBI) e receita
     * de cadeiras exibida no KPI executivo.
     */
    private function consultasVendidasPorDiaDescartes(array $f): array
    {
        $periodo = $this->buildPeriodFilter('d.dataVenda_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $filtros = $this->filtrosDeal($f);
        $sql = "
            SELECT
                DATE(d.dataVenda_dealV2) AS dia,
                SUM(CASE WHEN d.faseId_dealV2 IN ('Won', 'C11:WON') THEN 1 ELSE 0 END) AS consultas_vendidas,
                SUM(CASE WHEN d.faseId_dealV2 = 'C9:Won' THEN COALESCE(d.valorVendaDolar_dealV2, 0) ELSE 0 END) AS faturamento_cadeiras
            FROM dealV2 d
            WHERE d.dataVenda_dealV2 IS NOT NULL
              AND d.dataVenda_dealV2 > '0000-00-00'
              AND ({$periodo['sql']})
              {$filtros['sql']}
              AND d.faseId_dealV2 IN ('Won', 'C11:WON', 'C9:Won')
            GROUP BY DATE(d.dataVenda_dealV2)
            ORDER BY dia
        ";

        $dias = [];
        $faturamentoCadeiras = 0.0;
        foreach ($this->queryAll($sql, array_merge($periodo['params'], $filtros['params'])) as $r) {
            $dias[$r['dia']] = (int)$r['consultas_vendidas'];
            $faturamentoCadeiras += (float)$r['faturamento_cadeiras'];
        }
        return ['dias' => $dias, 'faturamento_cadeiras' => $faturamentoCadeiras];
    }

    private function ordenarMotivosDescarte(array $motivos): array
    {
        arsort($motivos, SORT_NUMERIC);
        $saida = [];
        foreach ($motivos as $label => $qtd) {
            $saida[] = ['label' => $label, 'qtd' => (int)$qtd];
        }
        return $saida;
    }

    private function zeroBucketDescartes(): array
    {
        return [
            'total_leads' => 0,
            'qualificados' => 0,
            'descarte_com1' => 0,
            'descarte_com2' => 0,
            'descarte_com3' => 0,
            'consultas_vendidas' => 0,
        ];
    }

    private function somarBucketDescartes(array &$destino, array $origem): void
    {
        foreach ($this->zeroBucketDescartes() as $campo => $_) {
            $destino[$campo] += (int)($origem[$campo] ?? 0);
        }
    }

    private function finalizarBucketDescartes(string $label, array $valores, ?int $mes = null): array
    {
        $pct = static fn(int $num, int $den): float => $den > 0 ? $num / $den : 0.0;
        $linha = ['label' => $label];
        if ($mes !== null) {
            $linha['mes'] = $mes;
        }
        return $linha + $valores + [
            'pct_com1' => $pct($valores['descarte_com1'], $valores['qualificados']),
            'pct_com2' => $pct($valores['descarte_com2'], $valores['consultas_vendidas']),
            'pct_com3' => $pct($valores['descarte_com3'], $valores['descarte_com2']),
        ];
    }

    /** Mensal, semanal e diário derivados da mesma base diária reconciliada. */
    private function montarSeriesDescartes(array $porDia): array
    {
        $porMes = [];
        $porSemana = [];
        foreach ($porDia as $dia => $valores) {
            $ts = strtotime($dia);
            $chaveMes = date('Y-m', $ts);
            $chaveSemana = date('o', $ts) . '-' . date('W', $ts);
            $porMes[$chaveMes] = $porMes[$chaveMes] ?? $this->zeroBucketDescartes();
            $porSemana[$chaveSemana] = $porSemana[$chaveSemana] ?? $this->zeroBucketDescartes();
            $this->somarBucketDescartes($porMes[$chaveMes], $valores);
            $this->somarBucketDescartes($porSemana[$chaveSemana], $valores);
        }

        ksort($porMes);
        ksort($porSemana);
        ksort($porDia);

        $mensal = [];
        foreach ($porMes as $label => $valores) {
            $mensal[] = $this->finalizarBucketDescartes($label, $valores);
        }

        $semanal = [];
        foreach ($porSemana as $chave => $valores) {
            $partes = explode('-', $chave);
            $semanal[] = $this->finalizarBucketDescartes('S' . $partes[1], $valores);
        }

        $MESES = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $diario = [];
        foreach ($porDia as $dia => $valores) {
            $mes = (int)substr($dia, 5, 2);
            $label = (int)substr($dia, 8, 2) . ' ' . $MESES[$mes];
            $diario[] = $this->finalizarBucketDescartes($label, $valores, $mes);
        }

        return ['mensal' => $mensal, 'semanal' => $semanal, 'diario' => $diario];
    }

    private function zeroSeriePerformance(): array
    {
        return [
            'total_leads' => 0, 'qualificados' => 0, 'descartados' => 0,
            'cadeira_vendida' => 0, 'consultas_vendidas' => 0,
            'combos_vendidos' => 0, 'faturamento_combos' => 0.0, 'custo_mkt' => 0.0,
        ];
    }

    private function somaSeriePerformance(array &$acc, array $vals): void
    {
        foreach ($vals as $k => $v) {
            $acc[$k] += $v;
        }
    }

    /**
     * Séries Mensal/Semanal/Diário das 9 métricas do seletor. 4 buscas no
     * total (não 12 = 4 fontes × 3 granularidades): cada fonte é agrupada só
     * por DIA, e mês/semana são somados a partir dos MESMOS buckets diários
     * em PHP — garante que as 3 granularidades nunca "descasem" entre si, e
     * seguindo o checklist de performance do CLAUDE.md ("nenhuma mesma
     * query/cálculo caro executado 2×+ sem necessidade"). Cada fonte usa as
     * MESMAS condições de funilSelects()/leadsOportunidadesGlobalFormClasse(),
     * só trocando o SUM(CASE...) único por GROUP BY DATE(col).
     *
     * Diário mostra só o mês mais recente do período filtrado (dias 1..N) —
     * pedido explícito do usuário; Mensal/Semanal cobrem o período inteiro.
     *
     * Métricas devolvidas em bruto — %Qualificação/CPL/ROI são razões e
     * ficam pro front derivar por bucket (mesma filosofia de
     * seriesMensais(): "a média de razões não é a razão das médias").
     */
    private function seriePerformance(array $f): array
    {
        $porDia = [];
        $garante = function (string $dia) use (&$porDia) {
            if (!isset($porDia[$dia])) {
                $porDia[$dia] = $this->zeroSeriePerformance();
            }
        };

        // 1) leadV2 por dataUltForm_leadV2 — total_leads/qualificados/descartados
        $periodo = $this->buildPeriodFilter('l.dataUltForm_leadV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $filtros = $this->filtrosLead($f);
        $classe  = $this->filtroFormClasseLead($f);
        $sql = "
            SELECT DATE(l.dataUltForm_leadV2) AS dia,
                COUNT(*) AS total_leads,
                SUM(CASE WHEN l.qualificado_leadV2 = 'Sim' THEN 1 ELSE 0 END) AS qualificados,
                SUM(CASE WHEN l.motivoDescarte_leadV2 <> '' THEN 1 ELSE 0 END) AS descartados
            FROM leadV2 l
            WHERE l.dataUltForm_leadV2 IS NOT NULL AND l.dataUltForm_leadV2 > '0000-00-00'
              AND ({$periodo['sql']})
              {$filtros['sql']}{$classe}
            GROUP BY DATE(l.dataUltForm_leadV2)
        ";
        foreach ($this->queryAll($sql, array_merge($periodo['params'], $filtros['params'])) as $r) {
            $garante($r['dia']);
            $porDia[$r['dia']]['total_leads']  += (int)$r['total_leads'];
            $porDia[$r['dia']]['qualificados'] += (int)$r['qualificados'];
            $porDia[$r['dia']]['descartados']  += (int)$r['descartados'];
        }

        // 2) dealV2 por dataVenda_dealV2 — cadeira vendida + consultas vendidas
        $periodo = $this->buildPeriodFilter('d.dataVenda_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $filtros = $this->filtrosDeal($f);
        $classe  = $this->filtroFormClasseDeal($f);
        $sql = "
            SELECT DATE(d.dataVenda_dealV2) AS dia,
                SUM(CASE WHEN d.faseId_dealV2 = 'C9:Won' THEN 1 ELSE 0 END) AS cadeira_vendida,
                SUM(CASE WHEN d.faseId_dealV2 IN ('Won', 'C11:WON') THEN 1 ELSE 0 END) AS consultas_vendidas
            FROM dealV2 d
            WHERE d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
              AND ({$periodo['sql']})
              {$filtros['sql']}{$classe}
            GROUP BY DATE(d.dataVenda_dealV2)
        ";
        foreach ($this->queryAll($sql, array_merge($periodo['params'], $filtros['params'])) as $r) {
            $garante($r['dia']);
            $porDia[$r['dia']]['cadeira_vendida']    += (int)$r['cadeira_vendida'];
            $porDia[$r['dia']]['consultas_vendidas'] += (int)$r['consultas_vendidas'];
        }

        // 3) dealV2 por dataContratoComboAssinado_dealV2 — combos + faturamento
        $periodo = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $filtros = $this->filtrosDeal($f);
        $classe  = $this->filtroFormClasseDeal($f);
        $sql = "
            SELECT DATE(d.dataContratoComboAssinado_dealV2) AS dia,
                SUM(CASE WHEN d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON') THEN 1 ELSE 0 END) AS combos_vendidos,
                SUM(CASE WHEN d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON')
                         THEN COALESCE(d.valorComboLiquido_dealV2, 0) ELSE 0 END) AS faturamento_combos
            FROM dealV2 d
            WHERE d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
              AND ({$periodo['sql']})
              {$filtros['sql']}{$classe}
            GROUP BY DATE(d.dataContratoComboAssinado_dealV2)
        ";
        foreach ($this->queryAll($sql, array_merge($periodo['params'], $filtros['params'])) as $r) {
            $garante($r['dia']);
            $porDia[$r['dia']]['combos_vendidos']    += (int)$r['combos_vendidos'];
            $porDia[$r['dia']]['faturamento_combos'] += (float)$r['faturamento_combos'];
        }

        // 4) custoMkt por data_custoMkt — mesma regra bimodal de custoMktGlobalFormClasse()
        $periodo = $this->buildPeriodFilter('cm.data_custoMkt', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $filtros = $this->filtrosCusto($f);
        $classe  = $this->filtroFormClasseCusto($f);
        $sql = "
            SELECT DATE(cm.data_custoMkt) AS dia,
                SUM(CASE
                    WHEN YEAR(cm.data_custoMkt) = 2025 AND cm.origem_custoMkt = 'meta' THEN cm.valor_custoMkt
                    WHEN YEAR(cm.data_custoMkt) = 2026 AND cm.conta_custoMkt IN ('Dollar 1', 'Dollar 2') THEN cm.valor_custoMkt
                    ELSE 0
                END) AS custo_dollar,
                SUM(CASE
                    WHEN YEAR(cm.data_custoMkt) = 2025 AND cm.origem_custoMkt = 'google' THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio, 0)
                    WHEN YEAR(cm.data_custoMkt) = 2026 AND cm.conta_custoMkt <> 'Dollar 1' AND cm.conta_custoMkt <> 'Dollar 2' THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio, 0)
                    ELSE 0
                END) AS custo_real
            FROM custoMkt cm
            LEFT JOIN (SELECT data_cambio, MAX(valor_cambio) AS valor_cambio FROM cambio GROUP BY data_cambio) ca
                ON ca.data_cambio = cm.data_custoMkt
            WHERE {$periodo['sql']} {$filtros['sql']}{$classe}
            GROUP BY DATE(cm.data_custoMkt)
        ";
        foreach ($this->queryAll($sql, array_merge($periodo['params'], $filtros['params'])) as $r) {
            $garante($r['dia']);
            $porDia[$r['dia']]['custo_mkt'] += (float)$r['custo_dollar'] + (float)$r['custo_real'];
        }

        ksort($porDia);

        // Agrega em PHP pra mês/semana a partir dos MESMOS buckets diários.
        $porMes = $porSemana = [];
        $mesOrdem = $semanaOrdem = [];
        foreach ($porDia as $dia => $vals) {
            $ts = strtotime($dia);

            $chaveMes = date('Y-m', $ts);
            if (!isset($porMes[$chaveMes])) {
                $porMes[$chaveMes] = $this->zeroSeriePerformance();
                $mesOrdem[] = $chaveMes;
            }
            $this->somaSeriePerformance($porMes[$chaveMes], $vals);

            // date('o','W') = ano/semana ISO-8601 (segunda-feira início, semana 1 = a
            // que contém a 1ª quinta-feira do ano) — mesma convenção usada no mock.
            $chaveSemana = date('o', $ts) . '-' . date('W', $ts);
            if (!isset($porSemana[$chaveSemana])) {
                $porSemana[$chaveSemana] = $this->zeroSeriePerformance();
                $semanaOrdem[] = $chaveSemana;
            }
            $this->somaSeriePerformance($porSemana[$chaveSemana], $vals);
        }

        $MESES = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        $mensal = [];
        foreach ($mesOrdem as $chave) {
            $mensal[] = ['label' => $MESES[(int)substr($chave, 5, 2)]] + $porMes[$chave];
        }

        $semanal = [];
        foreach ($semanaOrdem as $chave) {
            $semana = explode('-', $chave)[1];
            $semanal[] = ['label' => 'S' . str_pad($semana, 2, '0', STR_PAD_LEFT)] + $porSemana[$chave];
        }

        // Diário cobre o PERÍODO INTEIRO filtrado — mesmo comportamento de
        // Mensal/Semanal, não só o mês mais recente (correção: sem filtro de
        // mês, "sem filtro" tem que valer pro ano inteiro, não só o último
        // mês com dado). `mes` (1-12) vai junto só pro front decidir como
        // desenhar o eixo: dia puro 1..31 quando o período cai num único mês,
        // ou 1 marca por virada de mês quando cobre vários (ver
        // renderTimeChart() em paginas/marketing/desempenho.php).
        $diario = [];
        foreach ($porDia as $dia => $vals) {
            $mes = (int)substr($dia, 5, 2);
            $diario[] = ['label' => (int)substr($dia, 8, 2) . ' ' . $MESES[$mes], 'mes' => $mes] + $vals;
        }

        return ['mensal' => $mensal, 'semanal' => $semanal, 'diario' => $diario];
    }
}
