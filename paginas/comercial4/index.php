<div class="dash-comercial4">

<style>
/* Ajustes específicos do dashboard Comercial 4. */
.dash-comercial4 .kpi-row .kpi-chip { background: var(--card); }

.dash-comercial4 .funil-card.digital .funil-bar { background: var(--consulta); }
.dash-comercial4 .funil-card .funil-row-label { display: flex; flex-direction: column; gap: 1px; line-height: 1.25; }
.dash-comercial4 .c4-funil-valor { font-size: 8px; color: var(--muted); font-weight: 500; }

/* align-items:start (em vez do stretch default do grid) evita um loop de
   auto-inflação: com stretch, o card de registros (alto, 20 linhas) esticava
   a coluna de performance, e o JS que mede a coluna pra limitar a tabela
   acabava lendo essa altura já inflada — nunca limitava de verdade. */
.dash-comercial4 .c4-linha2 { align-items: start; }
.dash-comercial4 .c4-perf-col { display: flex; flex-direction: column; gap: 16px; min-height: 0; }
.dash-comercial4 .c4-registros-card { display: flex; flex-direction: column; overflow: hidden; }
.dash-comercial4 .c4-registros-card .tbl-wrap { flex: 1; min-height: 0; overflow: auto; }
.dash-comercial4 .c4-registros-card .bi-table tbody tr:nth-child(even) { background: var(--card2); }
.dash-comercial4 .c4-registros-card .bi-table tbody tr:hover { background: var(--consulta-glow); }
.dash-comercial4 .c4-registros-card .bi-table th, .dash-comercial4 .c4-registros-card .bi-table td { text-align: center; }
.dash-comercial4 .c4-registros-card .tbl-wrap thead th { position: sticky; top: 0; z-index: 1; background: var(--card); cursor: pointer; user-select: none; white-space: nowrap; }
.dash-comercial4 .c4-registros-card .tbl-wrap thead th:hover { background: var(--card2); }
.dash-comercial4 .c4-registros-card .tbl-wrap thead th.th-active { color: var(--consulta); }

