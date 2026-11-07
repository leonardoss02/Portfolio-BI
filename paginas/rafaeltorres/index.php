<div class="dash-rafaeltorres">

<style>
/* Ajustes estéticos deste dashboard — borda vermelha nos KPIs, remoção do
   gradiente nas barras de funil/descarte e badge de % no final de cada linha.
   Validar aqui antes de replicar para os demais dashboards. */
.dash-rafaeltorres .kpi-chip {
  border-left: 3px solid var(--red);
}

.dash-rafaeltorres .funil-card.digital .funil-bar {
  background: var(--consulta);
}
.dash-rafaeltorres #f-bar-combos {
  background: var(--fecho);
}

.dash-rafaeltorres .funil-row-cv,
.dash-rafaeltorres .descarte-pct {
  display: inline-flex;
  align-items: center;
  width: auto;
  flex-shrink: 0;
  padding: 2px 8px;
  border-radius: 999px;
  font-family: var(--font-mono);
  font-size: 10.5px;
  font-weight: 700;
}
.dash-rafaeltorres .funil-row-cv {
  background: var(--consulta-glow);
  color: var(--consulta);
}
.dash-rafaeltorres .descarte-pct {
  background: var(--red-glow);
  color: var(--red);
}

.dash-rafaeltorres #thead-clientes th {
  cursor: pointer;
  user-select: none;
}
.dash-rafaeltorres #thead-clientes th:hover,
.dash-rafaeltorres #thead-clientes th.th-active {
  background: var(--border);
  color: var(--text);
}
.dash-rafaeltorres #thead-clientes th.th-nosort {
  cursor: default;
}
.dash-rafaeltorres #thead-clientes th.th-nosort:hover {
  background: var(--card2);
  color: var(--muted);
}
.dash-rafaeltorres .bx-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: var(--consulta);
  font-weight: 600;
  text-decoration: none;
  font-size: 12.5px;
}
.dash-rafaeltorres .bx-link:hover {
  text-decoration: underline;
}

.dash-rafaeltorres .tbl-wrap { position: relative; }
.dash-rafaeltorres .bi-table thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background: var(--card2);
}
</style>

<!-- ── LOADING ─────────────────────────────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>

<!-- ── HEADER ─────────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo">AB</div>
    <div>
      <div class="header-title">Dashboard Rafael Torres</div>
      <div class="header-sub">Consultas Agendadas &rarr; Realizadas &rarr; Combos Vendidos</div>
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
      <a class="tab-switch-btn active" href="<?= $this->site ?>rafaeltorres<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Rafael Torres</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>rafaeltorres/eventos<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Eventos</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>rafaeltorres/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
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

  <!-- Form (busca sobre valores brutos de formPreenchido_dealV2) -->
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
<script>initFiltersToggle({ key: 'rafaeltorres' });</script>

<!-- ── ERRO ───────────────────────────────────────────────────── -->
<div id="error-banner" role="alert"></div>

