<div class="dash-executive">
<style>
    /* ── TOKENS LOCAIS ──────────────────────────────
       Overrides do bloco .dash-executive de css/bi-theme.css:1203-1253. Mesma
       especificidade, mas este <style> vem depois do <link>, então ganha por
       ordem de fonte. Escopado — nenhum outro dashboard é afetado. */
    .dash-executive {
      /* bi-theme usava #94a3b8 no claro e #64748b no dark — invertido: o cinza
         mais claro ficava no fundo branco (contraste 2.5:1). Atinge todo label
         pequeno: .fin-label, .fin-sublabel (9.5px), .kpi-chip-label,
         .seg-kpi-label, .vistos-table th. */
      --text-muted: #64748b;   /* 4.8:1 sobre #ffffff */
    }
    html.dark .dash-executive {
      --text-muted: #94a3b8;   /* 7.0:1 sobre #0f172a */
      /* O dark do bi-theme troca superfícies e os tints *-bg, mas mantém as
         matizes do claro — aí a caixa âmbar do ROI dava marrom sobre marrom
         (3.9:1) e o header do card Evento ficava com tint laranja vivo e texto
         marrom-dourado. */
      --amber:  #fbbf24;
      --green:  #34d399;
      --blue:   #60a5fa;
      --orange: #f59e0b;
      --red:    #f87171;
      --danger: #f87171;
    }

    /* ── FILTROS ──────────────────────────────────────────── */
    /* A barra é uma extensão do header escuro — e, como ele, não muda no dark
       mode (os tokens --hdr-* de bi-theme.css:38-50 são fixos nos dois temas).
       Em vez de retintar os ~20 controles um a um, os tokens de superfície e
       texto são REDEFINIDOS aqui: as regras abaixo continuam escritas com
       var(--surface-2)/var(--text-primary)/var(--blue) e passam a resolver para
       os tons do header. Custom property é herdada, então os dropdowns
       flutuantes (filhos de .filters) vêm junto. */
    .filters {
      --surface:        #1e2845;                 /* fundo dos dropdowns */
      --surface-2:      rgba(255,255,255,.07);   /* fundo de input/botão */
      --surface-3:      rgba(255,255,255,.10);
      --border:         rgba(255,255,255,.14);
      --border-soft:    rgba(255,255,255,.22);
      --text-primary:   var(--hdr-text);         /* #ffffff */
      --text-secondary: var(--hdr-meta);         /* #c7cfe3 */
      --text-muted:     var(--hdr-sub);          /* #9aa5c0 — 5.5:1 sobre a barra */
      --blue:           #93c5fd;                 /* 8.9:1 sobre #222c45; o --blue
                                                    do tema claro (#1a6ec8) dava
                                                    2.4:1 aqui */
      --blue-bg:        rgba(59,130,246,.16);

      background: var(--hdr-to);
      border-bottom: 1px solid rgba(255,255,255,.08);
      padding: 10px 24px;
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }
    /* O ícone nativo do date picker é desenhado pela UA conforme o
       color-scheme: no tema claro ele vem escuro e desaparece sobre a barra. */
    .filters input[type="date"] { color-scheme: dark; }

    .filter-label {
      font-size: 10px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.07em;
    }

    .filter-select {
      background: var(--surface-2);
      border: 1px solid var(--border);
      color: var(--text-primary);
      border-radius: var(--radius-sm);
      padding: 5px 28px 5px 10px;
      font-size: 11px;
      font-family: 'Fira Sans', sans-serif;
      cursor: pointer;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
      appearance: none;
      -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2394a3b8'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 9px center;
      background-color: var(--surface-2);
    }

    .filter-select:focus {
      border-color: var(--blue);
      box-shadow: 0 0 0 3px var(--blue-bg);
    }


    .multi-dd-wrap { position: relative; }
    .multi-dd-btn {
      background: var(--surface-2); border: 1px solid var(--border);
      color: var(--text-primary); border-radius: var(--radius-sm);
      padding: 5px 10px; font-size: 11px; font-family: 'Fira Sans', sans-serif;
      cursor: pointer; outline: none; white-space: nowrap;
      display: flex; align-items: center; gap: 5px; transition: border-color 0.2s;
    }
    .multi-dd-btn:hover, .multi-dd-btn.open { border-color: var(--blue); }
    .multi-dd-btn.has-value { color: var(--blue); border-color: var(--blue); background: var(--blue-bg); font-weight: 600; }
    .dd-caret { font-size: 9px; color: var(--text-muted); margin-left: 2px; }
    .multi-dd-list {
      display: none; position: absolute; top: calc(100% + 4px); left: 0;
      background: var(--surface); border: 1px solid var(--border);
      border-radius: var(--radius-sm); box-shadow: var(--shadow);
      z-index: 300; min-width: 100%;
      /* bi-theme.css:540-546 dá max-height:240px + overflow-y:auto a esta mesma
         classe. Aqui havia `overflow: hidden` — mesma especificidade, mais
         abaixo, então vencia SÓ o overflow: o teto de 240px continuava valendo
         e os itens além dele (Set a Dez, no filtro de Mês) ficavam recortados e
         inalcançáveis, sem scroll possível. O teto agora é explícito e cabem os
         12 meses (12 x 31px = 374px); listas maiores rolam. */
      max-height: 380px;
      overflow: hidden auto;
    }
    .multi-dd-list.open { display: block; }
    .multi-dd-item {
      padding: 7px 12px; font-size: 11px; cursor: pointer;
      display: flex; align-items: center; gap: 8px;
      color: var(--text-secondary); user-select: none; white-space: nowrap;
    }
    .multi-dd-item:hover { background: var(--surface-2); }
    .multi-dd-item .dd-check {
      width: 12px; height: 12px; border-radius: 3px; border: 1.5px solid var(--border);
      flex-shrink: 0; display: flex; align-items: center; justify-content: center;
    }
    .multi-dd-item.selected { color: var(--blue); font-weight: 600; }
    .multi-dd-item.selected .dd-check {
      /* var(--surface) e nao #fff: no dark o --blue local e claro (#60a5fa) e um
         check branco cairia para ~2.6:1. Assim o glifo acompanha o fundo do tema. */
      background: var(--blue); border-color: var(--blue); color: var(--surface); font-size: 8px;
    }
    .multi-dd-item.selected .dd-check::after { content: '✓'; }
    .camp-tags { display: flex; flex-wrap: wrap; gap: 4px; padding: 0; }
    .camp-tags:not(:empty) { padding: 4px 6px 0; }
    .filter-tag {
      display: inline-flex; align-items: center; gap: 3px;
      background: var(--blue-bg); border: 1px solid var(--blue); color: var(--blue);
      border-radius: 4px; padding: 2px 6px; font-size: 10px; font-weight: 600;
      font-family: 'Fira Code', monospace;
    }
    .tag-remove {
      background: none; border: none; color: var(--blue); cursor: pointer;
      padding: 0; font-size: 12px; line-height: 1; opacity: 0.7;
    }
    .tag-remove:hover { opacity: 1; }
    .btn-apply {
      border-radius: var(--radius-sm);
      padding: 6px 16px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.03em;
    }

    .btn-clear {
      background: transparent;
      border: 1px solid var(--border);
      color: var(--text-muted);
      border-radius: var(--radius-sm);
      padding: 5px 13px;
      font-size: 11px;
      cursor: pointer;
      transition: all 0.18s;
      font-family: 'Fira Sans', sans-serif;
    }

    .btn-clear:hover { border-color: var(--text-secondary); color: var(--text-secondary); }

    .filter-sep {
      width: 1px;
      height: 20px;
      background: var(--border);
      flex-shrink: 0;
    }

    /* ── Campanha searchable dropdown ── */
    .camp-wrap {
      position: relative;
      width: 220px;
      flex-shrink: 0;
    }

    .camp-input-row {
      display: flex;
      align-items: center;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      background: var(--surface-2);
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .camp-input-row:focus-within {
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
    }

    .camp-icon {
      padding: 0 6px 0 8px;
      color: var(--text-muted);
      display: flex;
      align-items: center;
      pointer-events: none;
    }

    .camp-input {
      border: none;
      outline: none;
      background: transparent;
      font-size: 11px;
      font-family: 'Fira Sans', sans-serif;
      color: var(--text-primary);
      padding: 5px 4px 5px 0;
      width: 100%;
      min-width: 0;
    }

    .camp-input::placeholder { color: var(--text-muted); }

    .camp-clear {
      padding: 0 7px;
      color: var(--text-muted);
      cursor: pointer;
      background: none;
      border: none;
      font-size: 13px;
      line-height: 1;
      display: none;
      align-items: center;
    }

    .camp-clear:hover { color: var(--red); }

    .camp-clear.visible { display: flex; }

    .camp-dropdown {
      position: absolute;
      top: calc(100% + 4px);
      left: 0;
      width: 100%;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow-lg);
      max-height: 240px;
      overflow-y: auto;
      z-index: 200;
      display: none;
    }

    .camp-dropdown.open { display: block; }

    .camp-opt {
      padding: 7px 12px;
      font-size: 11px;
      cursor: pointer;
      color: var(--text-primary);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      transition: background 0.12s;
    }

    .camp-opt:hover, .camp-opt.focused { background: var(--blue-bg); color: var(--blue); }

    .camp-opt.selected { font-weight: 700; color: var(--blue); }

    .camp-empty {
      padding: 10px 12px;
      font-size: 11px;
      color: var(--text-muted);
      text-align: center;
    }

    /* ── LOADING / ERROR ─────────────────────────────────── */
    #loading-overlay {
      position: fixed; top: 0; left: 0; right: 0; z-index: 999;
      height: 3px; background: transparent; transition: opacity 0.3s;
    }
    #loading-overlay::after {
      content: ''; display: block; height: 100%;
      background: var(--hdr-stripe);
      animation: loadbar 1.2s ease-in-out infinite;
    }
    @keyframes loadbar {
      0%   { width: 0%; margin-left: 0; }
      50%  { width: 60%; margin-left: 20%; }
      100% { width: 0%; margin-left: 100%; }
    }
    #loading-overlay.hidden { opacity: 0; pointer-events: none; }
    body.loading main { opacity: 0.55; transition: opacity 0.2s; pointer-events: none; }
    body:not(.loading) main { opacity: 1; transition: opacity 0.3s; }

    .spinner {
      width: 40px; height: 40px;
      border: 3px solid var(--border);
      border-top-color: var(--blue);
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    .loading-text {
      font-size: 13px;
      color: var(--text-secondary);
      font-family: 'Fira Code', monospace;
    }

    #error-banner {
      background: var(--danger-glow);
      border: 1px solid var(--danger);
      border-radius: var(--radius);
      padding: 14px 20px;
      color: var(--danger);
      margin: 16px 24px;
      display: none;
      font-size: 12px;
    }

    /* ── MAIN LAYOUT ──────────────────────────────────────── */
    .main {
      padding: 20px 24px;
      display: flex;
      flex-direction: column;
      gap: 20px;
      max-width: 1800px;
      margin: 0 auto;
    }

    /* ── PAINEL GLOBAL — 2 linhas empilhadas (KPIs do funil + Resumo
       Financeiro/Vistos) ──────────────────────────────────── */
    .panel-global {
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .dash-executive .panel-global {
      background: none;
      border: none;
      box-shadow: none;
      border-radius: 0;
      overflow: visible;
    }
    .dash-executive .panel-global .panel-section {
      border-right: none;
    }

    /* Faixa financeira horizontal (Investimento/Faturamento/Lucro/ROI) dentro
       do card "Resumo Financeiro" */
    .fin-strip { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 7px; }
    @media (max-width: 900px) { .fin-strip { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 500px) { .fin-strip { grid-template-columns: 1fr; } }

    .fin-cell {
      background: var(--surface-2);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 8px 9px;
      display: flex;
      flex-direction: column;
      gap: 2px;
      align-items: center;
      justify-content: center;
      text-align: center;
      min-width: 0;
      transition: transform 0.2s cubic-bezier(0.4,0,0.2,1), box-shadow 0.2s cubic-bezier(0.4,0,0.2,1), border-color 0.2s cubic-bezier(0.4,0,0.2,1);
    }
    .fin-cell:hover {
      transform: translateY(-1px);
      box-shadow: var(--shadow-sm);
      border-color: var(--border-soft);
    }
    .fin-cell .fin-value { font-size: 17px; line-height: 1.1; }
    .fin-cell .fin-value, .fin-cell .fin-subvalue { white-space: nowrap; }
    .fin-cell .fin-label, .fin-cell .fin-sublabel {
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;
    }
    /* Empilhado: "Mkt $ 142,000" + "Físico $ 43,000" lado a lado pedem
       ~217px e a célula tem ~118px úteis — a quebra ficava irregular. */
    .fin-subtitle { display: flex; flex-direction: column; align-items: center; gap: 0; margin-top: 2px; }
    .fin-sub-item { display: flex; align-items: baseline; gap: 4px; }

    /* Linha 2: Resumo Financeiro completo + 2 tabelas de visto */
    .panel-row-3 { display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 14px; align-items: stretch; }
    .panel-row-3 > .panel-section { min-height: 0; }
    @media (max-width: 1400px) {
      .panel-row-3 { grid-template-columns: 1fr 1fr; }
      .panel-row-3 > .panel-section.fin-resumo { grid-column: 1 / -1; }
    }
    @media (max-width: 760px) {
      .panel-row-3 { grid-template-columns: 1fr; }
      .panel-row-3 > .panel-section.fin-resumo { grid-column: auto; }
    }

    .panel-section {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-sm);
      padding: 18px 22px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    /* Fix A — bi-theme.css tem ".dash-executive .panel-global .panel-section"
       (maior especificidade) definindo padding: 18px 22px; sem esta regra
       equivalente, o valor abaixo nunca chegaria a renderizar.

       flex-wrap/align-items — bi-theme.css:746 declara "flex-wrap: wrap;
       align-items: center" no ".panel-section" base, e NENHUMA regra mais
       específica os sobrescreve. Combinado com "flex-direction: column", isso
       fazia do card um container flex multi-COLUNA: ao passar da altura
       disponível, o conteúdo final abria uma 2a coluna fora da track do grid e
       o card vizinho (opaco) pintava por cima. "align-items: stretch" também é
       o que faz .mini-table-scroll ocupar a largura inteira do painel. */
    .dash-executive .panel-global .panel-section {
      padding: 22px 26px;
      flex-wrap: nowrap;
      align-items: stretch;
    }
    /* 0,4,0 — ".panel-row-3 > .panel-section" (0,2,0) perdia para a regra acima
       (0,3,0), então este padding nunca chegava a renderizar. */
    .dash-executive .panel-global .panel-row-3 > .panel-section {
      padding: 10px 12px;
      gap: 7px;
    }

    .mini-table-scroll {
      flex: 1;
      min-height: 0;
      overflow-y: auto;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      /* Teto de altura. Sem ele a linha era ditada pelo card mais alto e descia
         até o fim da tela — o flex:1 acima nunca chegava a apertar a área de
         rolagem. 195px + cromo do card = 244px, a altura natural do Resumo
         Financeiro em 3 linhas: as 3 colunas terminam juntas, sem espaço morto.
         O teto vive aqui, e não num grid-auto-rows do .panel-row-3: no Chrome
         minmax(0,Npx) vira track FIXA de N e, no layout de 2 colunas
         (<=1400px), o Resumo Financeiro ganha linha própria e sobrariam ~120px
         vazios dentro dele. */
      max-height: 195px;
    }

    /* A barra de rolagem só ganha cor quando o mouse entra no card — mesmo
       comportamento das tabelas do Marketing (bi-theme.css:1624-1643), herdado
       do Power BI. Os 9px do ::-webkit-scrollbar ficam reservados mesmo com o
       thumb transparente, então não há salto de layout ao entrar/sair. */
    .mini-table-scroll {
      scrollbar-width: thin;
      scrollbar-color: transparent transparent;
      transition: scrollbar-color 0.15s;
    }
    .panel-row-3 > .panel-section:hover .mini-table-scroll {
      scrollbar-color: var(--border) transparent;
    }
    .mini-table-scroll::-webkit-scrollbar { width: 9px; }
    .mini-table-scroll::-webkit-scrollbar-track { background: transparent; }
    .mini-table-scroll::-webkit-scrollbar-thumb {
      background: transparent;
      border-radius: 99px;
      border: 2px solid transparent;
      background-clip: content-box;
    }
    .panel-row-3 > .panel-section:hover .mini-table-scroll::-webkit-scrollbar-thumb {
      background: var(--border);
      background-clip: content-box;
    }
    .panel-row-3 > .panel-section:hover .mini-table-scroll::-webkit-scrollbar-thumb:hover {
      background: var(--text-muted);
      background-clip: content-box;
    }

    .vistos-table { width: 100%; border-collapse: collapse; font-size: 11.5px; }

    .vistos-table thead th {
      position: sticky;
      top: 0;
      z-index: 1;
      background: var(--surface-2);
      padding: 6px 10px;
      font-size: 9.5px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: var(--text-muted);
      text-align: left;
      border-bottom: 1px solid var(--border);
    }

    .vistos-table thead th.num,
    .vistos-table td.num { text-align: right; }

    .vistos-table td {
      padding: 5px 10px;
      /* altura fixa da linha: torna previsível quantas linhas cabem no teto de
         195px da área de rolagem (~6,5) */
      height: 26px;
      font-family: 'Fira Code', monospace;
      color: var(--text-primary);
      border-bottom: 1px solid var(--border);
    }

    .vistos-table tbody tr:last-child td { border-bottom: none; }
    /* .vistos-table é classe local — nunca herdou o hover de .bi-table. */
    .vistos-table tbody tr { transition: background 0.12s ease; }
    .vistos-table tbody tr:hover { background: var(--surface-2); }
    .vistos-table .td-nome { font-family: inherit; }
    .vistos-table .td-amber { color: var(--amber); font-weight: 700; }
    .vistos-table .td-green { color: var(--green); font-weight: 700; }

    .vistos-empty {
      text-align: center;
      color: var(--text-muted);
      font-style: italic;
      padding: 10px !important;
    }

    .section-title {
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: var(--text-primary);
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Escopado na linha 2: a regra base acima veste o título de todo card.
       Descendente (e não filho direto) porque no Resumo Financeiro o título
       agora vive dentro do .fin-head, ao lado da caixa de ROI. */
    .panel-row-3 > .panel-section .section-title {
      font-size: 11.5px;
      margin-bottom: 3px;
    }

    /* Header do Resumo Financeiro: título à esquerda, ROI no canto superior
       direito. O ::after do .section-title já é uma régua com flex:1, então ela
       preenche o meio e empurra o ROI para a direita. */
    .fin-head {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .fin-head .section-title {
      /* a base em bi-theme.css:748 tem width:100%, que num flex container
         disputaria espaço com a caixa de ROI */
      width: auto;
      flex: 1;
      min-width: 0;
      margin-bottom: 0;
    }
    .fin-head .fin-roi-box {
      flex-shrink: 0;
      gap: 7px;
      padding: 2px 9px;
    }
    .fin-head .fin-roi-caption { font-size: 9px; }
    .fin-head .fin-roi-value   { font-size: 14px; }

    .section-title::before {
      content: '';
      width: 3px;
      height: 14px;
      border-radius: 2px;
      /* Vermelho da marca (mesma barra de .dash-marketing .bi-card-header e do
         nav-dot do Executive). O azul anterior não casava com nada na página. */
      background: var(--red);
      flex-shrink: 0;
    }

    .section-title::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    .fin-label {
      font-size: 9.5px;
      font-weight: 600;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }

    .fin-value {
      font-family: 'Fira Code', monospace;
      font-size: 22px;
      font-weight: 700;
      letter-spacing: -0.015em;
    }

    /* Investimento é custo planejado, não alarme — em vermelho competia com
       Lucro negativo / ROI < 1x, que são os sinais que importam. */
    .fin-value.invest  { color: var(--text-primary); opacity: .9; }
    .fin-value.fatur   { color: var(--green); }
    .fin-value.lucro   { color: var(--green); }

    .fin-sublabel {
      font-size: 9px;
      font-weight: 500;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }

    .fin-subvalue {
      font-family: 'Fira Code', monospace;
      font-size: 11px;
      font-weight: 600;
      color: var(--text-secondary);
    }

    /* ROI em caixa própria full-width (modelo do .roi-badge do Marketing).
       Antes era uma tag de 11px encravada na célula de Investimento — sem
       destaque e semanticamente errado (ROI não é propriedade do custo). */
    .fin-roi-box {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      background: var(--amber-bg);
      border: 1px solid var(--amber);
      border-radius: var(--radius-sm);
      padding: 5px 12px;
    }

    .fin-roi-caption {
      font-size: 9.5px;
      font-weight: 700;
      color: var(--amber);
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .fin-roi-value {
      font-family: 'Fira Code', monospace;
      font-size: 16px;
      font-weight: 700;
      color: var(--amber);
      letter-spacing: -0.015em;
    }

    .lucro-badge {
      display: inline-flex;
      align-items: center;
      background: var(--green-bg);
      border: 1px solid var(--green);
      border-radius: var(--radius-sm);
      padding: 3px 10px;
      font-family: 'Fira Code', monospace;
      font-size: 14px;
      font-weight: 700;
      color: var(--green);
      margin-top: 2px;
      letter-spacing: -0.015em;
      /* .fin-cell .fin-value/.fin-subvalue têm nowrap (ver acima), mas o badge
         não é nenhum dos dois: "$ 10,028,589" quebrava em duas linhas e sozinho
         empurrava a faixa inteira ~20px para baixo. */
      white-space: nowrap;
    }

    .lucro-badge.negativo {
      background: var(--danger-glow);
      border-color: var(--danger);
      color: var(--danger);
    }

    .fin-roi-box.positivo {
      background: var(--green-bg);
      border-color: var(--green);
    }
    .fin-roi-box.positivo .fin-roi-caption,
    .fin-roi-box.positivo .fin-roi-value { color: var(--green); }

    .fin-roi-box.negativo {
      background: var(--danger-glow);
      border-color: var(--danger);
    }
    .fin-roi-box.negativo .fin-roi-caption,
    .fin-roi-box.negativo .fin-roi-value { color: var(--danger); }

    /* ── KPI CHIPS ────────────────────────────────────────── */
    .kpi-row {
      display: flex;
      gap: 10px;
    }
    /* Chips do funil (LINHA 1) -25%: 87px -> ~65px. Escopado em .kpi-row para
       não mexer nos 5 chips de custo do Resumo Financeiro, que têm as suas
       próprias medidas em .fin-kpi-grid. Os dois labels do chip (rótulo em cima
       e conversão embaixo) são o que mais pesa: 15px + 5px de margem cada. */
    .kpi-row > .kpi-chip { padding: 6px 9px; gap: 2px; }
    .kpi-row .kpi-chip-label { margin-bottom: 1px; }
    .kpi-row .kpi-chip-value { font-size: 14px; }

    /* Grade dos 5 chips de CPL/Ticket/CAC dentro do Resumo Financeiro.
       Como .kpi-row, os 5 chips herdavam "flex-wrap: wrap" + "min-width: 110px"
       de bi-theme.css:698-705 e pediam ~590px numa track de ~435px — quebravam
       em 2-3 linhas e eram o gatilho do bug de coluna do .panel-section. */
    /* 6 tracks em vez de 3: os 5 chips ocupam 2+2+2 na 1a linha e 3+3 na 2a,
       então nenhuma célula sobra vazia no canto inferior direito (era o que
       acontecia com repeat(3, 1fr) e um número ímpar de chips). */
    .fin-kpi-grid {
      display: grid;
      grid-template-columns: repeat(6, minmax(0, 1fr));
      gap: 7px;
    }
    /* 0,2,0 — neutraliza o min-width: 110px de bi-theme.css:703, que numa track
       minmax(0,1fr) faria o chip transbordar a própria coluna. */
    .fin-kpi-grid > .kpi-chip { min-width: 0; grid-column: span 2; }
    .fin-kpi-grid > .kpi-chip:nth-child(4),
    .fin-kpi-grid > .kpi-chip:nth-child(5) { grid-column: span 3; }
    /* Densidade dos 5 chips de custo, nos valores do .fin-cac-grid do Marketing
       (bi-theme.css:1855-1857). Escopado de propósito: as regras base
       .kpi-chip/-label/-value também vestem os 6 chips do funil na LINHA 1
       (:1236-1265), que continuam do tamanho de hoje. */
    .fin-kpi-grid > .kpi-chip { padding: 6px 9px; gap: 0; }
    .fin-kpi-grid .kpi-chip-label { font-size: 8.5px; margin-bottom: 2px; }
    .fin-kpi-grid .kpi-chip-value { font-size: 13px; }
    /* Full-width: 5 chips numa linha única (span 6/5 não divide, então volta
       para tracks iguais e span 1). */
    @media (max-width: 1400px) {
      .fin-kpi-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); }
      .fin-kpi-grid > .kpi-chip,
      .fin-kpi-grid > .kpi-chip:nth-child(4),
      .fin-kpi-grid > .kpi-chip:nth-child(5) { grid-column: span 1; }
    }
    @media (max-width: 760px) {
      .fin-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .fin-kpi-grid > .kpi-chip:nth-child(5) { grid-column: span 2; }
    }

    /* Trilho de etapa nos 6 chips do funil — a cor espelha a do valor e dá
       direção de leitura (aquisição → qualificação → ... → fecho).
       Não usamos .aquisicao/.consulta/... de bi-theme.css:716-721 porque elas
       também trocam o background por um tint *-glow que, no dark, composita
       sobre --bg (#020617) e apaga o aspecto de card do chip. */
    .kpi-chip.rail-red,
    .kpi-chip.rail-blue,
    .kpi-chip.rail-orange,
    .kpi-chip.rail-amber,
    .kpi-chip.rail-teal,
    .kpi-chip.rail-green { border-left-width: 3px; }
    .kpi-chip.rail-red    { border-left-color: var(--red); }
    .kpi-chip.rail-blue   { border-left-color: var(--blue); }
    .kpi-chip.rail-orange { border-left-color: var(--orange); }
    .kpi-chip.rail-amber  { border-left-color: var(--amber); }
    .kpi-chip.rail-teal   { border-left-color: var(--teal); }
    .kpi-chip.rail-green  { border-left-color: var(--green); }

    .kpi-chip {
      flex: 1;
      background: #ffffff;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 10px 10px;
      text-align: center;
      box-shadow: var(--shadow-sm);
      transition: transform 0.2s cubic-bezier(0.4,0,0.2,1), box-shadow 0.2s cubic-bezier(0.4,0,0.2,1), border-color 0.2s cubic-bezier(0.4,0,0.2,1);
    }

    html.dark .kpi-chip { background: var(--surface-2); }

    .kpi-chip:hover {
      transform: translateY(-1px);
      box-shadow: var(--shadow);
      border-color: var(--border-soft);
    }

    .kpi-chip.highlight {
      background: var(--green-bg);
      border-color: var(--green);
    }

    .kpi-chip-label {
      font-size: 10px;
      font-weight: 600;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 5px;
    }

    .kpi-chip-value {
      font-family: 'Fira Code', monospace;
      font-size: 17px;
      font-weight: 700;
      color: var(--text-primary);
      line-height: 1;
      letter-spacing: -0.015em;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .kpi-chip-value.blue   { color: var(--blue); }
    .kpi-chip-value.orange { color: var(--orange); }
    .kpi-chip-value.green  { color: var(--green); }
    .kpi-chip-value.teal   { color: var(--teal); }
    .kpi-chip-value.amber  { color: var(--amber); }
    .kpi-chip-value.purple { color: var(--purple); }

    /* ── GRADE DE SEGMENTOS ───────────────────────────────── */
    .segments-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
    }

    @media (max-width: 1200px) { .segments-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 700px)  { .segments-grid { grid-template-columns: 1fr; } }

    /* ── CARD DE SEGMENTO ─────────────────────────────────── */
    .seg-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      transition: box-shadow 0.22s ease, transform 0.22s ease;
    }
    /* Fix F — bi-theme.css tem ".dash-executive .seg-card" com mais
       especificidade definindo a mesma transition; sem esta regra
       equivalente, a curva/duração acima nunca chegaria a renderizar. */
    .dash-executive .seg-card { transition: box-shadow 0.24s cubic-bezier(0.4,0,0.2,1), transform 0.24s cubic-bezier(0.4,0,0.2,1); }

    .seg-card:hover {
      box-shadow: var(--shadow);
      transform: translateY(-2px);
    }
    /* Fix C — bi-theme.css tem ".dash-executive .seg-card:hover" com mais
       especificidade usando o token global --sh (não o --shadow local
       do .dash-executive); sem esta regra, o box-shadow acima nunca renderiza. */
    .dash-executive .seg-card:hover { box-shadow: var(--shadow); }

    .seg-card.evento    { border-top: 3px solid var(--orange); }
    .seg-card.digital   { border-top: 3px solid var(--blue); }
    .seg-card.organico  { border-top: 3px solid var(--green); }
    .seg-card.indicacao { border-top: 3px solid var(--purple); }

    .seg-header {
      padding: 13px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--border);
    }

    .seg-card.evento    .seg-header { background: var(--orange-bg); }
    .seg-card.digital   .seg-header { background: var(--blue-bg); }
    .seg-card.organico  .seg-header { background: var(--green-bg); }
    .seg-card.indicacao .seg-header { background: var(--purple-bg); }

    .seg-name {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .seg-card.evento    .seg-name { color: var(--orange); }
    .seg-card.digital   .seg-name { color: var(--blue-dark); }
    .seg-card.organico  .seg-name { color: var(--green); }
    .seg-card.indicacao .seg-name { color: var(--purple); }

    .seg-name-icon {
      width: 24px; height: 24px;
      border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
    }

    .seg-card.evento    .seg-name-icon { background: var(--orange-bg); }
    .seg-card.digital   .seg-name-icon { background: var(--blue-bg); }
    .seg-card.organico  .seg-name-icon { background: var(--green-bg); }
    .seg-card.indicacao .seg-name-icon { background: var(--purple-bg); }

    .seg-name-icon svg { width: 13px; height: 13px; }

    .seg-header-metrics {
      display: flex;
      gap: 14px;
    }

    .seg-header-kpi { text-align: right; }

    .seg-header-kpi-label {
      font-size: 10.5px;
      font-weight: 600;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 2px;
    }

    .seg-header-kpi-value {
      font-family: 'Fira Code', monospace;
      font-size: 14px;
      font-weight: 700;
      letter-spacing: -0.01em;
    }
    /* Fix D — bi-theme.css tem ".dash-executive .seg-header-kpi-value" com
       mais especificidade; sem esta regra equivalente, o tamanho/tracking
       acima nunca chegariam a renderizar.
       white-space — sem isso "$ 214,000" quebrava em duas linhas no layout
       4-up e desalinhava o header (visível no card Orgânico). */
    .dash-executive .seg-header-kpi-value { font-size: 15px; letter-spacing: -0.008em; white-space: nowrap; }
    /* No layout 4-up o header pede ~257px e só tem ~251px (1440px) / ~207px
       (1280px). Deixar o wrap decidir criava headers de alturas diferentes na
       mesma linha (Evento/Digital quebravam, Orgânico/Indicação não, porque
       "ROI —" é mais curto). flex: 0 0 100% põe as métricas sempre na própria
       linha — 4 cards com a mesma altura de header em qualquer largura. */
    .dash-executive .seg-header         { flex-wrap: wrap; row-gap: 8px; }
    .dash-executive .seg-header-metrics { flex: 0 0 100%; justify-content: flex-end; }
    .dash-executive .seg-name           { min-width: 0; }
    .dash-executive .seg-name-icon      { flex-shrink: 0; }

    .seg-card.evento    .seg-header-kpi-value { color: var(--orange); }
    .seg-card.digital   .seg-header-kpi-value { color: var(--blue); }
    .seg-card.organico  .seg-header-kpi-value { color: var(--green); }
    .seg-card.indicacao .seg-header-kpi-value { color: var(--purple); }

    /* Lucro/ROI ganham cor semântica (bom/mau) por cima da cor do segmento */
    .seg-card.evento    .seg-header-kpi-value.positivo { color: var(--green); }
    .seg-card.evento    .seg-header-kpi-value.alerta   { color: var(--amber); }
    .seg-card.evento    .seg-header-kpi-value.negativo { color: var(--danger); }
    .seg-card.digital   .seg-header-kpi-value.positivo { color: var(--green); }
    .seg-card.digital   .seg-header-kpi-value.alerta   { color: var(--amber); }
    .seg-card.digital   .seg-header-kpi-value.negativo { color: var(--danger); }
    .seg-card.organico  .seg-header-kpi-value.positivo { color: var(--green); }
    .seg-card.organico  .seg-header-kpi-value.alerta   { color: var(--amber); }
    .seg-card.organico  .seg-header-kpi-value.negativo { color: var(--danger); }
    .seg-card.indicacao .seg-header-kpi-value.positivo { color: var(--green); }
    .seg-card.indicacao .seg-header-kpi-value.alerta   { color: var(--amber); }
    .seg-card.indicacao .seg-header-kpi-value.negativo { color: var(--danger); }

    /* ROI não aplicável (Orgânico/Indicação — sem investimento pago) */
    .seg-header-kpi.muted .seg-header-kpi-value { color: var(--text-muted); }
    /* Orgânico/Indicação não têm investimento pago — o ROI mostra "—" e a
       barra de 0x-2x ficava como um traço órfão embaixo. */
    .seg-header-kpi.muted .roi-bar { display: none; }

    /* Barrinha de progresso do ROI (0x–2x, marca em 1x = ponto de equilíbrio) */
    .roi-bar {
      width: 44px;
      height: 3px;
      border-radius: 2px;
      background: var(--border);
      margin: 4px 0 0 auto;
      position: relative;
      overflow: hidden;
    }
    .roi-bar::after {
      content: '';
      position: absolute;
      left: 50%;
      top: -1px;
      bottom: -1px;
      width: 1px;
      background: var(--text-secondary);
      opacity: 0.55;
    }
    .roi-bar-fill {
      position: absolute;
      left: 0; top: 0; bottom: 0;
      width: 0%;
      border-radius: 2px;
      background: var(--text-muted);
      transition: width 0.3s ease, background-color 0.3s ease;
    }
    .roi-bar-fill.positivo { background: var(--green); }
    .roi-bar-fill.alerta   { background: var(--amber); }
    .roi-bar-fill.negativo { background: var(--danger); }

    /* CAC & CPL não aplicável (Orgânico/Indicação) */
    .seg-cac-na {
      font-size: 11px;
      color: var(--text-muted);
      font-style: italic;
      padding: 4px 2px 2px;
    }

    .seg-body {
      padding: 14px 12px;
      display: flex;
      flex-direction: column;
      gap: 8px;
      flex: 1;
    }
    /* Fix B — bi-theme.css tem ".dash-executive .seg-body" com mais
       especificidade definindo gap: 8px; sem esta regra equivalente,
       o valor abaixo nunca chegaria a renderizar. */
    .dash-executive .seg-body { gap: 12px; }

    .seg-sub-title {
      font-size: 10.5px;
      font-weight: 600;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      padding-top: 6px;
      border-top: 1px solid var(--border);
      margin-top: 2px;
    }

    .seg-kpi-row {
      display: flex;
      gap: 5px;
    }

    .seg-kpi {
      flex: 1;
      background: var(--surface-2);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 7px 6px;
      text-align: center;
      transition: border-color 0.18s;
    }
    /* Fix E/G — bi-theme.css tem ".dash-executive .seg-kpi" com mais
       especificidade definindo border-radius (token global --r-sm) e
       transition; sem estas regras equivalentes, os valores acima nunca
       chegariam a renderizar. */
    .dash-executive .seg-kpi {
      border-radius: var(--radius-sm);
      transition: border-color 0.2s cubic-bezier(0.4,0,0.2,1);
    }

    .seg-kpi:hover { border-color: var(--border-soft); }
    /* Fix G (cont.) — bi-theme.css tem ".dash-executive .seg-kpi:hover" com
       mais especificidade aplicando var(--border) em vez de var(--border-soft)
       como o autor original pretendia; sem esta regra, o hover acima nunca
       chegaria a renderizar. */
    .dash-executive .seg-kpi:hover { border-color: var(--border-soft); }

    .seg-kpi-label {
      font-size: 10px;
      font-weight: 600;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 4px;
    }

    .seg-kpi-value {
      font-family: 'Fira Code', monospace;
      font-size: 14px;
      font-weight: 700;
      color: var(--text-primary);
      letter-spacing: -0.01em;
    }

    .seg-kpi-value.orange { color: var(--orange); }
    .seg-kpi-value.green  { color: var(--green); }
    .seg-kpi-value.teal   { color: var(--teal); }
    .seg-kpi-value.amber  { color: var(--amber); }
    .seg-kpi-value.blue   { color: var(--blue); }
    .seg-kpi-value.purple { color: var(--purple); }
    /* investimento: custo, não alarme — ver .fin-value.invest */
    .seg-kpi-value.red    { color: var(--text-secondary); }
    .seg-kpi-value.muted  { color: var(--text-secondary); font-size: 13px; }

    /* ── DATE RANGE FILTER ────────────────────────────────── */
    .date-range-group {
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .date-input-wrap {
      display: flex;
      align-items: center;
      gap: 4px;
      background: var(--surface-2);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 0 7px;
      height: 28px;
      transition: border-color 0.15s;
    }
    .date-input-wrap:focus-within { border-color: var(--blue); }
    .date-input-wrap input[type="date"] {
      background: transparent;
      border: none;
      outline: none;
      color: var(--text-primary);
      font-family: 'Fira Code', monospace;
      font-size: 10px;
      cursor: pointer;
      width: 94px;
      color-scheme: light dark;
    }
    html.dark .date-input-wrap input[type="date"] { color-scheme: dark; }
    /* Quando o campo mostra o período efetivo (só leitura visual) */
    .date-sep-label {
      font-size: 9px;
      color: var(--text-muted);
      font-weight: 600;
      letter-spacing: 0.04em;
    }
    .date-clear-btn {
      background: none;
      border: none;
      cursor: pointer;
      color: var(--text-muted);
      font-size: 11px;
      padding: 0 2px;
      line-height: 1;
      display: none;
    }
    .date-clear-btn.visible { display: block; }
    .date-clear-btn:hover { color: var(--text-primary); }

    /* ── TOOLTIP KPI ──────────────────────────────────────── */
    #kpi-tooltip {
      position: fixed;
      z-index: 9999;
      pointer-events: none;
      background: var(--surface-2);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 10px 14px;
      min-width: 215px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.20);
      display: none;
    }
    html.dark #kpi-tooltip { box-shadow: 0 8px 24px rgba(0,0,0,0.55); }
    .tt-title {
      font-size: 9px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-bottom: 7px;
      padding-bottom: 5px;
      border-bottom: 1px solid var(--border);
    }
    .tt-row {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 3px 0;
    }
    .tt-period {
      color: var(--text-secondary);
      font-size: 10px;
      min-width: 46px;
    }
    .tt-val {
      font-family: 'Fira Code', monospace;
      font-size: 12px;
      font-weight: 700;
      color: var(--text-primary);
      flex: 1;
      text-align: right;
    }
    .tt-compare .tt-period { color: var(--text-muted); font-size: 9.5px; }
    .tt-compare .tt-val    { color: var(--text-secondary); font-size: 11px; }
    .tt-arrow {
      font-size: 13px;
      font-weight: 800;
      min-width: 56px;
      text-align: right;
      white-space: nowrap;
    }
    .tt-arrow.good { color: var(--green); }
    .tt-arrow.bad  { color: var(--danger); }
    .tt-sep {
      border-top: 1px solid var(--border);
      margin: 5px 0 3px;
    }
    .tt-lm .tt-period { color: var(--text-muted); font-size: 9.5px; }
    .tt-lm .tt-val    { color: var(--text-secondary); font-size: 11px; }
    .tt-lm-tag {
      font-size: 8.5px;
      color: var(--text-muted);
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 3px;
      padding: 1px 4px;
      white-space: nowrap;
    }
    [data-metric] { cursor: default; }

    /* ── TIMESTAMP ────────────────────────────────────────── */
    .last-updated {
      font-size: 10px;
      color: var(--text-muted);
      font-family: 'Fira Code', monospace;
      text-align: right;
      padding: 4px 0 8px;
    }
</style>

<!-- ── TOOLTIP KPI ─────────────────────────────────────── -->
<div id="kpi-tooltip" role="tooltip" aria-hidden="true">
  <div class="tt-title" id="tt-title"></div>
  <!-- Mês atual vs mesmo período ano passado (com seta) -->
  <div class="tt-row">
    <span class="tt-period" id="tt-ma-period"></span>
    <span class="tt-val" id="tt-ma-val"></span>
    <span class="tt-arrow" id="tt-arrow"></span>
  </div>
  <!-- Mesmo período ano passado (referência) -->
  <div class="tt-row tt-compare">
    <span class="tt-period" id="tt-py-period"></span>
    <span class="tt-val" id="tt-py-val"></span>
  </div>
  <!-- Último mês fechado -->
  <div class="tt-sep"></div>
  <div class="tt-row tt-lm" id="tt-lm-row">
    <span class="tt-period" id="tt-lm-period"></span>
    <span class="tt-lm-tag">últ. mês</span>
    <span class="tt-val" id="tt-lm-val"></span>
    <span class="tt-arrow" id="tt-lm-arrow"></span>
  </div>
</div>

<!-- ── OVERLAY DE CARREGAMENTO ──────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>

<!-- ── HEADER ──────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo" aria-hidden="true">MR</div>
    <div>
      <div class="header-title">Executive View &mdash; Acme EUA</div>
      <div class="header-sub">Evento &middot; Digital &middot; Orgânico &middot; Indicação</div>
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
      <a class="tab-switch-btn active" href="<?= $this->site ?>executive<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Executive</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>executive/digital<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Digital &times; Evento</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>executive/especialistas<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Especialistas</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>executive/analitico<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Analítico</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>executive/serasa<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Serasa</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>executive/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
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

  <div class="filter-sep"></div>

  <div class="filter-pair">
    <span class="filter-label">Responsável</span>
    <div class="camp-wrap" id="resp-wrap">
      <div class="camp-tags" id="resp-tags"></div>
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="resp-input" class="camp-input" type="text" placeholder="Pesquisar responsável..." autocomplete="off" aria-label="Pesquisar responsável" aria-autocomplete="list" aria-controls="resp-dropdown">
        <button class="camp-clear" id="resp-clear" aria-label="Limpar responsável" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-dropdown" id="resp-dropdown" role="listbox"></div>
    </div>
  </div>

  <button class="btn-apply" id="btn-apply" onclick="applyFilters()">Aplicar</button>
  <button class="btn-clear" id="btn-clear" onclick="clearFilters()">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'executive' });</script>

