<div class="dash-digital">
<style>
  /* ═══════════════════════════════════════════════════════
     Estilos específicos da aba Digital × Evento
     (tokens de cor/tema globais vivem em css/bi-theme.css,
     escopados sob .dash-digital pelo template compartilhado)
  ═══════════════════════════════════════════════════════ */

  /* ── FILTROS ──────────────────────────────────────────────── */
  /* A barra é uma extensão do header escuro — e, como ele, não muda no dark
     mode (tokens --hdr-* de bi-theme.css:38-50, fixos nos dois temas). Em vez
     de retintar cada controle, os tokens de superfície/texto são REDEFINIDOS
     aqui: as regras abaixo continuam escritas com var(--surface)/var(--blue)/
     var(--text-*) e passam a resolver para os tons do header. Custom property é
     herdada, então os dropdowns flutuantes vêm junto. Mesmo tratamento de
     paginas/executive/index.php. */
  .dash-digital .filters {
    --surface:        #1e2845;                 /* fundo de dropdown e botão */
    --surface-2:      rgba(255,255,255,.07);
    --surface-3:      rgba(255,255,255,.10);
    --border:         rgba(255,255,255,.14);
    --border-soft:    rgba(255,255,255,.22);
    --text-primary:   var(--hdr-text);         /* #ffffff */
    --text-secondary: var(--hdr-meta);         /* #c7cfe3 */
    --text-muted:     var(--hdr-sub);          /* #9aa5c0 */
    --blue:           #93c5fd;                 /* o --blue do claro (#1a6ec8)
                                                  dava 2.4:1 sobre a barra */
    --blue-bg:        rgba(59,130,246,.16);

    background: var(--hdr-to);
    border-bottom: 1px solid rgba(255,255,255,.08);
    padding: 10px 24px;
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
  }
  /* Botões/inputs um tom acima do fundo da barra (o --surface opaco acima é
     para os dropdowns, que flutuam sobre o conteúdo e não podem ser translúcidos). */
  .dash-digital .filters .multi-dd-btn,
  .dash-digital .filters .filter-select,
  .dash-digital .filters .camp-input-row { background-color: rgba(255,255,255,.07); }
  /* O ícone nativo do date picker é desenhado pela UA conforme o color-scheme:
     no tema claro vem escuro e desaparece sobre a barra. */
  .dash-digital .filters input[type="date"] { color-scheme: dark; }
  .dash-digital .filter-label { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.07em; }
  .dash-digital .filter-select {
    background: var(--surface); border: 1px solid var(--border);
    color: var(--text-primary); border-radius: var(--radius-sm);
    padding: 5px 28px 5px 10px; font-size: 11px;
    font-family: 'Fira Sans', sans-serif; cursor: pointer; outline: none;
    appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2394a3b8'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 9px center; background-color: var(--surface);
    transition: border-color 0.2s;
  }
  .dash-digital .filter-select:focus { border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-bg); }
  .dash-digital .filter-sep { width: 1px; height: 20px; background: var(--border); flex-shrink: 0; }
  .dash-digital .multi-dd-wrap { position: relative; }
  .dash-digital .multi-dd-btn {
    background: var(--surface); border: 1px solid var(--border);
    color: var(--text-primary); border-radius: var(--radius-sm);
    padding: 5px 10px; font-size: 11px; font-family: 'Fira Sans', sans-serif;
    cursor: pointer; outline: none; white-space: nowrap;
    display: flex; align-items: center; gap: 5px; transition: border-color 0.2s;
  }
  .dash-digital .multi-dd-btn:hover, .dash-digital .multi-dd-btn.open { border-color: var(--blue); }
  .dash-digital .multi-dd-btn.has-value { color: var(--blue); border-color: var(--blue); background: var(--blue-bg); font-weight: 600; }
  .dash-digital .dd-caret { font-size: 9px; color: var(--text-muted); margin-left: 2px; }
  .dash-digital .multi-dd-list {
    display: none; position: absolute; top: calc(100% + 4px); left: 0;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius-sm); box-shadow: var(--shadow);
    z-index: 300; min-width: 100%;
    /* bi-theme.css:540-546 dá max-height:240px + overflow-y:auto a esta mesma
       classe. Aqui havia `overflow: hidden`, que vencia SÓ o overflow: o teto de
       240px continuava valendo e os itens além dele (Set a Dez, no filtro de
       Mês) ficavam recortados e inalcançáveis, sem scroll possível. */
    max-height: 380px;
    overflow: hidden auto;
  }
  .dash-digital .multi-dd-list.open { display: block; }
  .dash-digital .multi-dd-item {
    padding: 7px 12px; font-size: 11px; cursor: pointer;
    display: flex; align-items: center; gap: 8px;
    color: var(--text-secondary); user-select: none; white-space: nowrap;
  }
  .dash-digital .multi-dd-item:hover { background: var(--surface-2); }
  .dash-digital .multi-dd-item .dd-check {
    width: 12px; height: 12px; border-radius: 3px; border: 1.5px solid var(--border);
    flex-shrink: 0; display: flex; align-items: center; justify-content: center;
  }
  .dash-digital .multi-dd-item.selected { color: var(--blue); font-weight: 600; }
  .dash-digital .multi-dd-item.selected .dd-check { background: var(--blue); border-color: var(--blue); color: #fff; font-size: 8px; }
  .dash-digital .multi-dd-item.selected .dd-check::after { content: '✓'; }
  .dash-digital .btn-apply {
    border-radius: var(--radius-sm); padding: 6px 16px; font-size: 11px; font-weight: 700;
    letter-spacing: 0.03em;
  }
  .dash-digital .btn-clear {
    background: transparent; border: 1px solid var(--border); color: var(--text-muted);
    border-radius: var(--radius-sm); padding: 5px 13px; font-size: 11px; cursor: pointer;
    transition: all 0.18s; font-family: 'Fira Sans', sans-serif;
  }
  .dash-digital .btn-clear:hover { border-color: var(--text-secondary); color: var(--text-secondary); }

  /* ── LOADING / ERROR ─────────────────────────────────────── */
  .dash-digital .spinner {
    width: 40px; height: 40px;
    border: 3px solid var(--border); border-top-color: var(--orange);
    border-radius: 50%; animation: dashDigitalSpin 0.8s linear infinite;
  }
  @keyframes dashDigitalSpin { to { transform: rotate(360deg); } }
  .dash-digital .loading-text { font-size: 13px; color: var(--text-secondary); font-family: 'Fira Code', monospace; }

  /* ── MAIN LAYOUT ──────────────────────────────────────────── */
  .dash-digital .main { padding: 20px 24px; display: flex; flex-direction: column; gap: 16px; max-width: 1800px; margin: 0 auto; }

  /* ── PAINEL GLOBAL ────────────────────────────────────────── */
  .dash-digital .panel-global {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); display: flex;
  }
  .dash-digital .panel-section {
    padding: 18px 22px; border-right: 1px solid var(--border);
    display: flex; flex-direction: column; gap: 10px;
  }
  .dash-digital .panel-section:last-child { border-right: none; }
  .dash-digital .panel-section.financeiro { width: 230px; flex-shrink: 0; }
  .dash-digital .panel-section.funil      { flex: 1; }
  .dash-digital .panel-section.cac-cpl    { width: 280px; flex-shrink: 0; }

  .dash-digital .section-title {
    font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
    color: var(--text-primary); margin-bottom: 4px; display: flex; align-items: center; gap: 8px;
  }
  .dash-digital .section-title::before {
    content: ''; width: 3px; height: 12px; border-radius: 2px; background: var(--blue); flex-shrink: 0;
  }
  .dash-digital .section-title::after { content: ''; flex: 1; height: 1px; background: var(--border); }

  .dash-digital .fin-row { display: flex; flex-direction: column; gap: 2px; }
  .dash-digital .fin-label { font-size: 10.5px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; }
  .dash-digital .fin-value { font-family: 'Fira Code', monospace; font-size: 20px; font-weight: 700; letter-spacing: -0.02em; }
  .dash-digital .fin-value.invest { color: var(--text-secondary); }
  .dash-digital .fin-value.fatur  { color: var(--green); }
  .dash-digital .fin-divider { height: 1px; background: var(--border); margin: 2px 0; }

  .dash-digital .roi-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--amber-bg); border: 1px solid var(--amber); border-radius: var(--radius-sm);
    padding: 5px 12px; font-family: 'Fira Code', monospace;
    font-size: 17px; font-weight: 700; color: var(--amber); margin-top: 2px; letter-spacing: -0.02em;
  }
  .dash-digital .lucro-badge {
    display: inline-flex; align-items: center;
    background: var(--green-bg); border: 1px solid var(--green); border-radius: var(--radius-sm);
    padding: 5px 12px; font-family: 'Fira Code', monospace;
    font-size: 15px; font-weight: 700; color: var(--green); margin-top: 2px; letter-spacing: -0.02em;
  }

  .dash-digital .kpi-row { display: flex; gap: 8px; }
  .dash-digital .kpi-chip {
    flex: 1; background: var(--surface-2); border: 1px solid var(--border);
    border-radius: var(--radius-sm); padding: 10px; text-align: center;
    transition: transform 0.18s, box-shadow 0.18s, border-color 0.18s;
  }
  .dash-digital .kpi-chip:hover { transform: translateY(-1px); box-shadow: var(--shadow-sm); }
  .dash-digital .kpi-chip.highlight { background: var(--green-bg); border-color: var(--green); }
  .dash-digital .kpi-chip-label { font-size: 9.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 5px; }
  .dash-digital .kpi-chip-value { font-family: 'Fira Code', monospace; font-size: 16px; font-weight: 700; color: var(--text-primary); line-height: 1; letter-spacing: -0.02em; }
  .dash-digital .kpi-chip-value.blue   { color: var(--blue); }
  .dash-digital .kpi-chip-value.orange { color: var(--orange); }
  .dash-digital .kpi-chip-value.green  { color: var(--green); }
  .dash-digital .kpi-chip-value.teal   { color: var(--teal); }
  .dash-digital .kpi-chip-value.amber  { color: var(--amber); }
  .dash-digital .kpi-chip-value.cyan   { color: var(--cyan); }

  /* ── GRADE COMPARATIVA 2 COLUNAS ─────────────────────────── */
  .dash-digital .funis-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }
  @media (max-width: 900px) { .dash-digital .funis-grid { grid-template-columns: 1fr; } }

  /* ── CARD DE FUNIL ────────────────────────────────────────── */
  .dash-digital .funil-card {
    background: var(--surface);
    border: 2px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    display: flex; flex-direction: column;
    transition: box-shadow 0.22s, transform 0.22s;
  }
  .dash-digital .funil-card:hover { box-shadow: var(--shadow); transform: translateY(-2px); }
  .dash-digital .funil-card.evento  { border-color: var(--orange); }
  .dash-digital .funil-card.digital { border-color: var(--blue); }

  /* Header do card */
  .dash-digital .funil-header {
    padding: 14px 18px;
    display: flex; align-items: flex-start; justify-content: space-between;
    border-bottom: 1px solid var(--border);
  }
  .dash-digital .funil-card.evento  .funil-header { background: var(--orange-bg); }
  .dash-digital .funil-card.digital .funil-header { background: var(--blue-bg); }

  .dash-digital .funil-name {
    font-size: 12px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
  }
  .dash-digital .funil-card.evento  .funil-name { color: var(--orange); }
  .dash-digital .funil-card.digital .funil-name { color: var(--blue-dark); }

  .dash-digital .funil-name-icon {
    width: 26px; height: 26px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  }
  .dash-digital .funil-card.evento  .funil-name-icon { background: var(--gold-glow); }
  .dash-digital .funil-card.digital .funil-name-icon { background: var(--consulta-glow); }
  .dash-digital .funil-name-icon svg { width: 14px; height: 14px; }

  .dash-digital .perf-badge {
    display: inline-block; font-size: 9px; font-weight: 700; color: #fff;
    padding: 2px 7px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.04em;
  }
  .dash-digital .perf-badge.absoluto { background: var(--green); }
  .dash-digital .perf-badge.roi      { background: var(--accent2); }
  .dash-digital .perf-badge.lucro    { background: var(--sinal); }

  .dash-digital .funil-meta { display: flex; gap: 14px; flex-shrink: 0; }
  .dash-digital .funil-meta-item { text-align: right; }
  .dash-digital .funil-meta-label { font-size: 9px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; margin-bottom: 2px; }
  .dash-digital .funil-meta-value { font-family: 'Fira Code', monospace; font-size: 14px; font-weight: 700; }
  .dash-digital .funil-card.evento  .funil-meta-value { color: var(--orange); }
  .dash-digital .funil-card.digital .funil-meta-value { color: var(--blue); }
  .dash-digital .funil-meta-value.highlight-lucro { color: var(--green) !important; font-size: 13px; }
  .dash-digital .funil-meta-value.highlight-roi   { color: var(--amber) !important; font-size: 14px; }

  /* Corpo do funil — rows */
  .dash-digital .funil-body { padding: 10px 16px; display: flex; flex-direction: column; width: 100%; gap: 1px; }

  .dash-digital .funil-row {
    display: flex; align-items: center; width: 100%; gap: 10px;
    padding: 5px 0; border-bottom: 1px solid #f8fafc;
  }
  .dash-digital .funil-row:last-child { border-bottom: none; }

  .dash-digital .funil-row-label { font-size: 9px; color: var(--text-secondary); font-weight: 600; width: 100px; flex-shrink: 0; }

  .dash-digital .funil-bar-wrap { flex: 1; height: 18px; background: var(--surface-2); border-radius: 3px; overflow: hidden; display: flex; align-items: center; justify-content: center; }
  .dash-digital .funil-bar {
    height: 100%; min-width: 6px; transition: width 0.6s ease;
  }
  .dash-digital .funil-card.evento  .funil-bar { background: var(--gold); }
  .dash-digital .funil-card.digital .funil-bar { background: var(--consulta); }

  .dash-digital .funil-row-val { font-family: 'Fira Code', monospace; font-size: 11px; font-weight: 700; width: 46px; text-align: right; flex-shrink: 0; }
  .dash-digital .funil-card.evento  .funil-row-val { color: var(--orange); }
  .dash-digital .funil-card.digital .funil-row-val { color: var(--blue); }

  .dash-digital .funil-row-cv { font-size: 8.5px; color: var(--text-muted); width: 42px; text-align: right; flex-shrink: 0; }

  /* Seção CAC */
  .dash-digital .funil-cacs {
    padding: 10px 16px;
    border-top: 1px solid var(--border);
    display: flex; gap: 6px; flex-wrap: wrap;
  }
  .dash-digital .funil-cac {
    flex: 1; min-width: 70px;
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: var(--radius-sm); padding: 6px 8px; text-align: center;
  }
  .dash-digital .funil-cac-label { font-size: 9px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; margin-bottom: 3px; }
  .dash-digital .funil-cac-value { font-family: 'Fira Code', monospace; font-size: 12px; font-weight: 700; }
  .dash-digital .funil-card.evento  .funil-cac-value { color: var(--orange); }
  .dash-digital .funil-card.digital .funil-cac-value { color: var(--blue); }

  /* ── TIMESTAMP ──────────────────────────────────────────── */
  .dash-digital .last-updated { font-size: 10px; color: var(--text-muted); font-family: 'Fira Code', monospace; text-align: right; padding: 4px 0 8px; }

  /* ── searchable dropdown (camp-wrap) ───────────────────── */
  .dash-digital .camp-wrap { position: relative; flex-shrink: 0; }
  .dash-digital .camp-input-row {
    display: flex; align-items: center;
    border: 1px solid var(--border); border-radius: var(--radius-sm);
    background: var(--surface); transition: border-color 0.2s, box-shadow 0.2s;
  }
  .dash-digital .camp-input-row:focus-within { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(59,130,246,0.12); }
  .dash-digital .camp-icon { padding: 0 6px 0 8px; color: var(--text-muted); display: flex; align-items: center; pointer-events: none; }
  .dash-digital .camp-input { border: none; outline: none; background: transparent; font-size: 11px; color: var(--text-primary); width: 150px; padding: 6px 4px; font-family: inherit; }
  .dash-digital .camp-clear { background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 13px; padding: 0 6px; display: none; align-items: center; line-height: 1; }
  .dash-digital .camp-clear.visible { display: flex; }
  .dash-digital .camp-dropdown { position: absolute; top: calc(100% + 4px); left: 0; width: 100%; min-width: 180px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); box-shadow: var(--shadow-lg, 0 8px 24px rgba(0,0,0,0.08)); max-height: 240px; overflow-y: auto; z-index: 200; display: none; }
  .dash-digital .camp-dropdown.open { display: block; }
  .dash-digital .camp-opt { padding: 7px 12px; font-size: 11px; cursor: pointer; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: background 0.12s; }
  .dash-digital .camp-opt:hover, .dash-digital .camp-opt.focused { background: var(--blue-bg, #eff6ff); color: var(--blue, #3b82f6); }
  .dash-digital .camp-opt.selected { font-weight: 700; color: var(--blue, #3b82f6); }
  .dash-digital .camp-empty { padding: 10px 12px; font-size: 11px; color: var(--text-muted); text-align: center; }
  .dash-digital .camp-tags { display: flex; flex-wrap: wrap; gap: 4px; }
  .dash-digital .camp-tags:not(:empty) { padding: 4px 6px 0; }
  .dash-digital .filter-tag { display: inline-flex; align-items: center; gap: 3px; background: var(--blue-bg, #eff6ff); color: var(--blue, #3b82f6); border: 1px solid var(--blue); border-radius: 4px; font-size: 10px; font-weight: 600; padding: 2px 6px; }
  .dash-digital .tag-remove { background: none; border: none; cursor: pointer; color: inherit; opacity: 0.6; font-size: 12px; padding: 0; line-height: 1; }
  .dash-digital .tag-remove:hover { opacity: 1; }
  html.dark .dash-digital .camp-input-row { background: var(--surface); border-color: var(--border); }
  html.dark .dash-digital .camp-input { color: var(--text-primary); }
  html.dark .dash-digital .camp-dropdown { background: var(--surface); border-color: var(--border); }
  html.dark .dash-digital .camp-opt { color: var(--text-secondary); }
  html.dark .dash-digital .camp-opt:hover { background: var(--surface-2); }

  /* ── date range ────────────────────────────────────────── */
  .dash-digital .date-range-group { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
  .dash-digital .date-input-wrap  { position: relative; display: flex; align-items: center; }
  .dash-digital .date-input-wrap input[type="date"] {
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm);
    color: var(--text-primary); font-size: 11px; padding: 5px 28px 5px 8px;
    outline: none; font-family: inherit; cursor: pointer; width: 130px;
    transition: border-color 0.2s;
  }
  .dash-digital .date-input-wrap input[type="date"]:focus { border-color: var(--blue, #3b82f6); }
  .dash-digital .date-clear-btn {
    position: absolute; right: 6px; background: none; border: none; cursor: pointer;
    color: var(--text-muted); font-size: 13px; display: none; line-height: 1; padding: 0 2px;
  }
  .dash-digital .date-clear-btn.visible { display: block; }
  .dash-digital .date-sep-label { font-size: 10px; color: var(--text-muted); }
</style>

<!-- ── OVERLAY ────────────────────────────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>

<!-- ── HEADER ─────────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo" aria-hidden="true">MR</div>
    <div>
      <div class="header-title">Digital &times; Evento &mdash; Funil Comparativo</div>
      <div class="header-sub">Segmentação por Classificação de Form Preenchido</div>
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
      <a class="tab-switch-btn" href="<?= $this->site ?>executive<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Executive</a>
      <a class="tab-switch-btn active" href="<?= $this->site ?>executive/digital<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Digital &times; Evento</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>executive/especialistas<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Especialistas</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>executive/analitico<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Analítico</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>executive/serasa<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Serasa</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>executive/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
    </div>
  </div>
</header>

<!-- ── FILTROS ────────────────────────────────────────────────── -->
<section class="filters is-collapsible" aria-label="Filtros do dashboard">
  <button class="filters-toggle" type="button" aria-expanded="true" aria-controls="filters-fields">
    <span>Filtros</span>
    <span class="dd-caret filters-toggle-caret">&#x25BE;</span>
  </button>
  <div class="filters-fields" id="filters-fields">
  <!-- Data -->
  <div class="filter-pair">
    <span class="filter-label">Data</span>
    <div class="date-range-group">
      <div class="date-input-wrap">
        <input type="date" id="data-inicio" aria-label="Data início">
        <button class="date-clear-btn" id="data-inicio-clear" aria-label="Limpar">&#x2715;</button>
      </div>
      <span class="date-sep-label">até</span>
      <div class="date-input-wrap">
        <input type="date" id="data-fim" aria-label="Data fim">
        <button class="date-clear-btn" id="data-fim-clear" aria-label="Limpar">&#x2715;</button>
      </div>
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

  <!-- Form -->
  <div class="filter-pair">
    <span class="filter-label">Form</span>
    <div class="camp-wrap" id="form-wrap">
      <div class="camp-input-row">
        <span class="camp-icon"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
        <input class="camp-input" id="form-input" placeholder="Pesquisar form..." autocomplete="off">
        <button class="camp-clear" id="form-clear" aria-label="Limpar">✕</button>
      </div>
      <div class="camp-tags" id="form-tags"></div>
      <div class="camp-dropdown" id="form-dropdown" role="listbox"></div>
    </div>
  </div>

  <!-- Campanha -->
  <div class="filter-pair">
    <span class="filter-label">Campanha</span>
    <div class="camp-wrap" id="camp-wrap">
      <div class="camp-input-row">
        <span class="camp-icon"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
        <input class="camp-input" id="camp-input" placeholder="Pesquisar campanha..." autocomplete="off">
        <button class="camp-clear" id="camp-clear" aria-label="Limpar">✕</button>
      </div>
      <div class="camp-tags" id="camp-tags"></div>
      <div class="camp-dropdown" id="camp-dropdown" role="listbox"></div>
    </div>
  </div>

  <!-- Responsável -->
  <div class="filter-pair">
    <span class="filter-label">Responsável</span>
    <div class="camp-wrap" id="resp-wrap">
      <div class="camp-input-row">
        <span class="camp-icon"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
        <input class="camp-input" id="resp-input" placeholder="Pesquisar responsável..." autocomplete="off">
        <button class="camp-clear" id="resp-clear" aria-label="Limpar">✕</button>
      </div>
      <div class="camp-tags" id="resp-tags"></div>
      <div class="camp-dropdown" id="resp-dropdown" role="listbox"></div>
    </div>
  </div>

  <button class="btn-apply" id="btn-apply" onclick="applyFilters()">Aplicar</button>
  <button class="btn-clear" id="btn-clear" onclick="clearFilters()">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'executive-digital' });</script>

<!-- ── ERRO ──────────────────────────────────────────────────── -->
<div id="error-banner" role="alert"></div>

<!-- ── CONTEÚDO PRINCIPAL ──────────────────────────────────────── -->
<main class="main" id="dashboard-main" aria-label="Dashboard Digital × Evento">

  <!-- PAINEL GLOBAL -->
  <div class="panel-global" id="panel-global">

    <!-- Financeiro -->
    <div class="panel-section financeiro">
      <div class="section-title">Financeiro</div>
      <div class="fin-row">
        <span class="fin-label">Investimento</span>
        <span class="fin-value invest" id="g-invest">—</span>
      </div>
      <div class="fin-divider"></div>
      <div class="fin-row">
        <span class="fin-label">Faturamento</span>
        <span class="fin-value fatur" id="g-fatur">—</span>
      </div>
      <div class="fin-divider"></div>
      <div class="fin-row">
        <span class="fin-label">Lucro</span>
        <div class="lucro-badge" id="g-lucro">—</div>
      </div>
      <div class="fin-row">
        <span class="fin-label">ROI(x)</span>
        <div class="roi-badge" id="g-roi">—</div>
      </div>
    </div>

    <!-- Funil Comercial Global -->
    <div class="panel-section funil">
      <div class="section-title">Funil Comercial</div>
      <div class="kpi-row">
        <div class="kpi-chip">
          <div class="kpi-chip-label">Leads</div>
          <div class="kpi-chip-value" id="g-leads">—</div>
        </div>
        <div class="kpi-chip">
          <div class="kpi-chip-label">Qualificados</div>
          <div class="kpi-chip-value blue" id="g-qualif">—</div>
        </div>
        <div class="kpi-chip">
          <div class="kpi-chip-label">Cadeiras</div>
          <div class="kpi-chip-value orange" id="g-cadeira">—</div>
        </div>
      </div>
      <div class="kpi-row">
        <div class="kpi-chip">
          <div class="kpi-chip-label">Cons. Vend.</div>
          <div class="kpi-chip-value orange" id="g-consult-v">—</div>
        </div>
        <div class="kpi-chip">
          <div class="kpi-chip-label">Cons. Real.</div>
          <div class="kpi-chip-value teal" id="g-consult-r">—</div>
        </div>
        <div class="kpi-chip highlight">
          <div class="kpi-chip-label">Combos</div>
          <div class="kpi-chip-value green" id="g-combos">—</div>
        </div>
      </div>
    </div>

    <!-- CAC & CPL Global -->
    <div class="panel-section cac-cpl">
      <div class="section-title">CAC &amp; CPL</div>
      <div class="kpi-row">
        <div class="kpi-chip">
          <div class="kpi-chip-label">CPL</div>
          <div class="kpi-chip-value amber" id="g-cpl">—</div>
        </div>
        <div class="kpi-chip">
          <div class="kpi-chip-label">Ticket Médio</div>
          <div class="kpi-chip-value amber" id="g-ticket">—</div>
        </div>
      </div>
      <div class="kpi-row">
        <div class="kpi-chip">
          <div class="kpi-chip-label">CAC Cadeira</div>
          <div class="kpi-chip-value" id="g-cac-cad">—</div>
        </div>
        <div class="kpi-chip">
          <div class="kpi-chip-label">CAC Consulta</div>
          <div class="kpi-chip-value" id="g-cac-cons">—</div>
        </div>
      </div>
      <div class="kpi-row">
        <div class="kpi-chip">
          <div class="kpi-chip-label">CAC Combo</div>
          <div class="kpi-chip-value" id="g-cac-comb">—</div>
        </div>
      </div>
    </div>
  </div>

  <!-- GRADE DE FUNIS COMPARATIVOS -->
  <div class="funis-grid">

    <!-- EVENTO -->
    <article class="funil-card evento" id="card-evento" aria-label="Funil Evento">
      <div class="funil-header">
        <div>
          <div class="funil-name">
            <div class="funil-name-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="#9a7530" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/>
              </svg>
            </div>
            &#127914; Evento
            <span class="perf-badge" id="e-perf-badge" style="display:none"></span>
          </div>
        </div>
        <div class="funil-meta">
          <div class="funil-meta-item">
            <div class="funil-meta-label">Invest.</div>
            <div class="funil-meta-value" id="e-invest">—</div>
          </div>
          <div class="funil-meta-item">
            <div class="funil-meta-label">Fatur.</div>
            <div class="funil-meta-value" id="e-fatur">—</div>
          </div>
          <div class="funil-meta-item">
            <div class="funil-meta-label">Lucro</div>
            <div class="funil-meta-value highlight-lucro" id="e-lucro">—</div>
          </div>
          <div class="funil-meta-item">
            <div class="funil-meta-label">ROI</div>
            <div class="funil-meta-value highlight-roi" id="e-roi">—</div>
          </div>
        </div>
      </div>

      <div class="funil-body">
        <div class="funil-row">
          <span class="funil-row-label">Leads</span>
          <div class="funil-bar-wrap"><div class="funil-bar" id="e-bar-leads" style="width:100%"></div></div>
          <span class="funil-row-val" id="e-leads">—</span>
          <span class="funil-row-cv">&nbsp;</span>
        </div>
        <div class="funil-row">
          <span class="funil-row-label">Qualificados</span>
          <div class="funil-bar-wrap"><div class="funil-bar" id="e-bar-qualif" style="width:0%"></div></div>
          <span class="funil-row-val" id="e-qualif">—</span>
          <span class="funil-row-cv" id="e-cv-qualif">—</span>
        </div>
        <div class="funil-row">
          <span class="funil-row-label">Cadeiras</span>
          <div class="funil-bar-wrap"><div class="funil-bar" id="e-bar-cadeira" style="width:0%"></div></div>
          <span class="funil-row-val" id="e-cadeira">—</span>
          <span class="funil-row-cv" id="e-cv-cadeira">—</span>
        </div>
        <div class="funil-row">
          <span class="funil-row-label">Cons. Vendidas</span>
          <div class="funil-bar-wrap"><div class="funil-bar" id="e-bar-consult-v" style="width:0%"></div></div>
          <span class="funil-row-val" id="e-consult-v">—</span>
          <span class="funil-row-cv" id="e-cv-consult-v">—</span>
        </div>
        <div class="funil-row">
          <span class="funil-row-label">Cons. Realizadas</span>
          <div class="funil-bar-wrap"><div class="funil-bar" id="e-bar-consult-r" style="width:0%"></div></div>
          <span class="funil-row-val" id="e-consult-r">—</span>
          <span class="funil-row-cv" id="e-cv-consult-r">—</span>
        </div>
        <div class="funil-row">
          <span class="funil-row-label">FPs Realizados</span>
          <div class="funil-bar-wrap"><div class="funil-bar" id="e-bar-fp" style="width:0%"></div></div>
          <span class="funil-row-val" id="e-fp">—</span>
          <span class="funil-row-cv" id="e-cv-fp">—</span>
        </div>
        <div class="funil-row">
          <span class="funil-row-label">Combos</span>
          <div class="funil-bar-wrap"><div class="funil-bar" id="e-bar-combos" style="width:0%"></div></div>
          <span class="funil-row-val" id="e-combos">—</span>
          <span class="funil-row-cv" id="e-cv-combos">—</span>
        </div>
      </div>

      <div class="funil-cacs">
        <div class="funil-cac">
          <div class="funil-cac-label">CPL</div>
          <div class="funil-cac-value" id="e-cpl">—</div>
        </div>
        <div class="funil-cac">
          <div class="funil-cac-label">CAC Cad.</div>
          <div class="funil-cac-value" id="e-cac-cad">—</div>
        </div>
        <div class="funil-cac">
          <div class="funil-cac-label">CAC Cons.</div>
          <div class="funil-cac-value" id="e-cac-cons">—</div>
        </div>
        <div class="funil-cac">
          <div class="funil-cac-label">CAC Combo</div>
          <div class="funil-cac-value" id="e-cac-comb">—</div>
        </div>
        <div class="funil-cac">
          <div class="funil-cac-label">Ticket M.</div>
          <div class="funil-cac-value" id="e-ticket">—</div>
        </div>
      </div>
    </article>

    <!-- DIGITAL -->
    <article class="funil-card digital" id="card-digital" aria-label="Funil Digital">
      <div class="funil-header">
        <div>
          <div class="funil-name">
            <div class="funil-name-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="#1a6ec8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>
              </svg>
            </div>
            &#128241; Digital
            <span class="perf-badge" id="d-perf-badge" style="display:none"></span>
          </div>
        </div>
        <div class="funil-meta">
          <div class="funil-meta-item">
            <div class="funil-meta-label">Invest.</div>
            <div class="funil-meta-value" id="d-invest">—</div>
          </div>
          <div class="funil-meta-item">
            <div class="funil-meta-label">Fatur.</div>
            <div class="funil-meta-value" id="d-fatur">—</div>
          </div>
          <div class="funil-meta-item">
            <div class="funil-meta-label">Lucro</div>
            <div class="funil-meta-value highlight-lucro" id="d-lucro">—</div>
          </div>
          <div class="funil-meta-item">
            <div class="funil-meta-label">ROI</div>
            <div class="funil-meta-value highlight-roi" id="d-roi">—</div>
          </div>
        </div>
      </div>

      <div class="funil-body">
        <div class="funil-row">
          <span class="funil-row-label">Leads</span>
          <div class="funil-bar-wrap"><div class="funil-bar" id="d-bar-leads" style="width:100%"></div></div>
          <span class="funil-row-val" id="d-leads">—</span>
          <span class="funil-row-cv">&nbsp;</span>
        </div>
        <div class="funil-row">
          <span class="funil-row-label">Qualificados</span>
          <div class="funil-bar-wrap"><div class="funil-bar" id="d-bar-qualif" style="width:0%"></div></div>
          <span class="funil-row-val" id="d-qualif">—</span>
          <span class="funil-row-cv" id="d-cv-qualif">—</span>
        </div>
        <div class="funil-row">
          <span class="funil-row-label">Cons. Vendidas</span>
          <div class="funil-bar-wrap"><div class="funil-bar" id="d-bar-consult-v" style="width:0%"></div></div>
          <span class="funil-row-val" id="d-consult-v">—</span>
          <span class="funil-row-cv" id="d-cv-consult-v">—</span>
        </div>
        <div class="funil-row">
          <span class="funil-row-label">Cons. Realizadas</span>
          <div class="funil-bar-wrap"><div class="funil-bar" id="d-bar-consult-r" style="width:0%"></div></div>
          <span class="funil-row-val" id="d-consult-r">—</span>
          <span class="funil-row-cv" id="d-cv-consult-r">—</span>
        </div>
        <div class="funil-row">
          <span class="funil-row-label">FPs Realizados</span>
          <div class="funil-bar-wrap"><div class="funil-bar" id="d-bar-fp" style="width:0%"></div></div>
          <span class="funil-row-val" id="d-fp">—</span>
          <span class="funil-row-cv" id="d-cv-fp">—</span>
        </div>
        <div class="funil-row">
          <span class="funil-row-label">Combos</span>
          <div class="funil-bar-wrap"><div class="funil-bar" id="d-bar-combos" style="width:0%"></div></div>
          <span class="funil-row-val" id="d-combos">—</span>
          <span class="funil-row-cv" id="d-cv-combos">—</span>
        </div>
      </div>

      <div class="funil-cacs">
        <div class="funil-cac">
          <div class="funil-cac-label">CPL</div>
          <div class="funil-cac-value" id="d-cpl">—</div>
        </div>
        <div class="funil-cac">
          <div class="funil-cac-label">CAC Cons.</div>
          <div class="funil-cac-value" id="d-cac-cons">—</div>
        </div>
        <div class="funil-cac">
          <div class="funil-cac-label">CAC Combo</div>
          <div class="funil-cac-value" id="d-cac-comb">—</div>
        </div>
        <div class="funil-cac">
          <div class="funil-cac-label">Ticket M.</div>
          <div class="funil-cac-value" id="d-ticket">—</div>
        </div>
      </div>
    </article>

  </div><!-- /funis-grid -->

  <div class="last-updated" id="last-updated" aria-live="polite"></div>

</main>

<script>
// ══════════════════════════════════════════════════════════════
//  Digital × Evento — Dashboard JS
//  Consome: executive/digitalDados (controller MRPHP)
//  Depende de funções compartilhadas em js/bi-dashboard.js:
//  createMultiDropdown, createSearchDropdown, getDateInput,
//  setEffectiveDate, clearDateInput, computeEffectiveDates,
//  fBRL, fNum, fROI, fLucro, fPct, fBar
// ══════════════════════════════════════════════════════════════

const API_URL = '<?= $this->site ?>executive/digitalDados';
const MONTHS  = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const YEARS = ['2025', '2026'];

const yearDropdown  = createMultiDropdown('year-wrap',  'year-btn',  'year-list',
  YEARS,  l => l,       l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list',
  MONTHS, l => l,       (l, i) => i + 1);

const NOW_YEAR  = String(new Date().getFullYear());
const NOW_MONTH = new Date().getMonth() + 1;
yearDropdown.select([NOW_YEAR]);
monthDropdown.select([String(NOW_MONTH)]);

const formDropdown = createSearchDropdown('form-wrap', 'form-input', 'form-dropdown', 'form-clear', 'form-tags', 'Nenhum form encontrado');
const campDropdown = createSearchDropdown('camp-wrap', 'camp-input', 'camp-dropdown', 'camp-clear', 'camp-tags', 'Nenhuma campanha encontrada');
const respDropdown = createSearchDropdown('resp-wrap', 'resp-input', 'resp-dropdown', 'resp-clear', 'resp-tags', 'Nenhum responsável encontrado');

// ── Formatadores locais (não compartilhados em bi-dashboard.js) ─
function fBRLShort(val) {
  const n = Number(val) || 0;
  return '$ ' + n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}
function fLucroShort(val){ return fBRLShort(Math.max(0, Number(val) || 0)); }
function set(id, value) { const el = document.getElementById(id); if (el) el.textContent = value; }
function setWidth(id, pct) { const el = document.getElementById(id); if (el) el.style.width = pct; }

// ── Preencher Painel Global ───────────────────────────────────
function fillGlobal(g) {
  set('g-invest',    fBRL(g.investimento));
  set('g-fatur',     fBRL(g.faturamento_total));
  set('g-lucro',     fLucro(g.lucro));
  set('g-roi',       fROI(g.roi));
  set('g-leads',     fNum(g.total_leads));
  set('g-qualif',    fNum(g.leads_qualificados));
  set('g-cadeira',   fNum(g.cadeira_vendida));
  set('g-consult-v', fNum(g.consultas_vendidas));
  set('g-consult-r', fNum(g.consultas_realizadas));
  set('g-fp',        fNum(g.fp_realizado));
  set('g-combos',    fNum(g.combos_vendidos));
  set('g-cpl',       fBRL(g.cpl));
  set('g-ticket',    fBRL(g.ticket_medio));
  set('g-cac-cad',   fBRL(g.cac_cadeira));
  set('g-cac-cons',  fBRL(g.cac_consulta));
  set('g-cac-comb',  fBRL(g.cac_combos));
}

// ── Preencher Funil Evento ────────────────────────────────────
function fillEvento(e) {
  const L = e.total_leads || 1;

  // Header meta
  set('e-invest', fBRLShort(e.investimento));
  set('e-fatur',  fBRLShort(e.faturamento_total));
  set('e-lucro',  fLucroShort(e.lucro));
  set('e-roi',    fROI(e.roi));

  // Funnel rows + bars + conversions
  set('e-leads',     fNum(e.total_leads));
  setWidth('e-bar-leads', '100%');

  set('e-qualif',    fNum(e.leads_qualificados));
  set('e-cv-qualif', fPctRatio(e.leads_qualificados, e.total_leads));
  setWidth('e-bar-qualif', fBar(e.leads_qualificados, L));

  set('e-cadeira',    fNum(e.cadeira_vendida));
  set('e-cv-cadeira', fPctRatio(e.cadeira_vendida, e.leads_qualificados));
  setWidth('e-bar-cadeira', fBar(e.cadeira_vendida, L));

  set('e-consult-v',    fNum(e.consultas_vendidas));
  set('e-cv-consult-v', fPctRatio(e.consultas_vendidas, e.cadeira_vendida));
  setWidth('e-bar-consult-v', fBar(e.consultas_vendidas, L));

  set('e-consult-r',    fNum(e.consultas_realizadas));
  set('e-cv-consult-r', fPctRatio(e.consultas_realizadas, e.consultas_vendidas));
  setWidth('e-bar-consult-r', fBar(e.consultas_realizadas, L));

  set('e-fp',    fNum(e.fp_realizado));
  set('e-cv-fp', fPctRatio(e.fp_realizado, e.consultas_realizadas));
  setWidth('e-bar-fp', fBar(e.fp_realizado, L));

  set('e-combos',    fNum(e.combos_vendidos));
  set('e-cv-combos', fPctRatio(e.combos_vendidos, e.fp_realizado));
  setWidth('e-bar-combos', fBar(e.combos_vendidos, L));

  // CAC
  set('e-cpl',      fBRL(e.cpl));
  set('e-cac-cad',  fBRL(e.cac_cadeira));
  set('e-cac-cons', fBRL(e.cac_consulta));
  set('e-cac-comb', fBRL(e.cac_combos));
  set('e-ticket',   fBRL(e.ticket_medio));
}

// ── Preencher Funil Digital ───────────────────────────────────
function fillDigital(d) {
  const L = d.total_leads || 1;

  // Header meta
  set('d-invest', fBRLShort(d.investimento));
  set('d-fatur',  fBRLShort(d.faturamento_total));
  set('d-lucro',  fLucroShort(d.lucro));
  set('d-roi',    fROI(d.roi));

  // Funnel rows + bars + conversions
  set('d-leads',     fNum(d.total_leads));
  setWidth('d-bar-leads', '100%');

  set('d-qualif',    fNum(d.leads_qualificados));
  set('d-cv-qualif', fPctRatio(d.leads_qualificados, d.total_leads));
  setWidth('d-bar-qualif', fBar(d.leads_qualificados, L));

  set('d-consult-v',    fNum(d.consultas_vendidas));
  set('d-cv-consult-v', fPctRatio(d.consultas_vendidas, d.leads_qualificados));
  setWidth('d-bar-consult-v', fBar(d.consultas_vendidas, L));

  set('d-consult-r',    fNum(d.consultas_realizadas));
  set('d-cv-consult-r', fPctRatio(d.consultas_realizadas, d.consultas_vendidas));
  setWidth('d-bar-consult-r', fBar(d.consultas_realizadas, L));

  set('d-fp',    fNum(d.fp_realizado));
  set('d-cv-fp', fPctRatio(d.fp_realizado, d.consultas_realizadas));
  setWidth('d-bar-fp', fBar(d.fp_realizado, L));

  set('d-combos',    fNum(d.combos_vendidos));
  set('d-cv-combos', fPctRatio(d.combos_vendidos, d.fp_realizado));
  setWidth('d-bar-combos', fBar(d.combos_vendidos, L));

  // CAC
  set('d-cpl',      fBRL(d.cpl));
  set('d-cac-cons', fBRL(d.cac_consulta));
  set('d-cac-comb', fBRL(d.cac_combos));
  set('d-ticket',   fBRL(d.ticket_medio));
}

// ── Badges de performance ─────────────────────────────────────
function applyBadges(e, d) {
  const eRoi   = Number(e.roi)   || 0;
  const dRoi   = Number(d.roi)   || 0;
  const eLucro = Math.max(0, Number(e.lucro) || 0);
  const dLucro = Math.max(0, Number(d.lucro) || 0);

  const eBadge = document.getElementById('e-perf-badge');
  const dBadge = document.getElementById('d-perf-badge');

  function setBadge(el, eWinsRoi, eWinsLucro, isEvento) {
    const winsRoi   = isEvento ? eWinsRoi   : !eWinsRoi;
    const winsLucro = isEvento ? eWinsLucro : !eWinsLucro;
    el.className = 'perf-badge';
    el.style.display = 'none';
    if (winsRoi && winsLucro) {
      el.textContent = '🏆 Líder Absoluto';
      el.classList.add('absoluto');
      el.style.display = 'inline-block';
    } else if (winsRoi) {
      el.textContent = '📈 Melhor ROI';
      el.classList.add('roi');
      el.style.display = 'inline-block';
    } else if (winsLucro) {
      el.textContent = '💰 Maior Lucro';
      el.classList.add('lucro');
      el.style.display = 'inline-block';
    }
  }

  setBadge(eBadge, eRoi > dRoi, eLucro > dLucro, true);
  setBadge(dBadge, eRoi > dRoi, eLucro > dLucro, false);
}

// ── Badge de período ──────────────────────────────────────────
function updateBadge(year, months) {
  const badge = document.getElementById('filter-label-badge');
  if (!year && !months) { badge.textContent = 'TODOS OS PERÍODOS'; return; }
  const parts = [];
  if (year)   parts.push(year);
  if (months) {
    const labels = months.split(',').map(n => MONTHS[parseInt(n) - 1]);
    parts.push(labels.join(', '));
  }
  badge.textContent = parts.join(' · ').toUpperCase();
}

// ── Carregar API ──────────────────────────────────────────────
async function loadDashboard(year, months, campanha, form, responsavel, dataInicio, dataFim) {
  const overlay = document.getElementById('loading-overlay');
  const errBan  = document.getElementById('error-banner');

  overlay.classList.remove('hidden');
  document.body.classList.add('loading');
  errBan.style.display = 'none';

  let url = API_URL;
  const params = new URLSearchParams();
  if (year)        params.set('year',        year);
  if (months)      params.set('months',      months);
  if (campanha)    params.append('campanha',    campanha);
  if (form)        params.append('form',        form);
  if (responsavel) params.append('responsavel', responsavel);
  if (dataInicio)  params.append('data_inicio', dataInicio);
  if (dataFim)     params.append('data_fim',    dataFim);
  if ([...params].length) url += '?' + params.toString();

  try {
    const res  = await fetch(url, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    fillGlobal(data.global);
    fillEvento(data.evento);
    fillDigital(data.digital);
    applyBadges(data.evento, data.digital);

    const ts = data.meta?.generated
      ? new Date(data.meta.generated.replace(' ', 'T')).toLocaleString('pt-BR')
      : new Date().toLocaleString('pt-BR');
    document.getElementById('last-updated').textContent = 'Atualizado em: ' + ts;

  } catch (err) {
    errBan.textContent   = '⚠️ Erro ao carregar dados: ' + err.message;
    errBan.style.display = 'block';
    console.error(err);
  } finally {
    overlay.classList.add('hidden');
    document.body.classList.remove('loading');
  }
}

// ── loadFiltros ───────────────────────────────────────────────
async function loadFiltros() {
  try {
    const [resForms, resResps, resCamps] = await Promise.all([
      fetch('<?= $this->site ?>executive/forms',        { cache: 'no-store' }),
      fetch('<?= $this->site ?>executive/responsaveis', { cache: 'no-store' }),
      fetch('<?= $this->site ?>executive/campanhas',    { cache: 'no-store' }),
    ]);
    formDropdown.load(await resForms.json());
    respDropdown.load(await resResps.json());
    campDropdown.load(await resCamps.json());
  } catch (e) {
    console.warn('Não foi possível carregar filtros:', e.message);
  }
}

// ── Filtros ───────────────────────────────────────────────────
function applyFilters() {
  const year        = yearDropdown.getValue();
  const months      = monthDropdown.getValue();
  const campanha    = campDropdown.getValue();
  const form        = formDropdown.getValue();
  const responsavel = respDropdown.getValue();
  const dataInicio  = getDateInput('data-inicio');
  const dataFim     = getDateInput('data-fim');
  if (!dataInicio && !dataFim) {
    const eff = computeEffectiveDates(year, months);
    setEffectiveDate('data-inicio', eff.start);
    setEffectiveDate('data-fim', eff.end);
  }
  const apiInicio = dataInicio || document.getElementById('data-inicio')?.value || null;
  const apiFim    = dataFim    || document.getElementById('data-fim')?.value    || null;
  updateBadge(year, months);
  loadDashboard(year, months, campanha, form, responsavel, apiInicio, apiFim);
}

function clearFilters() {
  yearDropdown.reset(true);
  monthDropdown.reset(true);
  campDropdown.reset();
  formDropdown.reset();
  respDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim',    'data-fim-clear');
  updateBadge(null, null);
  loadDashboard(null, null, null, null, null, null, null);
}

// ── Init ──────────────────────────────────────────────────────
['data-inicio', 'data-fim'].forEach(id => {
  const input    = document.getElementById(id);
  const clearBtn = document.getElementById(id + '-clear');
  if (!input || !clearBtn) return;
  input.addEventListener('focus', () => {
    const wrap = input.closest('.date-input-wrap');
    if (wrap?.classList.contains('is-effective')) {
      wrap.classList.remove('is-effective');
      input.value = '';
    }
  });
  input.addEventListener('input', () => clearBtn.classList.toggle('visible', !!input.value));
  clearBtn.addEventListener('click', () => {
    const wrap = input.closest('.date-input-wrap');
    if (wrap) wrap.classList.remove('is-effective');
    input.value = '';
    clearBtn.classList.remove('visible');
  });
});

loadFiltros();
applyFilters();
</script>
</div><!-- /dash-digital -->
