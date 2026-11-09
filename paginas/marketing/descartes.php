<div class="dash-marketing dash-marketing-descartes">

<div id="loading-overlay" class="hidden"></div>

<header class="header">
  <div class="header-left">
    <div class="logo" aria-hidden="true">MR</div>
    <div>
      <div class="header-title">Marketing &mdash; Motivos de Descarte</div>
      <div class="header-sub">Comercial 1 &middot; Comercial 2 &middot; Comercial 3</div>
    </div>
    <button class="btn-theme" id="theme-btn" type="button" onclick="toggleTheme()" aria-label="Alternar tema claro/escuro" title="Alternar tema">
      <svg id="theme-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      <svg id="theme-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
        <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
        <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
      </svg>
    </button>
  </div>
  <div class="header-right">
    <span class="header-badge" id="filter-label-badge">TODOS OS PERÍODOS</span>
    <div class="tab-switch">
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Visão Geral</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/comparativo<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Comparativo</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/eventos<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Eventos</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/desempenho<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Desempenho</a>
      <a class="tab-switch-btn active" href="<?= $this->site ?>marketing/descartes<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Descartes</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/classificacao<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Classificação</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
    </div>
  </div>
</header>

<!-- Mesmo painel recolhível e os mesmos sete filtros das demais views de Marketing. -->
<section class="filters is-collapsible" id="filters-section" aria-label="Filtros do dashboard">
  <button class="filters-toggle" id="filters-toggle" type="button" aria-expanded="true" aria-controls="filters-fields">
    <span>Filtros</span><span class="dd-caret filters-toggle-caret">▾</span>
  </button>
  <div class="filters-fields" id="filters-fields">
    <div class="filter-pair">
      <span class="filter-label">Últimos</span>
      <div class="rel-period-group">
        <input type="number" id="rel-n" class="filter-input rel-n" min="1" step="1" inputmode="numeric" placeholder="—" autocomplete="off" aria-label="Quantidade de períodos">
        <select id="rel-unit" class="filter-select" aria-label="Unidade do período">
          <option value="dias">Dias</option>
          <option value="semanas">Semanas</option>
          <option value="meses">Meses</option>
        </select>
      </div>
    </div>
    <div class="filter-sep"></div>
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
    <div class="filter-sep"></div>

    <div class="filter-pair">
      <span class="filter-label">Campanha</span>
      <div class="camp-wrap" id="camp-wrap">
        <div class="camp-tags" id="camp-tags"></div>
        <div class="camp-input-row">
          <span class="camp-icon"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
          <input id="camp-input" class="camp-input" type="text" placeholder="Pesquisar campanha..." autocomplete="off" aria-label="Pesquisar campanha" aria-controls="camp-dropdown">
          <button class="camp-clear" id="camp-clear" aria-label="Limpar campanha" tabindex="-1">&#x2715;</button>
        </div>
        <div class="camp-dropdown" id="camp-dropdown" role="listbox"></div>
      </div>
    </div>

    <div class="filter-pair">
      <span class="filter-label">Form</span>
      <div class="camp-wrap" id="form-wrap">
        <div class="camp-tags" id="form-tags"></div>
        <div class="camp-input-row">
          <span class="camp-icon"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
          <input id="form-input" class="camp-input" type="text" placeholder="Pesquisar form..." autocomplete="off" aria-label="Pesquisar form preenchido" aria-controls="form-dropdown">
          <button class="camp-clear" id="form-clear" aria-label="Limpar form" tabindex="-1">&#x2715;</button>
        </div>
        <div class="camp-dropdown" id="form-dropdown" role="listbox"></div>
      </div>
    </div>

    <div class="filter-pair">
      <span class="filter-label">Conj. Anúncios</span>
      <div class="camp-wrap" id="conj-wrap">
        <div class="camp-tags" id="conj-tags"></div>
        <div class="camp-input-row">
          <span class="camp-icon"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
          <input id="conj-input" class="camp-input" type="text" placeholder="Pesquisar conjunto..." autocomplete="off" aria-label="Pesquisar conjunto de anúncios" aria-controls="conj-dropdown">
          <button class="camp-clear" id="conj-clear" aria-label="Limpar conjunto de anúncios" tabindex="-1">&#x2715;</button>
        </div>
        <div class="camp-dropdown" id="conj-dropdown" role="listbox"></div>
      </div>
    </div>

    <div class="filter-pair">
      <span class="filter-label">Anúncio</span>
      <div class="camp-wrap" id="an-wrap">
        <div class="camp-tags" id="an-tags"></div>
        <div class="camp-input-row">
          <span class="camp-icon"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
          <input id="an-input" class="camp-input" type="text" placeholder="Pesquisar anúncio..." autocomplete="off" aria-label="Pesquisar anúncio" aria-controls="an-dropdown">
          <button class="camp-clear" id="an-clear" aria-label="Limpar anúncio" tabindex="-1">&#x2715;</button>
        </div>
        <div class="camp-dropdown" id="an-dropdown" role="listbox"></div>
      </div>
    </div>

    <button class="btn-apply" id="btn-apply">Aplicar</button>
    <button class="btn-clear" id="btn-clear">Limpar</button>
  </div>