.dash-comercial4 .c4-bar-list {
  display: flex; flex-direction: column; gap: 6px;
  max-height: 260px; overflow-y: auto; padding-right: 4px;
}
.dash-comercial4 .c4-bar-row { display: flex; align-items: center; gap: 10px; }
.dash-comercial4 .c4-avatar {
  width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 700; color: #fff; font-family: var(--font-ui);
}
.dash-comercial4 .c4-bar-name {
  width: 140px; flex-shrink: 0; font-size: 11.5px; font-weight: 600; color: var(--text);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.dash-comercial4 .c4-bar-track { flex: 1; height: 16px; background: var(--card2); border-radius: 3px; overflow: hidden; }
.dash-comercial4 .c4-bar-fill { height: 100%; border-radius: 3px; min-width: 2px; background: var(--consulta); transition: width .5s ease; }
.dash-comercial4 .c4-bar-val { font-family: var(--font-mono); font-size: 11.5px; font-weight: 700; color: var(--text); width: 44px; text-align: right; flex-shrink: 0; }
.dash-comercial4 .c4-bar-vendas {
  font-family: var(--font-mono); font-size: 10px; font-weight: 700; color: var(--fecho);
  background: var(--fecho-glow); border-radius: 4px; padding: 2px 6px; width: 56px; text-align: center; flex-shrink: 0;
}

.dash-comercial4 .role-tabs { margin-bottom: 10px; }

.dash-comercial4 .c4-table-footer {
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
  padding: 10px 14px; font-size: 12px; color: var(--muted);
}
.dash-comercial4 .c4-page-btn {
  border: 1px solid var(--border); background: var(--card); border-radius: var(--r-sm);
  padding: 5px 12px; font-size: 12px; cursor: pointer; color: var(--text);
}
.dash-comercial4 .c4-page-btn:disabled { opacity: .4; cursor: default; }
.dash-comercial4 .c4-page-btn:not(:disabled):hover { border-color: var(--consulta); color: var(--consulta); }
</style>

<!-- ── LOADING ─────────────────────────────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>

<!-- ── HEADER ─────────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo">C4</div>
    <div>
      <div class="header-title">Dashboard Comercial 4</div>
      <div class="header-sub">Aplicados · Milestones · RFE/NOID (Bitrix C3:)</div>
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
    <div class="header-badge" id="filter-label-badge">TODOS OS PERÍODOS</div>
    <div class="tab-switch">
      <a class="tab-switch-btn active" href="<?= $this->site ?>comercial4<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Comercial 4</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial4/liga<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Liga Acme-EUA</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>comercial4/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
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

  <!-- Data (de/até) -->
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

  <!-- Fases -->
  <div class="filter-pair">
    <span class="filter-label">Fases</span>
    <div class="multi-dd-wrap" id="fases-wrap">
      <button class="multi-dd-btn" id="fases-btn" type="button">Todas <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="fases-list"></div>
    </div>
  </div>

  <!-- Produtos -->
  <div class="filter-pair">
    <span class="filter-label">Produtos</span>
    <div class="camp-wrap" id="produtos-wrap">
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="produtos-input" class="camp-input" type="text" placeholder="Pesquisar produto..." autocomplete="off">
        <button class="camp-clear" id="produtos-clear" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-tags" id="produtos-tags"></div>
      <div class="camp-dropdown" id="produtos-dropdown"></div>
    </div>
  </div>

  <!-- Deal -->
  <div class="filter-pair">
    <span class="filter-label">Deal</span>
    <div class="camp-wrap" id="deal-wrap">
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="deal-input" class="camp-input" type="text" placeholder="Pesquisar deal..." autocomplete="off">
        <button class="camp-clear" id="deal-clear" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-tags" id="deal-tags"></div>
      <div class="camp-dropdown" id="deal-dropdown"></div>
    </div>
  </div>

  <div class="filter-sep"></div>

  <!-- CSM -->
  <div class="filter-pair">
    <span class="filter-label">CSM</span>
    <div class="camp-wrap" id="csm-wrap">
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="csm-input" class="camp-input" type="text" placeholder="Pesquisar CSM..." autocomplete="off">
        <button class="camp-clear" id="csm-clear" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-tags" id="csm-tags"></div>
      <div class="camp-dropdown" id="csm-dropdown"></div>
    </div>
  </div>

  <!-- Case Manager -->
  <div class="filter-pair">
    <span class="filter-label">Case Manager</span>
    <div class="camp-wrap" id="cm-wrap">
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="cm-input" class="camp-input" type="text" placeholder="Pesquisar case manager..." autocomplete="off">
        <button class="camp-clear" id="cm-clear" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-tags" id="cm-tags"></div>
      <div class="camp-dropdown" id="cm-dropdown"></div>
    </div>
  </div>

  <!-- ADV ML1 -->
  <div class="filter-pair">
    <span class="filter-label">ADV ML1</span>
    <div class="camp-wrap" id="advml1-wrap">
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="advml1-input" class="camp-input" type="text" placeholder="Pesquisar..." autocomplete="off">
        <button class="camp-clear" id="advml1-clear" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-tags" id="advml1-tags"></div>
      <div class="camp-dropdown" id="advml1-dropdown"></div>
    </div>
  </div>

  <!-- ADV ML2 -->
  <div class="filter-pair">
    <span class="filter-label">ADV ML2</span>
    <div class="camp-wrap" id="advml2-wrap">
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="advml2-input" class="camp-input" type="text" placeholder="Pesquisar..." autocomplete="off">
        <button class="camp-clear" id="advml2-clear" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-tags" id="advml2-tags"></div>
      <div class="camp-dropdown" id="advml2-dropdown"></div>
    </div>
  </div>

  <!-- ADV ML3 -->
  <div class="filter-pair">
    <span class="filter-label">ADV ML3</span>
    <div class="camp-wrap" id="advml3-wrap">
      <div class="camp-input-row">
        <span class="camp-icon">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="advml3-input" class="camp-input" type="text" placeholder="Pesquisar..." autocomplete="off">
        <button class="camp-clear" id="advml3-clear" tabindex="-1">&#x2715;</button>
      </div>
      <div class="camp-tags" id="advml3-tags"></div>
      <div class="camp-dropdown" id="advml3-dropdown"></div>
    </div>
  </div>

  <button class="btn-apply" onclick="applyFilters()">Aplicar</button>
  <button class="btn-clear" onclick="clearFilters()">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'comercial4' });</script>

<!-- ── ERRO ───────────────────────────────────────────────────── -->
<div id="error-banner" role="alert"></div>

