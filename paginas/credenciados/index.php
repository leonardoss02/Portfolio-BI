<div class="dash-credenciados">

<div id="loading-overlay" class="hidden" aria-hidden="true"></div>
<div id="chart-tooltip" class="cred-chart-tooltip" role="tooltip" aria-hidden="true"></div>

<header class="header">
  <div class="header-left">
    <div class="logo" aria-hidden="true">CR</div>
    <div>
      <div class="header-title">Dashboard Credenciados</div>
      <div class="header-sub">Funil comercial · Conversões · Motivos de descarte</div>
    </div>
    <button class="btn-theme" id="theme-btn" type="button" onclick="toggleTheme()" aria-label="Alternar tema claro ou escuro" title="Alternar tema">
      <svg id="theme-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      <svg id="theme-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none" aria-hidden="true">
        <circle cx="12" cy="12" r="5"/>
        <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
        <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
      </svg>
    </button>
  </div>
  <div class="header-right">
    <div class="cred-header-meta">
      <div class="header-badge" id="filter-label-badge">TODOS OS PERÍODOS</div>
      <div class="last-updated" id="last-updated">Aguardando atualização</div>
    </div>
    <a class="btn-tab-switch" href="<?= $this->site ?>credenciados/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário &rarr;</a>
  </div>
</header>

<section class="filters is-collapsible" aria-label="Filtros do dashboard">
  <button class="filters-toggle" type="button" aria-expanded="true" aria-controls="filters-fields">
    <span>Filtros</span>
    <span class="dd-caret filters-toggle-caret" aria-hidden="true">&#x25BE;</span>
  </button>

  <div class="filters-fields" id="filters-fields">
    <div class="filter-pair">
      <span class="filter-label">Ano</span>
      <div class="multi-dd-wrap" id="year-wrap">
        <button class="multi-dd-btn" id="year-btn" type="button">Todos <span class="dd-caret" aria-hidden="true">▾</span></button>
        <div class="multi-dd-list" id="year-list"></div>
      </div>
    </div>

    <div class="filter-pair">
      <span class="filter-label">Mês</span>
      <div class="multi-dd-wrap" id="month-wrap">
        <button class="multi-dd-btn" id="month-btn" type="button">Todos <span class="dd-caret" aria-hidden="true">▾</span></button>
        <div class="multi-dd-list" id="month-list"></div>
      </div>
    </div>

    <div class="filter-sep" aria-hidden="true"></div>

    <div class="filter-pair">
      <span class="filter-label">Data</span>
      <div class="date-range-group">
        <div class="date-input-wrap">
          <input type="date" id="data-inicio" aria-label="Data inicial">
          <button class="date-clear-btn" id="data-inicio-clear" type="button" aria-label="Limpar data inicial">&#x2715;</button>
        </div>
        <span class="date-sep-label">até</span>
        <div class="date-input-wrap">
          <input type="date" id="data-fim" aria-label="Data final">
          <button class="date-clear-btn" id="data-fim-clear" type="button" aria-label="Limpar data final">&#x2715;</button>
        </div>
      </div>
    </div>

    <button class="btn-apply" id="btn-apply" type="button">Aplicar</button>
    <button class="btn-clear" id="btn-clear" type="button">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'credenciados' });</script>

<div id="error-banner" role="alert" aria-live="assertive"></div>
<div class="cred-sr-only" id="dashboard-status" role="status" aria-live="polite"></div>

