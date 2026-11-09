<?php
/**
 * O BI Explorer foi removido do projeto. Este teste garante que nenhuma
 * referência a ele sobrou no código ou na documentação.
 */

return [
    'nenhum arquivo do projeto cita o BI Explorer' => function () {
        $raiz = dirname(__DIR__, 2);
        $pastas = ['controle', 'paginas', 'template', 'js', 'css', 'lib'];
        $citam = [];
        foreach ($pastas as $pasta) {
            $arquivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$raiz/$pasta", FilesystemIterator::SKIP_DOTS));
            foreach ($arquivos as $arquivo) {
                if (stripos(file_get_contents($arquivo->getPathname()), 'biexplorer') !== false
                    || stripos(file_get_contents($arquivo->getPathname()), 'bi explorer') !== false) {
                    $citam[] = substr($arquivo->getPathname(), strlen($raiz) + 1);
                }
            }
        }
        afirmar($citam === [], 'ainda citam o BI Explorer: ' . implode(', ', $citam));
    },

    'o README não cita o BI Explorer' => function () {
        $readme = file_get_contents(dirname(__DIR__, 2) . '/README.md');
        afirmar(stripos($readme, 'biexplorer') === false && stripos($readme, 'bi explorer') === false, 'o README ainda cita o BI Explorer');
    },

    'a página 404 tem conteúdo' => function () {
        $conteudo = file_get_contents(dirname(__DIR__, 2) . '/paginas/error/404.php');
        afirmar(str_contains($conteudo, 'Página não encontrada'), 'a página 404 continua vazia');
    },

    'o template inicial não carrega CSS inexistente' => function () {
        $conteudo = file_get_contents(dirname(__DIR__, 2) . '/template/index.php');
        afirmar(!str_contains($conteudo, 'css/app.css'), 'template/index.php ainda carrega css/app.css');
    },
];
