<div class="dash-npseventos">

<style>
/* ══════════════════════════════════════════════════════════════
   Dashboard NPS Eventos — porte da medida DAX "Html_NPS".
   Validado antes em npseventos-mock.html (apagado após aprovação).
   ══════════════════════════════════════════════════════════════ */

.dash-npseventos {
  /* Paleta herdada literalmente da medida DAX de origem (mesmo --ev usado
     pela família de dashboards Comercial — mesmo relatório Power BI). */
  --ev:     #f54a00;  --ev-glow: rgba(245,74,0,.09);
  --nps-ex: #6366f1;  --nps-ex-glow: rgba(99,102,241,.12);
}
html.dark .dash-npseventos { --nps-ex: #818cf8; }

/* KPI chips deste dashboard: fundo branco/card em vez do tint colorido de
   fundo (--*-glow) — só a borda esquerda e o valor ficam coloridos. Mesmo
   padrão de override escopado já usado em dash-rafaeltorres para .kpi-chip. */
.dash-npseventos .kpi-chip.consulta,
.dash-npseventos .kpi-chip.fecho,
.dash-npseventos .kpi-chip.aquisicao,
.dash-npseventos .kpi-chip.sinal {
  background: var(--card);
}

/* ── Círculo de NPS ─────────────────────────────────────────── */
.dash-npseventos .nps-wrap { display: flex; align-items: center; gap: 16px; }
.dash-npseventos .nps-score {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  width: 76px; height: 76px; border-radius: 50%; border: 4px solid var(--border); color: var(--muted);
  flex-shrink: 0;
}
.dash-npseventos .nps-score.ex   { border-color: var(--nps-ex); color: var(--nps-ex); }
.dash-npseventos .nps-score.gr   { border-color: var(--green);  color: var(--green); }
.dash-npseventos .nps-score.warn { border-color: var(--amber);  color: var(--amber); }
.dash-npseventos .nps-score.rd   { border-color: var(--danger); color: var(--danger); }
.dash-npseventos .nps-num { font-size: 22px; font-weight: 800; line-height: 1; font-family: var(--font-mono); }
.dash-npseventos .nps-lbl { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--muted); margin-top: 2px; }
.dash-npseventos .nps-info { display: flex; flex-direction: column; gap: 6px; flex: 1; }
.dash-npseventos .nps-desc { font-size: 12.5px; color: var(--text); line-height: 1.45; }
.dash-npseventos .nps-desc b { font-weight: 700; }
.dash-npseventos .nps-sub  { font-size: 11px; color: var(--muted); }
.dash-npseventos .nps-sub b { color: var(--text); font-weight: 700; }

/* ── Barras de avaliação 0–10 ──────────────────────────────────── */
.dash-npseventos .ar { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 11px; }
.dash-npseventos .ar:last-child { margin-bottom: 0; }
.dash-npseventos .al { color: var(--muted); width: 140px; flex-shrink: 0; font-size: 11px; }
.dash-npseventos .al.bold { color: var(--text); font-weight: 700; }
.dash-npseventos .aw { flex: 1; height: 14px; background: var(--card2); border-radius: 4px; overflow: hidden; border: 1px solid var(--border); }
.dash-npseventos .af { height: 100%; border-radius: 4px; transition: width .5s ease; }
.dash-npseventos .af.ev { background: linear-gradient(90deg, var(--ev-glow), var(--ev)); }
.dash-npseventos .af.gr { background: linear-gradient(90deg, var(--green-glow), var(--green)); }
.dash-npseventos .af.bl { background: linear-gradient(90deg, var(--consulta-glow), var(--consulta)); }
.dash-npseventos .av { font-weight: 700; width: 34px; text-align: right; flex-shrink: 0; color: var(--text); font-family: var(--font-mono); }
.dash-npseventos .as { font-size: 9px; color: var(--muted); width: 18px; text-align: right; flex-shrink: 0; }
.dash-npseventos .sep { border: none; border-top: 1px dashed var(--border); margin: 10px 0; }

/* ── Ranking (top motivos/fatores) — reuso do componente .cad-desc-* já
   existente no tema (criado em comercial/cadeiras.php), só escopado aqui. */
