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

// Script de linha de comando: pela web, responde 404 e não executa nada.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$raiz = dirname(__DIR__, 2);
foreach (glob(__DIR__ . '/src/*.php') as $classe) {
    require_once $classe;
}

$opcoes = getopt('', ['hoje:', 'escala:', 'sem-banco']);
$config = require __DIR__ . '/config.php';
if (isset($opcoes['escala'])) {
    $config['escala'] = (float) $opcoes['escala'];
}
$agoraReal = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
$hoje = $opcoes['hoje'] ?? $agoraReal->format('Y-m-d');
// Gerando para hoje: nenhum horário passa da hora atual. Para outra data, vale o fim daquele dia.
$agora = $hoje === $agoraReal->format('Y-m-d') ? new DateTimeImmutable($agoraReal->format('Y-m-d H:i:s')) : null;

$inicio = microtime(true);
echo "Gerando dados fictícios até {$hoje} (escala {$config['escala']})...\n";

$ctx = Contexto::criar($hoje, $config, $agora);
Gerador::rodar($ctx);
echo Verificacao::resumo($ctx->base), "\n";

$falhas = Verificacao::checar($ctx);
if ($falhas) {
    foreach ($falhas as $falha) {
        echo "✘ $falha\n";
    }
    echo "A base não foi gravada. Corrija o gerador ou rode `docker compose up --build` de novo.\n";
    exit(1);
}

if (!isset($opcoes['sem-banco'])) {
    $db = Banco::conectar(Banco::lerEnv($raiz . '/.env'));
    Banco::aplicarSchema($db, $raiz . '/database/schema.sql');
    Banco::gravar($db, $ctx->base);
    Banco::prepararStorage($raiz);
    $apagados = Banco::limparCache($raiz . '/.mrphp/cache');
    echo "Banco recriado. Cache limpo ({$apagados} arquivos).\n";
}

printf("Pronto em %.1f s.\n", microtime(true) - $inicio);
