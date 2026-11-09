<div class="dash-especialistas">

<!-- ── LOADING ─────────────────────────────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>

<!-- ── HEADER ─────────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo">MR</div>
    <div>
      <div class="header-title">Dashboard Especialistas</div>
      <div class="header-sub">Consultas C5 &middot; Realizadas &rarr; FP Vendido &rarr; Contrato &rarr; Combo</div>
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
      <a class="tab-switch-btn" href="<?= $this->site ?>executive<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Executive</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>executive/digital<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Digital &times; Evento</a>
      <a class="tab-switch-btn active" href="<?= $this->site ?>executive/especialistas<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Especialistas</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>executive/analitico<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Analítico</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>executive/serasa<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Serasa</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>executive/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
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

  <!-- Especialista -->
  <div class="filter-pair">
    <span class="filter-label">Especialista</span>
    <div class="multi-dd-wrap" id="esp-wrap">
      <button class="multi-dd-btn" id="esp-btn" type="button">Todos <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="esp-list"></div>
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

  <!-- Form -->
  <div class="filter-pair">
    <span class="filter-label">Form</span>
    <div class="multi-dd-wrap" id="form-wrap">
      <button class="multi-dd-btn" id="form-btn" type="button">Todos <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="form-list"></div>
    </div>
  </div>

  <!-- Campanha -->
  <div class="filter-pair">
    <span class="filter-label">Campanha</span>
    <div class="multi-dd-wrap" id="camp-wrap">
      <button class="multi-dd-btn" id="camp-btn" type="button">Todos <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="camp-list"></div>
    </div>
  </div>

  <!-- Responsável -->
  <div class="filter-pair">
    <span class="filter-label">Responsável</span>
    <div class="camp-wrap" id="resp-wrap">
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
        </span>
        <input id="resp-input" class="camp-input" type="text" placeholder="Pesquisar responsável..." autocomplete="off">
        <button class="camp-clear" id="resp-clear" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-tags" id="resp-tags"></div>
      <div class="camp-dropdown" id="resp-dropdown"></div>
    </div>
  </div>

  <button class="btn-apply" onclick="applyFilters()">Aplicar</button>
  <button class="btn-clear" onclick="clearFilters()">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'executive-especialistas' });</script>

<!-- ── ERRO ───────────────────────────────────────────────────── -->
<div id="error-banner" role="alert"></div>

