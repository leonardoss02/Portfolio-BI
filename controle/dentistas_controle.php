<?php

/**
 * Dashboard Dentistas — visão comercial do segmento de clientes dentistas.
 *
 * Segmento novo (form de captação e funil de vendas dedicados desde
 * 31/08/2026). Mesmo padrão dos demais dashboards: toda a lógica de
 * SQL/cálculo vive em métodos privados desta classe, e os helpers (cache,
 * filtros, JSON) são cópia própria do controller — sem camada compartilhada
 * (convenção do projeto, ver memória cache-copia-por-controller). Espinha
 * dorsal copiada de controle/comercial_controle.php.
 */
class dentistas extends MRPHP
{
    /**
     * Prefixo do funil C33 (pipe exclusivo de Dentistas em dealV2). Usar
     * LIKE, NUNCA LEFT(faseId_dealV2, 2) = 'C3' — colide com C3/C31/C35/
     * C37/C39, todos pipes distintos que também começam por "C3" (confirmado
     * via /sql/query em produção).
     */
    private const SQL_FUNIL_DENTISTA = "'C33:%'";

    /**
     * faseId_dealV2 de "Contrato Assinado" no funil C33. Ainda não existe
     * nenhum deal nessa fase hoje (só C33:NEW), mas a condição já é real —
     * literal embutido na query (não é input do usuário, não precisa de
     * bind_param), mesmo estilo de SQL_FUNIL_DENTISTA.
     */
    private const SQL_FASE_CONTRATO_C33 = "'C33:WON'";

    /** Base do link direto pro Bitrix24 — padrão do projeto (ver controle/consultacom2_controle.php). */
    private const BITRIX_BASE = 'https://exemplo.bitrix24.com.br/crm/deal/details/';

    public function index()
    {
        $this->setTemplate('dentistas');
    }

    public function dicionario()
    {
        $this->setTemplate('dentistas');
    }

    // ===================================================================
    // ENDPOINT JSON
    // ===================================================================

