<!-- ══════════════════════════════════════════════════════════════
     Comercial — Time Comercial 1: vende consulta por DOIS canais com
     KPI próprio cada. Evento (Fernanda Matos, Priscila Rangel): cadeiras
     → consulta. Digital (Eduardo Pires, Larissa Campos): leads →
     consulta. "Consulta Vendida" é comum aos dois lados.

     Diferente das duas outras abas de time (Cadeiras: 1 time, 1
     tabela), aqui são 2 seções paralelas independentes — cada uma com
     seu ranking e seus 2 gráficos.

     Todo o CSS vive no bloco .dash-comercial-c1 de css/bi-theme.css
     (seção 20f) — nada de <style> inline aqui.
     ══════════════════════════════════════════════════════════════ -->

<div class="dash-comercial-c1">

<header class="header">
  <div class="header-left">
    <div class="logo">CO</div>
    <div>
      <div class="header-title">Time Comercial 1 — Painel de Performance</div>
      <div class="header-sub">Evento (cadeiras) · Digital (leads) · Consultas vendidas</div>
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
      <a class="tab-switch-btn active" href="<?= $this->site ?>comercial/comercial1<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Com. 1</a>
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
<script>initFiltersToggle({ key: 'comercial-comercial1' });</script>

<div id="loading-overlay" class="hidden"></div>
<div id="error-banner" role="alert"></div>