<!-- ── CONTEÚDO PRINCIPAL ──────────────────────────────────────── -->
<main class="main" id="dashboard-main">

  <!-- 4 KPI CHIPS -->
  <div class="kpi-row">
    <div class="kpi-chip consulta">
      <span class="kpi-chip-label">Total de Clientes</span>
      <span class="kpi-chip-val" id="k-total-clientes">—</span>
    </div>
    <div class="kpi-chip fecho">
      <span class="kpi-chip-label">Vendas</span>
      <span class="kpi-chip-val" id="k-vendas">—</span>
    </div>
    <div class="kpi-chip sinal">
      <span class="kpi-chip-label">Arquiteturas a Agendar</span>
      <span class="kpi-chip-val" id="k-arquiteturas">—</span>
    </div>
    <div class="kpi-chip evento">
      <span class="kpi-chip-label">Agendadas</span>
      <span class="kpi-chip-val" id="k-agendadas">—</span>
    </div>
  </div>

  <!-- LINHA 1: FUNIL + RANKING POR PAPEL -->
  <div class="funis-grid">

    <!-- FUNIL DE CLIENTES -->
    <article class="funil-card digital">
      <div class="funil-header">
        <div>
          <span class="funil-name">Funil de Clientes</span>
          <div style="font-size:10px;color:var(--muted);font-weight:500;margin-top:2px">Análise de possível faturamento em aberto</div>
        </div>
      </div>
      <div class="funil-body" id="funil-body">
        <div style="padding:12px 0;color:var(--muted);font-size:12px;text-align:center">Carregando...</div>
      </div>
    </article>

    <!-- RANKING POR PAPEL -->
    <div class="chart-card">
      <div class="chart-header">
        <div class="chart-title-group">
          <span class="chart-dot" style="background:#4F79BC"></span>
          <span class="chart-title">Clientes e Vendas por <span id="ranking-titulo">CSM</span></span>
        </div>
      </div>
      <div class="chart-body">
        <div class="gran-toggle role-tabs" id="role-tabs">
          <button class="gran-btn active" data-papel="csm"         onclick="setPapel('csm')">CSM</button>
          <button class="gran-btn"        data-papel="caseManager" onclick="setPapel('caseManager')">Case Manager</button>
          <button class="gran-btn"        data-papel="advMl1"      onclick="setPapel('advMl1')">ADV ML1</button>
          <button class="gran-btn"        data-papel="advMl2"      onclick="setPapel('advMl2')">ADV ML2</button>
          <button class="gran-btn"        data-papel="advMl3"      onclick="setPapel('advMl3')">ADV ML3</button>
        </div>
        <div class="c4-bar-list" id="ranking-list">
          <div style="padding:12px 0;color:var(--muted);font-size:12px;text-align:center">Carregando...</div>
        </div>
      </div>
    </div>

  </div>

  <!-- LINHA 2: PERFORMANCE DE VENDAS (STATUS + PRODUTO, empilhados) AO LADO DA TABELA DE REGISTROS -->
  <div class="funis-grid c4-linha2" style="margin-top:16px">

    <div class="c4-perf-col">

      <div class="chart-card">
        <div class="chart-header">
          <div class="chart-title-group">
            <span class="chart-dot" style="background:var(--fecho)"></span>
            <span class="chart-title">Performance de Vendas por Status</span>
          </div>
        </div>
        <div class="chart-body">
          <div class="c4-bar-list" id="perf-status-list">
            <div style="padding:12px 0;color:var(--muted);font-size:12px;text-align:center">Carregando...</div>
          </div>
        </div>
      </div>

      <div class="chart-card">
        <div class="chart-header">
          <div class="chart-title-group">
            <span class="chart-dot" style="background:var(--gold)"></span>
            <span class="chart-title">Performance de Vendas por Produtos</span>
          </div>
        </div>
        <div class="chart-body">
          <div class="c4-bar-list" id="perf-produto-list">
            <div style="padding:12px 0;color:var(--muted);font-size:12px;text-align:center">Carregando...</div>
          </div>
        </div>
      </div>

    </div>

    <!-- TABELA DE REGISTROS -->
    <div class="bi-card c4-registros-card">
      <div class="bi-card-header" style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
        <div>
          <span class="bi-card-title">Registros de Orçamento</span>
          <span class="bi-card-sub">Nome do cliente, IdBitrix, Responsável, Etapa e Fase</span>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
          <input
            type="text"
            id="registros-search"
            placeholder="Buscar..."
            autocomplete="off"
            style="height:28px;padding:0 8px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--card2);color:var(--text);font-family:var(--font-ui);font-size:12px;outline:none;min-width:150px"
          >
          <div class="exp-menu-wrap" id="registros-export-wrap">
            <button class="exp-menu-btn" id="registros-export-btn" type="button">Exportar &#9662;</button>
            <div class="exp-menu-list" id="registros-export-list">
              <div class="exp-menu-item" data-fmt="csv">Exportar CSV</div>
              <div class="exp-menu-item" data-fmt="excel">Exportar Excel</div>
            </div>
          </div>
        </div>
      </div>
      <div class="bi-card-body" style="padding:0;display:flex;flex-direction:column;flex:1;min-height:0">
        <div class="tbl-wrap" id="registros-tbl-wrap">
          <table class="bi-table">
            <thead id="registros-thead"></thead>
            <tbody id="registros-body">
              <tr><td colspan="5" style="text-align:center;color:var(--muted)">Carregando...</td></tr>
            </tbody>
          </table>
        </div>
        <div class="c4-table-footer">
          <span id="registros-info">—</span>
          <div style="display:flex;gap:6px">
            <button class="c4-page-btn" id="registros-prev" onclick="mudarPagina(-1)">&#x2039; Anterior</button>
            <button class="c4-page-btn" id="registros-next" onclick="mudarPagina(1)">Próxima &#x203A;</button>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="last-updated" id="last-updated"></div>

