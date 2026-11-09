<!-- ══════════════════════════════════════════════════════════════
     Comercial — Time Comercial 2: 9 vendedores, pipe C5 (combo
     pós-consulta). Evento e Digital NÃO são sub-times como em
     Comercial 1 — são um corte do MESMO vendedor pela mesma regra de
     canal da Visão Geral (tipoLead IN ('OTR','OTW')). Por isso é 1
     time, 1 tabela com cabeçalho de 2 níveis (Evento | Digital |
     Total × 3 submétricas cada) em vez de 2 tabelas paralelas.

     Sem pódio fixo: a posição na tabela é sempre a da ordenação
     atual (fiel ao HTML de origem, Html_Comercial2) — diferente de
     Cadeiras/Comercial1, aqui não há uma métrica única óbvia pra
     ancorar um pódio entre as 9 colunas.

     Todo o CSS vive no bloco .dash-comercial-2 de css/bi-theme.css
     (seção 20g) — nada de <style> inline aqui.
     ══════════════════════════════════════════════════════════════ -->

<div class="dash-comercial-2">

<header class="header">
  <div class="header-left">
    <div class="logo">CO</div>
    <div>
      <div class="header-title">Time Comercial 2 — Painel de Performance</div>
      <div class="header-sub">Consultas Realizadas · Combos Vendidos · Evento × Digital</div>
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
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Visão Geral</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial/yoy<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Comparativo</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial/cadeiras<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Cadeiras</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial/comercial1<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Com. 1</a>
      <a class="tab-switch-btn active" href="<?= $this->site ?>comercial/comercial2<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Com. 2</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial/comercial3<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Com. 3</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
    </div>
  </div>
</header>

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
    <span class="filter-label">Vendedor</span>
    <div class="multi-dd-wrap" id="vend-wrap">
      <button class="multi-dd-btn" id="vend-btn" type="button">Todos <span class="dd-caret">&#9662;</span></button>
      <div class="multi-dd-list" id="vend-list"></div>
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
        <input id="form-input" class="camp-input" type="text" placeholder="Pesquisar form..." autocomplete="off" aria-label="Pesquisar form preenchido">
        <button class="camp-clear" id="form-clear" aria-label="Limpar form" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-dropdown" id="form-dropdown" role="listbox"></div>
    </div>
  </div>

  <button class="btn-apply" id="btn-apply">Aplicar</button>
  <button class="btn-clear" id="btn-clear">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'comercial-comercial2' });</script>

<div id="loading-overlay" class="hidden"></div>
<div id="error-banner" role="alert"></div>

<main class="main" aria-label="Performance do time Comercial 2">

  <article class="bi-card">
    <div class="bi-card-header">
      <span class="bi-card-title">Ranking de Vendedores</span>
      <span class="bi-card-sub">clique numa coluna para ordenar — a posição reflete a ordenação atual</span>
    </div>
    <div class="bi-card-body">
      <div class="c2-tbl-scroll">
        <table class="c2-tbl" id="c2-tbl">
          <thead id="c2-thead"></thead>
          <tbody id="c2-tbody"></tbody>
        </table>
      </div>
    </div>
  </article>

  <div class="c2-grid2">
    <article class="bi-card">
      <div class="bi-card-header">
        <span class="bi-card-title" style="color:var(--ev)">Performance Evento por Vendedor</span>
        <div class="c2-legend">
          <span class="c2-legend-item"><i class="c2-sw cr"></i>Cons. Realizadas</span>
          <span class="c2-legend-item"><i class="c2-sw cb"></i>Combos</span>
          <span class="c2-legend-item"><i class="c2-sw conv"></i>Conversão %</span>
        </div>
      </div>
      <div class="bi-card-body">
        <div class="c2-chart-wrap" id="wrap-perf-ev">
          <svg id="svg-perf-ev" role="img" aria-labelledby="desc-perf-ev"></svg>
          <span id="desc-perf-ev" class="sr-only"></span>
        </div>
      </div>
    </article>

    <article class="bi-card">
      <div class="bi-card-header">
        <span class="bi-card-title" style="color:var(--dg)">Performance Digital por Vendedor</span>
        <div class="c2-legend">
          <span class="c2-legend-item"><i class="c2-sw cr dim"></i>Cons. Realizadas</span>
          <span class="c2-legend-item"><i class="c2-sw cb dim"></i>Combos</span>
          <span class="c2-legend-item"><i class="c2-sw conv"></i>Conversão %</span>
        </div>
      </div>
      <div class="bi-card-body">
        <div class="c2-chart-wrap" id="wrap-perf-dg">
          <svg id="svg-perf-dg" role="img" aria-labelledby="desc-perf-dg"></svg>
          <span id="desc-perf-dg" class="sr-only"></span>
        </div>
      </div>
    </article>
  </div>

  <div class="c2-grid2">
    <article class="bi-card">
      <div class="bi-card-header">
        <span class="bi-card-title" id="chart-title-cr">Consultas Realizadas ao Longo do Tempo</span>
        <div class="gran-toggle">
          <button class="gran-btn active" data-gran="mes"    onclick="setGranularidade('mes')">Mês</button>
          <button class="gran-btn"        data-gran="semana" onclick="setGranularidade('semana')">Semana</button>
          <button class="gran-btn"        data-gran="dia"    onclick="setGranularidade('dia')">Dia</button>
        </div>
        <div class="c2-legend">
          <span class="c2-legend-item"><i class="c2-sw cr"></i>Evento</span>
          <span class="c2-legend-item"><i class="c2-sw cr dim"></i>Digital</span>
        </div>
      </div>
      <div class="bi-card-body">
        <div class="c2-chart-wrap" id="wrap-tempo-cr">
          <svg id="svg-tempo-cr" role="img" aria-labelledby="desc-tempo-cr"></svg>
          <span id="desc-tempo-cr" class="sr-only"></span>
          <div class="c2-nota-parcial" id="nota-tempo-cr" hidden></div>
        </div>
      </div>
    </article>

    <article class="bi-card">
      <div class="bi-card-header">
        <span class="bi-card-title" id="chart-title-cb">Combos Vendidos ao Longo do Tempo</span>
        <div class="gran-toggle">
          <button class="gran-btn active" data-gran="mes"    onclick="setGranularidade('mes')">Mês</button>
          <button class="gran-btn"        data-gran="semana" onclick="setGranularidade('semana')">Semana</button>
          <button class="gran-btn"        data-gran="dia"    onclick="setGranularidade('dia')">Dia</button>
        </div>
        <div class="c2-legend">
          <span class="c2-legend-item"><i class="c2-sw cb"></i>Evento</span>
          <span class="c2-legend-item"><i class="c2-sw cb dim"></i>Digital</span>
        </div>
      </div>
      <div class="bi-card-body">
        <div class="c2-chart-wrap" id="wrap-tempo-cb">
          <svg id="svg-tempo-cb" role="img" aria-labelledby="desc-tempo-cb"></svg>
          <span id="desc-tempo-cb" class="sr-only"></span>
          <div class="c2-nota-parcial" id="nota-tempo-cb" hidden></div>
        </div>
      </div>
    </article>
  </div>

