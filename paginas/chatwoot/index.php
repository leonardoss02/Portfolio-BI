<div class="dash-chatwoot">

<style>
/* Estilos específicos do dashboard Chatwoot, sobre css/bi-theme.css.
   Migrado de Power Bi/Chatwoot/assets/chatwoot.css — variáveis extras,
   KPI chips (7 em linha), gráficos combo, tabela expansível e drill-down
   não usados por nenhum outro dashboard. */
.dash-chatwoot {
  --teal: #0891b2;
  --teal-glow: rgba(8,145,178,.09);
  --purple: #7c3aed;
  --purple-glow: rgba(124,58,237,.10);
}

.dash-chatwoot .kpi-chip {
  min-width: 120px;
  padding: 12px 14px;
}
.dash-chatwoot .kpi-chip-val {
  font-size: clamp(16px, 1.7vw, 24px);
  white-space: nowrap;
}
.dash-chatwoot .kpi-chip-label { font-size: 10.5px; white-space: nowrap; }

.dash-chatwoot .kpi-chip.c-green  { border-left: 3px solid var(--green); }
.dash-chatwoot .kpi-chip.c-blue   { border-left: 3px solid var(--consulta); }
.dash-chatwoot .kpi-chip.c-teal   { border-left: 3px solid var(--teal); }
.dash-chatwoot .kpi-chip.c-amber  { border-left: 3px solid var(--amber); }
.dash-chatwoot .kpi-chip.c-red    { border-left: 3px solid var(--danger); }
.dash-chatwoot .kpi-chip.c-purple { border-left: 3px solid var(--purple); }

.dash-chatwoot .kpi-chip-val.c-green  { color: var(--green); }
.dash-chatwoot .kpi-chip-val.c-blue   { color: var(--consulta); }
.dash-chatwoot .kpi-chip-val.c-teal   { color: var(--teal); }
.dash-chatwoot .kpi-chip-val.c-amber  { color: var(--amber); }
.dash-chatwoot .kpi-chip-val.c-red    { color: var(--danger); }
.dash-chatwoot .kpi-chip-val.c-purple { color: var(--purple); }

/* Subtítulo MoM: valor mês anterior + delta com seta */
.dash-chatwoot .kpi-chip-sub {
  font-size: 10.5px;
  color: var(--muted);
  margin-top: 4px;
  display: flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}
.dash-chatwoot .kpi-mom {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 999px;
}
.dash-chatwoot .kpi-mom.up   { color: var(--green);  background: var(--green-glow); }
.dash-chatwoot .kpi-mom.down { color: var(--danger); background: var(--danger-glow); }
.dash-chatwoot .kpi-mom.flat { color: var(--muted);  background: var(--card2); }
.dash-chatwoot .kpi-mom-prev { color: var(--muted); }

/* Grid de gráficos */
.dash-chatwoot .chart-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
@media (max-width: 1000px) { .dash-chatwoot .chart-grid { grid-template-columns: 1fr; } }
.dash-chatwoot .chart-wrap { position: relative; height: 300px; }

