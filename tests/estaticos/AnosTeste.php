<?php
/**
 * Os dashboards não podem depender de um ano escrito no código: em 2027 as
 * listas de ano, os filtros padrão e a conversão do custo de marketing
 * continuariam presos em 2025/2026.
 */

function arquivosPhp(string $pasta): array
{
    $arquivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pasta, FilesystemIterator::SKIP_DOTS));
    return array_filter(iterator_to_array($arquivos, false), fn($a) => $a->getExtension() === 'php');
}

function quemCasa(string $pasta, string $regex): array
{
    $raiz = dirname(__DIR__, 2);
    $casam = [];
    foreach (arquivosPhp("$raiz/$pasta") as $arquivo) {
        if (preg_match($regex, file_get_contents($arquivo->getPathname()))) {
            $casam[] = substr($arquivo->getPathname(), strlen($raiz) + 1);
        }
    }
    return $casam;
}

return [
    'nenhuma view tem lista fixa de anos' => function () {
        $casam = quemCasa('paginas', '/const\s+YEARS\s*=\s*\[/');
        afirmar($casam === [], 'listas fixas em: ' . implode(', ', $casam));
    },

    'nenhuma view abre com um ano fixo' => function () {
        $casam = array_merge(
            quemCasa('paginas', "/select\\(\\[\\s*'20\\d\\d'\\s*\\]\\)/"),
            quemCasa('paginas', "/year:\\s*'20\\d\\d'/")
        );
        afirmar($casam === [], 'ano padrão fixo em: ' . implode(', ', $casam));
    },

    'a conversão do custo de marketing não compara com um ano exato' => function () {
        $casam = quemCasa('controle', '/YEAR\(cm\.data_custoMkt\) = 20\d\d/');
        afirmar($casam === [], 'YEAR(...) = ano fixo em: ' . implode(', ', $casam));
    },

    'bi-dashboard.js define anosDesde()' => function () {
        $js = file_get_contents(dirname(__DIR__, 2) . '/js/bi-dashboard.js');
        afirmar(str_contains($js, 'function anosDesde('), 'anosDesde() não existe');
    },
];