</main>
</div>

<div class="bi-tooltip" id="c2-tooltip"></div>

<script>
// ══════════════════════════════════════════════════════════════
//  Comercial — Time Comercial 2 · JS da view
//
//  Consome comercial/comercial2Dados e comercial/forms.
//
//  createMultiDropdown, createSearchDropdown, getDateInput,
//  clearDateInput e fNum vêm de js/bi-dashboard.js, carregado no
//  <head> pelo template e COMPARTILHADO por 18 views — não alterar
//  aquele arquivo por causa desta. applyTheme/toggleTheme/relógio do
//  rodapé vêm de template/comercial.php, compartilhados por todas as
//  abas — não redeclarar aqui.
//
//  Layout e interação validados antes em comercial-2-mock.html.
// ══════════════════════════════════════════════════════════════

const API       = '<?= $this->site ?>comercial/comercial2Dados';
const FORMS_URL = '<?= $this->site ?>comercial/forms';

let DADOS = null;
let granularidadeAtual = 'mes';

// ── 1. Helpers ────────────────────────────────────────────────
function esc(s) {
  return String(s === null || s === undefined ? '' : s)
    .replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
}
function kNum(v) { return v >= 10000 ? (v / 1000).toFixed(1).replace('.', ',') + 'k' : fNum(Math.round(v)); }
function fConv(v) { v = v || 0; if (v > 999) { return '999%+'; } return v.toFixed(1).replace('.', ',') + '%'; }
function primeiroNome(n) { return String(n || '').trim().split(/\s+/)[0] || '—'; }

// ── 1b. Tooltip genérico (reaproveita .bi-tooltip do bi-theme.css,
// mesmo componente já usado pelo KPI do Executive) — usado pelos 4
// gráficos SVG desta view via colunas de hover invisíveis.
function mostrarTooltip(titulo, linhas, evt) {
  var tip = document.getElementById('c2-tooltip');
  tip.innerHTML = '<div class="bi-tooltip-inner"><div class="bi-tooltip-title">' + esc(titulo) + '</div>' +
    linhas.map(function (l) {
      return '<div class="bi-tooltip-row"><span class="bi-tooltip-label">' + esc(l[0]) + '</span>' +
        '<span class="bi-tooltip-val">' + esc(l[1]) + '</span></div>';
    }).join('') + '</div>';
  tip.classList.add('visible');
  posicionarTooltip(evt);
}
function posicionarTooltip(evt) {
  var tip = document.getElementById('c2-tooltip');
  var pad = 14;
  var x = evt.clientX + pad, y = evt.clientY + pad;
  var r = tip.getBoundingClientRect();
  if (x + r.width + 8 > window.innerWidth)  { x = evt.clientX - r.width - pad; }
  if (y + r.height + 8 > window.innerHeight) { y = evt.clientY - r.height - pad; }
  tip.style.left = Math.max(8, x) + 'px';
  tip.style.top  = Math.max(8, y) + 'px';
}
function esconderTooltip() {
  document.getElementById('c2-tooltip').classList.remove('visible');
}