</main>
</div>

<script>
// ══════════════════════════════════════════════════════════════
//  Comercial 4 — Dashboard JS
//  Consome: comercial4/dados, comercial4/ranking, comercial4/performance,
//  comercial4/registros, comercial4/opcoes (controller MRPHP)
//
//  createMultiDropdown, createSearchDropdown, fNum, fBar, fPctRatio são
//  fornecidas por js/bi-dashboard.js (carregado no <head> pelo template).
// ══════════════════════════════════════════════════════════════

const API_DADOS       = '<?= $this->site ?>comercial4/dados';
const API_RANKING     = '<?= $this->site ?>comercial4/ranking';
const API_PERFORMANCE = '<?= $this->site ?>comercial4/performance';
const API_REGISTROS   = '<?= $this->site ?>comercial4/registros';
const API_OPCOES      = '<?= $this->site ?>comercial4/opcoes';

const MONTHS = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const YEARS  = ['2024', '2025', '2026'];
const FASES  = ['Aplicados', 'Milestone 1', 'Milestone 2', 'Milestone 3', 'RFE/Noid Respondida', 'RFE/Noid Recebida', 'RFE Ajuste de status'];
const PAPEL_LABEL = { csm: 'CSM', caseManager: 'Case Manager', advMl1: 'ADV ML1', advMl2: 'ADV ML2', advMl3: 'ADV ML3' };

let papelAtual = 'csm';
let paginaAtual = 1;
let ultimosFiltros = { year: null, months: null, dataInicio: null, dataFim: null, fases: null, produtos: null, nomeDeal: null, csm: null, caseManager: null, advMl1: null, advMl2: null, advMl3: null };

const yearDropdown     = createMultiDropdown('year-wrap', 'year-btn', 'year-list', YEARS, l => l, l => l);
const monthDropdown    = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);
const fasesDropdown    = createMultiDropdown('fases-wrap', 'fases-btn', 'fases-list', FASES, l => l, l => l);
const produtosDropdown = createSearchDropdown('produtos-wrap', 'produtos-input', 'produtos-dropdown', 'produtos-clear', 'produtos-tags', 'Nenhum produto encontrado');
const dealDropdown     = createSearchDropdown('deal-wrap', 'deal-input', 'deal-dropdown', 'deal-clear', 'deal-tags', 'Nenhum deal encontrado');
const csmDropdown      = createSearchDropdown('csm-wrap', 'csm-input', 'csm-dropdown', 'csm-clear', 'csm-tags', 'Nenhum CSM encontrado');
const cmDropdown       = createSearchDropdown('cm-wrap', 'cm-input', 'cm-dropdown', 'cm-clear', 'cm-tags', 'Nenhum case manager encontrado');
const advMl1Dropdown   = createSearchDropdown('advml1-wrap', 'advml1-input', 'advml1-dropdown', 'advml1-clear', 'advml1-tags', 'Nenhum resultado');
const advMl2Dropdown   = createSearchDropdown('advml2-wrap', 'advml2-input', 'advml2-dropdown', 'advml2-clear', 'advml2-tags', 'Nenhum resultado');
const advMl3Dropdown   = createSearchDropdown('advml3-wrap', 'advml3-input', 'advml3-dropdown', 'advml3-clear', 'advml3-tags', 'Nenhum resultado');