<div class="main" id="dashboard-main">
  <section class="cred-kpi-grid" aria-label="Indicadores do funil">
    <article class="cred-kpi cred-kpi-leads">
      <span class="cred-kpi-label">Total de Leads</span>
      <strong class="cred-kpi-value" id="k-total-leads">—</strong>
      <span class="cred-kpi-sub">Base afiliada no período</span>
    </article>
    <article class="cred-kpi cred-kpi-qualified">
      <span class="cred-kpi-label">Qualificados</span>
      <strong class="cred-kpi-value" id="k-qualificados">—</strong>
      <span class="cred-kpi-sub" id="p-qualificados">— dos leads</span>
    </article>
    <article class="cred-kpi cred-kpi-chair">
      <span class="cred-kpi-label">Cadeiras Vendidas</span>
      <strong class="cred-kpi-value" id="k-cadeiras">—</strong>
      <span class="cred-kpi-sub" id="p-cadeiras">— dos qualificados</span>
    </article>
    <article class="cred-kpi cred-kpi-sold">
      <span class="cred-kpi-label">Consultas Vendidas</span>
      <strong class="cred-kpi-value" id="k-consultas-vendidas">—</strong>
      <span class="cred-kpi-sub" id="p-consultas-vendidas">— dos qualificados</span>
    </article>
    <article class="cred-kpi cred-kpi-realized">
      <span class="cred-kpi-label">Consultas Realizadas</span>
      <strong class="cred-kpi-value" id="k-consultas-realizadas">—</strong>
      <span class="cred-kpi-sub" id="p-consultas-realizadas">— das vendidas</span>
    </article>
    <article class="cred-kpi cred-kpi-fp">
      <span class="cred-kpi-label">FP Realizado</span>
      <strong class="cred-kpi-value" id="k-fp-realizado">—</strong>
      <span class="cred-kpi-sub">Quantidade no período</span>
    </article>
    <article class="cred-kpi cred-kpi-paid">
      <span class="cred-kpi-label">Taxa Paga</span>
      <strong class="cred-kpi-value" id="k-taxa-paga">—</strong>
      <span class="cred-kpi-sub">Quantidade no período</span>
    </article>
    <article class="cred-kpi cred-kpi-combo">
      <span class="cred-kpi-label">Combo Vendido</span>
      <strong class="cred-kpi-value" id="k-combo">—</strong>
      <span class="cred-kpi-sub" id="p-combo">— das realizadas</span>
    </article>
    <article class="cred-kpi cred-kpi-cancel">
      <span class="cred-kpi-label">Distrato</span>
      <strong class="cred-kpi-value" id="k-distrato">—</strong>
      <span class="cred-kpi-sub">Quantidade no período</span>
    </article>
  </section>

  <section class="bi-card cred-chart-card" aria-labelledby="conversion-title">
    <div class="bi-card-header cred-chart-header">
      <div>
        <div class="bi-card-title" id="conversion-title">Conversão por Período</div>
        <div class="bi-card-sub">Volumes no eixo esquerdo · conversões no eixo direito</div>
      </div>
      <div class="gran-toggle" role="group" aria-label="Granularidade do gráfico">
        <button class="gran-btn active" type="button" data-gran="mes" aria-pressed="true">Mês-Ano</button>
        <button class="gran-btn" type="button" data-gran="dia_semana" aria-pressed="false">Dia da Semana</button>
        <button class="gran-btn" type="button" data-gran="diaria" aria-pressed="false">Diária</button>
        <button class="gran-btn" type="button" data-gran="semana_ano" aria-pressed="false">Semana do Ano</button>
      </div>
    </div>

    <div class="cred-chart-legend" aria-label="Legenda do gráfico">
      <span><i class="cred-legend-bar is-qualified" aria-hidden="true"></i>Qualificados</span>
      <span><i class="cred-legend-bar is-consult" aria-hidden="true"></i>Consultas</span>
      <span><i class="cred-legend-bar is-combo" aria-hidden="true"></i>Combos</span>
      <span><i class="cred-legend-line is-conv-consult" aria-hidden="true"></i>% Conv. Consultas</span>
      <span><i class="cred-legend-line is-conv-combo" aria-hidden="true"></i>% Conv. Combo</span>
    </div>

    <div class="cred-chart-body" id="chart-body" aria-busy="true">
      <div class="cred-empty-state">Carregando gráfico…</div>
    </div>
    <p class="cred-sr-only" id="chart-description"></p>
  </section>

  <div class="cred-bottom-grid">
    <section class="bi-card cred-table-card" aria-labelledby="records-title">
      <div class="bi-card-header cred-table-header">
        <div>
          <div class="bi-card-title" id="records-title">Registros de Credenciados</div>
          <div class="bi-card-sub" id="records-count">Aguardando dados</div>
        </div>
        <div class="cred-table-actions">
          <label class="cred-search">
            <span class="cred-sr-only">Buscar nos registros</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input id="records-search" type="search" placeholder="Buscar ID, telefone, fase…" autocomplete="off">
          </label>
          <div class="exp-menu-wrap" id="records-export-wrap">
            <button class="exp-menu-btn" id="records-export-btn" type="button">Exportar ▾</button>
            <div class="exp-menu-list" id="records-export-list">
              <div class="exp-menu-item" data-fmt="csv">Exportar CSV</div>
              <div class="exp-menu-item" data-fmt="excel">Exportar Excel</div>
            </div>
          </div>
        </div>
      </div>

      <div class="tbl-wrap cred-table-wrap" id="records-table-wrap" aria-busy="true">
        <table class="bi-table cred-table">
          <thead>
            <tr>
              <th data-key="id"><button type="button" class="cred-sort" data-sort="id">ID <span aria-hidden="true"></span></button></th>
              <th data-key="nome"><button type="button" class="cred-sort" data-sort="nome">Nome <span aria-hidden="true"></span></button></th>
              <th data-key="telefone"><button type="button" class="cred-sort" data-sort="telefone">Telefone <span aria-hidden="true"></span></button></th>
              <th data-key="data_formulario" aria-sort="descending"><button type="button" class="cred-sort" data-sort="data_formulario">Data Formulário <span aria-hidden="true">▼</span></button></th>
              <th data-key="fase"><button type="button" class="cred-sort" data-sort="fase">Fase <span aria-hidden="true"></span></button></th>
              <th data-key="profissao"><button type="button" class="cred-sort" data-sort="profissao">Profissão <span aria-hidden="true"></span></button></th>
              <th data-key="tempo_experiencia"><button type="button" class="cred-sort" data-sort="tempo_experiencia">Tempo Experiência <span aria-hidden="true"></span></button></th>
            </tr>
          </thead>
          <tbody id="records-body">
            <tr><td colspan="7"><div class="cred-empty-state">Carregando registros…</div></td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="bi-card cred-discard-card" aria-labelledby="discard-title">
      <div class="bi-card-header">
        <div>
          <div class="bi-card-title cred-discard-title" id="discard-title">Motivos de Descarte</div>
          <div class="bi-card-sub">Top 10 · demais agrupados em Outros</div>
        </div>
      </div>
      <div class="cred-discard-list" id="discard-list" aria-busy="true">
        <div class="cred-empty-state">Carregando motivos…</div>
      </div>
    </section>
  </div>
