<div class="dash-law">

<style>
/* Ajustes específicos do dashboard Law. */
.dash-law .hbar-legend {
  display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 12px;
  font-size: 11px; color: var(--muted);
}
.dash-law .hbar-legend .legend-item { display: flex; align-items: center; gap: 5px; }
.dash-law .hbar-legend .legend-color { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }

.dash-law .hbar-list {
  display: flex; flex-direction: column; gap: 6px;
  max-height: 220px; overflow-y: auto; padding-right: 4px;
}
.dash-law .hbar-row2 { display: flex; align-items: center; gap: 10px; }
.dash-law .hbar-name {
  width: 150px; flex-shrink: 0; font-size: 11.5px; font-weight: 600; color: var(--text);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.dash-law .hbar-track { flex: 1; height: 17px; }
.dash-law .hbar-stack {
  height: 100%; border-radius: 3px; overflow: hidden; display: flex; gap: 1px;
  min-width: 2px; transition: width 0.5s ease;
}
.dash-law .hbar-segment {
  height: 100%; display: flex; align-items: center; justify-content: center;
  color: #fff; font-family: var(--font-mono); font-size: 10px; font-weight: 700;
  white-space: nowrap; overflow: hidden;
}

.dash-law .donut-wrap { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; justify-content: center; padding: 8px 0; }
.dash-law .donut-svg { width: 180px; height: 180px; flex-shrink: 0; }
.dash-law .donut-legend { display: flex; flex-direction: column; gap: 10px; }
.dash-law .donut-legend-item { display: flex; align-items: center; gap: 8px; font-size: 12.5px; }
.dash-law .donut-legend-dot { width: 11px; height: 11px; border-radius: 3px; flex-shrink: 0; }
.dash-law .donut-legend-label { color: var(--muted); min-width: 90px; }
.dash-law .donut-legend-val { font-family: var(--font-mono); font-weight: 700; color: var(--text); }
.dash-law .donut-legend-pct { font-family: var(--font-mono); font-size: 11px; color: var(--muted); }

.dash-law .kpi-chip.nvc { border-left: 3px solid #7c3aed; background: rgba(124,58,237,.09); }
.dash-law .kpi-chip.nvc .kpi-chip-val { color: #7c3aed; }

.dash-law #hbar-tooltip {
  position: fixed; z-index: 9999; pointer-events: none;
  background: var(--card); border: 1px solid var(--border);
  border-radius: var(--r); padding: 10px 12px; min-width: 160px;
  box-shadow: 0 8px 24px rgba(0,0,0,.20); display: none;
}
html.dark .dash-law #hbar-tooltip { box-shadow: 0 8px 24px rgba(0,0,0,.55); }
.dash-law .hbtt-title {
  font-size: 11.5px; font-weight: 700; color: var(--text);
  margin-bottom: 6px; padding-bottom: 6px; border-bottom: 1px solid var(--border);
}
.dash-law .hbtt-row { display: flex; align-items: center; gap: 6px; padding: 2px 0; font-size: 11.5px; }
.dash-law .hbtt-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.dash-law .hbtt-label { color: var(--muted); flex: 1; }
.dash-law .hbtt-val { font-family: var(--font-mono); font-weight: 700; color: var(--text); }

.dash-law #evolucao-card { display: flex; flex-direction: column; }
.dash-law #evolucao-card .chart-body { flex: 1; display: flex; flex-direction: column; }
.dash-law #line-chart-body { flex: 1; }
.dash-law #line-chart-body svg { width: 100%; height: 100%; display: block; }
</style>

<!-- ── LOADING ─────────────────────────────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>
<div id="hbar-tooltip" role="tooltip" aria-hidden="true"></div>

<!-- ── HEADER ─────────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo">LW</div>
    <div>
      <div class="header-title">Dashboard Law</div>
      <div class="header-sub">Casos · Case Managers · Fases (Bitrix)</div>
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
    <div class="header-badge" id="filter-label-badge">TODOS OS PERÍODOS</div>
    <div class="tab-switch">
      <a class="tab-switch-btn active" href="<?= $this->site ?>law<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Law</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>law/prioridade<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Alta Prioridade</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>law/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
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

  <!-- Data (de/até) -->
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

  <!-- Ciclo (busca sobre o calendário de ciclos de 4 semanas do Law) -->
  <div class="filter-pair">
    <span class="filter-label">Ciclo</span>
    <div class="camp-wrap" id="ciclo-wrap">
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="ciclo-input" class="camp-input" type="text" placeholder="Pesquisar ciclo..." autocomplete="off">
        <button class="camp-clear" id="ciclo-clear" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-tags" id="ciclo-tags"></div>
      <div class="camp-dropdown" id="ciclo-dropdown"></div>
    </div>
  </div>

  <!-- Status -->
  <div class="filter-pair">
    <span class="filter-label">Status</span>
    <div class="multi-dd-wrap" id="status-wrap">
      <button class="multi-dd-btn" id="status-btn" type="button">Todos <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="status-list"></div>
    </div>
  </div>

  <!-- Milestone -->
  <div class="filter-pair">
    <span class="filter-label">Milestone</span>
    <div class="multi-dd-wrap" id="milestone-wrap">
      <button class="multi-dd-btn" id="milestone-btn" type="button">Todos <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="milestone-list"></div>
    </div>
  </div>

  <div class="filter-sep"></div>

  <!-- Case Manager (busca sobre advogadoAtendimento_dealV2) -->
  <div class="filter-pair">
    <span class="filter-label">Case Manager</span>
    <div class="camp-wrap" id="cm-wrap">
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="cm-input" class="camp-input" type="text" placeholder="Pesquisar case manager..." autocomplete="off">
        <button class="camp-clear" id="cm-clear" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-tags" id="cm-tags"></div>
      <div class="camp-dropdown" id="cm-dropdown"></div>
    </div>
  </div>

  <button class="btn-apply" onclick="applyFilters()">Aplicar</button>
  <button class="btn-clear" onclick="clearFilters()">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'law' });</script>