// ── 2. Tabela: cabeçalho de 2 níveis, 9 colunas ordenáveis ─────
//
// A posição é sempre a da ordenação ATUAL (fiel ao HTML de origem) —
// diferente de Cadeiras/Comercial1, onde o pódio é fixo por uma
// métrica. Com 9 colunas em 3 grupos, não há uma métrica única óbvia
// pra ancorar um pódio; a fonte também não separa as duas coisas.
const COLS = [
  { k: 'cr_ev',    rot: 'Cons.',   grp: 'ev'  },
  { k: 'cb_ev',    rot: 'Combos',  grp: 'ev'  },
  { k: 'conv_ev',  rot: 'Conv%',   grp: 'ev'  },
  { k: 'cr_dg',    rot: 'Cons.',   grp: 'dg'  },
  { k: 'cb_dg',    rot: 'Combos',  grp: 'dg'  },
  { k: 'conv_dg',  rot: 'Conv%',   grp: 'dg'  },
  { k: 'total_cr', rot: 'Cons.',   grp: 'tot' },
  { k: 'total_cb', rot: 'Combos',  grp: 'tot' },
  { k: 'conv_t',   rot: 'Conv%',   grp: 'tot' },
];
const ROTULO_GRP = { ev: 'Evento', dg: 'Digital', tot: 'Total' };

let sortField = 'conv_t', sortDesc = true;   // default do HTML de origem

function sortBy(f) {
  if (sortField === f) { sortDesc = !sortDesc; } else { sortField = f; sortDesc = true; }
  renderRanking();
}

function renderThead() {
  var thead = document.getElementById('c2-thead');
  var lvl1 = '<tr class="lvl1"><th class="dim" rowspan="2">Vendedor</th>' +
    ['ev', 'dg', 'tot'].map(function (g) {
      return '<th colspan="3" class="grp-' + g + ' grp-start">' + ROTULO_GRP[g] + '</th>';
    }).join('') + '</tr>';
  var lvl2 = '<tr class="lvl2">' + COLS.map(function (c, i) {
    var primeiroDoGrupo = i % 3 === 0;
    var ativo = sortField === c.k;
    var arr = ativo ? (sortDesc ? '▼' : '▲') : '↕';
    return '<th class="' + (primeiroDoGrupo ? 'grp-start ' : '') + (ativo ? 'active' : '') + '"' +
      ' tabindex="0" role="button" aria-label="Ordenar por ' + esc(ROTULO_GRP[c.grp]) + ' ' + esc(c.rot) + '"' +
      ' onclick="sortBy(\'' + c.k + '\')"' +
      ' onkeydown="if(event.key===\'Enter\'||event.key===\' \'){event.preventDefault();sortBy(\'' + c.k + '\');}">' +
      esc(c.rot) + '<span class="arr">' + arr + '</span></th>';
  }).join('') + '</tr>';
  thead.innerHTML = lvl1 + lvl2;
}

function renderRanking() {
  renderThead();
  var tbody = document.getElementById('c2-tbody');
  var R = (DADOS.ranking || []).slice();

  if (!R.length) {
    tbody.innerHTML = '<tr><td colspan="10" class="c2-vazio">Sem vendedores no período</td></tr>';
    return;
  }

  var s = R.sort(function (a, b) {
    var va = a[sortField] || 0, vb = b[sortField] || 0;
    return sortDesc ? vb - va : va - vb;
  });

  var rows = s.map(function (d, i) {
    var r = i + 1;
    var rc = r === 1 ? 'r1' : r === 2 ? 'r2' : r === 3 ? 'r3' : 'rn';
    var cells = COLS.map(function (c, ci) {
      var primeiroDoGrupo = ci % 3 === 0;
      var conteudo = c.k.indexOf('conv') === 0 || c.k === 'conv_t'
        ? '<span class="c2-conv">' + fConv(d[c.k]) + '</span>'
        : fNum(d[c.k]);
      return '<td class="' + (primeiroDoGrupo ? 'grp-start' : '') + '">' + conteudo + '</td>';
    }).join('');
    return '<tr>' +
      '<td class="dim"><div class="c2-name"><span class="c2-rank ' + rc + '">' + r + '</span>' +
      '<span class="c2-avatar">' + esc(primeiroNome(d.nome)[0] || '?') + '</span>' +
      '<span>' + esc(d.nome) + '</span></div></td>' +
      cells + '</tr>';
  }).join('');

  var tot = { cr_ev: 0, cr_dg: 0, cb_ev: 0, cb_dg: 0 };
  R.forEach(function (d) { tot.cr_ev += d.cr_ev; tot.cr_dg += d.cr_dg; tot.cb_ev += d.cb_ev; tot.cb_dg += d.cb_dg; });
  var totConvEv = tot.cr_ev > 0 ? tot.cb_ev / tot.cr_ev * 100 : 0;
  var totConvDg = tot.cr_dg > 0 ? tot.cb_dg / tot.cr_dg * 100 : 0;
  var totCr = tot.cr_ev + tot.cr_dg, totCb = tot.cb_ev + tot.cb_dg;
  var totConvT = totCr > 0 ? totCb / totCr * 100 : 0;
  rows += '<tr class="total"><td class="dim">TOTAL</td>' +
    '<td class="grp-start">' + fNum(tot.cr_ev) + '</td><td>' + fNum(tot.cb_ev) + '</td><td><span class="c2-conv">' + fConv(totConvEv) + '</span></td>' +
    '<td class="grp-start">' + fNum(tot.cr_dg) + '</td><td>' + fNum(tot.cb_dg) + '</td><td><span class="c2-conv">' + fConv(totConvDg) + '</span></td>' +
    '<td class="grp-start">' + fNum(totCr) + '</td><td>' + fNum(totCb) + '</td><td><span class="c2-conv">' + fConv(totConvT) + '</span></td></tr>';

  tbody.innerHTML = rows;
}
</script>

