<div class="dash-law-prioridade">

<style>
/* Ajustes específicos da aba Alta Prioridade do dashboard Law. */
.dash-law-prioridade .kpi-chip.ml1 { border-left: 3px solid #4F79BC; background: rgba(79,121,188,.09); }
.dash-law-prioridade .kpi-chip.ml1 .kpi-chip-val { color: #4F79BC; }
.dash-law-prioridade .kpi-chip.ml2 { border-left: 3px solid var(--consulta); background: var(--consulta-glow); }
.dash-law-prioridade .kpi-chip.ml2 .kpi-chip-val { color: var(--consulta); }
.dash-law-prioridade .kpi-chip.ml3 { border-left: 3px solid var(--fecho); background: var(--fecho-glow); }
.dash-law-prioridade .kpi-chip.ml3 .kpi-chip-val { color: var(--fecho); }

.dash-law-prioridade .destaque-card {
  display: flex; align-items: center; gap: 16px;
  padding: 18px 22px;
}
.dash-law-prioridade .destaque-icon { font-size: 30px; line-height: 1; }
.dash-law-prioridade .destaque-nome { font-size: 17px; font-weight: 700; color: var(--text); }
.dash-law-prioridade .destaque-qtd  { font-size: 13px; color: var(--muted); margin-top: 2px; }
.dash-law-prioridade .destaque-qtd b { font-family: var(--font-mono); color: var(--danger); font-weight: 700; }

.dash-law-prioridade .table-scroll { max-height: 320px; overflow-y: auto; }
.dash-law-prioridade .bi-table { width: 100%; border-collapse: collapse; }
.dash-law-prioridade .bi-table thead th { position: sticky; top: 0; z-index: 1; }
</style>

<!-- ── LOADING ─────────────────────────────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>

<!-- ── HEADER ─────────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo">LW</div>
    <div>
      <div class="header-title">Dashboard Law</div>
      <div class="header-sub">Alta Prioridade · Casos urgentes em aberto</div>
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
      <a class="tab-switch-btn" href="<?= $this->site ?>law<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Law</a>
      <a class="tab-switch-btn active" href="<?= $this->site ?>law/prioridade<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Alta Prioridade</a>
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
<script>initFiltersToggle({ key: 'law-prioridade' });</script>

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
    <div class="kpi-chip sinal">
      <span class="kpi-chip-label">Em Aberto</span>
      <span class="kpi-chip-val" id="k-em-aberto">—</span>
    </div>
    <div class="kpi-chip risco">
      <span class="kpi-chip-label">Alta Prioridade</span>
      <span class="kpi-chip-val" id="k-alta-prioridade">—</span>
    </div>
    <div class="kpi-chip ml1">
      <span class="kpi-chip-label">ML1</span>
      <span class="kpi-chip-val" id="k-ml1">—</span>
    </div>
    <div class="kpi-chip ml2">
      <span class="kpi-chip-label">ML2</span>
      <span class="kpi-chip-val" id="k-ml2">—</span>
    </div>
    <div class="kpi-chip ml3">
      <span class="kpi-chip-label">ML3</span>
      <span class="kpi-chip-val" id="k-ml3">—</span>
    </div>
  </div>

  <!-- DESTAQUE: CASE MANAGER COM MAIS CASOS EM PRIORIDADE -->
  <div class="bi-card" style="margin-top:16px">
    <div class="destaque-card" id="destaque-cm">
      <span class="destaque-icon">🔥</span>
      <div>
        <div class="destaque-nome" id="destaque-nome">Carregando...</div>
        <div class="destaque-qtd" id="destaque-qtd"></div>
      </div>
    </div>
  </div>

  <!-- LINHA: TABELA DE CASOS + EVOLUÇÃO -->
  <div class="funis-grid" style="margin-top:16px">

    <!-- TABELA DE CASOS ALTA PRIORIDADE -->
    <div class="chart-card">
      <div class="chart-header">
        <div class="chart-title-group">
          <span class="chart-dot" style="background:var(--danger)"></span>
          <span class="chart-title">Casos Alta Prioridade</span>
        </div>
      </div>
      <div class="chart-body table-scroll">
        <table class="bi-table" id="tabela-casos">
          <thead>
            <tr>
              <th>idBitrix_dealV2</th>
              <th>DataCriação</th>
              <th>Case Manager</th>
              <th>Fase</th>
              <th class="num">Alta Prioridade</th>
            </tr>
          </thead>
          <tbody id="tabela-casos-body">
            <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:16px">Carregando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- GRÁFICO DE EVOLUÇÃO -->
    <div class="chart-card">
      <div class="chart-header">
        <div class="chart-title-group">
          <span class="chart-dot" style="background:var(--danger)"></span>
          <span class="chart-title">Evolução de Casos em Prioridade</span>
        </div>
        <div class="gran-toggle">
          <button class="gran-btn"        data-gran="dia"    onclick="setGranularidade('dia')">Dia</button>
          <button class="gran-btn"        data-gran="semana" onclick="setGranularidade('semana')">Semana</button>
          <button class="gran-btn active" data-gran="mes"    onclick="setGranularidade('mes')">Mês</button>
          <button class="gran-btn"        data-gran="ano"    onclick="setGranularidade('ano')">Ano</button>
          <button class="gran-btn"        data-gran="ciclo"  onclick="setGranularidade('ciclo')">Ciclo</button>
        </div>
      </div>
      <div class="chart-body" id="line-chart-body">
        <div style="height:220px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:12px">
          Carregando gráfico...
        </div>
      </div>
    </div>

  </div>

  <div class="last-updated" id="last-updated"></div>

</main>
</div>

<script>
// ══════════════════════════════════════════════════════════════
//  Law — Aba Alta Prioridade — Dashboard JS
//  Consome: law/prioridadeDados, law/responsaveis, law/ciclos, law/prioridadeGrafico
//
//  createMultiDropdown, createSearchDropdown, fNum são fornecidas por
//  js/bi-dashboard.js (carregado no <head> pelo template, antes deste script).
// ══════════════════════════════════════════════════════════════

const API_URL          = '<?= $this->site ?>law/prioridadeDados';
const RESPONSAVEIS_URL = '<?= $this->site ?>law/responsaveis';
const CICLOS_URL       = '<?= $this->site ?>law/ciclos';
const GRAFICO_URL      = '<?= $this->site ?>law/prioridadeGrafico';
const MONTHS           = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const YEARS            = ['2024', '2025', '2026'];
const STATUS_OPTS       = ['Em Aberto', 'Aplicados', 'Aprovados', 'NVC'];
const MILESTONE_OPTS    = ['ML1', 'ML2', 'ML3'];

let currentGranularidade = 'mes';
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

['data-inicio', 'data-fim'].forEach(id => {
  const input    = document.getElementById(id);
  const clearBtn = document.getElementById(id + '-clear');
  input?.addEventListener('change', () => clearBtn?.classList.toggle('visible', !!input.value));
  clearBtn?.addEventListener('click', () => clearDateInput(id, id + '-clear'));
});

function set(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }

function escHtmlLawPrioridade(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// ── Granularidade do gráfico de evolução ────────────────────────
function setGranularidade(gran) {
  currentGranularidade = gran;
  document.querySelectorAll('.gran-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.gran === gran);
  });
  loadGrafico(gran);
}

// ── Preencher KPIs (kpis + milestoneDonut) ──────────────────────
function fillKpis(k, milestoneDonut) {
  set('k-totais',          fNum(k.casos_totais));
  set('k-em-aberto',       fNum(k.casos_em_aberto));
  set('k-alta-prioridade', fNum(k.casos_alta_prioridade));

  const ml = { ML1: 0, ML2: 0, ML3: 0 };
  (milestoneDonut || []).forEach(d => { ml[d.milestone] = d.qtd; });
  set('k-ml1', fNum(ml.ML1));
  set('k-ml2', fNum(ml.ML2));
  set('k-ml3', fNum(ml.ML3));
}

// ── Case manager em destaque ─────────────────────────────────────
function fillDestaque(top) {
  const nomeEl = document.getElementById('destaque-nome');
  const qtdEl  = document.getElementById('destaque-qtd');
  if (!top || !top.case_manager || !top.qtd) {
    nomeEl.textContent = 'Nenhum caso de alta prioridade no período';
    qtdEl.textContent  = '';
    return;
  }
  nomeEl.textContent = top.case_manager;
  qtdEl.innerHTML     = '<b>' + fNum(top.qtd) + '</b> caso' + (top.qtd === 1 ? '' : 's') + ' de alta prioridade';
}

// ── Tabela de casos ──────────────────────────────────────────────
function fillTabela(rows) {
  const body = document.getElementById('tabela-casos-body');
  if (!rows || !rows.length) {
    body.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:16px">Nenhum caso de alta prioridade no período</td></tr>';
    return;
  }
  body.innerHTML = rows.map(r => `
    <tr>
      <td>${escHtmlLawPrioridade(r.id_bitrix)}</td>
      <td>${escHtmlLawPrioridade(r.data_criacao)}</td>
      <td>${escHtmlLawPrioridade(r.case_manager)}</td>
      <td>${escHtmlLawPrioridade(r.fase)}</td>
      <td class="num">${fNum(r.alta_prioridade)}</td>
    </tr>
  `).join('');
}

// ── Gráfico de linha (série única) ────────────────────────────────
function buildLineChartSingle(data) {
  const containerId = 'line-chart-body';
  if (!data || !data.length) {
    document.getElementById(containerId).innerHTML =
      '<div style="height:220px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:12px">Sem dados para o período</div>';
    return;
  }

  const COLOR = 'var(--danger)';
  const W = 860, H = 240;
  const pl = 42, pr = 16, pt = 20, pb = 34;
  const cW = W - pl - pr, cH = H - pt - pb;

  const n     = data.length;
  const nSafe = n || 1;
  const slot  = cW / nSafe;
  const step  = nSafe <= 12 ? 1 : nSafe <= 24 ? 2 : nSafe <= 52 ? 4 : 8;
  const fs    = nSafe > 30 ? 7 : nSafe > 15 ? 8 : 9;

  const maxVal = Math.max(1, ...data.map(d => d.qtd)) * 1.15;
  const yOf = v => pt + cH - Math.round((v / maxVal) * cH);
  const xOf = i => Math.round(pl + slot * i + slot / 2);

  const gridLines = [0, 0.25, 0.5, 0.75, 1].map(f => {
    const y = pt + Math.round(cH * f);
    const v = Math.round(maxVal * (1 - f));
    const isBase = f === 1;
    return `<line x1="${pl}" y1="${y}" x2="${pl + cW}" y2="${y}" stroke="${isBase ? '#cbd5e1' : '#f1f5f9'}" stroke-width="1"/>` +
      `<text x="${pl - 6}" y="${y + 3}" text-anchor="end" font-size="9" fill="#94a3b8" class="num">${fNum(v)}</text>`;
  }).join('');

  const labels = data.map((d, i) => (i % step === 0 || i === n - 1)
    ? `<text x="${xOf(i)}" y="${H - 8}" text-anchor="middle" font-size="${fs}" fill="#64748b" font-weight="500">${d.label}</text>`
    : '').join('');

  const pathD = data.map((d, i) => `${i === 0 ? 'M' : 'L'}${xOf(i)},${yOf(d.qtd)}`).join(' ');
  const dots  = data.map((d, i) => `<circle cx="${xOf(i)}" cy="${yOf(d.qtd)}" r="2.6" fill="${COLOR}"/>`).join('');
  const valLabels = data.map((d, i) => d.qtd > 0
    ? `<text x="${xOf(i)}" y="${yOf(d.qtd) - 8}" text-anchor="middle" font-size="9" font-weight="700" fill="${COLOR}" class="num">${fNum(d.qtd)}</text>`
    : '').join('');

  const svg = `<svg width="100%" viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg" style="display:block;overflow:visible">
    ${gridLines}
    <path d="${pathD}" fill="none" stroke="${COLOR}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
    ${dots}${valLabels}${labels}
  </svg>`;

  document.getElementById(containerId).innerHTML = svg;
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

// ── Recarregar só o gráfico de evolução (troca de granularidade) ──
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
    '<div style="height:220px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:12px">Carregando gráfico...</div>';

  try {
    const res  = await fetch(GRAFICO_URL + '?' + params.toString(), { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (data.error) throw new Error(data.error);
    buildLineChartSingle(data.grafico);
  } catch (err) {
    document.getElementById('line-chart-body').innerHTML =
      '<div style="height:220px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:12px">Erro ao carregar gráfico</div>';
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

    fillKpis(data.kpis, data.milestoneDonut);
    fillDestaque(data.topCaseManager);
    fillTabela(data.casos);
    buildLineChartSingle(data.grafico);

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

yearDropdown.select(['2026']);
applyFilters();
</script>
