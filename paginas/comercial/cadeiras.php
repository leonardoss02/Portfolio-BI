<!-- ══════════════════════════════════════════════════════════════
     Comercial — Time de Cadeiras: performance dos 3 vendedores que
     trabalham o lead OTR e fecham a venda de ingresso (C9:WON).

     O eixo desta tela é a PESSOA, não o funil — daí o ranking vir
     primeiro e o resto ser leitura de apoio.

     ATENÇÃO: aqui "Evento" é só tipoLead OTR. Nas abas Visão Geral e
     Comparativo o canal Evento é OTR + OTW. É deliberado (é a regra do
     relatório de origem) e está explicado no dicionário.

     Todo o CSS vive no bloco .dash-comercial-cadeiras de
     css/bi-theme.css (seção 20e) — nada de <style> inline aqui.
     ══════════════════════════════════════════════════════════════ -->

<div class="dash-comercial-cadeiras">

<header class="header">
  <div class="header-left">
    <div class="logo">CO</div>
    <div>
      <div class="header-title">Time Cadeiras — Painel de Performance</div>
      <div class="header-sub">Leads qualificados OTR · Cadeiras vendidas · Conversão · Descartes</div>
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
      <a class="tab-switch-btn active" href="<?= $this->site ?>comercial/cadeiras<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Cadeiras</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial/comercial1<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Com. 1</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial/comercial2<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Com. 2</a>
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
<script>initFiltersToggle({ key: 'comercial-cadeiras' });</script>

<div id="loading-overlay" class="hidden"></div>
<div id="error-banner" role="alert"></div>

<main class="main" aria-label="Performance do time de cadeiras">

  <article class="bi-card">
    <div class="bi-card-header">
      <span class="bi-card-title">Ranking de Vendedores</span>
      <span class="cad-badge" id="cad-badge">—</span>
    </div>
    <div class="bi-card-body">
      <table class="cad-tbl" id="cad-tbl">
        <thead id="cad-thead"></thead>
        <tbody id="cad-tbody"></tbody>
      </table>
    </div>
  </article>

  <article class="bi-card">
    <div class="bi-card-header">
      <span class="bi-card-title">Performance de Vendedores</span>
      <div class="cad-legend">
        <span class="cad-legend-item"><i class="cad-sw leads"></i>Leads qualificados</span>
        <span class="cad-legend-item"><i class="cad-sw cad"></i>Cadeiras vendidas</span>
        <span class="cad-legend-item"><i class="cad-sw conv"></i>Conversão %</span>
      </div>
    </div>
    <div class="bi-card-body">
      <div class="cad-chart-wrap" id="wrap-perf">
        <svg id="svg-perf" role="img" aria-labelledby="desc-perf"></svg>
        <span id="desc-perf" class="sr-only"></span>
      </div>
    </div>
  </article>

  <div class="cad-split">

    <article class="bi-card">
      <div class="bi-card-header">
        <span class="bi-card-title" id="chart-title">Cadeiras Vendidas ao Longo do Tempo</span>
        <div class="gran-toggle" id="gran-toggle">
          <button class="gran-btn active" data-gran="mes"    onclick="setGranularidade('mes')">Mês</button>
          <button class="gran-btn"        data-gran="semana" onclick="setGranularidade('semana')">Semana</button>
          <button class="gran-btn"        data-gran="dia"    onclick="setGranularidade('dia')">Dia</button>
        </div>
        <span class="bi-card-sub">períodos sem venda não aparecem</span>
      </div>
      <div class="bi-card-body">
        <div class="cad-chart-wrap" id="wrap-tempo">
          <svg id="svg-tempo" role="img" aria-labelledby="desc-tempo"></svg>
          <span id="desc-tempo" class="sr-only"></span>
        </div>
        <div class="cad-nota-parcial" id="nota-tempo" hidden></div>
      </div>
    </article>

    <article class="bi-card">
      <div class="bi-card-header">
        <span class="bi-card-title">Motivos de Descarte</span>
        <span class="bi-card-sub" id="desc-sub">top 10</span>
      </div>
      <div class="bi-card-body">
        <div class="cad-desc" id="cad-desc"></div>
      </div>
    </article>

  </div>

</main>
</div>

