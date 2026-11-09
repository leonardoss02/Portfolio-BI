<?php
/**
 * Gera a base de dados fictícia e grava no banco configurado no .env.
 *
 * Uso:
 *   php database/seed/gerar.php                     dados até hoje, gravando no banco
 *   php database/seed/gerar.php --hoje=2027-03-15   finge que hoje é outra data
 *   php database/seed/gerar.php --escala=0.1        base menor (mais rápida)
 *   php database/seed/gerar.php --sem-banco         só gera e confere, sem gravar
 *
 * Cada execução derruba e recria todas as tabelas (database/schema.sql).
 */

declare(strict_types=1);

$raiz = dirname(__DIR__, 2);
foreach (glob(__DIR__ . '/src/*.php') as $classe) {
    require_once $classe;
}

$opcoes = getopt('', ['hoje:', 'escala:', 'sem-banco']);
$config = require __DIR__ . '/config.php';
if (isset($opcoes['escala'])) {
    $config['escala'] = (float) $opcoes['escala'];
}
$hoje = $opcoes['hoje'] ?? (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');

$inicio = microtime(true);
echo "Gerando dados fictícios até {$hoje} (escala {$config['escala']})...\n";

$ctx = Contexto::criar($hoje, $config);
Gerador::rodar($ctx);

foreach ($ctx->base->tabelas() as $tabela) {
    printf("  %-18s %8d linhas\n", $tabela, $ctx->base->total($tabela));
}

if (!isset($opcoes['sem-banco'])) {
    $db = Banco::conectar(Banco::lerEnv($raiz . '/.env'));
    Banco::aplicarSchema($db, $raiz . '/database/schema.sql');
    Banco::gravar($db, $ctx->base);
    $apagados = Banco::limparCache($raiz . '/.mrphp/cache');
    echo "Banco recriado. Cache limpo ({$apagados} arquivos).\n";
}

printf("Pronto em %.1f s.\n", microtime(true) - $inicio);