<!-- ── ERRO ────────────────────────────────────────────────── -->
<div id="error-banner" role="alert"></div>

<!-- ── CONTEÚDO PRINCIPAL ──────────────────────────────────── -->
<main class="main" id="dashboard-main" aria-label="Dashboard executivo">

  <!-- PAINEL GLOBAL -->
  <div class="panel-global" id="panel-global">

    <!-- LINHA 1: KPIs do Funil Comercial em sequência -->
    <div class="kpi-row">
      <div class="kpi-chip rail-red" data-metric="total_leads">
        <div class="kpi-chip-label">Leads</div>
        <div class="kpi-chip-value" id="g-leads">—</div>
      </div>
      <div class="kpi-chip rail-blue" data-metric="leads_qualificados">
        <div class="kpi-chip-label">Qualificados</div>
        <div class="kpi-chip-value blue" id="g-qualif">—</div>
        <div class="kpi-chip-label" id="g-cv-qualif" style="text-transform:none;letter-spacing:0;font-weight:600">—</div>
      </div>
      <div class="kpi-chip rail-orange" data-metric="cadeira_vendida">
        <div class="kpi-chip-label">Cadeiras Vendidas</div>
        <div class="kpi-chip-value orange" id="g-cadeira">—</div>
        <div class="kpi-chip-label" id="g-cv-cadeira" style="text-transform:none;letter-spacing:0;font-weight:600">—</div>
      </div>
      <div class="kpi-chip rail-amber" data-metric="consultas_vendidas">
        <div class="kpi-chip-label">Consultas Vendidas</div>
        <div class="kpi-chip-value orange" id="g-consult-v">—</div>
        <div class="kpi-chip-label" id="g-cv-consult-v" style="text-transform:none;letter-spacing:0;font-weight:600">—</div>
      </div>
      <div class="kpi-chip rail-teal" data-metric="consultas_realizadas">
        <div class="kpi-chip-label">Consultas Realizadas</div>
        <div class="kpi-chip-value teal" id="g-consult-r">—</div>
        <div class="kpi-chip-label" id="g-cv-consult-r" style="text-transform:none;letter-spacing:0;font-weight:600">—</div>
      </div>
      <div class="kpi-chip rail-green" data-metric="combos_vendidos">
        <div class="kpi-chip-label">Combos Vendidos</div>
        <div class="kpi-chip-value green" id="g-combos">—</div>
        <div class="kpi-chip-label" id="g-cv-combos" style="text-transform:none;letter-spacing:0;font-weight:600">—</div>
      </div>
    </div>

    <!-- LINHA 2: Resumo Financeiro completo + Vistos (2 tabelas) -->
    <div class="panel-row-3">

      <div class="panel-section fin-resumo">
        <!-- 3 linhas de conteúdo: (1) Investimento/Faturamento/Lucro,
             (2 e 3) os 5 chips de custo — com o ROI no canto superior direito
             do header, ao lado do título. -->
        <div class="fin-head">
          <div class="section-title">Resumo Financeiro</div>
          <div class="fin-roi-box" id="g-roi-box" data-metric="roi" title="ROI — quantas vezes o faturamento superou o investimento">
            <span class="fin-roi-caption">ROI</span>
            <span class="fin-roi-value" id="g-roi">—</span>
          </div>
        </div>
        <div class="fin-strip">
          <div class="fin-cell" data-metric="investimento">
            <span class="fin-label">Investimento</span>
            <span class="fin-value invest" id="g-invest">—</span>
            <div class="fin-subtitle">
              <span class="fin-sub-item" data-metric="custo_mkt">
                <span class="fin-sublabel">Mkt</span><span class="fin-subvalue" id="g-invest-mkt">—</span>
              </span>
              <span class="fin-sub-item" data-metric="custo_fisico">
                <span class="fin-sublabel">Físico</span><span class="fin-subvalue" id="g-invest-fisico">—</span>
              </span>
            </div>
          </div>
          <div class="fin-cell" data-metric="faturamento_total">
            <span class="fin-label">Faturamento</span>
            <span class="fin-value fatur" id="g-fatur">—</span>
          </div>
          <div class="fin-cell" data-metric="lucro">
            <span class="fin-label">Lucro</span>
            <div class="lucro-badge" id="g-lucro">—</div>
          </div>
        </div>
        <div class="fin-kpi-grid">
          <div class="kpi-chip" data-metric="cpl">
            <div class="kpi-chip-label">CPL</div>
            <div class="kpi-chip-value amber" id="g-cpl">—</div>
          </div>
          <div class="kpi-chip" data-metric="ticket_medio">
            <div class="kpi-chip-label">Ticket Médio</div>
            <!-- verde, não âmbar: âmbar é a família de custo (CPL/CAC) e Ticket
                 Médio é receita, como Faturamento e Lucro. -->
            <div class="kpi-chip-value green" id="g-ticket">—</div>
          </div>
          <div class="kpi-chip" data-metric="cac_cadeira">
            <div class="kpi-chip-label">CAC Cadeira</div>
            <div class="kpi-chip-value" id="g-cac-cad">—</div>
          </div>
          <div class="kpi-chip" data-metric="cac_consulta">
            <div class="kpi-chip-label">CAC Consulta</div>
            <div class="kpi-chip-value" id="g-cac-cons">—</div>
          </div>
          <div class="kpi-chip" data-metric="cac_combos">
            <div class="kpi-chip-label">CAC Combo</div>
            <div class="kpi-chip-value" id="g-cac-comb">—</div>
          </div>
        </div>
      </div>

      <div class="panel-section vistos-tipo">
        <div class="section-title">Faturamento por Visto</div>
        <div class="mini-table-scroll">
          <table class="vistos-table" id="vistos-tipo-table">
            <thead><tr><th>Visto</th><th class="num">Vistos</th><th class="num">Faturamento</th></tr></thead>
            <tbody id="vistos-tipo-tbody"><tr><td colspan="3" class="vistos-empty">Carregando...</td></tr></tbody>
          </table>
        </div>
      </div>

      <div class="panel-section vistos-vendedor">
        <div class="section-title">Vistos por Vendedor</div>
        <div class="mini-table-scroll">
          <table class="vistos-table" id="vistos-vendedor-table">
            <thead><tr><th>Vendedor</th><th class="num">Vistos</th><th class="num">Ticket Médio</th></tr></thead>
            <tbody id="vistos-vendedor-tbody"><tr><td colspan="3" class="vistos-empty">Carregando...</td></tr></tbody>
          </table>
        </div>
      </div>

    </div>
  </div>

  <!-- GRADE DE SEGMENTOS -->
  <div class="segments-grid">

    <!-- EVENTO -->
    <article class="seg-card evento" id="seg-evento" aria-label="Segmento Evento">
      <div class="seg-header">
        <div class="seg-name">
          <div class="seg-name-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/>
            </svg>
          </div>
          Evento
        </div>
        <div class="seg-header-metrics">
          <div class="seg-header-kpi" data-metric="lucro">
            <div class="seg-header-kpi-label">Lucro</div>
            <div class="seg-header-kpi-value" id="e-lucro">—</div>
          </div>
          <div class="seg-header-kpi" data-metric="roi">
            <div class="seg-header-kpi-label">ROI</div>
            <div class="seg-header-kpi-value" id="e-roi">—</div>
            <div class="roi-bar"><span class="roi-bar-fill" id="e-roi-bar"></span></div>
          </div>
          <div class="seg-header-kpi" data-metric="combos_vendidos">
            <div class="seg-header-kpi-label">Combos</div>
            <div class="seg-header-kpi-value" id="e-combos">—</div>
          </div>
        </div>
      </div>
      <div class="seg-body">
        <div class="seg-kpi-row">
          <div class="seg-kpi" data-metric="investimento">
            <div class="seg-kpi-label">Invest.</div>
            <div class="seg-kpi-value red" id="e-invest">—</div>
          </div>
          <div class="seg-kpi" data-metric="faturamento_total">
            <div class="seg-kpi-label">Faturamento</div>
            <div class="seg-kpi-value green" id="e-fatur">—</div>
          </div>
        </div>
        <div class="seg-sub-title">Funil Comercial</div>
        <div class="seg-kpi-row">
          <div class="seg-kpi" data-metric="total_leads"><div class="seg-kpi-label">Leads</div><div class="seg-kpi-value" id="e-leads">—</div></div>
          <div class="seg-kpi" data-metric="leads_qualificados"><div class="seg-kpi-label">Qualif.</div><div class="seg-kpi-value blue" id="e-qualif">—</div></div>
          <div class="seg-kpi" data-metric="cadeira_vendida"><div class="seg-kpi-label">Cadeiras</div><div class="seg-kpi-value orange" id="e-cadeira">—</div></div>
        </div>
        <div class="seg-kpi-row">
          <div class="seg-kpi" data-metric="consultas_vendidas"><div class="seg-kpi-label">Cons. V.</div><div class="seg-kpi-value orange" id="e-consult-v">—</div></div>
          <div class="seg-kpi" data-metric="consultas_realizadas"><div class="seg-kpi-label">Cons. R.</div><div class="seg-kpi-value teal" id="e-consult-r">—</div></div>
        </div>
        <div class="seg-sub-title">CAC &amp; CPL</div>
        <div class="seg-kpi-row">
          <div class="seg-kpi" data-metric="cpl"><div class="seg-kpi-label">CPL</div><div class="seg-kpi-value muted" id="e-cpl">—</div></div>
          <div class="seg-kpi" data-metric="cac_cadeira"><div class="seg-kpi-label">CAC Cad.</div><div class="seg-kpi-value muted" id="e-cac-cad">—</div></div>
          <div class="seg-kpi" data-metric="cac_consulta"><div class="seg-kpi-label">CAC Cons.</div><div class="seg-kpi-value muted" id="e-cac-cons">—</div></div>
        </div>
        <div class="seg-kpi-row">
          <div class="seg-kpi" data-metric="cac_combos"><div class="seg-kpi-label">CAC Combo</div><div class="seg-kpi-value muted" id="e-cac-comb">—</div></div>
          <div class="seg-kpi" data-metric="ticket_medio"><div class="seg-kpi-label">Ticket</div><div class="seg-kpi-value amber" id="e-ticket">—</div></div>
        </div>
      </div>
    </article>

    <!-- DIGITAL -->
    <article class="seg-card digital" id="seg-digital" aria-label="Segmento Digital">
      <div class="seg-header">
        <div class="seg-name">
          <div class="seg-name-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>
            </svg>
          </div>
          Digital
        </div>
        <div class="seg-header-metrics">
          <div class="seg-header-kpi" data-metric="lucro">
            <div class="seg-header-kpi-label">Lucro</div>
            <div class="seg-header-kpi-value" id="d-lucro">—</div>
          </div>
          <div class="seg-header-kpi" data-metric="roi">
            <div class="seg-header-kpi-label">ROI</div>
            <div class="seg-header-kpi-value" id="d-roi">—</div>
            <div class="roi-bar"><span class="roi-bar-fill" id="d-roi-bar"></span></div>
          </div>
          <div class="seg-header-kpi" data-metric="combos_vendidos">
            <div class="seg-header-kpi-label">Combos</div>
            <div class="seg-header-kpi-value" id="d-combos">—</div>
          </div>
        </div>
      </div>
      <div class="seg-body">
        <div class="seg-kpi-row">
          <div class="seg-kpi" data-metric="investimento"><div class="seg-kpi-label">Invest.</div><div class="seg-kpi-value red" id="d-invest">—</div></div>
          <div class="seg-kpi" data-metric="faturamento_total"><div class="seg-kpi-label">Faturamento</div><div class="seg-kpi-value green" id="d-fatur">—</div></div>
        </div>
        <div class="seg-sub-title">Funil Comercial</div>
        <div class="seg-kpi-row">
          <div class="seg-kpi" data-metric="total_leads"><div class="seg-kpi-label">Leads</div><div class="seg-kpi-value" id="d-leads">—</div></div>
          <div class="seg-kpi" data-metric="leads_qualificados"><div class="seg-kpi-label">Qualif.</div><div class="seg-kpi-value blue" id="d-qualif">—</div></div>
          <div class="seg-kpi" data-metric="cadeira_vendida"><div class="seg-kpi-label">Cadeiras</div><div class="seg-kpi-value orange" id="d-cadeira">—</div></div>
        </div>
        <div class="seg-kpi-row">
          <div class="seg-kpi" data-metric="consultas_vendidas"><div class="seg-kpi-label">Cons. V.</div><div class="seg-kpi-value orange" id="d-consult-v">—</div></div>
          <div class="seg-kpi" data-metric="consultas_realizadas"><div class="seg-kpi-label">Cons. R.</div><div class="seg-kpi-value teal" id="d-consult-r">—</div></div>
        </div>
        <div class="seg-sub-title">CAC &amp; CPL</div>
        <div class="seg-kpi-row">
          <div class="seg-kpi" data-metric="cpl"><div class="seg-kpi-label">CPL</div><div class="seg-kpi-value muted" id="d-cpl">—</div></div>
          <div class="seg-kpi" data-metric="cac_cadeira"><div class="seg-kpi-label">CAC Cad.</div><div class="seg-kpi-value muted" id="d-cac-cad">—</div></div>
          <div class="seg-kpi" data-metric="cac_consulta"><div class="seg-kpi-label">CAC Cons.</div><div class="seg-kpi-value muted" id="d-cac-cons">—</div></div>
        </div>
        <div class="seg-kpi-row">
          <div class="seg-kpi" data-metric="cac_combos"><div class="seg-kpi-label">CAC Combo</div><div class="seg-kpi-value muted" id="d-cac-comb">—</div></div>
          <div class="seg-kpi" data-metric="ticket_medio"><div class="seg-kpi-label">Ticket</div><div class="seg-kpi-value amber" id="d-ticket">—</div></div>
        </div>
      </div>
    </article>

    <!-- ORGÂNICO -->
    <article class="seg-card organico" id="seg-organico" aria-label="Segmento Orgânico">
      <div class="seg-header">
        <div class="seg-name">
          <div class="seg-name-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22V12M12 12C12 7 7 2 2 2c0 5 5 10 10 10zM12 12c0-5 5-10 10-10-1 5-5 10-10 10z"/>
            </svg>
          </div>
          Orgânico
        </div>
        <div class="seg-header-metrics">
          <div class="seg-header-kpi" data-metric="lucro">
            <div class="seg-header-kpi-label">Lucro</div>
            <div class="seg-header-kpi-value" id="o-lucro">—</div>
          </div>
          <div class="seg-header-kpi muted" data-metric="roi" title="Sem investimento pago neste canal">
            <div class="seg-header-kpi-label">ROI</div>
            <div class="seg-header-kpi-value">—</div>
            <div class="roi-bar"></div>
          </div>
          <div class="seg-header-kpi" data-metric="combos_vendidos">
            <div class="seg-header-kpi-label">Combos</div>
            <div class="seg-header-kpi-value" id="o-combos">—</div>
          </div>
        </div>
      </div>
      <div class="seg-body">
        <div class="seg-kpi-row">
          <div class="seg-kpi" data-metric="lucro"><div class="seg-kpi-label">Lucro</div><div class="seg-kpi-value green" id="o-lucro-2">—</div></div>
          <div class="seg-kpi" data-metric="ticket_medio"><div class="seg-kpi-label">Ticket Médio</div><div class="seg-kpi-value amber" id="o-ticket">—</div></div>
        </div>
        <div class="seg-sub-title">Funil Comercial</div>
        <div class="seg-kpi-row">
          <div class="seg-kpi" data-metric="total_leads"><div class="seg-kpi-label">Leads</div><div class="seg-kpi-value" id="o-leads">—</div></div>
          <div class="seg-kpi" data-metric="leads_qualificados"><div class="seg-kpi-label">Qualif.</div><div class="seg-kpi-value blue" id="o-qualif">—</div></div>
        </div>
        <div class="seg-kpi-row">
          <div class="seg-kpi" data-metric="consultas_vendidas"><div class="seg-kpi-label">Cons. V.</div><div class="seg-kpi-value orange" id="o-consult-v">—</div></div>
          <div class="seg-kpi" data-metric="consultas_realizadas"><div class="seg-kpi-label">Cons. R.</div><div class="seg-kpi-value teal" id="o-consult-r">—</div></div>
          <div class="seg-kpi" data-metric="combos_vendidos"><div class="seg-kpi-label">Combos</div><div class="seg-kpi-value green" id="o-combos-2">—</div></div>
        </div>
        <div class="seg-sub-title">CAC &amp; CPL</div>
        <div class="seg-cac-na">Não aplicável — sem investimento pago neste canal</div>
      </div>
    </article>

    <!-- INDICAÇÃO -->
    <article class="seg-card indicacao" id="seg-indicacao" aria-label="Segmento Indicação">
      <div class="seg-header">
        <div class="seg-name">
          <div class="seg-name-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          Indicação
        </div>
        <div class="seg-header-metrics">
          <div class="seg-header-kpi" data-metric="lucro">
            <div class="seg-header-kpi-label">Lucro</div>
            <div class="seg-header-kpi-value" id="i-lucro">—</div>
          </div>
          <div class="seg-header-kpi muted" data-metric="roi" title="Sem investimento pago neste canal">
            <div class="seg-header-kpi-label">ROI</div>
            <div class="seg-header-kpi-value">—</div>
            <div class="roi-bar"></div>
          </div>
          <div class="seg-header-kpi" data-metric="combos_vendidos">
            <div class="seg-header-kpi-label">Combos</div>
            <div class="seg-header-kpi-value" id="i-combos">—</div>
          </div>
        </div>
      </div>
      <div class="seg-body">
        <div class="seg-kpi-row">
          <div class="seg-kpi" data-metric="lucro"><div class="seg-kpi-label">Lucro</div><div class="seg-kpi-value green" id="i-lucro-2">—</div></div>
          <div class="seg-kpi" data-metric="ticket_medio"><div class="seg-kpi-label">Ticket Médio</div><div class="seg-kpi-value amber" id="i-ticket">—</div></div>
        </div>
        <div class="seg-sub-title">Funil Comercial</div>
        <div class="seg-kpi-row">
          <div class="seg-kpi" data-metric="total_leads"><div class="seg-kpi-label">Leads</div><div class="seg-kpi-value" id="i-leads">—</div></div>
          <div class="seg-kpi" data-metric="leads_qualificados"><div class="seg-kpi-label">Qualif.</div><div class="seg-kpi-value blue" id="i-qualif">—</div></div>
        </div>
        <div class="seg-kpi-row">
          <div class="seg-kpi" data-metric="consultas_vendidas"><div class="seg-kpi-label">Cons. V.</div><div class="seg-kpi-value orange" id="i-consult-v">—</div></div>
          <div class="seg-kpi" data-metric="consultas_realizadas"><div class="seg-kpi-label">Cons. R.</div><div class="seg-kpi-value teal" id="i-consult-r">—</div></div>
          <div class="seg-kpi" data-metric="combos_vendidos"><div class="seg-kpi-label">Combos</div><div class="seg-kpi-value green" id="i-combos-2">—</div></div>
        </div>
        <div class="seg-sub-title">CAC &amp; CPL</div>
        <div class="seg-cac-na">Não aplicável — sem investimento pago neste canal</div>
      </div>
    </article>

  </div><!-- /segments-grid -->

  <div class="last-updated" id="last-updated" aria-live="polite"></div>

