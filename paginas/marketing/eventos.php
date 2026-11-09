<div class="dash-marketing-eventos">

<!-- ══════════════════════════════════════════════════════════════
     Marketing — 3ª aba "Eventos": só o universo Evento (prefixo de
     formulário OTR/INA/OTW/WEB, sempre ativo no backend — não é filtro
     alternável aqui). Cabeçalho de KPIs + Resumo Financeiro herdados
     literalmente da 1ª view; abaixo, Representatividade de Combos/
     Consultas e Investimento por formulário à esquerda, Performance por
     Formulário (tabela) + Desempenho por Formulário (toggle de métrica) à
     direita — layout validado em marketing-eventos-mock.html (apagado
     após aprovação). Todo o CSS vive em .dash-marketing-eventos de
     css/bi-theme.css (seção 20i).
     ══════════════════════════════════════════════════════════════ -->

<div id="loading-overlay" class="hidden"></div>

<header class="header">
  <div class="header-left">
    <div class="logo" aria-hidden="true">MR</div>
    <div>
      <div class="header-title">Marketing &mdash; Acme EUA</div>
      <div class="header-sub">Leads &middot; Funil &middot; Campanhas &middot; Anúncios</div>
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
    <div class="tab-switch">
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Visão Geral</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/comparativo<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Comparativo</a>
      <a class="tab-switch-btn active" href="<?= $this->site ?>marketing/eventos<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Eventos</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/desempenho<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Desempenho</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/descartes<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Descartes</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/classificacao<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Classificação</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
    </div>
  </div>
</header>

<!-- ── FILTROS ─────────────────────────────────────────────────── -->
<section class="filters is-collapsible" aria-label="Filtros da aba Eventos">
  <button class="filters-toggle" type="button" aria-expanded="true" aria-controls="filters-fields">
    <span>Filtros</span>
    <span class="dd-caret filters-toggle-caret">&#x25BE;</span>
  </button>
  <div class="filters-fields" id="filters-fields">
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
  <button class="btn-clear" onclick="clearFilters()">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'marketing-eventos' });</script>

<!-- ── ERRO ────────────────────────────────────────────────── -->
<div id="error-banner" role="alert"></div>

<!-- LINHA 1 — KPIs (herdado literalmente da 1ª view) -->
<div class="kpi-row kpi-top">
  <div class="kpi-chip">
    <span class="kpi-chip-label">Total de Leads</span>
    <span class="kpi-chip-val" id="ev-kpi-leads">—</span>
  </div>
  <div class="kpi-chip">
    <span class="kpi-chip-label">Qualificados</span>
    <span class="kpi-chip-val" id="ev-kpi-qualificados">—</span>
  </div>
  <div class="kpi-chip consulta">
    <span class="kpi-chip-label">Oportunidades</span>
    <span class="kpi-chip-val" id="ev-kpi-oportunidades">—</span>
  </div>
  <div class="kpi-chip evento">
    <span class="kpi-chip-label">Cadeiras Vendidas</span>
    <span class="kpi-chip-val" id="ev-kpi-cadeiras">—</span>
  </div>
  <div class="kpi-chip consulta">
    <span class="kpi-chip-label">Consultas Vendidas</span>
    <span class="kpi-chip-val" id="ev-kpi-consultas-vendidas">—</span>
  </div>
  <div class="kpi-chip consulta">
    <span class="kpi-chip-label">Consultas Realizadas</span>
    <span class="kpi-chip-val" id="ev-kpi-consultas-realizadas">—</span>
  </div>
  <div class="kpi-chip fecho">
    <span class="kpi-chip-label">Combos Vendidos</span>
    <span class="kpi-chip-val" id="ev-kpi-combos">—</span>
  </div>
</div>