<script>
// ══════════════════════════════════════════════════════════════
//  Comercial — Time de Cadeiras · JS da view
//
//  Consome comercial/cadeirasDados e comercial/forms.
//
//  createMultiDropdown, createSearchDropdown, getDateInput,
//  clearDateInput, fNum e fPctRatio vêm de js/bi-dashboard.js,
//  carregado no <head> pelo template e COMPARTILHADO por 15 views —
//  não alterar aquele arquivo por causa desta.
//
//  Layout e interação validados antes em comercial-cadeiras-mock.html.
// ══════════════════════════════════════════════════════════════

const API       = '<?= $this->site ?>comercial/cadeirasDados';
const FORMS_URL = '<?= $this->site ?>comercial/forms';

let DADOS = null;
let granularidadeAtual = 'mes';
</script>

<script>// ── 1. Helpers ────────────────────────────────────────────────
function esc(s) {
  return String(s === null || s === undefined ? '' : s)
    .replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
}

function fPct2(v) { return (Number(v) || 0).toFixed(2).replace('.', ',') + '%'; }

// Rótulo curto para os gráficos: 4 dígitos por barra colidem.
function kNum(v) { return v >= 10000 ? (v / 1000).toFixed(1).replace('.', ',') + 'k' : fNum(Math.round(v)); }

// Iniciais para o avatar. Nome único devolve 1 letra em vez de quebrar.
function iniciais(nome) {
  var p = String(nome || '').trim().split(/\s+/).filter(Boolean);
  if (!p.length) { return '?'; }
  return (p.length === 1 ? p[0].slice(0, 2) : p[0][0] + p[p.length - 1][0]).toUpperCase();
}

// Faixas de conversão do relatório de origem.
function classeConv(v) { return v >= 12 ? 'hi' : v >= 7 ? 'mid' : 'lo'; }

// ── 2. Ranking ────────────────────────────────────────────────
//
// A posição é sempre pelo desempenho (cadeiras, depois conversão,
// depois nome), NÃO pela ordenação escolhida na tabela: clicar em
// "Vendedor" para achar alguém não pode reescrever quem é o 1º lugar.
const COLS = [
  { k: 'pos',      rot: '#',              num: false, sortavel: false },
  { k: 'nome',     rot: 'Vendedor',       num: false, sortavel: true,  cls: 'col-nome' },
  { k: 'leads',    rot: 'Leads qualif.',  num: true,  sortavel: true  },
  { k: 'cadeiras', rot: 'Cadeiras vend.', num: true,  sortavel: true  },
  { k: 'conv',     rot: 'Conversão',      num: true,  sortavel: true  }
];

let _sortCol = 'cadeiras';
let _sortDesc = true;

function ordemDePodio(a, b) {
  return (b.cadeiras - a.cadeiras)
      || (b.conv - a.conv)
      || a.nome.localeCompare(b.nome, 'pt-BR');
}

function sortBy(col) {
  var def = COLS.find(function (c) { return c.k === col; });
  if (!def || !def.sortavel) { return; }
  if (_sortCol === col) { _sortDesc = !_sortDesc; }
  else { _sortCol = col; _sortDesc = true; }
  renderRanking();
}

function thHtml(c) {
  var extra = (c.num ? ' num' : '') + (c.cls ? ' ' + c.cls : '');
  if (!c.sortavel) {
    return '<th class="cad-th' + extra + '" style="cursor:default">' + esc(c.rot) + '</th>';
  }
  var ativo = _sortCol === c.k;
  var arr   = ativo ? (_sortDesc ? '▼' : '▲') : '↕';
  return '<th class="cad-th' + extra + (ativo ? ' active' : '') + '"' +
    ' tabindex="0" role="button" aria-label="Ordenar por ' + esc(c.rot) + '"' +
    ' onclick="sortBy(\'' + c.k + '\')"' +
    ' onkeydown="if(event.key===\'Enter\'||event.key===\' \'){event.preventDefault();sortBy(\'' + c.k + '\');}">' +
    esc(c.rot) + '<span class="arr">' + arr + '</span></th>';
}