/* Toggle de granularidade */
.dash-chatwoot .gran-toggle {
  display: inline-flex;
  background: var(--card2);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 2px;
  gap: 2px;
}
.dash-chatwoot .gran-toggle button {
  border: none;
  background: transparent;
  color: var(--text-secondary, var(--muted));
  font-family: 'Fira Sans', sans-serif;
  font-size: 11px;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: 6px;
  cursor: pointer;
  transition: background .15s, color .15s;
  white-space: nowrap;
}
.dash-chatwoot .gran-toggle button.active { background: var(--consulta); color: #fff; }

/* Tabela analítica clicável */
.dash-chatwoot .resp-row { cursor: pointer; }
.dash-chatwoot .resp-row:hover td { background: var(--card2); }
.dash-chatwoot .resp-row .chevron {
  display: inline-block;
  margin-right: 8px;
  color: var(--muted);
  transition: transform .15s;
}
.dash-chatwoot .resp-row.open .chevron { transform: rotate(90deg); }
.dash-chatwoot .resp-row.open td { background: var(--card2); }
.dash-chatwoot .tbl-hint {
  padding: 10px 16px;
  font-size: 11px;
  color: var(--muted);
  border-top: 1px solid var(--border);
}
.dash-chatwoot .tbl-wrap { overflow-x: auto; }

/* Badges de status */
.dash-chatwoot .status-badge {
  display: inline-block;
  padding: 2px 9px;
  border-radius: 999px;
  font-size: 9.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .4px;
  white-space: nowrap;
}
.dash-chatwoot .status-badge.open     { background: var(--amber-glow);    color: var(--amber); }
.dash-chatwoot .status-badge.resolved { background: var(--green-glow);    color: var(--green); }
.dash-chatwoot .status-badge.pending  { background: var(--consulta-glow); color: var(--consulta); }
.dash-chatwoot .status-badge.snoozed  { background: var(--teal-glow);     color: var(--teal); }

/* Drill-down: sub-tabela expansível */
.dash-chatwoot .detalhe-row td { background: var(--card2); padding: 0; }
.dash-chatwoot .detalhe-wrap {
  padding: 10px 14px 16px 34px;
  max-height: 420px;
  overflow: auto;
}
.dash-chatwoot .detalhe-wrap table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
.dash-chatwoot .detalhe-wrap th {
  position: sticky; top: 0;
  background: var(--card2);
  text-align: left;
  padding: 6px 9px;
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: var(--muted);
  border-bottom: 1px solid var(--border);
  z-index: 1;
}
.dash-chatwoot .detalhe-wrap td { padding: 6px 9px; border-bottom: 1px solid var(--border); color: var(--text-secondary, inherit); }
.dash-chatwoot .detalhe-wrap .num { text-align: right; }
.dash-chatwoot .detalhe-wrap tr:hover td { background: var(--card); }
.dash-chatwoot .detalhe-loading { padding: 24px; text-align: center; color: var(--muted); font-size: 11px; }
</style>

<div id="loading-overlay" class="hidden"></div>

<header class="dash-header">
  <div class="dash-header-inner">
    <div class="header-brand">
      <div class="brand-logo">💬</div>
      <div class="brand-label">
        <span class="brand-name">Acme EUA</span>
        <span class="brand-page">Chatwoot — Atendimento Comercial</span>
      </div>
    </div>
    <div class="header-right">
      <span class="period-badge" id="badge-periodo">
        <span class="badge-dot"></span>
        <span id="badge-texto">carregando…</span>
      </span>
      <a class="btn-tab-switch" href="<?= $this->site ?>chatwoot/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário &rarr;</a>
    </div>
  </div>
</header>

<section class="filters is-collapsible" aria-label="Filtros do dashboard">
  <button class="filters-toggle" type="button" aria-expanded="true" aria-controls="filters-fields">
    <span>Filtros</span>
    <span class="dd-caret filters-toggle-caret">&#x25BE;</span>
  </button>
  <div class="filters-fields" id="filters-fields">
  <div class="filter-group">
    <span class="filter-label">Ano</span>
    <div class="multi-dd-wrap" id="year-wrap">
      <button class="multi-dd-btn" id="year-btn" type="button">Todos <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="year-list"></div>
    </div>
  </div>
  <div class="filter-group">
    <span class="filter-label">Mês</span>
    <div class="multi-dd-wrap" id="month-wrap">
      <button class="multi-dd-btn" id="month-btn" type="button">Todos <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="month-list"></div>
    </div>
  </div>
  <div class="filter-group">
    <span class="filter-label">Responsável</span>
    <div class="multi-dd-wrap" id="resp-wrap">
      <button class="multi-dd-btn" id="resp-btn" type="button">Todos <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="resp-list"></div>
    </div>
  </div>
  <div class="filter-group">
    <span class="filter-label">Status</span>
    <div class="multi-dd-wrap" id="status-wrap">
      <button class="multi-dd-btn" id="status-btn" type="button">Todos <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="status-list"></div>
    </div>
  </div>
  <button class="btn-apply" onclick="applyFilters()">Aplicar</button>
  <button class="btn-clear" onclick="clearFilters()">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'chatwoot', badgeId: 'badge-periodo' });</script>

