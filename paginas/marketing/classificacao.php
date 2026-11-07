<div class="dash-marketing-classif">

<!-- ══════════════════════════════════════════════════════════════
     Marketing — Desempenho por Classificação de Campanha (7ª aba,
     porte do relatório Power BI de mesmo nome).

     Classifica por FORM (condFormClasse — prefixo OTR/INA/OTW/WEB =
     Evento, resto = Digital), MESMO eixo da Visão Geral — diferente
     do Comparativo Anual, que classifica por nome de campanha (ver
     nota em comparativo.php). Os 9 KPIs do topo são os MESMOS de
     dados() (montarKpisMarketing(), zero fórmula nova); só o layout
     muda (uma linha de 9, em vez de 7 + card financeiro separado),
     pra bater com o print do PBI usado como referência.

     Abaixo, 3 gráficos de linha (Combos/Consultas Vendidas/Cadeiras
     Vendidas), cada um com 2 séries (Digital×Evento) e alternância
     de granularidade Ano-Mês/Semana do Ano/Dia do Mês — endpoint
     próprio (classificacaoGrafico), separado dos KPIs: trocar o
     toggle não reconsulta classificacaoDados.

     Todo o CSS novo vive em css/bi-theme.css, seção
     ".dash-marketing-classif" — nada de <style> inline aqui. O que já
     é global (kpi-chip, gran-toggle/gran-btn, bi-card, tab-switch,
     bi-tooltip, filters) é reusado sem alteração.
     ══════════════════════════════════════════════════════════════ -->

<!-- ── LOADING ─────────────────────────────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>

<!-- ── HEADER ──────────────────────────────────────────────── -->
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
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/eventos<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Eventos</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/desempenho<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Desempenho</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/descartes<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Descartes</a>
      <a class="tab-switch-btn active" href="<?= $this->site ?>marketing/classificacao<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Classificação</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
    </div>
  </div>
</header>

<!-- ── FILTROS (recolhível — mesmo padrão/markup das outras abas) ── -->
<section class="filters is-collapsible" id="filters-section" aria-label="Filtros do dashboard">
  <button class="filters-toggle" id="filters-toggle" type="button" aria-expanded="true" aria-controls="filters-fields">
    <span>Filtros</span>
    <span class="dd-caret filters-toggle-caret">▾</span>
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
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="camp-input" class="camp-input" type="text" placeholder="Pesquisar campanha..." autocomplete="off" aria-label="Pesquisar campanha" aria-autocomplete="list" aria-controls="camp-dropdown">
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
    <span class="filter-label">Conj. Anúncios</span>
    <div class="camp-wrap" id="conj-wrap">
      <div class="camp-tags" id="conj-tags"></div>
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="conj-input" class="camp-input" type="text" placeholder="Pesquisar conjunto..." autocomplete="off" aria-label="Pesquisar conjunto de anúncios" aria-autocomplete="list" aria-controls="conj-dropdown">
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
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="an-input" class="camp-input" type="text" placeholder="Pesquisar anúncio..." autocomplete="off" aria-label="Pesquisar anúncio" aria-autocomplete="list" aria-controls="an-dropdown">
        <button class="camp-clear" id="an-clear" aria-label="Limpar anúncio" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-dropdown" id="an-dropdown" role="listbox"></div>
    </div>
  </div>

  <button class="btn-apply" id="btn-apply">Aplicar</button>
  <button class="btn-clear" id="btn-clear">Limpar</button>
  </div>
</section>

<!-- ── ERRO ────────────────────────────────────────────────── -->
<div id="error-banner" role="alert"></div>

