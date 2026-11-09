<div class="dash-marketing">

<!-- ══════════════════════════════════════════════════════════════
     Marketing (Dashboard ADS) — tela única + cross-filter.

     Layout: KPIs no topo; abaixo, Resumo Financeiro + Funil (com
     lucro acumulado por etapa) à esquerda, e à direita Categorias
     (Form) compacto no topo + Campanha/Conjunto/Anúncio em largura
     cheia empilhados — espelha o Dashboard ADS do Power BI. O time
     lê as 3 tabelas de breakdown e o funil ao mesmo tempo, então
     nada aqui pode exigir scroll de página — a altura do bloco é
     calculada em medirAltura().

     Cross-filter: clicar numa linha de qualquer tabela filtra todo
     o resto (as outras 3 tabelas, os 7 KPIs, o funil e o
     financeiro). Layout e interação foram validados antes em
     marketing-layout-mock.html (grade 2×2 original) e
     marketing-redesign-mock.html (pilha atual + lucro no funil).

     Todo o CSS vive no bloco .dash-marketing de css/bi-theme.css
     (seções 20 e 20b) — nada de <style> inline aqui.
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

  <!-- Filtros vindos de clique em linha. Mora no header porque a superfície é
       escura (a .filter-tag global só é legível assim) e a altura já está
       paga — a barra não custa pixel do orçamento de tela única. -->
  <div class="mk-crossbar" id="mk-crossbar" role="status" aria-live="polite"
       aria-label="Filtros aplicados por clique"></div>

  <div class="header-right">
    <span class="header-badge" id="filter-label-badge">TODOS OS PERÍODOS</span>
    <div class="tab-switch">
      <a class="tab-switch-btn active" href="<?= $this->site ?>marketing<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Visão Geral</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/comparativo<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Comparativo</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/eventos<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Eventos</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/desempenho<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Desempenho</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/descartes<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Descartes</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/classificacao<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Classificação</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
    </div>
  </div>
</header>

<!-- ── FILTROS (recolhível) ────────────────────────────────────
     Recolhido por padrão (lembrado em localStorage) para sobrar mais altura
     de tela pro conteúdo. Aplicar recolhe de novo sozinho. ── -->
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
<main class="main" id="dashboard-main" aria-label="Dashboard de marketing">

  <!-- LINHA 1 — KPIs -->
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

  <!-- LINHA 2 — Resumo Financeiro + Funil | 4 tabelas em grade 2×2 -->
  <div class="mk-split fit" id="mk-split">

    <div class="mk-col mk-col-side">

      <div class="bi-card fin-card">
        <div class="bi-card-header"><span class="bi-card-title">Resumo Financeiro</span></div>
        <div class="bi-card-body">
          <div class="fin-invest-row">
            <div class="fin-row">
              <span class="fin-label">Investimento Total</span>
              <span class="fin-value invest" id="fin-invest">—</span>
              <span class="fin-origem" id="fin-invest-origem"></span>
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

      <article class="funil-card">
        <div class="funil-header"><span class="funil-name">Funil de Vendas</span></div>
        <div class="funil-v2-legend" aria-hidden="true">
          <span><i class="sw-casc"></i>Cascateamento</span>
          <span><i class="sw-painel"></i>Painel</span>
        </div>
        <div class="funil-v2-body is-empty" id="funil-body">Carregando...</div>
      </article>

    </div>

    <div class="mk-col mk-col-tables" id="mk-col-tables">

      <article class="bi-card mk-tbl-card" data-table="form_classe">
        <div class="bi-card-header">
          <span class="bi-card-title">Categorias (Form)</span>
          <div class="mk-tbl-head-right">
            <span class="mk-tbl-count" id="count-form_classe">—</span>
            <button class="mk-expand-btn" data-open="form_classe" title="Ver tabela completa" aria-label="Ver tabela completa de Categorias">
              <svg viewBox="0 0 24 24"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
            </button>
          </div>
        </div>
        <div class="mk-tbl-scroll">
          <table class="bi-table mk-tbl">
            <thead><tr id="thead-form_classe"></tr></thead>
            <tbody id="tbody-form_classe"></tbody>
          </table>
        </div>
      </article>

      <article class="bi-card mk-tbl-card" data-table="campanha">
        <div class="bi-card-header">
          <span class="bi-card-title">Campanhas</span>
          <div class="mk-tbl-head-right">
            <span class="mk-tbl-count" id="count-campanha">—</span>
            <button class="mk-expand-btn" data-open="campanha" title="Ver tabela completa" aria-label="Ver tabela completa de Campanhas">
              <svg viewBox="0 0 24 24"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
            </button>
          </div>
        </div>
        <div class="mk-tbl-scroll">
          <table class="bi-table mk-tbl">
            <thead><tr id="thead-campanha"></tr></thead>
            <tbody id="tbody-campanha"></tbody>
          </table>
        </div>
      </article>

      <article class="bi-card mk-tbl-card" data-table="conjunto">
        <div class="bi-card-header">
          <span class="bi-card-title">Conj. de Anúncios</span>
          <div class="mk-tbl-head-right">
            <span class="mk-tbl-count" id="count-conjunto">—</span>
            <button class="mk-expand-btn" data-open="conjunto" title="Ver tabela completa" aria-label="Ver tabela completa de Conjuntos">
              <svg viewBox="0 0 24 24"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
            </button>
          </div>
        </div>
        <div class="mk-tbl-scroll">
          <table class="bi-table mk-tbl">
            <thead><tr id="thead-conjunto"></tr></thead>
            <tbody id="tbody-conjunto"></tbody>
          </table>
        </div>
      </article>

      <article class="bi-card mk-tbl-card" data-table="anuncio">
        <div class="bi-card-header">
          <span class="bi-card-title">Anúncios</span>
          <div class="mk-tbl-head-right">
            <span class="mk-tbl-count" id="count-anuncio">—</span>
            <button class="mk-expand-btn" data-open="anuncio" title="Ver tabela completa" aria-label="Ver tabela completa de Anúncios">
              <svg viewBox="0 0 24 24"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
            </button>
          </div>
        </div>
        <div class="mk-tbl-scroll">
          <table class="bi-table mk-tbl">
            <thead><tr id="thead-anuncio"></tr></thead>
            <tbody id="tbody-anuncio"></tbody>
          </table>
        </div>
      </article>

    </div>
  </div>