function renderRanking() {
  var R = (DADOS.ranking || []).slice();
  var thead = document.getElementById('cad-thead');
  var tbody = document.getElementById('cad-tbody');

  thead.innerHTML = '<tr>' + COLS.map(thHtml).join('') + '</tr>';

  if (!R.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="cad-vazio">Sem vendedores no período</td></tr>';
    document.getElementById('cad-badge').textContent = '—';
    return;
  }

  // Posição fixa por desempenho, calculada antes de qualquer ordenação.
  var podio = {};
  R.slice().sort(ordemDePodio).forEach(function (d, i) { podio[d.nome] = i + 1; });

  // Medalha só para quem de fato vendeu. Num período sem venda nenhuma
  // (time inteiro zerado) o desempate cairia no nome e daria ouro a quem
  // não vendeu nada — o pódio viraria ordem alfabética disfarçada.
  var temMedalha = function (d, pos) { return pos <= 3 && (d.cadeiras || 0) > 0; };

  // Empate na coluna ordenada cai no MESMO critério do pódio. Sem isso a
  // ordem das linhas fica na ordem em que o banco devolveu e a tabela
  // discorda das medalhas — dois vendedores com 600 cadeiras apareciam
  // com a prata em cima e o ouro embaixo.
  var exibidas = R.sort(function (a, b) {
    if (_sortCol === 'nome') {
      var cmp = String(a.nome).localeCompare(String(b.nome), 'pt-BR');
      return _sortDesc ? -cmp : cmp;
    }
    var va = a[_sortCol], vb = b[_sortCol];
    va = (va === null || va === undefined) ? -Infinity : va;
    vb = (vb === null || vb === undefined) ? -Infinity : vb;
    if (va === vb) { return ordemDePodio(a, b); }
    return _sortDesc ? vb - va : va - vb;
  });

  var maxL = Math.max.apply(null, R.map(function (d) { return d.leads || 0; }).concat([1]));
  var maxC = Math.max.apply(null, R.map(function (d) { return d.cadeiras || 0; }).concat([1]));

  tbody.innerHTML = exibidas.map(function (d) {
    var pos = podio[d.nome];
    var rc  = !temMedalha(d, pos) ? 'rn' : (pos === 1 ? 'r1' : pos === 2 ? 'r2' : 'r3');
    var barra = function (v, max, cls) {
      return '<div class="cad-cellbar">' +
        '<div class="cad-cellbar-track"><div class="cad-cellbar-fill ' + cls + '" style="width:' +
          Math.max(2, Math.round((v || 0) / max * 100)) + '%"></div></div>' +
        '<span class="cad-cellbar-val ' + cls + '">' + fNum(v || 0) + '</span></div>';
    };
    return '<tr>' +
      '<td><span class="cad-rank ' + rc + '">' + pos + '</span></td>' +
      '<td><div class="cad-name"><span class="cad-avatar">' + esc(iniciais(d.nome)) + '</span>' +
        '<span class="cad-name-txt">' + esc(d.nome) + '</span></div></td>' +
      '<td class="num">' + barra(d.leads, maxL, 'leads') + '</td>' +
      '<td class="num">' + barra(d.cadeiras, maxC, 'cad') + '</td>' +
      '<td class="num"><span class="cad-conv ' + classeConv(d.conv || 0) + '">' + fPct2(d.conv) + '</span></td>' +
      '</tr>';
  }).join('');

  var t = DADOS.totais || { leads: 0, cadeiras: 0, conv: 0 };
  tbody.innerHTML += '<tr class="total">' +
    '<td colspan="2">TOTAL DO TIME</td>' +
    '<td class="num">' + fNum(t.leads) + '</td>' +
    '<td class="num">' + fNum(t.cadeiras) + '</td>' +
    '<td class="num"><span class="cad-conv ' + classeConv(t.conv || 0) + '">' + fPct2(t.conv) + '</span></td>' +
    '</tr>';

  document.getElementById('cad-badge').textContent =
    R.length + (R.length === 1 ? ' vendedor' : ' vendedores');
}
</script>

<script>// ── 3. Escala de eixo ─────────────────────────────────────────
// Topo em valor "redondo": testa 4 e 5 divisões e fica com a que
// menos sobra acima do maior valor. Mesma função das outras abas.
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

function primeiroNome(n) { return String(n || '').trim().split(/\s+/)[0] || '—'; }

// Ano-semana ISO (Monday first day, semana 1 = a que tem a 1ª 5ª-feira do
// ano) — mesma convenção de YEARWEEK(col, 1) usada no backend.
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

// O último período da série está fechado? Só é parcial quando é o
// período corrente (mês/semana/dia) E ele ainda não terminou.
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

