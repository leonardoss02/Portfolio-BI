<?php

/**
 * Dashboard Law (jurídico) — MRPHP.
 *
 * Acompanha os casos de dealV2 que pertencem ao funil de Law (fase_dealV2 IN
 * Milestone 1/2/3, Aplicados, Aprovado USCIS, NVC), por case manager
 * (advogadoAtendimento_dealV2). Mesmo padrão de rafaeltorres_controle.php —
 * sem camada calculos/, toda a lógica de SQL/cálculo em métodos privados.
 *
 * "Status" (Em Aberto/Aplicados/Aprovados/NVC) e "Milestone" (ML1/ML2/ML3)
 * não são colunas armazenadas — são derivadas de fase_dealV2 via CASE WHEN
 * (equivalente às colunas calculadas SWITCH() do modelo Power BI de origem).
 *
 * "Alta prioridade" também não é armazenada: é calculada a cada query via
 * EXISTS contra taskV2 (idDeal_taskV2 = idBitrix_dealV2, priority_taskV2 = 2,
 * status_taskV2 <> 'Concluída').
 */
class law extends MRPHP
{
    private const FASE_APLICADOS = 'Aplicados';
    private const FASE_APROVADOS = 'Aprovado USCIS';
    private const FASE_NVC       = 'NVC';

    /** fase_dealV2 aceitas no funil de Law (equivalente a Status <> "Outros"). */
    private const SQL_FASES_LAW = "('Milestone 1','Milestone 2','Milestone 3','Aplicados','Aprovado USCIS','NVC')";

    /** fase_dealV2 dos 3 milestones ("Em Aberto"). */
    private const SQL_FASES_MILESTONE = "('Milestone 1','Milestone 2','Milestone 3')";

    /** Início do calendário de ciclos do Law (ciclos de 4 semanas). */
    private const CICLO_EPOCH = '2025-05-19';

    public function index()
    {
        $this->setTemplate('law');
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
     * GET /law/dados — payload único: 6 KPIs, barra status×case-manager,
     * barra milestone×case-manager, donut de milestones, e o gráfico de
     * período (granularidade default = mes).
     */
    public function dados()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('law_dados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();

            $response = [
                'meta'                  => $f,
                'kpis'                  => $this->calcularKpis($f),
                'barCaseManager'        => $this->calcularBarCaseManager($f),
                'milestoneCaseManager'  => $this->calcularMilestoneCaseManager($f),
                'milestoneDonut'        => $this->calcularMilestoneDonut($f),
                'grafico'               => $this->calcularGraficoPeriodo($f),
            ];
            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * GET /law/grafico — payload leve, só o gráfico de período. Usado ao
     * trocar a granularidade (dia/semana/mes/ano/ciclo) sem re-buscar
     * KPIs/barras/donut (que não dependem da granularidade).
     */
    public function grafico()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('law_grafico');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();
            $response = ['grafico' => $this->calcularGraficoPeriodo($f)];
            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /law/responsaveis — case managers distintos (dropdown de busca). */
    public function responsaveis()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('law_responsaveis');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DROPDOWN);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $sql = "
                SELECT DISTINCT d.advogadoAtendimento_dealV2 AS responsavel
                FROM dealV2 d
                WHERE d.fase_dealV2 IN " . self::SQL_FASES_LAW . "
                  AND d.advogadoAtendimento_dealV2 IS NOT NULL AND d.advogadoAtendimento_dealV2 != ''
                ORDER BY responsavel
            ";
            $data = array_column($this->queryAll($sql), 'responsavel');
            $this->cacheSet($cacheKey, $data);
            $this->jsonOut($data);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /law/ciclos — números de ciclo distintos já usados nos dados (dropdown de busca). */
    public function ciclos()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('law_ciclos');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DROPDOWN);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $expr = $this->exprAgrupamento('ciclo', 'd.criadoEm_dealV2');
            $sql = "
                SELECT DISTINCT ($expr) AS ciclo
                FROM dealV2 d
                WHERE d.fase_dealV2 IN " . self::SQL_FASES_LAW . "
                  AND d.criadoEm_dealV2 IS NOT NULL AND d.criadoEm_dealV2 > '0000-00-00'
                  AND d.criadoEm_dealV2 >= '" . self::CICLO_EPOCH . "'
                ORDER BY ciclo
            ";
            $data = array_map('intval', array_column($this->queryAll($sql), 'ciclo'));
            $this->cacheSet($cacheKey, $data);
            $this->jsonOut($data);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    public function prioridade()
    {
        $this->setTemplate('law');
    }

