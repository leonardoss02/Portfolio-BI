<div class="dash-financeiro">

<style>
/* Estilos específicos do dashboard Financeiro, sobre css/bi-theme.css.
   Migrado de Power Bi/Financeiro/assets/financeiro.css — variáveis extras
   e variantes de cor não usadas por nenhum outro dashboard. */
.dash-financeiro {
  --teal: #0891b2;
  --teal-glow: rgba(8,145,178,.09);
}

/* Valor numérico menor para caber 8 chips em linha */
.dash-financeiro .kpi-chip {
  min-width: 90px;
  padding: 10px 12px;
  background: var(--card);
  box-shadow: none;
}
.dash-financeiro .kpi-chip:hover {
  box-shadow: none;
}
.dash-financeiro .kpi-chip-val {
  font-size: clamp(14px, 1.5vw, 19px);
  white-space: nowrap;
}
.dash-financeiro .kpi-chip-label {
  font-size: 10px;
  white-space: nowrap;
}

/* Variantes de cor dos KPI chips */
.dash-financeiro .kpi-chip.c-green { border-left: 3px solid var(--green); }
.dash-financeiro .kpi-chip.c-blue  { border-left: 3px solid var(--consulta); }
.dash-financeiro .kpi-chip.c-teal  { border-left: 3px solid var(--teal); }
.dash-financeiro .kpi-chip.c-amber { border-left: 3px solid var(--amber); }
.dash-financeiro .kpi-chip.c-red   { border-left: 3px solid var(--danger); }

/* .c-* (css/bi-theme.css) é badge/pill em outros contextos e vem com
   background próprio — aqui é só cor do número, sem o realce colorido atrás. */
.dash-financeiro .kpi-chip-val.c-green,
.dash-financeiro .kpi-chip-val.c-blue,
.dash-financeiro .kpi-chip-val.c-teal,
.dash-financeiro .kpi-chip-val.c-amber,
.dash-financeiro .kpi-chip-val.c-red {
  background: none;
}
.dash-financeiro .kpi-chip-val.c-green { color: var(--green); }
.dash-financeiro .kpi-chip-val.c-blue  { color: var(--consulta); }
.dash-financeiro .kpi-chip-val.c-teal  { color: var(--teal); }
.dash-financeiro .kpi-chip-val.c-amber { color: var(--amber); }
.dash-financeiro .kpi-chip-val.c-red   { color: var(--danger); }

.dash-financeiro .kpi-chip-sub {
  font-size: 10px;
  color: var(--muted);
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Gráfico */
.dash-financeiro .chart-wrap {
  position: relative;
  height: 280px;
}

/* Badges de status de parcela/cliente */
.dash-financeiro .status-badge {
  display: inline-block;
  padding: 2px 9px;
  border-radius: 999px;
  font-size: 9.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .4px;
  white-space: nowrap;
}
.dash-financeiro .status-badge.emdia        { background: var(--green-glow); color: var(--green); }
.dash-financeiro .status-badge.atrasado     { background: var(--amber-glow); color: var(--amber); }
.dash-financeiro .status-badge.inadimplente { background: var(--danger-glow); color: var(--danger); }
.dash-financeiro .status-badge.congelado    { background: var(--teal-glow);  color: var(--teal); }

/* Tabela de clientes — linhas expansíveis */
.dash-financeiro .cliente-row { cursor: pointer; }
.dash-financeiro .cliente-row.zebra td { background: var(--card2); }
.dash-financeiro .cliente-row:hover td { background: var(--card2); }
.dash-financeiro .cliente-row .chevron {
  display: inline-block;
  transition: transform .15s;
  margin-right: 6px;
  color: var(--muted);
}
.dash-financeiro .cliente-row.open .chevron { transform: rotate(90deg); }

/* Sub-tabela de parcelas */
.dash-financeiro .parcelas-row td { background: var(--card2); padding: 0; }
.dash-financeiro .parcelas-wrap { padding: 10px 14px 14px 34px; }
.dash-financeiro .parcelas-wrap table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
.dash-financeiro .parcelas-wrap th {
  text-align: left;
  padding: 5px 8px;
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: var(--muted);
  border-bottom: 1px solid var(--border);
}
.dash-financeiro .parcelas-wrap td { padding: 5px 8px; border-bottom: 1px solid var(--border); }
.dash-financeiro .parcelas-wrap .num { text-align: right; }
.dash-financeiro .parcelas-loading { padding: 14px; text-align: center; color: var(--muted); font-size: 11px; }

.dash-financeiro .tbl-wrap { overflow-x: auto; }

/* Cabeçalho ordenável da tabela de clientes */
.dash-financeiro #thead-clientes th {
  cursor: pointer;
  user-select: none;
}
.dash-financeiro #thead-clientes th:hover,
.dash-financeiro #thead-clientes th.th-active {
  background: var(--border);
  color: var(--text);
}
</style>