</section>

<div id="error-banner" role="alert"></div>

<main class="main" id="dashboard-main" aria-label="Dashboard de motivos de descarte">
  <div class="kpi-row kpi-top">
    <div class="kpi-chip"><span class="kpi-chip-label">Total de Leads</span><span class="kpi-chip-val" id="kpi-leads">—</span><span class="kpi-sub">&nbsp;</span></div>
    <div class="kpi-chip"><span class="kpi-chip-label">Qualificados</span><span class="kpi-chip-val" id="kpi-qualificados">—</span><span class="kpi-sub">&nbsp;</span></div>
    <div class="kpi-chip"><span class="kpi-chip-label">% Qualificação</span><span class="kpi-chip-val" id="kpi-pct-qualificados">—</span><span class="kpi-sub">&nbsp;</span></div>
    <div class="kpi-chip evento"><span class="kpi-chip-label">Cadeiras Vendidas</span><span class="kpi-chip-val" id="kpi-cadeiras">—</span><span class="kpi-sub" id="kpi-cadeiras-sub">&nbsp;</span></div>
    <div class="kpi-chip consulta"><span class="kpi-chip-label">Consultas Vendidas</span><span class="kpi-chip-val" id="kpi-consultas-vendidas">—</span><span class="kpi-sub" id="kpi-consultas-vendidas-sub">&nbsp;</span></div>
    <div class="kpi-chip consulta"><span class="kpi-chip-label">Consultas Realizadas</span><span class="kpi-chip-val" id="kpi-consultas-realizadas">—</span><span class="kpi-sub">&nbsp;</span></div>
    <div class="kpi-chip fecho"><span class="kpi-chip-label">Combos Vendidos</span><span class="kpi-chip-val" id="kpi-combos">—</span><span class="kpi-sub" id="kpi-combos-sub">&nbsp;</span></div>
  </div>

  <section class="desc-reasons-grid" aria-label="Motivos de descarte por comercial">
    <article class="bi-card desc-reason-card">
      <div class="bi-card-header"><span class="bi-card-title">Descartes Comercial 1</span><span class="desc-card-total" id="total-com1">—</span></div>
      <div class="bi-card-body"><div class="desc-reason-list" id="motivos-com1"></div></div>
    </article>
    <article class="bi-card desc-reason-card">
      <div class="bi-card-header"><span class="bi-card-title">Descartes Comercial 2</span><span class="desc-card-total" id="total-com2">—</span></div>
      <div class="bi-card-body"><div class="desc-reason-list" id="motivos-com2"></div></div>
    </article>
    <article class="bi-card desc-reason-card">
      <div class="bi-card-header"><span class="bi-card-title">Descartes Comercial 3</span><span class="desc-card-total" id="total-com3">—</span></div>
      <div class="bi-card-body"><div class="desc-reason-list" id="motivos-com3"></div></div>
    </article>
  </section>

  <div class="desc-metric-tabs" id="metric-toggle" role="group" aria-label="Métrica dos gráficos">
    <button type="button" data-metrica="qualificados">Qualificados</button>
    <button type="button" data-metrica="descarte_com1">Descartes Com1</button>
    <button type="button" data-metrica="descarte_com2">Descartes Com2</button>
    <button type="button" data-metrica="descarte_com3">Descartes Com3</button>
    <button type="button" data-metrica="pct_com1">%Descartes Com1</button>
    <button type="button" class="active" data-metrica="pct_com2">%Descartes Com2</button>
    <button type="button" data-metrica="pct_com3">%Descartes Com3</button>
  </div>

  <section class="desc-chart-stack" aria-label="Evolução temporal">
    <article class="bi-card desc-chart-card"><div class="bi-card-header"><span class="bi-card-title">Desempenho Mensal</span><span class="desc-chart-sub" id="chart-mensal-sub">—</span></div><div class="desc-chart" id="chart-mensal"></div></article>
    <article class="bi-card desc-chart-card"><div class="bi-card-header"><span class="bi-card-title">Desempenho Semanal</span><span class="desc-chart-sub" id="chart-semanal-sub">—</span></div><div class="desc-chart" id="chart-semanal"></div></article>
    <article class="bi-card desc-chart-card"><div class="bi-card-header"><span class="bi-card-title">Desempenho Diário</span><span class="desc-chart-sub" id="chart-diario-sub">—</span></div><div class="desc-chart" id="chart-diario"></div></article>
  </section>
