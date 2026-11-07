<div class="dash-dentistas">
<style>
/* ══════════════════════════════════════════════════════════════
 *  CSS específico do dashboard Dentistas — escopado em .dash-dentistas.
 *  Vocabulário compartilhado (header, filtros, kpi-chip, funil-card,
 *  bi-table) vem de css/bi-theme.css.
 * ══════════════════════════════════════════════════════════════ */

/* ── Tabela de deals: cabeçalho fixo ao rolar + link pro Bitrix ─── */
.dash-dentistas .tbl-wrap { position: relative; }
.dash-dentistas .bi-table thead th {
  position: sticky;
  /* .tbl-wrap tem seu próprio overflow:auto (ver estilo inline no card da
     tabela) — ele é o contêiner de scroll, então o thead cola em top:0
     dentro dele, igual ao padrão do Rafael Torres. */
  top: 0;
  z-index: 2;
  background: var(--card2);
}
.dash-dentistas .bx-link {
  display: inline-flex; align-items: center; gap: 5px;
  color: var(--consulta); font-weight: 600; text-decoration: none; font-size: 12.5px;
}
.dash-dentistas .bx-link:hover { text-decoration: underline; }
.dash-dentistas .bi-table thead th,
.dash-dentistas .bi-table tbody td {
  text-align: center;
}
.dash-dentistas .fase-badge {
  display: inline-block; padding: 3px 10px; border-radius: 20px;
  background: var(--card2); border: 1px solid var(--border);
  font-family: var(--font-mono); font-size: 11px; font-weight: 600; color: var(--muted);
}

/* ── Tabelas de agrupamento (Leads por Anúncio / Evento): compactas, com
   rolagem interna própria em vez de esticar o card conforme a lista cresce. */
.dash-dentistas .agrupamentos-grid .bi-table { font-size: 12px; }
.dash-dentistas .agrupamentos-grid .bi-table thead th { padding: 7px 12px; font-size: 9.5px; }
.dash-dentistas .agrupamentos-grid .bi-table tbody td { padding: 6px 12px; }
.dash-dentistas .agrupamentos-grid .tbl-wrap { max-height: 210px; overflow-y: auto; }
/* Filtro dinâmico por clique — linha vira um "chip" clicável; não
   selecionada dessaturada (não removida) quando há alguma seleção ativa. */
.dash-dentistas .agrupamentos-grid tr.is-clickable { cursor: pointer; }
.dash-dentistas .agrupamentos-grid tr.is-dimmed { opacity: .4; }
.dash-dentistas .agrupamentos-grid tr.is-dimmed:hover { opacity: .7; }
.dash-dentistas .agrupamentos-grid tr.is-active td:first-child { color: var(--consulta); font-weight: 700; }
.dash-dentistas .agrupamentos-grid tr.is-active { background: var(--card2); }

/* ── Gráfico de evolução (ao lado do funil) ─────────────────────── */
/* Card do mesmo tamanho do funil ao lado — cabeçalho e corpo mais
   compactos que o padrão global de .bi-card-header/.chart-body. */
.dash-dentistas .chart-card .bi-card-header { padding: 12px 16px 8px; }
.dash-dentistas .chart-card .chart-body { padding: 6px 8px 2px; }
.dash-dentistas #metric-toggle .gran-btn { padding: 3px 8px; font-size: 9px; }
.dash-dentistas .chart-holder { position: relative; }
.dash-dentistas .chart-wrap { width: 100%; overflow-x: auto; }
.dash-dentistas .chart-wrap svg { display: block; width: 100%; height: auto; min-width: 260px; }
.dash-dentistas .chart-wrap .pt-label { paint-order: stroke; stroke: var(--card); stroke-width: 3px; stroke-linejoin: round; }
.dash-dentistas .chart-tooltip {
  position: absolute; top: 0; left: 0; z-index: 5; pointer-events: none;
  min-width: 148px; padding: 9px 11px;
  background: var(--card); border: 1px solid var(--border); border-radius: var(--r-sm);
  box-shadow: var(--sh-lg); font-size: 12px; color: var(--text);
  transition: opacity .12s; opacity: 1;
}
.dash-dentistas .chart-tooltip[hidden] { display: none; }
.dash-dentistas .tt-mes {
  font-weight: 700; font-size: 11px; letter-spacing: .06em; text-transform: uppercase;
  color: var(--muted); padding-bottom: 6px; margin-bottom: 6px; border-bottom: 1px solid var(--border);
}
.dash-dentistas .tt-row { display: flex; align-items: center; gap: 8px; padding: 2px 0; }
.dash-dentistas .tt-dot { width: 8px; height: 8px; border-radius: 2px; flex-shrink: 0; }
.dash-dentistas .tt-label { color: var(--muted); }
.dash-dentistas .tt-val { margin-left: auto; font-family: var(--font-mono); font-weight: 700; font-variant-numeric: tabular-nums; }
.dash-dentistas .chart-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 6px; padding: 24px 0; color: var(--muted); font-size: 12px; text-align: center;
}
/* Cards do mesmo tamanho — a linha do grid estica os dois pro maior deles;
   o conteúdo mais curto (funil/gráfico) centraliza no espaço extra em vez
   de deixar um vão em branco embaixo. */