<div id="loading-overlay" class="hidden"></div>

<header class="dash-header">
  <div class="dash-header-inner">
    <div class="header-brand">
      <div class="brand-logo">$</div>
      <div class="brand-label">
        <span class="brand-name">Acme EUA</span>
        <span class="brand-page">Financeiro — Recebimentos &amp; Inadimplência</span>
      </div>
    </div>
    <div class="header-right">
      <span class="period-badge" id="badge-periodo">
        <span class="badge-dot"></span>
        <span id="badge-texto">carregando…</span>
      </span>
      <a class="btn-tab-switch" href="<?= $this->site ?>financeiro/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário &rarr;</a>
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
    <span class="filter-label">Cliente</span>
    <input type="text" class="filter-input" id="filtro-nome" placeholder="Nome do cliente..." onkeydown="if(event.key==='Enter') applyFilters()">
  </div>
  <div class="filter-group">
    <span class="filter-label">ID QB</span>
    <input type="text" class="filter-input" id="filtro-idqb" placeholder="ID QB..." onkeydown="if(event.key==='Enter') applyFilters()">
  </div>
  <button class="btn-apply" onclick="applyFilters()">Aplicar</button>
  <button class="btn-clear" onclick="clearFilters()">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'financeiro', badgeId: 'badge-periodo' });</script>

<main class="dash-main">

  <div class="kpi-row" id="kpis"></div>

  <div class="bi-card chart-card">
    <div class="bi-card-header chart-card-header">
      <span class="bi-card-title">Recebido, A Receber, Saldo Vencido e Resgatado por Mês</span>
    </div>
    <div class="bi-card-body">
      <div class="chart-wrap"><canvas id="chartMensal"></canvas></div>
    </div>
  </div>

  <div class="bi-card">
    <div class="bi-card-header" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">
      <span class="bi-card-title">Clientes</span>
      <div style="display:flex;align-items:center;gap:10px">
        <input
          type="text"
          id="clientes-search"
          placeholder="Buscar por nome ou status..."
          autocomplete="off"
          oninput="filtrarClientes()"
          style="height:30px;padding:0 10px;border-radius:var(--r-sm,6px);border:1px solid var(--border);background:var(--card2);color:var(--text);font-family:var(--font-ui);font-size:12.5px;outline:none;min-width:220px"
        >
        <span class="bi-card-sub" id="clientes-count"></span>
        <div class="exp-menu-wrap" id="clientes-export-wrap">
          <button class="exp-menu-btn" id="clientes-export-btn" type="button">Exportar ▾</button>
          <div class="exp-menu-list" id="clientes-export-list">
            <div class="exp-menu-item" data-fmt="csv">Exportar CSV</div>
            <div class="exp-menu-item" data-fmt="excel">Exportar Excel</div>
          </div>
        </div>
      </div>
    </div>
    <div class="tbl-wrap">
      <table class="bi-table">
        <thead id="thead-clientes"></thead>
        <tbody id="tbody-clientes">
          <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--muted)">Carregando…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
Chart.register(ChartDataLabels);

const API_DADOS    = '<?= $this->site ?>financeiro/dados';
const API_PARCELAS = '<?= $this->site ?>financeiro/parcelas';
const YEARS  = ['2019','2020','2021','2022','2023','2024','2025','2026'];
const MONTHS = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

