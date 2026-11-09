<?php

/**
 * Dashboard "NPS Eventos" — porte da medida DAX "Html_NPS" do Power BI.
 *
 * Cruza as respostas da pesquisa pós-evento "On The Road" (tabela
 * npsEvento): quem comprou consulta e quem não comprou.
 *
 * Os dois questionários fazem perguntas de "indicação" diferentes entre si
 * (compradores: "indicaria a Acme-EUA"; não compradores: "indicaria o
 * evento OTR") — por isso nunca são combinados num único NPS, sempre dois
 * blocos separados (comprou/naoComprou).
 */
class npseventos extends MRPHP
{
    private const CACHE_TTL_DADOS    = 600;

    /** Thresholds do círculo de NPS (0-10), idênticos à medida DAX original. */
    private const NPS_CLASSE_EX   = 9;
    private const NPS_CLASSE_GR   = 7.5;
    private const NPS_CLASSE_WARN = 5;

    public function index()
    {
        $this->setTemplate('npseventos');
    }

    /** GET /npseventos/dicionario — página estática, sem fetch. */
    public function dicionario()
    {
        $this->setTemplate('npseventos');
    }

    /** GET /npseventos/dados?year=&months=&cidades= */
    public function dados()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('npseventos_dados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $comprou    = $this->carregarPlanilhaComprou();
            $naoComprou = $this->carregarPlanilhaNaoComprou();

            $filtros = $this->lerFiltros();
            $linhasComprou    = $this->filtrarLinhas($comprou['linhas'], $filtros);
            $linhasNaoComprou = $this->filtrarLinhas($naoComprou['linhas'], $filtros);

            $blocoComprou    = $this->calcularBlocoComprou($linhasComprou);
            $blocoNaoComprou = $this->calcularBlocoNaoComprou($linhasNaoComprou);

            $totalComprou    = count($linhasComprou);
            $totalNaoComprou = count($linhasNaoComprou);
            $total           = $totalComprou + $totalNaoComprou;

            $response = [
                'total'              => $total,
                'totalComprou'       => $totalComprou,
                'pctComprou'         => $total > 0 ? round($totalComprou / $total, 4) : 0,
                'totalNaoComprou'    => $totalNaoComprou,
                'pctNaoComprou'      => $total > 0 ? round($totalNaoComprou / $total, 4) : 0,
                'chanceMediaImigrar' => $blocoNaoComprou['chanceImigrar'],
                'comprou'            => $blocoComprou,
                'naoComprou'         => $blocoNaoComprou,
                'fatoresFechamento'  => $this->topN($linhasComprou, 'fatorFechamento', 5),
                'motivosNaoFechou'   => $this->topN($linhasNaoComprou, 'motivoNaoFechou', 5),
                'interessePosEvento' => $this->topN($linhasNaoComprou, 'interessePosEvento', 3),
                'cidadesDisponiveis' => $this->cidadesDisponiveis($comprou['linhas'], $naoComprou['linhas']),
                'anosDisponiveis'    => $this->anosDisponiveis($comprou['linhas'], $naoComprou['linhas']),
                'indisponivel'       => $comprou['indisponivel'] || $naoComprou['indisponivel'],
            ];

            // Sem alguma das planilhas os blocos saem incompletos; cachear isso
            // congelaria a tela por 10min mesmo depois de o acesso voltar.
            if (!$response['indisponivel']) {
                $this->cacheSet($cacheKey, $response);
            }
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    // ===================================================================
    // FILTROS (em memória — no máximo ~150 linhas por request, não precisa de SQL)
    // ===================================================================

    private function lerFiltros(): array
    {
        return [
            'anos'       => $this->csvParaLista($this->g('year')),
            'meses'      => array_map('intval', $this->csvParaLista($this->g('months'))),
            'cidades'    => array_map(fn($c) => $this->normalizarTexto($c), $this->csvParaLista($this->g('cidades'))),
            'dataInicio' => $this->g('data_inicio'),
            'dataFim'    => $this->g('data_fim'),
        ];
    }

    private function csvParaLista(?string $csv): array
    {
        if ($csv === null || trim($csv) === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $csv)), fn($v) => $v !== ''));
    }

    /**
     * Período: range explícito (data_inicio/data_fim) OU ano/mês — mesma
     * regra de precedência de buildPeriodFilter() nos dashboards SQL (ex.:
     * rafaeltorres_controle.php): se qualquer ponta do range vier preenchida,
     * ano/mês são ignorados por completo. Cidade é sempre um filtro à parte.
     */
    private function filtrarLinhas(array $linhas, array $filtros): array
    {
        $temRange = ($filtros['dataInicio'] !== null && $filtros['dataInicio'] !== '')
                 || ($filtros['dataFim'] !== null && $filtros['dataFim'] !== '');

        if (!$temRange && !$filtros['anos'] && !$filtros['meses'] && !$filtros['cidades']) {
            return $linhas;
        }

        return array_values(array_filter($linhas, function ($l) use ($filtros, $temRange) {
            if ($temRange) {
                if (!$l['timestamp']) {
                    return false;
                }
                $data = $l['timestamp']->format('Y-m-d');
                if ($filtros['dataInicio'] && $data < $filtros['dataInicio']) {
                    return false;
                }
                if ($filtros['dataFim'] && $data > $filtros['dataFim']) {
                    return false;
                }
            } else {
                if ($filtros['anos'] && (!$l['timestamp'] || !in_array($l['timestamp']->format('Y'), $filtros['anos'], true))) {
                    return false;
                }
                if ($filtros['meses'] && (!$l['timestamp'] || !in_array((int)$l['timestamp']->format('n'), $filtros['meses'], true))) {
                    return false;
                }
            }
            if ($filtros['cidades'] && !in_array($this->normalizarTexto($l['cidade']), $filtros['cidades'], true)) {
                return false;
            }
            return true;
        }));
    }

    // ===================================================================
    // CÁLCULO DOS BLOCOS (equivalente às VARs da medida DAX Html_NPS)
    // ===================================================================

    private function classeCor(float $valor): string
    {
        if ($valor >= self::NPS_CLASSE_EX)   return 'ex';
        if ($valor >= self::NPS_CLASSE_GR)   return 'gr';
        if ($valor >= self::NPS_CLASSE_WARN) return 'warn';
        return 'rd';
    }

    private function media(array $linhas, string $campo): ?float
    {
        $valores = array_values(array_filter(
            array_map(fn($l) => $l[$campo] ?? null, $linhas),
            fn($v) => $v !== null
        ));
        if (!$valores) {
            return null;
        }
        return round(array_sum($valores) / count($valores), 1);
    }

    private function calcularBlocoComprou(array $linhas): array
    {
        $expGeral = $this->media($linhas, 'expGeral');
        return [
            'expGeral'    => $expGeral,
            'classe'      => $expGeral !== null ? $this->classeCor($expGeral) : null,
            'indicacao'   => $this->media($linhas, 'indicacao'),
            'local'       => $this->media($linhas, 'local'),
            'coffee'      => $this->media($linhas, 'coffee'),
            'palestrante' => $this->media($linhas, 'palestrante'),
            'recepcao'    => $this->media($linhas, 'recepcao'),
            'brindes'     => $this->media($linhas, 'brindes'),
            'abordagem'   => $this->media($linhas, 'abordagem'),
            'atendimento' => $this->media($linhas, 'atendimento'),
        ];
    }

    private function calcularBlocoNaoComprou(array $linhas): array
    {
        $expGeral = $this->media($linhas, 'expGeral');
        return [
            'expGeral'      => $expGeral,
            'classe'        => $expGeral !== null ? $this->classeCor($expGeral) : null,
            'indicacao'     => $this->media($linhas, 'indicacao'),
            'local'         => $this->media($linhas, 'local'),
            'coffee'        => $this->media($linhas, 'coffee'),
            'palestrante'   => $this->media($linhas, 'palestrante'),
            'recepcao'      => $this->media($linhas, 'recepcao'),
            'brindes'       => $this->media($linhas, 'brindes'),
            'abordagem'     => $this->media($linhas, 'abordagem'),
            'chanceImigrar' => $this->media($linhas, 'chanceImigrar'),
        ];
    }

    /** Equivalente ao TOPN/CONCATENATEX da DAX: conta ocorrências não vazias e devolve as N mais frequentes. */
    private function topN(array $linhas, string $campo, int $n): array
    {
        $contagem = [];
        foreach ($linhas as $l) {
            $valor = trim((string)($l[$campo] ?? ''));
            if ($valor === '') {
                continue;
            }
            $contagem[$valor] = ($contagem[$valor] ?? 0) + 1;
        }
        arsort($contagem);
        $resultado = [];
        foreach (array_slice($contagem, 0, $n, true) as $label => $valor) {
            $resultado[] = ['label' => $label, 'valor' => $valor];
        }
        return $resultado;
    }

    /**
     * Cidades distintas das duas planilhas combinadas (equivalente à medida
     * DAX "Cidade do Evento", que faz UNION das duas tabelas). Dedup por nome
     * normalizado (sem acento/case) — a planilha tem grafias inconsistentes
     * ("São José dos Campos" x "São José dos campos"); fica a primeira grafia
     * encontrada.
     */
    private function cidadesDisponiveis(array $comprou, array $naoComprou): array
    {
        $vistos = [];
        foreach (array_merge($comprou, $naoComprou) as $l) {
            $cidade = trim((string)($l['cidade'] ?? ''));
            if ($cidade === '') {
                continue;
            }
            $chave = $this->normalizarTexto($cidade);
            if (!isset($vistos[$chave])) {
                $vistos[$chave] = $cidade;
            }
        }
        $cidades = array_values($vistos);
        sort($cidades, SORT_FLAG_CASE | SORT_STRING);
        return $cidades;
    }

    /** Anos com pelo menos uma resposta, para popular o dropdown de período. */
    private function anosDisponiveis(array $comprou, array $naoComprou): array
    {
        $anos = [];
        foreach (array_merge($comprou, $naoComprou) as $l) {
            if ($l['timestamp']) {
                $anos[$l['timestamp']->format('Y')] = true;
            }
        }
        $lista = array_keys($anos);
        sort($lista);
        return $lista;
    }

    // ===================================================================
    // RESPOSTAS (tabela npsEvento)
    // ===================================================================

    private function carregarPlanilhaComprou(): array
    {
        return $this->carregarRespostas('comprou');
    }

    private function carregarPlanilhaNaoComprou(): array
    {
        return $this->carregarRespostas('nao_comprou');
    }

    /**
     * Lê as respostas de um tipo ('comprou' ou 'nao_comprou') no formato que os
     * cálculos acima esperam: timestamp (DateTime), nome, cidade, notas (float
     * ou null) e textos. Os filtros de ano, mês e cidade continuam em memória,
     * porque são poucas centenas de linhas.
     *
     * @return array{linhas: array<int, array<string, mixed>>, indisponivel: bool}
     */
    private function carregarRespostas(string $tipo): array
    {
        $campos = $tipo === 'comprou'
            ? ['expGeral', 'local', 'coffee', 'palestrante', 'recepcao', 'brindes', 'abordagem', 'atendimento', 'indicacao', 'fatorFechamento']
            : ['expGeral', 'local', 'coffee', 'palestrante', 'recepcao', 'brindes', 'abordagem', 'chanceImigrar', 'indicacao', 'motivoNaoFechou', 'interessePosEvento'];
        $textos = ['fatorFechamento', 'motivoNaoFechou', 'interessePosEvento'];

        $colunas = implode(', ', array_map(fn($c) => "{$c}_npsEvento AS {$c}", $campos));
        $stmt = $this->db->prepare(
            "SELECT respondidoEm_npsEvento AS respondidoEm, nome_npsEvento AS nome, cidade_npsEvento AS cidade, {$colunas}
               FROM npsEvento
              WHERE tipo_npsEvento = ?
              ORDER BY respondidoEm_npsEvento"
        );
        $stmt->bind_param('s', $tipo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $linhas = [];
        while ($r = $resultado->fetch_assoc()) {
            $linha = [
                'timestamp' => new \DateTime($r['respondidoEm']),
                'nome'      => (string)$r['nome'],
                'cidade'    => (string)$r['cidade'],
            ];
            foreach ($campos as $c) {
                $linha[$c] = in_array($c, $textos, true)
                    ? (string)($r[$c] ?? '')
                    : ($r[$c] === null ? null : (float)$r[$c]);
            }
            $linhas[] = $linha;
        }
        $stmt->close();

        return ['linhas' => $linhas, 'indisponivel' => !$linhas];
    }

    /**
     * Texto -> minúsculo, sem acento e com espaço colapsado. Usado para
     * comparar cidades sem depender de acento ou maiúscula. Cópia de
     * consultacom2_controle.php::normalizarTexto() — sem mb_*, porque nada
     * garante mbstring no servidor.
     */
    private function normalizarTexto(string $t): string
    {
        static $acentos = [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c',
            'Á' => 'a', 'À' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Ä' => 'a',
            'É' => 'e', 'È' => 'e', 'Ê' => 'e', 'Ë' => 'e',
            'Í' => 'i', 'Ì' => 'i', 'Î' => 'i', 'Ï' => 'i',
            'Ó' => 'o', 'Ò' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o',
            'Ú' => 'u', 'Ù' => 'u', 'Û' => 'u', 'Ü' => 'u', 'Ç' => 'c',
        ];
        $t = strtolower(strtr(trim($t), $acentos));
        return preg_replace('/\s+/', ' ', $t);
    }

    // ===================================================================
    // HELPERS: CACHE EM ARQUIVO (.mrphp/cache) — cópia de consultacom2_controle.php
    // ===================================================================

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
    // HELPERS: HTTP / JSON — cópia de consultacom2_controle.php
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
                ? ['error' => $e->getMessage(), 'classe' => get_class($e), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]
                : ['error' => 'Erro interno do servidor.'],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }
}