<main class="dash-main">

  <div class="kpi-row" id="kpis"></div>

  <div class="chart-grid">
    <div class="bi-card chart-card">
      <div class="bi-card-header">
        <span class="bi-card-title">Evolução das Conversas por responsável</span>
      </div>
      <div class="bi-card-body">
        <div class="chart-wrap"><canvas id="chartResp"></canvas></div>
      </div>
    </div>

    <div class="bi-card chart-card">
      <div class="bi-card-header">
        <span class="bi-card-title">Evolução das Conversas pelo período</span>
        <div class="gran-toggle" id="gran-toggle">
          <button type="button" data-gran="ano">Ano</button>
          <button type="button" data-gran="mes" class="active">Mês</button>
          <button type="button" data-gran="dow">Dia da Semana</button>
          <button type="button" data-gran="dia">Dia</button>
        </div>
      </div>
      <div class="bi-card-body">
        <div class="chart-wrap"><canvas id="chartPeriodo"></canvas></div>
      </div>
    </div>
  </div>

  <div class="bi-card">
    <div class="bi-card-header">
      <span class="bi-card-title">Tabela Analítica por responsável</span>
      <span class="bi-card-sub" id="resp-count"></span>
    </div>
    <div class="tbl-wrap">
      <table class="bi-table">
        <thead>
          <tr>
            <th>Responsável</th>
            <th class="num">Conversas</th>
            <th>Criado em (recente)</th>
            <th>Última atividade</th>
            <th class="num">TRM (min)</th>
            <th class="num">Duração méd. (h)</th>
            <th class="num">Ingressos</th>
            <th class="num">Consultas</th>
          </tr>
        </thead>
        <tbody id="tbody-resp">
          <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--muted)">Carregando…</td></tr>
        </tbody>
      </table>
    </div>
    <div class="tbl-hint">Clique em um responsável para expandir o analítico de conversas.</div>
  </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
Chart.register(ChartDataLabels);
const API_DADOS   = '<?= $this->site ?>chatwoot/dados';
const API_SERIE   = '<?= $this->site ?>chatwoot/serie';
const API_DETALHE = '<?= $this->site ?>chatwoot/detalhe';

const YEARS  = anosDesde(2025);
const MONTHS = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const TEAM   = ['Helena', 'Eduardo Pires', 'Priscila Rangel', 'Fernanda matos'];
const STATUSES = [
  { v: 'open',     l: 'Em aberto' },
  { v: 'resolved', l: 'Resolvidas' },
  { v: 'pending',  l: 'Pendentes' },
  { v: 'snoozed',  l: 'Adiadas' },
];

// Cores dos gráficos
const C = {
  conversas: '#13315c', ingressos: '#c9a227', consultas: '#7c3aed',
  resolvidas: '#1a9fe0', abertas: '#e8703a',
};

