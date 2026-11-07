<div class="dash-consultacom2">

<style>
.dash-consultacom2 #thead-consultas th {
  cursor: pointer;
  user-select: none;
}
.dash-consultacom2 #thead-consultas th:hover,
.dash-consultacom2 #thead-consultas th.th-active {
  background: var(--border);
  color: var(--text);
}
.dash-consultacom2 #thead-consultas th.th-nosort {
  cursor: default;
}
.dash-consultacom2 #thead-consultas th.th-nosort:hover {
  background: var(--card2);
  color: var(--muted);
}
.dash-consultacom2 .bx-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: var(--consulta);
  font-weight: 600;
  text-decoration: none;
  font-size: 12.5px;
}
.dash-consultacom2 .bx-link:hover {
  text-decoration: underline;
}
.dash-consultacom2 .st-badge {
  display: inline-block;
  padding: 2px 9px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 600;
  white-space: nowrap;
}
.dash-consultacom2 .st-ok       { color: var(--green); background: var(--green-glow); }
.dash-consultacom2 .st-pendente { color: var(--amber); background: var(--amber-glow); }
.dash-consultacom2 .st-ok-nome   { color: var(--consulta); background: var(--consulta-glow); }
.dash-consultacom2 .st-aviso    { color: var(--amber); font-weight: 600; }
/* Largura: com 9 colunas o table-layout automatico dava ~470px so pro e-mail
   e empurrava Fase Bitrix e o link pra fora da tela. Datas e rotulos em uma
   linha so, e nome/e-mail cortados com reticencias (valor inteiro no title). */
.dash-consultacom2 .tbl-wrap { overflow-x: auto; }
.dash-consultacom2 .bi-table td { white-space: nowrap; }
/* Cabecalho pode quebrar em duas linhas: o nowrap global do .bi-table th fazia
   "CONSULTA AGENDADA" sozinho reservar ~200px, mais que a data que ele rotula. */
.dash-consultacom2 .bi-table th { white-space: normal; }
.dash-consultacom2 .bi-table td.cell-clip {
  max-width: 230px;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>

<!-- ── LOADING ─────────────────────────────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>

<!-- ── HEADER ─────────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo">C2</div>
    <div>
      <div class="header-title">ConsultaCom2</div>
      <div class="header-sub">Consultas do time Comercial 2 &middot; Rafael Torres, Beatriz Lemos, Camila Duarte, Juliana Prado</div>
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
  <div class="header-badge" id="filter-label-badge">MÊS ATUAL</div>
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

  <!-- Vendedor -->
  <div class="filter-pair">
    <span class="filter-label">Vendedor</span>
    <div class="multi-dd-wrap" id="comercial-wrap">
      <button class="multi-dd-btn" id="comercial-btn" type="button">Todos <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="comercial-list"></div>
    </div>
  </div>

  <button class="btn-apply" onclick="applyFilters()">Aplicar</button>
  <button class="btn-clear" onclick="clearFilters()">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'consultacom2' });</script>

<!-- ── ERRO ───────────────────────────────────────────────────── -->
<div id="error-banner" role="alert"></div>