<!-- ── CONTEÚDO PRINCIPAL ──────────────────────────────────────── -->
<main class="main" id="dashboard-main">

  <!-- 6 KPI CHIPS -->
  <div class="kpi-row">
    <div class="kpi-chip">
      <span class="kpi-chip-label">Consultas Agendadas</span>
      <span class="kpi-chip-val" id="k-agendadas">—</span>
    </div>
    <div class="kpi-chip">
      <span class="kpi-chip-label">Consultas a Realizar</span>
      <span class="kpi-chip-val" id="k-a-realizar">—</span>
    </div>
    <div class="kpi-chip">
      <span class="kpi-chip-label">Consultas Realizadas</span>
      <span class="kpi-chip-val" id="k-realizadas">—</span>
    </div>
    <div class="kpi-chip">
      <span class="kpi-chip-label">Fp Realizado</span>
      <span class="kpi-chip-val" id="k-fp-realizado">—</span>
    </div>
    <div class="kpi-chip">
      <span class="kpi-chip-label">Assinatura Pendente</span>
      <span class="kpi-chip-val" id="k-assinatura">—</span>
    </div>
    <div class="kpi-chip">
      <span class="kpi-chip-label">Combos Vendidos</span>
      <span class="kpi-chip-val" id="k-combos">—</span>
    </div>
  </div>

  <!-- GRÁFICO DE PERÍODO -->
  <div class="chart-card">
    <div class="chart-header">
      <div class="chart-title-group">
        <span class="chart-dot" style="background:#3b82f6"></span>
        <span class="chart-title">Consultas Realizadas &amp; Combos Vendidos</span>
      </div>
      <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <div class="chart-legend">
          <div class="legend-item"><span class="legend-color" style="background:#d97706;height:2px"></span>Conversão %</div>
          <div class="legend-item"><span class="legend-color" style="background:#3b82f6"></span>Realizadas</div>
          <div class="legend-item"><span class="legend-color" style="background:#10b981"></span>Combos</div>
        </div>
        <div class="gran-toggle">
          <button class="gran-btn active" data-modo="anoMes"     onclick="setModo('anoMes')">Ano-Mês</button>
          <button class="gran-btn"        data-modo="dia_semana" onclick="setModo('dia_semana')">Dia da Semana</button>
          <button class="gran-btn"        data-modo="dia_mes"    onclick="setModo('dia_mes')">Dia do Mês</button>
        </div>
      </div>
    </div>
    <div class="chart-body" id="chart-body">
      <div style="height:220px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:12px">
        Carregando gráfico...
      </div>
    </div>
  </div>

  <div class="funis-grid">
    <!-- FUNIL DE VENDAS -->
    <article class="funil-card digital">
      <div class="funil-header">
        <span class="funil-name">Funil de Vendas</span>
      </div>
      <div class="funil-body">
        <div class="funil-row">
          <span class="funil-row-label">Cons. Agendadas</span>
          <div class="funil-bar-wrap"><div class="funil-bar" id="f-bar-agendadas" style="width:100%"></div></div>
          <span class="funil-row-val" id="f-agendadas">—</span>
          <span class="funil-row-cv" id="f-cv-agendadas">—</span>
        </div>
        <div class="funil-row">
          <span class="funil-row-label">Cons. Realizadas</span>
          <div class="funil-bar-wrap"><div class="funil-bar" id="f-bar-realizadas" style="width:0%"></div></div>
          <span class="funil-row-val" id="f-realizadas">—</span>
          <span class="funil-row-cv" id="f-cv-realizadas">—</span>
        </div>
        <div class="funil-row">
          <span class="funil-row-label">Combos Vendidos</span>
          <div class="funil-bar-wrap"><div class="funil-bar" id="f-bar-combos" style="width:0%"></div></div>
          <span class="funil-row-val" id="f-combos">—</span>
          <span class="funil-row-cv" id="f-cv-combos">—</span>
        </div>
      </div>
    </article>

    <!-- MOTIVOS DE DESCARTE (barra horizontal — top 15 + "Outros") -->
    <article class="funil-card">
      <div class="funil-header">
        <span class="funil-name">Motivos de Descarte</span>
      </div>
      <div class="funil-body" id="descarte-body">
        <div style="padding:12px 0;color:var(--muted);font-size:12px;text-align:center">Carregando...</div>
      </div>
    </article>
  </div>

  <!-- TABELA DE CLIENTES -->
  <div class="bi-card" style="margin-top:20px">
    <div class="bi-card-header" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">
      <span class="bi-card-title">Clientes</span>
      <div style="display:flex;align-items:center;gap:10px">
        <input
          type="text"
          id="clientes-search"
          placeholder="Buscar por nome do deal ou email..."
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
    <div class="tbl-wrap" style="max-height:520px;overflow:auto">
      <table class="bi-table">
        <thead id="thead-clientes"></thead>
        <tbody id="tbody-clientes">
          <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--muted)">Carregando…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="last-updated" id="last-updated"></div>

</main>
</div>

<script>
// ══════════════════════════════════════════════════════════════
//  Rafael Torres — Dashboard JS
//  Consome: rafaeltorres/dados, rafaeltorres/forms (controller MRPHP)
//
//  createMultiDropdown, createSearchDropdown, getDateInput,
//  setEffectiveDate, clearDateInput, computeEffectiveDates, fNum,
//  fPctRatio, fBar são fornecidas por js/bi-dashboard.js (carregado
//  no <head> pelo template, antes deste script).
// ══════════════════════════════════════════════════════════════

