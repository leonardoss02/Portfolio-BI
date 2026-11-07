<?php

/**
 * Dashboard exclusivo da vendedora Helena Castro — MRPHP.
 *
 * Acompanhamento diário de vendas e evolução (out/2026): foco em consultas
 * vendidas, com ingressos (cadeiras) como série secundária. Tudo restrito a
 * responsavel_leadV2/responsavel_dealV2 = 'Helena Castro' (bind_param, nunca
 * interpolado) + filtro opcional de Form (aplicado à coluna de form própria
 * de cada tabela). Sem camada calculos/: toda a lógica de SQL/cálculo fica em
 * métodos privados desta classe.
 *
 * O backend devolve a série DIÁRIA (dias sem movimento preenchidos com zero)
 * do período e os KPIs do período e do período anterior de mesmo tamanho; o
 * agrupamento por semana/mês é feito na view, então trocar a granularidade
 * não faz nova requisição.
 *
 * Exceção: a aba Jornada do cliente (jornada/jornadaDados/trilha/buscaCliente)
 * lê a lead_tag de todos os responsáveis, com filtro opcional de responsável
 * (a view abre na Helena).
 */
class helenacastro extends MRPHP
{
    private const RESPONSAVEL = 'Helena Castro';

    /** faseId_dealV2 de "Cadeira vendida" (ver CLAUDE.md deste projeto). */
    private const FASE_CADEIRA = 'C9:Won';

    /** faseId_dealV2 de "Consulta vendida" (ver CLAUDE.md deste projeto). */
    private const SQL_FASES_CONSULTA = "('Won', 'C11:WON')";

    /** Janela usada quando a requisição não traz período nenhum. */
    private const DIAS_PADRAO = 30;

    /** Maior período aceito (~10 anos): acima disso a série diária passa de megabytes. */
    private const DIAS_MAXIMO = 3660;

    private const DIA_ZERADO = ['leads' => 0, 'leads_consultas' => 0, 'consultas_vendidas' => 0, 'ingressos_vendidos' => 0];

    /**
     * Tags da lead_tag que formam o fluxograma da Jornada, na ordem das
     * etapas. A view (paginas/helenacastro/jornada.php, CATALOGO) repete esta
     * lista com rótulo e posição. Tag fora dela não define coorte nem seta:
     * aparece só em "outras tags" e na trilha do cliente.
     */
    private const TAGS_JORNADA = [
        'lead-criado', 'carrinho-acessado', 'carrinho-pagamento',
        'pagamento-confirmado', 'carrinho-abandonado', 'lead-descartado',
    ];

    /** Etapas intermediárias: lead cuja última tag do catálogo é uma delas está "parado". */
    private const TAGS_ETAPA = ['lead-criado', 'carrinho-acessado', 'carrinho-pagamento'];

    /** Linhas da lista de jornadas devolvidas (as mais recentes); os totais contam a coorte inteira. */
    private const LIMITE_LINHAS_JORNADA = 3000;

    /** Link do lead no Bitrix: prefixo + idBitrix + '/'. */
    private const BITRIX_LEAD_URL = 'https://exemplo.bitrix24.com.br/crm/lead/details/';

    public function index()
    {
        $this->setTemplate('helenacastro');
    }

    /** GET /helenacastro/jornada — caminho dos leads pelas tags (lead_tag), filtrável por responsável. */
    public function jornada()
    {
        $this->setTemplate('helenacastro');
    }

    /** GET /helenacastro/dicionario — página estática que explica as métricas (sem consulta ao banco). */
    public function dicionario()
    {
        $this->setTemplate('helenacastro');
    }

    // ===================================================================
    // HELPERS: CACHE EM ARQUIVO (.mrphp/cache) — TTL curto, sem tocar no banco
    // Mesmo padrão de executive_controle.php (cópia própria do controller,
    // sem camada compartilhada — convenção do projeto, ver memória
    // cache-copia-por-controller).
    // ===================================================================

