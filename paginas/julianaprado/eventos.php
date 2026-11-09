<div class="dash-julianaprado-eventos">

<style>
.dash-julianaprado-eventos .det-wrap { overflow-x: auto; }
.dash-julianaprado-eventos table.abe-det { width: 100%; border-collapse: collapse; font-size: 12px; }
.dash-julianaprado-eventos table.abe-det thead th {
  background: var(--card2); color: var(--muted); font-size: 9.5px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .04em; padding: 11px 9px; text-align: center;
  white-space: nowrap; position: sticky; top: 0; z-index: 1; cursor: pointer; user-select: none;
  border-bottom: 1px solid var(--border); transition: background .12s, color .12s;
}
.dash-julianaprado-eventos table.abe-det thead th:hover,
.dash-julianaprado-eventos table.abe-det thead th.th-active { background: var(--border); color: var(--text); }
.dash-julianaprado-eventos table.abe-det thead th:first-child { text-align: left; }
.dash-julianaprado-eventos table.abe-det tbody td {
  padding: 7px 9px; border-bottom: 1px solid var(--border); text-align: center;
  font-family: var(--font-mono); font-weight: 600; white-space: nowrap; color: var(--text);
}
.dash-julianaprado-eventos table.abe-det tbody td:first-child { text-align: left; font-family: var(--font-ui); font-weight: 500; }
.dash-julianaprado-eventos table.abe-det tbody tr:hover td { background: var(--card2); }
.dash-julianaprado-eventos table.abe-det tbody tr.total td { border-top: 2px solid var(--border); border-bottom: none; background: var(--card2); font-weight: 800; }

.dash-julianaprado-eventos .funil-card.digital .funil-bar { background: var(--consulta); }
</style>

<!-- ── LOADING ─────────────────────────────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>

<!-- ── HEADER ─────────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo">CR</div>
    <div>
      <div class="header-title">Juliana Prado · Eventos</div>
      <div class="header-sub">Ingressos &rarr; Possibilidades &rarr; Consultas Vendidas</div>
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
      <a class="tab-switch-btn" href="<?= $this->site ?>julianaprado<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Juliana Prado</a>
      <a class="tab-switch-btn active" href="<?= $this->site ?>julianaprado/eventos<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Eventos</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>julianaprado/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
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

  <!-- Data do evento -->
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

  <button class="btn-apply" onclick="applyFilters()">Aplicar</button>
  <button class="btn-clear" onclick="clearFilters()">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'julianaprado-eventos' });</script>

<!-- ── ERRO ───────────────────────────────────────────────────── -->
<div id="error-banner" role="alert"></div>

<!-- ── CONTEÚDO PRINCIPAL ──────────────────────────────────────── -->
<main class="main" id="dashboard-main">

  <!-- 4 KPI CHIPS -->
  <div class="kpi-row">
    <div class="kpi-chip evento">
      <span class="kpi-chip-label">Ingressos Vendidos</span>
      <span class="kpi-chip-val" id="k-ingressos">—</span>
    </div>
    <div class="kpi-chip sinal">
      <span class="kpi-chip-label">Possibilidades</span>
      <span class="kpi-chip-val" id="k-possibilidades">—</span>
    </div>
    <div class="kpi-chip fecho highlight">
      <span class="kpi-chip-label">Consultas Vendidas</span>
      <span class="kpi-chip-val" id="k-consultas">—</span>
    </div>
  </div>

  <!-- FUNIL DE VENDAS -->
  <article class="funil-card digital">
    <div class="funil-header">
      <span class="funil-name">Funil de Vendas</span>
    </div>
    <div class="funil-body">
      <div class="funil-row">
        <span class="funil-row-label">Ingressos Vendidos</span>
        <div class="funil-bar-wrap"><div class="funil-bar" id="f-bar-ingressos" style="width:100%"></div></div>
        <span class="funil-row-val" id="f-ingressos">—</span>
        <span class="funil-row-cv">&nbsp;</span>
      </div>
      <div class="funil-row">
        <span class="funil-row-label">Possibilidades</span>
        <div class="funil-bar-wrap"><div class="funil-bar" id="f-bar-possibilidades" style="width:0%"></div></div>
        <span class="funil-row-val" id="f-possibilidades">—</span>
        <span class="funil-row-cv" id="f-cv-possibilidades">—</span>
      </div>
      <div class="funil-row">
        <span class="funil-row-label">Consultas Vendidas</span>
        <div class="funil-bar-wrap"><div class="funil-bar" id="f-bar-consultas" style="width:0%"></div></div>
        <span class="funil-row-val" id="f-consultas">—</span>
        <span class="funil-row-cv" id="f-cv-consultas">—</span>
      </div>
    </div>
  </article>

  <!-- CONSOLIDADO POR EVENTO -->
  <div class="bi-card">
    <div class="bi-card-header">
      <span class="bi-card-title">Consolidado por Evento</span>
    </div>
    <div class="bi-card-body det-wrap">
      <table class="abe-det">
        <thead id="det-head"></thead>
        <tbody id="det-body"></tbody>
      </table>
    </div>
  </div>

  <div class="last-updated" id="last-updated"></div>

