<?php
/**
 * Runner dos testes do projeto.
 *
 * Uso:
 *   php tests/rodar.php                          roda todos os testes
 *   php tests/rodar.php tests/seed/BaseTeste.php roda só os arquivos indicados
 *
 * Cada arquivo *Teste.php devolve um array ['nome do teste' => function () {...}].
 * Um teste passa quando a função termina sem lançar exceção.
 */

declare(strict_types=1);

$raiz = dirname(__DIR__);
require __DIR__ . '/lib/afirmar.php';

// Warning e notice viram falha: um aviso do PHP no gerador é um bug.
set_error_handler(function (int $nivel, string $mensagem, string $arquivo, int $linha): bool {
    throw new ErrorException($mensagem, 0, $nivel, $arquivo, $linha);
});

// O gerador não usa autoload: carrega todas as classes de database/seed/src.
foreach (glob($raiz . '/database/seed/src/*.php') ?: [] as $classe) {
    require_once $classe;
}

$arquivos = array_slice($argv, 1);
if (!$arquivos) {
    $arquivos = array_merge(
        glob($raiz . '/tests/seed/*Teste.php') ?: [],
        glob($raiz . '/tests/estaticos/*Teste.php') ?: []
    );
}

$passou = 0;
$falhou = 0;
foreach ($arquivos as $arquivo) {
    $caminho = is_file($arquivo) ? $arquivo : $raiz . '/' . ltrim($arquivo, '/');
    $nomeArquivo = basename($caminho, '.php');
    try {
        $testes = require $caminho;
    } catch (Throwable $e) {
        echo "✘ {$nomeArquivo} — não carregou: {$e->getMessage()}\n";
        $falhou++;
        continue;
    }
    foreach ($testes as $nome => $teste) {
        $inicio = microtime(true);
        try {
            $teste();
            $ms = (int) round((microtime(true) - $inicio) * 1000);
            echo "✔ {$nomeArquivo} › {$nome} ({$ms} ms)\n";
            $passou++;
        } catch (Throwable $e) {
            $origem = $e instanceof FalhaDeTeste ? '' : ' [' . get_class($e) . ' em ' . basename($e->getFile()) . ':' . $e->getLine() . ']';
            echo "✘ {$nomeArquivo} › {$nome} — {$e->getMessage()}{$origem}\n";
            $falhou++;
        }
    }
}

echo "\n{$passou} passaram, {$falhou} falharam\n";
exit($falhou > 0 ? 1 : 0);