<div class="ev-split">

  <!-- Resumo Financeiro (herdado literalmente da 1ª view) -->
  <div class="bi-card fin-card ev-fin">
    <div class="bi-card-header"><span class="bi-card-title">Resumo Financeiro</span></div>
    <div class="bi-card-body">
      <div class="fin-invest-row">
        <div class="fin-row">
          <span class="fin-label">Investimento Total</span>
          <span class="fin-value invest" id="ev-fin-invest">—</span>
        </div>
        <div style="text-align:right">
          <span class="fin-label" style="display:block;margin-bottom:3px">ROI(x)</span>
          <div class="roi-badge" id="ev-fin-roi">—</div>
        </div>
      </div>
      <div class="fin-cac-grid">
        <div class="kpi-chip">
          <span class="kpi-chip-label">CPL</span>
          <span class="kpi-chip-val" id="ev-fin-cpl">—</span>
        </div>
        <div class="kpi-chip evento">
          <span class="kpi-chip-label">CAC Cadeira</span>
          <span class="kpi-chip-val" id="ev-fin-cac-cadeira">—</span>
        </div>
        <div class="kpi-chip consulta">
          <span class="kpi-chip-label">CAC Consulta</span>
          <span class="kpi-chip-val" id="ev-fin-cac-consulta">—</span>
        </div>
        <div class="kpi-chip fecho">
          <span class="kpi-chip-label">CAC Combo</span>
          <span class="kpi-chip-val" id="ev-fin-cac-combo">—</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Representatividade de Combos e Consultas por Formulário -->
  <div class="bi-card ev-rep">
    <div class="bi-card-header"><span class="bi-card-title">Representatividade de Combos e Consultas</span></div>
    <div class="bi-card-body">
      <div class="hbar-legend">
        <span class="legend-item"><span class="legend-color" style="background:#c8102e"></span>Share Combos (%)</span>
        <span class="legend-item"><span class="legend-color" style="background:#1a6ec8"></span>Share Consultas (%)</span>
      </div>
      <div class="hbar-list lg" id="ev-hbar-representatividade"></div>
    </div>
  </div>

  <!-- Investimento por Formulário -->
  <div class="bi-card ev-inv">
    <div class="bi-card-header"><span class="bi-card-title">Investimento</span></div>
    <div class="bi-card-body">
      <div class="hbar-list" id="ev-hbar-investimento"></div>
    </div>
  </div>

  <!-- Performance por Formulário -->
  <div class="bi-card mk-tbl-card ev-tbl">
    <div class="bi-card-header">
      <span class="bi-card-title">Performance por Formulário</span>
      <span class="mk-tbl-count" id="ev-tbl-count"></span>
    </div>
    <div class="mk-tbl-scroll">
      <table class="bi-table mk-tbl">
        <thead><tr id="ev-tbl-thead"></tr></thead>
        <tbody id="ev-tbl-tbody"></tbody>
      </table>
    </div>
  </div>

  <!-- Desempenho por Formulário — toggle de métrica -->
  <div class="bi-card ev-desem">
    <div class="bi-card-header">
      <span class="bi-card-title">Desempenho por Formulário</span>
      <div class="gran-toggle" id="ev-metric-toggle">
        <button class="gran-btn active" data-metrica="total_leads"        type="button">Total de Leads</button>
        <button class="gran-btn"        data-metrica="qualificados"       type="button">Qualificados</button>
        <button class="gran-btn"        data-metrica="consultas_vendidas" type="button">Consultas Vendidas</button>
        <button class="gran-btn"        data-metrica="combos_vendidos"    type="button">Combos Vendidos</button>
        <button class="gran-btn"        data-metrica="cadeira_vendida"    type="button">Cadeiras Vendidas</button>
      </div>
    </div>
    <div class="bi-card-body">
      <div class="hbar-list" id="ev-hbar-desempenho"></div>
    </div>
  </div>

</div>

</div>

<div id="ev-hbar-tooltip"></div>

<script>
const API = '<?= $this->site ?>marketing/eventosDados';