<!-- ── ERRO ───────────────────────────────────────────────────── -->
<div id="error-banner" role="alert"></div>

<!-- ── CONTEÚDO PRINCIPAL ──────────────────────────────────────── -->
<main class="main" id="dashboard-main">

  <!-- 6 KPI CHIPS -->
  <div class="kpi-row">
    <div class="kpi-chip">
      <span class="kpi-chip-label">Total de Casos</span>
      <span class="kpi-chip-val" id="k-totais">—</span>
    </div>
    <div class="kpi-chip risco">
      <span class="kpi-chip-label">Alta Prioridade</span>
      <span class="kpi-chip-val" id="k-alta-prioridade">—</span>
    </div>
    <div class="kpi-chip sinal">
      <span class="kpi-chip-label">Em Aberto</span>
      <span class="kpi-chip-val" id="k-em-aberto">—</span>
    </div>
    <div class="kpi-chip consulta">
      <span class="kpi-chip-label">Aplicados</span>
      <span class="kpi-chip-val" id="k-aplicados">—</span>
    </div>
    <div class="kpi-chip fecho">
      <span class="kpi-chip-label">Aprovados</span>
      <span class="kpi-chip-val" id="k-aprovados">—</span>
    </div>
    <div class="kpi-chip nvc">
      <span class="kpi-chip-label">NVC</span>
      <span class="kpi-chip-val" id="k-nvc">—</span>
    </div>
  </div>

  <!-- LINHA 1: BARRA STATUS×CASE MANAGER + LINHA DE PERÍODO -->
  <div class="funis-grid">

    <!-- BARRA STATUS × CASE MANAGER -->
    <div class="chart-card">
      <div class="chart-header">
        <div class="chart-title-group">
          <span class="chart-dot" style="background:#3b82f6"></span>
          <span class="chart-title">Casos por Case Manager</span>
        </div>
      </div>
      <div class="chart-body">
        <div class="hbar-legend">
          <span class="legend-item"><span class="legend-color" style="background:var(--sinal)"></span>Em Aberto</span>
          <span class="legend-item"><span class="legend-color" style="background:var(--consulta)"></span>Aplicados</span>
          <span class="legend-item"><span class="legend-color" style="background:var(--fecho)"></span>Aprovados</span>
          <span class="legend-item"><span class="legend-color" style="background:#7c3aed"></span>NVC</span>
        </div>
        <div class="hbar-list" id="bar-status-cm">
          <div style="padding:12px 0;color:var(--muted);font-size:12px;text-align:center">Carregando...</div>
        </div>
      </div>
    </div>

    <!-- GRÁFICO DE LINHA POR PERÍODO -->
    <div class="chart-card" id="evolucao-card">
      <div class="chart-header">
        <div class="chart-title-group">
          <span class="chart-dot" style="background:#7c3aed"></span>
          <span class="chart-title">Evolução por Período</span>
        </div>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
          <div class="chart-legend">
            <div class="legend-item"><span class="legend-color" style="background:var(--sinal)"></span>Em Aberto</div>
            <div class="legend-item"><span class="legend-color" style="background:var(--consulta)"></span>Aplicados</div>
            <div class="legend-item"><span class="legend-color" style="background:var(--fecho)"></span>Aprovados</div>
            <div class="legend-item"><span class="legend-color" style="background:#7c3aed"></span>NVC</div>
          </div>
          <div class="gran-toggle">
            <button class="gran-btn"        data-gran="dia"    onclick="setGranularidade('dia')">Dia</button>
            <button class="gran-btn"        data-gran="semana" onclick="setGranularidade('semana')">Semana</button>
            <button class="gran-btn active" data-gran="mes"    onclick="setGranularidade('mes')">Mês</button>
            <button class="gran-btn"        data-gran="ano"    onclick="setGranularidade('ano')">Ano</button>
            <button class="gran-btn"        data-gran="ciclo"  onclick="setGranularidade('ciclo')">Ciclo</button>
          </div>
        </div>
      </div>
      <div class="chart-body" id="line-chart-body">
        <div style="height:100%;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:12px">
          Carregando gráfico...
        </div>
      </div>
    </div>

  </div>

  <!-- LINHA 2: BARRA MILESTONE×CASE MANAGER + DONUT DE MILESTONES -->
  <div class="funis-grid" style="margin-top:16px">

    <!-- BARRA MILESTONE × CASE MANAGER -->
    <div class="chart-card">
      <div class="chart-header">
        <div class="chart-title-group">
          <span class="chart-dot" style="background:#4F79BC"></span>
          <span class="chart-title">Milestones por Case Manager</span>
        </div>
      </div>
      <div class="chart-body">
        <div class="hbar-legend">
          <span class="legend-item"><span class="legend-color" style="background:#4F79BC"></span>ML1</span>
          <span class="legend-item"><span class="legend-color" style="background:var(--consulta)"></span>ML2</span>
          <span class="legend-item"><span class="legend-color" style="background:var(--fecho)"></span>ML3</span>
        </div>
        <div class="hbar-list" id="bar-milestone-cm">
          <div style="padding:12px 0;color:var(--muted);font-size:12px;text-align:center">Carregando...</div>
        </div>
      </div>
    </div>

    <!-- DONUT DE MILESTONES -->
    <div class="chart-card">
      <div class="chart-header">
        <div class="chart-title-group">
          <span class="chart-dot" style="background:var(--fecho)"></span>
          <span class="chart-title">Total por Milestone</span>
        </div>
      </div>
      <div class="chart-body" id="donut-body">
        <div style="height:220px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:12px">
          Carregando...
        </div>
      </div>
    </div>

  </div>

  <div class="last-updated" id="last-updated"></div>

