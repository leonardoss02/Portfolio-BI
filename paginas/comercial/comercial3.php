<!-- ══════════════════════════════════════════════════════════════
     Comercial — Time Comercial 3 (Retenção): 2 vendedores, pipe C1
     (recuperação de cancelamentos). Sem split Evento/Digital — este
     time não usa tipoLead. Estrutura fiel ao HTML de origem
     (Html_Comercial3): 2 rankings (por Consulta Agendada e por
     Consulta Realizada, ambos culminando em Combo Em Retenção),
     "Performance de Vendedores" (barras agrupadas + linha de
     conversão) e, lado a lado, "Evolução ao Longo do Tempo" e
     "Motivos de Descartes".

     Todo o CSS vive no bloco .dash-comercial-3 de css/bi-theme.css
     (seção 20h) — nada de <style> inline aqui.
     ══════════════════════════════════════════════════════════════ -->

<div class="dash-comercial-3">

<header class="header">
  <div class="header-left">
    <div class="logo">C3</div>
    <div>
      <div class="header-title">Time Comercial 3 — Painel de Performance</div>
      <div class="header-sub">Retenção · Consultas Agendadas/Realizadas · Combos · Descartes</div>
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
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial/comercial2<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Com. 2</a>
      <a class="tab-switch-btn active" href="<?= $this->site ?>comercial/comercial3<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Com. 3</a>
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
<script>initFiltersToggle({ key: 'comercial-comercial3' });</script>

<div id="loading-overlay" class="hidden"></div>
<div id="error-banner" role="alert"></div>

<main class="main" aria-label="Performance do time Comercial 3 — Retenção">

  <div class="c3-grid2">
    <article class="bi-card">
      <div class="bi-card-header">
        <span class="bi-card-title">Ranking — Consulta Agendada</span>
        <span class="bi-card-sub">Agendada &rarr; Combo · clique numa coluna para ordenar</span>
      </div>
      <div class="bi-card-body">
        <div class="c3-tbl-scroll">
          <table class="c3-tbl" id="c3-tbl-a">
            <thead id="c3-thead-a"></thead>
            <tbody id="c3-tbody-a"></tbody>
          </table>
        </div>
      </div>
    </article>

    <article class="bi-card">
      <div class="bi-card-header">
        <span class="bi-card-title">Ranking — Consulta Realizada</span>
        <span class="bi-card-sub">Realizada &rarr; Combo · clique numa coluna para ordenar</span>
      </div>
      <div class="bi-card-body">
        <div class="c3-tbl-scroll">
          <table class="c3-tbl" id="c3-tbl-b">
            <thead id="c3-thead-b"></thead>
            <tbody id="c3-tbody-b"></tbody>
          </table>
        </div>
      </div>
    </article>
  </div>

  <article class="bi-card">
    <div class="bi-card-header">
      <span class="bi-card-title">Performance de Vendedores</span>
      <div class="c3-legend">
        <span class="c3-legend-item"><i class="c3-sw agend"></i>Cons. Agendadas</span>
        <span class="c3-legend-item"><i class="c3-sw real"></i>Cons. Realizadas</span>
        <span class="c3-legend-item"><i class="c3-sw combos"></i>Combos</span>
        <span class="c3-legend-item"><i class="c3-sw conv"></i>Conversão % (Agendada)</span>
      </div>
    </div>
    <div class="bi-card-body">
      <div class="c3-chart-wrap" id="wrap-c3-perf">
        <svg id="svg-c3-perf" role="img" aria-labelledby="desc-c3-perf"></svg>
        <span id="desc-c3-perf" class="sr-only"></span>
      </div>
    </div>
  </article>

  <div class="c3-grid2">
    <article class="bi-card">
      <div class="bi-card-header">
        <span class="bi-card-title" id="chart-title">Evolução ao Longo do Tempo</span>
        <div class="gran-toggle" id="gran-toggle">
          <button class="gran-btn active" data-gran="mes"    onclick="setGranularidade('mes')">Mês</button>
          <button class="gran-btn"        data-gran="semana" onclick="setGranularidade('semana')">Semana</button>
          <button class="gran-btn"        data-gran="dia"    onclick="setGranularidade('dia')">Dia</button>
        </div>
        <div class="c3-legend">
          <span class="c3-legend-item"><i class="c3-sw agend"></i>Agendadas</span>
          <span class="c3-legend-item"><i class="c3-sw combos"></i>Combos</span>
        </div>
      </div>
      <div class="bi-card-body">
        <div class="c3-chart-wrap" id="wrap-c3-tempo">
          <svg id="svg-c3-tempo" role="img" aria-labelledby="desc-c3-tempo"></svg>
          <span id="desc-c3-tempo" class="sr-only"></span>
          <div class="c3-nota-parcial" id="nota-c3-tempo" hidden></div>
        </div>
      </div>
    </article>

    <article class="bi-card">
      <div class="bi-card-header">
        <span class="bi-card-title">Motivos de Descartes</span>
        <span class="bi-card-sub">Top 10 · pipe Retenção</span>
      </div>
      <div class="bi-card-body">
        <div class="c3-chart-wrap" id="wrap-c3-desc">
          <svg id="svg-c3-desc" role="img" aria-labelledby="desc-c3-desc"></svg>
          <span id="desc-c3-desc" class="sr-only"></span>
        </div>
      </div>
    </article>
  </div>