<script>
// ── 3. Escala de eixo ─────────────────────────────────────────
const PASSOS = [1, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10];

function escalaEixo(max) {
  if (max <= 0) { return { passo: 1, ticks: 4, topo: 4 }; }
  var melhor = null;
  [4, 5].forEach(function (ticks) {
    var exp = Math.pow(10, Math.floor(Math.log10(max / ticks)));
    for (var i = 0; i < PASSOS.length; i++) {
      var topo = PASSOS[i] * exp * ticks;
      if (topo >= max) {
        if (!melhor || topo < melhor.topo) { melhor = { passo: PASSOS[i] * exp, ticks: ticks, topo: topo }; }
        break;
      }
    }
  });
  return melhor || { passo: 1, ticks: 4, topo: 4 };
}

// ── 4. Performance por canal: 2 barras + linha de conversão ────
//
// cfg.dim diz se este card é o "Digital" (barras com opacidade
// reduzida) — MESMA cor de cr/cb nos dois cards, só a intensidade
// muda. Fiel ao HTML de origem: o eixo visual é a métrica, o canal é
// intensidade — diferente de Comercial1, onde Evento/Digital tinham
// cores próprias (lá eram sub-times; aqui é um corte do mesmo
// vendedor).
function renderPerf(cfg) {
  var R   = DADOS.ranking || [];
  var svg = document.getElementById(cfg.svgId);
  var n   = R.length;

  if (!n) {
    svg.removeAttribute('viewBox');
    svg.style.height = '80px';
    svg.innerHTML = '<text x="50%" y="50%" text-anchor="middle" class="c2-axis-txt">Sem dados no período</text>';
    document.getElementById(cfg.descId).textContent = 'Sem dados no período selecionado.';
    return;
  }

  var W = document.getElementById(cfg.wrapId).clientWidth || 560;
  var H = 260, padL = 46, padR = 50, padT = 24, padB = 42;
  var plotW = Math.max(80, W - padL - padR), plotH = H - padT - padB;
  var base  = padT + plotH;

  var bw   = plotW / n;
  var xc   = function (i) { return padL + bw * (i + 0.5); };
  var barW = Math.max(4, Math.min(18, bw * 0.22));
  var gap  = Math.max(2, Math.round(barW * 0.2));
  var grupo = barW * 2 + gap;

  var maxBar = 0, maxConv = 0;
  R.forEach(function (d) {
    maxBar  = Math.max(maxBar, d[cfg.colCr] || 0, d[cfg.colCb] || 0);
    maxConv = Math.max(maxConv, Math.min(999, d[cfg.colConv] || 0));
  });
  var escB = escalaEixo(maxBar);
  var escC = escalaEixo(maxConv);
  var yB = function (v) { return base - ((v || 0) / escB.topo) * plotH; };
  var yC = function (v) { return base - (Math.min(999, v || 0) / escC.topo) * plotH; };

  var s = '';
  var ticks = Math.max(escB.ticks, escC.ticks);
  var casasConv = escC.topo < 10 ? 1 : 0;
  for (var t = 0; t <= ticks; t++) {
    var f = t / ticks, gy = base - f * plotH;
    s += '<line class="c2-grid-line" x1="' + padL + '" y1="' + gy + '" x2="' + (padL + plotW) + '" y2="' + gy + '"/>';
    s += '<text class="c2-axis-txt" x="' + (padL - 8) + '" y="' + (gy + 3.5) + '" text-anchor="end">' + kNum(escB.topo * f) + '</text>';
    s += '<text class="c2-axis-txt right" x="' + (padL + plotW + 8) + '" y="' + (gy + 3.5) + '" text-anchor="start">' + (escC.topo * f).toFixed(casasConv).replace('.', ',') + '%</text>';
  }

  var pts = [];
  var rotulosBarra = [];
  var dimCls = cfg.dim ? ' dim' : '';
  R.forEach(function (d, i) {
    var cx = xc(i), x0 = cx - grupo / 2;
    [[cfg.colCr, 'c2-bar-cr' + dimCls, x0, 'cr'], [cfg.colCb, 'c2-bar-cb' + dimCls, x0 + barW + gap, 'cb']].forEach(function (par) {
      var v = d[par[0]] || 0;
      if (v <= 0) { return; }
      var yt = yB(v), h = Math.max(1, base - yt);
      s += '<rect class="' + par[1] + '" x="' + par[2] + '" y="' + (base - h) + '" width="' + barW + '" height="' + h + '" rx="2"/>';
      if (barW >= 14) {
        var lx = par[2] + barW / 2, ly = base - h - 5;
        rotulosBarra.push({ x: lx, y: ly });
        s += '<text class="c2-plabel ' + par[3] + '" x="' + lx + '" y="' + ly + '" text-anchor="middle">' + kNum(v) + '</text>';
      }
    });
    pts.push([cx, yC(d[cfg.colConv] || 0), d[cfg.colConv] || 0]);
    s += '<text class="c2-axis-nome" x="' + cx + '" y="' + (base + 20) + '" text-anchor="middle">' + esc(primeiroNome(d.nome)) + '</text>';
  });

  // Colunas de hover invisíveis (1 por vendedor, largura = fatia
  // inteira do eixo X) — tooltip mostra as 3 métricas juntas, não só
  // a barra/ponto exato sob o cursor.
  R.forEach(function (d, i) {
    s += '<rect class="c2-hover-col" data-i="' + i + '" x="' + (padL + bw * i) + '" y="' + padT + '" width="' + bw + '" height="' + plotH + '"/>';
  });

  if (pts.length > 1) {
    s += '<path class="c2-line-conv" d="' + pts.map(function (p, i) { return (i ? 'L' : 'M') + p[0] + ',' + p[1]; }).join('') + '"/>';
  }
  pts.forEach(function (p) {
    s += '<circle class="c2-dot-conv" cx="' + p[0] + '" cy="' + p[1] + '" r="4"/>';
    var acima  = p[1] - 10;
    var colide = rotulosBarra.some(function (r) { return Math.abs(r.x - p[0]) < 30 && Math.abs(r.y - acima) < 10; });
    var ly = colide ? p[1] + 15 : acima;
    s += '<text class="c2-plabel conv" x="' + p[0] + '" y="' + ly + '" text-anchor="middle">' + fConv(p[2]) + '</text>';
  });

  svg.style.height = H + 'px';
  svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
  svg.innerHTML = s;

  Array.prototype.forEach.call(svg.querySelectorAll('.c2-hover-col'), function (el) {
    var d = R[+el.getAttribute('data-i')];
    el.addEventListener('mousemove', function (evt) {
      mostrarTooltip(d.nome, [
        ['Cons. Realizadas', fNum(d[cfg.colCr])],
        ['Combos', fNum(d[cfg.colCb])],
        ['Conversão %', fConv(d[cfg.colConv])],
      ], evt);
    });
    el.addEventListener('mouseleave', esconderTooltip);
  });

  document.getElementById(cfg.descId).textContent =
    'Por vendedor: ' + R.map(function (d) {
      return d.nome + ' com ' + fNum(d[cfg.colCr]) + ' consultas realizadas, ' + fNum(d[cfg.colCb]) + ' combos e ' + fConv(d[cfg.colConv]) + ' de conversão';
    }).join('; ') + '. Os mesmos números estão na tabela de ranking acima.';
}
</script>