.dash-dentistas .funis-grid { align-items: stretch; }
.dash-dentistas .funis-grid .funil-card { height: 100%; }
.dash-dentistas .funis-grid .funil-body { flex: 1; justify-content: center; }
.dash-dentistas .funis-grid .chart-card { height: 100%; display: flex; flex-direction: column; }
.dash-dentistas .funis-grid .chart-card .chart-body { flex: 1; display: flex; align-items: center; }
.dash-dentistas .funis-grid .chart-card .chart-holder { width: 100%; }

.dash-dentistas .kpi-chip-sub {
  font-size: 10px;
  color: var(--muted);
  margin-top: 2px;
  white-space: nowrap;
}

/* ── Header/filtros do mesmo tamanho dos cards (bordas alinhadas com
   .main, que já tem max-width:1600px;margin:0 auto) ─────────────── */
.dash-dentistas .header,
.dash-dentistas .filters {
  max-width: 1600px;
  margin: 0 auto;
  width: 100%;
}
</style>

<!-- ── HEADER ──────────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo">DT</div>
    <div>
      <div class="header-title">Painel de Performance — Funil Dentistas</div>
      <div class="header-sub">Leads · Confirmados no Evento · Consultas Realizadas · Funil C33</div>
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
      <a class="tab-switch-btn active" href="<?= $this->site ?>dentistas<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Visão Geral</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>dentistas/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
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

  <!-- Evento (busca sobre valores brutos de dentistaEvento_dealV2) -->
  <div class="filter-pair">
    <span class="filter-label">Evento</span>
    <div class="camp-wrap" id="evento-wrap">
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="evento-input" class="camp-input" type="text" placeholder="Pesquisar evento..." autocomplete="off">
        <button class="camp-clear" id="evento-clear" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-tags" id="evento-tags"></div>
      <div class="camp-dropdown" id="evento-dropdown"></div>
    </div>
  </div>

  <!-- UTM Term / Anúncio (busca sobre valores brutos de utmTerm_dealV2) -->
  <div class="filter-pair">
    <span class="filter-label">Anúncio (UTM Term)</span>
    <div class="camp-wrap" id="utmterm-wrap">
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="utmterm-input" class="camp-input" type="text" placeholder="Pesquisar anúncio..." autocomplete="off">
        <button class="camp-clear" id="utmterm-clear" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-tags" id="utmterm-tags"></div>
      <div class="camp-dropdown" id="utmterm-dropdown"></div>
    </div>
  </div>

  <!-- Profissão (busca sobre valores brutos de profissao_dealV2) -->
  <div class="filter-pair">
    <span class="filter-label">Profissão</span>
    <div class="camp-wrap" id="profissao-wrap">
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="profissao-input" class="camp-input" type="text" placeholder="Pesquisar profissão..." autocomplete="off">
        <button class="camp-clear" id="profissao-clear" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-tags" id="profissao-tags"></div>
      <div class="camp-dropdown" id="profissao-dropdown"></div>
    </div>
  </div>

  <!-- Formação Acadêmica (busca sobre valores brutos de formacaoAcademica_dealV2) -->
  <div class="filter-pair">
    <span class="filter-label">Formação Acadêmica</span>
    <div class="camp-wrap" id="formacao-wrap">
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="formacao-input" class="camp-input" type="text" placeholder="Pesquisar formação..." autocomplete="off">
        <button class="camp-clear" id="formacao-clear" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-tags" id="formacao-tags"></div>
      <div class="camp-dropdown" id="formacao-dropdown"></div>
    </div>
  </div>

  <button class="btn-apply" id="btn-apply">Aplicar</button>
  <button class="btn-clear" id="btn-clear">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'dentistas' });</script>

<div id="loading-overlay" class="hidden"></div>
<div id="error-banner" role="alert"></div>