// ── 4. Performance: 2 barras agrupadas + linha de conversão ───
//
// Eixo Y DUPLO: contagens à esquerda, conversão % à direita. Sem o
// segundo eixo a linha de conversão (10-15) ficaria colada no chão
// de um eixo que vai a 8.000.
function renderPerf() {
  var R   = DADOS.ranking || [];
  var svg = document.getElementById('svg-perf');
  var n   = R.length;

  if (!n) {
    svg.removeAttribute('viewBox');
    svg.style.height = '80px';
    svg.innerHTML = '<text x="50%" y="50%" text-anchor="middle" class="cad-axis-txt">Sem dados no período</text>';
    document.getElementById('desc-perf').textContent = 'Sem dados no período selecionado.';
    return;
  }

  var W = document.getElementById('wrap-perf').clientWidth || 760;
  var H = 300, padL = 54, padR = 58, padT = 26, padB = 46;
  var plotW = Math.max(80, W - padL - padR), plotH = H - padT - padB;
  var base  = padT + plotH;

  // Escala de banda: cada vendedor ocupa uma faixa, o par de barras
  // fica centrado nela.
  var bw   = plotW / n;
  var xc   = function (i) { return padL + bw * (i + 0.5); };
  var barW = Math.max(6, Math.min(46, bw * 0.24));
  var gap  = Math.max(2, Math.round(barW * 0.18));
  var grupo = barW * 2 + gap;

  var maxBar  = 0, maxConv = 0;
  R.forEach(function (d) {
    maxBar  = Math.max(maxBar, d.leads || 0, d.cadeiras || 0);
    maxConv = Math.max(maxConv, d.conv || 0);
  });
  var escB = escalaEixo(maxBar);
  var escC = escalaEixo(maxConv);
  var yB = function (v) { return base - ((v || 0) / escB.topo) * plotH; };
  var yC = function (v) { return base - ((v || 0) / escC.topo) * plotH; };

  var s = '';

  // Grade única, com os DOIS rótulos no mesmo loop — é o que deixa
  // claro que as duas escalas dividem as mesmas linhas.
  var ticks = Math.max(escB.ticks, escC.ticks);
  for (var t = 0; t <= ticks; t++) {
    var f = t / ticks, gy = base - f * plotH;
    s += '<line class="cad-grid-line" x1="' + padL + '" y1="' + gy + '" x2="' + (padL + plotW) + '" y2="' + gy + '"/>';
    s += '<text class="cad-axis-txt" x="' + (padL - 8) + '" y="' + (gy + 3.5) + '" text-anchor="end">' + kNum(escB.topo * f) + '</text>';
    s += '<text class="cad-axis-txt right" x="' + (padL + plotW + 8) + '" y="' + (gy + 3.5) + '" text-anchor="start">' + (escC.topo * f).toFixed(0) + '%</text>';
  }

  var pts = [];
  var rotulosBarra = [];   // {x, y} dos rótulos já desenhados, para anticolisão

  R.forEach(function (d, i) {
    var cx = xc(i), x0 = cx - grupo / 2;

    [['leads', 'cad-bar-leads', x0], ['cadeiras', 'cad-bar-cad', x0 + barW + gap]].forEach(function (par) {
      var v = d[par[0]] || 0;
      if (v <= 0) { return; }
      var yt = yB(v), h = Math.max(1, base - yt);
      s += '<rect class="' + par[1] + '" x="' + par[2] + '" y="' + (base - h) + '" width="' + barW + '" height="' + h + '" rx="2"/>';
      if (barW >= 18) {
        var lx = par[2] + barW / 2, ly = base - h - 5;
        rotulosBarra.push({ x: lx, y: ly });
        s += '<text class="cad-plabel ' + (par[0] === 'leads' ? 'leads' : 'cad') + '" x="' + lx +
             '" y="' + ly + '" text-anchor="middle">' + kNum(v) + '</text>';
      }
    });

    pts.push([cx, yC(d.conv || 0), d.conv || 0]);
    s += '<text class="cad-axis-nome" x="' + cx + '" y="' + (base + 20) + '" text-anchor="middle">' + esc(primeiroNome(d.nome)) + '</text>';
  });

  if (pts.length > 1) {
    s += '<path class="cad-line-conv" d="' + pts.map(function (p, i) { return (i ? 'L' : 'M') + p[0] + ',' + p[1]; }).join('') + '"/>';
  }
  // O rótulo da conversão fica acima do ponto por padrão, mas quando a
  // barra encosta no topo do eixo os dois números caem na mesma altura e
  // se sobrepõem — aí o da conversão desce para baixo do ponto.
  pts.forEach(function (p) {
    s += '<circle class="cad-dot-conv" cx="' + p[0] + '" cy="' + p[1] + '" r="4.5"/>';
    var acima  = p[1] - 11;
    var colide = rotulosBarra.some(function (r) {
      return Math.abs(r.x - p[0]) < 34 && Math.abs(r.y - acima) < 11;
    });
    var ly = colide ? p[1] + 17 : acima;
    s += '<text class="cad-plabel conv" x="' + p[0] + '" y="' + ly + '" text-anchor="middle">' + fPct2(p[2]) + '</text>';
  });

  svg.style.height = H + 'px';
  svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
  svg.innerHTML = s;

  document.getElementById('desc-perf').textContent =
    'Por vendedor: ' + R.map(function (d) {
      return d.nome + ' com ' + fNum(d.leads) + ' leads qualificados, ' + fNum(d.cadeiras) + ' cadeiras e ' + fPct2(d.conv) + ' de conversão';
    }).join('; ') + '. Os mesmos números estão na tabela de ranking acima.';
}
</script>