</main>

<!-- Popover de hover com as 17 métricas + modal de tabela completa -->
<div class="mk-pop" id="mk-pop" role="tooltip"></div>

<!-- Tooltip do funil — .bi-tooltip compartilhado (css/bi-theme.css), mesmo
     padrão de paginas/marketing/classificacao.php. Separado do #mk-pop
     acima, que é o popover das 17 métricas das tabelas. -->
<div class="bi-tooltip" id="funil-tip" role="tooltip"></div>

<div class="mk-modal" id="mk-modal">
  <div class="mk-modal-box">
    <div class="mk-modal-head">
      <span class="mk-modal-title" id="mk-modal-title">—</span>
      <button class="mk-modal-close" id="mk-modal-close" aria-label="Fechar">&#x2715;</button>
    </div>
    <div class="mk-modal-body">
      <table class="bi-table" id="mk-modal-table">
        <thead><tr id="mk-modal-thead"></tr></thead>
        <tbody id="mk-modal-tbody"></tbody>
      </table>
    </div>
  </div>
</div>

</div>

<script>
// ══════════════════════════════════════════════════════════════
//  Marketing (Dashboard ADS) — Dashboard JS
//
//  Consome: marketing/dados, marketing/campanhas, marketing/forms,
//  marketing/conjuntosAnuncio, marketing/anuncios.
//
//  createMultiDropdown, createSearchDropdown, getDateInput,
//  clearDateInput, fNum, fBRL, fROI, fPct, fPctRatio, fBar são
//  fornecidas por js/bi-dashboard.js (carregado no <head> pelo
//  template, antes deste script) — esse arquivo é compartilhado
//  por 14 views e NÃO é alterado por este dashboard.
// ══════════════════════════════════════════════════════════════

const API           = '<?= $this->site ?>marketing/dados';
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

// ROI com 1 casa nas tabelas da tela (2.1x em vez de 2.14x): economiza um
// caractere na coluna mais estreita, e o valor cheio continua no popover de
// hover e no modal de tabela completa.
function fROI1(v) { return (Number(v) || 0).toFixed(1) + 'x'; }

// ══════════════════════════════════════════════════════════════
//  1. COLUNAS — as 17 métricas do payload. `short` é o rótulo na
//     tela, `label` o nome cheio (popover e modal).
// ══════════════════════════════════════════════════════════════
const COLS = [
  { k: 'invest',             label: 'Invest.',         short: 'Inv.',    fmt: fBRL },
  { k: 'roi',                label: 'ROI(x)',          short: 'ROI',     fmt: fROI, fmtTela: fROI1, roi: true },
  { k: 'cliques',            label: 'Cliques',         short: 'Cliq.',   fmt: fNum },
  { k: 'leads',              label: 'Leads',           short: 'Leads',   fmt: fNum },
  { k: 'cpl',                label: 'CPL',             short: 'CPL',     fmt: fBRL },
  { k: 'qualificados',       label: 'Qualificados',    short: 'Qual.',   fmt: fNum },
  { k: 'pct_qualificados',   label: '% Qualif.',       short: '% Qual.', fmt: fPct },
  { k: 'oportunidades',      label: 'Oportunidades',   short: 'Oport.',  fmt: fNum },
  { k: 'cadeira_vendida',    label: 'Cadeiras',        short: 'Cad.',    fmt: fNum },
  { k: 'conv_cadeira',       label: 'Conv. Cadeiras',  short: 'Cv.Cad.', fmt: fPct },
  { k: 'cac_cadeira',        label: 'CAC Cadeira',     short: 'CAC Cad.', fmt: fBRL },
  { k: 'consultas_vendidas', label: 'Cons. Vendidas',  short: 'Cons.',   fmt: fNum },
  { k: 'conv_consultas',     label: 'Conv. Consultas', short: 'Cv.Cons.', fmt: fPct },
  { k: 'cac_consulta',       label: 'CAC Consulta',    short: 'CAC Cons.', fmt: fBRL },
  { k: 'combos_vendidos',    label: 'Combos',          short: 'Combos',  fmt: fNum },
  { k: 'conv_combo',         label: 'Conv. Combo',     short: 'Cv.Comb.', fmt: fPct },
  { k: 'cac_combos',         label: 'CAC Combos',      short: 'CAC Comb.', fmt: fBRL },
];
const COL_BY_KEY = Object.fromEntries(COLS.map(c => [c.k, c]));

const DIM_LABEL  = { campanha: 'Campanha', conjunto: 'Conj. Anúncios', anuncio: 'Anúncio', form_classe: 'Classificação' };
const CARD_LABEL = { campanha: 'Campanhas', conjunto: 'Conjuntos de Anúncios', anuncio: 'Anúncios', form_classe: 'Categorias (Form)' };
const TABELAS    = ['form_classe', 'campanha', 'conjunto', 'anuncio'];

// ══════════════════════════════════════════════════════════════
//  2. CROSS-FILTER — clicar numa linha filtra o dashboard
//
//  Estado próprio, fora dos closures dos dropdowns: o
//  createSearchDropdown de js/bi-dashboard.js expõe só load/
//  getValue/reset (o Set de selecionados é privado) e é
//  compartilhado por 14 views. Os dois conjuntos só se encontram
//  em montaParams(), unidos num CSV que o buildInFilter() do
//  controller já consome.
// ══════════════════════════════════════════════════════════════
const crossFilters = { campanha: [], conjunto: [], anuncio: [], form_classe: [] };

