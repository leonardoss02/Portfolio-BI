<?php
/** Template do Dashboard Credenciados. */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$segments = array_values(array_filter(explode('/', trim($uri, '/')), fn($segment) => $segment !== ''));
$active = (($segments[1] ?? '') === 'dicionario') ? 'dicionario' : 'index';
$isEmbed = ($_GET['to'] ?? '') === '1';
$bodyClass = trim('credenciados-shell ' . ($isEmbed ? 'embed-mode' : ''));

function credNavClass(string $name, string $active): string
{
    return 'nav-item' . ($name === $active ? ' active' : '');
}

function credNavAria(string $name, string $active): string
{
    return $name === $active ? ' aria-current="page"' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acme EUA · BI · Credenciados</title>

    <script>
    (function () {
        var theme = localStorage.getItem('theme') || 'light';
        document.documentElement.classList.toggle('dark', theme === 'dark');
    })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&family=Fira+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= $this->site ?>css/bi-theme.css?v=<?= @filemtime(__DIR__ . '/../css/bi-theme.css') ?: '1' ?>">
    <script src="<?= $this->site ?>js/bi-dashboard.js?v=<?= @filemtime(__DIR__ . '/../js/bi-dashboard.js') ?: '1' ?>"></script>
</head>
<body class="<?= $bodyClass ?>">
<a class="cred-skip-link" href="#cred-page-content">Pular para o conteúdo</a>
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

        <nav class="nav" aria-label="Dashboard Credenciados">
            <div class="nav-section-label">Dashboard</div>

            <a class="<?= credNavClass('index', $active) ?>" href="<?= $this->site ?>credenciados"<?= credNavAria('index', $active) ?>>
                <div class="nav-item-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <path d="M18 8l2 2 3-4"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Credenciados
                    <div class="nav-item-sub">Funil · Conversão · Descartes</div>
                </div>
                <div class="nav-dot" style="background:#1a6ec8"></div>
            </a>

            <a class="<?= credNavClass('dicionario', $active) ?>" href="<?= $this->site ?>credenciados/dicionario"<?= credNavAria('dicionario', $active) ?>>
                <div class="nav-item-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Dicionário
                    <div class="nav-item-sub">Como cada métrica é calculada</div>
                </div>
                <div class="nav-dot" style="background:#5c94bd"></div>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-footer-text" id="footer-clock"></div>
        </div>
    </aside>

    <main class="content" id="cred-page-content" tabindex="-1">
        <?php $this->getPagina(); ?>
    </main>
</div>

<script>
function applyTheme(theme) {
    var isDark = theme === 'dark';
    document.documentElement.classList.toggle('dark', isDark);
    var btn = document.getElementById('theme-btn');
    var moon = document.getElementById('theme-icon-moon');
    var sun = document.getElementById('theme-icon-sun');
    if (btn) btn.classList.toggle('active', isDark);
    if (moon) moon.style.display = isDark ? 'none' : '';
    if (sun) sun.style.display = isDark ? '' : 'none';
}

function toggleTheme() {
    var theme = localStorage.getItem('theme') === 'dark' ? 'light' : 'dark';
    localStorage.setItem('theme', theme);
    applyTheme(theme);
}

applyTheme(localStorage.getItem('theme') || 'light');

<?php if ($isEmbed): ?>
document.querySelectorAll('.nav a.nav-item').forEach(function (anchor) {
    var url = new URL(anchor.href);
    url.searchParams.set('to', '1');
    anchor.href = url.toString();
});
<?php endif; ?>

function updateClock() {
    var clock = document.getElementById('footer-clock');
    if (clock) {
        clock.textContent = new Date().toLocaleString('pt-BR', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }
}
updateClock();
setInterval(updateClock, 30000);
</script>
</body>
</html>