<!-- ── CONTEÚDO PRINCIPAL ──────────────────────────────────── -->
<main class="main" id="dashboard-main" aria-label="Dashboard de marketing — Classificação de Campanha">

  <!-- LINHA DE KPIs — 9 chips numa linha só (não 7 + card financeiro
       separado como as outras abas): é como o print do Power BI usado
       de referência mostra. Todos os 9 campos vêm prontos de
       classificacaoDados() (montarKpisMarketing(), mesma fórmula da
       Visão Geral) — sem cálculo novo aqui. -->
  <div class="kpi-row kpi-top">
    <div class="kpi-chip">
      <span class="kpi-chip-label">Total de Leads</span>
      <span class="kpi-chip-val" id="kpi-leads">—</span>
      <span class="kpi-sub">&nbsp;</span>
    </div>
    <div class="kpi-chip">
      <span class="kpi-chip-label">Qualificados</span>
      <span class="kpi-chip-val" id="kpi-qualificados">—</span>
      <span class="kpi-sub">&nbsp;</span>
    </div>
    <div class="kpi-chip">
      <span class="kpi-chip-label">% Qualificação</span>
      <span class="kpi-chip-val" id="kpi-pct-qualificados">—</span>
      <span class="kpi-sub">&nbsp;</span>
    </div>
    <div class="kpi-chip evento">
      <span class="kpi-chip-label">Cadeiras Vendidas</span>
      <span class="kpi-chip-val" id="kpi-cadeiras">—</span>
      <span class="kpi-sub">&nbsp;</span>
    </div>
    <div class="kpi-chip consulta">
      <span class="kpi-chip-label">Consultas Vendidas</span>
      <span class="kpi-chip-val" id="kpi-consultas-vendidas">—</span>
      <span class="kpi-sub" id="kpi-consultas-vendidas-sub"></span>
    </div>
    <div class="kpi-chip consulta">
      <span class="kpi-chip-label">Consultas Realizadas</span>
      <span class="kpi-chip-val" id="kpi-consultas-realizadas">—</span>
      <span class="kpi-sub">&nbsp;</span>
    </div>
    <div class="kpi-chip fecho">
      <span class="kpi-chip-label">Combos Vendidos</span>
      <span class="kpi-chip-val" id="kpi-combos">—</span>
      <span class="kpi-sub" id="kpi-combos-sub"></span>
    </div>
    <div class="kpi-chip fecho">
      <span class="kpi-chip-label">% Conv. Combo</span>
      <span class="kpi-chip-val" id="kpi-conv-combo">—</span>
      <span class="kpi-sub">&nbsp;</span>
    </div>
    <div class="kpi-chip">
      <span class="kpi-chip-label">Investimento</span>
      <span class="kpi-chip-val" id="kpi-investimento">—</span>
      <span class="kpi-sub">&nbsp;</span>
    </div>
  </div>

  <!-- TOGGLE DE GRANULARIDADE — reusa .gran-toggle/.gran-btn global.
       Troca de modo dispara SÓ classificacaoGrafico (ver JS), não
       reconsulta os KPIs. -->
  <div class="classif-toolbar">
    <div class="gran-toggle" id="gran-toggle-classif">
      <button class="gran-btn active" data-modo="anoMes" type="button">Ano-Mês</button>
      <button class="gran-btn" data-modo="semana_ano" type="button">Semana do Ano</button>
      <button class="gran-btn" data-modo="dia_mes" type="button">Dia do Mês</button>
    </div>
  </div>

  <!-- 3 GRÁFICOS — Combos / Consultas Vendidas / Cadeiras Vendidas,
       cada um com 2 séries (Digital×Evento). -->
  <div class="classif-charts">

    <div class="bi-card classif-chart-card">
      <div class="bi-card-header">
        <span class="bi-card-title">Combos Vendidos</span>
        <span class="classif-chart-sub" id="chart-combos-sub"></span>
      </div>
      <div class="bi-card-body">
        <div class="classif-legend">
          <span class="classif-legend-item"><span class="classif-legend-dot digital"></span>Digital</span>
          <span class="classif-legend-item"><span class="classif-legend-dot evento"></span>Evento</span>
        </div>
        <div class="classif-chart-wrap" id="chart-combos"></div>
      </div>
    </div>

    <div class="bi-card classif-chart-card">
      <div class="bi-card-header">
        <span class="bi-card-title">Consultas Vendidas</span>
        <span class="classif-chart-sub" id="chart-consultas-sub"></span>
      </div>
      <div class="bi-card-body">
        <div class="classif-legend">
          <span class="classif-legend-item"><span class="classif-legend-dot digital"></span>Digital</span>
          <span class="classif-legend-item"><span class="classif-legend-dot evento"></span>Evento</span>
        </div>
        <div class="classif-chart-wrap" id="chart-consultas"></div>
      </div>
    </div>

    <div class="bi-card classif-chart-card">
      <div class="bi-card-header">
        <span class="bi-card-title">Cadeiras Vendidas</span>
        <span class="classif-chart-sub" id="chart-cadeiras-sub"></span>
      </div>
      <div class="bi-card-body">
        <div class="classif-legend">
          <span class="classif-legend-item"><span class="classif-legend-dot digital"></span>Digital</span>
          <span class="classif-legend-item"><span class="classif-legend-dot evento"></span>Evento</span>
        </div>
        <div class="classif-chart-wrap" id="chart-cadeiras"></div>
      </div>
    </div>

  </div>