</main>
</div>

<script>
// ══════════════════════════════════════════════════════════════
//  Juliana Prado — Aba Eventos (palestrante) — Dashboard JS
//  Consome: julianaprado/eventosDados (controller MRPHP)
//
//  createMultiDropdown, getDateInput, setEffectiveDate, clearDateInput,
//  fNum, fPctRatio, fBar são fornecidas por js/bi-dashboard.js (carregado
//  no <head> pelo template, antes deste script).
// ══════════════════════════════════════════════════════════════

const API    = '<?= $this->site ?>julianaprado/eventosDados';
const MONTHS = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const YEARS  = anosDesde(2025);

const yearDropdown  = createMultiDropdown('year-wrap',  'year-btn',  'year-list',  YEARS,  l => l, l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);

const NOW_YEAR = String(new Date().getFullYear());
yearDropdown.select([NOW_YEAR]);

// Botões de limpar das datas: aparecem quando a data tem valor.
['data-inicio', 'data-fim'].forEach(id => {
  const input = document.getElementById(id), clearBtn = document.getElementById(id + '-clear');
  input.addEventListener('change', () => clearBtn.classList.toggle('visible', !!input.value));
  clearBtn.addEventListener('click', () => clearDateInput(id, id + '-clear'));
});

function set(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }
function setWidth(id, v) { const el = document.getElementById(id); if (el) el.style.width = v; }

function fData(iso) {
  if (!iso) return '—';
  const [y, m, d] = String(iso).split(' ')[0].split('-');
  return (d && m && y) ? `${d}/${m}/${y}` : String(iso);
}

// ── Preencher KPIs ──────────────────────────────────────────────
function fillKpis(t) {
  set('k-ingressos',      fNum(t.ingressos_vendidos));
  set('k-possibilidades', fNum(t.possibilidades));
  set('k-consultas',      fNum(t.consultas_vendidas));
}

// ── Preencher Funil de Vendas ───────────────────────────────────
function fillFunil(t) {
  const ingressos      = t.ingressos_vendidos || 0;
  const possibilidades = t.possibilidades || 0;
  const consultas      = t.consultas_vendidas || 0;
  const L = ingressos || 1;

  set('f-ingressos', fNum(ingressos));
  setWidth('f-bar-ingressos', '100%');

  set('f-possibilidades', fNum(possibilidades));
  set('f-cv-possibilidades', fPctRatio(possibilidades, ingressos));
  setWidth('f-bar-possibilidades', fBar(possibilidades, L));

  set('f-consultas', fNum(consultas));
  set('f-cv-consultas', fPctRatio(consultas, possibilidades));
  setWidth('f-bar-consultas', fBar(consultas, L));
}

function esc(s) {
  return String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
}