<script>// ── 5. Cadeiras ao longo do tempo ─────────────────────────────
function renderTempo() {
  var T   = DADOS.tempo || [];
  var svg = document.getElementById('svg-tempo');
  var n   = T.length;

  if (!n) {
    svg.removeAttribute('viewBox');
    svg.style.height = '80px';
    svg.innerHTML = '<text x="50%" y="50%" text-anchor="middle" class="cad-axis-txt">Sem cadeiras vendidas no período</text>';
    document.getElementById('desc-tempo').textContent = 'Sem cadeiras vendidas no período selecionado.';
    return;
  }

  var W = document.getElementById('wrap-tempo').clientWidth || 520;
  var H = 260, padL = 44, padR = 16, padT = 24;

  // Anticolisão derivada da largura do rótulo (Jan/26 tem 6 chars).
  var plotW0 = Math.max(60, W - padL - padR);
  var bw0 = plotW0 / n;
  var maxChars = T.reduce(function (m, d) { return Math.max(m, String(d.mes).length); }, 0);
  var largRot = maxChars * 6.2 + 6;
  var girar = bw0 < largRot;
  var passo = Math.max(1, Math.ceil((girar ? 13 : largRot) / bw0));
  var padB = girar ? Math.min(78, Math.round(largRot * 0.64) + 16) : 30;
  var plotH = H - padT - padB, plotW = plotW0, base = padT + plotH;

  var parcial = ultimoPeriodoParcial(T, granularidadeAtual);

  var maxV = T.reduce(function (m, d) { return Math.max(m, d.cadeiras || 0); }, 0);
  var esc2 = escalaEixo(maxV);
  var y = function (v) { return base - ((v || 0) / esc2.topo) * plotH; };
  // Um ponto só não tem linha: fica centrado na área.
  var x = function (i) { return n === 1 ? padL + plotW / 2 : padL + (plotW / (n - 1)) * i; };

  var s = '';
  for (var t = 0; t <= esc2.ticks; t++) {
    var f = t / esc2.ticks, gy = base - f * plotH;
    s += '<line class="cad-grid-line" x1="' + padL + '" y1="' + gy + '" x2="' + (padL + plotW) + '" y2="' + gy + '"/>';
    s += '<text class="cad-axis-txt" x="' + (padL - 7) + '" y="' + (gy + 3.5) + '" text-anchor="end">' + kNum(esc2.topo * f) + '</text>';
  }

  if (n > 1) {
    var d1 = T.map(function (d, i) { return (i ? 'L' : 'M') + x(i) + ',' + y(d.cadeiras); }).join('');
    s += '<path class="cad-area" d="' + d1 + ' L' + x(n - 1) + ',' + base + ' L' + x(0) + ',' + base + ' Z"/>';
    s += '<path class="cad-line-tempo" d="' + d1 + '"/>';
  }

  T.forEach(function (d, i) {
    var px = x(i), py = y(d.cadeiras);
    var ehParcial = parcial && i === n - 1;
    s += '<circle class="cad-dot-tempo' + (ehParcial ? ' parcial' : '') + '" cx="' + px + '" cy="' + py + '" r="' + (ehParcial ? 4.5 : 3.5) + '"/>';
    if (d.cadeiras > 0) {
      s += '<text class="cad-plabel cad" x="' + px + '" y="' + (py - 10) + '" text-anchor="middle">' + fNum(d.cadeiras) + (ehParcial ? '*' : '') + '</text>';
    }
    if (i % passo !== 0 && i !== n - 1) { return; }
    var ly = girar ? (base + 14) : (H - 9);
    s += girar
      ? '<text class="cad-axis-txt" x="' + px + '" y="' + ly + '" text-anchor="end" transform="rotate(-40 ' + px + ' ' + ly + ')">' + esc(d.mes) + '</text>'
      : '<text class="cad-axis-txt" x="' + px + '" y="' + ly + '" text-anchor="middle">' + esc(d.mes) + '</text>';
  });

  svg.style.height = H + 'px';
  svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
  svg.innerHTML = s;

  var nota = document.getElementById('nota-tempo');
  if (nota) {
    if (parcial) {
      var unidade = granularidadeAtual === 'dia' ? 'hoje' : (granularidadeAtual === 'semana' ? 'a semana corrente' : 'o mês corrente');
      nota.textContent = granularidadeAtual === 'dia'
        ? '* Hoje ainda não fechou — conta só até agora.'
        : '* ' + T[n - 1].mes + ' ainda não fechou — ' + unidade + ' conta só até hoje.';
      nota.hidden = false;
    } else {
      nota.hidden = true;
    }
  }

  var soma = T.reduce(function (a, d) { return a + (d.cadeiras || 0); }, 0);
  document.getElementById('desc-tempo').textContent =
    'Cadeiras vendidas por mês, somando ' + fNum(soma) + ' no período: ' +
    T.map(function (d) { return d.mes + ' ' + fNum(d.cadeiras); }).join(', ') + '.';
}