// fNum vem de js/bi-dashboard.js (já global). fDec/fDateTime/cleanCanal são
// específicos deste dashboard, sem equivalente no shared JS.
const fDec = (v, d = 2) => Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: d, maximumFractionDigits: d });
const fDateTime = v => {
  if (!v || String(v).startsWith('0000')) return '—';
  const d = new Date(String(v).replace(' ', 'T'));
  return isNaN(d) ? '—' : d.toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
const cleanCanal = c => (c || '').replace(/^Channel::/, '') || '—';

// ── Dropdowns (createMultiDropdown vem de js/bi-dashboard.js, já global) ────
const yearDropdown   = createMultiDropdown('year-wrap',   'year-btn',   'year-list',   YEARS,    l => l,   l => l);
const monthDropdown  = createMultiDropdown('month-wrap',  'month-btn',  'month-list',  MONTHS,   l => l,   (l, i) => i + 1);
const respDropdown   = createMultiDropdown('resp-wrap',   'resp-btn',   'resp-list',   TEAM,     l => l,   l => l);
const statusDropdown = createMultiDropdown('status-wrap', 'status-btn', 'status-list', STATUSES, s => s.l, s => s.v);

// Padrão: ano e mês atuais
yearDropdown.select([String(new Date().getFullYear())]);
monthDropdown.select([String(new Date().getMonth() + 1)]);

// ── Estado ────────────────────────────────────────────────────
let chartResp = null, chartPeriodo = null;
let currentFilters = {};
let currentGran = 'mes';

function setLoading(on) {
  document.body.classList.toggle('loading', on);
  document.getElementById('loading-overlay').classList.toggle('hidden', !on);
}

function buildParams(extra = {}) {
  const p = new URLSearchParams();
  const f = currentFilters;
  if (f.year)        p.set('year', f.year);
  if (f.months)      p.set('months', f.months);
  if (f.responsavel) p.set('responsavel', f.responsavel);
  if (f.status)      p.set('status', f.status);
  for (const k in extra) p.set(k, extra[k]);
  return p;
}

// ── Carga principal ───────────────────────────────────────────
async function loadDashboard() {
  setLoading(true);
  try {
    const res = await fetch(API_DADOS + '?' + buildParams());
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const data = await res.json();

    renderKpis(data.kpis, data.kpisPrev);
    renderChartResp(data.porResponsavel);
    renderTabela(data.porResponsavel);

    const f = currentFilters;
    const parts = [];
    if (f.responsavel) parts.push(f.responsavel);
    if (f.status) {
      const labels = f.status.split(',').map(v => STATUSES.find(s => s.v === v)?.l || v);
      parts.push(labels.join(', '));
    }
    if (f.year) parts.push(f.year);
    if (f.months) {
      const nums   = f.months.split(',').map(Number);
      const labels = nums.map(n => MONTHS[n - 1]);
      parts.push(labels.join(', '));
    }
    document.getElementById('badge-texto').textContent = parts.length
      ? parts.join(' · ').toUpperCase()
      : 'TODOS OS PERÍODOS';

    await loadSerie();
  } catch (e) {
    console.error(e);
  } finally {
    setLoading(false);
  }
}

async function loadSerie() {
  try {
    const res = await fetch(API_SERIE + '?' + buildParams({ gran: currentGran }));
    const data = await res.json();
    renderChartPeriodo(data.serie);
  } catch (e) { console.error(e); }
}

// ── KPIs com MoM direcional ───────────────────────────────────
// dir: 'up' = maior é melhor | 'down' = menor é melhor
function momBadge(cur, prev, dir) {
  cur = Number(cur || 0); prev = Number(prev || 0);
  const prevTxt = `<span class="kpi-mom-prev">mês ant.: ${fNum(Math.round(prev))}</span>`;
  if (!prev) return prevTxt;
  const diff = cur - prev;
  const pct = Math.abs(diff / prev * 100);
  let cls = 'flat', arrow = '→';
  if (diff > 0) { arrow = '▲'; cls = (dir === 'up') ? 'up' : 'down'; }
  else if (diff < 0) { arrow = '▼'; cls = (dir === 'up') ? 'down' : 'up'; }
  return `${prevTxt}<span class="kpi-mom ${cls}">${arrow} ${fDec(pct, 1)}%</span>`;
}

function renderKpis(k, p) {
  const kpis = [
    { l: 'Ingressos Vendidos',   v: fNum(k.ingressos),   cur: k.ingressos,   prev: p.ingressos,   cls: 'c-green',  dir: 'up' },
    { l: 'Consultas Vendidas',   v: fNum(k.consultas),   cur: k.consultas,   prev: p.consultas,   cls: 'c-purple', dir: 'up' },
    { l: 'Total de Leads',       v: fNum(k.total_leads), cur: k.total_leads, prev: p.total_leads, cls: 'c-blue',   dir: 'up' },
    { l: 'TRM — 1ª resp. (min)', v: fDec(k.trm_min),     cur: k.trm_min,     prev: p.trm_min,     cls: 'c-teal',   dir: 'down' },
    { l: 'Duração Média (h)',    v: fDec(k.duracao_h),   cur: k.duracao_h,   prev: p.duracao_h,   cls: 'c-teal',   dir: 'down' },
    { l: 'Conversas em Aberto',  v: fNum(k.abertas),     cur: k.abertas,     prev: p.abertas,     cls: 'c-amber',  dir: 'down' },
    { l: 'Conversas Resolvidas', v: fNum(k.resolvidas),  cur: k.resolvidas,  prev: p.resolvidas,  cls: 'c-green',  dir: 'up' },
  ];
  document.getElementById('kpis').innerHTML = kpis.map(x => `
    <div class="kpi-chip ${x.cls}">
      <div class="kpi-chip-label">${x.l}</div>
      <div class="kpi-chip-val ${x.cls}">${x.v}</div>
      <div class="kpi-chip-sub">${momBadge(x.cur, x.prev, x.dir)}</div>
    </div>`).join('');
}

// ── Chart: por responsável ────────────────────────────────────
function sizeCanvas(canvas) {
  const wrap = canvas.parentElement;
  canvas.removeAttribute('style');
  canvas.width  = wrap.clientWidth  || 800;
  canvas.height = wrap.clientHeight || 300;
}

function comboConfig(labels, rows) {
  const bar = (key, label, color) => ({
    type: 'bar', label, data: rows.map(r => r[key]),
    backgroundColor: color, borderColor: color, yAxisID: 'y', order: 2,
    datalabels: {
      anchor: 'end', align: 'top', offset: 2, color: color, font: { size: 11, weight: '700' },
      formatter: v => v ? fNum(v) : '',
    },
  });
  const line = (key, label, color) => ({
    type: 'line', label, data: rows.map(r => r[key]),
    borderColor: color, backgroundColor: color + '33',
    yAxisID: 'y1', tension: .3, pointRadius: 3, pointHoverRadius: 5, order: 1, borderWidth: 2,
    datalabels: {
      align: 'top', offset: 6, color: color, font: { size: 11, weight: '700' }, clamp: true,
      formatter: v => (v === null || v === undefined) ? '' : fDec(v, 1) + '%',
    },
  });
  return {
    data: {
      labels,
      datasets: [
        bar('conversas',  'Conversas', C.conversas),
        bar('ingressos',  'Ingressos', C.ingressos),
        bar('consultas',  'Consultas', C.consultas),
        line('pct_resolvidas', '% Resolvidas', C.resolvidas),
        line('pct_abertas',    '% Em aberto',  C.abertas),
      ]
    },
    options: {
      responsive: false, maintainAspectRatio: false,
      layout: { padding: { top: 22 } },
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 11, font: { size: 10 }, usePointStyle: true } },
        tooltip: {
          callbacks: {
            label: ctx => {
              const pct = ctx.dataset.yAxisID === 'y1';
              return `${ctx.dataset.label}: ${pct ? fDec(ctx.parsed.y, 1) + '%' : fNum(ctx.parsed.y)}`;
            }
          }
        }
      },
      scales: {
        x: { ticks: { font: { size: 10 }, autoSkip: false, maxRotation: 30, minRotation: 0 } },
        y:  { position: 'left',  beginAtZero: true, title: { display: true, text: 'Contagem', font: { size: 9 } } },
        y1: { position: 'right', beginAtZero: true, max: 100, grid: { drawOnChartArea: false },
              ticks: { callback: v => v + '%', font: { size: 9 } } }
      }
    }
  };
}

