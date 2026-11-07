<?php

/**
 * Dashboard "Chatwoot" (Atendimento Comercial) — migrado de
 * Power Bi/Chatwoot (PDO standalone, index.html + api/chatwoot.php +
 * calculos/chatwoot.php) para MRPHP.
 *
 * Um único controller, com 1 view principal (filtros, KPIs com comparativo
 * MoM, dois gráficos de evolução e tabela analítica por responsável com
 * drill-down de conversas) + 1 dicionário. Toda a lógica de SQL/cálculo fica
 * em métodos privados desta classe (sem camada calculos/), mesmo padrão de
 * executive_controle.php, eventos_controle.php e financeiro_controle.php.
 *
 * Fonte: conversaCW (conversas do Chatwoot), já presente na conexão default
 * (mesmo banco `acme_dash_proddb` usado pelo projeto standalone original
 * e já consultado por eventos_controle.php) — não requer a conexão '2'.
 *
 * Escopo do dashboard: apenas o time comercial (ver CW_TEAM), excluindo
 * conversas sem primeira resposta real (sentinela CW_SEM_RESPOSTA).
 */
class chatwoot extends MRPHP
{
    private const CW_TABLE = 'conversaCW';

    /**
     * Time comercial que usa o Chatwoot. A tabela não tem coluna de equipe
     * preenchida, então o recorte é por nome do responsável. Para
     * incluir/remover alguém, editar esta lista.
     */
    private const CW_TEAM = ['Helena', 'Eduardo Pires', 'Priscila Rangel', 'Fernanda matos'];

    /**
     * Sentinela de "sem primeira resposta". O Chatwoot grava a época
     * (epoch 0) como 1969-12-31 21:00:00 (UTC-3) quando a conversa nunca
     * recebeu primeira resposta. Essas conversas são excluídas de todas as
     * queries, igual ao Power Query do PBI original.
     */
    private const CW_SEM_RESPOSTA = '1969-12-31 21:00:00';

    // ===================================================================
    // DASHBOARD
    // ===================================================================

    public function index()
    {
        $this->setTemplate('chatwoot');
    }

    // ===================================================================
    // HELPERS: CACHE EM ARQUIVO (.mrphp/cache) — TTL curto, sem tocar no banco
    // Mesmo padrão de executive_controle.php (cópia própria do controller,
    // sem camada compartilhada — convenção do projeto, ver memória
    // cache-copia-por-controller).
    // ===================================================================

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

