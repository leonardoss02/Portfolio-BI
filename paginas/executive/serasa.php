<div class="dash-serasa">
<style>
  /* Únicos componentes novos desta aba — o resto reaproveita as classes
     globais de css/bi-theme.css (.page, .header, .filter-bar, .bi-card,
     .kpi-row/.kpi-chip, .bi-table), sem duplicar CSS de outra aba. */
  .dash-serasa .pipe-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  @media (max-width: 1100px) { .dash-serasa .pipe-grid { grid-template-columns: 1fr; } }
  .dash-serasa .pipe-card.pipe-com2 { border-top: 3px solid var(--consulta); }
  .dash-serasa .pipe-card.pipe-com4 { border-top: 3px solid var(--accent2); }
  .dash-serasa .pipe-badge {
    font-size: 10px; font-weight: 700; color: var(--muted); background: var(--card2);
    border: 1px solid var(--border); border-radius: var(--r-sm); padding: 2px 7px;
    margin-left: 8px; vertical-align: middle;
  }
  .dash-serasa .mini-section { margin-top: 14px; }
  .dash-serasa .mini-title {
    font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase;
    letter-spacing: .04em; margin-bottom: 6px;
  }
  .dash-serasa .tbl-wrap { max-height: 480px; overflow: auto; }
  .dash-serasa .kpi-row + .kpi-row { margin-top: 8px; }
  .dash-serasa #serasa-search:focus { border-color: var(--consulta); }
  .dash-serasa #thead-serasa th[role="button"] { cursor: pointer; }
  .dash-serasa #thead-serasa th[role="button"]:hover,
  .dash-serasa #thead-serasa th[role="button"]:focus-visible { background: var(--card2); }
  .dash-serasa #thead-serasa th.th-active { color: var(--consulta); font-weight: 800; }
  .dash-serasa .mini-table { border: 1px solid var(--border); border-radius: var(--r-sm); overflow: hidden; }
  .dash-serasa .mini-table thead th { background: var(--card2); border-bottom: 1px solid var(--border); }
  .dash-serasa .mini-table td { border-bottom: 1px solid var(--border); }
  .dash-serasa .mini-table tr:last-child td { border-bottom: none; }
  .dash-serasa .td-risco-bg { background: var(--danger-glow); color: var(--danger); font-weight: 600; border-radius: var(--r-sm); }
  /* .bi-table tbody td { color: var(--text) } (bi-theme.css) tem mais
     especificidade que .td-green/.td-amber/.td-danger sozinhas e as
     sobrescrevia nesta tabela — reforça só aqui via #tbody-serasa. */
  .dash-serasa #tbody-serasa td.td-green  { color: var(--green); }
  .dash-serasa #tbody-serasa td.td-amber  { color: var(--amber); }
  .dash-serasa #tbody-serasa td.td-danger { color: var(--danger); }
  .dash-serasa #tbody-serasa tr:nth-child(even) { background: var(--card2); }

  /* Dash mais denso que os demais (2 pipes lado a lado, cada um com
     2 linhas de KPI + 2 mini-tabelas, mais uma tabela de 15 colunas) —
     reduz só aqui os tamanhos herdados das classes globais de
     bi-theme.css, sem tocar no design system compartilhado. */
  .dash-serasa .header-title { font-size: 13px; }
  .dash-serasa .header-sub { font-size: 10px; }
  .dash-serasa .logo { width: 32px; height: 32px; font-size: 12px; }

  .dash-serasa .bi-card-header { padding: 11px 14px 8px; }
  .dash-serasa .bi-card-title { font-size: 11.5px; }
  .dash-serasa .bi-card-sub { font-size: 10px; }
  .dash-serasa .bi-card-body { padding: 12px 14px; }

  .dash-serasa .kpi-chip { padding: 8px 12px; min-width: 90px; gap: 3px; }
  .dash-serasa .kpi-chip-val { font-size: 17px; }
  .dash-serasa .kpi-chip-label { font-size: 9.5px; }

  .dash-serasa .bi-table,
  .dash-serasa .mini-table { font-size: 12px; }
  .dash-serasa .bi-table thead th,
  .dash-serasa .mini-table thead th { font-size: 9px; padding: 7px 10px; }
  .dash-serasa .bi-table tbody td,
  .dash-serasa .mini-table td { padding: 6px 10px; }

  .dash-serasa .mini-title { font-size: 10px; }
