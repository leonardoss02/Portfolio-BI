<div class="dash-helenacastro">

<style>
/* Helena Castro — vendas diárias.
   Estilo minimalista e denso (ui-ux-pro-max: "Minimalism & Swiss Style",
   densidade de dashboard). Escala tipográfica local de 5 degraus:
   11 (rótulo de eixo/dado, label de KPI) · 12 (texto secundário) ·
   13 (título de card, tabela) · 16 (título de seção) · 18 (valor de KPI).
   Espaçamento em múltiplos de 4.
   Cores de série validadas (dataviz validate_palette.py, superfície do card
   nos dois temas): consulta #1a6ec8 · ingresso #b8860b · leads #c8102e
   (#e04a5f no escuro — o vermelho do tema fica em 2,9:1 sobre #161b27).
   Texto secundário usa --ink-2 (mesmos tons de --dic-muted, ≥ 4,5:1);
   o --muted global fica em 2,9:1 sobre o card. Rótulo de dado usa token de
   texto, nunca a cor da série. */
.dash-helenacastro {
  --s-consulta: #1a6ec8;
  --s-ingresso: #b8860b;
  --s-leads:    #c8102e;
  --ink-2:      #5f6a7d;
}
html.dark .dash-helenacastro {
  --s-leads: #e04a5f;
  --ink-2:   #8290a9;
}

/* KPIs compactos: valor e variação na mesma linha */
.dash-helenacastro .kpi-row { gap: 8px; }
.dash-helenacastro .kpi-chip {
  gap: 4px; padding: 8px 12px; min-width: 150px; box-shadow: none;
  transition: box-shadow .2s ease;
}
.dash-helenacastro .kpi-chip:hover { transform: none; box-shadow: var(--sh-sm); }
.dash-helenacastro .kpi-chip-label { font-size: 11px; color: var(--ink-2); }
.dash-helenacastro .kpi-line { display: flex; align-items: baseline; gap: 8px; min-width: 0; }
.dash-helenacastro .kpi-chip-val { font-size: 18px; min-width: 0; }
.dash-helenacastro .kpi-delta {
  font-family: var(--font-mono); font-size: 11px; font-weight: 600; line-height: 1;
  font-variant-numeric: tabular-nums; white-space: nowrap;
}
.dash-helenacastro .kpi-delta.up   { color: var(--fecho); }
.dash-helenacastro .kpi-delta.down { color: var(--danger); }
.dash-helenacastro .kpi-delta.flat { color: var(--ink-2); }
.dash-helenacastro .kpi-caption { margin-top: 8px; font-size: 12px; color: var(--ink-2); }

/* "Agrupar por" — segmented control, vale para todos os gráficos e a tabela */
.dash-helenacastro .toolbar {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  flex-wrap: wrap; margin: 16px 0 8px;
}
.dash-helenacastro .toolbar-title { font-size: 16px; font-weight: 600; color: var(--text); }
.dash-helenacastro .seg-wrap { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--ink-2); }
.dash-helenacastro .seg {
  display: inline-flex; gap: 2px; padding: 2px;
  background: var(--card2); border: 1px solid var(--border); border-radius: var(--r-sm);
}
.dash-helenacastro .seg-btn {
  min-height: 28px; padding: 4px 12px; border: 0; border-radius: 4px; background: transparent;
  font: 600 12px var(--font-ui); color: var(--ink-2); cursor: pointer;
  transition: background-color .15s ease, color .15s ease;
}
.dash-helenacastro .seg-btn:hover { color: var(--text); }
.dash-helenacastro .seg-btn.is-active { background: var(--card); color: var(--text); box-shadow: var(--sh-sm); }
.dash-helenacastro .seg-btn:focus-visible { outline: 2px solid var(--consulta); outline-offset: 1px; }

/* Cards de gráfico mais baixos */
.dash-helenacastro .chart-header { padding: 8px 16px; min-height: 40px; }
.dash-helenacastro .chart-body   { padding: 8px 16px; overflow: visible; }
.dash-helenacastro .chart-sub    { font-size: 12px; color: var(--ink-2); }
.dash-helenacastro .cv { position: relative; width: 100%; }
.dash-helenacastro .cv svg { display: block; border-radius: 4px; }
.dash-helenacastro .cv svg:focus { outline: none; }
.dash-helenacastro .cv svg:focus-visible { outline: 2px solid var(--consulta); outline-offset: 2px; }
.dash-helenacastro .cv-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 4px; text-align: center; font-size: 12px; color: var(--ink-2);
}
.dash-helenacastro .cv-empty strong { font-size: 13px; font-weight: 600; color: var(--text); }

/* Hierarquia: linha 1 = consultas (foco, largura inteira), linha 2 = leads e ingressos */
.dash-helenacastro .grid-2 {
  display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 12px;
}
@media (max-width: 1100px) {
  .dash-helenacastro .grid-2 { grid-template-columns: minmax(0, 1fr); }
}

/* Marcas SVG (cor só na marca; texto sempre em token de texto) */
.dash-helenacastro svg text { font-family: var(--font-ui); }
.dash-helenacastro .ax-text  { fill: var(--ink-2); font-size: 11px; }
.dash-helenacastro .ax-grid  { stroke: var(--border); stroke-width: 1; }
.dash-helenacastro .ax-base  { stroke: var(--ink-2); stroke-width: 1; opacity: .5; }
.dash-helenacastro .lbl {
  fill: var(--text); font-family: var(--font-mono); font-size: 11px; font-weight: 600;
  font-variant-numeric: tabular-nums;
}
.dash-helenacastro .lbl-ref  { fill: var(--ink-2); font-size: 11px; font-weight: 600; }
/* contorno na cor do card: rótulo legível por cima da linha da média e da grade */
.dash-helenacastro .lbl,
.dash-helenacastro .lbl-ref  { paint-order: stroke; stroke: var(--card); stroke-width: 3px; stroke-linejoin: round; }
.dash-helenacastro .ref-line { stroke: var(--ink-2); stroke-width: 1; stroke-dasharray: 4 4; }
.dash-helenacastro .mk-consulta { fill: var(--s-consulta); }
.dash-helenacastro .mk-ingresso { fill: var(--s-ingresso); }
.dash-helenacastro .mk-leads    { fill: var(--s-leads); }
.dash-helenacastro .is-parcial  { opacity: .45; }
.dash-helenacastro .band        { fill: var(--text); opacity: 0; }
.dash-helenacastro .band.is-hover { opacity: .06; }
.dash-helenacastro .hit        { fill: transparent; cursor: crosshair; }

/* Tooltip: componente compartilhado .bi-tooltip (fundo sempre escuro) */
.dash-helenacastro .tt-key { display: inline-flex; align-items: center; gap: 8px; }
.dash-helenacastro .tt-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.dash-helenacastro .tt-dot.none { background: transparent; }

/* Tabelas (relatório e clientes): todas as células centralizadas, inclusive
   cabeçalho e Total. O prefixo .dash-helenacastro vence o
   `.bi-table td.num { text-align: right }` global (bi-theme.css). */
.dash-helenacastro .bi-table th,
.dash-helenacastro .bi-table td,
.dash-helenacastro .bi-table th.num,
.dash-helenacastro .bi-table td.num { text-align: center; }
.dash-helenacastro .bi-table td.num { font-variant-numeric: tabular-nums; }
.dash-helenacastro .bi-table td.zero { color: var(--ink-2); }
.dash-helenacastro .tbl-scroll { max-height: 360px; overflow: auto; }
.dash-helenacastro .tbl-scroll thead th { position: sticky; top: 0; z-index: 1; background: var(--card2); }
.dash-helenacastro .bi-table tfoot td {
  position: sticky; bottom: 0; font-weight: 700;
  border-top: 2px solid var(--border); background: var(--card2);
}