</main>

<script>
// ══════════════════════════════════════════════════════════════
//  Executive View — Dashboard JS
//  Consome: api/executive_view.php
// ══════════════════════════════════════════════════════════════

const API = '<?= $this->site ?>executive/dados';
const MONTHS  = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const YEARS = anosDesde(2025);

// ── Multi-select dropdown factory ─────────────────────────────
const yearDropdown  = createMultiDropdown('year-wrap',  'year-btn',  'year-list',
  YEARS,  l => l,       l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list',
  MONTHS, l => l,       (l, i) => i + 1);

const NOW_YEAR  = String(new Date().getFullYear());
const NOW_MONTH = new Date().getMonth() + 1;
yearDropdown.select([NOW_YEAR]);
monthDropdown.select([String(NOW_MONTH)]);


// ── Setar texto em elemento ──────────────────────────────────
function set(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value;
}

// ── Cor semântica (bom/mau) baseada no sinal real de lucro/ROI ──
// Não altera o texto exibido (fLucro/fROI continuam formatando como hoje) —
// só decide qual classe de destaque (positivo/alerta/negativo) aplicar.
function setSemantic(id, text, tier) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = text;
  el.classList.remove('positivo', 'alerta', 'negativo');
  if (tier) el.classList.add(tier);
}
function lucroTier(lucro) {
  return (Number(lucro) || 0) < 0 ? 'negativo' : null;
}
function roiTier(roi, investimento) {
  if ((Number(investimento) || 0) <= 0) return null; // sem investimento pago: ROI não se aplica
  const n = Number(roi) || 0;
  if (n >= 1) return 'positivo';
  if (n > 0)  return 'alerta';
  return 'negativo';
}
// Barra de progresso do ROI: preenchimento proporcional até 2.0x (a marca
// central do .roi-bar já indica visualmente o ponto de equilíbrio, 1.0x).
function setRoiBar(id, roi, tier) {
  const el = document.getElementById(id);
  if (!el) return;
  const pct = Math.max(0, Math.min(1, (Number(roi) || 0) / 2)) * 100;
  el.style.width = pct + '%';
  el.classList.remove('positivo', 'alerta', 'negativo');
  if (tier) el.classList.add(tier);
}

