<?php

/**
 * API de debug — executa SQL somente-leitura via curl contra qualquer conexão
 * configurada no .env (default, 2, ...). Protegida por token dedicado
 * (SQL_API_TOKEN no .env) independente do gate global API_MODE/API_USER,
 * já que este endpoint expõe execução de queries brutas no banco de produção.
 */
class sql extends MRPHP
{
    public function query()
    {
        try {
            $this->rateLimit(60, 60);
            $this->autenticar();

            $sqlQuery   = trim((string)($_REQUEST['sql'] ?? ''));
            $connection = (string)($_REQUEST['connection'] ?? 'default');

            $erro = $this->validarSomenteLeitura($sqlQuery);
            if ($erro !== null) {
                $this->jsonOut(['error' => $erro], 400);
            }

            try {
                $db = ($connection === 'default') ? $this->db : $this->connectDatabase($connection);
            } catch (\Throwable $e) {
                $this->jsonOut(['error' => "Conexão '$connection' não configurada."], 400);
            }
            if (!$db) {
                $this->jsonOut(['error' => "Conexão '$connection' não configurada."], 400);
            }

            // mysqli lança \mysqli_sql_exception em erro (modo padrão desde PHP 8.1),
            // em vez de retornar false — captura aqui para responder 400 em vez de 500.
            $inicio = microtime(true);
            try {
                $result = $db->query($sqlQuery);
            } catch (\mysqli_sql_exception $e) {
                $tempoMs = round((microtime(true) - $inicio) * 1000, 2);
                $this->jsonOut(['error' => $e->getMessage(), 'sql' => $sqlQuery, 'tempo_ms' => $tempoMs], 400);
            }
            $tempoMs = round((microtime(true) - $inicio) * 1000, 2);

            if ($result === false) {
                $this->jsonOut(['error' => $db->error, 'sql' => $sqlQuery, 'tempo_ms' => $tempoMs], 400);
            }

            $rows = ($result instanceof \mysqli_result) ? $result->fetch_all(MYSQLI_ASSOC) : [];
            $this->jsonOut([
                'connection' => $connection,
                'sql'        => $sqlQuery,
                'tempo_ms'   => $tempoMs,
                'linhas'     => count($rows),
                'resultado'  => $rows,
            ]);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    private function autenticar(): void
    {
        $token = $this->getEnvToken();
        if (empty($token)) {
            $this->jsonOut(['error' => 'SQL_API_TOKEN não configurado no .env'], 500);
        }

        $enviado = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($enviado, 'Bearer ')) {
            $enviado = substr($enviado, 7);
        } elseif ($enviado === '') {
            $enviado = (string)($_REQUEST['token'] ?? '');
        }

        if (!hash_equals((string)$token, (string)$enviado)) {
            $this->jsonOut(['error' => 'Acesso negado'], 401);
        }
    }

    private function getEnvToken(): ?string
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
            $name = trim(substr($line, 0, $pos));
            if ($name === 'SQL_API_TOKEN') {
                return trim(trim(substr($line, $pos + 1)), '"\'');
            }
        }

        return null;
    }

    private function validarSomenteLeitura(string $sql): ?string
    {
        $sql = trim($sql);
        if ($sql === '') {
            return 'Query vazia.';
        }

        // bloqueia múltiplas instruções (permite só ; opcional no final)
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

    private function jsonOut(array $data, int $status = 200): void
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
