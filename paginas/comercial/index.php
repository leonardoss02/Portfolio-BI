<div class="dash-comercial">
<style>
/* ══════════════════════════════════════════════════════════════
 *  CSS específico do dashboard Comercial — escopado em .dash-comercial.
 *  Tudo o que é vocabulário compartilhado (header, filtros, kpi-chip,
 *  funil-card, bi-card, chart-legend) vem de css/bi-theme.css.
 * ══════════════════════════════════════════════════════════════ */

/* Cores de canal deste dashboard: o laranja e o azul do relatório Power BI
   original, em vez dos tokens --gold/--consulta do tema. */
.dash-comercial {
  --ev:      #f54a00;
  --ev-glow: rgba(245,74,0,.09);
  --dg:      #5c94bd;
  --dg-glow: rgba(92,148,189,.12);
}

/* ── Barra de totalizadores (abaixo dos filtros) ───────────────── */
/* Superfície de conteúdo (var(--card)/var(--border)), não de chrome — a
   barra some entre o header e os filtros navy quando usa var(--hdr-*), que
   é fixo nos dois temas. Aqui ela precisa ler como dado, então usa os
   mesmos tokens claro/escuro-aware dos kpi-chip/funil-card da página. */
.dash-comercial .tot-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(96px, 1fr));
  background: var(--card);
  border-bottom: 1px solid var(--border);
  box-shadow: var(--sh-sm);
  padding: 4px 24px 10px;
}
.dash-comercial .tot-item {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 7px 6px; position: relative;
}
.dash-comercial .tot-item:not(:last-child)::after {
  content: ''; position: absolute; right: 0; top: 18%; height: 64%; width: 1px;
  background: var(--border);
}
.dash-comercial .tot-label {
  font-size: 9px; font-weight: 700; letter-spacing: .09em; text-transform: uppercase;
  color: var(--muted); margin-bottom: 5px; text-align: center; white-space: nowrap;
}
.dash-comercial .tot-val {
  font-family: var(--font-mono); font-size: 21px; font-weight: 700; line-height: 1;
  font-variant-numeric: tabular-nums; color: var(--text); white-space: nowrap;
}
.dash-comercial .tot-val.is-money { font-size: 15px; }
/* Cores semânticas do funil — mesmos tokens já usados pelos kpi-chip/
   canal-tag desta página (--ev/--dg/--fecho/--danger) ou globais do tema
   (--consulta/--amber/--accent2), todos já theme-safe. */
.dash-comercial .tot-val.c-leads { color: var(--accent2); }
.dash-comercial .tot-val.c-quali { color: var(--consulta); }
.dash-comercial .tot-val.c-cad   { color: var(--ev); }
.dash-comercial .tot-val.c-cv    { color: var(--dg); }
.dash-comercial .tot-val.c-cr    { color: var(--consulta); }
.dash-comercial .tot-val.c-cb    { color: var(--fecho); }
.dash-comercial .tot-val.c-dist  { color: var(--danger); }
.dash-comercial .tot-val.c-valor { color: var(--amber); }

/* ── Faixas de KPI por canal ───────────────────────────────────── */
.dash-comercial .canal-bloco { display: flex; flex-direction: column; gap: 0; }
.dash-comercial .canal-tag {
  align-self: flex-start;
  font-size: 10px; font-weight: 700; letter-spacing: .10em; text-transform: uppercase;
  padding: 5px 12px; border-radius: var(--r-sm) var(--r-sm) 0 0; border: 1px solid; border-bottom: none;
  display: inline-flex; align-items: center; gap: 7px;
}
.dash-comercial .canal-tag::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
.dash-comercial .canal-tag.ev { color: var(--ev);    background: var(--ev-glow);    border-color: rgba(245,74,0,.30); }
.dash-comercial .canal-tag.dg { color: var(--dg);    background: var(--dg-glow);    border-color: rgba(92,148,189,.34); }
.dash-comercial .canal-tag.rt { color: var(--fecho); background: var(--fecho-glow); border-color: rgba(13,138,82,.28); }
.dash-comercial .canal-tag .canal-tag-sub { font-weight: 500; letter-spacing: .04em; opacity: .72; text-transform: none; }
.dash-comercial .kpi-chip-val.is-money { font-size: 16px; }
.dash-comercial .kpi-chip.risco .kpi-chip-val { color: var(--danger); }

