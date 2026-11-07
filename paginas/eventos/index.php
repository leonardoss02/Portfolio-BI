<div class="dash-eventos">

<style>
/* Estilos específicos do Pipeline de Eventos, sobre css/bi-theme.css.
   Cores/variáveis reaproveitadas: --red/--gold/--consulta/--sinal/--fecho
   (zonas do funil), --green/--amber/--danger (ROI), --card/--border/--muted. */
.dash-eventos .valstrip-eventos {
  display: grid; grid-template-columns: .8fr 1fr 1.4fr; gap: 14px; margin-bottom: 16px;
}
@media (max-width: 1180px) {
  .dash-eventos .valstrip-eventos { grid-template-columns: 1fr 1fr; }
  .dash-eventos .valstrip-eventos .card-eventos-preview { grid-column: 1 / -1; }
}
@media (max-width: 700px) { .dash-eventos .valstrip-eventos { grid-template-columns: 1fr; } }
.dash-eventos .cost-bar-eventos {
  height: 7px; border-radius: 5px; background: var(--card2); overflow: hidden;
  display: flex; margin: 6px 0; border: 1px solid var(--border);
}
.dash-eventos .cost-bar-eventos .seg-campanha    { background: var(--red); }
.dash-eventos .cost-bar-eventos .seg-operacional { background: var(--gold); }
.dash-eventos .fin-value { font-size: 16px; }

/* Custo Total em destaque no topo do card (era o último item) + bolinha de
   cor ligando cada rótulo ao segmento correspondente da barra — antes não
   havia como saber, só olhando a barra, qual metade era Campanha e qual
   era Operacional. */
.dash-eventos .fin-total-row { display: flex; flex-direction: column; gap: 2px; padding-bottom: 12px; margin-bottom: 12px; border-bottom: 1px solid var(--border); }
.dash-eventos .fin-total-row .fin-value { font-size: 24px; }
.dash-eventos .fin-legend-label { display: inline-flex; align-items: center; gap: 6px; }
.dash-eventos .fin-legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.dash-eventos .fin-legend-dot.campanha    { background: var(--red); }
.dash-eventos .fin-legend-dot.operacional { background: var(--gold); }
.dash-eventos .fin-pct { font-size: 10px; color: var(--muted); font-weight: 600; margin-left: 4px; }

/* Card financeiro unificado: Faturamento Atual x Projeção + ROI, lado a
   lado com barra de progresso (% já realizado da projeção) — substitui os
   2 cards separados e desconectados que existiam antes. Barra em cor sólida
   (não degradê) e ROI como pill suave sem borda, reaproveitando .roi-cell/
   .rc-* já usados na tabela "Performance por Evento" abaixo. */
.dash-eventos .fin-unificado { display: grid; grid-template-columns: 1.15fr 1px 1fr; }
@media (max-width: 700px) { .dash-eventos .fin-unificado { grid-template-columns: 1fr; } }
.dash-eventos .fin-unificado > .fin-col { padding: 16px 18px; }
.dash-eventos .fin-unificado > .fin-sep { background: var(--border); }
@media (max-width: 700px) { .dash-eventos .fin-unificado > .fin-sep { display: none; } }
.dash-eventos .fat-label { font-size: 10px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }
.dash-eventos .fat-atual { font-family: var(--font-mono); font-weight: 700; font-size: 24px; letter-spacing: -.02em; color: var(--fecho); margin-top: 2px; }
.dash-eventos .fat-progress-track { height: 8px; border-radius: 5px; background: var(--card2); overflow: hidden; margin: 10px 0 6px; }
.dash-eventos .fat-progress-fill { height: 100%; border-radius: 5px; background: var(--fecho); transition: width .5s ease; }
.dash-eventos .fat-progress-note { font-size: 11.5px; color: var(--text); display: flex; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
.dash-eventos .fat-progress-note b { font-family: var(--font-mono); color: var(--muted); font-weight: 600; }
.dash-eventos .roi-row { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; }
.dash-eventos .roi-row + .roi-row { border-top: 1px solid var(--border); }
.dash-eventos .roi-row-label { font-size: 12px; font-weight: 600; color: var(--muted); }
.dash-eventos .roi-row .roi-cell { font-size: 15px; padding: 4px 10px; }

/* Prévia dos 2 últimos eventos + o próximo, em tabela — colunas e valores
   centralizados (a pedido do usuário, diferente do alinhamento padrão
   número-à-direita do resto do dashboard). O próximo evento ganha leve
   destaque de fundo (--gold-glow) + selo, sem duplicar cor em toda a linha. */
.dash-eventos .card-eventos-preview .bi-card-body { padding: 0; }
.dash-eventos .evt-prev-table { width: 100%; border-collapse: collapse; }
.dash-eventos .evt-prev-table th {
  font-size: 9.5px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted);
  font-weight: 700; text-align: center; padding: 8px 10px 6px; border-bottom: 1px solid var(--border);
}
.dash-eventos .evt-prev-table td {
  padding: 8px 10px; font-size: 12px; text-align: center; font-family: var(--font-mono);
  border-bottom: 1px solid var(--border);
}
.dash-eventos .evt-prev-table tr:last-child td { border-bottom: 0; }
.dash-eventos .evt-prev-name { font-weight: 700; color: var(--text); font-size: 12.5px; font-family: var(--font-ui); }
.dash-eventos .evt-prev-date { display: block; font-size: 10px; color: var(--muted); font-weight: 600; margin-top: 1px; font-family: var(--font-ui); }
.dash-eventos .evt-prev-table tr.proximo td { background: var(--gold-glow); }
.dash-eventos .evt-prev-table tr.proximo .evt-prev-name::after {
  content: 'PRÓXIMO'; font-size: 8.5px; font-weight: 700; letter-spacing: .05em;
  color: var(--gold); background: var(--card); border: 1px solid var(--gold);
  padding: 1px 6px; border-radius: 20px; margin-left: 7px; vertical-align: middle;
}
.dash-eventos .evt-prev-table td.pend { color: var(--muted); }
@media (max-width: 560px) {
  .dash-eventos .evt-prev-table { font-size: 11px; }
  .dash-eventos .evt-prev-table th, .dash-eventos .evt-prev-table td { padding: 6px 6px; }
}

/* Feedback de carregamento nos números financeiros — evita o "pulo" seco
   de "—" direto pro valor quando a resposta da API chega. */
@keyframes skel-shimmer { 0% { background-position: -120px 0; } 100% { background-position: 120px 0; } }
.dash-eventos .skel-shimmer {
  display: inline-block; height: 1em; width: 3.4em; border-radius: 4px;
  background: linear-gradient(90deg, var(--card2) 25%, var(--border) 50%, var(--card2) 75%);
  background-size: 240px 100%; animation: skel-shimmer 1.1s infinite linear; vertical-align: middle;
}

/* Funil de conversão em fluxograma, no vocabulário do fluxograma da
   Jornada do cliente (paginas/helenacastro/jornada.php). Caixas neutras:
   título 13, número 18, apoio 12. Cor só com significado: o bloco do
   funil aparece no ponto do rótulo e no filete de baixo (mesmos tokens do
   .kpi-chip), verde = venda final, vermelho = perdido. Setas: espessura
   pela raiz do volume da etapa de destino; a etiqueta traz só os 3
   percentuais do controller (pct_cadeira, pct_consulta, taxa_combo),
   porque as métricas não formam coorte (cada uma usa a própria data e o
   próprio pipe). Abaixo de 1000px o desenho rola na horizontal. */