// ── Preencher Painel Global ──────────────────────────────────
function fillGlobal(g) {
  set('g-invest',        fBRL(g.investimento));
  set('g-invest-mkt',    fBRL(g.custo_mkt));
  set('g-invest-fisico', fBRL(g.custo_fisico));
  set('g-fatur',     fBRL(g.faturamento_total));
  setSemantic('g-lucro', fLucro(g.lucro), lucroTier(g.lucro));
  const roiT = roiTier(g.roi, g.investimento);
  setSemantic('g-roi',   fROI(g.roi),     roiT);
  const roiBox = document.getElementById('g-roi-box');
  if (roiBox) {
    roiBox.classList.remove('positivo', 'alerta', 'negativo');
    if (roiT) roiBox.classList.add(roiT);
  }
  // Funil Comercial — KPIs em sequência (% ao lado de cada valor continua
  // mostrando a conversão vs. etapa anterior, igual à barra que existia antes)
  set('g-leads', fNum(g.total_leads));

  set('g-qualif', fNum(g.leads_qualificados));
  set('g-cv-qualif', fPctRatio(g.leads_qualificados, g.total_leads) + ' dos leads');

  set('g-cadeira', fNum(g.cadeira_vendida));
  set('g-cv-cadeira', fPctRatio(g.cadeira_vendida, g.leads_qualificados) + ' dos qualificados');

  set('g-consult-v', fNum(g.consultas_vendidas));
  set('g-cv-consult-v', fPctRatio(g.consultas_vendidas, g.cadeira_vendida) + ' das cadeiras');

  set('g-consult-r', fNum(g.consultas_realizadas));
  set('g-cv-consult-r', fPctRatio(g.consultas_realizadas, g.consultas_vendidas) + ' das vendidas');

  set('g-combos', fNum(g.combos_vendidos));
  set('g-cv-combos', fPctRatio(g.combos_vendidos, g.consultas_realizadas) + ' das realizadas');

  set('g-cpl',       fBRL(g.cpl));
  set('g-ticket',    fBRL(g.ticket_medio));
  set('g-cac-cad',   fBRL(g.cac_cadeira));
  set('g-cac-cons',  fBRL(g.cac_consulta));
  set('g-cac-comb',  fBRL(g.cac_combos));
}