</style>

<div id="loading-overlay" class="hidden"></div>

<div class="page">

  <!-- HEADER -->
  <div class="header">
    <div class="header-left">
      <div class="logo">MR</div>
      <div>
        <div class="header-title">Serasa — Análise de Crédito</div>
        <div class="header-sub">Consulta Serasa · Comercial 2 (pipe C5) · Comercial 4 (pipe C3)</div>
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
      <div class="last-updated" id="last-updated">—</div>
      <div class="tab-switch">
        <a class="tab-switch-btn" href="<?= $this->site ?>executive<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Executive</a>
        <a class="tab-switch-btn" href="<?= $this->site ?>executive/digital<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Digital &times; Evento</a>
        <a class="tab-switch-btn" href="<?= $this->site ?>executive/especialistas<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Especialistas</a>
        <a class="tab-switch-btn" href="<?= $this->site ?>executive/analitico<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Analítico</a>
        <a class="tab-switch-btn active" href="<?= $this->site ?>executive/serasa<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Serasa</a>
        <a class="tab-switch-btn" href="<?= $this->site ?>executive/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
      </div>
    </div>
  </div>

  <!-- FILTROS -->
  <div class="filter-bar is-collapsible">
    <button class="filters-toggle" type="button" aria-expanded="true" aria-controls="filters-fields">
      <span>Filtros</span>
      <span class="dd-caret filters-toggle-caret">&#x25BE;</span>
    </button>
    <div class="filters-fields" id="filters-fields">
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

    <button class="btn-apply" onclick="applyFilters()">Aplicar</button>
    <button class="btn-clear"  onclick="clearFilters()">Limpar</button>
    </div>
  </div>
  <script>initFiltersToggle({ key: 'executive-serasa' });</script>

  <div id="error-banner" role="alert"></div>

  <!-- VISÃO GERAL -->
  <div class="bi-card">
    <div class="bi-card-header">
      <span class="bi-card-title">Visão Geral</span>
      <span class="bi-card-sub">Comercial 2 + Comercial 4 combinados</span>
    </div>
    <div class="bi-card-body">
      <div class="kpi-row">
        <div class="kpi-chip consulta">
          <div class="kpi-chip-label">Cards Consultados no Serasa</div>
          <div class="kpi-chip-val" id="kpi-total-consultado">—</div>
        </div>
        <div class="kpi-chip risco">
          <div class="kpi-chip-label">Nome Sujo</div>
          <div class="kpi-chip-val" id="kpi-nome-sujo-pct">—</div>
        </div>
        <div class="kpi-chip risco">
          <div class="kpi-chip-label">Sócio Negativado</div>
          <div class="kpi-chip-val" id="kpi-socio-neg-pct">—</div>
        </div>
        <div class="kpi-chip sinal">
          <div class="kpi-chip-label">Dívida Total (USD)</div>
          <div class="kpi-chip-val" id="kpi-divida-total">—</div>
        </div>
      </div>
    </div>
  </div>

  <!-- COMERCIAL 2 x COMERCIAL 4 -->
  <div class="pipe-grid">

    <div class="bi-card pipe-card pipe-com2">
      <div class="bi-card-header">
        <span class="bi-card-title">Comercial 2 <span class="pipe-badge">Pipe C5</span></span>
        <span class="bi-card-sub">Cobertura: <span id="com2-cobertura">—</span></span>
      </div>
      <div class="bi-card-body">
        <div class="kpi-row">
          <div class="kpi-chip">
            <div class="kpi-chip-label">Total no Pipe</div>
            <div class="kpi-chip-val" id="com2-total">—</div>
          </div>
          <div class="kpi-chip consulta">
            <div class="kpi-chip-label">Consultados</div>
            <div class="kpi-chip-val" id="com2-consultados">—</div>
          </div>
          <div class="kpi-chip risco">
            <div class="kpi-chip-label">Nome Sujo</div>
            <div class="kpi-chip-val" id="com2-sujo">—</div>
          </div>
          <div class="kpi-chip risco">
            <div class="kpi-chip-label">Sócio Negativado</div>
            <div class="kpi-chip-val" id="com2-socio">—</div>
          </div>
        </div>
        <div class="kpi-row">
          <div class="kpi-chip">
            <div class="kpi-chip-label">Score Médio</div>
            <div class="kpi-chip-val" id="com2-score">—</div>
          </div>
          <div class="kpi-chip sinal">
            <div class="kpi-chip-label">Dívidas (USD)</div>
            <div class="kpi-chip-val" id="com2-dividas">—</div>
          </div>
          <div class="kpi-chip sinal">
            <div class="kpi-chip-label">Cartório (USD)</div>
            <div class="kpi-chip-val" id="com2-cartorio">—</div>
          </div>
        </div>

        <div class="mini-section">
          <div class="mini-title">Distribuição por Faixa (A → G)</div>
          <table class="mini-table">
            <thead><tr><th>Faixa</th><th class="num">Qtd</th><th class="num">%</th></tr></thead>
            <tbody id="range-com2-tbody"><tr><td colspan="3" style="text-align:center;color:var(--muted)">Carregando…</td></tr></tbody>
          </table>
        </div>

        <div class="mini-section">
          <div class="mini-title">Distribuição por Status CPF</div>
          <table class="mini-table">
            <thead><tr><th>Status</th><th class="num">Qtd</th></tr></thead>
            <tbody id="status-com2-tbody"><tr><td colspan="2" style="text-align:center;color:var(--muted)">Carregando…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="bi-card pipe-card pipe-com4">
      <div class="bi-card-header">
        <span class="bi-card-title">Comercial 4 <span class="pipe-badge">Pipe C3</span></span>
        <span class="bi-card-sub">Cobertura: <span id="com4-cobertura">—</span></span>
      </div>
      <div class="bi-card-body">
        <div class="kpi-row">
          <div class="kpi-chip">
            <div class="kpi-chip-label">Total no Pipe</div>
            <div class="kpi-chip-val" id="com4-total">—</div>
          </div>
          <div class="kpi-chip consulta">
            <div class="kpi-chip-label">Consultados</div>
            <div class="kpi-chip-val" id="com4-consultados">—</div>
          </div>
          <div class="kpi-chip risco">
            <div class="kpi-chip-label">Nome Sujo</div>
            <div class="kpi-chip-val" id="com4-sujo">—</div>
          </div>
          <div class="kpi-chip risco">
            <div class="kpi-chip-label">Sócio Negativado</div>
            <div class="kpi-chip-val" id="com4-socio">—</div>
          </div>
        </div>
        <div class="kpi-row">
          <div class="kpi-chip">
            <div class="kpi-chip-label">Score Médio</div>
            <div class="kpi-chip-val" id="com4-score">—</div>
          </div>
          <div class="kpi-chip sinal">
            <div class="kpi-chip-label">Dívidas (USD)</div>
            <div class="kpi-chip-val" id="com4-dividas">—</div>
          </div>
          <div class="kpi-chip sinal">
            <div class="kpi-chip-label">Cartório (USD)</div>
            <div class="kpi-chip-val" id="com4-cartorio">—</div>
          </div>
        </div>

        <div class="mini-section">
          <div class="mini-title">Distribuição por Faixa (A → G)</div>
          <table class="mini-table">
            <thead><tr><th>Faixa</th><th class="num">Qtd</th><th class="num">%</th></tr></thead>
            <tbody id="range-com4-tbody"><tr><td colspan="3" style="text-align:center;color:var(--muted)">Carregando…</td></tr></tbody>
          </table>
        </div>

        <div class="mini-section">
          <div class="mini-title">Distribuição por Status CPF</div>
          <table class="mini-table">
            <thead><tr><th>Status</th><th class="num">Qtd</th></tr></thead>
            <tbody id="status-com4-tbody"><tr><td colspan="2" style="text-align:center;color:var(--muted)">Carregando…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

  </div><!-- /pipe-grid -->

  <!-- TABELA DE CLIENTES -->
  <div class="bi-card">
    <div class="bi-card-header" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">
      <span class="bi-card-title">Clientes com Consulta Serasa</span>
      <div style="display:flex;align-items:center;gap:10px">
        <input
          type="text"
          id="serasa-search"
          placeholder="Buscar por nome..."
          autocomplete="off"
          oninput="filtrarSerasa()"
          style="height:30px;padding:0 10px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--card2);color:var(--text);font-family:inherit;font-size:12.5px;outline:none;min-width:220px;transition:border-color .15s"
        >
        <span class="bi-card-sub" id="serasa-count"></span>
      </div>
    </div>
    <div class="tbl-wrap">
      <table class="bi-table">
        <thead id="thead-serasa"></thead>
        <tbody id="tbody-serasa">
          <tr><td colspan="15" style="text-align:center;padding:32px;color:var(--muted)">Carregando…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /page -->