    private const CACHE_TTL_DROPDOWN = 600;
    private const CACHE_TTL_DADOS = 600;
    private const CACHE_TTL_JORNADA = 300;
    private const CACHE_TTL_BUSCA = 60;

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
     * GET /helenacastro/dados — payload único: série diária do período,
     * KPIs do período e do período anterior (mesmo número de dias, logo
     * antes), motivos de descarte e tabela de clientes (deals vendidos).
     */
    public function dados()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('helenacastro_dados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();
            [$ini, $fim] = $this->resolverPeriodo($f);

            $nDias  = $ini->diff($fim)->days + 1;
            $antFim = $ini->modify('-1 day');
            $antIni = $ini->modify('-' . $nDias . ' days');

            // Período atual + anterior numa query só; separados aqui.
            $porDia   = $this->calcularSerieDiaria($antIni, $fim, $f['form']);
            $dias     = $this->preencherDias($porDia, $ini, $fim);
            $anterior = $this->preencherDias($porDia, $antIni, $antFim);

            $response = [
                'meta' => [
                    'data_inicio' => $ini->format('Y-m-d'),
                    'data_fim'    => $fim->format('Y-m-d'),
                    'form'        => $f['form'],
                ],
                'dias'             => $dias,
                'kpis'             => $this->somarKpis($dias),
                'kpis_anterior'    => $this->somarKpis($anterior),
                'periodo_anterior' => ['data_inicio' => $antIni->format('Y-m-d'), 'data_fim' => $antFim->format('Y-m-d')],
                'descarte'         => $this->calcularMotivosDescarte($ini, $fim, $f['form']),
                'linhas'           => $this->calcularLinhasClientes($ini, $fim, $f['form']),
            ];
            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /helenacastro/forms — forms distintos (leadV2 + dealV2) já usados por Helena Castro (dropdown de busca). */
    public function forms()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('helenacastro_forms');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DROPDOWN);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $sql = "
                SELECT DISTINCT form FROM (
                    SELECT l.formPreenchido_leadV2 AS form
                    FROM leadV2 l
                    WHERE l.responsavel_leadV2 = ?
                      AND l.formPreenchido_leadV2 IS NOT NULL AND l.formPreenchido_leadV2 != ''

                    UNION

                    SELECT d.formPreenchido_dealV2 AS form
                    FROM dealV2 d
                    WHERE d.responsavel_dealV2 = ?
                      AND d.formPreenchido_dealV2 IS NOT NULL AND d.formPreenchido_dealV2 != ''
                ) t
                ORDER BY form
            ";
            $data = array_column($this->queryAll($sql, [self::RESPONSAVEL, self::RESPONSAVEL]), 'form');
            $this->cacheSet($cacheKey, $data);
            $this->jsonOut($data);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * GET /helenacastro/jornadaDados — números da Jornada do período:
     * KPIs, etapas, setas, entradas, parados e a lista de jornadas. Coorte =
     * leads cuja primeira tag do catálogo caiu no período; cada etapa conta
     * quem chegou nela em qualquer momento, até agora. Sem `responsavel`,
     * todos os responsáveis; com ele, só os leads desses responsáveis.
     */
    public function jornadaDados()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('helenacastro_jornada');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_JORNADA);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $f = $this->lerFiltros();
            [$ini, $fim] = $this->resolverPeriodo($f);

            $response = ['meta' => [
                'data_inicio' => $ini->format('Y-m-d'),
                'data_fim'    => $fim->format('Y-m-d'),
            ]] + $this->calcularJornada($ini, $fim, $f['responsavel']);
            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * GET /helenacastro/trilha?id={ID do lead no Bitrix} — todas as tags do
     * lead, inclusive as fora do catálogo, e os dados dele na leadV2. Sem
     * período e sem cache: é a trilha completa, como está agora. Devolve
     * e-mail e telefone, por isso passa por exigirAcesso().
     */
    public function trilha()
    {
        $this->exigirAcesso();
        try {
            $id = $this->g('id');
            if ($id === null || !ctype_digit($id)) {
                $this->jsonOut(['error' => 'Informe o ID do lead no Bitrix.'], 400);
            }

            $eventos = $this->queryAll("
                SELECT tag, descricao, dataCadastro AS data, id_leadV2
                FROM lead_tag
                WHERE idBitrix_lead = ?
                ORDER BY dataCadastro, id_lead_tag
            ", [$id]);

            $idLeadV2 = null;
            foreach ($eventos as $e) {
                if ($e['id_leadV2'] !== null) {
                    $idLeadV2 = (int)$e['id_leadV2'];
                }
            }
            $lead = $eventos ? $this->dadosLeadV2($id, $idLeadV2) : null;

            $this->jsonOut([
                'lead' => [
                    'id_bitrix'   => $id,
                    'link_bitrix' => self::BITRIX_LEAD_URL . $id . '/',
                    'id_leadV2'   => $lead['id_leadV2'] ?? null,
                    'nome'        => $lead['nome'] ?? null,
                    'email'       => $lead['email'] ?? null,
                    'telefone'    => $lead['telefone'] ?? null,
                    'responsavel' => $lead['responsavel'] ?? null,
                ],
                'eventos' => array_map(fn($e) => [
                    'tag'       => $e['tag'],
                    'descricao' => $e['descricao'],
                    'data'      => $e['data'],
                ], $eventos),
            ]);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * GET /helenacastro/jornadaResponsaveis — responsáveis dos leads com tag
     * do catálogo (dropdown de busca da Jornada). Responsável atual do lead
     * na leadV2, sem espaços nas pontas; lead sem leadV2 ou com responsável
     * em branco fica fora da lista. Só nomes da equipe, sem dado de cliente.
     */
    public function jornadaResponsaveis()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('helenacastro_jornada_resp');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DROPDOWN);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $inTags = implode(',', array_fill(0, count(self::TAGS_JORNADA), '?'));
            $sql = "
                SELECT DISTINCT TRIM(COALESCE(l.responsavel_leadV2, lb.responsavel_leadV2)) AS nome
                FROM (
                    SELECT idBitrix_lead, MAX(id_leadV2) AS id_leadV2
                    FROM lead_tag
                    WHERE tag IN ($inTags)
                    GROUP BY idBitrix_lead
                ) c
                LEFT JOIN leadV2 l  ON l.id_leadV2 = c.id_leadV2
                LEFT JOIN leadV2 lb ON c.id_leadV2 IS NULL AND lb.idBitrix_leadV2 = CONVERT(c.idBitrix_lead USING latin1)
                WHERE TRIM(COALESCE(l.responsavel_leadV2, lb.responsavel_leadV2)) <> ''
                ORDER BY nome
            ";
            $data = array_column($this->queryAll($sql, self::TAGS_JORNADA), 'nome');
            $this->cacheSet($cacheKey, $data);
            $this->jsonOut($data);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * GET /helenacastro/buscaCliente?q= — até 8 leads com tag cujo nome ou
     * e-mail contém q; com 4+ dígitos, também ID do Bitrix ou telefone. Busca
     * na lead_tag inteira, sem período, do lead mais recente para o mais antigo;
     * com `responsavel`, só entre os leads desses responsáveis (mesmo filtro
     * da Jornada). Devolve nome e e-mail, por isso passa por exigirAcesso().
     */
    public function buscaCliente()
    {
        $this->exigirAcesso();
        try {
            $q = trim((string)($this->g('q') ?? ''));
            if (mb_strlen($q) < 2) {
                $this->jsonOut([]);
            }

            $cacheKey = $this->cacheKeyFromRequest('helenacastro_busca');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_BUSCA);
            if ($cached !== null) {
                $this->jsonOut($cached);
            }

            $like    = '%' . addcslashes(mb_substr($q, 0, 80), '%_\\') . '%';
            $digitos = preg_replace('/\D/', '', $q);
            $conds   = ['x.nome LIKE ?', 'x.email LIKE ?'];
            $params  = [$like, $like];
            if (strlen($digitos) >= 4) {
                $conds[]  = 'CAST(x.id_bitrix AS CHAR) LIKE ?';
                $conds[]  = "REGEXP_REPLACE(x.telefone, '[^0-9]', '') LIKE ?";
                $params[] = '%' . $digitos . '%';
                $params[] = '%' . $digitos . '%';
            }
            $fResp = $this->buildInFilter('TRIM(x.responsavel)', $this->g('responsavel'));

            // Lead sem id_leadV2 na tag (ainda fora do espelho quando foi
            // marcado) cai no join pelo ID do Bitrix: INT × VARCHAR latin1,
            // então o CONVERT fica no lado INT (ver CLAUDE.md, checklist).
            $sql = "
                SELECT x.id_bitrix, x.nome, x.email
                FROM (
                    SELECT
                        c.idBitrix_lead AS id_bitrix,
                        c.ultima,
                        COALESCE(l.nome_leadV2, lb.nome_leadV2)                   AS nome,
                        COALESCE(l.email_leadV2, lb.email_leadV2)                 AS email,
                        COALESCE(l.telefoneTexto_leadV2, lb.telefoneTexto_leadV2) AS telefone,
                        COALESCE(l.responsavel_leadV2, lb.responsavel_leadV2)     AS responsavel
                    FROM (
                        SELECT idBitrix_lead, MAX(id_leadV2) AS id_leadV2, MAX(dataCadastro) AS ultima
                        FROM lead_tag
                        GROUP BY idBitrix_lead
                    ) c
                    LEFT JOIN leadV2 l  ON l.id_leadV2 = c.id_leadV2
                    LEFT JOIN leadV2 lb ON c.id_leadV2 IS NULL AND lb.idBitrix_leadV2 = CONVERT(c.idBitrix_lead USING latin1)
                ) x
                WHERE (" . implode(' OR ', $conds) . ") {$fResp['sql']}
                ORDER BY x.ultima DESC
                LIMIT 8
            ";
            $data = array_map(fn($r) => [
                'id_bitrix' => (string)$r['id_bitrix'],
                'nome'      => $r['nome'],
                'email'     => $r['email'],
            ], $this->queryAll($sql, array_merge($params, $fResp['params'])));

            $this->cacheSet($cacheKey, $data);
            $this->jsonOut($data);
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
    // HELPERS: CONTROLE DE ACESSO dos endpoints com dado pessoal
    // ===================================================================

    /**
     * Mesma política das páginas (Basic Auth, liberado só em iframe de
     * origem permitida com ?to=1), para os endpoints que devolvem dado
     * pessoal (trilha, buscaCliente). Cópia da guarda de hotel_controle.php,
     * porque a de home_controle.php é privada e o projeto adota cópia por
     * controller. Os demais endpoints JSON ficam abertos (decisão de
     * 14/09/2026, ver CLAUDE.md).
     *
     * O fetch() da página embutida nunca manda Sec-Fetch-Dest: iframe (manda
     * "empty" + Sec-Fetch-Site: same-origin), então o JS da Jornada reenvia o
     * ?to=1 da página em cada chamada a estes endpoints. Fora do embed, o
     * navegador manda sozinho as credenciais do Basic Auth da página; o realm
     * é o mesmo de home_controle.php para ele reaproveitar o login.
     */
    private function exigirAcesso(): void
    {
        $whitelist = array_filter(array_map('trim', explode(',', (string)$this->lerEnv('DASH_IFRAME_ALLOWLIST'))));

        $to1                = ($_GET['to'] ?? '') === '1';
        $isIframe           = ($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '') === 'iframe';
        $isFetchMesmaOrigem = ($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '') === 'empty'
            && ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'same-origin';

        $refererOrigem = '';
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $scheme = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_SCHEME);
            $host   = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
            if ($scheme && $host) {
                $refererOrigem = $scheme . '://' . $host;
            }
        }

        if ($to1 && ($isIframe || $isFetchMesmaOrigem) && $refererOrigem !== '' && in_array($refererOrigem, $whitelist, true)) {
            return;
        }

        $usuarioEsperado = (string)$this->lerEnv('DASH_AUTH_USER');
        $senhaEsperada   = (string)$this->lerEnv('DASH_AUTH_PASSWORD');

        $usuario = $_SERVER['PHP_AUTH_USER'] ?? null;
        $senha   = $_SERVER['PHP_AUTH_PW'] ?? null;

        // Apache em CGI/FastCGI não popula PHP_AUTH_USER; o cabeçalho cru
        // continua disponível.
        if ($usuario === null && !empty($_SERVER['HTTP_AUTHORIZATION']) && str_starts_with($_SERVER['HTTP_AUTHORIZATION'], 'Basic ')) {
            $decodificado = base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6));
            if ($decodificado !== false && str_contains($decodificado, ':')) {
                [$usuario, $senha] = explode(':', $decodificado, 2);
            }
        }

        $valido = $usuario !== null
            && $senha !== null
            && $usuarioEsperado !== ''
            && hash_equals($usuarioEsperado, (string)$usuario)
            && hash_equals($senhaEsperada, (string)$senha);

        if (!$valido) {
            header('WWW-Authenticate: Basic realm="Acme EUA BI"');
            $this->jsonOut(['error' => 'Acesso negado. Entre no painel com o seu login.'], 401);
        }
    }

    /** Valor de uma chave do .env (cópia do leitor de home_controle.php). */
    private function lerEnv(string $chave): ?string
    {
        $path = __DIR__ . '/../.env';
        if (!file_exists($path)) {
            return null;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
            $linha = trim($linha);
            if ($linha === '' || str_starts_with($linha, '#')) {
                continue;
            }
            $pos = strpos($linha, '=');
            if ($pos === false) {
                continue;
            }
            if (trim(substr($linha, 0, $pos)) === $chave) {
                return trim(trim(substr($linha, $pos + 1)), '"\'');
            }
        }
        return null;
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

    // ===================================================================
    // HELPERS: FILTROS E PERÍODO
    // ===================================================================

    /** Lê os filtros do querystring ($_GET) num array normalizado. */
    private function lerFiltros(): array
    {
        return [
            'year'        => $this->g('year'),
            'months'      => $this->g('months'),
            'data_inicio' => $this->g('data_inicio'),
            'data_fim'    => $this->g('data_fim'),
            'form'        => $this->g('form'),
            'responsavel' => $this->g('responsavel'),
        ];
    }

    /**
     * Intervalo [início, fim] do período, sempre com as duas pontas: a série
     * diária precisa delas para preencher os dias vazios e para calcular o
     * período anterior. A view sempre manda data_inicio/data_fim (o
     * "Últimos N" e o Ano/Mês já viram datas no navegador); Ano/Mês e o
     * padrão de 30 dias só valem para chamada direta ao endpoint.
     *
     * @return \DateTimeImmutable[] [início, fim]
     */
    private function resolverPeriodo(array $f): array
    {
        $hoje = new \DateTimeImmutable('today');
        $ini  = $this->dataValida($f['data_inicio']);
        $fim  = $this->dataValida($f['data_fim']);

        if (!$ini && !$fim && ($f['year'] !== null || $f['months'] !== null)) {
            [$ini, $fim] = $this->intervaloAnoMes($f['year'], $f['months'], $hoje);
        }

        $fim = $fim ?? $hoje;
        $ini = $ini ?? $fim->modify('-' . (self::DIAS_PADRAO - 1) . ' days');
        if ($ini > $fim) {
            [$ini, $fim] = [$fim, $ini];
        }
        if ($ini->diff($fim)->days + 1 > self::DIAS_MAXIMO) {
            $this->jsonOut(['error' => 'Período grande demais: escolha um intervalo de até 10 anos.'], 400);
        }
        return [$ini, $fim];
    }

    /** 'YYYY-MM-DD' válida (2000–2100) vira data; qualquer outra coisa vira null. */
    private function dataValida(?string $v): ?\DateTimeImmutable
    {
        if ($v === null || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m)) {
            return null;
        }
        if ((int)$m[1] < 2000 || (int)$m[1] > 2100 || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            return null;
        }
        return new \DateTimeImmutable($v);
    }

    /**
     * Ano/Mês → intervalo contínuo, igual ao intervaloAnoMes() das views:
     * do 1º dia do menor mês do menor ano ao último dia do maior mês do maior
     * ano (sem mês, os anos inteiros), limitado a hoje. Um intervalo que
     * ainda não começou (mês futuro) fica inteiro, e sai zerado.
     */
    private function intervaloAnoMes(?string $year, ?string $months, \DateTimeImmutable $hoje): array
    {
        $anos = $year !== null ? array_values(array_filter(array_map('intval', explode(',', $year)), fn($a) => $a >= 2000 && $a <= 2100)) : [];
        $yIni = $anos ? min($anos) : (int)$hoje->format('Y');
        $yFim = $anos ? max($anos) : $yIni;
        $ms   = $months !== null ? array_values(array_filter(array_map('intval', explode(',', $months)), fn($m) => $m >= 1 && $m <= 12)) : [];

        $mIni = $ms ? min($ms) : 1;
        $mFim = $ms ? max($ms) : 12;
        $ini  = new \DateTimeImmutable(sprintf('%04d-%02d-01', $yIni, $mIni));
        $fim  = new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $yFim, $mFim, $this->ultimoDiaDoMes($yFim, $mFim)));
        return [$ini, $ini > $hoje ? $fim : min($fim, $hoje)];
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

    /** Bounds literais (sargáveis) para `col >= ? AND col <= ?`. */
    private function bounds(\DateTimeImmutable $ini, \DateTimeImmutable $fim): array
    {
        return [$ini->format('Y-m-d') . ' 00:00:00', $fim->format('Y-m-d') . ' 23:59:59'];
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

    /** Filtro de Form sobre leadV2 (multi-select, valores brutos de formPreenchido_leadV2). */
    private function buildFormFilterLead(?string $csv): array
    {
        return $this->buildInFilter('l.formPreenchido_leadV2', $csv);
    }

    /** Filtro de Form sobre dealV2 (multi-select, valores brutos de formPreenchido_dealV2). */
    private function buildFormFilterDeal(?string $csv): array
    {
        return $this->buildInFilter('d.formPreenchido_dealV2', $csv);
    }

    // ===================================================================
    // CÁLCULOS: SÉRIE DIÁRIA (leads x vendas) + KPIs
    // ===================================================================

    /**
     * Contagens por dia entre $ini e $fim, só dos dias com movimento:
     * leads/leads_consultas de leadV2 pela data do último formulário e
     * consultas/ingressos vendidos de dealV2 pela data de venda —
     * consolidados num único SELECT via UNION ALL + agregação externa
     * (1 round-trip). Os KPIs saem da soma destes dias, então batem com a
     * série por construção.
     *
     * @return array<string, array> 'YYYY-MM-DD' => contagens
     */
    private function calcularSerieDiaria(\DateTimeImmutable $ini, \DateTimeImmutable $fim, ?string $form): array
    {
        [$de, $ate] = $this->bounds($ini, $fim);
        $formLead   = $this->buildFormFilterLead($form);
        $formDeal   = $this->buildFormFilterDeal($form);

        $sql = "
            SELECT
              dia,
              SUM(leads)              AS leads,
              SUM(leads_consultas)    AS leads_consultas,
              SUM(consultas_vendidas) AS consultas_vendidas,
              SUM(ingressos_vendidos) AS ingressos_vendidos
            FROM (
                SELECT
                  DATE(l.dataUltForm_leadV2) AS dia,
                  COUNT(*) AS leads,
                  SUM(CASE WHEN l.tipoLead_leadV2 = 'Consulta' THEN 1 ELSE 0 END) AS leads_consultas,
                  0 AS consultas_vendidas,
                  0 AS ingressos_vendidos
                FROM leadV2 l
                WHERE l.responsavel_leadV2 = ?
                  AND l.dataUltForm_leadV2 > '0000-00-00'
                  AND l.dataUltForm_leadV2 >= ? AND l.dataUltForm_leadV2 <= ?
                  {$formLead['sql']}
                GROUP BY dia

                UNION ALL

                SELECT
                  DATE(d.dataVenda_dealV2) AS dia,
                  0 AS leads,
                  0 AS leads_consultas,
                  SUM(CASE WHEN d.faseId_dealV2 IN " . self::SQL_FASES_CONSULTA . " THEN 1 ELSE 0 END) AS consultas_vendidas,
                  SUM(CASE WHEN d.faseId_dealV2 = '" . self::FASE_CADEIRA . "' THEN 1 ELSE 0 END) AS ingressos_vendidos
                FROM dealV2 d
                WHERE d.responsavel_dealV2 = ?
                  AND (d.faseId_dealV2 = '" . self::FASE_CADEIRA . "' OR d.faseId_dealV2 IN " . self::SQL_FASES_CONSULTA . ")
                  AND d.dataVenda_dealV2 > '0000-00-00'
                  AND d.dataVenda_dealV2 >= ? AND d.dataVenda_dealV2 <= ?
                  {$formDeal['sql']}
                GROUP BY dia
            ) t
            GROUP BY dia
        ";
        $params = array_merge(
            [self::RESPONSAVEL, $de, $ate],
            $formLead['params'],
            [self::RESPONSAVEL, $de, $ate],
            $formDeal['params']
        );

        $porDia = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $porDia[(string)$r['dia']] = [
                'leads'              => (int)$r['leads'],
                'leads_consultas'    => (int)$r['leads_consultas'],
                'consultas_vendidas' => (int)$r['consultas_vendidas'],
                'ingressos_vendidos' => (int)$r['ingressos_vendidos'],
            ];
        }
        return $porDia;
    }

    /** Um item por dia de $ini a $fim (inclusive), com zero nos dias sem movimento. */
    private function preencherDias(array $porDia, \DateTimeImmutable $ini, \DateTimeImmutable $fim): array
    {
        $out = [];
        for ($d = $ini; $d <= $fim; $d = $d->modify('+1 day')) {
            $k     = $d->format('Y-m-d');
            $out[] = ['data' => $k] + ($porDia[$k] ?? self::DIA_ZERADO);
        }
        return $out;
    }

    /**
     * Totais de uma lista de dias. Conversão = consultas vendidas ÷ leads de
     * consulta do mesmo período (compara totais, não segue o mesmo lead).
     */
    private function somarKpis(array $dias): array
    {
        $k = self::DIA_ZERADO;
        foreach ($dias as $d) {
            foreach ($k as $campo => $v) {
                $k[$campo] = $v + $d[$campo];
            }
        }
        $n = count($dias);
        $k['dias']                = $n;
        $k['conv_consultas']      = $k['leads_consultas'] > 0 ? round($k['consultas_vendidas'] / $k['leads_consultas'], 4) : 0.0;
        $k['media_consultas_dia'] = $n > 0 ? round($k['consultas_vendidas'] / $n, 4) : 0.0;
        return $k;
    }

    // ===================================================================
    // CÁLCULOS: MOTIVOS DE DESCARTE (leadV2.motivoDescarte_leadV2, top 15 + "Outros")
    // ===================================================================

    private function calcularMotivosDescarte(\DateTimeImmutable $ini, \DateTimeImmutable $fim, ?string $form, int $topN = 15): array
    {
        [$de, $ate] = $this->bounds($ini, $fim);
        $formLead   = $this->buildFormFilterLead($form);

        $sql = "
            SELECT TRIM(l.motivoDescarte_leadV2) AS motivo, COUNT(*) AS qtd
            FROM leadV2 l
            WHERE l.responsavel_leadV2 = ?
              AND l.motivoDescarte_leadV2 IS NOT NULL AND TRIM(l.motivoDescarte_leadV2) != ''
              AND l.dataUltForm_leadV2 > '0000-00-00'
              AND l.dataUltForm_leadV2 >= ? AND l.dataUltForm_leadV2 <= ?
              {$formLead['sql']}
            GROUP BY motivo
            ORDER BY qtd DESC
        ";
        $params = array_merge([self::RESPONSAVEL, $de, $ate], $formLead['params']);
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
    // CÁLCULOS: TABELA DE CLIENTES (deals vendidos — dealV2)
    // ===================================================================

    /** Linhas individuais dos deals vendidos (Ingresso OU Consulta) no período, para a tabela de clientes. */
    private function calcularLinhasClientes(\DateTimeImmutable $ini, \DateTimeImmutable $fim, ?string $form): array
    {
        [$de, $ate] = $this->bounds($ini, $fim);
        $formDeal   = $this->buildFormFilterDeal($form);

        $sql = "
            SELECT
                d.idBitrix_dealV2      AS id_bitrix,
                d.nome_dealV2          AS nome_deal,
                d.email_dealV2         AS email,
                d.dataVenda_dealV2     AS data_venda,
                d.formPreenchido_dealV2 AS form,
                CASE WHEN d.faseId_dealV2 = '" . self::FASE_CADEIRA . "' THEN 'Ingresso' ELSE 'Consulta' END AS tipo
            FROM dealV2 d
            WHERE d.responsavel_dealV2 = ?
              AND (d.faseId_dealV2 = '" . self::FASE_CADEIRA . "' OR d.faseId_dealV2 IN " . self::SQL_FASES_CONSULTA . ")
              AND d.dataVenda_dealV2 > '0000-00-00'
              AND d.dataVenda_dealV2 >= ? AND d.dataVenda_dealV2 <= ?
              {$formDeal['sql']}
            ORDER BY d.dataVenda_dealV2 DESC
            LIMIT 500
        ";
        $params = array_merge([self::RESPONSAVEL, $de, $ate], $formDeal['params']);
        $rows   = $this->queryAll($sql, $params);

        return array_map(fn($r) => [
            'id_bitrix'  => $r['id_bitrix'],
            'nome_deal'  => $r['nome_deal'],
            'email'      => $r['email'],
            'data_venda' => $this->fmtDataOuNull($r['data_venda']),
            'tipo'       => $r['tipo'],
            'form'       => $r['form'],
        ], $rows);
    }

    /**
     * Zero-date (0000-00-00, com ou sem parte de hora) ou vazio vira null;
     * senão formata via $this->data() (só a parte de data). Compara só os 10
     * primeiros caracteres — comparar a string inteira falha quando vem com
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
    // CÁLCULOS: JORNADA DO CLIENTE (lead_tag)
    // ===================================================================

    /**
     * Jornada da coorte [ini, fim]. Uma query traz todas as tags dos leads
     * cuja primeira tag do catálogo caiu no período, em ordem de
     * dataCadastro com desempate por id_lead_tag (duas tags podem cair no
     * mesmo segundo); a agregação é feita aqui, lead a lead. Etapas, setas,
     * entradas e parados usam só as tags do catálogo, na ordem em que
     * aconteceram; as demais entram em "outras_tags".
     *
     * Os leads chegam do que entrou mais recentemente para o mais antigo,
     * então a lista de jornadas fica com os primeiros LIMITE_LINHAS_JORNADA
     * sem precisar guardar e ordenar a coorte inteira (ver leadsDaJornada()).
     *
     * Situação do lead: "pagou" se tem pagamento-confirmado (vale mesmo com
     * tag posterior); senão, pela última tag do catálogo — abandonou,
     * descartado ou, numa etapa intermediária, em andamento.
     *
     * $responsavel (CSV) restringe a coorte ao responsável atual do lead na
     * leadV2; lead sem leadV2 só entra quando não há filtro.
     */
    private function calcularJornada(\DateTimeImmutable $ini, \DateTimeImmutable $fim, ?string $responsavel = null): array
    {
        [$de, $ate] = $this->bounds($ini, $fim);
        $inTags     = implode(',', array_fill(0, count(self::TAGS_JORNADA), '?'));
        $fResp      = $this->buildInFilter('TRIM(COALESCE(l.responsavel_leadV2, lb.responsavel_leadV2))', $responsavel);

        $sql = "
            SELECT
                t.idBitrix_lead AS id_bitrix,
                t.tag,
                t.dataCadastro  AS em,
                COALESCE(l.nome_leadV2, lb.nome_leadV2) AS nome
            FROM (
                SELECT idBitrix_lead, MAX(id_leadV2) AS id_leadV2, MIN(dataCadastro) AS entrada
                FROM lead_tag
                WHERE tag IN ($inTags)
                GROUP BY idBitrix_lead
                HAVING MIN(dataCadastro) >= ? AND MIN(dataCadastro) <= ?
            ) c
            JOIN lead_tag t     ON t.idBitrix_lead = c.idBitrix_lead
            LEFT JOIN leadV2 l  ON l.id_leadV2 = c.id_leadV2
            LEFT JOIN leadV2 lb ON c.id_leadV2 IS NULL AND lb.idBitrix_leadV2 = CONVERT(c.idBitrix_lead USING latin1)
            WHERE 1=1 {$fResp['sql']}
            ORDER BY c.entrada DESC, t.idBitrix_lead, t.dataCadastro, t.id_lead_tag
        ";

        $noCatalogo = array_flip(self::TAGS_JORNADA);
        $etapa      = array_flip(self::TAGS_ETAPA);
        $nos = $entradas = $parados = $outras = $arestas = [];
        $ateConfirmar = $linhas = [];
        $totalLeads   = 0;

        foreach ($this->leadsDaJornada($sql, array_merge(self::TAGS_JORNADA, [$de, $ate], $fResp['params'])) as $id => $lead) {
            $totalLeads++;
            $cat = array_values(array_filter($lead['ev'], fn($e) => isset($noCatalogo[$e['tag']])));
            $tagsCat = array_values(array_unique(array_column($cat, 'tag')));

            foreach ($tagsCat as $tag) {
                $nos[$tag] = ($nos[$tag] ?? 0) + 1;
            }
            foreach (array_unique(array_column($lead['ev'], 'tag')) as $tag) {
                if (!isset($noCatalogo[$tag])) {
                    $outras[$tag] = ($outras[$tag] ?? 0) + 1;
                }
            }

            $primeira = $cat[0];
            $ultima   = $cat[count($cat) - 1];
            $entradas[$primeira['tag']] = ($entradas[$primeira['tag']] ?? 0) + 1;

            // Seta = tag A seguida direto da tag B; conta o lead uma vez por par.
            $vistas = [];
            for ($i = 1, $n = count($cat); $i < $n; $i++) {
                $a = $cat[$i - 1]['tag'];
                $b = $cat[$i]['tag'];
                $k = $a . '>' . $b;
                if ($a === $b || isset($vistas[$k])) {
                    continue;
                }
                $vistas[$k] = true;
                $arestas[$k] ??= ['de' => $a, 'para' => $b, 'leads' => 0, 'tempos' => []];
                $arestas[$k]['leads']++;
                $arestas[$k]['tempos'][] = $cat[$i]['ts'] - $cat[$i - 1]['ts'];
            }

            $pago = null;
            foreach ($cat as $e) {
                if ($e['tag'] === 'pagamento-confirmado') {
                    $pago = $e;
                    break;
                }
            }
            if ($pago) {
                $situacao       = 'pagou';
                $ateConfirmar[] = $pago['ts'] - $primeira['ts'];
            } elseif ($ultima['tag'] === 'carrinho-abandonado') {
                $situacao = 'abandonou';
            } elseif ($ultima['tag'] === 'lead-descartado') {
                $situacao = 'descartado';
            } else {
                $situacao = 'andamento';
            }
            if ($situacao === 'andamento' && isset($etapa[$ultima['tag']])) {
                $parados[$ultima['tag']] = ($parados[$ultima['tag']] ?? 0) + 1;
            }

            if (count($linhas) < self::LIMITE_LINHAS_JORNADA) {
                $linhas[] = [
                    'id'       => $id,
                    'nome'     => $lead['nome'],
                    'entrada'  => $primeira['em'],
                    'ultima'   => $ultima['tag'],
                    'situacao' => $situacao,
                    'duracao'  => count($cat) > 1 ? $ultima['ts'] - $primeira['ts'] : null,
                    'etapas'   => $tagsCat,
                ];
            }
        }

        return [
            'kpis' => [
                'leads'           => $totalLeads,
                'carrinho'        => $nos['carrinho-acessado'] ?? 0,
                'pagos'           => $nos['pagamento-confirmado'] ?? 0,
                'abandonos'       => $nos['carrinho-abandonado'] ?? 0,
                'tempo_ate_pagar' => $this->mediana($ateConfirmar),
            ],
            'nos'          => $nos,
            'entradas'     => $entradas,
            'parados'      => $parados,
            'arestas'      => array_values(array_map(fn($a) => [
                'de'      => $a['de'],
                'para'    => $a['para'],
                'leads'   => $a['leads'],
                'mediana' => $this->mediana($a['tempos']),
            ], $arestas)),
            'outras_tags'  => $outras,
            'linhas'       => $linhas,
            'linhas_total' => $totalLeads,
        ];
    }

    /**
     * Linhas da query da Jornada agrupadas por lead, um lead por vez:
     * id do Bitrix => ['nome' => ..., 'ev' => [['tag', 'em', 'ts'], ...]].
     * As linhas de cada lead vêm juntas, então só o lead atual fica na memória, e
     * a leitura é sem buffer (bind_result + fetch, sem get_result): a coorte
     * de um ano passa de 190 mil linhas e, inteira num array, estourava o
     * memory_limit de 128 MB.
     */
    private function leadsDaJornada(string $sql, array $params): \Generator
    {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new \Exception('Erro na preparação da consulta: ' . $this->db->error);
        }
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        $stmt->bind_result($idBitrix, $tag, $em, $nome);

        $id   = null;
        $lead = null;
        while ($stmt->fetch()) {
            if ((string)$idBitrix !== $id) {
                if ($lead !== null) {
                    yield $id => $lead;
                }
                $id   = (string)$idBitrix;
                $lead = ['nome' => $nome, 'ev' => []];
            }
            $lead['ev'][] = ['tag' => $tag, 'em' => $em, 'ts' => strtotime($em)];
        }
        $stmt->close();
        if ($lead !== null) {
            yield $id => $lead;
        }
    }

    /** Mediana de uma lista de segundos (inteiro), ou null se vazia. */
    private function mediana(array $v): ?int
    {
        if (!$v) {
            return null;
        }
        sort($v);
        $m = intdiv(count($v), 2);
        return (int)round(count($v) % 2 ? $v[$m] : ($v[$m - 1] + $v[$m]) / 2);
    }

    /**
     * Dados do lead na leadV2: pelo id_leadV2 que veio na tag e, se não
     * houver (lead marcado antes de chegar ao espelho), pelo ID do Bitrix —
     * parâmetro string contra o VARCHAR, então o índice único vale.
     */
    private function dadosLeadV2(string $idBitrix, ?int $idLeadV2): ?array
    {
        $cols = 'id_leadV2, nome_leadV2 AS nome, email_leadV2 AS email, telefoneTexto_leadV2 AS telefone, responsavel_leadV2 AS responsavel';
        if ($idLeadV2 !== null) {
            $rows = $this->queryAll("SELECT $cols FROM leadV2 WHERE id_leadV2 = ?", [$idLeadV2]);
            if ($rows) {
                return $rows[0];
            }
        }
        return $this->queryAll("SELECT $cols FROM leadV2 WHERE idBitrix_leadV2 = ?", [$idBitrix])[0] ?? null;
    }
}