<main class="main" aria-label="Performance do time Comercial 1">

  <div class="c1-secao ev">
    <span class="c1-tag ev">Canal Evento <span class="c1-tag-sub">cadeiras → consulta</span></span>

    <article class="bi-card">
      <div class="bi-card-header">
        <span class="bi-card-title">Ranking — Evento</span>
      </div>
      <div class="bi-card-body">
        <table class="c1-tbl" id="tbl-ev">
          <thead>
            <tr>
              <th class="col-nome">Vendedor</th>
              <th>Cadeiras</th>
              <th>Cons. Vendidas</th>
              <th>Conversão</th>
            </tr>
          </thead>
          <tbody id="tbody-ev"></tbody>
        </table>
      </div>
    </article>

    <div class="c1-grid">
      <article class="bi-card">
        <div class="bi-card-header">
          <span class="bi-card-title">Cadeiras × Cons. Vendidas por Vendedor</span>
          <div class="c1-legend">
            <span class="c1-legend-item"><i class="c1-sw cad"></i>Cadeiras</span>
            <span class="c1-legend-item"><i class="c1-sw cv-ev"></i>Cons. Vendidas</span>
            <span class="c1-legend-item"><i class="c1-sw conv ev"></i>Conversão %</span>
          </div>
        </div>
        <div class="bi-card-body">
          <div class="c1-chart-wrap" id="wrap-perf-ev">
            <svg id="svg-perf-ev" role="img" aria-labelledby="desc-perf-ev"></svg>
            <span id="desc-perf-ev" class="sr-only"></span>
          </div>
        </div>
      </article>

      <article class="bi-card">
        <div class="bi-card-header">
          <span class="bi-card-title" id="chart-title-ev">Cadeiras × Cons. Vendidas ao Longo do Tempo</span>
          <div class="gran-toggle">
            <button class="gran-btn active" data-gran="mes"    onclick="setGranularidade('mes')">Mês</button>
            <button class="gran-btn"        data-gran="semana" onclick="setGranularidade('semana')">Semana</button>
            <button class="gran-btn"        data-gran="dia"    onclick="setGranularidade('dia')">Dia</button>
          </div>
          <div class="c1-legend">
            <span class="c1-legend-item"><i class="c1-sw primary ev"></i>Cadeiras</span>
            <span class="c1-legend-item"><i class="c1-sw secondary ev"></i>Cons. Vendidas</span>
          </div>
        </div>
        <div class="bi-card-body">
          <div class="c1-chart-wrap" id="wrap-tempo-ev">
            <svg id="svg-tempo-ev" role="img" aria-labelledby="desc-tempo-ev"></svg>
            <span id="desc-tempo-ev" class="sr-only"></span>
            <div class="c1-nota-parcial" id="nota-tempo-ev" hidden></div>
          </div>
        </div>
      </article>
    </div>
  </div>

  <div class="c1-secao dg">
    <span class="c1-tag dg">Canal Digital <span class="c1-tag-sub">leads → consulta</span></span>

    <article class="bi-card">
      <div class="bi-card-header">
        <span class="bi-card-title">Ranking — Digital</span>
      </div>
      <div class="bi-card-body">
        <table class="c1-tbl" id="tbl-dg">
          <thead>
            <tr>
              <th class="col-nome">Vendedor</th>
              <th>Leads Qualif.</th>
              <th>Cons. Vendidas</th>
              <th>Conversão</th>
            </tr>
          </thead>
          <tbody id="tbody-dg"></tbody>
        </table>
      </div>
    </article>

    <div class="c1-grid">
      <article class="bi-card">
        <div class="bi-card-header">
          <span class="bi-card-title">Leads × Cons. Vendidas por Vendedor</span>
          <div class="c1-legend">
            <span class="c1-legend-item"><i class="c1-sw lead"></i>Leads Qualif.</span>
            <span class="c1-legend-item"><i class="c1-sw cv-dg"></i>Cons. Vendidas</span>
            <span class="c1-legend-item"><i class="c1-sw conv dg"></i>Conversão %</span>
          </div>
        </div>
        <div class="bi-card-body">
          <div class="c1-chart-wrap" id="wrap-perf-dg">
            <svg id="svg-perf-dg" role="img" aria-labelledby="desc-perf-dg"></svg>
            <span id="desc-perf-dg" class="sr-only"></span>
          </div>
        </div>
      </article>

      <article class="bi-card">
        <div class="bi-card-header">
          <span class="bi-card-title" id="chart-title-dg">Leads × Cons. Vendidas ao Longo do Tempo</span>
          <div class="gran-toggle">
            <button class="gran-btn active" data-gran="mes"    onclick="setGranularidade('mes')">Mês</button>
            <button class="gran-btn"        data-gran="semana" onclick="setGranularidade('semana')">Semana</button>
            <button class="gran-btn"        data-gran="dia"    onclick="setGranularidade('dia')">Dia</button>
          </div>
          <div class="c1-legend">
            <span class="c1-legend-item"><i class="c1-sw primary dg"></i>Leads Qualif.</span>
            <span class="c1-legend-item"><i class="c1-sw secondary dg"></i>Cons. Vendidas</span>
          </div>
        </div>
        <div class="bi-card-body">
          <div class="c1-chart-wrap" id="wrap-tempo-dg">
            <svg id="svg-tempo-dg" role="img" aria-labelledby="desc-tempo-dg"></svg>
            <span id="desc-tempo-dg" class="sr-only"></span>
            <div class="c1-nota-parcial" id="nota-tempo-dg" hidden></div>
          </div>
        </div>
      </article>
    </div>
  </div>

</main>
</div>

<script>
// ══════════════════════════════════════════════════════════════
//  Comercial — Time Comercial 1 · JS da view
//
//  Consome comercial/comercial1Dados e comercial/forms.
//
//  createMultiDropdown, createSearchDropdown, getDateInput,
//  clearDateInput e fNum vêm de js/bi-dashboard.js, carregado no
//  <head> pelo template e COMPARTILHADO por 16 views — não alterar
//  aquele arquivo por causa desta.
//
//  Layout e interação validados antes em comercial-c1-mock.html.
// ══════════════════════════════════════════════════════════════

const API       = '<?= $this->site ?>comercial/comercial1Dados';
const FORMS_URL = '<?= $this->site ?>comercial/forms';

let DADOS = null;
let granularidadeAtual = 'mes';