function temSelecao() { return Object.values(crossFilters).some(a => a.length); }

/** Valor de dimensão que contém vírgula não é filtrável: o CSV do
 *  buildInFilter() quebraria em dois valores e o dashboard zeraria em
 *  silêncio. Degrada para "não clicável" em vez de resultado errado. */
function temVirgula(v) { return String(v).includes(','); }

/** Linha não clicável: Total (o backend não distingue uma campanha chamada
 *  "Total"), a linha "(sem X)" — que o backend marca com `vazio` e cujo filtro
 *  `IN (...)` não sabe expressar —, dimensão vazia, ou valor com vírgula. */
function naoClicavel(row) {
  const d = String(row.dimensao ?? '').trim();
  return row.dimensao === 'Total' || row.vazio === true || d === '' || temVirgula(d);
}

/** Motivo de a linha não ser filtrável, para o title e o popover. */
function motivoNaoClicavel(row) {
  if (row.dimensao === 'Total') return 'linha de total — não filtra';
  if (row.vazio === true) return 'são os registros sem essa dimensão — não é filtrável';
  if (temVirgula(row.dimensao)) return 'não é filtrável por clique: o nome contém vírgula';
  return 'não filtrável por clique';
}

function csvUnion(dropdownCsv, crossList) {
  const set = new Set();
  (dropdownCsv || '').split(',').map(s => s.trim()).filter(Boolean).forEach(v => set.add(v));
  (crossList || []).forEach(v => set.add(v));
  return set.size ? [...set].join(',') : null;
}

function toggleCrossFilter(tableKey, valor, additive) {
  const list = crossFilters[tableKey];
  if (!list) return;

  const i = list.indexOf(valor);
  if (i >= 0) {
    list.splice(i, 1);                 // clicar de novo desliga
  } else {
    if (!additive) list.length = 0;    // clique simples TROCA; Ctrl/Cmd acumula
    list.push(valor);
  }

  // Snapshot ANTES do fetch que vai colapsar esta tabela para 1 linha.
  if (list.length) snapshotCapture(tableKey); else snapshotDrop(tableKey);

  popHide();
  renderCrossBar();
  applyFilters({ quiet: true });
}

function crossClearAll() {
  Object.keys(crossFilters).forEach(k => { crossFilters[k].length = 0; });
  snapshotDrop();
  renderCrossBar();
}

// ══════════════════════════════════════════════════════════════
//  3. SNAPSHOT da tabela clicada
//
//  Filtrar `campanha = X` não altera a linha da `campanha = Y` —
//  cada linha é uma fatia independente do mesmo período. Então,
//  enquanto o período e os filtros das OUTRAS dimensões não
//  mudarem, o ranking congelado continua numericamente correto:
//  é "o ranking calculado sem esta seleção", que é exatamente o
//  que o Power BI mostra num visual filtrado por outro visual.
//  As regras de invalidação abaixo são o que sustenta isso.
// ══════════════════════════════════════════════════════════════
const _snapshot = { key: null, rows: null, ctx: null };

/** Contexto = tudo que NÃO é o cross-filter da própria dimensão. */
function ctxKey(exceptDim) {
  return JSON.stringify([
    yearDropdown.getValue(), monthDropdown.getValue(), relPeriod.label(),
    getDateInput('data-inicio'), getDateInput('data-fim'),
    campDropdown.getValue(), formDropdown.getValue(),
    conjDropdown.getValue(), anDropdown.getValue(),
    ...Object.keys(crossFilters).filter(k => k !== exceptDim)
      .map(k => k + '=' + crossFilters[k].join('|')),
  ]);
}

function snapshotCapture(tableKey) {
  // Já é o dono: NÃO recaptura. _tabelasRaw[tableKey] agora é a versão
  // colapsada — recapturar no Ctrl+clique destruiria o ranking.
  if (_snapshot.key === tableKey) return;
  _snapshot.key  = tableKey;
  _snapshot.rows = (_tabelasRaw[tableKey] || []).slice();
  _snapshot.ctx  = ctxKey(tableKey);
}

function snapshotDrop(tableKey) {
  if (!tableKey || _snapshot.key === tableKey) {
    _snapshot.key = _snapshot.rows = _snapshot.ctx = null;
  }
}

/** Rede de segurança: roda em todo render. */
function snapshotCheck() {
  const dono = _snapshot.key;
  if (!dono) return;
  if (!crossFilters[dono].length || _snapshot.ctx !== ctxKey(dono)) snapshotDrop(dono);
}

/** Fonte de verdade do render. Com snapshot ativo: corpo = ranking
 *  congelado, Total = valor VIVO (filtrado), para o rodapé fechar com os
 *  KPIs do topo em vez de contradizê-los. */
function rowsFor(tableKey) {
  snapshotCheck();
  const live = _tabelasRaw[tableKey] || [];
  if (_snapshot.key !== tableKey || !_snapshot.rows) return { rows: live, snap: false };
  return {
    rows: _snapshot.rows.filter(r => r.dimensao !== 'Total')
      .concat(live.filter(r => r.dimensao === 'Total')),
    snap: true,
  };
}

// ══════════════════════════════════════════════════════════════
//  4. TABELAS — render + ordenação client-side
// ══════════════════════════════════════════════════════════════
const _tabelasRaw = { campanha: [], conjunto: [], anuncio: [], form_classe: [] };
const _sort = {
  campanha:    { key: 'invest', dir: 'desc' },
  conjunto:    { key: 'invest', dir: 'desc' },
  anuncio:     { key: 'invest', dir: 'desc' },
  form_classe: { key: 'invest', dir: 'desc' },
};