</main>

<div class="bi-tooltip" id="classif-tip" role="tooltip"></div>

</div>

<script>
// ══════════════════════════════════════════════════════════════
//  createMultiDropdown, createSearchDropdown, getDateInput,
//  clearDateInput, initFiltersToggle, fNum, fBRL, fPct
//  são fornecidas por js/bi-dashboard.js (carregado no <head> pelo
//  template) — não redefinidas aqui.
// ══════════════════════════════════════════════════════════════

const API_DADOS   = '<?= $this->site ?>marketing/classificacaoDados';
const API_GRAFICO = '<?= $this->site ?>marketing/classificacaoGrafico';
const CAMPANHAS_URL = '<?= $this->site ?>marketing/campanhas';
const FORMS_URL     = '<?= $this->site ?>marketing/forms';
const CONJUNTOS_URL = '<?= $this->site ?>marketing/conjuntosAnuncio';
const ANUNCIOS_URL  = '<?= $this->site ?>marketing/anuncios';
const YEARS  = ['2024', '2025', '2026'];
const MONTHS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

const yearDropdown  = createMultiDropdown('year-wrap',  'year-btn',  'year-list',  YEARS,  l => l, l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);
const campDropdown  = createSearchDropdown('camp-wrap', 'camp-input', 'camp-dropdown', 'camp-clear', 'camp-tags', 'Nenhuma campanha encontrada');
const formDropdown  = createSearchDropdown('form-wrap', 'form-input', 'form-dropdown', 'form-clear', 'form-tags', 'Nenhum form encontrado');
const conjDropdown  = createSearchDropdown('conj-wrap', 'conj-input', 'conj-dropdown', 'conj-clear', 'conj-tags', 'Nenhum conjunto encontrado');
const anDropdown    = createSearchDropdown('an-wrap',   'an-input',   'an-dropdown',   'an-clear',   'an-tags',   'Nenhum anúncio encontrado');

yearDropdown.select([String(new Date().getFullYear())]);

// "Últimos N [dias|semanas|meses]" — um substitui o outro com Ano/Mês/Data
// (ver createRelativePeriod em js/bi-dashboard.js).
const relPeriod = createRelativePeriod({
  numId: 'rel-n', unitId: 'rel-unit',
  conflicts: [[yearDropdown, 'year-list'], [monthDropdown, 'month-list']],
});

fetch(CAMPANHAS_URL, { cache: 'no-store' }).then(r => r.json()).then(l => campDropdown.load(Array.isArray(l) ? l : [])).catch(err => console.error('Erro ao carregar campanhas:', err));
fetch(FORMS_URL,     { cache: 'no-store' }).then(r => r.json()).then(l => formDropdown.load(Array.isArray(l) ? l : [])).catch(err => console.error('Erro ao carregar forms:', err));
fetch(CONJUNTOS_URL, { cache: 'no-store' }).then(r => r.json()).then(l => conjDropdown.load(Array.isArray(l) ? l : [])).catch(err => console.error('Erro ao carregar conjuntos:', err));
fetch(ANUNCIOS_URL,  { cache: 'no-store' }).then(r => r.json()).then(l => anDropdown.load(Array.isArray(l) ? l : [])).catch(err => console.error('Erro ao carregar anúncios:', err));

function set(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }

function escHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function applyTheme(theme) {
  var isDark = theme === 'dark';
  document.documentElement.classList.toggle('dark', isDark);
  var btn = document.getElementById('theme-btn'), moon = document.getElementById('theme-icon-moon'), sun = document.getElementById('theme-icon-sun');
  if (btn) btn.classList.toggle('active', isDark);
  if (moon) moon.style.display = isDark ? 'none' : '';
  if (sun) sun.style.display = isDark ? '' : 'none';
}
function toggleTheme() {
  var theme = localStorage.getItem('theme') === 'dark' ? 'light' : 'dark';
  localStorage.setItem('theme', theme);
  applyTheme(theme);
}