.dash-helenacastro .bi-card-sub { color: var(--ink-2); }

/* Clientes — ordenação clicável */
.dash-helenacastro #thead-clientes th { cursor: pointer; user-select: none; }
.dash-helenacastro #thead-clientes th:hover,
.dash-helenacastro #thead-clientes th.th-active { background: var(--border); color: var(--text); }
.dash-helenacastro .cli-search {
  height: 32px; min-width: 220px; padding: 0 12px; border: 1px solid var(--border);
  border-radius: var(--r-sm); background: var(--card2); color: var(--text);
  font: 13px var(--font-ui); outline: none;
}
.dash-helenacastro .cli-search:focus-visible { border-color: var(--consulta); }

/* Modo demonstração (?demo=1): botão no cabeçalho, etiqueta e faixa em
   âmbar, para nenhum print com número fictício passar por dado real. O
   cabeçalho é escuro nos dois temas, por isso ali o tom é --hdr-gold. */
.dash-helenacastro .btn-demo[aria-pressed="true"] {
  border-color: var(--hdr-gold); color: var(--hdr-gold); background: rgba(216, 178, 95, .12);
}
.dash-helenacastro.is-demo .header-badge { border-color: var(--hdr-gold); color: var(--hdr-gold); }
.dash-helenacastro .demo-banner {
  display: none; margin: 0 0 12px; padding: 8px 12px;
  border: 1px dashed var(--sinal); border-radius: var(--r-sm); background: var(--sinal-glow);
  font-size: 12px; color: var(--text);
}
.dash-helenacastro.is-demo .demo-banner { display: block; }
.dash-helenacastro .demo-banner strong { color: var(--sinal); font-weight: 700; }
html.dark .dash-helenacastro .demo-banner strong { color: var(--hdr-gold); }

@media (prefers-reduced-motion: reduce) {
  .dash-helenacastro .kpi-chip,
  .dash-helenacastro .seg-btn,
  .dash-helenacastro .bi-tooltip { transition: none; }
}
</style>

<!-- ── LOADING ─────────────────────────────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>
<div class="bi-tooltip" id="chart-tooltip" role="tooltip" aria-hidden="true"></div>

<!-- ── HEADER ─────────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo">IS</div>
    <div>
      <div class="header-title">Dashboard Helena Castro</div>
      <div class="header-sub">Vendas diárias &middot; Consultas e ingressos</div>
    </div>
    <button class="btn-theme" id="theme-btn" type="button" onclick="toggleTheme()" aria-label="Alternar tema claro/escuro" title="Alternar tema">
      <svg id="theme-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      <svg id="theme-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
        <circle cx="12" cy="12" r="5"/>
        <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
        <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
      </svg>
    </button>
  </div>
  <div class="header-right">
    <div class="header-badge" id="filter-label-badge"><?= date('Y') ?></div>
    <button class="btn-tab-switch btn-demo" id="btn-demo" type="button" aria-pressed="false" onclick="toggleDemo()" title="Mostra a tela com números fictícios, para apresentação">Modo demonstração</button>
    <div class="tab-switch">
      <a class="tab-switch-btn active" href="<?= $this->site ?>helenacastro<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>" aria-current="page">Vendas diárias</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>helenacastro/jornada<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Jornada do cliente</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>helenacastro/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
    </div>
  </div>
</header>

<!-- ── FILTROS ─────────────────────────────────────────────────── -->
<section class="filters is-collapsible" aria-label="Filtros do painel">
  <button class="filters-toggle" type="button" aria-expanded="true" aria-controls="filters-fields">
    <span>Filtros</span>
    <span class="dd-caret filters-toggle-caret">&#x25BE;</span>
  </button>
  <div class="filters-fields" id="filters-fields">

  <!-- Últimos N [dias|semanas|meses] — opcional; ao abrir valem o Ano e o Mês atuais -->
  <div class="filter-pair">
    <span class="filter-label">Últimos</span>
    <div class="rel-period-group">
      <input type="number" id="rel-n" class="filter-input rel-n" min="1" step="1" inputmode="numeric" placeholder="—" autocomplete="off" aria-label="Quantidade de períodos">
      <select id="rel-unit" class="filter-select" aria-label="Unidade do período">
        <option value="dias">Dias</option>
        <option value="semanas">Semanas</option>
        <option value="meses">Meses</option>
      </select>
    </div>
  </div>

  <div class="filter-sep"></div>

  <!-- Ano -->
  <div class="filter-pair">
    <span class="filter-label">Ano</span>
    <div class="multi-dd-wrap" id="year-wrap">
      <button class="multi-dd-btn" id="year-btn" type="button">Todos <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="year-list"></div>
    </div>
  </div>

  <!-- Mês -->
  <div class="filter-pair">
    <span class="filter-label">Mês</span>
    <div class="multi-dd-wrap" id="month-wrap">
      <button class="multi-dd-btn" id="month-btn" type="button">Todos <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="month-list"></div>
    </div>
  </div>

  <div class="filter-sep"></div>

  <!-- Data range -->
  <div class="filter-pair">
    <span class="filter-label">Data</span>
    <div class="date-range-group">
      <div class="date-input-wrap">
        <input type="date" id="data-inicio" aria-label="Data início">
        <button class="date-clear-btn" id="data-inicio-clear" aria-label="Limpar data início">&#x2715;</button>
      </div>
      <span class="date-sep-label">até</span>
      <div class="date-input-wrap">
        <input type="date" id="data-fim" aria-label="Data fim">
        <button class="date-clear-btn" id="data-fim-clear" aria-label="Limpar data fim">&#x2715;</button>
      </div>
    </div>
  </div>

  <div class="filter-sep"></div>

  <!-- Form (busca sobre valores brutos de formPreenchido_leadV2/formPreenchido_dealV2) -->
  <div class="filter-pair">
    <span class="filter-label">Form</span>
    <div class="camp-wrap" id="form-wrap">
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="form-input" class="camp-input" type="text" placeholder="Pesquisar form..." autocomplete="off">
        <button class="camp-clear" id="form-clear" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-tags" id="form-tags"></div>
      <div class="camp-dropdown" id="form-dropdown"></div>
    </div>
  </div>

  <button class="btn-apply" onclick="applyFilters()">Aplicar</button>
  <button class="btn-clear" onclick="clearFilters()">Limpar</button>
  </div>
</section>
<script>
// Filtros sempre abertos ao entrar (ignora o "recolhido" lembrado) e o
// Aplicar não recolhe; só o botão FILTROS recolhe.
initFiltersToggle({ key: 'helenacastro', collapseOnApply: false }).open();
</script>

<!-- ── ERRO ───────────────────────────────────────────────────── -->
<div id="error-banner" role="alert"></div>