function sortBy(tableKey, colKey) {
  const st = _sort[tableKey];
  if (st.key === colKey) st.dir = st.dir === 'asc' ? 'desc' : 'asc';
  else { st.key = colKey; st.dir = colKey === 'dimensao' ? 'asc' : 'desc'; }
  renderTabela(tableKey);
  if (_modalKey === tableKey && document.getElementById('mk-modal').classList.contains('visible')) renderModal(tableKey);
}

function sortRows(rows, tableKey) {
  const st = _sort[tableKey];
  if (!st.key) return rows;
  const dir = st.dir === 'asc' ? 1 : -1;
  return rows.slice().sort((a, b) => {
    let av = a[st.key], bv = b[st.key];
    if (st.key === 'dimensao') return dir * String(av ?? '').localeCompare(String(bv ?? ''), 'pt', { numeric: true });
    av = (av === null || av === undefined) ? -Infinity : +av;
    bv = (bv === null || bv === undefined) ? -Infinity : +bv;
    return dir * (av - bv);
  });
}

function thHtml(tableKey, key, label, isNum) {
  const st = _sort[tableKey];
  const active = st.key === key;
  const arrow = active ? (st.dir === 'asc' ? ' ▲' : ' ▼') : '';
  const cls = [isNum ? 'num' : '', active ? 'th-active' : ''].filter(Boolean).join(' ');
  return `<th${cls ? ` class="${cls}"` : ''} title="Clique para ordenar" tabindex="0" role="button"
    aria-label="Ordenar por ${escHtml(label)}"
    onclick="sortBy('${tableKey}','${key}')"
    onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();sortBy('${tableKey}','${key}')}">${escHtml(label)}${arrow}</th>`;
}

function roiClass(val) {
  if (val === null || val === undefined) return '';
  return val < 1 ? ' td-danger' : (val >= 8 ? ' td-green' : ' td-amber');
}

// Largura em PX (não mais % de 100%): a tabela agora mostra as 17 colunas e
// rola lateralmente (pedido do usuário, além do popover de hover) em vez de
// só as 5 que cabiam na tela — precisa de largura própria maior que o card
// pra sobrar o que rolar. Calibrada pelo texto MAIS LARGO que cada métrica
// exibe de fato: investimento chega a "$ 159,141" e é o gargalo; CPL
// raramente passa de "$ 26"; ROI é o mais curto ("1.9x").
const NOME_PX = 180;
function colWidthPx(c) {
  if (/invest/.test(c.k))       return 95;
  if (/^cac_/.test(c.k))        return 85;
  if (c.k === 'cpl')            return 60;
  if (c.k === 'roi')            return 55;
  return 75; // cliques/leads/qualificados/oportunidades/contagens/% e conv.
}

function colgroupHtml(cols) {
  return '<colgroup>' + [`<col style="width:${NOME_PX}px">`]
    .concat(cols.map(c => `<col style="width:${colWidthPx(c)}px">`))
    .join('') + '</colgroup>';
}
function tabelaWidthPx(cols) {
  return NOME_PX + cols.reduce((a, c) => a + colWidthPx(c), 0);
}

function renderTabela(tableKey) {
  const cols = COLS; // as 17 métricas — rolagem lateral mostra o resto
  const { rows: raw } = rowsFor(tableKey);

  const tbl = document.getElementById('thead-' + tableKey).closest('table');
  tbl.querySelector('colgroup')?.remove();
  tbl.insertAdjacentHTML('afterbegin', colgroupHtml(cols));
  tbl.style.width = tabelaWidthPx(cols) + 'px';

  document.getElementById('thead-' + tableKey).innerHTML =
    thHtml(tableKey, 'dimensao', DIM_LABEL[tableKey], false) +
    cols.map(c => thHtml(tableKey, c.k, c.short, true)).join('');

  const tbody = document.getElementById('tbody-' + tableKey);
  if (!raw.length) {
    tbody.innerHTML = `<tr><td colspan="${cols.length + 1}" style="text-align:center;padding:18px;color:var(--muted)">Sem dados no período</td></tr>`;
    return;
  }

  const total = raw.filter(r => r.dimensao === 'Total');
  const rows  = sortRows(raw.filter(r => r.dimensao !== 'Total'), tableKey).concat(total);
  const sel   = new Set(crossFilters[tableKey] || []);

  tbody.innerHTML = rows.map(r => {
    const isTotal  = r.dimensao === 'Total';
    const clicavel = !naoClicavel(r);
    const marcada  = sel.has(r.dimensao);

    const cls = [
      isTotal ? 'tr-total' : '',
      (!isTotal && !clicavel) ? 'is-vazio' : '',
      marcada ? 'is-sel' : '',
      // atenua as demais linhas enquanto há seleção nesta tabela
      (sel.size && clicavel && !marcada) ? 'is-dim' : '',
    ].filter(Boolean).join(' ');

    // Célula do nome como <button> real quando clicável: Enter/Espaço, foco e
    // :focus-visible nativos, aria-pressed legítimo — e o clique borbulha para
    // o mesmo handler delegado, então o mouse continua clicando na linha toda.
    const titulo = (!isTotal && !clicavel)
      ? r.dimensao + ' — ' + motivoNaoClicavel(r)
      : r.dimensao;
    const nome = clicavel
      ? `<td title="${escHtml(titulo)}"><button type="button" class="mk-row-btn" aria-pressed="${marcada}">${escHtml(r.dimensao)}</button></td>`
      : `<td${isTotal ? ' class="td-nome"' : ''} title="${escHtml(titulo)}">${escHtml(r.dimensao)}</td>`;

    const tds = cols.map(c => {
      const v = r[c.k];
      return `<td class="num${c.roi ? roiClass(v) : ''}">${(c.fmtTela || c.fmt)(v)}</td>`;
    }).join('');

    return `<tr${cls ? ` class="${cls}"` : ''} data-dim="${escHtml(r.dimensao)}" data-tbl="${tableKey}">${nome}${tds}</tr>`;
  }).join('');
}