// fUSD/fUSD0/fData não existem em js/bi-dashboard.js (fBRL de lá formata
// diferente, com espaço após o "$") — mantidos locais para preservar
// exatamente a formatação do dashboard original.
const fUSD  = v => '$' + Number(v || 0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
const fUSD0 = v => '$' + Number(v || 0).toLocaleString('en-US', {minimumFractionDigits:0, maximumFractionDigits:0});
const fData = v => { if(!v || v.startsWith('0000')) return '—'; const d=new Date(v+'T00:00:00'); return isNaN(d) ? '—' : d.toLocaleDateString('pt-BR'); };

// ── Dropdowns (createMultiDropdown vem de js/bi-dashboard.js, já global) ────
const yearDropdown  = createMultiDropdown('year-wrap','year-btn','year-list', YEARS, l=>l, l=>l);
const monthDropdown = createMultiDropdown('month-wrap','month-btn','month-list', MONTHS, l=>l, (l,i)=>i+1);

yearDropdown.select([String(new Date().getFullYear())]);

function setLoading(on){ document.body.classList.toggle('loading', on); document.getElementById('loading-overlay').classList.toggle('hidden', !on); }

let chart = null;
let currentYear = null, currentMonths = null;
let currentNome = null, currentIdqb = null;
const parcelasCache = {};

// ── Carregamento principal ──────────────────────────────────────────────────
async function loadDashboard(year, months, nome, idqb){
  setLoading(true);
  currentYear = year; currentMonths = months; currentNome = nome; currentIdqb = idqb;
  try {
    const params = new URLSearchParams();
    if (year)   params.set('year', year);
    if (months) params.set('months', months);
    if (nome)   params.set('nome', nome);
    if (idqb)   params.set('idqb', idqb);

    const res = await fetch(API_DADOS + '?' + params);
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const data = await res.json();

    renderKpis(data.kpis);
    renderChart(data.serie);
    renderClientes(data.clientes);

    const parts = [];
    if (nome)   parts.push('Cliente: ' + nome);
    if (idqb)   parts.push('ID QB: ' + idqb);
    if (year)   parts.push(year);
    if (months) {
      const nums   = months.split(',').map(Number);
      const labels = nums.map(n => MONTHS[n - 1]);
      parts.push(labels.join(', '));
    }
    document.getElementById('badge-texto').textContent = parts.length
      ? parts.join(' · ').toUpperCase()
      : 'TODOS OS PERÍODOS';
  } catch(e) {
    console.error(e);
  } finally {
    setLoading(false);
  }
}

// ── KPI chips ───────────────────────────────────────────────────────────────
function renderKpis(k){
  const pct = (a,b) => b > 0 ? ((a/b)*100).toFixed(1)+'%' : '—';
  const kpis = [
    { l:'Total Recebido',        v:fUSD0(k.recebido),     cls:'c-green', c:fNum(k.cRecebido),     cu:'faturas pagas' },
    { l:'Resgatado (+30d)',      v:fUSD0(k.resgatado),    cls:'c-teal',  c:fNum(k.cResgatado),    cu:'faturas resgatadas' },
    { l:'A Receber (Em Dia)',    v:fUSD0(k.emDia),        cls:'c-blue',  c:fNum(k.cEmDia),        cu:'faturas em dia' },
    { l:'Saldo Atrasado',        v:fUSD0(k.atrasado),     cls:'c-amber', c:fNum(k.cAtrasado),     cu:'faturas atrasadas' },
    { l:'Saldo Inadimplente',    v:fUSD0(k.inadimplente), cls:'c-red',   c:fNum(k.cInadimplente), cu:'faturas inadimplentes' },
    { l:'Saldo Total Vencido',   v:fUSD0(k.saldoVencido), cls:'c-red',   c:pct(k.inadimplente, k.saldoVencido), cu:'inadimplente' },
  ];
  document.getElementById('kpis').innerHTML = kpis.map(x => `
    <div class="kpi-chip ${x.cls}">
      <div class="kpi-chip-label">${x.l}</div>
      <div class="kpi-chip-val ${x.cls}">${x.v}</div>
      <div class="kpi-chip-sub">${x.c} ${x.cu}</div>
    </div>`).join('');
}

// ── Gráfico mensal ──────────────────────────────────────────────────────────
function monthLabel(ym){
  const [y,m] = ym.split('-');
  return MONTHS[parseInt(m,10)-1] + '/' + y.slice(2);
}

function sizeCanvas(canvas){
  const wrap = canvas.parentElement;
  canvas.removeAttribute('style');
  canvas.width  = wrap.clientWidth  || 800;
  canvas.height = wrap.clientHeight || 280;
}

// Com poucos meses, preenche com entradas vazias nas pontas para centralizar visualmente
const CHART_MIN_POINTS = 10;
function padSeries(serie, minPoints){
  if (serie.length >= minPoints) return serie;
  const totalPad = minPoints - serie.length;
  const padLeft  = Math.floor(totalPad / 2);
  const padRight = totalPad - padLeft;
  const blank = () => ({ ym: '', rec: null, emd: null, atr: null, resg: null });
  return [
    ...Array.from({length: padLeft},  blank),
    ...serie,
    ...Array.from({length: padRight}, blank),
  ];
}

const fUSDk = v => (v === null || v === undefined) ? '' : '$' + (v/1000).toFixed(0) + 'k';

function renderChart(serie){
  const padded = padSeries(serie, CHART_MIN_POINTS);
  const labels = padded.map(r => r.ym ? monthLabel(r.ym) : '​');
  const ds = (key, label, color) => ({
    label, data: padded.map(r => r[key]), borderColor: color,
    backgroundColor: color + '22', tension: .3, spanGaps: true,
    pointRadius: 3, pointHoverRadius: 5,
    datalabels: {
      display: 'auto', align: 'top', offset: 4, clamp: true,
      color: color, font: { size: 9, weight: '700' },
      formatter: fUSDk,
    },
  });
  const cfg = {
    type: 'line',
    data: {
      labels,
      datasets: [
        ds('rec',  'Valor Recebido',  '#0d8a52'),
        ds('emd',  'Valor A Receber', '#1a6ec8'),
        ds('atr',  'Saldo Vencido',   '#b86e00'),
        ds('resg', 'Resgatado',       '#0891b2'),
      ]
    },
    options: {
      responsive: false, maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      layout: { padding: { top: 16 } },
      plugins: {
        legend: {
          position: 'top',
          labels: {
            boxWidth: 12, font: { size: 11 },
            // As cores de bi-theme.css (borderColor) já são sólidas; o problema é
            // o backgroundColor do dataset (translúcido, pra área do gráfico), que
            // o Chart.js usaria por padrão também na caixinha da legenda — aqui
            // ela é gerada de novo com fillStyle sólido, sem tocar no fill da linha.
            generateLabels: chart => Chart.defaults.plugins.legend.labels.generateLabels(chart)
              .map(item => ({ ...item, fillStyle: chart.data.datasets[item.datasetIndex].borderColor, strokeStyle: chart.data.datasets[item.datasetIndex].borderColor })),
          },
        },
        tooltip: {
          filter: ctx => ctx.parsed.y !== null,
          callbacks: {
            label: ctx => ctx.dataset.label + ': ' + fUSD(ctx.parsed.y),
            // Mesmo motivo do generateLabels acima: caixinha sólida na cor da linha.
            labelColor: ctx => ({ backgroundColor: ctx.dataset.borderColor, borderColor: ctx.dataset.borderColor }),
          }
        }
      },
      scales: {
        x: { ticks: { autoSkip: false, callback: (val, i) => padded[i]?.ym ? monthLabel(padded[i].ym) : '' } },
        y: { ticks: { callback: v => '$' + (v/1000).toFixed(0) + 'k' } }
      }
    }
  };
  if (chart) chart.destroy();
  const canvas = document.getElementById('chartMensal');
  sizeCanvas(canvas);
  chart = new Chart(canvas, cfg);

  clearTimeout(window._chartResizeTimer);
  window.onresize = () => {
    clearTimeout(window._chartResizeTimer);
    window._chartResizeTimer = setTimeout(() => {
      if (!chart) return;
      sizeCanvas(canvas);
      chart.resize(canvas.width, canvas.height);
    }, 150);
  };
}

// ── Tabela de clientes ──────────────────────────────────────────────────────
const STATUS_CLASS = { 'Em dia':'emdia', 'Atrasado':'atrasado', 'Inadimplente':'inadimplente', 'Congelado':'congelado' };

// ── Colunas da tabela (ordenação por clique-no-cabeçalho) ────────
const COLS_CLIENTES = [
  { k: 'nome',        label: 'Cliente',     type: 'text' },
  { k: 'status',      label: 'Status',      type: 'text' },
  { k: 'qtdParcelas', label: 'Parcelas',    type: 'num', num: true },
  { k: 'valorTotal',  label: 'Valor Total', type: 'num', num: true },
  { k: 'valorPago',   label: 'Valor Pago',  type: 'num', num: true },
  { k: 'saldo',       label: 'Saldo',       type: 'num', num: true },
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

function escHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function sortValueClientes(v, type) {
  if (v === null || v === undefined || v === '') return null;
  if (type === 'num') return Number(v);
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
    const active = c.k === _sortKeyClientes;
    const arrow  = active ? (_sortDirClientes === 'asc' ? ' ▲' : ' ▼') : '';
    const cls    = [c.num ? 'num' : '', active ? 'th-active' : ''].filter(Boolean).join(' ');
    return `<th class="${cls}" title="Clique para ordenar" tabindex="0" role="button" aria-label="Ordenar por ${c.label}" onclick="sortByClientes('${c.k}')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();sortByClientes('${c.k}')}">${c.label}${arrow}</th>`;
  }).join('') + '</tr>';
}
renderHeadClientes();