</div>
</div>

<script>
const CRED_API = '<?= $this->site ?>credenciados/dados';
const CRED_GRAPH_API = '<?= $this->site ?>credenciados/grafico';
const CRED_RECORDS_API = '<?= $this->site ?>credenciados/registros';
const CRED_MONTHS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
const CRED_NOW_YEAR = new Date().getFullYear();
const CRED_YEARS = Array.from({ length: Math.max(1, CRED_NOW_YEAR - 2023) }, (_, i) => String(2024 + i));

const credYearDropdown = createMultiDropdown('year-wrap', 'year-btn', 'year-list', CRED_YEARS, value => value, value => value);
const credMonthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', CRED_MONTHS, value => value, (value, index) => index + 1);
credYearDropdown.select([String(CRED_NOW_YEAR)]);

let credFilters = { year: String(CRED_NOW_YEAR), months: null, data_inicio: null, data_fim: null };
let credGranularity = 'mes';
let credAllRecords = [];
let credRecordsSort = { key: 'data_formulario', dir: 'desc' };
let credRecordsSearch = '';
let credLoadSequence = 0;
let credSearchTimer = null;
let credResizeTimer = null;
let credLastChartData = [];

const CRED_RECORD_COLS = [
  { k: 'id', label: 'ID' },
  { k: 'nome', label: 'Nome' },
  { k: 'telefone', label: 'Telefone' },
  { k: 'data_formulario', label: 'Data Formulário' },
  { k: 'fase', label: 'Fase' },
  { k: 'profissao', label: 'Profissão' },
  { k: 'tempo_experiencia', label: 'Tempo Experiência' }
];

const CRED_BARS = [
  { key: 'qualificados', label: 'Qualificados', color: '#5b8fd1' },
  { key: 'consultas', label: 'Consultas', color: '#173f68' },
  { key: 'combos', label: 'Combos', color: '#e87935' }
];
const CRED_LINES = [
  { key: 'conv_consultas', label: '% Conv. Consultas', color: '#22a663', dash: '' },
  { key: 'conv_combo', label: '% Conv. Combo', color: '#c84a9c', dash: '6 4' }
];

