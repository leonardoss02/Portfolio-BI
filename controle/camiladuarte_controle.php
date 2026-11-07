<?php

/**
 * Dashboard exclusivo da vendedora Camila Duarte — MRPHP.
 *
 * Mesmo padrão de eventos_controle.php/financeiro_controle.php (sem camada
 * calculos/ — a documentada no CLAUDE.md não é usada por nenhum dos 4
 * dashboards reais do projeto): toda a lógica de SQL/cálculo fica em
 * métodos privados desta classe.
 *
 * Todas as queries são sempre restritas a responsavel_dealV2 = 'Camila Duarte'
 * (bind_param, nunca interpolado) + filtro opcional de Form.
 */
class camiladuarte extends MRPHP
{
    private const RESPONSAVEL = 'Camila Duarte';

    /** faseId_dealV2 aceitas como "combo vendido" — fórmula já conhecida do projeto (ver executive_controle.php). */
    private const SQL_FASES_COMBO = "('C1:WON', 'C5:WON', 'C29:won', 'C31:WON')";

    /** fase_dealV2 (texto livre) que excluem um deal de "Fp Realizado". */
    private const SQL_FASES_FORA_FP = "('Comercial 3', 'Distrato', 'Lead sem qualificação')";

    /** Base do link direto pro deal no Bitrix (ver consultacom2_controle.php). */
    private const BITRIX_BASE = 'https://exemplo.bitrix24.com.br/crm/deal/details/';

    public function index()
    {
        $this->setTemplate('camiladuarte');
    }

    /** GET /camiladuarte/dicionario — página estática que explica as métricas (sem consulta ao banco). */
    public function dicionario()
    {
        $this->setTemplate('camiladuarte');
    }

    // ===================================================================
    // HELPERS: CACHE EM ARQUIVO (.mrphp/cache) — TTL curto, sem tocar no banco
    // Mesmo padrão de executive_controle.php (cópia própria do controller,
    // sem camada compartilhada — convenção do projeto, ver memória
    // cache-copia-por-controller).
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

    /**
     * GET /camiladuarte/dados — payload único: 6 KPIs, funil de vendas,
     * motivos de descarte e gráfico de período (modo conforme ?modo=).
     */
    public function dados()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('camiladuarte_dados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();

            $k           = $this->calcularKpisConsolidado($f);
            $agendadas   = $k['agendadas'];
            $realizadas  = $k['realizadas'];
            $fpRealizado = $k['fp_realizado'];
            $assinPend   = $k['assinatura_pendente'];
            $combos      = $k['combos_vendidos'];
            $aRealizar   = max(0, $agendadas - $realizadas);

            $kpis = [
                'consultas_agendadas'  => $agendadas,
                'consultas_a_realizar' => $aRealizar,
                'consultas_realizadas' => $realizadas,
                'fp_realizado'         => $fpRealizado,
                'assinatura_pendente'  => $assinPend,
                'combos_vendidos'      => $combos,
            ];

            $funil = [
                ['etapa' => 'Consultas Agendadas',  'valor' => $agendadas],
                ['etapa' => 'Consultas Realizadas', 'valor' => $realizadas],
                ['etapa' => 'Combos Vendidos',      'valor' => $combos],
            ];

            $descarte = $this->calcularMotivosDescarte($f);
            $grafico  = $this->calcularGraficoPeriodoConsolidado($f);
            $linhas   = $this->calcularLinhasClientes($f);

            $response = [
                'meta'     => $f,
                'kpis'     => $kpis,
                'funil'    => $funil,
                'descarte' => $descarte,
                'grafico'  => $grafico,
                'linhas'   => $linhas,
            ];
            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /camiladuarte/forms — forms distintos já usados por Camila Duarte (dropdown de busca). */
    public function forms()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('camiladuarte_forms');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DROPDOWN);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $sql = "
                SELECT DISTINCT d.formPreenchido_dealV2 AS form
                FROM dealV2 d
                WHERE d.responsavel_dealV2 = ?
                  AND d.formPreenchido_dealV2 IS NOT NULL AND d.formPreenchido_dealV2 != ''
                ORDER BY form
            ";
            $data = array_column($this->queryAll($sql, [self::RESPONSAVEL]), 'form');
            $this->cacheSet($cacheKey, $data);
            $this->jsonOut($data);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * GET /camiladuarte/grafico — payload leve, só o gráfico de período.
     * Usado ao trocar apenas a granularidade (modo) do gráfico, sem re-buscar
     * KPIs/funil/descarte (que não mudam com o modo).
     */
    public function grafico()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('camiladuarte_grafico');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();
            $response = ['grafico' => $this->calcularGraficoPeriodoConsolidado($f)];
            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    // ===================================================================
    // ABA 2: EVENTOS (Camila Duarte como palestrante — evento.palestrante_evento,
    // não dealV2.responsavel_dealV2). Período sempre sobre evento.data_evento.
    // ===================================================================