function renderClientes(rows){
  ultimasLinhasClientes = rows || [];
  const searchInput = document.getElementById('clientes-search');
  if (searchInput) searchInput.value = '';
  renderLinhasClientes(ordenarClientes(ultimasLinhasClientes));
}

// ── Busca client-side por Nome ou Status (as linhas já vêm carregadas do período) ──
// Retorna a tabela "como está na tela" (busca + ordenação atuais) — usado tanto
// pra renderizar quanto pro export do consolidado.
function linhasClientesVisiveis() {
  const termo = (document.getElementById('clientes-search').value || '').trim().toLowerCase();
  const base = termo
    ? ultimasLinhasClientes.filter(r =>
        (r.nome || '').toLowerCase().includes(termo) ||
        (r.status || '').toLowerCase().includes(termo))
    : ultimasLinhasClientes;
  return { linhas: ordenarClientes(base), filtrado: !!termo };
}

function filtrarClientes() {
  const { linhas, filtrado } = linhasClientesVisiveis();
  renderLinhasClientes(linhas, filtrado);
}

createExportMenu('clientes-export-wrap', 'clientes-export-btn', 'clientes-export-list', fmt => {
  const { linhas } = linhasClientesVisiveis();
  const nome = 'clientes-financeiro-' + new Date().toISOString().slice(0, 10);
  if (fmt === 'csv') exportRowsAsCsv(COLS_CLIENTES, linhas, nome);
  else               exportRowsAsExcel(COLS_CLIENTES, linhas, nome);
});