<!-- ── CONTEÚDO PRINCIPAL ──────────────────────────────────────── -->
<main class="main" id="dashboard-main">

  <div class="demo-banner" role="status"><strong>Modo demonstração:</strong> números fictícios, gerados só para apresentar a tela. O filtro de Form não se aplica. Para ver os dados reais, clique em "Sair da demonstração" no topo.</div>

  <!-- 4 KPIs (valor + variação contra o período anterior de mesmo tamanho) -->
  <div class="kpi-row">
    <div class="kpi-chip aquisicao">
      <span class="kpi-chip-label">Leads atendidos</span>
      <div class="kpi-line"><span class="kpi-chip-val" id="k-leads">—</span><span class="kpi-delta" id="d-leads"></span></div>
    </div>
    <div class="kpi-chip consulta">
      <span class="kpi-chip-label">Consultas vendidas</span>
      <div class="kpi-line"><span class="kpi-chip-val" id="k-consultas">—</span><span class="kpi-delta" id="d-consultas"></span></div>
    </div>
    <div class="kpi-chip consulta">
      <span class="kpi-chip-label">Conversão em consulta</span>
      <div class="kpi-line"><span class="kpi-chip-val" id="k-conv">—</span><span class="kpi-delta" id="d-conv"></span></div>
    </div>
    <div class="kpi-chip evento">
      <span class="kpi-chip-label">Ingressos vendidos</span>
      <div class="kpi-line"><span class="kpi-chip-val" id="k-ingressos">—</span><span class="kpi-delta" id="d-ingressos"></span></div>
    </div>
  </div>
  <div class="kpi-caption" id="kpi-caption"></div>

  <div class="toolbar">
    <h2 class="toolbar-title" id="toolbar-title">Evolução por dia</h2>
    <div class="seg-wrap">
      <span id="seg-label">Agrupar por</span>
      <div class="seg" role="group" aria-labelledby="seg-label">
        <button type="button" class="seg-btn is-active" data-modo="dia"    aria-pressed="true"  onclick="setModo('dia')">Dia</button>
        <button type="button" class="seg-btn"           data-modo="semana" aria-pressed="false" onclick="setModo('semana')">Semana</button>
        <button type="button" class="seg-btn"           data-modo="mes"    aria-pressed="false" onclick="setModo('mes')">Mês</button>
      </div>
    </div>
  </div>

  <!-- Linha 1: consultas (foco) -->
  <div class="chart-card">
    <div class="chart-header">
      <div class="chart-title-group">
        <span class="chart-dot" style="background:var(--s-consulta)"></span>
        <span class="chart-title" id="t-consultas">Consultas vendidas por dia</span>
      </div>
      <span class="chart-sub" id="sub-consultas">Linha tracejada: média do período</span>
    </div>
    <div class="chart-body"><div class="cv" id="cv-consultas"></div></div>
  </div>

  <!-- Linha 2: leads e ingressos -->
  <div class="grid-2">
    <div class="chart-card">
      <div class="chart-header">
        <div class="chart-title-group">
          <span class="chart-dot" style="background:var(--s-leads)"></span>
          <span class="chart-title" id="t-leads">Leads atendidos por dia</span>
        </div>
      </div>
      <div class="chart-body"><div class="cv" id="cv-leads"></div></div>
    </div>
    <div class="chart-card">
      <div class="chart-header">
        <div class="chart-title-group">
          <span class="chart-dot" style="background:var(--s-ingresso)"></span>
          <span class="chart-title" id="t-ingressos">Ingressos vendidos por dia</span>
        </div>
      </div>
      <div class="chart-body"><div class="cv" id="cv-ingressos"></div></div>
    </div>
  </div>

  <!-- Relatório por período -->
  <div class="bi-card" style="margin-top:16px">
    <div class="bi-card-header">
      <span class="bi-card-title" id="t-tabela">Relatório por dia</span>
      <span class="bi-card-sub" id="tabela-sub"></span>
    </div>
    <div class="tbl-scroll">
      <table class="bi-table">
        <thead>
          <tr>
            <th id="th-periodo">Dia</th>
            <th class="num">Leads</th>
            <th class="num">Leads de consulta</th>
            <th class="num">Consultas</th>
            <th class="num">Conversão</th>
            <th class="num">Ingressos</th>
          </tr>
        </thead>
        <tbody id="tbody-periodo"></tbody>
        <tfoot id="tfoot-periodo"></tfoot>
      </table>
    </div>
  </div>

  <!-- TABELA DE CLIENTES (deals vendidos) -->
  <div class="bi-card" style="margin-top:16px">
    <div class="bi-card-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
      <span class="bi-card-title">Clientes</span>
      <div style="display:flex;align-items:center;gap:8px">
        <input type="text" id="clientes-search" class="cli-search" placeholder="Buscar por nome do deal ou email..." aria-label="Buscar cliente por nome do deal ou email" autocomplete="off" oninput="filtrarClientes()">
        <span class="bi-card-sub" id="clientes-count"></span>
      </div>
    </div>
    <div class="tbl-scroll">
      <table class="bi-table">
        <thead id="thead-clientes"></thead>
        <tbody id="tbody-clientes">
          <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--ink-2)">Carregando…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="last-updated" id="last-updated"></div>

</main>
</div>

<script>
// ══════════════════════════════════════════════════════════════
//  Helena Castro — vendas diárias
//  Consome: helenacastro/dados, helenacastro/forms (controller MRPHP)
//
//  O backend devolve a série DIÁRIA do período (dias sem movimento já com
//  zero) e os KPIs do período e do período anterior; o agrupamento por
//  semana/mês é feito aqui (montarSerie), então trocar "Agrupar por" não
//  faz nova requisição.
//
//  createMultiDropdown, createSearchDropdown, createRelativePeriod,
//  getDateInput, setEffectiveDate, clearDateInput, isoLocal e fNum
//  são fornecidas por js/bi-dashboard.js
//  (carregado no <head> pelo template, antes deste script).
// ══════════════════════════════════════════════════════════════

const API_URL   = '<?= $this->site ?>helenacastro/dados';
const FORMS_URL = '<?= $this->site ?>helenacastro/forms';
const MONTHS    = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const NOW_YEAR  = String(new Date().getFullYear());
const NOW_MONTH = String(new Date().getMonth() + 1);
// De 2024 até o ano atual: a lista ganha o ano novo sozinha na virada.
const YEARS     = Array.from({ length: Math.max(1, Number(NOW_YEAR) - 2023) }, (_, i) => String(2024 + i));
const DIAS_SEMANA = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];

let currentModo   = 'dia';
let ultimoPayload = null;
// Modo demonstração (?demo=1 na URL): números fictícios gerados aqui
// (montarPayloadDemo), sem chamar o backend.
let modoDemo = new URLSearchParams(location.search).get('demo') === '1';

const yearDropdown  = createMultiDropdown('year-wrap',  'year-btn',  'year-list',  YEARS,  l => l, l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);
const formDropdown  = createSearchDropdown('form-wrap', 'form-input', 'form-dropdown', 'form-clear', 'form-tags', 'Nenhum form encontrado');
// "Últimos N" — um substitui o outro com Ano/Mês/Data (ver createRelativePeriod).
const relPeriod = createRelativePeriod({
  numId: 'rel-n', unitId: 'rel-unit',
  conflicts: [[yearDropdown, 'year-list'], [monthDropdown, 'month-list']],
});

// Abre filtrado no mês atual (até hoje).
yearDropdown.select([NOW_YEAR]);
monthDropdown.select([NOW_MONTH]);

