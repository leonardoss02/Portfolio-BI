<!-- ══════════════════════════════════════════════════════════════
     Comercial — Comparativo Anual (ano corrente × mesmo período do ano
     anterior, deslocado 12 meses com o fim limitado a hoje).

     Diferente da view 1 (painel de monitoramento), esta rola na
     vertical: é leitura analítica de cima para baixo.

     Esta aba NÃO mostra leads — são as 6 métricas do relatório de
     origem. Total de Leads e Qualificados só existem na Visão Geral.

     Todo o CSS vive no bloco .dash-comercial-yoy de css/bi-theme.css
     (seção 20d) — nada de <style> inline aqui.
     ══════════════════════════════════════════════════════════════ -->

<div class="dash-comercial-yoy">

<header class="header">
  <div class="header-left">
    <div class="logo">CO</div>
    <div>
      <div class="header-title">Comparativo Anual — Funil Comercial</div>
      <div class="header-sub">Ano atual × mesmo período do ano anterior</div>
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
      <a class="tab-switch-btn active" href="<?= $this->site ?>comercial/yoy<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Comparativo</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial/cadeiras<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Cadeiras</a>
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
        <span class="camp-icon"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
        <input id="form-input" class="camp-input" type="text" placeholder="Pesquisar form..." autocomplete="off" aria-label="Pesquisar form preenchido">
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
        <span class="camp-icon"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
        <input id="resp-input" class="camp-input" type="text" placeholder="Pesquisar responsável..." autocomplete="off" aria-label="Pesquisar responsável">
        <button class="camp-clear" id="resp-clear" aria-label="Limpar responsável" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-dropdown" id="resp-dropdown" role="listbox"></div>
    </div>
  </div>

  <button class="btn-apply" id="btn-apply">Aplicar</button>
  <button class="btn-clear" id="btn-clear">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'comercial-yoy' });</script>

<div class="yoy-periodbar" id="yoy-periodbar"></div>

<div id="loading-overlay" class="hidden"></div>
<div id="error-banner" role="alert"></div>

<main class="main" aria-label="Comparativo anual do funil comercial">

  <div class="yoy-tot-grid" id="yoy-totais"></div>

  <div class="yoy-bloco ev">
    <span class="yoy-tag ev">Canal Evento <span class="yoy-tag-sub">tipoLead OTR / OTW · *ingressos sem recorte de canal</span></span>
    <div class="yoy-kpi-grid ev" id="yoy-ev"></div>
  </div>

  <div class="yoy-bloco dg">
    <span class="yoy-tag dg">Canal Digital <span class="yoy-tag-sub">demais tipos de lead</span></span>
    <div class="yoy-kpi-grid dg" id="yoy-dg"></div>
  </div>

  <div class="yoy-bloco rt">
    <span class="yoy-tag rt">Retenção <span class="yoy-tag-sub">fase C1:WON</span></span>
    <div class="yoy-kpi-grid rt" id="yoy-rt"></div>
  </div>

  <div class="yoy-split">

    <article class="bi-card">
      <div class="bi-card-header">
        <div class="yoy-chart-head">
          <span class="bi-card-title">Ingressos YoY</span>
        </div>
        <div class="yoy-legend" id="leg-ing"></div>
      </div>
      <div class="bi-card-body">
        <div class="yoy-chart-wrap" id="wrap-ing">
          <svg id="svg-ing" role="img" aria-labelledby="desc-ing"></svg>
          <span id="desc-ing" class="sr-only"></span>
          <div class="yoy-tip" id="tip-ing"></div>
        </div>
        <div class="yoy-nota-parcial" id="nota-ing" hidden></div>
        <details class="yoy-datatable">
          <summary>Ver os mesmos dados em tabela</summary>
          <div class="yoy-tbl-scroll">
            <table class="yoy-tbl"><thead id="thead-ing"></thead><tbody id="tbody-ing"></tbody></table>
          </div>
        </details>
      </div>
    </article>

    <article class="bi-card">
      <div class="bi-card-header">
        <div class="yoy-chart-head">
          <span class="bi-card-title">Combos Totais YoY</span>
        </div>
        <div class="yoy-legend" id="leg-cb"></div>
      </div>
      <div class="bi-card-body">
        <div class="yoy-chart-wrap" id="wrap-cb">
          <svg id="svg-cb" role="img" aria-labelledby="desc-cb"></svg>
          <span id="desc-cb" class="sr-only"></span>
          <div class="yoy-tip" id="tip-cb"></div>
        </div>
        <div class="yoy-nota-parcial" id="nota-cb" hidden></div>
        <details class="yoy-datatable">
          <summary>Ver os mesmos dados em tabela</summary>
          <div class="yoy-tbl-scroll">
            <table class="yoy-tbl"><thead id="thead-cb"></thead><tbody id="tbody-cb"></tbody></table>
          </div>
        </details>
      </div>
    </article>

  </div>