function credRefreshSeriesColors() {
  const root = document.querySelector('.dash-credenciados');
  if (!root) return;
  const styles = getComputedStyle(root);
  const colors = [
    styles.getPropertyValue('--cred-qualified').trim(),
    styles.getPropertyValue('--cred-consult').trim(),
    styles.getPropertyValue('--cred-combo').trim(),
    styles.getPropertyValue('--cred-conv-consult').trim(),
    styles.getPropertyValue('--cred-conv-combo').trim()
  ];
  CRED_BARS.forEach((series, index) => { if (colors[index]) series.color = colors[index]; });
  CRED_LINES.forEach((series, index) => { if (colors[index + 3]) series.color = colors[index + 3]; });
}

function credEsc(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function credPct(value, decimals = 2) {
  return ((Number(value) || 0) * 100).toLocaleString('pt-BR', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals
  }) + '%';
}

function credSetText(id, value) {
  const element = document.getElementById(id);
  if (element) element.textContent = value;
}

function credBaseParams(includeGranularity = false) {
  const params = new URLSearchParams();
  Object.entries(credFilters).forEach(([key, value]) => {
    if (value) params.set(key, value);
  });
  if (includeGranularity) params.set('granularidade', credGranularity);
  return params;
}

async function credFetchJson(url, params) {
  const query = params && [...params].length ? '?' + params.toString() : '';
  const response = await fetch(url + query, { cache: 'no-store' });
  const data = await response.json();
  if (!response.ok || data.error) throw new Error(data.error || `HTTP ${response.status}`);
  return data;
}

function credReadFilters() {
  const dataInicio = getDateInput('data-inicio');
  const dataFim = getDateInput('data-fim');
  const hasDateRange = !!(dataInicio || dataFim);
  return {
    year: hasDateRange ? null : credYearDropdown.getValue(),
    months: hasDateRange ? null : credMonthDropdown.getValue(),
    data_inicio: dataInicio,
    data_fim: dataFim
  };
}

function credUpdateFilterBadge() {
  const parts = [];
  if (credFilters.year) parts.push(credFilters.year.split(',').join(', '));
  if (credFilters.months) {
    const labels = credFilters.months.split(',').map(month => CRED_MONTHS[Number(month) - 1]).filter(Boolean);
    if (labels.length) parts.push(labels.join(', '));
  }
  if (credFilters.data_inicio || credFilters.data_fim) {
    parts.push([credFilters.data_inicio || 'início', credFilters.data_fim || 'hoje'].join(' → '));
  }
  credSetText('filter-label-badge', parts.length ? parts.join(' · ').toUpperCase() : 'TODOS OS PERÍODOS');
}

function credRenderKpis(kpis) {
  credSetText('k-total-leads', fNum(kpis.total_leads || 0));
  credSetText('k-qualificados', fNum(kpis.qualificados || 0));
  credSetText('k-cadeiras', fNum(kpis.cadeiras_vendidas || 0));
  credSetText('k-consultas-vendidas', fNum(kpis.consultas_vendidas || 0));
  credSetText('k-consultas-realizadas', fNum(kpis.consultas_realizadas || 0));
  credSetText('k-fp-realizado', fNum(kpis.fp_realizado || 0));
  credSetText('k-taxa-paga', fNum(kpis.taxa_paga || 0));
  credSetText('k-combo', fNum(kpis.combo_vendido || 0));
  credSetText('k-distrato', fNum(kpis.distrato || 0));

  credSetText('p-qualificados', credPct(kpis.conv_qualificados) + ' dos leads');
  credSetText('p-cadeiras', credPct(kpis.conv_cadeiras) + ' dos qualificados');
  credSetText('p-consultas-vendidas', credPct(kpis.conv_consultas_vendidas) + ' dos qualificados');
  credSetText('p-consultas-realizadas', credPct(kpis.conv_consultas_realizadas) + ' das vendidas');
  credSetText('p-combo', credPct(kpis.conv_combo) + ' das realizadas');
}