function renderChartResp(rows) {
  const labels = rows.map(r => r.responsavel);
  const cfg = comboConfig(labels, rows);
  if (chartResp) chartResp.destroy();
  const canvas = document.getElementById('chartResp');
  sizeCanvas(canvas);
  chartResp = new Chart(canvas, cfg);
}

function renderChartPeriodo(serie) {
  const labels = serie.map(r => r.label);
  const cfg = comboConfig(labels, serie);
  if (chartPeriodo) chartPeriodo.destroy();
  const canvas = document.getElementById('chartPeriodo');
  sizeCanvas(canvas);
  chartPeriodo = new Chart(canvas, cfg);
}

// ── Tabela analítica ──────────────────────────────────────────
function renderTabela(rows) {
  document.getElementById('resp-count').textContent = rows.length + (rows.length === 1 ? ' responsável' : ' responsáveis');
  const tbody = document.getElementById('tbody-resp');
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--muted)">Nenhuma conversa no período.</td></tr>';
    return;
  }
  tbody.innerHTML = rows.map((r, i) => `
    <tr class="resp-row" data-resp="${encodeURIComponent(r.responsavel)}" data-idx="${i}">
      <td><span class="chevron">▸</span>${r.responsavel}</td>
      <td class="num">${fNum(r.conversas)}</td>
      <td>${fDateTime(r.criado_recente)}</td>
      <td>${fDateTime(r.ult_atividade)}</td>
      <td class="num">${r.trm_min == null ? '—' : fDec(r.trm_min)}</td>
      <td class="num">${r.duracao_h == null ? '—' : fDec(r.duracao_h)}</td>
      <td class="num td-green">${fNum(r.ingressos)}</td>
      <td class="num">${fNum(r.consultas)}</td>
    </tr>
    <tr class="detalhe-row" id="detalhe-${i}" style="display:none"><td colspan="8"></td></tr>`).join('');
  tbody.querySelectorAll('.resp-row').forEach(row => {
    row.addEventListener('click', () => toggleDetalhe(row));
  });
}