const YEARS  = anosDesde(2025);
const MONTHS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
const yearDropdown  = createMultiDropdown('year-wrap',  'year-btn',  'year-list',  YEARS,  l => l, l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);

// ══════════════════════════════════════════════════════════════
// CROSS-FILTER — a tabela e os 3 gráficos continuam mostrando TODOS os
// formulários (pedido do usuário: clicar não pode sumir com o resto,
// igual ao destaque do Power BI) — só o(s) selecionado(s) ficam em
// destaque (.is-sel) e o resto esmaecido (.is-dim), 100% client-side, sem
// novo fetch pra isso. O cabeçalho de KPIs/Resumo Financeiro, por outro
// lado, PRECISA refletir a seleção (pedido do usuário) — como a tabela
// por formulário não carrega os 2 campos que faltam pra recompor ROI/CAC
// corretamente quando 2+ formulários estão selecionados (faturamento
// bruto de combo, consultas realizadas — só a versão já dividida em
// %/CAC de cada linha), o cabeçalho refaz um fetch enxuto no MESMO
// endpoint com `form=`, reaproveitando o filtro que já existe em
// filtrosLead()/filtrosDeal()/filtrosCusto() — e descarta a tabela_form
// que vem nessa resposta (já filtrada por form), continuando a usar a
// TABELA_FORM cheia carregada no fetch original.
// ══════════════════════════════════════════════════════════════
let crossForm = [];
let kpiFetchSeq = 0;

function toggleForm(form) {
  const i = crossForm.indexOf(form);
  if (i >= 0) crossForm.splice(i, 1);
  else crossForm.push(form);
  renderTudo();
  refreshKpisForSelection();
}

async function refreshKpisForSelection() {
  const seq = ++kpiFetchSeq;
  const params = new URLSearchParams();
  const year = yearDropdown.getValue(), months = monthDropdown.getValue();
  if (year) params.set('year', year);
  if (months) params.set('months', months);
  if (crossForm.length) params.set('form', crossForm.join(','));
  try {
    const res  = await fetch(API + '?' + params);
    const data = await res.json();
    if (seq !== kpiFetchSeq) return; // resposta velha (seleção já mudou de novo)
    if (res.ok && !data.error) fillKpis(data.kpis);
  } catch (err) {
    // silencioso: o cabeçalho só mantém o último valor bom conhecido
  }
}

function escHtml(s) {
  return String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
}

// ══════════════════════════════════════════════════════════════
// KPIs + Resumo Financeiro (mesmas fórmulas de montarKpisMarketing())
// ══════════════════════════════════════════════════════════════
function fillKpis(k) {
  const set = (id, v) => document.getElementById(id).textContent = v;
  set('ev-kpi-leads', fNum(k.total_leads));
  set('ev-kpi-qualificados', fNum(k.qualificados));
  set('ev-kpi-oportunidades', fNum(k.oportunidades));
  set('ev-kpi-cadeiras', fNum(k.cadeira_vendida));
  set('ev-kpi-consultas-vendidas', fNum(k.consultas_vendidas));
  set('ev-kpi-consultas-realizadas', fNum(k.consultas_realizadas));
  set('ev-kpi-combos', fNum(k.combos_vendidos));

  set('ev-fin-invest', fBRL(k.investimento));
  set('ev-fin-roi', fROI(k.roi));
  set('ev-fin-cpl', fBRL(k.cpl));
  set('ev-fin-cac-cadeira', fBRL(k.cac_cadeira));
  set('ev-fin-cac-consulta', fBRL(k.cac_consulta));
  set('ev-fin-cac-combo', fBRL(k.cac_combos));
}