<!-- ── CONTEÚDO PRINCIPAL ──────────────────────────────────────── -->
<main class="main" id="dashboard-main">

  <div class="bi-card">
    <div class="bi-card-header" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">
      <span class="bi-card-title">Consultas</span>
      <div style="display:flex;align-items:center;gap:10px">
        <span class="bi-card-sub st-aviso" id="planilha-aviso" style="display:none">&#9888; planilha indispon&iacute;vel &mdash; status n&atilde;o calculado</span>
        <select
          id="consultas-status-filter"
          onchange="filtrarConsultas()"
          style="height:30px;padding:0 8px;border-radius:var(--r-sm,6px);border:1px solid var(--border);background:var(--card2);color:var(--text);font-family:var(--font-ui);font-size:12.5px;outline:none"
        >
          <option value="">Status: todos</option>
          <option value="OK">OK</option>
          <option value="OK (nome)">OK (nome)</option>
          <option value="Pendente">Pendente</option>
        </select>
        <input
          type="text"
          id="consultas-search"
          placeholder="Buscar em qualquer coluna..."
          autocomplete="off"
          oninput="filtrarConsultas()"
          style="height:30px;padding:0 10px;border-radius:var(--r-sm,6px);border:1px solid var(--border);background:var(--card2);color:var(--text);font-family:var(--font-ui);font-size:12.5px;outline:none;min-width:240px"
        >
        <div class="exp-menu-wrap" id="consultas-export-wrap">
          <button class="exp-menu-btn" id="consultas-export-btn" type="button">Exportar &#9662;</button>
          <div class="exp-menu-list" id="consultas-export-list">
            <div class="exp-menu-item" data-fmt="csv">Exportar CSV</div>
            <div class="exp-menu-item" data-fmt="excel">Exportar Excel</div>
          </div>
        </div>
        <span class="bi-card-sub" id="consultas-count"></span>
      </div>
    </div>
    <div class="tbl-wrap" style="max-height:640px;overflow:auto">
      <table class="bi-table">
        <thead id="thead-consultas"></thead>
        <tbody id="tbody-consultas">
          <tr><td colspan="10" style="text-align:center;padding:32px;color:var(--muted)">Carregando…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="last-updated" id="last-updated"></div>

</main>
</div>

<script>
// ══════════════════════════════════════════════════════════════
//  ConsultaCom2 — Dashboard JS
//  Consome: consultacom2/dados (controller MRPHP)
//
//  createMultiDropdown, fNum são fornecidas por js/bi-dashboard.js
//  (carregado no <head> pelo template, antes deste script).
// ══════════════════════════════════════════════════════════════

const API_URL = '<?= $this->site ?>consultacom2/dados';
const MONTHS  = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const YEARS   = ['2024', '2025', '2026'];
const EQUIPE  = ['Rafael Torres', 'Beatriz Lemos', 'Camila Duarte', 'Juliana Prado', 'Gustavo Leal'];

const yearDropdown      = createMultiDropdown('year-wrap',      'year-btn',      'year-list',      YEARS,  l => l, l => l);
const monthDropdown     = createMultiDropdown('month-wrap',     'month-btn',     'month-list',     MONTHS, l => l, (l, i) => i + 1);
const comercialDropdown = createMultiDropdown('comercial-wrap', 'comercial-btn', 'comercial-list', EQUIPE, l => l, l => l);

const NOW_YEAR  = String(new Date().getFullYear());
const NOW_MONTH = new Date().getMonth() + 1;
yearDropdown.select([NOW_YEAR]);
monthDropdown.select([String(NOW_MONTH)]);

// Tres estados: OK (casou pelo e-mail), OK (nome) (fallback — o e-mail do CRM
// diverge do da planilha, normalmente digitacao errada) e Pendente. Status null
// = planilha indisponivel, ai mostra "—" em vez de "Pendente", que seria falso.
function statusBadge(v, detalhe) {
  if (!v) return '—';
  const cls = v === 'OK' ? 'st-ok' : (v === 'Pendente' ? 'st-pendente' : 'st-ok-nome');
  const tip = detalhe ? ` title="${escHtml(detalhe)}"` : '';
  return `<span class="st-badge ${cls}"${tip}>${escHtml(v)}</span>`;
}

function escHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// ── Colunas da tabela (ordenação por clique-no-cabeçalho) ────────
const COLS_CONSULTAS = [
  { k: 'nome_card',               label: 'Nome do Card',       type: 'text', sortable: true },
  { k: 'email',                   label: 'Email',              type: 'text', sortable: true },
  { k: 'status',                  label: 'Status',             type: 'text', sortable: true },
  { k: 'comercial2',              label: 'Comercial 2',        type: 'text', sortable: true },
  { k: 'data_consulta_realizada', label: 'Consulta Realizada', type: 'date', sortable: true },
  { k: 'data_consulta_agendada',  label: 'Consulta Agendada',  type: 'date', sortable: true },
  { k: 'data_contrato_assinado',  label: 'Contrato Assinado',  type: 'date', sortable: true },
  { k: 'data_consulta_planilha',  label: 'Consulta (Planilha)', type: 'text', sortable: true },
  { k: 'fase_bitrix',             label: 'Fase Bitrix',        type: 'text', sortable: true },
  { k: 'link_bitrix',             label: 'Bitrix',             type: 'text', sortable: false },
];

