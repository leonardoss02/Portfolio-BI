<?php
/*
 * template/consultacom2.php
 *
 * Template do dashboard "ConsultaCom2" (controller `consultacom2`) — cópia
 * estrutural de template/rafaeltorres.php, com duas abas: a tabela de
 * consultas do time Comercial 2 e a planilha de conferência do time.
 */

$isEmbed = ($_GET['to'] ?? '') === '1';

// Aba ativa pelo segmento da URL: /consultacom2, /consultacom2/planilha ou
// /consultacom2/eventos. Expressão inline em vez das funções globais
// navClass()/navAria() de template/marketing.php — são três abas, não vale
// declarar função global.
$uri      = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$segments = array_values(array_filter(explode('/', trim($uri, '/')), fn($s) => $s !== ''));
$seg      = $segments[1] ?? '';
$active   = in_array($seg, ['planilha', 'eventos'], true) ? $seg : 'index';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acme EUA · BI · ConsultaCom2</title>

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

            <a class="nav-item<?= $active === 'index' ? ' active' : '' ?>" href="<?= $this->site ?>consultacom2"<?= $active === 'index' ? ' aria-current="page"' : '' ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 21v-1a8 8 0 0 1 16 0v1"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Consultas
                    <div class="nav-item-sub">Rafael · Beatriz · Camila · Juliana</div>
                </div>
                <div class="nav-dot" style="background:#c8102e"></div>
            </a>

            <a class="nav-item<?= $active === 'planilha' ? ' active' : '' ?>" href="<?= $this->site ?>consultacom2/planilha"<?= $active === 'planilha' ? ' aria-current="page"' : '' ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <line x1="3" y1="9" x2="21" y2="9"/>
                        <line x1="9" y1="9" x2="9" y2="21"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Planilha
                    <div class="nav-item-sub">Conferência do time · Status</div>
                </div>
                <div class="nav-dot" style="background:#d4a017"></div>
            </a>

            <a class="nav-item<?= $active === 'eventos' ? ' active' : '' ?>" href="<?= $this->site ?>consultacom2/eventos"<?= $active === 'eventos' ? ' aria-current="page"' : '' ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="17" rx="2"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Eventos
                    <div class="nav-item-sub">Cruzamento com card de Evento</div>
                </div>
                <div class="nav-dot" style="background:#2f7d32"></div>
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