.dash-npseventos .cad-desc { display: flex; flex-direction: column; gap: 8px; }
.dash-npseventos .cad-desc-row {
  display: grid; grid-template-columns: minmax(0, 150px) minmax(0, 1fr) 30px;
  align-items: center; gap: 10px;
}
.dash-npseventos .cad-desc-label {
  font-size: 11.5px; color: var(--text);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.dash-npseventos .cad-desc-track { height: 10px; border-radius: 3px; background: var(--card2); overflow: hidden; border: 1px solid var(--border); }
.dash-npseventos .cad-desc-fill  { height: 100%; border-radius: 3px; min-width: 2px; background: var(--consulta); transition: width .5s ease; }
.dash-npseventos .cad-desc-val   { font-family: var(--font-mono); font-size: 12px; font-weight: 700; text-align: right; color: var(--text); font-variant-numeric: tabular-nums; }
/* Top item: verde em ranking positivo (fatores de fechamento), vermelho em negativo (motivos de não fechamento). */
.dash-npseventos .cad-desc-row.top.pos .cad-desc-fill  { background: var(--green); }
.dash-npseventos .cad-desc-row.top.pos .cad-desc-label { color: var(--green); font-weight: 600; }
.dash-npseventos .cad-desc-row.top.neg .cad-desc-fill  { background: var(--danger); }
.dash-npseventos .cad-desc-row.top.neg .cad-desc-label { color: var(--danger); font-weight: 600; }
.dash-npseventos .cad-vazio { padding: 18px 0; text-align: center; color: var(--muted); font-size: 12px; }

/* ── Interesse pós-evento (pills) ──────────────────────────────── */
.dash-npseventos .ip {
  display: flex; align-items: center; justify-content: space-between;
  padding: 8px 11px; border-radius: 8px; background: var(--card2); border: 1px solid var(--border);
  margin-bottom: 7px; font-size: 11.5px;
}
.dash-npseventos .ip:last-child { margin-bottom: 0; }
.dash-npseventos .il { color: var(--text); }
.dash-npseventos .iv { font-weight: 700; color: var(--consulta); font-family: var(--font-mono); }

/* ── Barra de chance de imigrar (gradiente) ────────────────────── */
.dash-npseventos .chance-bar {
  height: 18px; border-radius: 6px;
  background: linear-gradient(90deg, var(--danger), var(--amber), var(--green));
  position: relative; margin: 10px 0 5px;
}
.dash-npseventos .chance-dot {
  width: 16px; height: 16px; border-radius: 50%;
  background: var(--text); border: 2px solid var(--card);
  position: absolute; top: 1px; transform: translateX(-50%);
  transition: left .5s ease;
}
.dash-npseventos .chance-lbl { display: flex; justify-content: space-between; font-size: 9.5px; color: var(--muted); }
.dash-npseventos .chance-val { text-align: center; font-size: 14px; font-weight: 700; color: var(--consulta); margin-top: 8px; font-family: var(--font-mono); }

.dash-npseventos .subsection-title {
  font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px;
  color: var(--muted); margin-bottom: 8px;
}
.dash-npseventos .subsection-title:not(:first-child) { margin-top: 12px; }

.dash-npseventos .g2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
.dash-npseventos .g3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
@media (max-width: 900px) {
  .dash-npseventos .g2, .dash-npseventos .g3 { grid-template-columns: 1fr; }
}

.dash-npseventos .bi-card-header .dot {
  width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; margin-right: 6px; display: inline-block;
}
.dash-npseventos .dot.gr { background: var(--green); }
.dash-npseventos .dot.ev { background: var(--ev); }
.dash-npseventos .dot.bl { background: var(--consulta); }

.dash-npseventos .avisos-indisponivel {
  display: none; background: var(--amber-glow); border: 1px solid var(--amber); color: var(--amber);
  border-radius: var(--r-sm); padding: 8px 12px; font-size: 12px; margin-bottom: 12px;
}
.dash-npseventos .avisos-indisponivel.visible { display: block; }
</style>

<!-- ── LOADING ─────────────────────────────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>

<!-- ── HEADER ─────────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo">⭐</div>
    <div>
      <div class="header-title">NPS &amp; Satisfação — Eventos OTR</div>
      <div class="header-sub">Pesquisa pós-evento · Fecharam vs Não Fecharam consulta</div>
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
    <div class="header-badge" id="header-badge">— RESPOSTAS</div>
    <a class="btn-tab-switch" href="<?= $this->site ?>npseventos/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário &rarr;</a>
  </div>
</header>

<!-- ── FILTROS ─────────────────────────────────────────────────── -->
<section class="filters is-collapsible" aria-label="Filtros do painel">
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
      <span class="filter-label">Cidade</span>
      <div class="camp-wrap" id="cidade-wrap">
        <div class="camp-input-row">
          <span class="camp-icon">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
          </span>
          <input id="cidade-input" class="camp-input" type="text" placeholder="Pesquisar cidade..." autocomplete="off">
          <button class="camp-clear" id="cidade-clear" tabindex="-1">&#x2715;</button>
        </div>
        <div class="camp-tags" id="cidade-tags"></div>
        <div class="camp-dropdown" id="cidade-dropdown"></div>
      </div>
    </div>

    <button class="btn-apply" onclick="applyFilters()">Aplicar</button>
    <button class="btn-clear" onclick="clearFilters()">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'npseventos' });</script>