    /**
     * GET /law/prioridadeDados — payload único da aba Alta Prioridade: KPIs
     * (reaproveitados de calcularKpis/calcularMilestoneDonut), case manager em
     * destaque, tabela de casos e gráfico de evolução (granularidade default
     * = mes).
     */
    public function prioridadeDados()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('law_prioridadeDados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();

            // topCaseManager derivado dos mesmos "casos" já buscados (Fase 1C.4)
            // em vez de uma query própria — mesmo filtro-base, só exclui case
            // manager nulo/vazio na hora de agrupar em PHP (replicando a
            // exclusão que a query antiga fazia no WHERE).
            $casos = $this->calcularCasosAltaPrioridade($f);

            $response = [
                'meta'           => $f,
                'kpis'           => $this->calcularKpis($f),
                'milestoneDonut' => $this->calcularMilestoneDonut($f),
                'topCaseManager' => $this->deriveTopCaseManager($casos),
                'casos'          => $casos,
                'grafico'        => $this->calcularGraficoPrioridade($f),
            ];
            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * GET /law/prioridadeGrafico — payload leve, só o gráfico de evolução da
     * aba Alta Prioridade. Usado ao trocar a granularidade sem re-buscar
     * KPIs/tabela/destaque.
     */
    public function prioridadeGrafico()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('law_prioridadeGrafico');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();
            $response = ['grafico' => $this->calcularGraficoPrioridade($f)];
            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    public function dicionario()
    {
        $this->setTemplate('law');
    }

    // ===================================================================
    // HELPERS: HTTP / JSON (padrão idêntico ao de rafaeltorres_controle.php)
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

    private function lerFiltros(): array
    {
        $granularidade = $this->g('granularidade');
        if (!in_array($granularidade, ['ano', 'mes', 'semana', 'dia', 'ciclo'], true)) {
            $granularidade = 'mes';
        }
        return [
            'year'          => $this->g('year'),
            'months'        => $this->g('months'),
            'data_inicio'   => $this->g('data_inicio'),
            'data_fim'      => $this->g('data_fim'),
            'responsavel'   => $this->g('responsavel'),
            'ciclo'         => $this->g('ciclo'),
            'status'        => $this->g('status'),
            'milestone'     => $this->g('milestone'),
            'granularidade' => $granularidade,
        ];
    }

    /**
     * Fase 1C.4: reescrito pra gerar intervalo de data literal ($col >= ? AND
     * $col <= ?, sargável) em vez de YEAR($col)=?/MONTH($col) IN(...) — mesmo
     * fix já provado em marketing/executive. `law` não tem nenhuma coluna-
     * expressão passada aqui (só `d.criadoEm_dealV2`, sempre coluna simples),
     * mas a guarda é mantida por segurança/consistência com o padrão de
     * referência (executive_controle.php).
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
     * Filtro de período com precedência de range explícito: se data_inicio E
     * data_fim vierem preenchidos, ignora year/months (mesmo padrão de
     * executive_controle.php::buildPeriodFilter). Senão cai no buildDateFilter.
     */
    private function buildPeriodFilter(string $col, $year, ?string $months, ?string $dataInicio = null, ?string $dataFim = null): array
    {
        if ($dataInicio !== null && $dataInicio !== '' && $dataFim !== null && $dataFim !== '') {
            return ['sql' => "$col >= ? AND $col <= ?", 'params' => [$dataInicio, $dataFim . ' 23:59:59']];
        }
        return $this->buildDateFilter($col, $year, $months);
    }

    /** Mapeia os rótulos de UI (ML1/ML2/ML3) para os valores de fase_dealV2. */
    private function mapMilestoneCsv(?string $csv): ?string
    {
        if ($csv === null || trim($csv) === '') {
            return null;
        }
        $map  = ['ML1' => 'Milestone 1', 'ML2' => 'Milestone 2', 'ML3' => 'Milestone 3'];
        $vals = array_filter(array_map(fn($v) => $map[trim($v)] ?? null, explode(',', $csv)));
        return $vals ? implode(',', $vals) : null;
    }

    /** Filtro-base comum a todas as queries do dashboard: período, case manager, status, milestone e ciclo. */
    private function filtrosBase(array $f): array
    {
        return [
            'periodo'   => $this->buildPeriodFilter('d.criadoEm_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']),
            'resp'      => $this->buildInFilter('d.advogadoAtendimento_dealV2', $f['responsavel']),
            'status'    => $this->buildInFilter($this->sqlStatusExpr(), $f['status']),
            'milestone' => $this->buildInFilter('d.fase_dealV2', $this->mapMilestoneCsv($f['milestone'])),
            'ciclo'     => $this->buildInFilter($this->exprAgrupamento('ciclo', 'd.criadoEm_dealV2'), $f['ciclo']),
        ];
    }