function credRenderMotivos(motivos) {
  const container = document.getElementById('discard-list');
  container.setAttribute('aria-busy', 'false');
  if (!motivos || !motivos.length) {
    container.innerHTML = '<div class="cred-empty-state">Nenhum motivo de descarte no período.</div>';
    return;
  }
  const max = Math.max(1, ...motivos.map(item => Number(item.qtd) || 0));
  container.innerHTML = motivos.map(item => {
    const width = Math.max(4, ((Number(item.qtd) || 0) / max) * 100);
    return `
      <div class="cred-discard-row">
        <div class="cred-discard-meta">
          <span class="cred-discard-label" title="${credEsc(item.motivo)}">${credEsc(item.motivo)}</span>
          <span class="cred-discard-value">${fNum(item.qtd)} <small>${credPct(item.pct, 1)}</small></span>
        </div>
        <div class="cred-discard-track" aria-hidden="true"><span style="width:${width.toFixed(2)}%"></span></div>
      </div>`;
  }).join('');
}

function credVisibleRecords() {
  let rows = credAllRecords;
  if (credRecordsSearch) {
    const q = credRecordsSearch.toLowerCase();
    rows = rows.filter(row => CRED_RECORD_COLS.some(col => String(row[col.k] ?? '').toLowerCase().includes(q)));
  }
  const { key, dir } = credRecordsSort;
  rows = [...rows].sort((a, b) => {
    const cmp = String(a[key] ?? '').localeCompare(String(b[key] ?? ''), 'pt-BR', { numeric: true });
    return dir === 'asc' ? cmp : -cmp;
  });
  return rows;
}

function credRenderRecordsView() {
  const tbody = document.getElementById('records-body');
  const wrap = document.getElementById('records-table-wrap');
  const rows = credVisibleRecords();
  wrap.setAttribute('aria-busy', 'false');

  credSetText('records-count', `${fNum(rows.length)} registro${rows.length === 1 ? '' : 's'}`);

  document.querySelectorAll('.cred-table th').forEach(th => {
    const key = th.dataset.key;
    const indicator = th.querySelector('.cred-sort span');
    const active = key === credRecordsSort.key;
    th.removeAttribute('aria-sort');
    if (indicator) indicator.textContent = active ? (credRecordsSort.dir === 'asc' ? '▲' : '▼') : '';
    if (active) th.setAttribute('aria-sort', credRecordsSort.dir === 'asc' ? 'ascending' : 'descending');
  });

  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="7"><div class="cred-empty-state">Nenhum registro encontrado.</div></td></tr>';
    return;
  }
  tbody.innerHTML = rows.map(row => `
    <tr>
      <td>${credEsc(row.id || '—')}</td>
      <td>${credEsc(row.nome || '—')}</td>
      <td>${credEsc(row.telefone || '—')}</td>
      <td class="cred-nowrap">${credEsc(row.data_formulario || '—')}</td>
      <td>${credEsc(row.fase || '—')}</td>
      <td>${credEsc(row.profissao || '—')}</td>
      <td>${credEsc(row.tempo_experiencia || '—')}</td>
    </tr>`).join('');
}

createExportMenu('records-export-wrap', 'records-export-btn', 'records-export-list', fmt => {
  const rows = credVisibleRecords();
  const nome = 'credenciados-' + new Date().toISOString().slice(0, 10);
  if (fmt === 'csv') exportRowsAsCsv(CRED_RECORD_COLS, rows, nome);
  else               exportRowsAsExcel(CRED_RECORD_COLS, rows, nome);
});

function credTooltipHtml(point) {
  return `
    <div class="cred-tooltip-title">${credEsc(point.label)}</div>
    ${CRED_BARS.map(series => `<div class="cred-tooltip-row"><i style="background:${series.color}"></i><span>${series.label}</span><strong>${fNum(point[series.key] || 0)}</strong></div>`).join('')}
    ${CRED_LINES.map(series => `<div class="cred-tooltip-row"><i class="is-line" style="background:${series.color}"></i><span>${series.label}</span><strong>${credPct(point[series.key], 1)}</strong></div>`).join('')}`;
}

function credPositionTooltip(event, element) {
  const tooltip = document.getElementById('chart-tooltip');
  const rect = element ? element.getBoundingClientRect() : null;
  const anchorX = event && Number.isFinite(event.clientX) && event.clientX > 0 ? event.clientX : (rect ? rect.left + rect.width / 2 : window.innerWidth / 2);
  const anchorY = event && Number.isFinite(event.clientY) && event.clientY > 0 ? event.clientY : (rect ? rect.top : window.innerHeight / 2);
  const margin = 14;
  const width = tooltip.offsetWidth || 210;
  const height = tooltip.offsetHeight || 170;
  let left = anchorX + margin;
  let top = anchorY - height - margin;
  if (left + width > window.innerWidth - margin) left = anchorX - width - margin;
  if (top < margin) top = anchorY + margin;
  tooltip.style.left = Math.max(margin, left) + 'px';
  tooltip.style.top = Math.max(margin, top) + 'px';
}