    public function dados()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('chatwoot_dados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();

            $prev  = $this->cwPrevPeriod($f['year'], $f['months']);
            $fPrev = array_merge($f, [
                'year'       => $prev['year'],
                'months'     => $prev['months'],
                'dataInicio' => null,
                'dataFim'    => null,
            ]);

            $response = [
                'kpis'           => $this->cwKpis($f),
                'kpisPrev'       => $this->cwKpis($fPrev),
                'porResponsavel' => $this->cwPorResponsavel($f),
                'meta'           => [
                    'periodoAtual'    => $f,
                    'periodoAnterior' => $prev,
                ],
            ];
            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    public function serie()
    {
        try {
            $f    = $this->lerFiltros();
            $gran = $this->g('gran') ?? 'mes';
            if (!in_array($gran, ['ano', 'mes', 'dow', 'dia'], true)) {
                $gran = 'mes';
            }

            $cacheKey = $this->cacheKeyFromRequest('chatwoot_serie');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $response = [
                'gran'  => $gran,
                'serie' => $this->cwSerie($f, $gran),
            ];
            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    public function detalhe()
    {
        try {
            $responsavel = $this->g('responsavel');
            if ($responsavel === null) {
                $this->jsonOut(['error' => 'Parâmetro "responsavel" é obrigatório para o detalhe.'], 400);
            }

            $cacheKey = $this->cacheKeyFromRequest('chatwoot_detalhe');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();

            $response = [
                'responsavel' => $responsavel,
                'detalhe'     => $this->cwDetalhe($f, $responsavel),
            ];
            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    // ===================================================================
    // DICIONÁRIO (página estática — sem dados JSON)
    // ===================================================================

    public function dicionario()
    {
        $this->setTemplate('chatwoot');
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
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
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
                ? [
                    'error' => $e->getMessage(),
                    'file'  => $e->getFile(),
                    'line'  => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]
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
    // HELPERS: FILTROS SQL (traduzidos de PDO nomeado -> mysqli posicional)
    // ===================================================================

    /** Lê os filtros do querystring ($_GET) num array normalizado. */
    private function lerFiltros(): array
    {
        return [
            'year'        => $this->g('year'),
            'months'      => $this->g('months'),
            'responsavel' => $this->g('responsavel'),
            'status'      => $this->g('status'),
            'dataInicio'  => $this->g('di'),
            'dataFim'     => $this->g('df'),
        ];
    }

    /**
     * Fase 1C.4: reescrito pra gerar intervalo de data literal (sargável) em
     * vez de YEAR($col)=?/MONTH($col) IN(...) — mesmo fix já provado em
     * marketing/executive/law/financeiro/helenacastro.
     */
    private function buildDateFilter(string $col, ?string $year, ?string $months): array
    {
        if (!preg_match('/^[A-Za-z_]\w*\.\w+$/', $col) && !preg_match('/^[A-Za-z_]\w*$/', $col)) {
            return $this->buildDateFilterLegacy($col, $year, $months);
        }

        $years = ($year !== null && $year !== '')
            ? array_values(array_filter(array_map('trim', explode(',', $year)), fn($v) => $v !== ''))
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
    private function buildDateFilterLegacy(string $col, ?string $year, ?string $months): array
    {
        $conds  = [];
        $params = [];

        if ($year !== null && $year !== '') {
            $years = array_values(array_filter(array_map('trim', explode(',', $year)), fn($v) => $v !== ''));
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
     *  year/month. Comportamento `||` preservado como já era neste arquivo. */
    private function buildPeriodFilter(string $col, ?string $year, ?string $months, ?string $dataInicio = null, ?string $dataFim = null): array
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

    /** Filtro IN para valores múltiplos separados por vírgula. */
    private function buildInFilter(string $col, ?string $values): array
    {
        if ($values === null || $values === '') {
            return ['sql' => '', 'params' => []];
        }
        $list = array_values(array_filter(array_map('trim', explode(',', $values)), fn($v) => $v !== ''));
        if (!$list) {
            return ['sql' => '', 'params' => []];
        }
        return [
            'sql'    => 'AND ' . $col . ' IN (' . implode(',', array_fill(0, count($list), '?')) . ')',
            'params' => $list,
        ];
    }

    // ===================================================================
    // CÁLCULOS: FRAGMENTOS SQL REUTILIZÁVEIS
    // ===================================================================

    /** Restringe qualquer consulta ao time comercial e exclui a sentinela de "sem resposta". */
    private function cwTeamFilter(): string
    {
        $nomes = array_map(fn($n) => "'" . str_replace("'", "''", $n) . "'", self::CW_TEAM);
        return 'responsavel_conversaCW IN (' . implode(',', $nomes) . ')'
             . " AND primeiraRespostaEm_conversaCW <> '" . self::CW_SEM_RESPOSTA . "'";
    }

    private function cwCountIf(string $cond): string
    {
        return "COUNT(DISTINCT CASE WHEN $cond THEN idCw_conversaCW END)";
    }

    private function cwRespExpr(): string
    {
        return "CASE WHEN responsavel_conversaCW IS NULL OR responsavel_conversaCW = '' "
             . "THEN '(Sem responsável)' ELSE responsavel_conversaCW END";
    }

    /**
     * TRM (min) e Duração (h). A tabela guarda "sem resposta" como datas-lixo
     * (epoch 1969, anos 0020/9992), não só 0000-00-00. A regra correta —
     * resposta DEPOIS da criação, dentro de uma janela sã (até 1 ano) —
     * descarta esse lixo, espelhando o AVERAGEX do DAX que ignora BLANK.
     */
    private function cwTrmExpr(): string
    {
        return "AVG(CASE WHEN primeiraRespostaEm_conversaCW >= criadoEm_conversaCW "
             . "AND TIMESTAMPDIFF(MINUTE, criadoEm_conversaCW, primeiraRespostaEm_conversaCW) BETWEEN 0 AND 525600 "
             . "THEN TIMESTAMPDIFF(MINUTE, criadoEm_conversaCW, primeiraRespostaEm_conversaCW) END)";
    }

    private function cwDuracaoExpr(): string
    {
        return "AVG(CASE WHEN ultimaAtividadeEm_conversaCW >= criadoEm_conversaCW "
             . "THEN TIMESTAMPDIFF(HOUR, criadoEm_conversaCW, ultimaAtividadeEm_conversaCW) END)";
    }

    /** Monta o WHERE comum (escopo do time + período em criadoEm + responsável + status). */
    private function cwWhere(array $f, bool $usarMeses = true): array
    {
        $per  = $this->buildPeriodFilter(
            'criadoEm_conversaCW',
            $f['year'] ?? null,
            $usarMeses ? ($f['months'] ?? null) : null,
            $f['dataInicio'] ?? null,
            $f['dataFim'] ?? null
        );
        $resp = $this->buildInFilter('responsavel_conversaCW', $f['responsavel'] ?? null);
        $st   = $this->buildInFilter('status_conversaCW', $f['status'] ?? null);

        $sql    = 'WHERE ' . $this->cwTeamFilter() . ' AND ' . $per['sql'] . ' ' . $resp['sql'] . ' ' . $st['sql'];
        $params = array_merge($per['params'], $resp['params'], $st['params']);
        return ['sql' => trim($sql), 'params' => $params];
    }

    // ===================================================================
    // CÁLCULOS: KPIS / PERÍODO ANTERIOR / RESPONSÁVEL / SÉRIE / DETALHE
    // ===================================================================

    private function cwKpis(array $f): array
    {
        $w   = $this->cwWhere($f);
        $sql = "SELECT
            COUNT(DISTINCT idCw_conversaCW)                                    AS total_leads,
            " . $this->cwCountIf("rotulos_conversaCW LIKE '%venda-ingresso%'") . " AS ingressos,
            " . $this->cwCountIf("rotulos_conversaCW LIKE '%venda-consulta%'") . " AS consultas,
            " . $this->cwCountIf("status_conversaCW = 'open'")     . "            AS abertas,
            " . $this->cwCountIf("status_conversaCW = 'resolved'") . "            AS resolvidas,
            " . $this->cwTrmExpr()     . " AS trm_min,
            " . $this->cwDuracaoExpr() . " AS duracao_h
          FROM " . self::CW_TABLE . " {$w['sql']}";

        $r = $this->queryOne($sql, $w['params']);
        return [
            'total_leads' => (int)($r['total_leads'] ?? 0),
            'ingressos'   => (int)($r['ingressos']   ?? 0),
            'consultas'   => (int)($r['consultas']   ?? 0),
            'abertas'     => (int)($r['abertas']     ?? 0),
            'resolvidas'  => (int)($r['resolvidas']  ?? 0),
            'trm_min'     => isset($r['trm_min'])     && $r['trm_min']     !== null ? round((float)$r['trm_min'], 2)     : 0.0,
            'duracao_h'   => isset($r['duracao_h'])   && $r['duracao_h']   !== null ? round((float)$r['duracao_h'], 2)  : 0.0,
        ];
    }

    /** Período do mês anterior ao mais recente selecionado (para o comparativo MoM). */
    private function cwPrevPeriod(?string $year, ?string $months): array
    {
        $y = (int)($year ?: date('Y'));
        $monthList = $months
            ? array_values(array_filter(array_map('intval', explode(',', $months))))
            : [(int)date('n')];
        $m = $monthList ? min($monthList) : (int)date('n');
        $m -= 1;
        if ($m < 1) {
            $m = 12;
            $y -= 1;
        }
        return ['year' => (string)$y, 'months' => (string)$m];
    }

    private function cwPorResponsavel(array $f): array
    {
        $w      = $this->cwWhere($f);
        $respEx = $this->cwRespExpr();
        $sql    = "SELECT
            $respEx                                                            AS responsavel,
            COUNT(DISTINCT idCw_conversaCW)                                    AS conversas,
            " . $this->cwCountIf("rotulos_conversaCW LIKE '%venda-ingresso%'") . " AS ingressos,
            " . $this->cwCountIf("rotulos_conversaCW LIKE '%venda-consulta%'") . " AS consultas,
            " . $this->cwCountIf("status_conversaCW = 'resolved'") . "            AS resolvidas,
            " . $this->cwCountIf("status_conversaCW = 'open'")     . "            AS abertas,
            MAX(criadoEm_conversaCW)                                           AS criado_recente,
            MAX(ultimaAtividadeEm_conversaCW)                                  AS ult_atividade,
            " . $this->cwTrmExpr()     . " AS trm_min,
            " . $this->cwDuracaoExpr() . " AS duracao_h
          FROM " . self::CW_TABLE . " {$w['sql']}
          GROUP BY $respEx
          ORDER BY conversas DESC";

        $rows = $this->queryAll($sql, $w['params']);
        return array_map(function ($r) {
            $conversas = (int)$r['conversas'];
            return [
                'responsavel'    => $r['responsavel'],
                'conversas'      => $conversas,
                'ingressos'      => (int)$r['ingressos'],
                'consultas'      => (int)$r['consultas'],
                'resolvidas'     => (int)$r['resolvidas'],
                'abertas'        => (int)$r['abertas'],
                'pct_resolvidas' => $conversas ? round($r['resolvidas'] / $conversas * 100, 1) : 0.0,
                'pct_abertas'    => $conversas ? round($r['abertas']    / $conversas * 100, 1) : 0.0,
                'criado_recente' => $this->cwCleanDate($r['criado_recente']),
                'ult_atividade'  => $this->cwCleanDate($r['ult_atividade']),
                'trm_min'        => $r['trm_min']   !== null ? round((float)$r['trm_min'], 2)   : null,
                'duracao_h'      => $r['duracao_h'] !== null ? round((float)$r['duracao_h'], 2)  : null,
            ];
        }, $rows);
    }

    /**
     * Série temporal por granularidade. gran: 'ano' | 'mes' | 'dow' | 'dia'.
     * Ano ignora ano/mês para mostrar a tendência completa entre todos os anos;
     * mês/dia/dia-da-semana respeitam o filtro de ano e mês selecionado.
     */
    private function cwSerie(array $f, string $gran): array
    {
        switch ($gran) {
            case 'ano':
                $bucket = 'YEAR(criadoEm_conversaCW)';
                $ord    = $bucket;
                $w      = $this->cwWhere(['responsavel' => $f['responsavel'] ?? null, 'status' => $f['status'] ?? null]);
                break;
            case 'dow':
                $bucket = 'DAYOFWEEK(criadoEm_conversaCW)';
                $ord    = $bucket;
                $w      = $this->cwWhere($f, true);
                break;
            case 'dia':
                $bucket = 'DAY(criadoEm_conversaCW)';
                $ord    = $bucket;
                $w      = $this->cwWhere($f, true);
                break;
            case 'mes':
            default:
                $bucket = "DATE_FORMAT(criadoEm_conversaCW, '%Y-%m')";
                $ord    = $bucket;
                $w      = $this->cwWhere($f, true);
                break;
        }

        $sql = "SELECT
            $bucket AS bucket,
            $ord    AS ord,
            COUNT(DISTINCT idCw_conversaCW)                                    AS conversas,
            " . $this->cwCountIf("rotulos_conversaCW LIKE '%venda-ingresso%'") . " AS ingressos,
            " . $this->cwCountIf("rotulos_conversaCW LIKE '%venda-consulta%'") . " AS consultas,
            " . $this->cwCountIf("status_conversaCW = 'resolved'") . "            AS resolvidas,
            " . $this->cwCountIf("status_conversaCW = 'open'")     . "            AS abertas
          FROM " . self::CW_TABLE . " {$w['sql']}
          GROUP BY bucket, ord
          ORDER BY ord";

        $rows = $this->queryAll($sql, $w['params']);
        return array_map(function ($r) use ($gran) {
            $conversas = (int)$r['conversas'];
            return [
                'label'          => $this->cwSerieLabel($gran, $r['bucket']),
                'conversas'      => $conversas,
                'ingressos'      => (int)$r['ingressos'],
                'consultas'      => (int)$r['consultas'],
                'resolvidas'     => (int)$r['resolvidas'],
                'abertas'        => (int)$r['abertas'],
                'pct_resolvidas' => $conversas ? round($r['resolvidas'] / $conversas * 100, 1) : 0.0,
                'pct_abertas'    => $conversas ? round($r['abertas']    / $conversas * 100, 1) : 0.0,
            ];
        }, $rows);
    }

    /** Detalhe: conversas individuais de um responsável (drill-down). */
    private function cwDetalhe(array $f, string $responsavel): array
    {
        $w       = $this->cwWhere($f);
        $semResp = ($responsavel === '(Sem responsável)');
        if ($semResp) {
            $respSql = "AND (responsavel_conversaCW IS NULL OR responsavel_conversaCW = '')";
            $params  = $w['params'];
        } else {
            $respSql = 'AND responsavel_conversaCW = ?';
            $params  = array_merge($w['params'], [$responsavel]);
        }

        $trmRow = "CASE WHEN primeiraRespostaEm_conversaCW >= criadoEm_conversaCW "
                . "AND TIMESTAMPDIFF(MINUTE, criadoEm_conversaCW, primeiraRespostaEm_conversaCW) BETWEEN 0 AND 525600 "
                . "THEN TIMESTAMPDIFF(MINUTE, criadoEm_conversaCW, primeiraRespostaEm_conversaCW) END";
        $durRow = "CASE WHEN ultimaAtividadeEm_conversaCW >= criadoEm_conversaCW "
                . "THEN TIMESTAMPDIFF(HOUR, criadoEm_conversaCW, ultimaAtividadeEm_conversaCW) END";

        $sql = "SELECT
            idCw_conversaCW                                                    AS idcw,
            NULLIF(TRIM(nomeRemetenteMeta_conversaCW), '')                     AS nome,
            telefoneRemetenteMeta_conversaCW                                   AS telefone,
            status_conversaCW                                                  AS status,
            canal_conversaCW                                                   AS canal,
            rotulos_conversaCW                                                 AS rotulos,
            criadoEm_conversaCW                                                AS criado,
            primeiraRespostaEm_conversaCW                                      AS resposta,
            ultimaAtividadeEm_conversaCW                                       AS ult_atividade,
            $trmRow AS trm_min,
            $durRow AS duracao_h
          FROM " . self::CW_TABLE . " {$w['sql']} $respSql
          ORDER BY criadoEm_conversaCW DESC
          LIMIT 500";

        $rows = $this->queryAll($sql, $params);
        return array_map(fn($r) => [
            'idcw'          => (int)$r['idcw'],
            'nome'          => $r['nome'],
            'telefone'      => $r['telefone'],
            'status'        => $r['status'],
            'canal'         => $r['canal'],
            'criado'        => $this->cwCleanDate($r['criado']),
            'resposta'      => $this->cwCleanDate($r['resposta']),
            'ult_atividade' => $this->cwCleanDate($r['ult_atividade']),
            'trm_min'       => $r['trm_min']   !== null ? (int)$r['trm_min']   : null,
            'duracao_h'     => $r['duracao_h'] !== null ? (int)$r['duracao_h'] : null,
            'ingressos'     => str_contains((string)$r['rotulos'], 'venda-ingresso') ? 1 : 0,
            'consultas'     => str_contains((string)$r['rotulos'], 'venda-consulta') ? 1 : 0,
        ], $rows);
    }

    // ===================================================================
    // HELPERS DE FORMATAÇÃO
    // ===================================================================

    private function cwCleanDate(?string $d): ?string
    {
        if ($d === null || $d === '' || str_starts_with($d, '0000-00-00')) {
            return null;
        }
        return $d;
    }

    private function cwSerieLabel(string $gran, $bucket): string
    {
        if ($gran === 'dow') {
            $dias = [1 => 'Dom', 2 => 'Seg', 3 => 'Ter', 4 => 'Qua', 5 => 'Qui', 6 => 'Sex', 7 => 'Sáb'];
            return $dias[(int)$bucket] ?? (string)$bucket;
        }
        if ($gran === 'mes') {
            $meses = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun',
                      7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];
            [$y, $m] = array_pad(explode('-', (string)$bucket), 2, '');
            $mi = (int)$m;
            return ($meses[$mi] ?? $m) . '/' . substr($y, -2);
        }
        return (string)$bucket;
    }
}