// ══════════════════════════════════════════════════════════════
//  5. CLIQUE NA LINHA + feedback visual
// ══════════════════════════════════════════════════════════════

// Um único listener delegado: o <tbody> é reescrito a cada render, então
// listener por linha não sobreviveria. Cobre também o <tbody> do modal, que
// emite os mesmos data-tbl/data-dim.
document.addEventListener('click', e => {
  // body.loading/loading-quiet já bloqueiam o ponteiro em .main, mas não o
  // teclado — o guard não é redundante.
  if (document.body.classList.contains('loading') ||
      document.body.classList.contains('loading-quiet')) return;

  const tr = e.target.closest('tbody tr[data-tbl][data-dim]');
  if (!tr) return;                                 // <th> de ordenar e botão
                                                   // de expandir vivem fora
                                                   // do <tbody>
  if (tr.classList.contains('tr-total') || tr.classList.contains('is-vazio')) return;
  const dim = (tr.dataset.dim || '').trim();
  if (!dim || dim === 'Total' || temVirgula(dim)) return;

  toggleCrossFilter(tr.dataset.tbl, dim, e.metaKey || e.ctrlKey);
});

/** Chips do que está selecionado, no header. */
function renderCrossBar() {
  const bar = document.getElementById('mk-crossbar');
  if (!bar) return;

  const chips = [];
  Object.keys(crossFilters).forEach(d => crossFilters[d].forEach(v => chips.push({ d, v })));

  if (!chips.length) { bar.innerHTML = ''; return; }

  bar.innerHTML = chips.map(c => `
    <span class="filter-tag" data-dim="${c.d}" data-val="${escHtml(c.v)}">
      <span class="mk-chip-dim">${DIM_LABEL[c.d]}:</span> ${escHtml(c.v)}
      <button class="tag-remove" type="button" aria-label="Remover filtro ${escHtml(DIM_LABEL[c.d])} ${escHtml(c.v)}">&#x2715;</button>
    </span>`).join('')
    + `<button class="mk-chip-clear" type="button" id="mk-cross-clear">limpar seleção</button>`;
}

document.getElementById('mk-crossbar').addEventListener('click', e => {
  if (document.body.classList.contains('loading') ||
      document.body.classList.contains('loading-quiet')) return;
  if (e.target.id === 'mk-cross-clear') { crossClearAll(); applyFilters({ quiet: true }); return; }
  const rm = e.target.closest('.tag-remove');
  if (!rm) return;
  const chip = rm.closest('.filter-tag');
  toggleCrossFilter(chip.dataset.dim, chip.dataset.val, true); // additive → só remove
});

/** Estado por card, reusando a pílula do header de cada tabela (zero altura
 *  nova). Vermelho = "confie, foi recalculado com a seleção". Âmbar =
 *  "leitura especial: ranking congelado". */
function renderCardFlags() {
  const algumaSel = temSelecao();

  TABELAS.forEach(k => {
    const card = document.querySelector(`.mk-tbl-card[data-table="${k}"]`);
    const pill = document.getElementById('count-' + k);
    if (!card || !pill) return;

    const propria = crossFilters[k].length > 0;
    const outra   = algumaSel && !propria;
    const snap    = _snapshot.key === k;

    card.classList.toggle('is-cross', outra);
    pill.classList.toggle('is-cross', outra);
    pill.classList.toggle('is-snap', snap);

    if (snap) {
      pill.textContent = 'ranking sem a seleção';
      pill.title = 'As linhas são o ranking do período ANTES desta seleção — '
        + 'filtrar uma campanha não muda o número das outras, então continuam corretas. '
        + 'A linha Total já é o valor filtrado e fecha com os KPIs do topo.';
    } else {
      const n = (_tabelasRaw[k] || []).filter(r => r.dimensao !== 'Total').length;
      pill.textContent = outra ? 'filtrado' : n + (n === 1 ? ' linha' : ' linhas');
      pill.title = outra ? 'Recalculado com a seleção ativa em outra dimensão.' : '';
    }
  });
}

// ══════════════════════════════════════════════════════════════
//  6. HOVER — popover com as 17 métricas da linha
// ══════════════════════════════════════════════════════════════
const pop = document.getElementById('mk-pop');

function popHtml(row) {
  const item = c => `<span class="mk-pop-k">${escHtml(c.label)}</span><span class="mk-pop-v">${c.fmt(row[c.k])}</span>`;
  const grupos = [
    ['invest', 'roi', 'cliques'],
    ['leads', 'cpl', 'qualificados', 'pct_qualificados', 'oportunidades'],
    ['cadeira_vendida', 'conv_cadeira', 'cac_cadeira'],
    ['consultas_vendidas', 'conv_consultas', 'cac_consulta'],
    ['combos_vendidos', 'conv_combo', 'cac_combos'],
  ];
  const dica = naoClicavel(row)
    ? motivoNaoClicavel(row)
    : 'clique filtra o dashboard · Ctrl+clique acumula';
  return `<div class="mk-pop-title">${escHtml(row.dimensao)}</div>
    <div class="mk-pop-grid">${grupos.map(g => g.map(k => item(COL_BY_KEY[k])).join('')).join('<div class="mk-pop-sep"></div>')}</div>
    <div class="mk-pop-hint">${dica}</div>`;
}

// Lê de rowsFor(), não de _tabelasRaw: com snapshot ativo a linha visível vem
// do ranking congelado, e o popover tem de mostrar os MESMOS números dela.
function findRow(tbl, dim) { return rowsFor(tbl).rows.find(r => r.dimensao === dim); }

function popHide() { pop.classList.remove('visible'); }