<!-- ── CONTEÚDO ────────────────────────────────────────────────── -->
<main class="main" id="dashboard-main" aria-label="Funil Dentistas">

  <!-- Ordem confirmada pelo usuário: confirmados no evento vem antes da consulta
       neste segmento. Sem chip de "Qualificados" — a fonte de lead é o próprio
       pipe C33 (dealV2), não leadV2, então todo lead já é qualificado. -->
  <div class="kpi-row">
    <div class="kpi-chip aquisicao">
      <span class="kpi-chip-label">Total de Leads</span>
      <span class="kpi-chip-val" id="k-leads">—</span>
    </div>
    <div class="kpi-chip sinal">
      <span class="kpi-chip-label">Confirmados no Evento</span>
      <span class="kpi-chip-val" id="k-cadeira-vendida">—</span>
      <span class="kpi-chip-sub" id="k-cadeira-vendida-pct"></span>
    </div>
    <div class="kpi-chip consulta">
      <span class="kpi-chip-label">Consultas Realizadas</span>
      <span class="kpi-chip-val" id="k-consultas-realizadas">—</span>
      <span class="kpi-chip-sub" id="k-consultas-realizadas-pct"></span>
    </div>
    <div class="kpi-chip fecho">
      <span class="kpi-chip-label">Contrato Assinado</span>
      <span class="kpi-chip-val" id="k-contrato-assinado">—</span>
      <span class="kpi-chip-sub" id="k-contrato-assinado-pct"></span>
    </div>
  </div>

  <div class="funis-grid">
    <div class="funil-card">
      <div class="funil-header">
        <span class="funil-name">Funil Comercial — Dentistas</span>
      </div>
      <div class="funil-body" id="funil-body"></div>
    </div>

    <div class="bi-card chart-card">
      <div class="bi-card-header" style="flex-direction:column;align-items:stretch;gap:8px">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
          <span class="bi-card-title">Evolução <span id="chart-periodo-label">mensal</span></span>
          <div class="gran-toggle" id="periodo-toggle">
            <button class="gran-btn active" data-periodo="mes"    type="button">Mês</button>
            <button class="gran-btn"        data-periodo="semana" type="button">Semana</button>
            <button class="gran-btn"        data-periodo="dia"    type="button">Dia</button>
          </div>
        </div>
        <div class="gran-toggle" id="metric-toggle">
          <button class="gran-btn active" data-metrica="leads"                type="button">Leads</button>
          <button class="gran-btn"        data-metrica="cadeira_vendida"      type="button">Confirm.</button>
          <button class="gran-btn"        data-metrica="consultas_realizadas" type="button">Consultas</button>
          <button class="gran-btn"        data-metrica="contrato_assinado"    type="button">Contrato</button>
        </div>
      </div>
      <div class="bi-card-body chart-body">
        <div class="chart-holder">
          <div class="chart-wrap" id="chart-wrap"></div>
          <div class="chart-tooltip" id="chart-tooltip" hidden></div>
        </div>
      </div>
    </div>
  </div>

  <div class="funis-grid agrupamentos-grid" style="margin-top:20px">
    <div class="bi-card">
      <div class="bi-card-header">
        <span class="bi-card-title">Leads por Anúncio</span>
        <span class="bi-card-sub">utmTerm_dealV2</span>
      </div>
      <div class="bi-card-body" style="padding:0">
        <div class="tbl-wrap">
          <table class="bi-table">
            <thead><tr><th>Anúncio</th><th>Leads</th><th>% do total</th></tr></thead>
            <tbody id="tbl-por-anuncio"></tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="bi-card">
      <div class="bi-card-header">
        <span class="bi-card-title">Leads por Evento</span>
        <span class="bi-card-sub">dentistaEvento_dealV2</span>
      </div>
      <div class="bi-card-body" style="padding:0">
        <div class="tbl-wrap">
          <table class="bi-table">
            <thead><tr><th>Evento</th><th>Leads</th><th>% do total</th></tr></thead>
            <tbody id="tbl-por-evento"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="bi-card" style="margin-top:20px">
    <div class="bi-card-header" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">
      <span class="bi-card-title">Negócios no pipe Dentistas</span>
      <div style="display:flex;align-items:center;gap:10px">
        <input
          type="text"
          id="linhas-search"
          placeholder="Buscar por nome, fase, evento ou ID Bitrix..."
          autocomplete="off"
          oninput="filtrarLinhas()"
          style="height:30px;padding:0 10px;border-radius:var(--r-sm,6px);border:1px solid var(--border);background:var(--card2);color:var(--text);font-family:var(--font-ui);font-size:12.5px;outline:none;min-width:220px"
        >
        <span class="bi-card-sub" id="linhas-count"></span>
        <div class="exp-menu-wrap" id="linhas-export-wrap">
          <button class="exp-menu-btn" id="linhas-export-btn" type="button">Exportar ▾</button>
          <div class="exp-menu-list" id="linhas-export-list">
            <div class="exp-menu-item" data-fmt="csv">Exportar CSV</div>
            <div class="exp-menu-item" data-fmt="excel">Exportar Excel</div>
          </div>
        </div>
      </div>
    </div>
    <div class="bi-card-body tbl-wrap" style="padding:0;max-height:560px;overflow:auto">
      <table class="bi-table">
        <thead>
          <tr>
            <th>ID Bitrix</th>
            <th>Nome</th>
            <th>Fase</th>
            <th>Evento</th>
            <th>Profissão</th>
            <th>Formação Acadêmica</th>
            <th>Data Último Form</th>
            <th>Consulta realizada</th>
            <th>Data assinatura do contrato</th>
            <th>Bitrix</th>
          </tr>
        </thead>
        <tbody id="linhas-body"></tbody>
      </table>
    </div>
  </div>