</main>
</div>

<script>
// ══════════════════════════════════════════════════════════════
//  Comercial — Comparativo Anual · JS da view
//
//  Consome comercial/yoyDados, comercial/forms e comercial/responsaveis.
//
//  createMultiDropdown, createSearchDropdown, getDateInput,
//  clearDateInput, fNum e fBRL (que formata USD apesar do nome) vêm de
//  js/bi-dashboard.js, carregado no <head> pelo template e
//  COMPARTILHADO por 14 views — não alterar aquele arquivo por causa
//  desta.
//
//  Layout e interação validados antes em comercial-yoy-mock.html.
// ══════════════════════════════════════════════════════════════

const API       = '<?= $this->site ?>comercial/yoyDados';
const FORMS_URL = '<?= $this->site ?>comercial/forms';
const RESP_URL  = '<?= $this->site ?>comercial/responsaveis';

let DADOS = null;

// ── 1. Métricas: rótulo, formatador e régua de cor num lugar só ──
//
// 'bom' é a INTENÇÃO da métrica, não o sinal do número. Só distrato é
// 'down' (cair é bom) — sem isso uma queda de 30% nos distratos sairia
// em vermelho, que é o que o relatório de origem faz.
const M = {
  ing:   { rot: 'Ingressos',        fmt: fNum, bom: 'up',   money: false },
  cv:    { rot: 'Cons. Vendidas',   fmt: fNum, bom: 'up',   money: false },
  cr:    { rot: 'Cons. Realizadas', fmt: fNum, bom: 'up',   money: false },
  cb:    { rot: 'Combos',           fmt: fNum, bom: 'up',   money: false },
  dist:  { rot: 'Distratos',        fmt: fNum, bom: 'down', money: false },
  valor: { rot: 'Valor de Combos',  fmt: fBRL, bom: 'up',   money: true  }
};

const K_TOTAIS   = ['ing', 'cv', 'cr', 'cb', 'dist', 'valor'];
const K_EVENTO   = ['ing', 'cv', 'cr', 'cb', 'dist', 'valor'];
const K_DIGITAL  = ['cv', 'cr', 'cb', 'dist', 'valor'];
const K_RETENCAO = ['cb', 'valor'];

const MESES_UI = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];

function esc(s) {
  return String(s === null || s === undefined ? '' : s)
    .replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
}

function dataBr(iso) {
  if (!iso) { return '—'; }
  var p = String(iso).slice(0, 10).split('-');
  return p[2] + ' ' + MESES_UI[Number(p[1]) - 1];
}

// Rótulo curto para o gráfico: 9+ barras com o número cheio colidem.
function kNum(v) { return v >= 10000 ? (v / 1000).toFixed(0) + 'k' : fNum(Math.round(v)); }

// ── 2. Variação ───────────────────────────────────────────────
function deltaPct(atual, ly) {
  if (!ly) { return atual ? Infinity : 0; }
  return (atual - ly) / Math.abs(ly);
}

function fDelta(v) {
  if (!isFinite(v)) { return 'novo'; }
  var s = (Math.abs(v) * 100).toFixed(1).replace('.', ',') + '%';
  return (v > 0 ? '+' : v < 0 ? '−' : '') + s;
}