// ── Drill-down (expansão inline da linha) ─────────────────────
const detalheCache = {};

async function toggleDetalhe(row) {
  const idx = row.getAttribute('data-idx');
  const responsavel = decodeURIComponent(row.getAttribute('data-resp'));
  const detailRow = document.getElementById('detalhe-' + idx);
  const cell = detailRow.querySelector('td');

  if (row.classList.contains('open')) {
    row.classList.remove('open');
    detailRow.style.display = 'none';
    return;
  }
  row.classList.add('open');
  detailRow.style.display = '';

  const cacheKey = responsavel + '|' + (currentFilters.year || '') + '|' + (currentFilters.months || '') + '|' + (currentFilters.status || '');
  if (detalheCache[cacheKey]) { cell.innerHTML = detalheCache[cacheKey]; return; }

  cell.innerHTML = '<div class="detalhe-loading">Carregando conversas…</div>';
  try {
    const res = await fetch(API_DETALHE + '?' + buildParams({ responsavel }));
    const data = await res.json();
    const html = renderDetalhe(data.detalhe);
    detalheCache[cacheKey] = html;
    cell.innerHTML = html;
  } catch (e) {
    console.error(e);
    cell.innerHTML = '<div class="detalhe-loading">Erro ao carregar conversas.</div>';
  }
}

function renderDetalhe(rows) {
  if (!rows || !rows.length) {
    return '<div class="detalhe-loading">Nenhuma conversa no período.</div>';
  }
  return `<div class="detalhe-wrap"><table>
    <thead><tr>
      <th>Cliente</th><th>Status</th><th>Canal</th>
      <th>Criado em</th><th>1ª resposta</th><th>Últ. atividade</th>
      <th class="num">TRM (min)</th><th class="num">Duração (h)</th>
      <th class="num">Ingressos</th><th class="num">Consultas</th>
    </tr></thead>
    <tbody>${rows.map(r => `
      <tr>
        <td>${r.nome ? r.nome : (r.telefone || '—')}</td>
        <td><span class="status-badge ${r.status}">${r.status}</span></td>
        <td>${cleanCanal(r.canal)}</td>
        <td>${fDateTime(r.criado)}</td>
        <td>${fDateTime(r.resposta)}</td>
        <td>${fDateTime(r.ult_atividade)}</td>
        <td class="num">${r.trm_min == null ? '—' : fNum(r.trm_min)}</td>
        <td class="num">${r.duracao_h == null ? '—' : fNum(r.duracao_h)}</td>
        <td class="num">${r.ingressos}</td>
        <td class="num">${r.consultas}</td>
      </tr>`).join('')}
    </tbody>
  </table>${rows.length >= 500 ? '<div class="detalhe-loading">Mostrando as 500 conversas mais recentes do período.</div>' : ''}</div>`;
}

// ── Toggle de granularidade ───────────────────────────────────
document.getElementById('gran-toggle').addEventListener('click', e => {
  const btn = e.target.closest('button[data-gran]');
  if (!btn) return;
  document.querySelectorAll('#gran-toggle button').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  currentGran = btn.getAttribute('data-gran');
  loadSerie();
});

// ── Resize dos gráficos ───────────────────────────────────────
window.addEventListener('resize', () => {
  clearTimeout(window._rsz);
  window._rsz = setTimeout(() => {
    [['chartResp', chartResp], ['chartPeriodo', chartPeriodo]].forEach(([id, ch]) => {
      if (!ch) return;
      const c = document.getElementById(id);
      sizeCanvas(c);
      ch.resize(c.width, c.height);
    });
  }, 150);
});

// ── Filtros ───────────────────────────────────────────────────
function applyFilters() {
  for (const k in detalheCache) delete detalheCache[k];
  currentFilters = {
    year:        yearDropdown.getValue(),
    months:      monthDropdown.getValue(),
    responsavel: respDropdown.getValue(),
    status:      statusDropdown.getValue(),
  };
  loadDashboard();
}

function clearFilters() {
  yearDropdown.reset(); monthDropdown.reset(); respDropdown.reset(); statusDropdown.reset();
  for (const k in detalheCache) delete detalheCache[k];
  currentFilters = {};
  loadDashboard();
}

applyFilters();
</script>

</div>