<script>
function isoYearWeek(d) {
  var date = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
  var dia = (date.getUTCDay() + 6) % 7;
  date.setUTCDate(date.getUTCDate() - dia + 3);
  var isoYear = date.getUTCFullYear();
  var qui1 = new Date(Date.UTC(isoYear, 0, 4));
  qui1.setUTCDate(qui1.getUTCDate() - ((qui1.getUTCDay() + 6) % 7) + 3);
  var semana = 1 + Math.round((date - qui1) / (7 * 86400000));
  return isoYear * 100 + semana;
}

function ultimoPeriodoParcial(T, granularidade) {
  if (!T.length) { return false; }
  var ym = T[T.length - 1].ym;
  if (!ym) { return false; }
  var h = new Date();
  if (granularidade === 'dia') {
    var hoje = h.getFullYear() + '-' + String(h.getMonth() + 1).padStart(2, '0') + '-' + String(h.getDate()).padStart(2, '0');
    return String(ym) === hoje;
  }
  if (granularidade === 'semana') {
    return Number(ym) === isoYearWeek(h);
  }
  var atual = h.getFullYear() + '-' + String(h.getMonth() + 1).padStart(2, '0');
  if (ym !== atual) { return false; }
  return h.getDate() < new Date(h.getFullYear(), h.getMonth() + 1, 0).getDate();
}