const API_URL     = '<?= $this->site ?>rafaeltorres/dados';
const FORMS_URL   = '<?= $this->site ?>rafaeltorres/forms';
const GRAFICO_URL = '<?= $this->site ?>rafaeltorres/grafico';
const MONTHS      = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const YEARS       = ['2024', '2025', '2026'];

let currentModo   = 'anoMes';
// Últimos filtros aplicados via applyFilters()/clearFilters() — usados por
// setModo() para recarregar só o gráfico sem repetir os KPIs/funil/descarte.
let ultimosFiltros = { year: null, months: null, form: null, dataInicio: null, dataFim: null };

const yearDropdown  = createMultiDropdown('year-wrap',  'year-btn',  'year-list',  YEARS,  l => l, l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);
const formDropdown  = createSearchDropdown('form-wrap', 'form-input', 'form-dropdown', 'form-clear', 'form-tags', 'Nenhum form encontrado');

const NOW_YEAR  = String(new Date().getFullYear());
const NOW_MONTH = new Date().getMonth() + 1;
yearDropdown.select([NOW_YEAR]);
monthDropdown.select([String(NOW_MONTH)]);

// ── Campos de Data ──────────────────────────────────────────────
// Ano/Mês pintam o intervalo nos campos em modo exibição (.is-effective,
// que getDateInput ignora). Editar uma data pintada assim torna as duas
// filtro de verdade, com os valores que estão na tela, e solta o Ano/Mês;
// escolher Ano ou Mês de novo apaga as datas digitadas.
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
function setWidth(id, v) { const el = document.getElementById(id); if (el) el.style.width = v; }

// ── Modo do gráfico de período ─────────────────────────────────
// Troca só a granularidade do gráfico — não refaz KPIs/funil/descarte
// (que não dependem do modo), só busca /rafaeltorres/grafico.
function setModo(modo) {
  currentModo = modo;
  document.querySelectorAll('.gran-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.modo === modo);
  });
  loadGrafico(modo);
}

// ── Preencher KPIs ──────────────────────────────────────────────
function fillKpis(k) {
  set('k-agendadas',   fNum(k.consultas_agendadas));
  set('k-a-realizar',  fNum(k.consultas_a_realizar));
  set('k-realizadas',  fNum(k.consultas_realizadas));
  set('k-fp-realizado',fNum(k.fp_realizado));
  set('k-assinatura',  fNum(k.assinatura_pendente));
  set('k-combos',      fNum(k.combos_vendidos));
}

// ── Preencher Funil de Vendas ───────────────────────────────────
function fillFunil(funil) {
  if (!funil || !funil.length) return;
  const agendadas  = funil[0]?.valor || 0;
  const realizadas = funil[1]?.valor || 0;
  const combos     = funil[2]?.valor || 0;
  const L = agendadas || 1;

  set('f-agendadas', fNum(agendadas));
  set('f-cv-agendadas', '100%');
  setWidth('f-bar-agendadas', '100%');

  set('f-realizadas', fNum(realizadas));
  set('f-cv-realizadas', fPctRatio(realizadas, agendadas));
  setWidth('f-bar-realizadas', fBar(realizadas, L));

  set('f-combos', fNum(combos));
  set('f-cv-combos', fPctRatio(combos, realizadas));
  setWidth('f-bar-combos', fBar(combos, L));
}

// ── Preencher Motivos de Descarte (linhas geradas dinamicamente) ─
function fillDescarte(descarte) {
  const body = document.getElementById('descarte-body');
  if (!descarte || !descarte.length) {
    body.innerHTML = '<div style="padding:12px 0;color:var(--muted);font-size:12px;text-align:center">Sem descartes no período</div>';
    return;
  }
  const maxQtd   = Math.max(...descarte.map(d => d.qtd));
  const totalQtd = descarte.reduce((soma, d) => soma + d.qtd, 0);
  body.innerHTML = descarte.map(d => `
    <div class="funil-row">
      <span class="funil-row-label" style="width:90px" title="${d.motivo}">${d.motivo}</span>
      <div class="funil-bar-wrap"><div class="funil-bar" style="width:${fBar(d.qtd, maxQtd)};background:var(--red)"></div></div>
      <span class="funil-row-val">${fNum(d.qtd)}</span>
      <span class="descarte-pct">${fPctRatio(d.qtd, totalQtd)}</span>
    </div>
  `).join('');
}