.dash-eventos { --ink-2: #5f6a7d; --fu-forte: 55%; --fu-fraco: 30%; }
html.dark .dash-eventos { --ink-2: #8290a9; --fu-forte: 78%; --fu-fraco: 45%; }
.dash-eventos .fu-scroll { overflow-x: auto; }
.dash-eventos .fu-scroll svg { display: block; }
.dash-eventos .fu-scroll svg text { font-family: var(--font-ui); }
.dash-eventos .fu-no { outline: none; transition: opacity .15s ease; }
.dash-eventos .fu-box { fill: var(--card2); stroke: var(--border); stroke-width: 1; }
.dash-eventos .fu-no:hover .fu-box,
.dash-eventos .fu-no:focus-visible .fu-box { stroke: var(--ink-2); }
.dash-eventos .fu-faixa.ganho { fill: var(--fecho); }
.dash-eventos .fu-faixa.risco { fill: var(--danger); }
.dash-eventos .fu-titulo { fill: var(--text); font-size: 13px; font-weight: 600; }
.dash-eventos .fu-valor  { fill: var(--text); font-size: 18px; font-weight: 700; font-variant-numeric: tabular-nums; }
.dash-eventos .fu-sub    { fill: var(--ink-2); font-size: 12px; }
.dash-eventos .fu-sub-n  { fill: var(--text); font-weight: 600; font-variant-numeric: tabular-nums; }
.dash-eventos .fu-trilho { fill: var(--border); }
.dash-eventos .fu-barra  { fill: var(--fecho); }
.dash-eventos .fu-zona   { fill: var(--ink-2); font-size: 11px; font-weight: 600; }
.dash-eventos .fu-dot.aquisicao { fill: var(--red); }
.dash-eventos .fu-dot.evento    { fill: var(--gold); }
.dash-eventos .fu-dot.consulta  { fill: var(--consulta); }
.dash-eventos .fu-dot.sinal     { fill: var(--sinal); }
.dash-eventos .fu-dot.fecho     { fill: var(--fecho); }
.dash-eventos .fu-chave { stroke-width: 2; stroke-linecap: round; stroke: var(--border); }
.dash-eventos .fu-chave.consulta { stroke: color-mix(in srgb, var(--consulta) 55%, var(--card)); }
.dash-eventos .fu-chave.sinal    { stroke: color-mix(in srgb, var(--sinal) 55%, var(--card)); }

.dash-eventos .fu-ar { --fu-cor: var(--ink-2); --fu-mix: var(--fu-forte); transition: opacity .15s ease; }
.dash-eventos .fu-ar.c-ganho { --fu-cor: var(--fecho); }
.dash-eventos .fu-ar.c-risco { --fu-cor: var(--danger); }
.dash-eventos .fu-ar.is-zero { --fu-mix: var(--fu-fraco); }
.dash-eventos .fu-ar:hover,
.dash-eventos .fu-ar.is-foco { --fu-mix: 90%; }
.dash-eventos .fu-linha { fill: none; stroke: color-mix(in srgb, var(--fu-cor) var(--fu-mix), var(--card)); }
.dash-eventos .fu-ponta { fill: color-mix(in srgb, var(--fu-cor) var(--fu-mix), var(--card)); }
.dash-eventos .fu-hit   { fill: none; stroke: transparent; stroke-width: 14; }
.dash-eventos .fu-etq rect { fill: var(--card); stroke: color-mix(in srgb, var(--fu-cor) 45%, var(--card)); stroke-width: 1; }
.dash-eventos .fu-etq-p { fill: var(--text); font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums; }
.dash-eventos .fu-etq-t { fill: var(--ink-2); font-size: 11px; }
/* passar o mouse numa caixa ou seta: o resto apaga */
.dash-eventos .fu-svg.foco .fu-ar,
.dash-eventos .fu-svg.foco .fu-no { opacity: .16; }
.dash-eventos .fu-svg.foco .is-foco { opacity: 1; }

.dash-eventos .fu-legenda {
  display: flex; align-items: center; gap: 8px 20px; flex-wrap: wrap;
  margin-top: 12px; font-size: 12px; color: var(--ink-2);
}
.dash-eventos .fu-legenda[hidden] { display: none; }
.dash-eventos .fu-leg { display: inline-flex; align-items: center; gap: 8px; }
.dash-eventos .fu-leg svg { display: block; flex: none; }
.dash-eventos .fu-leg-l { fill: none; stroke: color-mix(in srgb, var(--ink-2) 55%, var(--card)); }
.dash-eventos .fu-leg-p { fill: color-mix(in srgb, var(--ink-2) 55%, var(--card)); }
.dash-eventos .fu-leg-c.ganho { fill: var(--fecho); }
.dash-eventos .fu-leg-c.risco { fill: var(--danger); }
.dash-eventos .fu-legt { fill: var(--ink-2); font-size: 12px; }
.dash-eventos .fu-nota { margin-top: 4px; font-size: 12px; color: var(--ink-2); }

/* Dica: componente compartilhado .bi-tooltip (fundo sempre escuro). A
   definição usa --hdr-meta, claro nos dois temas; por isso o #fu-tip
   fica dentro de .dash-eventos (fora dele herdava o --text escuro). */
.dash-eventos .fu-tip-def { margin-top: 8px; font-size: 12px; line-height: 1.45; color: var(--hdr-meta); max-width: 280px; }

@media (prefers-reduced-motion: reduce) {
  .dash-eventos .fu-no, .dash-eventos .fu-ar { transition: none; }
}

.dash-eventos .bottom-eventos { display: grid; grid-template-columns: 1fr 1.25fr; gap: 16px; margin-top: 16px; }
@media (max-width: 900px) { .dash-eventos .bottom-eventos { grid-template-columns: 1fr; } }
.dash-eventos .motivo-bar-row { display: grid; grid-template-columns: 130px 1fr 34px; align-items: center; gap: 8px; margin-bottom: 9px; }
.dash-eventos .motivo-bar-row .blbl {
  font-size: 11.5px; color: var(--text); font-weight: 600; text-align: right;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.dash-eventos .motivo-bar-row .bval { font-size: 11.5px; font-weight: 700; font-family: var(--font-mono); }
/* Motivo dominante em destaque — antes a lista era neutra, sem indicar
   qual descarte merece mais atenção do time. */
.dash-eventos .motivo-bar-row.top .blbl { color: var(--danger); }
.dash-eventos .motivo-bar-row.top .funil-bar-fill { background: var(--danger); }
.dash-eventos .roi-cell { border-radius: 6px; padding: 3px 8px; display: inline-block; min-width: 44px; font-family: var(--font-mono); font-weight: 700; }
.dash-eventos .rc-green  { background: var(--green-glow);  color: var(--green); }
.dash-eventos .rc-amber  { background: var(--amber-glow);  color: var(--amber); }
.dash-eventos .rc-danger { background: var(--danger-glow); color: var(--danger); }

/* Barra de magnitude do ROI embutida na célula da tabela — sem ela, um
   ROI alto num evento pequeno parecia visualmente igual a um ROI baixo
   num evento grande. Evento de maior faturamento marcado com ★. */
.dash-eventos .roi-bar-cell { display: flex; align-items: center; gap: 8px; justify-content: flex-end; }
.dash-eventos .roi-bar-track { width: 52px; height: 6px; border-radius: 4px; background: var(--card2); overflow: hidden; flex-shrink: 0; }
.dash-eventos .roi-bar-fill { height: 100%; border-radius: 4px; }
.dash-eventos .roi-bar-fill.rc-green  { background: var(--green); }
.dash-eventos .roi-bar-fill.rc-amber  { background: var(--amber); }
.dash-eventos .roi-bar-fill.rc-danger { background: var(--danger); }
.dash-eventos tr.destaque td:first-child { position: relative; padding-left: 22px; }
.dash-eventos tr.destaque td:first-child::before {
  content: '★'; position: absolute; left: 8px; color: var(--gold); font-size: 11px;
}
</style>

<!-- ── FILTROS ─────────────────────────────────────────────── -->
<section class="filters is-collapsible" aria-label="Filtros do dashboard">
  <button class="filters-toggle" type="button" aria-expanded="true" aria-controls="filters-fields">
    <span>Filtros</span>
    <span class="dd-caret filters-toggle-caret">&#x25BE;</span>
  </button>
  <div class="filters-fields" id="filters-fields">
  <div class="filter-pair">
    <span class="filter-label">Data do evento</span>
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
    <span class="filter-label">Evento</span>
    <div class="camp-wrap" id="evento-wrap">
      <div class="camp-tags" id="evento-tags"></div>
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="evento-input" class="camp-input" type="text" placeholder="Pesquisar evento..." autocomplete="off" aria-label="Pesquisar evento" aria-autocomplete="list" aria-controls="evento-dropdown">
        <button class="camp-clear" id="evento-clear" aria-label="Limpar evento" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-dropdown" id="evento-dropdown" role="listbox"></div>
    </div>
  </div>

  <button class="btn-apply" id="btn-apply" onclick="applyFilters()">Aplicar</button>
  <button class="btn-clear" id="btn-clear" onclick="clearFilters()">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'eventos' });</script>

<!-- ── LOADING ─────────────────────────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>

<!-- ── ERRO ────────────────────────────────────────────────── -->
<div id="error-banner" role="alert"></div>

<!-- Dica do funil (dentro de .dash-eventos, ver .fu-tip-def) -->
<div class="bi-tooltip" id="fu-tip" aria-hidden="true"><div class="bi-tooltip-inner" id="fu-tip-in"></div></div>

<!-- ── HEADER ──────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo">ME</div>
    <div>
      <div class="header-title">Pipeline de Vendas &amp; Custos de Eventos</div>
      <div class="header-sub">Canal OTR — Leads &rarr; Combos Vendidos</div>
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
    <span class="count-chip" id="evt-count">0 eventos</span>
    <span class="last-updated" id="last-updated">—</span>
    <div class="tab-switch">
      <a class="tab-switch-btn active" href="<?= $this->site ?>eventos<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Pipeline</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>eventos/detalhamento<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Consolidado</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>eventos/relatorio<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Relatório</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>eventos/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
    </div>
  </div>
</header>

<!-- ── CONTEÚDO PRINCIPAL ──────────────────────────────────── -->
<main class="main" id="dashboard-main" aria-label="Pipeline de Eventos">

  <div class="valstrip-eventos">
    <div class="bi-card">
      <div class="bi-card-header"><span class="bi-card-title">Custos (USD)</span></div>
      <div class="bi-card-body">
        <div class="fin-total-row">
          <span class="fin-label">Custo Total</span>
          <span class="fin-value invest" id="v-custo-total"><span class="skel-shimmer"></span></span>
        </div>
        <div class="fin-row"><span class="fin-label fin-legend-label"><span class="fin-legend-dot campanha"></span>Campanha</span><span class="fin-value" id="v-custo-campanha"><span class="skel-shimmer"></span></span></div>
        <div class="cost-bar-eventos"><div class="seg-campanha" id="bar-campanha" style="width:0%"></div><div class="seg-operacional" id="bar-operacional" style="width:0%"></div></div>
        <div class="fin-row"><span class="fin-label fin-legend-label"><span class="fin-legend-dot operacional"></span>Operacional</span><span class="fin-value" id="v-custo-operacional"><span class="skel-shimmer"></span></span></div>
      </div>
    </div>

    <!-- Faturamento (Atual x Projeção) e ROI unificados num único card, com
         barra de progresso mostrando quanto já foi realizado da projeção —
         antes eram 2 cards desconectados, sem comparação visual entre eles,
         e o ROI era um badge com borda colorida independente do valor. -->
    <div class="bi-card fin-unificado">
      <div class="fin-col">
        <div class="fat-label">Faturamento Atual</div>
        <div class="fat-atual" id="v-faturamento"><span class="skel-shimmer"></span></div>
        <div class="fat-progress-track"><div class="fat-progress-fill" id="fat-progress" style="width:0%"></div></div>
        <div class="fat-progress-note">
          <span id="fat-note">—</span>
          <b>Projeção <span id="v-projecao"><span class="skel-shimmer"></span></span></b>
        </div>
      </div>
      <div class="fin-sep"></div>
      <div class="fin-col">
        <div class="fat-label" style="margin-bottom:2px;">ROI</div>
        <div class="roi-row">
          <span class="roi-row-label">Atual</span>
          <span class="roi-cell" id="v-roi">—</span>
        </div>
        <div class="roi-row">
          <span class="roi-row-label">Projetado</span>
          <span class="roi-cell" id="v-roi-projetado">—</span>
        </div>
      </div>
    </div>

    <!-- Prévia dos 2 últimos eventos + o próximo — independente dos filtros
         da tela (sempre mostra os eventos reais mais recentes/futuro). -->
    <div class="bi-card card-eventos-preview">
      <div class="bi-card-header"><span class="bi-card-title">Últimos Eventos &amp; Próximo</span></div>
      <div class="bi-card-body">
        <table class="evt-prev-table">
          <thead>
            <tr>
              <th>Evento</th>
              <th>Invest.</th>
              <th>Qualif.</th>
              <th>Cadeiras</th>
              <th>Consultas</th>
              <th>Combos</th>
            </tr>
          </thead>
          <tbody id="tabela-eventos-preview">
            <tr><td colspan="6" style="color:var(--muted);">—</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="bi-card">
    <div class="bi-card-header"><span class="bi-card-title">Funil de Conversão</span></div>
    <div class="bi-card-body">
      <div class="fu-scroll" id="fu"></div>
      <div class="fu-legenda" id="fu-legenda" hidden></div>
      <div class="fu-nota" id="fu-nota"></div>
    </div>
  </div>

  <div class="bottom-eventos">
    <div class="bi-card">
      <div class="bi-card-header"><span class="bi-card-title">Motivo de Descarte — Comercial 2</span></div>
      <div class="bi-card-body" id="motivos-descarte">—</div>
    </div>
    <div class="bi-card">
      <div class="bi-card-header"><span class="bi-card-title">Performance por Evento</span></div>
      <div class="bi-card-body" style="max-height:280px;overflow-y:auto;padding:0;">
        <table class="bi-table">
          <thead><tr><th>Evento</th><th class="num">Faturamento</th><th class="num">ROI(x)</th><th class="num">ROI Projetado</th></tr></thead>
          <tbody id="tabela-performance"></tbody>
        </table>
      </div>
    </div>
  </div>

</main>

<script>
const API         = '<?= $this->site ?>eventos/dados';
const LISTA_API   = '<?= $this->site ?>eventos/lista';
const PREVIEW_API = '<?= $this->site ?>eventos/preview';

const YEARS  = ['2024', '2025', '2026', '2027'];
const MONTHS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

const yearDropdown  = createMultiDropdown('year-wrap',  'year-btn',  'year-list',  YEARS,  l => l, l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);

const NOW_YEAR = String(new Date().getFullYear());
yearDropdown.select([NOW_YEAR]);

// O widget de busca (createSearchDropdown) é o mesmo do executive, mas
// trabalha com strings — mapeamos rótulo -> id_evento aqui na página,
// já que a fonte de dados (lista de eventos) é específica do Eventos.
const eventoDropdown = createSearchDropdown(
  'evento-wrap', 'evento-input', 'evento-dropdown', 'evento-clear', 'evento-tags',
  'Nenhum evento encontrado'
);
let eventoRotuloParaId = {};

async function loadEventos() {
  try {
    const res  = await fetch(LISTA_API, { cache: 'no-store' });
    const data = await res.json();
    eventoRotuloParaId = {};
    (data.eventos || []).forEach(e => { eventoRotuloParaId[e.rotulo] = e.id; });
    eventoDropdown.load((data.eventos || []).map(e => e.rotulo));
  } catch (e) {
    console.warn('Não foi possível carregar a lista de eventos:', e.message);
  }
}

function getEventoIds() {
  const rotulos = eventoDropdown.getValue();
  if (!rotulos) return null;
  const ids = rotulos.split(',').map(r => eventoRotuloParaId[r]).filter(Boolean);
  return ids.length ? ids.join(',') : null;
}

function roiClass(v) {
  return v == null ? 'rc-danger' : (v >= 10 ? 'rc-green' : (v >= 5 ? 'rc-amber' : 'rc-danger'));
}

function esc(s) {
  return String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
}

// ══════════════════════════════════════════════════════════════
//  Funil de conversão em fluxograma (SVG). Mesma família visual do
//  fluxograma da Jornada do cliente: ponta(), cubica() e distribuir()
//  são cópias de paginas/helenacastro/jornada.php. Linha de cima = as 5
//  etapas; linha de baixo = consultas e FP em aberto e o FP perdido,
//  pendurados na etapa de cima por curvas em S.
// ══════════════════════════════════════════════════════════════
const ZONA = {
  aquisicao: 'Aquisição', evento: 'Evento', consulta: 'Consulta', sinal: 'Sinal & FP', fecho: 'Fechamento',
};

// Definições da dica (resumo de paginas/eventos/dicionario.php)
const DEF = {
  total_leads:         'Leads ligados ao evento. Um lead que preencheu mais de um formulário conta uma vez.',
  cadeiras:            'Ingressos vendidos para o evento presencial.',
  consultas_vendidas:  'Consultas compradas por leads do evento, pelo canal digital ou pelo pipe do evento.',
  consultas_a_agendar: 'Consulta ainda sem data.',
  consultas_agendadas: 'Consulta com data marcada e ainda sem registro de realização.',
  sinais_pagos:        'Clientes que pagaram a entrada depois da consulta.',
  fp_agendado:         'Financial Planning marcado e ainda não realizado.',
  fp_nao_agendado:     'Consulta realizada e FP ainda sem data.',
  fp_perdido:          'FP realizado, mas o card caiu em Comercial 3, Distrato ou Lead sem qualificação.',
  combos:              'Contratos do pacote de imigração assinados: a venda final do funil.',
};

const n1 = v => v.toFixed(1);
const _cv = document.createElement('canvas').getContext('2d');
function larguraTxt(txt, fonte) { _cv.font = fonte; return _cv.measureText(txt).width; }
let F = {};
function fontes() {
  const ui = getComputedStyle(document.documentElement).getPropertyValue('--font-ui').trim();
  F = { tit: `600 13px ${ui}`, sub: `400 12px ${ui}`, etqP: `700 13px ${ui}`, etqT: `400 11px ${ui}`, leg: `400 12px ${ui}` };
}
// Quebra em 2 linhas quando não cabe, no ponto que deixa as duas mais
// parecidas ("das pessoas / em sala", não "das pessoas em / sala").
function quebrar(txt, fonte, max) {
  const pal = txt.split(' ');
  if (pal.length < 2 || larguraTxt(txt, fonte) <= max) return [txt];
  let melhor = null;
  for (let i = 1; i < pal.length; i++) {
    const a = pal.slice(0, i).join(' '), b = pal.slice(i).join(' ');
    const m = Math.max(larguraTxt(a, fonte), larguraTxt(b, fonte));
    if (!melhor || m < melhor.m) melhor = { m, linhas: [a, b] };
  }
  return melhor.linhas;
}

// Ponta de seta com entalhe; `recuo` é onde a linha deve parar.
function ponta(x, y, dir, lw) {
  const h = Math.max(5, lw * .75 + 3.5), L = Math.max(9, lw * 1.1 + 6), e = L * .28;
  const pts = {
    baixo: [[x, y], [x - h, y - L], [x, y - L + e], [x + h, y - L]],
    dir:   [[x, y], [x - L, y - h], [x - L + e, y], [x - L, y + h]],
  }[dir];
  return { d: 'M' + pts.map(p => n1(p[0]) + ',' + n1(p[1])).join(' L') + ' Z', recuo: L - e };
}

// Cúbica com tangente vertical nas pontas
function cubica(p0, p1, p2, p3) {
  return `M${n1(p0[0])},${n1(p0[1])} C${n1(p1[0])},${n1(p1[1])} ${n1(p2[0])},${n1(p2[1])} ${n1(p3[0])},${n1(p3[1])}`;
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

// Grupos da linha de baixo: cada um centrado sob a caixa-mãe; se dois se
// encostam, o da direita anda; se passa da borda, todos voltam juntos.
function encaixar(grupos, lo, hi, gap) {
  const xs = grupos.map(g => Math.max(lo, g.ideal - g.w / 2));
  for (let i = 1; i < xs.length; i++) xs[i] = Math.max(xs[i], xs[i - 1] + grupos[i - 1].w + gap);
  const n = xs.length;
  if (n && xs[n - 1] + grupos[n - 1].w > hi) {
    xs[n - 1] = hi - grupos[n - 1].w;
    for (let i = n - 2; i >= 0; i--) xs[i] = Math.min(xs[i], xs[i + 1] - gap - grupos[i].w);
  }
  return xs;
}

// Caixa: título (1 ou 2 linhas), número, linhas de apoio ("subs", que
// quebram em 2 quando não cabem) e, no Fechamento, a barra da taxa.
// Devolve o SVG do conteúdo e onde ele termina (para a altura).
function linhaValor(tl) { return 22 + 16 * (tl - 1) + 26; }
function conteudo(no, x, y, w, tl) {
  const px = x + 16, pr = x + w - 12;
  let s = no.tit.map((l, i) => `<text class="fu-titulo" x="${n1(px)}" y="${y + 22 + 16 * i}">${esc(l)}</text>`).join('');
  let yy = y + linhaValor(tl);
  s += `<text class="fu-valor" x="${n1(px)}" y="${yy}">${fNum(no.v)}</text>`;
  let primeira = true;
  (no.subs || []).forEach(sb => {
    const num = Array.isArray(sb) ? fNum(sb[0]) : null;
    quebrar(num ? num + ' ' + sb[1] : sb, F.sub, pr - px).forEach((l, j) => {
      yy += primeira ? 20 : 16;
      primeira = false;
      const txt = num && !j ? `<tspan class="fu-sub-n">${num}</tspan>${esc(l.slice(num.length))}` : esc(l);
      s += `<text class="fu-sub" x="${n1(px)}" y="${yy}">${txt}</text>`;
    });
  });
  if (no.barra != null) {
    yy += 10;
    const bw = pr - px;
    s += `<rect class="fu-trilho" x="${n1(px)}" y="${yy}" width="${n1(bw)}" height="4" rx="2"/>`;
    if (no.barra > 0) s += `<rect class="fu-barra" x="${n1(px)}" y="${yy}" width="${n1(Math.max(4, bw * Math.min(1, no.barra)))}" height="4" rx="2"/>`;
    yy += 4;
  }
  return { svg: s, fim: yy - y };
}

// Uma linha de caixas com título quebrado e altura comuns, para os
// números ficarem alinhados de uma caixa para a outra.
function montarLinha(specs, x0, y, larguras, gaps) {
  let x = x0;
  const nos = specs.map((s, i) => {
    const no = { ...s, x, y, w: larguras[i] };
    x += larguras[i] + (gaps[i] || 0);
    no.tit = quebrar(no.titulo, F.tit, no.w - 28);
    return no;
  });
  const tl = Math.max(1, ...nos.map(no => no.tit.length));
  const h = Math.max(...nos.map(no => conteudo(no, 0, 0, no.w, tl).fim)) + 14;
  nos.forEach(no => { no.tl = tl; no.h = h; });
  return nos;
}

// Espessura pela raiz (a maior não apaga as outras), de 1,5 a 10px
const espessura = (n, max) => 1.5 + 8.5 * Math.sqrt(Math.min(1, Math.max(0, n) / Math.max(1, max)));

let TIP = {};

// Setas entre caixas vizinhas, na altura do número
function setasLado(porK, defs, maxN) {
  return defs.map(d => {
    const A = porK[d.de], B = porK[d.para];
    const y = A.y + linhaValor(A.tl) - 6;
    const lw = espessura(d.n, maxN), x1 = A.x + A.w + 2, x2 = B.x - 2;
    const pt = ponta(x2, y, 'dir', lw);
    const s = {
      k: d.de + '>' + d.para, cor: d.cor || 'etapa', lw, zero: !d.n,
      d: `M${n1(x1)},${n1(y)} L${n1(x2 - pt.recuo)},${n1(y)}`, seta: pt.d,
    };
    if (d.pct != null) {
      // a base ("dos qualificados") sai quando nem em 2 linhas cabe no vão;
      // fica o % e a dica explica
      const base = quebrar(d.base, F.etqT, x2 - x1 - 20);
      const linhas = [fPct(d.pct), ...(base.every(l => larguraTxt(l, F.etqT) <= x2 - x1 - 20) ? base : [])];
      const ew = Math.ceil(Math.max(larguraTxt(linhas[0], F.etqP), ...linhas.slice(1).map(l => larguraTxt(l, F.etqT))) + 16);
      const eh = 22 + 14 * (linhas.length - 1);
      s.etq = { x: (x1 + x2) / 2 - ew / 2, y: y - lw / 2 - 6 - eh, w: ew, h: eh, linhas };
      TIP[s.k] = { titulo: d.tipTitulo, linhas: [['Percentual', fPct(d.pct)], [d.tipConta, `${fNum(d.num)} ÷ ${fNum(d.den)}`]], def: d.tipDef };
    }
    return s;
  });
}

// Linha de baixo: grupos de caixas pendurados na caixa-mãe por curvas em S
function linhaDeBaixo(porK, grupos, y1, W, maxN, rw) {
  const gapItem = 12, gapGrupo = 24;
  grupos.forEach(g => {
    const P = porK[g.pai];
    g.w = g.itens.length * rw + (g.itens.length - 1) * gapItem;
    g.ideal = P.x + P.w / 2;
  });
  const xs = encaixar(grupos, 2, W - 2, gapGrupo);
  const specs = [], gaps = [];
  grupos.forEach((g, gi) => {
    g.x = xs[gi];
    g.itens.forEach((it, i) => {
      specs.push(it);
      const fimGrupo = i === g.itens.length - 1;
      gaps.push(fimGrupo && gi < grupos.length - 1 ? xs[gi + 1] - (g.x + g.w) : gapItem);
    });
  });
  const nos = montarLinha(specs, xs[0], y1, specs.map(() => rw), gaps);
  nos.forEach(no => { porK[no.k] = no; });

  const setas = [];
  grupos.forEach(g => {
    const P = porK[g.pai];
    const itens = g.itens.map(it => { const f = porK[it.k]; return { f, ideal: f.x + f.w / 2, lw: espessura(f.v, maxN) }; });
    const xsDe = distribuir(itens, P.x + 20, P.x + P.w - 20);
    itens.forEach((it, i) => {
      const f = it.f, ys = P.y + P.h, xe = f.x + f.w / 2, ye = f.y - 1, ym = (ys + ye) / 2;
      const pt = ponta(xe, ye, 'baixo', it.lw);
      setas.push({
        k: g.pai + '>' + f.k, cor: f.faixa === 'risco' ? 'risco' : 'etapa', lw: it.lw, zero: !f.v,
        d: cubica([xsDe[i], ys], [xsDe[i], ym], [xe, ym], [xe, ye - pt.recuo]), seta: pt.d,
      });
    });
  });
  return { nos, setas };
}

// Rótulo de bloco: ponto na cor do bloco + nome
function rotuloZona(zona, x, y, txt) {
  return `<circle class="fu-dot ${zona}" cx="${n1(x + 4)}" cy="${y - 4}" r="4"/>` +
         `<text class="fu-zona" x="${n1(x + 14)}" y="${y}">${esc(txt || ZONA[zona])}</text>`;
}

// Legenda: no canto vazio do desenho quando cabe (como na Jornada), senão
// numa linha embaixo dele. Glifos com 48px de largura a partir de (x, y).
const LEGENDA = [
  ['seta',  'Próxima etapa · espessura pelo volume'],
  ['desce', 'Situação agora: em aberto ou perdido'],
  ['cores', 'Verde: venda final · vermelho: perdido'],
];
function glifo(tipo, x, y) {
  if (tipo === 'seta') {
    const pt = ponta(x + 48, y, 'dir', 6);
    return `<path class="fu-leg-l" d="M${x},${y} L${x + 14},${y}" stroke-width="2"/>` +
      `<path class="fu-leg-l" d="M${x + 20},${y} L${n1(x + 48 - pt.recuo)},${y}" stroke-width="6"/><path class="fu-leg-p" d="${pt.d}"/>`;
  }
  if (tipo === 'desce') {
    const pt = ponta(x + 30, y + 8, 'baixo', 2);
    return `<path class="fu-leg-l" d="${cubica([x + 18, y - 8], [x + 18, y], [x + 30, y], [x + 30, y + 8 - pt.recuo])}" stroke-width="2"/><path class="fu-leg-p" d="${pt.d}"/>`;
  }
  return `<circle class="fu-leg-c ganho" cx="${x + 18}" cy="${y}" r="4"/><circle class="fu-leg-c risco" cx="${x + 32}" cy="${y}" r="4"/>`;
}
function legendaSvg(x, y) {
  return LEGENDA.map(([tipo, txt], i) => {
    const yy = y + 12 + i * 24;
    return glifo(tipo, x, yy) + `<text class="fu-legt" x="${x + 60}" y="${yy + 4}">${esc(txt)}</text>`;
  }).join('');
}
function legendaHtml() {
  return LEGENDA.map(([tipo, txt]) =>
    `<span class="fu-leg"><svg width="50" height="20" viewBox="0 -10 50 20" aria-hidden="true">${glifo(tipo, 1, 0)}</svg><span>${esc(txt)}</span></span>`
  ).join('');
}

// Abaixo de 1200px (notebook com o menu aberto) o vão encolhe e a
// etiqueta da seta fica só com o %.
function layoutFunil(t, W) {
  const G = W < 1200 ? 80 : Math.round(Math.min(128, Math.max(108, W * .085)));
  const w = (W - 4 - 4 * G) / 5, y0 = 28;
  const nos = montarLinha([
    { k: 'aq', zona: 'aquisicao', titulo: 'Total de leads', v: t.total_leads, def: 'total_leads',
      subs: [[t.qualificados, 'qualificados'], [t.oportunidades, 'oportunidades']] },
    { k: 'ev', zona: 'evento', titulo: 'Cadeiras vendidas', v: t.cadeiras, def: 'cadeiras',
      subs: [[t.checkins, 'pessoas em sala']] },
    { k: 'co', zona: 'consulta', titulo: 'Consultas vendidas', v: t.consultas_vendidas, def: 'consultas_vendidas',
      subs: [[t.consultas_realizadas, 'realizadas']] },
    { k: 'si', zona: 'sinal', titulo: 'Sinais pagos', v: t.sinais_pagos, def: 'sinais_pagos',
      subs: [[t.fp_vendido, 'FP vendidos']] },
    { k: 'fe', zona: 'fecho', titulo: 'Combos vendidos', v: t.combos, def: 'combos', faixa: 'ganho',
      subs: [fPct(t.taxa_combo) + ' das realizadas'], barra: t.taxa_combo },
  ], 2, y0, Array(5).fill(w), Array(4).fill(G));
  const porK = Object.fromEntries(nos.map(no => [no.k, no]));
  // referência da espessura: a maior etapa de destino (em geral, Cadeiras)
  const maxN = Math.max(t.cadeiras, t.consultas_vendidas, t.sinais_pagos, t.combos);
  const setas = setasLado(porK, [
    { de: 'aq', para: 'ev', n: t.cadeiras, pct: t.pct_cadeira, base: 'dos qualificados',
      num: t.cadeiras, den: t.qualificados, tipTitulo: 'Qualificados → Cadeiras', tipConta: 'Cadeiras ÷ qualificados',
      tipDef: 'Quantas cadeiras foram vendidas para cada 100 leads qualificados.' },
    { de: 'ev', para: 'co', n: t.consultas_vendidas, pct: t.pct_consulta, base: 'das pessoas em sala',
      num: t.consultas_vendidas, den: t.checkins, tipTitulo: 'Sala → Consultas', tipConta: 'Consultas ÷ pessoas em sala',
      tipDef: 'Quantas consultas foram vendidas para cada 100 pessoas em sala. Pode passar de 100%: a consulta vendida não exige check-in.' },
    { de: 'co', para: 'si', n: t.sinais_pagos },
    { de: 'si', para: 'fe', n: t.combos, cor: 'ganho' },
  ], maxN);

  const grupos = [
    { pai: 'co', rotulo: 'Consultas em aberto', zona: 'consulta', itens: [
      { k: 'aag', titulo: 'A agendar', v: t.consultas_a_agendar, def: 'consultas_a_agendar', subs: ['sem data'] },
      { k: 'agd', titulo: 'Agendadas', v: t.consultas_agendadas, def: 'consultas_agendadas', subs: ['data marcada'] },
    ] },
    { pai: 'si', rotulo: 'Financial Planning', zona: 'sinal', itens: [
      { k: 'fpa', titulo: 'FP agendado', v: t.fp_agendado, def: 'fp_agendado', subs: ['a realizar'] },
      { k: 'fpn', titulo: 'FP não agendado', v: t.fp_nao_agendado, def: 'fp_nao_agendado', subs: ['sem data'] },
      { k: 'fpp', titulo: 'FP perdido', v: t.fp_perdido, def: 'fp_perdido', faixa: 'risco', subs: ['caiu em fase perdida'] },
    ] },
  ];
  const y1 = y0 + nos[0].h + 72;
  const baixo = linhaDeBaixo(porK, grupos, y1, W, maxN, Math.min(w, 168));
  const yB = y1 + baixo.nos[0].h + 12;

  let extra = nos.map(no => rotuloZona(no.zona, no.x, y0 - 8)).join('');
  // filete embaixo de cada grupo da linha de baixo, com o nome do bloco
  grupos.forEach(g => {
    extra += `<line class="fu-chave ${g.zona}" x1="${n1(g.x)}" x2="${n1(g.x + g.w)}" y1="${yB}" y2="${yB}"/>` +
      rotuloZona(g.zona, g.x, yB + 20, g.rotulo);
  });
  const largLeg = 60 + Math.max(...LEGENDA.map(([, txt]) => larguraTxt(txt, F.leg)));
  const legNoSvg = grupos[0].x - 24 >= largLeg;
  if (legNoSvg) extra += legendaSvg(2, y1 + 4);
  return { W, H: yB + 26, nos: [...nos, ...baixo.nos], setas: [...setas, ...baixo.setas], extra, legNoSvg };
}

function etqSvg(e) {
  const cx = e.x + e.w / 2;
  return `<rect x="${n1(e.x)}" y="${n1(e.y)}" width="${e.w}" height="${e.h}" rx="4"/>` +
    e.linhas.map((l, i) => `<text class="${i ? 'fu-etq-t' : 'fu-etq-p'}" x="${n1(cx)}" y="${n1(e.y + 15 + 14 * i)}" text-anchor="middle">${esc(l)}</text>`).join('');
}

let funilT = null;   // último totais recebido, para redesenhar quando a largura muda

function renderFunnel(t) {
  funilT = t;
  desenharFunil();
}

function desenharFunil() {
  const t = funilT, box = document.getElementById('fu');
  if (!t) return;
  fontes();
  TIP = {};
  esconderTip();
  const L = layoutFunil(t, Math.max(box.clientWidth, 1000));   // abaixo disso rola na horizontal

  let svg = `<svg class="fu-svg" width="${L.W}" height="${L.H}" viewBox="0 0 ${L.W} ${L.H}" role="img" aria-label="Funil de conversão: ${fNum(t.total_leads)} leads, ${fNum(t.combos)} combos vendidos">`;
  svg += L.extra;
  [...L.setas].sort((a, b) => b.lw - a.lw).forEach(s => {
    svg += `<g class="fu-ar c-${s.cor}${s.zero ? ' is-zero' : ''}" data-ar="${s.k}">` +
      `<path class="fu-linha" d="${s.d}" stroke-width="${n1(s.lw)}"/><path class="fu-ponta" d="${s.seta}"/><path class="fu-hit" d="${s.d}"/></g>`;
  });
  L.nos.forEach(no => {
    svg += `<g class="fu-no" data-no="${no.k}" tabindex="0" aria-label="${esc(no.titulo)}: ${fNum(no.v)}">` +
      `<rect class="fu-box" x="${n1(no.x)}" y="${no.y}" width="${n1(no.w)}" height="${no.h}" rx="6"/>` +
      (no.faixa ? `<rect class="fu-faixa ${no.faixa}" x="${n1(no.x + 1)}" y="${no.y + 1}" width="4" height="${no.h - 2}" rx="2"/>` : '') +
      conteudo(no, no.x, no.y, no.w, no.tl).svg + '</g>';
    TIP[no.k] = { titulo: (no.zona ? ZONA[no.zona] + ' · ' : '') + no.titulo, linhas: [['Quantidade', fNum(no.v)]], def: DEF[no.def] };
  });
  L.setas.filter(s => s.etq).forEach(s => {
    svg += `<g class="fu-ar fu-etq c-${s.cor}" data-ar="${s.k}">${etqSvg(s.etq)}</g>`;
  });
  box.innerHTML = svg + '</svg>';
  focoAtual = null;

  const leg = document.getElementById('fu-legenda');
  leg.hidden = L.legNoSvg;
  leg.innerHTML = L.legNoSvg ? '' : legendaHtml();
  document.getElementById('fu-nota').textContent =
    'Percentuais: cadeiras ÷ qualificados, consultas vendidas ÷ pessoas em sala e combos ÷ consultas realizadas. ' +
    'Passe o mouse numa caixa para ver a definição e isolar as setas dela.';
}

// Dica e foco: mesmo comportamento do fluxograma da Jornada
const fuTip = document.getElementById('fu-tip'), fuTipIn = document.getElementById('fu-tip-in');
function mostrarTip(k, x, y) {
  const d = TIP[k];
  if (!d) { esconderTip(); return; }
  fuTipIn.innerHTML = `<div class="bi-tooltip-title">${esc(d.titulo)}</div>` +
    d.linhas.map(([l, v]) => `<div class="bi-tooltip-row"><span class="bi-tooltip-label">${esc(l)}</span><span class="bi-tooltip-val">${esc(v)}</span></div>`).join('') +
    (d.def ? `<div class="fu-tip-def">${esc(d.def)}</div>` : '');
  fuTip.classList.add('visible');
  fuTip.setAttribute('aria-hidden', 'false');
  const r = fuTip.getBoundingClientRect();
  fuTip.style.left = Math.min(x + 16, window.innerWidth - r.width - 8) + 'px';
  fuTip.style.top  = (y + 16 + r.height > window.innerHeight ? y - r.height - 12 : y + 16) + 'px';
}
function esconderTip() { fuTip.classList.remove('visible'); fuTip.setAttribute('aria-hidden', 'true'); }

let focoAtual = null;
function focar(alvo) {
  const svg = document.querySelector('#fu svg');
  if (!svg || alvo === focoAtual) return;
  focoAtual = alvo;
  svg.classList.toggle('foco', !!alvo);
  const ars = new Set(), nos = new Set();
  if (alvo && alvo.dataset.no) {
    const k = alvo.dataset.no;
    nos.add(k);
    svg.querySelectorAll('[data-ar]').forEach(g => {
      const [a, b] = g.dataset.ar.split('>');
      if (a === k || b === k) { ars.add(g.dataset.ar); nos.add(a); nos.add(b); }
    });
  } else if (alvo) {
    const [a, b] = alvo.dataset.ar.split('>');
    ars.add(alvo.dataset.ar); nos.add(a); nos.add(b);
  }
  svg.querySelectorAll('[data-ar]').forEach(g => g.classList.toggle('is-foco', ars.has(g.dataset.ar)));
  svg.querySelectorAll('[data-no]').forEach(g => g.classList.toggle('is-foco', nos.has(g.dataset.no)));
}

const fuEl = document.getElementById('fu');
fuEl.addEventListener('mousemove', ev => {
  const alvo = ev.target.closest('[data-no], [data-ar]');
  focar(alvo);
  if (alvo) mostrarTip(alvo.dataset.no || alvo.dataset.ar, ev.clientX, ev.clientY); else esconderTip();
});
fuEl.addEventListener('mouseleave', () => { esconderTip(); focar(null); });
fuEl.addEventListener('focusin', ev => {
  const alvo = ev.target.closest('[data-no]');
  if (!alvo) return;
  const r = alvo.getBoundingClientRect();
  focar(alvo);
  mostrarTip(alvo.dataset.no, r.left + r.width / 2, r.bottom);
});
fuEl.addEventListener('focusout', () => { esconderTip(); focar(null); });

// Redesenha quando a LARGURA da área muda (inclusive pela barra de rolagem)
let _fuLarg = 0, _fuResizeT = null;
new ResizeObserver(([e]) => {
  const w = Math.round(e.contentRect.width);
  if (w === _fuLarg) return;
  _fuLarg = w;
  clearTimeout(_fuResizeT);
  _fuResizeT = setTimeout(desenharFunil, 120);
}).observe(fuEl);
document.fonts.ready.then(desenharFunil);

function renderValstrip(t) {
  const custoTotal      = t.custo_total || 0;
  const pctCampanha    = custoTotal > 0 ? (t.investimento_mkt / custoTotal * 100) : 0;
  const pctOperacional = custoTotal > 0 ? (t.custo_fisico     / custoTotal * 100) : 0;

  // % ao lado do valor — reforça em texto a mesma leitura que a bolinha de
  // cor já dá, sem depender só da cor pra saber o peso de cada custo.
  document.getElementById('v-custo-campanha').innerHTML    = fBRL(t.investimento_mkt) + '<span class="fin-pct">(' + pctCampanha.toFixed(0) + '%)</span>';
  document.getElementById('v-custo-operacional').innerHTML = fBRL(t.custo_fisico) + '<span class="fin-pct">(' + pctOperacional.toFixed(0) + '%)</span>';
  document.getElementById('v-custo-total').textContent      = fBRL(custoTotal);
  document.getElementById('bar-campanha').style.width    = pctCampanha.toFixed(1) + '%';
  document.getElementById('bar-operacional').style.width = pctOperacional.toFixed(1) + '%';

  document.getElementById('v-faturamento').textContent = fBRL(t.faturamento);
  document.getElementById('v-projecao').textContent    = fBRL(t.projecao_faturamento);

  // Pill de ROI colorida pelo mesmo critério e mesma classe (.roi-cell/.rc-*)
  // da tabela de performance abaixo — antes era um badge com borda à parte,
  // sempre âmbar, independente do valor real.
  const roiBadge = document.getElementById('v-roi');
  roiBadge.textContent = fROI(t.roi);
  roiBadge.className   = 'roi-cell ' + roiClass(t.roi);

  const roiProjBadge = document.getElementById('v-roi-projetado');
  roiProjBadge.textContent = fROI(t.roi_projetado);
  roiProjBadge.className   = 'roi-cell ' + roiClass(t.roi_projetado);

  // % da projeção já realizado pelo faturamento atual — antes Atual e
  // Projeção eram 2 cards sem nenhuma comparação visual entre si.
  const pctRealizado = t.projecao_faturamento > 0 ? (t.faturamento / t.projecao_faturamento * 100) : 0;
  document.getElementById('fat-progress').style.width = Math.min(100, pctRealizado).toFixed(1) + '%';
  document.getElementById('fat-note').textContent = pctRealizado.toFixed(1) + '% da projeção já realizado';
}

function renderMotivos(motivos) {
  const el = document.getElementById('motivos-descarte');
  if (!motivos || !motivos.length) {
    el.innerHTML = '<div style="color:var(--muted);font-size:12px;">Sem motivos de descarte no período.</div>';
    return;
  }
  const max = Math.max(1, ...motivos.map(m => +m.qtd));
  el.innerHTML = motivos.map(m => `
    <div class="motivo-bar-row${(+m.qtd === max) ? ' top' : ''}">
      <div class="blbl" title="${esc(m.motivo)}">${esc(m.motivo)}</div>
      <div class="funil-bar-track"><div class="funil-bar-fill" style="width:${(m.qtd / max * 100).toFixed(1)}%"></div></div>
      <div class="bval">${fNum(m.qtd)}</div>
    </div>`).join('');
}

function renderTabela(eventos, totais) {
  const validos  = eventos.filter(e => e.roi != null);
  const maiorFat = Math.max(0, ...validos.map(e => +e.faturamento || 0));
  const maxRoi   = Math.max(1, ...validos.map(e => +e.roi || 0), +totais.roi || 0);
  const linhas = validos.map(e => `
    <tr class="${(+e.faturamento === maiorFat && maiorFat > 0) ? 'destaque' : ''}">
      <td>${esc(e.nome_formatado || e.nome)}</td>
      <td class="num">${fBRL(e.faturamento)}</td>
      <td class="num">
        <div class="roi-bar-cell">
          <div class="roi-bar-track"><div class="roi-bar-fill ${roiClass(e.roi)}" style="width:${Math.min(100, e.roi / maxRoi * 100).toFixed(0)}%"></div></div>
          <span class="roi-cell ${roiClass(e.roi)}">${fROI(e.roi)}</span>
        </div>
      </td>
      <td class="num"><span class="roi-cell ${roiClass(e.roi_projetado)}">${fROI(e.roi_projetado)}</span></td>
    </tr>`).join('');
  const total = `
    <tr style="font-weight:800;border-top:2px solid var(--border);">
      <td>Total</td>
      <td class="num">${fBRL(totais.faturamento)}</td>
      <td class="num"><span class="roi-cell ${roiClass(totais.roi)}">${fROI(totais.roi)}</span></td>
      <td class="num"><span class="roi-cell ${roiClass(totais.roi_projetado)}">${fROI(totais.roi_projetado)}</span></td>
    </tr>`;
  document.getElementById('tabela-performance').innerHTML = linhas + total;
}

function renderEventosPreview(eventos) {
  const el = document.getElementById('tabela-eventos-preview');
  if (!eventos || !eventos.length) {
    el.innerHTML = '<tr><td colspan="6" style="color:var(--muted);">Sem eventos cadastrados.</td></tr>';
    return;
  }
  el.innerHTML = eventos.map(e => {
    // data_evento pode vir como DATE ("2026-08-08") ou DATETIME
    // ("2026-08-08 00:00:00") — fatiar os 10 primeiros chars antes de
    // montar o ISO evita "2026-08-08 00:00:00T00:00:00" (Invalid Date).
    const dataFmt = e.data ? new Date(String(e.data).slice(0, 10) + 'T00:00:00').toLocaleDateString('pt-BR') : '—';
    return `
    <tr class="${e.is_proximo ? 'proximo' : ''}">
      <td><span class="evt-prev-name">${esc(e.nome)}</span><span class="evt-prev-date">${dataFmt}</span></td>
      <td>${fBRL(e.investimento)}</td>
      <td>${fNum(e.qualificados)}</td>
      <td>${fNum(e.cadeiras)}</td>
      <td>${fNum(e.consultas)}</td>
      <td>${fNum(e.combos)}</td>
    </tr>`;
  }).join('');
}

async function loadEventosPreview() {
  try {
    const res  = await fetch(PREVIEW_API, { cache: 'no-store' });
    const data = await res.json();
    if (data.error) throw new Error(data.error);
    renderEventosPreview(data.eventos || []);
  } catch (e) {
    console.warn('Não foi possível carregar a prévia de eventos:', e.message);
    document.getElementById('tabela-eventos-preview').innerHTML =
      '<tr><td colspan="6" style="color:var(--muted);">Falha ao carregar.</td></tr>';
  }
}

async function loadDashboard(ano, mes, dataInicio, dataFim, eventos) {
  const params = new URLSearchParams();
  if (eventos) {
    params.set('eventos', eventos);
  } else if (dataInicio || dataFim) {
    if (dataInicio) params.set('data_inicio', dataInicio);
    if (dataFim)    params.set('data_fim', dataFim);
  } else {
    if (ano) params.set('ano', ano);
    if (mes) params.set('mes', mes);
  }

  document.getElementById('error-banner').textContent = '';
  document.getElementById('loading-overlay').classList.remove('hidden');
  document.body.classList.add('loading');
  try {
    const res  = await fetch(API + '?' + params.toString(), { cache: 'no-store' });
    const data = await res.json();
    if (data.error) {
      document.getElementById('error-banner').textContent = 'Erro: ' + data.error;
      return;
    }
    const t = data.totais || {};
    renderValstrip(t);
    renderFunnel(t);
    renderMotivos(data.motivos_descarte || []);
    renderTabela(data.eventos || [], t);
    document.getElementById('evt-count').textContent    = fNum(t.qt_eventos || 0) + ' eventos';
    document.getElementById('last-updated').textContent = 'Atualizado em: ' + new Date().toLocaleString('pt-BR');
  } catch (e) {
    document.getElementById('error-banner').textContent = 'Falha ao carregar: ' + e.message;
  } finally {
    document.getElementById('loading-overlay').classList.add('hidden');
    document.body.classList.remove('loading');
  }
}

function applyFilters() {
  const ano        = yearDropdown.getValue();
  const mes        = monthDropdown.getValue();
  const dataInicio = getDateInput('data-inicio');
  const dataFim    = getDateInput('data-fim');
  const eventos    = getEventoIds();
  updateBadge(ano, mes, eventoDropdown.getValue());
  loadDashboard(ano, mes, dataInicio, dataFim, eventos);
}

function clearFilters() {
  yearDropdown.reset();
  monthDropdown.reset();
  eventoDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim', 'data-fim-clear');
  yearDropdown.select([NOW_YEAR]);
  applyFilters();
}

// ── Badge do período selecionado ────────────────────────────────
function updateBadge(year, months, evento) {
  const badge = document.getElementById('filter-label-badge');
  if (!year && !months && !evento) {
    badge.textContent = 'TODOS OS PERÍODOS';
    return;
  }
  const parts = [];
  if (evento) parts.push(evento);
  if (year)   parts.push(year);
  if (months) {
    const nums   = months.split(',').map(Number);
    const labels = nums.map(n => MONTHS[n - 1]);
    parts.push(labels.join(', '));
  }
  badge.textContent = parts.join(' · ').toUpperCase();
}

['data-inicio', 'data-fim'].forEach(id => {
  const input    = document.getElementById(id);
  const clearId  = id + '-clear';
  const clearBtn = document.getElementById(clearId);
  input?.addEventListener('change', () => clearBtn?.classList.toggle('visible', !!input.value));
  clearBtn?.addEventListener('click', () => clearDateInput(id, clearId));
});

loadEventos();
loadEventosPreview();
applyFilters();
</script>

</div>