    public function eventos()
    {
        $this->setTemplate('camiladuarte');
    }

    /**
     * GET /camiladuarte/eventosDados — payload: totais (KPIs/funil) + linha por evento.
     */
    public function eventosDados()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('camiladuarte_eventosDados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f      = $this->lerFiltrosEventos();
            $linhas = $this->calcularEventosPalestrante($f);

            $eventos = [];
            $totais  = ['ingressos_vendidos' => 0, 'possibilidades' => 0, 'consultas_vendidas' => 0];

            foreach ($linhas as $r) {
                $row = [
                    'id_evento'          => (int)$r['id_evento'],
                    'nome_evento'        => $r['nome_evento'],
                    'data_evento'        => $r['data_evento'],
                    'ingressos_vendidos' => (int)$r['ingressos'],
                    'possibilidades'     => (int)$r['possibilidades'],
                    'consultas_vendidas' => (int)$r['consultas_vendidas'],
                ];
                $row['pct_consultas_possibilidades'] = $row['possibilidades'] > 0
                    ? round($row['consultas_vendidas'] / $row['possibilidades'], 4)
                    : 0.0;

                $eventos[] = $row;

                $totais['ingressos_vendidos'] += $row['ingressos_vendidos'];
                $totais['possibilidades']     += $row['possibilidades'];
                $totais['consultas_vendidas'] += $row['consultas_vendidas'];
            }

            $totais['pct_consultas_possibilidades'] = $totais['possibilidades'] > 0
                ? round($totais['consultas_vendidas'] / $totais['possibilidades'], 4)
                : 0.0;

            $response = [
                'meta'    => $f,
                'totais'  => $totais,
                'eventos' => $eventos,
            ];
            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
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

    /** Lê os filtros do querystring ($_GET) num array normalizado. */
    private function lerFiltros(): array
    {
        $modo = $this->g('modo');
        if (!in_array($modo, ['anoMes', 'dia_semana', 'dia_mes'], true)) {
            $modo = 'anoMes';
        }
        return [
            'year'        => $this->g('year'),
            'months'      => $this->g('months'),
            'data_inicio' => $this->g('data_inicio'),
            'data_fim'    => $this->g('data_fim'),
            'form'        => $this->g('form'),
            'modo'        => $modo,
        ];
    }

    /**
     * Fase 1C.4: reescrito pra gerar intervalo de data literal (sargável) em
     * vez de YEAR($col)=?/MONTH($col) IN(...) — mesmo fix já provado em
     * marketing/executive/rafaeltorres. Guarda de coluna-expressão necessária
     * aqui: exprDataAgendadaCorrigida() é passada como "coluna" em 2 chamadas.
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

    /** Versão antiga (YEAR()/MONTH(), não-sargável) — usada só se $col não for
     *  uma coluna simples (ver guarda em buildDateFilter). */
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

    /** Filtro de período: range (data_inicio/data_fim, half-open) OU
     *  year/months. Comportamento `||` preservado como já era neste arquivo. */
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

    /** Filtro de Form: multi-select sobre valores brutos de formPreenchido_dealV2 (não classificação binária). */
    private function buildFormFilter(?string $csv): array
    {
        return $this->buildInFilter('d.formPreenchido_dealV2', $csv);
    }

    /**
     * "Data corrigida" de Consultas Agendadas:
     *  - se não há data de agendamento preenchida mas já há data de realização preenchida
     *    (pulou direto pra realizada, sem passar por agendamento formal), usa a data realizada;
     *  - se há data de agendamento, mas ela caiu num mês diferente do mês em que a consulta foi
     *    de fato realizada, também usa a data realizada (evita duplicar a métrica entre 2 meses);
     *  - caso contrário, mantém a data de agendamento bruta.
     */
    private function exprDataAgendadaCorrigida(): string
    {
        return "(CASE
            WHEN (d.dataConsultaAgendada_dealV2 IS NULL OR d.dataConsultaAgendada_dealV2 <= '0000-00-00')
                 AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
                THEN d.dataConsultaRealizada_dealV2
            WHEN d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
                 AND MONTH(d.dataConsultaRealizada_dealV2) <> MONTH(d.dataConsultaAgendada_dealV2)
                THEN d.dataConsultaRealizada_dealV2
            ELSE d.dataConsultaAgendada_dealV2
        END)";
    }