</main>
</div>

<div class="bi-tooltip" id="c3-tooltip"></div>

<script>
// ══════════════════════════════════════════════════════════════
//  Comercial — Time Comercial 3 (Retenção) · JS da view
//
//  Consome comercial/comercial3Dados e comercial/forms.
//
//  createMultiDropdown, createSearchDropdown, getDateInput,
//  clearDateInput e fNum vêm de js/bi-dashboard.js, carregado no
//  <head> pelo template e COMPARTILHADO por todas as views — não
//  alterar aquele arquivo por causa desta. applyTheme/toggleTheme/
//  relógio do rodapé vêm de template/comercial.php.
//
//  Tooltip/escala/mês-parcial reaproveitam literalmente o padrão já
//  validado em comercial2.php (mesmo componente .bi-tooltip).
// ══════════════════════════════════════════════════════════════

const API       = '<?= $this->site ?>comercial/comercial3Dados';
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

function mostrarTooltip(titulo, linhas, evt) {
  var tip = document.getElementById('c3-tooltip');
  tip.innerHTML = '<div class="bi-tooltip-inner"><div class="bi-tooltip-title">' + esc(titulo) + '</div>' +
    linhas.map(function (l) {
      return '<div class="bi-tooltip-row"><span class="bi-tooltip-label">' + esc(l[0]) + '</span>' +
        '<span class="bi-tooltip-val">' + esc(l[1]) + '</span></div>';
    }).join('') + '</div>';
  tip.classList.add('visible');
  posicionarTooltip(evt);
}
function posicionarTooltip(evt) {
  var tip = document.getElementById('c3-tooltip');
  var pad = 14;
  var x = evt.clientX + pad, y = evt.clientY + pad;
  var r = tip.getBoundingClientRect();
  if (x + r.width + 8 > window.innerWidth)  { x = evt.clientX - r.width - pad; }
  if (y + r.height + 8 > window.innerHeight) { y = evt.clientY - r.height - pad; }
  tip.style.left = Math.max(8, x) + 'px';
  tip.style.top  = Math.max(8, y) + 'px';
}
function esconderTooltip() {
  document.getElementById('c3-tooltip').classList.remove('visible');
}

// ── 2. Rankings (2 tabelas, mesma estrutura, chave numérica principal
// diferente) — estado de ordenação independente por tabela. Posição
// reflete a ordenação ATUAL (mesmo critério do Comercial 2): com só 2
// vendedores não há pódio fixo relevante aqui.
const TABS = {
  a: { field: 'agend',     desc: true, valKey: 'agend',     convKey: 'conv_agend', rot: 'Cons. Agendadas', theadId: 'c3-thead-a', tbodyId: 'c3-tbody-a' },
  b: { field: 'realizada', desc: true, valKey: 'realizada', convKey: 'conv_real',  rot: 'Cons. Realizadas', theadId: 'c3-thead-b', tbodyId: 'c3-tbody-b' },
};