<!-- ── CONTEÚDO PRINCIPAL ──────────────────────────────────────── -->
<main class="main" id="dashboard-main">

  <!-- KPI GRID (7 métricas principais) -->
  <div class="kpi-grid" id="kpi-grid">

    <div class="kpi-card">
      <div class="kpi-card-top">
        <div class="kpi-label">Agendadas</div>
        <div class="kpi-icon" style="background:var(--surface-2);color:var(--text-secondary)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
        </div>
      </div>
      <div class="kpi-value" id="k-agendadas">—</div>
      <div class="kpi-desc">Consultas marcadas</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-card-top">
        <div class="kpi-label">Realizadas</div>
        <div class="kpi-icon" style="background:var(--consulta-glow);color:var(--consulta)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
        </div>
      </div>
      <div class="kpi-value blue" id="k-realizadas">—</div>
      <div class="kpi-desc">Consultas efetuadas</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-card-top">
        <div class="kpi-label">A Realizar</div>
        <div class="kpi-icon" style="background:var(--amber-glow);color:var(--amber)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
          </svg>
        </div>
      </div>
      <div class="kpi-value amber" id="k-a-realizar">—</div>
      <div class="kpi-desc">Agendadas &minus; Realizadas</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-card-top">
        <div class="kpi-label">FP Vendido</div>
        <div class="kpi-icon" style="background:var(--consulta-glow);color:var(--consulta)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
          </svg>
        </div>
      </div>
      <div class="kpi-value blue" id="k-fp-vendido">—</div>
      <div class="kpi-desc">Financial Planning vendido</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-card-top">
        <div class="kpi-label">Assinaturas</div>
        <div class="kpi-icon" style="background:var(--accent2-glow);color:var(--accent2)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
          </svg>
        </div>
      </div>
      <div class="kpi-value purple" id="k-contrato">—</div>
      <div class="kpi-desc">Contratos enviados</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-card-top">
        <div class="kpi-label">Combos</div>
        <div class="kpi-icon" style="background:var(--green-glow);color:var(--green)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/>
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
          </svg>
        </div>
      </div>
      <div class="kpi-value green" id="k-combos">—</div>
      <div class="kpi-desc">Combos vendidos</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-card-top">
        <div class="kpi-label">Conversão</div>
        <div class="kpi-icon" style="background:var(--amber-glow);color:var(--amber)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>
          </svg>
        </div>
      </div>
      <div class="kpi-value amber" id="k-conversao">—</div>
      <div class="kpi-desc">Combos / Realizadas</div>
    </div>

  </div><!-- /kpi-grid -->

  <!-- Distrato + Churn (linha secundária, menor) -->
  <div style="display:flex;gap:10px;max-width:300px">
    <div class="kpi-card" style="flex:1">
      <div class="kpi-card-top">
        <div class="kpi-label">Distrato</div>
        <div class="kpi-icon" style="background:var(--danger-glow);color:var(--danger)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
          </svg>
        </div>
      </div>
      <div class="kpi-value red" id="k-distrato" style="font-size:18px">—</div>
      <div class="kpi-desc">Rescisões com combo</div>
    </div>
    <div class="kpi-card" style="flex:1">
      <div class="kpi-card-top">
        <div class="kpi-label">Churn FP</div>
        <div class="kpi-icon" style="background:var(--danger-glow);color:var(--danger)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
          </svg>
        </div>
      </div>
      <div class="kpi-value red" id="k-churn" style="font-size:18px">—</div>
      <div class="kpi-desc">Cancelamentos FP</div>
    </div>
  </div>

  <!-- CHART CARD -->
  <div class="chart-card">
    <div class="chart-header">
      <div class="chart-title-group">
        <span class="chart-dot" style="background:var(--consulta)"></span>
        <span class="chart-title">Consultas Realizadas &amp; Combos Vendidos</span>
      </div>
      <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <div class="chart-legend">
          <div class="legend-item"><span class="legend-color" style="background:var(--amber);height:2px"></span>Conversão %</div>
          <div class="legend-item"><span class="legend-color" style="background:var(--consulta)"></span>Realizadas</div>
          <div class="legend-item"><span class="legend-color" style="background:var(--green)"></span>Combos</div>
        </div>
        <div class="gran-toggle">
          <button class="gran-btn" data-gran="ano"   onclick="setGran('ano')">Ano</button>
          <button class="gran-btn active" data-gran="mes" onclick="setGran('mes')">Mês</button>
          <button class="gran-btn" data-gran="semana" onclick="setGran('semana')">Semana</button>
          <button class="gran-btn" data-gran="dia"   onclick="setGran('dia')">Dia</button>
        </div>
      </div>
    </div>
    <div class="chart-body" id="chart-body">
      <div style="height:220px;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:12px">
        Carregando gráfico...
      </div>
    </div>
  </div>

  <!-- TABLE CARD -->
  <div class="table-card">
    <div class="table-header">
      <span class="chart-dot" style="background:var(--accent2)"></span>
      <span class="chart-title">Performance por Especialista</span>
    </div>
    <div style="overflow-x:auto">
      <table class="esp-table" id="esp-table">
        <thead>
          <tr>
            <th class="th-left">Especialista</th>
            <th class="th-right">Realizadas</th>
            <th class="th-right">Combos</th>
            <th class="th-right">Convers&atilde;o</th>
            <th class="th-right">Churn FP</th>
            <th class="th-right">Distrato</th>
          </tr>
        </thead>
        <tbody id="esp-tbody">
          <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text-muted)">Carregando...</td></tr>
        </tbody>
        <tfoot id="esp-tfoot"></tfoot>
      </table>
    </div>
  </div>

  <div class="last-updated" id="last-updated"></div>

</main>

<script>
// ══════════════════════════════════════════════════════════════
//  Especialistas — Dashboard JS
//  Consome: executive/especialistasDados (controller MRPHP)
//
//  createMultiDropdown, createSearchDropdown, getDateInput,
//  setEffectiveDate, clearDateInput, fNum e fPct são fornecidas
//  por js/bi-dashboard.js (carregado no <head> pelo template,
//  antes deste script) — apenas os pontos de chamada permanecem
//  aqui.
// ══════════════════════════════════════════════════════════════

const API_URL = '<?= $this->site ?>executive/especialistasDados';
const MONTHS  = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const YEARS   = anosDesde(2025);

let currentGran = 'mes';