// ── Campos de Data ──────────────────────────────────────────────
// Ano/Mês pintam o intervalo nos campos em modo exibição (.is-effective,
// que getDateInput ignora). Editar uma data pintada assim torna as duas
// filtro de verdade, com os valores que estão na tela, e solta o Ano/Mês;
// escolher Ano ou Mês de novo apaga as datas digitadas. As datas pintadas
// pelo "Últimos N" são tratadas pelo createRelativePeriod, que ouve o
// mesmo evento antes daqui.
const DATAS = ['data-inicio', 'data-fim'];
const wrapData = id => document.getElementById(id).closest('.date-input-wrap');
DATAS.forEach(id => {
  document.getElementById(id).addEventListener('change', () => {
    if (wrapData(id).classList.contains('is-effective')) {
      DATAS.forEach(o => wrapData(o).classList.remove('is-effective'));
      yearDropdown.reset();
      monthDropdown.reset();
    }
    DATAS.forEach(o => document.getElementById(o + '-clear').classList.toggle('visible', !!document.getElementById(o).value));
  });
  document.getElementById(id + '-clear').addEventListener('click', () => clearDateInput(id, id + '-clear'));
});
['year-list', 'month-list'].forEach(listId => {
  document.getElementById(listId).addEventListener('mousedown', e => {
    if (!e.target.closest('.multi-dd-item')) return;
    DATAS.forEach(id => { if (getDateInput(id)) clearDateInput(id, id + '-clear'); });
  });
});

fetch(FORMS_URL, { cache: 'no-store' })
  .then(res => res.json())
  .then(list => formDropdown.load(Array.isArray(list) ? list : []))
  .catch(err => console.error('Erro ao carregar forms:', err));

function set(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }
function escHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function fPct1(r) { return (r * 100).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%'; }
function fDec1(v) { return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }); }

// ── Datas (horário local; ver isoLocal em bi-dashboard.js) ──────
function parseIso(s) { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); }
function addDias(d, n) { return new Date(d.getFullYear(), d.getMonth(), d.getDate() + n); }
function fDataBr(iso) { const [y, m, d] = iso.split('-'); return `${d}/${m}/${y}`; }
function fDiaMes(iso) { const [, m, d] = iso.split('-'); return `${d}/${m}`; }

// ══════════════════════════════════════════════════════════════
//  AGRUPAMENTO (dia / semana ISO / mês) da série diária do backend
// ══════════════════════════════════════════════════════════════
function chavePeriodo(iso, modo) {
  if (modo === 'mes') return iso.slice(0, 7);
  if (modo === 'semana') {
    const d = parseIso(iso);
    return isoLocal(addDias(d, -((d.getDay() + 6) % 7))); // segunda-feira da semana (ISO)
  }
  return iso;
}

function rotuloPeriodo(chave, modo) {
  if (modo === 'mes') { const [y, m] = chave.split('-'); return MONTHS[Number(m) - 1] + '/' + y.slice(2); }
  if (modo === 'semana') return 'Sem ' + fDiaMes(chave);
  return fDiaMes(chave);
}

// ini/fim = primeiro e último dia do período que caem dentro do filtro
// (semana/mês nas pontas do intervalo vêm cortados).
function rotuloLongo(chave, modo, ini, fim) {
  if (modo === 'mes') { const [y, m] = chave.split('-'); return MONTHS[Number(m) - 1] + ' de ' + y; }
  if (modo === 'semana') return fDiaMes(ini) + ' a ' + fDataBr(fim);
  return DIAS_SEMANA[parseIso(chave).getDay()] + ', ' + fDataBr(chave);
}

function diasNoPeriodo(chave, modo) {
  if (modo === 'semana') return 7;
  if (modo === 'mes') { const [y, m] = chave.split('-').map(Number); return new Date(y, m, 0).getDate(); }
  return 1;
}

function somar(dias) {
  const k = dias.reduce((s, d) => {
    s.leads += d.leads; s.leads_consultas += d.leads_consultas;
    s.consultas_vendidas += d.consultas_vendidas; s.ingressos_vendidos += d.ingressos_vendidos;
    return s;
  }, { leads: 0, leads_consultas: 0, consultas_vendidas: 0, ingressos_vendidos: 0 });
  k.conv_consultas = k.leads_consultas ? k.consultas_vendidas / k.leads_consultas : 0;
  return k;
}

function montarSerie(dias, modo) {
  const hoje    = isoLocal(new Date());
  const buckets = new Map();
  dias.forEach(d => {
    const ch = chavePeriodo(d.data, modo);
    if (!buckets.has(ch)) buckets.set(ch, []);
    buckets.get(ch).push(d);
  });
  return [...buckets.entries()].map(([chave, ds]) => {
    const k = somar(ds);
    // incompleto: cortado pelo filtro (menos dias que o normal) ou chega a
    // hoje, que ainda não terminou
    const parcial = ds.length < diasNoPeriodo(chave, modo) || ds[ds.length - 1].data >= hoje;
    return {
      chave, parcial, label: rotuloPeriodo(chave, modo),
      titulo: rotuloLongo(chave, modo, ds[0].data, ds[ds.length - 1].data) + (parcial ? ' (parcial)' : ''),
      ...k,
    };
  });
}

// ══════════════════════════════════════════════════════════════
//  MODO DEMONSTRAÇÃO — dados fictícios no mesmo formato de
//  helenacastro/dados. Cada dia é sorteado com semente na própria data:
//  o mesmo dia sempre dá os mesmos números, qualquer que seja o filtro.
// ══════════════════════════════════════════════════════════════
function rngDemo(seed) {
  let h = 1779033703 ^ seed.length;
  for (let i = 0; i < seed.length; i++) { h = Math.imul(h ^ seed.charCodeAt(i), 3432918353); h = h << 13 | h >>> 19; }
  let a = h >>> 0;
  return () => {
    a = a + 0x6D2B79F5 | 0;
    let t = Math.imul(a ^ a >>> 15, 1 | a);
    t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t;
    return ((t ^ t >>> 14) >>> 0) / 4294967296;
  };
}

const DEMO_INICIO_TENDENCIA = new Date(2026, 0, 1);
const DEMO_FORMS   = ['consulta-digital-2026', 'consulta-indicacao-2026', 'otr-curitiba-2026', 'otr-belohorizonte-2026'];

// Dia útil: 30–70 leads, ~30–40% de consulta, 0–3 consultas (subindo ao
// longo de 2026) e 4–12 ingressos; fim de semana mais fraco.
function diaDemo(iso) {
  const r   = rngDemo(iso);
  const dt  = parseIso(iso);
  const fds = dt.getDay() === 0 || dt.getDay() === 6;
  const t   = Math.min(1.5, Math.max(0.5, 0.6 + (dt - DEMO_INICIO_TENDENCIA) / (86400000 * 365) * 0.8));
  const leads  = Math.round((fds ? 14 : 30) + r() * (fds ? 12 : 40));
  const leadsC = Math.round(leads * (0.30 + r() * 0.10));
  const cons   = Math.min(leadsC, Math.floor(r() * (fds ? 1.6 : 3.6) * t));
  const ingr   = Math.round((fds ? 1 : 4) + r() * (fds ? 4 : 8));
  return { data: iso, leads, leads_consultas: leadsC, consultas_vendidas: cons, ingressos_vendidos: ingr };
}

function diasDemo(ini, fim) {
  const out = [];
  for (let d = parseIso(ini); d <= parseIso(fim); d = addDias(d, 1)) out.push(diaDemo(isoLocal(d)));
  return out;
}

function kpisDemo(dias) {
  const k = somar(dias);
  k.dias = dias.length;
  return k;
}

