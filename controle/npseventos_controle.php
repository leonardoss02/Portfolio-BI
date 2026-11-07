<?php

/**
 * Dashboard "NPS Eventos" — porte da medida DAX "Html_NPS" do Power BI.
 *
 * Cruza duas planilhas Google Sheets da pesquisa pós-evento "On The Road":
 * quem comprou consulta e quem não comprou. Sem nenhuma consulta ao MySQL —
 * a fonte é só as duas planilhas (mesmo padrão de leitura CSV de
 * consultacom2_controle.php, cache/JSON copiados daquele arquivo — cópia por
 * controller, sem trait compartilhado, é decisão deliberada do projeto).
 *
 * As duas planilhas fazem perguntas de "indicação" diferentes entre si
 * (compradores: "indicaria a Acme-EUA"; não compradores: "indicaria o
 * evento OTR") — por isso nunca são combinadas num único NPS, sempre dois
 * blocos separados (comprou/naoComprou).
 */
class npseventos extends MRPHP
{
    private const SHEET_COMPROU_CSV_URL =
        'https://docs.google.com/spreadsheets/d/ID_DA_PLANILHA_NPS_1/gviz/tq?tqx=out:csv&gid=1581784259';
    private const SHEET_NAO_COMPROU_CSV_URL =
        'https://docs.google.com/spreadsheets/d/ID_DA_PLANILHA_NPS_2/gviz/tq?tqx=out:csv&gid=1950299764';

    private const CACHE_TTL_DADOS    = 600;
    private const CACHE_TTL_PLANILHA = 600;
    private const CACHE_TTL_FALHA    = 60;

    /** $escape do fgetcsv sempre explícito — ver nota em consultacom2_controle.php. */
    private const CSV_ESCAPE = '';

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
    // PLANILHAS (Google Sheets -> CSV) — mesmo padrão de consultacom2_controle.php
    // ===================================================================

    private function carregarPlanilhaComprou(): array
    {
        return $this->carregarPlanilhaGenerica(
            'npseventos_comprou',
            self::SHEET_COMPROU_CSV_URL,
            fn($csv) => $this->parsePlanilhaComprouCsv($csv)
        );
    }

    private function carregarPlanilhaNaoComprou(): array
    {
        return $this->carregarPlanilhaGenerica(
            'npseventos_nao_comprou',
            self::SHEET_NAO_COMPROU_CSV_URL,
            fn($csv) => $this->parsePlanilhaNaoComprouCsv($csv)
        );
    }

    /**
     * Baixa e parseia uma planilha, com cache de chave fixa (não depende do
     * filtro do usuário) e fallback em 3 níveis: cache normal, cache vencido
     * (stale) se o download falhar, e marcador de falha de 60s para não pagar
     * os 15s de timeout do cURL a cada request enquanto o Google está fora.
     *
     * @return array{linhas: array<int, array<string, mixed>>, indisponivel: bool}
     */
    private function carregarPlanilhaGenerica(string $cacheKey, string $url, callable $parser): array
    {
        $cached = $this->cacheGet($cacheKey, self::CACHE_TTL_PLANILHA);
        if ($cached !== null) {
            return ['linhas' => $this->reidratarLinhas($cached), 'indisponivel' => false];
        }

        $falhouAgora = $this->cacheGet($cacheKey . '_falha', self::CACHE_TTL_FALHA) !== null;

        $linhas = [];
        if (!$falhouAgora) {
            $csv    = $this->baixarCsv($url, $cacheKey);
            $linhas = $csv === null ? [] : $parser($csv);
        }

        if (!$linhas) {
            if (!$falhouAgora) {
                $this->cacheSet($cacheKey . '_falha', time());
            }
            $stale = $this->cacheGet($cacheKey, PHP_INT_MAX);
            if ($stale !== null) {
                error_log("[npseventos] {$cacheKey}: download falhou — usando cache vencido");
                return ['linhas' => $this->reidratarLinhas($stale), 'indisponivel' => false];
            }
            return ['linhas' => [], 'indisponivel' => true];
        }

        $this->cacheSet($cacheKey, $this->desidratarLinhas($linhas));
        return ['linhas' => $linhas, 'indisponivel' => false];
    }

