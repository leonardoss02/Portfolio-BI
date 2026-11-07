<?php

/**
 * Dashboard Comercial 4 (pós-venda jurídico/imigratório) — MRPHP.
 *
 * Port do relatório Power BI "Dashboard completo Comercial 4". Acompanha os
 * casos de dealV2 no funil Bitrix C3: (Aplicados → Milestone 1/2/3 → ciclo de
 * RFE/NOID — Request for Evidence / Notice of Intent to Deny), com um
 * ranking de clientes/vendas por 5 papéis (CSM, Case Manager, ADV ML1/2/3) e
 * um recorte de vendas por status/produto vindo de orcamentoV2/itemOrcamentoV2.
 *
 * "Vendido" — ETAPA_VENDIDO = "Vendido", mesmo valor da medida DAX original.
 * Até 30/09/2026 a etapa se chamava "Negociação fechada" na base e era usada
 * como equivalente; o Bitrix a renomeou para "Vendido" (etapaId APPROVED).
 *
 * Mesmo padrão de law_controle.php — sem camada calculos/, toda a lógica de
 * SQL/cálculo em métodos privados desta classe.
 *
 * View "Liga Acme-EUA" (/comercial4/liga + /comercial4/ligaDados): relatório
 * semanal da campanha de upsell do time de CS. Mapeados e conversão vêm do
 * JSON do Bitrix que o sync grava em dealV2.dataJson_dealV2; vendas, dos
 * orçamentos vendidos (ver seção "LIGA ACME-EUA" no fim).
 */
class comercial4 extends MRPHP
{
    /** Etapa que identifica "consulta a agendar" dentro do funil C3:. */
    private const FASE_ID_AGENDAR = 'C3:NEW';

    /** Etapa que identifica "reunião de arquitetura já agendada" dentro do funil C3:. */
    private const FASE_ID_AGENDADA = 'C3:PREPARATION';

    /** As 7 etapas do funil, na ordem de exibição — equivalente à tabela de
     *  parâmetro "Funil Status" do modelo Power BI de origem (coluna Ordem). */
    private const FUNIL_ETAPAS = [
        'Aplicados',
        'Milestone 1',
        'Milestone 2',
        'Milestone 3',
        'RFE/Noid Respondida',
        'RFE/Noid Recebida',
        'RFE Ajuste de status',
    ];

    /** Etapa de orcamentoV2 equivalente a "venda fechada" (ver docblock da classe). */
    private const ETAPA_VENDIDO = 'Vendido';

    /**
     * Mapa papel (UI) → coluna de dealV2 usada para agrupar/filtrar o
     * ranking. Nunca interpolar o parâmetro `papel` vindo do usuário direto
     * no SQL — sempre resolver pela whitelist abaixo.
     */
    private const PAPEL_COLUNA = [
        'csm'         => 'responsavelComercial4_dealV2',
        'caseManager' => 'advogadoAtendimento_dealV2',
        'advMl1'      => 'advMl1_dealV2',
        'advMl2'      => 'advMl2_dealV2',
        'advMl3'      => 'advMl3_dealV2',
    ];

    private const PAGINA_TAMANHO = 20;

    /** Teto de segurança para o export "tudo" (sem paginação) da tabela de registros. */
    private const EXPORT_TETO = 5000;

    public function index()
    {
        $this->setTemplate('comercial4');
    }

    public function dicionario()
    {
        $this->setTemplate('comercial4');
    }

    /** GET /comercial4/liga — relatório "Liga Acme-EUA" (dados via ligaDados()). */
    public function liga()
    {
        $this->setTemplate('comercial4');
    }

    // ===================================================================
    // HELPERS: CACHE EM ARQUIVO (.mrphp/cache) — cópia de law_controle.php,
    // sem camada compartilhada (convenção do projeto).
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

    // ===================================================================
    // ENDPOINTS JSON
    // ===================================================================