function sortTab(key, field) {
  var tab = TABS[key];
  if (tab.field === field) { tab.desc = !tab.desc; } else { tab.field = field; tab.desc = true; }
  renderTabela(key);
}

function renderTabela(key) {
  var tab = TABS[key];
  var cols = [
    { k: 'nome',        rot: 'Vendedor' },
    { k: tab.valKey,    rot: tab.rot },
    { k: 'combos',      rot: 'Combos Vend.' },
    { k: tab.convKey,   rot: 'Conversão' },
  ];

  var thead = document.getElementById(tab.theadId);
  thead.innerHTML = '<tr><th class="c3-th-rank">#</th>' + cols.map(function (c) {
    var ativo = tab.field === c.k;
    var arr = ativo ? (tab.desc ? '▼' : '▲') : '↕';
    return '<th class="' + (c.k !== 'nome' ? 'num ' : '') + (ativo ? 'active' : '') + '"' +
      ' tabindex="0" role="button" aria-label="Ordenar por ' + esc(c.rot) + '"' +
      ' onclick="sortTab(\'' + key + '\',\'' + c.k + '\')"' +
      ' onkeydown="if(event.key===\'Enter\'||event.key===\' \'){event.preventDefault();sortTab(\'' + key + '\',\'' + c.k + '\');}">' +
      esc(c.rot) + '<span class="arr">' + arr + '</span></th>';
  }).join('') + '</tr>';

  var tbody = document.getElementById(tab.tbodyId);
  var R = (DADOS.ranking || []).slice();

  if (!R.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="c3-vazio">Sem vendedores no período</td></tr>';
    return;
  }

  var s = R.sort(function (x, y) {
    var vx = tab.field === 'nome' ? x.nome : (x[tab.field] || 0);
    var vy = tab.field === 'nome' ? y.nome : (y[tab.field] || 0);
    if (typeof vx === 'string') { return tab.desc ? vy.localeCompare(vx, 'pt-BR') : vx.localeCompare(vy, 'pt-BR'); }
    return tab.desc ? vy - vx : vx - vy;
  });

  var rows = s.map(function (d, i) {
    var r = i + 1;
    var rc = r === 1 ? 'r1' : r === 2 ? 'r2' : 'rn';
    return '<tr><td class="c3-th-rank"><span class="c3-rank ' + rc + '">' + r + '</span></td>' +
      '<td><div class="c3-name"><span class="c3-avatar">' + esc(primeiroNome(d.nome)[0] || '?') + '</span>' +
      '<span>' + esc(d.nome) + '</span></div></td>' +
      '<td class="num">' + fNum(d[tab.valKey]) + '</td>' +
      '<td class="num">' + fNum(d.combos) + '</td>' +
      '<td class="num"><span class="c3-conv">' + fConv(d[tab.convKey]) + '</span></td></tr>';
  }).join('');

  var totVal = R.reduce(function (a, d) { return a + (d[tab.valKey] || 0); }, 0);
  var totCombos = R.reduce(function (a, d) { return a + (d.combos || 0); }, 0);
  var totConv = totVal > 0 ? totCombos / totVal * 100 : 0;
  rows += '<tr class="total"><td class="c3-th-rank"></td><td>TOTAL</td>' +
    '<td class="num">' + fNum(totVal) + '</td><td class="num">' + fNum(totCombos) + '</td>' +
    '<td class="num"><span class="c3-conv">' + fConv(totConv) + '</span></td></tr>';

  tbody.innerHTML = rows;
}

// ── 3. Escala de eixo (mesmo helper de comercial2.php) ─────────
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