/* Chips nas cores do PBI (sobrescreve as variantes evento/consulta do tema) */
.dash-comercial .kpi-chip.evento  { border-left-color: var(--ev); background: var(--ev-glow); }
.dash-comercial .kpi-chip.evento   .kpi-chip-val { color: var(--ev); }
.dash-comercial .kpi-chip.consulta { border-left-color: var(--dg); background: var(--dg-glow); }
.dash-comercial .kpi-chip.consulta .kpi-chip-val { color: var(--dg); }

/* ── Funis: cor sólida (o tema usa gradiente) e cores do PBI ───── */
.dash-comercial .funil-card.evento  { border-color: var(--ev); }
.dash-comercial .funil-card.evento  .funil-header     { background: var(--ev-glow); }
.dash-comercial .funil-card.evento  .funil-name,
.dash-comercial .funil-card.evento  .funil-meta-value,
.dash-comercial .funil-card.evento  .funil-row-val    { color: var(--ev); }
.dash-comercial .funil-card.evento  .funil-name-icon  { background: var(--ev-glow); }
.dash-comercial .funil-card.evento  .funil-bar        { background: var(--ev); }

.dash-comercial .funil-card.digital { border-color: var(--dg); }
.dash-comercial .funil-card.digital .funil-header     { background: var(--dg-glow); }
.dash-comercial .funil-card.digital .funil-name,
.dash-comercial .funil-card.digital .funil-meta-value,
.dash-comercial .funil-card.digital .funil-row-val    { color: var(--dg); }
.dash-comercial .funil-card.digital .funil-name-icon  { background: var(--dg-glow); }
.dash-comercial .funil-card.digital .funil-bar        { background: var(--dg); }

/* O valor em dólar do cabeçalho do funil continua verde (highlight-lucro),
   mas a regra acima é mais específica — restaura. */
.dash-comercial .funil-meta-value.highlight-lucro { color: var(--green); }

/* ── Gráfico de evolução ───────────────────────────────────────── */
.dash-comercial .chart-body { padding: 10px 6px 4px; }
.dash-comercial .chart-holder { position: relative; }
.dash-comercial .chart-wrap { width: 100%; overflow-x: auto; }
.dash-comercial .chart-wrap svg { display: block; width: 100%; height: auto; min-width: 620px; }
/* Halo na cor do card: o rótulo do ponto sempre vence o número do eixo Y. */
.dash-comercial .chart-wrap .pt-label { paint-order: stroke; stroke: var(--card); stroke-width: 3px; stroke-linejoin: round; }

.dash-comercial .chart-tooltip {
  position: absolute; top: 0; left: 0; z-index: 5; pointer-events: none;
  min-width: 148px; padding: 9px 11px;
  background: var(--card); border: 1px solid var(--border); border-radius: var(--r-sm);
  box-shadow: var(--sh-lg); font-size: 12px; color: var(--text);
  transition: opacity .12s; opacity: 1;
}
.dash-comercial .chart-tooltip[hidden] { display: none; }
.dash-comercial .tt-mes {
  font-weight: 700; font-size: 11px; letter-spacing: .06em; text-transform: uppercase;
  color: var(--muted); padding-bottom: 6px; margin-bottom: 6px; border-bottom: 1px solid var(--border);
}
.dash-comercial .tt-row { display: flex; align-items: center; gap: 8px; padding: 2px 0; }
.dash-comercial .tt-dot { width: 8px; height: 8px; border-radius: 2px; flex-shrink: 0; }
.dash-comercial .tt-label { color: var(--muted); }
.dash-comercial .tt-val { margin-left: auto; font-family: var(--font-mono); font-weight: 700; font-variant-numeric: tabular-nums; }
.dash-comercial .chart-legend { flex-wrap: wrap; }
.dash-comercial .chart-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 6px; padding: 40px 0; color: var(--muted); font-size: 12px;
}
</style>

<!-- ── HEADER ──────────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo">CO</div>
    <div>
      <div class="header-title">Painel de Performance — Funil Comercial</div>
      <div class="header-sub">Evento (OTR/OTW) · Digital · Retenção · Consultas · Combos</div>
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
    <div class="last-updated" id="last-updated"></div>
    <div class="tab-switch">
      <a class="tab-switch-btn active" href="<?= $this->site ?>comercial<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Visão Geral</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial/yoy<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Comparativo</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial/cadeiras<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Cadeiras</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial/comercial1<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Com. 1</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial/comercial2<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Com. 2</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial/comercial3<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Com. 3</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
    </div>
  </div>