</main>
</div>

<script>
// ══════════════════════════════════════════════════════════════
//  Law — Dashboard JS
//  Consome: law/dados, law/responsaveis, law/grafico (controller MRPHP)
//
//  createMultiDropdown, createSearchDropdown, fNum são fornecidas por
//  js/bi-dashboard.js (carregado no <head> pelo template, antes deste script).
// ══════════════════════════════════════════════════════════════

const API_URL          = '<?= $this->site ?>law/dados';
const RESPONSAVEIS_URL = '<?= $this->site ?>law/responsaveis';
const CICLOS_URL       = '<?= $this->site ?>law/ciclos';
const GRAFICO_URL      = '<?= $this->site ?>law/grafico';
const MONTHS           = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const YEARS            = anosDesde(2025);
const STATUS_OPTS       = ['Em Aberto', 'Aplicados', 'Aprovados', 'NVC'];
const MILESTONE_OPTS    = ['ML1', 'ML2', 'ML3'];

let currentGranularidade = 'mes';
// Últimos filtros aplicados via applyFilters()/clearFilters() — usados por
// setGranularidade() para recarregar só o gráfico de período sem repetir KPIs/barras/donut.
let ultimosFiltros = { year: null, months: null, dataInicio: null, dataFim: null, responsavel: null, ciclo: null, status: null, milestone: null };

