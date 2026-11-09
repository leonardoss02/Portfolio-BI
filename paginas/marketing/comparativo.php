<div class="dash-marketing-comp">

<!-- ══════════════════════════════════════════════════════════════
     Marketing — Comparativo Anual (ano corrente × mesmo período do
     ano anterior).

     Diferente da view 1 (tela única com cross-filter), esta rola na
     vertical: é leitura analítica de cima para baixo, não painel de
     monitoramento — não há medirAltura()/--mk-h aqui.

     Ordem de leitura: KPIs → tabela por classificação de campanha →
     funil + evolução mensal.

     ATENÇÃO à divergência intencional: esta view classifica
     Evento/Digital pelo NOME DA CAMPANHA (condClasseCampanha), e a
     view 1 classifica pelo FORM (condFormClasse). O "Evento" de uma
     não bate com o da outra — está explicado no dicionário.

     Todo o CSS vive no bloco .dash-marketing-comp de css/bi-theme.css
     (seção 20c) — nada de <style> inline aqui.
     ══════════════════════════════════════════════════════════════ -->

<div id="loading-overlay" class="hidden"></div>

<!-- ── HEADER ──────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo" aria-hidden="true">MR</div>
    <div>
      <div class="header-title">Comparativo Anual &mdash; Marketing</div>
      <div class="header-sub">Ano atual &times; mesmo período do ano anterior</div>
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
      <a class="tab-switch-btn active" href="<?= $this->site ?>marketing/comparativo<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Comparativo</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/eventos<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Eventos</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/desempenho<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Desempenho</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/descartes<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Descartes</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/classificacao<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Classificação</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>marketing/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
    </div>
  </div>
</header>

<!-- ── FILTROS ─────────────────────────────────────────────── -->
<section class="filters is-collapsible" aria-label="Filtros do dashboard">
  <button class="filters-toggle" type="button" aria-expanded="true" aria-controls="filters-fields">
    <span>Filtros</span>
    <span class="dd-caret filters-toggle-caret">&#x25BE;</span>
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
<script>initFiltersToggle({ key: 'marketing-comparativo' });</script>

<!-- ── BARRA DE PERÍODO COMPARADO ──────────────────────────── -->
<div class="cmp-periodbar" id="cmp-periodbar"></div>

<div id="error-banner" role="alert"></div>

<!-- ── CONTEÚDO ────────────────────────────────────────────── -->
<main class="main" aria-label="Comparativo anual de marketing">

  <div class="cmp-kpi-grid" id="kpi-volume"></div>
  <div class="cmp-kpi-grid efic" id="kpi-eficiencia"></div>

  <!-- Largura cheia: são 12 métricas × 3 colunas; dentro de um split
       de 60% só ~4 apareciam antes do scroll horizontal. -->
  <article class="bi-card">
    <div class="bi-card-header">
      <span class="bi-card-title">Performance por Classificação de Campanha</span>
      <span class="bi-card-sub">Evento = campanha OTR/INA/OTW/WEB &middot; todo o resto em Digital</span>
    </div>
    <div class="cmp-tbl-scroll">
      <table class="cmp-tbl" id="cmp-tbl">
        <thead id="cmp-thead"></thead>
        <tbody id="cmp-tbody"></tbody>
      </table>
    </div>
  </article>

  <div class="cmp-split">

    <article class="bi-card">
      <div class="bi-card-header">
        <span class="bi-card-title">Funil de Vendas</span>
        <span class="bi-card-sub">barra cheia = ano atual &middot; tracejada = ano anterior</span>
      </div>
      <div class="bi-card-body">
        <div class="cmp-funil" id="cmp-funil"></div>
      </div>
    </article>

    <article class="bi-card">
      <div class="bi-card-header">
        <div class="cmp-chart-head">
          <span class="bi-card-title">Evolução Mensal</span>
          <select class="cmp-metric-sel" id="metric-sel" aria-label="Métrica do gráfico">
            <option value="investimento">Investimento</option>
            <option value="total_leads">Leads</option>
            <option value="qualificados">Qualificados</option>
            <option value="cadeira_vendida">Cadeiras Vendidas</option>
            <option value="consultas_vendidas">Consultas Vendidas</option>
            <option value="combos_vendidos">Combos Vendidos</option>
            <option value="faturamento_combos">Faturamento</option>
            <option value="cpl">CPL</option>
          </select>
        </div>
        <div class="cmp-legend" id="cmp-legend"></div>
      </div>
      <div class="bi-card-body">
        <div class="cmp-chart-wrap" id="chart-wrap">
          <svg id="cmp-chart" role="img" aria-labelledby="chart-desc"></svg>
          <span id="chart-desc" class="sr-only"></span>
          <div class="cmp-tip" id="cmp-tip"></div>
        </div>
        <details class="cmp-datatable">
          <summary>Ver os mesmos dados em tabela</summary>
          <div class="cmp-tbl-scroll">
            <table class="cmp-tbl" id="chart-tbl"><thead id="chart-thead"></thead><tbody id="chart-tbody"></tbody></table>
          </div>
        </details>
      </div>
    </article>

  </div>