/* ───────────────────────── Tooltip compartilhado (.bi-tooltip já existe
   em css/bi-theme.css — reusado tal como está) ───────────────────────── */
const tipEl = document.getElementById('classif-tip');
function showTip(clientX, clientY, html, minY) {
  tipEl.innerHTML = html;
  tipEl.classList.add('visible');
  const pad = 16;
  let x = clientX + pad, y = clientY + pad;
  tipEl.style.left = x + 'px';
  tipEl.style.top = y + 'px';
  const rect = tipEl.getBoundingClientRect();
  if (rect.right > window.innerWidth) x = clientX - rect.width - pad;
  if (rect.bottom > window.innerHeight) y = clientY - rect.height - pad;
  y = Math.max(y, minY || 0);
  tipEl.style.left = Math.max(4, x) + 'px';
  tipEl.style.top = Math.max(4, y) + 'px';
}
function hideTip() { tipEl.classList.remove('visible'); }
function tipRowDot(cls, label, val) {
  return '<div class="bi-tooltip-row"><span class="bi-tooltip-label"><span class="classif-tip-dot ' + cls + '"></span>' + escHtml(label) + '</span><span class="bi-tooltip-val">' + val + '</span></div>';
}
function tipHtml(title, rows) {
  return '<div class="bi-tooltip-inner"><div class="bi-tooltip-title">' + escHtml(title) + '</div>' + rows.join('') + '</div>';
}

/* ───────────────────────── KPIs (mesmas fórmulas da Visão Geral) ───────────────────────── */

function fillKpis(k) {
  set('kpi-leads', fNum(k.total_leads));
  set('kpi-qualificados', fNum(k.qualificados));
  set('kpi-pct-qualificados', fPct(k.pct_qualificados));
  set('kpi-cadeiras', fNum(k.cadeira_vendida));
  set('kpi-consultas-vendidas', fNum(k.consultas_vendidas));
  set('kpi-consultas-vendidas-sub', fBRL(k.faturamento_consultas));
  set('kpi-consultas-realizadas', fNum(k.consultas_realizadas));
  set('kpi-combos', fNum(k.combos_vendidos));
  set('kpi-combos-sub', fBRL(k.faturamento_combos));
  set('kpi-conv-combo', fPct(k.conv_combo));
  set('kpi-investimento', fBRL(k.investimento));
}

/* ───────────────────────── RENDER: gráfico de linha com 2 séries (Digital×Evento) ─────────────────────────
   Adaptado de paginas/marketing/desempenho.php::renderTimeChart() (grid+
   linha+hover), generalizado para 2 séries simultâneas em vez de 1 —
   mesma convenção de eixo X sempre horizontal (nunca rotacionado, ver nota
   em desempenho.php) e mesmo desbaste de rótulo por densidade. */