    /**
     * Status derivado de fase_dealV2 (equivalente à coluna calculada SWITCH()
     * do Power BI) — nunca uma coluna armazenada no banco.
     */
    private function sqlStatusExpr(string $col = 'd.fase_dealV2'): string
    {
        return "CASE
            WHEN $col IN " . self::SQL_FASES_MILESTONE . " THEN 'Em Aberto'
            WHEN $col = '" . self::FASE_APROVADOS . "' THEN 'Aprovados'
            WHEN $col = '" . self::FASE_APLICADOS . "' THEN 'Aplicados'
            WHEN $col = '" . self::FASE_NVC . "' THEN 'NVC'
            ELSE 'Outros'
        END";
    }

    /**
     * Alta prioridade: existe ao menos uma task de taskV2 vinculada ao deal
     * (via idDeal_taskV2 = idBitrix_dealV2) com prioridade 2 e status
     * diferente de "Concluída". Task com status_taskV2 NULL não conta (NULL
     * <> 'Concluída' é NULL/falso em SQL). Calculado a cada query, nunca
     * armazenado.
     */
    private function sqlAltaPrioridadeExists(): string
    {
        return "EXISTS (
            SELECT 1 FROM taskV2 t
            WHERE t.idDeal_taskV2 = d.idBitrix_dealV2
              AND t.priority_taskV2 = 2
              AND t.status_taskV2 <> 'Concluída'
        )";
    }

    /**
     * Expressão de agrupamento por período. "ciclo" é o calendário próprio do
     * Law (sem precedente no restante do projeto): blocos de 4 semanas a
     * partir de CICLO_EPOCH, 1-indexado — tradução direta, passo a passo, da
     * fórmula DAX original (NumSemanas = semanas inteiras desde o epoch;
     * Ciclo = bloco de 4 semanas + 1).
     */
    private function exprAgrupamento(string $granularidade, string $col): string
    {
        return match ($granularidade) {
            'ano'    => "YEAR($col)",
            'semana' => "YEARWEEK($col, 1)",
            'dia'    => "DATE($col)",
            'ciclo'  => "(FLOOR(FLOOR(DATEDIFF($col, '" . self::CICLO_EPOCH . "') / 7) / 4) + 1)",
            default  => "DATE_FORMAT($col, '%Y-%m')",
        };
    }

    // ===================================================================
    // CÁLCULOS: 6 KPIs
    // ===================================================================

    /**
     * Fase 1C.4: as 2 queries (KPIs de status + contagem de alta prioridade)
     * viram 1 só — mesmo filtro-base nas duas, a de alta prioridade só ANDava
     * uma condição a mais (EXISTS), então vira mais uma coluna SUM(CASE) na
     * mesma passada por dealV2 em vez de escanear a tabela de novo.
     */
    private function calcularKpis(array $f): array
    {
        $b       = $this->filtrosBase($f);
        $status  = $this->sqlStatusExpr();
        $altaExp = $this->sqlAltaPrioridadeExists();
        $params  = array_merge($b['periodo']['params'], $b['resp']['params'], $b['status']['params'], $b['milestone']['params'], $b['ciclo']['params']);

        $sql = "
            SELECT
              SUM(CASE WHEN $status = 'Em Aberto' THEN 1 ELSE 0 END) AS em_aberto,
              SUM(CASE WHEN $status = 'Aplicados' THEN 1 ELSE 0 END) AS aplicados,
              SUM(CASE WHEN $status = 'Aprovados' THEN 1 ELSE 0 END) AS aprovados,
              SUM(CASE WHEN $status = 'NVC'       THEN 1 ELSE 0 END) AS nvc,
              SUM(CASE WHEN $altaExp              THEN 1 ELSE 0 END) AS alta_prioridade
            FROM dealV2 d
            WHERE d.fase_dealV2 IN " . self::SQL_FASES_LAW . "
              AND ({$b['periodo']['sql']})
              {$b['resp']['sql']}
              {$b['status']['sql']}
              {$b['milestone']['sql']}
              {$b['ciclo']['sql']}
        ";
        $row = $this->queryOne($sql, $params);

        $emAberto  = (int)($row['em_aberto'] ?? 0);
        $aplicados = (int)($row['aplicados'] ?? 0);
        $aprovados = (int)($row['aprovados'] ?? 0);
        $nvc       = (int)($row['nvc'] ?? 0);

        return [
            'casos_totais'          => $emAberto + $aplicados + $aprovados + $nvc,
            'casos_alta_prioridade' => (int)($row['alta_prioridade'] ?? 0),
            'casos_em_aberto'       => $emAberto,
            'casos_aplicados'       => $aplicados,
            'casos_aprovados'       => $aprovados,
            'casos_nvc'             => $nvc,
        ];
    }

    // ===================================================================
    // CÁLCULOS: BARRA STATUS × CASE MANAGER
    // ===================================================================

    private function calcularBarCaseManager(array $f): array
    {
        $b      = $this->filtrosBase($f);
        $status = $this->sqlStatusExpr();
        $params = array_merge($b['periodo']['params'], $b['resp']['params'], $b['status']['params'], $b['milestone']['params'], $b['ciclo']['params']);

        $sql = "
            SELECT
              d.advogadoAtendimento_dealV2 AS case_manager,
              SUM(CASE WHEN $status = 'Em Aberto' THEN 1 ELSE 0 END) AS em_aberto,
              SUM(CASE WHEN $status = 'Aplicados' THEN 1 ELSE 0 END) AS aplicados,
              SUM(CASE WHEN $status = 'Aprovados' THEN 1 ELSE 0 END) AS aprovados,
              SUM(CASE WHEN $status = 'NVC'       THEN 1 ELSE 0 END) AS nvc
            FROM dealV2 d
            WHERE d.fase_dealV2 IN " . self::SQL_FASES_LAW . "
              AND d.advogadoAtendimento_dealV2 IS NOT NULL AND d.advogadoAtendimento_dealV2 != ''
              AND ({$b['periodo']['sql']})
              {$b['resp']['sql']}
              {$b['status']['sql']}
              {$b['milestone']['sql']}
              {$b['ciclo']['sql']}
            GROUP BY d.advogadoAtendimento_dealV2
            ORDER BY COUNT(*) DESC
        ";
        $rows = $this->queryAll($sql, $params);

        return array_map(fn($r) => [
            'case_manager' => $r['case_manager'],
            'em_aberto'    => (int)$r['em_aberto'],
            'aplicados'    => (int)$r['aplicados'],
            'aprovados'    => (int)$r['aprovados'],
            'nvc'          => (int)$r['nvc'],
        ], $rows);
    }

    // ===================================================================
    // CÁLCULOS: BARRA MILESTONE × CASE MANAGER + DONUT DE MILESTONES
    // ===================================================================

    private function calcularMilestoneCaseManager(array $f): array
    {
        $b      = $this->filtrosBase($f);
        $params = array_merge($b['periodo']['params'], $b['resp']['params'], $b['status']['params'], $b['milestone']['params'], $b['ciclo']['params']);

        $sql = "
            SELECT
              d.advogadoAtendimento_dealV2 AS case_manager,
              SUM(CASE WHEN d.fase_dealV2 = 'Milestone 1' THEN 1 ELSE 0 END) AS ml1,
              SUM(CASE WHEN d.fase_dealV2 = 'Milestone 2' THEN 1 ELSE 0 END) AS ml2,
              SUM(CASE WHEN d.fase_dealV2 = 'Milestone 3' THEN 1 ELSE 0 END) AS ml3
            FROM dealV2 d
            WHERE d.fase_dealV2 IN " . self::SQL_FASES_MILESTONE . "
              AND d.advogadoAtendimento_dealV2 IS NOT NULL AND d.advogadoAtendimento_dealV2 != ''
              AND ({$b['periodo']['sql']})
              {$b['resp']['sql']}
              {$b['status']['sql']}
              {$b['milestone']['sql']}
              {$b['ciclo']['sql']}
            GROUP BY d.advogadoAtendimento_dealV2
            ORDER BY COUNT(*) DESC
        ";
        $rows = $this->queryAll($sql, $params);

        return array_map(fn($r) => [
            'case_manager' => $r['case_manager'],
            'ml1'          => (int)$r['ml1'],
            'ml2'          => (int)$r['ml2'],
            'ml3'          => (int)$r['ml3'],
        ], $rows);
    }

    private function calcularMilestoneDonut(array $f): array
    {
        $b      = $this->filtrosBase($f);
        $params = array_merge($b['periodo']['params'], $b['resp']['params'], $b['status']['params'], $b['milestone']['params'], $b['ciclo']['params']);

        $sql = "
            SELECT
              SUM(CASE WHEN d.fase_dealV2 = 'Milestone 1' THEN 1 ELSE 0 END) AS ml1,
              SUM(CASE WHEN d.fase_dealV2 = 'Milestone 2' THEN 1 ELSE 0 END) AS ml2,
              SUM(CASE WHEN d.fase_dealV2 = 'Milestone 3' THEN 1 ELSE 0 END) AS ml3
            FROM dealV2 d
            WHERE d.fase_dealV2 IN " . self::SQL_FASES_MILESTONE . "
              AND ({$b['periodo']['sql']})
              {$b['resp']['sql']}
              {$b['status']['sql']}
              {$b['milestone']['sql']}
              {$b['ciclo']['sql']}
        ";
        $row = $this->queryOne($sql, $params);

        return [
            ['milestone' => 'ML1', 'qtd' => (int)($row['ml1'] ?? 0)],
            ['milestone' => 'ML2', 'qtd' => (int)($row['ml2'] ?? 0)],
            ['milestone' => 'ML3', 'qtd' => (int)($row['ml3'] ?? 0)],
        ];
    }

    // ===================================================================
    // CÁLCULOS: GRÁFICO DE PERÍODO (4 séries: em_aberto/aplicados/aprovados/nvc)
    // ===================================================================

    private function calcularGraficoPeriodo(array $f): array
    {
        $granularidade = $f['granularidade'];
        $b             = $this->filtrosBase($f);
        $status        = $this->sqlStatusExpr();
        $grupo         = $this->exprAgrupamento($granularidade, 'd.criadoEm_dealV2');
        $params        = array_merge($b['periodo']['params'], $b['resp']['params'], $b['status']['params'], $b['milestone']['params'], $b['ciclo']['params']);

        // Datas anteriores ao epoch do ciclo não têm ciclo definido (equivalente
        // ao BLANK() da fórmula DAX original) — excluídas apenas nesta visão.
        $whereCiclo = $granularidade === 'ciclo' ? "AND d.criadoEm_dealV2 >= '" . self::CICLO_EPOCH . "'" : '';

        $sql = "
            SELECT
              $grupo AS periodo,
              SUM(CASE WHEN $status = 'Em Aberto' THEN 1 ELSE 0 END) AS em_aberto,
              SUM(CASE WHEN $status = 'Aplicados' THEN 1 ELSE 0 END) AS aplicados,
              SUM(CASE WHEN $status = 'Aprovados' THEN 1 ELSE 0 END) AS aprovados,
              SUM(CASE WHEN $status = 'NVC'       THEN 1 ELSE 0 END) AS nvc
            FROM dealV2 d
            WHERE d.fase_dealV2 IN " . self::SQL_FASES_LAW . "
              AND d.criadoEm_dealV2 IS NOT NULL AND d.criadoEm_dealV2 > '0000-00-00'
              AND ({$b['periodo']['sql']})
              {$b['resp']['sql']}
              {$b['status']['sql']}
              {$b['milestone']['sql']}
              {$b['ciclo']['sql']}
              $whereCiclo
            GROUP BY periodo
            ORDER BY periodo ASC
        ";
        $rows = $this->queryAll($sql, $params);

        return array_map(fn($r) => [
            'label'     => $this->labelPeriodo($granularidade, $r['periodo']),
            'em_aberto' => (int)$r['em_aberto'],
            'aplicados' => (int)$r['aplicados'],
            'aprovados' => (int)$r['aprovados'],
            'nvc'       => (int)$r['nvc'],
        ], $rows);
    }

    /**
     * Rótulo de exibição de um valor de período (ano/ciclo/semana/dia/mês),
     * conforme a granularidade — compartilhado entre calcularGraficoPeriodo()
     * e calcularGraficoPrioridade().
     */
    private function labelPeriodo(string $granularidade, $valor): string
    {
        static $nomeMes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $v = (string)$valor;
        if ($granularidade === 'ano')    return $v;
        if ($granularidade === 'ciclo')  return 'Ciclo ' . $v;
        if ($granularidade === 'semana') return 'S' . substr($v, -2) . '/' . substr($v, 2, 2);
        if ($granularidade === 'dia')    return date('d/m', strtotime($v));
        $m = (int)substr($v, 5, 2);
        if ($m < 1 || $m > 12) return $v;
        return $nomeMes[$m - 1] . '/' . substr($v, 2, 2);
    }

    // ===================================================================
    // CÁLCULOS: ABA "ALTA PRIORIDADE"
    // ===================================================================

    /**
     * Case manager com mais casos de alta prioridade — derivado dos "casos"
     * já buscados por calcularCasosAltaPrioridade() (Fase 1C.4), em vez de uma
     * query própria repetindo o mesmo filtro-base. Réplica em PHP da exclusão
     * de case manager nulo/vazio que a query antiga fazia no WHERE.
     */
    private function deriveTopCaseManager(array $casos): array
    {
        $contagem = [];
        foreach ($casos as $c) {
            $cm = $c['case_manager'] ?? null;
            if ($cm === null || $cm === '') {
                continue;
            }
            $contagem[$cm] = ($contagem[$cm] ?? 0) + 1;
        }
        if (!$contagem) {
            return ['case_manager' => null, 'qtd' => 0];
        }
        arsort($contagem);
        $top = array_key_first($contagem);
        return ['case_manager' => $top, 'qtd' => $contagem[$top]];
    }

    /** Linhas de detalhe dos casos de alta prioridade no recorte de filtros atual. */
    private function calcularCasosAltaPrioridade(array $f): array
    {
        $b      = $this->filtrosBase($f);
        $params = array_merge($b['periodo']['params'], $b['resp']['params'], $b['status']['params'], $b['milestone']['params'], $b['ciclo']['params']);

        $sql = "
            SELECT
              d.idBitrix_dealV2 AS id_bitrix,
              d.criadoEm_dealV2 AS data_criacao,
              d.advogadoAtendimento_dealV2 AS case_manager,
              d.fase_dealV2 AS fase
            FROM dealV2 d
            WHERE d.fase_dealV2 IN " . self::SQL_FASES_LAW . "
              AND ({$b['periodo']['sql']})
              {$b['resp']['sql']}
              {$b['status']['sql']}
              {$b['milestone']['sql']}
              {$b['ciclo']['sql']}
              AND {$this->sqlAltaPrioridadeExists()}
            ORDER BY d.criadoEm_dealV2 ASC
        ";
        $rows = $this->queryAll($sql, $params);

        return array_map(fn($r) => [
            'id_bitrix'       => $r['id_bitrix'],
            'data_criacao'    => ($r['data_criacao'] && $r['data_criacao'] > '0000-00-00') ? date('d/m/Y', strtotime($r['data_criacao'])) : '—',
            'case_manager'    => $r['case_manager'],
            'fase'            => $r['fase'],
            'alta_prioridade' => 1,
        ], $rows);
    }

    /** Evolução (série única) de casos de alta prioridade agrupados por período, conforme a granularidade escolhida. */
    private function calcularGraficoPrioridade(array $f): array
    {
        $granularidade = $f['granularidade'];
        $b             = $this->filtrosBase($f);
        $grupo         = $this->exprAgrupamento($granularidade, 'd.criadoEm_dealV2');
        $params        = array_merge($b['periodo']['params'], $b['resp']['params'], $b['status']['params'], $b['milestone']['params'], $b['ciclo']['params']);

        $whereCiclo = $granularidade === 'ciclo' ? "AND d.criadoEm_dealV2 >= '" . self::CICLO_EPOCH . "'" : '';

        $sql = "
            SELECT
              $grupo AS periodo,
              COUNT(*) AS qtd
            FROM dealV2 d
            WHERE d.fase_dealV2 IN " . self::SQL_FASES_LAW . "
              AND d.criadoEm_dealV2 IS NOT NULL AND d.criadoEm_dealV2 > '0000-00-00'
              AND ({$b['periodo']['sql']})
              {$b['resp']['sql']}
              {$b['status']['sql']}
              {$b['milestone']['sql']}
              {$b['ciclo']['sql']}
              $whereCiclo
              AND {$this->sqlAltaPrioridadeExists()}
            GROUP BY periodo
            ORDER BY periodo ASC
        ";
        $rows = $this->queryAll($sql, $params);

        return array_map(fn($r) => [
            'label' => $this->labelPeriodo($granularidade, $r['periodo']),
            'qtd'   => (int)$r['qtd'],
        ], $rows);
    }
}