// ══════════════════════════════════════════════════════════════
// BARRA HORIZONTAL — genérica, série única ou múltipla (adaptado de
// paginas/law/index.php::buildHBarCaseManager()). Escala INDEPENDENTE por
// série: cada métrica é esticada até seu próprio líder ocupar toda a
// fatia de trilha que lhe cabe (100/nº de séries) — nenhum formulário
// sozinho bate 100% de representatividade, então normalizar contra um
// total combinado deixava toda barra pequena.
// ══════════════════════════════════════════════════════════════
function buildHBar(containerId, rows, series, opts) {
  opts = opts || {};
  const el = document.getElementById(containerId);
  if (!rows.length) {
    el.innerHTML = '<div style="padding:12px 0;color:var(--muted);font-size:12px;text-align:center">Sem dados no período</div>';
    return;
  }
  const fatia = 100 / series.length;
  const serieMax = {};
  series.forEach(s => {
    serieMax[s.key] = Math.max(1e-9, ...rows.map(r => opts.fmtVal ? opts.fmtVal(r, s) : r[s.key]));
  });

  el.innerHTML = rows.map(r => {
    const segments = series
      .filter(s => (opts.fmtVal ? opts.fmtVal(r, s) : r[s.key]) > 0)
      .map(s => {
        const val   = opts.fmtVal ? opts.fmtVal(r, s) : r[s.key];
        const pct   = (val / serieMax[s.key]) * fatia;
        const label = opts.labelFmt ? opts.labelFmt(val) : fNum(val);
        return `<div class="hbar-segment" style="width:${pct.toFixed(2)}%;background:${s.color}">${pct >= 7 ? label : ''}</div>`;
      })
      .join('');
    const marcada  = crossForm.includes(r.dimensao);
    const atenuada = crossForm.length > 0 && !marcada;
    const cls = [marcada ? 'is-selected' : '', atenuada ? 'is-dim' : ''].filter(Boolean).join(' ');
    return `
      <div class="hbar-row2${cls ? ' ' + cls : ''}" data-form="${escHtml(r.dimensao)}">
        <span class="hbar-name" title="${escHtml(r.dimensao)}">${escHtml(r.dimensao)}</span>
        <div class="hbar-track"><div class="hbar-stack" style="width:100%">${segments}</div></div>
      </div>`;
  }).join('');

  el.querySelectorAll('.hbar-row2').forEach(rowEl => {
    const row = rows.find(r => r.dimensao === rowEl.dataset.form);
    rowEl.addEventListener('mouseenter', e => showHbarTooltip(e, row, series, opts));
    rowEl.addEventListener('mousemove', positionHbarTooltip);
    rowEl.addEventListener('mouseleave', hideHbarTooltip);
    rowEl.addEventListener('click', () => toggleForm(row.dimensao));
  });
}

function showHbarTooltip(e, row, series, opts) {
  const tip = document.getElementById('ev-hbar-tooltip');
  const rowsHtml = series.map(s => {
    const val = opts.fmtVal ? opts.fmtVal(row, s) : row[s.key];
    return `<div class="hbtt-row">
      <span class="hbtt-dot" style="background:${s.color}"></span>
      <span class="hbtt-label">${s.label}</span>
      <span class="hbtt-val">${opts.labelFmt ? opts.labelFmt(val) : fNum(val)}</span>
    </div>`;
  }).join('');
  tip.innerHTML = `<div class="hbtt-title">${escHtml(row.dimensao)}</div>${rowsHtml}`;
  tip.style.display = 'block';
  positionHbarTooltip(e);
}
function positionHbarTooltip(e) {
  const tip = document.getElementById('ev-hbar-tooltip');
  const margin = 14, tw = tip.offsetWidth || 180, th = tip.offsetHeight || 90;
  let x = e.clientX + margin, y = e.clientY - th - margin;
  if (x + tw > window.innerWidth - margin) x = e.clientX - tw - margin;
  if (y < margin) y = e.clientY + margin;
  tip.style.left = x + 'px'; tip.style.top = y + 'px';
}
function hideHbarTooltip() { document.getElementById('ev-hbar-tooltip').style.display = 'none'; }

