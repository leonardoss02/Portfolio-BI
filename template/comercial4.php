<?php
/*
 * template/comercial4.php
 *
 * Template do dashboard Comercial 4 (controller `comercial4`). Cópia
 * estrutural de template/law.php — sem verificação de login (nenhum
 * dashboard do projeto usa autenticação hoje). Abas: "Painel" (index),
 * "Liga Acme-EUA" e "Dicionário".
 */

$uri      = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$segments = array_values(array_filter(explode('/', trim($uri, '/')), fn($s) => $s !== ''));
$tab      = $segments[1] ?? '';

$activeMap = [
    'dicionario' => 'dicionario',
    'liga'       => 'liga',
];
$active   = $activeMap[$tab] ?? 'index';
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
    <title>Acme EUA · BI · Comercial 4</title>

    <script>
    (function () {
        var t = localStorage.getItem('theme') || 'light';
        document.documentElement.classList.toggle('dark', t === 'dark');
    })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&family=Fira+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<?php if ($active === 'liga'): ?>
    <!-- Fontes do relatório "Liga Acme-EUA" do CS (paginas/comercial4/liga.php). -->
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter+Tight:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<?php endif; ?>

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

            <div class="nav-section-label">Dashboard</div>

            <a class="<?= navClass('index', $active) ?>" href="<?= $this->site ?>comercial4"<?= navAria('index', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z"/>
                        <path d="M12 12L4 7.5M12 12l8-4.5M12 12v9"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Comercial 4
                    <div class="nav-item-sub">Aplicados · Milestones · RFE/NOID</div>
                </div>
                <div class="nav-dot" style="background:#4F79BC"></div>
            </a>

            <a class="<?= navClass('liga', $active) ?>" href="<?= $this->site ?>comercial4/liga"<?= navAria('liga', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M7 4h10v5a5 5 0 0 1-10 0z"/>
                        <path d="M17 5h2a2 2 0 0 1 0 4h-2.2M7 5H5a2 2 0 0 0 0 4h2.2"/>
                        <path d="M12 14v6M8 20h8"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Liga Acme-EUA
                    <div class="nav-item-sub">Campanha de upsell · CSM</div>
                </div>
                <div class="nav-dot" style="background:#B8893B"></div>
            </a>

            <a class="<?= navClass('dicionario', $active) ?>" href="<?= $this->site ?>comercial4/dicionario"<?= navAria('dicionario', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Dicionário
                    <div class="nav-item-sub">O que significa cada métrica</div>
                </div>
                <div class="nav-dot" style="background:#d4a017"></div>
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
