<?php

/**
 * Dashboard "Financeiro" (Recebimentos & Inadimplência) — migrado de
 * Power Bi/Financeiro (PDO standalone, api/+calculos/, dependia de
 * Power Bi/ExecutiveView/api/db.php para conexão) para MRPHP.
 *
 * Um único controller, com 1 view principal (KPIs, gráfico mensal e tabela
 * de clientes com drill-down de parcelas) + 1 dicionário. Toda a lógica de
 * SQL/cálculo fica em métodos privados desta classe (sem camada calculos/),
 * mesmo padrão de executive_controle.php e eventos_controle.php.
 *
 * Fontes: invoice (faturas QuickBooks),
 * dealV2/deal (join só p/ email+telefone da parcela, no drill-down).
 */
class financeiro extends MRPHP
{
    private const ID_EMPRESA = 1;

    // ===================================================================
    // DASHBOARD
    // ===================================================================

    public function index()
    {
        $this->setTemplate('financeiro');
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

    /**
     * GET /financeiro/dados — payload principal: kpis, serie (mensal) e
     * clientes (rollup). Recebido/Resgatado são filtrados pela data de
     * pagamento; os demais (Em Dia/Atrasado/Inadimplente/Congelado) pela
     * data de vencimento — os dois grupos não somam entre si.
     */
    public function dados()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('financeiro_dados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();

            $kpis     = $this->calcularKpisFinanceiro($f);
            $serie    = $this->calcularSerieFinanceiro($f);
            $clientes = $this->calcularClientesFinanceiro($f);

            $response = [
                'kpis'     => $kpis,
                'serie'    => $serie,
                'clientes' => $clientes,
            ];
            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * GET /financeiro/parcelas — detalhe de parcelas de um cliente (sob
     * demanda, ao expandir a linha na tabela). Requer `customer`. Não
     * aplica o filtro de busca por nome/idqb (só o customer exato), igual
     * ao comportamento original.
     */
    public function parcelas()
    {
        try {
            $customer = $this->g('customer');
            if ($customer === null) {
                $this->jsonOut(['error' => 'Parâmetro customer é obrigatório.'], 400);
            }

            $cacheKey = $this->cacheKeyFromRequest('financeiro_parcelas');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();

            $response = ['rows' => $this->calcularParcelasFinanceiro($customer, $f)];
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
        $this->setTemplate('financeiro');
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
            'data_inicio' => $this->g('dataInicio'),
            'data_fim'    => $this->g('dataFim'),
            'nome'        => $this->g('nome'),
            'idqb'        => $this->g('idqb'),
        ];
    }

    /**
     * Fase 1C.4: reescrito pra gerar intervalo de data literal (sargável) em
     * vez de YEAR($col)=?/MONTH($col) IN(...) — mesmo fix já provado em
     * marketing/executive/law. Guarda de coluna-expressão mantida por
     * consistência com a referência, embora nenhuma chamada aqui passe
     * expressão (só colunas simples de invoice).
     */
    private function buildDateFilter(string $col, ?string $year, ?string $months): array
    {
        if (!preg_match('/^[A-Za-z_]\w*\.\w+$/', $col)) {
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

    /**
     * Filtro de período: range (data_inicio/data_fim, aceita half-open — só
     * um dos dois preenchido já ativa o range) OU year/month. Comportamento
     * `||` preservado como já era neste arquivo (diferente de executive, que
     * exige os dois campos — cada controller manteve o que já tinha).
     */
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

    /**
     * Filtro de busca por nome/idqb — equivalente a $buscaWhere/$buscaParams
     * do original. `$alias` é o alias da tabela de origem na query principal
     * (`i` para invoice); a subquery de resolução do customerQB_invoice é
     * sempre contra `invoice`.
     */
    private function buildBuscaFilter(string $alias, string $colCustomer, ?string $nome, ?string $idqb): array
    {
        $conds  = [];
        $params = [];

        if ($nome !== null && $nome !== '') {
            $conds[]  = "$alias.$colCustomer IN (SELECT customerQB_invoice FROM invoice WHERE nomeQB_invoice LIKE ?)";
            $params[] = '%' . $nome . '%';
        }
        if ($idqb !== null && $idqb !== '') {
            $conds[]  = "$alias.$colCustomer IN (SELECT customerQB_invoice FROM invoice WHERE idQB_invoice = ? OR customerQB_invoice = ?)";
            $params[] = $idqb;
            $params[] = $idqb;
        }

        return ['sql' => $conds ? ' AND ' . implode(' AND ', $conds) : '', 'params' => $params];
    }

    // ===================================================================
    // CÁLCULOS: KPIS / SÉRIE / CLIENTES / PARCELAS
    // ===================================================================

    /**
     * KPIs financeiros — 2 blocos: recebido/resgatado (por data de
     * pagamento) e saldos por status (por data de vencimento).
     */
    /**
     * Fase 1C.4: as antigas sqlKpiPag/sqlKpiDue (2 queries em invoice, cada
     * uma com seu próprio filtro de data — pagamento vs vencimento) viram 1
     * query só, cada métrica com sua condição de data embutida no próprio
     * SUM/COUNT(CASE) — mesmo princípio das queries "Unificado" do executive.
     */
    private function calcularKpisFinanceiro(array $f): array
    {
        $dfPag  = $this->buildPeriodFilter('i.dataQBPagamento_invoice', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $dfDue  = $this->buildPeriodFilter('i.dueDateQB_invoice', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $busca  = $this->buildBuscaFilter('i', 'customerQB_invoice', $f['nome'], $f['idqb']);

        $empresa = self::ID_EMPRESA;

        $condPag = "{$dfPag['sql']} AND i.dataQBPagamento_invoice IS NOT NULL AND i.dataQBPagamento_invoice > '0000-00-00'";
        $condDue = "{$dfDue['sql']} AND i.dueDateQB_invoice IS NOT NULL AND i.dueDateQB_invoice > '0000-00-00'";

        $sqlKpi = "
            SELECT
                SUM(CASE WHEN $condPag AND ballancedQB_invoice < amountQB_invoice
                          THEN amountQB_invoice - ballancedQB_invoice ELSE 0 END) AS recebido,
                COUNT(CASE WHEN $condPag AND ballancedQB_invoice < amountQB_invoice THEN 1 END) AS cRecebido,
                SUM(CASE WHEN $condPag AND DATEDIFF(dataQBPagamento_invoice, dueDateQB_invoice) > 29
                          THEN amountQB_invoice - ballancedQB_invoice ELSE 0 END) AS resgatado,
                COUNT(CASE WHEN $condPag AND DATEDIFF(dataQBPagamento_invoice, dueDateQB_invoice) > 29 THEN 1 END) AS cResgatado,
                SUM(CASE WHEN $condDue AND status_invoice = 'Em dia' AND ballancedQB_invoice > 0 THEN ballancedQB_invoice ELSE 0 END) AS emDia,
                COUNT(CASE WHEN $condDue AND status_invoice = 'Em dia' AND ballancedQB_invoice > 0 THEN 1 END) AS cEmDia,
                SUM(CASE WHEN $condDue AND status_invoice = 'Atrasado'     THEN ballancedQB_invoice ELSE 0 END) AS atrasado,
                COUNT(CASE WHEN $condDue AND status_invoice = 'Atrasado'     THEN 1 END) AS cAtrasado,
                SUM(CASE WHEN $condDue AND status_invoice = 'Inadimplente'  THEN ballancedQB_invoice ELSE 0 END) AS inadimplente,
                COUNT(CASE WHEN $condDue AND status_invoice = 'Inadimplente' THEN 1 END) AS cInadimplente,
                SUM(CASE WHEN $condDue AND status_invoice = 'Congelado'     THEN ballancedQB_invoice ELSE 0 END) AS congelado,
                COUNT(CASE WHEN $condDue AND status_invoice = 'Congelado'    THEN 1 END) AS cCongelado
            FROM invoice i
            WHERE i.id_empresa = $empresa
              {$busca['sql']}
        ";
        $params = array_merge(
            $dfPag['params'], $dfPag['params'], $dfPag['params'], $dfPag['params'],
            $dfDue['params'], $dfDue['params'], $dfDue['params'], $dfDue['params'], $dfDue['params'], $dfDue['params'], $dfDue['params'], $dfDue['params'],
            $busca['params']
        );
        $kpi = $this->queryOne($sqlKpi, $params);
        $kpiPag = $kpi;
        $kpiDue = $kpi;

        $saldoVencido = (float)($kpiDue['atrasado'] ?? 0) + (float)($kpiDue['inadimplente'] ?? 0) + (float)($kpiDue['congelado'] ?? 0);

        return [
            'recebido'      => (float)($kpiPag['recebido'] ?? 0),
            'cRecebido'     => (int)($kpiPag['cRecebido'] ?? 0),
            'resgatado'     => (float)($kpiPag['resgatado'] ?? 0),
            'cResgatado'    => (int)($kpiPag['cResgatado'] ?? 0),
            'emDia'         => (float)($kpiDue['emDia'] ?? 0),
            'cEmDia'        => (int)($kpiDue['cEmDia'] ?? 0),
            'atrasado'      => (float)($kpiDue['atrasado'] ?? 0),
            'cAtrasado'     => (int)($kpiDue['cAtrasado'] ?? 0),
            'inadimplente'  => (float)($kpiDue['inadimplente'] ?? 0),
            'cInadimplente' => (int)($kpiDue['cInadimplente'] ?? 0),
            'congelado'     => (float)($kpiDue['congelado'] ?? 0),
            'cCongelado'    => (int)($kpiDue['cCongelado'] ?? 0),
            'saldoVencido'  => $saldoVencido,
        ];
    }

    /**
     * Série mensal combinada: recebido/resgatado (por pagamento) e
     * em-dia/vencido (por vencimento), mesclados por mês (ym).
     */
    /**
     * Fase 1C.4: as antigas sqlSeriePag/sqlSerieDue viram UNION ALL de uma
     * query só, marcadas por "tipo" (mesmo princípio de
     * calcularGraficoEspecialistas() do executive) — os 2 ramos agrupam por
     * uma coluna de data diferente cada (pagamento vs vencimento), então não
     * dá pra virar SUM(CASE) numa linha só como em calcularKpisFinanceiro().
     */
    private function calcularSerieFinanceiro(array $f): array
    {
        $dfPag = $this->buildPeriodFilter('i.dataQBPagamento_invoice', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $dfDue = $this->buildPeriodFilter('i.dueDateQB_invoice', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $busca = $this->buildBuscaFilter('i', 'customerQB_invoice', $f['nome'], $f['idqb']);

        $empresa = self::ID_EMPRESA;

        $sql = "
            SELECT
                DATE_FORMAT(dataQBPagamento_invoice, '%Y-%m') AS ym,
                'pag' AS tipo,
                SUM(CASE WHEN ballancedQB_invoice < amountQB_invoice
                          THEN amountQB_invoice - ballancedQB_invoice ELSE 0 END) AS v1,
                SUM(CASE WHEN DATEDIFF(dataQBPagamento_invoice, dueDateQB_invoice) > 29
                          THEN amountQB_invoice - ballancedQB_invoice ELSE 0 END) AS v2
            FROM invoice i
            WHERE i.id_empresa = $empresa
              AND i.dataQBPagamento_invoice IS NOT NULL AND i.dataQBPagamento_invoice > '0000-00-00'
              AND {$dfPag['sql']}
              {$busca['sql']}
            GROUP BY ym

            UNION ALL

            SELECT
                DATE_FORMAT(dueDateQB_invoice, '%Y-%m') AS ym,
                'due' AS tipo,
                SUM(CASE WHEN status_invoice = 'Em dia' AND ballancedQB_invoice > 0 THEN ballancedQB_invoice ELSE 0 END) AS v1,
                SUM(CASE WHEN status_invoice IN ('Atrasado','Inadimplente','Congelado') THEN ballancedQB_invoice ELSE 0 END) AS v2
            FROM invoice i
            WHERE i.id_empresa = $empresa
              AND i.dueDateQB_invoice IS NOT NULL AND i.dueDateQB_invoice > '0000-00-00'
              AND {$dfDue['sql']}
              {$busca['sql']}
            GROUP BY ym
        ";
        $params = array_merge(
            $dfPag['params'], $busca['params'],
            $dfDue['params'], $busca['params']
        );
        $rows = $this->queryAll($sql, $params);

        $serieMap = [];
        foreach ($rows as $r) {
            if ($r['tipo'] === 'pag') {
                $serieMap[$r['ym']]['rec']  = (float)$r['v1'];
                $serieMap[$r['ym']]['resg'] = (float)$r['v2'];
            } else {
                $serieMap[$r['ym']]['emd'] = (float)$r['v1'];
                $serieMap[$r['ym']]['atr'] = (float)$r['v2'];
            }
        }
        ksort($serieMap);

        $serie = [];
        foreach ($serieMap as $ym => $vals) {
            $serie[] = [
                'ym'   => $ym,
                'rec'  => $vals['rec']  ?? null,
                'emd'  => $vals['emd']  ?? null,
                'atr'  => $vals['atr']  ?? null,
                'resg' => $vals['resg'] ?? null,
            ];
        }
        return $serie;
    }

    /**
     * Tabela de clientes — agregado por customerQB_invoice. Status por
     * prioridade: Inadimplente > Congelado > Atrasado > Em dia.
     */
    private function calcularClientesFinanceiro(array $f): array
    {
        $dfDue = $this->buildPeriodFilter('i.dueDateQB_invoice', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        $busca = $this->buildBuscaFilter('i', 'customerQB_invoice', $f['nome'], $f['idqb']);

        $empresa = self::ID_EMPRESA;

        $sql = "
            SELECT
                i.customerQB_invoice AS customerId,
                MAX(i.nomeQB_invoice) AS nome,
                CASE
                    WHEN SUM(CASE WHEN i.status_invoice = 'Inadimplente' THEN 1 ELSE 0 END) > 0 THEN 'Inadimplente'
                    WHEN SUM(CASE WHEN i.status_invoice = 'Congelado'    THEN 1 ELSE 0 END) > 0 THEN 'Congelado'
                    WHEN SUM(CASE WHEN i.status_invoice = 'Atrasado'     THEN 1 ELSE 0 END) > 0 THEN 'Atrasado'
                    ELSE 'Em dia'
                END AS status,
                COUNT(*) AS qtdParcelas,
                SUM(i.amountQB_invoice) AS valorTotal,
                SUM(i.amountQB_invoice - i.ballancedQB_invoice) AS valorPago,
                SUM(i.ballancedQB_invoice) AS saldo
            FROM invoice i
            WHERE i.id_empresa = $empresa
              AND i.dueDateQB_invoice IS NOT NULL AND i.dueDateQB_invoice > '0000-00-00'
              AND {$dfDue['sql']}
              {$busca['sql']}
            GROUP BY i.customerQB_invoice
            ORDER BY saldo DESC
        ";

        return $this->queryAll($sql, array_merge($dfDue['params'], $busca['params']));
    }

    /**
     * Detalhe de parcelas de um cliente específico. Não aplica o filtro de
     * busca por nome/idqb — só o customer exato (mesmo comportamento do
     * original). O fallback de email/telefone para a tabela legada `deal`
     * é preservado como está no original: o JOIN existe, mas os campos só
     * vêm de `dealV2` (o fallback nunca foi de fato implementado na fonte).
     */
    private function calcularParcelasFinanceiro(string $customer, array $f): array
    {
        $dfDue = $this->buildPeriodFilter('i.dueDateQB_invoice', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);

        $empresa = self::ID_EMPRESA;

        $sql = "
            SELECT
                i.idQB_invoice            AS idqb,
                d.email_dealV2            AS email,
                d.telefone_dealV2         AS telefone,
                i.amountQB_invoice        AS valorParcela,
                (i.amountQB_invoice - i.ballancedQB_invoice) AS valorPago,
                i.ballancedQB_invoice     AS saldo,
                i.dataQBPagamento_invoice AS dataPagamento,
                i.dueDateQB_invoice       AS dataVencimento,
                i.status_invoice          AS status
            FROM invoice i
            LEFT JOIN dealV2 d  ON d.idBitrix_dealV2 = i.idDealBitrix_invoice
            LEFT JOIN deal   de ON de.idBitrix_deal   = i.idDealBitrix_invoice
            WHERE i.id_empresa = $empresa
              AND i.customerQB_invoice = ?
              AND i.dueDateQB_invoice IS NOT NULL AND i.dueDateQB_invoice > '0000-00-00'
              AND {$dfDue['sql']}
            ORDER BY i.dueDateQB_invoice DESC
        ";

        $params = array_merge([$customer], $dfDue['params']);

        return $this->queryAll($sql, $params);
    }
}