document.addEventListener('mouseover', e => {
  const tr = e.target.closest('.mk-tbl tbody tr[data-tbl][data-dim]');
  if (!tr) return;
  const row = findRow(tr.dataset.tbl, tr.dataset.dim);
  if (!row) return;
  pop.innerHTML = popHtml(row);
  pop.classList.add('visible');
  posicionaPop(tr);
});
document.addEventListener('mouseout', e => {
  if (e.target.closest('.mk-tbl tbody tr')) popHide();
});

function posicionaPop(tr) {
  const r = tr.getBoundingClientRect();
  const p = pop.getBoundingClientRect();
  let left = r.right + 10;
  if (left + p.width > window.innerWidth - 8) left = Math.max(8, r.left - p.width - 10);
  let top = r.top - 6;
  if (top + p.height > window.innerHeight - 8) top = Math.max(8, window.innerHeight - p.height - 8);
  pop.style.left = left + 'px';
  pop.style.top = top + 'px';
}

// ══════════════════════════════════════════════════════════════
//  7. MODAL — tabela completa (17 métricas × todas as linhas)
// ══════════════════════════════════════════════════════════════
let _modalKey = null;
const modal = document.getElementById('mk-modal');

function renderModal(tableKey) {
  _modalKey = tableKey;
  set('mk-modal-title', 'Performance por ' + CARD_LABEL[tableKey]);
  document.getElementById('mk-modal-thead').innerHTML =
    thHtml(tableKey, 'dimensao', DIM_LABEL[tableKey], false) +
    COLS.map(c => thHtml(tableKey, c.k, c.label, true)).join('');

  const { rows: raw } = rowsFor(tableKey);
  const total = raw.filter(r => r.dimensao === 'Total');
  const rows  = sortRows(raw.filter(r => r.dimensao !== 'Total'), tableKey).concat(total);
  const sel   = new Set(crossFilters[tableKey] || []);

  document.getElementById('mk-modal-tbody').innerHTML = rows.map(r => {
    const isTotal  = r.dimensao === 'Total';
    const clicavel = !naoClicavel(r);
    const marcada  = sel.has(r.dimensao);
    const cls = [
      isTotal ? 'tr-total' : '',
      (!isTotal && !clicavel) ? 'is-vazio' : '',
      marcada ? 'is-sel' : '',
      (sel.size && clicavel && !marcada) ? 'is-dim' : '',
    ].filter(Boolean).join(' ');

    const nome = clicavel
      ? `<td title="${escHtml(r.dimensao)}"><button type="button" class="mk-row-btn" aria-pressed="${marcada}">${escHtml(r.dimensao)}</button></td>`
      : `<td title="${escHtml(r.dimensao)}">${escHtml(r.dimensao)}</td>`;

    const tds = COLS.map(c => {
      const v = r[c.k];
      return `<td class="num${c.roi ? roiClass(v) : ''}">${c.fmt(v)}</td>`;
    }).join('');
    return `<tr${cls ? ` class="${cls}"` : ''} data-dim="${escHtml(r.dimensao)}" data-tbl="${tableKey}">${nome}${tds}</tr>`;
  }).join('');
}