const yearDropdown = createMultiDropdown('year-wrap',  'year-btn',  'year-list',  YEARS,  l => l, l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);
const statusDropdown = createMultiDropdown('status-wrap', 'status-btn', 'status-list', STATUS_OPTS, l => l, l => l);
const milestoneDropdown = createMultiDropdown('milestone-wrap', 'milestone-btn', 'milestone-list', MILESTONE_OPTS, l => l, l => l);
const cmDropdown = createSearchDropdown('cm-wrap', 'cm-input', 'cm-dropdown', 'cm-clear', 'cm-tags', 'Nenhum case manager encontrado');
const cicloDropdown = createSearchDropdown('ciclo-wrap', 'ciclo-input', 'ciclo-dropdown', 'ciclo-clear', 'ciclo-tags', 'Nenhum ciclo encontrado');

fetch(RESPONSAVEIS_URL, { cache: 'no-store' })
  .then(res => res.json())
  .then(list => cmDropdown.load(Array.isArray(list) ? list : []))
  .catch(err => console.error('Erro ao carregar case managers:', err));

fetch(CICLOS_URL, { cache: 'no-store' })
  .then(res => res.json())
  .then(list => cicloDropdown.load(Array.isArray(list) ? list.map(String) : []))
  .catch(err => console.error('Erro ao carregar ciclos:', err));

// ── Inputs de data (de/até) — limpar ao focar num campo já preenchido, mostrar
// botão de limpar quando houver valor (mesmo padrão de executive/index.php) ──
['data-inicio', 'data-fim'].forEach(id => {
  const input    = document.getElementById(id);
  const clearBtn = document.getElementById(id + '-clear');
  input?.addEventListener('change', () => clearBtn?.classList.toggle('visible', !!input.value));
  clearBtn?.addEventListener('click', () => clearDateInput(id, id + '-clear'));
});

function set(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }

// ── Granularidade do gráfico de período ─────────────────────────
function setGranularidade(gran) {
  currentGranularidade = gran;
  document.querySelectorAll('.gran-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.gran === gran);
  });
  loadGrafico(gran);
}

// ── Preencher KPIs ──────────────────────────────────────────────
function fillKpis(k) {
  set('k-totais',          fNum(k.casos_totais));
  set('k-alta-prioridade', fNum(k.casos_alta_prioridade));
  set('k-em-aberto',       fNum(k.casos_em_aberto));
  set('k-aplicados',       fNum(k.casos_aplicados));
  set('k-aprovados',       fNum(k.casos_aprovados));
  set('k-nvc',             fNum(k.casos_nvc));
}

