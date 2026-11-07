<?php
/*
 * template/executive.php
 *
 * Template compartilhado pelos 5 "tabs" do dashboard Executive View
 * (controller `executive`). Substitui o antigo shell index.html +
 * <iframe> do app-fonte: aqui não há mais iframe — cada tab é uma
 * rota real do MRPHP, renderizada no mesmo documento, e o tema
 * (claro/escuro) vive uma única vez neste template em vez de ser
 * sincronizado via postMessage entre pai e iframe.
 */

// Estado ativo do menu — a partir do 2º segmento da URL
// (o 1º é sempre o controller `executive`).
$uri      = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$segments = array_values(array_filter(explode('/', trim($uri, '/')), fn($s) => $s !== ''));
$tab      = $segments[1] ?? '';

$activeMap = [
    'digital'       => 'digital',
    'especialistas' => 'especialistas',
    'analitico'     => 'analitico',
    'serasa'        => 'serasa',
    'dicionario'    => 'dicionario',
];
$active   = $activeMap[$tab] ?? 'executive';
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
    <title>Acme EUA · BI · Executive View</title>

    <script>
    (function () {
        // Mesmo default do app-fonte: 'light' quando localStorage.theme
        // ainda não existe (ver index.html / *_view.html original).
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

            <a class="<?= navClass('executive', $active) ?>" href="<?= $this->site ?>executive"<?= navAria('executive', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <line x1="9" y1="3" x2="9" y2="21"/>
                        <line x1="9" y1="12" x2="21" y2="12"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Executive View
                    <div class="nav-item-sub">Global · Evento · Digital · Orgânico</div>
                </div>
                <div class="nav-dot" style="background:var(--red)"></div>
            </a>

            <a class="<?= navClass('digital', $active) ?>" href="<?= $this->site ?>executive/digital"<?= navAria('digital', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6"  y1="20" x2="6"  y2="14"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Digital × Evento
                    <div class="nav-item-sub">Funil Comparativo por Form</div>
                </div>
                <div class="nav-dot" style="background:var(--consulta)"></div>
            </a>

            <a class="<?= navClass('especialistas', $active) ?>" href="<?= $this->site ?>executive/especialistas"<?= navAria('especialistas', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Especialistas
                    <div class="nav-item-sub">Consultas · Combos · Conversão</div>
                </div>
                <div class="nav-dot" style="background:var(--gold)"></div>
            </a>

            <a class="<?= navClass('analitico', $active) ?>" href="<?= $this->site ?>executive/analitico"<?= navAria('analitico', $active) ?>>
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
                    Analítico
                    <div class="nav-item-sub">Esp. · Evento · Digital · Total</div>
                </div>
                <div class="nav-dot" style="background:var(--sinal)"></div>
            </a>

            <a class="<?= navClass('serasa', $active) ?>" href="<?= $this->site ?>executive/serasa"<?= navAria('serasa', $active) ?>>
                <div class="nav-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2l8 4v6c0 5-3.5 9-8 10-4.5-1-8-5-8-10V6z"/>
                        <path d="M9 12l2 2 4-4"/>
                    </svg>
                </div>
                <div class="nav-item-text">
                    Serasa
                    <div class="nav-item-sub">Crédito · Comercial 2 · Comercial 4</div>
                </div>
                <div class="nav-dot" style="background:var(--accent2)"></div>
            </a>

            <div class="nav-section-label" style="margin-top:8px">Referência</div>

            <a class="<?= navClass('dicionario', $active) ?>" href="<?= $this->site ?>executive/dicionario"<?= navAria('dicionario', $active) ?>>
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
                <div class="nav-dot" style="background:var(--fecho)"></div>
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
// ── Tema — aplicado uma única vez aqui (sem postMessage: não há mais iframe) ──
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

// ── Relógio no rodapé ─────────────────────────────────────────
function updateClock() {
    document.getElementById('footer-clock').textContent =
        new Date().toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
updateClock();
setInterval(updateClock, 30000);
</script>
</body>
</html>