// ── Preencher Tabela de Clientes (linhas individuais de dealV2) ─
function escHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// ── Colunas da tabela (ordenação por clique-no-cabeçalho) ────────
const COLS_CLIENTES = [
  { k: 'id_bitrix',               label: 'IdBitrix',                type: 'text', sortable: true },
  { k: 'nome_deal',               label: 'Nome Deal',               type: 'text', sortable: true },
  { k: 'email',                   label: 'Email',                   type: 'text', sortable: true },
  { k: 'data_consulta_agendada',  label: 'Data Consulta Agendada',  type: 'date', sortable: true },
  { k: 'data_consulta_realizada', label: 'Data Consulta Realizada', type: 'date', sortable: true },
  { k: 'data_fp_realizado',       label: 'Data Fp Realizado',       type: 'date', sortable: true },
  { k: 'data_assinatura_combo',   label: 'Data Assinatura Combo',   type: 'date', sortable: true },
  { k: 'link_bitrix',             label: 'Bitrix',                  type: 'text', sortable: false },
];

let ultimasLinhasClientes = [];
let _sortKeyClientes = null; // null = mantém a ordem original vinda do backend
let _sortDirClientes = 'asc';

function sortByClientes(key) {
  const col = COLS_CLIENTES.find(c => c.k === key);
  if (!col || !col.sortable) return;
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
    const active = c.k === _sortKeyClientes;
    const arrow  = c.sortable ? (active ? (_sortDirClientes === 'asc' ? ' ▲' : ' ▼') : '') : '';
    const cls    = [active ? 'th-active' : '', c.sortable ? '' : 'th-nosort'].filter(Boolean).join(' ');
    return `<th class="${cls}" title="${c.sortable ? 'Clique para ordenar' : ''}" tabindex="0" role="button" aria-label="${c.sortable ? 'Ordenar por ' + c.label : c.label}" onclick="sortByClientes('${c.k}')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();sortByClientes('${c.k}')}">${c.label}${arrow}</th>`;
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
// Retorna a tabela "como está na tela" (busca + ordenação atuais) — usado tanto
// pra renderizar quanto pro export (exporta exatamente o que está visível).
function linhasClientesVisiveis() {
  const termo = (document.getElementById('clientes-search').value || '').trim().toLowerCase();
  const base = termo
    ? ultimasLinhasClientes.filter(r =>
        (r.nome_deal || '').toLowerCase().includes(termo) ||
        (r.email || '').toLowerCase().includes(termo))
    : ultimasLinhasClientes;
  return { linhas: ordenarClientes(base), filtrado: !!termo };
}

function filtrarClientes() {
  const { linhas, filtrado } = linhasClientesVisiveis();
  renderLinhasClientes(linhas, filtrado);
}

createExportMenu('clientes-export-wrap', 'clientes-export-btn', 'clientes-export-list', fmt => {
  const { linhas } = linhasClientesVisiveis();
  const nome = 'clientes-rafaeltorres-' + new Date().toISOString().slice(0, 10);
  if (fmt === 'csv') exportRowsAsCsv(COLS_CLIENTES, linhas, nome);
  else               exportRowsAsExcel(COLS_CLIENTES, linhas, nome);
});

function renderLinhasClientes(linhas, filtrado = false) {
  const tbody = document.getElementById('tbody-clientes');
  const count = document.getElementById('clientes-count');
  if (!linhas || !linhas.length) {
    count.textContent = filtrado ? '0 clientes' : '';
    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--muted)">${filtrado ? 'Nenhum cliente encontrado para essa busca.' : 'Nenhum cliente encontrado no período.'}</td></tr>`;
    return;
  }
  const sufixo = (!filtrado && linhas.length >= 500) ? '+' : '';
  count.textContent = linhas.length + sufixo + ' clientes';
  tbody.innerHTML = linhas.map(r => `
    <tr>
      <td>${escHtml(r.id_bitrix ?? '—')}</td>
      <td>${escHtml(r.nome_deal ?? '—')}</td>
      <td>${escHtml(r.email ?? '—')}</td>
      <td>${escHtml(r.data_consulta_agendada ?? '—')}</td>
      <td>${escHtml(r.data_consulta_realizada ?? '—')}</td>
      <td>${escHtml(r.data_fp_realizado ?? '—')}</td>
      <td>${escHtml(r.data_assinatura_combo ?? '—')}</td>
      <td>${r.link_bitrix ? `<a class="bx-link" href="${escHtml(r.link_bitrix)}" target="_blank" rel="noopener">Abrir ↗</a>` : '—'}</td>
    </tr>
  `).join('');
}