    /** GET /comercial4/dados — KPIs + funil de 7 etapas. */
    public function dados()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('comercial4_dados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();
            $response = [
                'meta'  => $f,
                'kpis'  => $this->calcularKpis($f),
                'funil' => $this->calcularFunil($f),
            ];
            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /comercial4/ranking?papel=csm|caseManager|advMl1|advMl2|advMl3 — clientes + vendas por pessoa do papel selecionado. */
    public function ranking()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('comercial4_ranking');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $papel  = $this->g('papel') ?? 'csm';
            $coluna = self::PAPEL_COLUNA[$papel] ?? self::PAPEL_COLUNA['csm'];

            $f        = $this->lerFiltros();
            $response = ['papel' => $papel, 'linhas' => $this->calcularRanking($f, $coluna)];
            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /comercial4/performance — vendas fechadas agrupadas por status (fase_dealV2) e por produto. */
    public function performance()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('comercial4_performance');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f        = $this->lerFiltros();
            $response = [
                'porStatus'  => $this->calcularPerformancePorStatus($f),
                'porProduto' => $this->calcularPerformancePorProduto($f),
            ];
            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * GET /comercial4/registros?pagina=1&busca=... — tabela paginada de
     * orçamentos do funil C3:. `busca` filtra (server-side, LIKE) por nome do
     * cliente/IdBitrix/responsável/etapa/fase — a tabela tem ~17 mil
     * linhas no total, grande demais para carregar tudo e filtrar no
     * navegador (padrão de `consultacom2`). `tudo=1` ignora a paginação (com
     * teto de segurança) para o botão de exportar CSV/Excel trazer todas as
     * linhas do recorte de filtros atual, não só a página visível.
     */
    public function registros()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('comercial4_registros');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $pagina   = max(1, (int)($this->g('pagina') ?? 1));
            $busca    = $this->g('busca');
            $tudo     = $this->g('tudo') === '1';
            $ordem    = $this->g('ordem');
            $dir      = $this->g('dir');
            $f        = $this->lerFiltros();
            $response = $this->calcularRegistros($f, $pagina, $busca, $tudo, $ordem, $dir);
            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /comercial4/opcoes — listas distintas para os dropdowns de busca (Produtos/Deal/CSM/Case Manager/ADV ML1-3). */
    public function opcoes()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('comercial4_opcoes');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DROPDOWN);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $produtos = array_column($this->queryAll("
                SELECT DISTINCT io.nomeOriginalProduto_itemOrcamentoV2 AS v
                FROM dealV2 d
                JOIN orcamentoV2 o ON {$this->joinDeal('d', 'o')}
                JOIN itemOrcamentoV2 io ON io.id_orcamentoV2 = o.id_orcamentoV2
                WHERE d.faseId_dealV2 LIKE 'C3:%'
                  AND io.nomeOriginalProduto_itemOrcamentoV2 IS NOT NULL AND io.nomeOriginalProduto_itemOrcamentoV2 != ''
                ORDER BY v
            "), 'v');

            $data = [
                'produtos'    => $produtos,
                'csm'         => $this->distinctColuna('responsavelComercial4_dealV2'),
                'caseManager' => $this->distinctColuna('advogadoAtendimento_dealV2'),
                'advMl1'      => $this->distinctColuna('advMl1_dealV2'),
                'advMl2'      => $this->distinctColuna('advMl2_dealV2'),
                'advMl3'      => $this->distinctColuna('advMl3_dealV2'),
                'nomeDeal'    => $this->distinctColuna('nome_dealV2'),
            ];
            $this->cacheSet($cacheKey, $data);
            $this->jsonOut($data);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * GET /comercial4/ligaDados — números da view "Liga Acme-EUA" no formato
     * do objeto `D` do relatório HTML do CS, para o JS do relatório ser
     * reaproveitado quase sem mudança. Sem filtros: chave de cache fixa.
     *
     * Mapeados e conversão saem do JSON do Bitrix no negócio (estado atual);
     * vendas, dos orçamentos (ligaCarregarVendas()). `vendas` sai null sem
     * nenhuma venda no período da campanha.
     */
    public function ligaDados()
    {
        try {
            $cached = $this->cacheGet('comercial4_liga', self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $deals     = $this->ligaCarregarDeals();
            $map       = $this->ligaMapeados($deals);
            $cambio    = $this->ligaCarregarCambio();
            $vendas    = $this->ligaVendasLista($this->ligaCarregarVendas(), $cambio);
            $quinzenas = $this->ligaQuinzenas($map['eq'], $vendas);

            $anterior      = $quinzenas[count($quinzenas) - 2] ?? null;
            $map['ant_eq'] = $anterior['map_eq'] ?? null;

            $response = [
                'atualizado_em' => date('d/m/Y H:i'),
                'periodo'       => [
                    'inicio' => date('d/m/Y', strtotime(self::LIGA_INICIO)),
                    'ate'    => date('d/m/Y'),
                ],
                'meta'          => self::LIGA_META_VALOR,
                'map'           => $map,
                'conv'          => $this->ligaConversao($deals),
                'quinzenas'     => $quinzenas,
                'vendas'        => $this->ligaVendas($vendas, $cambio),
            ];
            $this->cacheSet('comercial4_liga', $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    private function distinctColuna(string $col): array
    {
        return array_column($this->queryAll("
            SELECT DISTINCT d.$col AS v
            FROM dealV2 d
            WHERE d.faseId_dealV2 LIKE 'C3:%'
              AND d.$col IS NOT NULL AND d.$col != ''
            ORDER BY v
        "), 'v');
    }

    // ===================================================================
    // HELPERS: HTTP / JSON (padrão idêntico ao de law_controle.php)
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
    // HELPERS: FILTROS (buildDateFilter/buildInFilter/buildPeriodFilter —
    // cópia literal de law_controle.php, já sargáveis).
    // ===================================================================

    private function lerFiltros(): array
    {
        return [
            'year'        => $this->g('year'),
            'months'      => $this->g('months'),
            'data_inicio' => $this->g('data_inicio'),
            'data_fim'    => $this->g('data_fim'),
            'fases'       => $this->g('fases'),
            'produtos'    => $this->g('produtos'),
            'csm'         => $this->g('csm'),
            'caseManager' => $this->g('caseManager'),
            'advMl1'      => $this->g('advMl1'),
            'advMl2'      => $this->g('advMl2'),
            'advMl3'      => $this->g('advMl3'),
            'nomeDeal'    => $this->gLista('nomeDeal'),
        ];
    }

    /**
     * Lista vinda como `?chave[]=a&chave[]=b` (não CSV): nome_dealV2 pode
     * conter vírgula e espaço na borda, então os valores seguem exatamente
     * como estão no banco — sem explode(',') nem trim.
     */
    private function gLista(string $key): ?array
    {
        $v = $_GET[$key] ?? null;
        if (is_string($v)) {
            $v = [$v];
        }
        if (!is_array($v)) {
            return null;
        }
        $vals = array_values(array_filter($v, fn($x) => is_string($x) && $x !== ''));
        return $vals ?: null;
    }

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

    /** Igual a buildInFilter(), mas recebendo a lista já pronta de gLista(). */
    private function buildInFilterLista(string $col, ?array $vals): array
    {
        if (!$vals) {
            return ['sql' => '', 'params' => []];
        }
        return ['sql' => "AND $col IN (" . implode(',', array_fill(0, count($vals), '?')) . ")", 'params' => $vals];
    }

    private function buildPeriodFilter(string $col, $year, ?string $months, ?string $dataInicio = null, ?string $dataFim = null): array
    {
        if ($dataInicio !== null && $dataInicio !== '' && $dataFim !== null && $dataFim !== '') {
            return ['sql' => "$col >= ? AND $col <= ?", 'params' => [$dataInicio, $dataFim . ' 23:59:59']];
        }
        return $this->buildDateFilter($col, $year, $months);
    }

    /**
     * Filtro-base comum a todas as queries do dashboard (aplicado ao alias
     * `d` de dealV2): período, fases do funil, os 5 papéis (CSM/Case
     * Manager/ADV ML1-3) e o nome do deal. O filtro de Produtos vira um EXISTS contra
     * orcamentoV2/itemOrcamentoV2 (não é coluna de dealV2).
     *
     * `periodoVenda` é o período das 4 métricas de venda (KPI Vendas, vendas
     * do ranking, Performance por Status/Produto) — aplicado ao alias `o` de
     * orcamentoV2, ver periodoVenda(). As demais métricas usam `periodo`.
     */
    private function filtrosBase(array $f): array
    {
        return [
            'periodo'     => $this->buildPeriodFilter('d.criadoEm_dealV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']),
            'periodoVenda' => $this->periodoVenda($f),
            'fases'       => $this->buildInFilter('d.fase_dealV2', $f['fases']),
            'csm'         => $this->buildInFilter('d.responsavelComercial4_dealV2', $f['csm']),
            'caseManager' => $this->buildInFilter('d.advogadoAtendimento_dealV2', $f['caseManager']),
            'advMl1'      => $this->buildInFilter('d.advMl1_dealV2', $f['advMl1']),
            'advMl2'      => $this->buildInFilter('d.advMl2_dealV2', $f['advMl2']),
            'advMl3'      => $this->buildInFilter('d.advMl3_dealV2', $f['advMl3']),
            'produtos'    => $this->filtroProdutoExists($f['produtos']),
            'nomeDeal'    => $this->buildInFilterLista('d.nome_dealV2', $f['nomeDeal']),
        ];
    }

    /**
     * Período das métricas de venda pela data da venda do orçamento
     * (dataVenda_orcamentoV2), não pela criação do deal. Orçamento sem data
     * de venda vem como '0000-00-00' (não NULL) e fica fora mesmo sem
     * filtro de período.
     */
    private function periodoVenda(array $f): array
    {
        $p = $this->buildPeriodFilter('o.dataVenda_orcamentoV2', $f['year'], $f['months'], $f['data_inicio'], $f['data_fim']);
        return ['sql' => "o.dataVenda_orcamentoV2 > '0000-00-00' AND ({$p['sql']})", 'params' => $p['params']];
    }

    /** Filtro de Produtos: EXISTS contra orcamentoV2/itemOrcamentoV2 via joinDeal() (não é coluna própria de dealV2). */
    private function filtroProdutoExists(?string $produtosCsv): array
    {
        // Aliases próprios (op/iop), distintos dos usados pelas queries que
        // chamam esta subquery (elas já têm seus próprios `o`/`io` — cada
        // FROM é um escopo à parte em SQL, mas nomes distintos evitam
        // qualquer ambiguidade de leitura.
        $in = $this->buildInFilter('iop.nomeOriginalProduto_itemOrcamentoV2', $produtosCsv);
        if ($in['sql'] === '') {
            return ['sql' => '', 'params' => []];
        }
        $condSemAnd = preg_replace('/^AND\s+/', '', $in['sql']);
        return [
            'sql' => "AND EXISTS (
                SELECT 1 FROM orcamentoV2 op
                JOIN itemOrcamentoV2 iop ON iop.id_orcamentoV2 = op.id_orcamentoV2
                WHERE {$this->joinDeal('d', 'op')} AND $condSemAnd
            )",
            'params' => $in['params'],
        ];
    }

    /**
     * Condição de JOIN entre dealV2 e orcamentoV2.
     *
     * A chave correta é `orcamentoV2.dealId_orcamentoV2` → `dealV2.idBitrix_dealV2`
     * (índice ÚNICO em idBitrix_dealV2, dealId_orcamentoV2 preenchido em 100%
     * dos orçamentos, batendo em 99,94% dos casos — confirmado ao vivo via
     * /sql/query). **Não usar contactId** para este join: cheguei a implementar
     * assim (replicando a relação N:N do modelo Power BI original,
     * `BaseOrcamento[contactId_orcamentoV2] → BaseDeal`), mas contactId não é
     * uma chave confiável aqui — um contato pode ter mais de um deal ativo no
     * funil C3: (fan-out), e ~92 deals + ~699 orçamentos têm contactId em
     * branco (`''`), o que casava TODO orçamento em branco com TODO deal em
     * branco: 64.308 das 81.642 linhas que a tabela de registros mostrava
     * (79%!) eram esse produto cartesiano espúrio, sem relação real nenhuma.
     * Trocar para dealId_orcamentoV2 derrubou o total para 17.103 (o real) e
     * de ~3,5s para 7-70ms (índice único em vez de full scan).
     *
     * Mesmo assim há mismatch de collation entre os dois lados
     * (dealV2.idBitrix_dealV2 é latin1_swedish_ci, orcamentoV2.dealId_orcamentoV2
     * é utf8mb3_uca1400_ai_ci) — mesmo fix de CONVERT do gotcha já documentado
     * (ver CLAUDE.md, evento.id_evento×idEvento_dealV2).
     */
    private function joinDeal(string $aliasDeal, string $aliasOrcamento): string
    {
        return "$aliasDeal.idBitrix_dealV2 = CONVERT($aliasOrcamento.dealId_orcamentoV2 USING latin1)";
    }

    private function fragmentosSql(array $b): string
    {
        return "{$b['fases']['sql']} {$b['csm']['sql']} {$b['caseManager']['sql']} {$b['advMl1']['sql']} {$b['advMl2']['sql']} {$b['advMl3']['sql']} {$b['produtos']['sql']} {$b['nomeDeal']['sql']}";
    }

    /** $periodo = 'periodoVenda' nas queries de venda (mesma posição no SQL que 'periodo'). */
    private function paramsBase(array $b, string $periodo = 'periodo'): array
    {
        return array_merge(
            $b[$periodo]['params'],
            $b['fases']['params'],
            $b['csm']['params'],
            $b['caseManager']['params'],
            $b['advMl1']['params'],
            $b['advMl2']['params'],
            $b['advMl3']['params'],
            $b['produtos']['params'],
            $b['nomeDeal']['params']
        );
    }

    // ===================================================================
    // CÁLCULOS: KPIS + FUNIL
    // ===================================================================

    /**
     * Total de Clientes + Arquiteturas a Agendar + Agendadas em uma única
     * passada por dealV2 (Fase 1C.4 — consolidação de KPIs que batem na
     * mesma tabela+filtro).
     */
    private function calcularKpis(array $f): array
    {
        $b      = $this->filtrosBase($f);
        $params = $this->paramsBase($b);

        $sql = "
            SELECT
              COUNT(*) AS total_clientes,
              SUM(CASE WHEN d.faseId_dealV2 = '" . self::FASE_ID_AGENDAR . "' THEN 1 ELSE 0 END) AS arquiteturas_agendar,
              SUM(CASE WHEN d.faseId_dealV2 = '" . self::FASE_ID_AGENDADA . "' THEN 1 ELSE 0 END) AS agendadas
            FROM dealV2 d
            WHERE d.faseId_dealV2 LIKE 'C3:%'
              AND ({$b['periodo']['sql']})
              {$this->fragmentosSql($b)}
        ";
        $row = $this->queryOne($sql, $params);

        return [
            'total_clientes'        => (int)($row['total_clientes'] ?? 0),
            'vendas'                => $this->calcularVendas($f),
            'arquiteturas_agendar'  => (int)($row['arquiteturas_agendar'] ?? 0),
            'agendadas'             => (int)($row['agendadas'] ?? 0),
        ];
    }

    private function calcularVendas(array $f): int
    {
        $b      = $this->filtrosBase($f);
        $params = array_merge([self::ETAPA_VENDIDO], $this->paramsBase($b, 'periodoVenda'));

        $sql = "
            SELECT COUNT(DISTINCT o.id_orcamentoV2) AS vendas
            FROM dealV2 d
            JOIN orcamentoV2 o ON {$this->joinDeal('d', 'o')} AND o.etapa_orcamentoV2 = ?
            WHERE d.faseId_dealV2 LIKE 'C3:%'
              AND ({$b['periodoVenda']['sql']})
              {$this->fragmentosSql($b)}
        ";
        $row = $this->queryOne($sql, $params);
        return (int)($row['vendas'] ?? 0);
    }

    /**
     * Funil de 7 etapas: contagem e valor (valorAssessoria_dealV2) por
     * fase_dealV2, numa única passada (SUM(CASE WHEN...) por etapa em vez de
     * 1 query por etapa). % do anterior calculado em PHP a partir do array
     * já ordenado (equivalente à medida DAX "04 Percentual do anterior").
     */
    private function calcularFunil(array $f): array
    {
        $b      = $this->filtrosBase($f);
        $params = $this->paramsBase($b);

        $selects = [];
        foreach (self::FUNIL_ETAPAS as $i => $etapa) {
            $selects[] = "SUM(CASE WHEN d.fase_dealV2 = '" . addslashes($etapa) . "' THEN 1 ELSE 0 END) AS qtd_$i";
            $selects[] = "SUM(CASE WHEN d.fase_dealV2 = '" . addslashes($etapa) . "' THEN d.valorAssessoria_dealV2 ELSE 0 END) AS valor_$i";
        }

        $sql = "
            SELECT " . implode(', ', $selects) . "
            FROM dealV2 d
            WHERE d.faseId_dealV2 LIKE 'C3:%'
              AND ({$b['periodo']['sql']})
              {$this->fragmentosSql($b)}
        ";
        $row = $this->queryOne($sql, $params);

        $funil = [];
        foreach (self::FUNIL_ETAPAS as $i => $etapa) {
            $funil[] = [
                'etapa' => $etapa,
                'qtd'   => (int)($row["qtd_$i"] ?? 0),
                'valor' => (float)($row["valor_$i"] ?? 0),
            ];
        }

        foreach ($funil as $i => &$etapa) {
            $etapa['pct_anterior'] = ($i === 0 || $funil[$i - 1]['qtd'] === 0)
                ? null
                : $etapa['qtd'] / $funil[$i - 1]['qtd'];
        }
        unset($etapa);

        return $funil;
    }

    // ===================================================================
    // CÁLCULOS: RANKING POR PAPEL (CSM/Case Manager/ADV ML1-3)
    // ===================================================================

    /**
     * "Clientes" (contagem de dealV2) e "Vendas" (contagem de orçamentos
     * fechados via joinDeal(), dealId_orcamentoV2 → idBitrix_dealV2)
     * agrupados pela coluna do papel selecionado — 2 queries agregadas,
     * mescladas em PHP (evita subquery correlacionada por linha).
     *
     * Clientes seguem o período pela criação do deal; vendas, pela data da
     * venda (periodoVenda()) — por isso a mescla é pela união dos nomes:
     * quem vendeu no período sem ter deal criado nele entra com 0 clientes.
     */
    private function calcularRanking(array $f, string $coluna): array
    {
        $b      = $this->filtrosBase($f);
        $params = $this->paramsBase($b);

        $sqlClientes = "
            SELECT d.$coluna AS nome, COUNT(*) AS clientes
            FROM dealV2 d
            WHERE d.faseId_dealV2 LIKE 'C3:%'
              AND d.$coluna IS NOT NULL AND d.$coluna != ''
              AND ({$b['periodo']['sql']})
              {$this->fragmentosSql($b)}
            GROUP BY d.$coluna
        ";
        $clientesPorNome = [];
        foreach ($this->queryAll($sqlClientes, $params) as $r) {
            $clientesPorNome[$r['nome']] = (int)$r['clientes'];
        }

        $paramsVendas = array_merge([self::ETAPA_VENDIDO], $this->paramsBase($b, 'periodoVenda'));
        $sqlVendas = "
            SELECT d.$coluna AS nome, COUNT(DISTINCT o.id_orcamentoV2) AS vendas
            FROM dealV2 d
            JOIN orcamentoV2 o ON {$this->joinDeal('d', 'o')} AND o.etapa_orcamentoV2 = ?
            WHERE d.faseId_dealV2 LIKE 'C3:%'
              AND d.$coluna IS NOT NULL AND d.$coluna != ''
              AND ({$b['periodoVenda']['sql']})
              {$this->fragmentosSql($b)}
            GROUP BY d.$coluna
        ";
        $vendasPorNome = [];
        foreach ($this->queryAll($sqlVendas, $paramsVendas) as $r) {
            $vendasPorNome[$r['nome']] = (int)$r['vendas'];
        }

        $linhas = [];
        foreach (array_keys($clientesPorNome + $vendasPorNome) as $nome) {
            $linhas[] = ['nome' => (string)$nome, 'clientes' => $clientesPorNome[$nome] ?? 0, 'vendas' => $vendasPorNome[$nome] ?? 0];
        }
        usort($linhas, fn($a, $c) => $c['clientes'] <=> $a['clientes']);

        return $linhas;
    }

    // ===================================================================
    // CÁLCULOS: PERFORMANCE DE VENDAS (por Status e por Produto)
    // ===================================================================

    /**
     * Mesma fórmula-base em ambos ("Qtd Vendas por Produto" do modelo
     * original): itens de orçamento fechado (ETAPA_VENDIDO) ligados a um
     * deal do funil C3: via joinDeal(), agrupados por fase_dealV2 ou por
     * nomeOriginalProduto_itemOrcamentoV2.
     */
    private function calcularPerformancePorStatus(array $f): array
    {
        $b      = $this->filtrosBase($f);
        $params = array_merge([self::ETAPA_VENDIDO], $this->paramsBase($b, 'periodoVenda'));

        $sql = "
            SELECT d.fase_dealV2 AS grupo, COUNT(DISTINCT io.id_itemOrcamentoV2) AS vendas
            FROM dealV2 d
            JOIN orcamentoV2 o ON {$this->joinDeal('d', 'o')} AND o.etapa_orcamentoV2 = ?
            JOIN itemOrcamentoV2 io ON io.id_orcamentoV2 = o.id_orcamentoV2
            WHERE d.faseId_dealV2 LIKE 'C3:%'
              AND ({$b['periodoVenda']['sql']})
              {$this->fragmentosSql($b)}
            GROUP BY d.fase_dealV2
            ORDER BY vendas DESC
        ";
        return array_map(fn($r) => ['grupo' => $r['grupo'], 'vendas' => (int)$r['vendas']], $this->queryAll($sql, $params));
    }

    private function calcularPerformancePorProduto(array $f): array
    {
        $b      = $this->filtrosBase($f);
        $params = array_merge([self::ETAPA_VENDIDO], $this->paramsBase($b, 'periodoVenda'));

        $sql = "
            SELECT io.nomeOriginalProduto_itemOrcamentoV2 AS grupo, COUNT(DISTINCT io.id_itemOrcamentoV2) AS vendas
            FROM dealV2 d
            JOIN orcamentoV2 o ON {$this->joinDeal('d', 'o')} AND o.etapa_orcamentoV2 = ?
            JOIN itemOrcamentoV2 io ON io.id_orcamentoV2 = o.id_orcamentoV2
            WHERE d.faseId_dealV2 LIKE 'C3:%'
              AND ({$b['periodoVenda']['sql']})
              {$this->fragmentosSql($b)}
            GROUP BY io.nomeOriginalProduto_itemOrcamentoV2
            ORDER BY vendas DESC
            LIMIT 20
        ";
        return array_map(fn($r) => ['grupo' => $r['grupo'], 'vendas' => (int)$r['vendas']], $this->queryAll($sql, $params));
    }

    // ===================================================================
    // CÁLCULOS: TABELA DE REGISTROS (orcamentoV2 × dealV2, paginada)
    // ===================================================================

    /**
     * Busca (server-side) por nome do cliente, IdBitrix, responsável, etapa
     * ou fase — a tabela de registros tem ~17 mil linhas no total, grande
     * demais para carregar tudo e filtrar no navegador como `consultacom2`
     * faz com listas menores.
     */
    private function buscaFilter(?string $busca): array
    {
        if ($busca === null || trim($busca) === '') {
            return ['sql' => '', 'params' => []];
        }
        $termo = '%' . trim($busca) . '%';
        return [
            'sql' => "AND (
                o.nome_orcamentoV2 LIKE ? OR
                o.idBitrix_orcamentoV2 LIKE ? OR
                d.responsavelComercial4_dealV2 LIKE ? OR
                o.etapa_orcamentoV2 LIKE ? OR
                d.fase_dealV2 LIKE ?
            )",
            'params' => array_fill(0, 5, $termo),
        ];
    }

    /**
     * Colunas ordenáveis da tabela de registros → expressão SQL real. Nunca
     * interpolar `ordem`/`dir` vindos do usuário direto no SQL — sempre
     * resolver por esta whitelist.
     *
     * `id_bitrix` usa CAST(...AS UNSIGNED): idBitrix_orcamentoV2 é VARCHAR,
     * então ORDER BY direto compara como texto ("1001" vem antes de "10011"
     * porque "1001" é prefixo de "10011") — o CAST força comparação numérica.
     */
    private const ORDEM_COLUNAS = [
        'nome_cliente' => 'o.nome_orcamentoV2',
        'id_bitrix'    => 'CAST(o.idBitrix_orcamentoV2 AS UNSIGNED)',
        'responsavel'  => 'd.responsavelComercial4_dealV2',
        'etapa'        => 'o.etapa_orcamentoV2',
        'fase'         => 'd.fase_dealV2',
    ];

    /**
     * $tudo=true (usado pelo botão de exportar) ignora a paginação e traz
     * todas as linhas do recorte de filtros+busca atual, até o teto de
     * segurança EXPORT_TETO — em vez de exportar só a página de 20 linhas
     * visível na tela.
     *
     * Ordenação é sempre server-side (não só da página visível): a tabela
     * tem ~17 mil linhas, ordenar client-side só reordenaria as 20 linhas
     * já carregadas, sem efeito real sobre o conjunto todo.
     */
    private function calcularRegistros(array $f, int $pagina, ?string $busca = null, bool $tudo = false, ?string $ordem = null, ?string $dir = null): array
    {
        $b        = $this->filtrosBase($f);
        $bf       = $this->buscaFilter($busca);
        $params   = array_merge($this->paramsBase($b), $bf['params']);
        $offset   = ($pagina - 1) * self::PAGINA_TAMANHO;
        $limite   = $tudo ? self::EXPORT_TETO : self::PAGINA_TAMANHO;
        $colOrdem = self::ORDEM_COLUNAS[$ordem] ?? null;
        $orderBy  = $colOrdem ? "$colOrdem " . ($dir === 'desc' ? 'DESC' : 'ASC') : 'o.criadoEm_orcamentoV2 DESC';

        $sqlTotal = "
            SELECT COUNT(*) AS total
            FROM orcamentoV2 o
            JOIN dealV2 d ON {$this->joinDeal('d', 'o')}
            WHERE d.faseId_dealV2 LIKE 'C3:%'
              AND ({$b['periodo']['sql']})
              {$this->fragmentosSql($b)}
              {$bf['sql']}
        ";
        $total = (int)($this->queryOne($sqlTotal, $params)['total'] ?? 0);

        $sql = "
            SELECT
              o.nome_orcamentoV2 AS nome_cliente,
              o.idBitrix_orcamentoV2 AS id_bitrix,
              d.responsavelComercial4_dealV2 AS responsavel,
              o.etapa_orcamentoV2 AS etapa,
              d.fase_dealV2 AS fase
            FROM orcamentoV2 o
            JOIN dealV2 d ON {$this->joinDeal('d', 'o')}
            WHERE d.faseId_dealV2 LIKE 'C3:%'
              AND ({$b['periodo']['sql']})
              {$this->fragmentosSql($b)}
              {$bf['sql']}
            ORDER BY $orderBy
            LIMIT $limite" . ($tudo ? '' : " OFFSET $offset") . "
        ";
        $linhas = $this->queryAll($sql, $params);

        return [
            'linhas'       => $linhas,
            'total'        => $total,
            'pagina'       => $tudo ? 1 : $pagina,
            'totalPaginas' => $tudo ? 1 : max(1, (int)ceil($total / self::PAGINA_TAMANHO)),
        ];
    }

    // ===================================================================
    // LIGA ACME-EUA (view /comercial4/liga — relatório semanal do CS)
    // ===================================================================

    /** Parâmetros fixos da campanha, tirados do relatório do CS. */
    private const LIGA_INICIO        = '2026-05-18';
    private const LIGA_META_VALOR    = 480000;
    private const LIGA_META_MAPEADOS = 378;
    private const LIGA_CARTEIRA      = 430;
    private const LIGA_META_CONV     = 25;

    /**
     * Etapa de orçamento que é venda, pelo código do Bitrix. No fim de
     * setembro/2026 "Negociação fechada" (UC_TI6PII) virou "Vendido" — nome
     * e código mudaram juntos; APPROVED é etapa padrão do Bitrix, menos
     * sujeita a renomear que as UC_*.
     */
    private const LIGA_ETAPA_VENDA = 'APPROVED';

    /** Ordinal por extenso do destaque "É o {ordinal} dos 10 produtos do mapeamento a registrar venda". */
    private const LIGA_ORDINAIS = ['primeiro', 'segundo', 'terceiro', 'quarto', 'quinto', 'sexto', 'sétimo', 'oitavo', 'nono', 'décimo'];

    /** CSMs da campanha: responsavelComercial4Id_dealV2 (ID de usuário do Bitrix) → nome curto usado no relatório. */
    private const LIGA_CSM = [
        '271' => 'Simone',
        '453' => 'Patrícia',
        '725' => 'Viviane',
        '727' => 'Tânia',
    ];

    /**
     * Os 10 produtos do mapeamento: campo de lista do Bitrix (chave do JSON
     * em dataJson_dealV2) → nome do produto. As chaves e os IDs de opção de
     * LIGA_STATUS_IDS foram decodificados cruzando o JSON com a exportação
     * CSV do pipe feita pelo CS em 25/09/2026 (99,95% de concordância) — o
     * JSON só traz o ID da opção, nunca o rótulo.
     */
    private const LIGA_PRODUTOS = [
        'UF_CRM_1754505193629' => 'Parceiro A - Inglês',
        'UF_CRM_1754505461677' => 'Parceiro B - Recolocação Profissional',
        'UF_CRM_1754507018954' => 'Parceiro C - Transporte Pet',
        'UF_CRM_1754507171249' => 'Parceiro D - Validação de Diplomas',
        'UF_CRM_1754507317558' => 'Parceiro E - Consultoria Tributária',
        'UF_CRM_1754507457898' => 'Parceiro F - Seguros Vida e Saúde',
        'UF_CRM_1754507589605' => 'Assessoria Living',
        'UF_CRM_1754507694629' => 'Assessoria Mídia/Fortalecimento de Perfil',
        'UF_CRM_1754508279125' => 'Parceiro G - Serviços Pessoais',
        'UF_CRM_1762775058734' => 'Parceiro H - Bolsa de Estudos',
    ];

    /**
     * ID de opção de lista → status. O mesmo rótulo tem um ID diferente em
     * cada um dos 10 campos. ID fora deste mapa = opção apagada no Bitrix (a
     * exportação mostra vazio) → conta como "sem status". "fechado_comercial"
     * é produto vendido junto com o combo pelo time comercial, não venda da
     * Liga.
     */
    private const LIGA_STATUS_IDS = [
        'iniciar'            => ['11237', '11817', '11825', '11829', '11833', '11837', '11845', '11849', '11841', '11953'],
        'abordagem'          => ['11681', '11909', '11279', '11293', '11307', '11321', '11335', '11349', '11363', '11957'],
        'sem_oportunidade'   => ['11239', '11249', '11721', '11713', '11697', '11729', '11737', '11745', '11753', '11959'],
        'em_negociacao'      => ['11241', '11689', '11281', '11295', '11309', '11323', '11337', '11351', '11365', '11961'],
        'orcamento_parceiro' => ['11243', '11251', '11339', '11353'],
        'declinada'          => ['11245', '11253', '11285', '11299', '11313', '11327', '11341', '11355', '11369', '11963'],
        'fechada'            => ['11247', '11255', '11287', '11301', '11315', '11343', '11357'],
        'interesse_futuro'   => ['11261', '11257', '11289', '11303', '11317', '11331', '11345', '11359', '11373', '11967'],
        'fechado_comercial'  => ['11259', '11347', '11361'],
    ];

    /**
     * Fotos de clientes mapeados por quinzena, anotadas pelo CS nos
     * relatórios semanais: nº da quinzena → [data da foto, mapeados]. O
     * Bitrix só guarda o estado atual, então quinzena já encerrada vem daqui
     * e a corrente é calculada ao vivo (ver ligaQuinzenas()).
     */
    private const LIGA_MAPEADOS_HIST = [
        1 => ['2026-05-29', 94],
        2 => ['2026-06-12', 283],
        3 => ['2026-06-26', 293],
        4 => ['2026-07-13', 299],
        5 => ['2026-07-27', 340],
        6 => ['2026-08-10', 377],
        7 => ['2026-08-24', 392],
        8 => ['2026-09-14', 398],
        9 => ['2026-09-21', 397],
    ];

    /** Dia em que a meta de mapeados foi batida pela 1ª vez e o total da foto daquele dia (relatório do CS). */
    private const LIGA_META_ALCANCADA = ['2026-08-17', 384];

    /** ID de opção → status (LIGA_STATUS_IDS invertido, montado uma vez por requisição). */
    private ?array $ligaStatusPorId = null;

    /**
     * Um negócio do funil C3: por linha, com o CSM, a data de criação e
     * `status` = os 10 status de produto já traduzidos por ligaStatus(), na
     * ordem de LIGA_PRODUTOS. JSON_VALUE sobre o JSON do Bitrix — as chaves
     * vêm só de LIGA_PRODUTOS (constante), nenhuma entrada do usuário entra
     * no SQL. ~1,5s nos ~1.760 negócios, por isso o cache em ligaDados().
     * Serve só a mapeados e conversão — as vendas vêm dos orçamentos (ver
     * ligaCarregarVendas()).
     */
    private function ligaCarregarDeals(): array
    {
        $colunas = [];
        foreach (array_keys(self::LIGA_PRODUTOS) as $i => $campo) {
            $colunas[] = sprintf('JSON_VALUE(d.dataJson_dealV2, \'$.%s\') AS p%d', $campo, $i);
        }

        $deals = $this->queryAll("
            SELECT d.responsavelComercial4Id_dealV2 AS csm,
                   d.criadoEm_dealV2 AS criado,
                   " . implode(",\n                   ", $colunas) . "
            FROM dealV2 d
            WHERE d.faseId_dealV2 LIKE 'C3:%'
        ");

        foreach ($deals as &$deal) {
            $deal['status'] = [];
            foreach (array_keys($colunas) as $i) {
                $deal['status'][] = $this->ligaStatus($deal["p$i"] ?? '');
                unset($deal["p$i"]);
            }
        }
        unset($deal);

        return $deals;
    }

    private function ligaStatus($idOpcao): ?string
    {
        if ($this->ligaStatusPorId === null) {
            $this->ligaStatusPorId = [];
            foreach (self::LIGA_STATUS_IDS as $status => $ids) {
                foreach ($ids as $id) {
                    $this->ligaStatusPorId[$id] = $status;
                }
            }
        }
        return $this->ligaStatusPorId[(string)$idOpcao] ?? null;
    }

    /**
     * Carteira da campanha: negócio de uma das CSMs da Liga criado antes do
     * início (18/05). criadoEm_dealV2 vem como 'Y-m-d H:i:s', então a
     * comparação de texto com 'Y-m-d' já exclui o próprio dia 18.
     */
    private function ligaNaCarteira(array $deal): bool
    {
        return isset(self::LIGA_CSM[(string)($deal['csm'] ?? '')])
            && strcmp((string)($deal['criado'] ?? ''), self::LIGA_INICIO) < 0;
    }

    /** Cliente mapeado = os 10 produtos já trabalhados: status conhecido e diferente de "Iniciar abordagem". */
    private function ligaClienteMapeado(array $deal): bool
    {
        foreach ($deal['status'] as $status) {
            if ($status === null || $status === 'iniciar') {
                return false;
            }
        }
        return true;
    }

    /**
     * Meta 2 · clientes mapeados da carteira, ao vivo. Regra confirmada com o
     * usuário em 28/09/2026: reproduziu os 397 do relatório de 21/09 na
     * exportação de 25/09.
     */
    private function ligaMapeados(array $deals): array
    {
        $eq = 0;
        foreach ($deals as $deal) {
            if ($this->ligaNaCarteira($deal) && $this->ligaClienteMapeado($deal)) {
                $eq++;
            }
        }

        [$dataMeta, $eqMeta] = self::LIGA_META_ALCANCADA;

        return [
            'eq'        => $eq,
            'meta'      => self::LIGA_META_MAPEADOS,
            'carteira'  => self::LIGA_CARTEIRA,
            'pct'       => round($eq / self::LIGA_META_MAPEADOS * 100, 1),
            'pct_cart'  => round($eq / self::LIGA_CARTEIRA * 100, 1),
            'excedente' => $eq - self::LIGA_META_MAPEADOS,
            'data_meta' => date('d/m', strtotime($dataMeta)),
            'eq_meta'   => $eqMeta,
            'inicio'    => self::LIGA_MAPEADOS_HIST[1][1],
        ];
    }

    /**
     * Meta 3 · conversão — PROVISÓRIA até o CS confirmar como chega aos
     * 43/115 do relatório. Pares cliente×produto da carteira pelo status
     * atual: fechadas ÷ (fechadas + em negociação + declinadas + orçamento
     * pendente parceiro). Sem data de mudança de status, entram também
     * negociações anteriores à campanha.
     */
    private function ligaConversao(array $deals): array
    {
        $n = ['fechada' => 0, 'em_negociacao' => 0, 'declinada' => 0, 'orcamento_parceiro' => 0];
        foreach ($deals as $deal) {
            if (!$this->ligaNaCarteira($deal)) {
                continue;
            }
            foreach ($deal['status'] as $status) {
                if ($status !== null && isset($n[$status])) {
                    $n[$status]++;
                }
            }
        }
        $total = array_sum($n);

        return [
            'fechadas'   => $n['fechada'],
            'em_negoc'   => $n['em_negociacao'],
            'declinadas' => $n['declinada'],
            'orcamento'  => $n['orcamento_parceiro'],
            'total'      => $total,
            'pct'        => $total > 0 ? round($n['fechada'] / $total * 100, 1) : 0,
            'meta'       => self::LIGA_META_CONV,
            'provisorio' => true,
        ];
    }

    /**
     * Vendas da Liga: orçamentos em LIGA_ETAPA_VENDA com data da venda entre
     * LIGA_INICIO e hoje, de cliente do funil C3: de uma das CSMs da Liga (a
     * CSM do negócio). Decisões do usuário em 01/10/2026: sem o corte de
     * carteira dos mapeados (negócio criado depois de 18/05 também conta) e
     * venda sem valor também conta. Um produto por orçamento (os 140
     * vendidos em 01/10 tinham 1 item cada); se vier mais de um, fica o
     * primeiro, para o valor do orçamento não se repetir.
     */
    private function ligaCarregarVendas(): array
    {
        $csms = array_map('strval', array_keys(self::LIGA_CSM));
        $in   = implode(',', array_fill(0, count($csms), '?'));

        $linhas = $this->queryAll("
            SELECT o.id_orcamentoV2 AS id,
                   o.dataVenda_orcamentoV2 AS data,
                   o.oportunidade_orcamentoV2 AS valor,
                   o.moedaId_orcamentoV2 AS moeda,
                   d.responsavelComercial4Id_dealV2 AS csm,
                   d.nome_dealV2 AS deal,
                   TRIM(io.nomeOriginalProduto_itemOrcamentoV2) AS produto,
                   TRIM(CONCAT_WS(' ', c.primeiroNome_contactV2, c.sobrenome_contactV2)) AS cliente
            FROM orcamentoV2 o
            JOIN dealV2 d ON {$this->joinDeal('d', 'o')}
            LEFT JOIN itemOrcamentoV2 io ON io.id_orcamentoV2 = o.id_orcamentoV2
            LEFT JOIN contactV2 c ON c.idBitrix_contactV2 = CONVERT(o.contactId_orcamentoV2 USING latin1)
            WHERE o.etapaId_orcamentoV2 = ?
              AND o.dataVenda_orcamentoV2 >= ? AND o.dataVenda_orcamentoV2 <= ?
              AND d.faseId_dealV2 LIKE 'C3:%'
              AND d.responsavelComercial4Id_dealV2 IN ($in)
            ORDER BY o.dataVenda_orcamentoV2, o.id_orcamentoV2, io.id_itemOrcamentoV2
        ", array_merge([self::LIGA_ETAPA_VENDA, self::LIGA_INICIO, date('Y-m-d')], $csms));

        $porOrcamento = [];
        foreach ($linhas as $linha) {
            $porOrcamento[$linha['id']] ??= $linha;
        }
        return array_values($porOrcamento);
    }

    /** Câmbio BRL/USD por dia (MAX, regra oficial do projeto), em ordem de data, desde um pouco antes de LIGA_INICIO. */
    private function ligaCarregarCambio(): array
    {
        $rows = $this->queryAll("
            SELECT data_cambio AS d, MAX(valor_cambio) AS v
            FROM cambio
            WHERE data_cambio >= ? AND data_cambio <= ?
            GROUP BY data_cambio
            ORDER BY data_cambio
        ", [date('Y-m-d', strtotime(self::LIGA_INICIO . ' -15 days')), date('Y-m-d')]);

        return array_map('floatval', array_column($rows, 'v', 'd'));
    }

    /** Cotação do dia ou, se o dia não tiver, a última anterior. */
    private function ligaDolar(array $cambio, string $data): float
    {
        $dolar = 0.0;
        foreach ($cambio as $dia => $valor) {
            if ($dia > $data) {
                break;
            }
            $dolar = $valor;
        }
        return $dolar;
    }

    /**
     * Linhas de ligaCarregarVendas() já em R$, na ordem da data da venda:
     * BRL como está, USD × câmbio do dia da venda (a base só tem BRL e USD).
     * Cliente = nome do contato do orçamento; sem contato, o nome do negócio.
     */
    private function ligaVendasLista(array $linhas, array $cambio): array
    {
        $vendas = [];
        foreach ($linhas as $linha) {
            $valor = (float)$linha['valor'];
            if (strtoupper(trim((string)$linha['moeda'])) === 'USD') {
                $valor *= $this->ligaDolar($cambio, $linha['data']);
            }
            $vendas[] = [
                'data'    => $linha['data'],
                'brl'     => round($valor, 2),
                'csm'     => self::LIGA_CSM[(string)$linha['csm']],
                'produto' => (string)($linha['produto'] ?? '') !== '' ? $linha['produto'] : 'Sem produto',
                'cliente' => (string)($linha['cliente'] ?? '') !== '' ? $linha['cliente'] : (string)$linha['deal'],
            ];
        }
        return $vendas;
    }

    /**
     * Objeto `vendas` do relatório (`V` no JS de liga.php). null sem nenhuma
     * venda: a tela mantém os blocos de venda ocultos. "Semana" = os últimos
     * 7 dias contando hoje; o dólar exibido nela é a cotação mais recente.
     * Destaque = produto do mapeamento cuja primeira venda da campanha caiu
     * na semana (o primeiro, se houver mais de um).
     */
    private function ligaVendas(array $vendas, array $cambio): ?array
    {
        if (!$vendas) {
            return null;
        }

        $mapeamento = array_values(self::LIGA_PRODUTOS);
        $semanaIni  = date('Y-m-d', strtotime('-6 days'));

        $comb = 0.0;
        $porCsm = $porProduto = $primeira = $semana = [];
        foreach ($vendas as $v) {
            $comb += $v['brl'];

            $porCsm[$v['csm']]['q'] = ($porCsm[$v['csm']]['q'] ?? 0) + 1;
            $porCsm[$v['csm']]['v'] = ($porCsm[$v['csm']]['v'] ?? 0) + $v['brl'];

            $porProduto[$v['produto']]['q'] = ($porProduto[$v['produto']]['q'] ?? 0) + 1;
            $porProduto[$v['produto']]['v'] = ($porProduto[$v['produto']]['v'] ?? 0) + $v['brl'];

            $primeira[$v['produto']] ??= $v;

            if ($v['data'] >= $semanaIni) {
                $semana[] = [
                    'cliente' => $v['cliente'],
                    'produto' => $v['produto'],
                    'csm'     => $v['csm'],
                    'data'    => date('d/m', strtotime($v['data'])),
                    'brl'     => $v['brl'],
                ];
            }
        }

        $novo = null;
        $ordemMapeamento = array_values(array_filter(array_keys($primeira), fn($p) => in_array($p, $mapeamento, true)));
        foreach ($ordemMapeamento as $i => $produto) {
            if ($primeira[$produto]['data'] >= $semanaIni) {
                $novo = [
                    'nome'           => $produto,
                    'cliente'        => $primeira[$produto]['cliente'],
                    'csm'            => $primeira[$produto]['csm'],
                    'valor'          => $primeira[$produto]['brl'],
                    'ordinal'        => self::LIGA_ORDINAIS[$i] ?? null,
                    'total_produtos' => count($mapeamento),
                ];
                break;
            }
        }

        $csm = [];
        foreach ($porCsm as $nome => $c) {
            $csm[] = ['n' => $nome, 'q' => $c['q'], 'v' => round($c['v'], 2)];
        }
        usort($csm, fn($a, $b) => [$b['v'], $b['q']] <=> [$a['v'], $a['q']]);

        $prodVol = [];
        $split   = ['map_v' => 0.0, 'map_q' => 0];
        foreach ($porProduto as $nome => $p) {
            $fora      = !in_array($nome, $mapeamento, true);
            $prodVol[] = ['n' => $nome, 'q' => $p['q'], 'v' => round($p['v'], 2), 'fora' => $fora];
            if (!$fora) {
                $split['map_v'] += $p['v'];
                $split['map_q'] += $p['q'];
            }
        }
        usort($prodVol, fn($a, $b) => [$b['q'], $b['v']] <=> [$a['q'], $a['v']]);
        $split['map_v'] = round($split['map_v'], 2);

        $comb = round($comb, 2);

        return [
            'qtd'          => count($vendas),
            'comb'         => $comb,
            'pct'          => round($comb / self::LIGA_META_VALOR * 100, 1),
            'ticket'       => round($comb / count($vendas), 2),
            'semana'       => [
                'periodo' => date('d/m', strtotime($semanaIni)) . ' a ' . date('d/m'),
                'qtd'     => count($semana),
                'total'   => round(array_sum(array_column($semana, 'brl')), 2),
                'dolar'   => $this->ligaDolar($cambio, date('Y-m-d')),
                'vendas'  => $semana,
            ],
            'novo_produto' => $novo,
            'csm'          => $csm,
            'prod_vol'     => $prodVol,
            'split'        => $split,
        ];
    }

    /**
     * Quinzenas de 15 dias a partir de LIGA_INICIO até a que contém hoje
     * (Q1 = 18/05–01/06, …), como no relatório. Mapeados: a quinzena corrente
     * é a foto de hoje, ao vivo; as encerradas vêm de LIGA_MAPEADOS_HIST
     * (null se ninguém anotou a foto). Vendas (`total` em R$ e `qtd`) pela
     * data da venda, a partir de ligaVendasLista().
     */
    private function ligaQuinzenas(int $eqAtual, array $vendas): array
    {
        $inicio = new \DateTimeImmutable(self::LIGA_INICIO);
        $hoje   = new \DateTimeImmutable('today');
        $lista  = [];

        for ($n = 1; ; $n++) {
            $ini = $inicio->modify('+' . (15 * ($n - 1)) . ' days');
            if ($ini > $hoje) {
                break;
            }
            $fim      = $ini->modify('+14 days');
            $corrente = $hoje <= $fim;

            [$foto, $eq] = $corrente
                ? [$hoje->format('Y-m-d'), $eqAtual]
                : (self::LIGA_MAPEADOS_HIST[$n] ?? [null, null]);

            $de  = $ini->format('Y-m-d');
            $ate = $fim->format('Y-m-d');
            $daQuinzena = array_filter($vendas, fn($v) => $v['data'] >= $de && $v['data'] <= $ate);

            $lista[] = [
                'label'     => "Q$n",
                'datas'     => $ini->format('d/m') . ' – ' . $fim->format('d/m'),
                'foto'      => $foto !== null ? date('d/m', strtotime($foto)) : null,
                'map_eq'    => $eq,
                'map_pct'   => $eq !== null ? round($eq / self::LIGA_META_MAPEADOS * 100, 1) : null,
                'andamento' => $corrente,
                'total'     => round(array_sum(array_column($daQuinzena, 'brl')), 2),
                'qtd'       => count($daQuinzena),
            ];

            if ($corrente) {
                break;
            }
        }

        return $lista;
    }
}