// ── 5. Ao longo do tempo: linha dupla, EIXO ÚNICO ──────────────
//
// cr_ev/cr_dg (ou cb_ev/cb_dg) são a MESMA métrica cortada por canal,
// ordem de grandeza comparável (confirmado: 1.005 x 542 no agregado)
// — ao contrário de Comercial1 (cad x leads_dg, 19x de diferença),
// eixo duplo aqui seria complexidade sem necessidade.
function renderTempo(cfg) {
  var T   = DADOS.tempo || [];
  var svg = document.getElementById(cfg.svgId);
  var n   = T.length;

  if (!n) {
    svg.removeAttribute('viewBox');
    svg.style.height = '80px';
    svg.innerHTML = '<text x="50%" y="50%" text-anchor="middle" class="c2-axis-txt">Sem dados no período</text>';
    document.getElementById(cfg.descId).textContent = 'Sem dados no período selecionado.';
    var n0 = document.getElementById(cfg.notaId); if (n0) { n0.hidden = true; }
    return;
  }

  var W = document.getElementById(cfg.wrapId).clientWidth || 520;
  var H = 260, padL = 44, padR = 16, padT = 24;

  var plotW0 = Math.max(60, W - padL - padR);
  var bw0 = plotW0 / n;
  var maxChars = T.reduce(function (m, d) { return Math.max(m, String(d.mes).length); }, 0);
  var largRot = maxChars * 6.2 + 6;
  var girar = bw0 < largRot;
  var passo = Math.max(1, Math.ceil((girar ? 13 : largRot) / bw0));
  var padB = girar ? Math.min(78, Math.round(largRot * 0.64) + 16) : 30;
  var plotH = H - padT - padB, plotW = plotW0, base = padT + plotH;

  var parcial = ultimoPeriodoParcial(T, granularidadeAtual);

  var maxV = T.reduce(function (m, d) { return Math.max(m, d[cfg.colEv] || 0, d[cfg.colDg] || 0); }, 0);
  var esc2 = escalaEixo(maxV);
  var y = function (v) { return base - ((v || 0) / esc2.topo) * plotH; };
  var x = function (i) { return n === 1 ? padL + plotW / 2 : padL + (plotW / (n - 1)) * i; };

  var s = '';
  for (var t = 0; t <= esc2.ticks; t++) {
    var f = t / esc2.ticks, gy = base - f * plotH;
    s += '<line class="c2-grid-line" x1="' + padL + '" y1="' + gy + '" x2="' + (padL + plotW) + '" y2="' + gy + '"/>';
    s += '<text class="c2-axis-txt" x="' + (padL - 7) + '" y="' + (gy + 3.5) + '" text-anchor="end">' + kNum(esc2.topo * f) + '</text>';
  }

  // Evento: linha cheia, com rótulo de valor.
  if (n > 1) {
    var dEv = T.map(function (d, i) { return (i ? 'L' : 'M') + x(i) + ',' + y(d[cfg.colEv]); }).join('');
    s += '<path class="c2-line-tempo ' + cfg.classe + '" d="' + dEv + '"/>';
  }
  T.forEach(function (d, i) {
    var px = x(i), py = y(d[cfg.colEv]);
    var ehParcial = parcial && i === n - 1;
    s += '<circle class="c2-dot-tempo ' + cfg.classe + (ehParcial ? ' parcial' : '') + '" cx="' + px + '" cy="' + py + '" r="' + (ehParcial ? 4.5 : 3.5) + '"/>';
    if (d[cfg.colEv] > 0) {
      s += '<text class="c2-plabel ' + cfg.classe + '" x="' + px + '" y="' + (py - 10) + '" text-anchor="middle">' + fNum(d[cfg.colEv]) + (ehParcial ? '*' : '') + '</text>';
    }
  });

  // Digital: mesma cor, opacidade menor. Rótulo ABAIXO do ponto (o de
  // Evento fica acima) — direções opostas evitam colisão sem precisar
  // de checagem de proximidade ponto a ponto.
  if (n > 1) {
    var dDg = T.map(function (d, i) { return (i ? 'L' : 'M') + x(i) + ',' + y(d[cfg.colDg]); }).join('');
    s += '<path class="c2-line-tempo ' + cfg.classe + ' dim" d="' + dDg + '"/>';
  }
  T.forEach(function (d, i) {
    var px = x(i), py = y(d[cfg.colDg]);
    var ehParcialDg = parcial && i === n - 1;
    s += '<circle class="c2-dot-tempo ' + cfg.classe + ' dim' + (ehParcialDg ? ' parcial' : '') + '" cx="' + px + '" cy="' + py + '" r="' + (ehParcialDg ? 4 : 3) + '"/>';
    if (d[cfg.colDg] > 0) {
      s += '<text class="c2-plabel ' + cfg.classe + ' dim" x="' + px + '" y="' + (py + 15) + '" text-anchor="middle">' + fNum(d[cfg.colDg]) + (ehParcialDg ? '*' : '') + '</text>';
    }
  });

  for (var i5 = 0; i5 < n; i5++) {
    if (i5 % passo !== 0 && i5 !== n - 1) { continue; }
    var lx = x(i5), lyy = girar ? (base + 14) : (H - 9);
    s += girar
      ? '<text class="c2-axis-txt" x="' + lx + '" y="' + lyy + '" text-anchor="end" transform="rotate(-40 ' + lx + ' ' + lyy + ')">' + esc(T[i5].mes) + '</text>'
      : '<text class="c2-axis-txt" x="' + lx + '" y="' + lyy + '" text-anchor="middle">' + esc(T[i5].mes) + '</text>';
  }

  // Colunas de hover invisíveis por mês — fronteira no meio do
  // caminho até o mês vizinho (estilo Voronoi de gráfico de linha),
  // já que os pontos aqui são posição (escala de ponto), não barra.
  for (var i6 = 0; i6 < n; i6++) {
    var left  = i6 === 0     ? padL          : (x(i6 - 1) + x(i6)) / 2;
    var right = i6 === n - 1 ? (padL + plotW) : (x(i6) + x(i6 + 1)) / 2;
    s += '<rect class="c2-hover-col" data-i="' + i6 + '" x="' + left + '" y="' + padT + '" width="' + (right - left) + '" height="' + plotH + '"/>';
  }

  svg.style.height = H + 'px';
  svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
  svg.innerHTML = s;

  Array.prototype.forEach.call(svg.querySelectorAll('.c2-hover-col'), function (el) {
    var d = T[+el.getAttribute('data-i')];
    el.addEventListener('mousemove', function (evt) {
      mostrarTooltip(d.mes, [
        ['Evento', fNum(d[cfg.colEv])],
        ['Digital', fNum(d[cfg.colDg])],
      ], evt);
    });
    el.addEventListener('mouseleave', esconderTooltip);
  });

  var nota = document.getElementById(cfg.notaId);
  if (nota) {
    if (parcial) {
      var unidade = granularidadeAtual === 'semana' ? 'a semana corrente' : 'o mês corrente';
      nota.textContent = granularidadeAtual === 'dia'
        ? '* Hoje ainda não fechou — conta só até agora.'
        : '* ' + T[n - 1].mes + ' ainda não fechou — ' + unidade + ' conta só até hoje.';
      nota.hidden = false;
    } else {
      nota.hidden = true;
    }
  }

  var somaEv = T.reduce(function (a, d) { return a + (d[cfg.colEv] || 0); }, 0);
  var somaDg = T.reduce(function (a, d) { return a + (d[cfg.colDg] || 0); }, 0);
  document.getElementById(cfg.descId).textContent =
    cfg.rot + ' por mês: Evento soma ' + fNum(somaEv) + ', Digital soma ' + fNum(somaDg) + '. ' +
    T.map(function (d) { return d.mes + ' evento=' + fNum(d[cfg.colEv]) + ' digital=' + fNum(d[cfg.colDg]); }).join(', ') + '.';
}
</script>

