<?php
/*
 * template/hotel.php
 *
 * Shell do sistema de Gestão de Eventos (hotéis) — controller `hotel`.
 *
 * Estrutura copiada de template/eventos.php (sidebar, tema claro/escuro em
 * localStorage, modo embed com ?to=1, cache-bust por filemtime). Cada
 * dashboard deste projeto tem o seu próprio template, por decisão de projeto:
 * misturar as navegações num shell comum acopla sistemas que evoluem em
 * ritmos diferentes.
 *
 * Diferença relevante para os dashboards de BI: aqui as abas são rotas de
 * verdade, uma por tela. No sistema antigo tudo vivia numa página só e cada
 * ciclo de render recalculava as doze abas, mesmo as invisíveis.
 */

require_once __DIR__ . '/../paginas/hotel/partes/helpers.php';

$uri      = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$segmentos = array_values(array_filter(explode('/', trim($uri, '/')), static fn($s) => $s !== ''));
$abaAtiva  = $segmentos[1] ?? 'index';
$isEmbed   = ($_GET['to'] ?? '') === '1';

/*
 * Abas: rota => [rótulo, ícone].
 *
 * Cada aba já teve um subtítulo e um ponto de cor próprio — dez matizes
 * diferentes numa coluna de 220px, nenhum deles significando nada. Quem
 * orienta agora são os três rótulos de grupo; o estado ativo é barra da
 * marca + peso da fonte.
 */
