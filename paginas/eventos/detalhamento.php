<div class="dash-eventos">

<style>
.dash-eventos .kpi-mini-grid {
  display: grid; grid-template-columns: repeat(8, 1fr); gap: 12px; margin-bottom: 16px;
}
@media (max-width: 1150px) { .dash-eventos .kpi-mini-grid { grid-template-columns: repeat(4, 1fr); } }
@media (max-width: 640px)  { .dash-eventos .kpi-mini-grid { grid-template-columns: repeat(2, 1fr); } }
.dash-eventos .kpi-mini-card {
  background: var(--card); border: 1px solid var(--border); border-radius: var(--r-lg);
  padding: 13px 12px; text-align: center;
}
.dash-eventos .kpi-mini-card .kc-t { font-size: 11px; font-weight: 700; letter-spacing: .03em; color: var(--kc, var(--muted)); margin-bottom: 7px; }
.dash-eventos .kpi-mini-card .kc-v { font-family: var(--font-mono); font-weight: 700; font-size: 21px; color: var(--kc, var(--text)); line-height: 1; white-space: nowrap; }
.dash-eventos .kpi-mini-card .kc-v.kc-v-sm { font-size: 16px; }
.dash-eventos .kc-blue  { --kc: var(--consulta); }
.dash-eventos .kc-green { --kc: var(--fecho); }
.dash-eventos .kc-red   { --kc: var(--red); }
.dash-eventos .kc-gold  { --kc: var(--gold); }

.dash-eventos .det-wrap {
  overflow-x: auto; scrollbar-width: thin; scrollbar-color: var(--border) var(--card2);
}
.dash-eventos .det-wrap::-webkit-scrollbar { height: 9px; }
.dash-eventos .det-wrap::-webkit-scrollbar-track { background: var(--card2); border-radius: 999px; }
.dash-eventos .det-wrap::-webkit-scrollbar-thumb { background: var(--border); border-radius: 999px; }
.dash-eventos .det-wrap::-webkit-scrollbar-thumb:hover { background: var(--muted); }
table.det { width: 100%; border-collapse: collapse; font-size: 12px; }
table.det thead th {
  background: var(--card2); color: var(--muted); font-size: 9.5px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .04em; padding: 11px 7px; text-align: center;
  white-space: nowrap; position: sticky; top: 0; z-index: 1; cursor: pointer; user-select: none;
  border-bottom: 1px solid var(--border); transition: background .12s, color .12s;
}
table.det thead th:hover, table.det thead th.th-active { background: var(--border); color: var(--text); }
table.det thead th:first-child { text-align: left; }
table.det tbody td {
  padding: 7px 7px; border-bottom: 1px solid var(--border); text-align: center;
  font-family: var(--font-mono); font-weight: 600; white-space: nowrap; color: var(--text);
}
table.det tbody td:first-child { text-align: left; font-family: var(--font-ui); font-weight: 500; }
table.det tbody tr:hover td { background: var(--card2); }
table.det tbody tr.total td { border-top: 2px solid var(--border); border-bottom: none; background: var(--card2); font-weight: 800; }
.dash-eventos .c-red   { color: var(--red); }
.dash-eventos .c-blue  { color: var(--consulta); }
.dash-eventos .c-green { color: var(--green); }
.dash-eventos .c-danger{ color: var(--danger); }
.dash-eventos .c-muted { color: var(--muted); }
.dash-eventos .roi-cell { border-radius: 6px; padding: 3px 9px; display: inline-block; min-width: 46px; text-align: center; font-family: var(--font-mono); font-weight: 700; }
.dash-eventos .rc-green  { background: var(--green-glow);  color: var(--green); }
.dash-eventos .rc-amber  { background: var(--amber-glow);  color: var(--amber); }
.dash-eventos .rc-danger { background: var(--danger-glow); color: var(--danger); }
</style>