</main>
</div>

<script>
// ══════════════════════════════════════════════════════════════
//  Dashboard Dentistas — consome dentistas/dados.
//
//  createMultiDropdown, createSearchDropdown, getDateInput, clearDateInput,
//  fNum, fPctRatio, fBar vêm de js/bi-dashboard.js (carregado no <head> pelo
//  template).
// ══════════════════════════════════════════════════════════════

const API          = '<?= $this->site ?>dentistas/dados';
const EVENTOS_URL  = '<?= $this->site ?>dentistas/eventos';
const UTMTERMS_URL = '<?= $this->site ?>dentistas/utmterms';
const PROFISSOES_URL = '<?= $this->site ?>dentistas/profissoes';
const FORMACOES_URL  = '<?= $this->site ?>dentistas/formacoes';

const YEARS  = ['2024', '2025', '2026', '2027'];
const MONTHS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

const yearDropdown      = createMultiDropdown('year-wrap',  'year-btn',  'year-list',  YEARS,  l => l, l => l);
const monthDropdown     = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);
const eventoDropdown    = createSearchDropdown('evento-wrap', 'evento-input', 'evento-dropdown', 'evento-clear', 'evento-tags', 'Nenhum evento encontrado');
const utmtermDropdown   = createSearchDropdown('utmterm-wrap', 'utmterm-input', 'utmterm-dropdown', 'utmterm-clear', 'utmterm-tags', 'Nenhum anúncio encontrado');
const profissaoDropdown = createSearchDropdown('profissao-wrap', 'profissao-input', 'profissao-dropdown', 'profissao-clear', 'profissao-tags', 'Nenhuma profissão encontrada');
const formacaoDropdown  = createSearchDropdown('formacao-wrap', 'formacao-input', 'formacao-dropdown', 'formacao-clear', 'formacao-tags', 'Nenhuma formação encontrada');

fetch(EVENTOS_URL, { cache: 'no-store' })
  .then(res => res.json())
  .then(list => eventoDropdown.load(Array.isArray(list) ? list : []))
  .catch(err => console.error('Erro ao carregar eventos:', err));

fetch(UTMTERMS_URL, { cache: 'no-store' })
  .then(res => res.json())
  .then(list => utmtermDropdown.load(Array.isArray(list) ? list : []))
  .catch(err => console.error('Erro ao carregar utm terms:', err));

fetch(PROFISSOES_URL, { cache: 'no-store' })
  .then(res => res.json())
  .then(list => profissaoDropdown.load(Array.isArray(list) ? list : []))
  .catch(err => console.error('Erro ao carregar profissões:', err));

fetch(FORMACOES_URL, { cache: 'no-store' })
  .then(res => res.json())
  .then(list => formacaoDropdown.load(Array.isArray(list) ? list : []))
  .catch(err => console.error('Erro ao carregar formações:', err));

// Ano corrente pré-selecionado. Mês fica sem seleção default ("Todos") — só
// 9 dias de histórico, pré-selecionar o mês corrente esconderia os dados de agosto.
yearDropdown.select([String(new Date().getFullYear())]);