// ══════════════════════════════════════════════════════════════
// Representatividade / Investimento / Desempenho — nenhum campo novo no
// payload: tudo derivado no JS a partir de TABELA_FORM (a própria linha
// "Total" já dá os denominadores) — mesma filosofia de "métrica troca
// sem novo fetch" de paginas/dentistas/index.php.
// ══════════════════════════════════════════════════════════════
let TABELA_FORM = [];
let TOTAL_FORM  = { combos_vendidos: 0, consultas_vendidas: 0 };

function linhasSemTotal() { return TABELA_FORM.filter(r => r.dimensao !== 'Total'); }

function renderRepresentatividade() {
  const rows = linhasSemTotal()
    .map(r => ({
      dimensao: r.dimensao,
      share_combos: TOTAL_FORM.combos_vendidos > 0 ? r.combos_vendidos / TOTAL_FORM.combos_vendidos : 0,
      share_consultas: TOTAL_FORM.consultas_vendidas > 0 ? r.consultas_vendidas / TOTAL_FORM.consultas_vendidas : 0,
    }))
    .sort((a, b) => b.share_combos - a.share_combos);
  buildHBar('ev-hbar-representatividade', rows,
    [{ key: 'share_combos', label: 'Share Combos', color: '#c8102e' },
     { key: 'share_consultas', label: 'Share Consultas', color: '#1a6ec8' }],
    { labelFmt: v => fPct(v) });
}

function renderInvestimento() {
  const rows = linhasSemTotal().slice().sort((a, b) => b.invest - a.invest);
  buildHBar('ev-hbar-investimento', rows, [{ key: 'invest', label: 'Investimento', color: '#c8102e' }],
    { labelFmt: v => fBRL(v) });
}

let metricaAtual = 'total_leads';
const METRICA_KEY = {
  total_leads: 'leads', qualificados: 'qualificados', consultas_vendidas: 'consultas_vendidas',
  combos_vendidos: 'combos_vendidos', cadeira_vendida: 'cadeira_vendida',
};
const METRICA_LABEL = {
  total_leads: 'Total de Leads', qualificados: 'Qualificados', consultas_vendidas: 'Consultas Vendidas',
  combos_vendidos: 'Combos Vendidos', cadeira_vendida: 'Cadeiras Vendidas',
};
function renderDesempenho() {
  const campo = METRICA_KEY[metricaAtual];
  const rows = linhasSemTotal().map(r => ({ dimensao: r.dimensao, val: r[campo] })).sort((a, b) => b.val - a.val);
  buildHBar('ev-hbar-desempenho', rows, [{ key: 'val', label: METRICA_LABEL[metricaAtual], color: '#7c3aed' }]);
}
document.getElementById('ev-metric-toggle').addEventListener('click', e => {
  const btn = e.target.closest('.gran-btn');
  if (!btn) return;
  metricaAtual = btn.dataset.metrica;
  document.querySelectorAll('#ev-metric-toggle .gran-btn').forEach(b => b.classList.toggle('active', b === btn));
  renderDesempenho();
});

// ══════════════════════════════════════════════════════════════
// Tabela "Performance por Formulário" — 17 colunas (mesmas de
// paginas/marketing/index.php::COLS), sortable, cross-filter por linha.
// ══════════════════════════════════════════════════════════════
const COLS = [
  { k: 'invest',             label: 'Invest.',         fmt: fBRL },
  { k: 'roi',                label: 'ROI(x)',          fmt: fROI },
  { k: 'cliques',            label: 'Cliques',         fmt: fNum },
  { k: 'leads',              label: 'Leads',           fmt: fNum },
  { k: 'cpl',                label: 'CPL',             fmt: fBRL },
  { k: 'qualificados',       label: 'Qualificados',    fmt: fNum },
  { k: 'pct_qualificados',   label: '% Qualif.',       fmt: fPct },
  { k: 'oportunidades',      label: 'Oportunidades',   fmt: fNum },
  { k: 'cadeira_vendida',    label: 'Cadeiras',        fmt: fNum },
  { k: 'conv_cadeira',       label: 'Conv. Cadeiras',  fmt: fPct },
  { k: 'cac_cadeira',        label: 'CAC Cadeira',     fmt: fBRL },
  { k: 'consultas_vendidas', label: 'Cons. Vendidas',  fmt: fNum },
  { k: 'conv_consultas',     label: 'Conv. Consultas', fmt: fPct },
  { k: 'cac_consulta',       label: 'CAC Consulta',    fmt: fBRL },
  { k: 'combos_vendidos',    label: 'Combos',          fmt: fNum },
  { k: 'conv_combo',         label: 'Conv. Combo',     fmt: fPct },
  { k: 'cac_combos',         label: 'CAC Combos',      fmt: fBRL },
];
let sortCol = 'invest', sortDir = 'desc';