function classeDelta(v, bom) {
  if (!isFinite(v)) { return bom === 'down' ? 'neg' : 'pos'; }
  if (bom === 'neutral' || Math.abs(v) < 0.0005) { return 'flat'; }
  return ((bom === 'up') ? (v > 0) : (v < 0)) ? 'pos' : 'neg';
}

// A seta segue o NÚMERO; só a cor segue a intenção da métrica.
function chipDelta(atual, ly, bom) {
  var v   = deltaPct(atual, ly);
  var cl  = classeDelta(v, bom);
  var arw = !isFinite(v) ? '▲' : (Math.abs(v) < 0.0005 ? '=' : (v > 0 ? '▲' : '▼'));
  return '<span class="yoy-delta ' + cl + '"><span class="arw">' + arw + '</span>' + fDelta(v) + '</span>';
}

// ── Mês parcial ───────────────────────────────────────────────
//
// O fim do período é limitado a hoje, então o último mês quase sempre
// está pela metade. Os DOIS anos são cortados no mesmo dia, e a
// comparação continua justa — o que engana é a barra curta, que lê como
// queda. Daí a barra esmaecida e a nota abaixo do gráfico.
function ultimoDiaDoMesJs(y, m) {
  return new Date(y, m, 0).getDate();
}

function mesParcial(meta) {
  var fim = (meta.periodo_atual || {}).fim;
  if (!fim) { return null; }
  var y = +fim.slice(0, 4), m = +fim.slice(5, 7), d = +fim.slice(8, 10);
  if (d >= ultimoDiaDoMesJs(y, m)) { return null; }
  return { rotulo: MESES_UI[m - 1], dia: dataBr(fim), diaLy: dataBr((meta.periodo_ly || {}).fim) };
}

// ── 3. Barra de período ───────────────────────────────────────
function renderPeriodo(meta) {
  var pa = meta.periodo_atual, pl = meta.periodo_ly;
  document.getElementById('yoy-periodbar').innerHTML =
    '<span class="yoy-per now"><span class="yoy-per-dot"></span>' +
      '<span class="yoy-per-year">' + esc(meta.ano_atual) + '</span>' +
      '<span class="yoy-per-range">' + dataBr(pa.inicio) + ' – ' + dataBr(pa.fim) + '</span></span>' +
    '<span class="yoy-per-vs">versus</span>' +
    '<span class="yoy-per ly"><span class="yoy-per-dot"></span>' +
      '<span class="yoy-per-year">' + esc(meta.ano_ly) + '</span>' +
      '<span class="yoy-per-range">' + dataBr(pl.inicio) + ' – ' + dataBr(pl.fim) + '</span></span>' +
    '<span class="yoy-per-note">O ano anterior é sempre o mesmo intervalo deslocado 12 meses.</span>';
}

// ── 4. Tiles e KPIs ───────────────────────────────────────────
function tileHtml(k, a, l) {
  var d = M[k];
  return '<div class="yoy-tile m-' + k + '">' +
    '<span class="yoy-tile-label">' + esc(d.rot) + '</span>' +
    '<span class="yoy-tile-val' + (d.money ? ' is-money' : '') + '">' + esc(d.fmt(a[k])) + '</span>' +
    '<span class="yoy-tile-foot">' +
      '<span class="yoy-tile-ly">LY <b>' + esc(d.fmt(l[k])) + '</b></span>' +
      chipDelta(a[k], l[k], d.bom) +
    '</span></div>';
}

function kpiHtml(k, a, l, nota) {
  var d = M[k];
  var marca = nota ? '<i class="yoy-nota" title="' + esc(nota) + '">*</i>' : '';
  return '<div class="yoy-kpi m-' + k + '">' +
    '<span class="yoy-kpi-label">' + esc(d.rot) + marca + '</span>' +
    '<span class="yoy-kpi-val' + (d.money ? ' is-money' : '') + '">' + esc(d.fmt(a[k])) + '</span>' +
    '<span class="yoy-kpi-foot">' +
      '<span class="yoy-kpi-ly">LY <b>' + esc(d.fmt(l[k])) + '</b></span>' +
      chipDelta(a[k], l[k], d.bom) +
    '</span></div>';
}

