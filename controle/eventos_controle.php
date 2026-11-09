<?php

/**
 * Dashboard "Eventos" — migrado de Power Bi/Eventos (PDO standalone, api/+calculos/)
 * para MRPHP. Um único controller, com 2 views (Pipeline de Eventos, Consolidado por
 * Evento) + 1 dicionário + endpoints JSON de dados e de lista de eventos. Toda a
 * lógica de SQL/cálculo fica em métodos privados desta classe (sem camada calculos/),
 * mesmo padrão do executive_controle.php — mas os JOINs são específicos do domínio
 * de Eventos (tabela `evento`, `conversaCW`/`msgCW`), diferentes dos do executive.
 *
 * Ajuste de negócio aplicado nesta migração (a pedido do usuário): a constante
 * VALOR_COMBO_PROJECAO usada na fórmula de Projeção de Faturamento passou de 120000
 * (valor original do dashboard de origem) para 20000.
 */
class eventos extends MRPHP
{
    private const VALOR_COMBO_PROJECAO = 20000;

    // ===================================================================
    // PIPELINE DE EVENTOS
    // ===================================================================

    public function index()
    {
        $this->setTemplate('eventos');
    }

    /**
     * GET /eventos/dados — payload principal: totais (funil + custos + ROI),
     * eventos (linha por evento, ordenada por ROI desc) e motivos_descarte.
     * Período filtrado SOMENTE em evento.data_evento (ver buildEventoFilter()).
     */
    public function dados()
    {
        // O join com msgCW (engajadosBidsList(), ~845 mil linhas) já mediu estourando
        // o timeout do proxy em produção (Cloudflare 524, >120s) antes da Fase 1C.2 —
        // agora é cacheado à parte (chave fixa, TTL 30min), então só o primeiro
        // cache-miss depois do TTL expirar paga esse custo. set_time_limit mantido
        // como proteção defensiva, mesmo padrão usado em executive_controle.php.
        set_time_limit(180);
        try {
            $cacheKey = $this->cacheKeyFromRequest('eventos_dados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();

            $deals       = $this->calcularDealsPorEvento($f);
            $leads       = $this->calcularLeadsPorEvento($f);
            $investMkt   = $this->calcularInvestimentoMktPorEvento($f);
            $custoFisico = $this->calcularCustoFisicoPorEvento($f);
            $motivos     = $this->calcularMotivosDescarte($f);

            $camposFunil = [
                'total_leads', 'qualificados', 'oportunidades',
                'cadeiras', 'checkins',
                'consultas_vendidas', 'consultas_a_agendar', 'consultas_agendadas', 'consultas_realizadas',
                'sinais_pagos',
                'fp_agendado', 'fp_nao_agendado', 'fp_perdido', 'fp_vendido',
                'combos', 'faturamento',
                'investimento_mkt', 'custo_fisico',
            ];

            $MESES_NOME = [
                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
                7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
            ];

            $eventos = [];
            $totais  = array_fill_keys($camposFunil, 0.0);

            foreach ($deals as $id => $d) {
                $l = $leads[$id] ?? [];

                $ts      = strtotime((string)$d['data_evento']);
                $nomeFmt = $d['nome_evento'];
                if ($ts) {
                    $nomeFmt .= ' - ' . ($MESES_NOME[(int)date('n', $ts)] ?? '') . ' - ' . date('Y', $ts);
                }

                $m = [
                    'id'             => $id,
                    'nome'           => $d['nome_evento'],
                    'nome_formatado' => $nomeFmt,
                    'data'           => $d['data_evento'],
                    'cidade'         => $d['cidade_evento'],
                    'status'         => (int)$d['status_evento'],
                    'nome_form'      => $d['nomeForm_evento'],

                    'total_leads'          => (int)($l['total_leads']   ?? 0),
                    'qualificados'         => (int)($l['qualificados']  ?? 0),
                    'oportunidades'        => (int)($l['oportunidades'] ?? 0),

                    'cadeiras'             => (int)$d['cadeiras'],
                    'checkins'             => (int)$d['checkins'],
                    'consultas_vendidas'   => (int)$d['consultas_vendidas'],
                    'consultas_a_agendar'  => (int)$d['consultas_a_agendar'],
                    'consultas_agendadas'  => (int)$d['consultas_agendadas'],
                    'consultas_realizadas' => (int)$d['consultas_realizadas'],
                    'sinais_pagos'         => (int)$d['sinais_pagos'],
                    'fp_agendado'          => (int)$d['fp_agendado'],
                    'fp_nao_agendado'      => (int)$d['fp_nao_agendado'],
                    'fp_perdido'           => (int)$d['fp_perdido'],
                    'fp_vendido'           => (int)$d['fp_vendido'],
                    'combos'               => (int)$d['combos'],
                    'faturamento'          => (float)$d['faturamento'],

                    'investimento_mkt'     => (float)($investMkt[$id]   ?? 0),
                    'custo_fisico'         => (float)($custoFisico[$id] ?? 0),
                ];

                $m         = array_merge($m, $this->calcularDerivados($m));
                $eventos[] = $m;

                foreach ($camposFunil as $c) {
                    $totais[$c] += (float)$m[$c];
                }
            }

            $derivTotais = $this->calcularDerivados($totais);
            $totais      = array_merge($totais, $derivTotais);

            $totais['qt_eventos']   = count($eventos);
            $totais['pct_cadeira']  = $totais['qualificados'] != 0 ? $totais['cadeiras'] / $totais['qualificados'] : 0.0;
            $totais['pct_consulta'] = $totais['checkins']     != 0 ? $totais['consultas_vendidas'] / $totais['checkins'] : 0.0;

            foreach ($camposFunil as $c) {
                if (!in_array($c, ['faturamento', 'investimento_mkt', 'custo_fisico'], true)) {
                    $totais[$c] = (int)round($totais[$c]);
                }
            }

            usort($eventos, fn($a, $b) => ($b['roi'] ?? -1) <=> ($a['roi'] ?? -1));

            $payload = [
                'filtros'          => $f,
                'totais'           => $totais,
                'eventos'          => $eventos,
                'motivos_descarte' => $motivos,
            ];
            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    // ===================================================================
    // CONSOLIDADO POR EVENTO
    // ===================================================================

    public function detalhamento()
    {
        $this->setTemplate('eventos');
    }

    // ===================================================================
    // RELATÓRIO IMPRIMÍVEL (PDF via caixa de impressão do navegador)
    // ===================================================================

    /**
     * GET /eventos/relatorio — documento imprimível em A4 paisagem.
     * Não tem endpoint próprio: a view consome /eventos/dados com os
     * filtros herdados da querystring, e deriva CPL/CAC no JS.
     * Reusa setTemplate('eventos'), então o Basic Auth do gancho
     * home::eventos_controle() já cobre esta rota.
     */
    public function relatorio()
    {
        $this->setTemplate('eventos');
    }

    // ===================================================================
    // PRÉVIA DE EVENTOS (2 últimos + próximo) — widget da linha superior
    // ===================================================================

    /**
     * GET /eventos/preview — 2 últimos eventos realizados + o próximo agendado,
     * com Investimento (custo total), Qualificados, Cadeiras, Consultas e
     * Combos. Independente dos filtros da tela (sempre os eventos reais mais
     * recentes/futuro) — por isso cache de chave fixa e TTL bem mais longo
     * que o de dados(), mesmo padrão de engajadosBidsList().
     */
    public function preview()
    {
        try {
            $payload = $this->cacheRemember('eventos_preview_widget', 1800, function () {
                return $this->calcularPreviewEventos();
            });
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * Busca os 2 últimos eventos (data_evento <= hoje, mais recentes primeiro)
     * e o próximo (data_evento > hoje, mais próximo primeiro), depois reaproveita
     * calcularDealsPorEvento()/calcularLeadsPorEvento()/calcularInvestimentoMktPorEvento()/
     * calcularCustoFisicoPorEvento() já existentes — via filtro 'eventos' com os
     * IDs encontrados e período livre — para não duplicar nenhuma regra de negócio
     * do funil em uma query paralela.
     */
    private function calcularPreviewEventos(): array
    {
        $passados = $this->queryAll("
            SELECT id_evento, nome_evento, data_evento
            FROM evento
            WHERE deletado_evento = 0
              AND data_evento > '0000-00-00'
              AND data_evento <= CURDATE()
            ORDER BY data_evento DESC
            LIMIT 2
        ");
        $proximo = $this->queryAll("
            SELECT id_evento, nome_evento, data_evento
            FROM evento
            WHERE deletado_evento = 0
              AND data_evento > CURDATE()
            ORDER BY data_evento ASC
            LIMIT 1
        ");

        $lista = array_merge(array_reverse($passados), $proximo);
        if (!$lista) {
            return ['eventos' => []];
        }

        $ids = array_map(fn($e) => (int)$e['id_evento'], $lista);
        $f   = [
            'year' => null, 'months' => null, 'days' => null,
            'data_inicio' => null, 'data_fim' => null,
            'eventos' => implode(',', $ids), 'status' => null,
        ];

        $deals       = $this->calcularDealsPorEvento($f);
        $leads       = $this->calcularLeadsPorEvento($f);
        $investMkt   = $this->calcularInvestimentoMktPorEvento($f);
        $custoFisico = $this->calcularCustoFisicoPorEvento($f);

        $idsProximo = array_map(fn($e) => (int)$e['id_evento'], $proximo);

        $out = [];
        foreach ($lista as $ev) {
            $id = (int)$ev['id_evento'];
            $d  = $deals[$id] ?? [];
            $l  = $leads[$id] ?? [];
            $out[] = [
                'id'           => $id,
                'nome'         => $ev['nome_evento'],
                'data'         => $ev['data_evento'],
                'is_proximo'   => in_array($id, $idsProximo, true),
                'investimento' => (float)($investMkt[$id] ?? 0) + (float)($custoFisico[$id] ?? 0),
                'qualificados' => (int)($l['qualificados']       ?? 0),
                'cadeiras'     => (int)($d['cadeiras']           ?? 0),
                'consultas'    => (int)($d['consultas_vendidas'] ?? 0),
                'combos'       => (int)($d['combos']             ?? 0),
            ];
        }

        return ['eventos' => $out];
    }

    // ===================================================================
    // LISTA DE EVENTOS (dropdown de filtro)
    // ===================================================================

    /**
     * GET /eventos/lista — lista de eventos p/ o dropdown de filtro (rótulo:
     * Nome + Mês/Ano). Ignora seleção de eventos/status para sempre trazer
     * todas as opções disponíveis, restringindo só pelo período informado.
     */
    public function lista()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('eventos_lista');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DROPDOWN);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $f            = $this->lerFiltros();
            $f['eventos'] = null;
            $f['status']  = null;

            $filtro = $this->buildEventoFilter('e', $f);

            $meses = [
                1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun',
                7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez',
            ];

            $sql = "
                SELECT id_evento, nome_evento, data_evento, status_evento
                FROM evento e
                WHERE {$filtro['sql']}
                ORDER BY e.data_evento DESC
            ";
            $rows = $this->queryAll($sql, $filtro['params']);

            $eventos = [];
            foreach ($rows as $r) {
                $ts  = strtotime($r['data_evento']);
                $mes = $ts ? (int)date('n', $ts) : 0;
                $ano = $ts ? (int)date('Y', $ts) : 0;
                $rot = $r['nome_evento'];
                if ($ts) {
                    $rot .= ' — ' . ($meses[$mes] ?? '') . '/' . $ano;
                }
                $eventos[] = [
                    'id'     => (int)$r['id_evento'],
                    'nome'   => $r['nome_evento'],
                    'data'   => $r['data_evento'],
                    'mes'    => $mes,
                    'ano'    => $ano,
                    'status' => (int)$r['status_evento'],
                    'rotulo' => $rot,
                ];
            }

            $payload = ['eventos' => $eventos];
            $this->cacheSet($cacheKey, $payload);
            $this->jsonOut($payload);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    // ===================================================================
    // DICIONÁRIO (página estática — sem dados JSON)
    // ===================================================================

    public function dicionario()
    {
        $this->setTemplate('eventos');
    }

    // ===================================================================
    // HELPERS: HTTP / JSON
    // ===================================================================

    private function g(string $key): ?string
    {
        $v = $_GET[$key] ?? null;
        return ($v === null || $v === '') ? null : trim($v);
    }

    private function jsonOut($data): void
    {
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
    // HELPERS: CACHE EM ARQUIVO (.mrphp/cache) — TTL curto, sem tocar no banco
    // Mesmo padrão de executive_controle.php:502-546 (cópia própria do
    // controller, sem camada compartilhada — convenção do projeto).
    // ===================================================================

    /** Segundos de validade do cache do dropdown de eventos (lista()). */
    private const CACHE_TTL_DROPDOWN = 600;

    /** Segundos de validade do cache da resposta agregada de dados().
     *  Cache-miss custa caro — o join com msgCW (CTE `engajados`, ~845 mil linhas,
     *  independente do filtro do usuário) já foi medido estourando o timeout do
     *  proxy (Cloudflare 524, >120s) em produção. TTL de 10min reduz a frequência
     *  com que esse custo é pago; ver Fase 1C.2 do plano de performance para o
     *  cache de fragmento que resolve a causa raiz (o join não devia depender
     *  do filtro pra começo de conversa). */
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
    // HELPERS: FILTROS SQL (traduzidos de PDO nomeado -> mysqli posicional)
    // ===================================================================

    /** Lê os filtros do querystring ($_GET) num array normalizado. */
    private function lerFiltros(): array
    {
        return [
            'year'        => $this->g('ano') ?? $this->g('year'),
            'months'      => $this->g('mes') ?? $this->g('months'),
            'days'        => $this->g('dia') ?? $this->g('days'),
            'data_inicio' => $this->g('data_inicio'),
            'data_fim'    => $this->g('data_fim'),
            'eventos'     => $this->g('eventos'),
            'status'      => $this->g('status'),
        ];
    }

    /**
     * Monta o conjunto de condições WHERE sobre a tabela evento — fonte única da
     * verdade do dashboard: o período (ano/mês/dia/intervalo) SEMPRE incide sobre
     * evento.data_evento; todo o resto (leads, deals, custos, faturamento) é
     * atribuído ao evento independentemente da própria data. Sempre aplica
     * deletado_evento = 0.
     */
    private function buildEventoFilter(string $alias, array $f): array
    {
        $conds  = ["$alias.deletado_evento = 0"];
        $params = [];

        $periodo = $this->buildPeriodFilter(
            "$alias.data_evento",
            $f['year']        ?? null,
            $f['months']      ?? null,
            $f['data_inicio'] ?? null,
            $f['data_fim']    ?? null,
            $f['days']        ?? null
        );
        if ($periodo['sql'] !== '1=1') {
            $conds[] = $periodo['sql'];
        }
        $params = array_merge($params, $periodo['params']);

        $evIn = $this->buildInFilter("$alias.id_evento", $f['eventos'] ?? null);
        if ($evIn['sql'] !== '') {
            $conds[] = preg_replace('/^\s*AND\s+/', '', $evIn['sql']);
            $params  = array_merge($params, $evIn['params']);
        }

        $stIn = $this->buildInFilter("$alias.status_evento", $f['status'] ?? null);
        if ($stIn['sql'] !== '') {
            $conds[] = preg_replace('/^\s*AND\s+/', '', $stIn['sql']);
            $params  = array_merge($params, $stIn['params']);
        }

        return ['sql' => implode("\n              AND ", $conds), 'params' => $params];
    }

    /**
     * Fase 1C.4: reescrito pra gerar intervalo de data literal (sargável) pra
     * ano/mês, em vez de YEAR($col)=?/MONTH($col) IN(...) — mesmo fix já
     * provado em marketing/executive/etc. Diferente dos outros controllers,
     * este tem um parâmetro extra ($days, filtro por dia do mês) que nenhum
     * outro tem — documentado como pendência em PENDENCIAS-PERFORMANCE.md.
     * Solução adotada (a "mais simples e segura" já registrada lá): o range
     * de ano/mês vira sargável normalmente, e — só quando $days é usado — uma
     * condição extra `AND DAY($col) IN (...)` é encadeada depois, ainda
     * função-wrapped (não-sargável nessa parte específica, mas o range de
     * ano/mês já reduz o universo escaneado antes dela entrar em ação).
     * Guarda de coluna-expressão mantida por consistência com a referência,
     * embora nenhuma chamada aqui passe expressão (só `$alias.data_evento`).
     */
    private function buildDateFilter(string $col, $year, ?string $months, ?string $days = null): array
    {
        if (!preg_match('/^[A-Za-z_]\w*\.\w+$/', $col)) {
            return $this->buildDateFilterLegacy($col, $year, $months, $days);
        }

        $ds = ($days !== null && $days !== '')
            ? array_values(array_filter(array_map('trim', explode(',', $days)), fn($v) => $v !== ''))
            : [];
        $condDia    = $ds ? "DAY($col) IN (" . implode(',', array_fill(0, count($ds), '?')) . ")" : null;
        $paramsDia  = $ds;

        $years = ($year !== null && $year !== '')
            ? array_values(array_filter(array_map('trim', explode(',', (string)$year)), fn($v) => $v !== ''))
            : [];
        $ms = ($months !== null && $months !== '')
            ? array_values(array_filter(array_map('trim', explode(',', $months)), fn($v) => $v !== ''))
            : [];

        if (!$years) {
            if (!$ms) {
                return $condDia
                    ? ['sql' => $condDia, 'params' => $paramsDia]
                    : ['sql' => '1=1', 'params' => []];
            }
            $sql    = "MONTH($col) IN (" . implode(',', array_fill(0, count($ms), '?')) . ")";
            $params = $ms;
            if ($condDia) {
                $sql .= " AND $condDia";
                $params = array_merge($params, $paramsDia);
            }
            return ['sql' => $sql, 'params' => $params];
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

        $sql = '(' . implode(' OR ', $ranges) . ')';
        if ($condDia) {
            $sql .= " AND $condDia";
            $params = array_merge($params, $paramsDia);
        }

        return ['sql' => $sql, 'params' => $params];
    }

    /** Versão antiga (YEAR()/MONTH()/DAY(), não-sargável) — usada só se $col
     *  não for uma coluna simples (ver guarda em buildDateFilter). */
    private function buildDateFilterLegacy(string $col, $year, ?string $months, ?string $days = null): array
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

        if ($days !== null && $days !== '') {
            $ds = array_values(array_filter(array_map('trim', explode(',', $days)), fn($v) => $v !== ''));
            if ($ds) {
                $conds[] = "DAY($col) IN (" . implode(',', array_fill(0, count($ds), '?')) . ")";
                array_push($params, ...$ds);
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
     *  year/month/day. Comportamento `||` preservado como já era neste
     *  arquivo. Nota: no modo range explícito, $days é ignorado — mesmo
     *  comportamento de antes (a versão original também não combinava
     *  data_inicio/data_fim com day). */
    private function buildPeriodFilter(string $col, $year, ?string $months, ?string $dataInicio = null, ?string $dataFim = null, ?string $days = null): array
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
        return $this->buildDateFilter($col, $year, $months, $days);
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

    /** Zero-date "preenchida" (equivalente DAX: NOT ISBLANK / > 0). MySQL guarda 0000-00-00 em datas vazias. */
    private function sqlDateFilled(string $col): string
    {
        return "($col IS NOT NULL AND $col > '0000-00-00 00:00:00')";
    }

    private function sqlDateEmpty(string $col): string
    {
        return "($col IS NULL OR $col <= '0000-00-00 00:00:00')";
    }

    // ===================================================================
    // CÁLCULOS: FUNIL POR EVENTO (leadV2 / dealV2 agregados por id_evento)
    // ===================================================================

    /**
     * Métricas de deal agregadas por evento. Retorna array keyed by id_evento.
     *
     * Nota de performance (Fase 1C): evento.id_evento é INT, mas
     * dealV2.idEvento_dealV2/leadV2.idEvento_leadV2 são VARCHAR(100) em
     * latin1_swedish_ci — o mismatch de tipo+collation faz o MySQL descartar o
     * índice existente (idx_dealV2_idEvento) e cair em "Range checked for each
     * record" (full scan por linha de evento). Medido: 39,25s -> 0,52s (~76x)
     * ao envolver o lado INT em CONVERT(... USING latin1) no JOIN, sem precisar
     * de nenhuma mudança de schema. Mesmo fix aplicado em calcularLeadsPorEvento(),
     * calcularMotivosDescarte() e nos dashboards rafaeltorres/julianaprado/
     * camiladuarte/beatrizlemos (mesma tabela evento, mesmo join).
     */
    private function calcularDealsPorEvento(array $f): array
    {
        $filtro = $this->buildEventoFilter('e', $f);

        $foraFp     = "d.fase_dealV2 NOT IN ('Comercial 3','Distrato','Lead sem qualificação')";
        $dataVenda  = $this->sqlDateFilled('d.dataVenda_dealV2');
        $dataAgend  = $this->sqlDateFilled('d.dataConsultaAgendada_dealV2');
        $dataReal   = $this->sqlDateFilled('d.dataConsultaRealizada_dealV2');
        $dataFpAg   = $this->sqlDateFilled('d.dataFpAgendado_dealV2');
        $dataFpReal = $this->sqlDateFilled('d.dataFpRealizado_dealV2');

        $sql = "
            SELECT
                e.id_evento,
                e.nome_evento,
                e.data_evento,
                e.nomeForm_evento,
                e.status_evento,
                e.cidade_evento,

                SUM(CASE WHEN d.faseId_dealV2 = 'C9:WON' AND $dataVenda
                         THEN 1 ELSE 0 END) AS cadeiras,

                SUM(CASE WHEN d.checkin_dealV2 = '1'
                          AND d.faseId_dealV2 IS NOT NULL
                          AND d.faseId_dealV2 NOT LIKE '%:%'
                         THEN 1 ELSE 0 END) AS checkins,

                SUM(CASE WHEN d.faseId_dealV2 IN ('WON','C11:WON') AND $dataVenda
                         THEN 1 ELSE 0 END) AS consultas_vendidas,

                SUM(CASE WHEN d.faseId_dealV2 = 'C5:NEW'
                          AND " . $this->sqlDateEmpty('d.dataConsultaAgendada_dealV2') . "
                          AND " . $this->sqlDateEmpty('d.dataConsultaRealizada_dealV2') . "
                         THEN 1 ELSE 0 END) AS consultas_a_agendar,

                SUM(CASE WHEN $dataAgend
                          AND " . $this->sqlDateEmpty('d.dataConsultaRealizada_dealV2') . "
                          AND LEFT(d.faseId_dealV2,2) = 'C5'
                         THEN 1 ELSE 0 END) AS consultas_agendadas,

                SUM(CASE WHEN $dataReal AND LEFT(d.faseId_dealV2,2) = 'C5'
                         THEN 1 ELSE 0 END) AS consultas_realizadas,

                SUM(CASE WHEN $dataVenda
                          AND LEFT(d.faseId_dealV2,2) = 'C5'
                          AND d.faseId_dealV2 NOT IN ('C5:UC_AQ2QYT','C5:LOSE','C5:UC_CL1DBM')
                          AND $dataReal
                         THEN 1 ELSE 0 END) AS sinais_pagos,

                SUM(CASE WHEN LEFT(d.faseId_dealV2,2) = 'C5'
                          AND " . $this->sqlDateEmpty('d.dataFpRealizado_dealV2') . "
                          AND $dataReal AND $dataFpAg AND $foraFp
                         THEN 1 ELSE 0 END) AS fp_agendado,

                SUM(CASE WHEN LEFT(d.faseId_dealV2,2) = 'C5'
                          AND $dataVenda AND $dataReal
                          AND " . $this->sqlDateEmpty('d.dataFpAgendado_dealV2') . "
                          AND $foraFp
                         THEN 1 ELSE 0 END) AS fp_nao_agendado,

                SUM(CASE WHEN LEFT(d.faseId_dealV2,2) = 'C5'
                          AND $dataFpReal
                          AND d.fase_dealV2 IN ('Comercial 3','Distrato','Lead sem qualificação')
                         THEN 1 ELSE 0 END) AS fp_perdido,

                SUM(CASE WHEN LEFT(d.faseId_dealV2,2) = 'C5'
                          AND $dataFpReal AND $dataReal AND $dataFpAg AND $foraFp
                         THEN 1 ELSE 0 END) AS fp_vendido,

                SUM(CASE WHEN " . $this->sqlDateFilled('d.dataContratoComboAssinado_dealV2') . "
                          AND d.faseId_dealV2 IN ('C1:WON','C5:WON','C29:WON','C31:WON')
                         THEN 1 ELSE 0 END) AS combos,

                COALESCE(SUM(CASE WHEN " . $this->sqlDateFilled('d.dataContratoComboAssinado_dealV2') . "
                          AND d.faseId_dealV2 IN ('C1:WON','C5:WON','C29:WON','C31:WON')
                         THEN d.valorComboLiquido_dealV2 ELSE 0 END), 0) AS faturamento

            FROM evento e
            LEFT JOIN dealV2 d ON d.idEvento_dealV2 = CONVERT(e.id_evento USING latin1)
            WHERE {$filtro['sql']}
            GROUP BY e.id_evento, e.nome_evento, e.data_evento, e.nomeForm_evento, e.status_evento, e.cidade_evento
        ";

        $rows = $this->queryAll($sql, $filtro['params']);

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['id_evento']] = $r;
        }
        return $out;
    }

    /**
     * Lista de bids (idLeadBitrix) com pelo menos uma mensagem "incoming"/"0" no
     * Chatwoot — usado por calcularLeadsPorEvento() para contar "oportunidades".
     * Esse join (conversaCW x msgCW, ~845 mil linhas) é INDEPENDENTE de qualquer
     * filtro de evento/período — o comentário original desta query já apontava
     * isso (nenhum bid fora do lead set filtrado casaria de qualquer forma no
     * join final por igualdade de bid). Fase 1C.2 do plano de performance:
     * cacheado isolado, chave fixa, TTL de 30min — em cache-miss, recalculado ao
     * vivo a cada combinação de filtro, esse join já foi medido estourando o
     * timeout do proxy em produção (Cloudflare 524, >120s).
     *
     * Retorna os bids já formatados como lista de inteiros literais (ex.:
     * "123,456,789") em vez de bind params — os valores vêm do próprio banco
     * (BIGINT via CAST/coluna numérica), nunca de entrada do usuário, então
     * interpolar como literal é seguro. Mesmo padrão de
     * marketing_controle.php::engajadosBidsList().
     */
    private function engajadosBidsList(): string
    {
        return $this->cacheRemember('eventos_engajados_bids', 1800, function () {
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

    /**
     * Métricas de lead agregadas por evento: total, qualificados, oportunidades.
     * Oportunidades = leads com ao menos uma mensagem "incoming"/"0" no Chatwoot
     * (leadV2.idBitrix_leadV2 -> conversaCW.idLeadBitrix_conversaCW -> msgCW).
     */
    private function calcularLeadsPorEvento(array $f): array
    {
        $filtro      = $this->buildEventoFilter('e', $f);
        $bidsIntList = $this->engajadosBidsList();
        $oportunidadeExpr = $bidsIntList === ''
            ? '0'
            : "SUM(CASE WHEN l.idBitrix_leadV2 REGEXP '^[0-9]+\$' AND CAST(l.idBitrix_leadV2 AS UNSIGNED) IN ($bidsIntList) THEN 1 ELSE 0 END)";

        // JOIN direto leadV2 -> evento (sem CTE intermediária): a versão anterior
        // materializava leadV2 JOIN evento numa CTE e depois juntava essa CTE de
        // volta em evento de novo — um segundo join sem índice (o resultado da CTE
        // não carrega nenhum índice da tabela original), medido travando bem acima
        // de 60s. Join único, direto, mede ~1s (Fase 1C, mesmo CONVERT de
        // calcularDealsPorEvento — ver nota lá).
        $sql = "
            SELECT
                e.id_evento,
                COUNT(l.id_leadV2) AS total_leads,
                SUM(CASE WHEN l.qualificado_leadV2 = 'Sim' THEN 1 ELSE 0 END) AS qualificados,
                {$oportunidadeExpr} AS oportunidades
            FROM evento e
            LEFT JOIN leadV2 l ON l.idEvento_leadV2 = CONVERT(e.id_evento USING latin1)
            WHERE {$filtro['sql']}
            GROUP BY e.id_evento
        ";

        $rows = $this->queryAll($sql, $filtro['params']);

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['id_evento']] = $r;
        }
        return $out;
    }

    /**
     * Contagem por motivo de descarte (Comercial 2), deals de eventos do
     * período, com tipoLead = 'otr'. Retorna [ ['motivo'=>..., 'qtd'=>...], ... ].
     */
    private function calcularMotivosDescarte(array $f, int $topN = 8): array
    {
        $filtro = $this->buildEventoFilter('e', $f);

        $sql = "
            SELECT
                TRIM(d.motivoDescarteComercial2_dealV2) AS motivo,
                COUNT(*) AS qtd
            FROM evento e
            JOIN dealV2 d ON d.idEvento_dealV2 = CONVERT(e.id_evento USING latin1)
            WHERE {$filtro['sql']}
              AND d.motivoDescarteComercial2_dealV2 IS NOT NULL
              AND TRIM(d.motivoDescarteComercial2_dealV2) <> ''
              AND d.tipoLead_dealV2 = 'otr'
            GROUP BY TRIM(d.motivoDescarteComercial2_dealV2)
            HAVING qtd > 0
            ORDER BY qtd DESC
            LIMIT $topN
        ";

        return $this->queryAll($sql, $filtro['params']);
    }

    // ===================================================================
    // CÁLCULOS: CUSTOS / FATURAMENTO / ROI POR EVENTO (tudo em USD)
    // ===================================================================

    /**
     * InvestimentoMKT (USD) por evento — via identificador_custoMkt = nomeForm_evento.
     * Retorna array keyed by id_evento => float.
     */
    private function calcularInvestimentoMktPorEvento(array $f): array
    {
        $filtro = $this->buildEventoFilter('e', $f);

        $sql = "
            SELECT
                e.id_evento,
                COALESCE(SUM(
                    CASE
                        WHEN YEAR(cm.data_custoMkt) <= 2025 AND cm.origem_custoMkt = 'meta'
                            THEN cm.valor_custoMkt
                        WHEN YEAR(cm.data_custoMkt) >= 2026 AND cm.conta_custoMkt IN ('Dollar 1','Dollar 2')
                            THEN cm.valor_custoMkt
                        WHEN YEAR(cm.data_custoMkt) <= 2025 AND cm.origem_custoMkt = 'google'
                            THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio,0)
                        WHEN YEAR(cm.data_custoMkt) >= 2026 AND cm.conta_custoMkt <> 'Dollar 1' AND cm.conta_custoMkt <> 'Dollar 2'
                            THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio,0)
                        ELSE 0
                    END
                ), 0) AS investimento_mkt
            FROM evento e
            LEFT JOIN custoMkt cm
                   ON cm.identificador_custoMkt = e.nomeForm_evento
            LEFT JOIN (SELECT data_cambio, MAX(valor_cambio) AS valor_cambio FROM cambio GROUP BY data_cambio) ca
                   ON ca.data_cambio = cm.data_custoMkt
            WHERE {$filtro['sql']}
            GROUP BY e.id_evento
        ";

        $rows = $this->queryAll($sql, $filtro['params']);

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['id_evento']] = (float)$r['investimento_mkt'];
        }
        return $out;
    }

    /**
     * Custo Físico (USD) por evento — via custoEvento.id_evento = evento.id_evento.
     * Câmbio casado pelo mais recente disponível até data_custoEvento (não só data exata) —
     * evita zerar eventos com data_custoEvento futura/recente sem câmbio sincronizado ainda.
     * Retorna array keyed by id_evento => float.
     */
    private function calcularCustoFisicoPorEvento(array $f): array
    {
        $filtro = $this->buildEventoFilter('e', $f);

        $sql = "
            SELECT
                e.id_evento,
                COALESCE(SUM(
                    ce.valor_custoEvento / NULLIF(ca.valor_cambio,0)
                ), 0) AS custo_fisico
            FROM evento e
            LEFT JOIN custoEvento ce ON ce.id_evento = e.id_evento
            LEFT JOIN (SELECT data_cambio, MAX(valor_cambio) AS valor_cambio FROM cambio GROUP BY data_cambio) ca
                   ON ca.data_cambio = (SELECT MAX(c2.data_cambio) FROM cambio c2 WHERE c2.data_cambio <= ce.data_custoEvento)
            WHERE {$filtro['sql']}
            GROUP BY e.id_evento
        ";

        $rows = $this->queryAll($sql, $filtro['params']);

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['id_evento']] = (float)$r['custo_fisico'];
        }
        return $out;
    }

    /**
     * Métricas derivadas de um conjunto de valores já agregados: custo_total,
     * projecao_faturamento, roi, roi_projetado, taxa_combo, lucro.
     * ROI é null quando custo_total = 0 (equivalente a BLANK no DAX).
     */
    private function calcularDerivados(array $m): array
    {
        $faturamento      = (float)($m['faturamento']          ?? 0);
        $investimentoMkt  = (float)($m['investimento_mkt']     ?? 0);
        $custoFisico      = (float)($m['custo_fisico']         ?? 0);
        $fpAgendado       = (float)($m['fp_agendado']          ?? 0);
        $consultaAgendada = (float)($m['consultas_agendadas']  ?? 0);
        $consultaAAgendar = (float)($m['consultas_a_agendar']  ?? 0);
        $combos           = (float)($m['combos']               ?? 0);
        $consultasReal    = (float)($m['consultas_realizadas'] ?? 0);

        $custoTotal = $investimentoMkt + $custoFisico;

        $projecao = $faturamento
            + (0.75 * $fpAgendado       * self::VALOR_COMBO_PROJECAO)
            + (0.25 * $consultaAgendada * self::VALOR_COMBO_PROJECAO)
            + (0.25 * $consultaAAgendar * self::VALOR_COMBO_PROJECAO);

        $roi     = $custoTotal != 0.0 ? $faturamento / $custoTotal : null;
        $roiProj = $custoTotal != 0.0 ? $projecao    / $custoTotal : null;

        $taxaCombo = $consultasReal != 0.0 ? $combos / $consultasReal : 0.0;

        // Lucro = Faturamento - Custo Total, nunca negativo.
        $lucro = max(0.0, $faturamento - $custoTotal);

        return [
            'custo_total'          => $custoTotal,
            'projecao_faturamento' => $projecao,
            'roi'                  => $roi,
            'roi_projetado'        => $roiProj,
            'taxa_combo'           => $taxaCombo,
            'lucro'                => $lucro,
        ];
    }
}