function credShowTooltip(event, point, element) {
  const tooltip = document.getElementById('chart-tooltip');
  tooltip.innerHTML = credTooltipHtml(point);
  tooltip.setAttribute('aria-hidden', 'false');
  tooltip.classList.add('visible');
  credPositionTooltip(event, element);
}

function credHideTooltip() {
  const tooltip = document.getElementById('chart-tooltip');
  tooltip.classList.remove('visible');
  tooltip.setAttribute('aria-hidden', 'true');
}

function credRenderChart(data) {
  const body = document.getElementById('chart-body');
  credLastChartData = Array.isArray(data) ? data : [];
  credRefreshSeriesColors();
  body.setAttribute('aria-busy', 'false');
  if (!data || !data.length) {
    body.innerHTML = '<div class="cred-empty-state cred-chart-empty">Sem dados para o período.</div>';
    credSetText('chart-description', 'O gráfico não possui dados para o período selecionado.');
    return;
  }

  const W = Math.max(340, Math.round(body.clientWidth || 1000));
  const H = Math.max(180, Math.round(body.clientHeight || 260));
  const pad = { left: 36, right: 46, top: 30, bottom: 54 };
  const plotW = W - pad.left - pad.right;
  const plotH = H - pad.top - pad.bottom;
  const n = data.length;
  const slot = plotW / Math.max(1, n);
  const barWidth = Math.max(2, Math.min(28, slot * 0.22));
  const barGap = Math.max(1, Math.min(6, slot * 0.05));
  const groupWidth = CRED_BARS.length * barWidth + (CRED_BARS.length - 1) * barGap;
  const labelStep = n <= 12 ? 1 : n <= 24 ? 2 : n <= 52 ? 4 : Math.ceil(n / 12);

  const maxVolumeRaw = Math.max(1, ...data.flatMap(point => CRED_BARS.map(series => Number(point[series.key]) || 0)));
  const maxPercentRaw = Math.max(0.05, ...data.flatMap(point => CRED_LINES.map(series => Number(point[series.key]) || 0)));
  const maxVolume = maxVolumeRaw * 1.18;
  const maxPercent = maxPercentRaw * 1.18;
  const yVolume = value => pad.top + plotH - ((Number(value) || 0) / maxVolume) * plotH;
  const yPercent = value => pad.top + plotH - ((Number(value) || 0) / maxPercent) * plotH;
  const centerX = index => pad.left + slot * index + slot / 2;

  const grid = [0, .25, .5, .75, 1].map(fraction => {
    const y = pad.top + plotH * fraction;
    const volume = Math.round(maxVolume * (1 - fraction));
    const percent = maxPercent * (1 - fraction);
    return `
      <line class="cred-chart-grid" x1="${pad.left}" y1="${y}" x2="${pad.left + plotW}" y2="${y}"/>
      <text class="cred-chart-axis" x="${pad.left - 7}" y="${y + 4}" text-anchor="end">${fNum(volume)}</text>
      <text class="cred-chart-axis is-percent" x="${pad.left + plotW + 7}" y="${y + 4}" text-anchor="start">${credPct(percent, 0)}</text>`;
  }).join('');

  let bars = '';
  const linePoints = CRED_LINES.map(() => []);
  const hitAreas = [];

  data.forEach((point, index) => {
    const cx = centerX(index);
    const startX = cx - groupWidth / 2;
    const printLabel = index % labelStep === 0 || index === n - 1;

    CRED_BARS.forEach((series, seriesIndex) => {
      const value = Number(point[series.key]) || 0;
      const x = startX + seriesIndex * (barWidth + barGap);
      const y = yVolume(value);
      const height = Math.max(0, pad.top + plotH - y);
      bars += `<rect x="${x.toFixed(2)}" y="${y.toFixed(2)}" width="${barWidth.toFixed(2)}" height="${height.toFixed(2)}" rx="2" fill="${series.color}"/>`;
      if (printLabel && value > 0 && slot >= 24) {
        bars += `<text class="cred-chart-value" x="${(x + barWidth / 2).toFixed(2)}" y="${Math.max(12, y - 5).toFixed(2)}" text-anchor="middle" fill="${series.color}">${fNum(value)}</text>`;
      }
    });

    if (printLabel) {
      bars += `<text class="cred-chart-xlabel" x="${cx.toFixed(2)}" y="${H - 18}" text-anchor="middle">${credEsc(point.label)}</text>`;
    }

    CRED_LINES.forEach((series, seriesIndex) => {
      linePoints[seriesIndex].push({ x: cx, y: yPercent(point[series.key]), value: Number(point[series.key]) || 0, printLabel });
    });

    const accessible = `${point.label}: ${fNum(point.qualificados)} qualificados, ${fNum(point.consultas)} consultas, ${fNum(point.combos)} combos, conversão de consultas ${credPct(point.conv_consultas, 1)}, conversão de combo ${credPct(point.conv_combo, 1)}.`;
    hitAreas.push(`<rect class="cred-chart-hit" data-index="${index}" x="${(cx - slot / 2).toFixed(2)}" y="${pad.top}" width="${Math.max(3, slot).toFixed(2)}" height="${plotH}" fill="transparent" tabindex="0" role="img" aria-label="${credEsc(accessible)}"/>`);
  });

  const lines = CRED_LINES.map((series, seriesIndex) => {
    const points = linePoints[seriesIndex];
    const path = points.map((point, index) => `${index ? 'L' : 'M'}${point.x.toFixed(2)},${point.y.toFixed(2)}`).join(' ');
    const dots = points.map(point => `<circle cx="${point.x.toFixed(2)}" cy="${point.y.toFixed(2)}" r="3.2" fill="${series.color}" class="cred-chart-dot"/>`).join('');
    const labels = points.filter(point => point.printLabel && point.value > 0 && slot >= 28).map(point => {
      const offset = seriesIndex === 0 ? -9 : 14;
      return `<text class="cred-chart-line-value" x="${point.x.toFixed(2)}" y="${(point.y + offset).toFixed(2)}" text-anchor="middle" fill="${series.color}">${credPct(point.value, 1)}</text>`;
    }).join('');
    return `<path d="${path}" fill="none" stroke="${series.color}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="${series.dash}" class="cred-chart-line"/>${dots}${labels}`;
  }).join('');

  body.innerHTML = `
    <svg class="cred-chart-svg" viewBox="0 0 ${W} ${H}" role="img" aria-labelledby="conversion-title chart-description" preserveAspectRatio="xMidYMid meet">
      ${grid}${bars}${lines}${hitAreas.join('')}
    </svg>`;

  credSetText('chart-description', data.map(point => `${point.label}: ${point.qualificados} qualificados, ${point.consultas} consultas, ${point.combos} combos.`).join(' '));

  body.querySelectorAll('.cred-chart-hit').forEach(hit => {
    const point = data[Number(hit.dataset.index)];
    hit.addEventListener('mouseenter', event => credShowTooltip(event, point, hit));
    hit.addEventListener('mousemove', event => credPositionTooltip(event, hit));
    hit.addEventListener('mouseleave', credHideTooltip);
    hit.addEventListener('focus', event => credShowTooltip(event, point, hit));
    hit.addEventListener('blur', credHideTooltip);
  });
}