// ── Gráfico SVG (combo: colunas + linha de conversão) ───────────
// Motor copiado/adaptado de paginas/executive/especialistas.php (buildChart) —
// mesma lib feita à mão, sem dependência externa; único ajuste é que aqui o
// rótulo de cada bucket já vem pronto do PHP conforme o modo (anoMes/dia_semana/dia_mes).
function buildChart(data) {
  if (!data || !data.length) {
    document.getElementById('chart-body').innerHTML =
      '<div style="height:280px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:12px">Sem dados para o período</div>';
    return;
  }

  const W = 860, H = 225;
  const pl = 48, pr = 58, pt = 24, pb = 36;
  const cW = W - pl - pr, cH = H - pt - pb;

  const n     = data.length;
  const nSafe = n || 1;
  const slot  = cW / nSafe;

  const bw  = slot > 20 ? Math.round(slot * 0.30) : slot > 8 ? Math.round(slot * 0.36) : Math.max(2, Math.round(slot * 0.42));
  const gap = Math.max(1, Math.round(bw * 0.18));

  const step     = nSafe <= 12 ? 1 : nSafe <= 24 ? 2 : nSafe <= 52 ? 4 : 8;
  const showNums = nSafe <= 20;
  const fs       = nSafe > 30 ? 7 : nSafe > 15 ? 8 : 9;

  const maxBar  = Math.max(1, ...data.map(d => Math.max(d.realizadas, d.combos))) * 1.18;
  const maxConv = Math.max(0.001, ...data.map(d => d.conversao)) * 1.20;

  const yBar  = v => pt + cH - Math.round((v / maxBar)  * cH);
  const yConv = v => pt + cH - Math.round((v / maxConv) * cH);

  const gridLines = [0, 0.25, 0.5, 0.75, 1].map(f => {
    const y  = pt + Math.round(cH * f);
    const vL = Math.round(maxBar  * (1 - f));
    const vR = ((maxConv * (1 - f)) * 100).toFixed(0) + '%';
    const isBase = f === 1;
    return (
      `<line x1="${pl}" y1="${y}" x2="${pl+cW}" y2="${y}" stroke="${isBase ? '#cbd5e1' : '#f1f5f9'}" stroke-width="1"/>` +
      `<text x="${pl-6}" y="${y+3}" text-anchor="end" font-size="9" fill="#94a3b8" class="num">${fNum(vL)}</text>` +
      `<text x="${pl+cW+6}" y="${y+3}" text-anchor="start" font-size="9" fill="#d97706" class="num">${vR}</text>`
    );
  }).join('');

  let bars = '', tips = '';
  const convPts = [];

  data.forEach((d, i) => {
    const cx  = Math.round(pl + slot * i + slot / 2);
    const xr  = cx - gap / 2 - bw;
    const xc  = cx + gap / 2;
    const hr  = Math.max(0, cH - (yBar(d.realizadas) - pt));
    const hc  = Math.max(0, cH - (yBar(d.combos)     - pt));
    const yr  = yBar(d.realizadas);
    const yc  = yBar(d.combos);
    const cv  = (d.conversao * 100).toFixed(1) + '%';
    const printLbl = (i % step === 0) || i === n - 1;

    const tipX = cx > pl + cW * 0.80 ? cx - 122 : cx - 4;

    bars +=
      `<g class="bar-slot" data-tip="${i}">` +
      `<rect x="${cx - Math.round(slot/2)}" y="${pt}" width="${Math.round(slot)}" height="${cH}" fill="transparent"/>` +
      `<rect x="${xr}" y="${yr}" width="${bw}" height="${hr}" fill="#3b82f6" rx="2"/>` +
      `<rect x="${xc}" y="${yc}" width="${bw}" height="${hc}" fill="#10b981" rx="2"/>` +
      (showNums && hr > 14 ? `<text x="${xr+Math.round(bw/2)}" y="${yr-4}" text-anchor="middle" font-size="8" fill="#1d4ed8" font-weight="600" class="num">${d.realizadas}</text>` : '') +
      (showNums && hc > 14 ? `<text x="${xc+Math.round(bw/2)}" y="${yc-4}" text-anchor="middle" font-size="8" fill="#047857" font-weight="600" class="num">${d.combos}</text>` : '') +
      (printLbl ? `<text x="${cx}" y="${H-8}" text-anchor="middle" font-size="${fs}" fill="#64748b" font-weight="500">${d.label}</text>` : '') +
      `</g>`;

    tips +=
      `<g class="tip" style="pointer-events:none" transform="translate(${tipX},${pt+2})">` +
        `<rect x="0" y="0" width="118" height="72" rx="8" fill="#0f172a" opacity=".96"/>` +
        `<text x="59" y="14" text-anchor="middle" font-size="9" fill="#94a3b8" font-weight="600">${d.label}</text>` +
        `<line x1="8" y1="19" x2="110" y2="19" stroke="rgba(255,255,255,0.08)" stroke-width="1"/>` +
        `<rect x="8" y="26" width="7" height="7" rx="2" fill="#3b82f6"/>` +
        `<text x="20" y="33" font-size="9" fill="#94a3b8">Realizadas</text>` +
        `<text x="110" y="33" text-anchor="end" font-size="9" fill="#60a5fa" font-weight="700" class="num">${fNum(d.realizadas)}</text>` +
        `<rect x="8" y="38" width="7" height="7" rx="2" fill="#10b981"/>` +
        `<text x="20" y="45" font-size="9" fill="#94a3b8">Combos</text>` +
        `<text x="110" y="45" text-anchor="end" font-size="9" fill="#34d399" font-weight="700" class="num">${fNum(d.combos)}</text>` +
        `<line x1="8" y1="51" x2="110" y2="51" stroke="rgba(255,255,255,0.06)" stroke-width="1"/>` +
        `<circle cx="12" cy="59" r="3.5" fill="#d97706"/>` +
        `<text x="20" y="63" font-size="9" fill="#94a3b8">Conversão</text>` +
        `<text x="110" y="63" text-anchor="end" font-size="9" fill="#fbbf24" font-weight="700" class="num">${cv}</text>` +
      `</g>`;

    convPts.push({ cx, cy: yConv(d.conversao), cv, tipIdx: i });
  });

  let lineConv = '', dotsConv = '';
  const pathD = convPts.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.cx},${p.cy}`).join(' ');
  lineConv = `<path d="${pathD}" fill="none" stroke="#d97706" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>`;

  convPts.forEach((p, i) => {
    const showLbl = showNums && n <= 36;
    dotsConv +=
      `<circle cx="${p.cx}" cy="${p.cy}" r="4" fill="#d97706" stroke="#fff" stroke-width="2"/>` +
      (showLbl ? `<text x="${p.cx}" y="${p.cy - 9}" text-anchor="middle" font-size="8" fill="#b45309" font-weight="700" class="num">${p.cv}</text>` : '');
  });

  const svg = `<svg width="100%" viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg" style="display:block;overflow:visible">
    ${gridLines}${bars}${lineConv}${dotsConv}
    <g id="tips-layer">${tips}</g>
  </svg>`;

  document.getElementById('chart-body').innerHTML = svg;

  const tipsEls = document.querySelectorAll('#tips-layer .tip');
  document.querySelectorAll('.bar-slot').forEach(slot => {
    const idx = slot.getAttribute('data-tip');
    slot.addEventListener('mouseenter', () => tipsEls[idx]?.classList.add('visible'));
    slot.addEventListener('mouseleave', () => tipsEls[idx]?.classList.remove('visible'));
  });
}

// ── Badge ────────────────────────────────────────────────────────
// Datas digitadas na etiqueta: '01/10/2026 – 15/10/2026', 'desde …' ou 'até …'.
function rotuloDatas(ini, fim) {
  const br = iso => iso.split('-').reverse().join('/');
  if (ini && fim) return br(ini) + ' – ' + br(fim);
  return ini ? 'desde ' + br(ini) : 'até ' + br(fim);
}

// datas = [início, fim] digitados à mão (um deles pode ser null), ou null.
function updateBadge(year, months, form, datas) {
  const badge = document.getElementById('filter-label-badge');
  if (!year && !months && !form && !datas) {
    badge.textContent = 'TODOS OS PERÍODOS';
    return;
  }
  const parts = [];
  if (form)   parts.push('Form: ' + form);
  if (datas)  parts.push(rotuloDatas(datas[0], datas[1]));
  if (year)   parts.push(year);
  if (months) {
    const labels = months.split(',').map(n => MONTHS[Number(n) - 1]);
    parts.push(labels.join(', '));
  }
  badge.textContent = parts.join(' · ').toUpperCase();
}

// ── Recarregar só o gráfico (troca de modo/granularidade) ───────
async function loadGrafico(modo) {
  const { year, months, form, dataInicio, dataFim } = ultimosFiltros;
  const params = new URLSearchParams();
  if (year)       params.set('year', year);
  if (months)     params.set('months', months);
  if (form)       params.set('form', form);
  if (modo)       params.set('modo', modo);
  if (dataInicio) params.append('data_inicio', dataInicio);
  if (dataFim)    params.append('data_fim', dataFim);

  document.getElementById('chart-body').innerHTML =
    '<div style="height:220px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:12px">Carregando gráfico...</div>';

  try {
    const res  = await fetch(GRAFICO_URL + '?' + params.toString(), { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (data.error) throw new Error(data.error);
    buildChart(data.grafico);
  } catch (err) {
    document.getElementById('chart-body').innerHTML =
      '<div style="height:220px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:12px">Erro ao carregar gráfico</div>';
    console.error(err);
  }
}

// ── Carregar dados ────────────────────────────────────────────────
async function loadDashboard(year, months, form, modo, dataInicio, dataFim) {
  ultimosFiltros = { year, months, form, dataInicio, dataFim };

  const overlay = document.getElementById('loading-overlay');
  const errBan  = document.getElementById('error-banner');
  overlay.classList.remove('hidden');
  document.body.classList.add('loading');
  errBan.style.display = 'none';
  errBan.classList.remove('visible');

  let url = API_URL;
  const params = new URLSearchParams();
  if (year)       params.set('year', year);
  if (months)     params.set('months', months);
  if (form)       params.set('form', form);
  if (modo)       params.set('modo', modo);
  if (dataInicio) params.append('data_inicio', dataInicio);
  if (dataFim)    params.append('data_fim', dataFim);
  if ([...params].length) url += '?' + params.toString();

  try {
    const res  = await fetch(url, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    fillKpis(data.kpis);
    buildChart(data.grafico);
    fillFunil(data.funil);
    fillDescarte(data.descarte);
    fillClientes(data.linhas);

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
  const form       = formDropdown.getValue();
  const dataInicio = getDateInput('data-inicio');
  const dataFim    = getDateInput('data-fim');
  if (!dataInicio && !dataFim) {
    const eff = computeEffectiveDates(year, months);
    setEffectiveDate('data-inicio', eff.start);
    setEffectiveDate('data-fim', eff.end);
  }
  const apiInicio = dataInicio || document.getElementById('data-inicio')?.value || null;
  const apiFim    = dataFim    || document.getElementById('data-fim')?.value    || null;
  updateBadge(year, months, form, dataInicio || dataFim ? [dataInicio, dataFim] : null);
  loadDashboard(year, months, form, currentModo, apiInicio, apiFim);
}

function clearFilters() {
  yearDropdown.reset();
  monthDropdown.reset();
  formDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim', 'data-fim-clear');
  currentModo = 'anoMes';
  document.querySelectorAll('.gran-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.modo === 'anoMes'));
  updateBadge(null, null, null);
  loadDashboard(null, null, null, currentModo, null, null);
}

applyFilters();
</script>