fetch(API_OPCOES, { cache: 'no-store' })
  .then(res => res.json())
  .then(op => {
    produtosDropdown.load(Array.isArray(op.produtos) ? op.produtos : []);
    dealDropdown.load(Array.isArray(op.nomeDeal) ? op.nomeDeal : []);
    csmDropdown.load(Array.isArray(op.csm) ? op.csm : []);
    cmDropdown.load(Array.isArray(op.caseManager) ? op.caseManager : []);
    advMl1Dropdown.load(Array.isArray(op.advMl1) ? op.advMl1 : []);
    advMl2Dropdown.load(Array.isArray(op.advMl2) ? op.advMl2 : []);
    advMl3Dropdown.load(Array.isArray(op.advMl3) ? op.advMl3 : []);
  })
  .catch(err => console.error('Erro ao carregar opções de filtro:', err));

['data-inicio', 'data-fim'].forEach(id => {
  const input    = document.getElementById(id);
  const clearBtn = document.getElementById(id + '-clear');
  input?.addEventListener('change', () => clearBtn?.classList.toggle('visible', !!input.value));
  clearBtn?.addEventListener('click', () => clearDateInput(id, id + '-clear'));
});

function set(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }

function escHtmlC4(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function corAvatar(nome) {
  let hash = 0;
  const s = String(nome || '');
  for (let i = 0; i < s.length; i++) hash = s.charCodeAt(i) + ((hash << 5) - hash);
  return `hsl(${hash % 360}, 55%, 45%)`;
}
function iniciais(nome) {
  const partes = String(nome || '').trim().split(/\s+/).filter(Boolean);
  if (!partes.length) return '?';
  return (partes[0][0] + (partes[1]?.[0] || '')).toUpperCase();
}

// valorAssessoria_dealV2 é um valor em reais (a medida DAX original formata
// como "R$ #,##0.00") — fBRL de js/bi-dashboard.js é para valores em dólar
// (usado pelos demais dashboards), por isso um formatador próprio aqui.
function fReal(val) {
  const n = Number(val) || 0;
  return 'R$ ' + n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ── KPIs ─────────────────────────────────────────────────────────
function fillKpis(k) {
  set('k-vendas', fNum(k.vendas));
  set('k-total-clientes', fNum(k.total_clientes));
  set('k-arquiteturas', fNum(k.arquiteturas_agendar));
  set('k-agendadas', fNum(k.agendadas));
}

// ── Funil de Clientes (N etapas, decrescente) ─────────────────────
function fillFunil(funil) {
  const body = document.getElementById('funil-body');
  if (!funil || !funil.length) {
    body.innerHTML = '<div style="padding:12px 0;color:var(--muted);font-size:12px;text-align:center">Sem dados no período</div>';
    return;
  }
  const base = funil[0].qtd || 1;
  body.innerHTML = funil.map(e => {
    const pct = e.pct_anterior === null ? '—' : (e.pct_anterior * 100).toFixed(0) + '%';
    return `
      <div class="funil-row">
        <span class="funil-row-label" title="${escHtmlC4(e.etapa)}">
          ${escHtmlC4(e.etapa)}
          <span class="c4-funil-valor">${fReal(e.valor)}</span>
        </span>
        <div class="funil-bar-wrap"><div class="funil-bar" style="width:${fBar(e.qtd, base)}"></div></div>
        <span class="funil-row-val">${fNum(e.qtd)}</span>
        <span class="funil-row-cv">${pct}</span>
      </div>
    `;
  }).join('');
}

// ── Ranking por papel (CSM/Case Manager/ADV ML1-3) ────────────────
function fillRanking(linhas) {
  const el = document.getElementById('ranking-list');
  if (!linhas || !linhas.length) {
    el.innerHTML = '<div style="padding:12px 0;color:var(--muted);font-size:12px;text-align:center">Sem dados no período</div>';
    return;
  }
  const max = Math.max(1, ...linhas.map(r => r.clientes));
  el.innerHTML = linhas.map(r => `
    <div class="c4-bar-row">
      <span class="c4-avatar" style="background:${corAvatar(r.nome)}">${iniciais(r.nome)}</span>
      <span class="c4-bar-name" title="${escHtmlC4(r.nome)}">${escHtmlC4(r.nome)}</span>
      <div class="c4-bar-track"><div class="c4-bar-fill" style="width:${fBar(r.clientes, max)}"></div></div>
      <span class="c4-bar-val">${fNum(r.clientes)}</span>
      <span class="c4-bar-vendas">${fNum(r.vendas)} vendas</span>
    </div>
  `).join('');
}

// ── Performance de Vendas (por Status / por Produto — mesma lista simples) ──
function fillPerformanceList(containerId, linhas) {
  const el = document.getElementById(containerId);
  if (!linhas || !linhas.length) {
    el.innerHTML = '<div style="padding:12px 0;color:var(--muted);font-size:12px;text-align:center">Sem vendas fechadas no período</div>';
    return;
  }
  const max = Math.max(1, ...linhas.map(r => r.vendas));
  el.innerHTML = linhas.map(r => `
    <div class="c4-bar-row">
      <span class="c4-bar-name" style="width:170px" title="${escHtmlC4(r.grupo)}">${escHtmlC4(r.grupo)}</span>
      <div class="c4-bar-track"><div class="c4-bar-fill" style="width:${fBar(r.vendas, max)};background:var(--fecho)"></div></div>
      <span class="c4-bar-val">${fNum(r.vendas)}</span>
    </div>
  `).join('');
}

// ── Registros (tabela paginada) ───────────────────────────────────
function fillRegistros(data) {
  const body = document.getElementById('registros-body');
  const linhas = data.linhas || [];
  if (!linhas.length) {
    body.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--muted)">Sem registros no período</td></tr>';
  } else {
    body.innerHTML = linhas.map(r => `
      <tr>
        <td>${escHtmlC4(r.nome_cliente)}</td>
        <td>${escHtmlC4(r.id_bitrix)}</td>
        <td>${escHtmlC4(r.responsavel)}</td>
        <td>${escHtmlC4(r.etapa)}</td>
        <td>${escHtmlC4(r.fase)}</td>
      </tr>
    `).join('');
  }
  set('registros-info', `Página ${data.pagina} de ${data.totalPaginas} · ${fNum(data.total)} registros`);
  document.getElementById('registros-prev').disabled = data.pagina <= 1;
  document.getElementById('registros-next').disabled = data.pagina >= data.totalPaginas;
}

// ── Badge ────────────────────────────────────────────────────────
function paramsFiltrosAtuais() {
  const params = new URLSearchParams();
  const f = ultimosFiltros;
  if (f.year)        params.set('year', f.year);
  if (f.months)      params.set('months', f.months);
  if (f.dataInicio)  params.set('data_inicio', f.dataInicio);
  if (f.dataFim)     params.set('data_fim', f.dataFim);
  if (f.fases)       params.set('fases', f.fases);
  if (f.produtos)    params.set('produtos', f.produtos);
  // Lista (nomeDeal[]), não CSV: nome de deal pode ter vírgula.
  if (f.nomeDeal)    f.nomeDeal.forEach(n => params.append('nomeDeal[]', n));
  if (f.csm)         params.set('csm', f.csm);
  if (f.caseManager) params.set('caseManager', f.caseManager);
  if (f.advMl1)      params.set('advMl1', f.advMl1);
  if (f.advMl2)      params.set('advMl2', f.advMl2);
  if (f.advMl3)      params.set('advMl3', f.advMl3);
  return params;
}

function updateBadge() {
  const badge = document.getElementById('filter-label-badge');
  const f = ultimosFiltros;
  const algum = f.year || f.months || f.dataInicio || f.dataFim || f.fases || f.produtos || f.nomeDeal || f.csm || f.caseManager || f.advMl1 || f.advMl2 || f.advMl3;
  if (!algum) {
    badge.textContent = 'TODOS OS PERÍODOS';
    return;
  }
  const parts = [];
  if (f.dataInicio || f.dataFim) {
    parts.push((f.dataInicio || '…') + ' até ' + (f.dataFim || '…'));
  } else {
    if (f.year)   parts.push(f.year);
    if (f.months) parts.push(f.months.split(',').map(n => MONTHS[Number(n) - 1]).join(', '));
  }
  if (f.fases)       parts.push('Fases: ' + f.fases);
  if (f.nomeDeal)    parts.push('Deal: ' + f.nomeDeal.join(', '));
  if (f.csm)         parts.push('CSM: ' + f.csm);
  if (f.caseManager) parts.push('Case Manager: ' + f.caseManager);
  badge.textContent = parts.join(' · ').toUpperCase();
}

// ── Carregar cada bloco ────────────────────────────────────────────
async function loadDados() {
  const res  = await fetch(API_DADOS + '?' + paramsFiltrosAtuais().toString(), { cache: 'no-store' });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const data = await res.json();
  if (data.error) throw new Error(data.error);
  fillKpis(data.kpis);
  fillFunil(data.funil);
}

async function loadRanking() {
  const params = paramsFiltrosAtuais();
  params.set('papel', papelAtual);
  const res  = await fetch(API_RANKING + '?' + params.toString(), { cache: 'no-store' });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const data = await res.json();
  if (data.error) throw new Error(data.error);
  fillRanking(data.linhas);
}

async function loadPerformance() {
  const res  = await fetch(API_PERFORMANCE + '?' + paramsFiltrosAtuais().toString(), { cache: 'no-store' });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const data = await res.json();
  if (data.error) throw new Error(data.error);
  fillPerformanceList('perf-status-list', data.porStatus);
  fillPerformanceList('perf-produto-list', data.porProduto);
  syncRegistrosHeight();
}

// A tabela de registros tem ~17 mil linhas no total — busca é sempre
// server-side (nunca carrega tudo pra filtrar no navegador).
function buscaRegistrosAtual() {
  return (document.getElementById('registros-search').value || '').trim();
}

function paramsRegistros() {
  const params = paramsFiltrosAtuais();
  const busca = buscaRegistrosAtual();
  if (busca) params.set('busca', busca);
  if (ordemRegistros) {
    params.set('ordem', ordemRegistros);
    params.set('dir', dirRegistros);
  }
  return params;
}

async function loadRegistros() {
  const params = paramsRegistros();
  params.set('pagina', paginaAtual);
  const res  = await fetch(API_REGISTROS + '?' + params.toString(), { cache: 'no-store' });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const data = await res.json();
  if (data.error) throw new Error(data.error);
  fillRegistros(data);
  syncRegistrosHeight();
}

// Limita a altura do card de registros à altura da coluna de gráficos de
// performance ao lado (a tabela rola por dentro) — limita o CARD inteiro
// (não só a tabela): o header e o rodapé de paginação ficam de fora do
// cálculo, e o "flex:1" do .tbl-wrap (CSS) consome só o espaço restante.
function syncRegistrosHeight() {
  const col  = document.querySelector('.c4-perf-col');
  const card = document.querySelector('.c4-registros-card');
  if (col && card) card.style.maxHeight = col.offsetHeight + 'px';
}
window.addEventListener('resize', syncRegistrosHeight);

const COLS_REGISTROS = [
  { k: 'nome_cliente', label: 'Nome Cliente' },
  { k: 'id_bitrix',    label: 'IdBitrix' },
  { k: 'responsavel',  label: 'Responsável' },
  { k: 'etapa',        label: 'Etapa' },
  { k: 'fase',         label: 'Fase' },
];

// Ordenação é sempre server-side (a tabela tem ~17 mil linhas — ordenar só
// as 20 linhas já carregadas na tela não teria efeito real sobre o total).
let ordemRegistros = null;
let dirRegistros = 'asc';

function renderRegistrosHead() {
  document.getElementById('registros-thead').innerHTML = '<tr>' + COLS_REGISTROS.map(c => {
    const active = c.k === ordemRegistros;
    const arrow  = active ? (dirRegistros === 'asc' ? ' ▲' : ' ▼') : ' ▲▼';
    return `<th class="${active ? 'th-active' : ''}" title="Clique para ordenar" tabindex="0" role="button" aria-label="Ordenar por ${c.label}" onclick="sortRegistros('${c.k}')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();sortRegistros('${c.k}')}">${c.label}${arrow}</th>`;
  }).join('') + '</tr>';
}
renderRegistrosHead();

function sortRegistros(key) {
  if (ordemRegistros === key) {
    dirRegistros = dirRegistros === 'asc' ? 'desc' : 'asc';
  } else {
    ordemRegistros = key;
    dirRegistros = 'asc';
  }
  paginaAtual = 1;
  renderRegistrosHead();
  loadRegistros().catch(err => console.error('Erro ao carregar registros:', err));
}

createExportMenu('registros-export-wrap', 'registros-export-btn', 'registros-export-list', async fmt => {
  const params = paramsRegistros();
  params.set('tudo', '1');
  const res  = await fetch(API_REGISTROS + '?' + params.toString(), { cache: 'no-store' });
  const data = await res.json();
  const nome = 'registros-comercial4-' + new Date().toISOString().slice(0, 10);
  if (fmt === 'csv') exportRowsAsCsv(COLS_REGISTROS, data.linhas || [], nome);
  else               exportRowsAsExcel(COLS_REGISTROS, data.linhas || [], nome);
});

let buscaRegistrosTimer = null;
document.getElementById('registros-search').addEventListener('input', () => {
  clearTimeout(buscaRegistrosTimer);
  buscaRegistrosTimer = setTimeout(() => {
    paginaAtual = 1;
    loadRegistros().catch(err => console.error('Erro ao carregar registros:', err));
  }, 350);
});

// ── Troca de aba de papel (CSM/Case Manager/ADV ML1-3) ────────────
function setPapel(papel) {
  papelAtual = papel;
  document.querySelectorAll('#role-tabs .gran-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.papel === papel));
  set('ranking-titulo', PAPEL_LABEL[papel] || papel);
  loadRanking().catch(err => console.error('Erro ao carregar ranking:', err));
}

// ── Paginação da tabela de registros ──────────────────────────────
function mudarPagina(delta) {
  paginaAtual = Math.max(1, paginaAtual + delta);
  loadRegistros().catch(err => console.error('Erro ao carregar registros:', err));
}

// ── Carregar tudo (aplicar/limpar filtros) ────────────────────────
async function loadDashboard() {
  const overlay = document.getElementById('loading-overlay');
  const errBan  = document.getElementById('error-banner');
  overlay.classList.remove('hidden');
  document.body.classList.add('loading');
  errBan.style.display = 'none';
  errBan.classList.remove('visible');

  paginaAtual = 1;

  try {
    await Promise.all([loadDados(), loadRanking(), loadPerformance(), loadRegistros()]);
    document.getElementById('last-updated').textContent = 'Atualizado em: ' + new Date().toLocaleString('pt-BR');
  } catch (err) {
    errBan.textContent = '⚠️ Erro ao carregar dados: ' + err.message;
    errBan.style.display = 'block';
    errBan.classList.add('visible');
    console.error(err);
  } finally {
    overlay.classList.add('hidden');
    document.body.classList.remove('loading');
  }
}

// ── Aplicar / Limpar filtros ──────────────────────────────────────
function applyFilters() {
  ultimosFiltros = {
    year: yearDropdown.getValue(),
    months: monthDropdown.getValue(),
    dataInicio: getDateInput('data-inicio'),
    dataFim: getDateInput('data-fim'),
    fases: fasesDropdown.getValue(),
    produtos: produtosDropdown.getValue(),
    nomeDeal: dealDropdown.getSelected().length ? dealDropdown.getSelected() : null,
    csm: csmDropdown.getValue(),
    caseManager: cmDropdown.getValue(),
    advMl1: advMl1Dropdown.getValue(),
    advMl2: advMl2Dropdown.getValue(),
    advMl3: advMl3Dropdown.getValue(),
  };
  updateBadge();
  loadDashboard();
}

function clearFilters() {
  yearDropdown.reset();
  monthDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim', 'data-fim-clear');
  fasesDropdown.reset();
  produtosDropdown.reset();
  dealDropdown.reset();
  csmDropdown.reset();
  cmDropdown.reset();
  advMl1Dropdown.reset();
  advMl2Dropdown.reset();
  advMl3Dropdown.reset();
  document.getElementById('registros-search').value = '';
  applyFilters();
}

yearDropdown.select(['2026']);
applyFilters();
</script>