<!-- ── FILTROS ─────────────────────────────────────────────── -->
<section class="filters is-collapsible" aria-label="Filtros do dashboard">
  <button class="filters-toggle" type="button" aria-expanded="true" aria-controls="filters-fields">
    <span>Filtros</span>
    <span class="dd-caret filters-toggle-caret">&#x25BE;</span>
  </button>
  <div class="filters-fields" id="filters-fields">
  <div class="filter-pair">
    <span class="filter-label">Data do evento</span>
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

  <div class="filter-pair">
    <span class="filter-label">Ano</span>
    <div class="multi-dd-wrap" id="year-wrap">
      <button class="multi-dd-btn" id="year-btn" type="button">Todos <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="year-list"></div>
    </div>
  </div>

  <div class="filter-pair">
    <span class="filter-label">Mês</span>
    <div class="multi-dd-wrap" id="month-wrap">
      <button class="multi-dd-btn" id="month-btn" type="button">Todos <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="month-list"></div>
    </div>
  </div>

  <div class="filter-sep"></div>

  <div class="filter-pair">
    <span class="filter-label">Evento</span>
    <div class="camp-wrap" id="evento-wrap">
      <div class="camp-tags" id="evento-tags"></div>
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="evento-input" class="camp-input" type="text" placeholder="Pesquisar evento..." autocomplete="off" aria-label="Pesquisar evento" aria-autocomplete="list" aria-controls="evento-dropdown">
        <button class="camp-clear" id="evento-clear" aria-label="Limpar evento" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-dropdown" id="evento-dropdown" role="listbox"></div>
    </div>
  </div>

  <button class="btn-apply" id="btn-apply" onclick="applyFilters()">Aplicar</button>
  <button class="btn-clear" id="btn-clear" onclick="clearFilters()">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'eventos-detalhamento' });</script>

<!-- ── LOADING ─────────────────────────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>

<!-- ── ERRO ────────────────────────────────────────────────── -->
<div id="error-banner" role="alert"></div>

<!-- ── HEADER ──────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo">ME</div>
    <div>
      <div class="header-title">Consolidado por Evento</div>
      <div class="header-sub">Comercial &amp; Financeiro</div>
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
    <span class="header-badge" id="filter-label-badge">TODOS OS PERÍODOS</span>
    <span class="last-updated" id="last-updated">—</span>
    <div class="tab-switch">
      <a class="tab-switch-btn" href="<?= $this->site ?>eventos<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Pipeline</a>
      <a class="tab-switch-btn active" href="<?= $this->site ?>eventos/detalhamento<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Consolidado</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>eventos/relatorio<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Relatório</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>eventos/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
    </div>
  </div>
</header>

<!-- ── CONTEÚDO PRINCIPAL ──────────────────────────────────── -->
<main class="main" id="dashboard-main" aria-label="Consolidado por Evento">

  <div class="kpi-mini-grid" id="kpi-mini-grid"></div>

  <div class="bi-card">
    <div class="bi-card-body det-wrap">
      <table class="det">
        <thead id="det-head"></thead>
        <tbody id="det-body"></tbody>
      </table>
    </div>
  </div>

</main>

<script>
const API       = '<?= $this->site ?>eventos/dados';
const LISTA_API = '<?= $this->site ?>eventos/lista';

const YEARS  = ['2024', '2025', '2026', '2027'];
const MONTHS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

const yearDropdown  = createMultiDropdown('year-wrap',  'year-btn',  'year-list',  YEARS,  l => l, l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);

const NOW_YEAR = String(new Date().getFullYear());
yearDropdown.select([NOW_YEAR]);

const eventoDropdown = createSearchDropdown(
  'evento-wrap', 'evento-input', 'evento-dropdown', 'evento-clear', 'evento-tags',
  'Nenhum evento encontrado'
);
let eventoRotuloParaId = {};

async function loadEventos() {
  try {
    const res  = await fetch(LISTA_API, { cache: 'no-store' });
    const data = await res.json();
    eventoRotuloParaId = {};
    (data.eventos || []).forEach(e => { eventoRotuloParaId[e.rotulo] = e.id; });
    eventoDropdown.load((data.eventos || []).map(e => e.rotulo));
  } catch (e) {
    console.warn('Não foi possível carregar a lista de eventos:', e.message);
  }
}

function getEventoIds() {
  const rotulos = eventoDropdown.getValue();
  if (!rotulos) return null;
  const ids = rotulos.split(',').map(r => eventoRotuloParaId[r]).filter(Boolean);
  return ids.length ? ids.join(',') : null;
}

function esc(s) {
  return String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
}