// ── 1. Helpers ────────────────────────────────────────────────
function esc(s) {
  return String(s === null || s === undefined ? '' : s)
    .replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
}

function kNum(v) { return v >= 10000 ? (v / 1000).toFixed(1).replace('.', ',') + 'k' : fNum(Math.round(v)); }

// Conversão com CLAMP visual: denominador quase zero produz % de
// milhares (ex.: 5950,4%), que estoura a largura da célula e lê como
// bug de formatação em vez de "essa métrica não se aplica bem aqui".
// Acima de 999% mostra "999%+" com o valor exato no title.
function fConv(v) {
  v = v || 0;
  if (v > 999) { return '999%+'; }
  return v.toFixed(1).replace('.', ',') + '%';
}

function primeiroNome(n) { return String(n || '').trim().split(/\s+/)[0] || '—'; }

// ── 2. Rankings (2 linhas cada, sem sort — ver nota no CSS) ────
function tbodyRow(nome, colA, colB, conv) {
  return '<tr>' +
    '<td class="col-nome">' + esc(nome) + '</td>' +
    '<td>' + fNum(colA) + '</td>' +
    '<td>' + fNum(colB) + '</td>' +
    '<td title="' + conv.toFixed(2).replace('.', ',') + '%">' + fConv(conv) + '</td>' +
    '</tr>';
}

function renderRankingEvento(ranking) {
  var tbody = document.getElementById('tbody-ev');
  if (!ranking.length) {
    tbody.innerHTML = '<tr><td colspan="4" class="c1-vazio">Sem vendedores no período</td></tr>';
    return;
  }
  var rows = ranking.map(function (d) { return tbodyRow(d.nome, d.cad, d.cv, d.conv); }).join('');
  var totCad = ranking.reduce(function (a, d) { return a + (d.cad || 0); }, 0);
  var totCv  = ranking.reduce(function (a, d) { return a + (d.cv  || 0); }, 0);
  var totConv = totCad > 0 ? totCv / totCad * 100 : 0;
  rows += '<tr class="total"><td class="col-nome">TOTAL</td><td>' + fNum(totCad) + '</td><td>' + fNum(totCv) +
          '</td><td title="' + totConv.toFixed(2).replace('.', ',') + '%">' + fConv(totConv) + '</td></tr>';
  tbody.innerHTML = rows;
}

function renderRankingDigital(ranking) {
  var tbody = document.getElementById('tbody-dg');
  if (!ranking.length) {
    tbody.innerHTML = '<tr><td colspan="4" class="c1-vazio">Sem vendedores no período</td></tr>';
    return;
  }
  var rows = ranking.map(function (d) { return tbodyRow(d.nome, d.leads_dg, d.cv, d.conv); }).join('');
  var totLd = ranking.reduce(function (a, d) { return a + (d.leads_dg || 0); }, 0);
  var totCv = ranking.reduce(function (a, d) { return a + (d.cv || 0); }, 0);
  var totConv = totLd > 0 ? totCv / totLd * 100 : 0;
  rows += '<tr class="total"><td class="col-nome">TOTAL</td><td>' + fNum(totLd) + '</td><td>' + fNum(totCv) +
          '</td><td title="' + totConv.toFixed(2).replace('.', ',') + '%">' + fConv(totConv) + '</td></tr>';
  tbody.innerHTML = rows;
}

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

