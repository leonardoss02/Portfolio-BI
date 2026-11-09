<?php

/**
 * Dashboard "Executive View" — migrado de Power Bi/ExecutiveView (PDO standalone) para MRPHP.
 *
 * Um único controller, com 4 abas (métodos de view) + 1 dicionário + endpoints JSON de dados
 * e de filtros compartilhados. Toda a lógica de SQL/cálculo fica em métodos privados desta
 * classe (sem camada calculos/), por pedido explícito do projeto.
 *
 * Correções de comportamento aplicadas nesta migração (unificando divergências que existiam
 * entre as abas no projeto de origem — ver plano de migração):
 *   1. Exclusão de vistoEscolhido_dealV2 = 'F' (distrato) em Combos Vendidos/Faturamento,
 *      aplicada em TODAS as abas (a origem só tinha isso na aba Executive).
 *   2. Guarda de zero-date completa (IS NOT NULL AND > '0000-00-00') em Combos Vendidos,
 *      aplicada em TODAS as abas (a origem tinha isso incompleto na aba Especialistas).
 *   3. Custo físico de evento (aba Digital x Evento) agora converte BRL->USD via câmbio,
 *      igual à aba Executive (a origem não convertia nessa aba especificamente).
 *   4. Lista de especialistas usada nos filtros é sempre a de 6 nomes (a origem tinha uma
 *      lista de 5 nomes só no dropdown da aba Especialistas).
 */
class executive extends MRPHP
{
    // ===================================================================
    // ABA: EXECUTIVE (painel global + 4 segmentos + comparativos YoY/MoM)
    // ===================================================================

    public function index()
    {
        $this->setTemplate('executive');
    }