const fMoney2 = v => '$ ' + (Number(v) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const fRoiPlain = v => (v == null ? '—' : Number(v).toFixed(2));

// Cor da célula de ROI(x): verde = já realizado (ROI ≈ projeção); âmbar = ainda
// há potencial (ROI < projeção); vermelho = sem retorno (ROI 0/nulo).
function roiCellClass(roi, proj) {
  if (roi == null) return '';
  if (roi <= 0) return 'rc-danger';
  if (proj != null && roi >= proj - 0.005) return 'rc-green';
  return 'rc-amber';
}
const moneyClass = v => (+v > 0 ? 'c-green' : (+v < 0 ? 'c-danger' : 'c-muted'));

// ── Configuração das colunas (label + célula + valor de ordenação) ──────────
const COLS = [
  { k: 'nome_formatado',       label: 'Evento',               cell: e => esc(e.nome_formatado || e.nome) },
  { k: 'investimento_mkt',     label: 'Custo MKT',             tdcls: 'c-red',   cell: e => fBRL(e.investimento_mkt) },
  { k: 'custo_fisico',         label: 'Custo Físico',          tdcls: 'c-red',   cell: e => fBRL(e.custo_fisico) },
  { k: 'custo_total',          label: 'Custo Total',           tdcls: 'c-red',   cell: e => fBRL(e.custo_total) },
  { k: 'cadeiras',             label: 'Ingressos',             tdcls: 'c-blue',  cell: e => fNum(e.cadeiras) },
  { k: 'consultas_vendidas',   label: 'Consultas',             tdcls: 'c-blue',  cell: e => fNum(e.consultas_vendidas) },
  { k: 'sinais_pagos',         label: 'SinalPago',                               cell: e => fNum(e.sinais_pagos) },
  { k: 'combos',               label: 'Combos',                tdcls: 'c-green', cell: e => fNum(e.combos) },
  { k: 'faturamento',          label: 'Faturamento',                             cell: e => `<span class="${moneyClass(e.faturamento)}">${fBRL(e.faturamento)}</span>` },
  { k: 'lucro',                label: 'Lucro',                                   cell: e => `<span class="${moneyClass(e.lucro)}">${fBRL(e.lucro)}</span>` },
  { k: 'roi',                  label: 'ROI(x)',                                  cell: e => `<span class="roi-cell ${roiCellClass(e.roi, e.roi_projetado)}">${fRoiPlain(e.roi)}</span>` },
  { k: 'projecao_faturamento', label: 'Projeção Faturamento',                    cell: e => fMoney2(e.projecao_faturamento) },
  { k: 'roi_projetado',        label: 'ROI Projetado',                          cell: e => fRoiPlain(e.roi_projetado) },
];

let _data    = null;
let _sortKey = 'data';
let _sortDir = 'asc';

function sortBy(key) {
  if (_sortKey === key) {
    _sortDir = _sortDir === 'asc' ? 'desc' : 'asc';
  } else {
    _sortKey = key;
    _sortDir = (key === 'nome_formatado') ? 'asc' : 'desc';
  }
  renderTable();
}

function sortEventos(list) {
  const key = _sortKey, dir = (_sortDir === 'asc' ? 1 : -1);
  return list.slice().sort((a, b) => {
    let av = a[key], bv = b[key];
    if (key === 'nome_formatado') { av = a.nome_formatado || a.nome; bv = b.nome_formatado || b.nome; }
    if (key === 'nome_formatado' || key === 'data') {
      return dir * String(av).localeCompare(String(bv), 'pt', { numeric: true });
    }
    av = (av == null) ? -Infinity : +av;
    bv = (bv == null) ? -Infinity : +bv;
    return dir * (av - bv);
  });
}

function renderTable() {
  if (!_data) return;
  const t       = _data.totais || {};
  const eventos = sortEventos(_data.eventos || []);

  const head = '<tr>' + COLS.map(c => {
    const active = c.k === _sortKey;
    const arrow  = active ? (_sortDir === 'asc' ? ' ▲' : ' ▼') : '';
    return `<th class="${active ? 'th-active' : ''}" title="Clique para ordenar" tabindex="0" role="button" aria-label="Ordenar por ${c.label}" onclick="sortBy('${c.k}')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();sortBy('${c.k}')}">${c.label}${arrow}</th>`;
  }).join('') + '</tr>';

  const linhas = eventos.map(e =>
    '<tr>' + COLS.map(c => `<td class="${c.tdcls || ''}">${c.cell(e)}</td>`).join('') + '</tr>'
  ).join('');

  const total = '<tr class="total">' + COLS.map((c, i) =>
    i === 0 ? '<td>Total</td>' : `<td class="${c.tdcls || ''}">${c.cell(t)}</td>`
  ).join('') + '</tr>';

  document.getElementById('det-head').innerHTML = head;
  document.getElementById('det-body').innerHTML = linhas + total;
}

function renderKpis(t) {
  const kpis = [
    { l: 'Oportunidades', v: fNum(t.oportunidades),       c: 'kc-blue' },
    { l: 'Ingressos',     v: fNum(t.cadeiras),            c: 'kc-blue' },
    { l: 'Consultas',     v: fNum(t.consultas_vendidas),  c: 'kc-blue' },
    { l: 'Combos',        v: fNum(t.combos),              c: 'kc-green' },
    { l: 'Investimento',  v: fBRL(t.custo_total),         c: 'kc-red',   sm: true },
    { l: 'Faturamento',   v: fBRL(t.faturamento),         c: 'kc-gold',  sm: true },
    { l: 'Lucro',         v: fBRL(t.lucro),               c: 'kc-green', sm: true },
    { l: 'ROI',           v: fROI(t.roi),                 c: 'kc-blue' },
  ];
  document.getElementById('kpi-mini-grid').innerHTML = kpis.map(k =>
    `<div class="kpi-mini-card ${k.c}"><div class="kc-t">${k.l}</div><div class="kc-v${k.sm ? ' kc-v-sm' : ''}">${k.v}</div></div>`
  ).join('');
}

// Precedência EXCLUSIVA dos filtros — não combina. Extraída para que o link
// do Relatório PDF use exatamente a mesma regra do fetch, sem duplicá-la.
function buildParams(ano, mes, dataInicio, dataFim, eventos) {
  const params = new URLSearchParams();
  if (eventos) {
    params.set('eventos', eventos);
  } else if (dataInicio || dataFim) {
    if (dataInicio) params.set('data_inicio', dataInicio);
    if (dataFim)    params.set('data_fim', dataFim);
  } else {
    if (ano) params.set('ano', ano);
    if (mes) params.set('mes', mes);
  }
  return params;
}

async function loadDashboard(ano, mes, dataInicio, dataFim, eventos) {
  const params = buildParams(ano, mes, dataInicio, dataFim, eventos);

  document.getElementById('error-banner').textContent = '';
  document.getElementById('loading-overlay').classList.remove('hidden');
  document.body.classList.add('loading');
  try {
    const res  = await fetch(API + '?' + params.toString(), { cache: 'no-store' });
    const data = await res.json();
    if (data.error) {
      document.getElementById('error-banner').textContent = 'Erro: ' + data.error;
      return;
    }
    _data = data;
    renderKpis(data.totais || {});
    renderTable();
    document.getElementById('last-updated').textContent = 'Atualizado em: ' + new Date().toLocaleString('pt-BR');
  } catch (e) {
    document.getElementById('error-banner').textContent = 'Falha ao carregar: ' + e.message;
  } finally {
    document.getElementById('loading-overlay').classList.add('hidden');
    document.body.classList.remove('loading');
  }
}

function applyFilters() {
  const ano        = yearDropdown.getValue();
  const mes        = monthDropdown.getValue();
  const dataInicio = getDateInput('data-inicio');
  const dataFim    = getDateInput('data-fim');
  const eventos    = getEventoIds();
  updateBadge(ano, mes, eventoDropdown.getValue());
  loadDashboard(ano, mes, dataInicio, dataFim, eventos);
}

function clearFilters() {
  yearDropdown.reset();
  monthDropdown.reset();
  eventoDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim', 'data-fim-clear');
  yearDropdown.select([NOW_YEAR]);
  applyFilters();
}

// ── Badge do período selecionado ────────────────────────────────
function updateBadge(year, months, evento) {
  const badge = document.getElementById('filter-label-badge');
  if (!year && !months && !evento) {
    badge.textContent = 'TODOS OS PERÍODOS';
    return;
  }
  const parts = [];
  if (evento) parts.push(evento);
  if (year)   parts.push(year);
  if (months) {
    const nums   = months.split(',').map(Number);
    const labels = nums.map(n => MONTHS[n - 1]);
    parts.push(labels.join(', '));
  }
  badge.textContent = parts.join(' · ').toUpperCase();
}

['data-inicio', 'data-fim'].forEach(id => {
  const input    = document.getElementById(id);
  const clearId  = id + '-clear';
  const clearBtn = document.getElementById(clearId);
  input?.addEventListener('change', () => clearBtn?.classList.toggle('visible', !!input.value));
  clearBtn?.addEventListener('click', () => clearDateInput(id, clearId));
});

loadEventos();
applyFilters();
</script>

</div>