</main>

<div class="bi-tooltip" id="desc-tip" role="tooltip"></div>
</div>

<script>
const API = '<?= $this->site ?>marketing/descartesDados';
const CAMPANHAS_URL = '<?= $this->site ?>marketing/campanhas';
const FORMS_URL = '<?= $this->site ?>marketing/forms';
const CONJUNTOS_URL = '<?= $this->site ?>marketing/conjuntosAnuncio';
const ANUNCIOS_URL = '<?= $this->site ?>marketing/anuncios';
const YEARS = anosDesde(2025);
const MONTHS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

const yearDropdown = createMultiDropdown('year-wrap', 'year-btn', 'year-list', YEARS, l => l, l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);
const campDropdown = createSearchDropdown('camp-wrap', 'camp-input', 'camp-dropdown', 'camp-clear', 'camp-tags', 'Nenhuma campanha encontrada');
const formDropdown = createSearchDropdown('form-wrap', 'form-input', 'form-dropdown', 'form-clear', 'form-tags', 'Nenhum form encontrado');
const conjDropdown = createSearchDropdown('conj-wrap', 'conj-input', 'conj-dropdown', 'conj-clear', 'conj-tags', 'Nenhum conjunto encontrado');
const anDropdown = createSearchDropdown('an-wrap', 'an-input', 'an-dropdown', 'an-clear', 'an-tags', 'Nenhum anúncio encontrado');

yearDropdown.select([String(new Date().getFullYear())]);
// "Últimos N [dias|semanas|meses]" — um substitui o outro com Ano/Mês/Data
// (ver createRelativePeriod em js/bi-dashboard.js).
const relPeriod = createRelativePeriod({
  numId: 'rel-n', unitId: 'rel-unit',
  conflicts: [[yearDropdown, 'year-list'], [monthDropdown, 'month-list']],
});
fetch(CAMPANHAS_URL, { cache: 'no-store' }).then(r => r.json()).then(v => campDropdown.load(Array.isArray(v) ? v : [])).catch(console.error);
fetch(FORMS_URL, { cache: 'no-store' }).then(r => r.json()).then(v => formDropdown.load(Array.isArray(v) ? v : [])).catch(console.error);
fetch(CONJUNTOS_URL, { cache: 'no-store' }).then(r => r.json()).then(v => conjDropdown.load(Array.isArray(v) ? v : [])).catch(console.error);
fetch(ANUNCIOS_URL, { cache: 'no-store' }).then(r => r.json()).then(v => anDropdown.load(Array.isArray(v) ? v : [])).catch(console.error);