</header>

<!-- ── FILTROS ─────────────────────────────────────────────────── -->
<section class="filters is-collapsible" aria-label="Filtros do dashboard">
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
      <button class="multi-dd-btn" id="year-btn" type="button">Todos <span class="dd-caret">&#9662;</span></button>
      <div class="multi-dd-list" id="year-list"></div>
    </div>
  </div>

  <div class="filter-pair">
    <span class="filter-label">Mês</span>
    <div class="multi-dd-wrap" id="month-wrap">
      <button class="multi-dd-btn" id="month-btn" type="button">Todos <span class="dd-caret">&#9662;</span></button>
      <div class="multi-dd-list" id="month-list"></div>
    </div>
  </div>

  <div class="filter-pair">
    <span class="filter-label">Tipo Lead</span>
    <div class="multi-dd-wrap" id="tipo-wrap">
      <button class="multi-dd-btn" id="tipo-btn" type="button">Todos <span class="dd-caret">&#9662;</span></button>
      <div class="multi-dd-list" id="tipo-list"></div>
    </div>
  </div>

  <div class="filter-sep"></div>

  <div class="filter-pair">
    <span class="filter-label">Form</span>
    <div class="camp-wrap" id="form-wrap">
      <div class="camp-tags" id="form-tags"></div>
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="form-input" class="camp-input" type="text" placeholder="Pesquisar form..." autocomplete="off" aria-label="Pesquisar form preenchido" aria-autocomplete="list" aria-controls="form-dropdown">
        <button class="camp-clear" id="form-clear" aria-label="Limpar form" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-dropdown" id="form-dropdown" role="listbox"></div>
    </div>
  </div>

  <div class="filter-pair">
    <span class="filter-label">Responsável</span>
    <div class="camp-wrap" id="resp-wrap">
      <div class="camp-tags" id="resp-tags"></div>
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="resp-input" class="camp-input" type="text" placeholder="Pesquisar responsável..." autocomplete="off" aria-label="Pesquisar responsável" aria-autocomplete="list" aria-controls="resp-dropdown">
        <button class="camp-clear" id="resp-clear" aria-label="Limpar responsável" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-dropdown" id="resp-dropdown" role="listbox"></div>
    </div>
  </div>

  <button class="btn-apply" id="btn-apply">Aplicar</button>
  <button class="btn-clear" id="btn-clear">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'comercial-index' });</script>

<!-- ── TOTALIZADORES ───────────────────────────────────────────── -->
<div class="tot-bar" id="tot-bar"></div>

<div id="loading-overlay" class="hidden"></div>
<div id="error-banner" role="alert"></div>

<!-- ── CONTEÚDO ────────────────────────────────────────────────── -->
<main class="main" id="dashboard-main" aria-label="Funil comercial">

  <div class="canal-bloco">
    <span class="canal-tag ev">Canal Evento <span class="canal-tag-sub">tipoLead OTR / OTW</span></span>
    <div class="kpi-row" id="kpi-ev"></div>
  </div>

  <div class="canal-bloco">
    <span class="canal-tag dg">Canal Digital <span class="canal-tag-sub">demais tipos de lead</span></span>
    <div class="kpi-row" id="kpi-dg"></div>
  </div>

  <div class="canal-bloco">
    <span class="canal-tag rt">Retenção <span class="canal-tag-sub">fase C1:WON</span></span>
    <div class="kpi-row" id="kpi-rt"></div>
  </div>

  <div class="funis-grid">
    <div class="funil-card evento">
      <div class="funil-header">
        <div class="funil-name">
          <span class="funil-name-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 4h16v3l-6 6v6l-4 2v-8L4 7z"/>
            </svg>
          </span>
          Funil Evento
        </div>
        <div class="funil-meta">
          <div class="funil-meta-item">
            <div class="funil-meta-label">Combos</div>
            <div class="funil-meta-value" id="fm-ev-cb">—</div>
          </div>
          <div class="funil-meta-item">
            <div class="funil-meta-label">Valor</div>
            <div class="funil-meta-value highlight-lucro" id="fm-ev-valor">—</div>
          </div>
        </div>
      </div>
      <div class="funil-body" id="funil-ev"></div>
    </div>

    <div class="funil-card digital">
      <div class="funil-header">
        <div class="funil-name">
          <span class="funil-name-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/>
            </svg>
          </span>
          Funil Digital
        </div>
        <div class="funil-meta">
          <div class="funil-meta-item">
            <div class="funil-meta-label">Combos</div>
            <div class="funil-meta-value" id="fm-dg-cb">—</div>
          </div>
          <div class="funil-meta-item">
            <div class="funil-meta-label">Valor</div>
            <div class="funil-meta-value highlight-lucro" id="fm-dg-valor">—</div>
          </div>
        </div>
      </div>
      <div class="funil-body" id="funil-dg"></div>
    </div>
  </div>

  <div class="bi-card">
    <div class="bi-card-header">
      <span class="bi-card-title" id="chart-title">Evolução mensal</span>
      <div class="gran-toggle" id="gran-toggle">
        <button class="gran-btn active" data-gran="mes"    onclick="setGranularidade('mes')">Mês</button>
        <button class="gran-btn"        data-gran="semana" onclick="setGranularidade('semana')">Semana</button>
        <button class="gran-btn"        data-gran="dia"    onclick="setGranularidade('dia')">Dia</button>
      </div>
      <div class="chart-legend">
        <span class="legend-item"><i class="legend-color" style="background:var(--ev)"></i>Ingressos</span>
        <span class="legend-item"><i class="legend-color" style="background:var(--dg)"></i>Consultas vendidas</span>
        <span class="legend-item"><i class="legend-color" style="background:var(--fecho)"></i>Combos</span>
      </div>
    </div>
    <div class="bi-card-body chart-body">
      <div class="chart-holder">
        <div class="chart-wrap" id="chart-wrap"></div>
        <div class="chart-tooltip" id="chart-tooltip" hidden></div>
      </div>
    </div>
  </div>