// ── ESPECIALISTAS ──────────────────────────────────────────────
// FIX: a versão original desta aba tinha apenas 5 nomes (faltava
// Ricardo Albuquerque), divergindo da equipe padrão de 6 usada no
// backend e nas demais abas. Corrigido para os 6 nomes.
const ESPECIALISTAS = ['Rafael Torres', 'Camila Duarte', 'Paulo Nogueira', 'Beatriz Lemos', 'Juliana Prado', 'Ricardo Albuquerque'];
const espDropdown   = createMultiDropdown('esp-wrap',   'esp-btn',   'esp-list',   ESPECIALISTAS, l => l, l => l);
const yearDropdown  = createMultiDropdown('year-wrap',  'year-btn',  'year-list',  YEARS,  l => l, l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);

const NOW_YEAR  = String(new Date().getFullYear());
const NOW_MONTH = new Date().getMonth() + 1;
yearDropdown.select([NOW_YEAR]);
monthDropdown.select([String(NOW_MONTH)]);

const campDropdown = createMultiDropdown('camp-wrap', 'camp-btn', 'camp-list', [], l => l, l => l);
const formDropdown = createMultiDropdown('form-wrap', 'form-btn', 'form-list', [], l => l, l => l);
const respDropdown = createSearchDropdown('resp-wrap', 'resp-input', 'resp-dropdown', 'resp-clear', 'resp-tags');

// ── Formatador local de texto (id -> textContent) ──────────────
function set(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }

// ── Granularidade ─────────────────────────────────────────────
function setGran(gran) {
  currentGran = gran;
  document.querySelectorAll('.gran-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.gran === gran);
  });
  applyFilters();
}

// ── Preencher KPIs ────────────────────────────────────────────
function fillKpis(k) {
  set('k-agendadas',  fNum(k.agendadas));
  set('k-realizadas', fNum(k.realizadas));
  set('k-a-realizar', fNum(k.a_realizar));
  set('k-fp-vendido', fNum(k.fp_vendido));
  set('k-contrato',   fNum(k.contrato_enviado));
  set('k-combos',     fNum(k.combos_vendidos));
  set('k-conversao',  fPct(k.conversao));
  set('k-distrato',   fNum(k.distrato));
  set('k-churn',      fNum(k.churn_fp));
}