// ── Configuração das colunas da tabela (label + célula + valor de ordenação) ──
const COLS = [
  { k: 'nome_evento',                   label: 'Evento',                      cell: e => esc(e.nome_evento) },
  { k: 'data_evento',                   label: 'Data do Evento',              cell: e => fData(e.data_evento) },
  { k: 'ingressos_vendidos',            label: 'Ingressos Vendidos',          cell: e => fNum(e.ingressos_vendidos) },
  { k: 'possibilidades',                label: 'Possibilidades',              cell: e => fNum(e.possibilidades) },
  { k: 'consultas_vendidas',            label: 'Consultas Vendidas',          cell: e => fNum(e.consultas_vendidas) },
  { k: 'pct_consultas_possibilidades',  label: '% Consultas/Possibilidades',  cell: e => fPctRatio(e.consultas_vendidas, e.possibilidades) },
];

let _data    = null;
let _sortKey = 'data_evento';
let _sortDir = 'desc';

function sortBy(key) {
  if (_sortKey === key) {
    _sortDir = _sortDir === 'asc' ? 'desc' : 'asc';
  } else {
    _sortKey = key;
    _sortDir = (key === 'nome_evento') ? 'asc' : 'desc';
  }
  renderTable();
}

function sortEventos(list) {
  const key = _sortKey, dir = (_sortDir === 'asc' ? 1 : -1);
  return list.slice().sort((a, b) => {
    if (key === 'data_evento' || key === 'nome_evento') {
      return dir * String(a[key] ?? '').localeCompare(String(b[key] ?? ''), 'pt', { numeric: true });
    }
    return dir * ((Number(a[key]) || 0) - (Number(b[key]) || 0));
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

  const linhas = eventos.length
    ? eventos.map(e => '<tr>' + COLS.map(c => `<td>${c.cell(e)}</td>`).join('') + '</tr>').join('')
    : `<tr><td colspan="${COLS.length}" style="text-align:center;color:var(--muted);padding:16px 0">Sem eventos no período</td></tr>`;

  const total = eventos.length
    ? '<tr class="total">' + COLS.map((c, i) => i === 0 ? '<td>Total</td>' : `<td>${c.cell(t)}</td>`).join('') + '</tr>'
    : '';

  document.getElementById('det-head').innerHTML = head;
  document.getElementById('det-body').innerHTML = linhas + total;
}

// ── Badge ────────────────────────────────────────────────────────
function updateBadge(year, months) {
  const badge = document.getElementById('filter-label-badge');
  if (!year && !months) {
    badge.textContent = 'TODOS OS PERÍODOS';
    return;
  }
  const parts = [];
  if (year)   parts.push(year);
  if (months) parts.push(months.split(',').map(n => MONTHS[Number(n) - 1]).join(', '));
  badge.textContent = parts.join(' · ').toUpperCase();
}

// ── Carregar dados ────────────────────────────────────────────────
async function loadDashboard(year, months, dataInicio, dataFim) {
  const overlay = document.getElementById('loading-overlay');
  const errBan  = document.getElementById('error-banner');
  overlay.classList.remove('hidden');
  document.body.classList.add('loading');
  errBan.style.display = 'none';
  errBan.classList.remove('visible');

  const params = new URLSearchParams();
  if (year)       params.set('year', year);
  if (months)     params.set('months', months);
  if (dataInicio) params.append('data_inicio', dataInicio);
  if (dataFim)    params.append('data_fim', dataFim);

  let url = API;
  if ([...params].length) url += '?' + params.toString();

  try {
    const res  = await fetch(url, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    _data = data;
    fillKpis(data.totais);
    fillFunil(data.totais);
    renderTable();

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
  const year       = yearDropdown.getValue();
  const months     = monthDropdown.getValue();
  const dataInicio = getDateInput('data-inicio');
  const dataFim    = getDateInput('data-fim');
  updateBadge(year, months);
  loadDashboard(year, months, dataInicio, dataFim);
}

function clearFilters() {
  yearDropdown.reset();
  monthDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim', 'data-fim-clear');
  updateBadge(null, null);
  loadDashboard(null, null, null, null);
}

applyFilters();
</script>