document.querySelectorAll('.mk-expand-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    renderModal(btn.dataset.open);
    modal.classList.add('visible');
    popHide();
  });
});
function fechaModal() { modal.classList.remove('visible'); _modalKey = null; }
document.getElementById('mk-modal-close').addEventListener('click', fechaModal);
modal.addEventListener('click', e => { if (e.target === modal) fechaModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') fechaModal(); });

// ══════════════════════════════════════════════════════════════
//  8. KPIs / Resumo Financeiro / Funil
// ══════════════════════════════════════════════════════════════
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

function fillFinanceiro(k, porOrigem) {
  set('fin-invest', fBRL(k.investimento));
  const origens = Object.keys(porOrigem || {});
  set('fin-invest-origem', origens
    .map(o => `${o.charAt(0).toUpperCase() + o.slice(1)} ${fPctRatio(porOrigem[o].custo_mkt, k.investimento)}`)
    .join(' · '));
  set('fin-roi', fROI(k.roi));
  set('fin-cpl', fBRL(k.cpl));
  set('fin-cac-cadeira', fBRL(k.cac_cadeira));
  set('fin-cac-consulta', fBRL(k.cac_consulta));
  set('fin-cac-combo', fBRL(k.cac_combos));
}

// Silhueta do funil: 7 bandas de altura igual num viewBox 0 0 300 520,
// desenhadas pelo usuário. Geometria FIXA (não escala com o dado) — mesmo
// comportamento do funil em cascata do Power BI. Quem carrega a leitura
// quantitativa são os números em texto nas colunas dos dois lados.
// Validado em funil-marketing-v2-mock.html (30/09/2026).
const FUNIL_V2_PATHS = [
  'M 10,0 L 290,0 L 290,50 C 290,65 245,72 245,74 L 55,74 C 55,72 10,65 10,50 Z',
  'M 55,75 L 245,75 L 245,120 C 245,135 220,146 220,149 L 80,149 C 80,146 55,135 55,120 Z',
  'M 80,150 L 220,150 L 220,195 C 220,210 173,212 173,224 L 127,224 C 127,212 80,210 80,195 Z',
  'M 127,225 L 173,225 C 173,235 167,240 167,280 C 167,295 163,298 163,299 L 137,299 C 137,298 133,295 133,280 C 133,240 127,235 127,225 Z',
  'M 137,300 L 163,300 L 161,374 L 139,374 Z',
  'M 139,375 L 161,375 C 161,420 156,435 156,449 L 144,449 C 144,435 139,420 139,375 Z',
  'M 148,450 L 152,450 L 151,520 L 149,520 Z'
];

// Rótulos por posição. /marketing/dados sempre traz as 7 etapas nesta ordem
// (montarFunilMarketing em controle/marketing_controle.php) — o `etapa` do
// backend serve de fallback se isso mudar.
const FUNIL_V2_ETAPAS = [
  { label: 'Total de Leads',       cv: '' },
  { label: 'Leads Qualificados',   cv: '% Qualificação' },
  { label: 'Oportunidades',        cv: 'Oportunidades' },
  { label: 'Cadeiras Vendidas',    cv: 'Conv. Cadeiras' },
  { label: 'Consultas Vendidas',   cv: 'Conv. Consultas Vendidas' },
  { label: 'Consultas Realizadas', cv: 'Conv. Consultas Realizadas' },
  { label: 'Combos Vendidos',      cv: 'Conv. de Combo' }
];

// As 2 primeiras etapas escrevem o valor por dentro da silhueta, onde ela
// ainda é larga; da 3ª em diante o texto vai na coluna da direita.
const FUNIL_V2_DENTRO = 2;

function fillFunil(funil) {
  const body = document.getElementById('funil-body');
  if (!funil || !funil.length) {
    body.classList.add('is-empty');
    body.textContent = 'Sem dados no período';
    return;
  }
  body.classList.remove('is-empty');

  const linhas = funil.map((row, i) => {
    const meta = FUNIL_V2_ETAPAS[i] || { label: row.etapa, cv: 'Conversão' };
    const pos  = row.lucro >= 0;
    // 1ª etapa é a base do funil: 100% por definição, e o payload nem traz
    // `conversao` nela.
    const cvTxt = i === 0
      ? fPct(1)
      : escHtml(meta.cv) + ': <b>' + fPct(row.conversao) + '</b>';
    const lado = i < FUNIL_V2_DENTRO ? '' :
      '<span class="funil-v2-side-val">' + fNum(row.valor) + '</span>' +
      '<span class="funil-v2-side-lbl">' + escHtml(meta.label) + '</span>';
    return '<div class="funil-v2-row' + (pos ? ' is-pos' : '') + '" data-step="' + i + '">' +
        '<div class="funil-v2-casc">' +
          '<span class="funil-v2-casc-val ' + (pos ? 'is-pos' : 'is-neg') + '">' + fLucro(row.lucro) + '</span>' +
          '<span class="funil-v2-casc-cv">' + cvTxt + '</span>' +
        '</div>' +
        '<div></div>' +
        '<div class="funil-v2-side">' + lado + '</div>' +
      '</div>';
  }).join('');

  const paths = funil.map((row, i) =>
    '<path class="funil-v2-path" data-step="' + i + '" d="' + (FUNIL_V2_PATHS[i] || '') + '" fill="url(#funil-v2-grad)"/>'
  ).join('');

  const dentro = funil.slice(0, FUNIL_V2_DENTRO).map((row, i) => {
    const meta = FUNIL_V2_ETAPAS[i] || { label: row.etapa };
    return '<div class="funil-v2-inner-cell">' +
        '<span class="funil-v2-inner-val">' + fNum(row.valor) + '</span>' +
        '<span class="funil-v2-inner-lbl">' + escHtml(meta.label) + '</span>' +
      '</div>';
  }).join('');

  // aria-hidden: os mesmos números já estão como texto nas colunas ao lado —
  // o desenho é reforço visual, não a única via de leitura.
  const overlay =
    '<div class="funil-v2-overlay" aria-hidden="true"><div class="funil-v2-shape">' +
      '<svg class="funil-v2-svg" viewBox="0 0 300 520" preserveAspectRatio="none">' +
        '<defs><linearGradient id="funil-v2-grad" x1="0" y1="0" x2="0" y2="1">' +
          '<stop class="funil-v2-grad-top" offset="0"/><stop class="funil-v2-grad-bot" offset="1"/>' +
        '</linearGradient></defs>' + paths +
      '</svg>' +
      '<div class="funil-v2-inner">' + dentro + '</div>' +
    '</div></div>';

  body.innerHTML = linhas + overlay;
  ligaFunilTooltip(body, funil);
}

/* Tooltip: preso à LINHA inteira (e não ao <path>), porque o overlay do
   desenho é pointer-events:none e a última etapa é uma agulha de 4px. A
   linha ativa acende o path correspondente via classe. */
const funilTipEl = document.getElementById('funil-tip');
function funilShowTip(clientX, clientY, html) {
  funilTipEl.innerHTML = html;
  funilTipEl.classList.add('visible');
  const pad = 16;
  let x = clientX + pad, y = clientY + pad;
  funilTipEl.style.left = x + 'px';
  funilTipEl.style.top  = y + 'px';
  const rect = funilTipEl.getBoundingClientRect();
  if (rect.right  > window.innerWidth)  x = clientX - rect.width  - pad;
  if (rect.bottom > window.innerHeight) y = clientY - rect.height - pad;
  funilTipEl.style.left = Math.max(4, x) + 'px';
  funilTipEl.style.top  = Math.max(4, y) + 'px';
}
function funilHideTip() { funilTipEl.classList.remove('visible'); }
function funilTipRow(label, val) {
  return '<div class="bi-tooltip-row"><span class="bi-tooltip-label">' + escHtml(label) +
    '</span><span class="bi-tooltip-val">' + val + '</span></div>';
}

function ligaFunilTooltip(body, funil) {
  const paths = body.querySelectorAll('.funil-v2-path');
  body.querySelectorAll('.funil-v2-row').forEach(el => {
    const i    = Number(el.dataset.step);
    const row  = funil[i];
    const meta = FUNIL_V2_ETAPAS[i] || { label: row.etapa, cv: 'Conversão' };
    el.addEventListener('mousemove', ev => {
      paths.forEach(p => p.classList.toggle('is-hot', Number(p.dataset.step) === i));
      funilShowTip(ev.clientX, ev.clientY,
        '<div class="bi-tooltip-inner">' +
          '<div class="bi-tooltip-title">' + escHtml(meta.label) + '</div>' +
          funilTipRow('Quantidade', fNum(row.valor)) +
          (i === 0 ? '' : funilTipRow(meta.cv, fPct(row.conversao))) +
          funilTipRow('Lucro acumulado', fLucro(row.lucro)) +
        '</div>');
    });
    el.addEventListener('mouseleave', () => {
      paths.forEach(p => p.classList.remove('is-hot'));
      funilHideTip();
    });
  });
}

// ══════════════════════════════════════════════════════════════
//  9. Altura: o bloco recebe o espaço que sobrou abaixo dos KPIs
//     e as tabelas dividem entre si — em monitor maior aparecem
//     mais linhas, e o dashboard nunca vira scroll de página.
// ══════════════════════════════════════════════════════════════
function medirAltura() {
  const content = document.querySelector('.content');
  const split = document.getElementById('mk-split');
  if (!content || !split) return;
  const disponivel = content.clientHeight - split.offsetTop - 16;
  // Piso: abaixo disso as tabelas ficariam com poucas linhas visíveis —
  // melhor a página rolar um pouco do que o card esconder o conteúdo.
  // 820: piso antes governado pelas tabelas (750 = 3 tabelas de 190px +
  // Categorias compacto + gaps). Subiu com o funil em cascata: a silhueta
  // se divide em 7 bandas e, abaixo de ~820, as bandas do meio ficam
  // baixas demais pro par valor+rótulo da cascata caber sem apertar.
  // Ver .mk-tbl-card min-height e a seção 20c no bi-theme.css.
  split.style.setProperty('--mk-h', Math.max(disponivel, 820) + 'px');
}
window.addEventListener('resize', medirAltura);

// ══════════════════════════════════════════════════════════════
//  10. Carregar dashboard
// ══════════════════════════════════════════════════════════════
function montaParams() {
  const rel = relPeriod.getRange();
  return {
    year:        yearDropdown.getValue(),
    months:      monthDropdown.getValue(),
    // dropdown ∪ cross-filter — o backend recebe um CSV só e não precisa
    // saber de onde cada valor veio.
    campanha:    csvUnion(campDropdown.getValue(), crossFilters.campanha),
    conjunto:    csvUnion(conjDropdown.getValue(), crossFilters.conjunto),
    anuncio:     csvUnion(anDropdown.getValue(),   crossFilters.anuncio),
    // 'form' (nome do form preenchido) e 'form_classe' (Evento/Digital) são
    // params DIFERENTES: a 4ª tabela agrupa por classe, não por form.
    form:        formDropdown.getValue(),
    form_classe: crossFilters.form_classe.length ? crossFilters.form_classe.join(',') : null,
    data_inicio: rel ? rel.start : getDateInput('data-inicio'),
    data_fim:    rel ? rel.end   : getDateInput('data-fim'),
  };
}

async function loadDashboard(f, opts = {}) {
  const overlay = document.getElementById('loading-overlay');
  const errorBanner = document.getElementById('error-banner');

  overlay.classList.remove('hidden');
  // 'quiet': recarga vinda de clique em linha não escurece o dashboard
  // inteiro (body.loading .main{opacity:.55}) — só a barra vermelha do topo.
  // As duas classes bloqueiam clique em .main; só a primeira escurece.
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
    fillFunil(data.funil);
    fillFinanceiro(data.kpis, data.investimento_por_origem);

    _tabelasRaw.campanha    = data.tabelas.campanha    || [];
    _tabelasRaw.conjunto    = data.tabelas.conjunto    || [];
    _tabelasRaw.anuncio     = data.tabelas.anuncio     || [];
    _tabelasRaw.form_classe = data.tabelas.form_classe || [];

    TABELAS.forEach(renderTabela);
    renderCardFlags();
    if (_modalKey && modal.classList.contains('visible')) renderModal(_modalKey);
  } catch (err) {
    errorBanner.textContent = 'Erro ao carregar dados: ' + err.message;
    errorBanner.classList.add('visible');
  } finally {
    overlay.classList.add('hidden');
    document.body.classList.remove('loading', 'loading-quiet');
    requestAnimationFrame(medirAltura);
  }
}

// opts.fromControls: veio do botão Aplicar ou de mudança de período — o
// ranking congelado deixa de ser comparável, então o snapshot cai.
// opts.quiet: veio de clique em linha — recarga sem piscar a tela.
function applyFilters(opts = {}) {
  if (opts.fromControls) snapshotDrop();
  updateBadge();
  loadDashboard(montaParams(), { quiet: !!opts.quiet });
}

function clearFilters() {
  relPeriod.reset();
  yearDropdown.reset(); monthDropdown.reset();
  campDropdown.reset(); formDropdown.reset();
  conjDropdown.reset(); anDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim', 'data-fim-clear');
  crossClearAll();
  applyFilters({ fromControls: true });
}

// ── Badge do período selecionado (só os dropdowns da barra de filtros —
// o crossbar de clique já mostra os filtros por clique separadamente) ──
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

document.getElementById('btn-apply').addEventListener('click', () => applyFilters({ fromControls: true }));
document.getElementById('btn-clear').addEventListener('click', clearFilters);

// ── Painel de filtros recolhível ──────────────────────────────
// Componente compartilhado (initFiltersToggle, js/bi-dashboard.js) desde que
// virou padrão dos dashboards — recolher no Aplicar já vem de lá. O onToggle
// é o que esta tela tem de próprio: abrir/fechar muda o offsetTop do
// #mk-split, então a altura da faixa de tabelas precisa ser remedida.
initFiltersToggle({
  key: 'marketing',
  onToggle: () => requestAnimationFrame(medirAltura),
});

// Botões de limpar e visibilidade dos date inputs
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

medirAltura();
applyFilters({ fromControls: true });
</script>
