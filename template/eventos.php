<?php
/*
 * template/eventos.php
 *
 * Template compartilhado pelas páginas do dashboard Eventos (controller
 * `eventos`). Cópia estrutural de template/executive.php, com sidebar
 * própria (Pipeline de Eventos / Consolidado por Evento / Dicionário) —
 * não reaproveita o template do Executive View para não misturar as
 * navegações dos dois dashboards nesta migração.
 */

$uri      = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$segments = array_values(array_filter(explode('/', trim($uri, '/')), fn($s) => $s !== ''));
$tab      = $segments[1] ?? '';

$activeMap = [
    'detalhamento' => 'detalhamento',
    'relatorio'    => 'relatorio',
    'dicionario'   => 'dicionario',
];
$active   = $activeMap[$tab] ?? 'pipeline';
$isEmbed  = ($_GET['to'] ?? '') === '1';

function navClass($name, $active)
{
    return 'nav-item' . ($name === $active ? ' active' : '');
}
function navAria($name, $active)
{
    return $name === $active ? ' aria-current="page"' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acme EUA · BI · Eventos</title>

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
    <script src="<?= $this->site ?>js/bi-dashboard.js?v=<?= @filemtime(__DIR__ . '/../js/bi-dashboard.js') ?: '1' ?>"></script>
</head>
<body<?= $isEmbed ? ' class="embed-mode"' : '' ?>>
<div class="app-shell">

    <aside class="sidebar" aria-label="Menu de navegação">

        <div class="brand">
            <div class="brand-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            <div>
                <div class="brand-name">Acme EUA</div>
                <div class="brand-sub">BI · ANALYTICS</div>
            </div>
        </div>

        <nav class="nav" role="navigation">

            <div class="nav-section-label">Dashboards</div>

            <a class="<?= navClass('pipeline', $active) ?>" href="<?= $this->site ?>eventos"<?= navAria('pipeline', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6"  y1="20" x2="6"  y2="14"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Pipeline de Eventos
                    <div class="nav-item-sub">Funil · Custos · ROI</div>
                </div>
                <div class="nav-dot" style="background:#c8102e"></div>
            </a>

            <a class="<?= navClass('detalhamento', $active) ?>" href="<?= $this->site ?>eventos/detalhamento"<?= navAria('detalhamento', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <line x1="3"  y1="9"  x2="21" y2="9"/>
                        <line x1="3"  y1="15" x2="21" y2="15"/>
                        <line x1="9"  y1="3"  x2="9"  y2="21"/>
                        <line x1="15" y1="3"  x2="15" y2="21"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Consolidado por Evento
                    <div class="nav-item-sub">Tabela · Custos · Lucro</div>
                </div>
                <div class="nav-dot" style="background:#1a6ec8"></div>
            </a>

            <a class="<?= navClass('relatorio', $active) ?>" href="<?= $this->site ?>eventos/relatorio"<?= navAria('relatorio', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="8" y1="13" x2="16" y2="13"/>
                        <line x1="8" y1="17" x2="14" y2="17"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Relatório PDF
                    <div class="nav-item-sub">Imprimível · A4 paisagem</div>
                </div>
                <div class="nav-dot" style="background:#9a7530"></div>
            </a>

            <div class="nav-section-label" style="margin-top:8px">Referência</div>

            <a class="<?= navClass('dicionario', $active) ?>" href="<?= $this->site ?>eventos/dicionario"<?= navAria('dicionario', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Dicionário
                    <div class="nav-item-sub">Métricas · Funil · Fórmulas</div>
                </div>
                <div class="nav-dot" style="background:#14b8a6"></div>
            </a>

        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-footer-text" id="footer-clock"></div>
        </div>
    </aside>

    <main class="content">
        <?php $this->getPagina(); ?>
    </main>

</div>

<script>
function applyTheme(theme) {
    var isDark = theme === 'dark';
    document.documentElement.classList.toggle('dark', isDark);
    var btn  = document.getElementById('theme-btn');
    var moon = document.getElementById('theme-icon-moon');
    var sun  = document.getElementById('theme-icon-sun');
    if (btn)  btn.classList.toggle('active', isDark);
    if (moon) moon.style.display = isDark ? 'none' : '';
    if (sun)  sun.style.display  = isDark ? ''     : 'none';
}
function toggleTheme() {
    var theme = localStorage.getItem('theme') === 'dark' ? 'light' : 'dark';
    localStorage.setItem('theme', theme);
    applyTheme(theme);
}
applyTheme(localStorage.getItem('theme') || 'light');

<?php if ($isEmbed): ?>
document.querySelectorAll('.nav a.nav-item').forEach(function (a) {
    var u = new URL(a.href);
    u.searchParams.set('to', '1');
    a.href = u.toString();
});
<?php endif; ?>

function updateClock() {
    document.getElementById('footer-clock').textContent =
        new Date().toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
updateClock();
setInterval(updateClock, 30000);
</script>
</body>
</html>
