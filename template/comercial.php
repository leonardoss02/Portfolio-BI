<?php
/*
 * template/comercial.php
 *
 * Template do dashboard "Comercial" (funil Evento × Digital × Retenção) —
 * controller `comercial`. Cópia estrutural de template/marketing.php.
 */

$uri      = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$segments = array_values(array_filter(explode('/', trim($uri, '/')), fn($s) => $s !== ''));
$tab      = $segments[1] ?? '';

$activeMap = [
    'yoy'         => 'yoy',
    'cadeiras'    => 'cadeiras',
    'comercial1'  => 'comercial1',
    'comercial2'  => 'comercial2',
    'comercial3'  => 'comercial3',
    'dicionario'  => 'dicionario',
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
    <title>Acme EUA · BI · Comercial</title>

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

            <div class="nav-section-label">Dashboard</div>

            <a class="<?= navClass('index', $active) ?>" href="<?= $this->site ?>comercial"<?= navAria('index', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 3v18h18"/>
                        <path d="M7 15l4-4 3 3 5-6"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Funil Comercial
                    <div class="nav-item-sub">Evento · Digital · Retenção</div>
                </div>
                <div class="nav-dot" style="background:#f54a00"></div>
            </a>

            <a class="<?= navClass('yoy', $active) ?>" href="<?= $this->site ?>comercial/yoy"<?= navAria('yoy', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 20h18"/>
                        <rect x="5" y="11" width="4" height="9"/>
                        <rect x="14" y="7" width="4" height="13"/>
                        <path d="M4 6l5-3 5 3 6-4"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Comparativo Anual
                    <div class="nav-item-sub">Ano atual × mesmo período de LY</div>
                </div>
                <div class="nav-dot" style="background:#0d8a52"></div>
            </a>

            <a class="<?= navClass('cadeiras', $active) ?>" href="<?= $this->site ?>comercial/cadeiras"<?= navAria('cadeiras', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Time Cadeiras
                    <div class="nav-item-sub">Ranking · conversão · descartes</div>
                </div>
                <div class="nav-dot" style="background:#b86e00"></div>
            </a>

            <a class="<?= navClass('comercial1', $active) ?>" href="<?= $this->site ?>comercial/comercial1"<?= navAria('comercial1', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Time Comercial 1
                    <div class="nav-item-sub">Evento · Digital · Consultas</div>
                </div>
                <div class="nav-dot" style="background:#6f42c1"></div>
            </a>

            <a class="<?= navClass('comercial2', $active) ?>" href="<?= $this->site ?>comercial/comercial2"<?= navAria('comercial2', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.85"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Time Comercial 2
                    <div class="nav-item-sub">Consultas · Combos · Evento/Digital</div>
                </div>
                <div class="nav-dot" style="background:#c8102e"></div>
            </a>

            <a class="<?= navClass('comercial3', $active) ?>" href="<?= $this->site ?>comercial/comercial3"<?= navAria('comercial3', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 12a9 9 0 1 0 3-6.7"/>
                        <polyline points="3 4 3 9 8 9"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Time Comercial 3
                    <div class="nav-item-sub">Retenção · Recuperação de cancelamentos</div>
                </div>
                <div class="nav-dot" style="background:#0d9488"></div>
            </a>

            <a class="<?= navClass('dicionario', $active) ?>" href="<?= $this->site ?>comercial/dicionario"<?= navAria('dicionario', $active) ?>>
                <div class="nav-item-icon">
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

    <main class="content">
        <?php $this->getPagina(); ?>
    </main>

</div>

<script>
// O botão de tema deste dashboard fica no header de cada página (ao lado do
// título), não na sidebar — por isso os elementos são opcionais aqui.
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