function credRenderUpdated(meta) {
  const cached = meta && meta.cached ? ' · cache' : '';
  credSetText('last-updated', `Atualizado em ${meta?.generated || '—'}${cached}`);
}

function credShowError(error) {
  const banner = document.getElementById('error-banner');
  banner.textContent = 'Não foi possível carregar o dashboard: ' + error.message;
  banner.classList.add('visible');
}

function credClearError() {
  const banner = document.getElementById('error-banner');
  banner.classList.remove('visible');
  banner.textContent = '';
}

async function credLoadAll() {
  const sequence = ++credLoadSequence;
  const overlay = document.getElementById('loading-overlay');
  overlay.classList.remove('hidden');
  document.body.classList.add('loading');
  credClearError();
  credSetText('dashboard-status', 'Carregando dados do dashboard.');

  const dataParams = credBaseParams(false);
  const graphParams = credBaseParams(true);
  const recordsParams = credBaseParams(false);

  try {
    const [data, graph, records] = await Promise.all([
      credFetchJson(CRED_API, dataParams),
      credFetchJson(CRED_GRAPH_API, graphParams),
      credFetchJson(CRED_RECORDS_API, recordsParams)
    ]);
    if (sequence !== credLoadSequence) return;
    credRenderKpis(data.kpis || {});
    credRenderMotivos(data.motivos || []);
    credRenderChart(graph.serie || []);
    credAllRecords = records.registros || [];
    credRenderRecordsView();
    credRenderUpdated(data.meta || {});
    credSetText('dashboard-status', 'Dashboard atualizado com sucesso.');
  } catch (error) {
    if (sequence === credLoadSequence) credShowError(error);
  } finally {
    if (sequence === credLoadSequence) {
      overlay.classList.add('hidden');
      document.body.classList.remove('loading');
    }
  }
}