// ── 4. Performance: 2 barras agrupadas + linha de conversão ───
//
// Genérico para as duas seções: cfg diz quais campos/classes usar.
// Eixo Y DUPLO: contagens à esquerda, conversão % à direita — mesmo
// desenho da aba Cadeiras, com a linha de conversão clampada em 999%
// (Larissa/Digital tem denominador quase zero em outros cenários).
function renderPerf(cfg) {
  var R   = DADOS[cfg.lado].ranking || [];
  var svg = document.getElementById(cfg.svgId);
  var n   = R.length;

  if (!n) {
    svg.removeAttribute('viewBox');
    svg.style.height = '80px';
    svg.innerHTML = '<text x="50%" y="50%" text-anchor="middle" class="c1-axis-txt">Sem dados no período</text>';
    document.getElementById(cfg.descId).textContent = 'Sem dados no período selecionado.';
    return;
  }

  var W = document.getElementById(cfg.wrapId).clientWidth || 380;
  var H = 260, padL = 46, padR = 50, padT = 24, padB = 40;
  var plotW = Math.max(80, W - padL - padR), plotH = H - padT - padB;
  var base  = padT + plotH;

  var bw   = plotW / n;
  var xc   = function (i) { return padL + bw * (i + 0.5); };
  var barW = Math.max(8, Math.min(40, bw * 0.24));
  var gap  = Math.max(2, Math.round(barW * 0.18));
  var grupo = barW * 2 + gap;

  var maxBar = 0, maxConv = 0;
  R.forEach(function (d) {
    maxBar  = Math.max(maxBar, d[cfg.colA] || 0, d[cfg.colB] || 0);
    maxConv = Math.max(maxConv, Math.min(999, d.conv || 0));
  });
  var escB = escalaEixo(maxBar);
  var escC = escalaEixo(maxConv);
  var yB = function (v) { return base - ((v || 0) / escB.topo) * plotH; };
  var yC = function (v) { return base - (Math.min(999, v || 0) / escC.topo) * plotH; };

  var s = '';
  var ticks = Math.max(escB.ticks, escC.ticks);
  for (var t = 0; t <= ticks; t++) {
    var f = t / ticks, gy = base - f * plotH;
    s += '<line class="c1-grid-line" x1="' + padL + '" y1="' + gy + '" x2="' + (padL + plotW) + '" y2="' + gy + '"/>';
    s += '<text class="c1-axis-txt" x="' + (padL - 8) + '" y="' + (gy + 3.5) + '" text-anchor="end">' + kNum(escB.topo * f) + '</text>';
    // Digital tem conversão ~1-2%: toFixed(0) duplicava rótulos ("2%","2%").
    var _casasConv = escC.topo < 10 ? 1 : 0;
    s += '<text class="c1-axis-txt" x="' + (padL + plotW + 8) + '" y="' + (gy + 3.5) + '" text-anchor="start" fill="' + cfg.corConv + '">' + (escC.topo * f).toFixed(_casasConv).replace('.', ',') + '%</text>';
  }

  var pts = [];
  var rotulosBarra = [];
  R.forEach(function (d, i) {
    var cx = xc(i), x0 = cx - grupo / 2;
    [[cfg.colA, cfg.classA, x0, cfg.plabelA], [cfg.colB, cfg.classB, x0 + barW + gap, cfg.plabelB]].forEach(function (par) {
      var v = d[par[0]] || 0;
      if (v <= 0) { return; }
      var yt = yB(v), h = Math.max(1, base - yt);
      s += '<rect class="' + par[1] + '" x="' + par[2] + '" y="' + (base - h) + '" width="' + barW + '" height="' + h + '" rx="2"/>';
      if (barW >= 18) {
        var lx = par[2] + barW / 2, ly = base - h - 5;
        rotulosBarra.push({ x: lx, y: ly });
        s += '<text class="c1-plabel ' + par[3] + '" x="' + lx + '" y="' + ly + '" text-anchor="middle">' + kNum(v) + '</text>';
      }
    });
    pts.push([cx, yC(d.conv || 0), d.conv || 0]);
    s += '<text class="c1-axis-nome" x="' + cx + '" y="' + (base + 20) + '" text-anchor="middle">' + esc(primeiroNome(d.nome)) + '</text>';
  });

  if (pts.length > 1) {
    s += '<path class="c1-line-conv ' + cfg.sec + '" d="' + pts.map(function (p, i) { return (i ? 'L' : 'M') + p[0] + ',' + p[1]; }).join('') + '"/>';
  }
  pts.forEach(function (p) {
    s += '<circle class="c1-dot-conv ' + cfg.sec + '" cx="' + p[0] + '" cy="' + p[1] + '" r="4.5"/>';
    var acima  = p[1] - 11;
    var colide = rotulosBarra.some(function (r) { return Math.abs(r.x - p[0]) < 34 && Math.abs(r.y - acima) < 11; });
    var ly = colide ? p[1] + 17 : acima;
    s += '<text class="c1-plabel conv ' + cfg.sec + '" x="' + p[0] + '" y="' + ly + '" text-anchor="middle">' + fConv(p[2]) + '</text>';
  });

  svg.style.height = H + 'px';
  svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
  svg.innerHTML = s;

  document.getElementById(cfg.descId).textContent =
    'Por vendedor: ' + R.map(function (d) {
      return d.nome + ' com ' + fNum(d[cfg.colA]) + ' ' + cfg.rotA + ', ' + fNum(d[cfg.colB]) + ' consultas vendidas e ' + fConv(d.conv) + ' de conversão';
    }).join('; ') + '. Os mesmos números estão na tabela de ranking acima.';
}

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