// ── 4. Performance de Vendedores: 3 barras (agend/realizada/combos)
// + linha de conversão (Agendada→Combo) em eixo duplo. As 3 métricas
// vêm da MESMA linha por vendedor (calcularRankingComercial3, 1 query
// só) — sem risco de desalinhamento de ordem entre séries, diferente
// do HTML de origem (que montava _ranking/_ranking2 em duas
// ADDCOLUMNS/VALUES separadas e confiava na mesma ordem por sorte).
function renderPerf() {
  var R   = DADOS.ranking || [];
  var svg = document.getElementById('svg-c3-perf');
  var n   = R.length;

  if (!n) {
    svg.removeAttribute('viewBox');
    svg.style.height = '80px';
    svg.innerHTML = '<text x="50%" y="50%" text-anchor="middle" class="c3-axis-txt">Sem dados no período</text>';
    document.getElementById('desc-c3-perf').textContent = 'Sem dados no período selecionado.';
    return;
  }

  var W = document.getElementById('wrap-c3-perf').clientWidth || 900;
  var H = 260, padL = 50, padR = 54, padT = 24, padB = 42;
  var plotW = Math.max(120, W - padL - padR), plotH = H - padT - padB, base = padT + plotH;

  var bw    = plotW / n;
  var xc    = function (i) { return padL + bw * (i + 0.5); };
  var barW  = Math.max(6, Math.min(22, bw * 0.16));
  var gap   = Math.max(2, Math.round(barW * 0.25));
  var grupo = barW * 3 + gap * 2;

  var maxBar = 0, maxConv = 0;
  R.forEach(function (d) {
    maxBar  = Math.max(maxBar, d.agend || 0, d.realizada || 0, d.combos || 0);
    maxConv = Math.max(maxConv, Math.min(999, d.conv_agend || 0));
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
    s += '<line class="c3-grid-line" x1="' + padL + '" y1="' + gy + '" x2="' + (padL + plotW) + '" y2="' + gy + '"/>';
    s += '<text class="c3-axis-txt" x="' + (padL - 8) + '" y="' + (gy + 3.5) + '" text-anchor="end">' + kNum(escB.topo * f) + '</text>';
    s += '<text class="c3-axis-txt right" x="' + (padL + plotW + 8) + '" y="' + (gy + 3.5) + '" text-anchor="start">' + (escC.topo * f).toFixed(casasConv).replace('.', ',') + '%</text>';
  }

  var pts = [];
  var rotulosBarra = [];
  R.forEach(function (d, i) {
    var cx = xc(i), x0 = cx - grupo / 2;
    [['agend', 'c3-bar-agend', x0, 'agend'], ['realizada', 'c3-bar-real', x0 + barW + gap, 'real'], ['combos', 'c3-bar-combos', x0 + (barW + gap) * 2, 'combos']].forEach(function (par) {
      var v = d[par[0]] || 0;
      if (v <= 0) { return; }
      var yt = yB(v), h = Math.max(1, base - yt);
      s += '<rect class="' + par[1] + '" x="' + par[2] + '" y="' + (base - h) + '" width="' + barW + '" height="' + h + '" rx="2"/>';
      if (barW >= 12) {
        var lx = par[2] + barW / 2, ly = base - h - 5;
        rotulosBarra.push({ x: lx, y: ly });
        s += '<text class="c3-plabel ' + par[3] + '" x="' + lx + '" y="' + ly + '" text-anchor="middle">' + kNum(v) + '</text>';
      }
    });
    pts.push([cx, yC(d.conv_agend || 0), d.conv_agend || 0]);
    s += '<text class="c3-axis-nome" x="' + cx + '" y="' + (base + 20) + '" text-anchor="middle">' + esc(primeiroNome(d.nome)) + '</text>';
  });

  R.forEach(function (d, i) {
    s += '<rect class="c3-hover-col" data-i="' + i + '" x="' + (padL + bw * i) + '" y="' + padT + '" width="' + bw + '" height="' + plotH + '"/>';
  });

  if (pts.length > 1) {
    s += '<path class="c3-line-conv" d="' + pts.map(function (p, i) { return (i ? 'L' : 'M') + p[0] + ',' + p[1]; }).join('') + '"/>';
  }
  pts.forEach(function (p) {
    s += '<circle class="c3-dot-conv" cx="' + p[0] + '" cy="' + p[1] + '" r="4"/>';
    var acima  = p[1] - 10;
    var colide = rotulosBarra.some(function (r) { return Math.abs(r.x - p[0]) < 30 && Math.abs(r.y - acima) < 10; });
    var ly = colide ? p[1] + 15 : acima;
    s += '<text class="c3-plabel conv" x="' + p[0] + '" y="' + ly + '" text-anchor="middle">' + fConv(p[2]) + '</text>';
  });

  svg.style.height = H + 'px';
  svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
  svg.innerHTML = s;

  Array.prototype.forEach.call(svg.querySelectorAll('.c3-hover-col'), function (el) {
    var d = R[+el.getAttribute('data-i')];
    el.addEventListener('mousemove', function (evt) {
      mostrarTooltip(d.nome, [
        ['Cons. Agendadas', fNum(d.agend)],
        ['Cons. Realizadas', fNum(d.realizada)],
        ['Combos', fNum(d.combos)],
        ['Conversão % (Agendada)', fConv(d.conv_agend)],
      ], evt);
    });
    el.addEventListener('mouseleave', esconderTooltip);
  });

  document.getElementById('desc-c3-perf').textContent =
    'Por vendedor: ' + R.map(function (d) {
      return d.nome + ' com ' + fNum(d.agend) + ' consultas agendadas, ' + fNum(d.realizada) + ' realizadas, ' + fNum(d.combos) + ' combos e ' + fConv(d.conv_agend) + ' de conversão';
    }).join('; ') + '. Os mesmos números estão nas tabelas de ranking acima.';
}

// ── 5. Evolução ao Longo do Tempo: linha dupla, EIXO ÚNICO (agend x
// combos são ordem de grandeza comparável, mesmo padrão do HTML de
// origem que também usava escala única). ────────────────────────
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

function renderTempo() {
  var T   = DADOS.tempo || [];
  var svg = document.getElementById('svg-c3-tempo');
  var n   = T.length;

  if (!n) {
    svg.removeAttribute('viewBox');
    svg.style.height = '80px';
    svg.innerHTML = '<text x="50%" y="50%" text-anchor="middle" class="c3-axis-txt">Sem dados no período</text>';
    document.getElementById('desc-c3-tempo').textContent = 'Sem dados no período selecionado.';
    document.getElementById('nota-c3-tempo').hidden = true;
    return;
  }

  var W = document.getElementById('wrap-c3-tempo').clientWidth || 460;
  var H = 280, padL = 44, padR = 16, padT = 24;

  var plotW0 = Math.max(60, W - padL - padR);
  var bw0 = plotW0 / n;
  var maxChars = T.reduce(function (m, d) { return Math.max(m, String(d.mes).length); }, 0);
  var largRot = maxChars * 6.2 + 6;
  var girar = bw0 < largRot;
  var passo = Math.max(1, Math.ceil((girar ? 13 : largRot) / bw0));
  var padB = girar ? Math.min(78, Math.round(largRot * 0.64) + 16) : 30;
  var plotH = H - padT - padB, plotW = plotW0, base = padT + plotH;

  var parcial = ultimoPeriodoParcial(T, granularidadeAtual);

  var maxV = T.reduce(function (m, d) { return Math.max(m, d.agend || 0, d.combos || 0); }, 0);
  var esc2 = escalaEixo(maxV);
  var y = function (v) { return base - ((v || 0) / esc2.topo) * plotH; };
  var x = function (i) { return n === 1 ? padL + plotW / 2 : padL + (plotW / (n - 1)) * i; };

  var s = '';
  for (var t = 0; t <= esc2.ticks; t++) {
    var f = t / esc2.ticks, gy = base - f * plotH;
    s += '<line class="c3-grid-line" x1="' + padL + '" y1="' + gy + '" x2="' + (padL + plotW) + '" y2="' + gy + '"/>';
    s += '<text class="c3-axis-txt" x="' + (padL - 7) + '" y="' + (gy + 3.5) + '" text-anchor="end">' + kNum(esc2.topo * f) + '</text>';
  }

  ['agend', 'combos'].forEach(function (serie) {
    if (n > 1) {
      var d0 = T.map(function (d, i) { return (i ? 'L' : 'M') + x(i) + ',' + y(d[serie]); }).join('');
      s += '<path class="c3-area ' + serie + '" d="' + d0 + ' L' + x(n - 1) + ',' + base + ' L' + x(0) + ',' + base + ' Z"/>';
      s += '<path class="c3-line-tempo ' + serie + '" d="' + d0 + '"/>';
    }
    T.forEach(function (d, i) {
      var px = x(i), py = y(d[serie]);
      var ehParcial = parcial && i === n - 1;
      s += '<circle class="c3-dot-tempo ' + serie + (ehParcial ? ' parcial' : '') + '" cx="' + px + '" cy="' + py + '" r="' + (ehParcial ? 4.5 : 3.5) + '"/>';
      if (d[serie] > 0) {
        var ly = serie === 'agend' ? py - 10 : py + 15;
        s += '<text class="c3-plabel ' + serie + '" x="' + px + '" y="' + ly + '" text-anchor="middle">' + fNum(d[serie]) + (ehParcial ? '*' : '') + '</text>';
      }
    });
  });

  for (var i5 = 0; i5 < n; i5++) {
    if (i5 % passo !== 0 && i5 !== n - 1) { continue; }
    var lx = x(i5), lyy = girar ? (base + 14) : (H - padB + 21);
    s += girar
      ? '<text class="c3-axis-txt" x="' + lx + '" y="' + lyy + '" text-anchor="end" transform="rotate(-40 ' + lx + ' ' + lyy + ')">' + esc(T[i5].mes) + '</text>'
      : '<text class="c3-axis-txt" x="' + lx + '" y="' + lyy + '" text-anchor="middle">' + esc(T[i5].mes) + '</text>';
  }

  for (var i6 = 0; i6 < n; i6++) {
    var left  = i6 === 0     ? padL          : (x(i6 - 1) + x(i6)) / 2;
    var right = i6 === n - 1 ? (padL + plotW) : (x(i6) + x(i6 + 1)) / 2;
    s += '<rect class="c3-hover-col" data-i="' + i6 + '" x="' + left + '" y="' + padT + '" width="' + (right - left) + '" height="' + plotH + '"/>';
  }

  svg.style.height = H + 'px';
  svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
  svg.innerHTML = s;

  Array.prototype.forEach.call(svg.querySelectorAll('.c3-hover-col'), function (el) {
    var d = T[+el.getAttribute('data-i')];
    el.addEventListener('mousemove', function (evt) {
      mostrarTooltip(d.mes, [
        ['Agendadas', fNum(d.agend)],
        ['Combos', fNum(d.combos)],
      ], evt);
    });
    el.addEventListener('mouseleave', esconderTooltip);
  });

  var nota = document.getElementById('nota-c3-tempo');
  if (parcial) {
    var unidade = granularidadeAtual === 'semana' ? 'a semana corrente' : 'o mês corrente';
    nota.textContent = granularidadeAtual === 'dia'
      ? '* Hoje ainda não fechou — conta só até agora.'
      : '* ' + T[n - 1].mes + ' ainda não fechou — ' + unidade + ' conta só até hoje.';
    nota.hidden = false;
  } else {
    nota.hidden = true;
  }

  var somaAg = T.reduce(function (a, d) { return a + (d.agend || 0); }, 0);
  var somaCb = T.reduce(function (a, d) { return a + (d.combos || 0); }, 0);
  document.getElementById('desc-c3-tempo').textContent =
    'Por mês: Agendadas soma ' + fNum(somaAg) + ', Combos soma ' + fNum(somaCb) + '. ' +
    T.map(function (d) { return d.mes + ' agendadas=' + fNum(d.agend) + ' combos=' + fNum(d.combos); }).join(', ') + '.';
}

// ── 6. Motivos de Descartes: barra horizontal, top 10 ───────────
function renderDescartes() {
  var D   = DADOS.descartes || [];
  var svg = document.getElementById('svg-c3-desc');

  if (!D.length) {
    svg.removeAttribute('viewBox');
    svg.style.height = '80px';
    svg.innerHTML = '<text x="50%" y="50%" text-anchor="middle" class="c3-axis-txt">Sem descartes no período</text>';
    document.getElementById('desc-c3-desc').textContent = 'Sem descartes no período selecionado.';
    return;
  }

  var rowH = 26, pl = 150, pr = 54, pt = 8, W = 460;
  var H = pt + D.length * rowH + 10;
  var maxV = D.reduce(function (m, d) { return Math.max(m, d.qtd || 0); }, 1);
  var cW = W - pl - pr;

  var s = '';
  D.forEach(function (d, i) {
    var y = pt + i * rowH, bw = Math.max(2, Math.round((d.qtd || 0) / maxV * cW));
    s += '<text class="c3-axis-txt desc" x="' + (pl - 8) + '" y="' + (y + rowH / 2 + 3.5) + '" text-anchor="end">' + esc(d.motivo) + '</text>';
    s += '<rect class="c3-bar-desc" x="' + pl + '" y="' + (y + 4) + '" width="' + bw + '" height="' + (rowH - 10) + '" rx="3"/>';
    s += '<text class="c3-plabel desc" x="' + (pl + bw + 6) + '" y="' + (y + rowH / 2 + 3.5) + '" text-anchor="start">' + fNum(d.qtd) + '</text>';
    s += '<rect class="c3-hover-col" data-i="' + i + '" x="0" y="' + y + '" width="' + W + '" height="' + rowH + '"/>';
  });

  svg.style.height = H + 'px';
  svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
  svg.innerHTML = s;

  Array.prototype.forEach.call(svg.querySelectorAll('.c3-hover-col'), function (el) {
    var d = D[+el.getAttribute('data-i')];
    el.addEventListener('mousemove', function (evt) {
      mostrarTooltip(d.motivo, [['Quantidade', fNum(d.qtd)]], evt);
    });
    el.addEventListener('mouseleave', esconderTooltip);
  });

  document.getElementById('desc-c3-desc').textContent =
    'Motivos de descarte, do mais frequente ao menos: ' +
    D.map(function (d) { return d.motivo + ' (' + fNum(d.qtd) + ')'; }).join(', ') + '.';
}

// ── 7. Orquestração ──────────────────────────────────────────────
function renderTudo() {
  renderTabela('a');
  renderTabela('b');
  renderPerf();
  renderTempo();
  renderDescartes();
  var lu = document.getElementById('last-updated');
  if (lu) { lu.textContent = 'Atualizado ' + ((DADOS.meta || {}).generated || ''); }
}

// ── Filtros ───────────────────────────────────────────────────
const YEARS  = ['2024', '2025', '2026', '2027'];
const MONTHS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

// A lista do time é fixa também aqui, mas NÃO é a autoridade: o
// controller intersecciona o que vier com a própria const, então um
// nome injetado na URL não amplia o recorte.
const EQUIPE = <?= json_encode($equipeComercial3, JSON_UNESCAPED_UNICODE) ?>;

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
  mes: 'Evolução ao Longo do Tempo (mensal)',
  semana: 'Evolução ao Longo do Tempo (semanal)',
  dia: 'Evolução ao Longo do Tempo (diária)',
};

function setGranularidade(g) {
  granularidadeAtual = g;
  document.querySelectorAll('#gran-toggle .gran-btn').forEach(b => b.classList.toggle('active', b.dataset.gran === g));
  document.getElementById('chart-title').textContent = TITULOS_GRAN[g];
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
  document.querySelectorAll('#gran-toggle .gran-btn').forEach(b => b.classList.toggle('active', b.dataset.gran === 'mes'));
  document.getElementById('chart-title').textContent = TITULOS_GRAN.mes;
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

// Os 3 SVGs de gráfico (perf, tempo, descartes) são redesenhados no
// resize: a geometria vem da largura medida do card. As tabelas não
// precisam — não são SVG.
window.addEventListener('resize', function () {
  if (DADOS) {
    renderPerf(); renderTempo(); renderDescartes();
  }
});

applyFilters();
</script>