<!-- ── ERRO ───────────────────────────────────────────────────── -->
<div id="error-banner" role="alert"></div>
<div id="aviso-indisponivel" class="avisos-indisponivel">⚠️ Uma das planilhas de origem está temporariamente indisponível — os números abaixo podem estar incompletos.</div>

<!-- ── CONTEÚDO PRINCIPAL ──────────────────────────────────────── -->
<main class="main" id="dashboard-main">

  <!-- KPI BAND -->
  <div class="kpi-row">
    <div class="kpi-chip consulta">
      <span class="kpi-chip-label">Total de Respostas</span>
      <span class="kpi-chip-val" id="k-total">—</span>
    </div>
    <div class="kpi-chip fecho">
      <span class="kpi-chip-label">Fecharam Consulta</span>
      <span class="kpi-chip-val" id="k-fechou">—</span>
    </div>
    <div class="kpi-chip aquisicao">
      <span class="kpi-chip-label">Não Fecharam</span>
      <span class="kpi-chip-val" id="k-naofechou">—</span>
    </div>
    <div class="kpi-chip sinal">
      <span class="kpi-chip-label">Chance Média Imigrar</span>
      <span class="kpi-chip-val" id="k-chance">—</span>
    </div>
  </div>

  <!-- NPS CARDS -->
  <div class="g2" style="margin-top:14px">
    <div class="bi-card">
      <div class="bi-card-header"><span class="dot gr"></span><span class="bi-card-title">NPS — Fecharam Consulta</span></div>
      <div class="bi-card-body">
        <div class="nps-wrap">
          <div class="nps-score" id="cv-score"><div class="nps-num" id="cv-num">—</div><div class="nps-lbl">/10</div></div>
          <div class="nps-info">
            <div class="nps-desc">Média da <b>Experiência Geral</b> avaliada pelos participantes que fecharam consulta.</div>
            <div class="nps-sub">Score de indicação (Acme-EUA): <b id="cv-indicacao">—</b></div>
          </div>
        </div>
      </div>
    </div>

    <div class="bi-card">
      <div class="bi-card-header"><span class="dot ev"></span><span class="bi-card-title">NPS — Não Fecharam</span></div>
      <div class="bi-card-body">
        <div class="nps-wrap">
          <div class="nps-score" id="nf-score"><div class="nps-num" id="nf-num">—</div><div class="nps-lbl">/10</div></div>
          <div class="nps-info">
            <div class="nps-desc">Média da <b>Experiência Geral</b> avaliada pelos participantes que não fecharam consulta.</div>
            <div class="nps-sub">Score de indicação (Evento OTR): <b id="nf-indicacao">—</b></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- AVALIAÇÕES -->
  <div class="g2">
    <div class="bi-card">
      <div class="bi-card-header"><span class="dot gr"></span><span class="bi-card-title">Avaliações do Evento — Fecharam</span></div>
      <div class="bi-card-body">
        <div class="ar"><div class="al">Local do Evento</div><div class="aw"><div class="af gr" id="cv-local-bar" style="width:0%"></div></div><div class="av" id="cv-local-val">—</div><div class="as">/10</div></div>
        <div class="ar"><div class="al">Coffee Break</div><div class="aw"><div class="af gr" id="cv-coffee-bar" style="width:0%"></div></div><div class="av" id="cv-coffee-val">—</div><div class="as">/10</div></div>
        <div class="ar"><div class="al">Palestrante</div><div class="aw"><div class="af gr" id="cv-palestrante-bar" style="width:0%"></div></div><div class="av" id="cv-palestrante-val">—</div><div class="as">/10</div></div>
        <div class="ar"><div class="al">Recepção</div><div class="aw"><div class="af gr" id="cv-recepcao-bar" style="width:0%"></div></div><div class="av" id="cv-recepcao-val">—</div><div class="as">/10</div></div>
        <div class="ar"><div class="al">Brindes</div><div class="aw"><div class="af gr" id="cv-brindes-bar" style="width:0%"></div></div><div class="av" id="cv-brindes-val">—</div><div class="as">/10</div></div>
        <div class="ar"><div class="al">Abordagem Comercial</div><div class="aw"><div class="af gr" id="cv-abordagem-bar" style="width:0%"></div></div><div class="av" id="cv-abordagem-val">—</div><div class="as">/10</div></div>
        <div class="ar"><div class="al">Atendimento Comercial</div><div class="aw"><div class="af gr" id="cv-atendimento-bar" style="width:0%"></div></div><div class="av" id="cv-atendimento-val">—</div><div class="as">/10</div></div>
        <hr class="sep">
        <div class="ar"><div class="al bold">Indicaria Acme-EUA</div><div class="aw"><div class="af bl" id="cv-indicacao-bar" style="width:0%"></div></div><div class="av" id="cv-indicacao-val">—</div><div class="as">/10</div></div>
      </div>
    </div>

    <div class="bi-card">
      <div class="bi-card-header"><span class="dot ev"></span><span class="bi-card-title">Avaliações do Evento — Não Fecharam</span></div>
      <div class="bi-card-body">
        <div class="ar"><div class="al">Local do Evento</div><div class="aw"><div class="af ev" id="nf-local-bar" style="width:0%"></div></div><div class="av" id="nf-local-val">—</div><div class="as">/10</div></div>
        <div class="ar"><div class="al">Coffee Break</div><div class="aw"><div class="af ev" id="nf-coffee-bar" style="width:0%"></div></div><div class="av" id="nf-coffee-val">—</div><div class="as">/10</div></div>
        <div class="ar"><div class="al">Palestrante</div><div class="aw"><div class="af ev" id="nf-palestrante-bar" style="width:0%"></div></div><div class="av" id="nf-palestrante-val">—</div><div class="as">/10</div></div>
        <div class="ar"><div class="al">Recepção</div><div class="aw"><div class="af ev" id="nf-recepcao-bar" style="width:0%"></div></div><div class="av" id="nf-recepcao-val">—</div><div class="as">/10</div></div>
        <div class="ar"><div class="al">Brindes</div><div class="aw"><div class="af ev" id="nf-brindes-bar" style="width:0%"></div></div><div class="av" id="nf-brindes-val">—</div><div class="as">/10</div></div>
        <div class="ar"><div class="al">Abordagem Comercial</div><div class="aw"><div class="af ev" id="nf-abordagem-bar" style="width:0%"></div></div><div class="av" id="nf-abordagem-val">—</div><div class="as">/10</div></div>
        <hr class="sep">
        <div class="ar"><div class="al bold">Indicaria o Evento OTR</div><div class="aw"><div class="af bl" id="nf-indicacao-bar" style="width:0%"></div></div><div class="av" id="nf-indicacao-val">—</div><div class="as">/10</div></div>
      </div>
    </div>
  </div>

  <!-- LINHA INFERIOR -->
  <div class="g3">
    <div class="bi-card">
      <div class="bi-card-header"><span class="dot gr"></span><span class="bi-card-title">Por que Fechou?</span></div>
      <div class="bi-card-body"><div class="cad-desc" id="ranking-fatores"></div></div>
    </div>

    <div class="bi-card">
      <div class="bi-card-header"><span class="dot ev"></span><span class="bi-card-title">Por que Não Fechou?</span></div>
      <div class="bi-card-body"><div class="cad-desc" id="ranking-motivos"></div></div>
    </div>

    <div class="bi-card">
      <div class="bi-card-header"><span class="dot bl"></span><span class="bi-card-title">Pós-Evento — Não Fecharam</span></div>
      <div class="bi-card-body">
        <div class="subsection-title">Interesse em morar nos EUA</div>
        <div id="interesse-list"></div>

        <div class="subsection-title">Chance de imigrar nos próximos 12 meses</div>
        <div class="chance-bar"><div class="chance-dot" id="chance-dot" style="left:0%"></div></div>
        <div class="chance-lbl"><span>0</span><span>5</span><span>10</span></div>
        <div class="chance-val" id="chance-val">—</div>
      </div>
    </div>
  </div>