// ── 5. Série mensal, EIXO DUPLO: métrica principal (cad ou
//      leads_dg) com área+linha cheia+rótulo, na escala própria à
//      esquerda; "Cons. Vendidas" sempre secundária, tracejada, sem
//      rótulo, na escala própria à direita. Sem o eixo duplo, cv
//      (máx. 218) fica colada no zero quando a principal chega a
//      4.077 (leads_dg no lado Digital) — ~19x de diferença.
function renderTempo(cfg) {
  var T   = DADOS[cfg.lado].tempo || [];
  var svg = document.getElementById(cfg.svgId);
  var n   = T.length;

  if (!n) {
    svg.removeAttribute('viewBox');
    svg.style.height = '80px';
    svg.innerHTML = '<text x="50%" y="50%" text-anchor="middle" class="c1-axis-txt">Sem dados no período</text>';
    document.getElementById(cfg.descId).textContent = 'Sem dados no período selecionado.';
    var n0 = document.getElementById(cfg.notaId); if (n0) { n0.hidden = true; }
    return;
  }

  var W = document.getElementById(cfg.wrapId).clientWidth || 520;
  var H = 260, padL = 44, padR = 46, padT = 24;

  var plotW0 = Math.max(60, W - padL - padR);
  var bw0 = plotW0 / n;
  var maxChars = T.reduce(function (m, d) { return Math.max(m, String(d.mes).length); }, 0);
  var largRot = maxChars * 6.2 + 6;
  var girar = bw0 < largRot;
  var passo = Math.max(1, Math.ceil((girar ? 13 : largRot) / bw0));
  var padB = girar ? Math.min(78, Math.round(largRot * 0.64) + 16) : 30;
  var plotH = H - padT - padB, plotW = plotW0, base = padT + plotH;

  var parcial = ultimoPeriodoParcial(T, granularidadeAtual);

  var maxA = T.reduce(function (m, d) { return Math.max(m, d[cfg.colA] || 0); }, 0);
  var maxB = T.reduce(function (m, d) { return Math.max(m, d[cfg.colB] || 0); }, 0);
  var escA = escalaEixo(maxA);
  var escB = escalaEixo(maxB);
  var yA = function (v) { return base - ((v || 0) / escA.topo) * plotH; };
  var yB = function (v) { return base - ((v || 0) / escB.topo) * plotH; };
  var x  = function (i) { return n === 1 ? padL + plotW / 2 : padL + (plotW / (n - 1)) * i; };

  var s = '';
  var ticks = Math.max(escA.ticks, escB.ticks);
  for (var t = 0; t <= ticks; t++) {
    var f = t / ticks, gy = base - f * plotH;
    s += '<line class="c1-grid-line" x1="' + padL + '" y1="' + gy + '" x2="' + (padL + plotW) + '" y2="' + gy + '"/>';
    s += '<text class="c1-axis-txt" x="' + (padL - 7) + '" y="' + (gy + 3.5) + '" text-anchor="end">' + kNum(escA.topo * f) + '</text>';
    s += '<text class="c1-axis-txt right ' + cfg.sec + '" x="' + (padL + plotW + 8) + '" y="' + (gy + 3.5) + '" text-anchor="start">' + kNum(escB.topo * f) + '</text>';
  }

  // Área + linha cheia da métrica principal, no eixo A (esquerda)
  if (n > 1) {
    var dA = T.map(function (d, i) { return (i ? 'L' : 'M') + x(i) + ',' + yA(d[cfg.colA]); }).join('');
    s += '<path class="c1-area-primary ' + cfg.sec + '" d="' + dA + ' L' + x(n - 1) + ',' + base + ' L' + x(0) + ',' + base + ' Z"/>';
    s += '<path class="c1-line-primary ' + cfg.sec + '" d="' + dA + '"/>';
  }
  T.forEach(function (d, i) {
    var px = x(i), py = yA(d[cfg.colA]);
    var ehParcial = parcial && i === n - 1;
    s += '<circle class="c1-dot-primary ' + cfg.sec + (ehParcial ? ' parcial' : '') + '" cx="' + px + '" cy="' + py + '" r="' + (ehParcial ? 4.5 : 3.5) + '"/>';
    if (d[cfg.colA] > 0) {
      s += '<text class="c1-plabel primary ' + cfg.sec + '" x="' + px + '" y="' + (py - 10) + '" text-anchor="middle">' + fNum(d[cfg.colA]) + (ehParcial ? '*' : '') + '</text>';
    }
  });

  // "Cons. Vendidas": sempre secundária aqui, tracejada, no eixo B
  // (direita), sem rótulo — o valor exato está na tabela de ranking.
  if (n > 1) {
    var dB = T.map(function (d, i) { return (i ? 'L' : 'M') + x(i) + ',' + yB(d[cfg.colB]); }).join('');
    s += '<path class="c1-line-secondary ' + cfg.sec + '" d="' + dB + '"/>';
  }
  T.forEach(function (d, i) {
    var px = x(i), py = yB(d[cfg.colB]);
    s += '<circle class="c1-dot-secondary ' + cfg.sec + '" cx="' + px + '" cy="' + py + '" r="3"/>';
  });

  for (var i5 = 0; i5 < n; i5++) {
    if (i5 % passo !== 0 && i5 !== n - 1) { continue; }
    var lx = x(i5), lyy = girar ? (base + 14) : (H - 9);
    s += girar
      ? '<text class="c1-axis-txt" x="' + lx + '" y="' + lyy + '" text-anchor="end" transform="rotate(-40 ' + lx + ' ' + lyy + ')">' + esc(T[i5].mes) + '</text>'
      : '<text class="c1-axis-txt" x="' + lx + '" y="' + lyy + '" text-anchor="middle">' + esc(T[i5].mes) + '</text>';
  }

  svg.style.height = H + 'px';
  svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
  svg.innerHTML = s;

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

  var somaA = T.reduce(function (a, d) { return a + (d[cfg.colA] || 0); }, 0);
  document.getElementById(cfg.descId).textContent =
    cfg.rotA + ' por mês, somando ' + fNum(somaA) + ' no período: ' +
    T.map(function (d) { return d.mes + ' ' + fNum(d[cfg.colA]); }).join(', ') + '.';
}