// ── Barra horizontal empilhada por case manager (1 barra, segmentos coloridos) ──
function buildHBarCaseManager(containerId, rows, series) {
  const el = document.getElementById(containerId);
  if (!rows || !rows.length) {
    el.innerHTML = '<div style="padding:12px 0;color:var(--muted);font-size:12px;text-align:center">Sem dados no período</div>';
    return;
  }
  const totals   = rows.map(r => series.reduce((s, ser) => s + r[ser.key], 0));
  const maxTotal = Math.max(1, ...totals);
  // Largura útil da barra (descontando o nome do case manager) — usada só pra
  // decidir se um segmento é largo o bastante pra mostrar o número dentro dele,
  // evitando números de segmentos vizinhos muito estreitos ficarem colados.
  const trackWidth = Math.max(200, el.clientWidth - 160);

  el.innerHTML = rows.map((r, i) => {
    const total = totals[i] || 1;
    const segments = series
      .filter(s => r[s.key] > 0)
      .map(s => {
        const val    = r[s.key];
        const segPx  = (val / maxTotal) * trackWidth;
        const minPx  = 9 + String(val).length * 6.5;
        const label  = segPx >= minPx ? fNum(val) : '';
        return `<div class="hbar-segment" style="width:${fBar(val, total)};background:${s.color}">${label}</div>`;
      })
      .join('');
    return `
      <div class="hbar-row2">
        <span class="hbar-name" title="${escHtmlLaw(r.case_manager)}">${escHtmlLaw(r.case_manager)}</span>
        <div class="hbar-track">
          <div class="hbar-stack" style="width:${fBar(total, maxTotal)}">${segments}</div>
        </div>
      </div>
    `;
  }).join('');

  el.querySelectorAll('.hbar-row2').forEach((rowEl, i) => {
    rowEl.addEventListener('mouseenter', e => showHbarTooltip(e, rows[i], series));
    rowEl.addEventListener('mousemove', positionHbarTooltip);
    rowEl.addEventListener('mouseleave', hideHbarTooltip);
  });
}

// ── Tooltip da barra empilhada por case manager ──────────────────
function showHbarTooltip(e, row, series) {
  const tip = document.getElementById('hbar-tooltip');
  const rowsHtml = series.map(s => `
    <div class="hbtt-row">
      <span class="hbtt-dot" style="background:${s.color}"></span>
      <span class="hbtt-label">${s.label}</span>
      <span class="hbtt-val">${fNum(row[s.key] || 0)}</span>
    </div>`).join('');
  tip.innerHTML = `<div class="hbtt-title">${escHtmlLaw(row.case_manager)}</div>${rowsHtml}`;
  tip.style.display = 'block';
  positionHbarTooltip(e);
}

function positionHbarTooltip(e) {
  const tip = document.getElementById('hbar-tooltip');
  const margin = 14;
  const tw = tip.offsetWidth  || 180;
  const th = tip.offsetHeight || 90;
  let x = e.clientX + margin;
  let y = e.clientY - th - margin;
  if (x + tw > window.innerWidth - margin) x = e.clientX - tw - margin;
  if (y < margin) y = e.clientY + margin;
  tip.style.left = x + 'px';
  tip.style.top  = y + 'px';
}

function hideHbarTooltip() {
  document.getElementById('hbar-tooltip').style.display = 'none';
}

// ── Tooltip do gráfico de linha (mesmo padrão da barra por case manager) ──
function showLineTooltip(e, d, series) {
  const tip = document.getElementById('hbar-tooltip');
  const rowsHtml = series.map(s => `
    <div class="hbtt-row">
      <span class="hbtt-dot" style="background:${s.color}"></span>
      <span class="hbtt-label">${s.label}</span>
      <span class="hbtt-val">${fNum(d[s.key] || 0)}</span>
    </div>`).join('');
  tip.innerHTML = `<div class="hbtt-title">${escHtmlLaw(d.label)}</div>${rowsHtml}`;
  tip.style.display = 'block';
  positionHbarTooltip(e);
}