// ── Preencher card Vistos (ticket médio por visto / vistos por vendedor) ──
function escHtml(str) {
  return String(str ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function renderVistosTipo(rows) {
  const tbody = document.getElementById('vistos-tipo-tbody');
  if (!rows || !rows.length) { tbody.innerHTML = '<tr><td colspan="3" class="vistos-empty">Sem dados para o período</td></tr>'; return; }
  tbody.innerHTML = rows.map(r => `
    <tr>
      <td class="td-nome">${escHtml(r.tipo_visto)}</td>
      <td class="num">${fNum(r.combos)}</td>
      <td class="num td-green">${fBRL(r.faturamento)}</td>
    </tr>`).join('');
}

function renderVistosVendedor(rows) {
  const tbody = document.getElementById('vistos-vendedor-tbody');
  if (!rows || !rows.length) { tbody.innerHTML = '<tr><td colspan="3" class="vistos-empty">Sem dados para o período</td></tr>'; return; }
  tbody.innerHTML = rows.map(r => `
    <tr>
      <td class="td-nome">${escHtml(r.vendedor)}</td>
      <td class="num">${fNum(r.combos)}</td>
      <td class="num td-amber">${fBRL(r.ticket_medio)}</td>
    </tr>`).join('');
}

// ── Preencher Segmento Evento ────────────────────────────────
function fillEvento(e) {
  setSemantic('e-lucro', fLucro(e.lucro), lucroTier(e.lucro));
  setSemantic('e-roi',   fROI(e.roi),     roiTier(e.roi, e.investimento));
  setRoiBar('e-roi-bar', e.roi, roiTier(e.roi, e.investimento));
  set('e-combos',    fNum(e.combos_vendidos));
  set('e-invest',    fBRL(e.investimento));
  set('e-fatur',     fBRL(e.faturamento_total));
  set('e-leads',     fNum(e.total_leads));
  set('e-qualif',    fNum(e.leads_qualificados));
  set('e-cadeira',   fNum(e.cadeira_vendida));
  set('e-consult-v', fNum(e.consultas_vendidas));
  set('e-consult-r', fNum(e.consultas_realizadas));
  set('e-cpl',       fBRL(e.cpl));
  set('e-cac-cad',   fBRL(e.cac_cadeira));
  set('e-cac-cons',  fBRL(e.cac_consulta));
  set('e-cac-comb',  fBRL(e.cac_combos));
  set('e-ticket',    fBRL(e.ticket_medio));
}

// ── Preencher Segmento Digital ───────────────────────────────
function fillDigital(d) {
  setSemantic('d-lucro', fLucro(d.lucro), lucroTier(d.lucro));
  setSemantic('d-roi',   fROI(d.roi),     roiTier(d.roi, d.investimento));
  setRoiBar('d-roi-bar', d.roi, roiTier(d.roi, d.investimento));
  set('d-combos',    fNum(d.combos_vendidos));
  set('d-invest',    fBRL(d.investimento));
  set('d-fatur',     fBRL(d.faturamento_total));
  set('d-leads',     fNum(d.total_leads));
  set('d-qualif',    fNum(d.leads_qualificados));
  set('d-cadeira',   fNum(d.cadeira_vendida));
  set('d-consult-v', fNum(d.consultas_vendidas));
  set('d-consult-r', fNum(d.consultas_realizadas));
  set('d-cpl',       fBRL(d.cpl));
  set('d-cac-cad',   fBRL(d.cac_cadeira));
  set('d-cac-cons',  fBRL(d.cac_consulta));
  set('d-cac-comb',  fBRL(d.cac_combos));
  set('d-ticket',    fBRL(d.ticket_medio));
}

// ── Preencher Segmento Orgânico ──────────────────────────────
function fillOrganico(o) {
  setSemantic('o-lucro', fLucro(o.lucro), lucroTier(o.lucro));
  set('o-combos',    fNum(o.combos_vendidos));
  set('o-lucro-2',   fLucro(o.lucro));
  set('o-ticket',    fBRL(o.ticket_medio));
  set('o-leads',     fNum(o.total_leads));
  set('o-qualif',    fNum(o.leads_qualificados));
  set('o-consult-v', fNum(o.consultas_vendidas));
  set('o-consult-r', fNum(o.consultas_realizadas));
  set('o-combos-2',  fNum(o.combos_vendidos));
}

// ── Preencher Segmento Indicação ─────────────────────────────
function fillIndicacao(i) {
  setSemantic('i-lucro', fLucro(i.lucro), (Number(i.lucro) || 0) < 0 ? 'negativo' : 'positivo');
  set('i-combos',    fNum(i.combos_vendidos));
  set('i-lucro-2',   fLucro(i.lucro));
  set('i-ticket',    fBRL(i.ticket_medio));
  set('i-leads',     fNum(i.total_leads));
  set('i-qualif',    fNum(i.leads_qualificados));
  set('i-consult-v', fNum(i.consultas_vendidas));
  set('i-consult-r', fNum(i.consultas_realizadas));
  set('i-combos-2',  fNum(i.combos_vendidos));
}

// ── Badge do período selecionado ──────────────────────────────
function updateBadge(year, months, campanha, form, responsavel) {
  const badge = document.getElementById('filter-label-badge');
  if (!year && !months && !campanha && !form && !responsavel) {
    badge.textContent = 'TODOS OS PERÍODOS';
    return;
  }
  const parts = [];
  if (responsavel) parts.push('Resp: ' + responsavel);
  if (form)        parts.push('Form: ' + form);
  if (campanha)    parts.push(campanha);
  if (year)        parts.push(year);
  if (months) {
    const nums   = months.split(',').map(Number);
    const labels = nums.map(n => MONTHS[n - 1]);
    parts.push(labels.join(', '));
  }
  badge.textContent = parts.join(' · ').toUpperCase();
}

// ── Carregar dados da API ─────────────────────────────────────
// Memo em memória por querystring — evita round-trip de rede para a mesma
// combinação de filtro dentro da sessão (ex.: reaplicar o mesmo filtro, ou
// trocar de aba e voltar). TTL espelha o CACHE_TTL_DADOS do servidor (10min);
// o cache do servidor já cobre o mesmo caso entre abas/usuários diferentes,
// isso só evita a viagem de rede quando o próprio navegador já tem a resposta.
const dashboardCache = new Map(); // url -> { data, ts }
const DASHBOARD_CACHE_TTL_MS = 10 * 60 * 1000;

function renderDashboardData(data) {
  fillGlobal(data.global);
  fillEvento(data.evento);
  fillDigital(data.digital);
  fillOrganico(data.organico);
  fillIndicacao(data.indicacao);
  renderVistosTipo(data.vistos?.por_tipo);
  renderVistosVendedor(data.vistos?.por_vendedor);

  comparisonData = {
    mes_atual:        data.mes_atual,
    ano_passado:      data.ano_passado,
    ultimo_mes:       data.ultimo_mes,
    has_month_filter: data.has_month_filter,
    comparisons:      data.comparisons || null,
  };

  // Mostrar período efetivo nos campos de data (só se o usuário não definiu manualmente)
  if (data.effective_start && !getDateInput('data-inicio')) {
    setEffectiveDate('data-inicio', data.effective_start);
  }
  if (data.effective_end && !getDateInput('data-fim')) {
    setEffectiveDate('data-fim', data.effective_end);
  }

  const ts = data.meta?.generated
    ? new Date(data.meta.generated.replace(' ', 'T')).toLocaleString('pt-BR')
    : new Date().toLocaleString('pt-BR');
  document.getElementById('last-updated').textContent = 'Atualizado em: ' + ts;
}

async function loadDashboard(year, months, campanha, form, responsavel, dataInicio, dataFim) {
  const overlay = document.getElementById('loading-overlay');
  const errBan  = document.getElementById('error-banner');

  let url = API;
  const params = new URLSearchParams();
  if (year)        params.set('year', year);
  if (months)      params.set('months', months);
  if (campanha)    params.set('campanha', campanha);
  if (form)        params.set('form', form);
  if (responsavel) params.set('responsavel', responsavel);
  if (dataInicio)  params.set('data_inicio', dataInicio);
  if (dataFim)     params.set('data_fim', dataFim);
  // Envia a data de hoje do cliente para o backend calcular períodos proporcionais
  const todayStr = new Date().toISOString().slice(0, 10);
  params.set('today', todayStr);
  if ([...params].length) url += '?' + params.toString();

  const cached = dashboardCache.get(url);
  if (cached && (Date.now() - cached.ts) < DASHBOARD_CACHE_TTL_MS) {
    errBan.style.display = 'none';
    renderDashboardData(cached.data);
    return;
  }

  overlay.classList.remove('hidden');
  document.body.classList.add('loading');
  errBan.style.display = 'none';

  try {
    const res  = await fetch(url, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const data = await res.json();
    if (data.error) throw new Error(data.error);

    dashboardCache.set(url, { data, ts: Date.now() });
    renderDashboardData(data);

  } catch (err) {
    errBan.textContent  = '⚠️ Erro ao carregar dados: ' + err.message;
    errBan.style.display = 'block';
    console.error(err);
  } finally {
    overlay.classList.add('hidden');
    document.body.classList.remove('loading');
  }
}

// ── Tooltip de KPIs ──────────────────────────────────────────
const METRIC_META = {
  investimento:         { label: 'Investimento',     fmt: 'brl', better: 'higher' },
  faturamento_total:    { label: 'Faturamento',      fmt: 'brl', better: 'higher' },
  lucro:                { label: 'Lucro',            fmt: 'brl', better: 'higher' },
  roi:                  { label: 'ROI',              fmt: 'roi', better: 'higher' },
  total_leads:          { label: 'Leads',            fmt: 'num', better: 'higher' },
  leads_qualificados:   { label: 'Qualificados',     fmt: 'num', better: 'higher' },
  cadeira_vendida:      { label: 'Cadeiras',         fmt: 'num', better: 'higher' },
  consultas_vendidas:   { label: 'Cons. Vendidas',   fmt: 'num', better: 'higher' },
  consultas_realizadas: { label: 'Cons. Realizadas', fmt: 'num', better: 'higher' },
  combos_vendidos:      { label: 'Combos',           fmt: 'num', better: 'higher' },
  cpl:                  { label: 'CPL',              fmt: 'brl', better: 'lower'  },
  ticket_medio:         { label: 'Ticket Médio',     fmt: 'brl', better: 'higher' },
  cac_cadeira:          { label: 'CAC Cadeira',      fmt: 'brl', better: 'lower'  },
  cac_consulta:         { label: 'CAC Consulta',     fmt: 'brl', better: 'lower'  },
  cac_combos:           { label: 'CAC Combo',        fmt: 'brl', better: 'lower'  },
};

let comparisonData = null;

function fmtTip(val, fmt) {
  if (val == null || val === '' || isNaN(Number(val))) return '—';
  const n = Number(val);
  if (fmt === 'brl') return '$ ' + n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
  if (fmt === 'roi') return n.toFixed(2) + 'x';
  return n.toLocaleString('pt-BR');
}

function positionTooltip(e) {
  const tip = document.getElementById('kpi-tooltip');
  const margin = 14;
  const tw = tip.offsetWidth  || 220;
  const th = tip.offsetHeight || 90;
  let x = e.clientX + margin;
  let y = e.clientY - th - margin;
  if (x + tw > window.innerWidth  - margin) x = e.clientX - tw - margin;
  if (y < margin)                            y = e.clientY + margin;
  tip.style.left = x + 'px';
  tip.style.top  = y + 'px';
}

function setArrow(el, current, base, meta) {
  el.textContent = '';
  el.className = 'tt-arrow';
  const cur = Number(current), bas = Number(base);
  if (current == null || base == null || isNaN(cur) || isNaN(bas) || bas === 0) return;
  // Threshold: base muito pequena torna o % sem sentido
  const minBase = meta.fmt === 'brl' ? 500 : 5;
  if (Math.abs(bas) < minBase) return;
  const diff = cur - bas;
  const pct  = ((Math.abs(diff) / Math.abs(bas)) * 100).toFixed(1);
  // Cap: acima de 999% não exibe número, só a seta
  const pctLabel = Math.abs((diff / bas) * 100) > 999 ? '' : ' ' + pct + '%';
  const isUp = diff >= 0;
  const good = meta.better === 'higher' ? isUp : !isUp;
  el.textContent = (isUp ? '↑' : '↓') + pctLabel;
  el.classList.add(good ? 'good' : 'bad');
}

function showKpiTooltip(el, e) {
  if (!comparisonData) return;
  const metric = el.dataset.metric;
  const meta   = METRIC_META[metric];
  if (!meta) return;

  // Detect which segment card this KPI belongs to
  const segCard = el.closest('.seg-card');
  const segClass = segCard ? ['evento','digital','organico','indicacao'].find(c => segCard.classList.contains(c)) : null;
  const segData  = segClass && comparisonData.comparisons?.[segClass]
    ? comparisonData.comparisons[segClass]
    : comparisonData;

  const ma  = segData.mes_atual;
  const py  = segData.ano_passado;
  const lm  = segData.ultimo_mes;
  const hasMonth = comparisonData.has_month_filter;
  const maV = ma?.[metric];
  const pyV = py?.[metric];
  const lmV = lm?.[metric];

  document.getElementById('tt-title').textContent = meta.label;

  // Linha 1: período atual + seta vs ano passado
  document.getElementById('tt-ma-period').textContent = ma?.periodo ?? '—';
  document.getElementById('tt-ma-val').textContent    = fmtTip(maV, meta.fmt);

  const ttArrow = document.getElementById('tt-arrow');
  ttArrow.className   = 'tt-arrow';
  ttArrow.textContent = '';
  setArrow(ttArrow, maV, pyV, meta);

  // Linha 2: mesmo período ano passado (referência)
  document.getElementById('tt-py-period').textContent = py?.periodo ?? '—';
  document.getElementById('tt-py-val').textContent    = fmtTip(pyV, meta.fmt);

  // Linha 3: mês anterior (só aparece quando há filtro de mês)
  const lmRow = document.getElementById('tt-lm-row');
  if (lmRow) lmRow.style.display = hasMonth ? '' : 'none';
  if (hasMonth && lm) {
    document.getElementById('tt-lm-period').textContent = lm?.periodo ?? '—';
    document.getElementById('tt-lm-val').textContent    = fmtTip(lmV, meta.fmt);
    const ttLmArrow = document.getElementById('tt-lm-arrow');
    ttLmArrow.className   = 'tt-arrow';
    ttLmArrow.textContent = '';
    setArrow(ttLmArrow, maV, lmV, meta);
  }

  const tip = document.getElementById('kpi-tooltip');
  tip.style.display = 'block';
  positionTooltip(e);
}

function hideKpiTooltip() {
  document.getElementById('kpi-tooltip').style.display = 'none';
}

// Delegação em vez de um listener por elemento: mouseenter/mouseleave não
// borbulham, e há [data-metric] aninhados (.fin-cell[investimento] contém dois
// .fin-sub-item[data-metric]). Voltando do filho ao pai disparava o mouseleave
// do filho e NENHUM mouseenter no pai — o tooltip sumia até sair da célula.
// mouseover borbulha, então closest() sempre re-resolve no [data-metric] mais
// interno sob o cursor, que é a resposta correta, e se recupera na volta.
function initTooltips() {
  document.addEventListener('mouseover', e => {
    const el = e.target.closest('[data-metric]');
    if (!el) { hideKpiTooltip(); return; }
    showKpiTooltip(el, e);
  });
  document.addEventListener('mousemove', e => {
    if (document.getElementById('kpi-tooltip').style.display === 'block') positionTooltip(e);
  });
  document.addEventListener('mouseleave', hideKpiTooltip);
}

// ── Helpers de date input ─────────────────────────────────────
function clearEffectiveDates() {
  ['data-inicio', 'data-fim'].forEach(id => {
    const el   = document.getElementById(id);
    const wrap = el?.closest('.date-input-wrap');
    if (!el || !wrap) return;
    el.value = '';
    wrap.classList.remove('is-effective');
  });
}

// ── Aplicar filtros ───────────────────────────────────────────
function applyFilters() {
  const year        = yearDropdown.getValue();
  const months      = monthDropdown.getValue();
  const campanha    = campDropdown.getValue();
  const form        = formDropdown.getValue();
  const responsavel = respDropdown.getValue();
  // Só envia datas digitadas manualmente — o servidor computa as efetivas
  const dataInicio = getDateInput('data-inicio');
  const dataFim    = getDateInput('data-fim');
  // Limpa datas efetivas antes da chamada para que o servidor recompute
  if (!dataInicio) clearEffectiveDates();
  updateBadge(year, months, campanha, form, responsavel);
  loadDashboard(year, months, campanha, form, responsavel, dataInicio, dataFim);
}

// ── Limpar filtros ────────────────────────────────────────────
function clearFilters() {
  yearDropdown.reset(true);
  monthDropdown.reset(true);
  campDropdown.reset();
  formDropdown.reset();
  respDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim',    'data-fim-clear');
  updateBadge(null, null, null, null, null);
  loadDashboard(null, null, null, null, null, null, null);
}

// ── Instâncias dos dropdowns ──────────────────────────────────
const formDropdown = createSearchDropdown(
  'form-wrap', 'form-input', 'form-dropdown', 'form-clear', 'form-tags',
  'Nenhum form encontrado'
);
const respDropdown = createSearchDropdown(
  'resp-wrap', 'resp-input', 'resp-dropdown', 'resp-clear', 'resp-tags',
  'Nenhum responsável encontrado'
);
const campDropdown = createSearchDropdown(
  'camp-wrap', 'camp-input', 'camp-dropdown', 'camp-clear', 'camp-tags',
  'Nenhuma campanha encontrada'
);

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

// ── Inicializar ───────────────────────────────────────────────
initTooltips();

// Botões de limpar e visibilidade dos date inputs
['data-inicio', 'data-fim'].forEach(id => {
  const input   = document.getElementById(id);
  const clearId = id + '-clear';
  const clearBtn = document.getElementById(clearId);
  // Ao focar um campo em modo "exibição", converte para filtro real
  input?.addEventListener('focus', () => {
    const wrap = input.closest('.date-input-wrap');
    if (wrap?.classList.contains('is-effective')) {
      wrap.classList.remove('is-effective');
      input.value = '';  // limpa para o usuário digitar do zero
    }
  });
  input?.addEventListener('change', () => {
    clearBtn?.classList.toggle('visible', !!input.value);
  });
  clearBtn?.addEventListener('click', () => {
    clearDateInput(id, clearId);
  });
});

loadFiltros();
applyFilters();
</script>
</div>