</main>

<script>
// ══════════════════════════════════════════════════════════════
//  NPS Eventos — Dashboard JS
//  Consome: npseventos/dados (controller MRPHP, sem consulta ao MySQL —
//  fonte são duas planilhas Google Sheets, ver controle/npseventos_controle.php)
//
//  createMultiDropdown, createSearchDropdown, initFiltersToggle, fNum são
//  fornecidas por js/bi-dashboard.js (carregado no <head> pelo template).
// ══════════════════════════════════════════════════════════════

const API    = '<?= $this->site ?>npseventos/dados';
const MONTHS = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

const yearDropdown  = createMultiDropdown('year-wrap', 'year-btn', 'year-list', [], l => l, l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);
const cidadeDropdown = createSearchDropdown('cidade-wrap', 'cidade-input', 'cidade-dropdown', 'cidade-clear', 'cidade-tags', 'Nenhuma cidade encontrada');

function set(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }
function setWidth(id, v) { const el = document.getElementById(id); if (el) el.style.width = v; }

function escHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function fmt1(v) {
  return (v === null || v === undefined) ? '—' : Number(v).toFixed(1);
}

// ── NPS: círculo + sub-score de indicação ──────────────────────
function fillNps(prefixo, bloco) {
  const scoreEl = document.getElementById(prefixo + '-score');
  scoreEl.className = 'nps-score' + (bloco.classe ? ' ' + bloco.classe : '');
  set(prefixo + '-num', fmt1(bloco.expGeral));
  set(prefixo + '-indicacao', fmt1(bloco.indicacao) + '/10');
}