// ── Gráfico SVG (combo: colunas + linha de conversão) ─────────
function buildChart(data) {
  if (!data || !data.length) {
    document.getElementById('chart-body').innerHTML =
      '<div style="height:280px;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:12px">Sem dados para o período</div>';
    return;
  }

  // Dimensões
  const W = 860, H = 225;
  const pl = 48, pr = 58, pt = 24, pb = 36;
  const cW = W - pl - pr, cH = H - pt - pb;

  const n     = data.length;
  const nSafe = n || 1;
  const slot  = cW / nSafe;

  // Largura de cada barra (par lado a lado)
  const bw  = slot > 20 ? Math.round(slot * 0.30) : slot > 8 ? Math.round(slot * 0.36) : Math.max(2, Math.round(slot * 0.42));
  const gap = Math.max(1, Math.round(bw * 0.18));

  const step     = nSafe <= 12 ? 1 : nSafe <= 24 ? 2 : nSafe <= 52 ? 4 : 8;
  const showNums = nSafe <= 20;
  const fs       = nSafe > 30 ? 7 : nSafe > 15 ? 8 : 9;

  // Escalas
  const maxBar  = Math.max(1, ...data.map(d => Math.max(d.realizadas, d.combos))) * 1.18;
  const maxConv = Math.max(0.001, ...data.map(d => d.conversao)) * 1.20;

  // Helpers de escala
  const yBar  = v => pt + cH - Math.round((v / maxBar)  * cH);
  const yConv = v => pt + cH - Math.round((v / maxConv) * cH);

  // ── Grade horizontal ───────────────────────────────────────
  const gridLines = [0, 0.25, 0.5, 0.75, 1].map(f => {
    const y  = pt + Math.round(cH * f);
    const vL = Math.round(maxBar  * (1 - f));
    const vR = ((maxConv * (1 - f)) * 100).toFixed(0) + '%';
    const isBase = f === 1;
    return (
      `<line x1="${pl}" y1="${y}" x2="${pl+cW}" y2="${y}" stroke="${isBase ? '#cbd5e1' : '#f1f5f9'}" stroke-width="1"/>` +
      // Eixo esquerdo (contagens)
      `<text x="${pl-6}" y="${y+3}" text-anchor="end" font-size="9" fill="#94a3b8" class="num">${fNum(vL)}</text>` +
      // Eixo direito (conversão %)
      `<text x="${pl+cW+6}" y="${y+3}" text-anchor="start" font-size="9" fill="#b86e00" class="num">${vR}</text>`
    );
  }).join('');

  // ── Barras ─────────────────────────────────────────────────
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

    // tooltip: posicionar à esquerda nos últimos 20% do gráfico
    const tipX = cx > pl + cW * 0.80 ? cx - 122 : cx - 4;

    bars +=
      `<g class="bar-slot" data-tip="${i}">` +
      // área de hover transparente
      `<rect x="${cx - Math.round(slot/2)}" y="${pt}" width="${Math.round(slot)}" height="${cH}" fill="transparent"/>` +
      // coluna realizadas (consulta)
      `<rect x="${xr}" y="${yr}" width="${bw}" height="${hr}" fill="#1a6ec8" rx="2"/>` +
      // coluna combos (fecho/verde)
      `<rect x="${xc}" y="${yc}" width="${bw}" height="${hc}" fill="#0d8a52" rx="2"/>` +
      // rótulos sobre barras (só se couber)
      (showNums && hr > 14 ? `<text x="${xr+Math.round(bw/2)}" y="${yr-4}" text-anchor="middle" font-size="8" fill="#1a6ec8" font-weight="600" class="num">${d.realizadas}</text>` : '') +
      (showNums && hc > 14 ? `<text x="${xc+Math.round(bw/2)}" y="${yc-4}" text-anchor="middle" font-size="8" fill="#0d8a52" font-weight="600" class="num">${d.combos}</text>` : '') +
      // rótulo do eixo X
      (printLbl ? `<text x="${cx}" y="${H-8}" text-anchor="middle" font-size="${fs}" fill="#64748b" font-weight="500">${d.label}</text>` : '') +
      `</g>`;

    // tooltip separado — renderizado após linha/dots para ficar na frente
    tips +=
      `<g class="tip" style="pointer-events:none" transform="translate(${tipX},${pt+2})">` +
        `<rect x="0" y="0" width="118" height="72" rx="8" fill="#0f172a" opacity=".96"/>` +
        `<text x="59" y="14" text-anchor="middle" font-size="9" fill="#94a3b8" font-weight="600">${d.label}</text>` +
        `<line x1="8" y1="19" x2="110" y2="19" stroke="rgba(255,255,255,0.08)" stroke-width="1"/>` +
        `<rect x="8" y="26" width="7" height="7" rx="2" fill="#1a6ec8"/>` +
        `<text x="20" y="33" font-size="9" fill="#94a3b8">Realizadas</text>` +
        `<text x="110" y="33" text-anchor="end" font-size="9" fill="#81afe1" font-weight="700" class="num">${fNum(d.realizadas)}</text>` +
        `<rect x="8" y="38" width="7" height="7" rx="2" fill="#0d8a52"/>` +
        `<text x="20" y="45" font-size="9" fill="#94a3b8">Combos</text>` +
        `<text x="110" y="45" text-anchor="end" font-size="9" fill="#7abfa0" font-weight="700" class="num">${fNum(d.combos)}</text>` +
        `<line x1="8" y1="51" x2="110" y2="51" stroke="rgba(255,255,255,0.06)" stroke-width="1"/>` +
        `<circle cx="12" cy="59" r="3.5" fill="#b86e00"/>` +
        `<text x="20" y="63" font-size="9" fill="#94a3b8">Conversão</text>` +
        `<text x="110" y="63" text-anchor="end" font-size="9" fill="#d8af73" font-weight="700" class="num">${cv}</text>` +
      `</g>`;

    convPts.push({ cx, cy: yConv(d.conversao), cv, tipIdx: i });
  });

  // ── Linha de conversão ─────────────────────────────────────
  let lineConv = '', dotsConv = '';

  // Polyline path
  const pathD = convPts.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.cx},${p.cy}`).join(' ');
  lineConv = `<path d="${pathD}" fill="none" stroke="#b86e00" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>`;

  convPts.forEach((p, i) => {
    const showLbl = showNums && n <= 36;
    dotsConv +=
      `<circle cx="${p.cx}" cy="${p.cy}" r="4" fill="#b86e00" stroke="#fff" stroke-width="2"/>` +
      (showLbl ? `<text x="${p.cx}" y="${p.cy - 9}" text-anchor="middle" font-size="8" fill="#b86e00" font-weight="700" class="num">${p.cv}</text>` : '');
  });

  // ── Render ─────────────────────────────────────────────────
  // tips ficam após lineConv e dotsConv para pintar por cima
  const svg = `<svg width="100%" viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg" style="display:block;overflow:visible">
    ${gridLines}${bars}${lineConv}${dotsConv}
    <g id="tips-layer">${tips}</g>
  </svg>`;

  document.getElementById('chart-body').innerHTML = svg;

  // conecta hover do bar-slot com o tip correspondente no tips-layer
  const tipsEls = document.querySelectorAll('#tips-layer .tip');
  document.querySelectorAll('.bar-slot').forEach(slot => {
    const idx = slot.getAttribute('data-tip');
    slot.addEventListener('mouseenter', () => tipsEls[idx]?.classList.add('visible'));
    slot.addEventListener('mouseleave', () => tipsEls[idx]?.classList.remove('visible'));
  });
}

// ── Tabela ────────────────────────────────────────────────────
function fillTabela(tabela) {
  const tbody = document.getElementById('esp-tbody');
  const tfoot = document.getElementById('esp-tfoot');

  if (!tabela || !tabela.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text-muted)">Sem dados para o período</td></tr>';
    tfoot.innerHTML = '';
    return;
  }

  // FIX: lista alinhada com a equipe padrão de 6 especialistas (ver ESPECIALISTAS acima).
  const ALLOWED = ['Rafael Torres', 'Camila Duarte', 'Paulo Nogueira', 'Beatriz Lemos', 'Juliana Prado', 'Ricardo Albuquerque'];
  const espSel  = espDropdown.getValue();
  const espList = espSel ? espSel.split(',') : ALLOWED;
  const filtered = tabela.filter(r => espList.includes(r.especialista));

  let totRe = 0, totCo = 0, totDi = 0, totCh = 0;
  tbody.innerHTML = filtered.map((r, idx) => {
    totRe += r.realizadas; totCo += r.combos; totDi += r.distrato; totCh += r.churn_fp;
    const bg = idx % 2 === 0 ? '#ffffff' : '#f8fafc';
    return `<tr style="background:${bg}">
      <td class="td-nome">${r.especialista}</td>
      <td class="td-num">${fNum(r.realizadas)}</td>
      <td class="td-num td-green">${fNum(r.combos)}</td>
      <td class="td-num td-amber">${fPct(r.conversao)}</td>
      <td class="td-num ${r.churn_fp > 0 ? 'td-red' : ''}">${fNum(r.churn_fp)}</td>
      <td class="td-num ${r.distrato > 0 ? 'td-red' : ''}">${fNum(r.distrato)}</td>
    </tr>`;
  }).join('');

  const totConv = totRe > 0 ? totCo / totRe : 0;
  tfoot.innerHTML = `<tr class="tr-total">
    <td class="td-nome">Total</td>
    <td>${fNum(totRe)}</td>
    <td class="td-green">${fNum(totCo)}</td>
    <td class="td-amber">${fPct(totConv)}</td>
    <td class="${totCh > 0 ? 'td-red' : ''}">${fNum(totCh)}</td>
    <td class="${totDi > 0 ? 'td-red' : ''}">${fNum(totDi)}</td>
  </tr>`;
}

// ── Badge ─────────────────────────────────────────────────────
function updateBadge(year, months, campanha, form, responsavel) {
  const badge = document.getElementById('filter-label-badge');
  if (!year && !months && !campanha && !form && !responsavel) {
    badge.textContent = 'TODOS OS PERÍODOS';
    return;
  }
  const parts = [];
  if (responsavel) parts.push('Resp: ' + responsavel);
  if (form)        parts.push('Form: ' + form);
  if (campanha)    parts.push(campanha);
  if (year)        parts.push(year);
  if (months) {
    const labels = months.split(',').map(n => MONTHS[Number(n) - 1]);
    parts.push(labels.join(', '));
  }
  badge.textContent = parts.join(' · ').toUpperCase();
}

// ── Carregar dados ────────────────────────────────────────────
async function loadDashboard(year, months, campanha, form, responsavel, granularidade, dataInicio, dataFim) {
  const overlay = document.getElementById('loading-overlay');
  const errBan  = document.getElementById('error-banner');
  overlay.classList.remove('hidden');
  document.body.classList.add('loading');
  errBan.style.display = 'none';

  let url = API_URL;
  const params = new URLSearchParams();
  if (year)         params.set('year', year);
  if (months)       params.set('months', months);
  if (campanha)     params.set('campanha', campanha);
  if (form)         params.set('form', form);
  if (responsavel)  params.set('responsavel', responsavel);
  if (granularidade) params.set('granularidade', granularidade);
  if (dataInicio)   params.append('data_inicio', dataInicio);
  if (dataFim)      params.append('data_fim', dataFim);
  if ([...params].length) url += '?' + params.toString();

  try {
    const res  = await fetch(url, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    fillKpis(data.kpis);
    buildChart(data.grafico);
    fillTabela(data.tabela);

    const ts = data.meta?.generated
      ? new Date(data.meta.generated.replace(' ', 'T')).toLocaleString('pt-BR')
      : new Date().toLocaleString('pt-BR');
    document.getElementById('last-updated').textContent = 'Atualizado em: ' + ts;

  } catch (err) {
    errBan.textContent  = '⚠️ Erro ao carregar dados: ' + err.message;
    errBan.style.display = 'block';
    console.error(err);
  } finally {
    overlay.classList.add('hidden');
    document.body.classList.remove('loading');
  }
}

// ── Date range default (específico desta aba) ──────────────────
function computeEffectiveDates(year, months) {
  const today    = new Date();
  const todayStr = today.toISOString().slice(0, 10);
  const y        = year ? parseInt(year) : today.getFullYear();
  if (months) {
    const arr  = months.split(',').map(Number);
    const minM = Math.min(...arr), maxM = Math.max(...arr);
    const start = `${y}-${String(minM).padStart(2, '0')}-01`;
    const isNow = y === today.getFullYear() && maxM === today.getMonth() + 1;
    const end   = isNow ? todayStr : new Date(y, maxM, 0).toISOString().slice(0, 10);
    return { start, end };
  }
  return { start: `${y}-01-01`, end: y === today.getFullYear() ? todayStr : `${y}-12-31` };
}

// ── Aplicar / Limpar filtros ──────────────────────────────────
function applyFilters() {
  const year        = yearDropdown.getValue();
  const months      = monthDropdown.getValue();
  const campanha    = campDropdown.getValue();
  const form        = formDropdown.getValue();
  const responsavel = respDropdown.getValue();
  const dataInicio  = getDateInput('data-inicio');
  const dataFim     = getDateInput('data-fim');
  if (!dataInicio && !dataFim) {
    const eff = computeEffectiveDates(year, months);
    setEffectiveDate('data-inicio', eff.start);
    setEffectiveDate('data-fim', eff.end);
  }
  const apiInicio = dataInicio || document.getElementById('data-inicio')?.value || null;
  const apiFim    = dataFim    || document.getElementById('data-fim')?.value    || null;
  updateBadge(year, months, campanha, form, responsavel);
  loadDashboard(year, months, campanha, form, responsavel, currentGran, apiInicio, apiFim);
}

function clearFilters() {
  espDropdown.reset();
  yearDropdown.reset();
  monthDropdown.reset();
  campDropdown.reset();
  formDropdown.reset();
  respDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim',    'data-fim-clear');
  updateBadge(null, null, null, null, null);
  loadDashboard(null, null, null, null, null, currentGran, null, null);
}

// ── Carregar listas de filtros ────────────────────────────────
async function loadFiltros() {
  try {
    const [resForms, resResps, resCamps] = await Promise.all([
      fetch('<?= $this->site ?>executive/forms',        { cache: 'no-store' }),
      fetch('<?= $this->site ?>executive/responsaveis', { cache: 'no-store' }),
      fetch('<?= $this->site ?>executive/campanhas',    { cache: 'no-store' }),
    ]);
    formDropdown.load(await resForms.json());
    respDropdown.load(await resResps.json());
    campDropdown.load(await resCamps.json());
  } catch (e) {
    console.warn('Não foi possível carregar filtros:', e.message);
  }
}

// ── Inicializar ───────────────────────────────────────────────
loadFiltros();
applyFilters();

['data-inicio', 'data-fim'].forEach(id => {
  const input    = document.getElementById(id);
  const clearBtn = document.getElementById(id + '-clear');
  if (!input || !clearBtn) return;
  input.addEventListener('focus', () => {
    const wrap = input.closest('.date-input-wrap');
    if (wrap?.classList.contains('is-effective')) {
      wrap.classList.remove('is-effective');
      input.value = '';
    }
  });
  input.addEventListener('input', () => clearBtn.classList.toggle('visible', !!input.value));
  clearBtn.addEventListener('click', () => {
    const wrap = input.closest('.date-input-wrap');
    if (wrap) wrap.classList.remove('is-effective');
    input.value = '';
    clearBtn.classList.remove('visible');
  });
});
</script>

</div><!-- /dash-especialistas -->