function renderTiles(a, l) {
  document.getElementById('yoy-totais').innerHTML = K_TOTAIS.map(function (k) { return tileHtml(k, a, l); }).join('');
}

function renderKpis(elId, keys, a, l, notas) {
  notas = notas || {};
  document.getElementById(elId).innerHTML = keys.map(function (k) { return kpiHtml(k, a, l, notas[k]); }).join('');
}

// ── 5. Gráfico: barra (ano atual) + linha tracejada (ano anterior) ──
//
// SVG à mão: o projeto não carrega biblioteca de gráfico.

// Topo do eixo em valor "redondo". Testa 4 e 5 divisões e fica com a
// que MENOS sobra acima do maior valor.
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

const GRAFICOS = [
  { k: 'ing', rot: 'Ingressos', svgId: 'svg-ing', wrapId: 'wrap-ing', tipId: 'tip-ing',
    descId: 'desc-ing', theadId: 'thead-ing', tbodyId: 'tbody-ing', legId: 'leg-ing' },
  { k: 'cb',  rot: 'Combos',    svgId: 'svg-cb',  wrapId: 'wrap-cb',  tipId: 'tip-cb',
    descId: 'desc-cb',  theadId: 'thead-cb',  tbodyId: 'tbody-cb',  legId: 'leg-cb' }
];

// Geometria do último desenho de cada gráfico, consumida pelo tooltip.
const GEO = {};

function renderLegenda(cfg) {
  var m = DADOS.meta;
  document.getElementById(cfg.legId).innerHTML =
    '<span class="yoy-legend-item"><i class="yoy-legend-swatch now"></i>' + esc(m.ano_atual) + '</span>' +
    '<span class="yoy-legend-item"><i class="yoy-legend-swatch ly"></i>' + esc(m.ano_ly) + '</span>';
}

