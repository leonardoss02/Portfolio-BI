<?php
/*
 * template/marketing.php
 *
 * Template do dashboard "Marketing" (Dashboard ADS) — controller `marketing`.
 * Cópia estrutural de template/rafaeltorres.php: sem sub-abas (só a view
 * principal + dicionário), sem verificação de login (nenhum dashboard do
 * projeto usa autenticação hoje).
 */

$uri      = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$segments = array_values(array_filter(explode('/', trim($uri, '/')), fn($s) => $s !== ''));
$tab      = $segments[1] ?? '';

$activeMap = [
    'comparativo'   => 'comparativo',
    'eventos'       => 'eventos',
    'desempenho'    => 'desempenho',
    'descartes'     => 'descartes',
    'classificacao' => 'classificacao',
    'dicionario'    => 'dicionario',
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
    <title>Acme EUA · BI · Marketing</title>

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

            <a class="<?= navClass('index', $active) ?>" href="<?= $this->site ?>marketing"<?= navAria('index', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6"  y1="20" x2="6"  y2="14"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Marketing
                    <div class="nav-item-sub">Leads · Funil · Campanhas</div>
                </div>
                <div class="nav-dot" style="background:#c8102e"></div>
            </a>

            <a class="<?= navClass('comparativo', $active) ?>" href="<?= $this->site ?>marketing/comparativo"<?= navAria('comparativo', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Comparativo Anual
                    <div class="nav-item-sub">Ano atual × ano anterior</div>
                </div>
                <div class="nav-dot" style="background:#1a6ec8"></div>
            </a>

            <a class="<?= navClass('eventos', $active) ?>" href="<?= $this->site ?>marketing/eventos"<?= navAria('eventos', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Eventos
                    <div class="nav-item-sub">Performance por formulário</div>
                </div>
                <div class="nav-dot" style="background:#c8102e"></div>
            </a>

            <a class="<?= navClass('desempenho', $active) ?>" href="<?= $this->site ?>marketing/desempenho"<?= navAria('desempenho', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <polyline points="3 17 9 11 13 15 21 7"/>
                        <polyline points="14 7 21 7 21 14"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Desempenho
                    <div class="nav-item-sub">Perfil do lead · série temporal</div>
                </div>
                <div class="nav-dot" style="background:#7c3aed"></div>
            </a>

            <a class="<?= navClass('descartes', $active) ?>" href="<?= $this->site ?>marketing/descartes"<?= navAria('descartes', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 6h18"/><path d="M7 12h14"/><path d="M11 18h10"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Descartes
                    <div class="nav-item-sub">Motivos · Comercial 1, 2 e 3</div>
                </div>
                <div class="nav-dot" style="background:#c8102e"></div>
            </a>

            <a class="<?= navClass('classificacao', $active) ?>" href="<?= $this->site ?>marketing/classificacao"<?= navAria('classificacao', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <polyline points="3 17 9 11 13 15 21 7"/>
                        <polyline points="14 7 21 7 21 14"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Classificação
                    <div class="nav-item-sub">Digital × Evento por form</div>
                </div>
                <div class="nav-dot" style="background:#4a90e2"></div>
            </a>

            <a class="<?= navClass('dicionario', $active) ?>" href="<?= $this->site ?>marketing/dicionario"<?= navAria('dicionario', $active) ?>>
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