<script>
// ── 6. Configuração dos 4 gráficos ─────────────────────────────
const CFG_PERF_EV = {
  svgId: 'svg-perf-ev', wrapId: 'wrap-perf-ev', descId: 'desc-perf-ev',
  colCr: 'cr_ev', colCb: 'cb_ev', colConv: 'conv_ev', dim: false
};
const CFG_PERF_DG = {
  svgId: 'svg-perf-dg', wrapId: 'wrap-perf-dg', descId: 'desc-perf-dg',
  colCr: 'cr_dg', colCb: 'cb_dg', colConv: 'conv_dg', dim: true
};
const CFG_TEMPO_CR = {
  svgId: 'svg-tempo-cr', wrapId: 'wrap-tempo-cr', descId: 'desc-tempo-cr', notaId: 'nota-tempo-cr',
  colEv: 'cr_ev', colDg: 'cr_dg', classe: 'cr', rot: 'Consultas Realizadas'
};
const CFG_TEMPO_CB = {
  svgId: 'svg-tempo-cb', wrapId: 'wrap-tempo-cb', descId: 'desc-tempo-cb', notaId: 'nota-tempo-cb',
  colEv: 'cb_ev', colDg: 'cb_dg', classe: 'cb', rot: 'Combos Vendidos'
};

