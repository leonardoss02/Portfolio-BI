<?php

/**
 * Carga única dos dados do sistema antigo. Roda por linha de comando — não é
 * rota web, para não haver como disparar uma sobrescrita completa por HTTP.
 *
 *   php lib/hotel/importar.php                       # busca na API do Apps Script
 *   php lib/hotel/importar.php --fonte=dump.json     # usa um dump já baixado
 *   php lib/hotel/importar.php --simular             # calcula e relata sem gravar
 *
 * Substitui as coleções inteiras e preserva os ids de origem: rodar duas
 * vezes seguidas produz exatamente o mesmo resultado.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Este script só roda por linha de comando.\n");
}

require_once __DIR__ . '/Importador.php';
require_once __DIR__ . '/RepoJson.php';

$opts     = getopt('', ['fonte::', 'dir::', 'simular', 'json']);
$fonte    = $opts['fonte'] ?? null;
$simular  = array_key_exists('simular', $opts);
$saidaJson = array_key_exists('json', $opts);

$dir  = $opts['dir'] ?? ($simular
    ? sys_get_temp_dir() . '/hotel-simulacao-' . bin2hex(random_bytes(3))
    : __DIR__ . '/../../.mrphp/hotel');

try {
    $dados = $fonte
        ? HotelImportador::deArquivo($fonte)
        : HotelImportador::daApi();

    $repo       = new HotelRepoJson($dir);
    $importador = new HotelImportador($repo);
    $rel        = $importador->importar($dados);
} catch (Throwable $e) {
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . "\n");
    exit(1);
}

if ($saidaJson) {
    echo json_encode($rel, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
    exit(0);
}

$brl = static fn(float $v): string => 'R$ ' . number_format($v, 2, ',', '.');

echo "\n== Importação " . ($simular ? '(SIMULAÇÃO — nada foi gravado no destino real)' : '') . " ==\n";
echo 'Origem : ' . ($fonte ?: 'API do Apps Script') . "\n";
echo "Destino: {$dir}\n\n";

printf("%-13s %8s %8s %16s   %s\n", 'ENTIDADE', 'ORIGEM', 'GRAVADO', 'SOMA DOS VALORES', 'VÍNCULO');
echo str_repeat('-', 96), "\n";

$totalGeral = 0.0;
foreach ($rel['entidades'] as $ent => $r) {
    $vinculo = '';
    if (isset($r['vinculo'])) {
        $partes = [];
        foreach ($r['vinculo'] as $como => $n) {
            if ($n > 0) {
                $partes[] = "{$como}:{$n}";
            }
        }
        $vinculo = implode('  ', $partes);
    }

    printf(
        "%-13s %8d %8d %16s   %s\n",
        $ent,
        $r['origem'],
        $r['importados'],
        isset($r['valor']) ? $brl((float)$r['valor']) : '—',
        $vinculo
    );

    $totalGeral += (float)($r['valor'] ?? 0);
}

echo str_repeat('-', 96), "\n";
printf("%-13s %8s %8s %16s\n\n", 'TOTAL', '', '', $brl($totalGeral));

if ($rel['avisos']) {
    echo 'AVISOS (' . count($rel['avisos']) . "):\n";
    foreach ($rel['avisos'] as $a) {
        echo "  - {$a}\n";
    }
    echo "\n";
}

if ($rel['orfaos']) {
    echo 'ÓRFÃOS (' . count($rel['orfaos']) . " registros sem evento — foram gravados, mas sem vínculo):\n";
    printf("  %-12s %6s %-24s %-12s %14s\n", 'ENTIDADE', 'ID', 'CIDADE', 'DATA', 'VALOR');
    $somaOrfaos = 0.0;
    foreach ($rel['orfaos'] as $o) {
        printf(
            "  %-12s %6s %-24s %-12s %14s\n",
            $o['entidade'],
            (string)($o['id'] ?? '?'),
            mb_substr((string)$o['cidade'], 0, 24),
            (string)($o['data'] ?? '—'),
            $brl((float)$o['valor'])
        );
        $somaOrfaos += (float)$o['valor'];
    }
    printf("  %-45s %14s\n\n", 'soma dos órfãos', $brl($somaOrfaos));
} else {
    echo "Nenhum órfão: todo custo ficou ligado a um hotel.\n\n";
}

if ($simular && !isset($opts['dir'])) {
    foreach (glob($dir . '/*.json') ?: [] as $f) {
        unlink($f);
    }
    @rmdir($dir);
}