<script>
// ══════════════════════════════════════════════════════════════
//  Serasa — Dashboard JS
//  Consome: executive/serasaDados (controller MRPHP)
//  Depende de funções compartilhadas em js/bi-dashboard.js:
//  createMultiDropdown, getDateInput, clearDateInput, fBRL, fNum, fPctRatio
// ══════════════════════════════════════════════════════════════

const API    = '<?= $this->site ?>executive/serasaDados';
const MONTHS = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const YEARS  = ['2024','2025','2026'];

const yearDropdown  = createMultiDropdown('year-wrap',  'year-btn',  'year-list',  YEARS,  l => l, l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);

// Só o ano vem pré-selecionado — diferente das outras abas (que também
// travam no mês atual): aqui o interesse é visibilidade de consumo
// acumulado de um recurso limitado, não "o que aconteceu este mês".
yearDropdown.select([String(new Date().getFullYear())]);

function set(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }
function escHtml(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

function rangeCellClass(faixa) {
  const f = String(faixa || '').toUpperCase();
  if (f === 'A' || f === 'B') return 'td-green';
  if (f === 'F' || f === 'G') return 'td-danger';
  return f ? 'td-amber' : '';
}
function simCellClass(v) { return String(v).toLowerCase() === 'sim' ? 'td-risco-bg' : ''; }

// ── Visão Geral + cards por pipe ────────────────────────────────
function fillResumoTop(combinado) {
  set('kpi-total-consultado', fNum(combinado.consultas_realizadas));
  set('kpi-nome-sujo-pct',    fPctRatio(combinado.nome_sujo_qtd, combinado.consultas_realizadas));
  set('kpi-socio-neg-pct',    fPctRatio(combinado.socio_negativado_qtd, combinado.consultas_realizadas));
  set('kpi-divida-total',     fBRL(combinado.valor_total_dividas_usd));
}

function fillPipeCard(prefix, r) {
  set(`${prefix}-total`,       fNum(r.total_pipeline));
  set(`${prefix}-consultados`, fNum(r.consultas_realizadas));
  set(`${prefix}-cobertura`,   fPctRatio(r.consultas_realizadas, r.total_pipeline));
  set(`${prefix}-sujo`,        `${fNum(r.nome_sujo_qtd)} (${fPctRatio(r.nome_sujo_qtd, r.consultas_realizadas)})`);
  set(`${prefix}-socio`,       `${fNum(r.socio_negativado_qtd)} (${fPctRatio(r.socio_negativado_qtd, r.consultas_realizadas)})`);
  set(`${prefix}-score`,       fNum(r.score_medio));
  set(`${prefix}-dividas`,     fBRL(r.valor_total_dividas_usd));
  set(`${prefix}-cartorio`,    fBRL(r.valor_cartorio_total_usd));
}

function renderRangeTable(prefix, rows) {
  const tbody = document.getElementById(`range-${prefix}-tbody`);
  if (!rows || !rows.length) {
    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:var(--muted)">Sem consultas no período</td></tr>';
    return;
  }
  const total = rows.reduce((s, r) => s + r.qtd, 0);
  tbody.innerHTML = rows.map((r, i) => `
    <tr class="${i % 2 === 0 ? 'row-odd' : 'row-even'}">
      <td class="${rangeCellClass(r.faixa)}">${escHtml(r.faixa)}</td>
      <td class="num">${fNum(r.qtd)}</td>
      <td class="num">${fPctRatio(r.qtd, total)}</td>
    </tr>
  `).join('');
}

function renderStatusTable(prefix, rows) {
  const tbody = document.getElementById(`status-${prefix}-tbody`);
  if (!rows || !rows.length) {
    tbody.innerHTML = '<tr><td colspan="2" style="text-align:center;color:var(--muted)">Sem consultas no período</td></tr>';
    return;
  }
  tbody.innerHTML = rows.map((r, i) => `
    <tr class="${i % 2 === 0 ? 'row-odd' : 'row-even'}">
      <td>${escHtml(r.status)}</td>
      <td class="num">${fNum(r.qtd)}</td>
    </tr>
  `).join('');
}

// ── Tabela de clientes (ordenação por clique-no-cabeçalho + busca) ─
// Mesmo padrão de paginas/rafaeltorres/index.php (COLS_CLIENTES/renderHeadClientes/
// sortByClientes/filtrarClientes/renderLinhasClientes), com um tipo 'number' a
// mais no comparador para as colunas de score/valores USD.
const COLS_SERASA = [
  { k: 'id_bitrix',                 label: 'IdBitrix',          type: 'text'   },
  { k: 'nome',                      label: 'Nome',              type: 'text'   },
  { k: 'pipe',                      label: 'Pipe',              type: 'text'   },
  { k: 'fase',                      label: 'Fase',              type: 'text'   },
  { k: 'fase_id',                   label: 'Fase ID',           type: 'text'   },
  { k: 'data_fp_realizado',         label: 'Data FP',           type: 'date'   },
  { k: 'data_combo_assinado',       label: 'Data Combo',        type: 'date'   },
  { k: 'serasa_range',              label: 'Faixa',             type: 'text'   },
  { k: 'serasa_score',              label: 'Score',             type: 'number' },
  { k: 'status_cpf',                label: 'Status CPF',        type: 'text'   },
  { k: 'nome_sujo',                 label: 'Nome Sujo',         type: 'text'   },
  { k: 'socio_negativado',          label: 'Sócio Negativado',  type: 'text'   },
  { k: 'valor_total_dividas_usd',   label: 'Dívidas (USD)',     type: 'number' },
  { k: 'valor_cartorio_total_usd',  label: 'Cartório (USD)',    type: 'number' },
  { k: 'responsavel',               label: 'Responsável',       type: 'text'   },
];

let ultimasLinhasSerasa = [];
let _sortKeySerasa = null;
let _sortDirSerasa = 'asc';

function sortBySerasa(key) {
  if (_sortKeySerasa === key) {
    _sortDirSerasa = _sortDirSerasa === 'asc' ? 'desc' : 'asc';
  } else {
    _sortKeySerasa = key;
    _sortDirSerasa = 'asc';
  }
  renderHeadSerasa();
  filtrarSerasa();
}

// dd/mm/yyyy -> yyyymmdd (string) para comparar; vazio/— sempre vai pro final
function sortValueSerasa(v, type) {
  if (v === null || v === undefined || v === '') return null;
  if (type === 'date') {
    const [d, m, y] = String(v).split('/');
    return y + m.padStart(2, '0') + d.padStart(2, '0');
  }
  if (type === 'number') return Number(v);
  return String(v).toLowerCase();
}

function ordenarSerasa(linhas) {
  if (!_sortKeySerasa) return linhas;
  const col = COLS_SERASA.find(c => c.k === _sortKeySerasa);
  const dir = _sortDirSerasa === 'asc' ? 1 : -1;
  return linhas.slice().sort((a, b) => {
    const av = sortValueSerasa(a[_sortKeySerasa], col.type);
    const bv = sortValueSerasa(b[_sortKeySerasa], col.type);
    if (av === null && bv === null) return 0;
    if (av === null) return 1;
    if (bv === null) return -1;
    if (av < bv) return -1 * dir;
    if (av > bv) return 1 * dir;
    return 0;
  });
}

function renderHeadSerasa() {
  document.getElementById('thead-serasa').innerHTML = '<tr>' + COLS_SERASA.map(c => {
    const active = c.k === _sortKeySerasa;
    const arrow  = active ? (_sortDirSerasa === 'asc' ? ' ▲' : ' ▼') : '';
    const numCls = c.type === 'number' ? ' num' : '';
    return `<th class="${active ? 'th-active' : ''}${numCls}" title="Clique para ordenar" tabindex="0" role="button" aria-label="Ordenar por ${c.label}" onclick="sortBySerasa('${c.k}')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();sortBySerasa('${c.k}')}">${c.label}${arrow}</th>`;
  }).join('') + '</tr>';
}
renderHeadSerasa();

function fillDetalhe(linhas) {
  ultimasLinhasSerasa = linhas || [];
  const searchInput = document.getElementById('serasa-search');
  if (searchInput) searchInput.value = '';
  renderLinhasSerasa(ordenarSerasa(ultimasLinhasSerasa));
}

function filtrarSerasa() {
  const termo = (document.getElementById('serasa-search').value || '').trim().toLowerCase();
  const base = termo
    ? ultimasLinhasSerasa.filter(r => (r.nome || '').toLowerCase().includes(termo))
    : ultimasLinhasSerasa;
  renderLinhasSerasa(ordenarSerasa(base), !!termo);
}

function renderLinhasSerasa(linhas, filtrado = false) {
  const tbody = document.getElementById('tbody-serasa');
  const count = document.getElementById('serasa-count');
  if (!linhas || !linhas.length) {
    count.textContent = filtrado ? '0 clientes' : '';
    tbody.innerHTML = `<tr><td colspan="${COLS_SERASA.length}" style="text-align:center;padding:32px;color:var(--muted)">${filtrado ? 'Nenhum cliente encontrado para essa busca.' : 'Nenhum cliente consultado no período.'}</td></tr>`;
    return;
  }
  const sufixo = (!filtrado && linhas.length >= 500) ? '+' : '';
  count.textContent = linhas.length + sufixo + ' clientes';
  tbody.innerHTML = linhas.map(r => `
    <tr>
      <td>${escHtml(r.id_bitrix ?? '—')}</td>
      <td>${escHtml(r.nome ?? '—')}</td>
      <td>${escHtml(r.pipe ?? '—')}</td>
      <td>${escHtml(r.fase ?? '—')}</td>
      <td>${escHtml(r.fase_id ?? '—')}</td>
      <td>${escHtml(r.data_fp_realizado ?? '—')}</td>
      <td>${escHtml(r.data_combo_assinado ?? '—')}</td>
      <td class="${rangeCellClass(r.serasa_range)}">${escHtml(r.serasa_range ?? '—')}</td>
      <td class="num ${rangeCellClass(r.serasa_range)}">${r.serasa_score ?? '—'}</td>
      <td>${escHtml(r.status_cpf ?? '—')}</td>
      <td class="${simCellClass(r.nome_sujo)}">${escHtml(r.nome_sujo ?? '—')}</td>
      <td class="${simCellClass(r.socio_negativado)}">${escHtml(r.socio_negativado ?? '—')}</td>
      <td class="num">${fBRL(r.valor_total_dividas_usd)}</td>
      <td class="num">${fBRL(r.valor_cartorio_total_usd)}</td>
      <td>${escHtml(r.responsavel ?? '—')}</td>
    </tr>
  `).join('');
}

// ── Carregar API ──────────────────────────────────────────────
async function loadDashboard(year, months, dataInicio, dataFim) {
  const overlay = document.getElementById('loading-overlay');
  const errBan  = document.getElementById('error-banner');
  overlay.classList.remove('hidden');
  document.body.classList.add('loading');
  errBan.style.display = 'none';

  const params = new URLSearchParams();
  if (year)       params.set('year', year);
  if (months)     params.set('months', months);
  if (dataInicio) params.set('data_inicio', dataInicio);
  if (dataFim)    params.set('data_fim', dataFim);
  const url = API + ([...params].length ? '?' + params.toString() : '');

  try {
    const res  = await fetch(url, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    fillResumoTop(data.resumo.combinado);
    fillPipeCard('com2', data.resumo.comercial2);
    fillPipeCard('com4', data.resumo.comercial4);
    renderRangeTable('com2', data.distribuicao_range.comercial2);
    renderRangeTable('com4', data.distribuicao_range.comercial4);
    renderStatusTable('com2', data.distribuicao_status_cpf.comercial2);
    renderStatusTable('com4', data.distribuicao_status_cpf.comercial4);
    fillDetalhe(data.detalhe);

    const ts = data.meta?.generated
      ? new Date(data.meta.generated.replace(' ', 'T')).toLocaleString('pt-BR')
      : new Date().toLocaleString('pt-BR');
    document.getElementById('last-updated').textContent = 'Atualizado em: ' + ts;
  } catch (err) {
    errBan.textContent   = '⚠️ Erro ao carregar dados: ' + err.message;
    errBan.style.display = 'block';
    console.error(err);
  } finally {
    overlay.classList.add('hidden');
    document.body.classList.remove('loading');
  }
}

function applyFilters() {
  const year       = yearDropdown.getValue();
  const months     = monthDropdown.getValue();
  const dataInicio = getDateInput('data-inicio');
  const dataFim    = getDateInput('data-fim');
  updateBadge(year, months);
  loadDashboard(year, months, dataInicio, dataFim);
}

function clearFilters() {
  yearDropdown.reset(true);
  monthDropdown.reset(true);
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim',    'data-fim-clear');
  updateBadge(null, null);
  loadDashboard(null, null, null, null);
}

// ── Badge do período selecionado ──────────────────────────────
function updateBadge(year, months) {
  const badge = document.getElementById('filter-label-badge');
  if (!year && !months) {
    badge.textContent = 'TODOS OS PERÍODOS';
    return;
  }
  const parts = [];
  if (year) parts.push(year);
  if (months) {
    const nums   = months.split(',').map(Number);
    const labels = nums.map(n => MONTHS[n - 1]);
    parts.push(labels.join(', '));
  }
  badge.textContent = parts.join(' · ').toUpperCase();
}

// ── Init ──────────────────────────────────────────────────────
['data-inicio', 'data-fim'].forEach(id => {
  const input    = document.getElementById(id);
  const clearBtn = document.getElementById(id + '-clear');
  if (!input || !clearBtn) return;
  input.addEventListener('input', () => clearBtn.classList.toggle('visible', !!input.value));
  clearBtn.addEventListener('click', () => { input.value = ''; clearBtn.classList.remove('visible'); });
});

applyFilters();
</script>
</div><!-- /dash-serasa -->