</main>
</div>

<script>
// ══════════════════════════════════════════════════════════════
//  Dashboard Comercial — consome comercial/dados, comercial/forms
//  e comercial/responsaveis.
//
//  createMultiDropdown, createSearchDropdown, getDateInput,
//  clearDateInput, fNum, fBRL, fPctRatio vêm de js/bi-dashboard.js
//  (carregado no <head> pelo template, antes deste script).
// ══════════════════════════════════════════════════════════════

const API       = '<?= $this->site ?>comercial/dados';
const FORMS_URL = '<?= $this->site ?>comercial/forms';
const RESP_URL  = '<?= $this->site ?>comercial/responsaveis';

const YEARS  = anosDesde(2025);
const MONTHS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

// Lista fixa (7 valores distintos no CRM). '(vazio)' é o token que o
// controller traduz de volta para string vazia — CSV não transporta ''.
const TIPOS_LEAD = ['OTR', 'OTW', 'Consulta', 'Venda direta', 'B2B', 'Indicação', '(vazio)'];

const yearDropdown  = createMultiDropdown('year-wrap',  'year-btn',  'year-list',  YEARS,  l => l, l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);
const tipoDropdown  = createMultiDropdown('tipo-wrap',  'tipo-btn',  'tipo-list',  TIPOS_LEAD, l => l, l => l);
const formDropdown  = createSearchDropdown('form-wrap', 'form-input', 'form-dropdown', 'form-clear', 'form-tags', 'Nenhum form encontrado');
const respDropdown  = createSearchDropdown('resp-wrap', 'resp-input', 'resp-dropdown', 'resp-clear', 'resp-tags', 'Nenhum responsável encontrado');

const NOW_YEAR = String(new Date().getFullYear());
yearDropdown.select([NOW_YEAR]);

fetch(FORMS_URL, { cache: 'no-store' }).then(r => r.json()).then(l => formDropdown.load(Array.isArray(l) ? l : [])).catch(err => console.error('Erro ao carregar forms:', err));
fetch(RESP_URL,  { cache: 'no-store' }).then(r => r.json()).then(l => respDropdown.load(Array.isArray(l) ? l : [])).catch(err => console.error('Erro ao carregar responsáveis:', err));