function renderTudo() {
  renderRanking();
  renderPerf(CFG_PERF_EV);
  renderPerf(CFG_PERF_DG);
  renderTempo(CFG_TEMPO_CR);
  renderTempo(CFG_TEMPO_CB);
  var lu = document.getElementById('last-updated');
  if (lu) { lu.textContent = 'Atualizado ' + ((DADOS.meta || {}).generated || ''); }
}

// ── Filtros ───────────────────────────────────────────────────
const YEARS  = anosDesde(2025);
const MONTHS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

// A lista do time é fixa também aqui, mas NÃO é a autoridade: o
// controller intersecciona o que vier com a própria const, então um
// nome injetado na URL não amplia o recorte.
const EQUIPE = <?= json_encode($equipeComercial2, JSON_UNESCAPED_UNICODE) ?>;

const yearDropdown  = createMultiDropdown('year-wrap',  'year-btn',  'year-list',  YEARS,  l => l, l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);
const vendDropdown  = createMultiDropdown('vend-wrap',  'vend-btn',  'vend-list',  EQUIPE, l => l, l => l);
const formDropdown  = createSearchDropdown('form-wrap', 'form-input', 'form-dropdown', 'form-clear', 'form-tags', 'Nenhum form encontrado');

const NOW_YEAR = String(new Date().getFullYear());
yearDropdown.select([NOW_YEAR]);

fetch(FORMS_URL, { cache: 'no-store' })
  .then(r => r.json())
  .then(l => formDropdown.load(Array.isArray(l) ? l : []))
  .catch(err => console.error('Erro ao carregar forms:', err));

function montaParams() {
  return {
    year:          yearDropdown.getValue(),
    months:        monthDropdown.getValue(),
    responsavel:   vendDropdown.getValue(),
    form:          formDropdown.getValue(),
    data_inicio:   getDateInput('data-inicio'),
    data_fim:      getDateInput('data-fim'),
    granularidade: granularidadeAtual,
  };
}

const TITULOS_GRAN = {
  mes:    { cr: 'Consultas Realizadas ao Longo do Tempo (mensal)',  cb: 'Combos Vendidos ao Longo do Tempo (mensal)' },
  semana: { cr: 'Consultas Realizadas ao Longo do Tempo (semanal)', cb: 'Combos Vendidos ao Longo do Tempo (semanal)' },
  dia:    { cr: 'Consultas Realizadas ao Longo do Tempo (diária)',  cb: 'Combos Vendidos ao Longo do Tempo (diária)' },
};

function setGranularidade(g) {
  granularidadeAtual = g;
  document.querySelectorAll('.gran-toggle .gran-btn').forEach(b => b.classList.toggle('active', b.dataset.gran === g));
  document.getElementById('chart-title-cr').textContent = TITULOS_GRAN[g].cr;
  document.getElementById('chart-title-cb').textContent = TITULOS_GRAN[g].cb;
  applyFilters();
}

// ── Carga ─────────────────────────────────────────────────────
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

    DADOS = data;
    renderTudo();
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
  const badge  = document.getElementById('filter-label-badge');
  const year   = yearDropdown.getValue();
  const months = monthDropdown.getValue();
  const vend   = vendDropdown.getValue();
  const form   = formDropdown.getValue();
  if (!year && !months && !vend && !form) {
    badge.textContent = 'TODOS OS PERÍODOS';
    return;
  }
  const parts = [];
  if (form) parts.push('Form: ' + form);
  if (vend) parts.push(vend);
  if (year) parts.push(year);
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
  vendDropdown.reset();
  formDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim', 'data-fim-clear');
  yearDropdown.select([NOW_YEAR]);
  granularidadeAtual = 'mes';
  document.querySelectorAll('.gran-toggle .gran-btn').forEach(b => b.classList.toggle('active', b.dataset.gran === 'mes'));
  document.getElementById('chart-title-cr').textContent = TITULOS_GRAN.mes.cr;
  document.getElementById('chart-title-cb').textContent = TITULOS_GRAN.mes.cb;
  applyFilters();
}

document.getElementById('btn-apply').addEventListener('click', applyFilters);
document.getElementById('btn-clear').addEventListener('click', clearFilters);

['data-inicio', 'data-fim'].forEach(id => {
  const input    = document.getElementById(id);
  const clearBtn = document.getElementById(id + '-clear');
  input?.addEventListener('change', () => clearBtn?.classList.toggle('visible', !!input.value));
  clearBtn?.addEventListener('click', () => clearDateInput(id, id + '-clear'));
});

// Os 4 SVGs de gráfico (perf x2, tempo x2) são redesenhados no
// resize: a geometria vem da largura medida do card. A tabela não
// precisa — ela não é SVG.
window.addEventListener('resize', function () {
  if (DADOS) {
    renderPerf(CFG_PERF_EV); renderPerf(CFG_PERF_DG);
    renderTempo(CFG_TEMPO_CR); renderTempo(CFG_TEMPO_CB);
  }
});

applyFilters();
</script>