// ── 6. Motivos de descarte ────────────────────────────────────
function renderDescartes() {
  var D   = (DADOS.descartes || []).slice(0, 10);
  var el  = document.getElementById('cad-desc');
  var sub = document.getElementById('desc-sub');

  if (!D.length) {
    el.innerHTML = '<div class="cad-vazio">Nenhum lead descartado no período</div>';
    sub.textContent = '—';
    return;
  }

  var max   = D.reduce(function (m, d) { return Math.max(m, d.qtd || 0); }, 0) || 1;
  var total = D.reduce(function (a, d) { return a + (d.qtd || 0); }, 0);
  sub.textContent = 'top ' + D.length + ' · ' + fNum(total) + ' leads';

  el.innerHTML = D.map(function (d, i) {
    return '<div class="cad-desc-row' + (i === 0 ? ' top' : '') + '">' +
      '<span class="cad-desc-label" title="' + esc(d.motivo) + '">' + esc(d.motivo) + '</span>' +
      '<div class="cad-desc-track"><div class="cad-desc-fill" style="width:' +
        Math.max(2, Math.round((d.qtd || 0) / max * 100)) + '%"></div></div>' +
      '<span class="cad-desc-val">' + fNum(d.qtd) + '</span>' +
      '<span class="cad-desc-pct">' + fPctRatio(d.qtd, total) + '</span>' +
      '</div>';
  }).join('');
}

function renderTudo() {
  renderRanking();
  renderPerf();
  renderTempo();
  renderDescartes();
  var lu = document.getElementById('last-updated');
  if (lu) { lu.textContent = 'Atualizado ' + ((DADOS.meta || {}).generated || ''); }
}
</script>

<script>
// ── 7. Filtros ────────────────────────────────────────────────
const YEARS  = ['2024', '2025', '2026', '2027'];
const MONTHS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

// A lista do time é fixa também aqui, mas ela NÃO é a autoridade: o
// controller intersecciona o que vier com a própria const, então um nome
// injetado na URL não amplia o recorte.
const EQUIPE = <?= json_encode($equipeCadeiras, JSON_UNESCAPED_UNICODE) ?>;

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
  mes: 'Cadeiras Vendidas ao Longo do Tempo (mensal)',
  semana: 'Cadeiras Vendidas ao Longo do Tempo (semanal)',
  dia: 'Cadeiras Vendidas ao Longo do Tempo (diária)',
};

function setGranularidade(g) {
  granularidadeAtual = g;
  document.querySelectorAll('#gran-toggle .gran-btn').forEach(b => b.classList.toggle('active', b.dataset.gran === g));
  document.getElementById('chart-title').textContent = TITULOS_GRAN[g];
  applyFilters();
}

// ── 8. Carga ──────────────────────────────────────────────────
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

// Os dois SVGs são redesenhados no resize: a geometria vem da largura
// medida do card, e o .cad-split colapsa em 1100px.
window.addEventListener('resize', function () {
  if (DADOS) { renderPerf(); renderTempo(); }
});

applyFilters();
</script>
