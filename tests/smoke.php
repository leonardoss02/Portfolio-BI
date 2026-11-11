<?php
/**
 * Teste de fumaça: visita todas as telas e endpoints do projeto rodando e
 * confere se respondem e se as métricas principais têm números.
 *
 * Uso (dentro do container):
 *   docker compose exec app php tests/smoke.php
 *   docker compose exec app php tests/smoke.php --hoje=2027-03-15 --apenas=envelhecimento
 *
 * Opções:
 *   --hoje=AAAA-MM-DD  data usada nos filtros (padrão: hoje em America/Sao_Paulo)
 *   --base=URL         onde o app responde (padrão: http://localhost)
 *   --apenas=grupo     roda só as entradas daquele grupo
 *
 * Rode com o app em modo DEV (docker/app.dev.env): assim qualquer warning do
 * PHP vira erro 500 e aparece aqui.
 */

declare(strict_types=1);

// Script de linha de comando: pela web, responde 404 e não executa nada.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$opcoes = getopt('', ['hoje:', 'base:', 'apenas:']);
$fuso = new DateTimeZone('America/Sao_Paulo');
$hoje = new DateTimeImmutable($opcoes['hoje'] ?? 'today', $fuso);
$base = rtrim($opcoes['base'] ?? 'http://localhost', '/');
$apenas = $opcoes['apenas'] ?? null;

$placeholders = [
    '{ano}' => $hoje->format('Y'),
    '{mes}' => $hoje->format('n'),
    '{hoje}' => $hoje->format('Y-m-d'),
    '{inicioMes}' => $hoje->format('Y-m-01'),
    '{inicioAno}' => $hoje->format('Y-01-01'),
    '{ha29dias}' => $hoje->modify('-29 days')->format('Y-m-d'),
    '{anoAnterior}' => (string) ((int) $hoje->format('Y') - 1),
];

$entradas = require __DIR__ . '/smoke/endpoints.php';

$passou = 0;
$falhas = [];
foreach ($entradas as $entrada) {
    $grupo = $entrada['grupo'] ?? null;
    if ($grupo !== $apenas) {
        continue;
    }
    $url = strtr($entrada['url'], $placeholders);
    [$status, $corpo] = requisitar($base . $url);

    $problema = null;
    $resumo = '';
    if ($entrada['tipo'] === 'bloqueado') {
        // Arquivos internos (scripts de linha de comando, .env, schema) não podem ser servidos pela web.
        if (!in_array($status, [403, 404], true)) {
            $problema = "deveria estar bloqueado, mas respondeu HTTP $status";
        }
    } elseif ($status !== 200) {
        $problema = "HTTP $status" . trechoDeErro($corpo);
    } elseif ($entrada['tipo'] === 'json') {
        $dados = json_decode($corpo, true);
        if (!is_array($dados)) {
            $problema = 'resposta não é JSON' . trechoDeErro($corpo);
        } elseif (!empty($dados['error']) || !empty($dados['erro'])) {
            $problema = 'JSON com erro: ' . json_encode($dados['error'] ?? $dados['erro'], JSON_UNESCAPED_UNICODE);
        } else {
            foreach ($entrada['exigir'] ?? [] as $caminho => $condicoes) {
                $caminho = (string) $caminho; // o PHP transforma a chave '0' em inteiro
                $valor = valorNoCaminho($dados, $caminho);
                $resumo .= " $caminho=" . resumirValor($valor);
                foreach ((array) $condicoes as $condicao) {
                    if (!atende($valor, $condicao)) {
                        $problema = "$caminho não atende '$condicao' (valor: " . resumirValor($valor) . ')';
                        break 2;
                    }
                }
            }
        }
    } elseif (isset($entrada['contem']) && !str_contains($corpo, $entrada['contem'])) {
        $problema = "a página não contém '{$entrada['contem']}'";
    } elseif (isset($entrada['contemRegex']) && !preg_match($entrada['contemRegex'], $corpo)) {
        $problema = "a página não satisfaz {$entrada['contemRegex']}";
    }

    if ($problema === null) {
        echo "✔ $url$resumo\n";
        $passou++;
    } else {
        echo "✘ $url — $problema\n";
        $falhas[] = $url;
    }
}

echo "\n$passou passaram, " . count($falhas) . " falharam\n";
exit($falhas ? 1 : 0);

/** @return array{int, string} status HTTP e corpo */
function requisitar(string $url): array
{
    $contexto = stream_context_create(['http' => [
        'header' => 'Authorization: Basic ' . base64_encode('demo:demo'),
        'ignore_errors' => true,
        'timeout' => 120,
    ]]);
    $corpo = @file_get_contents($url, false, $contexto);
    $status = 0;
    foreach ($http_response_header ?? [] as $linha) {
        if (preg_match('#^HTTP/\S+ (\d{3})#', $linha, $m)) {
            $status = (int) $m[1];
        }
    }
    return [$status, $corpo === false ? '' : $corpo];
}

/** Caminho com pontos ('kpis.leads'); '*' percorre uma lista ('linhas.*.status' devolve todos os status). */
function valorNoCaminho(mixed $dados, string $caminho): mixed
{
    $partes = explode('.', $caminho);
    $parte = array_shift($partes);
    if ($parte === '*') {
        if (!is_array($dados)) {
            return null;
        }
        $resto = implode('.', $partes);
        return array_map(fn($item) => $resto === '' ? $item : valorNoCaminho($item, $resto), array_values($dados));
    }
    if (!is_array($dados) || !array_key_exists($parte, $dados)) {
        return null;
    }
    return $partes ? valorNoCaminho($dados[$parte], implode('.', $partes)) : $dados[$parte];
}

function atende(mixed $valor, string $condicao): bool
{
    if ($condicao === '>0') {
        return is_numeric($valor) && $valor > 0;
    }
    if (str_starts_with($condicao, '>=')) {
        return is_numeric($valor) && $valor >= (float) substr($condicao, 2);
    }
    if (str_starts_with($condicao, 'entre:')) {
        [, $min, $max] = explode(':', $condicao);
        return is_numeric($valor) && $valor >= (float) $min && $valor <= (float) $max;
    }
    if ($condicao === 'naoVazio') {
        return is_array($valor) ? count($valor) > 0 : ($valor !== null && $valor !== '');
    }
    if ($condicao === '===false') {
        return $valor === false;
    }
    if ($condicao === '===true') {
        return $valor === true;
    }
    if (str_starts_with($condicao, 'contem:')) {
        $alvo = substr($condicao, 7);
        return is_array($valor) && in_array($alvo, array_map('strval', array_values($valor)), true);
    }
    throw new InvalidArgumentException("Condição desconhecida: $condicao");
}

function resumirValor(mixed $valor): string
{
    if (is_array($valor)) {
        return '[' . count($valor) . ' itens]';
    }
    if (is_float($valor)) {
        return (string) round($valor, 2);
    }
    return var_export($valor, true);
}

function trechoDeErro(string $corpo): string
{
    $texto = trim(preg_replace('/\s+/', ' ', strip_tags($corpo)));
    return $texto === '' ? '' : ' — ' . mb_substr($texto, 0, 160);
}