// ── 6. Configuração dos 4 gráficos (2 seções × 2 gráficos) ─────
const CFG_PERF_EV = {
  lado: 'evento', sec: 'ev', svgId: 'svg-perf-ev', wrapId: 'wrap-perf-ev', descId: 'desc-perf-ev',
  colA: 'cad', colB: 'cv', classA: 'c1-bar-cad', classB: 'c1-bar-cv-ev',
  plabelA: 'cad', plabelB: 'cv-ev', corConv: 'var(--c1-conv-ev)', rotA: 'cadeiras'
};
const CFG_PERF_DG = {
  lado: 'digital', sec: 'dg', svgId: 'svg-perf-dg', wrapId: 'wrap-perf-dg', descId: 'desc-perf-dg',
  colA: 'leads_dg', colB: 'cv', classA: 'c1-bar-lead', classB: 'c1-bar-cv-dg',
  plabelA: 'lead', plabelB: 'cv-dg', corConv: 'var(--c1-conv-dg)', rotA: 'leads qualificados'
};
// colA = métrica principal da seção (eixo esquerdo, área+linha+rótulo);
// colB = "Cons. Vendidas", sempre secundária (eixo direito, tracejada).
const CFG_TEMPO_EV = {
  lado: 'evento', sec: 'ev', svgId: 'svg-tempo-ev', wrapId: 'wrap-tempo-ev', descId: 'desc-tempo-ev', notaId: 'nota-tempo-ev',
  colA: 'cad', colB: 'cv', rotA: 'Cadeiras'
};
const CFG_TEMPO_DG = {
  lado: 'digital', sec: 'dg', svgId: 'svg-tempo-dg', wrapId: 'wrap-tempo-dg', descId: 'desc-tempo-dg', notaId: 'nota-tempo-dg',
  colA: 'leads_dg', colB: 'cv', rotA: 'Leads qualificados'
};