async function credLoadChartOnly() {
  const body = document.getElementById('chart-body');
  body.setAttribute('aria-busy', 'true');
  body.innerHTML = '<div class="cred-empty-state cred-chart-empty">Atualizando gráfico…</div>';
  try {
    const data = await credFetchJson(CRED_GRAPH_API, credBaseParams(true));
    credRenderChart(data.serie || []);
  } catch (error) {
    body.setAttribute('aria-busy', 'false');
    body.innerHTML = '<div class="cred-empty-state cred-chart-empty">Erro ao carregar o gráfico.</div>';
    credShowError(error);
  }
}

function credApplyFilters() {
  credFilters = credReadFilters();
  credUpdateFilterBadge();
  credLoadAll();
}

function credClearFilters() {
  credYearDropdown.reset();
  credMonthDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim', 'data-fim-clear');
  document.getElementById('records-search').value = '';
  credRecordsSearch = '';
  credRecordsSort = { key: 'data_formulario', dir: 'desc' };
  credGranularity = 'mes';
  document.querySelectorAll('.gran-btn[data-gran]').forEach(button => {
    const active = button.dataset.gran === 'mes';
    button.classList.toggle('active', active);
    button.setAttribute('aria-pressed', active ? 'true' : 'false');
  });
  credFilters = credReadFilters();
  credUpdateFilterBadge();
  credLoadAll();
}

document.getElementById('btn-apply').addEventListener('click', credApplyFilters);
document.getElementById('btn-clear').addEventListener('click', credClearFilters);

['data-inicio', 'data-fim'].forEach(id => {
  const input = document.getElementById(id);
  const clear = document.getElementById(id + '-clear');
  input.addEventListener('change', () => clear.classList.toggle('visible', !!input.value));
  clear.addEventListener('click', () => clearDateInput(id, id + '-clear'));
});

document.querySelectorAll('.gran-btn[data-gran]').forEach(button => {
  button.addEventListener('click', () => {
    if (button.dataset.gran === credGranularity) return;
    credGranularity = button.dataset.gran;
    document.querySelectorAll('.gran-btn[data-gran]').forEach(item => {
      const active = item === button;
      item.classList.toggle('active', active);
      item.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    credLoadChartOnly();
  });
});

document.querySelectorAll('.cred-sort').forEach(button => {
  button.addEventListener('click', () => {
    const sort = button.dataset.sort;
    if (credRecordsSort.key === sort) {
      credRecordsSort.dir = credRecordsSort.dir === 'asc' ? 'desc' : 'asc';
    } else {
      credRecordsSort = { key: sort, dir: sort === 'data_formulario' ? 'desc' : 'asc' };
    }
    credRenderRecordsView();
  });
});

document.getElementById('records-search').addEventListener('input', event => {
  clearTimeout(credSearchTimer);
  credSearchTimer = setTimeout(() => {
    credRecordsSearch = event.target.value.trim();
    credRenderRecordsView();
  }, 200);
});

window.addEventListener('resize', () => {
  credHideTooltip();
  clearTimeout(credResizeTimer);
  credResizeTimer = setTimeout(() => {
    if (credLastChartData.length) credRenderChart(credLastChartData);
  }, 120);
});

new MutationObserver(() => {
  if (credLastChartData.length) credRenderChart(credLastChartData);
}).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

credUpdateFilterBadge();
credLoadAll();
</script>