function esc(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function set(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }

// ── Funil ─────────────────────────────────────────────────────────
// Ordem: Leads → Confirmados no Evento → Consultas Realizadas → Contrato
// Assinado (confirmados no evento vem antes da consulta neste segmento).
// Sem etapa de Qualificados — fonte de lead é o pipe C33, já qualificado.
// Cores sólidas por etapa — mesma paleta do kpi-chip e do gráfico acima
// (nada de gradiente): red/sinal/consulta/fecho, nessa ordem fixa.
const FUNIL_CORES = ['var(--red)', 'var(--sinal)', 'var(--consulta)', 'var(--fecho)'];

function renderFunil(etapas) {
  const max = etapas[0]?.valor || 1;
  document.getElementById('funil-body').innerHTML = etapas.map((s, i) => {
    const anterior = etapas[i - 1];
    const w   = fBar(s.valor, max);
    const pct = i === 0 ? '—' : fPctRatio(s.valor, anterior.valor);
    const bg  = FUNIL_CORES[i] || 'var(--consulta)';
    return '<div class="funil-row">'
      + '<span class="funil-row-label">' + esc(s.etapa) + '</span>'
      + '<div class="funil-bar-wrap"><div class="funil-bar" style="width:' + w + ';background:' + bg + '"></div></div>'
      + '<span class="funil-row-val">' + esc(fNum(s.valor)) + '</span>'
      + '<span class="funil-row-cv">' + esc(pct) + '</span>'
      + '</div>';
  }).join('');
}

// ── Gráfico de evolução mensal (seletor de métrica) ───────────────
// Uma única série por vez (o payload já traz as 4 juntas — trocar de
// métrica é só re-render local, sem novo fetch). Cores casam com as dos
// kpi-chip acima (mesmo token por métrica).
const METRICAS = {
  leads:                { label: 'Leads',                cor: 'var(--red)' },
  cadeira_vendida:      { label: 'Confirmados no Evento', cor: 'var(--sinal)' },
  consultas_realizadas: { label: 'Consultas Realizadas', cor: 'var(--consulta)' },
  contrato_assinado:    { label: 'Contrato Assinado',    cor: 'var(--fecho)' },
};

let chartGeo        = null;
let chartSerieAtual = [];
let metricaAtual    = 'leads';
let periodoAtual    = 'mes';

const PERIODO_LABELS = { mes: 'mensal', semana: 'semanal', dia: 'diária' };

function setPeriodo(p) {
  periodoAtual = p;
  document.querySelectorAll('#periodo-toggle .gran-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.periodo === p);
  });
  document.getElementById('chart-periodo-label').textContent = PERIODO_LABELS[p];
  applyFilters();
}

document.querySelectorAll('#periodo-toggle .gran-btn').forEach(btn => {
  btn.addEventListener('click', () => setPeriodo(btn.dataset.periodo));
});

function buildChart(serie, metrica) {
  if (!serie || !serie.length) {
    return '<div class="chart-empty"><span style="font-size:22px">&#128202;</span>Sem dados de evolução para o período selecionado</div>';
  }
  const cor = METRICAS[metrica].cor;
  // Proporção mais baixa que o padrão dos outros dashboards — este card
  // fica ao lado do funil e precisa caber na mesma altura dele.
  const W = 640, H = 170, pl = 8, pr = 8, pt = 18, pb = 24;
  const cW = W - pl - pr, cH = H - pt - pb, n = serie.length;
  let max = 1;
  serie.forEach(d => { max = Math.max(max, d[metrica] || 0); });
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

  const showLabel = (i) => {
    if (n <= 8) return true;
    const vals = serie.map(d => d[metrica] || 0);
    return i === 0 || i === n - 1 || i === vals.indexOf(Math.max.apply(null, vals));
  };

  const areaD = serie.map((s, i) => (i ? 'L' : 'M') + px(i) + ' ' + py(s[metrica] || 0)).join(' ');
  const area  = '<path d="' + areaD + ' L' + px(n - 1) + ' ' + base + ' L' + px(0) + ' ' + base + ' Z"'
    + ' fill="' + cor + '" fill-opacity=".12" stroke="none"/>';

  const pts = serie.map((d, i) => px(i) + ',' + py(d[metrica] || 0)).join(' ');
  let linha = '<polyline points="' + pts + '" fill="none" stroke="' + cor + '" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round"/>';
  const usados = [];
  serie.forEach((d, i) => {
    const v = d[metrica] || 0;
    linha += '<circle cx="' + px(i) + '" cy="' + py(v) + '" r="3.2" fill="' + cor + '"/>';
    if (showLabel(i) && v > 0) {
      let ly = py(v) - 9;
      usados[i] = usados[i] || [];
      while (usados[i].some(p => Math.abs(p - ly) < 11)) { ly -= 11; }
      usados[i].push(ly);
      linha += '<text class="pt-label" x="' + px(i) + '" y="' + ly + '" text-anchor="middle" font-size="9.5" font-weight="700" fill="' + cor + '">' + v + '</text>';
    }
  });

  const xLbls = serie.map((d, i) => {
    const anchor = i === 0 ? 'start' : (i === n - 1 ? 'end' : 'middle');
    return '<text x="' + px(i) + '" y="' + (base + 18) + '" text-anchor="' + anchor + '" font-size="10" fill="currentColor" fill-opacity=".6">' + esc(d.mes) + '</text>';
  }).join('');

  chartGeo = { W: W, H: H, pl: pl, step: step, pt: pt, base: base, n: n, max: max, serie: serie, metrica: metrica };

  return '<svg viewBox="0 0 ' + W + ' ' + H + '" width="100%" role="img" aria-label="Evolução mensal"'
    + ' style="color:var(--muted);font-family:var(--font-ui)">'
    + grid
    + '<line id="chart-cursor" x1="0" y1="' + pt + '" x2="0" y2="' + base + '" stroke="currentColor" stroke-opacity=".35" stroke-width="1" stroke-dasharray="3 3" visibility="hidden"/>'
    + area
    + yLbls
    + linha
    + xLbls
    + '<circle r="5.5" fill="none" stroke="' + cor + '" stroke-width="2" id="chart-hover-dot" visibility="hidden"/>'
    + '</svg>';
}