// Largura por coluna em PX (mesma calibragem de paginas/marketing/index.php::
// colWidthPx()) — tabela com largura própria maior que o card, rolagem lateral.
const NOME_PX = 220;
function colWidthPx(c) {
  if (/invest/.test(c.k)) return 95;
  if (/^cac_/.test(c.k))  return 85;
  if (c.k === 'cpl')      return 60;
  if (c.k === 'roi')      return 55;
  return 75;
}
function colgroupHtml() {
  return '<colgroup>' + [`<col style="width:${NOME_PX}px">`]
    .concat(COLS.map(c => `<col style="width:${colWidthPx(c)}px">`)).join('') + '</colgroup>';
}
function tabelaWidthPx() { return NOME_PX + COLS.reduce((a, c) => a + colWidthPx(c), 0); }

function roiClass(val) {
  if (val === null || val === undefined) return '';
  return val < 1 ? ' td-danger' : (val >= 8 ? ' td-green' : ' td-amber');
}

function renderThead() {
  const thead = document.getElementById('ev-tbl-thead');
  const tbl = thead.closest('table');
  tbl.querySelector('colgroup')?.remove();
  tbl.insertAdjacentHTML('afterbegin', colgroupHtml());
  tbl.style.width = tabelaWidthPx() + 'px';

  const thHtml = (key, label, isNum) => {
    const active = sortCol === key;
    const arrow = active ? (sortDir === 'desc' ? ' ▼' : ' ▲') : '';
    return `<th${isNum ? ' class="num"' : ''} title="Clique para ordenar" data-col="${key}">${escHtml(label)}${arrow}</th>`;
  };
  thead.innerHTML = thHtml('dimensao', 'Form Preenchido', false) + COLS.map(c => thHtml(c.k, c.label, true)).join('');
  thead.querySelectorAll('th[data-col]').forEach(th => th.addEventListener('click', () => {
    const col = th.dataset.col;
    if (sortCol === col) sortDir = sortDir === 'desc' ? 'asc' : 'desc';
    else { sortCol = col; sortDir = 'desc'; }
    renderThead();
    renderTabela();
  }));
}

function sortRows(rows) {
  return rows.slice().sort((a, b) => {
    if (sortCol === 'dimensao') return sortDir === 'desc'
      ? b.dimensao.localeCompare(a.dimensao, 'pt', { numeric: true })
      : a.dimensao.localeCompare(b.dimensao, 'pt', { numeric: true });
    const av = a[sortCol] ?? -Infinity, bv = b[sortCol] ?? -Infinity;
    return sortDir === 'desc' ? bv - av : av - bv;
  });
}