function setText(id, value) { const el = document.getElementById(id); if (el) el.textContent = value; }
function escHtml(value) { return String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function applyTheme(theme) {
  const dark = theme === 'dark';
  document.documentElement.classList.toggle('dark', dark);
  document.getElementById('theme-btn')?.classList.toggle('active', dark);
  if (document.getElementById('theme-icon-moon')) document.getElementById('theme-icon-moon').style.display = dark ? 'none' : '';
  if (document.getElementById('theme-icon-sun')) document.getElementById('theme-icon-sun').style.display = dark ? '' : 'none';
}
function toggleTheme() {
  const theme = localStorage.getItem('theme') === 'dark' ? 'light' : 'dark';
  localStorage.setItem('theme', theme); applyTheme(theme);
}

const tipEl = document.getElementById('desc-tip');
function showTip(e, title, label, value) {
  tipEl.innerHTML = '<div class="bi-tooltip-inner"><div class="bi-tooltip-title">' + escHtml(title) + '</div><div class="bi-tooltip-row"><span class="bi-tooltip-label">' + escHtml(label) + '</span><span class="bi-tooltip-val">' + escHtml(value) + '</span></div></div>';
  tipEl.classList.add('visible');
  let x = e.clientX + 14, y = e.clientY + 14;
  tipEl.style.left = x + 'px'; tipEl.style.top = y + 'px';
  const rect = tipEl.getBoundingClientRect();
  if (rect.right > innerWidth) x = e.clientX - rect.width - 14;
  if (rect.bottom > innerHeight) y = e.clientY - rect.height - 14;
  tipEl.style.left = Math.max(4, x) + 'px'; tipEl.style.top = Math.max(4, y) + 'px';
}
function hideTip() { tipEl.classList.remove('visible'); }

function fillKpis(k) {
  setText('kpi-leads', fNum(k.total_leads));
  setText('kpi-qualificados', fNum(k.qualificados));
  setText('kpi-pct-qualificados', fPct(k.pct_qualificados));
  setText('kpi-cadeiras', fNum(k.cadeira_vendida));
  setText('kpi-cadeiras-sub', fBRL(k.faturamento_cadeiras));
  setText('kpi-consultas-vendidas', fNum(k.consultas_vendidas));
  setText('kpi-consultas-vendidas-sub', fBRL(k.faturamento_consultas));
  setText('kpi-consultas-realizadas', fNum(k.consultas_realizadas));
  setText('kpi-combos', fNum(k.combos_vendidos));
  setText('kpi-combos-sub', fBRL(k.faturamento_combos));
}

function renderReasons(id, rows) {
  const el = document.getElementById(id);
  if (!rows?.length) { el.innerHTML = '<div class="desc-empty">Sem descartes no período</div>'; return; }
  const max = Math.max(...rows.map(r => Number(r.qtd) || 0), 1);
  el.innerHTML = rows.map((r, i) => '<div class="desc-reason-row" data-index="' + i + '">' +
    '<span class="desc-reason-label" title="' + escHtml(r.label) + '">' + escHtml(r.label) + '</span>' +
    '<span class="desc-reason-track"><span class="desc-reason-fill" style="width:' + Math.max(2, (r.qtd / max) * 100).toFixed(1) + '%"></span></span>' +
    '<span class="desc-reason-value">' + fNum(r.qtd) + '</span></div>').join('');
  el.querySelectorAll('.desc-reason-row').forEach(row => {
    const item = rows[Number(row.dataset.index)];
    row.addEventListener('mousemove', e => showTip(e, item.label, 'Quantidade', fNum(item.qtd)));
    row.addEventListener('mouseleave', hideTip);
  });
}

const METRICS = {
  qualificados:   { label: 'Qualificados', format: fNum },
  descarte_com1:  { label: 'Descartes Com1', format: fNum },
  descarte_com2:  { label: 'Descartes Com2', format: fNum },
  descarte_com3:  { label: 'Descartes Com3', format: fNum },
  pct_com1:       { label: '% Descartes Com1', format: fPct },
  pct_com2:       { label: '% Descartes Com2', format: fPct },
  pct_com3:       { label: '% Descartes Com3', format: fPct },
};
let currentMetric = 'pct_com2';
let SERIES = { mensal: [], semanal: [], diario: [] };

function renderChart(id, subId, series) {
  const el = document.getElementById(id), sub = document.getElementById(subId), meta = METRICS[currentMetric];
  sub.textContent = meta.label + ' · ' + (series?.length || 0) + ' pontos';
  if (!series?.length) { el.innerHTML = '<div class="desc-empty">Sem dados no período</div>'; el._state = null; return; }

  const values = series.map(row => Number(row[currentMetric]) || 0);
  const count = values.length, W = 960, H = 160, left = 30, right = 24, top = 22, bottom = 30;
  const width = W - left - right, height = H - top - bottom;
  const max = Math.max(...values, 0.0001) * 1.16;
  const step = count > 1 ? width / (count - 1) : 0;
  const x = i => left + i * step;
  const y = v => top + height - (v / max) * height;
  let svg = '';
  for (let i = 1; i <= 3; i++) {
    const gy = top + height * i / 4;
    svg += '<line class="desc-grid-line" x1="' + left + '" x2="' + (W - right) + '" y1="' + gy + '" y2="' + gy + '"/>';
  }
  const path = values.map((v, i) => (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(v).toFixed(1)).join(' ');
  svg += '<path class="desc-line" d="' + path + '"/>';

  const minLabelGap = 54;
  const axisEvery = Math.max(1, Math.ceil(minLabelGap / Math.max(step, 0.001)));
  values.forEach((value, i) => {
    svg += '<circle class="desc-point" cx="' + x(i).toFixed(1) + '" cy="' + y(value).toFixed(1) + '" r="3.2"/>';
    if (count <= 18 || i % Math.ceil(count / 18) === 0 || i === count - 1) {
      svg += '<text class="desc-value-label" x="' + x(i).toFixed(1) + '" y="' + Math.max(10, y(value) - 8).toFixed(1) + '" text-anchor="middle">' + escHtml(meta.format(value)) + '</text>';
    }
    if (i % axisEvery === 0 || i === count - 1) {
      svg += '<text class="desc-axis-label" x="' + x(i).toFixed(1) + '" y="' + (H - 8) + '" text-anchor="middle">' + escHtml(series[i].label) + '</text>';
    }
  });
  svg += '<rect class="desc-chart-hit" x="0" y="0" width="' + W + '" height="' + H + '"/>';
  el.innerHTML = '<svg viewBox="0 0 ' + W + ' ' + H + '" preserveAspectRatio="none" aria-label="' + escHtml(meta.label) + '">' + svg + '</svg>';
  el._state = { series, values, meta, W, left, step };
}

function renderAllCharts() {
  renderChart('chart-mensal', 'chart-mensal-sub', SERIES.mensal);
  renderChart('chart-semanal', 'chart-semanal-sub', SERIES.semanal);
  renderChart('chart-diario', 'chart-diario-sub', SERIES.diario);
}

['chart-mensal', 'chart-semanal', 'chart-diario'].forEach(id => {
  const el = document.getElementById(id);
  el.addEventListener('mousemove', e => {
    const st = el._state, svg = el.querySelector('svg');
    if (!st || !svg) return;
    const rect = svg.getBoundingClientRect();
    const svgX = (e.clientX - rect.left) * st.W / rect.width;
    const index = Math.max(0, Math.min(st.series.length - 1, st.step ? Math.round((svgX - st.left) / st.step) : 0));
    showTip(e, st.series[index].label, st.meta.label, st.meta.format(st.values[index]));
  });
  el.addEventListener('mouseleave', hideTip);
});

document.getElementById('metric-toggle').addEventListener('click', e => {
  const button = e.target.closest('button[data-metrica]');
  if (!button) return;
  currentMetric = button.dataset.metrica;
  document.querySelectorAll('#metric-toggle button').forEach(item => item.classList.toggle('active', item === button));
  renderAllCharts();
});

function paramsFromFilters() {
  const rel = relPeriod.getRange();
  return {
    year: yearDropdown.getValue(), months: monthDropdown.getValue(),
    campanha: campDropdown.getValue(), form: formDropdown.getValue(),
    conjunto: conjDropdown.getValue(), anuncio: anDropdown.getValue(),
    data_inicio: rel ? rel.start : getDateInput('data-inicio'),
    data_fim:    rel ? rel.end   : getDateInput('data-fim'),
  };
}

// ── Badge do período selecionado ────────────────────────────────
function updateBadge() {
  const badge      = document.getElementById('filter-label-badge');
  const year       = yearDropdown.getValue();
  const months     = monthDropdown.getValue();
  const campanha   = campDropdown.getValue();
  const form       = formDropdown.getValue();
  const conjunto   = conjDropdown.getValue();
  const anuncio    = anDropdown.getValue();
  const rel        = relPeriod.label();
  if (!rel && !year && !months && !campanha && !form && !conjunto && !anuncio) {
    badge.textContent = 'TODOS OS PERÍODOS';
    return;
  }
  const parts = [];
  if (anuncio)  parts.push('Anúncio: ' + anuncio);
  if (conjunto) parts.push('Conjunto: ' + conjunto);
  if (form)     parts.push('Form: ' + form);
  if (campanha) parts.push(campanha);
  if (rel)      parts.push(rel);
  if (year)     parts.push(year);
  if (months) {
    const nums   = months.split(',').map(Number);
    const labels = nums.map(n => MONTHS[n - 1]);
    parts.push(labels.join(', '));
  }
  badge.textContent = parts.join(' · ').toUpperCase();
}

async function loadDashboard() {
  updateBadge();
  const overlay = document.getElementById('loading-overlay'), error = document.getElementById('error-banner');
  overlay.classList.remove('hidden'); document.body.classList.add('loading');
  const query = new URLSearchParams();
  Object.entries(paramsFromFilters()).forEach(([key, value]) => { if (value) query.set(key, value); });
  try {
    const response = await fetch(API + '?' + query);
    const data = await response.json();
    if (!response.ok || data.error) throw new Error(data.error || 'Erro ao carregar os dados.');
    error.classList.remove('visible');
    fillKpis(data.kpis);
    setText('total-com1', fNum(data.totais.com1)); setText('total-com2', fNum(data.totais.com2)); setText('total-com3', fNum(data.totais.com3));
    renderReasons('motivos-com1', data.motivos.com1); renderReasons('motivos-com2', data.motivos.com2); renderReasons('motivos-com3', data.motivos.com3);
    SERIES = data.serie; renderAllCharts();
  } catch (err) {
    error.textContent = 'Erro ao carregar dados: ' + err.message; error.classList.add('visible');
  } finally {
    overlay.classList.add('hidden'); document.body.classList.remove('loading');
  }
}

function clearFilters() {
  relPeriod.reset();
  yearDropdown.reset(); monthDropdown.reset(); campDropdown.reset(); formDropdown.reset(); conjDropdown.reset(); anDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear'); clearDateInput('data-fim', 'data-fim-clear'); loadDashboard();
}

document.getElementById('btn-apply').addEventListener('click', loadDashboard);
document.getElementById('btn-clear').addEventListener('click', clearFilters);
initFiltersToggle({ key: 'marketing-descartes' });
['data-inicio', 'data-fim'].forEach(id => {
  const input = document.getElementById(id), clear = document.getElementById(id + '-clear');
  input?.addEventListener('change', () => clear?.classList.toggle('visible', !!input.value));
  clear?.addEventListener('click', () => clearDateInput(id, id + '-clear'));
});
applyTheme(localStorage.getItem('theme') || 'light');
loadDashboard();
</script>
