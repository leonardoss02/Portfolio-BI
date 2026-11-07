<?php

/**
 * BI Explorer — ferramenta de auto-exploração de dados (Fase 1 / MVP).
 *
 * Mesmo padrão dos demais dashboards (sem camada compartilhada): toda a
 * lógica de introspecção de schema, geração de SQL e persistência em
 * arquivo vive em métodos privados desta classe.
 *
 * Diferente dos outros dashboards (que têm colunas/filtros fixos por
 * negócio), aqui tabela e coluna vêm do usuário em tempo de execução —
 * por isso NUNCA são interpoladas na query sem antes serem validadas
 * contra o schema real (SHOW FULL TABLES / SHOW COLUMNS), já que
 * identificadores não podem ser passados via bind_param (só valores).
 */
class biexplorer extends MRPHP
{
    private const SCHEMA_CACHE_TTL = 21600; // 6h
    private const LIMITE_PADRAO_PREVIEW = 1000;
    private const LIMITE_MAX_PREVIEW = 10000;
    private const LIMITE_LINHAS_SQL_MANUAL = 5000;
    private const TIMEOUT_SQL_MANUAL_SEGUNDOS = 8;
    private const MAX_VERSOES = 20;

    private const FUNCOES_METRICA = ['COUNT', 'COUNT_DISTINCT', 'SUM', 'AVG', 'MIN', 'MAX', 'STDDEV'];
    private const FUNCOES_NUMERICAS = ['SUM', 'AVG', 'STDDEV'];

    private const MAX_JOINS = 5;
    private const TIPOS_JOIN = ['LEFT', 'INNER'];

    private const PALAVRAS_CHAVE_EXPRESSAO = ['CASE', 'WHEN', 'THEN', 'ELSE', 'END', 'AND', 'OR', 'NOT', 'IS', 'NULL', 'TRUE', 'FALSE'];
    private const FUNCOES_COLUNA_CALCULADA = ['IF', 'DATEDIFF', 'MONTH', 'YEAR', 'DAY', 'CONCAT', 'ROUND', 'UPPER', 'LOWER', 'COALESCE', 'IFNULL', 'ABS'];
    private const OPERADORES_FORMULA_METRICA = ['+', '-', '*', '/'];
    private const TIPOS_RESULTADO_COLUNA_CALCULADA = ['texto', 'numero', 'data', 'booleano'];
    private const MAX_TOKENS_EXPRESSAO = 200;
    private const MAX_PROFUNDIDADE_PARENTESES = 20;
    private const MAX_TAMANHO_EXPRESSAO = 2000;

    private static ?bool $ehMariaDb = null;

    public function index()
    {
        $this->setTemplate('biexplorer');
    }

    // =====================================================================
    // ENDPOINTS — Schema
    // =====================================================================