function renderLinhasClientes(rows, filtrado = false){
  const tbody = document.getElementById('tbody-clientes');
  const count = document.getElementById('clientes-count');
  if (!rows.length) {
    count.textContent = filtrado ? '0 clientes' : '';
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:32px;color:var(--muted)">${filtrado ? 'Nenhum cliente encontrado para essa busca.' : 'Nenhum cliente encontrado no período.'}</td></tr>`;
    return;
  }
  count.textContent = rows.length + ' clientes';
  tbody.innerHTML = rows.map((r, i) => `
    <tr class="cliente-row${i % 2 === 1 ? ' zebra' : ''}" data-idx="${i}" data-customer="${r.customerId}" data-nome="${escHtml(r.nome || '')}">
      <td><span class="chevron">▸</span>${escHtml(r.nome) || '(sem nome)'}</td>
      <td><span class="status-badge ${STATUS_CLASS[r.status] || ''}">${r.status}</span></td>
      <td class="num">${fNum(r.qtdParcelas)}</td>
      <td class="num">${fUSD(r.valorTotal)}</td>
      <td class="num td-green">${fUSD(r.valorPago)}</td>
      <td class="num">${fUSD(r.saldo)}</td>
    </tr>
    <tr class="parcelas-row" id="parcelas-${i}" style="display:none"><td colspan="6"></td></tr>
  `).join('');

  tbody.querySelectorAll('.cliente-row').forEach(row => {
    row.addEventListener('click', () => toggleParcelas(row));
  });
}

async function toggleParcelas(row){
  const idx = row.getAttribute('data-idx');
  const customer = row.getAttribute('data-customer');
  const nome = row.getAttribute('data-nome') || '';
  const detailRow = document.getElementById('parcelas-' + idx);
  const isOpen = row.classList.contains('open');

  if (isOpen) {
    row.classList.remove('open');
    detailRow.style.display = 'none';
    return;
  }
  row.classList.add('open');
  detailRow.style.display = '';
  const cell = detailRow.querySelector('td');

  const cacheKey = customer + '|' + currentYear + '|' + currentMonths;
  let rows = parcelasCache[cacheKey];
  if (!rows) {
    cell.innerHTML = '<div class="parcelas-loading">Carregando parcelas…</div>';
    try {
      const params = new URLSearchParams({ customer });
      if (currentYear)   params.set('year', currentYear);
      if (currentMonths) params.set('months', currentMonths);
      const res = await fetch(API_PARCELAS + '?' + params);
      const data = await res.json();
      rows = data.rows || [];
      parcelasCache[cacheKey] = rows;
    } catch(e) {
      console.error(e);
      cell.innerHTML = '<div class="parcelas-loading">Erro ao carregar parcelas.</div>';
      return;
    }
  }
  cell.innerHTML = renderParcelasTable(rows);
  const btnAnalitico = cell.querySelector('.btn-export-analitico');
  if (btnAnalitico) btnAnalitico.addEventListener('click', () => exportarAnaliticoCliente(nome, rows));
}