// ── Barras de avaliação 0–10 ────────────────────────────────────
function setBar(id, valor) {
  set(id + '-val', fmt1(valor));
  const pct = (valor === null || valor === undefined) ? 0 : Math.max(2, Number(valor) / 10 * 100);
  setWidth(id + '-bar', pct + '%');
}

function fillAvaliacoesComprou(b) {
  setBar('cv-local', b.local);
  setBar('cv-coffee', b.coffee);
  setBar('cv-palestrante', b.palestrante);
  setBar('cv-recepcao', b.recepcao);
  setBar('cv-brindes', b.brindes);
  setBar('cv-abordagem', b.abordagem);
  setBar('cv-atendimento', b.atendimento);
  setBar('cv-indicacao', b.indicacao);
}

function fillAvaliacoesNaoComprou(b) {
  setBar('nf-local', b.local);
  setBar('nf-coffee', b.coffee);
  setBar('nf-palestrante', b.palestrante);
  setBar('nf-recepcao', b.recepcao);
  setBar('nf-brindes', b.brindes);
  setBar('nf-abordagem', b.abordagem);
  setBar('nf-indicacao', b.indicacao);
}

// ── Rankings (top motivos/fatores) ─────────────────────────────
function renderRanking(id, items, polaridade) {
  const el = document.getElementById(id);
  if (!items || !items.length) {
    el.innerHTML = '<div class="cad-vazio">Sem dados no período</div>';
    return;
  }
  const max = Math.max(...items.map(i => i.valor));
  el.innerHTML = items.map((item, i) => `
    <div class="cad-desc-row${i === 0 ? ' top ' + polaridade : ''}">
      <span class="cad-desc-label" title="${escHtml(item.label)}">${escHtml(item.label)}</span>
      <div class="cad-desc-track"><div class="cad-desc-fill" style="width:${Math.max(2, Math.round(item.valor / max * 100))}%"></div></div>
      <span class="cad-desc-val">${fNum(item.valor)}</span>
    </div>
  `).join('');
}