function esc(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

// ── Totalizadores ─────────────────────────────────────────────────
function renderTotais(t) {
  const defs = [
    { l: 'Total de Leads',  v: fNum(t.leads),      c: 'c-leads' },
    { l: 'Leads Qualif.',   v: fNum(t.leadsQuali), c: 'c-quali' },
    { l: 'Cadeiras Vend.',  v: fNum(t.cad),        c: 'c-cad'   },
    { l: 'Consultas Vend.', v: fNum(t.cv),         c: 'c-cv'    },
    { l: 'Consultas Real.', v: fNum(t.cr),         c: 'c-cr'    },
    { l: 'Combos Vendidos', v: fNum(t.cb),         c: 'c-cb'    },
    { l: 'Distratos',       v: fNum(t.dist),       c: 'c-dist'  },
    { l: 'Valor de Combos', v: fBRL(t.valor),      c: 'c-valor', money: true }
  ];
  document.getElementById('tot-bar').innerHTML = defs.map(k =>
    '<div class="tot-item">'
    + '<div class="tot-label">' + esc(k.l) + '</div>'
    + '<div class="tot-val ' + k.c + (k.money ? ' is-money' : '') + '">' + esc(k.v) + '</div>'
    + '</div>').join('');
}

// ── Faixas de KPI ─────────────────────────────────────────────────
function renderChips(elId, variante, itens) {
  document.getElementById(elId).innerHTML = itens.map(k =>
    '<div class="kpi-chip ' + (k.risco ? 'risco' : variante) + '">'
    + '<span class="kpi-chip-label">' + esc(k.l) + '</span>'
    + '<span class="kpi-chip-val' + (k.money ? ' is-money' : '') + '">' + esc(k.v) + '</span>'
    + '</div>').join('');
}

// ── Funis ─────────────────────────────────────────────────────────
function renderFunil(elId, metaCbId, metaValorId, dados, etapas) {
  const max = etapas[0].v || 1;
  document.getElementById(elId).innerHTML = etapas.map((s, i) => {
    const w   = Math.max(2, Math.round(s.v / max * 100));
    const pct = i === 0 ? '—' : fPctRatio(s.v, etapas[i - 1].v);
    return '<div class="funil-row">'
      + '<div class="funil-row-label">' + esc(s.l) + '</div>'
      + '<div class="funil-bar-wrap"><div class="funil-bar" style="width:' + w + '%"></div></div>'
      + '<div class="funil-row-val">' + esc(fNum(s.v)) + '</div>'
      + '<div class="funil-row-cv">' + esc(pct) + '</div>'
      + '</div>';
  }).join('');
  document.getElementById(metaCbId).textContent    = fNum(dados.cb);
  document.getElementById(metaValorId).textContent = fBRL(dados.valor);
}

// ── Gráfico de evolução (SVG inline; não há helper compartilhado) ──
// Geometria do último gráfico desenhado, consumida pelo tooltip.
let chartGeo = null;

function buildChart(serie) {
  if (!serie || !serie.length) {
    return '<div class="chart-empty"><span style="font-size:22px">&#128202;</span>Sem dados de evolução para o período selecionado</div>';
  }
  // Padding lateral mínimo: os rótulos do eixo Y ficam sobre as linhas de
  // grade (não numa coluna à esquerda), então a série ocupa a largura toda.
  const W = 1200, H = 260, pl = 8, pr = 8, pt = 26, pb = 30;
  const cW = W - pl - pr, cH = H - pt - pb, n = serie.length;
  let max = 1;
  serie.forEach(d => { max = Math.max(max, d.ing || 0, d.cv || 0, d.cb || 0); });
  const step = n > 1 ? cW / (n - 1) : 0;
  const px = i => pl + step * i;
  const py = v => pt + cH - (v / max) * cH;
  const base = pt + cH;

  let grid = '', yLbls = '';
  for (let t = 0; t <= 4; t++) {
    const gy = base - t / 4 * cH;
    grid  += '<line x1="' + pl + '" y1="' + gy + '" x2="' + (pl + cW) + '" y2="' + gy + '" stroke="currentColor" stroke-opacity=".14"/>';
    if (t > 0 && t < 4) {
      yLbls += '<text x="' + (pl + 2) + '" y="' + (gy - 4) + '" text-anchor="start" font-size="9.5" fill="currentColor" fill-opacity=".5">' + Math.round(max * t / 4) + '</text>';
    }
  }

  // Com muitos meses, rotular só o primeiro, o último e o pico de cada série.
  const showLabel = (i, key) => {
    if (n <= 8) return true;
    const vals = serie.map(d => d[key] || 0);
    return i === 0 || i === n - 1 || i === vals.indexOf(Math.max.apply(null, vals));
  };

  // Área sob a linha — desenhada antes de todas as linhas para não cobri-las.
  function area(key, color) {
    const d = serie.map((s, i) => (i ? 'L' : 'M') + px(i) + ' ' + py(s[key] || 0)).join(' ');
    return '<path d="' + d + ' L' + px(n - 1) + ' ' + base + ' L' + px(0) + ' ' + base + ' Z"'
      + ' fill="' + color + '" fill-opacity=".12" stroke="none"/>';
  }

  const usados = [];
  function linha(key, color) {
    const pts = serie.map((d, i) => px(i) + ',' + py(d[key] || 0)).join(' ');
    let s = '<polyline points="' + pts + '" fill="none" stroke="' + color + '" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round"/>';
    serie.forEach((d, i) => {
      const v = d[key] || 0;
      s += '<circle cx="' + px(i) + '" cy="' + py(v) + '" r="3.2" fill="' + color + '"/>';
      if (showLabel(i, key) && v > 0) {
        let ly = py(v) - 9;
        usados[i] = usados[i] || [];
        while (usados[i].some(p => Math.abs(p - ly) < 11)) { ly -= 11; }
        usados[i].push(ly);
        s += '<text class="pt-label" x="' + px(i) + '" y="' + ly + '" text-anchor="middle" font-size="9.5" font-weight="700" fill="' + color + '">' + v + '</text>';
      }
    });
    return s;
  }

  // Primeiro e último rótulo do eixo X encostam na borda: ancoragem própria.
  const xLbls = serie.map((d, i) => {
    const anchor = i === 0 ? 'start' : (i === n - 1 ? 'end' : 'middle');
    return '<text x="' + px(i) + '" y="' + (base + 18) + '" text-anchor="' + anchor + '" font-size="10" fill="currentColor" fill-opacity=".6">' + esc(d.mes) + '</text>';
  }).join('');

  // Geometria publicada para o hover converter posição do mouse em índice de mês.
  chartGeo = { W: W, H: H, pl: pl, step: step, pt: pt, base: base, n: n, max: max, serie: serie };

  return '<svg viewBox="0 0 ' + W + ' ' + H + '" width="100%" role="img" aria-label="Evolução mensal"'
    + ' style="color:var(--muted);font-family:var(--font-ui)">'
    + grid
    + '<line id="chart-cursor" x1="0" y1="' + pt + '" x2="0" y2="' + base + '" stroke="currentColor" stroke-opacity=".35" stroke-width="1" stroke-dasharray="3 3" visibility="hidden"/>'
    + area('ing', 'var(--ev)')
    + area('cv',  'var(--dg)')
    + area('cb',  'var(--fecho)')
    + yLbls
    + linha('ing', 'var(--ev)')
    + linha('cv',  'var(--dg)')
    + linha('cb',  'var(--fecho)')
    + xLbls
    + '<g id="chart-hover-dots" visibility="hidden">'
    +   '<circle r="5.5" fill="none" stroke="var(--ev)"    stroke-width="2" id="hd-ing"/>'
    +   '<circle r="5.5" fill="none" stroke="var(--dg)"    stroke-width="2" id="hd-cv"/>'
    +   '<circle r="5.5" fill="none" stroke="var(--fecho)" stroke-width="2" id="hd-cb"/>'
    + '</g>'
    + '</svg>';
}

// ── Tooltip do gráfico ────────────────────────────────────────────
// Um listener no SVG inteiro (em vez de faixas invisíveis por mês): converte a
// posição do mouse do espaço da tela para o do viewBox e arredonda ao mês mais
// próximo, o que mantém o alvo utilizável mesmo com o card estreito.
const TT_SERIES = [
  { k: 'ing', label: 'Ingressos',          cor: 'var(--ev)'    },
  { k: 'cv',  label: 'Consultas vendidas', cor: 'var(--dg)'    },
  { k: 'cb',  label: 'Combos',             cor: 'var(--fecho)' },
];

function attachChartHover() {
  const wrap    = document.getElementById('chart-wrap');
  const holder  = wrap.parentElement;
  const tooltip = document.getElementById('chart-tooltip');
  const svg     = wrap.querySelector('svg');
  if (!svg || !chartGeo) return;

  const cursor = svg.querySelector('#chart-cursor');
  const dots   = svg.querySelector('#chart-hover-dots');
  const g      = chartGeo;
  const py     = v => g.base - (v / g.max) * (g.base - g.pt);

  function esconde() {
    tooltip.hidden = true;
    if (cursor) cursor.setAttribute('visibility', 'hidden');
    if (dots)   dots.setAttribute('visibility', 'hidden');
  }

  function mostra(e) {
    const r = svg.getBoundingClientRect();
    if (!r.width) return;
    const xView = (e.clientX - r.left) / r.width * g.W;
    let i = g.step ? Math.round((xView - g.pl) / g.step) : 0;
    i = Math.max(0, Math.min(g.n - 1, i));
    const d = g.serie[i];
    if (!d) return;

    tooltip.innerHTML = '<div class="tt-mes">' + esc(d.mes) + '</div>'
      + TT_SERIES.map(sr =>
          '<div class="tt-row">'
          + '<span class="tt-dot" style="background:' + sr.cor + '"></span>'
          + '<span class="tt-label">' + sr.label + '</span>'
          + '<span class="tt-val">' + fNum(d[sr.k] || 0) + '</span>'
          + '</div>').join('');
    tooltip.hidden = false;

    const xPonto = g.pl + g.step * i;
    if (cursor) {
      cursor.setAttribute('x1', xPonto);
      cursor.setAttribute('x2', xPonto);
      cursor.setAttribute('visibility', 'visible');
    }
    if (dots) {
      TT_SERIES.forEach(sr => {
        const c = dots.querySelector('#hd-' + sr.k);
        if (c) { c.setAttribute('cx', xPonto); c.setAttribute('cy', py(d[sr.k] || 0)); }
      });
      dots.setAttribute('visibility', 'visible');
    }

    // Posiciona ao lado do cursor, sem vazar do card.
    const hr  = holder.getBoundingClientRect();
    const tw  = tooltip.offsetWidth, th = tooltip.offsetHeight;
    let left  = e.clientX - hr.left + 14;
    if (left + tw > hr.width) left = e.clientX - hr.left - tw - 14;
    let top   = e.clientY - hr.top - th / 2;
    top = Math.max(0, Math.min(hr.height - th, top));
    tooltip.style.left = Math.max(0, left) + 'px';
    tooltip.style.top  = top + 'px';
  }

  svg.addEventListener('mousemove', mostra);
  svg.addEventListener('mouseleave', esconde);
  // Toque: mostra no ponto tocado e some ao soltar.
  svg.addEventListener('touchmove', ev => { if (ev.touches[0]) mostra(ev.touches[0]); }, { passive: true });
  svg.addEventListener('touchend', esconde);
}

// ── Carregamento ──────────────────────────────────────────────────
let granularidadeAtual = 'mes';

function montaParams() {
  return {
    year:          yearDropdown.getValue(),
    months:        monthDropdown.getValue(),
    tipo_lead:     tipoDropdown.getValue(),
    form:          formDropdown.getValue(),
    responsavel:   respDropdown.getValue(),
    data_inicio:   getDateInput('data-inicio'),
    data_fim:      getDateInput('data-fim'),
    granularidade: granularidadeAtual,
  };
}

const TITULOS_GRAN = { mes: 'Evolução mensal', semana: 'Evolução semanal', dia: 'Evolução diária' };

function setGranularidade(g) {
  granularidadeAtual = g;
  document.querySelectorAll('#gran-toggle .gran-btn').forEach(b => b.classList.toggle('active', b.dataset.gran === g));
  document.getElementById('chart-title').textContent = TITULOS_GRAN[g];
  applyFilters();
}

async function loadDashboard(f) {
  const overlay = document.getElementById('loading-overlay');
  const erro    = document.getElementById('error-banner');

  overlay.classList.remove('hidden');
  document.body.classList.add('loading');

  const params = new URLSearchParams();
  Object.entries(f).forEach(([k, v]) => { if (v) params.set(k, v); });

  try {
    const res  = await fetch(API + '?' + params.toString(), { cache: 'no-store' });
    const data = await res.json();
    if (!res.ok || data.error) {
      erro.textContent = 'Erro ao carregar dados: ' + (data.error || res.status);
      erro.classList.add('visible');
      return;
    }
    erro.classList.remove('visible');

    const t = data.totais || {};
    const ev = data.evento || {}, dg = data.digital || {}, rt = data.retencao || {};

    renderTotais(t);

    renderChips('kpi-ev', 'evento', [
      { l: 'Total Leads',     v: fNum(ev.totalLeads) },
      { l: 'Qualificados',    v: fNum(ev.quali) },
      { l: 'Ingressos',       v: fNum(ev.ing) },
      { l: 'Consultas Vend.', v: fNum(ev.cv) },
      { l: 'Consultas Real.', v: fNum(ev.cr) },
      { l: 'Combos Vend.',    v: fNum(ev.cb) },
      { l: 'Distratos',       v: fNum(ev.dist), risco: true },
      { l: 'Valor Vendido',   v: fBRL(ev.valor), money: true }
    ]);
    renderChips('kpi-dg', 'consulta', [
      { l: 'Total Leads',     v: fNum(dg.totalLeads) },
      { l: 'Qualificados',    v: fNum(dg.quali) },
      { l: 'Consultas Vend.', v: fNum(dg.cv) },
      { l: 'Consultas Real.', v: fNum(dg.cr) },
      { l: 'Combos Vend.',    v: fNum(dg.cb) },
      { l: 'Distratos',       v: fNum(dg.dist), risco: true },
      { l: 'Valor Vendido',   v: fBRL(dg.valor), money: true }
    ]);
    renderChips('kpi-rt', 'fecho', [
      { l: 'Combos Vendidos', v: fNum(rt.cb) },
      { l: 'Valor Vendido',   v: fBRL(rt.valor), money: true }
    ]);

    renderFunil('funil-ev', 'fm-ev-cb', 'fm-ev-valor', ev, [
      { l: 'Qualificados',    v: ev.quali || 0 },
      { l: 'Ingressos',       v: ev.ing   || 0 },
      { l: 'Consultas Vend.', v: ev.cv    || 0 },
      { l: 'Consultas Real.', v: ev.cr    || 0 },
      { l: 'Combos',          v: ev.cb    || 0 }
    ]);
    renderFunil('funil-dg', 'fm-dg-cb', 'fm-dg-valor', dg, [
      { l: 'Qualificados',    v: dg.quali || 0 },
      { l: 'Consultas Vend.', v: dg.cv    || 0 },
      { l: 'Consultas Real.', v: dg.cr    || 0 },
      { l: 'Combos',          v: dg.cb    || 0 }
    ]);

    document.getElementById('chart-wrap').innerHTML = buildChart(data.tempo || []);
    document.getElementById('chart-tooltip').hidden = true;
    attachChartHover();
    document.getElementById('last-updated').textContent = 'Atualizado em: ' + new Date().toLocaleString('pt-BR');
  } catch (err) {
    erro.textContent = 'Falha ao carregar: ' + err.message;
    erro.classList.add('visible');
  } finally {
    overlay.classList.add('hidden');
    document.body.classList.remove('loading');
  }
}

function applyFilters() { updateBadge(); loadDashboard(montaParams()); }

// ── Badge do período selecionado ────────────────────────────────
function updateBadge() {
  const badge      = document.getElementById('filter-label-badge');
  const year       = yearDropdown.getValue();
  const months     = monthDropdown.getValue();
  const tipo       = tipoDropdown.getValue();
  const form       = formDropdown.getValue();
  const responsavel = respDropdown.getValue();
  if (!year && !months && !tipo && !form && !responsavel) {
    badge.textContent = 'TODOS OS PERÍODOS';
    return;
  }
  const parts = [];
  if (responsavel) parts.push('Resp: ' + responsavel);
  if (form)        parts.push('Form: ' + form);
  if (tipo)        parts.push('Tipo: ' + tipo);
  if (year)        parts.push(year);
  if (months) {
    const nums   = months.split(',').map(Number);
    const labels = nums.map(n => MONTHS[n - 1]);
    parts.push(labels.join(', '));
  }
  badge.textContent = parts.join(' · ').toUpperCase();
}

function clearFilters() {
  yearDropdown.reset();
  monthDropdown.reset();
  tipoDropdown.reset();
  formDropdown.reset();
  respDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim', 'data-fim-clear');
  yearDropdown.select([NOW_YEAR]);
  granularidadeAtual = 'mes';
  document.querySelectorAll('#gran-toggle .gran-btn').forEach(b => b.classList.toggle('active', b.dataset.gran === 'mes'));
  document.getElementById('chart-title').textContent = TITULOS_GRAN.mes;
  applyFilters();
}

document.getElementById('btn-apply').addEventListener('click', applyFilters);
document.getElementById('btn-clear').addEventListener('click', clearFilters);

// Botões de limpar dos date inputs
['data-inicio', 'data-fim'].forEach(id => {
  const input    = document.getElementById(id);
  const clearBtn = document.getElementById(id + '-clear');
  input?.addEventListener('change', () => clearBtn?.classList.toggle('visible', !!input.value));
  clearBtn?.addEventListener('click', () => clearDateInput(id, id + '-clear'));
});

applyFilters();
</script>