function attachChartHover() {
  const wrap    = document.getElementById('chart-wrap');
  const holder  = wrap.parentElement;
  const tooltip = document.getElementById('chart-tooltip');
  const svg     = wrap.querySelector('svg');
  if (!svg || !chartGeo) return;

  const cursor = svg.querySelector('#chart-cursor');
  const dot    = svg.querySelector('#chart-hover-dot');
  const g      = chartGeo;
  const py     = v => g.base - (v / g.max) * (g.base - g.pt);

  function esconde() {
    tooltip.hidden = true;
    if (cursor) cursor.setAttribute('visibility', 'hidden');
    if (dot)    dot.setAttribute('visibility', 'hidden');
  }

  function mostra(e) {
    const r = svg.getBoundingClientRect();
    if (!r.width) return;
    const xView = (e.clientX - r.left) / r.width * g.W;
    let i = g.step ? Math.round((xView - g.pl) / g.step) : 0;
    i = Math.max(0, Math.min(g.n - 1, i));
    const d = g.serie[i];
    if (!d) return;

    const m = METRICAS[g.metrica];
    tooltip.innerHTML = '<div class="tt-mes">' + esc(d.mes) + '</div>'
      + '<div class="tt-row">'
      + '<span class="tt-dot" style="background:' + m.cor + '"></span>'
      + '<span class="tt-label">' + esc(m.label) + '</span>'
      + '<span class="tt-val">' + fNum(d[g.metrica] || 0) + '</span>'
      + '</div>';
    tooltip.hidden = false;

    const xPonto = g.pl + g.step * i;
    if (cursor) {
      cursor.setAttribute('x1', xPonto);
      cursor.setAttribute('x2', xPonto);
      cursor.setAttribute('visibility', 'visible');
    }
    if (dot) {
      dot.setAttribute('cx', xPonto);
      dot.setAttribute('cy', py(d[g.metrica] || 0));
      dot.setAttribute('visibility', 'visible');
    }

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
  svg.addEventListener('touchmove', ev => { if (ev.touches[0]) mostra(ev.touches[0]); }, { passive: true });
  svg.addEventListener('touchend', esconde);
}

function renderChart() {
  document.getElementById('chart-wrap').innerHTML = buildChart(chartSerieAtual, metricaAtual);
  document.getElementById('chart-tooltip').hidden  = true;
  attachChartHover();
}

function setMetrica(m) {
  metricaAtual = m;
  document.querySelectorAll('#metric-toggle .gran-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.metrica === m);
  });
  renderChart();
}

document.querySelectorAll('#metric-toggle .gran-btn').forEach(btn => {
  btn.addEventListener('click', () => setMetrica(btn.dataset.metrica));
});

