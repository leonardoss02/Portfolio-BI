<?php

/**
 * Tudo que fala com o MariaDB: ler o .env, conectar, recriar o schema e
 * gravar a Base em lote.
 */
final class Banco
{
    /** Lê um arquivo .env no mesmo formato que o mrphp.php entende. */
    public static function lerEnv(string $arquivo): array
    {
        if (!is_file($arquivo)) {
            throw new RuntimeException("Arquivo .env não encontrado: $arquivo");
        }
        $env = [];
        foreach (file($arquivo, FILE_IGNORE_NEW_LINES) as $linha) {
            $linha = trim($linha);
            if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
                continue;
            }
            [$nome, $valor] = explode('=', $linha, 2);
            $env[trim($nome)] = trim(trim($valor), "\"'");
        }
        return $env;
    }

    /** Conecta com as credenciais DB_DEFAULT_*. Espera o banco subir (até 30 s). */
    public static function conectar(array $env): mysqli
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $ultimoErro = null;
        for ($tentativa = 1; $tentativa <= 30; $tentativa++) {
            try {
                $db = new mysqli(
                    $env['DB_DEFAULT_HOST'] ?? '',
                    $env['DB_DEFAULT_USER'] ?? '',
                    $env['DB_DEFAULT_PASS'] ?? '',
                    $env['DB_DEFAULT_NAME'] ?? ''
                );
                $db->set_charset($env['DB_DEFAULT_CHARSET'] ?? 'utf8mb4');
                return $db;
            } catch (mysqli_sql_exception $e) {
                $ultimoErro = $e;
                sleep(1);
            }
        }
        throw new RuntimeException('Não foi possível conectar ao banco: ' . $ultimoErro?->getMessage());
    }

    /** Executa o schema.sql inteiro (que derruba e recria as tabelas). */
    public static function aplicarSchema(mysqli $db, string $arquivoSql): void
    {
        $db->multi_query(file_get_contents($arquivoSql));
        do {
            if ($resultado = $db->store_result()) {
                $resultado->free();
            }
        } while ($db->more_results() && $db->next_result());
    }

    /** Grava todas as tabelas da Base, em lotes, dentro de uma transação. */
    public static function gravar(mysqli $db, Base $base, int $lote = 500): void
    {
        $db->query('SET unique_checks = 0');
        $db->begin_transaction();
        foreach ($base->tabelas() as $tabela) {
            foreach (array_chunk($base->linhas($tabela), $lote) as $linhas) {
                $db->query(self::montarInsert($tabela, $linhas));
            }
        }
        $db->commit();
        $db->query('SET unique_checks = 1');
    }

    /**
     * Monta um INSERT de várias linhas. As colunas são a união das chaves de
     * todas as linhas (sem as que começam com "_"); chave ausente vira NULL.
     */
    public static function montarInsert(string $tabela, array $linhas): string
    {
        $colunas = [];
        foreach ($linhas as $linha) {
            foreach (array_keys($linha) as $coluna) {
                if (!str_starts_with($coluna, '_')) {
                    $colunas[$coluna] = true;
                }
            }
        }
        $colunas = array_keys($colunas);

        $valores = [];
        foreach ($linhas as $linha) {
            $campos = [];
            foreach ($colunas as $coluna) {
                $campos[] = self::literal($linha[$coluna] ?? null);
            }
            $valores[] = '(' . implode(',', $campos) . ')';
        }

        $listaColunas = implode(',', array_map(fn($c) => "`$c`", $colunas));
        return "INSERT INTO `$tabela` ($listaColunas) VALUES " . implode(',', $valores);
    }

    /** Apaga o cache JSON dos dashboards, para não mostrar números antigos. */
    public static function limparCache(string $dirCache): int
    {
        $apagados = 0;
        foreach (glob(rtrim($dirCache, '/') . '/*.json') ?: [] as $arquivo) {
            if (@unlink($arquivo)) {
                $apagados++;
            }
        }
        return $apagados;
    }

    private static function literal(mixed $valor): string
    {
        return match (true) {
            $valor === null => 'NULL',
            is_bool($valor) => $valor ? '1' : '0',
            is_int($valor) => (string) $valor,
            is_float($valor) => rtrim(rtrim(number_format($valor, 6, '.', ''), '0'), '.'),
            $valor instanceof DateTimeInterface => "'" . $valor->format('Y-m-d H:i:s') . "'",
            default => "'" . strtr((string) $valor, [
                '\\' => '\\\\', "'" => "\\'", "\0" => '\\0', "\n" => '\\n', "\r" => '\\r', "\x1a" => '\\Z',
            ]) . "'",
        };
    }
}