function renderTudo() {
  var D = DADOS;
  renderRankingEvento(D.evento.ranking);
  renderRankingDigital(D.digital.ranking);
  renderPerf(CFG_PERF_EV);
  renderPerf(CFG_PERF_DG);
  renderTempo(CFG_TEMPO_EV);
  renderTempo(CFG_TEMPO_DG);
  var lu = document.getElementById('last-updated');
  if (lu) { lu.textContent = 'Atualizado ' + ((D.meta || {}).generated || ''); }
}

// ── Filtros ───────────────────────────────────────────────────
const YEARS  = ['2024', '2025', '2026', '2027'];
const MONTHS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

// A lista do time é fixa também aqui, mas NÃO é a autoridade: o
// controller intersecciona o que vier com as próprias consts, então um
// nome injetado na URL não amplia o recorte.
const EQUIPE = <?= json_encode($equipeComercial1, JSON_UNESCAPED_UNICODE) ?>;

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
  mes:    { ev: 'Cadeiras × Cons. Vendidas ao Longo do Tempo (mensal)',   dg: 'Leads × Cons. Vendidas ao Longo do Tempo (mensal)' },
  semana: { ev: 'Cadeiras × Cons. Vendidas ao Longo do Tempo (semanal)',  dg: 'Leads × Cons. Vendidas ao Longo do Tempo (semanal)' },
  dia:    { ev: 'Cadeiras × Cons. Vendidas ao Longo do Tempo (diária)',   dg: 'Leads × Cons. Vendidas ao Longo do Tempo (diária)' },
};

function setGranularidade(g) {
  granularidadeAtual = g;
  document.querySelectorAll('.gran-toggle .gran-btn').forEach(b => b.classList.toggle('active', b.dataset.gran === g));
  document.getElementById('chart-title-ev').textContent = TITULOS_GRAN[g].ev;
  document.getElementById('chart-title-dg').textContent = TITULOS_GRAN[g].dg;
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
  document.getElementById('chart-title-ev').textContent = TITULOS_GRAN.mes.ev;
  document.getElementById('chart-title-dg').textContent = TITULOS_GRAN.mes.dg;
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

// Os 4 SVGs são redesenhados no resize: a geometria vem da largura
// medida do card, e o .c1-grid colapsa em 1100px.
window.addEventListener('resize', function () {
  if (DADOS) {
    renderPerf(CFG_PERF_EV); renderPerf(CFG_PERF_DG);
    renderTempo(CFG_TEMPO_EV); renderTempo(CFG_TEMPO_DG);
  }
});

applyFilters();
</script>
