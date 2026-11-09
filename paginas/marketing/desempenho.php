<div class="dash-marketing dash-marketing-desempenho">

<!-- ══════════════════════════════════════════════════════════════
     Marketing — Desempenho (5ª aba, "Dashboard ADS" do Power BI).
     Layout: KPIs no topo (mesma faixa de 7 chips da Visão Geral); abaixo,
     split de 2 colunas — Resumo Financeiro + 3 rankings de perfil do lead
     (~34%) à esquerda, seletor de métrica + 3 gráficos de série temporal +
     rosca de Formação (~66%) à direita. Validado antes num mock standalone
     (marketing-desempenho-mock.html, apagado após aprovação — ver plano).

     Todo o CSS novo vive em css/bi-theme.css, seção ".dash-marketing-desempenho"
     — nada de <style> inline aqui. O que já é global (kpi-chip, fin-row,
     roi-badge, fin-cac-grid, gran-toggle/gran-btn, bi-card, tab-switch,
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
      <a class="tab-switch-btn active" href="<?= $this->site ?>marketing/desempenho<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Desempenho</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/descartes<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Descartes</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/classificacao<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Classificação</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
    </div>
  </div>
</header>

<!-- ── FILTROS (recolhível — mesmo padrão/markup da Visão Geral) ── -->
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
<main class="main" id="dashboard-main" aria-label="Dashboard de marketing — Desempenho">

  <!-- LINHA 1 — mesma faixa de 7 KPIs da Visão Geral -->
  <div class="kpi-row kpi-top">
    <div class="kpi-chip">
      <span class="kpi-chip-label">Total de Leads</span>
      <span class="kpi-chip-val" id="kpi-leads">—</span>
      <span class="kpi-sub">&nbsp;</span>
    </div>
    <div class="kpi-chip">
      <span class="kpi-chip-label">Qualificados</span>
      <span class="kpi-chip-val" id="kpi-qualificados">—</span>
      <span class="kpi-sub" id="kpi-qualificados-sub"></span>
    </div>
    <div class="kpi-chip consulta">
      <span class="kpi-chip-label">Oportunidades</span>
      <span class="kpi-chip-val" id="kpi-oportunidades">—</span>
      <span class="kpi-sub" id="kpi-oportunidades-sub"></span>
    </div>
    <div class="kpi-chip evento">
      <span class="kpi-chip-label">Cadeiras Vendidas</span>
      <span class="kpi-chip-val" id="kpi-cadeiras">—</span>
      <span class="kpi-sub" id="kpi-cadeiras-sub"></span>
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
  </div>

  <!-- LINHA 2 — ~34% análises de perfil à esquerda, ~66% desempenho temporal à direita -->
  <div class="desemp-split">

    <div class="desemp-col-side">

      <!-- Financeiro — markup idêntico ao da Visão Geral, sem mudança de fórmula -->
      <div class="bi-card fin-card">
        <div class="bi-card-header"><span class="bi-card-title">Resumo Financeiro</span></div>
        <div class="bi-card-body">
          <div class="fin-invest-row">
            <div class="fin-row">
              <span class="fin-label">Investimento Total</span>
              <span class="fin-value invest" id="fin-invest">—</span>
            </div>
            <div style="text-align:right">
              <span class="fin-label" style="display:block;margin-bottom:3px">ROI(x)</span>
              <div class="roi-badge" id="fin-roi">—</div>
            </div>
          </div>
          <div class="fin-cac-grid">
            <div class="kpi-chip">
              <span class="kpi-chip-label">CPL</span>
              <span class="kpi-chip-val" id="fin-cpl">—</span>
            </div>
            <div class="kpi-chip evento">
              <span class="kpi-chip-label">CAC Cadeira</span>
              <span class="kpi-chip-val" id="fin-cac-cadeira">—</span>
            </div>
            <div class="kpi-chip consulta">
              <span class="kpi-chip-label">CAC Consulta</span>
              <span class="kpi-chip-val" id="fin-cac-consulta">—</span>
            </div>
            <div class="kpi-chip fecho">
              <span class="kpi-chip-label">CAC Combo</span>
              <span class="kpi-chip-val" id="fin-cac-combo">—</span>
            </div>
          </div>
        </div>
      </div>

      <div class="bi-card">
        <div class="bi-card-header">
          <span class="bi-card-title">Top Motivos de Descarte</span>
          <span class="desemp-rank-sub" id="rank-motivos-sub">—</span>
        </div>
        <div class="bi-card-body">
          <div class="desemp-rank-list" id="rank-motivos"></div>
        </div>
      </div>

      <div class="bi-card">
        <div class="bi-card-header">
          <span class="bi-card-title">Top Profissões</span>
          <span class="desemp-rank-sub" id="rank-profissoes-sub">—</span>
        </div>
        <div class="bi-card-body">
          <div class="desemp-rank-list" id="rank-profissoes"></div>
        </div>
      </div>

      <div class="bi-card">
        <div class="bi-card-header">
          <span class="bi-card-title">Tempo de Experiência</span>
          <span class="desemp-rank-sub" id="rank-tempo-sub">—</span>
        </div>
        <div class="bi-card-body">
          <div class="desemp-rank-list" id="rank-tempo"></div>
        </div>
      </div>

    </div>

    <div class="desemp-col-main">

      <div class="bi-card">
        <div class="bi-card-header"><span class="bi-card-title">Evolução por Métrica</span></div>
        <div class="bi-card-body">
          <div class="gran-toggle desemp-metric-toggle" id="metric-toggle">
            <button class="gran-btn" data-metrica="total_leads" type="button">Total de Leads</button>
            <button class="gran-btn" data-metrica="qualificados" type="button">Qualificados</button>
            <button class="gran-btn" data-metrica="pct_qualificacao" type="button">% Qualificação</button>
            <button class="gran-btn" data-metrica="descartados" type="button">Descartados</button>
            <button class="gran-btn active" data-metrica="cadeira_vendida" type="button">Cadeiras</button>
            <button class="gran-btn" data-metrica="consultas_vendidas" type="button">Consultas Vendidas</button>
            <button class="gran-btn" data-metrica="combos_vendidos" type="button">Combos</button>
            <button class="gran-btn" data-metrica="cpl" type="button">CPL</button>
            <button class="gran-btn" data-metrica="roi" type="button">ROI</button>
          </div>
        </div>
      </div>

      <div class="bi-card desemp-chart-card">
        <div class="bi-card-header">
          <span class="bi-card-title">Desempenho Mensal</span>
          <span class="desemp-chart-sub" id="chart-mensal-sub"></span>
        </div>
        <div class="bi-card-body"><div class="desemp-chart-wrap" id="chart-mensal"></div></div>
      </div>

      <div class="bi-card desemp-chart-card">
        <div class="bi-card-header">
          <span class="bi-card-title">Desempenho Semanal</span>
          <span class="desemp-chart-sub" id="chart-semanal-sub"></span>
        </div>
        <div class="bi-card-body"><div class="desemp-chart-wrap" id="chart-semanal"></div></div>
      </div>

      <div class="bi-card desemp-chart-card">
        <div class="bi-card-header">
          <span class="bi-card-title">Desempenho Diário</span>
          <span class="desemp-chart-sub" id="chart-diario-sub"></span>
        </div>
        <div class="bi-card-body"><div class="desemp-chart-wrap" id="chart-diario"></div></div>
      </div>

      <div class="bi-card">
        <div class="bi-card-header"><span class="bi-card-title">Nível de Formação</span></div>
        <div class="bi-card-body" id="donut-formacao"></div>
      </div>

    </div>

  </div>

</main>

<div class="bi-tooltip" id="desemp-tip" role="tooltip"></div>

</div>

<script>
// ══════════════════════════════════════════════════════════════
//  createMultiDropdown, createSearchDropdown, getDateInput,
//  clearDateInput, initFiltersToggle, fNum, fBRL, fROI, fPctRatio
//  são fornecidas por js/bi-dashboard.js (carregado no <head> pelo
//  template) — não redefinidas aqui.
// ══════════════════════════════════════════════════════════════

const API           = '<?= $this->site ?>marketing/desempenhoDados';
const CAMPANHAS_URL = '<?= $this->site ?>marketing/campanhas';
const FORMS_URL     = '<?= $this->site ?>marketing/forms';
const CONJUNTOS_URL = '<?= $this->site ?>marketing/conjuntosAnuncio';
const ANUNCIOS_URL  = '<?= $this->site ?>marketing/anuncios';
const YEARS  = anosDesde(2025);
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
const tipEl = document.getElementById('desemp-tip');
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
function tipRow(label, val) {
  return '<div class="bi-tooltip-row"><span class="bi-tooltip-label">' + escHtml(label) + '</span><span class="bi-tooltip-val">' + val + '</span></div>';
}
function tipHtml(title, rows) {
  return '<div class="bi-tooltip-inner"><div class="bi-tooltip-title">' + escHtml(title) + '</div>' + rows.join('') + '</div>';
}

/* ───────────────────────── KPIs + Financeiro (mesmas fórmulas da Visão Geral) ───────────────────────── */

function fillKpis(k) {
  set('kpi-leads', fNum(k.total_leads));
  set('kpi-qualificados', fNum(k.qualificados));
  set('kpi-qualificados-sub', fPct(k.pct_qualificados) + ' dos leads');
  set('kpi-oportunidades', fNum(k.oportunidades));
  set('kpi-oportunidades-sub', fPct(k.pct_oportunidades) + ' dos qualif.');
  set('kpi-cadeiras', fNum(k.cadeira_vendida));
  set('kpi-cadeiras-sub', fPct(k.conv_cadeira) + ' dos qualif.');
  set('kpi-consultas-vendidas', fNum(k.consultas_vendidas));
  set('kpi-consultas-vendidas-sub', fBRL(k.faturamento_consultas));
  set('kpi-consultas-realizadas', fNum(k.consultas_realizadas));
  set('kpi-combos', fNum(k.combos_vendidos));
  set('kpi-combos-sub', fBRL(k.faturamento_combos));
}

function fillFinanceiro(k) {
  set('fin-invest', fBRL(k.investimento));
  set('fin-roi', fROI(k.roi));
  set('fin-cpl', fBRL(k.cpl));
  set('fin-cac-cadeira', fBRL(k.cac_cadeira));
  set('fin-cac-consulta', fBRL(k.cac_consulta));
  set('fin-cac-combo', fBRL(k.cac_combos));
}

/* ───────────────────────── RENDER: ranking Top-N (Motivos/Profissões/Tempo Exp.) ───────────────────────── */

function renderRanking(containerId, subId, rows) {
  const el = document.getElementById(containerId), sub = document.getElementById(subId);
  if (!rows || !rows.length) { el.innerHTML = '<div class="desemp-rank-empty">Sem dados no período</div>'; if (sub) sub.textContent = '—'; return; }
  const max = Math.max.apply(null, rows.map(r => r.qtd));
  const total = rows.reduce((a, r) => a + r.qtd, 0);
  if (sub) sub.textContent = 'top ' + rows.length + ' · ' + fNum(total);
  el.innerHTML = rows.map((r, i) => {
    return '<div class="desemp-rank-row' + (i === 0 ? ' top' : '') + '" data-idx="' + i + '">' +
      '<span class="desemp-rank-label">' + escHtml(r.label) + '</span>' +
      '<div class="desemp-rank-track"><div class="desemp-rank-fill" style="width:' + Math.max(2, Math.round(r.qtd / max * 100)) + '%"></div></div>' +
      '<span class="desemp-rank-val">' + fNum(r.qtd) + '</span>' +
      '<span class="desemp-rank-pct">' + fPctRatio(r.qtd, total) + '</span>' +
      '</div>';
  }).join('');
  el.querySelectorAll('.desemp-rank-row').forEach(row => {
    const r = rows[+row.dataset.idx];
    const html = tipHtml(r.label, [tipRow('Quantidade', fNum(r.qtd)), tipRow('% do top', fPctRatio(r.qtd, total))]);
    row.addEventListener('mouseenter', e => showTip(e.clientX, e.clientY, html));
    row.addEventListener('mousemove', e => showTip(e.clientX, e.clientY, html));
    row.addEventListener('mouseleave', hideTip);
  });
}

/* ───────────────────────── RENDER: rosca (Nível de Formação) ───────────────────────── */

function renderDonut(containerId, data) {
  const body = document.getElementById(containerId);
  const total = (data || []).reduce((s, d) => s + d.qtd, 0);
  if (!total) { body.innerHTML = '<div class="desemp-chart-empty">Sem dados no período</div>'; return; }
  const palette = ['#1a6ec8', '#c8102e', '#0a7d4f', '#7c3aed', '#d4a017', '#0891b2', '#db2777'];
  const r = 52, cx = 60, cy = 60, sw = 16, circ = 2 * Math.PI * r;
  let offset = 0;
  const arcs = data.map((d, i) => {
    const frac = d.qtd / total, len = frac * circ;
    const arc = '<circle class="donut-arc" data-idx="' + i + '" cx="' + cx + '" cy="' + cy + '" r="' + r + '" fill="none" stroke="' + palette[i % palette.length] + '"' +
      ' stroke-width="' + sw + '" stroke-dasharray="' + len.toFixed(2) + ' ' + (circ - len).toFixed(2) + '" stroke-dashoffset="' + (-offset).toFixed(2) + '"' +
      ' transform="rotate(-90 ' + cx + ' ' + cy + ')" stroke-linecap="butt" pointer-events="stroke" style="cursor:default"/>';
    offset += len;
    return arc;
  }).join('');
  const svg = '<svg class="donut-svg" viewBox="0 0 120 120">' +
    '<circle cx="' + cx + '" cy="' + cy + '" r="' + r + '" fill="none" stroke="var(--border)" stroke-width="' + sw + '"/>' +
    arcs +
    '<text x="' + cx + '" y="' + (cy - 4) + '" text-anchor="middle" font-size="18" font-weight="700" fill="var(--text)" class="num">' + fNum(total) + '</text>' +
    '<text x="' + cx + '" y="' + (cy + 14) + '" text-anchor="middle" font-size="9" fill="var(--muted)">leads</text>' +
    '</svg>';
  const legend = data.map((d, i) => {
    return '<div class="donut-legend-item">' +
      '<span class="donut-legend-dot" style="background:' + palette[i % palette.length] + '"></span>' +
      '<span class="donut-legend-label">' + escHtml(d.label) + '</span>' +
      '<span class="donut-legend-val">' + fNum(d.qtd) + '</span>' +
      '<span class="donut-legend-pct">(' + fPctRatio(d.qtd, total) + ')</span>' +
      '</div>';
  }).join('');
  body.innerHTML = '<div class="donut-wrap">' + svg + '<div class="donut-legend">' + legend + '</div></div>';

  function tipFor(i) {
    const d = data[i];
    return tipHtml(d.label, [tipRow('Quantidade', fNum(d.qtd)), tipRow('% do total', fPctRatio(d.qtd, total))]);
  }
  body.querySelectorAll('.donut-arc').forEach(arc => {
    const i = +arc.dataset.idx;
    arc.addEventListener('mouseenter', e => showTip(e.clientX, e.clientY, tipFor(i)));
    arc.addEventListener('mousemove', e => showTip(e.clientX, e.clientY, tipFor(i)));
    arc.addEventListener('mouseleave', hideTip);
  });
  body.querySelectorAll('.donut-legend-item').forEach((item, i) => {
    item.addEventListener('mouseenter', e => showTip(e.clientX, e.clientY, tipFor(i)));
    item.addEventListener('mousemove', e => showTip(e.clientX, e.clientY, tipFor(i)));
    item.addEventListener('mouseleave', hideTip);
  });
}

/* ───────────────────────── MÉTRICAS DO SELETOR (9 pills) ─────────────────────────
   Backend devolve valores BRUTOS por bucket (total_leads, qualificados,
   descartados, cadeira_vendida, consultas_vendidas, combos_vendidos,
   custo_mkt, faturamento_combos) — %Qualificação/CPL/ROI são razões
   derivadas aqui (mesma filosofia de comparativo.php: "a média de razões
   não é a razão das médias"). */

function fUSD2(v) { return '$ ' + (v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

const METRICAS = {
  total_leads:        { label: 'Total de Leads',    color: '#c8102e', compute: d => d.total_leads,   fmt: fNum },
  qualificados:       { label: 'Qualificados',       color: '#1a6ec8', compute: d => d.qualificados,  fmt: fNum },
  pct_qualificacao:   { label: '% Qualificação',     color: '#7c3aed', compute: d => d.total_leads ? d.qualificados / d.total_leads : 0, fmt: v => (v * 100).toFixed(1) + '%' },
  descartados:        { label: 'Descartados',        color: '#d4a017', compute: d => d.descartados,  fmt: fNum },
  cadeira_vendida:    { label: 'Cadeiras',           color: '#c8102e', compute: d => d.cadeira_vendida, fmt: fNum },
  consultas_vendidas: { label: 'Consultas Vendidas', color: '#1a6ec8', compute: d => d.consultas_vendidas, fmt: fNum },
  combos_vendidos:    { label: 'Combos',             color: '#0a7d4f', compute: d => d.combos_vendidos, fmt: fNum },
  cpl:                { label: 'CPL',                color: '#d4a017', compute: d => d.qualificados ? d.custo_mkt / d.qualificados : 0, fmt: fUSD2 },
  roi:                { label: 'ROI',                color: '#0a7d4f', compute: d => d.custo_mkt ? d.faturamento_combos / d.custo_mkt : 0, fmt: fROI },
};
let metricaAtual = 'cadeira_vendida';
let SERIE = { mensal: [], semanal: [], diario: [] };

/* ───────────────────────── RENDER: gráfico linha+área (Mensal/Semanal/Diário) ─────────────────────────
   Adaptado de paginas/dentistas/index.php::buildChart() (área+linha+ponto+
   rótulo) — sem rotação em nenhum eixo: rótulo rotacionado a -40° provou,
   em tela real (não em reprodução isolada), distorcer glifos pequenos o
   bastante pra virar outra coisa (o "0" da fonte mono lia como a barra
   vizinha; "S01" na fonte sans lia como "C01"). Densidade é resolvida só
   por desbaste (mostrar menos rótulos de eixo), nunca por rotação. */
function renderTimeChart(containerId, subId, serie, metricKey) {
  const el = document.getElementById(containerId), sub = document.getElementById(subId);
  const meta = METRICAS[metricKey];
  if (sub) sub.textContent = meta.label + ' · ' + serie.length + ' pontos';
  if (!serie || !serie.length) { el.innerHTML = '<div class="desemp-chart-empty">Sem dados no período</div>'; return; }

  const vals = serie.map(d => meta.compute(d));
  const n = vals.length;
  const W = 720, H = 104, pl = 26, pr = 26, pt = 12, pb = 20;
  const cW = W - pl - pr, cH = H - pt - pb;
  const maxVal = Math.max.apply(null, vals.concat([0.0001]));
  const max = maxVal * 1.15;
  const step = n > 1 ? cW / (n - 1) : 0;
  const px = i => pl + step * i;
  const py = v => pt + cH - (max > 0 ? (v / max) * cH : 0);
  const base = pt + cH;

  let grid = '';
  for (let g = 1; g <= 3; g++) {
    const gy = pt + cH * (g / 4);
    grid += '<line class="desemp-grid-line" x1="' + pl + '" x2="' + (W - pr) + '" y1="' + gy.toFixed(1) + '" y2="' + gy.toFixed(1) + '" stroke-dasharray="2 3"/>';
  }

  const areaD = vals.map((v, i) => (i ? 'L' : 'M') + px(i).toFixed(1) + ' ' + py(v).toFixed(1)).join(' ');
  const area = '<path d="' + areaD + ' L' + px(n - 1).toFixed(1) + ' ' + base + ' L' + px(0).toFixed(1) + ' ' + base + ' Z" fill="' + meta.color + '" fill-opacity=".14" stroke="none"/>';

  // Rótulo em todos os pontos: fonte menor conforme a densidade + desloca o
  // rótulo de índice ímpar um pouco mais pra cima, separando visualmente
  // vizinhos com valor (e portanto altura) parecidos.
  const ptFontSize = n > 20 ? 7.3 : (n > 10 ? 8.2 : 9.5);
  const lineD = vals.map((v, i) => (i ? 'L' : 'M') + px(i).toFixed(1) + ' ' + py(v).toFixed(1)).join(' ');
  let line = '<path d="' + lineD + '" fill="none" stroke="' + meta.color + '" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round"/>';
  vals.forEach((v, i) => {
    line += '<circle cx="' + px(i).toFixed(1) + '" cy="' + py(v).toFixed(1) + '" r="' + (n > 60 ? 1.5 : 2.8) + '" fill="' + meta.color + '"/>';
    let ly = py(v) - 7;
    if (n > 15 && i % 2 === 1) ly -= ptFontSize + 1;
    line += '<text class="desemp-pt-label" style="font-size:' + ptFontSize + 'px" x="' + px(i).toFixed(1) + '" y="' + ly.toFixed(1) + '" text-anchor="middle">' + meta.fmt(v) + '</text>';
  });

  // Eixo X — sempre horizontal, nunca rotacionado (ver nota acima). Só o
  // Diário carrega `mes` (Mensal/Semanal não): quando o período filtrado
  // cai num único mês, mostra o dia puro 1..31 (o corte que existia antes,
  // restringindo o Diário ao último mês do período mesmo sem filtro de mês
  // aplicado, estava errado — "sem filtro" tem que valer pro ano inteiro,
  // não só o mês mais recente com dado). Quando cobre vários meses, mostra
  // só 1 marca por virada de mês (evita repetir "Set Set Set..." ou rótulo
  // de dia ambíguo entre meses diferentes).
  const temMes = serie.length && serie[0].mes !== undefined;
  const multiMes = temMes && new Set(serie.map(d => d.mes)).size > 1;
  let axis = '';
  if (temMes && multiMes) {
    let mesAnterior = null;
    serie.forEach((d, i) => {
      if (d.mes === mesAnterior) return;
      mesAnterior = d.mes;
      axis += '<text class="desemp-axis-label" x="' + px(i).toFixed(1) + '" y="' + (H - 10) + '" text-anchor="middle">' + escHtml(d.label.split(' ')[1]) + '</text>';
    });
  } else {
    const rotulo = d => temMes ? d.label.split(' ')[0] : d.label;
    const maxChars = serie.reduce((m, d) => Math.max(m, String(rotulo(d)).length), 0);
    const largMin = maxChars * 6.4 + 14;
    const passo = Math.max(1, Math.ceil(largMin / Math.max(step, 0.001)));
    serie.forEach((d, i) => {
      if (i % passo !== 0 && i !== n - 1) return;
      axis += '<text class="desemp-axis-label" x="' + px(i).toFixed(1) + '" y="' + (H - 10) + '" text-anchor="middle">' + escHtml(rotulo(d)) + '</text>';
    });
  }

  // Hit-area invisível pro hover.
  const hit = '<rect x="0" y="0" width="' + W + '" height="' + H + '" fill="transparent" class="desemp-hit"/>';

  el.innerHTML = '<svg viewBox="0 0 ' + W + ' ' + H + '" width="100%" preserveAspectRatio="none" style="display:block">' + grid + area + line + axis + hit + '</svg>';

  el._chartState = { serie, meta, pl, step, n, W };
}

/* ───────────────────────── Hover/tooltip dos gráficos de linha (índice mais próximo) ───────────────────────── */
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
    const val = st.meta.compute(d);
    showTip(e.clientX, e.clientY, tipHtml(d.label, [tipRow(st.meta.label, st.meta.fmt(val))]), rect.top);
  });
  el.addEventListener('mouseleave', hideTip);
}