function renderChartDuas(containerId, subId, serie) {
  const el = document.getElementById(containerId), sub = document.getElementById(subId);
  if (sub) sub.textContent = serie.length + ' pontos';
  if (!serie || !serie.length) { el.innerHTML = '<div class="classif-chart-empty">Sem dados no período</div>'; return; }

  const valsD = serie.map(d => d.digital);
  const valsE = serie.map(d => d.evento);
  const n = serie.length;
  const W = 720, H = 100, pl = 30, pr = 16, pt = 10, pb = 18;
  const cW = W - pl - pr, cH = H - pt - pb;
  const maxVal = Math.max.apply(null, valsD.concat(valsE).concat([0.0001]));
  const max = maxVal * 1.15;
  const step = n > 1 ? cW / (n - 1) : 0;
  const px = i => pl + step * i;
  const py = v => pt + cH - (max > 0 ? (v / max) * cH : 0);

  let grid = '';
  for (let g = 1; g <= 3; g++) {
    const gy = pt + cH * (g / 4);
    grid += '<line class="classif-grid-line" x1="' + pl + '" x2="' + (W - pr) + '" y1="' + gy.toFixed(1) + '" y2="' + gy.toFixed(1) + '" stroke-dasharray="2 3"/>';
  }

  const r = n > 40 ? 1.6 : 2.2;
  const linhaSerie = (vals, cls) => {
    const d = vals.map((v, i) => (i ? 'L' : 'M') + px(i).toFixed(1) + ' ' + py(v).toFixed(1)).join(' ');
    let out = '<path class="classif-line ' + cls + '" d="' + d + '"/>';
    vals.forEach((v, i) => {
      out += '<circle class="classif-dot ' + cls + '" cx="' + px(i).toFixed(1) + '" cy="' + py(v).toFixed(1) + '" r="' + r + '"/>';
    });
    return out;
  };
  // Digital primeiro: Evento fica por cima (mesma leitura do print — a
  // linha de Evento costuma ter o volume menor e não pode sumir atrás).
  const linhas = linhaSerie(valsD, 'digital') + linhaSerie(valsE, 'evento');

  // Rótulos de dado por cima de tudo (linhas+pontos das duas séries), sempre
  // visíveis nas duas séries — decisão do usuário, mesmo em período denso.
  // Fonte encolhe com a densidade (mesmo gatilho de
  // desempenho.php::renderTimeChart()); quando os dois rótulos do mesmo
  // índice ficam perto demais um do outro, o que estiver mais baixo sobe o
  // mínimo necessário pra não sobrepor o outro. Degraus calibrados pros
  // máximos reais desta view: "Dia do Mês" nunca passa de 31 pontos
  // (DAYOFMONTH agregado, não é linha do tempo contínua), "Semana do Ano" e
  // "Ano-Mês" podem passar disso num período amplo — só aí cai no degrau
  // mais compacto.
  const ptFontSize = n > 40 ? 6.6 : (n > 20 ? 7.4 : (n > 10 ? 8.0 : 8.8));
  const gapMinimo = ptFontSize + 3;
  let labels = '';
  serie.forEach((d, i) => {
    const extra = (n > 15 && i % 2 === 1) ? ptFontSize + 1 : 0;
    let lyD = py(valsD[i]) - 6 - extra;
    let lyE = py(valsE[i]) - 6 - extra;
    if (Math.abs(lyD - lyE) < gapMinimo) {
      const push = gapMinimo - Math.abs(lyD - lyE);
      if (lyD < lyE) lyD -= push; else lyE -= push;
    }
    labels += '<text class="classif-pt-label digital" style="font-size:' + ptFontSize + 'px" x="' + px(i).toFixed(1) + '" y="' + lyD.toFixed(1) + '" text-anchor="middle">' + fNum(valsD[i]) + '</text>';
    labels += '<text class="classif-pt-label evento" style="font-size:' + ptFontSize + 'px" x="' + px(i).toFixed(1) + '" y="' + lyE.toFixed(1) + '" text-anchor="middle">' + fNum(valsE[i]) + '</text>';
  });

  const maxChars = serie.reduce((m, d) => Math.max(m, String(d.label).length), 0);
  const largMin = maxChars * 6.4 + 14;
  const passo = Math.max(1, Math.ceil(largMin / Math.max(step, 0.001)));
  let axis = '';
  serie.forEach((d, i) => {
    if (i % passo !== 0 && i !== n - 1) return;
    axis += '<text class="classif-axis-label" x="' + px(i).toFixed(1) + '" y="' + (H - 8) + '" text-anchor="middle">' + escHtml(d.label) + '</text>';
  });

  const hit = '<rect x="0" y="0" width="' + W + '" height="' + H + '" fill="transparent" class="classif-hit"/>';
  el.innerHTML = '<svg viewBox="0 0 ' + W + ' ' + H + '" width="100%" preserveAspectRatio="none" style="display:block">' + grid + linhas + labels + axis + hit + '</svg>';

  el._chartState = { serie, pl, step, n, W };
}

function attachChartHover(containerId) {
  const el = document.getElementById(containerId);
  el.addEventListener('mousemove', e => {
    const st = el._chartState;
    const svg = el.querySelector('svg');
    if (!st || !svg) return;
    const rect = svg.getBoundingClientRect();
    if (!rect.width) return;
    const scaleX = st.W / rect.width;
    const svgX = (e.clientX - rect.left) * scaleX;
    let idx = st.step > 0 ? Math.round((svgX - st.pl) / st.step) : 0;
    idx = Math.max(0, Math.min(st.n - 1, idx));
    const d = st.serie[idx];
    showTip(e.clientX, e.clientY, tipHtml(d.label, [
      tipRowDot('digital', 'Digital', fNum(d.digital)),
      tipRowDot('evento', 'Evento', fNum(d.evento)),
    ]), rect.top);
  });
  el.addEventListener('mouseleave', hideTip);
}