function renderGrafico(cfg) {
  var S     = DADOS.mensal;
  var meses = S.meses || [];
  var atual = (S.atual || {})[cfg.k] || [];
  var ly    = (S.ly    || {})[cfg.k] || [];
  var svg   = document.getElementById(cfg.svgId);
  var n     = meses.length;

  if (!n) {
    svg.removeAttribute('viewBox');
    svg.innerHTML = '<text x="50%" y="50%" text-anchor="middle" class="yoy-axis-txt">Sem dados no período</text>';
    document.getElementById(cfg.descId).textContent = 'Sem dados no período selecionado.';
    GEO[cfg.k] = null;
    return;
  }

  // clientWidth pode ser 0 na primeira pintura (card ainda sem layout).
  var W = document.getElementById(cfg.wrapId).clientWidth || 560;
  var H = 260, padL = 44, padR = 14, padT = 22;

  // Anticolisão do eixo X, derivada da LARGURA DO RÓTULO — não de um
  // limiar fixo de banda. "Jan/26" (janela multi-ano) ocupa o dobro de
  // "Jan", e um limiar único deixava os dois casos errados: com 36 meses
  // os rótulos horizontais encavalavam antes de a regra disparar.
  var plotW0   = Math.max(60, W - padL - padR);
  var bw0      = plotW0 / n;
  var maxChars = meses.reduce(function (m, x) { return Math.max(m, String(x).length); }, 0);
  var largRot  = maxChars * 6.2 + 6;          // 10px em fonte monoespaçada
  var girar    = bw0 < largRot;               // não cabe deitado → gira
  // Girado a -40°, cada rótulo precisa de ~13px de banda; deitado, da
  // largura inteira do texto.
  var passo    = Math.max(1, Math.ceil((girar ? 13 : largRot) / bw0));

  // Rótulo girado projeta ~sen(40°) da própria largura na vertical.
  var padB  = girar ? Math.min(78, Math.round(largRot * 0.64) + 16) : 30;
  var plotH = H - padT - padB;
  var plotW = plotW0;
  var base  = padT + plotH;

  // Escala de BANDA: o mês ocupa uma faixa, a barra fica no centro dela.
  var bw = plotW / n;
  var xc = function (i) { return padL + bw * (i + 0.5); };
  var barW = Math.max(3, Math.min(26, bw * 0.5));

  var parcial = mesParcial(DADOS.meta);

  var maxV = 0;
  for (var i = 0; i < n; i++) { maxV = Math.max(maxV, atual[i] || 0, ly[i] || 0); }
  var esc2 = escalaEixo(maxV);
  var y = function (v) { return padT + plotH - ((v || 0) / esc2.topo) * plotH; };

  var s = '';

  // Grade + eixo Y num gutter próprio à esquerda (a barra ocuparia o
  // espaço se o número ficasse sobre a linha, como na view 1).
  for (var t = 0; t <= esc2.ticks; t++) {
    var gv = esc2.topo * t / esc2.ticks, gy = y(gv);
    s += '<line class="yoy-grid-line" x1="' + padL + '" y1="' + gy + '" x2="' + (padL + plotW) + '" y2="' + gy + '"/>';
    s += '<text class="yoy-axis-txt" x="' + (padL - 7) + '" y="' + (gy + 3.5) + '" text-anchor="end">' + kNum(gv) + '</text>';
  }

  // Barras do ano atual. Altura mínima de 1px: uma barra de valor 1 num
  // eixo de topo 400 desapareceria por completo.
  var mostraRotulo = bw >= 24;
  for (var i2 = 0; i2 < n; i2++) {
    var v = atual[i2] || 0;
    if (v > 0) {
      var yt = y(v), h = Math.max(1, base - yt);
      var clsBar = 'yoy-bar' + ((parcial && i2 === n - 1) ? ' parcial' : '');
      s += '<rect class="' + clsBar + '" x="' + (xc(i2) - barW / 2) + '" y="' + (base - h) + '" width="' + barW + '" height="' + h + '" rx="2"/>';
      if (mostraRotulo) {
        s += '<text class="yoy-plabel" x="' + xc(i2) + '" y="' + (base - h - 5) + '" text-anchor="middle">' + kNum(v) + '</text>';
      }
    }
  }

  // Linha do ano anterior + marcadores. Rótulo de valor NÃO vai aqui:
  // cairia sobre o preenchimento da barra. Fica no tooltip e na tabela.
  if (n > 1) {
    var d = '';
    for (var i3 = 0; i3 < n; i3++) { d += (i3 ? 'L' : 'M') + xc(i3) + ',' + y(ly[i3]); }
    s += '<path class="yoy-line-ly" d="' + d + '"/>';
  }
  for (var i4 = 0; i4 < n; i4++) {
    s += '<circle class="yoy-dot-ly" cx="' + xc(i4) + '" cy="' + y(ly[i4]) + '" r="3.5"/>';
  }

  // Rótulos do eixo X. O último mês é sempre rotulado (é a leitura mais
  // procurada do gráfico), mesmo quando o desbaste o pularia.
  for (var i5 = 0; i5 < n; i5++) {
    if (i5 % passo !== 0 && i5 !== n - 1) { continue; }
    // ...mas não se ele fosse colidir com o rótulo anterior já desenhado.
    if (i5 === n - 1 && passo > 1 && ((n - 1) % passo !== 0) && (n - 1) - (Math.floor((n - 1) / passo) * passo) < passo / 2) { continue; }
    var lx = xc(i5), lyy = girar ? (base + 14) : (H - 9);
    s += girar
      ? '<text class="yoy-axis-txt" x="' + lx + '" y="' + lyy + '" text-anchor="end" transform="rotate(-40 ' + lx + ' ' + lyy + ')">' + esc(meses[i5]) + '</text>'
      : '<text class="yoy-axis-txt" x="' + lx + '" y="' + lyy + '" text-anchor="middle">' + esc(meses[i5]) + ((parcial && i5 === n - 1) ? '*' : '') + '</text>';
  }

  s += '<line class="yoy-crosshair" id="cross-' + cfg.k + '" x1="0" y1="' + padT + '" x2="0" y2="' + base + '" style="display:none"/>';
  s += '<rect id="hit-' + cfg.k + '" x="' + padL + '" y="' + padT + '" width="' + plotW + '" height="' + plotH + '" fill="transparent"/>';

  svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
  svg.innerHTML = s;

  GEO[cfg.k] = { W: W, H: H, padL: padL, bw: bw, n: n, base: base, padT: padT, escala: W ? 1 : 1 };

  var somaA = atual.reduce(function (x, v2) { return x + (v2 || 0); }, 0);
  var somaL = ly.reduce(function (x, v2) { return x + (v2 || 0); }, 0);
  document.getElementById(cfg.descId).textContent =
    cfg.rot + ' por mês: ' + DADOS.meta.ano_atual + ' soma ' + fNum(somaA) +
    ', ' + DADOS.meta.ano_ly + ' soma ' + fNum(somaL) + '. Os valores mês a mês estão na tabela abaixo do gráfico.';

  var nota = document.getElementById('nota-' + cfg.k);
  if (nota) {
    if (parcial) {
      nota.innerHTML = '* ' + esc(parcial.rotulo) + ' ainda não fechou: vai só até ' + esc(parcial.dia)
        + ' em ' + esc(DADOS.meta.ano_atual) + ' e até ' + esc(parcial.diaLy) + ' em ' + esc(DADOS.meta.ano_ly)
        + '. A comparação do mês é justa; o que a barra curta não mostra é o resto do mês.';
      nota.hidden = false;
    } else {
      nota.hidden = true;
    }
  }

  ligarTooltip(cfg);
  renderTabelaGrafico(cfg);
}