// ── Tabelas de agrupamento (Anúncio / Evento) ──────────────────────
// Filtro dinâmico por clique: cada linha alterna a seleção no dropdown
// correspondente (utmtermDropdown / eventoDropdown) e recarrega o
// dashboard inteiro — mesmo efeito de escolher no filtro do topo e clicar
// Aplicar, só que instantâneo. A própria tabela não se autofiltra (o
// backend já ignora essa dimensão nela mesma, ver calcularAgrupamentos())
// — em vez de linhas sumirem, as não selecionadas só ficam translúcidas,
// pra dar pra comparar/adicionar mais de uma. Linha "(não informado)" não
// é clicável: o valor bruto é '' e o filtro IN(...) não representa vazio.
function renderAgrupamento(elId, rows, dropdown) {
  const body = document.getElementById(elId);
  if (!rows || !rows.length) {
    body.innerHTML = '<tr><td colspan="3" style="text-align:center;color:var(--muted);padding:16px">Sem dados no período</td></tr>';
    return;
  }
  const total      = rows.reduce((acc, r) => acc + r.qtd, 0);
  const selecionados = new Set(dropdown.getSelected());
  const temSelecao   = selecionados.size > 0;

  body.innerHTML = rows.map(r => {
    const clicavel = r.valor !== '';
    const ativo    = clicavel && selecionados.has(r.valor);
    const classes  = [];
    if (clicavel) classes.push('is-clickable');
    if (ativo) classes.push('is-active');
    else if (temSelecao) classes.push('is-dimmed');
    const attrs = clicavel ? ' data-valor="' + esc(r.valor) + '"' : '';
    return '<tr class="' + classes.join(' ') + '"' + attrs + '>'
      + '<td>' + esc(r.grupo) + '</td>'
      + '<td>' + fNum(r.qtd) + '</td>'
      + '<td>' + fPctRatio(r.qtd, total) + '</td>'
      + '</tr>';
  }).join('');

  body.querySelectorAll('tr.is-clickable').forEach(tr => {
    tr.addEventListener('click', () => {
      dropdown.toggle(tr.dataset.valor);
      applyFilters();
    });
  });
}

// ── Tabela de linhas ────────────────────────────────────────────
// Colunas exportadas (CSV/Excel) — export usa exatamente as linhas
// visíveis na tela (busca + carregamento atuais), igual ao padrão de
// paginas/rafaeltorres/index.php.
const COLS_LINHAS = [
  { k: 'id_bitrix',               label: 'ID Bitrix' },
  { k: 'nome',                    label: 'Nome' },
  { k: 'fase',                    label: 'Fase' },
  { k: 'evento',                  label: 'Evento' },
  { k: 'profissao',               label: 'Profissão' },
  { k: 'formacao',                label: 'Formação Acadêmica' },
  { k: 'data_ult_form',           label: 'Data Último Form' },
  { k: 'data_consulta_realizada', label: 'Consulta realizada' },
  { k: 'data_contrato_assinado',  label: 'Data assinatura do contrato' },
  { k: 'link_bitrix',             label: 'Bitrix' },
];

let ultimasLinhas = [];

function linhasVisiveis() {
  const termo = (document.getElementById('linhas-search').value || '').trim().toLowerCase();
  const base = termo
    ? ultimasLinhas.filter(l =>
        (l.nome || '').toLowerCase().includes(termo) ||
        (l.fase || '').toLowerCase().includes(termo) ||
        (l.evento || '').toLowerCase().includes(termo) ||
        String(l.id_bitrix ?? '').toLowerCase().includes(termo))
    : ultimasLinhas;
  return { linhas: base, filtrado: !!termo };
}

function filtrarLinhas() {
  const { linhas, filtrado } = linhasVisiveis();
  renderLinhas(linhas, filtrado);
}

createExportMenu('linhas-export-wrap', 'linhas-export-btn', 'linhas-export-list', fmt => {
  const { linhas } = linhasVisiveis();
  const nome = 'negocios-pipe-dentistas-' + new Date().toISOString().slice(0, 10);
  if (fmt === 'csv') exportRowsAsCsv(COLS_LINHAS, linhas, nome);
  else               exportRowsAsExcel(COLS_LINHAS, linhas, nome);
});