attachChartHover('chart-combos');
attachChartHover('chart-consultas');
attachChartHover('chart-cadeiras');

/* ───────────────────────── Toggle de granularidade ─────────────────────────
   Troca de modo refaz SÓ o fetch do gráfico (classificacaoGrafico) — os
   KPIs não dependem de modo e não são reconsultados (mesmo padrão de
   paginas/helenacastro/index.php::setModo()). */
let modoAtual = 'anoMes';
let ultimosFiltros = {};

document.getElementById('gran-toggle-classif').addEventListener('click', e => {
  const btn = e.target.closest('.gran-btn');
  if (!btn) return;
  modoAtual = btn.dataset.modo;
  document.querySelectorAll('#gran-toggle-classif .gran-btn').forEach(b => b.classList.toggle('active', b === btn));
  loadGrafico(modoAtual);
});

/* ══════════════════════════════════════════════════════════════
   Carregar dashboard
   ══════════════════════════════════════════════════════════════ */
function montaParams() {
  const rel = relPeriod.getRange();
  return {
    year:        yearDropdown.getValue(),
    months:      monthDropdown.getValue(),
    campanha:    campDropdown.getValue(),
    conjunto:    conjDropdown.getValue(),
    anuncio:     anDropdown.getValue(),
    form:        formDropdown.getValue(),
    data_inicio: rel ? rel.start : getDateInput('data-inicio'),
    data_fim:    rel ? rel.end   : getDateInput('data-fim'),
  };
}

async function loadKpis(f) {
  const params = new URLSearchParams();
  Object.entries(f).forEach(([k, v]) => { if (v) params.set(k, v); });
  const res  = await fetch(API_DADOS + '?' + params);
  const data = await res.json();
  if (!res.ok || data.error) throw new Error(data.error || 'Erro ao carregar KPIs.');
  fillKpis(data.kpis);
}

async function loadGrafico(modo) {
  const params = new URLSearchParams();
  Object.entries(ultimosFiltros).forEach(([k, v]) => { if (v) params.set(k, v); });
  params.set('modo', modo);
  const res  = await fetch(API_GRAFICO + '?' + params);
  const data = await res.json();
  if (!res.ok || data.error) throw new Error(data.error || 'Erro ao carregar gráfico.');
  renderChartDuas('chart-combos', 'chart-combos-sub', data.combos);
  renderChartDuas('chart-consultas', 'chart-consultas-sub', data.consultas);
  renderChartDuas('chart-cadeiras', 'chart-cadeiras-sub', data.cadeiras);
}

async function loadDashboard(f) {
  const overlay = document.getElementById('loading-overlay');
  const errorBanner = document.getElementById('error-banner');

  overlay.classList.remove('hidden');
  document.body.classList.add('loading');
  ultimosFiltros = f;

  try {
    await Promise.all([loadKpis(f), loadGrafico(modoAtual)]);
    errorBanner.classList.remove('visible');
  } catch (err) {
    errorBanner.textContent = 'Erro ao carregar dados: ' + err.message;
    errorBanner.classList.add('visible');
  } finally {
    overlay.classList.add('hidden');
    document.body.classList.remove('loading');
  }
}

function applyFilters() {
  updateBadge();
  loadDashboard(montaParams());
}

function clearFilters() {
  relPeriod.reset();
  yearDropdown.reset(); monthDropdown.reset();
  campDropdown.reset(); formDropdown.reset();
  conjDropdown.reset(); anDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim', 'data-fim-clear');
  applyFilters();
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

document.getElementById('btn-apply').addEventListener('click', applyFilters);
document.getElementById('btn-clear').addEventListener('click', clearFilters);

initFiltersToggle({ key: 'marketing-classificacao' });

['data-inicio', 'data-fim'].forEach(id => {
  const input    = document.getElementById(id);
  const clearId  = id + '-clear';
  const clearBtn = document.getElementById(clearId);
  input?.addEventListener('change', () => {
    clearBtn?.classList.toggle('visible', !!input.value);
  });
  clearBtn?.addEventListener('click', () => {
    clearDateInput(id, clearId);
  });
});

applyTheme(localStorage.getItem('theme') || 'light');
applyFilters();
</script>