// ── Analítico (parcelas) de um cliente — baixado sob demanda, a partir das
// linhas já carregadas para o drill-down (mesmos dados exibidos na tela).
const COLS_PARCELAS = [
  { k: 'idqb',           label: 'ID QB' },
  { k: 'email',          label: 'Email' },
  { k: 'telefone',       label: 'Telefone' },
  { k: 'valorParcela',   label: 'Valor Parcela' },
  { k: 'valorPago',      label: 'Valor Pago' },
  { k: 'saldo',          label: 'Saldo' },
  { k: 'dataPagamento',  label: 'Data Pagamento' },
  { k: 'dataVencimento', label: 'Data Vencimento' },
  { k: 'status',         label: 'Status' },
];

function parcelaParaExport(p){
  return {
    idqb: p.idqb ?? '',
    email: p.email || '',
    telefone: p.telefone || '',
    valorParcela: p.valorParcela,
    valorPago: p.valorPago,
    saldo: p.saldo,
    dataPagamento: fData(p.dataPagamento),
    dataVencimento: fData(p.dataVencimento),
    status: p.status,
  };
}

function exportarAnaliticoCliente(nome, rows){
  // normalize('NFD') separa acento da letra (ex.: "é" -> "e" + combining acute);
  // o filter abaixo descarta só os combining marks (faixa Unicode 0x0300-0x036F).
  const semAcento = (nome || 'cliente').normalize('NFD')
    .split('').filter(ch => { const c = ch.charCodeAt(0); return c < 0x300 || c > 0x36f; }).join('');
  const slug = semAcento.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  exportRowsAsCsv(COLS_PARCELAS, rows.map(parcelaParaExport), 'analitico-' + (slug || 'cliente'));
}

function renderParcelasTable(rows){
  if (!rows || !rows.length) {
    return '<div class="parcelas-loading">Nenhuma parcela no período.</div>';
  }
  return `<div class="parcelas-wrap">
    <div style="display:flex;justify-content:flex-end;margin-bottom:8px">
      <button class="exp-menu-btn btn-export-analitico" type="button">Baixar Analítico (CSV)</button>
    </div>
    <table>
    <thead><tr>
      <th>ID QB</th><th>Email</th><th>Telefone</th>
      <th class="num">Valor Parcela</th><th class="num">Valor Pago</th><th class="num">Saldo</th>
      <th>Data Pagamento</th><th>Data Vencimento</th><th>Status</th>
    </tr></thead>
    <tbody>${rows.map(p => `
      <tr>
        <td>${p.idqb ?? '—'}</td>
        <td>${p.email || '—'}</td>
        <td>${p.telefone || '—'}</td>
        <td class="num">${fUSD(p.valorParcela)}</td>
        <td class="num td-green">${fUSD(p.valorPago)}</td>
        <td class="num">${fUSD(p.saldo)}</td>
        <td>${fData(p.dataPagamento)}</td>
        <td>${fData(p.dataVencimento)}</td>
        <td><span class="status-badge ${STATUS_CLASS[p.status] || ''}">${p.status}</span></td>
      </tr>`).join('')}
    </tbody>
  </table></div>`;
}

// ── Filtros ─────────────────────────────────────────────────────────────────
function applyFilters(){
  for (const k in parcelasCache) delete parcelasCache[k];
  const nome = document.getElementById('filtro-nome').value.trim() || null;
  const idqb = document.getElementById('filtro-idqb').value.trim() || null;
  loadDashboard(yearDropdown.getValue(), monthDropdown.getValue(), nome, idqb);
}

function clearFilters(){
  yearDropdown.reset(); monthDropdown.reset();
  document.getElementById('filtro-nome').value = '';
  document.getElementById('filtro-idqb').value = '';
  for (const k in parcelasCache) delete parcelasCache[k];
  loadDashboard(null, null, null, null);
}

applyFilters();
</script>

</div>