let ultimasLinhasConsultas = [];
let _sortKeyConsultas = null;
let _sortDirConsultas = 'asc';

function sortByConsultas(key) {
  const col = COLS_CONSULTAS.find(c => c.k === key);
  if (!col || !col.sortable) return;
  if (_sortKeyConsultas === key) {
    _sortDirConsultas = _sortDirConsultas === 'asc' ? 'desc' : 'asc';
  } else {
    _sortKeyConsultas = key;
    _sortDirConsultas = 'asc';
  }
  renderHeadConsultas();
  filtrarConsultas();
}

// dd/mm/yyyy -> yyyymmdd (string) para comparar; vazio/— sempre vai pro final
function sortValueConsultas(v, type) {
  if (v === null || v === undefined || v === '') return null;
  if (type === 'date') {
    const [d, m, y] = String(v).split('/');
    return y + m.padStart(2, '0') + d.padStart(2, '0');
  }
  return String(v).toLowerCase();
}

function ordenarConsultas(linhas) {
  if (!_sortKeyConsultas) return linhas;
  const col = COLS_CONSULTAS.find(c => c.k === _sortKeyConsultas);
  const dir = _sortDirConsultas === 'asc' ? 1 : -1;
  return linhas.slice().sort((a, b) => {
    const av = sortValueConsultas(a[_sortKeyConsultas], col.type);
    const bv = sortValueConsultas(b[_sortKeyConsultas], col.type);
    if (av === null && bv === null) return 0;
    if (av === null) return 1;
    if (bv === null) return -1;
    if (av < bv) return -1 * dir;
    if (av > bv) return 1 * dir;
    return 0;
  });
}

function renderHeadConsultas() {
  document.getElementById('thead-consultas').innerHTML = '<tr>' + COLS_CONSULTAS.map(c => {
    const active = c.k === _sortKeyConsultas;
    const arrow  = c.sortable ? (active ? (_sortDirConsultas === 'asc' ? ' ▲' : ' ▼') : ' ▲▼') : '';
    const cls    = [active ? 'th-active' : '', c.sortable ? '' : 'th-nosort'].filter(Boolean).join(' ');
    return `<th class="${cls}" title="${c.sortable ? 'Clique para ordenar' : ''}" tabindex="0" role="button" aria-label="${c.sortable ? 'Ordenar por ' + c.label : c.label}" onclick="sortByConsultas('${c.k}')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();sortByConsultas('${c.k}')}">${c.label}${arrow}</th>`;
  }).join('') + '</tr>';
}
renderHeadConsultas();

function fillConsultas(linhas) {
  ultimasLinhasConsultas = linhas || [];
  const searchInput = document.getElementById('consultas-search');
  if (searchInput) searchInput.value = '';
  const statusFiltro = document.getElementById('consultas-status-filter');
  if (statusFiltro) statusFiltro.value = '';
  renderLinhasConsultas(ordenarConsultas(ultimasLinhasConsultas));
}

// ── Busca client-side em todas as colunas + filtro de status (as linhas já vêm
// carregadas do período) — retorna a tabela "como está na tela" (busca + status
// + ordenação atuais), usado tanto pra renderizar quanto pro export.
function linhasConsultasVisiveis() {
  const termo  = (document.getElementById('consultas-search').value || '').trim().toLowerCase();
  const status = document.getElementById('consultas-status-filter').value;
  let base = status
    ? ultimasLinhasConsultas.filter(r => (r.status || '') === status)
    : ultimasLinhasConsultas;
  if (termo) {
    base = base.filter(r => [
      r.nome_card, r.email, r.status, r.status_detalhe, r.comercial2, r.fase_bitrix,
      r.data_consulta_realizada, r.data_consulta_agendada, r.data_contrato_assinado,
      r.data_consulta_planilha, r.id_bitrix,
    ].filter(Boolean).join(' ').toLowerCase().includes(termo));
  }
  return { linhas: ordenarConsultas(base), filtrado: !!termo || !!status };
}

function filtrarConsultas() {
  const { linhas, filtrado } = linhasConsultasVisiveis();
  renderLinhasConsultas(linhas, filtrado);
}