function renderTabelaGrafico(cfg) {
  var S = DADOS.mensal, m = DADOS.meta;
  var meses = S.meses || [];
  var atual = (S.atual || {})[cfg.k] || [], ly = (S.ly || {})[cfg.k] || [];
  document.getElementById(cfg.theadId).innerHTML =
    '<tr><th class="dim">Ano</th>' + meses.map(function (x) { return '<th>' + esc(x) + '</th>'; }).join('') + '<th>Total</th></tr>';
  var linha = function (rot, arr, cls) {
    var soma = arr.reduce(function (a, v) { return a + (v || 0); }, 0);
    return '<tr' + (cls ? ' class="' + cls + '"' : '') + '><td class="dim">' + esc(rot) + '</td>' +
      arr.map(function (v) { return '<td>' + fNum(v || 0) + '</td>'; }).join('') +
      '<td>' + fNum(soma) + '</td></tr>';
  };
  document.getElementById(cfg.tbodyId).innerHTML = linha(m.ano_atual, atual, '') + linha(m.ano_ly, ly, 'r-ly');
}

// ── 6. Tooltip do gráfico ─────────────────────────────────────
//
// Um listener no wrap (não por barra): com 36 meses × 2 séries seriam
// 72 alvos. O índice do mês vem da matemática da banda.
function ligarTooltip(cfg) {
  var wrap = document.getElementById(cfg.wrapId);
  var svg  = document.getElementById(cfg.svgId);
  var tip  = document.getElementById(cfg.tipId);

  if (wrap.dataset.tipOn === '1') { return; }   // um listener por card, não por redesenho
  wrap.dataset.tipOn = '1';

  wrap.addEventListener('mousemove', function (ev) {
    var g = GEO[cfg.k];
    if (!g) { return; }

    var r  = wrap.getBoundingClientRect();
    // O SVG é desenhado no viewBox da largura medida e escalado para
    // 100% — converte px de tela para px do viewBox.
    var k  = g.W / (r.width || g.W);
    var px = (ev.clientX - r.left) * k;

    // floor, não round: em escala de banda o cursor pertence à banda em
    // que está, não ao ponto mais próximo.
    var i = Math.floor((px - g.padL) / g.bw);
    if (i < 0 || i >= g.n) { tip.classList.remove('on'); ocultarCross(cfg); return; }

    var S = DADOS.mensal;
    var a = (S.atual[cfg.k] || [])[i] || 0;
    var l = (S.ly[cfg.k] || [])[i] || 0;
    var m = DADOS.meta;

    tip.innerHTML =
      '<div class="yoy-tip-title">' + esc(S.meses[i]) + ' · ' + esc(cfg.rot) + '</div>' +
      '<div class="yoy-tip-row"><span class="yoy-tip-key"><i class="yoy-tip-dot now"></i>' + esc(m.ano_atual) + '</span>' +
        '<span class="yoy-tip-val">' + fNum(a) + '</span></div>' +
      '<div class="yoy-tip-row"><span class="yoy-tip-key"><i class="yoy-tip-dot ly"></i>' + esc(m.ano_ly) + '</span>' +
        '<span class="yoy-tip-val">' + fNum(l) + '</span></div>' +
      '<div class="yoy-tip-row yoy-tip-delta">' + chipDelta(a, l, M[cfg.k] ? M[cfg.k].bom : 'up') + '</div>';

    tip.classList.add('on');

    // Clampa dentro do card para o tooltip não vazar nas extremidades.
    var tw = tip.offsetWidth, th = tip.offsetHeight;
    var cxTela = (g.padL + g.bw * (i + 0.5)) / k;
    var left = Math.min(Math.max(cxTela - tw / 2, 4), Math.max(4, r.width - tw - 4));
    var top  = Math.max(4, (ev.clientY - r.top) - th - 14);
    tip.style.left = left + 'px';
    tip.style.top  = top + 'px';

    var cross = svg.querySelector('#cross-' + cfg.k);
    if (cross) {
      var cx = g.padL + g.bw * (i + 0.5);
      cross.setAttribute('x1', cx); cross.setAttribute('x2', cx);
      cross.style.display = '';
    }
  });

  wrap.addEventListener('mouseleave', function () {
    tip.classList.remove('on');
    ocultarCross(cfg);
  });
}

