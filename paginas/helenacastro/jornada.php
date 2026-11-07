<?php $abaTo = (($_GET['to'] ?? '') === '1') ? '?to=1' : ''; ?>
<div class="dash-helenacastro">

<style>
/* Helena Castro — jornada do cliente (lead_tag).
   Mesma régua da view de vendas: escala tipográfica 11 · 12 · 13 · 16 · 18,
   espaçamento em múltiplos de 4, raios 4px e --r-sm. Cor só onde tem
   significado: verde = pagou, âmbar = abandonou, vermelho = descartado,
   azul = trilha do cliente selecionado. Etapas intermediárias são neutras.
   Texto secundário usa --ink-2 (≥ 4,5:1 sobre o card nos dois temas). */
.dash-helenacastro {
  --s-consulta: #1a6ec8;
  --ink-2:      #5f6a7d;
}
html.dark .dash-helenacastro { --ink-2: #8290a9; }

/* KPIs compactos (mesmos da view de vendas) */
.dash-helenacastro .kpi-row { gap: 8px; }
.dash-helenacastro .kpi-chip {
  gap: 4px; padding: 8px 12px; min-width: 150px; box-shadow: none;
  transition: box-shadow .2s ease;
}
.dash-helenacastro .kpi-chip:hover { transform: none; box-shadow: var(--sh-sm); }
.dash-helenacastro .kpi-chip-label { font-size: 11px; color: var(--ink-2); }
.dash-helenacastro .kpi-line { display: flex; align-items: baseline; gap: 8px; min-width: 0; }
.dash-helenacastro .kpi-chip-val { font-size: 18px; min-width: 0; }
.dash-helenacastro .kpi-share {
  font-family: var(--font-mono); font-size: 11px; font-weight: 600; line-height: 1;
  color: var(--ink-2); white-space: nowrap;
}
.dash-helenacastro .kpi-caption { margin-top: 8px; font-size: 12px; color: var(--ink-2); }

/* Segmented control */
.dash-helenacastro .seg {
  display: inline-flex; gap: 2px; padding: 2px; flex-wrap: wrap;
  background: var(--card2); border: 1px solid var(--border); border-radius: var(--r-sm);
}
.dash-helenacastro .seg-btn {
  min-height: 28px; padding: 4px 12px; border: 0; border-radius: 4px; background: transparent;
  font: 600 12px var(--font-ui); color: var(--ink-2); cursor: pointer;
  transition: background-color .15s ease, color .15s ease;
}
.dash-helenacastro .seg-btn:hover { color: var(--text); }
.dash-helenacastro .seg-btn.is-active { background: var(--card); color: var(--text); box-shadow: var(--sh-sm); }
.dash-helenacastro .seg-btn:focus-visible { outline: 2px solid var(--consulta); outline-offset: 1px; }
.dash-helenacastro .seg-n { font-family: var(--font-mono); font-weight: 600; margin-left: 4px; }

/* Card do fluxograma */
.dash-helenacastro .chart-card { margin-top: 16px; }
.dash-helenacastro .chart-header {
  padding: 8px 16px; min-height: 40px; display: flex; align-items: center;
  justify-content: space-between; gap: 12px; flex-wrap: wrap;
}
.dash-helenacastro .chart-tools { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.dash-helenacastro .chart-tools .seg[hidden] { display: none; }
.dash-helenacastro .chart-body { padding: 16px 16px 16px; overflow: visible; }
.dash-helenacastro .fluxo-scroll { overflow-x: auto; }
.dash-helenacastro .fluxo-scroll svg { display: block; }
.dash-helenacastro .fluxo-legenda { margin-top: 12px; font-size: 12px; color: var(--ink-2); }
.dash-helenacastro .fluxo-outras  { margin-top: 4px; font-size: 12px; color: var(--ink-2); }
.dash-helenacastro .fluxo-outras code { font-family: var(--font-mono); font-size: 11px; color: var(--text); }

.dash-helenacastro .sel-bar {
  display: none; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
  margin-bottom: 8px; padding: 8px 12px; border-radius: var(--r-sm);
  background: var(--consulta-glow); border: 1px solid var(--border); font-size: 12px; color: var(--text);
}
.dash-helenacastro .sel-bar.visible { display: flex; }
.dash-helenacastro .sel-bar strong { font-weight: 600; }

/* Fluxograma. Caixas: título 13, número 18 e rodapé 11. Setas:
   espessura pela raiz da quantidade (a maior não apaga as outras), cor
   pelo destino (verde = pagamento, âmbar = abandono, vermelho =
   descarte, cinza = etapa do meio), tracejada quando o lead volta para
   uma etapa anterior. O número de cada seta vai numa etiqueta que não
   cobre caixa nem outra etiqueta. Cores das setas misturadas ao fundo
   do card (opacas), para o cruzamento não escurecer. */
.dash-helenacastro svg text { font-family: var(--font-ui); }
.dash-helenacastro .fx-no { cursor: pointer; outline: none; transition: opacity .15s ease; }
.dash-helenacastro .fx-box { fill: var(--card2); stroke: var(--border); stroke-width: 1; }
.dash-helenacastro .fx-no:hover .fx-box,
.dash-helenacastro .fx-no:focus-visible .fx-box { stroke: var(--ink-2); }
.dash-helenacastro .fx-no.is-filtro .fx-box { stroke: var(--text); stroke-width: 2; }
.dash-helenacastro .fx-faixa.ganho, .dash-helenacastro .fx-barra.ganho { fill: var(--fecho); }
.dash-helenacastro .fx-faixa.sinal, .dash-helenacastro .fx-barra.sinal { fill: var(--sinal); }
.dash-helenacastro .fx-faixa.risco, .dash-helenacastro .fx-barra.risco { fill: var(--danger); }
.dash-helenacastro .fx-titulo { fill: var(--text); font-size: 13px; font-weight: 600; }
.dash-helenacastro .fx-valor  { fill: var(--text); font-size: 18px; font-weight: 700; font-variant-numeric: tabular-nums; }
.dash-helenacastro .fx-share  { fill: var(--ink-2); font-family: var(--font-mono); font-size: 11px; font-weight: 600; }
.dash-helenacastro .fx-sub    { fill: var(--ink-2); font-size: 11px; }
.dash-helenacastro .fx-trilho { fill: var(--border); }
.dash-helenacastro .fx-barra  { fill: var(--ink-2); }
.dash-helenacastro .fx-secao  { fill: var(--ink-2); font-size: 11px; font-weight: 600; }

.dash-helenacastro { --fx-forte: 55%; --fx-fraco: 30%; }
html.dark .dash-helenacastro { --fx-forte: 78%; --fx-fraco: 45%; }
.dash-helenacastro .fx-ar { --fx-cor: var(--ink-2); --fx-mix: var(--fx-forte); transition: opacity .15s ease; }
.dash-helenacastro .fx-ar.c-ganho { --fx-cor: var(--fecho); }
.dash-helenacastro .fx-ar.c-sinal { --fx-cor: var(--sinal); }
.dash-helenacastro .fx-ar.c-risco { --fx-cor: var(--danger); }
.dash-helenacastro .fx-ar.is-menor { --fx-mix: var(--fx-fraco); }
.dash-helenacastro .fx-ar:hover,
.dash-helenacastro .fx-ar.is-foco { --fx-mix: 90%; }
.dash-helenacastro .fx-linha { fill: none; stroke: color-mix(in srgb, var(--fx-cor) var(--fx-mix), var(--card)); }
.dash-helenacastro .fx-ponta { fill: color-mix(in srgb, var(--fx-cor) var(--fx-mix), var(--card)); }
.dash-helenacastro .fx-hit   { fill: none; stroke: transparent; stroke-width: 14; }
.dash-helenacastro .fx-etq rect { fill: var(--card); stroke: color-mix(in srgb, var(--fx-cor) 45%, var(--card)); stroke-width: 1; }
.dash-helenacastro .fx-etq-n { fill: var(--text); font-size: 12px; font-weight: 600; font-variant-numeric: tabular-nums; }
.dash-helenacastro .fx-etq-p { fill: var(--ink-2); font-family: var(--font-mono); font-size: 11px; }
.dash-helenacastro .fx-etq-t { fill: var(--ink-2); font-size: 11px; }
.dash-helenacastro .fx-leg   { fill: var(--ink-2); font-size: 12px; }
.dash-helenacastro .fx-leg-l { fill: none; stroke: color-mix(in srgb, var(--ink-2) 55%, var(--card)); }
.dash-helenacastro .fx-leg-p { fill: color-mix(in srgb, var(--ink-2) 55%, var(--card)); }
.dash-helenacastro .fx-leg-c.ganho { fill: var(--fecho); }
.dash-helenacastro .fx-leg-c.sinal { fill: var(--sinal); }
.dash-helenacastro .fx-leg-c.risco { fill: var(--danger); }
/* passar o mouse numa etapa ou seta: o resto apaga */
.dash-helenacastro .fx-svg.foco .fx-ar,
.dash-helenacastro .fx-svg.foco .fx-no { opacity: .16; }
.dash-helenacastro .fx-svg.foco .is-foco { opacity: 1; }
/* trilha do cliente selecionado: o resto apaga, o caminho dele fica em azul */
.dash-helenacastro .has-sel .fx-no,
.dash-helenacastro .has-sel .fx-ar { opacity: .22; }
.dash-helenacastro .has-sel .is-on { opacity: 1; }
.dash-helenacastro .has-sel .fx-ar.is-on { --fx-cor: var(--s-consulta); --fx-mix: 100%; }
.dash-helenacastro .fx-no.is-on .fx-box { stroke: var(--s-consulta); stroke-width: 2; }
.dash-helenacastro .fx-passo     { fill: var(--s-consulta); }
.dash-helenacastro .fx-passo-txt { fill: #fff; font-family: var(--font-mono); font-size: 11px; font-weight: 600; }

/* Tooltip: componente compartilhado .bi-tooltip (fundo sempre escuro) */
.dash-helenacastro .bi-tooltip-row.sep { border-top: 1px solid rgba(255,255,255,.12); margin-top: 4px; }
.dash-helenacastro .bi-tooltip-period { color: var(--hdr-sub); }

/* Tabelas: todas as células centralizadas, como na view de vendas. O
   prefixo .dash-helenacastro vence o `.bi-table td.num` global. */
.dash-helenacastro .bi-card-sub { color: var(--ink-2); }
.dash-helenacastro .bi-card-header {
  display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
}
.dash-helenacastro .bi-table th,
.dash-helenacastro .bi-table td,
.dash-helenacastro .bi-table th.num,
.dash-helenacastro .bi-table td.num { text-align: center; }
.dash-helenacastro .bi-table td.num { font-variant-numeric: tabular-nums; }
.dash-helenacastro .tbl-scroll { max-height: 440px; overflow: auto; }
.dash-helenacastro .tbl-scroll thead th { position: sticky; top: 0; z-index: 1; background: var(--card2); }
.dash-helenacastro #thead-jornadas th { cursor: pointer; user-select: none; }
.dash-helenacastro #thead-jornadas th:hover,
.dash-helenacastro #thead-jornadas th.th-active { background: var(--border); color: var(--text); }
.dash-helenacastro #tbody-jornadas tr { cursor: pointer; }
.dash-helenacastro #tbody-jornadas tr:hover td { background: var(--card2); }
.dash-helenacastro #tbody-jornadas tr.is-sel td { background: var(--consulta-glow); }
.dash-helenacastro .cli-nome { font-weight: 600; color: var(--text); }
.dash-helenacastro .cli-id   { font-family: var(--font-mono); font-size: 11px; color: var(--ink-2); }
.dash-helenacastro .cli-sub  { font-size: 12px; color: var(--ink-2); }
.dash-helenacastro td.td-nowrap { white-space: nowrap; }
.dash-helenacastro .td-vazio { padding: 32px; color: var(--ink-2); }
.dash-helenacastro .tbl-nota { padding: 8px 16px; font-size: 12px; color: var(--ink-2); border-top: 1px solid var(--border); }
.dash-helenacastro .lista-filtros { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.dash-helenacastro .chip-filtro {
  display: none; align-items: center; gap: 8px; height: 28px; padding: 0 8px;
  border: 1px solid var(--border); border-radius: var(--r-sm); background: var(--card2);
  font: 12px var(--font-ui); color: var(--text); cursor: pointer;
}
.dash-helenacastro .chip-filtro.visible { display: inline-flex; }
.dash-helenacastro .chip-filtro:hover { border-color: var(--ink-2); }

/* Situação: ponto só quando sai do normal ("Em andamento" é texto puro) */
.dash-helenacastro .st { display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }
.dash-helenacastro .st::before { content: ''; width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.dash-helenacastro .st.andamento::before  { display: none; }
.dash-helenacastro .st.pagou::before      { background: var(--fecho); }
.dash-helenacastro .st.abandonou::before  { background: var(--sinal); }
.dash-helenacastro .st.descartado::before { background: var(--danger); }

/* Linha de baixo: lista de jornadas (2fr) + trilha do cliente (1fr) */
.dash-helenacastro .grid-2-1 {
  display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 12px;
  margin-top: 16px; align-items: stretch;
}
@media (max-width: 1100px) {
  .dash-helenacastro .grid-2-1 { grid-template-columns: minmax(0, 1fr); }
}

/* Trilha do cliente */
.dash-helenacastro .trilha-body { padding: 16px; max-height: 488px; overflow-y: auto; }
.dash-helenacastro .trilha-vazia {
  display: flex; flex-direction: column; gap: 4px; padding: 32px 16px; text-align: center;
  font-size: 12px; color: var(--ink-2);
}
.dash-helenacastro .trilha-vazia strong { font-size: 13px; font-weight: 600; color: var(--text); }
.dash-helenacastro .trilha-nome { font-size: 16px; font-weight: 600; color: var(--text); }
.dash-helenacastro .trilha-meta { margin-top: 4px; font-size: 12px; color: var(--ink-2); line-height: 1.5; }
.dash-helenacastro .trilha-meta a { color: var(--consulta); font-weight: 600; text-decoration: none; }
.dash-helenacastro .trilha-meta a:hover { text-decoration: underline; }
.dash-helenacastro .trilha-aviso { margin-top: 8px; font-size: 12px; color: var(--text); padding: 8px 12px; border-radius: var(--r-sm); background: var(--sinal-glow); }
.dash-helenacastro .trilha-resumo {
  display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;
  margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border); font-size: 12px; color: var(--ink-2);
}
.dash-helenacastro .trilha-lista { list-style: none; margin: 16px 0 0; padding: 0; }
.dash-helenacastro .trilha-item { position: relative; padding: 0 0 16px 24px; }
.dash-helenacastro .trilha-item::before {
  content: ''; position: absolute; left: 5px; top: 16px; bottom: 0; width: 2px; background: var(--border);
}
.dash-helenacastro .trilha-item:last-child { padding-bottom: 0; }
.dash-helenacastro .trilha-item:last-child::before { display: none; }
.dash-helenacastro .trilha-marca {
  position: absolute; left: 0; top: 4px; width: 12px; height: 12px; border-radius: 50%;
  background: var(--card); border: 2px solid var(--s-consulta);
}
.dash-helenacastro .trilha-marca.ganho { background: var(--fecho);  border-color: var(--fecho); }
.dash-helenacastro .trilha-marca.sinal { background: var(--sinal);  border-color: var(--sinal); }
.dash-helenacastro .trilha-marca.risco { background: var(--danger); border-color: var(--danger); }
.dash-helenacastro .trilha-marca.fora  { border-color: var(--ink-2); }
.dash-helenacastro .trilha-l1 { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; }
.dash-helenacastro .trilha-tag   { font-size: 13px; font-weight: 600; color: var(--text); }
.dash-helenacastro .trilha-delta { font-family: var(--font-mono); font-size: 11px; font-weight: 600; color: var(--ink-2); white-space: nowrap; }
.dash-helenacastro .trilha-quando { margin-top: 2px; font-family: var(--font-mono); font-size: 11px; color: var(--ink-2); }
.dash-helenacastro .trilha-desc { margin-top: 4px; font-size: 12px; color: var(--text); }
.dash-helenacastro .btn-sel-limpar {
  height: 28px; padding: 0 12px; border: 1px solid var(--border); border-radius: var(--r-sm);
  background: var(--card); color: var(--text); font: 600 12px var(--font-ui); cursor: pointer;
}
.dash-helenacastro .btn-sel-limpar:hover { border-color: var(--ink-2); }

/* Responsável e busca de cliente na barra de filtros (mesma família visual do camp-wrap) */
.dash-helenacastro #resp-wrap { width: 240px; }
.dash-helenacastro #cli-wrap { width: 280px; }
.dash-helenacastro #resp-wrap .camp-input-row,
.dash-helenacastro #cli-wrap .camp-input-row { background: var(--hdr-input-bg); border-color: var(--hdr-input-border); }
.dash-helenacastro .cli-opt-sub { color: var(--hdr-brand); font-size: 12px; overflow: hidden; text-overflow: ellipsis; }

/* Modo demonstração (?demo=1): mesmo tratamento da view de vendas —
   botão, etiqueta e faixa em âmbar, para nenhum print com número fictício
   passar por dado real. O cabeçalho é escuro nos dois temas, por isso ali
   o tom é --hdr-gold. */
.dash-helenacastro .btn-demo[aria-pressed="true"] {
  border-color: var(--hdr-gold); color: var(--hdr-gold); background: rgba(216, 178, 95, .12);
}
.dash-helenacastro.is-demo .header-badge { border-color: var(--hdr-gold); color: var(--hdr-gold); }
.dash-helenacastro .demo-banner {
  display: none; margin: 0 0 12px; padding: 8px 12px;
  border: 1px dashed var(--sinal); border-radius: var(--r-sm); background: var(--sinal-glow);
  font-size: 12px; color: var(--text);
}
.dash-helenacastro.is-demo .demo-banner { display: block; }
.dash-helenacastro .demo-banner strong { color: var(--sinal); font-weight: 700; }
html.dark .dash-helenacastro .demo-banner strong { color: var(--hdr-gold); }

@media (prefers-reduced-motion: reduce) {
  .dash-helenacastro .kpi-chip,
  .dash-helenacastro .seg-btn,
  .dash-helenacastro .fx-no,
  .dash-helenacastro .fx-ar,
  .dash-helenacastro .bi-tooltip { transition: none; }
}
</style>

<!-- ── LOADING ─────────────────────────────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>
<div class="bi-tooltip" id="chart-tooltip" role="tooltip" aria-hidden="true"></div>

<!-- ── HEADER ─────────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo">IS</div>
    <div>
      <div class="header-title">Jornada do cliente</div>
      <div class="header-sub">Caminho dos leads pelas tags do sistema &middot; <span id="sub-resp">Helena Castro</span></div>
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
    <div class="header-badge" id="filter-label-badge"><?= date('Y') ?></div>
    <button class="btn-tab-switch btn-demo" id="btn-demo" type="button" aria-pressed="false" onclick="toggleDemo()" title="Mostra a tela com leads fictícios, para apresentação">Modo demonstração</button>
    <div class="tab-switch">
      <a class="tab-switch-btn" href="<?= $this->site ?>helenacastro<?= $abaTo ?>">Vendas diárias</a>
      <a class="tab-switch-btn active" href="<?= $this->site ?>helenacastro/jornada<?= $abaTo ?>" aria-current="page">Jornada do cliente</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>helenacastro/dicionario<?= $abaTo ?>">Dicionário</a>
    </div>
  </div>
</header>

<!-- ── FILTROS ─────────────────────────────────────────────────── -->
<section class="filters is-collapsible" aria-label="Filtros do painel">
  <button class="filters-toggle" type="button" aria-expanded="true" aria-controls="filters-fields">
    <span>Filtros</span>
    <span class="dd-caret filters-toggle-caret">&#x25BE;</span>
  </button>
  <div class="filters-fields" id="filters-fields">

  <!-- Últimos N [dias|semanas|meses] — opcional; ao abrir vale o mês atual -->
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

  <!-- Ano -->
  <div class="filter-pair">
    <span class="filter-label">Ano</span>
    <div class="multi-dd-wrap" id="year-wrap">
      <button class="multi-dd-btn" id="year-btn" type="button">Todos <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="year-list"></div>
    </div>
  </div>

  <!-- Mês -->
  <div class="filter-pair">
    <span class="filter-label">Mês</span>
    <div class="multi-dd-wrap" id="month-wrap">
      <button class="multi-dd-btn" id="month-btn" type="button">Todos <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="month-list"></div>
    </div>
  </div>

  <div class="filter-sep"></div>

  <!-- Data range -->
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

  <!-- Responsável atual do lead na leadV2: abre na Helena; sem nenhum = todos -->
  <div class="filter-pair">
    <span class="filter-label">Responsável</span>
    <div class="camp-wrap" id="resp-wrap">
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </span>
        <input id="resp-input" class="camp-input" type="text" placeholder="Pesquisar responsável..." autocomplete="off" aria-label="Filtrar por responsável">
        <button class="camp-clear" id="resp-clear" type="button" tabindex="-1" aria-label="Limpar responsável">&#x2715;</button>
      </div>
      <div class="camp-tags" id="resp-tags"></div>
      <div class="camp-dropdown" id="resp-dropdown"></div>
    </div>
  </div>

  <div class="filter-sep"></div>

  <!-- Cliente: busca na lead_tag inteira (não só no período), dentro do responsável filtrado, e abre a trilha -->
  <div class="filter-pair">
    <span class="filter-label">Cliente</span>
    <div class="camp-wrap" id="cli-wrap">
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </span>
        <input id="cli-input" class="camp-input" type="text" placeholder="Nome, e-mail, telefone ou ID Bitrix" autocomplete="off" aria-label="Buscar cliente" role="combobox" aria-expanded="false" aria-controls="cli-dropdown">
        <button class="camp-clear" id="cli-clear" type="button" tabindex="-1" aria-label="Limpar cliente">&#x2715;</button>
      </div>
      <div class="camp-dropdown" id="cli-dropdown" role="listbox"></div>
    </div>
  </div>

  <button class="btn-apply" onclick="applyFilters()">Aplicar</button>
  <button class="btn-clear" onclick="clearFilters()">Limpar</button>
  </div>
</section>
<script>
// Filtros sempre abertos ao entrar (ignora o "recolhido" lembrado) e o
// Aplicar não recolhe; só o botão FILTROS recolhe.
initFiltersToggle({ key: 'helenacastro-jornada', collapseOnApply: false }).open();
</script>

<!-- ── ERRO ───────────────────────────────────────────────────── -->
<div id="error-banner" role="alert"></div>

<!-- ── CONTEÚDO PRINCIPAL ──────────────────────────────────────── -->
<main class="main" id="dashboard-main">

  <div class="demo-banner" role="status"><strong>Modo demonstração:</strong> leads fictícios ("Cliente Exemplo"), gerados só para apresentar a tela; a busca de cliente procura entre eles. Para ver os dados reais, clique em "Sair da demonstração" no topo.</div>

  <!-- 5 KPIs da coorte -->
  <div class="kpi-row">
    <div class="kpi-chip aquisicao">
      <span class="kpi-chip-label">Leads na jornada</span>
      <div class="kpi-line"><span class="kpi-chip-val" id="k-leads">—</span></div>
    </div>
    <div class="kpi-chip consulta">
      <span class="kpi-chip-label">Entraram no carrinho</span>
      <div class="kpi-line"><span class="kpi-chip-val" id="k-carrinho">—</span><span class="kpi-share" id="s-carrinho"></span></div>
    </div>
    <div class="kpi-chip fecho">
      <span class="kpi-chip-label">Pagamentos confirmados</span>
      <div class="kpi-line"><span class="kpi-chip-val" id="k-pagos">—</span><span class="kpi-share" id="s-pagos"></span></div>
    </div>
    <div class="kpi-chip sinal">
      <span class="kpi-chip-label">Abandonaram o carrinho</span>
      <div class="kpi-line"><span class="kpi-chip-val" id="k-abandono">—</span><span class="kpi-share" id="s-abandono"></span></div>
    </div>
    <div class="kpi-chip">
      <span class="kpi-chip-label">Tempo até pagar</span>
      <div class="kpi-line"><span class="kpi-chip-val" id="k-tempo">—</span><span class="kpi-share">mediana</span></div>
    </div>
  </div>
  <div class="kpi-caption" id="kpi-caption"></div>

  <!-- FLUXOGRAMA -->
  <div class="chart-card">
    <div class="chart-header">
      <div class="chart-title-group">
        <span class="chart-title">Caminho dos leads</span>
      </div>
      <div class="chart-tools">
        <div class="seg" role="group" aria-label="Setas exibidas" id="seg-setas">
          <button type="button" class="seg-btn is-active" data-setas="principais" aria-pressed="true"  onclick="setSetas('principais')">Principais</button>
          <button type="button" class="seg-btn"           data-setas="todas"      aria-pressed="false" onclick="setSetas('todas')">Todas<span class="seg-n" id="n-setas"></span></button>
        </div>
        <div class="seg" role="group" aria-label="Forma de exibição">
          <button type="button" class="seg-btn is-active" data-vista="fluxo"  aria-pressed="true"  onclick="setVista('fluxo')">Fluxograma</button>
          <button type="button" class="seg-btn"           data-vista="tabela" aria-pressed="false" onclick="setVista('tabela')">Tabela</button>
        </div>
      </div>
    </div>
    <div class="chart-body">
      <div class="sel-bar" id="sel-bar"></div>
      <div class="fluxo-scroll" id="fluxo"></div>
      <div id="fluxo-tabela" hidden></div>
      <div class="fluxo-legenda" id="fluxo-legenda"></div>
      <div class="fluxo-outras" id="fluxo-outras" hidden></div>
    </div>
  </div>

  <!-- JORNADAS + TRILHA -->
  <div class="grid-2-1">
    <div class="bi-card">
      <div class="bi-card-header">
        <span class="bi-card-title">Jornadas do período</span>
        <div class="lista-filtros">
          <button type="button" class="chip-filtro" id="chip-no" onclick="setNoFiltro(null)" title="Remover filtro de etapa"></button>
          <div class="seg" role="group" aria-label="Situação" id="seg-sit"></div>
        </div>
      </div>
      <div class="tbl-scroll">
        <table class="bi-table">
          <thead id="thead-jornadas"></thead>
          <tbody id="tbody-jornadas">
            <tr><td colspan="4" class="td-vazio">Carregando…</td></tr>
          </tbody>
        </table>
      </div>
      <div class="tbl-nota" id="tbl-nota"></div>
    </div>

    <div class="bi-card">
      <div class="bi-card-header">
        <span class="bi-card-title">Trilha do cliente</span>
      </div>
      <div class="trilha-body" id="trilha"></div>
    </div>
  </div>

  <div class="last-updated" id="last-updated"></div>

</main>
</div>

<script>
// ══════════════════════════════════════════════════════════════
//  Helena Castro — Jornada do cliente (lead_tag)
//  Consome: helenacastro/jornadaDados, helenacastro/trilha,
//  helenacastro/buscaCliente (controller MRPHP).
//
//  O backend devolve tudo agregado (KPIs, etapas, setas, entradas,
//  parados e a lista); trocar Fluxograma/Tabela, filtrar a lista por
//  situação ou por etapa e ordenar não fazem nova requisição. No modo
//  demonstração (?demo=1) o mesmo payload é montado aqui, com leads
//  fictícios, e agregarJornada() repete a regra de calcularJornada().
//
//  createMultiDropdown, createSearchDropdown, createRelativePeriod,
//  getDateInput, setEffectiveDate, clearDateInput, isoLocal e fNum vêm de
//  js/bi-dashboard.js (carregado no <head> pelo template).
// ══════════════════════════════════════════════════════════════

const API_DADOS  = '<?= $this->site ?>helenacastro/jornadaDados';
const API_TRILHA = '<?= $this->site ?>helenacastro/trilha';
const API_BUSCA  = '<?= $this->site ?>helenacastro/buscaCliente';
const API_RESP   = '<?= $this->site ?>helenacastro/jornadaResponsaveis';
// Responsável marcado ao abrir e depois do Limpar; tirar a etiqueta = todos.
const RESP_PADRAO = 'Helena Castro';
// Embed (?to=1): trilha e busca devolvem dado pessoal e, fora do iframe,
// exigem o login do painel; dentro dele, a liberação olha o ?to=1 da
// própria chamada, então ele vai junto (ver exigirAcesso() no controller).
const COM_TO = new URLSearchParams(location.search).get('to') === '1' ? '&to=1' : '';
const MONTHS = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const NOW_YEAR = String(new Date().getFullYear());
const NOW_MONTH = String(new Date().getMonth() + 1);
// De 2024 até o ano atual: a lista ganha o ano novo sozinha na virada.
const YEARS  = Array.from({ length: Math.max(1, Number(NOW_YEAR) - 2023) }, (_, i) => String(2024 + i));

// ── Catálogo: mesmas tags de TAGS_JORNADA no controller, com rótulo e
//    posição no fluxograma (col = coluna, row 1 = linha das saídas) ──
const CATALOGO = {
  'lead-criado':          { label: 'Lead criado',          col: 0,   row: 0, tipo: 'etapa' },
  'carrinho-acessado':   { label: 'Entrou no carrinho',   col: 1,   row: 0, tipo: 'etapa' },
  'carrinho-pagamento':   { label: 'Foi para o pagamento', col: 2,   row: 0, tipo: 'etapa' },
  'pagamento-confirmado': { label: 'Pagamento confirmado', col: 3,   row: 0, tipo: 'ganho' },
  'carrinho-abandonado':  { label: 'Carrinho abandonado',  col: 1.5, row: 1, tipo: 'sinal' },
  'lead-descartado':      { label: 'Lead descartado',      col: 0,   row: 1, tipo: 'risco' },
};
const ORDEM_TAGS = Object.keys(CATALOGO);
const rotulo = tag => (CATALOGO[tag] ? CATALOGO[tag].label : tag);
const nomeDe = (nome, id) => nome || 'Lead #' + id;

const SITUACOES = [
  ['todas',      'Todas'],
  ['andamento',  'Em andamento'],
  ['pagou',      'Pagaram'],
  ['abandonou',  'Abandonaram'],
  ['descartado', 'Descartados'],
];
const SIT_LABEL = { andamento: 'Em andamento', pagou: 'Pagou', abandonou: 'Abandonou o carrinho', descartado: 'Descartado' };
const ORDEM_SIT = ['andamento', 'pagou', 'abandonou', 'descartado'];

// Modo demonstração (?demo=1 na URL): leads fictícios gerados aqui
// (montarPayloadDemo), sem chamar o backend.
let modoDemo = new URLSearchParams(location.search).get('demo') === '1';

const estado = {
  res: null,            // payload de jornadaDados (ou da demonstração)
  arestas: new Map(),   // 'de>para' → { de, para, leads, mediana }
  periodo: null,        // { ini, fim, rotulo }
  resp: null,           // responsáveis do último Aplicar (CSV) ou null = todos; vale também na busca
  sel: null,           // trilha aberta: { lead, eventos }
  vista: 'fluxo',       // 'fluxo' | 'tabela'
  setas: 'principais',  // setas do fluxograma: 'principais' | 'todas'
  sit: 'todas',         // filtro de situação da lista
  no: null,             // filtro "passaram por <tag>" (clique numa etapa)
  sortKey: 'entrada', sortDir: -1,
  demoLeads: [],        // leads fictícios do período (busca e trilha na demonstração)
};

const yearDropdown  = createMultiDropdown('year-wrap',  'year-btn',  'year-list',  YEARS,  l => l, l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);
// "Últimos N" — um substitui o outro com Ano/Mês/Data (ver createRelativePeriod).
const relPeriod = createRelativePeriod({
  numId: 'rel-n', unitId: 'rel-unit',
  conflicts: [[yearDropdown, 'year-list'], [monthDropdown, 'month-list']],
});

// Abre filtrado no mês atual (até hoje), como a view de vendas.
yearDropdown.select([NOW_YEAR]);
monthDropdown.select([NOW_MONTH]);

// Responsável: a lista vem do backend (ou dos leads fictícios, na
// demonstração; ver aplicarModoDemo) e abre marcada na Helena.
const respDropdown = createSearchDropdown('resp-wrap', 'resp-input', 'resp-dropdown', 'resp-clear', 'resp-tags', 'Nenhum responsável encontrado');
respDropdown.toggle(RESP_PADRAO);
let respReais = [RESP_PADRAO];
fetch(API_RESP, { cache: 'no-store' })
  .then(res => res.json())
  .then(list => { if (Array.isArray(list)) respReais = list; if (!modoDemo) respDropdown.load(respReais); })
  .catch(err => console.error('Erro ao carregar responsáveis:', err));

// ── Campos de Data (mesma regra da view de vendas) ──────────────
// Ano/Mês pintam o intervalo nos campos em modo exibição (.is-effective,
// que getDateInput ignora). Editar uma data pintada assim torna as duas
// filtro de verdade, com os valores que estão na tela, e solta o Ano/Mês;
// escolher Ano ou Mês de novo apaga as datas digitadas. As datas pintadas
// pelo "Últimos N" são tratadas pelo createRelativePeriod, que ouve o
// mesmo evento antes daqui.
const DATAS = ['data-inicio', 'data-fim'];
const wrapData = id => document.getElementById(id).closest('.date-input-wrap');
DATAS.forEach(id => {
  document.getElementById(id).addEventListener('change', () => {
    if (wrapData(id).classList.contains('is-effective')) {
      DATAS.forEach(o => wrapData(o).classList.remove('is-effective'));
      yearDropdown.reset();
      monthDropdown.reset();
    }
    DATAS.forEach(o => document.getElementById(o + '-clear').classList.toggle('visible', !!document.getElementById(o).value));
  });
  document.getElementById(id + '-clear').addEventListener('click', () => clearDateInput(id, id + '-clear'));
});
['year-list', 'month-list'].forEach(listId => {
  document.getElementById(listId).addEventListener('mousedown', e => {
    if (!e.target.closest('.multi-dd-item')) return;
    DATAS.forEach(id => { if (getDateInput(id)) clearDateInput(id, id + '-clear'); });
  });
});

// ══════════════════════════════════════════════════════════════
//  FORMATADORES
// ══════════════════════════════════════════════════════════════
function set(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }
function escHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function fPct(n, d) {
  if (!d) return '—';
  return (n / d * 100).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
}
function parseIso(s) { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); }
function addDias(d, n) { return new Date(d.getFullYear(), d.getMonth(), d.getDate() + n); }
function fDataBr(iso) { const [y, m, d] = iso.split('-'); return `${d}/${m}/${y}`; }
// 'YYYY-MM-DD HH:MM:SS' (horário de Brasília, como vem do banco) → 'DD/MM/YYYY HH:MM[:SS]'
function fDataHora(s, comSeg) { const [d, h] = s.split(' '); return fDataBr(d) + ' ' + (comSeg ? h : h.slice(0, 5)); }
function tsDe(s) { return new Date(s.replace(' ', 'T')).getTime(); }
// Duração em segundos → '12 min', '3 h 20 min', '2 d 4 h'
function fDur(seg) {
  if (seg === null || seg === undefined) return '—';
  const min = Math.round(seg / 60);
  if (min < 1)  return '< 1 min';
  if (min < 60) return min + ' min';
  const h = Math.floor(min / 60), m = min % 60;
  if (h < 24) return h + ' h' + (m ? ' ' + m + ' min' : '');
  const d = Math.floor(h / 24), hr = h % 24;
  return d + ' d' + (hr ? ' ' + hr + ' h' : '');
}
const semAcento = s => String(s ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

// ══════════════════════════════════════════════════════════════
//  MODO DEMONSTRAÇÃO — leads fictícios no mesmo formato do backend.
//  Cada dia é sorteado com semente na própria data: o mesmo dia sempre
//  dá os mesmos leads, qualquer que seja o filtro. Tags com horário
//  depois de agora ficam de fora (a jornada "anda" ao longo do dia).
// ══════════════════════════════════════════════════════════════
function rngDemo(seed) {
  let h = 1779033703 ^ seed.length;
  for (let i = 0; i < seed.length; i++) { h = Math.imul(h ^ seed.charCodeAt(i), 3432918353); h = h << 13 | h >>> 19; }
  let a = h >>> 0;
  return () => {
    a = a + 0x6D2B79F5 | 0;
    let t = Math.imul(a ^ a >>> 15, 1 | a);
    t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t;
    return ((t ^ t >>> 14) >>> 0) / 4294967296;
  };
}

const DEMO_AGORA   = Date.now();
const DEMO_EPOCH   = new Date(2020, 0, 1);
const DEMO_MIN = 60e3, DEMO_HORA = 3600e3, DEMO_DIA = 86400e3;
const DEMO_ORIGENS = ['Formulário do site', 'Formulário do Instagram', 'Conversa no WhatsApp'];
const DEMO_MOTIVOS = ['Sem interesse no momento', 'Telefone inválido', 'Fora do perfil', 'Já é cliente'];
const fReal = v => 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function isoHora(ms) {
  const d = new Date(ms), p = x => String(x).padStart(2, '0');
  return `${isoLocal(d)} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
}

// Próxima tag a partir da atual, com o intervalo até ela; null = parou ali.
// Volta ao carrinho depois de abandonar no máximo uma vez.
function proximaDemo(tag, voltas, r) {
  const x = r(), entre = (a, b) => a + r() * (b - a);
  switch (tag) {
    case 'lead-criado':
      if (x < .50) return { tag: 'carrinho-acessado', dt: r() < .4 ? entre(1, 10) * DEMO_MIN : entre(1, 72) * DEMO_HORA };
      if (x < .67) return { tag: 'lead-descartado', dt: entre(2, 120) * DEMO_HORA };
      return null;
    case 'carrinho-acessado':
      if (x < .50) return { tag: 'carrinho-pagamento', dt: entre(2, 40) * DEMO_MIN };
      if (x < .88) return { tag: 'carrinho-abandonado', dt: DEMO_HORA + entre(0, 60) * DEMO_MIN };
      if (x < .90) return { tag: 'lead-descartado', dt: entre(1, 4) * DEMO_DIA };
      return null;
    case 'carrinho-pagamento':
      if (x < .66) return { tag: 'pagamento-confirmado', dt: entre(1, 20) * DEMO_MIN };
      if (x < .95) return { tag: 'carrinho-abandonado', dt: DEMO_HORA + entre(0, 60) * DEMO_MIN };
      return null;
    case 'carrinho-abandonado':
      if (voltas < 1 && x < .16) return { tag: 'carrinho-acessado', dt: entre(1, 5) * DEMO_DIA };
      if (x < .30) return { tag: 'lead-descartado', dt: entre(2, 7) * DEMO_DIA };
      return null;
    default:
      return null; // pagamento-confirmado e lead-descartado encerram
  }
}

function descricaoDemo(tag, lead, r) {
  switch (tag) {
    case 'lead-criado':          return lead.origem;
    case 'carrinho-acessado':   return lead.produto[0] + ' · ' + fReal(lead.produto[1]);
    case 'carrinho-pagamento':   return 'Escolheu ' + lead.metodo;
    case 'pagamento-confirmado': return lead.metodo + ' ' + fReal(lead.produto[1]);
    case 'carrinho-abandonado':  return 'Sem pagamento 1 h depois de entrar no carrinho';
    case 'lead-descartado':      return 'Motivo: ' + DEMO_MOTIVOS[Math.floor(r() * DEMO_MOTIVOS.length)];
    default:                     return '';
  }
}

// Dia útil: 10–22 leads; fim de semana: 4–10. ~3% sem linha na leadV2
// (sem nome, e-mail e responsável), como acontece com lead recém-criado.
function leadsDemoDoDia(iso) {
  const r    = rngDemo(iso + '|jornada');
  const dia  = parseIso(iso);
  const fds  = dia.getDay() === 0 || dia.getDay() === 6;
  const n    = Math.round((fds ? 4 : 10) + r() * (fds ? 6 : 12));
  const base = Math.round((dia - DEMO_EPOCH) / DEMO_DIA) * 40;
  const out  = [];
  for (let i = 0; i < n; i++) {
    const num = base + i, cod = String(num % 100000).padStart(5, '0');
    const inicio = new Date(dia.getFullYear(), dia.getMonth(), dia.getDate(),
      8 + Math.floor(r() * 14), Math.floor(r() * 60), Math.floor(r() * 60)).getTime();
    const semEspelho = r() < .03;
    const lead = {
      id:          String(900000 + num),
      id_leadV2:   semEspelho ? null : 500000 + num,
      nome:        semEspelho ? null : 'Cliente Exemplo ' + cod,
      email:       semEspelho ? null : 'cliente' + cod + '@exemplo.com',
      telefone:    semEspelho ? null : '+55 (11) 90000-' + cod.slice(-4),
      responsavel: r() < .7 ? 'Helena Castro' : 'Sistema Admin',
      origem:      DEMO_ORIGENS[Math.floor(r() * DEMO_ORIGENS.length)],
      produto:     r() < .35 ? ['Consulta de imigração', 690] : ['Ingresso On The Road', 100],
      metodo:      r() < .7 ? 'Pix' : 'Cartão de crédito',
    };
    if (semEspelho) lead.responsavel = null;

    const eventos = [];
    let tag = r() < .08 ? 'carrinho-acessado' : 'lead-criado', t = inicio, voltas = 0;
    while (tag) {
      eventos.push({ tag, ts: t, data: isoHora(t), descricao: descricaoDemo(tag, lead, r) });
      const p = proximaDemo(tag, voltas, r);
      if (!p) break;
      if (tag === 'carrinho-abandonado') voltas++;
      t += p.dt;
      tag = p.tag;
    }
    lead.eventos = eventos.filter(e => e.ts <= DEMO_AGORA);
    if (lead.eventos.length) out.push(lead);
  }
  return out;
}

function mediana(v) {
  if (!v.length) return null;
  const s = [...v].sort((a, b) => a - b), m = s.length >> 1;
  return Math.round(s.length % 2 ? s[m] : (s[m - 1] + s[m]) / 2);
}

// Mesma regra de calcularJornada() no controller (usada só na demonstração).
function agregarJornada(leads) {
  const nos = {}, entradas = {}, parados = {}, outras = {}, arestas = new Map(), ateConfirmar = [], linhas = [];
  const soma = (o, k) => { o[k] = (o[k] || 0) + 1; };
  leads.forEach(l => {
    const cat = l.eventos.filter(e => CATALOGO[e.tag]);
    if (!cat.length) return;
    const tagsCat = [...new Set(cat.map(e => e.tag))];
    tagsCat.forEach(t => soma(nos, t));
    new Set(l.eventos.map(e => e.tag)).forEach(t => { if (!CATALOGO[t]) soma(outras, t); });

    const primeira = cat[0], ultima = cat[cat.length - 1];
    soma(entradas, primeira.tag);

    const vistas = new Set();
    for (let i = 1; i < cat.length; i++) {
      const a = cat[i - 1].tag, b = cat[i].tag, k = a + '>' + b;
      if (a === b || vistas.has(k)) continue;
      vistas.add(k);
      if (!arestas.has(k)) arestas.set(k, { de: a, para: b, leads: 0, tempos: [] });
      const e = arestas.get(k);
      e.leads++;
      e.tempos.push((cat[i].ts - cat[i - 1].ts) / 1000);
    }

    const pago = cat.find(e => e.tag === 'pagamento-confirmado');
    let situacao = 'andamento';
    if (pago) { situacao = 'pagou'; ateConfirmar.push((pago.ts - primeira.ts) / 1000); }
    else if (ultima.tag === 'carrinho-abandonado') situacao = 'abandonou';
    else if (ultima.tag === 'lead-descartado')     situacao = 'descartado';
    if (situacao === 'andamento' && CATALOGO[ultima.tag].tipo === 'etapa') soma(parados, ultima.tag);

    linhas.push({
      id: l.id, nome: l.nome, entrada: primeira.data, ultima: ultima.tag, situacao,
      duracao: cat.length > 1 ? Math.round((ultima.ts - primeira.ts) / 1000) : null,
      etapas: tagsCat,
    });
  });
  linhas.sort((a, b) => b.entrada.localeCompare(a.entrada));
  return {
    kpis: {
      leads: linhas.length,
      carrinho: nos['carrinho-acessado'] || 0,
      pagos: nos['pagamento-confirmado'] || 0,
      abandonos: nos['carrinho-abandonado'] || 0,
      tempo_ate_pagar: mediana(ateConfirmar),
    },
    nos, entradas, parados,
    arestas: [...arestas.values()].map(e => ({ de: e.de, para: e.para, leads: e.leads, mediana: mediana(e.tempos) })),
    outras_tags: outras,
    linhas, linhas_total: linhas.length,
  };
}

// Responsáveis dos leads fictícios (leadsDemoDoDia).
const DEMO_RESPONSAVEIS = ['Helena Castro', 'Sistema Admin'];

// resp = CSV de responsáveis ou null; como no backend, lead sem
// responsável só entra sem filtro.
function montarPayloadDemo(ini, fim, resp) {
  const quem = resp ? new Set(resp.split(',')) : null;
  const leads = [];
  for (let d = parseIso(ini); d <= parseIso(fim); d = addDias(d, 1)) {
    leads.push(...leadsDemoDoDia(isoLocal(d)).filter(l => !quem || quem.has(l.responsavel)));
  }
  estado.demoLeads = leads;
  return { meta: { data_inicio: ini, data_fim: fim, demo: true }, ...agregarJornada(leads) };
}

function trilhaDemo(id) {
  const l = estado.demoLeads.find(x => x.id === id);
  if (!l) return null;
  return {
    lead: { id_bitrix: l.id, link_bitrix: null, id_leadV2: l.id_leadV2, nome: l.nome, email: l.email, telefone: l.telefone, responsavel: l.responsavel },
    eventos: l.eventos.map(e => ({ tag: e.tag, descricao: e.descricao, data: e.data })),
  };
}

function buscaDemo(q) {
  const t = semAcento(q.trim()), dig = q.replace(/\D/g, '');
  return estado.demoLeads.filter(l =>
    (l.nome && (semAcento(l.nome).includes(t) || l.email.includes(t))) ||
    (dig.length >= 4 && (l.id.includes(dig) || (l.telefone || '').replace(/\D/g, '').includes(dig)))
  ).slice(-8).reverse().map(l => ({ id_bitrix: l.id, nome: l.nome, email: l.email }));
}

function aplicarModoDemo() {
  document.querySelector('.dash-helenacastro').classList.toggle('is-demo', modoDemo);
  const btn = document.getElementById('btn-demo');
  btn.setAttribute('aria-pressed', modoDemo ? 'true' : 'false');
  btn.textContent = modoDemo ? 'Sair da demonstração' : 'Modo demonstração';
  respDropdown.load(modoDemo ? DEMO_RESPONSAVEIS : respReais);
  // As abas do cabeçalho levam o ?demo=1 junto, para a apresentação seguir nas outras telas.
  document.querySelectorAll('.tab-switch a').forEach(a => {
    const u = new URL(a.href);
    if (modoDemo) u.searchParams.set('demo', '1'); else u.searchParams.delete('demo');
    a.href = u.toString();
  });
}

// Liga/desliga e grava na URL (?demo=1), preservando os outros parâmetros
// (ex.: ?to=1 do embed) — o link copiado já abre em demonstração.
function toggleDemo() {
  modoDemo = !modoDemo;
  const u = new URL(location.href);
  if (modoDemo) u.searchParams.set('demo', '1'); else u.searchParams.delete('demo');
  history.replaceState(null, '', u);
  aplicarModoDemo();
  estado.sel = null;
  limparBusca();
  applyFilters();
}

// ══════════════════════════════════════════════════════════════
//  KPIs
// ══════════════════════════════════════════════════════════════
function fillKpis(r) {
  const k = r.kpis;
  set('k-leads',    fNum(k.leads));
  set('k-carrinho', fNum(k.carrinho));  set('s-carrinho', fPct(k.carrinho, k.leads) + ' dos leads');
  set('k-pagos',    fNum(k.pagos));     set('s-pagos',    fPct(k.pagos, k.leads) + ' dos leads');
  set('k-abandono', fNum(k.abandonos)); set('s-abandono', fPct(k.abandonos, k.carrinho) + ' dos carrinhos');
  set('k-tempo',    fDur(k.tempo_ate_pagar));
  set('kpi-caption',
    `${fNum(k.leads)} leads começaram a jornada entre ${fDataBr(r.meta.data_inicio)} e ${fDataBr(r.meta.data_fim)}. ` +
    'Cada etapa conta quem chegou nela em qualquer momento até agora, mesmo depois do fim do período.');
}

// ══════════════════════════════════════════════════════════════
//  FLUXOGRAMA — SVG à mão, caixas em posição fixa pelo CATALOGO.
//  Cada tipo de seta tem o seu espaço, para nenhuma passar por trás de
//  caixa nem dividir a reta com outra:
//   · lado    etapas vizinhas na mesma linha: reta no vão (a ida para a
//             direita em cima, a volta embaixo);
//   · arco    mesma linha pulando etapa: arco por cima das caixas, um
//             trilho por seta (as mais curtas por dentro);
//   · meio    entre a linha das etapas e a das saídas: curva em S. As
//             pontas se espalham na borda da caixa na ordem da outra
//             ponta, para cruzar o mínimo;
//   · entrada "entraram direto": toco que desce de uma etiqueta.
//  Os números vão por último, em etiquetas postas no ponto da seta que
//  não cobre caixa, outra etiqueta nem outra seta.
// ══════════════════════════════════════════════════════════════
const FX = { H: 88, MEIO: 150, TRILHO: 24 };
const ORDEM_ETAPA = { 'lead-criado': 0, 'carrinho-acessado': 1, 'carrinho-pagamento': 2, 'pagamento-confirmado': 3 };
const n1 = v => v.toFixed(1);

// Seta principal: leva ≥ 5% de quem estava na origem ou ≥ 0,5% da
// coorte. As demais só aparecem em "Todas", finas e sem número (a
// Tabela e a dica trazem o valor).
function ehPrincipal(n, origem, total) { return n >= origem * .05 || n >= total * .005; }
function fPctSeta(n, d) {
  if (!d) return '—';
  const p = n / d * 100;
  return p < 1 ? '<1%' : Math.round(p) + '%';
}

const _cv = document.createElement('canvas').getContext('2d');
function larguraTxt(txt, fonte) { _cv.font = fonte; return _cv.measureText(txt).width; }

function layoutFluxo(W, nTrilhos, temEntrada) {
  const G = Math.round(Math.min(168, Math.max(120, W * .1)));    // vão entre colunas
  const w = (W - 4 - 3 * G) / 4;
  const base = temEntrada ? 54 : 24;                              // altura do trilho mais baixo
  const y0 = nTrilhos ? base + (nTrilhos - 1) * FX.TRILHO + 14 : (temEntrada ? 44 : 8);
  const y1 = y0 + FX.H + FX.MEIO;
  const pos = {};
  ORDEM_TAGS.forEach(tag => {
    const c = CATALOGO[tag];
    pos[tag] = { x: 2 + c.col * (w + G), y: c.row ? y1 : y0, w, h: FX.H, row: c.row };
  });
  return { W, H: y1 + FX.H + 2, pos, y0, y1, base };
}

// Ponta de seta com entalhe; `recuo` é onde a linha deve parar.
function ponta(x, y, dir, lw) {
  const h = Math.max(5, lw * .75 + 3.5), L = Math.max(9, lw * 1.1 + 6), e = L * .28;
  const pts = {
    baixo: [[x, y], [x - h, y - L], [x, y - L + e], [x + h, y - L]],
    cima:  [[x, y], [x - h, y + L], [x, y + L - e], [x + h, y + L]],
    dir:   [[x, y], [x - L, y - h], [x - L + e, y], [x - L, y + h]],
    esq:   [[x, y], [x + L, y - h], [x + L - e, y], [x + L, y + h]],
  }[dir];
  return { d: 'M' + pts.map(p => n1(p[0]) + ',' + n1(p[1])).join(' L') + ' Z', recuo: L - e };
}

// Cúbica com tangente vertical nas pontas: caminho e ponto em t.
function cubica(p0, p1, p2, p3) {
  return {
    d: `M${n1(p0[0])},${n1(p0[1])} C${n1(p1[0])},${n1(p1[1])} ${n1(p2[0])},${n1(p2[1])} ${n1(p3[0])},${n1(p3[1])}`,
    em: t => {
      const u = 1 - t, a = u * u * u, b = 3 * u * u * t, c = 3 * u * t * t, d = t * t * t;
      return [a * p0[0] + b * p1[0] + c * p2[0] + d * p3[0], a * p0[1] + b * p1[1] + c * p2[1] + d * p3[1]];
    },
  };
}

// Pontas na borda de uma caixa: cada uma tenta ficar no x ideal (preso
// à borda), na ordem recebida, afastadas o bastante das vizinhas.
function distribuir(itens, lo, hi) {
  const n = itens.length;
  if (!n) return [];
  const gap = i => Math.max(16, (itens[i - 1].lw + itens[i].lw) / 2 + 10);
  const p = itens.map(it => Math.min(hi, Math.max(lo, it.ideal)));
  for (let i = 1; i < n; i++) p[i] = Math.max(p[i], p[i - 1] + gap(i));
  p[n - 1] = Math.min(p[n - 1], hi);
  for (let i = n - 2; i >= 0; i--) p[i] = Math.min(p[i], p[i + 1] - gap(i + 1));
  if (p[0] < lo) return p.map((_, i) => n > 1 ? lo + i * (hi - lo) / (n - 1) : (lo + hi) / 2);
  return p;
}

const cruza = (a, b) => a[0] < b[0] + b[2] && b[0] < a[0] + a[2] && a[1] < b[1] + b[3] && b[1] < a[1] + a[3];
const dentro = (p, r, m) => p[0] > r[0] - m && p[0] < r[0] + r[2] + m && p[1] > r[1] - m && p[1] < r[1] + r[3] + m;

function etqSvg(r, n, p, cls) {
  const [x, y, w, h] = r;
  return `<g class="fx-etq"><rect x="${n1(x)}" y="${n1(y)}" width="${w}" height="${h}" rx="4"/>` +
    `<text x="${n1(x + 6)}" y="${n1(y + 14)}"><tspan class="fx-etq-n">${escHtml(n)}</tspan><tspan class="${cls}" dx="5">${escHtml(p)}</tspan></text></g>`;
}

// Legenda das setas no canto vazio da linha das saídas.
function legendaSvg(L) {
  const cab = L.pos['carrinho-abandonado'], x = cab.x + cab.w + 56, y = L.y1 + 2;
  if (L.W - x < 360) return { svg: '', r: null };
  const t = (yy, txt) => `<text class="fx-leg" x="${x + 52}" y="${yy + 4}">${txt}</text>`;
  const seta = (yy, dash) => `<path class="fx-leg-l" d="M${x},${yy} L${x + 31},${yy}" stroke-width="2.5"${dash ? ' stroke-dasharray="5 3"' : ''}/>` +
    `<path class="fx-leg-p" d="${ponta(x + 40, yy, 'dir', 2.5).d}"/>`;
  let s = '';
  s += seta(y + 10, false) + t(y + 10, 'Seguiu em frente');
  s += seta(y + 30, true)  + t(y + 30, 'Voltou para uma etapa anterior');
  s += `<path class="fx-leg-l" d="M${x},${y + 50} L${x + 14},${y + 50}" stroke-width="1.5"/>` +
       `<path class="fx-leg-l" d="M${x + 22},${y + 50} L${x + 40},${y + 50}" stroke-width="7"/>` + t(y + 50, 'Espessura acompanha o número de leads');
  s += ['ganho', 'sinal', 'risco'].map((c, i) => `<circle class="fx-leg-c ${c}" cx="${x + 4 + i * 16}" cy="${y + 70}" r="4"/>`).join('') +
       t(y + 70, 'Cor pelo destino: pagamento, abandono, descarte');
  return { svg: s, r: [x - 8, y - 2, L.W - x + 8, 86] };
}

// Passos (posição na trilha inteira, inclusive tags fora do catálogo) e
// setas do cliente selecionado, para destacar no fluxograma.
function trilhaDe(sel) {
  if (!sel || !sel.eventos.length) return null;
  const passos = new Map(), arestas = new Set();
  sel.eventos.forEach((e, i) => {
    if (!passos.has(e.tag)) passos.set(e.tag, []);
    passos.get(e.tag).push(i + 1);
  });
  const cat = sel.eventos.filter(e => CATALOGO[e.tag]);
  cat.forEach((e, i) => { if (i && cat[i - 1].tag !== e.tag) arestas.add(cat[i - 1].tag + '>' + e.tag); });
  if (cat.length && cat[0].tag !== 'lead-criado') arestas.add('inicio>' + cat[0].tag);
  return { passos, arestas };
}

let focoAtual = null;   // etapa/seta sob o mouse (ver focar())

function renderFluxo() {
  const box = document.getElementById('fluxo');
  const r = estado.res, total = r.kpis.leads, nos = r.nos || {}, parados = r.parados || {};
  const tr = trilhaDe(estado.sel), todas = estado.setas === 'todas';
  const on = k => (tr && tr.arestas.has(k) ? ' is-on' : '');
  const css = getComputedStyle(document.documentElement);
  const fUi = css.getPropertyValue('--font-ui').trim(), fMono = css.getPropertyValue('--font-mono').trim();
  // a seta da trilha selecionada aparece mesmo se for das pequenas
  const mostra = s => todas || !s.menor || (tr && tr.arestas.has(s.k));

  const setas = [...estado.arestas.values()].filter(e => CATALOGO[e.de] && CATALOGO[e.para]).map(e => {
    const A = CATALOGO[e.de], B = CATALOGO[e.para];
    return {
      ...e, k: e.de + '>' + e.para,
      tipo: A.row !== B.row ? 'meio' : (A.row === 1 || Math.abs(A.col - B.col) <= 1 ? 'lado' : 'arco'),
      volta: B.row === 0 && (A.row === 1 || ORDEM_ETAPA[e.para] < ORDEM_ETAPA[e.de]),
      menor: !ehPrincipal(e.leads, nos[e.de] || 0, total),
    };
  }).filter(mostra);
  // Entradas diretas: a primeira tag do lead já é uma etapa do meio
  const entradas = Object.entries(r.entradas || {})
    .filter(([tag, n]) => tag !== 'lead-criado' && CATALOGO[tag] && CATALOGO[tag].row === 0 && n > 0)
    .map(([tag, n]) => ({ tag, n, k: 'inicio>' + tag, menor: !ehPrincipal(n, nos[tag] || 0, total) }))
    .filter(mostra);

  // Trilhos dos arcos: os de vão menor primeiro, no trilho mais baixo livre
  const arcos = setas.filter(s => s.tipo === 'arco');
  const vao = s => Math.abs(CATALOGO[s.de].col - CATALOGO[s.para].col);
  const trilhos = [];
  [...arcos].sort((a, b) => vao(a) - vao(b) || b.leads - a.leads).forEach(s => {
    const a = Math.min(CATALOGO[s.de].col, CATALOGO[s.para].col), b = a + vao(s);
    let k = 0;
    while ((trilhos[k] || []).some(([x, y]) => a < y && x < b)) k++;
    (trilhos[k] = trilhos[k] || []).push([a, b]);
    s.trilho = k;
  });

  const L = layoutFluxo(Math.max(box.clientWidth, 1080), trilhos.length, entradas.length > 0); // abaixo disso rola na horizontal
  const maxN = Math.max(1, ...setas.map(s => s.leads), ...entradas.map(s => s.n));
  const larg = n => 1.5 + 8.5 * Math.sqrt(n / maxN);
  setas.forEach(s => { s.lw = larg(s.leads); });
  entradas.forEach(s => { s.lw = larg(s.n); });

  // Pontas das setas do meio (borda de baixo das etapas, de cima das saídas)
  const bordas = new Map();
  const naBorda = (tag, lado, it) => { const k = tag + '|' + lado; if (!bordas.has(k)) bordas.set(k, []); bordas.get(k).push(it); };
  setas.filter(s => s.tipo === 'meio').forEach(s => {
    const A = L.pos[s.de], B = L.pos[s.para];
    naBorda(s.de,   A.row ? 'cima' : 'baixo', { s, campo: 'xDe',   outro: B });
    naBorda(s.para, B.row ? 'cima' : 'baixo', { s, campo: 'xPara', outro: A });
  });
  bordas.forEach((itens, k) => {
    const [tag, lado] = k.split('|'), P = L.pos[tag];
    itens.sort((a, b) => (a.outro.x - b.outro.x) || (L.pos[a.s.de].row - L.pos[b.s.de].row) || (b.s.leads - a.s.leads));
    const lo = P.x + (tag === 'lead-descartado' && lado === 'cima' ? 64 : 20);   // livra o rótulo "Saídas"
    const xs = distribuir(itens.map(it => ({ ideal: it.outro.x + it.outro.w / 2, lw: it.s.lw })), lo, P.x + P.w - 20);
    itens.forEach((it, i) => { it.s[it.campo] = xs[i]; });
  });

  // Pontas dos arcos na borda de cima: quem vem da esquerda à esquerda
  // (de dentro para fora), quem vai para a direita à direita (de fora
  // para dentro) — assim os arcos se aninham sem cruzar. O meio fica
  // livre para a etiqueta de "entraram direto".
  const livre = {};
  ORDEM_TAGS.filter(tag => CATALOGO[tag].row === 0).forEach(tag => {
    const P = L.pos[tag], esq = [], dir = [];
    arcos.forEach(s => {
      if (s.de !== tag && s.para !== tag) return;
      const outro = s.de === tag ? s.para : s.de;
      (CATALOGO[outro].col < CATALOGO[tag].col ? esq : dir).push({ s, campo: s.de === tag ? 'xDe' : 'xPara' });
    });
    esq.sort((a, b) => a.s.trilho - b.s.trilho);
    dir.sort((a, b) => b.s.trilho - a.s.trilho);
    const gap = (a, b) => Math.max(16, (a.s.lw + b.s.lw) / 2 + 10);
    esq.forEach((it, i) => { it.s[it.campo] = i ? esq[i - 1].s[esq[i - 1].campo] + gap(esq[i - 1], it) : P.x + 16; });
    for (let i = dir.length - 1; i >= 0; i--) {
      const it = dir[i];
      it.s[it.campo] = i === dir.length - 1 ? P.x + P.w - 16 : dir[i + 1].s[dir[i + 1].campo] - gap(it, dir[i + 1]);
    }
    livre[tag] = {
      lo: esq.length ? esq[esq.length - 1].s[esq[esq.length - 1].campo] + 14 : P.x + 8,
      hi: dir.length ? dir[0].s[dir[0].campo] - 14 : P.x + P.w - 8,
    };
  });

  // Geometria
  setas.forEach(s => {
    const A = L.pos[s.de], B = L.pos[s.para];
    if (s.tipo === 'lado') {
      const praDir = B.x > A.x, par = setas.some(o => o.de === s.para && o.para === s.de);
      const y = A.y + A.h / 2 + (par ? (praDir ? -13 : 13) : 0);
      const x1 = praDir ? A.x + A.w + 2 : A.x - 2, x2 = praDir ? B.x - 2 : B.x + B.w + 2;
      const pt = ponta(x2, y, praDir ? 'dir' : 'esq', s.lw);
      s.d = `M${n1(x1)},${n1(y)} L${n1(praDir ? x2 - pt.recuo : x2 + pt.recuo)},${n1(y)}`;
      s.seta = pt.d;
      s.em = t => [x1 + (x2 - x1) * t, y];
      s.etqY = par && !praDir ? y + s.lw / 2 + 14 : y - s.lw / 2 - 14;
      s.etqX = (x1 + x2) / 2;
    } else if (s.tipo === 'arco') {
      const hc = (L.base + s.trilho * FX.TRILHO) / .75, yb = L.y0;
      const pt = ponta(s.xPara, yb - 1, 'baixo', s.lw);
      Object.assign(s, cubica([s.xDe, yb], [s.xDe, yb - hc], [s.xPara, yb - hc], [s.xPara, yb - 1 - pt.recuo]));
      s.seta = pt.d;
    } else {
      const desce = B.row > A.row;
      const ys = desce ? A.y + A.h : A.y, ye = desce ? B.y - 1 : B.y + B.h + 1, ym = (ys + ye) / 2;
      const pt = ponta(s.xPara, ye, desce ? 'baixo' : 'cima', s.lw);
      Object.assign(s, cubica([s.xDe, ys], [s.xDe, ym], [s.xPara, ym], [s.xPara, desce ? ye - pt.recuo : ye + pt.recuo]));
      s.seta = pt.d;
    }
  });
  entradas.forEach(s => {
    const P = L.pos[s.tag], { lo, hi } = livre[s.tag];
    s.nTxt = fNum(s.n);
    s.pTxt = 'entraram direto';
    s.ew = Math.ceil(12 + larguraTxt(s.nTxt, `600 12px ${fUi}`) + 5 + larguraTxt(s.pTxt, `400 11px ${fUi}`));
    const cx = Math.max(P.x + 4 + s.ew / 2, Math.min(P.x + P.w - 4 - s.ew / 2, (lo + hi) / 2));
    const pt = ponta(cx, L.y0 - 1, 'baixo', s.lw);
    s.d = `M${n1(cx)},${L.y0 - 20} L${n1(cx)},${n1(L.y0 - 1 - pt.recuo)}`;
    s.seta = pt.d;
    s.em = t => [cx, L.y0 - 20 + 19 * t];
    s.etq = [cx - s.ew / 2, L.y0 - 40, s.ew, 20];
  });

  // Etiquetas: primeiro as fixas (vãos e entradas), depois as demais,
  // da maior seta para a menor, no ponto da curva com menos conflito.
  const leg = legendaSvg(L);
  const ocupado = ORDEM_TAGS.map(tag => { const P = L.pos[tag]; return [P.x - 4, P.y - 4, P.w + 8, P.h + 8]; });
  ocupado.push([L.pos['lead-descartado'].x, L.y1 - 24, 56, 18]);
  if (leg.r) ocupado.push(leg.r);
  entradas.forEach(s => ocupado.push(s.etq));
  const amostras = [...setas, ...entradas].map(s => ({ k: s.k, pts: Array.from({ length: 81 }, (_, i) => s.em(i / 80)) }));
  const comEtq = setas.filter(s => !s.menor).sort((a, b) => b.leads - a.leads);
  comEtq.forEach(s => {
    s.nTxt = fNum(s.leads);
    s.pTxt = fPctSeta(s.leads, nos[s.de]);
    s.ew = Math.ceil(12 + larguraTxt(s.nTxt, `600 12px ${fUi}`) + 5 + larguraTxt(s.pTxt, `400 11px ${fMono}`));
  });
  comEtq.filter(s => s.tipo === 'lado').forEach(s => { s.etq = [s.etqX - s.ew / 2, s.etqY - 10, s.ew, 20]; ocupado.push(s.etq); });
  comEtq.filter(s => s.tipo !== 'lado').forEach(s => {
    const ts = s.tipo === 'arco' ? [.5, .44, .56, .38, .62, .3, .7] : [.5, .42, .58, .34, .66, .26, .74, .18, .82];
    let melhor = null;
    ts.forEach(t => {
      const [cx, cy] = s.em(t), rr = [cx - s.ew / 2, cy - 10, s.ew, 20];
      let c = Math.abs(t - .5) * 60;
      if (rr[0] < 0 || rr[1] < 0 || rr[0] + rr[2] > L.W || rr[1] + rr[3] > L.H) c += 1e6;
      ocupado.forEach(o => { if (cruza(rr, o)) c += 1e5; });
      amostras.forEach(a => { if (a.k !== s.k) a.pts.forEach(p => { if (dentro(p, rr, 3)) c += 40; }); });
      if (!melhor || c < melhor.c) melhor = { c, rr };
    });
    s.etq = melhor.rr;
    ocupado.push(s.etq);
  });

  // Desenho: setas (a maior por baixo), caixas, etiquetas, legenda
  let svg = `<svg class="fx-svg${tr ? ' has-sel' : ''}" width="${L.W}" height="${L.H}" viewBox="0 0 ${L.W} ${L.H}" role="img" aria-label="Fluxograma da jornada: ${fNum(total)} leads no período">`;
  svg += `<text class="fx-secao" x="${L.pos['lead-descartado'].x + 2}" y="${L.y1 - 10}">Saídas</text>`;
  [...setas].sort((a, b) => b.leads - a.leads).forEach(s => {
    const dash = s.volta ? ` stroke-dasharray="${n1(Math.max(4, s.lw * 1.4))} ${n1(Math.max(3, s.lw))}"` : '';
    svg += `<g class="fx-ar c-${CATALOGO[s.para].tipo}${s.menor ? ' is-menor' : ''}${on(s.k)}" data-ar="${s.k}">` +
      `<path class="fx-linha" d="${s.d}" stroke-width="${n1(s.lw)}"${dash}/>` +
      `<path class="fx-ponta" d="${s.seta}"/><path class="fx-hit" d="${s.d}"/></g>`;
  });
  entradas.forEach(s => {
    svg += `<g class="fx-ar c-etapa${s.menor ? ' is-menor' : ''}${on(s.k)}" data-ar="${s.k}">` +
      `<path class="fx-linha" d="${s.d}" stroke-width="${n1(s.lw)}"/><path class="fx-ponta" d="${s.seta}"/><path class="fx-hit" d="${s.d}"/></g>`;
  });

  ORDEM_TAGS.forEach(tag => {
    const P = L.pos[tag], c = CATALOGO[tag], n = nos[tag] || 0;
    let base = total, sub = 'dos leads';
    if (c.tipo === 'etapa') sub += ` · ${fNum(parados[tag] || 0)} parados aqui`;
    if (tag === 'carrinho-abandonado') {
      const volta = estado.arestas.get('carrinho-abandonado>carrinho-acessado');
      base = nos['carrinho-acessado'] || 0;
      sub  = `dos carrinhos · ${fNum(volta ? volta.leads : 0)} voltaram`;
    }
    const fr = base ? Math.min(1, n / base) : 0, bw = P.w - 32;
    const passos = tr && tr.passos.get(tag);
    const cls = 'fx-no' + (passos ? ' is-on' : '') + (estado.no === tag ? ' is-filtro' : '');
    svg += `<g class="${cls}" data-no="${tag}" tabindex="0" role="button" aria-label="${escHtml(c.label)}: ${fNum(n)} leads. Listar estes leads.">
      <rect class="fx-box" x="${n1(P.x)}" y="${P.y}" width="${n1(P.w)}" height="${P.h}" rx="6"/>
      ${c.tipo !== 'etapa' ? `<rect class="fx-faixa ${c.tipo}" x="${n1(P.x + 1)}" y="${P.y + 1}" width="4" height="${P.h - 2}" rx="2"/>` : ''}
      <text class="fx-titulo" x="${n1(P.x + 16)}" y="${P.y + 22}">${escHtml(c.label)}</text>
      <text x="${n1(P.x + 16)}" y="${P.y + 46}"><tspan class="fx-valor">${fNum(n)}</tspan><tspan class="fx-share" dx="8">${fPct(n, base)}</tspan></text>
      <text class="fx-sub" x="${n1(P.x + 16)}" y="${P.y + 64}">${sub}</text>
      <rect class="fx-trilho" x="${n1(P.x + 16)}" y="${P.y + 74}" width="${n1(bw)}" height="4" rx="2"/>
      ${fr ? `<rect class="fx-barra ${c.tipo}" x="${n1(P.x + 16)}" y="${P.y + 74}" width="${n1(Math.max(4, bw * fr))}" height="4" rx="2"/>` : ''}
      ${passos ? passoBadge(P, passos) : ''}
    </g>`;
  });

  comEtq.forEach(s => {
    svg += `<g class="fx-ar c-${CATALOGO[s.para].tipo}${on(s.k)}" data-ar="${s.k}">${etqSvg(s.etq, s.nTxt, s.pTxt, 'fx-etq-p')}</g>`;
  });
  entradas.forEach(s => {
    svg += `<g class="fx-ar c-etapa${on(s.k)}" data-ar="${s.k}">${etqSvg(s.etq, s.nTxt, s.pTxt, 'fx-etq-t')}</g>`;
  });
  svg += leg.svg + '</svg>';
  box.innerHTML = svg;
  focoAtual = null;
}

// Passar o mouse: a etapa (ou seta) e as ligadas a ela ficam, o resto
// apaga. Desligado enquanto há trilha de cliente destacada.
function focar(alvo) {
  const svg = document.querySelector('#fluxo svg');
  if (!svg || svg.classList.contains('has-sel') || alvo === focoAtual) return;
  focoAtual = alvo;
  svg.classList.toggle('foco', !!alvo);
  const ars = new Set(), nosF = new Set();
  if (alvo && alvo.dataset.no) {
    const t = alvo.dataset.no;
    nosF.add(t);
    svg.querySelectorAll('[data-ar]').forEach(g => {
      const [a, b] = g.dataset.ar.split('>');
      if (a === t || b === t) { ars.add(g.dataset.ar); nosF.add(a); nosF.add(b); }
    });
  } else if (alvo) {
    const [a, b] = alvo.dataset.ar.split('>');
    ars.add(alvo.dataset.ar); nosF.add(a); nosF.add(b);
  }
  svg.querySelectorAll('[data-ar]').forEach(g => g.classList.toggle('is-foco', ars.has(g.dataset.ar)));
  svg.querySelectorAll('[data-no]').forEach(g => g.classList.toggle('is-foco', nosF.has(g.dataset.no)));
}

function passoBadge(P, passos) {
  const txt = passos.join(' · '), w = 16 + txt.length * 7;
  const x = P.x + P.w - w - 8, y = P.y + 8;
  return `<rect class="fx-passo" x="${x}" y="${y}" width="${w}" height="20" rx="4"/>
    <text class="fx-passo-txt" x="${x + w / 2}" y="${y + 14}" text-anchor="middle">${txt}</text>`;
}

// Tabela equivalente ao fluxograma (mesmos números, leitura sem cor/forma)
function renderFluxoTabela() {
  const r = estado.res, total = r.kpis.leads, nos = r.nos || {};
  const linhas = [];
  Object.entries(r.entradas || {}).sort((a, b) => b[1] - a[1]).forEach(([tag, n]) => {
    linhas.push(`<tr><td>Início da jornada</td><td>${escHtml(rotulo(tag))}</td><td class="num">${fNum(n)}</td><td class="num">${fPct(n, total)}</td><td class="num">—</td></tr>`);
  });
  [...estado.arestas.values()]
    .sort((x, y) => ORDEM_TAGS.indexOf(x.de) - ORDEM_TAGS.indexOf(y.de) || y.leads - x.leads)
    .forEach(e => {
      linhas.push(`<tr><td>${escHtml(rotulo(e.de))}</td><td>${escHtml(rotulo(e.para))}</td><td class="num">${fNum(e.leads)}</td><td class="num">${fPct(e.leads, nos[e.de])}</td><td class="num">${fDur(e.mediana)}</td></tr>`);
    });
  Object.entries(r.parados || {}).forEach(([tag, n]) => {
    linhas.push(`<tr><td>${escHtml(rotulo(tag))}</td><td>(parados, sem próxima tag)</td><td class="num">${fNum(n)}</td><td class="num">${fPct(n, nos[tag])}</td><td class="num">—</td></tr>`);
  });
  document.getElementById('fluxo-tabela').innerHTML = `
    <div class="tbl-scroll"><table class="bi-table">
      <thead><tr><th>De</th><th>Para</th><th class="num">Leads</th><th class="num">% de quem estava na origem</th><th class="num">Tempo mediano</th></tr></thead>
      <tbody>${linhas.join('') || '<tr><td colspan="5" class="td-vazio">Nenhum lead começou a jornada neste período.</td></tr>'}</tbody>
    </table></div>`;
}

function renderLegenda() {
  const r = estado.res, total = r.kpis.leads, nos = r.nos || {};
  const menores = [...estado.arestas.values()].filter(e => !ehPrincipal(e.leads, nos[e.de] || 0, total));
  set('n-setas', estado.arestas.size || '');
  let txt = 'O número de cada seta conta os leads que passaram direto de uma etapa para a outra; o % é sobre quem chegou na etapa de origem. ' +
    '"Parados" são leads cuja última tag é aquela etapa. Passe o mouse numa etapa para isolar as setas dela; clique para listar os leads que passaram por ela.';
  if (estado.vista === 'fluxo' && estado.setas === 'principais' && menores.length) {
    const soma = menores.reduce((a, e) => a + e.leads, 0);
    txt += ` ${menores.length} ${menores.length === 1 ? 'seta pequena' : 'setas pequenas'} (${fNum(soma)} ${soma === 1 ? 'lead' : 'leads'} no total) ficam em "Todas" e na Tabela.`;
  }
  set('fluxo-legenda', total
    ? txt
    : 'Nenhum lead começou a jornada neste período. Os números aparecem conforme o sistema grava as tags; ' +
      'para ver a tela com dados, use o Modo demonstração.');

  const outras = Object.entries(r.outras_tags || {}).sort((a, b) => b[1] - a[1]);
  const el = document.getElementById('fluxo-outras');
  el.hidden = !outras.length;
  el.innerHTML = outras.length
    ? 'Tags fora do fluxograma neste período (aparecem na trilha do cliente): ' +
      outras.map(([tag, n]) => `<code>${escHtml(tag)}</code> (${fNum(n)} ${n === 1 ? 'lead' : 'leads'})`).join(', ') + '.'
    : '';
}

function renderVista() {
  if (estado.vista === 'tabela') renderFluxoTabela(); else renderFluxo();
}

function setVista(v) {
  estado.vista = v;
  document.querySelectorAll('[data-vista]').forEach(b => {
    const ativo = b.dataset.vista === v;
    b.classList.toggle('is-active', ativo);
    b.setAttribute('aria-pressed', ativo ? 'true' : 'false');
  });
  document.getElementById('fluxo').hidden = v !== 'fluxo';
  document.getElementById('fluxo-tabela').hidden = v !== 'tabela';
  document.getElementById('seg-setas').hidden = v !== 'fluxo';   // a Tabela já traz todas
  if (estado.res) { renderVista(); renderLegenda(); }
}

function setSetas(v) {
  estado.setas = v;
  document.querySelectorAll('[data-setas]').forEach(b => {
    const ativo = b.dataset.setas === v;
    b.classList.toggle('is-active', ativo);
    b.setAttribute('aria-pressed', ativo ? 'true' : 'false');
  });
  if (!estado.res) return;
  renderFluxo();
  renderLegenda();
}

// ── Tooltip (componente compartilhado .bi-tooltip) ─────────────
const tip = document.getElementById('chart-tooltip');
function linhaTip(label, val, sep) {
  return `<div class="bi-tooltip-row${sep ? ' sep' : ''}"><span class="bi-tooltip-label">${escHtml(label)}</span><span class="bi-tooltip-val">${escHtml(val)}</span></div>`;
}
function htmlTipNo(tag) {
  const r = estado.res, n = (r.nos || {})[tag] || 0, c = CATALOGO[tag];
  let h = `<div class="bi-tooltip-title">${escHtml(c.label)}</div>`;
  h += linhaTip('Leads que chegaram', fNum(n));
  h += linhaTip('% dos leads', fPct(n, r.kpis.leads));
  if (c.tipo === 'etapa') h += linhaTip('Parados aqui', fNum((r.parados || {})[tag] || 0));
  if (tag !== 'lead-criado' && (r.entradas || {})[tag]) h += linhaTip('Começaram aqui', fNum(r.entradas[tag]));
  [...estado.arestas.values()].filter(e => e.de === tag).sort((x, y) => y.leads - x.leads)
    .forEach((e, i) => { h += linhaTip('→ ' + rotulo(e.para), fNum(e.leads), i === 0); });
  h += `<div class="bi-tooltip-period">Clique para listar estes leads</div>`;
  return h;
}
function htmlTipAresta(k) {
  const r = estado.res, [a, b] = k.split('>');
  if (a === 'inicio') {
    return `<div class="bi-tooltip-title">Entraram direto em ${escHtml(rotulo(b))}</div>` +
      linhaTip('Leads', fNum((r.entradas || {})[b] || 0)) +
      `<div class="bi-tooltip-period">A primeira tag do lead já foi esta, sem "Lead criado" antes</div>`;
  }
  const e = estado.arestas.get(k);
  return `<div class="bi-tooltip-title">${escHtml(rotulo(a))} → ${escHtml(rotulo(b))}</div>` +
    linhaTip('Leads', fNum(e.leads)) +
    linhaTip('% de quem chegou na origem', fPct(e.leads, (r.nos || {})[a])) +
    linhaTip('Tempo mediano entre as tags', fDur(e.mediana));
}
function mostrarTip(html, x, y) {
  tip.innerHTML = `<div class="bi-tooltip-inner">${html}</div>`;
  tip.classList.add('visible');
  tip.setAttribute('aria-hidden', 'false');
  const b = tip.getBoundingClientRect();
  let left = x + 16, top = y + 16;
  if (left + b.width > innerWidth - 8)  left = x - b.width - 16;
  if (top + b.height > innerHeight - 8) top = y - b.height - 16;
  tip.style.left = Math.max(8, left) + 'px';
  tip.style.top  = Math.max(8, top) + 'px';
}
function esconderTip() { tip.classList.remove('visible'); tip.setAttribute('aria-hidden', 'true'); }

const fluxoEl = document.getElementById('fluxo');
function alvoTip(ev) {
  const no = ev.target.closest('[data-no]'); if (no) return htmlTipNo(no.dataset.no);
  const ar = ev.target.closest('[data-ar]'); if (ar) return htmlTipAresta(ar.dataset.ar);
  return null;
}
fluxoEl.addEventListener('mousemove', ev => {
  const h = alvoTip(ev); h ? mostrarTip(h, ev.clientX, ev.clientY) : esconderTip();
  focar(ev.target.closest('[data-no],[data-ar]'));
});
fluxoEl.addEventListener('mouseleave', () => { esconderTip(); focar(null); });
fluxoEl.addEventListener('focusin', ev => {
  const no = ev.target.closest('[data-no]'); if (!no) return;
  const b = no.getBoundingClientRect();
  mostrarTip(htmlTipNo(no.dataset.no), b.right - 24, b.bottom - 8);
});
fluxoEl.addEventListener('focusout', esconderTip);
fluxoEl.addEventListener('click', ev => {
  const no = ev.target.closest('[data-no]'); if (no) setNoFiltro(estado.no === no.dataset.no ? null : no.dataset.no);
});
fluxoEl.addEventListener('keydown', ev => {
  const no = ev.target.closest('[data-no]');
  if (no && (ev.key === 'Enter' || ev.key === ' ')) { ev.preventDefault(); setNoFiltro(estado.no === no.dataset.no ? null : no.dataset.no); }
});

// ══════════════════════════════════════════════════════════════
//  LISTA DE JORNADAS — "Situação" já diz a última tag de quem saiu do
//  fluxo; para quem está em andamento, mostra onde parou.
// ══════════════════════════════════════════════════════════════
const COLUNAS = [
  { key: 'nome',    label: 'Cliente' },
  { key: 'entrada', label: 'Entrou em' },
  { key: 'sit',     label: 'Situação' },
  { key: 'dur',     label: 'Duração', num: true, title: 'Da primeira à última tag do fluxograma' },
];
const LIMITE_LINHAS = 150;

function linhasJornada() {
  return (estado.res.linhas || []).map(x => ({
    ...x, nomeExib: nomeDe(x.nome, x.id),
    ordemSit: ORDEM_SIT.indexOf(x.situacao) * 100 + ORDEM_TAGS.indexOf(x.ultima),
    dur: x.duracao === null ? -1 : x.duracao,
  }));
}

function renderSegSituacao(rows) {
  const cont = { todas: rows.length };
  rows.forEach(x => { cont[x.situacao] = (cont[x.situacao] || 0) + 1; });
  document.getElementById('seg-sit').innerHTML = SITUACOES.map(([k, label]) =>
    `<button type="button" class="seg-btn${estado.sit === k ? ' is-active' : ''}" aria-pressed="${estado.sit === k}" onclick="setSit('${k}')">${label}<span class="seg-n">${fNum(cont[k] || 0)}</span></button>`
  ).join('');
}

function renderJornadas() {
  let rows = linhasJornada();
  if (estado.no) rows = rows.filter(x => x.etapas.includes(estado.no));
  renderSegSituacao(rows);
  if (estado.sit !== 'todas') rows = rows.filter(x => x.situacao === estado.sit);

  const k = estado.sortKey, dir = estado.sortDir;
  const campo = { nome: 'nomeExib', entrada: 'entrada', sit: 'ordemSit', dur: 'dur' }[k];
  rows.sort((a, b) => {
    const va = a[campo], vb = b[campo];
    return (typeof va === 'string' ? va.localeCompare(vb, 'pt-BR') : va - vb) * dir;
  });

  document.getElementById('thead-jornadas').innerHTML = '<tr>' + COLUNAS.map(c =>
    `<th class="${c.num ? 'num' : ''}${c.key === k ? ' th-active' : ''}"${c.title ? ` title="${c.title}"` : ''} onclick="ordenar('${c.key}')">${c.label}${c.key === k ? (dir > 0 ? ' ▲' : ' ▼') : ''}</th>`
  ).join('') + '</tr>';

  const chip = document.getElementById('chip-no');
  chip.classList.toggle('visible', !!estado.no);
  chip.innerHTML = estado.no ? `Passaram por: <strong>${escHtml(rotulo(estado.no))}</strong> &#x2715;` : '';

  const selId = estado.sel && estado.sel.lead.id_bitrix;
  const tb = document.getElementById('tbody-jornadas');
  tb.innerHTML = rows.length
    ? rows.slice(0, LIMITE_LINHAS).map(x => `
      <tr data-id="${escHtml(x.id)}" class="${selId === x.id ? 'is-sel' : ''}">
        <td><div class="cli-nome">${escHtml(x.nomeExib)}</div><div class="cli-id">${x.nome ? '#' + escHtml(x.id) : 'sem dados na leadV2'}</div></td>
        <td class="td-nowrap">${fDataHora(x.entrada)}</td>
        <td><span class="st ${x.situacao}">${SIT_LABEL[x.situacao]}</span>${x.situacao === 'andamento' ? `<div class="cli-sub">parou em ${escHtml(rotulo(x.ultima))}</div>` : ''}</td>
        <td class="num td-nowrap">${x.duracao === null ? '—' : fDur(x.duracao)}</td>
      </tr>`).join('')
    : `<tr><td colspan="${COLUNAS.length}" class="td-vazio">Nenhum lead nesta combinação de filtros.</td></tr>`;

  const partes = [];
  if (rows.length > LIMITE_LINHAS) partes.push(`Mostrando ${fNum(LIMITE_LINHAS)} de ${fNum(rows.length)} leads; para achar alguém específico, use a busca de cliente nos filtros.`);
  else if (rows.length) partes.push(`${fNum(rows.length)} ${rows.length === 1 ? 'lead' : 'leads'}. Clique numa linha para ver a trilha.`);
  if (estado.res.linhas_total > (estado.res.linhas || []).length) {
    partes.push(`A lista traz os ${fNum(estado.res.linhas.length)} leads mais recentes de ${fNum(estado.res.linhas_total)}; os indicadores e o fluxograma contam todos.`);
  }
  set('tbl-nota', partes.join(' '));
}

document.getElementById('tbody-jornadas').addEventListener('click', ev => {
  const tr = ev.target.closest('tr[data-id]'); if (!tr) return;
  if (estado.sel && estado.sel.lead.id_bitrix === tr.dataset.id) setSel(null);
  else abrirTrilha(tr.dataset.id);
});

function ordenar(key) {
  if (estado.sortKey === key) estado.sortDir *= -1;
  else { estado.sortKey = key; estado.sortDir = key === 'nome' || key === 'sit' ? 1 : -1; }
  renderJornadas();
}
function setSit(s) { estado.sit = s; renderJornadas(); }
function setNoFiltro(tag) { estado.no = tag; renderJornadas(); if (estado.vista === 'fluxo') renderFluxo(); }

// ══════════════════════════════════════════════════════════════
//  TRILHA DO CLIENTE
// ══════════════════════════════════════════════════════════════
function situacaoDeEventos(eventos) {
  const cat = eventos.filter(e => CATALOGO[e.tag]);
  if (!cat.length) return null;
  if (cat.some(e => e.tag === 'pagamento-confirmado')) return 'pagou';
  const u = cat[cat.length - 1].tag;
  return u === 'carrinho-abandonado' ? 'abandonou' : u === 'lead-descartado' ? 'descartado' : 'andamento';
}

function naCoorte(sel) {
  const cat = sel.eventos.filter(e => CATALOGO[e.tag]);
  if (!cat.length || !estado.periodo) return false;
  const d = cat[0].data.slice(0, 10);
  return d >= estado.periodo.ini && d <= estado.periodo.fim;
}

function renderTrilha() {
  const el = document.getElementById('trilha'), bar = document.getElementById('sel-bar');
  const s = estado.sel;
  if (!s) {
    bar.classList.remove('visible');
    el.innerHTML = `<div class="trilha-vazia"><strong>Nenhum cliente selecionado</strong>
      Clique numa linha da lista ou busque pelo nome, e-mail, telefone ou ID do Bitrix nos filtros.</div>`;
    return;
  }
  const l = s.lead, nome = nomeDe(l.nome, l.id_bitrix), ev = s.eventos;
  bar.innerHTML = `<span>Trilha de <strong>${escHtml(nome)}</strong> destacada no fluxograma (${ev.length} ${ev.length === 1 ? 'tag' : 'tags'})` +
    (naCoorte(s) ? '' : ' · este lead não começou a jornada no período filtrado; os números continuam sendo dos leads do período') +
    `</span><button type="button" class="btn-sel-limpar" onclick="setSel(null)">Limpar seleção</button>`;
  bar.classList.add('visible');

  const contato = [l.email, l.telefone].filter(Boolean).map(escHtml).join(' · ');
  const bitrix  = l.link_bitrix
    ? `<a href="${escHtml(l.link_bitrix)}" target="_blank" rel="noopener">Lead #${escHtml(l.id_bitrix)} no Bitrix &#x2197;</a>`
    : `Lead #${escHtml(l.id_bitrix)}`;
  let html = `<div class="trilha-nome">${escHtml(nome)}</div>
    <div class="trilha-meta">
      ${contato ? `<div>${contato}</div>` : ''}
      <div>${l.responsavel ? 'Responsável: ' + escHtml(l.responsavel) + ' · ' : ''}${bitrix}</div>
    </div>`;
  if (!l.nome && l.id_leadV2 === null) {
    html += '<div class="trilha-aviso">Lead ainda não sincronizado na leadV2: nome, e-mail e responsável aparecem quando o espelho do CRM alcançar.</div>';
  }
  if (!ev.length) {
    el.innerHTML = html + '<div class="trilha-vazia"><strong>Nenhuma tag registrada</strong>Este lead não tem nenhuma tag na lead_tag.</div>';
    return;
  }

  const sit = situacaoDeEventos(ev), agora = Date.now();
  const marca = tag => (!CATALOGO[tag] ? ' fora' : CATALOGO[tag].tipo !== 'etapa' ? ' ' + CATALOGO[tag].tipo : '');
  const delta = i => {
    if (!i) return 'início';
    const seg = (tsDe(ev[i].data) - tsDe(ev[i - 1].data)) / 1000;
    return seg < 60 ? 'no mesmo minuto' : '+' + fDur(seg);
  };
  html += `<div class="trilha-resumo">${sit ? `<span class="st ${sit}">${SIT_LABEL[sit]}</span>` : '<span>Sem tag do fluxograma</span>'}
      <span>Na jornada há ${fDur((agora - tsDe(ev[0].data)) / 1000)} · última tag ${fDur((agora - tsDe(ev[ev.length - 1].data)) / 1000)} atrás</span></div>
    <ol class="trilha-lista">
      ${ev.map((e, i) => `
        <li class="trilha-item">
          <span class="trilha-marca${marca(e.tag)}"></span>
          <div class="trilha-l1"><span class="trilha-tag">${i + 1}. ${escHtml(rotulo(e.tag))}</span><span class="trilha-delta">${delta(i)}</span></div>
          <div class="trilha-quando">${fDataHora(e.data, true)}${CATALOGO[e.tag] ? ' · ' + escHtml(e.tag) : ' · fora do fluxograma'}</div>
          ${e.descricao ? `<div class="trilha-desc">${escHtml(e.descricao)}</div>` : ''}
        </li>`).join('')}
    </ol>`;
  el.innerHTML = html;
}

function setSel(trilha) {
  estado.sel = trilha || null;
  cliInput.value = trilha ? nomeDe(trilha.lead.nome, trilha.lead.id_bitrix) : '';
  document.getElementById('cli-clear').classList.toggle('visible', !!trilha);
  fecharBusca();
  if (estado.res) {
    if (estado.vista === 'fluxo') renderFluxo();
    renderJornadas();
  }
  renderTrilha();
}

// Dois cliques seguidos em linhas diferentes: só a última trilha pedida abre.
let _seqTrilha = 0;
async function abrirTrilha(id) {
  const seq = ++_seqTrilha;
  if (modoDemo) { setSel(trilhaDemo(id)); return; }
  try {
    const res  = await fetch(API_TRILHA + '?id=' + encodeURIComponent(id) + COM_TO, { cache: 'no-store' });
    const data = await lerJson(res);
    if (seq === _seqTrilha) setSel(data);
  } catch (err) {
    if (seq === _seqTrilha) mostrarErro('Erro ao carregar a trilha: ' + err.message);
  }
}

// ── Busca de cliente (lead_tag inteira, não só a coorte) ───────
const cliInput = document.getElementById('cli-input');
const cliDrop  = document.getElementById('cli-dropdown');
let cliOpcoes = [], cliFoco = -1, cliTimer = null, cliSeq = 0;

function fecharBusca() { cliDrop.classList.remove('open'); cliInput.setAttribute('aria-expanded', 'false'); cliFoco = -1; }
function limparBusca() { cliInput.value = ''; cliOpcoes = []; document.getElementById('cli-clear').classList.remove('visible'); fecharBusca(); }
function desenharBusca(msgVazio) {
  cliDrop.innerHTML = cliOpcoes.length
    ? cliOpcoes.map((o, i) => `<div class="camp-option${i === cliFoco ? ' focused' : ''}" role="option" data-i="${i}">${escHtml(nomeDe(o.nome, o.id_bitrix))} <span class="cli-opt-sub">${o.nome ? '#' + escHtml(o.id_bitrix) + (o.email ? ' · ' + escHtml(o.email) : '') : 'sem dados na leadV2'}</span></div>`).join('')
    : `<div class="camp-empty">${msgVazio || 'Nenhum cliente com tag encontrado'}</div>`;
  cliDrop.classList.add('open');
  cliInput.setAttribute('aria-expanded', 'true');
}
async function buscarClientes(q) {
  const seq = ++cliSeq;
  if (modoDemo) { cliOpcoes = buscaDemo(q); desenharBusca(); return; }
  desenharBusca('Buscando…');
  try {
    const resp = estado.resp ? '&responsavel=' + encodeURIComponent(estado.resp) : '';
    const res  = await fetch(API_BUSCA + '?q=' + encodeURIComponent(q) + resp + COM_TO, { cache: 'no-store' });
    const data = await lerJson(res);
    if (seq !== cliSeq) return; // resposta de uma busca já substituída
    cliOpcoes = Array.isArray(data) ? data : [];
    desenharBusca();
  } catch (err) {
    if (seq === cliSeq) { cliOpcoes = []; desenharBusca('Erro na busca: ' + escHtml(err.message)); }
  }
}
cliInput.addEventListener('input', () => {
  cliFoco = -1;
  document.getElementById('cli-clear').classList.toggle('visible', !!cliInput.value);
  clearTimeout(cliTimer);
  const q = cliInput.value.trim();
  if (q.length < 2) { cliSeq++; fecharBusca(); return; }
  cliTimer = setTimeout(() => buscarClientes(q), 250);
});
cliInput.addEventListener('keydown', ev => {
  if (!cliDrop.classList.contains('open') || !cliOpcoes.length) { if (ev.key === 'Escape') fecharBusca(); return; }
  if (ev.key === 'ArrowDown') { ev.preventDefault(); cliFoco = Math.min(cliOpcoes.length - 1, cliFoco + 1); desenharBusca(); }
  else if (ev.key === 'ArrowUp') { ev.preventDefault(); cliFoco = Math.max(0, cliFoco - 1); desenharBusca(); }
  else if (ev.key === 'Enter') { ev.preventDefault(); escolherCliente(cliOpcoes[Math.max(0, cliFoco)]); }
  else if (ev.key === 'Escape') fecharBusca();
});
cliDrop.addEventListener('mousedown', ev => {
  const o = ev.target.closest('[data-i]'); if (!o) return;
  ev.preventDefault();
  escolherCliente(cliOpcoes[Number(o.dataset.i)]);
});
function escolherCliente(o) { if (o) { fecharBusca(); abrirTrilha(o.id_bitrix); } }
document.addEventListener('mousedown', ev => { if (!ev.target.closest('#cli-wrap')) fecharBusca(); });
document.getElementById('cli-clear').addEventListener('click', () => { limparBusca(); setSel(null); });

// ══════════════════════════════════════════════════════════════
//  FILTROS / CARGA
// ══════════════════════════════════════════════════════════════

// Período sempre com as duas pontas: "Últimos N" > datas digitadas >
// Ano/Mês (vira intervalo contínuo). O backend só recebe data_inicio/data_fim.
function lerPeriodo() {
  const rel = relPeriod.getRange();
  if (rel) return { ini: rel.start, fim: rel.end, rotulo: relPeriod.label() };
  const hoje = isoLocal(new Date());
  let ini = getDateInput('data-inicio');
  let fim = getDateInput('data-fim');
  if (!ini && !fim) {
    const p = intervaloAnoMes(hoje);
    setEffectiveDate('data-inicio', p.ini);
    setEffectiveDate('data-fim', p.fim);
    return p;
  }
  ini = ini || fim;
  fim = fim || hoje;
  return { ini, fim, rotulo: fDataBr(ini) + ' – ' + fDataBr(fim) };
}

// Ano/Mês → intervalo contínuo: do 1º dia do menor mês do menor ano ao
// último dia do maior mês do maior ano (sem mês, os anos inteiros), até
// hoje. Anos ou meses salteados levam junto os do meio, que ficam marcados
// no filtro para o botão e a etiqueta mostrarem o que está valendo. Um mês
// que ainda não começou fica inteiro (e sai zerado). Mesma regra da view
// de vendas e do intervaloAnoMes() do controller. Datas no horário local.
function intervaloAnoMes(hoje) {
  const vAnos  = yearDropdown.getValue();
  const vMeses = monthDropdown.getValue();
  const anos   = (vAnos || NOW_YEAR).split(',').map(Number);
  const meses  = vMeses ? vMeses.split(',').map(Number) : [1, 12];
  const yIni = Math.min(...anos),  yFim = Math.max(...anos);
  const mIni = Math.min(...meses), mFim = Math.max(...meses);
  const faixa = (a, b) => Array.from({ length: b - a + 1 }, (_, i) => a + i);
  if (vAnos)  yearDropdown.select(faixa(yIni, yFim));
  if (vMeses) monthDropdown.select(faixa(mIni, mFim));

  const ini    = `${yIni}-${String(mIni).padStart(2, '0')}-01`;
  const fimMes = isoLocal(new Date(yFim, mFim, 0));
  const fim    = ini > hoje || fimMes < hoje ? fimMes : hoje;
  // Com um ano só, a etiqueta mostra o ano (e os meses); com vários, as datas.
  const rotulo = yIni === yFim
    ? [String(yIni)].concat(vMeses ? [faixa(mIni, mFim).map(m => MONTHS[m - 1]).join(', ')] : []).join(' · ')
    : fDataBr(ini) + ' – ' + fDataBr(fim);
  return { ini, fim, rotulo };
}

// Lê a resposta como JSON e transforma erro do servidor em mensagem
// legível (uma página de erro em HTML quebrava no JSON.parse com
// "Unexpected token '<'").
async function lerJson(res) {
  let data = null;
  try { data = JSON.parse(await res.text()); } catch (e) { /* não era JSON */ }
  if (data && data.error) throw new Error(data.error);
  if (!res.ok) throw new Error(`o servidor respondeu com erro (HTTP ${res.status}). Tente de novo em instantes.`);
  if (data === null) throw new Error('o servidor devolveu uma resposta inesperada. Tente de novo em instantes.');
  return data;
}

// Responsável na etiqueta e no subtítulo: o nome, "N responsáveis" ou todos.
function rotuloResp(resp) {
  if (!resp) return 'todos os responsáveis';
  const n = resp.split(',').length;
  return n === 1 ? resp : n + ' responsáveis';
}

function updateBadge(periodo, resp) {
  const partes = [periodo.rotulo];
  if (resp) partes.unshift(rotuloResp(resp));
  if (modoDemo) partes.unshift('Demonstração');
  set('filter-label-badge', partes.join(' · ').toUpperCase());
  set('sub-resp', rotuloResp(resp));
}

function mostrarErro(msg) {
  const errBan = document.getElementById('error-banner');
  errBan.textContent = '⚠️ ' + msg;
  errBan.style.display = 'block';
  errBan.classList.add('visible');
}

// Só a resposta do último Aplicar entra na tela: uma requisição mais lenta
// que chegue depois não sobrescreve a mais nova.
let _seqCarga = 0;

async function loadDashboard(periodo, resp) {
  const seq     = ++_seqCarga;
  const overlay = document.getElementById('loading-overlay');
  const errBan  = document.getElementById('error-banner');
  overlay.classList.remove('hidden');
  document.body.classList.add('loading');
  errBan.style.display = 'none';
  errBan.classList.remove('visible');

  try {
    let data;
    if (modoDemo) {
      data = montarPayloadDemo(periodo.ini, periodo.fim, resp);
    } else {
      const params = new URLSearchParams({ data_inicio: periodo.ini, data_fim: periodo.fim });
      if (resp) params.set('responsavel', resp);
      const res = await fetch(API_DADOS + '?' + params.toString(), { cache: 'no-store' });
      data = await lerJson(res);
    }
    if (seq !== _seqCarga) return;

    estado.res     = data;
    estado.arestas = new Map((data.arestas || []).map(a => [a.de + '>' + a.para, a]));
    estado.periodo = periodo;
    estado.no      = null;
    fillKpis(data);
    renderVista();
    renderLegenda();
    renderJornadas();
    renderTrilha();

    document.getElementById('last-updated').textContent = modoDemo
      ? 'Modo demonstração: leads fictícios'
      : 'Atualizado em: ' + new Date().toLocaleString('pt-BR');
  } catch (err) {
    if (seq !== _seqCarga) return;
    mostrarErro('Erro ao carregar dados: ' + err.message);
    console.error(err);
  } finally {
    if (seq === _seqCarga) {
      overlay.classList.add('hidden');
      document.body.classList.remove('loading');
    }
  }
}

function applyFilters() {
  const periodo = lerPeriodo();
  const resp    = respDropdown.getValue();
  // a busca de cliente passa a procurar dentro do novo responsável
  if (resp !== estado.resp) { cliSeq++; cliOpcoes = []; fecharBusca(); }
  estado.resp = resp;
  updateBadge(periodo, resp);
  loadDashboard(periodo, resp);
}

function clearFilters() {
  yearDropdown.reset();
  monthDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim', 'data-fim-clear');
  relPeriod.reset();
  respDropdown.reset();
  // volta ao padrão de abertura: mês atual e a Helena
  yearDropdown.select([NOW_YEAR]);
  monthDropdown.select([NOW_MONTH]);
  respDropdown.toggle(RESP_PADRAO);
  estado.sit = 'todas';
  limparBusca();
  estado.sel = null;
  applyFilters();
}

// Redesenha o fluxograma quando a LARGURA da área muda — inclusive quando
// a barra de rolagem da página aparece depois do primeiro desenho (o
// resize da janela não avisa disso, e sobrava uma rolagem horizontal).
let _resizeT = null, _larguraFluxo = 0;
new ResizeObserver(([e]) => {
  const w = Math.round(e.contentRect.width);
  if (!w || w === _larguraFluxo) return;
  _larguraFluxo = w;
  clearTimeout(_resizeT);
  _resizeT = setTimeout(() => { if (estado.res && estado.vista === 'fluxo') renderFluxo(); }, 120);
}).observe(fluxoEl);
// as etiquetas são medidas com a fonte da tela; se ela chegar depois do
// primeiro desenho, mede de novo
document.fonts.ready.then(() => { if (estado.res && estado.vista === 'fluxo') renderFluxo(); });

aplicarModoDemo();
renderTrilha();
applyFilters();
</script>