function escHtmlLaw(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

const STATUS_SERIES = [
  { key: 'em_aberto', label: 'Em Aberto', color: 'var(--sinal)' },
  { key: 'aplicados', label: 'Aplicados', color: 'var(--consulta)' },
  { key: 'aprovados', label: 'Aprovados', color: 'var(--fecho)' },
  { key: 'nvc',       label: 'NVC',       color: '#7c3aed' },
];
const MILESTONE_SERIES = [
  { key: 'ml1', label: 'ML1', color: '#4F79BC' },
  { key: 'ml2', label: 'ML2', color: 'var(--consulta)' },
  { key: 'ml3', label: 'ML3', color: 'var(--fecho)' },
];

// ── Donut SVG (stroke-dasharray) ─────────────────────────────────
function buildDonut(data) {
  const body = document.getElementById('donut-body');
  const total = (data || []).reduce((s, d) => s + d.qtd, 0);
  if (!total) {
    body.innerHTML = '<div style="height:220px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:12px">Sem dados no período</div>';
    return;
  }

  const colors = { ML1: '#4F79BC', ML2: 'var(--consulta)', ML3: 'var(--fecho)' };
  const labels = { ML1: 'Milestone 1', ML2: 'Milestone 2', ML3: 'Milestone 3' };

  const r = 52, cx = 60, cy = 60, sw = 16;
  const circ = 2 * Math.PI * r;
  let offset = 0;
  const arcs = data.map(d => {
    const frac = d.qtd / total;
    const len  = frac * circ;
    const arc  = `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${colors[d.milestone] || '#999'}"
      stroke-width="${sw}" stroke-dasharray="${len} ${circ - len}" stroke-dashoffset="${-offset}"
      transform="rotate(-90 ${cx} ${cy})" stroke-linecap="butt"/>`;
    offset += len;
    return arc;
  }).join('');

  const svg = `<svg class="donut-svg" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
    <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="var(--border)" stroke-width="${sw}"/>
    ${arcs}
    <text x="${cx}" y="${cy - 4}" text-anchor="middle" font-size="20" font-weight="700" fill="var(--text)" class="num">${fNum(total)}</text>
    <text x="${cx}" y="${cy + 14}" text-anchor="middle" font-size="9" fill="var(--muted)">casos</text>
  </svg>`;

  const legend = data.map(d => `
    <div class="donut-legend-item">
      <span class="donut-legend-dot" style="background:${colors[d.milestone] || '#999'}"></span>
      <span class="donut-legend-label">${labels[d.milestone] || d.milestone}</span>
      <span class="donut-legend-val">${fNum(d.qtd)}</span>
      <span class="donut-legend-pct">(${fPctRatio(d.qtd, total)})</span>
    </div>
  `).join('');

  body.innerHTML = `<div class="donut-wrap">${svg}<div class="donut-legend">${legend}</div></div>`;
}

// ── Gráfico de linha (4 séries por período) ──────────────────────
function buildLineChart(data) {
  const containerId = 'line-chart-body';
  if (!data || !data.length) {
    document.getElementById(containerId).innerHTML =
      '<div style="height:100%;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:12px">Sem dados para o período</div>';
    return;
  }

  const SERIES = [
    { key: 'em_aberto', label: 'Em Aberto', color: '#b86e00' },
    { key: 'aplicados', label: 'Aplicados', color: '#1a6ec8' },
    { key: 'aprovados', label: 'Aprovados', color: '#0d8a52' },
    { key: 'nvc',       label: 'NVC',       color: '#7c3aed' },
  ];

  const W = 860, H = 240;
  const pl = 42, pr = 16, pt = 20, pb = 34;
  const cW = W - pl - pr, cH = H - pt - pb;

  const n     = data.length;
  const nSafe = n || 1;
  const slot  = cW / nSafe;
  const step  = nSafe <= 12 ? 1 : nSafe <= 24 ? 2 : nSafe <= 52 ? 4 : 8;
  const fs    = nSafe > 30 ? 9 : nSafe > 15 ? 10 : 11;

  const maxVal = Math.max(1, ...data.flatMap(d => SERIES.map(s => d[s.key]))) * 1.15;
  const yOf = v => pt + cH - Math.round((v / maxVal) * cH);
  const xOf = i => Math.round(pl + slot * i + slot / 2);

  const gridLines = [0, 0.25, 0.5, 0.75, 1].map(f => {
    const y = pt + Math.round(cH * f);
    const v = Math.round(maxVal * (1 - f));
    const isBase = f === 1;
    return `<line x1="${pl}" y1="${y}" x2="${pl + cW}" y2="${y}" stroke="${isBase ? '#cbd5e1' : '#f1f5f9'}" stroke-width="1"/>` +
      `<text x="${pl - 6}" y="${y + 3}" text-anchor="end" font-size="11" fill="#94a3b8" class="num">${fNum(v)}</text>`;
  }).join('');

  const labels = data.map((d, i) => (i % step === 0 || i === n - 1)
    ? `<text x="${xOf(i)}" y="${H - 8}" text-anchor="middle" font-size="${fs}" fill="#64748b" font-weight="500">${d.label}</text>`
    : '').join('');

  // Rótulos de valor — só nos mesmos pontos amostrados no eixo X, pra não poluir
  // granularidades densas (Dia/Semana).
  const valueLabels = data.map((d, i) => {
    if (!(i % step === 0 || i === n - 1)) return '';
    return SERIES.map(s => {
      const val = d[s.key];
      if (!val) return '';
      return `<text x="${xOf(i)}" y="${yOf(val) - 8}" text-anchor="middle" font-size="9" font-weight="700" fill="${s.color}" class="num">${fNum(val)}</text>`;
    }).join('');
  }).join('');

  const lines = SERIES.map(s => {
    const pathD = data.map((d, i) => `${i === 0 ? 'M' : 'L'}${xOf(i)},${yOf(d[s.key])}`).join(' ');
    const dots  = data.map((d, i) => `<circle cx="${xOf(i)}" cy="${yOf(d[s.key])}" r="2.6" fill="${s.color}"/>`).join('');
    return `<path d="${pathD}" fill="none" stroke="${s.color}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events:none"/><g style="pointer-events:none">${dots}</g>`;
  }).join('');

  // Áreas invisíveis de hover — uma por período, cobrindo toda a altura do gráfico.
  const hitAreas = data.map((d, i) => {
    const x = pl + slot * i;
    return `<rect class="line-hit" data-i="${i}" x="${x}" y="0" width="${slot}" height="${H}" fill="transparent" style="cursor:pointer"/>`;
  }).join('');

  const svg = `<svg width="100%" height="100%" viewBox="0 0 ${W} ${H}" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" style="display:block;overflow:visible">
    ${gridLines}${lines}${labels}${valueLabels}${hitAreas}
  </svg>`;

  document.getElementById(containerId).innerHTML = svg;

  document.getElementById(containerId).querySelectorAll('.line-hit').forEach(rect => {
    const i = Number(rect.dataset.i);
    rect.addEventListener('mouseenter', e => showLineTooltip(e, data[i], SERIES));
    rect.addEventListener('mousemove', positionHbarTooltip);
    rect.addEventListener('mouseleave', hideHbarTooltip);
  });
}

// ── Badge ────────────────────────────────────────────────────────
function updateBadge(year, months, dataInicio, dataFim, responsavel, ciclo, status, milestone) {
  const badge = document.getElementById('filter-label-badge');
  if (!year && !months && !dataInicio && !dataFim && !responsavel && !ciclo && !status && !milestone) {
    badge.textContent = 'TODOS OS PERÍODOS';
    return;
  }
  const parts = [];
  if (responsavel) parts.push('Case Manager: ' + responsavel);
  if (status)      parts.push('Status: ' + status);
  if (milestone)   parts.push('Milestone: ' + milestone);
  if (ciclo)        parts.push('Ciclo: ' + ciclo);
  if (dataInicio || dataFim) {
    parts.push((dataInicio || '…') + ' até ' + (dataFim || '…'));
  } else {
    if (year)   parts.push(year);
    if (months) {
      const labels = months.split(',').map(n => MONTHS[Number(n) - 1]);
      parts.push(labels.join(', '));
    }
  }
  badge.textContent = parts.join(' · ').toUpperCase();
}

// ── Recarregar só o gráfico de período (troca de granularidade) ──
async function loadGrafico(granularidade) {
  const { year, months, dataInicio, dataFim, responsavel, ciclo, status, milestone } = ultimosFiltros;
  const params = new URLSearchParams();
  if (year)          params.set('year', year);
  if (months)        params.set('months', months);
  if (dataInicio)    params.set('data_inicio', dataInicio);
  if (dataFim)       params.set('data_fim', dataFim);
  if (responsavel)   params.set('responsavel', responsavel);
  if (ciclo)         params.set('ciclo', ciclo);
  if (status)        params.set('status', status);
  if (milestone)     params.set('milestone', milestone);
  if (granularidade) params.set('granularidade', granularidade);

  document.getElementById('line-chart-body').innerHTML =
    '<div style="height:100%;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:12px">Carregando gráfico...</div>';

  try {
    const res  = await fetch(GRAFICO_URL + '?' + params.toString(), { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (data.error) throw new Error(data.error);
    buildLineChart(data.grafico);
  } catch (err) {
    document.getElementById('line-chart-body').innerHTML =
      '<div style="height:100%;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:12px">Erro ao carregar gráfico</div>';
    console.error(err);
  }
}

// ── Carregar dados ────────────────────────────────────────────────
async function loadDashboard(year, months, dataInicio, dataFim, responsavel, ciclo, status, milestone, granularidade) {
  ultimosFiltros = { year, months, dataInicio, dataFim, responsavel, ciclo, status, milestone };

  const overlay = document.getElementById('loading-overlay');
  const errBan  = document.getElementById('error-banner');
  overlay.classList.remove('hidden');
  document.body.classList.add('loading');
  errBan.style.display = 'none';
  errBan.classList.remove('visible');

  let url = API_URL;
  const params = new URLSearchParams();
  if (year)          params.set('year', year);
  if (months)        params.set('months', months);
  if (dataInicio)    params.set('data_inicio', dataInicio);
  if (dataFim)       params.set('data_fim', dataFim);
  if (responsavel)   params.set('responsavel', responsavel);
  if (ciclo)         params.set('ciclo', ciclo);
  if (status)        params.set('status', status);
  if (milestone)     params.set('milestone', milestone);
  if (granularidade) params.set('granularidade', granularidade);
  if ([...params].length) url += '?' + params.toString();

  try {
    const res  = await fetch(url, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    fillKpis(data.kpis);
    buildHBarCaseManager('bar-status-cm', data.barCaseManager, STATUS_SERIES);
    buildHBarCaseManager('bar-milestone-cm', data.milestoneCaseManager, MILESTONE_SERIES);
    buildDonut(data.milestoneDonut);
    buildLineChart(data.grafico);

    document.getElementById('last-updated').textContent = 'Atualizado em: ' + new Date().toLocaleString('pt-BR');

  } catch (err) {
    errBan.textContent = '⚠️ Erro ao carregar dados: ' + err.message;
    errBan.style.display = 'block';
    errBan.classList.add('visible');
    console.error(err);
  } finally {
    overlay.classList.add('hidden');
    document.body.classList.remove('loading');
  }
}

// ── Aplicar / Limpar filtros ──────────────────────────────────────
function applyFilters() {
  const year        = yearDropdown.getValue();
  const months       = monthDropdown.getValue();
  const dataInicio   = getDateInput('data-inicio');
  const dataFim      = getDateInput('data-fim');
  const responsavel  = cmDropdown.getValue();
  const ciclo        = cicloDropdown.getValue();
  const status       = statusDropdown.getValue();
  const milestone    = milestoneDropdown.getValue();
  updateBadge(year, months, dataInicio, dataFim, responsavel, ciclo, status, milestone);
  loadDashboard(year, months, dataInicio, dataFim, responsavel, ciclo, status, milestone, currentGranularidade);
}

function clearFilters() {
  yearDropdown.reset();
  monthDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim', 'data-fim-clear');
  cmDropdown.reset();
  cicloDropdown.reset();
  statusDropdown.reset();
  milestoneDropdown.reset();
  currentGranularidade = 'mes';
  document.querySelectorAll('.gran-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.gran === 'mes'));
  updateBadge(null, null, null, null, null, null, null, null);
  loadDashboard(null, null, null, null, null, null, null, null, currentGranularidade);
}

yearDropdown.select([String(new Date().getFullYear())]);
applyFilters();
</script>