    // ===================================================================
    // CÁLCULOS: 6 KPIs
    // ===================================================================

    /**
     * As 5 métricas abaixo (agendadas/realizadas/fp/assinatura/combos) antes rodavam
     * como 5 SELECT COUNT(*) independentes, cada uma re-escaneando dealV2 do zero
     * com o mesmo filtro-base (responsavel_dealV2 + form). Consolidadas aqui num
     * único SELECT com agregação condicional (SUM(CASE WHEN ...)) — 1 scan em vez de 5.
     *
     * Atenção: como os 5 blocos de CASE (no SELECT) vêm antes do WHERE no texto da
     * query, a ordem dos parâmetros do bind_param segue essa ordem textual: primeiro
     * os params de período de cada CASE (na ordem em que aparecem), só depois
     * RESPONSAVEL e o filtro de form do WHERE.
     */
    private function calcularKpisConsolidado(array $f): array
    {
        $exprAgendada = $this->exprDataAgendadaCorrigida();

        $periodoAgendada  = $this->buildPeriodFilter($exprAgendada, $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $periodoRealizada = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $periodoFp        = $this->buildPeriodFilter('d.dataFpRealizado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $periodoAssin     = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $periodoCombo     = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $formFilter       = $this->buildFormFilter($f['form']);

        $sql = "
            SELECT
              SUM(CASE WHEN LEFT(d.faseId_dealV2, 3) = 'C5:'
                       AND $exprAgendada IS NOT NULL AND $exprAgendada > '0000-00-00'
                       AND ({$periodoAgendada['sql']})
                  THEN 1 ELSE 0 END) AS agendadas,

              SUM(CASE WHEN LEFT(d.faseId_dealV2, 2) = 'C5'
                       AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
                       AND ({$periodoRealizada['sql']})
                  THEN 1 ELSE 0 END) AS realizadas,

              SUM(CASE WHEN LEFT(d.faseId_dealV2, 2) = 'C5'
                       AND d.dataFpRealizado_dealV2 IS NOT NULL AND d.dataFpRealizado_dealV2 > '0000-00-00'
                       AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
                       AND d.dataFpAgendado_dealV2 IS NOT NULL AND d.dataFpAgendado_dealV2 > '0000-00-00'
                       AND d.fase_dealV2 NOT IN " . self::SQL_FASES_FORA_FP . "
                       AND ({$periodoFp['sql']})
                  THEN 1 ELSE 0 END) AS fp_realizado,

              SUM(CASE WHEN d.faseId_dealV2 = 'C5:UC_0Q30H1'
                       AND d.dataFpRealizado_dealV2 IS NOT NULL AND d.dataFpRealizado_dealV2 > '0000-00-00'
                       AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
                       AND d.dataFpAgendado_dealV2 IS NOT NULL AND d.dataFpAgendado_dealV2 > '0000-00-00'
                       AND ({$periodoAssin['sql']})
                  THEN 1 ELSE 0 END) AS assinatura_pendente,

              SUM(CASE WHEN d.faseId_dealV2 IN " . self::SQL_FASES_COMBO . "
                       AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
                       AND ({$periodoCombo['sql']})
                  THEN 1 ELSE 0 END) AS combos_vendidos

            FROM dealV2 d
            WHERE d.responsavel_dealV2 = ?
              {$formFilter['sql']}
        ";

        $params = array_merge(
            $periodoAgendada['params'],
            $periodoRealizada['params'],
            $periodoFp['params'],
            $periodoAssin['params'],
            $periodoCombo['params'],
            [self::RESPONSAVEL],
            $formFilter['params']
        );

        $row = $this->queryOne($sql, $params);

        return [
            'agendadas'           => (int)($row['agendadas'] ?? 0),
            'realizadas'          => (int)($row['realizadas'] ?? 0),
            'fp_realizado'        => (int)($row['fp_realizado'] ?? 0),
            'assinatura_pendente' => (int)($row['assinatura_pendente'] ?? 0),
            'combos_vendidos'     => (int)($row['combos_vendidos'] ?? 0),
        ];
    }

    // ===================================================================
    // CÁLCULOS: MOTIVOS DE DESCARTE (top 15 + "Outros")
    // ===================================================================

    private function calcularMotivosDescarte(array $f, int $topN = 15): array
    {
        $col        = 'd.dataConsultaRealizada_dealV2';
        $period     = $this->buildPeriodFilter($col, $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $formFilter = $this->buildFormFilter($f['form']);

        $sql = "
            SELECT TRIM(d.motivoDescarteComercial2_dealV2) AS motivo, COUNT(*) AS qtd
            FROM dealV2 d
            WHERE d.responsavel_dealV2 = ?
              AND d.motivoDescarteComercial2_dealV2 IS NOT NULL AND TRIM(d.motivoDescarteComercial2_dealV2) != ''
              AND $col IS NOT NULL AND $col > '0000-00-00'
              AND ({$period['sql']})
              {$formFilter['sql']}
            GROUP BY motivo
            ORDER BY qtd DESC
        ";
        $params = array_merge([self::RESPONSAVEL], $period['params'], $formFilter['params']);
        $rows   = $this->queryAll($sql, $params);

        $top   = array_slice($rows, 0, $topN);
        $resto = array_slice($rows, $topN);
        if ($resto) {
            $somaResto = array_sum(array_map(fn($r) => (int)$r['qtd'], $resto));
            $top[]     = ['motivo' => 'Outros', 'qtd' => $somaResto];
        }

        return array_map(fn($r) => ['motivo' => $r['motivo'], 'qtd' => (int)$r['qtd']], $top);
    }

    // ===================================================================
    // CÁLCULOS: TABELA DE CLIENTES (linhas individuais de dealV2)
    // ===================================================================

    /**
     * Linhas individuais de dealV2 para a tabela de clientes abaixo dos
     * gráficos. Diferente dos KPIs (cada um com sua própria coluna de data),
     * aqui o período deve casar com QUALQUER UMA das 4 datas relevantes —
     * por isso os 4 buildPeriodFilter() são unidos com OR em vez de somados
     * como condições independentes.
     */
    private function calcularLinhasClientes(array $f): array
    {
        $exprAgendada = $this->exprDataAgendadaCorrigida();

        $pAgendada  = $this->buildPeriodFilter($exprAgendada, $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pRealizada = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pFp        = $this->buildPeriodFilter('d.dataFpRealizado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $pCombo     = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $formFilter = $this->buildFormFilter($f['form']);

        $sql = "
            SELECT
                d.idBitrix_dealV2                  AS id_bitrix,
                d.nome_dealV2                       AS nome_deal,
                d.email_dealV2                       AS email,
                d.dataConsultaAgendada_dealV2       AS data_consulta_agendada,
                d.dataConsultaRealizada_dealV2      AS data_consulta_realizada,
                d.dataFpRealizado_dealV2            AS data_fp_realizado,
                d.dataContratoComboAssinado_dealV2  AS data_assinatura_combo
            FROM dealV2 d
            WHERE d.responsavel_dealV2 = ?
              AND ( ({$pAgendada['sql']}) OR ({$pRealizada['sql']}) OR ({$pFp['sql']}) OR ({$pCombo['sql']}) )
              {$formFilter['sql']}
            ORDER BY d.dataContratoComboAssinado_dealV2 DESC,
                     d.dataConsultaRealizada_dealV2 DESC,
                     d.dataConsultaAgendada_dealV2 DESC
            LIMIT 500
        ";

        $params = array_merge(
            [self::RESPONSAVEL],
            $pAgendada['params'],
            $pRealizada['params'],
            $pFp['params'],
            $pCombo['params'],
            $formFilter['params']
        );

        $rows = $this->queryAll($sql, $params);

        return array_map(fn($r) => [
            'id_bitrix'               => $r['id_bitrix'],
            'nome_deal'               => $r['nome_deal'],
            'email'                   => $r['email'],
            'data_consulta_agendada'  => $this->fmtDataOuNull($r['data_consulta_agendada']),
            'data_consulta_realizada' => $this->fmtDataOuNull($r['data_consulta_realizada']),
            'data_fp_realizado'       => $this->fmtDataOuNull($r['data_fp_realizado']),
            'data_assinatura_combo'   => $this->fmtDataOuNull($r['data_assinatura_combo']),
            'link_bitrix'             => $r['id_bitrix'] ? self::BITRIX_BASE . $r['id_bitrix'] . '/' : null,
        ], $rows);
    }

    /**
     * Zero-date (0000-00-00, com ou sem parte de hora) ou vazio vira null;
     * senão formata via $this->data() (só a parte de data — a tabela de
     * clientes não precisa da hora). Compara só os 10 primeiros caracteres
     * (a parte de data) — comparar a string inteira falha quando vem com
     * hora, pois '0000-00-00 00:00:00' > '0000-00-00' lexicograficamente.
     */
    private function fmtDataOuNull(?string $v): ?string
    {
        $dataParte = substr((string)$v, 0, 10);
        if ($v === null || $v === '' || $dataParte <= '0000-00-00') {
            return null;
        }
        return $this->data($dataParte);
    }

    // ===================================================================
    // CÁLCULOS: GRÁFICO DE PERÍODO (3 modos)
    // ===================================================================

    private function exprAgrupamento(string $modo, string $col): string
    {
        return match ($modo) {
            'dia_semana' => "WEEKDAY($col)",
            'dia_mes'    => "DAYOFMONTH($col)",
            default      => "DATE_FORMAT($col, '%Y-%m')",
        };
    }

    /**
     * As 2 séries do gráfico (Consultas Realizadas via dataConsultaRealizada_dealV2,
     * Combos Vendidos via dataContratoComboAssinado_dealV2) antes rodavam como 2
     * SELECT ... GROUP BY periodo independentes. Consolidadas aqui num único SELECT
     * via UNION ALL + agregação externa — 1 round-trip em vez de 2 (cada série
     * agrupa por sua própria coluna de data, então o scan em si continua sendo
     * 2 subconsultas lógicas, mas executadas numa única ida ao banco).
     */
    private function calcularGraficoPeriodoConsolidado(array $f): array
    {
        $modo = $f['modo'];

        $colRealizadas = 'd.dataConsultaRealizada_dealV2';
        $colCombos     = 'd.dataContratoComboAssinado_dealV2';

        $condRealizadas = "LEFT(d.faseId_dealV2, 2) = 'C5'";
        $condCombos     = "d.faseId_dealV2 IN " . self::SQL_FASES_COMBO;

        $groupRealizadas = $this->exprAgrupamento($modo, $colRealizadas);
        $groupCombos     = $this->exprAgrupamento($modo, $colCombos);

        $periodoRealizadas = $this->buildPeriodFilter($colRealizadas, $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $periodoCombos     = $this->buildPeriodFilter($colCombos, $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $formFilter        = $this->buildFormFilter($f['form']);

        $sql = "
            SELECT periodo, SUM(realizadas) AS realizadas, SUM(combos) AS combos
            FROM (
                SELECT $groupRealizadas AS periodo, COUNT(*) AS realizadas, 0 AS combos
                FROM dealV2 d
                WHERE d.responsavel_dealV2 = ?
                  AND $condRealizadas
                  AND $colRealizadas IS NOT NULL AND $colRealizadas > '0000-00-00'
                  AND ({$periodoRealizadas['sql']})
                  {$formFilter['sql']}
                GROUP BY periodo

                UNION ALL

                SELECT $groupCombos AS periodo, 0 AS realizadas, COUNT(*) AS combos
                FROM dealV2 d
                WHERE d.responsavel_dealV2 = ?
                  AND $condCombos
                  AND $colCombos IS NOT NULL AND $colCombos > '0000-00-00'
                  AND ({$periodoCombos['sql']})
                  {$formFilter['sql']}
                GROUP BY periodo
            ) t
            GROUP BY periodo
        ";

        $params = array_merge(
            [self::RESPONSAVEL],
            $periodoRealizadas['params'],
            $formFilter['params'],
            [self::RESPONSAVEL],
            $periodoCombos['params'],
            $formFilter['params']
        );

        $rows = $this->queryAll($sql, $params);

        $realizadasPorPeriodo = [];
        $combosPorPeriodo     = [];
        foreach ($rows as $r) {
            $realizadasPorPeriodo[(string)$r['periodo']] = (int)$r['realizadas'];
            $combosPorPeriodo[(string)$r['periodo']]     = (int)$r['combos'];
        }

        if ($modo === 'dia_semana') {
            // WEEKDAY(): 0=Segunda ... 6=Domingo — mapeamento direto pro rótulo PT-BR.
            $keys   = range(0, 6);
            $labels = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
        } elseif ($modo === 'dia_mes') {
            $keys   = range(1, 31);
            $labels = array_map('strval', $keys);
        } else {
            $keys = array_values(array_unique(array_merge(array_keys($realizadasPorPeriodo), array_keys($combosPorPeriodo))));
            sort($keys);
            $MESES_ABREV = ['01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr', '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago', '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'];
            $labels = array_map(function ($k) use ($MESES_ABREV) {
                [$ano, $mes] = explode('-', $k);
                return ($MESES_ABREV[$mes] ?? $mes) . '/' . substr($ano, 2);
            }, $keys);
        }

        $result = [];
        foreach ($keys as $i => $key) {
            $realizadas = (int)($realizadasPorPeriodo[(string)$key] ?? 0);
            $combos     = (int)($combosPorPeriodo[(string)$key] ?? 0);
            $result[]   = [
                'label'      => (string)$labels[$i],
                'realizadas' => $realizadas,
                'combos'     => $combos,
                'conversao'  => $realizadas > 0 ? round($combos / $realizadas, 4) : 0.0,
            ];
        }
        return $result;
    }

    // ===================================================================
    // ABA 2: HELPERS DE FILTRO E CÁLCULO (evento.palestrante_evento)
    // ===================================================================

    /** Lê os filtros do querystring da aba Eventos — só período, sem form/modo. */
    private function lerFiltrosEventos(): array
    {
        return [
            'year'        => $this->g('year'),
            'months'      => $this->g('months'),
            'data_inicio' => $this->g('data_inicio'),
            'data_fim'    => $this->g('data_fim'),
        ];
    }

    /**
     * Monta o WHERE sobre a tabela evento: sempre restrito à palestrante Camila
     * Duarte e não-deletado; período incide sobre evento.data_evento (nunca sobre
     * datas de dealV2) — mesmo princípio de eventos_controle.php::buildEventoFilter().
     */
    private function buildEventoFilter(string $alias, array $f): array
    {
        $periodo = $this->buildPeriodFilter("$alias.data_evento", $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);

        $conds  = ["$alias.deletado_evento = 0", "$alias.palestrante_evento = ?"];
        $params = [self::RESPONSAVEL];

        if ($periodo['sql'] !== '1=1') {
            $conds[] = $periodo['sql'];
        }
        $params = array_merge($params, $periodo['params']);

        return ['sql' => implode("\n              AND ", $conds), 'params' => $params];
    }

    /** Zero-date "preenchida" (MySQL guarda 0000-00-00 em datas vazias). */
    private function sqlDateFilled(string $col): string
    {
        return "($col IS NOT NULL AND $col > '0000-00-00 00:00:00')";
    }

    /**
     * Funil por evento: Ingressos Vendidos (cadeira), Possibilidades (checkin)
     * e Consultas Vendidas — mesmas regras de
     * eventos_controle.php::calcularDealsPorEvento().
     */
    private function calcularEventosPalestrante(array $f): array
    {
        $filtro = $this->buildEventoFilter('e', $f);

        $dataVenda = $this->sqlDateFilled('d.dataVenda_dealV2');

        $sql = "
            SELECT
                e.id_evento, e.nome_evento, e.data_evento,

                SUM(CASE WHEN d.faseId_dealV2 = 'C9:WON' AND $dataVenda
                         THEN 1 ELSE 0 END) AS ingressos,

                SUM(CASE WHEN d.checkin_dealV2 = '1'
                          AND d.faseId_dealV2 IS NOT NULL
                          AND d.faseId_dealV2 NOT LIKE '%:%'
                         THEN 1 ELSE 0 END) AS possibilidades,

                SUM(CASE WHEN d.faseId_dealV2 IN ('WON', 'C11:WON') AND $dataVenda
                         THEN 1 ELSE 0 END) AS consultas_vendidas

            FROM evento e
            LEFT JOIN dealV2 d ON d.idEvento_dealV2 = CONVERT(e.id_evento USING latin1)
            WHERE {$filtro['sql']}
            GROUP BY e.id_evento, e.nome_evento, e.data_evento
            ORDER BY e.data_evento DESC
        ";

        return $this->queryAll($sql, $filtro['params']);
    }
}