$navegacao = [
    'index'        => ['Visão geral',  'grafico'],
    'lista'        => ['Hotéis',       'hotel'],
    'equipamentos' => ['Equipamentos', 'av'],
    'videomakers'  => ['Videomakers',  'video'],
    'transportes'  => ['Transportes',  'aviao'],
    'frete'        => ['Frete',        'caminhao'],
    'materiais'    => ['Materiais',    'caixa'],
    'custosextras' => ['Custos extras', 'nota'],
    'valortotal'   => ['Valor total',  'dinheiro'],
    'calendario'   => ['Calendário',   'calendario'],
    'relatorio'    => ['Relatório',    'relatorio'],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acme EUA · Gestão de Eventos</title>

    <script>
    (function () {
        var t = localStorage.getItem('theme') || 'light';
        document.documentElement.classList.toggle('dark', t === 'dark');
    })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&family=Fira+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= $this->site ?>css/bi-theme.css?v=<?= @filemtime(__DIR__ . '/../css/bi-theme.css') ?: '1' ?>">
    <link rel="stylesheet" href="<?= $this->site ?>css/hotel.css?v=<?= @filemtime(__DIR__ . '/../css/hotel.css') ?: '1' ?>">

    <?php
    /*
     * createMultiDropdown() do JS compartilhado dos dashboards: o painel de
     * filtros desta tela usa a MESMA factory do Executive e do Marketing, em
     * vez de uma reimplementação parecida. Só declara funções globais, não
     * executa nada no carregamento.
     */
    ?>
    <script src="<?= $this->site ?>js/bi-dashboard.js?v=<?= @filemtime(__DIR__ . '/../js/bi-dashboard.js') ?: '1' ?>"></script>

    <?php
    /*
     * Os modulos JS carregam no <head>, de forma sincrona e nesta ordem.
     *
     * Nao podem ir para o fim do <body>: cada view traz um <script> inline que
     * usa GH.core / GH.ui / GH.forms, e script inline roda durante a analise
     * do documento — ou seja, ANTES de qualquer <script src> que venha depois
     * dele. Com os modulos no rodape, GH ficava indefinido no momento em que a
     * view executava, o bloco inteiro morria com TypeError e nenhum botao
     * respondia. "defer" tambem nao resolve, porque adia para depois da
     * analise, ainda mais tarde que o inline.
     *
     * E o mesmo criterio de template/eventos.php, que carrega bi-dashboard.js
     * no <head>. Sao arquivos locais e pequenos.
     */
    foreach (['core', 'ui', 'busca-select', 'forms', 'shell', 'filtros'] as $modulo):
        $arquivo = __DIR__ . '/../js/hotel/' . $modulo . '.js';
    ?>
    <script src="<?= $this->site ?>js/hotel/<?= $modulo ?>.js?v=<?= @filemtime($arquivo) ?: '1' ?>"></script>
    <?php endforeach; ?>
    <?php foreach (($modulosJs ?? []) as $modulo): ?>
        <script src="<?= $this->site ?>js/hotel/<?= hesc($modulo) ?>.js?v=<?= @filemtime(__DIR__ . '/../js/hotel/' . $modulo . '.js') ?: '1' ?>"></script>
    <?php endforeach; ?>
</head>
<body class="dash-hotel<?= $isEmbed ? ' embed-mode' : '' ?>">
<div class="app-shell">

    <main class="content">
        <?php
        /*
         * A navegação vem antes de tudo e vive dentro do conteúdo, não numa
         * sidebar. Motivo: `body.embed-mode .sidebar { display:none }` mora no
         * bi-theme.css, compartilhado pelos 16 dashboards — dentro do iframe
         * (`?to=1`) a coluna sumia e não sobrava forma de trocar de tela.
         * A marcação está em partes/abas.php, junto das views.
         */
        include __DIR__ . '/../paginas/hotel/partes/abas.php';
        ?>
        <?php
        /*
         * O shell (faixa de título, filtros, alertas, cartoes) vive aqui e
         * nao em cada view: sao os mesmos em todas as abas, e repeti-los por
         * tela foi o que fez os dicionarios de status divergirem no sistema
         * antigo. A faixa de título é a última linha do cartão de navegação
         * (partes/abas.php, que inclui partes/header.php antes de fechar
         * .gh-nav) — não fica mais fora do .main como cartão à parte.
         *
         * O painel de filtros mudou de lugar: antes ficava colado no topo,
         * acima até do aviso de eventos próximos; agora vem depois dele —
         * o aviso é o que muda com mais urgência, o filtro é o que a pessoa
         * ajusta a seguir. Os dois vivem dentro do .main, que traz o padding
         * e o max-width dos demais dashboards (bi-theme.css).
         */
        $partes = __DIR__ . '/../paginas/hotel/partes/';
        ?>

        <div class="main">
            <?php
            if (isset($shell)) {
                if (!empty($shell['semDados'])) {
                    include $partes . 'semdados.php';
                    if (empty($shell['semFiltros'])) {
                        include $partes . 'filtros.php';
                    }
                } else {
                    include $partes . 'alertas.php';
                    if (empty($shell['semFiltros'])) {
                        include $partes . 'filtros.php';
                    }
                    include $partes . 'statcards.php';
                }
            }
            $this->getPagina();
            ?>
        </div>
    </main>

</div>

<?php include __DIR__ . '/../paginas/hotel/partes/sprite-icones.php'; ?>

<!-- Região de avisos: substitui o alert() bloqueante do sistema antigo. -->
<div id="gh-toasts" class="gh-toasts" role="status" aria-live="polite" aria-atomic="false"></div>

<script>
function applyTheme(theme) {
    var isDark = theme === 'dark';
    document.documentElement.classList.toggle('dark', isDark);
    document.getElementById('theme-btn').classList.toggle('active', isDark);
    document.getElementById('theme-icon-moon').style.display = isDark ? 'none' : '';
    document.getElementById('theme-icon-sun').style.display  = isDark ? ''     : 'none';
    document.getElementById('theme-label').textContent = isDark ? 'Light mode' : 'Dark mode';
    // Os gráficos leem as cores das CSS vars e precisam se redesenhar.
    document.dispatchEvent(new CustomEvent('gh:tema', { detail: { dark: isDark } }));
}
function toggleTheme() {
    var theme = localStorage.getItem('theme') === 'dark' ? 'light' : 'dark';
    localStorage.setItem('theme', theme);
    applyTheme(theme);
}
applyTheme(localStorage.getItem('theme') || 'light');

function updateClock() {
    document.getElementById('footer-clock').textContent =
        new Date().toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
updateClock();
setInterval(updateClock, 30000);
</script>

</body>
</html>