function montarPayloadDemo(ini, fim) {
  const dias   = diasDemo(ini, fim);
  const n      = dias.length;
  const antIni = isoLocal(addDias(parseIso(ini), -n));
  const antFim = isoLocal(addDias(parseIso(ini), -1));
  const kpis   = kpisDemo(dias);

  // Clientes: uma linha por venda, da mais recente para a mais antiga, até 500
  const linhas = [];
  for (let i = n - 1; i >= 0 && linhas.length < 500; i--) {
    const d = dias[i];
    const vendas = Array(d.consultas_vendidas).fill('Consulta').concat(Array(d.ingressos_vendidos).fill('Ingresso'));
    vendas.forEach((tipo, j) => {
      if (linhas.length >= 500) return;
      const num = String(linhas.length + 1).padStart(3, '0');
      linhas.push({
        id_bitrix:  String(90000 + linhas.length),
        nome_deal:  'Cliente Exemplo ' + num,
        email:      'cliente' + num + '@exemplo.com',
        data_venda: fDataBr(d.data),
        tipo,
        form:       tipo === 'Consulta' ? DEMO_FORMS[j % 2] : DEMO_FORMS[2 + (j % 2)],
      });
    });
  }

  return {
    meta: { data_inicio: ini, data_fim: fim, form: null, demo: true },
    dias, kpis,
    kpis_anterior:    kpisDemo(diasDemo(antIni, antFim)),
    periodo_anterior: { data_inicio: antIni, data_fim: antFim },
    linhas,
  };
}

function aplicarModoDemo() {
  document.querySelector('.dash-helenacastro').classList.toggle('is-demo', modoDemo);
  const btn = document.getElementById('btn-demo');
  btn.setAttribute('aria-pressed', modoDemo ? 'true' : 'false');
  btn.textContent = modoDemo ? 'Sair da demonstração' : 'Modo demonstração';
  const form = document.getElementById('form-input');
  form.disabled    = modoDemo;
  form.placeholder = modoDemo ? 'Não se aplica na demonstração' : 'Pesquisar form...';
  // As abas do cabeçalho levam o ?demo=1 junto, para a apresentação seguir nas outras telas.
  document.querySelectorAll('.tab-switch a').forEach(a => {
    const u = new URL(a.href);
    if (modoDemo) u.searchParams.set('demo', '1'); else u.searchParams.delete('demo');
    a.href = u.toString();
  });
}

// Liga/desliga e grava na URL (?demo=1), preservando os outros parâmetros
// (ex.: ?to=1 do embed) — o link copiado já abre em demonstração.
function toggleDemo() {
  modoDemo = !modoDemo;
  const u = new URL(location.href);
  if (modoDemo) u.searchParams.set('demo', '1'); else u.searchParams.delete('demo');
  history.replaceState(null, '', u);
  aplicarModoDemo();
  applyFilters();
}

// ══════════════════════════════════════════════════════════════
//  KPIs
// ══════════════════════════════════════════════════════════════
function fillDelta(id, atual, anterior, tipo, legenda) {
  const el = document.getElementById(id);
  if (anterior === null || anterior === undefined) { el.textContent = ''; el.className = 'kpi-delta'; el.title = ''; return; }
  let txt, dir;
  if (tipo === 'pp') {
    const diff = (atual - anterior) * 100;
    dir = Math.abs(diff) < 0.05 ? 'flat' : diff > 0 ? 'up' : 'down';
    txt = (diff > 0 ? '+' : '') + fDec1(diff) + ' p.p.';
  } else if (!anterior) {
    dir = atual > 0 ? 'up' : 'flat';
    txt = atual > 0 ? 'novo' : 'sem variação';
  } else {
    const pct = (atual - anterior) / anterior * 100;
    dir = Math.abs(pct) < 0.5 ? 'flat' : pct > 0 ? 'up' : 'down';
    txt = (pct > 0 ? '+' : '') + Math.round(pct) + '%';
  }
  const seta = dir === 'up' ? '▲' : dir === 'down' ? '▼' : '■';
  el.className = 'kpi-delta ' + dir;
  el.textContent = seta + ' ' + txt;
  el.title = legenda;
}

function fillKpis(p) {
  const k = p.kpis, a = p.kpis_anterior;
  const anterior = a
    ? `${k.dias} dias anteriores (${fDataBr(p.periodo_anterior.data_inicio)} a ${fDataBr(p.periodo_anterior.data_fim)})`
    : '';
  const legenda = anterior ? 'Comparado com os ' + anterior : '';
  set('k-leads',     fNum(k.leads));
  set('k-consultas', fNum(k.consultas_vendidas));
  set('k-conv',      fPct1(k.conv_consultas));
  set('k-ingressos', fNum(k.ingressos_vendidos));
  fillDelta('d-leads',     k.leads,               a && a.leads,               null, legenda);
  fillDelta('d-consultas', k.consultas_vendidas,  a && a.consultas_vendidas,  null, legenda);
  fillDelta('d-conv',      k.conv_consultas,      a && a.conv_consultas,      'pp', legenda);
  fillDelta('d-ingressos', k.ingressos_vendidos,  a && a.ingressos_vendidos,  null, legenda);
  set('kpi-caption', anterior ? 'Variação em relação aos ' + anterior + '.' : '');
}

// ══════════════════════════════════════════════════════════════
//  GRÁFICOS — SVG à mão, 1 eixo por gráfico (sem eixo duplo).
//  Rótulo de dado em toda marca que couber; quando todas cabem, o eixo Y
//  e a grade saem (os rótulos já dizem o valor). Quando não cabem, as
//  colunas rotulam só o pico e o período mais recente e o eixo Y volta.
// ══════════════════════════════════════════════════════════════
const SERIES_INFO = {
  leads:              { label: 'Leads atendidos',    cor: 'var(--s-leads)' },
  leads_consultas:    { label: 'Leads de consulta',  cor: null },
  consultas_vendidas: { label: 'Consultas vendidas', cor: 'var(--s-consulta)' },
  conv_consultas:     { label: 'Conversão',          cor: null, fmt: fPct1 },
  ingressos_vendidos: { label: 'Ingressos vendidos', cor: 'var(--s-ingresso)' },
};