    /** DateTime não sobrevive a json_encode/decode — guarda-se a string ISO no cache. */
    private function desidratarLinhas(array $linhas): array
    {
        return array_map(function ($l) {
            $l['timestamp'] = $l['timestamp'] ? $l['timestamp']->format('c') : null;
            return $l;
        }, $linhas);
    }

    private function reidratarLinhas(array $linhas): array
    {
        return array_map(function ($l) {
            $l['timestamp'] = $l['timestamp'] ? new \DateTime($l['timestamp']) : null;
            return $l;
        }, $linhas);
    }

    /** Baixa o CSV; null em qualquer falha (rede, status != 200 ou corpo HTML de login). */
    private function baixarCsv(string $url, string $rotulo): ?string
    {
        if (!function_exists('curl_init')) {
            error_log("[npseventos] {$rotulo}: cURL indisponível neste servidor");
            return null;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'AcmeEUA-BI/1.0',
        ]);
        $raw    = curl_exec($ch);
        $errno  = curl_errno($ch);
        $err    = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $raw === false) {
            error_log("[npseventos] {$rotulo}: erro cURL: {$err}");
            return null;
        }
        if ((int)$status !== 200) {
            error_log("[npseventos] {$rotulo}: Google respondeu status {$status}");
            return null;
        }
        // Compartilhamento revogado? O Google devolve HTML de login.
        $inicio = ltrim($raw);
        if ($inicio === '' || $inicio[0] === '<') {
            error_log("[npseventos] {$rotulo}: resposta nao e CSV (acesso ao link revogado?)");
            return null;
        }
        return $raw;
    }

    /** Planilha de quem comprou consulta (109 respostas na sessão em que este dashboard foi criado). */
    private function parsePlanilhaComprouCsv(string $csv): array
    {
        $porTitulo = [
            'nome'                                                            => 'nome',
            'cidade em que participou do on the road'                        => 'cidade',
            'como voce avalia sua experiencia geral no evento?'              => 'expGeral',
            'como voce avalia o local do evento?'                            => 'local',
            'como voce avalia o coffee break?'                               => 'coffee',
            'como voce avalia o palestrante?'                                => 'palestrante',
            'como voce avalia a recepcao?'                                   => 'recepcao',
            'como voce avalia os brindes?'                                   => 'brindes',
            'como voce avalia a abordagem comercial?'                        => 'abordagem',
            'qual foi o principal fator para fechar a consulta?'             => 'fatorFechamento',
            'como voce avalia o atendimento comercial?'                      => 'atendimento',
            'o quanto voce indicaria a acme-eua para amigos ou familiares?' => 'indicacao',
        ];
        // Índice 12 ("O que mais influenciou sua decisão?") não é usado pela
        // medida DAX original e por isso não entra aqui.
        $ordemPadrao = [
            0 => 'timestampRaw', 1 => 'nome', 2 => 'cidade', 3 => null,
            4 => 'expGeral', 5 => 'local', 6 => 'coffee', 7 => 'palestrante',
            8 => 'recepcao', 9 => 'brindes', 10 => 'abordagem', 11 => 'fatorFechamento',
            12 => null, 13 => 'atendimento', 14 => 'indicacao',
        ];
        $numericos = ['expGeral', 'local', 'coffee', 'palestrante', 'recepcao', 'brindes', 'abordagem', 'atendimento', 'indicacao'];

        return $this->parsePlanilhaGenerica($csv, $porTitulo, $ordemPadrao, $numericos);
    }

    /** Planilha de quem não comprou consulta (21 respostas na sessão em que este dashboard foi criado). */
    private function parsePlanilhaNaoComprouCsv(string $csv): array
    {
        $porTitulo = [
            'nome'                                                                     => 'nome',
            'cidade em que participou do on the road'                                 => 'cidade',
            'como voce avalia sua experiencia geral no evento?'                       => 'expGeral',
            'como voce avalia o local do evento?'                                     => 'local',
            'como voce avalia o coffee break?'                                        => 'coffee',
            'como voce avalia o palestrante?'                                         => 'palestrante',
            'como voce avalia a recepcao?'                                            => 'recepcao',
            'como voce avalia os brindes?'                                            => 'brindes',
            'como voce avalia a abordagem comercial?'                                 => 'abordagem',
            'qual foi o principal motivo para nao fechar a consulta?'                 => 'motivoNaoFechou',
            'apos o evento, seu interesse em morar nos eua'                           => 'interessePosEvento',
            'qual a chance de iniciar um processo de imigracao nos proximos 12 meses?' => 'chanceImigrar',
            'o quanto voce indicaria o evento on the road para um amigo ou familiar?' => 'indicacao',
        ];
        $ordemPadrao = [
            0 => 'timestampRaw', 1 => 'nome', 2 => 'cidade',
            3 => 'expGeral', 4 => 'local', 5 => 'coffee', 6 => 'palestrante',
            7 => 'recepcao', 8 => 'brindes', 9 => 'abordagem', 10 => 'motivoNaoFechou',
            11 => 'interessePosEvento', 12 => 'chanceImigrar', 13 => 'indicacao',
        ];
        $numericos = ['expGeral', 'local', 'coffee', 'palestrante', 'recepcao', 'brindes', 'abordagem', 'chanceImigrar', 'indicacao'];

        return $this->parsePlanilhaGenerica($csv, $porTitulo, $ordemPadrao, $numericos);
    }

    /**
     * Parser comum às duas planilhas: fgetcsv via php://temp (aguenta quebra
     * de linha dentro de aspas — o cabeçalho da planilha de compradores tem
     * uma), colunas localizadas pelo nome normalizado do cabeçalho (os
     * títulos têm espaço à direita inconsistente, ex. "Nome ", " Como você
     * avalia..."), com fallback posicional.
     */
    private function parsePlanilhaGenerica(string $csv, array $porTitulo, array $ordemPadrao, array $numericos): array
    {
        $fp = fopen('php://temp', 'r+');
        if ($fp === false) {
            error_log('[npseventos] nao foi possivel abrir php://temp');
            return [];
        }
        fwrite($fp, $csv);
        rewind($fp);

        $header = fgetcsv($fp, 0, ',', '"', self::CSV_ESCAPE);
        if (!$header) {
            fclose($fp);
            return [];
        }

        $idx = [];
        foreach ($header as $i => $titulo) {
            $chave = $porTitulo[$this->normalizarTexto((string)$titulo)] ?? ($ordemPadrao[$i] ?? null);
            if ($chave !== null && !isset($idx[$chave])) {
                $idx[$chave] = $i;
            }
        }
        // Timestamp é sempre a 1ª coluna do form do Google — não depende do
        // nome do cabeçalho.
        if (!isset($idx['timestampRaw'])) {
            $idx['timestampRaw'] = 0;
        }

        $campo = function (array $cols, string $chave) use ($idx): string {
            return isset($idx[$chave]) ? trim((string)($cols[$idx[$chave]] ?? '')) : '';
        };

        $camposUsados = array_unique(array_merge(['nome', 'cidade'], array_values($porTitulo)));

        $linhas = [];
        while (($cols = fgetcsv($fp, 0, ',', '"', self::CSV_ESCAPE)) !== false) {
            if (count(array_filter($cols, fn($v) => trim((string)$v) !== '')) === 0) {
                continue; // linha em branco
            }
            $linha = [
                'timestamp' => $this->parseTimestamp($campo($cols, 'timestampRaw')),
            ];
            foreach ($camposUsados as $chave) {
                $linha[$chave] = in_array($chave, $numericos, true)
                    ? $this->toFloatOuNull($campo($cols, $chave))
                    : $campo($cols, $chave);
            }
            $linhas[] = $linha;
        }
        fclose($fp);

        return $linhas;
    }

    /**
     * Formato do Google Forms: M/D/AAAA H:MM:SS (locale EUA), confirmado
     * pelos próprios dados desta planilha — ex. "6/26/2026 15:13:47" só é
     * válido como mês/dia (dia 26 não existe como mês).
     */
    private function parseTimestamp(string $raw): ?\DateTime
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $dt = \DateTime::createFromFormat('n/j/Y G:i:s', $raw);
        return $dt ?: null;
    }

    private function toFloatOuNull(string $v): ?float
    {
        $v = trim($v);
        return ($v === '' || !is_numeric($v)) ? null : (float)$v;
    }

    /**
     * Texto -> minúsculo, sem acento e com espaço colapsado. Usado tanto para
     * localizar coluna pelo cabeçalho quanto para normalizar cidade (a
     * planilha tem grafias inconsistentes de cidade e cabeçalhos com espaço a
     * mais). Cópia de consultacom2_controle.php::normalizarTexto() — sem
     * mb_*, porque nada garante mbstring no servidor.
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
