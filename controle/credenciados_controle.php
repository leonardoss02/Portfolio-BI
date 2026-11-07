<?php

/**
 * Dashboard Credenciados — funil comercial dos registros que possuem um ID
 * de afiliado preenchido.
 *
 * leadV2 e dealV2 são fontes independentes: o recorte é aplicado na coluna
 * própria de cada tabela e os totais só são combinados no payload/visual.
 * Não existe JOIN por idAfiliado porque os conjuntos de IDs não são iguais.
 */
class credenciados extends MRPHP
{
    private const SQL_FASES_CONSULTA = "('WON', 'C11:WON')";
    private const SQL_FASES_COMBO = "('C1:WON', 'C5:WON', 'C29:won', 'C31:WON')";
    private const SQL_FASES_DISTRATO = "('C5:UC_CL1DBM', 'C5:UC_AQ2QYT')";
    private const SQL_FASES_FORA_FP = "('Comercial 3', 'Distrato', 'Lead sem qualificação')";

    private const CACHE_TTL_DADOS = 600;
    private const CACHE_TTL_REGISTROS = 300;

    public function index()
    {
        $this->setTemplate('credenciados');
    }

    public function dicionario()
    {
        $this->setTemplate('credenciados');
    }

    /** GET /credenciados/dados — KPIs e motivos de descarte. */
    public function dados()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('credenciados_dados');
            $cached = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['meta']['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();
            $lead = $this->calcularKpisLead($f);
            $deal = $this->calcularKpisDeal($f);

            $kpis = array_merge($lead, $deal, [
                'conv_qualificados' => $this->ratio($lead['qualificados'], $lead['total_leads']),
                'conv_cadeiras' => $this->ratio($deal['cadeiras_vendidas'], $lead['qualificados']),
                'conv_consultas_vendidas' => $this->ratio($deal['consultas_vendidas'], $lead['qualificados']),
                'conv_consultas_realizadas' => $this->ratio($deal['consultas_realizadas'], $deal['consultas_vendidas']),
                'conv_combo' => $this->ratio($deal['combo_vendido'], $deal['consultas_realizadas']),
            ]);

            $response = [
                'meta' => [
                    'generated' => date('d/m/Y H:i:s'),
                    'cached' => false,
                    'filtros' => $f,
                ],
                'kpis' => $kpis,
                'motivos' => $this->calcularMotivosDescarte($f),
            ];

            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /credenciados/grafico — payload leve para troca de granularidade. */
    public function grafico()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('credenciados_grafico');
            $cached = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['meta']['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();
            $response = [
                'meta' => [
                    'generated' => date('d/m/Y H:i:s'),
                    'cached' => false,
                    'granularidade' => $f['granularidade'],
                ],
                'serie' => $this->calcularSerie($f),
            ];

            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /credenciados/registros — tabela paginada de leads afiliados. */
    public function registros()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('credenciados_registros');
            $cached = $this->cacheGet($cacheKey, self::CACHE_TTL_REGISTROS);
            if ($cached !== null) {
                $cached['meta']['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();
            $response = $this->calcularRegistros($f);
            $response['meta']['generated'] = date('d/m/Y H:i:s');
            $response['meta']['cached'] = false;

            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    // ===================================================================
    // HTTP / JSON / CACHE
    // ===================================================================

    private function g(string $key): ?string
    {
        $value = $_GET[$key] ?? null;
        return ($value === null || trim((string)$value) === '') ? null : trim((string)$value);
    }

    private function jsonOut(array $data, int $status = 200): void
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
                ? ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]
                : ['error' => 'Erro interno do servidor.'],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

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

    private function cacheSet(string $key, array $data): void
    {
        $dir = $this->cacheDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents(
            $dir . '/' . $key . '.json',
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
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
        foreach (glob($this->cacheDir() . '/credenciados_*.json') ?: [] as $file) {
            if ((time() - filemtime($file)) > 7200) {
                @unlink($file);
            }
        }
    }

    // ===================================================================
    // BANCO / FILTROS
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
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }

    private function queryOne(string $sql, array $params = []): array
    {
        return $this->queryAll($sql, $params)[0] ?? [];
    }

    private function lerFiltros(): array
    {
        $granularidade = $this->g('granularidade');
        if (!in_array($granularidade, ['mes', 'dia_semana', 'diaria', 'semana_ano'], true)) {
            $granularidade = 'mes';
        }

        $dataInicio = $this->normalizarData($this->g('data_inicio'));
        $dataFim = $this->normalizarData($this->g('data_fim'));
        if ($dataInicio !== null && $dataFim !== null && $dataInicio > $dataFim) {
            [$dataInicio, $dataFim] = [$dataFim, $dataInicio];
        }

        return [
            'year' => $this->normalizarCsvInteiros($this->g('year'), 2000, 2100),
            'months' => $this->normalizarCsvInteiros($this->g('months'), 1, 12),
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'granularidade' => $granularidade,
        ];
    }

    private function normalizarCsvInteiros(?string $csv, int $min, int $max): ?string
    {
        if ($csv === null) {
            return null;
        }
        $validos = [];
        foreach (explode(',', $csv) as $valor) {
            $valor = trim($valor);
            if ($valor === '' || !ctype_digit($valor)) {
                continue;
            }
            $numero = (int)$valor;
            if ($numero >= $min && $numero <= $max) {
                $validos[(string)$numero] = true;
            }
        }
        return $validos ? implode(',', array_keys($validos)) : null;
    }

    private function normalizarData(?string $data): ?string
    {
        if ($data === null) {
            return null;
        }
        $dt = \DateTimeImmutable::createFromFormat('!Y-m-d', $data);
        return ($dt && $dt->format('Y-m-d') === $data) ? $data : null;
    }

    private function buildDateFilter(string $col, ?string $year, ?string $months): array
    {
        $years = $year ? array_map('intval', explode(',', $year)) : [];
        $monthInts = $months ? array_map('intval', explode(',', $months)) : [];
        $monthInts = array_values(array_unique($monthInts));
        sort($monthInts);

        if (!$years) {
            if (!$monthInts) {
                return ['sql' => '1=1', 'params' => []];
            }
            return [
                'sql' => "MONTH($col) IN (" . implode(',', array_fill(0, count($monthInts), '?')) . ')',
                'params' => array_map('strval', $monthInts),
            ];
        }

        $ranges = [];
        $params = [];
        foreach ($years as $yearInt) {
            if (!$monthInts) {
                $ranges[] = "($col >= ? AND $col <= ?)";
                $params[] = sprintf('%04d-01-01 00:00:00', $yearInt);
                $params[] = sprintf('%04d-12-31 23:59:59', $yearInt);
                continue;
            }

            foreach ($this->agruparMesesContiguos($monthInts) as [$inicio, $fim]) {
                $ranges[] = "($col >= ? AND $col <= ?)";
                $params[] = sprintf('%04d-%02d-01 00:00:00', $yearInt, $inicio);
                $params[] = sprintf(
                    '%04d-%02d-%02d 23:59:59',
                    $yearInt,
                    $fim,
                    $this->ultimoDiaDoMes($yearInt, $fim)
                );
            }
        }

        return ['sql' => '(' . implode(' OR ', $ranges) . ')', 'params' => $params];
    }

    private function agruparMesesContiguos(array $meses): array
    {
        $grupos = [];
        $inicio = $fim = null;
        foreach ($meses as $mes) {
            if ($inicio === null) {
                $inicio = $fim = $mes;
            } elseif ($mes === $fim + 1) {
                $fim = $mes;
            } else {
                $grupos[] = [$inicio, $fim];
                $inicio = $fim = $mes;
            }
        }
        if ($inicio !== null) {
            $grupos[] = [$inicio, $fim];
        }
        return $grupos;
    }

    private function ultimoDiaDoMes(int $ano, int $mes): int
    {
        if (in_array($mes, [1, 3, 5, 7, 8, 10, 12], true)) {
            return 31;
        }
        if (in_array($mes, [4, 6, 9, 11], true)) {
            return 30;
        }
        return ($ano % 4 === 0 && ($ano % 100 !== 0 || $ano % 400 === 0)) ? 29 : 28;
    }

    private function buildPeriodFilter(
        string $col,
        ?string $year,
        ?string $months,
        ?string $dataInicio,
        ?string $dataFim
    ): array {
        if ($dataInicio !== null || $dataFim !== null) {
            $conds = [];
            $params = [];
            if ($dataInicio !== null) {
                $conds[] = "$col >= ?";
                $params[] = $dataInicio . ' 00:00:00';
            }
            if ($dataFim !== null) {
                $conds[] = "$col <= ?";
                $params[] = $dataFim . ' 23:59:59';
            }
            return ['sql' => $conds ? implode(' AND ', $conds) : '1=1', 'params' => $params];
        }
        return $this->buildDateFilter($col, $year, $months);
    }

    private function periodFor(string $col, array $f): array
    {
        return $this->buildPeriodFilter(
            $col,
            $f['year'],
            $f['months'],
            $f['data_inicio'],
            $f['data_fim']
        );
    }

    /** Cria um bound externo para o otimizador não varrer deals fora do período. */
    private function buildBound(array $periodos): array
    {
        $parts = [];
        $params = [];
        foreach ($periodos as $periodo) {
            if (($periodo['sql'] ?? '1=1') === '1=1') {
                continue;
            }
            $parts[] = '(' . $periodo['sql'] . ')';
            $params = array_merge($params, $periodo['params']);
        }
        return [
            'sql' => $parts ? '(' . implode(' OR ', $parts) . ')' : '1=1',
            'params' => $params,
        ];
    }

    // ===================================================================
    // KPIs / DESCARTES
    // ===================================================================

    private function calcularKpisLead(array $f): array
    {
        $periodo = $this->periodFor('l.dataUltForm_leadV2', $f);
        $sql = "
            SELECT
                COUNT(*) AS total_leads,
                COALESCE(SUM(CASE WHEN l.qualificado_leadV2 = 'Sim' THEN 1 ELSE 0 END), 0) AS qualificados
            FROM leadV2 l
            WHERE l.idAfiliado_leadV2 <> ''
              AND l.dataUltForm_leadV2 IS NOT NULL
              AND l.dataUltForm_leadV2 > '0000-00-00'
              AND ({$periodo['sql']})
        ";
        $row = $this->queryOne($sql, $periodo['params']);
        return [
            'total_leads' => (int)($row['total_leads'] ?? 0),
            'qualificados' => (int)($row['qualificados'] ?? 0),
        ];
    }

    private function calcularKpisDeal(array $f): array
    {
        $pVenda = $this->periodFor('d.dataVenda_dealV2', $f);
        $pReal = $this->periodFor('d.dataConsultaRealizada_dealV2', $f);
        $pFp = $this->periodFor('d.dataFpRealizado_dealV2', $f);
        $pCombo = $this->periodFor('d.dataContratoComboAssinado_dealV2', $f);
        $preenchidaVenda = "d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'";
        $preenchidaReal = "d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'";
        $preenchidaFp = "d.dataFpRealizado_dealV2 IS NOT NULL AND d.dataFpRealizado_dealV2 > '0000-00-00'";
        $preenchidaFpAgendado = "d.dataFpAgendado_dealV2 IS NOT NULL AND d.dataFpAgendado_dealV2 > '0000-00-00'";
        $preenchidaCombo = "d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'";

        $metricas = [
            ['cadeiras_vendidas', $pVenda,
                "$preenchidaVenda AND d.faseId_dealV2 = 'C9:WON'"],
            ['consultas_vendidas', $pVenda,
                "$preenchidaVenda AND d.faseId_dealV2 IN " . self::SQL_FASES_CONSULTA],
            ['consultas_realizadas', $pReal,
                "$preenchidaReal AND LEFT(d.faseId_dealV2, 2) = 'C5'"],
            ['fp_realizado', $pFp,
                "LEFT(d.faseId_dealV2, 2) = 'C5'
                 AND $preenchidaFp
                 AND $preenchidaReal
                 AND $preenchidaFpAgendado
                 AND (d.fase_dealV2 IS NULL OR d.fase_dealV2 NOT IN " . self::SQL_FASES_FORA_FP . ')'],
            ['taxa_paga', $pVenda,
                "$preenchidaVenda
                 AND LEFT(d.faseId_dealV2, 2) = 'C5'
                 AND d.faseId_dealV2 NOT IN ('C5:UC_AQ2QYT', 'C5:LOSE', 'C5:UC_CL1DBM')
                 AND $preenchidaReal"],
            ['combo_vendido', $pCombo,
                "$preenchidaCombo
                 AND d.faseId_dealV2 IN " . self::SQL_FASES_COMBO],
            ['distrato', $pReal,
                "$preenchidaReal
                 AND d.faseId_dealV2 IN " . self::SQL_FASES_DISTRATO . "
                 AND $preenchidaFp"],
        ];

        $selects = [];
        $params = [];
        foreach ($metricas as [$alias, $periodo, $condicao]) {
            $selects[] = "COALESCE(SUM(CASE WHEN ({$periodo['sql']}) AND $condicao THEN 1 ELSE 0 END), 0) AS $alias";
            $params = array_merge($params, $periodo['params']);
        }

        $bound = $this->buildBound([$pVenda, $pReal, $pFp, $pCombo]);
        $sql = 'SELECT ' . implode(",\n                   ", $selects) . "
                FROM dealV2 d
                WHERE d.idAfiliado_dealV2 <> ''
                  AND ({$bound['sql']})";
        $row = $this->queryOne($sql, array_merge($params, $bound['params']));

        $out = [];
        foreach ($metricas as [$alias]) {
            $out[$alias] = (int)($row[$alias] ?? 0);
        }
        return $out;
    }

    private function calcularMotivosDescarte(array $f, int $topN = 10): array
    {
        $periodo = $this->periodFor('l.dataUltForm_leadV2', $f);
        $sql = "
            SELECT TRIM(l.motivoDescarte_leadV2) AS motivo, COUNT(*) AS qtd
            FROM leadV2 l
            WHERE l.idAfiliado_leadV2 <> ''
              AND l.dataUltForm_leadV2 IS NOT NULL
              AND l.dataUltForm_leadV2 > '0000-00-00'
              AND TRIM(COALESCE(l.motivoDescarte_leadV2, '')) <> ''
              AND ({$periodo['sql']})
            GROUP BY TRIM(l.motivoDescarte_leadV2)
            ORDER BY qtd DESC, motivo ASC
        ";
        $rows = $this->queryAll($sql, $periodo['params']);
        $total = array_sum(array_map(fn($row) => (int)$row['qtd'], $rows));
        $top = array_slice($rows, 0, $topN);
        $resto = array_slice($rows, $topN);
        if ($resto) {
            $top[] = [
                'motivo' => 'Outros',
                'qtd' => array_sum(array_map(fn($row) => (int)$row['qtd'], $resto)),
            ];
        }

        return array_map(fn($row) => [
            'motivo' => (string)$row['motivo'],
            'qtd' => (int)$row['qtd'],
            'pct' => $this->ratio((int)$row['qtd'], $total),
        ], $top);
    }

    // ===================================================================
    // SÉRIE TEMPORAL
    // ===================================================================

    private function exprAgrupamento(string $granularidade, string $col): string
    {
        return match ($granularidade) {
            'dia_semana' => "WEEKDAY($col)",
            'diaria' => "DATE($col)",
            'semana_ano' => "YEARWEEK($col, 3)",
            default => "DATE_FORMAT($col, '%Y-%m')",
        };
    }

    private function calcularSerie(array $f): array
    {
        $gran = $f['granularidade'];
        $colLead = 'l.dataUltForm_leadV2';
        $colVenda = 'd.dataVenda_dealV2';
        $colReal = 'd.dataConsultaRealizada_dealV2';
        $colCombo = 'd.dataContratoComboAssinado_dealV2';

        $pLead = $this->periodFor($colLead, $f);
        $pVenda = $this->periodFor($colVenda, $f);
        $pReal = $this->periodFor($colReal, $f);
        $pCombo = $this->periodFor($colCombo, $f);

        $gLead = $this->exprAgrupamento($gran, $colLead);
        $gVenda = $this->exprAgrupamento($gran, $colVenda);
        $gReal = $this->exprAgrupamento($gran, $colReal);
        $gCombo = $this->exprAgrupamento($gran, $colCombo);

        $sql = "
            SELECT periodo,
                   SUM(qualificados) AS qualificados,
                   SUM(consultas) AS consultas,
                   SUM(realizadas) AS realizadas,
                   SUM(combos) AS combos
            FROM (
                SELECT $gLead AS periodo, COUNT(*) AS qualificados, 0 AS consultas, 0 AS realizadas, 0 AS combos
                FROM leadV2 l
                WHERE l.idAfiliado_leadV2 <> ''
                  AND l.qualificado_leadV2 = 'Sim'
                  AND $colLead IS NOT NULL
                  AND $colLead > '0000-00-00'
                  AND ({$pLead['sql']})
                GROUP BY periodo

                UNION ALL

                SELECT $gVenda AS periodo, 0, COUNT(*), 0, 0
                FROM dealV2 d
                WHERE d.idAfiliado_dealV2 <> ''
                  AND d.faseId_dealV2 IN " . self::SQL_FASES_CONSULTA . "
                  AND $colVenda IS NOT NULL
                  AND $colVenda > '0000-00-00'
                  AND ({$pVenda['sql']})
                GROUP BY periodo

                UNION ALL

                SELECT $gReal AS periodo, 0, 0, COUNT(*), 0
                FROM dealV2 d
                WHERE d.idAfiliado_dealV2 <> ''
                  AND LEFT(d.faseId_dealV2, 2) = 'C5'
                  AND $colReal IS NOT NULL
                  AND $colReal > '0000-00-00'
                  AND ({$pReal['sql']})
                GROUP BY periodo

                UNION ALL

                SELECT $gCombo AS periodo, 0, 0, 0, COUNT(*)
                FROM dealV2 d
                WHERE d.idAfiliado_dealV2 <> ''
                  AND d.faseId_dealV2 IN " . self::SQL_FASES_COMBO . "
                  AND $colCombo IS NOT NULL
                  AND $colCombo > '0000-00-00'
                  AND ({$pCombo['sql']})
                GROUP BY periodo
            ) serie
            GROUP BY periodo
            ORDER BY periodo
        ";
        $params = array_merge(
            $pLead['params'],
            $pVenda['params'],
            $pReal['params'],
            $pCombo['params']
        );
        $rows = $this->queryAll($sql, $params);

        $porPeriodo = [];
        foreach ($rows as $row) {
            if ($row['periodo'] === null || $row['periodo'] === '') {
                continue;
            }
            $porPeriodo[(string)$row['periodo']] = [
                'qualificados' => (int)$row['qualificados'],
                'consultas' => (int)$row['consultas'],
                'realizadas' => (int)$row['realizadas'],
                'combos' => (int)$row['combos'],
            ];
        }

        $keys = $this->keysDaSerie($porPeriodo, $gran);
        $serie = [];
        foreach ($keys as $key) {
            $base = $porPeriodo[(string)$key] ?? [
                'qualificados' => 0,
                'consultas' => 0,
                'realizadas' => 0,
                'combos' => 0,
            ];
            $serie[] = [
                'periodo' => (string)$key,
                'label' => $this->labelPeriodo((string)$key, $gran),
                'qualificados' => $base['qualificados'],
                'consultas' => $base['consultas'],
                'realizadas' => $base['realizadas'],
                'combos' => $base['combos'],
                'conv_consultas' => $this->ratio($base['consultas'], $base['qualificados']),
                'conv_combo' => $this->ratio($base['combos'], $base['realizadas']),
            ];
        }
        return $serie;
    }

    private function keysDaSerie(array $porPeriodo, string $granularidade): array
    {
        if ($granularidade === 'dia_semana') {
            return range(0, 6);
        }

        $keys = array_keys($porPeriodo);
        sort($keys, SORT_STRING);
        if ($granularidade !== 'mes' || count($keys) < 2) {
            return $keys;
        }

        $inicio = \DateTimeImmutable::createFromFormat('!Y-m', (string)$keys[0]);
        $fim = \DateTimeImmutable::createFromFormat('!Y-m', (string)$keys[count($keys) - 1]);
        if (!$inicio || !$fim) {
            return $keys;
        }

        $meses = [];
        for ($atual = $inicio; $atual <= $fim; $atual = $atual->modify('+1 month')) {
            $meses[] = $atual->format('Y-m');
            if (count($meses) > 60) {
                return $keys;
            }
        }
        return $meses;
    }

    private function labelPeriodo(string $key, string $granularidade): string
    {
        if ($granularidade === 'dia_semana') {
            return ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'][(int)$key] ?? $key;
        }
        if ($granularidade === 'diaria') {
            $dt = \DateTimeImmutable::createFromFormat('!Y-m-d', $key);
            return $dt ? $dt->format('d/m/y') : $key;
        }
        if ($granularidade === 'semana_ano') {
            $numero = (int)$key;
            $ano = intdiv($numero, 100);
            $semana = $numero % 100;
            return sprintf('S%02d/%02d', $semana, $ano % 100);
        }

        $meses = [
            '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr',
            '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
            '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez',
        ];
        if (preg_match('/^(\d{4})-(\d{2})$/', $key, $match)) {
            return ($meses[$match[2]] ?? $match[2]) . '/' . substr($match[1], 2);
        }
        return $key;
    }

    // ===================================================================
    // TABELA DE REGISTROS (sem paginação — a view busca tudo de uma vez e
    // faz busca/ordenação no navegador)
    // ===================================================================

    private function calcularRegistros(array $f): array
    {
        $periodo = $this->periodFor('l.dataUltForm_leadV2', $f);
        $sql = "
            SELECT
                l.idBitrix_leadV2 AS id,
                l.nome_leadV2 AS nome,
                l.telefoneTexto_leadV2 AS telefone,
                l.dataUltForm_leadV2 AS data_formulario,
                l.etapa_leadV2 AS fase,
                l.profissao_leadV2 AS profissao,
                l.tempoExp_leadV2 AS tempo_experiencia
            FROM leadV2 l
            WHERE l.idAfiliado_leadV2 <> ''
              AND l.dataUltForm_leadV2 IS NOT NULL
              AND l.dataUltForm_leadV2 > '0000-00-00'
              AND ({$periodo['sql']})
            ORDER BY l.dataUltForm_leadV2 DESC, l.idBitrix_leadV2 ASC
        ";
        $rows = $this->queryAll($sql, $periodo['params']);
        $registros = array_map(fn($row) => [
            'id' => (string)$row['id'],
            'nome' => (string)$row['nome'],
            'telefone' => (string)$row['telefone'],
            'data_formulario' => $this->fmtDataHora($row['data_formulario']),
            'fase' => (string)$row['fase'],
            'profissao' => (string)$row['profissao'],
            'tempo_experiencia' => (string)$row['tempo_experiencia'],
        ], $rows);

        return [
            'meta' => ['total' => count($registros)],
            'registros' => $registros,
        ];
    }

    private function fmtDataHora(?string $value): ?string
    {
        $data = substr((string)$value, 0, 10);
        if ($value === null || $value === '' || $data <= '0000-00-00') {
            return null;
        }
        $dt = new \DateTimeImmutable($value);
        return $dt->format('d/m/Y H:i');
    }

    private function ratio(int $numerador, int $denominador): float
    {
        return $denominador > 0 ? round($numerador / $denominador, 4) : 0.0;
    }
}