    public function dados()
    {
        // A engine de comparação (5 segmentos x até 3 períodos) pode levar dezenas de
        // segundos em produção — o SAPI web (Apache/PHP-FPM) normalmente limita a 30s
        // por padrão, diferente do CLI usado em desenvolvimento local (sem limite).
        set_time_limit(180);
        error_log('[executive/dados] início');
        try {
            $cacheKey = $this->cacheKeyFromRequest('executive_dados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $year        = $this->g('year');
            $months      = $this->g('months');
            $campanha    = $this->g('campanha');
            $form        = $this->g('form');
            $responsavel = $this->g('responsavel');
            $dataInicio  = $this->g('data_inicio');
            $dataFim     = $this->g('data_fim');
            $today       = $this->g('today');

            // periodos/periodosAtivos precisam existir antes das queries de leadV2/dealV2 porque
            // calcularLeadsUnificado()/calcularFunilUnificado() computam os 5 segmentos do
            // período selecionado E os 3 períodos de comparação (ma/py/lm) numa única passada
            // por tabela, em vez de duas passadas separadas (uma por buildSegmentosTodos(), outra
            // por buildComparacoesTodos() como antes).
            $periodos       = $this->calcularPeriodosComparativos($today, $year, $months, $dataInicio, $dataFim);
            $periodosAtivos = $this->periodosAtivosFrom($periodos);

            error_log('[executive/dados] calculando leads/funil (segmentos + comparativos, 1 passada por tabela)...');
            $leadsUnificado = $this->calcularLeadsUnificado($year, $months, $campanha, $form, $responsavel, $dataInicio, $dataFim, $periodosAtivos);
            $funilUnificado = $this->calcularFunilUnificado($year, $months, $campanha, $form, $responsavel, $dataInicio, $dataFim, $periodosAtivos);

            error_log('[executive/dados] calculando 5 segmentos principais...');
            $segmentosPrincipais = $this->buildSegmentosTodos($year, $months, $campanha, $form, $dataInicio, $dataFim, $leadsUnificado['main'], $funilUnificado['main']);

            error_log('[executive/dados] calculando vistos (por tipo + por vendedor)...');
            $vistos = $this->calcularVistos($year, $months, $campanha, $form, $dataInicio, $dataFim);

            $response = [
                'meta' => [
                    'year'        => $year,
                    'months'      => $months,
                    'campanha'    => $campanha,
                    'form'        => $form,
                    'responsavel' => $responsavel,
                    'data_inicio' => $dataInicio,
                    'data_fim'    => $dataFim,
                    'generated'   => date('Y-m-d H:i:s'),
                ],
                'global'    => $segmentosPrincipais['global'],
                'evento'    => $segmentosPrincipais['evento'],
                'digital'   => $segmentosPrincipais['digital'],
                'organico'  => $segmentosPrincipais['organico'],
                'indicacao' => $segmentosPrincipais['indicacao'],
                'vistos'    => $vistos,
            ];
            error_log('[executive/dados] 5 segmentos principais OK, iniciando comparativos...');

            $comparisons = $this->buildComparacoesTodos($periodos, $periodosAtivos, $leadsUnificado['porPeriodo'], $funilUnificado['porPeriodo']);
            error_log('[executive/dados] comparativos OK, montando resposta final...');

            $response['mes_atual']        = $comparisons['global']['mes_atual'];
            $response['ano_passado']      = $comparisons['global']['ano_passado'];
            $response['ultimo_mes']       = $comparisons['global']['ultimo_mes'];
            $response['comparisons']      = $comparisons;
            $response['has_month_filter'] = $periodos['has_month_filter'];
            $response['effective_start']  = $periodos['effective_start'];
            $response['effective_end']    = $periodos['effective_end'];
            $response['cached']           = false;

            $this->cacheSet($cacheKey, $response);
            error_log('[executive/dados] fim — enviando JSON');
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            error_log('[executive/dados] EXCEÇÃO: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
            $this->jsonError($e);
        }
    }

    // ===================================================================
    // ABA: DIGITAL × EVENTO (funil comparativo por classe de form)
    // ===================================================================

    public function digital()
    {
        $this->setTemplate('executive');
    }

    public function digitalDados()
    {
        set_time_limit(180);
        try {
            $cacheKey = $this->cacheKeyFromRequest('executive_digitalDados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $year        = $this->g('year');
            $months      = $this->g('months');
            $campanha    = $this->g('campanha');
            $form        = $this->g('form');
            $responsavel = $this->g('responsavel');
            $dataInicio  = $this->g('data_inicio');
            $dataFim     = $this->g('data_fim');

            $segmentos = $this->buildSegmentosFormTodos($year, $months, $campanha, $form, $responsavel, $dataInicio, $dataFim);
            $response = [
                'meta' => [
                    'year'      => $year,
                    'months'    => $months,
                    'generated' => date('Y-m-d H:i:s'),
                ],
                'global'  => $segmentos['global'],
                'evento'  => $segmentos['evento'],
                'digital' => $segmentos['digital'],
                'cached'  => false,
            ];

            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    // ===================================================================
    // ABA: ESPECIALISTAS (KPIs + gráfico + tabela)
    // ===================================================================

    public function especialistas()
    {
        $this->setTemplate('executive');
    }

    public function especialistasDados()
    {
        set_time_limit(180);
        try {
            $cacheKey = $this->cacheKeyFromRequest('executive_especialistasDados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $year        = $this->g('year');
            $months      = $this->g('months');
            $campanha    = $this->g('campanha');
            $form        = $this->g('form');
            $responsavel = $this->g('responsavel') ?? $this->equipeEspecialistasPadrao();
            $dataInicio  = $this->g('data_inicio');
            $dataFim     = $this->g('data_fim');

            $granularidade = $_GET['granularidade'] ?? 'mes';
            if (!in_array($granularidade, ['ano', 'mes', 'semana', 'dia'], true)) {
                $granularidade = 'mes';
            }

            $kpis    = $this->calcularKpisEspecialistas($year, $months, null, $campanha, $form, $responsavel, $dataInicio, $dataFim);
            $grafico = $this->calcularGraficoEspecialistas($year, $months, $granularidade, null, $campanha, $form, $responsavel, $dataInicio, $dataFim);
            $tabela  = $this->calcularTabelaEspecialistas($year, $months, null, $campanha, $form, $dataInicio, $dataFim, $responsavel);

            $response = [
                'meta' => [
                    'year'          => $year,
                    'months'        => $months,
                    'campanha'      => $campanha,
                    'form'          => $form,
                    'responsavel'   => $responsavel,
                    'granularidade' => $granularidade,
                    'generated'     => date('Y-m-d H:i:s'),
                ],
                'kpis'    => $kpis,
                'grafico' => $grafico,
                'tabela'  => $tabela,
                'cached'  => false,
            ];

            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    // ===================================================================
    // ABA: ESPECIALISTAS ANALÍTICO (por especialista × frente)
    // ===================================================================

    public function analitico()
    {
        $this->setTemplate('executive');
    }

    public function analiticoDados()
    {
        set_time_limit(180);
        try {
            $cacheKey = $this->cacheKeyFromRequest('executive_analiticoDados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $year        = $this->g('year');
            $months      = $this->g('months');
            $campanha    = $this->g('campanha');
            $form        = $this->g('form');
            $responsavel = $this->g('responsavel') ?? $this->equipeEspecialistasPadrao();
            $dataInicio  = $this->g('data_inicio');
            $dataFim     = $this->g('data_fim');

            $data = $this->calcularAnaliticoEspecialistas($year, $months, $campanha, $form, $responsavel, $dataInicio, $dataFim);

            $response = [
                'meta' => [
                    'year'        => $year,
                    'months'      => $months,
                    'campanha'    => $campanha,
                    'form'        => $form,
                    'responsavel' => $responsavel,
                    'generated'   => date('Y-m-d H:i:s'),
                ],
                'especialistas' => $data,
                'cached'        => false,
            ];

            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    // ===================================================================
    // ABA: SERASA (consulta de crédito — pipes Comercial 2 / Comercial 4)
    // ===================================================================

    public function serasa()
    {
        $this->setTemplate('executive');
    }

    public function serasaDados()
    {
        set_time_limit(180);
        try {
            $cacheKey = $this->cacheKeyFromRequest('executive_serasaDados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $year       = $this->g('year');
            $months     = $this->g('months');
            $dataInicio = $this->g('data_inicio');
            $dataFim    = $this->g('data_fim');
            $cambio     = $this->taxaCambioMaisRecente();

            $response = [
                'meta' => [
                    'year'        => $year,
                    'months'      => $months,
                    'data_inicio' => $dataInicio,
                    'data_fim'    => $dataFim,
                    'taxa_cambio' => $cambio,
                    'generated'   => date('Y-m-d H:i:s'),
                ],
                'resumo'                  => $this->calcularSerasaResumo($year, $months, $dataInicio, $dataFim, $cambio),
                'distribuicao_range'      => $this->calcularSerasaDistribuicaoRange($year, $months, $dataInicio, $dataFim),
                'distribuicao_status_cpf' => $this->calcularSerasaDistribuicaoStatusCpf($year, $months, $dataInicio, $dataFim),
                'tendencia_mensal'        => $this->calcularSerasaTendenciaMensal($year, $months, $dataInicio, $dataFim),
                'detalhe'                 => $this->calcularSerasaDetalhe($year, $months, $dataInicio, $dataFim, $cambio),
                'cached'                  => false,
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
        $this->setTemplate('executive');
    }

    // ===================================================================
    // FILTROS COMPARTILHADOS (dropdowns de campanha/form/responsável)
    // ===================================================================

    public function campanhas()
    {
        try {
            $cached = $this->cacheGet('executive_campanhas', self::CACHE_TTL_DROPDOWN);
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
            $data = array_column($this->queryAll($sql), 'nome');
            $this->cacheSet('executive_campanhas', $data);
            $this->jsonOut($data);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    public function forms()
    {
        try {
            $cached = $this->cacheGet('executive_forms', self::CACHE_TTL_DROPDOWN);
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
            $data = array_column($this->queryAll($sql), 'form');
            $this->cacheSet('executive_forms', $data);
            $this->jsonOut($data);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    public function responsaveis()
    {
        try {
            $cached = $this->cacheGet('executive_responsaveis', self::CACHE_TTL_DROPDOWN);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $sql = "
                SELECT DISTINCT responsavel FROM (
                    SELECT responsavel_leadV2 AS responsavel FROM leadV2
                        WHERE responsavel_leadV2 IS NOT NULL AND responsavel_leadV2 != ''
                    UNION
                    SELECT responsavel_dealV2 AS responsavel FROM dealV2
                        WHERE responsavel_dealV2 IS NOT NULL AND responsavel_dealV2 != ''
                ) t ORDER BY responsavel
            ";
            $data = array_column($this->queryAll($sql), 'responsavel');
            $this->cacheSet('executive_responsaveis', $data);
            $this->jsonOut($data);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    // ===================================================================
    // HELPERS: HTTP / JSON
    // ===================================================================

    /** Lê um parâmetro GET, normalizando string vazia para null. */
    private function g(string $key): ?string
    {
        $v = $_GET[$key] ?? null;
        return ($v === null || $v === '') ? null : trim($v);
    }

    /** Lista fixa de especialistas usada como padrão quando nenhum responsável é filtrado. */
    private function equipeEspecialistasPadrao(): string
    {
        return 'Rafael Torres,Beatriz Lemos,Camila Duarte,Paulo Nogueira,Juliana Prado,Ricardo Albuquerque';
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
        // TEMPORÁRIO: sempre detalhado (independente de DEV/PROD) para investigar o
        // erro "Failed to fetch" em produção. Reverter para a checagem de $isDev
        // assim que o problema for identificado (não expor stack trace em produção).
        echo json_encode(
            [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    // ===================================================================
    // HELPERS: CACHE EM ARQUIVO (.mrphp/cache) — TTL curto, sem tocar no banco
    // ===================================================================

    /** Segundos de validade do cache dos dropdowns compartilhados (campanha/form/responsável). */
    private const CACHE_TTL_DROPDOWN = 600;

    /** Segundos de validade do cache da resposta agregada de cada endpoint de dados.
     *  Cache-miss custa alguns segundos (ver Fase 1C do plano de performance —
     *  vários métodos ainda fazem full scan mesmo com os índices da Fase 1/1B,
     *  porque o filtro de período fica preso em CASE WHEN ou usa YEAR()/MONTH()
     *  não-sargável). Não existe pre-warm automático — confirmado que nenhum
     *  workflow de warm-cache já existiu neste repositório. */
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

    /**
     * Fase 1C: reescrito para gerar intervalo de data literal ($col >= ? AND
     * $col <= ?, sargável) em vez de YEAR($col)=?/MONTH($col) IN(...) — o
     * modo padrão de uso de todas as abas do executive é ano+mês via dropdown
     * (nunca intervalo manual), então essa era a única real gerada no dia a dia,
     * anulando os índices das Fases 1/1B. Mesma técnica já provada em produção
     * em marketing_controle.php (harness de equivalência + diff de JSON real).
     *
     * Guarda: só monta o range literal quando $col é uma coluna simples
     * (alias.coluna). Quando $col é uma expressão computada (ex.:
     * exprDataAgendadaCorrigida(), um CASE WHEN de várias linhas — único caso
     * assim no arquivo, usado em calcularKpisEspecialistas()), reescrever como
     * range literal duplicaria a expressão inteira por range sem nenhum ganho
     * de sargabilidade (não dá pra indexar uma expressão) — mantém o
     * comportamento antigo YEAR()/MONTH() só nesse caso via buildDateFilterLegacy().
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

    /** Versão antiga (YEAR()/MONTH(), não-sargável) — usada só quando $col é uma
     *  expressão computada, não uma coluna simples (ver guarda em buildDateFilter). */
    private function buildDateFilterLegacy(string $col, $year, ?string $months): array
    {
        $conds = [];
        $params = [];

        if ($year !== null && $year !== '') {
            $years = array_values(array_filter(array_map('trim', explode(',', (string)$year)), fn($v) => $v !== ''));
            if (count($years) === 1) {
                $conds[] = "YEAR($col) = ?";
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

    /** Filtro de período: range (data_inicio/data_fim) OU year/months. Exige
     *  AMBOS data_inicio E data_fim pra entrar no modo range (preserva o
     *  comportamento atual do executive — diferente do marketing, que aceita
     *  um campo só; decisão da Fase 1C: fix de performance, não de resultado). */
    private function buildPeriodFilter(string $col, $year, ?string $months, ?string $dataInicio = null, ?string $dataFim = null): array
    {
        if ($dataInicio !== null && $dataInicio !== '' && $dataFim !== null && $dataFim !== '') {
            return ['sql' => "$col >= ? AND $col <= ?", 'params' => [$dataInicio, $dataFim . ' 23:59:59']];
        }
        return $this->buildDateFilter($col, $year, $months);
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

    /**
     * Condição de intervalo de datas pronta para uso dentro de CASE WHEN, com params
     * posicionais — mesmo formato do ramo "range" de buildPeriodFilter(), usada para
     * consolidar múltiplos períodos (mês atual/ano passado/último mês) numa só query.
     */
    private function condPeriodo(string $col, string $start, string $end): array
    {
        return ['sql' => "$col >= ? AND $col <= ?", 'params' => [$start, $end . ' 23:59:59']];
    }

    /** Fase 1C (item 11): subquery de dedupe de câmbio (2-3 registros por data
     *  em cambio, ver CLAUDE.md) — texto extraído pra um único lugar em vez de
     *  10 cópias no arquivo (higiene/manutenção, não é ganho de performance
     *  direto). Usar como `LEFT JOIN {$this->cambioDedupSubquery()} ca ON ...`. */
    private function cambioDedupSubquery(): string
    {
        return "(SELECT data_cambio, MAX(valor_cambio) AS valor_cambio FROM cambio GROUP BY data_cambio)";
    }

    /** Custo físico de evento (custoEvento) pode ter data_custoEvento futura/recente sem
     *  câmbio cadastrado ainda (sync de `cambio` roda até "hoje") — nesse caso o match
     *  exato por data falha, ca.valor_cambio fica NULL e o valor é zerado silenciosamente
     *  (NULLIF(NULL,0) → NULL → COALESCE(SUM(NULL),0) = 0). Aqui o join casa com o câmbio
     *  mais recente disponível até a data do custo, em vez de exigir data exata. */
    private function cambioMaisRecenteJoin(string $dataCol): string
    {
        return "LEFT JOIN {$this->cambioDedupSubquery()} ca
                ON ca.data_cambio = (SELECT MAX(c2.data_cambio) FROM cambio c2 WHERE c2.data_cambio <= {$dataCol})";
    }

    /**
     * Fase 1C: WHERE externo (sargável) que limita o scan à UNIÃO de todos os
     * intervalos de data considerados por uma query "Unificada" (filtro
     * principal via $mainDateParams + cada período comparativo ativo em
     * $periodosAtivos) — não muda quais linhas entram em qual SUM(CASE), só
     * evita escanear linhas que não poderiam bater em NENHUM dos períodos.
     * Usa min()/max() sobre os próprios params (não recalcula datas) porque
     * calcularPeriodosComparativos() só considera um único $refYear — em
     * seleção multi-ano, os comparativos (ma/py/lm) não cobririam o 2º ano
     * sozinhos, então o bound tem que vir também de $mainDateParams.
     * Retorna '1=1' (sem limite) se não houver nenhuma data conhecida (ex.:
     * filtro principal vazio) — mantém o full scan só nesse caso raro.
     */
    private function buildUnionPeriodoLimite(string $col, array $mainDateParams, array $periodosAtivos): array
    {
        $datas = $mainDateParams;
        foreach ($periodosAtivos as [$start, $end]) {
            $datas[] = $start . ' 00:00:00';
            $datas[] = $end . ' 23:59:59';
        }
        if (!$datas) {
            return ['sql' => '1=1', 'params' => []];
        }
        return ['sql' => "$col >= ? AND $col <= ?", 'params' => [min($datas), max($datas)]];
    }

    /** Mesma ideia de buildUnionPeriodoLimite(), mas para queries que testam
     *  VÁRIAS colunas de data diferentes na mesma query (ex.: dataVenda +
     *  dataConsultaRealizada + dataContratoComboAssinado) — o bound de datas é
     *  o mesmo em todos os casos (mesmo $year/$months/período), só a coluna
     *  testada muda por métrica, então o limite vira um OR entre elas. */
    private function buildUnionPeriodoLimiteMultiCol(array $cols, array $mainDateParams, array $periodosAtivos): array
    {
        $datas = $mainDateParams;
        foreach ($periodosAtivos as [$start, $end]) {
            $datas[] = $start . ' 00:00:00';
            $datas[] = $end . ' 23:59:59';
        }
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
     * Mesma lógica de buildInFilter(), mas sem o prefixo "AND" — para uso dentro de um
     * SUM(CASE WHEN ...) em vez do WHERE externo (necessário para consolidar múltiplas
     * passadas pela mesma tabela numa única query, ver calcularLeadsUnificado()/
     * calcularFunilUnificado()).
     */
    private function condInFilter(string $col, ?string $csv): array
    {
        if ($csv === null || trim($csv) === '') {
            return ['sql' => '1=1', 'params' => []];
        }
        $vals = array_values(array_filter(array_map('trim', explode(',', $csv)), fn($v) => $v !== ''));
        if (!$vals) {
            return ['sql' => '1=1', 'params' => []];
        }
        return ['sql' => "$col IN (" . implode(',', array_fill(0, count($vals), '?')) . ")", 'params' => $vals];
    }

    private function sqlFiltroCampanha(string $col, ?string $class): string
    {
        if ($class === null) return '';
        return match ($class) {
            'Orgânico'  => "AND (LEFT(UPPER($col), 3) = 'ORG' OR $col = 'Negado Cliente')",
            'Indicação' => "AND (LEFT(UPPER($col), 3) = 'IND' OR $col IN ('F&F', 'MGM'))",
            'Evento'    => "AND LEFT(UPPER($col), 3) IN ('OTR', 'INA', 'OTW', 'WEB')",
            'Digital'   => "AND LEFT(UPPER($col), 3) NOT IN ('ORG', 'IND', 'OTR', 'INA', 'OTW', 'WEB') AND $col NOT IN ('Negado Cliente', 'F&F', 'MGM')",
            default     => '',
        };
    }

    /**
     * Chaves dos 5 segmentos da aba Executive => classe usada na classificação por
     * NOME de campanha (sqlFiltroCampanha) ou por FORM (quando uma campanha específica
     * está selecionada). null = "global", sem restrição de classe.
     */
    private const SEGMENTOS = [
        'global'    => null,
        'evento'    => 'Evento',
        'digital'   => 'Digital',
        'organico'  => 'Orgânico',
        'indicacao' => 'Indicação',
    ];

    /** Chaves dos 3 segmentos da aba Digital × Evento (classificação só por FORM). */
    private const SEGMENTOS_FORM = [
        'global'  => null,
        'evento'  => 'Evento',
        'digital' => 'Digital',
    ];

    /**
     * Condição booleana (sem prefixo "AND", pronta para uso dentro de CASE WHEN) que é
     * verdadeira quando a linha pertence ao segmento $classe — permite calcular os 5
     * segmentos numa única passada pela tabela (SUM(CASE WHEN cond...)) em vez de uma
     * query por segmento. Replica exatamente a lógica que antes vivia espalhada entre
     * sqlFiltroCampanha()/sqlFiltroFormClass(): quando uma campanha específica está
     * selecionada ($campanhaEspecifica !== null), a classificação usa o form; caso
     * contrário usa o prefixo do nome da campanha.
     */
    private function condSegmento(string $classCol, ?string $campanhaEspecifica, string $formCol, ?string $classe): string
    {
        if ($classe === null) return '1=1';
        if ($campanhaEspecifica !== null) {
            return $this->condFormClasse($formCol, $classe);
        }
        return match ($classe) {
            'Orgânico'  => "(LEFT(UPPER($classCol), 3) = 'ORG' OR $classCol = 'Negado Cliente')",
            'Indicação' => "(LEFT(UPPER($classCol), 3) = 'IND' OR $classCol IN ('F&F', 'MGM'))",
            'Evento'    => "(LEFT(UPPER($classCol), 3) IN ('OTR', 'INA', 'OTW', 'WEB'))",
            'Digital'   => "(LEFT(UPPER($classCol), 3) NOT IN ('ORG', 'IND', 'OTR', 'INA', 'OTW', 'WEB') AND $classCol NOT IN ('Negado Cliente', 'F&F', 'MGM'))",
            default     => '1=1',
        };
    }

    /** Classificação por NOME de campanha, sempre — usada pelo custoMkt da aba Executive. */
    private function condClasseCampanha(string $col, ?string $classe): string
    {
        if ($classe === null) return '1=1';
        return match ($classe) {
            'Orgânico'  => "(LEFT(UPPER($col), 3) = 'ORG' OR $col = 'Negado Cliente')",
            'Indicação' => "(LEFT(UPPER($col), 3) = 'IND' OR $col IN ('F&F', 'MGM'))",
            'Evento'    => "(LEFT(UPPER($col), 3) IN ('OTR', 'INA', 'OTW', 'WEB'))",
            'Digital'   => "(LEFT(UPPER($col), 3) NOT IN ('ORG', 'IND', 'OTR', 'INA', 'OTW', 'WEB') AND $col NOT IN ('Negado Cliente', 'F&F', 'MGM'))",
            default     => '1=1',
        };
    }

    /** Classificação por FORM (Evento vs. não-Evento) — usada pela aba Digital × Evento. */
    private function condFormClasse(string $formCol, ?string $classe): string
    {
        if ($classe === null) return '1=1';
        return $classe === 'Evento'
            ? "($formCol IS NOT NULL AND $formCol != '' AND LEFT(UPPER($formCol), 3) IN ('OTR', 'INA', 'OTW', 'WEB'))"
            : "($formCol IS NULL OR $formCol = '' OR LEFT(UPPER($formCol), 3) NOT IN ('OTR', 'INA', 'OTW', 'WEB'))";
    }

    /** Classificação de custoMkt por identificador (aba Digital × Evento). */
    private function condFormClasseCustoMkt(?string $classe): string
    {
        if ($classe === null) return '1=1';
        $expr = "(
            LEFT(UPPER(COALESCE(cm.identificador_custoMkt, '')), 3) IN ('OTR', 'INA', 'OTW', 'WEB')
            OR (
                (cm.identificador_custoMkt IS NULL OR cm.identificador_custoMkt = '')
                AND LEFT(UPPER(cm.nomeCampanha_custoMkt), 3) = 'OTR'
            )
        )";
        return $classe === 'Evento' ? $expr : "NOT $expr";
    }

    // ===================================================================
    // CÁLCULOS: EXECUTIVE (classificação por nome de campanha)
    // ===================================================================

    /** Extrai as janelas de data ativas (ma/py/lm) de calcularPeriodosComparativos() no
     *  formato usado por condPeriodo()/calcularLeadsUnificado()/calcularFunilUnificado(). */
    private function periodosAtivosFrom(array $periodos): array
    {
        $periodosAtivos = [
            'ma' => [$periodos['ma']['start'], $periodos['ma']['end']],
            'py' => [$periodos['py']['start'], $periodos['py']['end']],
        ];
        if ($periodos['has_month_filter'] && $periodos['lm']) {
            $periodosAtivos['lm'] = [$periodos['lm']['start'], $periodos['lm']['end']];
        }
        return $periodosAtivos;
    }

    /**
     * Substitui calcularLeadsMultiSegmento() + calcularLeadsMultiSegmentoMultiPeriodo()
     * (que juntas escaneavam leadV2 duas vezes por chamada de dados() — uma com os
     * filtros do usuário para o período selecionado, outra sem filtro nenhum para os
     * comparativos ma/py/lm) por uma única passada pela tabela: o filtro do usuário
     * (campanha/form/responsável/data) entra dentro do próprio SUM(CASE) da coluna
     * "_main" em vez do WHERE externo, e cada janela de comparação ganha seu próprio
     * conjunto de colunas "_{$pKey}" sem filtro nenhum — exatamente como cada uma das
     * duas queries originais computava isoladamente, só que na mesma leitura da tabela.
     *
     * @return array{main: array, porPeriodo: array} 'main' no formato de
     *   calcularLeadsMultiSegmento(); 'porPeriodo' no formato de
     *   calcularLeadsMultiSegmentoMultiPeriodo().
     */
    private function calcularLeadsUnificado($year, ?string $months, ?string $campanha, ?string $form, ?string $responsavel, ?string $dataInicio, ?string $dataFim, array $periodosAtivos): array
    {
        $mainDate = $this->buildPeriodFilter('l.dataUltForm_leadV2', $year, $months, $dataInicio, $dataFim);
        $campCond = $this->condInFilter('l.utmCampaign_leadV2', $campanha);
        $formCond = $this->condInFilter('l.formPreenchido_leadV2', $form);
        $respCond = $this->condInFilter('l.responsavel_leadV2', $responsavel);
        $filtroMain       = "{$mainDate['sql']} AND {$campCond['sql']} AND {$formCond['sql']} AND {$respCond['sql']}";
        $filtroMainParams = array_merge($mainDate['params'], $campCond['params'], $formCond['params'], $respCond['params']);

        $selects = [];
        $params  = [];
        foreach (self::SEGMENTOS as $segKey => $classe) {
            $condMain = $this->condSegmento('l.utmCampaign_leadV2', $campanha, 'l.formPreenchido_leadV2', $classe);
            $selects[] = "SUM(CASE WHEN $condMain AND $filtroMain THEN 1 ELSE 0 END) AS total_{$segKey}_main";
            $params = array_merge($params, $filtroMainParams);
            $selects[] = "SUM(CASE WHEN $condMain AND $filtroMain AND l.qualificado_leadV2 = 'Sim' THEN 1 ELSE 0 END) AS qual_{$segKey}_main";
            $params = array_merge($params, $filtroMainParams);

            $condCompare = $this->condSegmento('l.utmCampaign_leadV2', null, 'l.formPreenchido_leadV2', $classe);
            foreach ($periodosAtivos as $pKey => [$start, $end]) {
                $dp = $this->condPeriodo('l.dataUltForm_leadV2', $start, $end);
                $selects[] = "SUM(CASE WHEN $condCompare AND {$dp['sql']} THEN 1 ELSE 0 END) AS total_{$segKey}_{$pKey}";
                $params = array_merge($params, $dp['params']);
                $selects[] = "SUM(CASE WHEN $condCompare AND {$dp['sql']} AND l.qualificado_leadV2 = 'Sim' THEN 1 ELSE 0 END) AS qual_{$segKey}_{$pKey}";
                $params = array_merge($params, $dp['params']);
            }
        }

        $limite = $this->buildUnionPeriodoLimite('l.dataUltForm_leadV2', $mainDate['params'], $periodosAtivos);
        $sql = "SELECT " . implode(",\n                ", $selects) . " FROM leadV2 l WHERE {$limite['sql']}";
        $params = array_merge($params, $limite['params']);
        $row = $this->queryOne($sql, $params);

        $main = [];
        $porPeriodo = [];
        foreach (self::SEGMENTOS as $segKey => $classe) {
            $main[$segKey] = [
                'total_leads'        => (int)($row["total_{$segKey}_main"] ?? 0),
                'leads_qualificados' => (int)($row["qual_{$segKey}_main"] ?? 0),
            ];
            foreach ($periodosAtivos as $pKey => $_) {
                $porPeriodo[$pKey][$segKey] = [
                    'total_leads'        => (int)($row["total_{$segKey}_{$pKey}"] ?? 0),
                    'leads_qualificados' => (int)($row["qual_{$segKey}_{$pKey}"] ?? 0),
                ];
            }
        }
        return ['main' => $main, 'porPeriodo' => $porPeriodo];
    }

    /** Mesma consolidação de calcularLeadsUnificado(), aplicada ao funil (dealV2) — substitui
     *  calcularFunilComercialMultiSegmento() + calcularFunilComercialMultiSegmentoMultiPeriodo(). */
    private function calcularFunilUnificado($year, ?string $months, ?string $campanha, ?string $form, ?string $responsavel, ?string $dataInicio, ?string $dataFim, array $periodosAtivos): array
    {
        $campCond = $this->condInFilter('d.utmCampaign_dealV2', $campanha);
        $formCond = $this->condInFilter('d.formPreenchido_dealV2', $form);
        $respCond = $this->condInFilter('d.responsavel_dealV2', $responsavel);
        $filtroMain       = "{$campCond['sql']} AND {$formCond['sql']} AND {$respCond['sql']}";
        $filtroMainParams = array_merge($campCond['params'], $formCond['params'], $respCond['params']);

        $selects = [];
        $params  = [];
        foreach (self::SEGMENTOS as $segKey => $classe) {
            $condMain = $this->condSegmento('d.utmCampaign_dealV2', $campanha, 'd.formPreenchido_dealV2', $classe);

            $df1 = $this->buildPeriodFilter('d.dataVenda_dealV2', $year, $months, $dataInicio, $dataFim);
            $selects[] = "SUM(CASE WHEN {$df1['sql']}
                     AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
                     AND d.faseId_dealV2 = 'C9:Won' AND $condMain AND $filtroMain
                    THEN 1 ELSE 0 END) AS cadeira_{$segKey}_main";
            $params = array_merge($params, $df1['params'], $filtroMainParams);

            $df2 = $this->buildPeriodFilter('d.dataVenda_dealV2', $year, $months, $dataInicio, $dataFim);
            $selects[] = "SUM(CASE WHEN {$df2['sql']}
                     AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
                     AND d.faseId_dealV2 IN ('Won', 'C11:WON') AND $condMain AND $filtroMain
                    THEN 1 ELSE 0 END) AS consulta_{$segKey}_main";
            $params = array_merge($params, $df2['params'], $filtroMainParams);

            $df3 = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $year, $months, $dataInicio, $dataFim);
            $selects[] = "SUM(CASE WHEN {$df3['sql']}
                     AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
                     AND LEFT(d.faseId_dealV2, 2) = 'C5' AND $condMain AND $filtroMain
                    THEN 1 ELSE 0 END) AS realizada_{$segKey}_main";
            $params = array_merge($params, $df3['params'], $filtroMainParams);

            $df4 = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $year, $months, $dataInicio, $dataFim);
            $selects[] = "SUM(CASE WHEN {$df4['sql']}
                     AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
                     AND d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON') AND $condMain AND $filtroMain
                    THEN 1 ELSE 0 END) AS combo_{$segKey}_main";
            $params = array_merge($params, $df4['params'], $filtroMainParams);

            $df4b = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $year, $months, $dataInicio, $dataFim);
            $selects[] = "SUM(CASE WHEN {$df4b['sql']}
                     AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
                     AND d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON') AND $condMain AND $filtroMain
                    THEN COALESCE(d.valorComboLiquido_dealV2, 0) ELSE 0 END) AS fatur_{$segKey}_main";
            $params = array_merge($params, $df4b['params'], $filtroMainParams);

            $condCompare = $this->condSegmento('d.utmCampaign_dealV2', null, 'd.formPreenchido_dealV2', $classe);
            foreach ($periodosAtivos as $pKey => [$start, $end]) {
                $dpVenda = $this->condPeriodo('d.dataVenda_dealV2', $start, $end);
                $selects[] = "SUM(CASE WHEN {$dpVenda['sql']}
                         AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
                         AND d.faseId_dealV2 = 'C9:Won' AND $condCompare
                        THEN 1 ELSE 0 END) AS cadeira_{$segKey}_{$pKey}";
                $params = array_merge($params, $dpVenda['params']);

                $selects[] = "SUM(CASE WHEN {$dpVenda['sql']}
                         AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
                         AND d.faseId_dealV2 IN ('Won', 'C11:WON') AND $condCompare
                        THEN 1 ELSE 0 END) AS consulta_{$segKey}_{$pKey}";
                $params = array_merge($params, $dpVenda['params']);

                $dpRealizada = $this->condPeriodo('d.dataConsultaRealizada_dealV2', $start, $end);
                $selects[] = "SUM(CASE WHEN {$dpRealizada['sql']}
                         AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
                         AND LEFT(d.faseId_dealV2, 2) = 'C5' AND $condCompare
                        THEN 1 ELSE 0 END) AS realizada_{$segKey}_{$pKey}";
                $params = array_merge($params, $dpRealizada['params']);

                $dpCombo = $this->condPeriodo('d.dataContratoComboAssinado_dealV2', $start, $end);
                $selects[] = "SUM(CASE WHEN {$dpCombo['sql']}
                         AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
                         AND d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON') AND $condCompare
                        THEN 1 ELSE 0 END) AS combo_{$segKey}_{$pKey}";
                $params = array_merge($params, $dpCombo['params']);

                $selects[] = "SUM(CASE WHEN {$dpCombo['sql']}
                         AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
                         AND d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON') AND $condCompare
                        THEN COALESCE(d.valorComboLiquido_dealV2, 0) ELSE 0 END) AS fatur_{$segKey}_{$pKey}";
                $params = array_merge($params, $dpCombo['params']);
            }
        }

        // Bound de união (Fase 1C): $df1['params'] serve de fonte pro filtro principal
        // porque buildPeriodFilter() calcula as mesmas datas independente de qual coluna
        // é passada (a coluna só aparece no SQL, não no cálculo da data) — d.dataVenda_dealV2,
        // d.dataConsultaRealizada_dealV2 e d.dataContratoComboAssinado_dealV2 teriam o
        // mesmo bound de qualquer forma.
        $limite = $this->buildUnionPeriodoLimiteMultiCol(
            ['d.dataVenda_dealV2', 'd.dataConsultaRealizada_dealV2', 'd.dataContratoComboAssinado_dealV2'],
            $df1['params'],
            $periodosAtivos
        );
        $sql = "SELECT " . implode(",\n                ", $selects) . " FROM dealV2 d WHERE {$limite['sql']}";
        $params = array_merge($params, $limite['params']);
        $row = $this->queryOne($sql, $params);

        $main = [];
        $porPeriodo = [];
        foreach (self::SEGMENTOS as $segKey => $classe) {
            $combosMain = (int)($row["combo_{$segKey}_main"] ?? 0);
            $faturMain  = (float)($row["fatur_{$segKey}_main"] ?? 0);
            $main[$segKey] = [
                'cadeira_vendida'      => (int)($row["cadeira_{$segKey}_main"] ?? 0),
                'consultas_vendidas'   => (int)($row["consulta_{$segKey}_main"] ?? 0),
                'consultas_realizadas' => (int)($row["realizada_{$segKey}_main"] ?? 0),
                'combos_vendidos'      => $combosMain,
                'faturamento_total'    => $faturMain,
                'ticket_medio'         => $combosMain > 0 ? $faturMain / $combosMain : 0.0,
            ];
            foreach ($periodosAtivos as $pKey => $_) {
                $combos = (int)($row["combo_{$segKey}_{$pKey}"] ?? 0);
                $fatur  = (float)($row["fatur_{$segKey}_{$pKey}"] ?? 0);
                $porPeriodo[$pKey][$segKey] = [
                    'cadeira_vendida'      => (int)($row["cadeira_{$segKey}_{$pKey}"] ?? 0),
                    'consultas_vendidas'   => (int)($row["consulta_{$segKey}_{$pKey}"] ?? 0),
                    'consultas_realizadas' => (int)($row["realizada_{$segKey}_{$pKey}"] ?? 0),
                    'combos_vendidos'      => $combos,
                    'faturamento_total'    => $fatur,
                    'ticket_medio'         => $combos > 0 ? $fatur / $combos : 0.0,
                ];
            }
        }
        return ['main' => $main, 'porPeriodo' => $porPeriodo];
    }

    /**
     * Card "Vistos" do painel global: combos vendidos e faturamento agrupados por
     * tipo de visto (vistoEscolhido_dealV2) e, separadamente, por vendedor da equipe
     * fixa de especialistas — mesma condição de "combo vendido" e mesma exclusão de
     * distrato (vistoEscolhido_dealV2 = 'F') já usadas em calcularFunilUnificado().
     * Uma única query agrupada por (tipo_visto, vendedor) alimenta as duas visões —
     * antes eram duas queries com WHERE idêntico, cada uma escaneando dealV2 de novo
     * só para trocar o GROUP BY.
     */
    private function calcularVistos($year, ?string $months, ?string $campanha, ?string $form, ?string $dataInicio, ?string $dataFim): array
    {
        $campCond = $this->condInFilter('d.utmCampaign_dealV2', $campanha);
        $formCond = $this->condInFilter('d.formPreenchido_dealV2', $form);
        $dateFilter = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $year, $months, $dataInicio, $dataFim);

        $comboCond = "d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
            AND d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON')";
        $tipoVistoExpr = "CASE WHEN d.vistoEscolhido_dealV2 IS NULL OR d.vistoEscolhido_dealV2 = '' THEN 'Não informado' ELSE d.vistoEscolhido_dealV2 END";

        $sql = "
            SELECT $tipoVistoExpr AS tipo_visto,
                   d.responsavel_dealV2 AS vendedor,
                   COUNT(*) AS combos,
                   SUM(COALESCE(d.valorComboLiquido_dealV2, 0)) AS faturamento
            FROM dealV2 d
            WHERE {$dateFilter['sql']} AND $comboCond AND {$campCond['sql']} AND {$formCond['sql']}
            GROUP BY tipo_visto, vendedor
        ";
        $params = array_merge($dateFilter['params'], $campCond['params'], $formCond['params']);

        $porTipoAgg = [];
        $porVendedorAgg = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $combos = (int)$r['combos'];
            $fatur  = (float)$r['faturamento'];

            $tipo = $r['tipo_visto'];
            if (!isset($porTipoAgg[$tipo])) {
                $porTipoAgg[$tipo] = ['combos' => 0, 'faturamento' => 0.0];
            }
            $porTipoAgg[$tipo]['combos']      += $combos;
            $porTipoAgg[$tipo]['faturamento'] += $fatur;

            // Por vendedor considera só quem tem responsável preenchido, igual à query original.
            $vendedor = $r['vendedor'];
            if ($vendedor !== null && $vendedor !== '') {
                if (!isset($porVendedorAgg[$vendedor])) {
                    $porVendedorAgg[$vendedor] = ['combos' => 0, 'faturamento' => 0.0];
                }
                $porVendedorAgg[$vendedor]['combos']      += $combos;
                $porVendedorAgg[$vendedor]['faturamento'] += $fatur;
            }
        }

        $porTipo = [];
        foreach ($porTipoAgg as $tipo => $v) {
            $porTipo[] = [
                'tipo_visto'   => $tipo,
                'combos'       => $v['combos'],
                'faturamento'  => $v['faturamento'],
                'ticket_medio' => $v['combos'] > 0 ? $v['faturamento'] / $v['combos'] : 0.0,
            ];
        }
        usort($porTipo, fn($a, $b) => $b['combos'] <=> $a['combos']);

        $porVendedor = [];
        foreach ($porVendedorAgg as $vendedor => $v) {
            $porVendedor[] = [
                'vendedor'     => $vendedor,
                'combos'       => $v['combos'],
                'faturamento'  => $v['faturamento'],
                'ticket_medio' => $v['combos'] > 0 ? $v['faturamento'] / $v['combos'] : 0.0,
            ];
        }
        usort($porVendedor, fn($a, $b) => $b['combos'] <=> $a['combos']);

        return ['por_tipo' => $porTipo, 'por_vendedor' => $porVendedor];
    }

    /**
     * Mesma consolidação para custoMkt. Preserva a regra original: sem campanha
     * específica selecionada, Orgânico/Indicação nunca têm custo de marketing
     * rastreado (não entram no SELECT, ficam zerados diretamente em PHP).
     */
    private function calcularCustoMktMultiSegmento($year, ?string $months, ?string $campanha, ?string $form, ?string $dataInicio, ?string $dataFim): array
    {
        $date       = $this->buildPeriodFilter('cm.data_custoMkt', $year, $months, $dataInicio, $dataFim);
        $campFilter = $campanha !== null ? $this->buildInFilter('cm.nomeCampanha_custoMkt', $campanha) : ['sql' => '', 'params' => []];
        $formFilter = $this->buildInFilter('cm.identificador_custoMkt', $form);

        $classesQuery = $campanha !== null
            ? self::SEGMENTOS
            : array_filter(self::SEGMENTOS, fn($k) => !in_array($k, ['organico', 'indicacao'], true), ARRAY_FILTER_USE_KEY);

        $selects = [];
        foreach ($classesQuery as $key => $classe) {
            $cond = $this->condClasseCampanha('cm.nomeCampanha_custoMkt', $classe);
            $selects[] = "SUM(CASE
                    WHEN YEAR(cm.data_custoMkt) <= 2025 AND cm.origem_custoMkt = 'meta' AND $cond THEN cm.valor_custoMkt
                    WHEN YEAR(cm.data_custoMkt) >= 2026 AND cm.conta_custoMkt IN ('Dollar 1', 'Dollar 2') AND $cond THEN cm.valor_custoMkt
                    ELSE 0
                END) AS dollar_{$key}";
            $selects[] = "SUM(CASE
                    WHEN YEAR(cm.data_custoMkt) <= 2025 AND cm.origem_custoMkt = 'google' AND $cond THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio, 0)
                    WHEN YEAR(cm.data_custoMkt) >= 2026 AND cm.conta_custoMkt <> 'Dollar 1' AND cm.conta_custoMkt <> 'Dollar 2' AND $cond THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio, 0)
                    ELSE 0
                END) AS real_{$key}";
        }

        $sql = "
            SELECT " . implode(",\n                ", $selects) . "
            FROM custoMkt cm
            LEFT JOIN {$this->cambioDedupSubquery()} ca
                ON ca.data_cambio = cm.data_custoMkt
            WHERE {$date['sql']}
            {$campFilter['sql']} {$formFilter['sql']}
        ";
        $params = array_merge($date['params'], $campFilter['params'], $formFilter['params']);
        $row = $this->queryOne($sql, $params);

        $result = [];
        foreach (self::SEGMENTOS as $key => $classe) {
            if (!array_key_exists($key, $classesQuery)) {
                $result[$key] = ['custo_dollar' => 0.0, 'custo_real_convertido' => 0.0, 'custo_real_total' => 0.0];
                continue;
            }
            $dollar = (float)($row["dollar_{$key}"] ?? 0);
            $real   = (float)($row["real_{$key}"] ?? 0);
            $result[$key] = ['custo_dollar' => $dollar, 'custo_real_convertido' => $real, 'custo_real_total' => $dollar + $real];
        }
        return $result;
    }

    private function calcularCustoFisicoEvento($year, ?string $months, ?string $campanha_class, ?string $campanha, ?string $form, ?string $dataInicio, ?string $dataFim): float
    {
        $date = $this->buildPeriodFilter('ce.data_custoEvento', $year, $months, $dataInicio, $dataFim);

        if ($campanha_class !== null && $campanha_class !== 'Evento') {
            return 0.0;
        }

        if ($form !== null) {
            $formFilter = $this->buildInFilter('ce.identificador_custoEvento', $form);
            $sql = "
                SELECT COALESCE(SUM(ce.valor_custoEvento / NULLIF(ca.valor_cambio, 0)), 0) AS custo_fisico
                FROM custoEvento ce
                {$this->cambioMaisRecenteJoin('ce.data_custoEvento')}
                WHERE {$date['sql']} {$formFilter['sql']}
            ";
            $row = $this->queryOne($sql, array_merge($date['params'], $formFilter['params']));
            return (float)($row['custo_fisico'] ?? 0);
        }

        if ($campanha !== null) {
            $campFilter = $this->buildInFilter('cm.nomeCampanha_custoMkt', $campanha);
            $sql = "
                SELECT COALESCE(SUM(ce.valor_custoEvento / NULLIF(ca.valor_cambio, 0)), 0) AS custo_fisico
                FROM custoEvento ce
                {$this->cambioMaisRecenteJoin('ce.data_custoEvento')}
                WHERE {$date['sql']}
                  AND ce.identificador_custoEvento IN (
                      SELECT DISTINCT cm.identificador_custoMkt FROM custoMkt cm WHERE 1=1 {$campFilter['sql']}
                  )
            ";
            $row = $this->queryOne($sql, array_merge($date['params'], $campFilter['params']));
            return (float)($row['custo_fisico'] ?? 0);
        }

        if ($campanha_class === 'Evento') {
            // Reaproveita eventoIdentificadores() (já usada por calcularCustoFisicoEventoMultiPeriodo())
            // em vez de repetir a subquery de classificação de campanha a cada chamada.
            $idsEvento = $this->eventoIdentificadores();
            if (!$idsEvento) {
                return 0.0;
            }
            $placeholders = implode(',', array_fill(0, count($idsEvento), '?'));
            $sql = "
                SELECT COALESCE(SUM(ce.valor_custoEvento / NULLIF(ca.valor_cambio, 0)), 0) AS custo_fisico
                FROM custoEvento ce
                {$this->cambioMaisRecenteJoin('ce.data_custoEvento')}
                WHERE {$date['sql']}
                  AND ce.identificador_custoEvento IN ($placeholders)
            ";
            $row = $this->queryOne($sql, array_merge($date['params'], $idsEvento));
            return (float)($row['custo_fisico'] ?? 0);
        } else {
            $sql = "
                SELECT COALESCE(SUM(ce.valor_custoEvento / NULLIF(ca.valor_cambio, 0)), 0) AS custo_fisico
                FROM custoEvento ce
                {$this->cambioMaisRecenteJoin('ce.data_custoEvento')}
                WHERE {$date['sql']}
            ";
        }

        $row = $this->queryOne($sql, $date['params']);
        return (float)($row['custo_fisico'] ?? 0);
    }

    /** Matemática pura dos KPIs financeiros a partir dos totais já calculados — sem I/O. */
    private function montarKpisFinanceiros(array $custoMkt, float $custoFis, array $funil, array $leads): array
    {
        $custoMktTotal = $custoMkt['custo_real_total'];
        $investimento  = $custoMktTotal + $custoFis;
        $faturamento   = $funil['faturamento_total'];

        $lucro = $faturamento - $investimento;
        $roi   = $investimento > 0 ? $faturamento / $investimento : 0.0;

        $leadsQualif = $leads['leads_qualificados'];
        $cpl         = $leadsQualif > 0 ? $custoMktTotal / $leadsQualif : 0.0;
        $cacCadeira  = $funil['cadeira_vendida'] > 0 ? $custoMktTotal / $funil['cadeira_vendida'] : 0.0;
        $cacConsulta = $funil['consultas_vendidas'] > 0 ? $custoMktTotal / $funil['consultas_vendidas'] : 0.0;
        $cacCombos   = $funil['combos_vendidos'] > 0 ? $custoMktTotal / $funil['combos_vendidos'] : 0.0;

        return [
            'investimento'      => $investimento,
            'custo_mkt'         => $custoMktTotal,
            'custo_fisico'      => $custoFis,
            'faturamento_total' => $faturamento,
            'lucro'             => $lucro,
            'roi'               => $roi,
            'cpl'               => $cpl,
            'cac_cadeira'       => $cacCadeira,
            'cac_consulta'      => $cacConsulta,
            'cac_combos'        => $cacCombos,
            'ticket_medio'      => $funil['ticket_medio'],
        ];
    }

    /**
     * Substitui buildSegmento() chamado 5x (uma por segmento) por 3 queries totais
     * (leads, funil, custoMkt), cada uma já trazendo os 5 segmentos de uma vez.
     * custoFisico continua por segmento (custoEvento tem só ~80 linhas, consolidar
     * não traria ganho relevante frente à complexidade da lógica de subquery). leads e
     * funil chegam já calculados via calcularLeadsUnificado()/calcularFunilUnificado()
     * (chamadas uma única vez em dados(), compartilhadas com os comparativos) em vez de
     * serem recalculados aqui.
     */
    private function buildSegmentosTodos($year, ?string $months, ?string $campanha, ?string $form, ?string $dataInicio, ?string $dataFim, array $leadsPorSeg, array $funilPorSeg): array
    {
        $custoMktPorSeg = $this->calcularCustoMktMultiSegmento($year, $months, $campanha, $form, $dataInicio, $dataFim);

        $result = [];
        foreach (self::SEGMENTOS as $key => $classe) {
            $custoFis = $this->calcularCustoFisicoEvento($year, $months, $classe, $campanha, $form, $dataInicio, $dataFim);
            $kpis = $this->montarKpisFinanceiros($custoMktPorSeg[$key], $custoFis, $funilPorSeg[$key], $leadsPorSeg[$key]);

            $result[$key] = [
                'total_leads'          => $leadsPorSeg[$key]['total_leads'],
                'leads_qualificados'   => $leadsPorSeg[$key]['leads_qualificados'],
                'cadeira_vendida'      => $funilPorSeg[$key]['cadeira_vendida'],
                'consultas_vendidas'   => $funilPorSeg[$key]['consultas_vendidas'],
                'consultas_realizadas' => $funilPorSeg[$key]['consultas_realizadas'],
                'combos_vendidos'      => $funilPorSeg[$key]['combos_vendidos'],
                'investimento'         => $kpis['investimento'],
                'custo_mkt'            => $kpis['custo_mkt'],
                'custo_fisico'         => $kpis['custo_fisico'],
                'faturamento_total'    => $kpis['faturamento_total'],
                'lucro'                => $kpis['lucro'],
                'roi'                  => $kpis['roi'],
                'ticket_medio'         => $kpis['ticket_medio'],
                'cpl'                  => $kpis['cpl'],
                'cac_cadeira'          => $kpis['cac_cadeira'],
                'cac_consulta'         => $kpis['cac_consulta'],
                'cac_combos'           => $kpis['cac_combos'],
            ];
        }
        return $result;
    }

    // ===================================================================
    // CÁLCULOS: EXECUTIVE — engine de comparação (YoY / vs. último mês)
    // ===================================================================

    private function calcularPeriodosComparativos(?string $today, $year, ?string $months, ?string $dataInicio, ?string $dataFim): array
    {
        $MESES_PT = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        $today      = $today ?: date('Y-m-d');
        $todayDay   = (int)substr($today, 8, 2);
        $todayMonth = (int)substr($today, 5, 2);

        $refYear = $year ? (int)$year : (int)date('Y');
        $pyYear  = $refYear - 1;

        $hasMonthFilter = ($months !== null && $months !== '') || $dataInicio || $dataFim;

        if ($hasMonthFilter) {
            if ($dataInicio || $dataFim) {
                // ── Modo range de datas ──────────────────────────────────
                $maStart = $dataInicio ?: sprintf('%04d-01-01', $refYear);
                $maEnd   = $dataFim ?: $today;

                $startTs   = strtotime($maStart);
                $endTs     = strtotime($maEnd);
                $rangeDays = max(0, (int)(($endTs - $startTs) / 86400));

                $suf       = '/' . substr($maEnd, 2, 2);
                $maPeriodo = date('d/m', $startTs) . '→' . date('d/m', $endTs) . $suf;

                $pyStart   = date('Y-m-d', strtotime($maStart . ' -1 year'));
                $pyEnd     = date('Y-m-d', strtotime($maEnd . ' -1 year'));
                $pySuf     = '/' . substr($pyStart, 2, 2);
                $pyPeriodo = date('d/m', strtotime($maStart)) . '→' . date('d/m', strtotime($maEnd)) . $pySuf;

                $lmEnd     = date('Y-m-d', strtotime($maStart . ' -1 day'));
                $lmStart   = date('Y-m-d', strtotime($lmEnd . " -{$rangeDays} days"));
                $lmSuf     = '/' . substr($lmEnd, 2, 2);
                $lmPeriodo = date('d/m', strtotime($lmStart)) . '→' . date('d/m', strtotime($lmEnd)) . $lmSuf;
            } else {
                // ── Modo seleção de meses ────────────────────────────────
                $monthList = array_map('intval', explode(',', $months));
                sort($monthList);
                $minMonth  = min($monthList);
                $maxMonth  = max($monthList);
                $numMonths = $maxMonth - $minMonth + 1;
                $curYear   = (int)date('Y');

                $maStart = sprintf('%04d-%02d-01', $refYear, $minMonth);
                $isCurrentPeriod = ($refYear === $curYear && $maxMonth === $todayMonth);
                if ($isCurrentPeriod) {
                    $maEnd    = $today;
                    $maEndDay = $todayDay;
                } else {
                    $maEndDay = (int)date('t', mktime(0, 0, 0, $maxMonth, 1, $refYear));
                    $maEnd    = sprintf('%04d-%02d-%02d', $refYear, $maxMonth, $maEndDay);
                }
                $maPeriodo = $this->rangeMesLabel($MESES_PT, $minMonth, $maxMonth, $refYear);

                $pyStart  = sprintf('%04d-%02d-01', $pyYear, $minMonth);
                $pyEndDay = $this->clampDay($maEndDay, $pyYear, $maxMonth);
                $pyEnd    = sprintf('%04d-%02d-%02d', $pyYear, $maxMonth, $pyEndDay);
                $pyPeriodo = $this->rangeMesLabel($MESES_PT, $minMonth, $maxMonth, $pyYear);

                $lmMaxMonth = $minMonth - 1;
                $lmYearMax  = $refYear;
                if ($lmMaxMonth < 1) { $lmMaxMonth = 12; $lmYearMax--; }

                $lmMinMonth = $lmMaxMonth - $numMonths + 1;
                $lmYearMin  = $lmYearMax;
                if ($lmMinMonth < 1) { $lmMinMonth += 12; $lmYearMin--; }

                $lmStart  = sprintf('%04d-%02d-01', $lmYearMin, $lmMinMonth);
                $lmEndDay = $this->clampDay($maEndDay, $lmYearMax, $lmMaxMonth);
                $lmEnd    = sprintf('%04d-%02d-%02d', $lmYearMax, $lmMaxMonth, $lmEndDay);
                $lmPeriodo = $this->rangeMesLabel($MESES_PT, $lmMinMonth, $lmMaxMonth, $lmYearMax);
            }
        } else {
            // ── Sem filtro de mês — ano completo até hoje ────────────────
            $maStart   = sprintf('%04d-01-01', $refYear);
            $maEnd     = $today;
            $maPeriodo = 'Jan→' . $MESES_PT[$todayMonth] . '/' . substr((string)$refYear, 2);

            $pyStart   = sprintf('%04d-01-01', $pyYear);
            $pyEnd     = sprintf('%04d-%02d-%02d', $pyYear, $todayMonth, $this->clampDay($todayDay, $pyYear, $todayMonth));
            $pyPeriodo = 'Jan→' . $MESES_PT[$todayMonth] . '/' . substr((string)$pyYear, 2);

            $lmStart = $lmEnd = $lmPeriodo = null;
        }

        return [
            'ma'               => ['start' => $maStart, 'end' => $maEnd, 'periodo' => $maPeriodo],
            'py'               => ['start' => $pyStart, 'end' => $pyEnd, 'periodo' => $pyPeriodo],
            'lm'               => $lmStart ? ['start' => $lmStart, 'end' => $lmEnd, 'periodo' => $lmPeriodo] : null,
            'has_month_filter' => $hasMonthFilter,
            'effective_start'  => $maStart,
            'effective_end'    => $maEnd,
        ];
    }

    private function clampDay(int $day, int $year, int $month): int
    {
        return min($day, (int)date('t', mktime(0, 0, 0, $month, 1, $year)));
    }

    private function rangeMesLabel(array $mesesPt, int $minM, int $maxM, int $yr): string
    {
        $suf = '/' . substr((string)$yr, 2);
        return $minM === $maxM ? $mesesPt[$minM] . $suf : $mesesPt[$minM] . '→' . $mesesPt[$maxM] . $suf;
    }

    /** Mesma consolidação de calcularLeadsUnificado()/calcularFunilUnificado(), para custoMkt. */
    private function calcularCustoMktMultiSegmentoMultiPeriodo(array $periodosAtivos): array
    {
        // Nos comparativos $campanha é sempre null -> Orgânico/Indicação nunca têm custo
        // de marketing rastreado (mesma regra de calcularCustoMktMultiSegmento()).
        $classesQuery = array_filter(self::SEGMENTOS, fn($k) => !in_array($k, ['organico', 'indicacao'], true), ARRAY_FILTER_USE_KEY);

        $selects = [];
        $params  = [];
        foreach ($classesQuery as $segKey => $classe) {
            $cond = $this->condClasseCampanha('cm.nomeCampanha_custoMkt', $classe);
            foreach ($periodosAtivos as $pKey => [$start, $end]) {
                $dp = $this->condPeriodo('cm.data_custoMkt', $start, $end);
                $selects[] = "SUM(CASE
                        WHEN {$dp['sql']} AND YEAR(cm.data_custoMkt) <= 2025 AND cm.origem_custoMkt = 'meta' AND $cond THEN cm.valor_custoMkt
                        WHEN {$dp['sql']} AND YEAR(cm.data_custoMkt) >= 2026 AND cm.conta_custoMkt IN ('Dollar 1', 'Dollar 2') AND $cond THEN cm.valor_custoMkt
                        ELSE 0
                    END) AS dollar_{$segKey}_{$pKey}";
                $params = array_merge($params, $dp['params'], $dp['params']);

                $selects[] = "SUM(CASE
                        WHEN {$dp['sql']} AND YEAR(cm.data_custoMkt) <= 2025 AND cm.origem_custoMkt = 'google' AND $cond THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio, 0)
                        WHEN {$dp['sql']} AND YEAR(cm.data_custoMkt) >= 2026 AND cm.conta_custoMkt <> 'Dollar 1' AND cm.conta_custoMkt <> 'Dollar 2' AND $cond THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio, 0)
                        ELSE 0
                    END) AS real_{$segKey}_{$pKey}";
                $params = array_merge($params, $dp['params'], $dp['params']);
            }
        }

        // Bound de união (Fase 1C) — só via periodosAtivos, esse método não tem
        // filtro "main" separado (todo período aqui já vem de $periodosAtivos).
        $limite = $this->buildUnionPeriodoLimite('cm.data_custoMkt', [], $periodosAtivos);

        $sql = "
            SELECT " . implode(",\n                ", $selects) . "
            FROM custoMkt cm
            LEFT JOIN {$this->cambioDedupSubquery()} ca
                ON ca.data_cambio = cm.data_custoMkt
            WHERE {$limite['sql']}
        ";
        $params = array_merge($params, $limite['params']);
        $row = $this->queryOne($sql, $params);

        $result = [];
        foreach ($periodosAtivos as $pKey => $_) {
            foreach (self::SEGMENTOS as $segKey => $classe) {
                if (!array_key_exists($segKey, $classesQuery)) {
                    $result[$pKey][$segKey] = ['custo_dollar' => 0.0, 'custo_real_convertido' => 0.0, 'custo_real_total' => 0.0];
                    continue;
                }
                $dollar = (float)($row["dollar_{$segKey}_{$pKey}"] ?? 0);
                $real   = (float)($row["real_{$segKey}_{$pKey}"] ?? 0);
                $result[$pKey][$segKey] = ['custo_dollar' => $dollar, 'custo_real_convertido' => $real, 'custo_real_total' => $dollar + $real];
            }
        }
        return $result;
    }

    /** Lista de identificadores de custoMkt classificados como "Evento" — calculada uma
     *  única vez e reaproveitada via IN(?,...), em vez de repetir a subquery correlacionada
     *  de calcularCustoFisicoEvento() uma vez por período. */
    /** Memoização em instância — eventoIdentificadores() é chamado 2x por
     *  requisição de /executive/dados (calcularCustoFisicoEvento() e
     *  calcularCustoFisicoEventoMultiPeriodo()), sem necessidade de rodar a
     *  query 2x (Fase 1C.2 do plano de performance). */
    private ?array $eventoIdentificadoresMemo = null;

    private function eventoIdentificadores(): array
    {
        if ($this->eventoIdentificadoresMemo !== null) {
            return $this->eventoIdentificadoresMemo;
        }
        $this->eventoIdentificadoresMemo = $this->cacheRemember('executive_evento_identificadores', 1800, function () {
            $sql = "
                SELECT DISTINCT identificador_custoMkt AS id
                FROM custoMkt cm
                WHERE LEFT(UPPER(cm.nomeCampanha_custoMkt), 3) IN ('OTR', 'INA', 'OTW', 'WEB')
                  AND identificador_custoMkt IS NOT NULL AND identificador_custoMkt != ''
            ";
            return array_column($this->queryAll($sql), 'id');
        });
        return $this->eventoIdentificadoresMemo;
    }

    /**
     * Mesma consolidação para custo físico de evento. Preserva a regra original: só os
     * segmentos "global" e "evento" de fato consultam o banco — Digital/Orgânico/Indicação
     * ficam zerados em PHP sem I/O (mesma condição de calcularCustoFisicoEvento()).
     */
    private function calcularCustoFisicoEventoMultiPeriodo(array $periodosAtivos): array
    {
        $idsEvento = $this->eventoIdentificadores();

        $selects = [];
        $params  = [];
        foreach ($periodosAtivos as $pKey => [$start, $end]) {
            $dp = $this->condPeriodo('ce.data_custoEvento', $start, $end);
            $selects[] = "SUM(CASE WHEN {$dp['sql']} THEN ce.valor_custoEvento / NULLIF(ca.valor_cambio, 0) ELSE 0 END) AS global_{$pKey}";
            $params = array_merge($params, $dp['params']);

            if ($idsEvento) {
                $placeholders = implode(',', array_fill(0, count($idsEvento), '?'));
                $selects[] = "SUM(CASE WHEN {$dp['sql']} AND ce.identificador_custoEvento IN ($placeholders) THEN ce.valor_custoEvento / NULLIF(ca.valor_cambio, 0) ELSE 0 END) AS evento_{$pKey}";
                $params = array_merge($params, $dp['params'], $idsEvento);
            } else {
                $selects[] = "0 AS evento_{$pKey}";
            }
        }

        // Bound de união (Fase 1C) — só via periodosAtivos, mesmo raciocínio de
        // calcularCustoMktMultiSegmentoMultiPeriodo().
        $limite = $this->buildUnionPeriodoLimite('ce.data_custoEvento', [], $periodosAtivos);

        $sql = "
            SELECT " . implode(",\n                ", $selects) . "
            FROM custoEvento ce
            {$this->cambioMaisRecenteJoin('ce.data_custoEvento')}
            WHERE {$limite['sql']}
        ";
        $params = array_merge($params, $limite['params']);
        $row = $this->queryOne($sql, $params);

        $result = [];
        foreach ($periodosAtivos as $pKey => $_) {
            foreach (self::SEGMENTOS as $segKey => $classe) {
                if ($classe === null) {
                    $result[$pKey][$segKey] = (float)($row["global_{$pKey}"] ?? 0);
                } elseif ($classe === 'Evento') {
                    $result[$pKey][$segKey] = (float)($row["evento_{$pKey}"] ?? 0);
                } else {
                    $result[$pKey][$segKey] = 0.0;
                }
            }
        }
        return $result;
    }

    /**
     * Substitui até 3 chamadas a buildSegmentosTodos() (uma por período: mês atual, ano
     * passado, último mês — cada uma re-escaneando leadV2/dealV2/custoMkt/custoEvento do
     * zero) por 1 única query consolidada por fonte de dados, computando os N períodos
     * ativos na mesma passada pela tabela (mesmo princípio das versões *MultiSegmento*,
     * aplicado a uma segunda dimensão: período em vez de segmento).
     */
    private function buildComparacoesTodos(array $periodos, array $periodosAtivos, array $leadsPorPeriodo, array $funilPorPeriodo): array
    {
        $custoMktPorPeriodo = $this->calcularCustoMktMultiSegmentoMultiPeriodo($periodosAtivos);
        $custoFisPorPeriodo = $this->calcularCustoFisicoEventoMultiPeriodo($periodosAtivos);

        $labelPeriodo  = [
            'ma' => $periodos['ma']['periodo'],
            'py' => $periodos['py']['periodo'],
            'lm' => $periodos['lm']['periodo'] ?? null,
        ];
        $chaveResposta = ['ma' => 'mes_atual', 'py' => 'ano_passado', 'lm' => 'ultimo_mes'];

        $comparisons = [];
        foreach (self::SEGMENTOS as $segKey => $classe) {
            foreach (['ma', 'py', 'lm'] as $pKey) {
                if (!isset($periodosAtivos[$pKey])) {
                    $comparisons[$segKey][$chaveResposta[$pKey]] = null;
                    continue;
                }
                $kpis = $this->montarKpisFinanceiros(
                    $custoMktPorPeriodo[$pKey][$segKey],
                    $custoFisPorPeriodo[$pKey][$segKey],
                    $funilPorPeriodo[$pKey][$segKey],
                    $leadsPorPeriodo[$pKey][$segKey]
                );
                $comparisons[$segKey][$chaveResposta[$pKey]] = [
                    'total_leads'          => $leadsPorPeriodo[$pKey][$segKey]['total_leads'],
                    'leads_qualificados'   => $leadsPorPeriodo[$pKey][$segKey]['leads_qualificados'],
                    'cadeira_vendida'      => $funilPorPeriodo[$pKey][$segKey]['cadeira_vendida'],
                    'consultas_vendidas'   => $funilPorPeriodo[$pKey][$segKey]['consultas_vendidas'],
                    'consultas_realizadas' => $funilPorPeriodo[$pKey][$segKey]['consultas_realizadas'],
                    'combos_vendidos'      => $funilPorPeriodo[$pKey][$segKey]['combos_vendidos'],
                    'investimento'         => $kpis['investimento'],
                    'custo_mkt'            => $kpis['custo_mkt'],
                    'custo_fisico'         => $kpis['custo_fisico'],
                    'faturamento_total'    => $kpis['faturamento_total'],
                    'lucro'                => $kpis['lucro'],
                    'roi'                  => $kpis['roi'],
                    'ticket_medio'         => $kpis['ticket_medio'],
                    'cpl'                  => $kpis['cpl'],
                    'cac_cadeira'          => $kpis['cac_cadeira'],
                    'cac_consulta'         => $kpis['cac_consulta'],
                    'cac_combos'           => $kpis['cac_combos'],
                    'periodo'              => $labelPeriodo[$pKey],
                ];
            }
        }
        return $comparisons;
    }

    // ===================================================================
    // CÁLCULOS: DIGITAL × EVENTO (classificação por prefixo de form)
    // ===================================================================

    /** Versão consolidada (3 segmentos: global/Evento/Digital numa só query) de calcularLeadsFormClass(). */
    private function calcularLeadsFormClassMultiSegmento($year, ?string $months, ?string $campanha, ?string $form, ?string $responsavel, ?string $dataInicio, ?string $dataFim): array
    {
        $date       = $this->buildPeriodFilter('l.dataUltForm_leadV2', $year, $months, $dataInicio, $dataFim);
        $campFiltro = $this->buildInFilter('l.utmCampaign_leadV2', $campanha);
        $formFlt    = $this->buildInFilter('l.formPreenchido_leadV2', $form);
        $respFiltro = $this->buildInFilter('l.responsavel_leadV2', $responsavel);

        $selects = [];
        foreach (self::SEGMENTOS_FORM as $key => $classe) {
            $cond = $this->condFormClasse('l.formPreenchido_leadV2', $classe);
            $selects[] = "SUM(CASE WHEN $cond THEN 1 ELSE 0 END) AS total_{$key}";
            $selects[] = "SUM(CASE WHEN $cond AND l.qualificado_leadV2 = 'Sim' THEN 1 ELSE 0 END) AS qual_{$key}";
        }

        $sql = "
            SELECT " . implode(",\n                ", $selects) . "
            FROM leadV2 l
            WHERE {$date['sql']}
            {$campFiltro['sql']} {$formFlt['sql']} {$respFiltro['sql']}
        ";
        $params = array_merge($date['params'], $campFiltro['params'], $formFlt['params'], $respFiltro['params']);
        $row = $this->queryOne($sql, $params);

        $result = [];
        foreach (self::SEGMENTOS_FORM as $key => $classe) {
            $result[$key] = [
                'total_leads'        => (int)($row["total_{$key}"] ?? 0),
                'leads_qualificados' => (int)($row["qual_{$key}"] ?? 0),
            ];
        }
        return $result;
    }

    /** Versão consolidada de calcularFunilFormClass(). */
    private function calcularFunilFormClassMultiSegmento($year, ?string $months, ?string $campanha, ?string $form, ?string $responsavel, ?string $dataInicio, ?string $dataFim): array
    {
        $campFiltro = $this->buildInFilter('d.utmCampaign_dealV2', $campanha);
        $formFlt    = $this->buildInFilter('d.formPreenchido_dealV2', $form);
        $respFiltro = $this->buildInFilter('d.responsavel_dealV2', $responsavel);

        $selects = [];
        $params  = [];
        foreach (self::SEGMENTOS_FORM as $key => $classe) {
            $cond = $this->condFormClasse('d.formPreenchido_dealV2', $classe);

            $df1 = $this->buildPeriodFilter('d.dataVenda_dealV2', $year, $months, $dataInicio, $dataFim);
            $selects[] = "SUM(CASE WHEN {$df1['sql']}
                     AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
                     AND d.faseId_dealV2 = 'C9:Won' AND $cond
                    THEN 1 ELSE 0 END) AS cadeira_{$key}";
            $params = array_merge($params, $df1['params']);

            $df2 = $this->buildPeriodFilter('d.dataVenda_dealV2', $year, $months, $dataInicio, $dataFim);
            $selects[] = "SUM(CASE WHEN {$df2['sql']}
                     AND d.dataVenda_dealV2 IS NOT NULL AND d.dataVenda_dealV2 > '0000-00-00'
                     AND d.faseId_dealV2 IN ('Won', 'C11:WON') AND $cond
                    THEN 1 ELSE 0 END) AS consulta_{$key}";
            $params = array_merge($params, $df2['params']);

            $df3 = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $year, $months, $dataInicio, $dataFim);
            $selects[] = "SUM(CASE WHEN {$df3['sql']}
                     AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
                     AND LEFT(d.faseId_dealV2, 2) = 'C5' AND $cond
                    THEN 1 ELSE 0 END) AS realizada_{$key}";
            $params = array_merge($params, $df3['params']);

            $df5 = $this->buildPeriodFilter('d.dataFpRealizado_dealV2', $year, $months, $dataInicio, $dataFim);
            $selects[] = "SUM(CASE WHEN {$df5['sql']}
                     AND d.dataFpRealizado_dealV2 IS NOT NULL AND d.dataFpRealizado_dealV2 > '0000-00-00'
                     AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
                     AND d.dataFpAgendado_dealV2 IS NOT NULL AND d.dataFpAgendado_dealV2 > '0000-00-00'
                     AND LEFT(d.faseId_dealV2, 2) = 'C5'
                     AND d.fase_dealV2 NOT IN ('Comercial 3', 'Distrato', 'Lead sem qualificação') AND $cond
                    THEN 1 ELSE 0 END) AS fprealizado_{$key}";
            $params = array_merge($params, $df5['params']);

            $df4 = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $year, $months, $dataInicio, $dataFim);
            $selects[] = "SUM(CASE WHEN {$df4['sql']}
                     AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
                     AND d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON') AND $cond
                    THEN 1 ELSE 0 END) AS combo_{$key}";
            $params = array_merge($params, $df4['params']);

            $df4b = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $year, $months, $dataInicio, $dataFim);
            $selects[] = "SUM(CASE WHEN {$df4b['sql']}
                     AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
                     AND d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON') AND $cond
                    THEN COALESCE(d.valorComboLiquido_dealV2, 0) ELSE 0 END) AS fatur_{$key}";
            $params = array_merge($params, $df4b['params']);
        }

        // Bound de união (Fase 1C), sem comparativos (esse método não tem
        // periodosAtivos) — só o filtro principal, testado nas 4 colunas de
        // data usadas pelas métricas (mesmo raciocínio de calcularFunilUnificado).
        $limite = $this->buildUnionPeriodoLimiteMultiCol(
            ['d.dataVenda_dealV2', 'd.dataConsultaRealizada_dealV2', 'd.dataFpRealizado_dealV2', 'd.dataContratoComboAssinado_dealV2'],
            $df1['params'],
            []
        );
        $sql = "
            SELECT " . implode(",\n                ", $selects) . "
            FROM dealV2 d
            WHERE {$limite['sql']}
            {$campFiltro['sql']} {$formFlt['sql']} {$respFiltro['sql']}
        ";
        $params = array_merge($params, $limite['params'], $campFiltro['params'], $formFlt['params'], $respFiltro['params']);
        $row = $this->queryOne($sql, $params);

        $result = [];
        foreach (self::SEGMENTOS_FORM as $key => $classe) {
            $combosVendidos   = (int)($row["combo_{$key}"] ?? 0);
            $faturamentoTotal = (float)($row["fatur_{$key}"] ?? 0);
            $result[$key] = [
                'cadeira_vendida'      => (int)($row["cadeira_{$key}"] ?? 0),
                'consultas_vendidas'   => (int)($row["consulta_{$key}"] ?? 0),
                'consultas_realizadas' => (int)($row["realizada_{$key}"] ?? 0),
                'fp_realizado'         => (int)($row["fprealizado_{$key}"] ?? 0),
                'combos_vendidos'      => $combosVendidos,
                'faturamento_total'    => $faturamentoTotal,
                'ticket_medio'         => $combosVendidos > 0 ? $faturamentoTotal / $combosVendidos : 0.0,
            ];
        }
        return $result;
    }

    /** Versão consolidada de calcularCustoMktFormClass(). */
    private function calcularCustoMktFormClassMultiSegmento($year, ?string $months, ?string $dataInicio, ?string $dataFim): array
    {
        $date = $this->buildPeriodFilter('cm.data_custoMkt', $year, $months, $dataInicio, $dataFim);

        $selects = [];
        foreach (self::SEGMENTOS_FORM as $key => $classe) {
            $cond = $this->condFormClasseCustoMkt($classe);
            $selects[] = "SUM(CASE
                    WHEN YEAR(cm.data_custoMkt) <= 2025 AND cm.origem_custoMkt = 'meta' AND $cond THEN cm.valor_custoMkt
                    WHEN YEAR(cm.data_custoMkt) >= 2026 AND cm.conta_custoMkt IN ('Dollar 1', 'Dollar 2') AND $cond THEN cm.valor_custoMkt
                    ELSE 0
                END) AS dollar_{$key}";
            $selects[] = "SUM(CASE
                    WHEN YEAR(cm.data_custoMkt) <= 2025 AND cm.origem_custoMkt = 'google' AND $cond THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio, 0)
                    WHEN YEAR(cm.data_custoMkt) >= 2026 AND cm.conta_custoMkt <> 'Dollar 1' AND cm.conta_custoMkt <> 'Dollar 2' AND $cond THEN cm.valor_custoMkt / NULLIF(ca.valor_cambio, 0)
                    ELSE 0
                END) AS real_{$key}";
        }

        $sql = "
            SELECT " . implode(",\n                ", $selects) . "
            FROM custoMkt cm
            LEFT JOIN {$this->cambioDedupSubquery()} ca ON ca.data_cambio = cm.data_custoMkt
            WHERE {$date['sql']}
        ";
        $row = $this->queryOne($sql, $date['params']);

        $result = [];
        foreach (self::SEGMENTOS_FORM as $key => $classe) {
            $dollar = (float)($row["dollar_{$key}"] ?? 0);
            $real   = (float)($row["real_{$key}"] ?? 0);
            $result[$key] = ['custo_dollar' => $dollar, 'custo_real_convertido' => $real, 'custo_real_total' => $dollar + $real];
        }
        return $result;
    }

    /**
     * Custo físico de evento por classe de form.
     * CORREÇÃO: a origem somava ce.valor_custoEvento sem converter BRL->USD nesta função
     * especificamente (diferente da aba Executive, que já convertia). Aqui aplicamos a
     * mesma divisão por câmbio médio diário para manter os dois cálculos consistentes.
     */
    private function calcularCustoFisicoFormClass($year, ?string $months, ?string $formClass, ?string $dataInicio, ?string $dataFim): float
    {
        $date = $this->buildPeriodFilter('ce.data_custoEvento', $year, $months, $dataInicio, $dataFim);

        if ($formClass === 'Digital') {
            return 0.0;
        }

        if ($formClass === 'Evento') {
            $sql = "
                SELECT COALESCE(SUM(ce.valor_custoEvento / NULLIF(ca.valor_cambio, 0)), 0) AS custo_fisico
                FROM custoEvento ce
                {$this->cambioMaisRecenteJoin('ce.data_custoEvento')}
                WHERE {$date['sql']}
                  AND ce.identificador_custoEvento IN (
                      SELECT DISTINCT cm.identificador_custoMkt
                      FROM custoMkt cm
                      WHERE LEFT(UPPER(COALESCE(cm.identificador_custoMkt, '')), 3) IN ('OTR', 'INA', 'OTW', 'WEB')
                         OR (
                             (cm.identificador_custoMkt IS NULL OR cm.identificador_custoMkt = '')
                             AND LEFT(UPPER(cm.nomeCampanha_custoMkt), 3) = 'OTR'
                         )
                  )
            ";
        } else {
            $sql = "
                SELECT COALESCE(SUM(ce.valor_custoEvento / NULLIF(ca.valor_cambio, 0)), 0) AS custo_fisico
                FROM custoEvento ce
                {$this->cambioMaisRecenteJoin('ce.data_custoEvento')}
                WHERE {$date['sql']}
            ";
        }

        $row = $this->queryOne($sql, $date['params']);
        return (float)($row['custo_fisico'] ?? 0);
    }

    /**
     * Substitui buildSegmentoForm() chamado 3x (uma por segmento) por 3 queries
     * totais (leads, funil, custoMkt), cada uma já trazendo os 3 segmentos de uma vez.
     */
    private function buildSegmentosFormTodos($year, ?string $months, ?string $campanha, ?string $form, ?string $responsavel, ?string $dataInicio, ?string $dataFim): array
    {
        $leadsPorSeg    = $this->calcularLeadsFormClassMultiSegmento($year, $months, $campanha, $form, $responsavel, $dataInicio, $dataFim);
        $funilPorSeg    = $this->calcularFunilFormClassMultiSegmento($year, $months, $campanha, $form, $responsavel, $dataInicio, $dataFim);
        $custoMktPorSeg = $this->calcularCustoMktFormClassMultiSegmento($year, $months, $dataInicio, $dataFim);

        $result = [];
        foreach (self::SEGMENTOS_FORM as $key => $classe) {
            $custoFis = $this->calcularCustoFisicoFormClass($year, $months, $classe, $dataInicio, $dataFim);
            $kpis = $this->montarKpisFinanceiros($custoMktPorSeg[$key], $custoFis, $funilPorSeg[$key], $leadsPorSeg[$key]);

            $result[$key] = [
                'total_leads'          => $leadsPorSeg[$key]['total_leads'],
                'leads_qualificados'   => $leadsPorSeg[$key]['leads_qualificados'],
                'cadeira_vendida'      => $funilPorSeg[$key]['cadeira_vendida'],
                'consultas_vendidas'   => $funilPorSeg[$key]['consultas_vendidas'],
                'consultas_realizadas' => $funilPorSeg[$key]['consultas_realizadas'],
                'fp_realizado'         => $funilPorSeg[$key]['fp_realizado'],
                'combos_vendidos'      => $funilPorSeg[$key]['combos_vendidos'],
                'investimento'         => $kpis['investimento'],
                'custo_mkt'            => $kpis['custo_mkt'],
                'custo_fisico'         => $kpis['custo_fisico'],
                'faturamento_total'    => $kpis['faturamento_total'],
                'lucro'                => $kpis['lucro'],
                'roi'                  => $kpis['roi'],
                'ticket_medio'         => $kpis['ticket_medio'],
                'cpl'                  => $kpis['cpl'],
                'cac_cadeira'          => $kpis['cac_cadeira'],
                'cac_consulta'         => $kpis['cac_consulta'],
                'cac_combos'           => $kpis['cac_combos'],
            ];
        }
        return $result;
    }

    // ===================================================================
    // CÁLCULOS: ESPECIALISTAS
    // ===================================================================

    private function calcularKpisEspecialistas(?string $year, ?string $months, ?string $campanha_class, ?string $campanha, ?string $form, ?string $responsavel, ?string $dataInicio, ?string $dataFim): array
    {
        $campFilter = $campanha !== null
            ? $this->buildInFilter('d.utmCampaign_dealV2', $campanha)
            : ['sql' => $this->sqlFiltroCampanha('d.utmCampaign_dealV2', $campanha_class), 'params' => []];
        $formFilter = $this->buildInFilter('d.formPreenchido_dealV2', $form);
        $respFilter = $this->buildInFilter('d.responsavel_dealV2', $responsavel);

        $exprAgendada = $this->exprDataAgendadaCorrigida();

        $dAg  = $this->buildPeriodFilter($exprAgendada, $year, $months, $dataInicio, $dataFim);
        $dRe1 = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $year, $months, $dataInicio, $dataFim);
        $dRe2 = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $year, $months, $dataInicio, $dataFim);
        $dRe3 = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $year, $months, $dataInicio, $dataFim);
        $dRe4 = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $year, $months, $dataInicio, $dataFim);
        $dCo  = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $year, $months, $dataInicio, $dataFim);
        $dCh  = $this->buildPeriodFilter('d.dataVenda_dealV2', $year, $months, $dataInicio, $dataFim);

        // Bound de união (Fase 1C): $dRe1['params'] serve de fonte porque é uma
        // coluna simples (mesmas datas que $dCo/$dCh dariam) — $dAg não serve,
        // porque $exprAgendada é uma expressão e cai no ramo legado de
        // buildDateFilter() (YEAR()/MONTH(), sem bound literal de data nos params).
        // A própria expressão entra no OR mesmo assim, por segurança (sem ganho de
        // índice, mas garante que nenhuma linha que só bateria via ela seja excluída).
        $limite = $this->buildUnionPeriodoLimiteMultiCol(
            [$exprAgendada, 'd.dataConsultaRealizada_dealV2', 'd.dataContratoComboAssinado_dealV2', 'd.dataVenda_dealV2'],
            $dRe1['params'],
            []
        );

        $sql = "
            SELECT
                SUM(CASE WHEN LEFT(d.faseId_dealV2, 2) = 'C5'
                     AND $exprAgendada IS NOT NULL AND $exprAgendada > '0000-00-00'
                     AND ({$dAg['sql']})
                    THEN 1 ELSE 0 END) AS agendadas,

                SUM(CASE WHEN {$dRe1['sql']}
                     AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
                     AND LEFT(d.faseId_dealV2, 2) = 'C5'
                    THEN 1 ELSE 0 END) AS realizadas,

                SUM(CASE WHEN {$dRe2['sql']}
                     AND LEFT(d.faseId_dealV2, 2) = 'C5'
                     AND d.dataFpRealizado_dealV2 IS NOT NULL AND d.dataFpRealizado_dealV2 > '0000-00-00'
                     AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
                     AND d.dataFpAgendado_dealV2 IS NOT NULL AND d.dataFpAgendado_dealV2 > '0000-00-00'
                     AND d.fase_dealV2 NOT IN ('Comercial 3', 'Distrato', 'Lead sem qualificação')
                    THEN 1 ELSE 0 END) AS fp_vendido,

                SUM(CASE WHEN {$dRe3['sql']}
                     AND d.faseId_dealV2 = 'C5:UC_0Q30H1'
                     AND d.dataFpRealizado_dealV2 IS NOT NULL AND d.dataFpRealizado_dealV2 > '0000-00-00'
                     AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
                     AND d.dataFpAgendado_dealV2 IS NOT NULL AND d.dataFpAgendado_dealV2 > '0000-00-00'
                    THEN 1 ELSE 0 END) AS contrato_enviado,

                SUM(CASE WHEN {$dCo['sql']}
                     AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
                     AND d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON')
                    THEN 1 ELSE 0 END) AS combos_vendidos,

                SUM(CASE WHEN {$dRe4['sql']}
                     AND d.faseId_dealV2 IN ('C5:UC_CL1DBM', 'C5:UC_AQ2QYT')
                     AND d.dataFpRealizado_dealV2 IS NOT NULL AND d.dataFpRealizado_dealV2 > '0000-00-00'
                     AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
                    THEN 1 ELSE 0 END) AS distrato,

                SUM(CASE WHEN {$dCh['sql']}
                     AND d.faseId_dealV2 IN ('C5:UC_CL1DBM', 'C5:UC_AQ2QYT')
                     AND d.dataFpRealizado_dealV2 IS NOT NULL AND d.dataFpRealizado_dealV2 > '0000-00-00'
                     AND (d.dataContratoComboAssinado_dealV2 IS NULL OR d.dataContratoComboAssinado_dealV2 = '0000-00-00')
                    THEN 1 ELSE 0 END) AS churn_fp
            FROM dealV2 d
            WHERE {$limite['sql']}
            {$campFilter['sql']} {$formFilter['sql']} {$respFilter['sql']}
        ";
        $params = array_merge(
            $dAg['params'], $dRe1['params'], $dRe2['params'], $dRe3['params'], $dRe4['params'],
            $dCo['params'], $dCh['params'], $limite['params'], $campFilter['params'], $formFilter['params'], $respFilter['params']
        );
        $row = $this->queryOne($sql, $params);

        $agendadas  = (int)($row['agendadas'] ?? 0);
        $realizadas = (int)($row['realizadas'] ?? 0);
        $combos     = (int)($row['combos_vendidos'] ?? 0);

        return [
            'agendadas'        => $agendadas,
            'realizadas'       => $realizadas,
            'a_realizar'       => max(0, $agendadas - $realizadas),
            'fp_vendido'       => (int)($row['fp_vendido'] ?? 0),
            'contrato_enviado' => (int)($row['contrato_enviado'] ?? 0),
            'combos_vendidos'  => $combos,
            'distrato'         => (int)($row['distrato'] ?? 0),
            'churn_fp'         => (int)($row['churn_fp'] ?? 0),
            'conversao'        => $realizadas > 0 ? round($combos / $realizadas, 4) : 0.0,
        ];
    }

    private function calcularGraficoEspecialistas(?string $year, ?string $months, string $granularidade, ?string $campanha_class, ?string $campanha, ?string $form, ?string $responsavel, ?string $dataInicio, ?string $dataFim): array
    {
        if ($granularidade === 'ano') {
            $exprRe = "YEAR(d.dataConsultaRealizada_dealV2)";
            $exprCo = "YEAR(d.dataContratoComboAssinado_dealV2)";
        } elseif ($granularidade === 'semana') {
            $exprRe = "YEARWEEK(d.dataConsultaRealizada_dealV2, 1)";
            $exprCo = "YEARWEEK(d.dataContratoComboAssinado_dealV2, 1)";
        } elseif ($granularidade === 'dia') {
            $exprRe = "DATE(d.dataConsultaRealizada_dealV2)";
            $exprCo = "DATE(d.dataContratoComboAssinado_dealV2)";
        } else {
            $exprRe = "DATE_FORMAT(d.dataConsultaRealizada_dealV2, '%Y-%m')";
            $exprCo = "DATE_FORMAT(d.dataContratoComboAssinado_dealV2, '%Y-%m')";
        }

        $nomeMes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $labelFn = function ($v) use ($granularidade, $nomeMes) {
            $v = (string)$v;
            if ($granularidade === 'ano')    return $v;
            if ($granularidade === 'semana') return 'S' . substr($v, -2) . '/' . substr($v, 2, 2);
            if ($granularidade === 'dia')    return date('d/m', strtotime($v));
            $m = (int)substr($v, 5, 2);
            if ($m < 1 || $m > 12) return $v;
            return $nomeMes[$m - 1] . '/' . substr($v, 2, 2);
        };

        // Fase 1C (item 11): as 2 passadas por dealV2 (realizada/combo) viram
        // UNION ALL de uma query só, marcadas por "tipo" — mesmo princípio de
        // consolidação já usado em calcularVistos(). O filtro de campanha/form/
        // responsável é o mesmo nos dois ramos (não muda entre realizada/combo),
        // computado uma vez só em vez de recriado a cada ramo.
        $campFilter = $campanha !== null
            ? $this->buildInFilter('d.utmCampaign_dealV2', $campanha)
            : ['sql' => $this->sqlFiltroCampanha('d.utmCampaign_dealV2', $campanha_class), 'params' => []];
        $formFilter = $this->buildInFilter('d.formPreenchido_dealV2', $form);
        $respFilter = $this->buildInFilter('d.responsavel_dealV2', $responsavel);

        $dRe = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $year, $months, $dataInicio, $dataFim);
        $dCo = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $year, $months, $dataInicio, $dataFim);

        $sql = "
            SELECT $exprRe AS periodo, $exprRe AS ord, 'realizada' AS tipo, COUNT(*) AS cnt
            FROM dealV2 d
            WHERE {$dRe['sql']}
              AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
              AND LEFT(d.faseId_dealV2, 2) = 'C5'
            {$campFilter['sql']} {$formFilter['sql']} {$respFilter['sql']}
            GROUP BY periodo

            UNION ALL

            SELECT $exprCo AS periodo, $exprCo AS ord, 'combo' AS tipo, COUNT(*) AS cnt
            FROM dealV2 d
            WHERE {$dCo['sql']}
              AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
              AND d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON')
            {$campFilter['sql']} {$formFilter['sql']} {$respFilter['sql']}
            GROUP BY periodo
        ";
        $params = array_merge(
            $dRe['params'], $campFilter['params'], $formFilter['params'], $respFilter['params'],
            $dCo['params'], $campFilter['params'], $formFilter['params'], $respFilter['params']
        );

        $rowsRe = [];
        $rowsCo = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $bucket = ['cnt' => (int)$r['cnt'], 'ord' => $r['ord']];
            if ($r['tipo'] === 'realizada') {
                $rowsRe[(string)$r['periodo']] = $bucket;
            } else {
                $rowsCo[(string)$r['periodo']] = $bucket;
            }
        }

        $allPeriodos = array_unique(array_merge(array_keys($rowsRe), array_keys($rowsCo)));
        usort($allPeriodos, function ($a, $b) use ($rowsRe, $rowsCo) {
            $oa = $rowsRe[$a]['ord'] ?? ($rowsCo[$a]['ord'] ?? $a);
            $ob = $rowsRe[$b]['ord'] ?? ($rowsCo[$b]['ord'] ?? $b);
            return $oa <=> $ob;
        });

        $result = [];
        foreach ($allPeriodos as $p) {
            $re = $rowsRe[$p]['cnt'] ?? 0;
            $co = $rowsCo[$p]['cnt'] ?? 0;
            $result[] = [
                'label'      => $labelFn($p),
                'realizadas' => $re,
                'combos'     => $co,
                'conversao'  => $re > 0 ? round($co / $re, 4) : 0.0,
            ];
        }
        return $result;
    }

    private function calcularTabelaEspecialistas(?string $year, ?string $months, ?string $campanha_class, ?string $campanha, ?string $form, ?string $dataInicio, ?string $dataFim, ?string $responsavel): array
    {
        $campFilter = $campanha !== null
            ? $this->buildInFilter('d.utmCampaign_dealV2', $campanha)
            : ['sql' => $this->sqlFiltroCampanha('d.utmCampaign_dealV2', $campanha_class), 'params' => []];
        $formFilter = $this->buildInFilter('d.formPreenchido_dealV2', $form);
        $respFilter = $this->buildInFilter('d.responsavel_dealV2', $responsavel);

        $dRe1 = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $year, $months, $dataInicio, $dataFim);
        $dRe2 = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $year, $months, $dataInicio, $dataFim);
        $dCo  = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $year, $months, $dataInicio, $dataFim);
        $dCh  = $this->buildPeriodFilter('d.dataVenda_dealV2', $year, $months, $dataInicio, $dataFim);

        // Bound de união (Fase 1C) nas 3 colunas de data usadas pelas métricas.
        $limite = $this->buildUnionPeriodoLimiteMultiCol(
            ['d.dataConsultaRealizada_dealV2', 'd.dataContratoComboAssinado_dealV2', 'd.dataVenda_dealV2'],
            $dRe1['params'],
            []
        );

        $sql = "
            SELECT
                d.responsavel_dealV2 AS especialista,

                SUM(CASE WHEN {$dRe1['sql']}
                     AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
                     AND LEFT(d.faseId_dealV2, 2) = 'C5'
                    THEN 1 ELSE 0 END) AS realizadas,

                SUM(CASE WHEN {$dCo['sql']}
                     AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
                     AND d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON')
                    THEN 1 ELSE 0 END) AS combos,

                SUM(CASE WHEN {$dRe2['sql']}
                     AND d.faseId_dealV2 IN ('C5:UC_CL1DBM', 'C5:UC_AQ2QYT')
                     AND d.dataFpRealizado_dealV2 IS NOT NULL AND d.dataFpRealizado_dealV2 > '0000-00-00'
                     AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
                    THEN 1 ELSE 0 END) AS distrato,

                SUM(CASE WHEN {$dCh['sql']}
                     AND d.faseId_dealV2 IN ('C5:UC_CL1DBM', 'C5:UC_AQ2QYT')
                     AND d.dataFpRealizado_dealV2 IS NOT NULL AND d.dataFpRealizado_dealV2 > '0000-00-00'
                     AND (d.dataContratoComboAssinado_dealV2 IS NULL OR d.dataContratoComboAssinado_dealV2 = '0000-00-00')
                    THEN 1 ELSE 0 END) AS churn_fp
            FROM dealV2 d
            WHERE d.responsavel_dealV2 IS NOT NULL AND d.responsavel_dealV2 != ''
            AND {$limite['sql']}
            {$campFilter['sql']} {$formFilter['sql']} {$respFilter['sql']}
            GROUP BY d.responsavel_dealV2
            HAVING realizadas > 0
            ORDER BY realizadas DESC
        ";
        $params = array_merge(
            $dRe1['params'], $dCo['params'], $dRe2['params'], $dCh['params'], $limite['params'],
            $campFilter['params'], $formFilter['params'], $respFilter['params']
        );

        $rows = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $re = (int)$r['realizadas'];
            $co = (int)$r['combos'];
            $rows[] = [
                'especialista' => $r['especialista'],
                'realizadas'   => $re,
                'combos'       => $co,
                'conversao'    => $re > 0 ? round($co / $re, 4) : 0.0,
                'distrato'     => (int)$r['distrato'],
                'churn_fp'     => (int)$r['churn_fp'],
            ];
        }
        return $rows;
    }

    // ===================================================================
    // CÁLCULOS: ESPECIALISTAS ANALÍTICO (por especialista × frente)
    // ===================================================================

    private const ESPECIALISTAS_ANALITICO = [
        'Rafael Torres', 'Paulo Nogueira', 'Camila Duarte', 'Juliana Prado', 'Beatriz Lemos', 'Ricardo Albuquerque',
    ];

    private function sqlFrenteExpr(): string
    {
        return "CASE
            WHEN d.formPreenchido_dealV2 IS NOT NULL
             AND d.formPreenchido_dealV2 != ''
             AND LEFT(UPPER(d.formPreenchido_dealV2), 3) IN ('OTR','INA','OTW','WEB')
            THEN 'Evento'
            ELSE 'Digital'
        END";
    }

    private function calcularAnaliticoEspecialistas(?string $year, ?string $months, ?string $campanha, ?string $form, ?string $responsavel, ?string $dataInicio, ?string $dataFim): array
    {
        $esps    = self::ESPECIALISTAS_ANALITICO;
        $nomeMes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $labelFn = function (string $v) use ($nomeMes): string {
            $m = (int)substr($v, 5, 2);
            if ($m < 1 || $m > 12) return $v;
            return $nomeMes[$m - 1] . '/' . substr($v, 2, 2);
        };

        $dRe = $this->buildPeriodFilter('d.dataConsultaRealizada_dealV2', $year, $months, $dataInicio, $dataFim);
        $dCo = $this->buildPeriodFilter('d.dataContratoComboAssinado_dealV2', $year, $months, $dataInicio, $dataFim);

        // Fase 1C (item 11): mesmo princípio de calcularGraficoEspecialistas() —
        // as 2 passadas (realizada/combo) viram UNION ALL de uma query só,
        // marcadas por "tipo"; o filtro de campanha/form/responsável é o mesmo
        // nos dois ramos, computado uma vez em vez de duas.
        $campFilter = $this->buildInFilter('d.utmCampaign_dealV2', $campanha);
        $formFilter = $this->buildInFilter('d.formPreenchido_dealV2', $form);
        $respFilter = $this->buildInFilter('d.responsavel_dealV2', $responsavel);

        $espPlaceholders = implode(',', array_fill(0, count($esps), '?'));
        $frenteExpr      = $this->sqlFrenteExpr();

        $sql = "
            SELECT
                d.responsavel_dealV2 AS esp,
                DATE_FORMAT(d.dataConsultaRealizada_dealV2, '%Y-%m') AS periodo,
                $frenteExpr AS frente,
                'realizada' AS tipo,
                COUNT(*) AS cnt
            FROM dealV2 d
            WHERE {$dRe['sql']}
              AND d.dataConsultaRealizada_dealV2 IS NOT NULL AND d.dataConsultaRealizada_dealV2 > '0000-00-00'
              AND LEFT(d.faseId_dealV2, 2) = 'C5'
              AND d.responsavel_dealV2 IN ($espPlaceholders)
              {$campFilter['sql']} {$formFilter['sql']} {$respFilter['sql']}
            GROUP BY esp, periodo, frente

            UNION ALL

            SELECT
                d.responsavel_dealV2 AS esp,
                DATE_FORMAT(d.dataContratoComboAssinado_dealV2, '%Y-%m') AS periodo,
                $frenteExpr AS frente,
                'combo' AS tipo,
                COUNT(*) AS cnt
            FROM dealV2 d
            WHERE {$dCo['sql']}
              AND d.dataContratoComboAssinado_dealV2 IS NOT NULL AND d.dataContratoComboAssinado_dealV2 > '0000-00-00'
              AND d.faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON')
              AND d.responsavel_dealV2 IN ($espPlaceholders)
              {$campFilter['sql']} {$formFilter['sql']} {$respFilter['sql']}
            GROUP BY esp, periodo, frente
        ";
        $params = array_merge(
            $dRe['params'], $esps, $campFilter['params'], $formFilter['params'], $respFilter['params'],
            $dCo['params'], $esps, $campFilter['params'], $formFilter['params'], $respFilter['params']
        );

        $dataRe = [];
        $dataCo = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            if ($r['tipo'] === 'realizada') {
                $dataRe[$r['esp']][$r['periodo']][$r['frente']] = (int)$r['cnt'];
            } else {
                $dataCo[$r['esp']][$r['periodo']][$r['frente']] = (int)$r['cnt'];
            }
        }

        $result = [];
        foreach ($esps as $esp) {
            $espRe = $dataRe[$esp] ?? [];
            $espCo = $dataCo[$esp] ?? [];
            $allPeriodos = array_unique(array_merge(array_keys($espRe), array_keys($espCo)));
            sort($allPeriodos);

            $frentes = ['Evento' => [], 'Digital' => [], 'Total' => []];
            $totais  = [
                'Evento'  => ['realizadas' => 0, 'combos' => 0],
                'Digital' => ['realizadas' => 0, 'combos' => 0],
                'Total'   => ['realizadas' => 0, 'combos' => 0],
            ];

            foreach ($allPeriodos as $p) {
                $reEv  = $espRe[$p]['Evento'] ?? 0;
                $reDg  = $espRe[$p]['Digital'] ?? 0;
                $coEv  = $espCo[$p]['Evento'] ?? 0;
                $coDg  = $espCo[$p]['Digital'] ?? 0;
                $reTot = $reEv + $reDg;
                $coTot = $coEv + $coDg;
                $lbl   = $labelFn($p);

                if ($reEv > 0 || $coEv > 0) {
                    $frentes['Evento'][] = ['periodo' => $lbl, 'realizadas' => $reEv, 'combos' => $coEv, 'conversao' => $reEv > 0 ? round($coEv / $reEv, 4) : 0.0];
                    $totais['Evento']['realizadas'] += $reEv;
                    $totais['Evento']['combos']     += $coEv;
                }
                if ($reDg > 0 || $coDg > 0) {
                    $frentes['Digital'][] = ['periodo' => $lbl, 'realizadas' => $reDg, 'combos' => $coDg, 'conversao' => $reDg > 0 ? round($coDg / $reDg, 4) : 0.0];
                    $totais['Digital']['realizadas'] += $reDg;
                    $totais['Digital']['combos']     += $coDg;
                }
                if ($reTot > 0 || $coTot > 0) {
                    $frentes['Total'][] = ['periodo' => $lbl, 'realizadas' => $reTot, 'combos' => $coTot, 'conversao' => $reTot > 0 ? round($coTot / $reTot, 4) : 0.0];
                    $totais['Total']['realizadas'] += $reTot;
                    $totais['Total']['combos']     += $coTot;
                }
            }

            foreach (['Evento', 'Digital', 'Total'] as $f) {
                $re = $totais[$f]['realizadas'];
                $co = $totais[$f]['combos'];
                $totais[$f]['conversao'] = $re > 0 ? round($co / $re, 4) : 0.0;
            }

            $result[] = ['nome' => $esp, 'frentes' => $frentes, 'totais' => $totais];
        }

        return $result;
    }

    // ===================================================================
    // CÁLCULOS: SERASA (consulta de crédito — pipes Comercial 2 / Comercial 4)
    // ===================================================================

    /**
     * "Consulta Serasa realizada" para um card: nem todo card dos pipes C5/C3 já
     * foi consultado (o volume de consultas é limitado), então isso não pode ser
     * assumido a partir da fase — usa o preenchimento da faixa de score retornada
     * pela consulta.
     */
    private function condSerasaConsultada(): string
    {
        return "TRIM(COALESCE(d.serasaRange_dealV2, '')) <> ''";
    }

    /**
     * Regra especial de câmbio para valorTotalDividas_dealV2/valorCartorioTotal_dealV2:
     * diferente do custoMkt/custoEvento (câmbio do dia de cada linha), aqui é sempre a
     * cotação mais recente disponível — são dívidas ativas no Brasil, não um custo
     * histórico datado, então um único valor serve para a consulta inteira.
     */
    private function taxaCambioMaisRecente(): float
    {
        $row = $this->queryOne("
            SELECT MAX(valor_cambio) AS taxa FROM cambio
            WHERE data_cambio = (SELECT MAX(data_cambio) FROM cambio)
        ");
        return (float)($row['taxa'] ?? 0);
    }

    /** Zero-date/vazio -> null; senão formata dd/mm/yyyy. */
    private function fmtDataSerasa(?string $v): ?string
    {
        $dataParte = substr((string)$v, 0, 10);
        if ($v === null || $v === '' || $dataParte <= '0000-00-00') {
            return null;
        }
        return $this->data($dataParte);
    }

    /**
     * KPIs por pipe (Comercial 2 = C5, Comercial 4 = C3): total no pipe, consultados,
     * nome sujo, sócio negativado, score médio e dívidas/cartório em USD. Uma única
     * passada pela tabela com SUM(CASE WHEN...)/AVG(CASE WHEN...) por pipe, mesmo
     * espírito de calcularVistos(). Percentuais ficam por conta do front (fPctRatio
     * em js/bi-dashboard.js) a partir das contagens brutas, para não duplicar a
     * mesma conta em PHP e JS.
     */
    private function calcularSerasaResumo($year, ?string $months, ?string $dataInicio, ?string $dataFim, float $cambio): array
    {
        $period     = $this->buildPeriodFilter('d.dataFpRealizado_dealV2', $year, $months, $dataInicio, $dataFim);
        $consultada = $this->condSerasaConsultada();

        $sql = "
            SELECT
                SUM(CASE WHEN LEFT(d.faseId_dealV2,2)='C5' THEN 1 ELSE 0 END) AS total_com2,
                SUM(CASE WHEN LEFT(d.faseId_dealV2,2)='C3' THEN 1 ELSE 0 END) AS total_com4,
                SUM(CASE WHEN LEFT(d.faseId_dealV2,2)='C5' AND $consultada THEN 1 ELSE 0 END) AS consultados_com2,
                SUM(CASE WHEN LEFT(d.faseId_dealV2,2)='C3' AND $consultada THEN 1 ELSE 0 END) AS consultados_com4,
                SUM(CASE WHEN LEFT(d.faseId_dealV2,2)='C5' AND $consultada AND d.nomeSujo_dealV2 = 'Sim' THEN 1 ELSE 0 END) AS nome_sujo_com2,
                SUM(CASE WHEN LEFT(d.faseId_dealV2,2)='C3' AND $consultada AND d.nomeSujo_dealV2 = 'Sim' THEN 1 ELSE 0 END) AS nome_sujo_com4,
                SUM(CASE WHEN LEFT(d.faseId_dealV2,2)='C5' AND $consultada AND d.socioEmpresaNegativa_dealV2 = 'Sim' THEN 1 ELSE 0 END) AS socio_neg_com2,
                SUM(CASE WHEN LEFT(d.faseId_dealV2,2)='C3' AND $consultada AND d.socioEmpresaNegativa_dealV2 = 'Sim' THEN 1 ELSE 0 END) AS socio_neg_com4,
                AVG(CASE WHEN LEFT(d.faseId_dealV2,2)='C5' AND $consultada THEN d.serasaScore_dealV2 END) AS score_com2,
                AVG(CASE WHEN LEFT(d.faseId_dealV2,2)='C3' AND $consultada THEN d.serasaScore_dealV2 END) AS score_com4,
                SUM(CASE WHEN LEFT(d.faseId_dealV2,2)='C5' AND $consultada THEN COALESCE(d.valorTotalDividas_dealV2,0) ELSE 0 END) AS dividas_brl_com2,
                SUM(CASE WHEN LEFT(d.faseId_dealV2,2)='C3' AND $consultada THEN COALESCE(d.valorTotalDividas_dealV2,0) ELSE 0 END) AS dividas_brl_com4,
                SUM(CASE WHEN LEFT(d.faseId_dealV2,2)='C5' AND $consultada THEN COALESCE(d.valorCartorioTotal_dealV2,0) ELSE 0 END) AS cartorio_brl_com2,
                SUM(CASE WHEN LEFT(d.faseId_dealV2,2)='C3' AND $consultada THEN COALESCE(d.valorCartorioTotal_dealV2,0) ELSE 0 END) AS cartorio_brl_com4
            FROM dealV2 d
            WHERE {$period['sql']}
              AND d.dataFpRealizado_dealV2 IS NOT NULL AND d.dataFpRealizado_dealV2 > '0000-00-00'
              AND LEFT(d.faseId_dealV2, 2) IN ('C5', 'C3')
        ";
        $row = $this->queryOne($sql, $period['params']);

        $montar = function (string $suf) use ($row, $cambio): array {
            return [
                'total_pipeline'           => (int)($row["total_{$suf}"] ?? 0),
                'consultas_realizadas'     => (int)($row["consultados_{$suf}"] ?? 0),
                'nome_sujo_qtd'            => (int)($row["nome_sujo_{$suf}"] ?? 0),
                'socio_negativado_qtd'     => (int)($row["socio_neg_{$suf}"] ?? 0),
                'score_medio'              => round((float)($row["score_{$suf}"] ?? 0), 0),
                'valor_total_dividas_usd'  => $cambio > 0 ? round((float)($row["dividas_brl_{$suf}"] ?? 0) / $cambio, 2) : 0.0,
                'valor_cartorio_total_usd' => $cambio > 0 ? round((float)($row["cartorio_brl_{$suf}"] ?? 0) / $cambio, 2) : 0.0,
            ];
        };

        $comercial2 = $montar('com2');
        $comercial4 = $montar('com4');

        return [
            'comercial2' => $comercial2,
            'comercial4' => $comercial4,
            'combinado'  => [
                'total_pipeline'           => $comercial2['total_pipeline'] + $comercial4['total_pipeline'],
                'consultas_realizadas'     => $comercial2['consultas_realizadas'] + $comercial4['consultas_realizadas'],
                'nome_sujo_qtd'            => $comercial2['nome_sujo_qtd'] + $comercial4['nome_sujo_qtd'],
                'socio_negativado_qtd'     => $comercial2['socio_negativado_qtd'] + $comercial4['socio_negativado_qtd'],
                'valor_total_dividas_usd'  => round($comercial2['valor_total_dividas_usd'] + $comercial4['valor_total_dividas_usd'], 2),
                'valor_cartorio_total_usd' => round($comercial2['valor_cartorio_total_usd'] + $comercial4['valor_cartorio_total_usd'], 2),
            ],
        ];
    }

    /** Distribuição de clientes consultados por faixa Serasa (A=melhor .. G=pior), por pipe. */
    private function calcularSerasaDistribuicaoRange($year, ?string $months, ?string $dataInicio, ?string $dataFim): array
    {
        $period     = $this->buildPeriodFilter('d.dataFpRealizado_dealV2', $year, $months, $dataInicio, $dataFim);
        $consultada = $this->condSerasaConsultada();

        $sql = "
            SELECT
                CASE WHEN LEFT(d.faseId_dealV2,2)='C5' THEN 'comercial2' ELSE 'comercial4' END AS pipe,
                TRIM(d.serasaRange_dealV2) AS faixa,
                COUNT(*) AS qtd
            FROM dealV2 d
            WHERE {$period['sql']}
              AND d.dataFpRealizado_dealV2 IS NOT NULL AND d.dataFpRealizado_dealV2 > '0000-00-00'
              AND LEFT(d.faseId_dealV2, 2) IN ('C5', 'C3')
              AND $consultada
            GROUP BY pipe, faixa
            ORDER BY pipe, faixa ASC
        ";
        $result = ['comercial2' => [], 'comercial4' => []];
        foreach ($this->queryAll($sql, $period['params']) as $r) {
            $result[$r['pipe']][] = ['faixa' => $r['faixa'], 'qtd' => (int)$r['qtd']];
        }
        return $result;
    }

    /** Distribuição de clientes consultados por Status CPF, por pipe. */
    private function calcularSerasaDistribuicaoStatusCpf($year, ?string $months, ?string $dataInicio, ?string $dataFim): array
    {
        $period     = $this->buildPeriodFilter('d.dataFpRealizado_dealV2', $year, $months, $dataInicio, $dataFim);
        $consultada = $this->condSerasaConsultada();
        $statusExpr = "CASE WHEN TRIM(COALESCE(d.statusCpf_dealV2,'')) = '' THEN 'Não informado' ELSE TRIM(d.statusCpf_dealV2) END";

        $sql = "
            SELECT
                CASE WHEN LEFT(d.faseId_dealV2,2)='C5' THEN 'comercial2' ELSE 'comercial4' END AS pipe,
                $statusExpr AS status,
                COUNT(*) AS qtd
            FROM dealV2 d
            WHERE {$period['sql']}
              AND d.dataFpRealizado_dealV2 IS NOT NULL AND d.dataFpRealizado_dealV2 > '0000-00-00'
              AND LEFT(d.faseId_dealV2, 2) IN ('C5', 'C3')
              AND $consultada
            GROUP BY pipe, status
            ORDER BY pipe, qtd DESC
        ";
        $result = ['comercial2' => [], 'comercial4' => []];
        foreach ($this->queryAll($sql, $period['params']) as $r) {
            $result[$r['pipe']][] = ['status' => $r['status'], 'qtd' => (int)$r['qtd']];
        }
        return $result;
    }

    /**
     * Consultas Serasa realizadas por mês (Comercial 2 vs Comercial 4) — visibilidade
     * de consumo ao longo do tempo de um recurso limitado, pedida explicitamente
     * pelo usuário.
     */
    private function calcularSerasaTendenciaMensal($year, ?string $months, ?string $dataInicio, ?string $dataFim): array
    {
        $period     = $this->buildPeriodFilter('d.dataFpRealizado_dealV2', $year, $months, $dataInicio, $dataFim);
        $consultada = $this->condSerasaConsultada();

        $sql = "
            SELECT
                DATE_FORMAT(d.dataFpRealizado_dealV2, '%Y-%m') AS mes,
                CASE WHEN LEFT(d.faseId_dealV2,2)='C5' THEN 'comercial2' ELSE 'comercial4' END AS pipe,
                COUNT(*) AS qtd
            FROM dealV2 d
            WHERE {$period['sql']}
              AND d.dataFpRealizado_dealV2 IS NOT NULL AND d.dataFpRealizado_dealV2 > '0000-00-00'
              AND LEFT(d.faseId_dealV2, 2) IN ('C5', 'C3')
              AND $consultada
            GROUP BY mes, pipe
            ORDER BY mes ASC
        ";
        $nomeMes = ['01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr', '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago', '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'];

        $porMes = [];
        foreach ($this->queryAll($sql, $period['params']) as $r) {
            $porMes[$r['mes']][$r['pipe']] = (int)$r['qtd'];
        }
        ksort($porMes);

        $result = [];
        foreach ($porMes as $mes => $vals) {
            $com2 = $vals['comercial2'] ?? 0;
            $com4 = $vals['comercial4'] ?? 0;
            $result[] = [
                'mes'        => $mes,
                'label'      => ($nomeMes[substr($mes, 5, 2)] ?? $mes) . '/' . substr($mes, 2, 2),
                'comercial2' => $com2,
                'comercial4' => $com4,
                'total'      => $com2 + $com4,
            ];
        }
        return $result;
    }

    /**
     * Tabela cliente a cliente dos cards já consultados (mesmas colunas do print
     * de referência), limitada a 500 linhas — mesmo padrão de
     * rafaeltorres_controle.php::calcularLinhasClientes().
     */
    private function calcularSerasaDetalhe($year, ?string $months, ?string $dataInicio, ?string $dataFim, float $cambio): array
    {
        $period     = $this->buildPeriodFilter('d.dataFpRealizado_dealV2', $year, $months, $dataInicio, $dataFim);
        $consultada = $this->condSerasaConsultada();

        $sql = "
            SELECT
                d.idBitrix_dealV2 AS id_bitrix,
                d.nome_dealV2 AS nome,
                d.fase_dealV2 AS fase,
                d.faseId_dealV2 AS fase_id,
                CASE WHEN LEFT(d.faseId_dealV2,2)='C5' THEN 'Comercial 2' ELSE 'Comercial 4' END AS pipe,
                d.dataFpRealizado_dealV2 AS data_fp_realizado,
                d.dataContratoComboAssinado_dealV2 AS data_combo_assinado,
                TRIM(d.serasaRange_dealV2) AS serasa_range,
                d.serasaScore_dealV2 AS serasa_score,
                d.statusCpf_dealV2 AS status_cpf,
                d.nomeSujo_dealV2 AS nome_sujo,
                d.socioEmpresaNegativa_dealV2 AS socio_negativado,
                COALESCE(d.valorTotalDividas_dealV2, 0) AS dividas_brl,
                COALESCE(d.valorCartorioTotal_dealV2, 0) AS cartorio_brl,
                d.responsavel_dealV2 AS responsavel
            FROM dealV2 d
            WHERE {$period['sql']}
              AND d.dataFpRealizado_dealV2 IS NOT NULL AND d.dataFpRealizado_dealV2 > '0000-00-00'
              AND LEFT(d.faseId_dealV2, 2) IN ('C5', 'C3')
              AND $consultada
            ORDER BY d.dataFpRealizado_dealV2 DESC
            LIMIT 500
        ";

        $rows = [];
        foreach ($this->queryAll($sql, $period['params']) as $r) {
            $rows[] = [
                'id_bitrix'                => $r['id_bitrix'],
                'nome'                     => $r['nome'],
                'fase'                     => $r['fase'],
                'fase_id'                  => $r['fase_id'],
                'pipe'                     => $r['pipe'],
                'data_fp_realizado'        => $this->fmtDataSerasa($r['data_fp_realizado']),
                'data_combo_assinado'      => $this->fmtDataSerasa($r['data_combo_assinado']),
                'serasa_range'             => $r['serasa_range'],
                'serasa_score'             => $r['serasa_score'] !== null ? (int)$r['serasa_score'] : null,
                'status_cpf'               => $r['status_cpf'],
                'nome_sujo'                => $r['nome_sujo'],
                'socio_negativado'         => $r['socio_negativado'],
                'valor_total_dividas_usd'  => $cambio > 0 ? round((float)$r['dividas_brl'] / $cambio, 2) : 0.0,
                'valor_cartorio_total_usd' => $cambio > 0 ? round((float)$r['cartorio_brl'] / $cambio, 2) : 0.0,
                'responsavel'              => $r['responsavel'],
            ];
        }
        return $rows;
    }
}