function renderInteresse(items) {
  const el = document.getElementById('interesse-list');
  if (!items || !items.length) {
    el.innerHTML = '<div class="cad-vazio">Sem dados no período</div>';
    return;
  }
  el.innerHTML = items.map(item => `
    <div class="ip"><div class="il">${escHtml(item.label)}</div><div class="iv">${fNum(item.valor)}</div></div>
  `).join('');
}

function fPct(ratio) {
  return (Number(ratio) * 100).toLocaleString('pt-BR', { maximumFractionDigits: 0 }) + '%';
}

// ── Carregar dashboard ──────────────────────────────────────────
// Range explícito (data-inicio/data-fim) tem prioridade sobre Ano/Mês no
// backend — mesma regra de buildPeriodFilter() dos dashboards SQL. Cidade é
// sempre um filtro à parte, some sempre que preenchida.
async function loadDashboard() {
  const overlay = document.getElementById('loading-overlay');
  const errBan  = document.getElementById('error-banner');
  overlay.classList.remove('hidden');
  errBan.style.display = 'none';
  errBan.classList.remove('visible');

  const params = new URLSearchParams();
  const year       = yearDropdown.getValue();
  const months     = monthDropdown.getValue();
  const cidades    = cidadeDropdown.getValue();
  const dataInicio = getDateInput('data-inicio');
  const dataFim    = getDateInput('data-fim');
  if (year)       params.set('year', year);
  if (months)     params.set('months', months);
  if (cidades)    params.set('cidades', cidades);
  if (dataInicio) params.set('data_inicio', dataInicio);
  if (dataFim)    params.set('data_fim', dataFim);
  updateBadge(year, months, cidades);

  let url = API;
  if ([...params].length) url += '?' + params.toString();

  try {
    const res  = await fetch(url, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    set('header-badge', fNum(data.total) + ' RESPOSTAS');
    set('k-total', fNum(data.total));
    set('k-fechou', fNum(data.totalComprou) + (data.total ? ' (' + fPct(data.pctComprou) + ')' : ''));
    set('k-naofechou', fNum(data.totalNaoComprou) + (data.total ? ' (' + fPct(data.pctNaoComprou) + ')' : ''));
    set('k-chance', fmt1(data.chanceMediaImigrar) + '/10');

    fillNps('cv', data.comprou);
    fillNps('nf', data.naoComprou);
    fillAvaliacoesComprou(data.comprou);
    fillAvaliacoesNaoComprou(data.naoComprou);

    renderRanking('ranking-fatores', data.fatoresFechamento, 'pos');
    renderRanking('ranking-motivos', data.motivosNaoFechou, 'neg');
    renderInteresse(data.interessePosEvento);

    const chancePct = data.chanceMediaImigrar === null ? 0 : Number(data.chanceMediaImigrar) / 10 * 100;
    document.getElementById('chance-dot').style.left = chancePct + '%';
    set('chance-val', fmt1(data.chanceMediaImigrar) + '/10');

    if (!yearDropdown.getValue() && data.anosDisponiveis) {
      yearDropdown.load(data.anosDisponiveis);
    }
    if (data.cidadesDisponiveis) {
      cidadeDropdown.load(data.cidadesDisponiveis);
    }

    document.getElementById('aviso-indisponivel').classList.toggle('visible', !!data.indisponivel);

  } catch (err) {
    errBan.textContent = '⚠️ Erro ao carregar dados: ' + err.message;
    errBan.style.display = 'block';
    errBan.classList.add('visible');
    console.error(err);
  } finally {
    overlay.classList.add('hidden');
  }
}

// ── Badge do período selecionado ────────────────────────────────
function updateBadge(year, months, cidades) {
  const badge = document.getElementById('filter-label-badge');
  if (!year && !months && !cidades) {
    badge.textContent = 'TODOS OS PERÍODOS';
    return;
  }
  const parts = [];
  if (cidades) parts.push(cidades);
  if (year)    parts.push(year);
  if (months) {
    const nums   = months.split(',').map(Number);
    const labels = nums.map(n => MONTHS[n - 1]);
    parts.push(labels.join(', '));
  }
  badge.textContent = parts.join(' · ').toUpperCase();
}

function applyFilters() { loadDashboard(); }
function clearFilters() {
  yearDropdown.reset();
  monthDropdown.reset();
  cidadeDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim', 'data-fim-clear');
  loadDashboard();
}

loadDashboard();
</script>

</div>