function ocultarCross(cfg) {
  var cross = document.querySelector('#cross-' + cfg.k);
  if (cross) { cross.style.display = 'none'; }
}

// ── 7. Render completo ────────────────────────────────────────
function renderTudo() {
  var D = DADOS;
  renderPeriodo(D.meta);
  renderTiles(D.totais, D.totais_ly);
  renderKpis('yoy-ev', K_EVENTO,   D.evento,   D.evento_ly, {
    ing: 'Cadeiras vendidas no total, sem recorte por canal — no relatório de origem '
       + 'esta métrica e a do totalizador são a mesma medida. Por isso o número é '
       + 'igual ao de cima e não soma com o Digital.'
  });
  renderKpis('yoy-dg', K_DIGITAL,  D.digital,  D.digital_ly);
  renderKpis('yoy-rt', K_RETENCAO, D.retencao, D.retencao_ly);
  GRAFICOS.forEach(function (cfg) { renderLegenda(cfg); renderGrafico(cfg); });
  var lu = document.getElementById('last-updated');
  if (lu) { lu.textContent = 'Atualizado ' + (D.meta.generated || ''); }
}

// ── 8. Filtros ────────────────────────────────────────────────
const YEARS  = ['2024', '2025', '2026', '2027'];
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

function montaParams() {
  return {
    year:        yearDropdown.getValue(),
    months:      monthDropdown.getValue(),
    tipo_lead:   tipoDropdown.getValue(),
    form:        formDropdown.getValue(),
    responsavel: respDropdown.getValue(),
    data_inicio: getDateInput('data-inicio'),
    data_fim:    getDateInput('data-fim'),
  };
}

// ── 9. Carga ──────────────────────────────────────────────────
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
  const badge       = document.getElementById('filter-label-badge');
  const year        = yearDropdown.getValue();
  const months      = monthDropdown.getValue();
  const tipo        = tipoDropdown.getValue();
  const form        = formDropdown.getValue();
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

// Os dois SVGs são redesenhados no resize: a geometria é calculada a
// partir da largura medida do card, e o .yoy-split colapsa em 1200px.
window.addEventListener('resize', function () {
  if (DADOS) { GRAFICOS.forEach(renderGrafico); }
});

applyFilters();
</script>