</main>

</div>

<script>
// ══════════════════════════════════════════════════════════════
//  Marketing — Comparativo Anual · JS da view
//
//  Formatadores e factories de dropdown (fBRL/fNum/fROI/fBar,
//  createMultiDropdown/createSearchDropdown, getDateInput) vêm de
//  js/bi-dashboard.js, carregado pelo template e COMPARTILHADO por
//  14 views — não alterar aquele arquivo por causa desta.
//
//  Layout e interação validados antes em
//  marketing-comparativo-mock.html.
// ══════════════════════════════════════════════════════════════

const API            = '<?= $this->site ?>marketing/comparativoDados';
const CAMPANHAS_URL  = '<?= $this->site ?>marketing/campanhas';
const FORMS_URL      = '<?= $this->site ?>marketing/forms';
const CONJUNTOS_URL  = '<?= $this->site ?>marketing/conjuntosAnuncio';
const ANUNCIOS_URL   = '<?= $this->site ?>marketing/anuncios';

const YEARS  = anosDesde(2025);
const MESES_UI = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

let DADOS = null;

// ══════════════════════════════════════════════════════════════
//  1. Formatadores locais
// ══════════════════════════════════════════════════════════════

// fBRL (compartilhado) arredonda para dólar inteiro — bom para
// investimento e faturamento, ruim para CPL/CAC, onde a diferença
// entre $22,44 e $22,04 é justamente o que se está olhando.
function fUSD2(v) {
  return '$ ' + (Number(v) || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Rótulos colados nos pontos do gráfico: 8+ meses × 2 séries são
// 16+ números no plot, e o formato cheio ($ 112.800) colidiria.
const kUSD = v => v >= 1000 ? '$' + (v / 1000).toFixed(v >= 10000 ? 0 : 1).replace('.', ',') + 'k' : '$' + Math.round(v);
const kNum = v => v >= 10000 ? (v / 1000).toFixed(0) + 'k' : fNum(Math.round(v));

const div = (n, d) => d > 0 ? n / d : 0;

function escHtml(s) {
  return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function dataBr(iso) {
  if (!iso) return '—';
  const [y, m, d] = iso.slice(0, 10).split('-');
  return d + ' ' + MESES_UI[Number(m) - 1].toLowerCase();
}

// ══════════════════════════════════════════════════════════════
//  2. Variação — a régua de cor é da MÉTRICA, não do sinal
//
//  'up'      → subir é bom   (leads, combos, faturamento, ROI)
//  'down'    → subir é RUIM  (CPL e os 3 CACs) — cores invertidas
//  'neutral' → sem juízo     (Investimento, Cliques)
//
//  Sem isso um CAC 40% maior sairia em verde, que é o que o
//  relatório do Power BI faz hoje.
// ══════════════════════════════════════════════════════════════
function deltaPct(atual, ly) {
  if (!ly) return atual ? Infinity : 0;
  return (atual - ly) / Math.abs(ly);
}

function fDelta(v) {
  if (!isFinite(v)) return 'novo';
  const s = (Math.abs(v) * 100).toFixed(1).replace('.', ',') + '%';
  return (v > 0 ? '+' : v < 0 ? '−' : '') + s;
}

function classeDelta(v, bom) {
  if (!isFinite(v)) return bom === 'down' ? 'neg' : 'pos';
  if (bom === 'neutral' || Math.abs(v) < 0.0005) return 'flat';
  return ((bom === 'up') ? (v > 0) : (v < 0)) ? 'pos' : 'neg';
}

function chipDelta(atual, ly, bom) {
  const v   = deltaPct(atual, ly);
  const cl  = classeDelta(v, bom);
  const arw = !isFinite(v) ? '▲' : (Math.abs(v) < 0.0005 ? '=' : (v > 0 ? '▲' : '▼'));
  return '<span class="cmp-delta ' + cl + '"><span class="arw">' + arw + '</span>' + fDelta(v) + '</span>';
}

// ══════════════════════════════════════════════════════════════
//  3. Barra de período
// ══════════════════════════════════════════════════════════════
function renderPeriodo(meta) {
  const pa = meta.periodo_atual, pl = meta.periodo_ly;
  document.getElementById('cmp-periodbar').innerHTML =
    '<span class="cmp-per now"><span class="cmp-per-dot"></span>' +
      '<span class="cmp-per-year">' + escHtml(meta.ano_atual) + '</span>' +
      '<span class="cmp-per-range">' + dataBr(pa.inicio) + ' – ' + dataBr(pa.fim) + '</span></span>' +
    '<span class="cmp-per-vs">versus</span>' +
    '<span class="cmp-per ly"><span class="cmp-per-dot"></span>' +
      '<span class="cmp-per-year">' + escHtml(meta.ano_ly) + '</span>' +
      '<span class="cmp-per-range">' + dataBr(pl.inicio) + ' – ' + dataBr(pl.fim) + '</span></span>' +
    '<span class="cmp-per-note">O ano anterior é sempre o mesmo intervalo deslocado 12 meses.</span>';
}

// ══════════════════════════════════════════════════════════════
//  4. KPIs comparativos
// ══════════════════════════════════════════════════════════════
const KPIS_VOLUME = [
  { k: 'investimento',         rot: 'Investimento',     fmt: fBRL, bom: 'neutral', cls: '' },
  { k: 'total_leads',          rot: 'Leads',            fmt: fNum, bom: 'up', cls: 'aquisicao' },
  { k: 'qualificados',         rot: 'Qualificados',     fmt: fNum, bom: 'up', cls: 'sinal' },
  { k: 'cadeira_vendida',      rot: 'Cadeiras',         fmt: fNum, bom: 'up', cls: 'evento' },
  { k: 'consultas_vendidas',   rot: 'Cons. Vendidas',   fmt: fNum, bom: 'up', cls: 'consulta' },
  { k: 'consultas_realizadas', rot: 'Cons. Realizadas', fmt: fNum, bom: 'up', cls: 'consulta' },
  { k: 'combos_vendidos',      rot: 'Combos',           fmt: fNum, bom: 'up', cls: 'fecho' },
  { k: 'faturamento_combos',   rot: 'Faturamento',      fmt: fBRL, bom: 'up', cls: 'fecho' },
];

const KPIS_EFIC = [
  { k: 'roi',          rot: 'ROI (x)',      fmt: fROI,  bom: 'up',   cls: 'fecho' },
  { k: 'cpl',          rot: 'CPL',          fmt: fUSD2, bom: 'down', cls: '' },
  { k: 'cac_cadeira',  rot: 'CAC Cadeira',  fmt: fUSD2, bom: 'down', cls: 'evento' },
  { k: 'cac_consulta', rot: 'CAC Consulta', fmt: fUSD2, bom: 'down', cls: 'consulta' },
  { k: 'cac_combos',   rot: 'CAC Combo',    fmt: fUSD2, bom: 'down', cls: 'fecho' },
];

function cardKpi(def, k, kly) {
  const a = k[def.k], l = kly[def.k];
  return '<div class="cmp-kpi ' + def.cls + '">' +
    '<span class="cmp-kpi-label">' + def.rot + '</span>' +
    '<span class="cmp-kpi-val">' + def.fmt(a) + '</span>' +
    '<span class="cmp-kpi-foot">' +
      '<span class="cmp-kpi-ly">LY <b>' + def.fmt(l) + '</b></span>' +
      chipDelta(a, l, def.bom) +
    '</span></div>';
}

function renderKpis(k, kly) {
  document.getElementById('kpi-volume').innerHTML     = KPIS_VOLUME.map(d => cardKpi(d, k, kly)).join('');
  document.getElementById('kpi-eficiencia').innerHTML = KPIS_EFIC.map(d => cardKpi(d, k, kly)).join('');
}

// ══════════════════════════════════════════════════════════════
//  5. Funil comparativo — duas barras EMPILHADAS por etapa
//
//  Sobrepostas no mesmo trilho, a barra do ano anterior ficava por
//  dentro da do ano atual e as duas se liam como uma barra só com
//  um risco no meio.
// ══════════════════════════════════════════════════════════════
const FUNIL_STAGE_CLASS = {
  'Total de Leads': 'aquisicao', 'Qualificados': 'sinal', 'Oportunidades': 'consulta',
  'Cadeiras Vendidas': 'evento', 'Consultas Vendidas': 'consulta',
  'Consultas Realizadas': 'sinal', 'Combos Vendidos': 'fecho',
};

function renderFunil(funil, funilLy) {
  const el = document.getElementById('cmp-funil');
  if (!funil || !funil.length) {
    el.innerHTML = '<div style="padding:12px 0;color:var(--muted);font-size:12px;text-align:center">Sem dados no período</div>';
    return;
  }

  // Base do eixo: o MAIOR topo entre os dois anos. Topos por ano
  // fariam dois funis visualmente iguais com volumes distintos.
  const base = Math.max(funil[0].valor || 0, (funilLy[0] || {}).valor || 0) || 1;

  el.innerHTML = funil.map((row, i) => {
    const a = row.valor, l = (funilLy[i] || {}).valor || 0;
    return '<div class="cmp-frow ' + (FUNIL_STAGE_CLASS[row.etapa] || '') + '">' +
      '<span class="cmp-frow-label">' + escHtml(row.etapa) + '</span>' +
      '<div class="cmp-track">' +
        '<div class="cmp-rail"><div class="cmp-bar-now" style="width:' + fBar(a, base) + '"></div></div>' +
        '<div class="cmp-rail"><div class="cmp-bar-ly"  style="width:' + fBar(l, base) + '"></div></div>' +
      '</div>' +
      '<span class="cmp-frow-val">' + fNum(a) + '<small>' + fNum(l) + '</small></span>' +
      '<span class="cmp-frow-delta">' + chipDelta(a, l, 'up') + '</span>' +
    '</div>';
  }).join('');
}

// ══════════════════════════════════════════════════════════════
//  6. Tabela — cabeçalho de 2 níveis (métrica > atual/LY/Δ)
// ══════════════════════════════════════════════════════════════
const COLS_TBL = [
  { k: 'investimento',         rot: 'Invest.',     fmt: fBRL,  bom: 'neutral' },
  { k: 'roi',                  rot: 'ROI',         fmt: fROI,  bom: 'up' },
  { k: 'cliques',              rot: 'Cliques',     fmt: fNum,  bom: 'neutral' },
  { k: 'total_leads',          rot: 'Leads',       fmt: fNum,  bom: 'up' },
  { k: 'cpl',                  rot: 'CPL',         fmt: fUSD2, bom: 'down' },
  { k: 'qualificados',         rot: 'Qualif.',     fmt: fNum,  bom: 'up' },
  { k: 'cadeira_vendida',      rot: 'Cadeiras',    fmt: fNum,  bom: 'up' },
  { k: 'consultas_vendidas',   rot: 'Cons. Vend.', fmt: fNum,  bom: 'up' },
  { k: 'consultas_realizadas', rot: 'Cons. Real.', fmt: fNum,  bom: 'up' },
  { k: 'combos_vendidos',      rot: 'Combos',      fmt: fNum,  bom: 'up' },
  { k: 'faturamento_combos',   rot: 'Faturamento', fmt: fBRL,  bom: 'up' },
  { k: 'cac_combos',           rot: 'CAC Combo',   fmt: fUSD2, bom: 'down' },
];

function renderTabela(tabela, meta) {
  document.getElementById('cmp-thead').innerHTML =
    '<tr class="lvl1"><th class="dim" rowspan="2">Classificação</th>' +
      COLS_TBL.map(c => '<th colspan="3">' + c.rot + '</th>').join('') + '</tr>' +
    '<tr class="lvl2">' +
      COLS_TBL.map(() =>
        '<th class="grp-start">' + escHtml(meta.ano_atual) + '</th><th>' + escHtml(meta.ano_ly) + '</th><th>Δ</th>'
      ).join('') + '</tr>';

  document.getElementById('cmp-tbody').innerHTML = tabela.map(ln => {
    const chave = ln.classe.toLowerCase();
    return '<tr' + (chave === 'total' ? ' class="row-total"' : '') + '>' +
      '<td class="dim"><span class="dim-dot ' + chave + '"></span>' + escHtml(ln.classe) + '</td>' +
      COLS_TBL.map(c =>
        '<td class="grp-start">' + c.fmt(ln.atual[c.k]) + '</td>' +
        '<td class="cell-ly">' + c.fmt(ln.ly[c.k]) + '</td>' +
        '<td>' + chipDelta(ln.atual[c.k], ln.ly[c.k], c.bom) + '</td>'
      ).join('') +
    '</tr>';
  }).join('');
}

// ══════════════════════════════════════════════════════════════
//  7. Gráfico de evolução mensal — 2 séries, UM eixo
//
//  SVG à mão: o projeto não carrega biblioteca de gráfico.
// ══════════════════════════════════════════════════════════════
const METRICAS = {
  investimento:       { rot: 'Investimento',       fmt: fBRL,  bom: 'neutral', eixo: kUSD, curto: kUSD },
  total_leads:        { rot: 'Leads',              fmt: fNum,  bom: 'up',      eixo: kNum, curto: kNum },
  qualificados:       { rot: 'Qualificados',       fmt: fNum,  bom: 'up',      eixo: kNum, curto: kNum },
  cadeira_vendida:    { rot: 'Cadeiras Vendidas',  fmt: fNum,  bom: 'up',      eixo: kNum, curto: kNum },
  consultas_vendidas: { rot: 'Consultas Vendidas', fmt: fNum,  bom: 'up',      eixo: kNum, curto: kNum },
  combos_vendidos:    { rot: 'Combos',             fmt: fNum,  bom: 'up',      eixo: kNum, curto: kNum },
  faturamento_combos: { rot: 'Faturamento',        fmt: fBRL,  bom: 'up',      eixo: kUSD, curto: kUSD },
  cpl:                { rot: 'CPL',                fmt: fUSD2, bom: 'down',    eixo: v => '$' + v.toFixed(0), curto: v => '$' + v.toFixed(0) },
};

// Topo do eixo em valor "redondo". Testa 4 e 5 divisões e fica com
// a que MENOS sobra acima do maior valor — só com [1,2,2.5,5,10] e
// 4 ticks, um máximo de $123k virava topo $200k e as linhas ficavam
// espremidas na metade de baixo.
const PASSOS = [1, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10];

function escalaEixo(max) {
  if (max <= 0) return { passo: 1, ticks: 4, topo: 4 };
  let melhor = null;
  [4, 5].forEach(ticks => {
    const exp = Math.pow(10, Math.floor(Math.log10(max / ticks)));
    for (const c of PASSOS) {
      const topo = c * exp * ticks;
      if (topo >= max) {
        if (!melhor || topo < melhor.topo) melhor = { passo: c * exp, ticks, topo };
        break;
      }
    }
  });
  return melhor;
}

// CPL é derivado mês a mês (investimento ÷ qualificados), nunca
// interpolado — a média de razões não é a razão das médias.
function serie(lado, metrica) {
  const s = DADOS.mensal[lado];
  if (metrica === 'cpl') {
    return s.investimento.map((inv, i) => div(inv, s.qualificados[i]));
  }
  return s[metrica] || [];
}

// Rótulo colado em cada ponto. O do ano atual vai ACIMA e o do ano
// anterior ABAIXO da respectiva linha; quando as séries se cruzam
// num mês, os dois rótulos ainda saem para lados opostos.
function rotulosSerie(s, outra, x, y, classe, curto, acima) {
  return s.map((v, i) => {
    const cruzou = acima ? v < outra[i] : v > outra[i];
    const dy = (acima ? -1 : 1) * (cruzou ? -11 : 11);
    const anchor = i === 0 ? 'start' : (i === s.length - 1 ? 'end' : 'middle');
    return '<text class="cmp-plabel ' + classe + '" x="' + x(i) + '" y="' + (y(v) + dy) +
           '" text-anchor="' + anchor + '">' + curto(v) + '</text>';
  }).join('');
}

let metricaAtual = 'investimento';

function renderGrafico() {
  if (!DADOS) return;

  const svg  = document.getElementById('cmp-chart');
  const wrap = document.getElementById('chart-wrap');
  const m    = METRICAS[metricaAtual];
  const MES  = DADOS.mensal.meses;
  const sA   = serie('atual', metricaAtual);
  const sL   = serie('ly', metricaAtual);

  if (!MES.length || !sA.length) {
    svg.innerHTML = '';
    return;
  }

  const W = wrap.clientWidth || 900, H = 300;
  // padT/padB folgados: os rótulos saem 11px acima/abaixo do ponto.
  const padL = 52, padR = 54, padT = 26, padB = 34;
  const plotW = W - padL - padR, plotH = H - padT - padB;

  const maxV  = Math.max.apply(null, sA.concat(sL));
  const esc   = escalaEixo(maxV);
  const TICKS = esc.ticks, topo = esc.topo;
  const x = i => padL + (MES.length === 1 ? plotW / 2 : plotW * i / (MES.length - 1));
  const y = v => padT + plotH - (v / topo) * plotH;

  let g = '';
  for (let t = 0; t <= TICKS; t++) {
    const v = esc.passo * t, yy = y(v);
    g += '<line class="cmp-grid-line" x1="' + padL + '" y1="' + yy + '" x2="' + (padL + plotW) + '" y2="' + yy + '"/>' +
         '<text class="cmp-axis-txt" x="' + (padL - 8) + '" y="' + (yy + 3.5) + '" text-anchor="end">' + m.eixo(v) + '</text>';
  }
  MES.forEach((mes, i) => {
    g += '<text class="cmp-axis-txt" x="' + x(i) + '" y="' + (H - 8) + '" text-anchor="middle">' + escHtml(mes) + '</text>';
  });

  const caminho = s => s.map((v, i) => (i ? 'L' : 'M') + x(i) + ' ' + y(v)).join(' ');
  // LY primeiro: a série de referência fica ATRÁS da atual.
  const linhas = '<path class="cmp-line ly"  d="' + caminho(sL) + '"/>' +
                 '<path class="cmp-line now" d="' + caminho(sA) + '"/>';

  let pontos = '';
  sL.forEach((v, i) => { pontos += '<circle class="cmp-dot ly"  cx="' + x(i) + '" cy="' + y(v) + '" r="4"/>'; });
  sA.forEach((v, i) => { pontos += '<circle class="cmp-dot now" cx="' + x(i) + '" cy="' + y(v) + '" r="4"/>'; });

  const rotulos = rotulosSerie(sA, sL, x, y, 'now', m.curto, true) +
                  rotulosSerie(sL, sA, x, y, 'ly',  m.curto, false);

  // Rótulo direto na ponta: com 2 séries a identidade não depende
  // só da legenda (nem só da cor).
  const iF  = MES.length - 1;
  const rot = '<text class="cmp-endlabel now" x="' + (x(iF) + 8) + '" y="' + (y(sA[iF]) + 3.5) + '">' + escHtml(DADOS.meta.ano_atual) + '</text>' +
              '<text class="cmp-endlabel ly"  x="' + (x(iF) + 8) + '" y="' + (y(sL[iF]) + 3.5) + '">' + escHtml(DADOS.meta.ano_ly) + '</text>';

  svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
  svg.innerHTML = g + linhas + pontos + rotulos + rot +
    '<line class="cmp-crosshair" id="cross" x1="0" y1="' + padT + '" x2="0" y2="' + (padT + plotH) + '" style="opacity:0"/>' +
    '<rect id="hit" x="' + padL + '" y="' + padT + '" width="' + plotW + '" height="' + plotH + '" fill="transparent"/>';

  document.getElementById('chart-desc').textContent =
    m.rot + ' por mês, ' + DADOS.meta.ano_atual + ' comparado a ' + DADOS.meta.ano_ly +
    '. Dados completos na tabela abaixo do gráfico.';

  // ── Camada de hover: crosshair + tooltip ──────────────────
  const hit   = document.getElementById('hit');
  const cross = document.getElementById('cross');
  const tip   = document.getElementById('cmp-tip');

  hit.addEventListener('mousemove', ev => {
    const r  = svg.getBoundingClientRect();
    const px = (ev.clientX - r.left) * (W / r.width);
    let i = MES.length === 1 ? 0 : Math.round((px - padL) / (plotW / (MES.length - 1)));
    i = Math.max(0, Math.min(MES.length - 1, i));

    cross.setAttribute('x1', x(i));
    cross.setAttribute('x2', x(i));
    cross.style.opacity = 1;

    const a = sA[i], l = sL[i];
    tip.innerHTML =
      '<div class="cmp-tip-title">' + escHtml(MES[i]) + ' · ' + m.rot + '</div>' +
      '<div class="cmp-tip-row"><span class="cmp-tip-key"><span class="cmp-tip-dot now"></span>' + escHtml(DADOS.meta.ano_atual) + '</span><span class="cmp-tip-val">' + m.fmt(a) + '</span></div>' +
      '<div class="cmp-tip-row"><span class="cmp-tip-key"><span class="cmp-tip-dot ly"></span>' + escHtml(DADOS.meta.ano_ly) + '</span><span class="cmp-tip-val">' + m.fmt(l) + '</span></div>' +
      '<div class="cmp-tip-row cmp-tip-delta"><span class="cmp-tip-key">Variação</span>' + chipDelta(a, l, m.bom) + '</div>';
    tip.classList.add('on');

    const pxCss = x(i) * (r.width / W);
    tip.style.left = Math.min(Math.max(pxCss + 14, 0), r.width - tip.offsetWidth - 4) + 'px';
    tip.style.top  = '8px';
  });
  hit.addEventListener('mouseleave', () => {
    cross.style.opacity = 0;
    tip.classList.remove('on');
  });

  // ── Tabela-alternativa: a leitura não pode existir só em pixels ──
  document.getElementById('chart-thead').innerHTML =
    '<tr class="lvl2"><th class="dim">Mês</th>' +
    MES.map(mes => '<th class="grp-start">' + escHtml(mes) + '</th>').join('') + '</tr>';
  document.getElementById('chart-tbody').innerHTML =
    '<tr><td class="dim">' + escHtml(DADOS.meta.ano_atual) + '</td>' + sA.map(v => '<td class="grp-start">' + m.fmt(v) + '</td>').join('') + '</tr>' +
    '<tr><td class="dim">' + escHtml(DADOS.meta.ano_ly) + '</td>'    + sL.map(v => '<td class="grp-start cell-ly">' + m.fmt(v) + '</td>').join('') + '</tr>';
}

function renderLegenda(meta) {
  document.getElementById('cmp-legend').innerHTML =
    '<span class="cmp-legend-item"><span class="cmp-legend-swatch now"></span>' + escHtml(meta.ano_atual) + '</span>' +
    '<span class="cmp-legend-item"><span class="cmp-legend-swatch ly"></span>'  + escHtml(meta.ano_ly) + ' (mesmo período)</span>';
}

// ══════════════════════════════════════════════════════════════
//  8. Filtros
// ══════════════════════════════════════════════════════════════
const yearDropdown  = createMultiDropdown('year-wrap', 'year-btn', 'year-list', YEARS, l => l, l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MESES_UI, l => l, (l, i) => i + 1);
const campDropdown  = createSearchDropdown('camp-wrap', 'camp-input', 'camp-dropdown', 'camp-clear', 'camp-tags', 'Nenhuma campanha encontrada');
const formDropdown  = createSearchDropdown('form-wrap', 'form-input', 'form-dropdown', 'form-clear', 'form-tags', 'Nenhum form encontrado');
const conjDropdown  = createSearchDropdown('conj-wrap', 'conj-input', 'conj-dropdown', 'conj-clear', 'conj-tags', 'Nenhum conjunto encontrado');
const anDropdown    = createSearchDropdown('an-wrap', 'an-input', 'an-dropdown', 'an-clear', 'an-tags', 'Nenhum anúncio encontrado');

// Ano corrente pré-selecionado: sem período o backend assume o ano
// corrente de qualquer forma, e assim o filtro mostra o que está valendo.
yearDropdown.select([String(new Date().getFullYear())]);

// "Últimos N [dias|semanas|meses]" — um substitui o outro com Ano/Mês/Data
// (ver createRelativePeriod em js/bi-dashboard.js). O ano anterior é a mesma
// janela deslocada 12 meses, como qualquer intervalo de datas.
const relPeriod = createRelativePeriod({
  numId: 'rel-n', unitId: 'rel-unit',
  conflicts: [[yearDropdown, 'year-list'], [monthDropdown, 'month-list']],
});

async function carregarListas() {
  const pares = [[CAMPANHAS_URL, campDropdown], [FORMS_URL, formDropdown], [CONJUNTOS_URL, conjDropdown], [ANUNCIOS_URL, anDropdown]];
  await Promise.all(pares.map(async ([url, dd]) => {
    try {
      const res = await fetch(url);
      if (res.ok) dd.load(await res.json());
    } catch (e) { /* dropdown fica vazio; o resto do dashboard segue */ }
  }));
}

function montaParams() {
  const rel = relPeriod.getRange();
  return {
    year:        yearDropdown.getValue(),
    months:      monthDropdown.getValue(),
    campanha:    campDropdown.getValue(),
    form:        formDropdown.getValue(),
    conjunto:    conjDropdown.getValue(),
    anuncio:     anDropdown.getValue(),
    data_inicio: rel ? rel.start : getDateInput('data-inicio'),
    data_fim:    rel ? rel.end   : getDateInput('data-fim'),
  };
}

['data-inicio', 'data-fim'].forEach(id => {
  document.getElementById(id).addEventListener('change', e => {
    document.getElementById(id + '-clear').classList.toggle('visible', !!e.target.value);
  });
  document.getElementById(id + '-clear').addEventListener('click', () => clearDateInput(id, id + '-clear'));
});

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
    const labels = nums.map(n => MESES_UI[n - 1]);
    parts.push(labels.join(', '));
  }
  badge.textContent = parts.join(' · ').toUpperCase();
}

document.getElementById('btn-apply').addEventListener('click', () => { updateBadge(); loadDashboard(montaParams()); });
document.getElementById('btn-clear').addEventListener('click', () => {
  relPeriod.reset();
  yearDropdown.reset(); monthDropdown.reset();
  campDropdown.reset(); formDropdown.reset(); conjDropdown.reset(); anDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim', 'data-fim-clear');
  yearDropdown.select([String(new Date().getFullYear())]);
  updateBadge();
  loadDashboard(montaParams());
});

document.getElementById('metric-sel').addEventListener('change', e => {
  metricaAtual = e.target.value;
  renderGrafico();   // troca de métrica é só redesenho: não refaz fetch
});
window.addEventListener('resize', renderGrafico);

// ══════════════════════════════════════════════════════════════
//  9. Carregar
// ══════════════════════════════════════════════════════════════
async function loadDashboard(f) {
  const overlay = document.getElementById('loading-overlay');
  const erro    = document.getElementById('error-banner');

  overlay.classList.remove('hidden');
  document.body.classList.add('loading');
  erro.textContent = '';
  erro.classList.remove('visible');

  const params = new URLSearchParams();
  Object.entries(f).forEach(([k, v]) => { if (v) params.set(k, v); });

  try {
    const res  = await fetch(API + '?' + params);
    const data = await res.json();
    if (!res.ok || data.error) throw new Error(data.error || ('HTTP ' + res.status));

    DADOS = data;
    renderPeriodo(data.meta);
    renderKpis(data.kpis, data.kpis_ly);
    renderTabela(data.tabela, data.meta);
    renderFunil(data.funil, data.funil_ly);
    renderLegenda(data.meta);
    renderGrafico();
  } catch (e) {
    erro.textContent = 'Erro ao carregar os dados: ' + e.message;
    erro.classList.add('visible');
  } finally {
    overlay.classList.add('hidden');
    document.body.classList.remove('loading');
  }
}

carregarListas();
updateBadge();
loadDashboard(montaParams());
</script>