function renderTudo() {
  renderTimeChart('chart-mensal', 'chart-mensal-sub', SERIE.mensal, metricaAtual);
  renderTimeChart('chart-semanal', 'chart-semanal-sub', SERIE.semanal, metricaAtual);
  renderTimeChart('chart-diario', 'chart-diario-sub', SERIE.diario, metricaAtual);
}

document.getElementById('metric-toggle').addEventListener('click', e => {
  const btn = e.target.closest('.gran-btn');
  if (!btn) return;
  metricaAtual = btn.dataset.metrica;
  document.querySelectorAll('#metric-toggle .gran-btn').forEach(b => b.classList.toggle('active', b === btn));
  renderTudo();
});

attachChartHover('chart-mensal');
attachChartHover('chart-semanal');
attachChartHover('chart-diario');

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

async function loadDashboard(f) {
  const overlay = document.getElementById('loading-overlay');
  const errorBanner = document.getElementById('error-banner');

  overlay.classList.remove('hidden');
  document.body.classList.add('loading');

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
    fillFinanceiro(data.kpis);
    renderRanking('rank-motivos', 'rank-motivos-sub', data.rankings.motivos);
    renderRanking('rank-profissoes', 'rank-profissoes-sub', data.rankings.profissoes);
    renderRanking('rank-tempo', 'rank-tempo-sub', data.rankings.tempo_exp);
    renderDonut('donut-formacao', data.formacao);
    SERIE = data.serie;
    renderTudo();
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

initFiltersToggle({ key: 'marketing-desempenho' });

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