function renderLinhas(linhas, filtrado = false) {
  const body = document.getElementById('linhas-body');
  if (!linhas.length) {
    set('linhas-count', filtrado ? '0 deals' : '');
    body.innerHTML = '<tr><td colspan="10" style="text-align:center;color:var(--muted);padding:20px">'
      + (filtrado ? 'Nenhum deal encontrado para essa busca.' : 'Nenhum deal no funil C33 ainda')
      + '</td></tr>';
    return;
  }
  set('linhas-count', linhas.length + (linhas.length === 1 ? ' deal' : ' deals'));
  body.innerHTML = linhas.map(l => '<tr>'
    + '<td>' + esc(l.id_bitrix) + '</td>'
    + '<td>' + esc(l.nome || '—') + '</td>'
    + '<td><span class="fase-badge">' + esc(l.fase) + '</span></td>'
    + '<td>' + esc(l.evento || '—') + '</td>'
    + '<td>' + esc(l.profissao || '—') + '</td>'
    + '<td>' + esc(l.formacao || '—') + '</td>'
    + '<td>' + esc(l.data_ult_form || '—') + '</td>'
    + '<td>' + esc(l.data_consulta_realizada || '—') + '</td>'
    + '<td>' + esc(l.data_contrato_assinado || '—') + '</td>'
    + '<td>' + (l.link_bitrix ? '<a class="bx-link" href="' + esc(l.link_bitrix) + '" target="_blank" rel="noopener">Abrir ↗</a>' : '—') + '</td>'
    + '</tr>').join('');
}

// ── Carregamento ──────────────────────────────────────────────────
function montaParams() {
  return {
    year:          yearDropdown.getValue(),
    months:        monthDropdown.getValue(),
    data_inicio:   getDateInput('data-inicio'),
    data_fim:      getDateInput('data-fim'),
    evento:        eventoDropdown.getValue(),
    utm_term:      utmtermDropdown.getValue(),
    profissao:     profissaoDropdown.getValue(),
    formacao:      formacaoDropdown.getValue(),
    granularidade: periodoAtual,
  };
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

    const k     = data.kpis || {};
    const funil = data.funil || [];

    set('k-leads',                fNum(k.leads));
    set('k-cadeira-vendida',      fNum(k.cadeira_vendida));
    set('k-consultas-realizadas', fNum(k.consultas_realizadas));
    set('k-contrato-assinado',    fNum(k.contrato_assinado));

    // % de cada etapa em relação à etapa anterior do funil (mesma ordem/
    // valores de data.funil, usados abaixo em renderFunil).
    for (let i = 1; i < funil.length; i++) {
      set('k-' + { 1: 'cadeira-vendida', 2: 'consultas-realizadas', 3: 'contrato-assinado' }[i] + '-pct',
        fPctRatio(funil[i].valor, funil[i - 1].valor) + ' da etapa anterior');
    }

    renderFunil(funil);

    const agrupamentos = data.agrupamentos || {};
    renderAgrupamento('tbl-por-anuncio', agrupamentos.por_anuncio || [], utmtermDropdown);
    renderAgrupamento('tbl-por-evento',  agrupamentos.por_evento  || [], eventoDropdown);

    ultimasLinhas = data.linhas || [];
    document.getElementById('linhas-search').value = '';
    renderLinhas(ultimasLinhas);

    chartSerieAtual = data.tempo || [];
    renderChart();

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
  const badge     = document.getElementById('filter-label-badge');
  const year      = yearDropdown.getValue();
  const months    = monthDropdown.getValue();
  const evento    = eventoDropdown.getValue();
  const utmTerm   = utmtermDropdown.getValue();
  const profissao = profissaoDropdown.getValue();
  const formacao  = formacaoDropdown.getValue();
  if (!year && !months && !evento && !utmTerm && !profissao && !formacao) {
    badge.textContent = 'TODOS OS PERÍODOS';
    return;
  }
  const parts = [];
  if (formacao)  parts.push('Formação: ' + formacao);
  if (profissao) parts.push('Profissão: ' + profissao);
  if (utmTerm)   parts.push('Anúncio: ' + utmTerm);
  if (evento)    parts.push(evento);
  if (year)      parts.push(year);
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
  eventoDropdown.reset();
  utmtermDropdown.reset();
  profissaoDropdown.reset();
  formacaoDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim', 'data-fim-clear');
  setPeriodo('mes');
}

document.getElementById('btn-apply').addEventListener('click', applyFilters);
document.getElementById('btn-clear').addEventListener('click', clearFilters);

['data-inicio', 'data-fim'].forEach(id => {
  const input    = document.getElementById(id);
  const clearBtn = document.getElementById(id + '-clear');
  input?.addEventListener('change', () => clearBtn?.classList.toggle('visible', !!input.value));
  clearBtn?.addEventListener('click', () => clearDateInput(id, id + '-clear'));
});

applyFilters();
</script>