    /** GET /biexplorer/tabelas */
    public function tabelas()
    {
        try {
            $bloqueadas = $this->tabelasBloqueadas();
            $tabelas = array_values(array_filter(
                $this->schemaTabelas(),
                fn($t) => !in_array($t['nome'], $bloqueadas, true)
            ));
            $this->jsonOut(['tabelas' => $tabelas]);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /biexplorer/colunas/{tabela} */
    public function colunas($tabela = null)
    {
        try {
            $tabela = $this->validarTabela((string)$tabela);
            $colunas = [];
            foreach ($this->schemaColunas($tabela) as $nome => $meta) {
                $colunas[] = [
                    'nome'        => $nome,
                    'tipo_mysql'  => $meta['tipo_mysql'],
                    'tipo_logico' => $meta['tipo_logico'],
                    'nullable'    => $meta['nullable'],
                ];
            }
            $this->jsonOut(['colunas' => $colunas]);
        } catch (\InvalidArgumentException $e) {
            $this->jsonOut(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    // =====================================================================
    // ENDPOINTS — Motor de query / preview
    // =====================================================================

    /** POST /biexplorer/preview — body: {spec: AnalysisSpec, somenteSql?: bool} */
    public function preview()
    {
        try {
            $body = json_decode((string)file_get_contents('php://input'), true) ?? [];
            $spec = is_array($body['spec'] ?? null) ? $body['spec'] : [];
            $somenteSql = !empty($body['somenteSql']);

            if (($spec['modo'] ?? 'visual') === 'sql') {
                // Mesmo limite restrito do endpoint /biexplorer/sql — modo "sql" aqui
                // executa o mesmo SQL livre-forma, não pode ter um teto mais frouxo.
                $this->rateLimitJson(20, 60);
                $r = $this->executarSqlLeitura((string)($spec['sqlManual'] ?? ''));
                $status = $r['status'];
                unset($r['status']);
                $this->jsonOut($r, $status);
                return;
            }

            // 300/60 (não 120) — preview é somente leitura, uso de um único analista;
            // cada mudança de filtro/coluna dispara um preview ~400ms depois, então uma
            // sessão de montagem ativa de análise passa fácil de 2 requisições/segundo.
            $this->rateLimitJson(300, 60);
            $consulta = $this->construirSql($spec);

            if ($somenteSql) {
                $this->jsonOut(['sql' => $consulta['sql'], 'params' => $consulta['params']]);
                return;
            }

            $inicio = microtime(true);
            $linhas = $this->queryAll($consulta['sql'], $consulta['params'], $consulta['tipos']);
            $tempoMs = round((microtime(true) - $inicio) * 1000, 2);

            $this->jsonOut([
                'sql'               => $consulta['sql'],
                'colunas'           => $consulta['colunas'],
                'linhas'            => $linhas,
                'tempo_ms'          => $tempoMs,
                'linhas_retornadas' => count($linhas),
                'truncado'          => count($linhas) >= $consulta['limite'],
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->jsonOut(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    // =====================================================================
    // ENDPOINTS — Editor SQL manual
    // =====================================================================

    /** POST /biexplorer/sql — body: {sql: string} */
    public function sql()
    {
        try {
            $this->rateLimitJson(20, 60);
            $body = json_decode((string)file_get_contents('php://input'), true) ?? [];
            $sqlBruto = trim((string)($body['sql'] ?? ''));

            $r = $this->executarSqlLeitura($sqlBruto);
            $status = $r['status'];
            unset($r['status']);
            $this->jsonOut($r, $status);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * Núcleo compartilhado entre /biexplorer/sql e o modo "sql" de /biexplorer/preview.
     * Retorna sempre um array com 'status' embutido (200 em sucesso), nunca chama jsonOut
     * diretamente — quem chama decide como responder.
     */
    private function executarSqlLeitura(string $sqlBruto): array
    {
        $erro = $this->validarSomenteLeitura($sqlBruto);
        if ($erro !== null) {
            return ['error' => $erro, 'status' => 400];
        }

        try {
            $sqlComLimite = $this->injetarLimite($sqlBruto);
        } catch (\InvalidArgumentException $e) {
            return ['error' => $e->getMessage(), 'status' => 400];
        }

        $sqlFinal = $this->aplicarTimeout($sqlComLimite, $this->db);

        $inicio = microtime(true);
        try {
            $resultado = $this->db->query($sqlFinal);
        } catch (\mysqli_sql_exception $e) {
            $tempoMs = round((microtime(true) - $inicio) * 1000, 2);
            $mensagem = $this->isErroTimeout($e)
                ? 'Consulta excedeu o tempo limite de ' . self::TIMEOUT_SQL_MANUAL_SEGUNDOS . 's.'
                : $e->getMessage();
            return ['error' => $mensagem, 'sql' => $sqlComLimite, 'tempo_ms' => $tempoMs, 'status' => 400];
        }
        $tempoMs = round((microtime(true) - $inicio) * 1000, 2);

        $linhas = ($resultado instanceof \mysqli_result) ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
        $colunas = $linhas ? array_keys($linhas[0]) : [];

        return [
            'sql'               => $sqlComLimite,
            'colunas'           => $colunas,
            'linhas'            => $linhas,
            'tempo_ms'          => $tempoMs,
            'linhas_retornadas' => count($linhas),
            'status'            => 200,
        ];
    }

    /** Copiado de controle/sql_controle.php — mesma regra, mesma ordem de checagens. */
    private function validarSomenteLeitura(string $sql): ?string
    {
        $sql = trim($sql);
        if ($sql === '') {
            return 'Query vazia.';
        }

        $semTrailing = rtrim($sql, "; \t\n\r");
        if (str_contains($semTrailing, ';')) {
            return 'Apenas uma instrução SQL por requisição é permitida.';
        }

        $permitido = '/^(SELECT|SHOW|EXPLAIN|DESCRIBE|DESC|WITH)\b/i';
        if (!preg_match($permitido, $sql)) {
            return 'Apenas SELECT, SHOW, EXPLAIN, DESCRIBE ou WITH são permitidos.';
        }

        $bloqueados = '/\b(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|REPLACE|CREATE|RENAME|GRANT|REVOKE|LOCK|UNLOCK|CALL|SET|EXEC|EXECUTE)\b|INTO\s+(OUTFILE|DUMPFILE)/i';
        if (preg_match($bloqueados, $sql)) {
            return 'Comando não permitido: apenas consultas de leitura são aceitas.';
        }

        return null;
    }

    /**
     * Diferente do /sql/query (debug do dev, sem limite), este editor é voltado ao
     * analista e roda contra o banco de produção — nunca deixa passar sem LIMIT, e
     * rejeita (não trunca silenciosamente) um LIMIT explícito acima do teto.
     */
    private function injetarLimite(string $sql, int $limiteMax = self::LIMITE_LINHAS_SQL_MANUAL): string
    {
        $semTrailing = rtrim($sql, "; \t\n\r");
        if (preg_match('/\bLIMIT\s+(\d+)(?:\s*,\s*(\d+))?\s*$/i', $semTrailing, $m)) {
            $valorLimite = isset($m[2]) ? (int)$m[2] : (int)$m[1];
            if ($valorLimite > $limiteMax) {
                throw new \InvalidArgumentException("LIMIT máximo permitido é {$limiteMax}.");
            }
            return $semTrailing;
        }
        return $semTrailing . " LIMIT {$limiteMax}";
    }

    /**
     * MariaDB: SET STATEMENT max_statement_time=N FOR <query> (segundos, aceita decimal).
     * MySQL puro: hint /*+ MAX_EXECUTION_TIME(ms) *\/ logo após o SELECT.
     * Dialeto detectado uma vez por processo via SELECT VERSION().
     */
    private function aplicarTimeout(string $sql, object $db, int $segundos = self::TIMEOUT_SQL_MANUAL_SEGUNDOS): string
    {
        if (self::$ehMariaDb === null) {
            $versao = '';
            $resultado = $db->query('SELECT VERSION() AS v');
            if ($resultado instanceof \mysqli_result) {
                $versao = (string)($resultado->fetch_assoc()['v'] ?? '');
            }
            self::$ehMariaDb = stripos($versao, 'MariaDB') !== false;
        }

        if (self::$ehMariaDb) {
            return "SET STATEMENT max_statement_time={$segundos} FOR " . $sql;
        }

        return preg_replace('/^SELECT\b/i', 'SELECT /*+ MAX_EXECUTION_TIME(' . ($segundos * 1000) . ') */', $sql, 1) ?? $sql;
    }

    /** MariaDB ER_STATEMENT_TIMEOUT = 1969, MySQL ER_QUERY_TIMEOUT = 3024. */
    private function isErroTimeout(\mysqli_sql_exception $e): bool
    {
        return in_array($e->getCode(), [1969, 3024], true);
    }

    // =====================================================================
    // ENDPOINTS — Persistência (arquivo, nunca banco — CRM é somente leitura)
    // =====================================================================

    /** GET /biexplorer/listar */
    public function listar()
    {
        try {
            $dir = $this->analisesDir();
            $this->garantirDiretorio($dir);
            $itens = [];
            foreach (glob($dir . '/*.json') ?: [] as $arquivo) {
                $dados = $this->lerJson($arquivo);
                if (!$dados) continue;
                $itens[] = [
                    'id'            => $dados['id'] ?? basename($arquivo, '.json'),
                    'nome'          => $dados['nome'] ?? '(sem nome)',
                    'descricao'     => $dados['descricao'] ?? '',
                    'atualizado_em' => $dados['atualizado_em'] ?? null,
                ];
            }
            usort($itens, fn($a, $b) => strcmp((string)$b['atualizado_em'], (string)$a['atualizado_em']));
            $this->jsonOut(['analises' => $itens]);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** GET /biexplorer/carregar/{id} */
    public function carregar($id = null)
    {
        try {
            $id = $this->sanitizarId((string)$id);
            $dados = $this->lerJson($this->analisesDir() . "/{$id}.json");
            if (!$dados) {
                $this->jsonOut(['error' => 'Análise não encontrada.'], 404);
            }
            $this->jsonOut($dados);
        } catch (\InvalidArgumentException $e) {
            $this->jsonOut(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** POST /biexplorer/salvar — body: {id?, nome, descricao?, spec} */
    public function salvar()
    {
        try {
            $body = json_decode((string)file_get_contents('php://input'), true) ?? [];
            $nome = trim((string)($body['nome'] ?? ''));
            if ($nome === '') {
                $this->jsonOut(['error' => 'Informe um nome para a análise.'], 400);
            }

            $spec = is_array($body['spec'] ?? null) ? $body['spec'] : [];
            $agora = date('c');

            $id = !empty($body['id']) ? $this->sanitizarId((string)$body['id']) : null;
            $existente = $id ? $this->lerJson($this->analisesDir() . "/{$id}.json") : null;

            if ($existente) {
                $versoes = $existente['versoes'] ?? [];
                array_unshift($versoes, [
                    'em'   => $existente['atualizado_em'] ?? $agora,
                    'spec' => $existente['spec'] ?? [],
                ]);
                $dados = $existente;
                $dados['nome'] = $nome;
                $dados['descricao'] = (string)($body['descricao'] ?? $existente['descricao'] ?? '');
                $dados['spec'] = $spec;
                $dados['atualizado_em'] = $agora;
                $dados['versoes'] = array_slice($versoes, 0, self::MAX_VERSOES);
            } else {
                $id = $this->gerarId();
                $dados = [
                    'id'            => $id,
                    'nome'          => $nome,
                    'descricao'     => (string)($body['descricao'] ?? ''),
                    'criado_em'     => $agora,
                    'atualizado_em' => $agora,
                    'spec'          => $spec,
                    'versoes'       => [],
                ];
            }

            $this->escreverJsonAtomico($this->analisesDir() . "/{$id}.json", $dados);
            $this->jsonOut($dados);
        } catch (\InvalidArgumentException $e) {
            $this->jsonOut(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** POST /biexplorer/duplicar/{id} */
    public function duplicar($id = null)
    {
        try {
            $id = $this->sanitizarId((string)$id);
            $original = $this->lerJson($this->analisesDir() . "/{$id}.json");
            if (!$original) {
                $this->jsonOut(['error' => 'Análise não encontrada.'], 404);
            }

            $novoId = $this->gerarId();
            $agora = date('c');
            $copia = $original;
            $copia['id'] = $novoId;
            $copia['nome'] = ($original['nome'] ?? 'Análise') . ' (cópia)';
            $copia['criado_em'] = $agora;
            $copia['atualizado_em'] = $agora;
            $copia['versoes'] = [];

            $this->escreverJsonAtomico($this->analisesDir() . "/{$novoId}.json", $copia);
            $this->jsonOut($copia);
        } catch (\InvalidArgumentException $e) {
            $this->jsonOut(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** POST /biexplorer/autosave — body: {id, spec} — rascunho, não mexe em versoes[] */
    public function autosave()
    {
        try {
            $body = json_decode((string)file_get_contents('php://input'), true) ?? [];
            $id = $this->sanitizarId((string)($body['id'] ?? ''));
            $dados = ['id' => $id, 'spec' => $body['spec'] ?? [], 'em' => date('c')];
            $this->escreverJsonAtomico($this->autosaveDir() . "/{$id}.json", $dados);
            $this->jsonOut(['ok' => true]);
        } catch (\InvalidArgumentException $e) {
            $this->jsonOut(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    private function analisesDir(): string
    {
        return __DIR__ . '/../.mrphp/bi-explorer/analises';
    }

    private function autosaveDir(): string
    {
        return __DIR__ . '/../.mrphp/bi-explorer/autosave';
    }

    /** Nunca usar $id cru em caminho de arquivo — impede path traversal. */
    private function sanitizarId(string $id): string
    {
        $id = preg_replace('/[^a-zA-Z0-9_\-]/', '', $id) ?? '';
        if ($id === '') {
            throw new \InvalidArgumentException('Id inválido.');
        }
        return $id;
    }

    private function gerarId(string $prefixo = 'bie'): string
    {
        return $prefixo . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
    }

    private function garantirDiretorio(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Mesma lógica/arquivo-por-IP-por-rota de MRPHP::rateLimit() (core), mas responde
     * JSON em vez de incluir paginas/error/429.php — o core sempre devolve uma página
     * HTML completa no limite excedido, o que quebra qualquer fetch() que espera JSON
     * (era a causa do "Unexpected token '<' ... is not valid JSON" reportado).
     */
    private function rateLimitJson(int $maxTentativas, int $janela): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rota = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $arquivo = __DIR__ . '/../.mrphp/rate/' . md5($ip . $rota) . '.json';

        $agora = time();
        $dados = file_exists($arquivo) ? json_decode((string)file_get_contents($arquivo), true) : null;

        if (!$dados || ($agora - $dados['inicio']) >= $janela) {
            $dados = ['count' => 1, 'inicio' => $agora];
        } else {
            $dados['count']++;
            if ($dados['count'] > $maxTentativas) {
                $restante = $janela - ($agora - $dados['inicio']);
                $this->jsonOut(['error' => "Muitas requisições. Aguarde {$restante}s e tente novamente."], 429);
            }
        }

        $this->garantirDiretorio(dirname($arquivo));
        file_put_contents($arquivo, json_encode($dados), LOCK_EX);
    }

    private function escreverJsonAtomico(string $caminho, array $dados): void
    {
        $this->garantirDiretorio(dirname($caminho));
        $tmp = $caminho . '.tmp.' . bin2hex(random_bytes(4));
        file_put_contents($tmp, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        if (file_exists($caminho)) {
            @unlink($caminho);
        }
        if (!rename($tmp, $caminho)) {
            @unlink($tmp);
            throw new \RuntimeException("Falha ao gravar $caminho");
        }
    }

    private function lerJson(string $caminho): ?array
    {
        if (!file_exists($caminho)) {
            return null;
        }
        $dados = json_decode((string)file_get_contents($caminho), true);
        return is_array($dados) ? $dados : null;
    }

    // =====================================================================
    // Motor de query — AnalysisSpec -> SQL parametrizado
    // =====================================================================

    private function construirSql(array $spec): array
    {
        $tabelaBase = $this->validarTabela((string)($spec['tabela'] ?? ''));
        $joinsSpec = is_array($spec['joins'] ?? null) ? $spec['joins'] : [];

        $resolvidoAliases = $this->montarMapaAliases($tabelaBase, $joinsSpec);
        $mapaAliases = $resolvidoAliases['mapa'];
        $joins = $this->montarJoins($tabelaBase, $joinsSpec, $mapaAliases, $resolvidoAliases['aliasesPorIndice']);
        $colunasCalculadas = $this->compilarColunasCalculadas($spec, $mapaAliases);

        $select  = $this->montarSelect($spec, $mapaAliases, $colunasCalculadas);
        $where   = $this->montarWhere($spec['filtros'] ?? [], $mapaAliases, $colunasCalculadas);
        $groupBy = $this->montarGroupBy($spec, $mapaAliases, $colunasCalculadas);
        $orderBy = $this->montarOrderBy($spec['ordenacao'] ?? [], $select['colunas']);
        $limit   = $this->montarLimit($spec['limite'] ?? null);

        $sql = "SELECT {$select['sql']} FROM `{$tabelaBase}` t{$joins['sql']} WHERE {$where['sql']}";
        if ($groupBy !== '') $sql .= " {$groupBy}";
        if ($orderBy !== '') $sql .= " {$orderBy}";
        $sql .= " {$limit['sql']}";

        $params = array_merge($where['params'], $limit['params']);
        $tipos  = str_repeat('s', count($where['params'])) . 'i';

        return [
            'sql'     => $sql,
            'params'  => $params,
            'tipos'   => $tipos,
            'colunas' => $select['colunas'],
            'limite'  => $limit['valor'],
        ];
    }

    /**
     * Mapa de aliases válidos para esta consulta: ['t' => tabelaBase, 'j_1' => 'custoMkt', ...],
     * junto do alias já resolvido por índice de $joinsSpec (para montarJoins() não
     * precisar recalcular o mesmo alias de novo). O alias de cada join vem de
     * aliasSqlDeJoin() (sanitizado a partir do id do join, nunca aceito cru) — nunca
     * de uma posição no array, para que remover um join no meio da lista não afete
     * o alias dos demais.
     */
    private function montarMapaAliases(string $tabelaBase, array $joinsSpec): array
    {
        if (count($joinsSpec) > self::MAX_JOINS) {
            throw new \InvalidArgumentException('Número máximo de joins (' . self::MAX_JOINS . ') excedido.');
        }

        $mapa = ['t' => $tabelaBase];
        $usados = ['t' => true, 'calc' => true]; // 'calc' reservado para colunas calculadas — nenhum join pode colidir
        $aliasesPorIndice = [];
        foreach ($joinsSpec as $i => $joinSpec) {
            $alias = $this->aliasSqlDeJoin((string)($joinSpec['id'] ?? ''), $usados);
            $mapa[$alias] = $this->validarTabela((string)($joinSpec['tabela'] ?? ''));
            $aliasesPorIndice[$i] = $alias;
        }
        return ['mapa' => $mapa, 'aliasesPorIndice' => $aliasesPorIndice];
    }

    /**
     * Sanitiza o id do join (vindo do cliente) para um alias SQL seguro, único na
     * consulta. O id do frontend já vem prefixado (proximoId('j') -> "j_1", "j_2"...),
     * então só prefixamos de novo quando o resultado sanitizado ficaria vazio ou
     * começaria com dígito (identificador SQL inválido) — nunca incondicionalmente,
     * senão "j_1" viraria "j_j_1" e nunca bateria com o alias que o próprio spec usa
     * em campos/filtros qualificados.
     */
    private function aliasSqlDeJoin(string $idCru, array &$usados): string
    {
        $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $idCru) ?? '';
        if ($alias === '' || preg_match('/^[0-9]/', $alias)) {
            $alias = 'j_' . $alias;
        }
        return $this->deduplicarAlias($alias, $usados);
    }

    /**
     * Resolve uma "coluna qualificada" (ex.: "valor_custoEvento", "j_1.valor_custoMkt"
     * ou "calc.cc_1") contra o mapa de aliases desta consulta. Sem ponto -> alias
     * implícito 't' (retrocompatível com specs salvos antes de existir join). Nomes
     * de coluna do schema real nunca têm ponto, então splitar na primeira ocorrência
     * nunca é ambíguo. Alias 'calc' é reservado para colunas calculadas (ver
     * compilarColunasCalculadas) — resolvido contra $colunasCalculadas em vez de
     * validarColuna(), já que não é uma coluna real do schema.
     */
    private function resolverColunaQualificada(string $campoQualificado, array $mapaAliases, array $colunasCalculadas = []): array
    {
        if ($campoQualificado === '') {
            throw new \InvalidArgumentException('Campo vazio.');
        }

        $pos = strpos($campoQualificado, '.');
        $alias  = $pos === false ? 't' : substr($campoQualificado, 0, $pos);
        $coluna = $pos === false ? $campoQualificado : substr($campoQualificado, $pos + 1);

        if ($alias === 'calc') {
            if (!isset($colunasCalculadas[$coluna])) {
                throw new \InvalidArgumentException("Coluna calculada 'calc.{$coluna}' não está definida nesta análise (ou colunas calculadas não podem referenciar outras colunas calculadas).");
            }
            $cc = $colunasCalculadas[$coluna];
            return [
                'alias' => 'calc',
                'coluna' => $coluna,
                'expr' => $cc['sql'],
                'meta' => ['tipo_mysql' => null, 'tipo_logico' => $cc['tipo_logico'], 'nullable' => true],
            ];
        }

        if (!isset($mapaAliases[$alias])) {
            throw new \InvalidArgumentException("Alias de tabela '{$alias}' não existe nesta análise (confira os joins).");
        }

        $meta = $this->validarColuna($mapaAliases[$alias], $coluna);
        return ['alias' => $alias, 'coluna' => $coluna, 'expr' => "{$alias}.`{$coluna}`", 'meta' => $meta];
    }

    /**
     * Monta " LEFT JOIN `custoMkt` j_1 ON t.`identificador_custoEvento` = j_1.`identificador_custoMkt`..."
     * Só suporta join "em estrela": cada condição liga uma coluna da TABELA BASE (sempre
     * alias 't') a uma coluna da tabela deste join — não encadeia join entre duas
     * tabelas já juntadas (fora de escopo desta fase).
     */
    private function montarJoins(string $tabelaBase, array $joinsSpec, array $mapaAliases, array $aliasesPorIndice): array
    {
        $partes = [];
        foreach ($joinsSpec as $i => $joinSpec) {
            $alias = $aliasesPorIndice[$i];
            $tabelaJoin = $mapaAliases[$alias]; // já validada por montarMapaAliases

            $tipo = strtoupper((string)($joinSpec['tipo'] ?? 'LEFT'));
            if (!in_array($tipo, self::TIPOS_JOIN, true)) {
                throw new \InvalidArgumentException("Tipo de join inválido: {$tipo}");
            }

            $condicoes = is_array($joinSpec['condicoes'] ?? null) ? $joinSpec['condicoes'] : [];
            if (!$condicoes) {
                throw new \InvalidArgumentException("Join #{$i} ({$tabelaJoin}) precisa de ao menos uma condição.");
            }

            $onPartes = [];
            foreach ($condicoes as $cond) {
                $campoBase = (string)($cond['campoBase'] ?? '');
                $campoJoin = (string)($cond['campoJoin'] ?? '');
                $this->validarColuna($tabelaBase, $campoBase);
                $this->validarColuna($tabelaJoin, $campoJoin);
                $onPartes[] = "t.`{$campoBase}` = {$alias}.`{$campoJoin}`";
            }

            $partes[] = " {$tipo} JOIN `{$tabelaJoin}` {$alias} ON " . implode(' AND ', $onPartes);
        }
        return ['sql' => implode('', $partes)];
    }

    private function montarSelect(array $spec, array $mapaAliases, array $colunasCalculadas = []): array
    {
        $temAgrupamento = !empty($spec['agrupamento']['linhas']) || !empty($spec['agrupamento']['metricas']);
        $usados = [];
        $partes = [];
        $colunasFinais = [];

        if (!$temAgrupamento) {
            $campos = $spec['campos'] ?? [];
            if (!$campos) {
                throw new \InvalidArgumentException('Selecione ao menos um campo.');
            }
            foreach ($campos as $campo) {
                $r = $this->resolverColunaQualificada((string)$campo, $mapaAliases, $colunasCalculadas);
                $alias = $this->deduplicarAlias($this->sanitizarAlias($r['coluna']), $usados);
                $partes[] = "{$r['expr']} AS `{$alias}`";
                $colunasFinais[] = $alias;
            }
            return ['sql' => implode(', ', $partes), 'colunas' => $colunasFinais];
        }

        foreach ($spec['agrupamento']['linhas'] ?? [] as $campo) {
            $r = $this->resolverColunaQualificada((string)$campo, $mapaAliases, $colunasCalculadas);
            $alias = $this->deduplicarAlias($this->sanitizarAlias($r['coluna']), $usados);
            $partes[] = "{$r['expr']} AS `{$alias}`";
            $colunasFinais[] = $alias;
        }

        $metricas = $this->montarMetricasAgrupamento($spec['agrupamento']['metricas'] ?? [], $mapaAliases, $colunasCalculadas);
        foreach ($metricas['itens'] as $item) {
            $alias = $this->deduplicarAlias($item['alias'], $usados);
            $sqlMetrica = preg_replace('/AS `[^`]+`$/', "AS `{$alias}`", $item['sql']) ?? $item['sql'];
            $partes[] = $sqlMetrica;
            $colunasFinais[] = $alias;
        }
        if (!$partes) {
            throw new \InvalidArgumentException('Defina ao menos uma coluna de agrupamento ou métrica.');
        }
        return ['sql' => implode(', ', $partes), 'colunas' => $colunasFinais];
    }

    private function montarMetrica(array $metrica, array $mapaAliases, array $colunasCalculadas = []): array
    {
        $funcao = strtoupper((string)($metrica['funcao'] ?? ''));
        $campo = (string)($metrica['campo'] ?? '');
        $alias = $this->sanitizarAlias((string)($metrica['alias'] ?? ($funcao . '_' . $campo)));

        if (!in_array($funcao, self::FUNCOES_METRICA, true)) {
            throw new \InvalidArgumentException("Função de métrica inválida: {$funcao}");
        }

        if ($campo === '*') {
            if ($funcao !== 'COUNT') {
                throw new \InvalidArgumentException("Somente COUNT aceita campo '*'.");
            }
            return ['sql' => "COUNT(*) AS `{$alias}`", 'alias' => $alias];
        }

        $r = $this->resolverColunaQualificada($campo, $mapaAliases, $colunasCalculadas);
        if (in_array($funcao, self::FUNCOES_NUMERICAS, true) && $r['meta']['tipo_logico'] !== 'numero') {
            throw new \InvalidArgumentException("Campo '{$campo}' não é numérico, incompatível com {$funcao}.");
        }

        $colExpr = $r['expr'];
        $sqlFuncao = $funcao === 'COUNT_DISTINCT' ? "COUNT(DISTINCT {$colExpr})" : "{$funcao}({$colExpr})";

        return ['sql' => "{$sqlFuncao} AS `{$alias}`", 'alias' => $alias];
    }

    /**
     * Resolve agrupamento.metricas[] com suporte a métricas tipo 'formula' (que
     * combinam OUTRAS métricas já definidas na mesma análise, ex. "m2 / m1").
     * Cada métrica é resolvida sob demanda com memoização + pilha de visitação
     * (detecta referência circular por grafo — não por ordem no array). Métrica
     * tipo 'funcao' (ou sem 'tipo', default retrocompatível) cai em montarMetrica()
     * de sempre; tipo 'formula' tokeniza a expressão (só ids de outras métricas
     * como referência, sem função, sem string) e monta a árvore aritmética via
     * parseExpressaoFormula/gerarSqlNoFormula (com NULLIF automático em toda divisão).
     */
    private function montarMetricasAgrupamento(array $metricasSpec, array $mapaAliases, array $colunasCalculadas = []): array
    {
        // id é opcional (retrocompatível com specs de antes da métrica-fórmula existir,
        // que nunca precisaram de id) — sem ele, sintetiza um a partir da posição no
        // array. Só precisa ser um id "de verdade" quando alguma OUTRA métrica for
        // referenciá-la por fórmula; o frontend atual sempre envia proximoId('m').
        $porId = [];
        foreach ($metricasSpec as $indice => $m) {
            $id = (string)($m['id'] ?? '');
            if ($id === '') {
                $id = '_metrica_' . $indice;
            }
            if (isset($porId[$id])) {
                throw new \InvalidArgumentException("Id de métrica duplicado: '{$id}'.");
            }
            $porId[$id] = $m;
        }

        $resolvidas = [];
        $visitando = [];

        $resolver = function (string $id) use (&$resolver, &$resolvidas, &$visitando, $porId, $mapaAliases, $colunasCalculadas): array {
            if (isset($resolvidas[$id])) {
                return $resolvidas[$id];
            }
            if (!isset($porId[$id])) {
                throw new \InvalidArgumentException("Métrica referenciada não existe: '{$id}'.");
            }
            if (isset($visitando[$id])) {
                throw new \InvalidArgumentException("Referência circular entre métricas envolvendo '{$id}'.");
            }
            $visitando[$id] = true;

            $m = $porId[$id];
            $alias = $this->sanitizarAlias((string)($m['alias'] ?? $id));

            if (($m['tipo'] ?? 'funcao') === 'formula') {
                $tokens = $this->tokenizarExpressao((string)($m['expressao'] ?? ''));
                $idsPermitidos = array_values(array_diff(array_keys($porId), [$id]));
                $this->validarTokensExpressao($tokens, $idsPermitidos, [], [], self::OPERADORES_FORMULA_METRICA, false);
                $cursor = 0;
                $ast = $this->parseExpressaoFormula($tokens, $cursor);
                if ($cursor !== count($tokens)) {
                    throw new \InvalidArgumentException("Fórmula da métrica '{$id}' tem tokens sobrando após a expressão.");
                }
                $sqlAgregado = '(' . $this->gerarSqlNoFormula($ast, $resolver) . ')';
            } else {
                $r = $this->montarMetrica($m, $mapaAliases, $colunasCalculadas);
                $sqlAgregado = preg_replace('/\s+AS\s+`[^`]+`$/i', '', $r['sql']) ?? $r['sql'];
            }

            unset($visitando[$id]);
            return $resolvidas[$id] = ['sqlAgregado' => $sqlAgregado, 'alias' => $alias];
        };

        $itens = [];
        foreach (array_keys($porId) as $id) {
            $r = $resolver($id);
            $itens[] = ['sql' => "{$r['sqlAgregado']} AS `{$r['alias']}`", 'alias' => $r['alias']];
        }
        return ['itens' => $itens];
    }

    private function montarWhere(array $filtros, array $mapaAliases, array $colunasCalculadas = []): array
    {
        $condicoes = [];
        $params = [];
        foreach ($filtros as $filtro) {
            $r = $this->montarFiltro((array)$filtro, $mapaAliases, $colunasCalculadas);
            $condicoes[] = $r['sql'];
            $params = array_merge($params, $r['params']);
        }
        return ['sql' => $condicoes ? implode(' AND ', $condicoes) : '1=1', 'params' => $params];
    }

    private function montarFiltro(array $filtro, array $mapaAliases, array $colunasCalculadas = []): array
    {
        $campo = (string)($filtro['campo'] ?? '');
        $tipo = (string)($filtro['tipo'] ?? '');
        $operador = (string)($filtro['operador'] ?? '');
        $valor = $filtro['valor'] ?? null;

        $colExpr = $this->resolverColunaQualificada($campo, $mapaAliases, $colunasCalculadas)['expr'];

        return match ($tipo) {
            'texto'    => $this->filtroTexto($colExpr, $operador, $valor),
            'numero'   => $this->filtroNumero($colExpr, $operador, $valor),
            'data'     => $this->filtroData($colExpr, $operador, $valor),
            'booleano' => $this->filtroBooleano($colExpr, $operador, $valor),
            default    => throw new \InvalidArgumentException("Tipo de filtro inválido: {$tipo}"),
        };
    }

    private function filtroTexto(string $colExpr, string $operador, mixed $valor): array
    {
        if ($operador === 'lista') {
            $itens = is_array($valor) ? $valor : [$valor];
            $itens = array_values(array_filter($itens, fn($v) => $v !== null && $v !== ''));
            if (!$itens) {
                return ['sql' => '1=0', 'params' => []];
            }
            $placeholders = implode(',', array_fill(0, count($itens), '?'));
            return ['sql' => "{$colExpr} IN ({$placeholders})", 'params' => array_map('strval', $itens)];
        }

        if ($operador === 'preenchido') {
            return ['sql' => "({$colExpr} IS NOT NULL AND {$colExpr} <> '')", 'params' => []];
        }
        if ($operador === 'vazio') {
            return ['sql' => "({$colExpr} IS NULL OR {$colExpr} = '')", 'params' => []];
        }

        $valor = (string)$valor;
        return match ($operador) {
            'contem'      => ['sql' => "{$colExpr} LIKE ?", 'params' => ["%{$valor}%"]],
            // NOT LIKE / <> são null-safe de propósito: um filtro "não contém"/"diferente"
            // deve incluir também os registros em branco (NULL), do contrário um analista
            // que filtra "responsavel <> Fulano" perderia silenciosamente as linhas sem
            // responsável — comportamento que surpreenderia quem vem de Power BI/Excel,
            // onde BLANK() já é tratado como "diferente" de qualquer valor não-vazio.
            'nao_contem'  => ['sql' => "({$colExpr} IS NULL OR {$colExpr} NOT LIKE ?)", 'params' => ["%{$valor}%"]],
            'comeca_com'  => ['sql' => "{$colExpr} LIKE ?", 'params' => ["{$valor}%"]],
            'termina_com' => ['sql' => "{$colExpr} LIKE ?", 'params' => ["%{$valor}"]],
            'igual'       => ['sql' => "{$colExpr} = ?", 'params' => [$valor]],
            'diferente'   => ['sql' => "({$colExpr} IS NULL OR {$colExpr} <> ?)", 'params' => [$valor]],
            default       => throw new \InvalidArgumentException("Operador de texto inválido: {$operador}"),
        };
    }

    private function filtroNumero(string $colExpr, string $operador, mixed $valor): array
    {
        if ($operador === 'preenchido') {
            return ['sql' => "{$colExpr} IS NOT NULL", 'params' => []];
        }
        if ($operador === 'vazio') {
            return ['sql' => "{$colExpr} IS NULL", 'params' => []];
        }
        if ($operador === 'entre') {
            $min = $valor['min'] ?? null;
            $max = $valor['max'] ?? null;
            if ($min === null || $max === null) {
                throw new \InvalidArgumentException("Operador 'entre' exige valor.min e valor.max.");
            }
            return ['sql' => "{$colExpr} BETWEEN ? AND ?", 'params' => [$min, $max]];
        }

        return match ($operador) {
            'maior'     => ['sql' => "{$colExpr} > ?", 'params' => [$valor]],
            'menor'     => ['sql' => "{$colExpr} < ?", 'params' => [$valor]],
            'igual'     => ['sql' => "{$colExpr} = ?", 'params' => [$valor]],
            // null-safe pelo mesmo motivo do filtroTexto: "diferente" não deve descartar em branco.
            'diferente' => ['sql' => "({$colExpr} IS NULL OR {$colExpr} <> ?)", 'params' => [$valor]],
            default     => throw new \InvalidArgumentException("Operador numérico inválido: {$operador}"),
        };
    }

    private function filtroData(string $colExpr, string $operador, mixed $valor): array
    {
        // '0000-00-00' é a convenção do banco para "sem data" (equivalente a BLANK no
        // DAX) — IS NOT NULL sozinho não pega isso, por isso os dois lados do check.
        if ($operador === 'preenchido') {
            return ['sql' => "({$colExpr} IS NOT NULL AND {$colExpr} > '0000-00-00')", 'params' => []];
        }
        if ($operador === 'vazio') {
            return ['sql' => "({$colExpr} IS NULL OR {$colExpr} <= '0000-00-00')", 'params' => []];
        }
        if ($operador === 'intervalo') {
            $inicio = (string)($valor['inicio'] ?? '');
            $fim = (string)($valor['fim'] ?? '');
            if ($inicio === '' || $fim === '') {
                throw new \InvalidArgumentException("Operador 'intervalo' exige valor.inicio e valor.fim.");
            }
            return ['sql' => "{$colExpr} BETWEEN ? AND ?", 'params' => ["{$inicio} 00:00:00", "{$fim} 23:59:59"]];
        }

        // Fase 1C.4 (escopo reduzido — biexplorer é query-builder livre, não
        // dashboard fixo): hoje/ontem/este_mes/este_ano viram range literal
        // sargável em vez de DATE()/YEAR()/MONTH() envolvendo a coluna, mesmo
        // princípio já aplicado nos dashboards fixos. 'esta_semana' mantida
        // como estava — limites de semana ISO são mais complexos de calcular
        // certo e o preset é usado com pouca frequência; não compensa a
        // complexidade extra neste escopo reduzido.
        $hoje = date('Y-m-d');
        switch ($operador) {
            case 'hoje':
                return ['sql' => "({$colExpr} >= ? AND {$colExpr} < ?)", 'params' => [
                    "$hoje 00:00:00",
                    date('Y-m-d 00:00:00', strtotime("$hoje +1 day")),
                ]];
            case 'ontem':
                return ['sql' => "({$colExpr} >= ? AND {$colExpr} < ?)", 'params' => [
                    date('Y-m-d 00:00:00', strtotime("$hoje -1 day")),
                    "$hoje 00:00:00",
                ]];
            case 'este_mes':
                $inicioMes = date('Y-m-01', strtotime($hoje));
                return ['sql' => "({$colExpr} >= ? AND {$colExpr} < ?)", 'params' => [
                    "$inicioMes 00:00:00",
                    date('Y-m-01 00:00:00', strtotime("$inicioMes +1 month")),
                ]];
            case 'este_ano':
                $inicioAno = date('Y-01-01', strtotime($hoje));
                return ['sql' => "({$colExpr} >= ? AND {$colExpr} < ?)", 'params' => [
                    "$inicioAno 00:00:00",
                    date('Y-01-01 00:00:00', strtotime("$inicioAno +1 year")),
                ]];
        }

        return match ($operador) {
            'esta_semana' => ['sql' => "YEARWEEK({$colExpr}, 3) = YEARWEEK(CURDATE(), 3)", 'params' => []],
            default       => throw new \InvalidArgumentException("Operador de data inválido: {$operador}"),
        };
    }

    private function filtroBooleano(string $colExpr, string $operador, mixed $valor): array
    {
        return match ($operador) {
            'sim'        => ['sql' => "{$colExpr} = ?", 'params' => ['1']],
            'nao'        => ['sql' => "{$colExpr} = ?", 'params' => ['0']],
            'preenchido' => ['sql' => "{$colExpr} IS NOT NULL", 'params' => []],
            'vazio'      => ['sql' => "{$colExpr} IS NULL", 'params' => []],
            default => throw new \InvalidArgumentException("Operador booleano inválido: {$operador}"),
        };
    }

    private function montarGroupBy(array $spec, array $mapaAliases, array $colunasCalculadas = []): string
    {
        $linhas = $spec['agrupamento']['linhas'] ?? [];
        if (!$linhas) {
            return '';
        }
        $cols = array_map(
            fn($c) => $this->resolverColunaQualificada((string)$c, $mapaAliases, $colunasCalculadas)['expr'],
            $linhas
        );
        return 'GROUP BY ' . implode(', ', $cols);
    }

    private function montarOrderBy(array $ordenacao, array $colunasDisponiveis): string
    {
        $partes = [];
        foreach ($ordenacao as $item) {
            $campo = (string)($item['campo'] ?? '');
            if (!in_array($campo, $colunasDisponiveis, true)) {
                continue; // ignora silenciosamente ordenação por coluna fora do SELECT atual
            }
            $direcao = strtoupper((string)($item['direcao'] ?? 'ASC'));
            $direcao = in_array($direcao, ['ASC', 'DESC'], true) ? $direcao : 'ASC';
            $partes[] = "`{$campo}` {$direcao}";
        }
        return $partes ? 'ORDER BY ' . implode(', ', $partes) : '';
    }

    private function montarLimit(?int $limite): array
    {
        $n = $limite ?? self::LIMITE_PADRAO_PREVIEW;
        $n = max(1, min($n, self::LIMITE_MAX_PREVIEW));
        return ['sql' => 'LIMIT ?', 'params' => [$n], 'valor' => $n];
    }

    private function sanitizarAlias(string $alias): string
    {
        $alias = preg_replace('/[^a-zA-Z0-9_]/', '_', $alias) ?? '';
        $alias = trim($alias, '_');
        if ($alias === '') {
            $alias = 'col';
        }
        return substr($alias, 0, 64);
    }

    private function deduplicarAlias(string $alias, array &$usados): string
    {
        $base = $alias;
        $i = 2;
        while (isset($usados[$alias])) {
            $alias = $base . '_' . $i;
            $i++;
        }
        $usados[$alias] = true;
        return $alias;
    }

    // =====================================================================
    // Expressões — colunas calculadas e métricas por fórmula
    //
    // Princípio: nunca interpolar o texto do usuário direto no SQL. Tokeniza ->
    // cada token é classificado contra uma whitelist fechada -> toda referência
    // (nome de coluna ou id de métrica) é validada pelo mesmo caminho que já
    // protege filtros/campos hoje (resolverColunaQualificada) ou pelo conjunto
    // fechado de ids de métrica já existentes na análise. O SQL final é sempre
    // concatenação de pedaços já filtrados um a um — nunca uma substring bruta
    // do que foi digitado.
    // =====================================================================

    /**
     * Tokeniza uma expressão livre digitada pelo usuário. NÃO entende gramática
     * (não sabe se "CASE" precisa de "WHEN" depois) — só quebra a string em
     * pedaços seguros e classifica cada um. Qualquer caractere fora da whitelist
     * (aspas duplas, ';', backtick, '@', '#', comentário --/#, etc.) lança
     * exceção imediatamente — inclusive dentro de uma string ou de um CASE,
     * já que nenhum desses símbolos existe em nenhuma das alternativas do regex.
     *
     * @return array<int, array{tipo:string, valor:string, pos:int}>
     */
    private function tokenizarExpressao(string $expr): array
    {
        $expr = trim($expr);
        if ($expr === '') {
            throw new \InvalidArgumentException('Expressão vazia.');
        }
        if (mb_strlen($expr) > self::MAX_TAMANHO_EXPRESSAO) {
            throw new \InvalidArgumentException('Expressão excede o tamanho máximo permitido (' . self::MAX_TAMANHO_EXPRESSAO . ' caracteres).');
        }

        $padrao = '/\G\s*(?:'
            . "(?<STR>'(?:[^'\\\\]|\\\\.)*')"
            . '|(?<NUM>\d+(?:\.\d+)?)'
            . '|(?<IDENT>[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)?)'
            . '|(?<OP><=|>=|<>|!=|=|<|>|\+|-|\*|\/)'
            . '|(?<LP>\()'
            . '|(?<RP>\))'
            . '|(?<COMMA>,)'
            . ')\s*/';

        $tokens = [];
        $pos = 0;
        $len = strlen($expr);
        while ($pos < $len) {
            if (!preg_match($padrao, $expr, $m, 0, $pos)) {
                $trecho = substr($expr, $pos, 20);
                throw new \InvalidArgumentException("Caractere ou token não reconhecido na expressão, próximo a: \"{$trecho}\".");
            }
            // Grupos nomeados não-participantes de uma alternância podem não aparecer
            // em $m — sempre checar com isset() antes de comparar com ''.
            if (isset($m['STR']) && $m['STR'] !== '') {
                $tokens[] = ['tipo' => 'string', 'valor' => $m['STR'], 'pos' => $pos];
            } elseif (isset($m['NUM']) && $m['NUM'] !== '') {
                $tokens[] = ['tipo' => 'numero', 'valor' => $m['NUM'], 'pos' => $pos];
            } elseif (isset($m['IDENT']) && $m['IDENT'] !== '') {
                $tokens[] = ['tipo' => 'identificador', 'valor' => $m['IDENT'], 'pos' => $pos];
            } elseif (isset($m['OP']) && $m['OP'] !== '') {
                $tokens[] = ['tipo' => 'operador', 'valor' => $m['OP'], 'pos' => $pos];
            } elseif (isset($m['LP']) && $m['LP'] !== '') {
                $tokens[] = ['tipo' => 'parenese_abre', 'valor' => '(', 'pos' => $pos];
            } elseif (isset($m['RP']) && $m['RP'] !== '') {
                $tokens[] = ['tipo' => 'parenese_fecha', 'valor' => ')', 'pos' => $pos];
            } elseif (isset($m['COMMA']) && $m['COMMA'] !== '') {
                $tokens[] = ['tipo' => 'virgula', 'valor' => ',', 'pos' => $pos];
            } else {
                break; // não deveria ocorrer; guarda contra loop infinito
            }
            $pos += strlen($m[0]);
        }

        if (count($tokens) > self::MAX_TOKENS_EXPRESSAO) {
            throw new \InvalidArgumentException('Expressão excede o número máximo de tokens permitido.');
        }
        return $tokens;
    }

    /**
     * Valida a ESTRUTURA de tokens já classificados: parênteses balanceados e
     * cada identificador é OU (a) nome de função permitida (deve ser seguido de
     * "(") OU (b) palavra-chave permitida OU (c) uma referência — quando
     * $identificadoresPermitidos não é null, toda referência precisa estar
     * nessa lista fechada; se for null, fica para o chamador validar de verdade
     * (caso das colunas calculadas, via resolverColunaQualificada). NÃO valida
     * "sentido" da expressão (não garante WHEN depois de CASE nem aridade de
     * função) — isso o MySQL rejeita em runtime, virando erro 400 do preview,
     * nunca uma brecha.
     *
     * @param array<int,array{tipo:string,valor:string,pos:int}> $tokens
     * @param array<int,string>|null $identificadoresPermitidos
     * @param array<int,string> $funcoesPermitidas
     * @param array<int,string> $palavrasChavePermitidas
     * @param array<int,string>|null $operadoresPermitidos null = todos os reconhecidos pelo tokenizer
     * @param bool $permitirStrings false = fórmula de métrica não aceita literal de texto
     * @return array<int,int> índices em $tokens que são referências (não função/palavra-chave)
     */
    private function validarTokensExpressao(
        array $tokens,
        ?array $identificadoresPermitidos,
        array $funcoesPermitidas,
        array $palavrasChavePermitidas = [],
        ?array $operadoresPermitidos = null,
        bool $permitirStrings = true
    ): array {
        if (!$tokens) {
            throw new \InvalidArgumentException('Expressão vazia.');
        }

        $funcoesUpper = array_map('strtoupper', $funcoesPermitidas);
        $palavrasUpper = array_map('strtoupper', $palavrasChavePermitidas);
        $profundidade = 0;
        $indicesReferencia = [];

        foreach ($tokens as $i => $tok) {
            if ($tok['tipo'] === 'parenese_abre') {
                if (++$profundidade > self::MAX_PROFUNDIDADE_PARENTESES) {
                    throw new \InvalidArgumentException('Expressão com aninhamento de parênteses excessivo.');
                }
                continue;
            }
            if ($tok['tipo'] === 'parenese_fecha') {
                if (--$profundidade < 0) {
                    throw new \InvalidArgumentException('Parênteses desbalanceados na expressão (fechamento sem abertura correspondente).');
                }
                continue;
            }
            if ($tok['tipo'] === 'string' && !$permitirStrings) {
                throw new \InvalidArgumentException("Literal de texto não permitido nesta expressão: {$tok['valor']}.");
            }
            if ($tok['tipo'] === 'operador' && $operadoresPermitidos !== null && !in_array($tok['valor'], $operadoresPermitidos, true)) {
                throw new \InvalidArgumentException("Operador não permitido nesta expressão: '{$tok['valor']}'.");
            }
            if ($tok['tipo'] !== 'identificador') {
                continue;
            }

            $valorUpper = strtoupper($tok['valor']);
            $proximo = $tokens[$i + 1] ?? null;
            $seguidoDeParenese = $proximo !== null && $proximo['tipo'] === 'parenese_abre';

            if ($seguidoDeParenese) {
                if (!in_array($valorUpper, $funcoesUpper, true)) {
                    throw new \InvalidArgumentException("Função não permitida na expressão: '{$tok['valor']}'.");
                }
                continue;
            }
            if (in_array($valorUpper, $palavrasUpper, true)) {
                continue;
            }
            if ($identificadoresPermitidos !== null && !in_array($tok['valor'], $identificadoresPermitidos, true)) {
                throw new \InvalidArgumentException("Referência desconhecida na expressão: '{$tok['valor']}'.");
            }
            $indicesReferencia[] = $i;
        }

        if ($profundidade !== 0) {
            throw new \InvalidArgumentException('Parênteses desbalanceados na expressão (abertura sem fechamento correspondente).');
        }
        return $indicesReferencia;
    }

    /**
     * Compila spec['colunasCalculadas'] num mapa id -> definição pronta pra ser
     * usada por resolverColunaQualificada() via alias reservado 'calc'. Cada
     * expressão só pode referenciar colunas reais (t.coluna ou j_N.coluna) — nunca outra
     * coluna calculada (terceiro argumento vazio na chamada de
     * resolverColunaQualificada abaixo é proposital: mantém o grafo de
     * dependência raso por construção, sem precisar de detecção de ciclo aqui).
     *
     * @return array<string, array{sql:string, tipo_logico:string, nome:string}>
     */
    private function compilarColunasCalculadas(array $spec, array $mapaAliases): array
    {
        $defs = is_array($spec['colunasCalculadas'] ?? null) ? $spec['colunasCalculadas'] : [];
        $compiladas = [];

        foreach ($defs as $def) {
            $id = (string)($def['id'] ?? '');
            if ($id === '') {
                throw new \InvalidArgumentException('Coluna calculada sem id.');
            }
            if (isset($compiladas[$id])) {
                throw new \InvalidArgumentException("Id de coluna calculada duplicado: '{$id}'.");
            }

            $nome = (string)($def['nome'] ?? $id);
            $tipoResultado = (string)($def['tipoResultado'] ?? '');
            if (!in_array($tipoResultado, self::TIPOS_RESULTADO_COLUNA_CALCULADA, true)) {
                throw new \InvalidArgumentException("Coluna calculada '{$nome}': tipoResultado inválido.");
            }

            $tokens = $this->tokenizarExpressao((string)($def['expressao'] ?? ''));
            $refs = $this->validarTokensExpressao($tokens, null, self::FUNCOES_COLUNA_CALCULADA, self::PALAVRAS_CHAVE_EXPRESSAO);

            $sql = '';
            foreach ($tokens as $i => $tok) {
                if (in_array($i, $refs, true)) {
                    $sql .= $this->resolverColunaQualificada($tok['valor'], $mapaAliases, [])['expr'];
                } elseif ($tok['tipo'] === 'identificador') {
                    $sql .= strtoupper($tok['valor']); // função (DATEDIFF/IF/...) ou palavra-chave (CASE/WHEN/...)
                } else {
                    $sql .= $tok['valor']; // número, string (já entre aspas simples), operador, parênteses, vírgula
                }
                $sql .= ' ';
            }

            $compiladas[$id] = ['sql' => '(' . trim($sql) . ')', 'tipo_logico' => $tipoResultado, 'nome' => $nome];
        }

        return $compiladas;
    }

    // ── Métricas por fórmula ────────────────────────────────────────────────

    /** Nó da árvore de uma fórmula de métrica: {tipo:'num'|'ref'|'bin', ...}. */
    private function parseExpressaoFormula(array $tokens, int &$i): array
    {
        $no = $this->parseTermoFormula($tokens, $i);
        while (isset($tokens[$i]) && $tokens[$i]['tipo'] === 'operador' && in_array($tokens[$i]['valor'], ['+', '-'], true)) {
            $op = $tokens[$i]['valor'];
            $i++;
            $dir = $this->parseTermoFormula($tokens, $i);
            $no = ['tipo' => 'bin', 'op' => $op, 'esq' => $no, 'dir' => $dir];
        }
        return $no;
    }

    private function parseTermoFormula(array $tokens, int &$i): array
    {
        $no = $this->parseFatorFormula($tokens, $i);
        while (isset($tokens[$i]) && $tokens[$i]['tipo'] === 'operador' && in_array($tokens[$i]['valor'], ['*', '/'], true)) {
            $op = $tokens[$i]['valor'];
            $i++;
            $dir = $this->parseFatorFormula($tokens, $i);
            $no = ['tipo' => 'bin', 'op' => $op, 'esq' => $no, 'dir' => $dir];
        }
        return $no;
    }

    private function parseFatorFormula(array $tokens, int &$i): array
    {
        $tok = $tokens[$i] ?? null;
        if ($tok === null) {
            throw new \InvalidArgumentException('Expressão incompleta (fim inesperado).');
        }

        if ($tok['tipo'] === 'operador' && $tok['valor'] === '-') { // unário
            $i++;
            return ['tipo' => 'bin', 'op' => '-', 'esq' => ['tipo' => 'num', 'valor' => '0'], 'dir' => $this->parseFatorFormula($tokens, $i)];
        }
        if ($tok['tipo'] === 'parenese_abre') {
            $i++;
            $no = $this->parseExpressaoFormula($tokens, $i);
            if (!isset($tokens[$i]) || $tokens[$i]['tipo'] !== 'parenese_fecha') {
                throw new \InvalidArgumentException('Parêntese não fechado na fórmula.');
            }
            $i++;
            return $no;
        }
        if ($tok['tipo'] === 'numero') {
            $i++;
            return ['tipo' => 'num', 'valor' => $tok['valor']];
        }
        if ($tok['tipo'] === 'identificador') {
            $i++;
            return ['tipo' => 'ref', 'valor' => $tok['valor']];
        }

        throw new \InvalidArgumentException("Token inesperado na fórmula: '{$tok['valor']}'.");
    }

    /** Gera o SQL a partir da árvore; todo denominador de '/' vira NULLIF(dir, 0) automaticamente. */
    private function gerarSqlNoFormula(array $no, callable $resolver): string
    {
        if ($no['tipo'] === 'num') {
            return $no['valor'];
        }
        if ($no['tipo'] === 'ref') {
            return $resolver($no['valor'])['sqlAgregado'];
        }
        $esq = $this->gerarSqlNoFormula($no['esq'], $resolver);
        $dir = $this->gerarSqlNoFormula($no['dir'], $resolver);
        if ($no['op'] === '/') {
            $dir = "NULLIF({$dir}, 0)";
        }
        return "({$esq} {$no['op']} {$dir})";
    }

    // =====================================================================
    // Introspecção de schema (cache em arquivo)
    // =====================================================================

    private function schemaCache(bool $forcar = false): array
    {
        $caminho = __DIR__ . '/../.mrphp/cache/biexplorer_schema.json';

        if (!$forcar) {
            $dados = $this->lerJson($caminho);
            if ($dados && isset($dados['atualizado_em']) && (time() - $dados['atualizado_em']) < self::SCHEMA_CACHE_TTL) {
                return $dados;
            }
        }

        $tabelas = [];
        $colunas = [];

        $resultadoTabelas = $this->db->query('SHOW FULL TABLES');
        while ($resultadoTabelas && ($linha = $resultadoTabelas->fetch_array(MYSQLI_NUM))) {
            $nome = $linha[0];
            $tipo = ($linha[1] ?? '') === 'VIEW' ? 'view' : 'tabela';
            $tabelas[] = ['nome' => $nome, 'tipo' => $tipo];

            $mapaColunas = [];
            $resultadoColunas = $this->db->query('SHOW COLUMNS FROM `' . $nome . '`');
            while ($resultadoColunas && ($col = $resultadoColunas->fetch_assoc())) {
                $mapaColunas[$col['Field']] = [
                    'tipo_mysql'  => $col['Type'],
                    'tipo_logico' => $this->tipoLogico($col['Type']),
                    'nullable'    => $col['Null'] === 'YES',
                ];
            }
            $colunas[$nome] = $mapaColunas;
        }

        $dados = ['atualizado_em' => time(), 'tabelas' => $tabelas, 'colunas' => $colunas];
        $this->escreverJsonAtomico($caminho, $dados);
        return $dados;
    }

    private function schemaTabelas(): array
    {
        return $this->schemaCache()['tabelas'] ?? [];
    }

    private function schemaColunas(string $tabela): array
    {
        return $this->schemaCache()['colunas'][$tabela] ?? [];
    }

    private function validarTabela(string $tabela): string
    {
        if ($tabela === '') {
            throw new \InvalidArgumentException('Informe uma tabela.');
        }
        $bloqueadas = $this->tabelasBloqueadas();
        foreach ($this->schemaTabelas() as $t) {
            if ($t['nome'] === $tabela) {
                if (in_array($tabela, $bloqueadas, true)) {
                    throw new \InvalidArgumentException("Tabela '{$tabela}' não está disponível para exploração.");
                }
                return $tabela;
            }
        }
        throw new \InvalidArgumentException("Tabela '{$tabela}' não existe.");
    }

    private function validarColuna(string $tabela, string $coluna): array
    {
        $colunas = $this->schemaColunas($tabela);
        if (!isset($colunas[$coluna])) {
            throw new \InvalidArgumentException("Coluna '{$coluna}' não existe na tabela '{$tabela}'.");
        }
        return $colunas[$coluna];
    }

    private function tipoLogico(string $tipoMysql): string
    {
        $tipo = strtolower($tipoMysql);
        if (str_starts_with($tipo, 'tinyint(1)')) {
            return 'booleano';
        }
        if (preg_match('/^(int|bigint|smallint|mediumint|decimal|float|double|numeric)\b/', $tipo)) {
            return 'numero';
        }
        if (preg_match('/^(date|datetime|timestamp|year)\b/', $tipo)) {
            return 'data';
        }
        return 'texto';
    }

    /** Blacklist opcional via .env (BI_EXPLORER_TABELAS_BLOQUEADAS=a,b,c) — não coberta por loadConfigurations(). */
    private function tabelasBloqueadas(): array
    {
        $valor = $this->lerEnvBruto('BI_EXPLORER_TABELAS_BLOQUEADAS');
        if (!$valor) {
            return [];
        }
        return array_map('trim', explode(',', $valor));
    }

    /** Mesmo padrão de controle/sql_controle.php::getEnvToken() — leitura direta do .env. */
    private function lerEnvBruto(string $chave): ?string
    {
        $path = __DIR__ . '/../.env';
        if (!file_exists($path)) {
            return null;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $nome = trim(substr($line, 0, $pos));
            if ($nome === $chave) {
                return trim(trim(substr($line, $pos + 1)), '"\'');
            }
        }
        return null;
    }

    // =====================================================================
    // Acesso a banco / respostas JSON (mesmo padrão dos demais controllers)
    // =====================================================================

    private function queryAll(string $sql, array $params = [], string $tipos = ''): array
    {
        $stmt = $this->db->prepare($sql);
        if ($params) {
            $tipos = $tipos !== '' ? $tipos : str_repeat('s', count($params));
            $stmt->bind_param($tipos, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function jsonOut($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function jsonError(\Throwable $e, int $status = 500): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        $isDev = strtoupper($this->config['app']['env'] ?? '') === 'DEV';
        $payload = ['error' => $e->getMessage()];
        if ($isDev) {
            $payload['file'] = $e->getFile();
            $payload['line'] = $e->getLine();
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