createExportMenu('consultas-export-wrap', 'consultas-export-btn', 'consultas-export-list', fmt => {
  const { linhas } = linhasConsultasVisiveis();
  const nome = 'consultas-consultacom2-' + new Date().toISOString().slice(0, 10);
  if (fmt === 'csv') exportRowsAsCsv(COLS_CONSULTAS, linhas, nome);
  else               exportRowsAsExcel(COLS_CONSULTAS, linhas, nome);
});

function renderLinhasConsultas(linhas, filtrado = false) {
  const tbody = document.getElementById('tbody-consultas');
  const count = document.getElementById('consultas-count');
  if (!linhas || !linhas.length) {
    count.textContent = filtrado ? '0 deals' : '';
    tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;padding:32px;color:var(--muted)">${filtrado ? 'Nenhum deal encontrado para essa busca.' : 'Nenhum deal encontrado no período.'}</td></tr>`;
    return;
  }
  const sufixo = (!filtrado && linhas.length >= 1000) ? '+' : '';
  count.textContent = linhas.length + sufixo + ' deals';
  tbody.innerHTML = linhas.map(r => `
    <tr>
      <td class="cell-clip" title="${escHtml(r.nome_card ?? '')}">${escHtml(r.nome_card ?? '—')}</td>
      <td class="cell-clip" title="${escHtml(r.email ?? '')}">${escHtml(r.email ?? '—')}</td>
      <td>${statusBadge(r.status, r.status_detalhe)}</td>
      <td>${escHtml(r.comercial2 ?? '—')}</td>
      <td>${escHtml(r.data_consulta_realizada ?? '—')}</td>
      <td>${escHtml(r.data_consulta_agendada ?? '—')}</td>
      <td>${escHtml(r.data_contrato_assinado ?? '—')}</td>
      <td>${escHtml(r.data_consulta_planilha ?? '—')}</td>
      <td>${escHtml(r.fase_bitrix ?? '—')}</td>
      <td>${r.link_bitrix ? `<a class="bx-link" href="${escHtml(r.link_bitrix)}" target="_blank" rel="noopener">Abrir ↗</a>` : '—'}</td>
    </tr>
  `).join('');
}

// ── Badge ────────────────────────────────────────────────────────
function updateBadge(year, months, comercial) {
  const badge = document.getElementById('filter-label-badge');
  if (!year && !months && !comercial) {
    badge.textContent = 'TODOS OS PERÍODOS';
    return;
  }
  const parts = [];
  if (year)   parts.push(year);
  if (months) {
    const labels = months.split(',').map(n => MONTHS[Number(n) - 1]);
    parts.push(labels.join(', '));
  }
  if (comercial) parts.push(comercial);
  badge.textContent = parts.join(' · ').toUpperCase();
}

// ── Carregar dados ────────────────────────────────────────────────
async function loadDashboard(year, months, comercial) {
  const overlay = document.getElementById('loading-overlay');
  const errBan  = document.getElementById('error-banner');
  overlay.classList.remove('hidden');
  document.body.classList.add('loading');
  errBan.style.display = 'none';
  errBan.classList.remove('visible');

  let url = API_URL;
  const params = new URLSearchParams();
  if (year)      params.set('year', year);
  if (months)    params.set('months', months);
  if (comercial) params.set('comercial', comercial);
  if ([...params].length) url += '?' + params.toString();

  try {
    const res  = await fetch(url, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    document.getElementById('planilha-aviso').style.display =
      data.planilha_indisponivel ? 'inline' : 'none';

    fillConsultas(data.linhas);

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
  const year      = yearDropdown.getValue();
  const months    = monthDropdown.getValue();
  const comercial = comercialDropdown.getValue();
  updateBadge(year, months, comercial);
  loadDashboard(year, months, comercial);
}

function clearFilters() {
  yearDropdown.reset();
  monthDropdown.reset();
  comercialDropdown.reset();
  yearDropdown.select([NOW_YEAR]);
  monthDropdown.select([String(NOW_MONTH)]);
  updateBadge(NOW_YEAR, String(NOW_MONTH), null);
  loadDashboard(NOW_YEAR, String(NOW_MONTH), null);
}

applyFilters();
</script>