    /** GET /dentistas/eventos — eventos distintos já usados no funil C33 (dropdown de busca). */
    public function eventos()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('dentistas_eventos');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DROPDOWN);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $sql = "
                SELECT DISTINCT d.dentistaEvento_dealV2 AS evento
                FROM dealV2 d
                WHERE d.faseId_dealV2 LIKE " . self::SQL_FUNIL_DENTISTA . "
                  AND d.dentistaEvento_dealV2 IS NOT NULL AND d.dentistaEvento_dealV2 != ''
                ORDER BY evento
            ";
            $data = array_column($this->queryAll($sql), 'evento');
            $this->cacheSet($cacheKey, $data);
            $this->jsonOut($data);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /dentistas/utmterms — UTM Terms (anúncio) distintos já usados no funil C33 (dropdown de busca). */
    public function utmterms()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('dentistas_utmterms');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DROPDOWN);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $sql = "
                SELECT DISTINCT d.utmTerm_dealV2 AS utm_term
                FROM dealV2 d
                WHERE d.faseId_dealV2 LIKE " . self::SQL_FUNIL_DENTISTA . "
                  AND d.utmTerm_dealV2 IS NOT NULL AND d.utmTerm_dealV2 != ''
                ORDER BY utm_term
            ";
            $data = array_column($this->queryAll($sql), 'utm_term');
            $this->cacheSet($cacheKey, $data);
            $this->jsonOut($data);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /dentistas/profissoes — profissões distintas já usadas no funil C33 (dropdown de busca). */
    public function profissoes()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('dentistas_profissoes');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DROPDOWN);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $sql = "
                SELECT DISTINCT d.profissao_dealV2 AS profissao
                FROM dealV2 d
                WHERE d.faseId_dealV2 LIKE " . self::SQL_FUNIL_DENTISTA . "
                  AND d.profissao_dealV2 IS NOT NULL AND d.profissao_dealV2 != ''
                ORDER BY profissao
            ";
            $data = array_column($this->queryAll($sql), 'profissao');
            $this->cacheSet($cacheKey, $data);
            $this->jsonOut($data);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /dentistas/formacoes — formações acadêmicas distintas já usadas no funil C33 (dropdown de busca). */
    public function formacoes()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('dentistas_formacoes');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DROPDOWN);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $sql = "
                SELECT DISTINCT d.formacaoAcademica_dealV2 AS formacao
                FROM dealV2 d
                WHERE d.faseId_dealV2 LIKE " . self::SQL_FUNIL_DENTISTA . "
                  AND d.formacaoAcademica_dealV2 IS NOT NULL AND d.formacaoAcademica_dealV2 != ''
                ORDER BY formacao
            ";
            $data = array_column($this->queryAll($sql), 'formacao');
            $this->cacheSet($cacheKey, $data);
            $this->jsonOut($data);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /dentistas/dados — KPIs, funil e linhas do funil C33 no período. */
    public function dados()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('dentistas_dados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f     = $this->lerFiltros();
            $deals = $this->calcularDeals($f);

            $payload = [
                'meta' => $f + [
                    'generated' => date('Y-m-d H:i:s'),
                ],
                'kpis' => [
                    'leads'                => $deals['leads'],
                    'consultas_realizadas' => $deals['consultas_realizadas'],
                    'cadeira_vendida'      => $deals['cadeira_vendida'],
                    'contrato_assinado'    => $deals['contrato_assinado'],
                ],
                // Ordem confirmada pelo usuário: confirmados no evento vem
                // antes da consulta neste segmento (diferente do funil
                // Executive/Comercial). Sem etapa de "Qualificados": a fonte
                // de lead agora é o próprio pipe C33 (dealV2), não leadV2 —
                // todo card que entra no pipe já é considerado qualificado.
                'funil' => [
                    ['etapa' => 'Leads',                 'valor' => $deals['leads']],
                    ['etapa' => 'Confirmados no Evento',  'valor' => $deals['cadeira_vendida']],
                    ['etapa' => 'Consultas Realizadas',   'valor' => $deals['consultas_realizadas']],
                    ['etapa' => 'Contrato Assinado',      'valor' => $deals['contrato_assinado']],
                ],
                'tempo'        => $this->calcularSerieTempo($f),
                'agrupamentos' => $this->calcularAgrupamentos($f),
                'linhas'       => $this->calcularLinhasDeals($f),
            ];

            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    // ===================================================================
    // CÁLCULO: dealV2 (funil C33 — leads, cadeira, consulta realizada, contrato)
    // ===================================================================

    /**
     * "Leads" deixou de vir de leadV2: a fonte agora é o próprio pipe C33 —
     * todo card que entra no pipe já conta como lead (e é considerado
     * qualificado, por isso não há mais etapa separada de "Qualificados").
     * Coluna de período é dataUltForm_dealV2, mesma já usada por
     * "Confirmados no Evento" — por isso as duas saem da mesma query/CASE.
     */
    private function calcularDeals(array $f): array
    {
        $pLeads = $this->buildPeriodFilter('d.dataUltForm_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pReal  = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pCombo = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $evento    = $this->buildEventoFilter($f['evento']);
        $utmTerm   = $this->buildUtmTermFilter($f['utm_term']);
        $profissao = $this->buildProfissaoFilter($f['profissao']);
        $formacao  = $this->buildFormacaoFilter($f['formacao']);

        $sql = "
            SELECT
                SUM(CASE WHEN d.dataUltForm_dealV2 IS NOT NULL AND d.dataUltForm_dealV2 > '0000-00-00'
                         AND ({$pLeads['sql']}) THEN 1 ELSE 0 END) AS leads,
                SUM(CASE WHEN d.checkin_dealV2 = '1'
                         AND d.dataUltForm_dealV2 IS NOT NULL AND d.dataUltForm_dealV2 > '0000-00-00'
                         AND ({$pLeads['sql']}) THEN 1 ELSE 0 END) AS cadeira_vendida,
                SUM(CASE WHEN d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
                         AND ({$pReal['sql']}) THEN 1 ELSE 0 END) AS consultas_realizadas,
                SUM(CASE WHEN d.faseId_dealV2 = " . self::SQL_FASE_CONTRATO_C33 . "
                         AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
                         AND ({$pCombo['sql']}) THEN 1 ELSE 0 END) AS contrato_assinado
            FROM dealV2 d
            WHERE d.faseId_dealV2 LIKE " . self::SQL_FUNIL_DENTISTA . "
              {$evento['sql']}
              {$utmTerm['sql']}
              {$profissao['sql']}
              {$formacao['sql']}
        ";

        $params = array_merge($pLeads['params'], $pLeads['params'], $pReal['params'], $pCombo['params'], $evento['params'], $utmTerm['params'], $profissao['params'], $formacao['params']);
        $row    = $this->queryOne($sql, $params);

        return [
            'leads'                => (int)($row['leads'] ?? 0),
            'cadeira_vendida'      => (int)($row['cadeira_vendida'] ?? 0),
            'consultas_realizadas' => (int)($row['consultas_realizadas'] ?? 0),
            'contrato_assinado'    => (int)($row['contrato_assinado'] ?? 0),
        ];
    }

    /**
     * Série temporal das 4 métricas, para o gráfico de evolução (seletor de
     * métrica no front escolhe qual delas exibir — os dados das 4 já vêm
     * juntos no mesmo payload, sem round-trip extra ao trocar de métrica).
     * Grão (mês/semana/dia) vem de $f['granularidade'], mesmo toggle e
     * mesmos helpers exprAgrupamentoPeriodo()/labelPeriodo() de
     * comercial_controle.php.
     *
     * Leads e Confirmados no Evento compartilham a mesma coluna de data
     * (dataUltForm_dealV2) — 1 query com 2 SUM(CASE). As outras 2 métricas
     * têm cada uma sua própria coluna de data — 1 query por métrica,
     * pivotadas por período em PHP.
     */
    private function calcularSerieTempo(array $f): array
    {
        $evento    = $this->buildEventoFilter($f['evento']);
        $utmTerm   = $this->buildUtmTermFilter($f['utm_term']);
        $profissao = $this->buildProfissaoFilter($f['profissao']);
        $formacao  = $this->buildFormacaoFilter($f['formacao']);

        $grLeads = $this->exprAgrupamentoPeriodo($f['granularidade'], 'd.dataUltForm_dealV2');
        $pLeads  = $this->buildPeriodFilter('d.dataUltForm_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $sqlLeads = "
            SELECT
                $grLeads AS periodo,
                COUNT(*) AS leads,
                SUM(CASE WHEN d.checkin_dealV2 = '1' THEN 1 ELSE 0 END) AS cadeira_vendida
            FROM dealV2 d
            WHERE d.faseId_dealV2 LIKE " . self::SQL_FUNIL_DENTISTA . "
              AND d.dataUltForm_dealV2 IS NOT NULL AND d.dataUltForm_dealV2 > '0000-00-00'
              AND ({$pLeads['sql']})
              {$evento['sql']}
              {$utmTerm['sql']}
              {$profissao['sql']}
              {$formacao['sql']}
            GROUP BY periodo
        ";
        $rowsLeads = $this->queryAll($sqlLeads, array_merge($pLeads['params'], $evento['params'], $utmTerm['params'], $profissao['params'], $formacao['params']));

        $grReal = $this->exprAgrupamentoPeriodo($f['granularidade'], 'd.dataConsultaRealizada_dealV2');
        $pReal = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $sqlReal = "
            SELECT $grReal AS periodo, COUNT(*) AS qtd
            FROM dealV2 d
            WHERE d.faseId_dealV2 LIKE " . self::SQL_FUNIL_DENTISTA . "
              AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
              AND ({$pReal['sql']})
              {$evento['sql']}
              {$utmTerm['sql']}
              {$profissao['sql']}
              {$formacao['sql']}
            GROUP BY periodo
        ";
        $rowsReal = $this->queryAll($sqlReal, array_merge($pReal['params'], $evento['params'], $utmTerm['params'], $profissao['params'], $formacao['params']));

        $grCombo = $this->exprAgrupamentoPeriodo($f['granularidade'], 'd.dataContratoComboAssinado_dealV2');
        $pCombo = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $sqlCombo = "
            SELECT $grCombo AS periodo, COUNT(*) AS qtd
            FROM dealV2 d
            WHERE d.faseId_dealV2 = " . self::SQL_FASE_CONTRATO_C33 . "
              AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
              AND ({$pCombo['sql']})
              {$evento['sql']}
              {$utmTerm['sql']}
              {$profissao['sql']}
              {$formacao['sql']}
            GROUP BY periodo
        ";
        $rowsCombo = $this->queryAll($sqlCombo, array_merge($pCombo['params'], $evento['params'], $utmTerm['params'], $profissao['params'], $formacao['params']));

        $porPeriodo = [];
        foreach ($rowsLeads as $r) {
            $p = (string)$r['periodo'];
            if ($p === '') {
                continue;
            }
            $porPeriodo[$p]['leads']           = (int)$r['leads'];
            $porPeriodo[$p]['cadeira_vendida'] = (int)$r['cadeira_vendida'];
        }
        foreach ($rowsReal as $r) {
            $p = (string)$r['periodo'];
            if ($p === '') {
                continue;
            }
            $porPeriodo[$p]['consultas_realizadas'] = (int)$r['qtd'];
        }
        foreach ($rowsCombo as $r) {
            $p = (string)$r['periodo'];
            if ($p === '') {
                continue;
            }
            $porPeriodo[$p]['contrato_assinado'] = (int)$r['qtd'];
        }
        ksort($porPeriodo);

        $serie = [];
        foreach ($porPeriodo as $p => $v) {
            $serie[] = [
                'mes'                  => $this->labelPeriodo($f['granularidade'], $p),
                'leads'                => $v['leads'] ?? 0,
                'cadeira_vendida'      => $v['cadeira_vendida'] ?? 0,
                'consultas_realizadas' => $v['consultas_realizadas'] ?? 0,
                'contrato_assinado'    => $v['contrato_assinado'] ?? 0,
            ];
        }
        return $serie;
    }

    /**
     * Leads agrupados por Anúncio (utmTerm_dealV2) e por Evento
     * (dentistaEvento_dealV2) — mesma base e mesmos filtros (período, evento,
     * utm_term) do KPI "Leads": pipe C33, coluna dataUltForm_dealV2.
     * Cardinalidade baixa hoje (poucas dezenas de valores distintos),
     * sem paginação/top-N.
     */
    /**
     * Cross-filter com auto-exclusão: a tabela "por Anúncio" nunca aplica o
     * próprio filtro de UTM Term nela mesma (senão clicar numa linha faria
     * as outras desaparecerem em vez de só ficar em destaque) — mas
     * continua respondendo a Evento/Profissão/Formação/período. Mesma
     * lógica espelhada em "por Evento". Ver front (renderAgrupamento() em
     * paginas/dentistas/index.php) para o toggle de seleção por clique.
     */
    private function calcularAgrupamentos(array $f): array
    {
        $periodo   = $this->buildPeriodFilter('d.dataUltForm_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $evento    = $this->buildEventoFilter($f['evento']);
        $utmTerm   = $this->buildUtmTermFilter($f['utm_term']);
        $profissao = $this->buildProfissaoFilter($f['profissao']);
        $formacao  = $this->buildFormacaoFilter($f['formacao']);

        $baseWhere = "
            WHERE d.faseId_dealV2 LIKE " . self::SQL_FUNIL_DENTISTA . "
              AND d.dataUltForm_dealV2 IS NOT NULL AND d.dataUltForm_dealV2 > '0000-00-00'
              AND ({$periodo['sql']})
              {$profissao['sql']}
              {$formacao['sql']}
        ";
        $baseParams = array_merge($periodo['params'], $profissao['params'], $formacao['params']);

        $sqlAnuncio  = "SELECT d.utmTerm_dealV2 AS grupo, COUNT(*) AS qtd FROM dealV2 d $baseWhere {$evento['sql']} GROUP BY grupo ORDER BY qtd DESC";
        $rowsAnuncio = $this->queryAll($sqlAnuncio, array_merge($baseParams, $evento['params']));

        $sqlEvento  = "SELECT d.dentistaEvento_dealV2 AS grupo, COUNT(*) AS qtd FROM dealV2 d $baseWhere {$utmTerm['sql']} GROUP BY grupo ORDER BY qtd DESC";
        $rowsEvento = $this->queryAll($sqlEvento, array_merge($baseParams, $utmTerm['params']));

        // 'valor' preserva o dado bruto (pode ser '') pro front usar como
        // chave de toggle/seleção — 'grupo' é só o rótulo de exibição.
        $mapear = fn(array $rows) => array_map(fn($r) => [
            'grupo' => ($r['grupo'] === null || $r['grupo'] === '') ? '(não informado)' : $r['grupo'],
            'valor' => (string)($r['grupo'] ?? ''),
            'qtd'   => (int)$r['qtd'],
        ], $rows);

        return [
            'por_anuncio' => $mapear($rowsAnuncio),
            'por_evento'  => $mapear($rowsEvento),
        ];
    }

    /**
     * Linhas do funil C33 para a tabela da tela — volume ínfimo hoje (poucas
     * dezenas de deals). Coluna "Data Último Form" (dataUltForm_dealV2) no
     * lugar de "Criado em" — mesma coluna usada como fonte de "Leads" no
     * restante do dashboard, e mesmo filtro de período (Data/Ano/Mês) que os
     * KPIs/funil/gráfico/agrupamentos já aplicam.
     */
    private function calcularLinhasDeals(array $f): array
    {
        $periodo   = $this->buildPeriodFilter('d.dataUltForm_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $evento    = $this->buildEventoFilter($f['evento']);
        $utmTerm   = $this->buildUtmTermFilter($f['utm_term']);
        $profissao = $this->buildProfissaoFilter($f['profissao']);
        $formacao  = $this->buildFormacaoFilter($f['formacao']);

        $sql = "
            SELECT
                d.idBitrix_dealV2                 AS id_bitrix,
                d.nome_dealV2                       AS nome,
                d.fase_dealV2                        AS fase,
                d.dentistaEvento_dealV2              AS evento,
                d.profissao_dealV2                  AS profissao,
                d.formacaoAcademica_dealV2          AS formacao,
                d.dataConsultaRealizada_dealV2      AS data_consulta_realizada,
                d.dataContratoComboAssinado_dealV2  AS data_contrato_assinado,
                d.dataUltForm_dealV2                AS data_ult_form
            FROM dealV2 d
            WHERE d.faseId_dealV2 LIKE " . self::SQL_FUNIL_DENTISTA . "
              AND ({$periodo['sql']})
              {$evento['sql']}
              {$utmTerm['sql']}
              {$profissao['sql']}
              {$formacao['sql']}
            ORDER BY d.dataUltForm_dealV2 DESC
            LIMIT 500
        ";

        $rows = $this->queryAll($sql, array_merge($periodo['params'], $evento['params'], $utmTerm['params'], $profissao['params'], $formacao['params']));

        return array_map(fn($r) => [
            'id_bitrix'               => $r['id_bitrix'],
            'nome'                    => $r['nome'],
            'fase'                    => $r['fase'],
            'evento'                  => $r['evento'],
            'profissao'               => $r['profissao'],
            'formacao'                => $r['formacao'],
            'data_consulta_realizada' => $this->fmtDataOuNull($r['data_consulta_realizada']),
            'data_contrato_assinado'  => $this->fmtDataOuNull($r['data_contrato_assinado']),
            'data_ult_form'           => $this->fmtDataOuNull($r['data_ult_form']),
            'link_bitrix'             => self::BITRIX_BASE . $r['id_bitrix'] . '/',
        ], $rows);
    }

    private function fmtDataOuNull(?string $v): ?string
    {
        $dataParte = substr((string)$v, 0, 10);
        if ($v === null || $v === '' || $dataParte <= '0000-00-00') {
            return null;
        }
        return $this->data($dataParte);
    }

    // ===================================================================
    // HELPERS: FILTROS (cópia de comercial_controle.php, reduzida ao
    // subconjunto necessário — este MVP não tem filtro de dimensão
    // selecionável, tipoLead='Dentista' é fixo no código)
    // ===================================================================

    private function lerFiltros(): array
    {
        $granularidade = $this->g('granularidade');
        return [
            'year'          => $this->g('year'),
            'months'        => $this->g('months'),
            'data_inicio'   => $this->g('data_inicio'),
            'data_fim'      => $this->g('data_fim'),
            'evento'        => $this->g('evento'),
            'utm_term'      => $this->g('utm_term'),
            'profissao'     => $this->g('profissao'),
            'formacao'      => $this->g('formacao'),
            // A URL só restringe: um valor fora da whitelist cai no default
            // 'mes' em vez de propagar lixo pro gráfico de evolução.
            'granularidade' => in_array($granularidade, ['semana', 'dia'], true) ? $granularidade : 'mes',
            // TODO: filtro de responsável, quando houver volume que justifique.
        ];
    }

    /**
     * Grão de agrupamento do gráfico de evolução — mesmo padrão de
     * comercial_controle.php::exprAgrupamentoPeriodo(). 'dia' é data
     * completa (não dia-do-mês agregado entre meses diferentes).
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

    /** Filtro de Evento: multi-select sobre valores brutos de dentistaEvento_dealV2. */
    private function buildEventoFilter(?string $csv): array
    {
        return $this->buildInFilter('d.dentistaEvento_dealV2', $csv);
    }

    /** Filtro de UTM Term (Anúncio): multi-select sobre valores brutos de utmTerm_dealV2. */
    private function buildUtmTermFilter(?string $csv): array
    {
        return $this->buildInFilter('d.utmTerm_dealV2', $csv);
    }

    /** Filtro de Profissão: multi-select sobre valores brutos de profissao_dealV2. */
    private function buildProfissaoFilter(?string $csv): array
    {
        return $this->buildInFilter('d.profissao_dealV2', $csv);
    }

    /** Filtro de Formação Acadêmica: multi-select sobre valores brutos de formacaoAcademica_dealV2. */
    private function buildFormacaoFilter(?string $csv): array
    {
        return $this->buildInFilter('d.formacaoAcademica_dealV2', $csv);
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
    // projeto (mesmo bloco de executive/rafaeltorres/comercial).
    // ===================================================================

    private const CACHE_TTL_DADOS = 600;
    private const CACHE_TTL_DROPDOWN = 600;

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