function renderTabela() {
  const total = TABELA_FORM.find(r => r.dimensao === 'Total');
  const rows  = sortRows(linhasSemTotal());
  document.getElementById('ev-tbl-count').textContent = rows.length + ' formulários';
  const tbody = document.getElementById('ev-tbl-tbody');

  const linhaHtml = (r, isTotal) => {
    const marcada = !isTotal && crossForm.includes(r.dimensao);
    const atenuada = !isTotal && crossForm.length > 0 && !marcada;
    const cls = [isTotal ? 'tr-total' : '', marcada ? 'is-sel' : '', atenuada ? 'is-dim' : ''].filter(Boolean).join(' ');
    const nome = isTotal
      ? `<td>${escHtml(r.dimensao)}</td>`
      : `<td title="${escHtml(r.dimensao)}"><button type="button" class="mk-row-btn" aria-pressed="${marcada}">${escHtml(r.dimensao)}</button></td>`;
    const tds = COLS.map(c => `<td class="num${c.k === 'roi' ? roiClass(r[c.k]) : ''}">${c.fmt(r[c.k])}</td>`).join('');
    return `<tr${cls ? ` class="${cls}"` : ''}${isTotal ? '' : ` data-form="${escHtml(r.dimensao)}"`}>${nome}${tds}</tr>`;
  };
  tbody.innerHTML = rows.map(r => linhaHtml(r, false)).join('') + (total ? linhaHtml(total, true) : '');
  tbody.querySelectorAll('tr[data-form]').forEach(tr => {
    tr.addEventListener('click', () => toggleForm(tr.dataset.form));
  });
}

function renderTudo() {
  TOTAL_FORM = TABELA_FORM.find(r => r.dimensao === 'Total') || { combos_vendidos: 0, consultas_vendidas: 0 };
  renderTabela();
  renderRepresentatividade();
  renderInvestimento();
  renderDesempenho();
}

// ══════════════════════════════════════════════════════════════
// Carregar dashboard
// ══════════════════════════════════════════════════════════════
function montaParams() {
  // Sem 'form' aqui de propósito: o clique em linha/barra não refaz fetch
  // (ver toggleForm()) — 'form' só existiria pra filtrar o backend, e a
  // seleção agora é 100% visual (is-sel/is-dim) no dado já carregado.
  return {
    year:   yearDropdown.getValue(),
    months: monthDropdown.getValue(),
  };
}

async function loadDashboard(f, opts = {}) {
  const overlay = document.getElementById('loading-overlay');
  const errorBanner = document.getElementById('error-banner');

  overlay.classList.remove('hidden');
  document.body.classList.add(opts.quiet ? 'loading-quiet' : 'loading');

  const params = new URLSearchParams();
  Object.entries(f).forEach(([k, v]) => { if (v) params.set(k, v); });

  try {
    const res  = await fetch(API + '?' + params);
    const data = await res.json();
    if (!res.ok || data.error) {
      errorBanner.textContent = data.error || 'Erro ao carregar dados.';
      errorBanner.classList.add('visible');
      return;
    }
    errorBanner.classList.remove('visible');

    fillKpis(data.kpis);
    TABELA_FORM = data.tabela_form || [];
    renderTudo();
  } catch (err) {
    errorBanner.textContent = 'Erro ao carregar dados: ' + err.message;
    errorBanner.classList.add('visible');
  } finally {
    overlay.classList.add('hidden');
    document.body.classList.remove('loading', 'loading-quiet');
  }
}

function applyFilters(opts = {}) {
  updateBadge();
  loadDashboard(montaParams(), { quiet: !!opts.quiet }).then(() => {
    // Troca de Ano/Mês com uma seleção ainda ativa: o fetch acima já trouxe
    // o KPI global do novo período (sobrescrevendo o header) — recalcula na
    // sequência pro formulário selecionado, senão o header ficaria com o
    // total errado (do período novo, mas sem aplicar a seleção antiga).
    if (crossForm.length) refreshKpisForSelection();
  });
}

function clearFilters() {
  yearDropdown.reset();
  monthDropdown.reset();
  crossForm = [];
  updateBadge();
  loadDashboard(montaParams());
}

// ── Badge do período selecionado ────────────────────────────────
function updateBadge() {
  const badge  = document.getElementById('filter-label-badge');
  const year   = yearDropdown.getValue();
  const months = monthDropdown.getValue();
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

renderThead();
applyFilters();
</script>