function escalaY(max, ticks) {
  if (max <= 0) return { max: ticks, step: 1 };
  const bruto = max / ticks;
  const mag   = Math.pow(10, Math.floor(Math.log10(bruto)));
  const n     = bruto / mag;
  const step  = Math.max(1, (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * mag);
  return { max: Math.ceil(max / step) * step, step };
}

// Coluna com ponta arredondada de 4px e base reta na linha de base.
function colunaPath(x, y, w, h) {
  if (h <= 0) return '';
  const r = Math.min(4, w / 2, h);
  return `M${x},${y + h}V${y + r}Q${x},${y} ${x + r},${y}H${x + w - r}Q${x + w},${y} ${x + w},${y + r}V${y + h}Z`;
}

// ~6,6px por caractere a 11px (Fira).
function largTexto(s) { return String(s).length * 6.6; }

// De quantos em quantos períodos cabe um texto, contando a partir do fim.
function cadencia(textos, slot, folga) {
  const larg = Math.max(0, ...textos.map(largTexto)) + folga;
  return Math.max(1, Math.ceil(larg / slot));
}
function noRitmo(i, n, passo) { return (n - 1 - i) % passo === 0; }
function xRotulo(x, texto, W) {
  const meia = largTexto(texto) / 2;
  return Math.min(W - meia - 2, Math.max(meia + 2, x));
}

function eixoX(W, H, m, slot, serie) {
  const n = serie.length;
  const passo = cadencia(serie.map(d => d.label), slot, 12);
  let s = '';
  serie.forEach((d, i) => {
    if (!noRitmo(i, n, passo)) return;
    s += `<text class="ax-text" x="${xRotulo(m.l + slot * i + slot / 2, d.label, W)}" y="${H - 6}" text-anchor="middle">${escHtml(d.label)}</text>`;
  });
  return s;
}

function eixoY(W, H, m, esc, comGrade) {
  const base = H - m.b;
  let s = `<line class="ax-base" x1="${m.l}" y1="${base}" x2="${W - m.r}" y2="${base}"/>`;
  if (!comGrade) return s;
  s += `<text class="ax-text" x="${m.l - 8}" y="${base + 4}" text-anchor="end">0</text>`;
  for (let v = esc.step; v <= esc.max; v += esc.step) {
    const y = m.t + (H - m.t - m.b) * (1 - v / esc.max);
    s += `<line class="ax-grid" x1="${m.l}" y1="${y}" x2="${W - m.r}" y2="${y}"/>`;
    s += `<text class="ax-text" x="${m.l - 8}" y="${y + 4}" text-anchor="end">${fNum(v)}</text>`;
  }
  return s;
}

function faixas(cls, m, H, slot, n) {
  let s = '';
  for (let i = 0; i < n; i++) {
    s += `<rect class="${cls}" data-i="${i}" x="${m.l + slot * i}" y="${m.t}" width="${slot}" height="${H - m.t - m.b}"/>`;
  }
  return s;
}

function vazio(el, h, titulo) {
  el.innerHTML = `<div class="cv-empty" style="height:${h}px">
    <strong>${escHtml(titulo)}</strong>
    <span>Amplie o intervalo nos filtros para ver a evolução.</span>
  </div>`;
}

// Mouse: faixa por período. Teclado: 1 parada de Tab por gráfico e as
// setas percorrem os períodos (Home/End vão às pontas, Esc fecha).
function ligarInteracao(el, serie) {
  const svg = el.querySelector('svg');
  const n   = serie.length;
  let atual = -1;
  const limpar = () => {
    svg.querySelectorAll('.is-hover').forEach(b => b.classList.remove('is-hover'));
    hideTooltip();
  };
  const destacar = (i, ev) => {
    limpar();
    svg.querySelectorAll(`.band[data-i="${i}"]`).forEach(b => b.classList.add('is-hover'));
    if (!ev) {
      const r = svg.querySelector(`.band[data-i="${i}"]`).getBoundingClientRect();
      ev = { clientX: r.x + r.width / 2, clientY: r.y + r.height / 3 };
    }
    showTooltip(ev, serie[i]);
  };
  svg.querySelectorAll('.hit').forEach(r => {
    const i = Number(r.dataset.i);
    r.addEventListener('mouseenter', e => { atual = i; destacar(i, e); });
    r.addEventListener('mousemove', posTooltip);
    r.addEventListener('mouseleave', limpar);
  });
  svg.setAttribute('tabindex', '0');
  svg.addEventListener('focus', () => { if (atual < 0 || atual >= n) atual = n - 1; destacar(atual); });
  svg.addEventListener('blur', limpar);
  svg.addEventListener('keydown', e => {
    const passos = { ArrowLeft: -1, ArrowRight: 1, Home: -n, End: n };
    if (e.key === 'Escape') { limpar(); return; }
    if (!(e.key in passos)) return;
    e.preventDefault();
    atual = Math.min(n - 1, Math.max(0, atual + passos[e.key]));
    destacar(atual);
  });
}

function resumoAria(nome, serie, key) {
  const vals  = serie.map(d => d[key]);
  const max   = Math.max(...vals);
  const total = vals.reduce((a, b) => a + b, 0);
  return `${nome}: ${fNum(total)} no período, pico de ${fNum(max)} em ${serie[vals.indexOf(max)].titulo}. Use as setas para percorrer os períodos.`;
}

// Colunas de 1 série. opts: { key, cls, h, media, nome, vazio }
function graficoColunas(elId, serie, opts) {
  const el   = document.getElementById(elId);
  const H    = opts.h;
  const n    = serie.length;
  const vals = serie.map(d => d[opts.key]);
  if (!n || !vals.some(v => v > 0)) return vazio(el, H, opts.vazio);

  const W      = Math.max(260, el.clientWidth);
  const textos = vals.map(fNum);
  const m      = { t: 20, r: opts.media ? 72 : 8, b: 22, l: 8 };
  let slot     = (W - m.l - m.r) / n;
  let passo    = cadencia(textos, slot, 4);
  if (passo > 1) { m.l = 36; slot = (W - m.l - m.r) / n; passo = cadencia(textos, slot, 4); }
  const todos = passo === 1;

  const maxV = Math.max(...vals);
  const esc  = todos ? { max: maxV, step: maxV } : escalaY(maxV, 3);
  const cH   = H - m.t - m.b;
  const bw   = Math.max(2, Math.min(24, slot * 0.62));
  const y    = v => m.t + cH * (1 - v / esc.max);

  // Denso demais para rotular tudo (rótulo cairia sobre a coluna vizinha):
  // só o pico e o período mais recente; o eixo Y cobre o resto.
  const iPico    = vals.lastIndexOf(maxV);
  const ultimoOk = iPico === n - 1 || (n - 1 - iPico) * slot >= largTexto(textos[n - 1]) + 4;
  const rotular  = i => vals[i] > 0 && (todos || i === iPico || (i === n - 1 && ultimoOk));

  let s = eixoY(W, H, m, esc, !todos) + eixoX(W, H, m, slot, serie) + faixas('band', m, H, slot, n);
  serie.forEach((d, i) => {
    const v = vals[i];
    const x = m.l + slot * i + (slot - bw) / 2;
    s += `<path class="${opts.cls}${d.parcial ? ' is-parcial' : ''}" d="${colunaPath(x, y(v), bw, m.t + cH - y(v))}"/>`;
    if (rotular(i)) {
      s += `<text class="lbl" x="${xRotulo(x + bw / 2, textos[i], W)}" y="${y(v) - 6}" text-anchor="middle">${textos[i]}</text>`;
    }
  });
  if (opts.media) {
    // média só dos períodos completos (semana/mês cortados nas pontas puxariam para baixo)
    const base  = serie.some(d => !d.parcial) ? serie.filter(d => !d.parcial) : serie;
    const media = base.reduce((a, d) => a + d[opts.key], 0) / base.length;
    const ym    = y(media);
    s += `<line class="ref-line" x1="${m.l}" y1="${ym}" x2="${W - m.r}" y2="${ym}"/>`;
    s += `<text class="lbl-ref" x="${W - m.r + 8}" y="${ym + 4}">média ${fDec1(media)}</text>`;
  }
  s += faixas('hit', m, H, slot, n);

  el.innerHTML = `<svg width="${W}" height="${H}" viewBox="0 0 ${W} ${H}" role="img" aria-label="${escHtml(resumoAria(opts.nome, serie, opts.key))}">${s}</svg>`;
  ligarInteracao(el, serie);
}

// ── Tooltip (mesmo conteúdo em todos os gráficos: o período inteiro) ──
function showTooltip(e, d) {
  const tip  = document.getElementById('chart-tooltip');
  const rows = Object.entries(SERIES_INFO).map(([k, info]) => `
    <div class="bi-tooltip-row">
      <span class="bi-tooltip-label tt-key"><span class="tt-dot ${info.cor ? '' : 'none'}" style="${info.cor ? 'background:' + info.cor : ''}"></span>${info.label}</span>
      <span class="bi-tooltip-val">${info.fmt ? info.fmt(d[k] || 0) : fNum(d[k] || 0)}</span>
    </div>`).join('');
  tip.innerHTML = `<div class="bi-tooltip-inner"><div class="bi-tooltip-title">${escHtml(d.titulo)}</div>${rows}</div>`;
  tip.classList.add('visible');
  posTooltip(e);
}
function posTooltip(e) {
  const tip = document.getElementById('chart-tooltip');
  const mg = 12, tw = tip.offsetWidth || 220, th = tip.offsetHeight || 200;
  let x = e.clientX + mg, y = e.clientY - th - mg;
  if (x + tw > window.innerWidth - mg) x = e.clientX - tw - mg;
  if (y < mg) y = e.clientY + mg;
  tip.style.left = x + 'px';
  tip.style.top  = y + 'px';
}
function hideTooltip() { document.getElementById('chart-tooltip').classList.remove('visible'); }

const SUFIXO_MODO = { dia: 'por dia', semana: 'por semana', mes: 'por mês' };

function renderGraficos(serie) {
  const suf = SUFIXO_MODO[currentModo];
  set('toolbar-title', 'Evolução ' + suf);
  set('t-consultas',  'Consultas vendidas ' + suf);
  set('t-leads',      'Leads atendidos ' + suf);
  set('t-ingressos',  'Ingressos vendidos ' + suf);
  set('sub-consultas', 'Linha tracejada: média do período' + (serie.some(d => d.parcial) ? ' · Barra clara: período incompleto' : ''));
  graficoColunas('cv-consultas', serie, { key: 'consultas_vendidas', cls: 'mk-consulta', h: 180, media: true, nome: 'Consultas vendidas ' + suf, vazio: 'Nenhuma consulta vendida no período' });
  graficoColunas('cv-leads',     serie, { key: 'leads',              cls: 'mk-leads',    h: 150, nome: 'Leads atendidos ' + suf,    vazio: 'Nenhum lead no período' });
  graficoColunas('cv-ingressos', serie, { key: 'ingressos_vendidos', cls: 'mk-ingresso', h: 150, nome: 'Ingressos vendidos ' + suf, vazio: 'Nenhum ingresso vendido no período' });
}

// ══════════════════════════════════════════════════════════════
//  TABELA POR PERÍODO (mais recente primeiro + linha de total)
// ══════════════════════════════════════════════════════════════
function td(v, txt) { return `<td class="num${v === 0 ? ' zero' : ''}">${txt ?? fNum(v)}</td>`; }

function fillTabela(serie, k) {
  const nomes = { dia: ['Relatório por dia', 'Dia'], semana: ['Relatório por semana', 'Semana'], mes: ['Relatório por mês', 'Mês'] };
  set('t-tabela', nomes[currentModo][0]);
  set('th-periodo', nomes[currentModo][1]);
  set('tabela-sub', serie.length + (serie.length === 1 ? ' linha' : ' linhas'));

  document.getElementById('tbody-periodo').innerHTML = serie.slice().reverse().map(d => `
    <tr>
      <td>${escHtml(d.titulo)}</td>
      ${td(d.leads)}${td(d.leads_consultas)}${td(d.consultas_vendidas)}
      ${td(d.leads_consultas ? d.conv_consultas : 0, d.leads_consultas ? fPct1(d.conv_consultas) : '—')}
      ${td(d.ingressos_vendidos)}
    </tr>`).join('');

  document.getElementById('tfoot-periodo').innerHTML = `
    <tr>
      <td>Total</td>
      ${td(k.leads)}${td(k.leads_consultas)}${td(k.consultas_vendidas)}
      ${td(k.conv_consultas, fPct1(k.conv_consultas))}
      ${td(k.ingressos_vendidos)}
    </tr>`;
}

// ── Tabela de Clientes (deals vendidos) ──────────────────────────
const COLS_CLIENTES = [
  { k: 'id_bitrix',  label: 'IdBitrix',   type: 'text' },
  { k: 'nome_deal',  label: 'Nome Deal',  type: 'text' },
  { k: 'email',      label: 'Email',      type: 'text' },
  { k: 'data_venda', label: 'Data Venda', type: 'date' },
  { k: 'tipo',       label: 'Tipo',       type: 'text' },
  { k: 'form',       label: 'Form',       type: 'text' },
];
let ultimasLinhasClientes = [];
let _sortKeyClientes = null; // null = mantém a ordem original vinda do backend
let _sortDirClientes = 'asc';

function sortByClientes(key) {
  if (_sortKeyClientes === key) {
    _sortDirClientes = _sortDirClientes === 'asc' ? 'desc' : 'asc';
  } else {
    _sortKeyClientes = key;
    _sortDirClientes = 'asc';
  }
  renderHeadClientes();
  filtrarClientes();
}

// dd/mm/yyyy -> yyyymmdd (string) para comparar; vazio/— sempre vai pro final
function sortValueClientes(v, type) {
  if (v === null || v === undefined || v === '') return null;
  if (type === 'date') {
    const [d, m, y] = String(v).split('/');
    return y + m.padStart(2, '0') + d.padStart(2, '0');
  }
  return String(v).toLowerCase();
}

function ordenarClientes(linhas) {
  if (!_sortKeyClientes) return linhas;
  const col = COLS_CLIENTES.find(c => c.k === _sortKeyClientes);
  const dir = _sortDirClientes === 'asc' ? 1 : -1;
  return linhas.slice().sort((a, b) => {
    const av = sortValueClientes(a[_sortKeyClientes], col.type);
    const bv = sortValueClientes(b[_sortKeyClientes], col.type);
    if (av === null && bv === null) return 0;
    if (av === null) return 1; // vazio sempre no final
    if (bv === null) return -1;
    if (av < bv) return -1 * dir;
    if (av > bv) return 1 * dir;
    return 0;
  });
}

function renderHeadClientes() {
  document.getElementById('thead-clientes').innerHTML = '<tr>' + COLS_CLIENTES.map(c => {
    const on   = c.k === _sortKeyClientes;
    const seta = on ? (_sortDirClientes === 'asc' ? ' ▲' : ' ▼') : '';
    const sort = on ? (_sortDirClientes === 'asc' ? 'ascending' : 'descending') : 'none';
    return `<th class="${on ? 'th-active' : ''}" aria-sort="${sort}" title="Clique para ordenar" tabindex="0" role="button" onclick="sortByClientes('${c.k}')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();sortByClientes('${c.k}')}">${c.label}${seta}</th>`;
  }).join('') + '</tr>';
}
renderHeadClientes();

function fillClientes(linhas) {
  ultimasLinhasClientes = linhas || [];
  const searchInput = document.getElementById('clientes-search');
  if (searchInput) searchInput.value = '';
  renderLinhasClientes(ordenarClientes(ultimasLinhasClientes));
}

// ── Busca client-side por Nome Deal ou Email (as linhas já vêm carregadas do período) ─
function filtrarClientes() {
  const termo = (document.getElementById('clientes-search').value || '').trim().toLowerCase();
  const base = termo
    ? ultimasLinhasClientes.filter(r =>
        (r.nome_deal || '').toLowerCase().includes(termo) ||
        (r.email || '').toLowerCase().includes(termo))
    : ultimasLinhasClientes;
  renderLinhasClientes(ordenarClientes(base), !!termo);
}

function renderLinhasClientes(linhas, filtrado = false) {
  const tbody = document.getElementById('tbody-clientes');
  const count = document.getElementById('clientes-count');
  if (!linhas || !linhas.length) {
    count.textContent = filtrado ? '0 clientes' : '';
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:32px;color:var(--ink-2)">${filtrado ? 'Nenhum cliente encontrado para essa busca.' : 'Nenhum cliente encontrado no período.'}</td></tr>`;
    return;
  }
  const sufixo = (!filtrado && linhas.length >= 500) ? '+' : '';
  count.textContent = linhas.length + sufixo + ' clientes';
  tbody.innerHTML = linhas.map(r => `
    <tr>
      <td>${escHtml(r.id_bitrix ?? '—')}</td>
      <td>${escHtml(r.nome_deal ?? '—')}</td>
      <td>${escHtml(r.email ?? '—')}</td>
      <td>${escHtml(r.data_venda ?? '—')}</td>
      <td>${escHtml(r.tipo ?? '—')}</td>
      <td>${escHtml(r.form ?? '—')}</td>
    </tr>
  `).join('');
}

// ══════════════════════════════════════════════════════════════
//  FILTROS / CARGA
// ══════════════════════════════════════════════════════════════

// Período sempre com as duas pontas: "Últimos N" > datas digitadas >
// Ano/Mês (vira intervalo contínuo). O backend só recebe data_inicio/data_fim.
function lerPeriodo() {
  const rel = relPeriod.getRange();
  if (rel) return { ini: rel.start, fim: rel.end, rotulo: relPeriod.label() };
  const hoje = isoLocal(new Date());
  let ini = getDateInput('data-inicio');
  let fim = getDateInput('data-fim');
  if (!ini && !fim) {
    const p = intervaloAnoMes(hoje);
    setEffectiveDate('data-inicio', p.ini);
    setEffectiveDate('data-fim', p.fim);
    return p;
  }
  ini = ini || fim;
  fim = fim || hoje;
  return { ini, fim, rotulo: fDataBr(ini) + ' – ' + fDataBr(fim) };
}

// Ano/Mês → intervalo contínuo: do 1º dia do menor mês do menor ano ao
// último dia do maior mês do maior ano (sem mês, os anos inteiros), até
// hoje. Anos ou meses salteados levam junto os do meio, que ficam marcados
// no filtro para o botão e a etiqueta mostrarem o que está valendo. Um mês
// que ainda não começou fica inteiro (e sai zerado). Mesma regra do
// intervaloAnoMes() do controller. Datas montadas no horário local.
function intervaloAnoMes(hoje) {
  const vAnos  = yearDropdown.getValue();
  const vMeses = monthDropdown.getValue();
  const anos   = (vAnos || NOW_YEAR).split(',').map(Number);
  const meses  = vMeses ? vMeses.split(',').map(Number) : [1, 12];
  const yIni = Math.min(...anos),  yFim = Math.max(...anos);
  const mIni = Math.min(...meses), mFim = Math.max(...meses);
  const faixa = (a, b) => Array.from({ length: b - a + 1 }, (_, i) => a + i);
  if (vAnos)  yearDropdown.select(faixa(yIni, yFim));
  if (vMeses) monthDropdown.select(faixa(mIni, mFim));

  const ini    = `${yIni}-${String(mIni).padStart(2, '0')}-01`;
  const fimMes = isoLocal(new Date(yFim, mFim, 0));
  const fim    = ini > hoje || fimMes < hoje ? fimMes : hoje;
  // Com um ano só, a etiqueta mostra o ano (e os meses); com vários, as datas.
  const rotulo = yIni === yFim
    ? [String(yIni)].concat(vMeses ? [faixa(mIni, mFim).map(m => MONTHS[m - 1]).join(', ')] : []).join(' · ')
    : fDataBr(ini) + ' – ' + fDataBr(fim);
  return { ini, fim, rotulo };
}

// Lê a resposta como JSON e transforma erro do servidor em mensagem
// legível (uma página de erro em HTML quebrava no JSON.parse com
// "Unexpected token '<'").
async function lerJson(res) {
  let data = null;
  try { data = JSON.parse(await res.text()); } catch (e) { /* não era JSON */ }
  if (data && data.error) throw new Error(data.error);
  if (!res.ok) throw new Error(`o servidor respondeu com erro (HTTP ${res.status}). Tente de novo em instantes.`);
  if (data === null) throw new Error('o servidor devolveu uma resposta inesperada. Tente de novo em instantes.');
  return data;
}

function updateBadge(periodo, form) {
  const partes = [periodo.rotulo];
  if (form) partes.unshift('Form: ' + form);
  if (modoDemo) partes.unshift('Demonstração');
  set('filter-label-badge', partes.join(' · ').toUpperCase());
}

// Redesenha tudo a partir do último payload (troca de "Agrupar por" e resize).
function renderTudo() {
  if (!ultimoPayload) return;
  const serie = montarSerie(ultimoPayload.dias || [], currentModo);
  renderGraficos(serie);
  fillTabela(serie, ultimoPayload.kpis);
}

// Só a resposta do último Aplicar entra na tela: uma requisição mais lenta
// que chegue depois não sobrescreve a mais nova.
let _seqCarga = 0;

async function loadDashboard(periodo, form) {
  const seq     = ++_seqCarga;
  const overlay = document.getElementById('loading-overlay');
  const errBan  = document.getElementById('error-banner');
  overlay.classList.remove('hidden');
  document.body.classList.add('loading');
  errBan.style.display = 'none';
  errBan.classList.remove('visible');

  const params = new URLSearchParams();
  params.set('data_inicio', periodo.ini);
  params.set('data_fim', periodo.fim);
  if (form) params.set('form', form);

  try {
    let data;
    if (modoDemo) {
      data = montarPayloadDemo(periodo.ini, periodo.fim);
    } else {
      const res = await fetch(API_URL + '?' + params.toString(), { cache: 'no-store' });
      data = await lerJson(res);
    }
    if (seq !== _seqCarga) return;

    ultimoPayload = data;
    fillKpis(data);
    renderTudo();
    fillClientes(data.linhas);

    document.getElementById('last-updated').textContent = modoDemo
      ? 'Modo demonstração: números fictícios'
      : 'Atualizado em: ' + new Date().toLocaleString('pt-BR');
  } catch (err) {
    if (seq !== _seqCarga) return;
    errBan.textContent = '⚠️ Erro ao carregar dados: ' + err.message;
    errBan.style.display = 'block';
    errBan.classList.add('visible');
    console.error(err);
  } finally {
    if (seq === _seqCarga) {
      overlay.classList.add('hidden');
      document.body.classList.remove('loading');
    }
  }
}

function setModo(modo) {
  currentModo = modo;
  document.querySelectorAll('.seg-btn').forEach(b => {
    const on = b.dataset.modo === modo;
    b.classList.toggle('is-active', on);
    b.setAttribute('aria-pressed', on ? 'true' : 'false');
  });
  renderTudo();
}

function applyFilters() {
  const periodo = lerPeriodo();
  const form    = modoDemo ? null : formDropdown.getValue(); // Form não se aplica na demonstração
  updateBadge(periodo, form);
  loadDashboard(periodo, form);
}

function clearFilters() {
  yearDropdown.reset();
  monthDropdown.reset();
  formDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim', 'data-fim-clear');
  relPeriod.reset();
  // volta ao padrão de abertura: mês atual
  yearDropdown.select([NOW_YEAR]);
  monthDropdown.select([NOW_MONTH]);
  applyFilters();
}

let _resizeT = null;
window.addEventListener('resize', () => {
  clearTimeout(_resizeT);
  _resizeT = setTimeout(renderGraficosAtuais, 120);
});
function renderGraficosAtuais() {
  if (ultimoPayload) renderGraficos(montarSerie(ultimoPayload.dias || [], currentModo));
}

aplicarModoDemo();
applyFilters();
</script>
