<div class="dash-consultacom2-eventos">

<style>
.dash-consultacom2-eventos #thead-eventos th {
  cursor: pointer;
  user-select: none;
}
.dash-consultacom2-eventos #thead-eventos th:hover,
.dash-consultacom2-eventos #thead-eventos th.th-active {
  background: var(--border);
  color: var(--text);
}
.dash-consultacom2-eventos #thead-eventos th.th-nosort {
  cursor: default;
}
.dash-consultacom2-eventos #thead-eventos th.th-nosort:hover {
  background: var(--card2);
  color: var(--muted);
}
.dash-consultacom2-eventos .bx-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: var(--consulta);
  font-weight: 600;
  text-decoration: none;
  font-size: 12.5px;
}
.dash-consultacom2-eventos .bx-link:hover {
  text-decoration: underline;
}
.dash-consultacom2-eventos .st-badge {
  display: inline-block;
  padding: 2px 9px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 600;
  white-space: nowrap;
}
.dash-consultacom2-eventos .st-won  { color: var(--green);  background: var(--green-glow); }
.dash-consultacom2-eventos .st-lose { color: var(--danger); background: var(--danger-glow); }
/* Mesma razão de paginas/consultacom2/index.php, agravada: aqui são 10 colunas
   e o table-layout automático dava tanta largura pro nome/e-mail/form que a
   última coluna (ID Bitrix Evento) nascia cortada na borda do card. O aperto
   dos clips abaixo, mais a fusão de "ID Evento" + "Evento" numa coluna só,
   devolvem ~200px e fazem a tabela caber sem rolagem na maioria das telas.
   Testado no mock consultacom2-eventos-mock.html (variante B).
   Datas e rótulos em uma linha só; textos longos cortados com reticências e o
   valor inteiro no title. */
.dash-consultacom2-eventos .tbl-wrap { overflow-x: auto; }
.dash-consultacom2-eventos .bi-table td { white-space: nowrap; }
.dash-consultacom2-eventos .bi-table th { white-space: normal; }
.dash-consultacom2-eventos .bi-table td.cell-clip {
  max-width: 180px;
  overflow: hidden;
  text-overflow: ellipsis;
}
/* Classe por coluna em vez de :nth-child — reordenar COLS_EVENTOS não pode
   desalinhar as larguras. */
.dash-consultacom2-eventos .bi-table td.clip-email { max-width: 200px; }
.dash-consultacom2-eventos .bi-table td.clip-form  { max-width: 165px; }
/* 14px de padding lateral × 10 colunas é o que sobrava pra cortar. */
.dash-consultacom2-eventos .bi-table thead th,
.dash-consultacom2-eventos .bi-table tbody td { padding-left: 11px; padding-right: 11px; }

/* ── Tabela "Por formulário" ─────────────────────────────────────────────
   A linha inteira é o botão de filtro da tabela de baixo. */
.dash-consultacom2-eventos #thead-forms th { cursor: pointer; user-select: none; }
.dash-consultacom2-eventos #thead-forms th:hover,
.dash-consultacom2-eventos #thead-forms th.th-active { background: var(--border); color: var(--text); }
.dash-consultacom2-eventos #tbody-forms tr { cursor: pointer; }
.dash-consultacom2-eventos .forms-aviso { cursor: help; border-bottom: 1px dotted var(--border); }
.dash-consultacom2-eventos #tbody-forms tr.tr-sel td { background: var(--consulta-glow); }
.dash-consultacom2-eventos #tbody-forms tr.tr-sel td:first-child {
  box-shadow: inset 3px 0 0 var(--consulta);
  font-weight: 700;
}
/* Os dois baldes do fim ("sem formulário" / "sem card de evento") não são
   formulários de verdade — ficam em itálico e apagados pra não se confundirem
   com uma campanha real. */
.dash-consultacom2-eventos #tbody-forms tr.tr-balde td:first-child {
  font-style: italic; color: var(--muted);
}
/* Chip "Formulário: ..." no cabeçalho da tabela de registros. */
.dash-consultacom2-eventos .form-chip {
  display: none; align-items: center; gap: 7px;
  padding: 3px 10px; border-radius: 999px;
  background: var(--consulta-glow); color: var(--consulta);
  font-size: 11.5px; font-weight: 600; max-width: 320px;
}
.dash-consultacom2-eventos .form-chip.on { display: inline-flex; }
.dash-consultacom2-eventos .form-chip-nome {
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.dash-consultacom2-eventos .form-chip-x {
  border: none; background: none; cursor: pointer; padding: 0 0 0 2px;
  color: inherit; font-size: 13px; line-height: 1; font-weight: 700;
}
.dash-consultacom2-eventos .form-chip-x:hover { opacity: .65; }
</style>

<!-- ── LOADING ─────────────────────────────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>

<!-- ── HEADER ─────────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo">C2</div>
    <div>
      <div class="header-title">Consultas &times; Eventos</div>
      <div class="header-sub">Cards do Comercial 2 (C5) com consulta realizada, cruzados pelo contato com o card do funil de Evento</div>
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
  <div class="header-badge" id="filter-label-badge">MÊS ATUAL</div>
</header>

<!-- ── FILTROS ─────────────────────────────────────────────────── -->
<section class="filters is-collapsible" aria-label="Filtros do painel">
  <button class="filters-toggle" type="button" aria-expanded="true" aria-controls="filters-fields">
    <span>Filtros</span>
    <span class="dd-caret filters-toggle-caret">&#x25BE;</span>
  </button>
  <div class="filters-fields" id="filters-fields">

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

  <!-- Vendedor -->
  <div class="filter-pair">
    <span class="filter-label">Vendedor</span>
    <div class="multi-dd-wrap" id="comercial-wrap">
      <button class="multi-dd-btn" id="comercial-btn" type="button">Todos <span class="dd-caret">▾</span></button>
      <div class="multi-dd-list" id="comercial-list"></div>
    </div>
  </div>

  <button class="btn-apply" onclick="applyFilters()">Aplicar</button>
  <button class="btn-clear" onclick="clearFilters()">Limpar</button>
  </div>
</section>
<script>initFiltersToggle({ key: 'consultacom2-eventos' });</script>

<!-- ── ERRO ───────────────────────────────────────────────────── -->
<div id="error-banner" role="alert"></div>

<!-- ── CONTEÚDO PRINCIPAL ──────────────────────────────────────── -->
<main class="main" id="dashboard-main">

  <div class="bi-card" style="margin-bottom:16px">
    <div class="bi-card-header" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">
      <span class="bi-card-title">Por formul&aacute;rio</span>
      <div style="display:flex;align-items:center;gap:10px">
        <span class="bi-card-sub">clique numa linha para filtrar a tabela abaixo</span>
        <span class="bi-card-sub forms-aviso" title="Um evento vende as consultas quase todas no dia dele e as realiza nos meses seguintes, ent&atilde;o as duas colunas quase nunca falam do mesmo grupo de pessoas &mdash; dividir uma pela outra n&atilde;o d&aacute; taxa de comparecimento.">
          &#9432; realizadas pela data da consulta &middot; vendidas pela data da venda
        </span>
        <span class="bi-card-sub" id="forms-count"></span>
      </div>
    </div>
    <div class="tbl-wrap" style="max-height:300px;overflow:auto">
      <table class="bi-table">
        <thead id="thead-forms"></thead>
        <tbody id="tbody-forms">
          <tr><td colspan="3" style="text-align:center;padding:24px;color:var(--muted)">Carregando&hellip;</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="bi-card">
    <div class="bi-card-header" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">
      <span class="bi-card-title">Consultas &times; Eventos</span>
      <span class="form-chip" id="form-chip">
        <span class="form-chip-nome" id="form-chip-nome"></span>
        <button class="form-chip-x" type="button" onclick="selecionarFormulario(null)" title="Limpar filtro de formul&aacute;rio" aria-label="Limpar filtro de formul&aacute;rio">&times;</button>
      </span>
      <div style="display:flex;align-items:center;gap:10px">
        <select
          id="eventos-fase-filter"
          onchange="filtrarEventos()"
          style="height:30px;padding:0 8px;border-radius:var(--r-sm,6px);border:1px solid var(--border);background:var(--card2);color:var(--text);font-family:var(--font-ui);font-size:12.5px;outline:none"
        >
          <option value="">Fase no evento: todas</option>
          <option value="WON">Won</option>
          <option value="LOSE">Lose</option>
          <option value="__sem__">Sem evento</option>
        </select>
        <input
          type="text"
          id="eventos-search"
          placeholder="Buscar em qualquer coluna..."
          autocomplete="off"
          oninput="filtrarEventos()"
          style="height:30px;padding:0 10px;border-radius:var(--r-sm,6px);border:1px solid var(--border);background:var(--card2);color:var(--text);font-family:var(--font-ui);font-size:12.5px;outline:none;min-width:240px"
        >
        <div class="exp-menu-wrap" id="eventos-export-wrap">
          <button class="exp-menu-btn" id="eventos-export-btn" type="button">Exportar &#9662;</button>
          <div class="exp-menu-list" id="eventos-export-list">
            <div class="exp-menu-item" data-fmt="csv">Exportar CSV</div>
            <div class="exp-menu-item" data-fmt="excel">Exportar Excel</div>
          </div>
        </div>
        <span class="bi-card-sub" id="eventos-count"></span>
      </div>
    </div>
    <div class="tbl-wrap" style="max-height:640px;overflow:auto">
      <table class="bi-table">
        <thead id="thead-eventos"></thead>
        <tbody id="tbody-eventos">
          <tr><td colspan="10" style="text-align:center;padding:32px;color:var(--muted)">Carregando…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="last-updated" id="last-updated"></div>

</main>
</div>

<script>
// ══════════════════════════════════════════════════════════════
//  ConsultaCom2 · Eventos — Dashboard JS
//  Consome: consultacom2/dadoseventos (controller MRPHP)
//
//  createMultiDropdown, createExportMenu, exportRowsAsCsv/Excel são
//  fornecidas por js/bi-dashboard.js (carregado no <head> pelo template,
//  antes deste script).
// ══════════════════════════════════════════════════════════════

const API_URL = '<?= $this->site ?>consultacom2/dadoseventos';
const MONTHS  = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const YEARS   = anosDesde(2025);

const yearDropdown  = createMultiDropdown('year-wrap',  'year-btn',  'year-list',  YEARS,  l => l, l => l);
const monthDropdown = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l, i) => i + 1);
// Lista vazia na criação: os vendedores vêm do endpoint (são 24, o C5 inteiro,
// não só o time do Comercial 2 das outras duas abas).
const comercialDropdown = createMultiDropdown('comercial-wrap', 'comercial-btn', 'comercial-list', [], l => l, l => l);

const NOW_YEAR  = String(new Date().getFullYear());
const NOW_MONTH = new Date().getMonth() + 1;
yearDropdown.select([NOW_YEAR]);
monthDropdown.select([String(NOW_MONTH)]);

// load() limpa a seleção do dropdown, então a lista de vendedores é carregada
// uma única vez — repetir a cada fetch apagaria o filtro que o usuário acabou
// de aplicar. A lista não depende de filtro mesmo (vem cacheada à parte).
let responsaveisCarregados = false;

function escHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// O id do card é o rótulo do link — sem id não há link (o card não existe
// daquele lado), então a célula vira travessão.
function bitrixCell(id, href) {
  if (!id) return '—';
  if (!href) return escHtml(id);
  return `<a class="bx-link" href="${escHtml(href)}" target="_blank" rel="noopener">${escHtml(id)} ↗</a>`;
}

// Won e Lose são as duas fases do funil de Evento (o único sem prefixo de
// categoria no Bitrix). Lose sem data de venda = descartado antes de comprar a
// consulta; com data = descartado depois. Os dois usam o mesmo badge, a coluna
// Data Venda é que distingue.
function faseBadge(v) {
  if (!v) return '—';
  const cls = String(v).toUpperCase() === 'WON' ? 'st-won' : 'st-lose';
  const rotulo = String(v).charAt(0).toUpperCase() + String(v).slice(1).toLowerCase();
  return `<span class="st-badge ${cls}">${escHtml(rotulo)}</span>`;
}

// ── Colunas da tabela (ordenação por clique-no-cabeçalho) ────────
const COLS_EVENTOS = [
  { k: 'nome_card',               label: 'Nome do Card (C5)',   type: 'text', sortable: true },
  { k: 'email',                   label: 'E-mail',              type: 'text', sortable: true },
  { k: 'comercial2',              label: 'Comercial 2',         type: 'text', sortable: true },
  { k: 'data_consulta_realizada', label: 'Consulta Realizada',  type: 'date', sortable: true },
  { k: 'fase_evento',             label: 'Fase no Evento',      type: 'text', sortable: true },
  { k: 'data_venda_evento',       label: 'Data Venda (Evento)', type: 'date', sortable: true },
  // Formulário do registro: o do card de evento ou, quando ele não existe,
  // o do próprio card C5 (o backend já resolve a precedência).
  { k: 'form',                    label: 'Form Preenchido',     type: 'text', sortable: true },
  // Id e nome do evento na mesma célula ("134 · OTR - Brasília"): são sempre
  // lidos juntos e, separados, custavam ~70px numa tabela que já não cabia.
  // A busca continua olhando os dois campos crus, então procurar por "134"
  // ou por "Brasília" segue funcionando.
  { k: 'evento_label',            label: 'Evento',              type: 'text', sortable: true },
  // O próprio número do card é o link: quem confere no Bitrix precisa do id
  // visível (ele circula em planilha e em conversa), não de um 'Abrir' genérico.
  { k: 'id_bitrix_c2',            label: 'ID Bitrix C5',        type: 'text', sortable: true },
  { k: 'id_bitrix_evento',        label: 'ID Bitrix Evento',    type: 'text', sortable: true },
];

let ultimasLinhasEventos = [];
let _sortKeyEventos = null;
let _sortDirEventos = 'asc';

function sortByEventos(key) {
  const col = COLS_EVENTOS.find(c => c.k === key);
  if (!col || !col.sortable) return;
  if (_sortKeyEventos === key) {
    _sortDirEventos = _sortDirEventos === 'asc' ? 'desc' : 'asc';
  } else {
    _sortKeyEventos = key;
    _sortDirEventos = 'asc';
  }
  renderHeadEventos();
  filtrarEventos();
}

// dd/mm/yyyy -> yyyymmdd (string) para comparar; vazio/— sempre vai pro final
function sortValueEventos(v, type) {
  if (v === null || v === undefined || v === '') return null;
  if (type === 'date') {
    const [d, m, y] = String(v).split('/');
    return y + m.padStart(2, '0') + d.padStart(2, '0');
  }
  return String(v).toLowerCase();
}

function ordenarEventos(linhas) {
  if (!_sortKeyEventos) return linhas;
  const col = COLS_EVENTOS.find(c => c.k === _sortKeyEventos);
  const dir = _sortDirEventos === 'asc' ? 1 : -1;
  return linhas.slice().sort((a, b) => {
    const av = sortValueEventos(a[_sortKeyEventos], col.type);
    const bv = sortValueEventos(b[_sortKeyEventos], col.type);
    if (av === null && bv === null) return 0;
    if (av === null) return 1;
    if (bv === null) return -1;
    if (av < bv) return -1 * dir;
    if (av > bv) return 1 * dir;
    return 0;
  });
}

function renderHeadEventos() {
  document.getElementById('thead-eventos').innerHTML = '<tr>' + COLS_EVENTOS.map(c => {
    const active = c.k === _sortKeyEventos;
    const arrow  = c.sortable ? (active ? (_sortDirEventos === 'asc' ? ' ▲' : ' ▼') : ' ▲▼') : '';
    const cls    = [active ? 'th-active' : '', c.sortable ? '' : 'th-nosort'].filter(Boolean).join(' ');
    return `<th class="${cls}" title="${c.sortable ? 'Clique para ordenar' : ''}" tabindex="0" role="button" aria-label="${c.sortable ? 'Ordenar por ' + c.label : c.label}" onclick="sortByEventos('${c.k}')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();sortByEventos('${c.k}')}">${c.label}${arrow}</th>`;
  }).join('') + '</tr>';
}
renderHeadEventos();

function fillEventos(linhas) {
  // evento_label é derivado no cliente, não vem do endpoint: o payload segue
  // com id_evento e nome_evento separados (que a busca usa), e a tabela e o
  // export consomem a versão junta.
  ultimasLinhasEventos = (linhas || []).map(r => ({
    ...r,
    evento_label: (r.id_evento || r.nome_evento)
      ? `${r.id_evento ?? '—'} · ${r.nome_evento ?? '—'}`
      : null,
  }));
  const searchInput = document.getElementById('eventos-search');
  if (searchInput) searchInput.value = '';
  const faseFiltro = document.getElementById('eventos-fase-filter');
  if (faseFiltro) faseFiltro.value = '';
  renderLinhasEventos(ordenarEventos(ultimasLinhasEventos));
}

// ── Busca client-side em todas as colunas + filtro de fase (as linhas já vêm
// carregadas do período) — retorna a tabela "como está na tela" (busca + fase
// + ordenação atuais), usado tanto pra renderizar quanto pro export.
function linhasEventosVisiveis() {
  const termo = (document.getElementById('eventos-search').value || '').trim().toLowerCase();
  const fase  = document.getElementById('eventos-fase-filter').value;

  let base = ultimasLinhasEventos;

  // Filtro vindo do clique na tabela "Por formulário". A chave é recalculada
  // aqui com a mesma regra de montarLinhasForms() pra não existirem duas
  // definições do que é "o formulário desta linha".
  if (formSelecionado !== null) {
    base = base.filter(r => (r.form || SEM_FORM) === formSelecionado);
  }

  if (fase === '__sem__')  base = base.filter(r => !r.fase_evento);
  else if (fase)           base = base.filter(r => String(r.fase_evento || '').toUpperCase() === fase);

  if (termo) {
    base = base.filter(r => [
      r.nome_card, r.email, r.comercial2, r.data_consulta_realizada, r.fase_evento,
      r.data_venda_evento, r.form, r.id_evento, r.nome_evento,
      r.id_bitrix_c2, r.id_bitrix_evento,
    ].filter(Boolean).join(' ').toLowerCase().includes(termo));
  }
  return { linhas: ordenarEventos(base), filtrado: !!termo || !!fase || formSelecionado !== null };
}

function filtrarEventos() {
  const { linhas, filtrado } = linhasEventosVisiveis();
  renderLinhasEventos(linhas, filtrado);
}

createExportMenu('eventos-export-wrap', 'eventos-export-btn', 'eventos-export-list', fmt => {
  const { linhas } = linhasEventosVisiveis();
  const nome = 'consultas-eventos-' + new Date().toISOString().slice(0, 10);
  if (fmt === 'csv') exportRowsAsCsv(COLS_EVENTOS, linhas, nome);
  else               exportRowsAsExcel(COLS_EVENTOS, linhas, nome);
});

function renderLinhasEventos(linhas, filtrado = false) {
  const tbody = document.getElementById('tbody-eventos');
  const count = document.getElementById('eventos-count');
  if (!linhas || !linhas.length) {
    count.textContent = filtrado ? '0 deals' : '';
    tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;padding:32px;color:var(--muted)">${filtrado ? 'Nenhum deal encontrado para essa busca.' : 'Nenhum deal encontrado no período.'}</td></tr>`;
    return;
  }
  const sufixo = (!filtrado && linhas.length >= 5000) ? '+' : '';
  count.textContent = linhas.length + sufixo + ' deals';
  tbody.innerHTML = linhas.map(r => `
    <tr>
      <td class="cell-clip" title="${escHtml(r.nome_card ?? '')}">${escHtml(r.nome_card ?? '—')}</td>
      <td class="cell-clip clip-email" title="${escHtml(r.email ?? '')}">${escHtml(r.email ?? '—')}</td>
      <td>${escHtml(r.comercial2 ?? '—')}</td>
      <td>${escHtml(r.data_consulta_realizada ?? '—')}</td>
      <td>${faseBadge(r.fase_evento)}</td>
      <td>${escHtml(r.data_venda_evento ?? '—')}</td>
      <td class="cell-clip clip-form" title="${escHtml(r.form ?? '')}">${escHtml(r.form ?? '—')}</td>
      <td class="cell-clip" title="${escHtml(r.evento_label ?? '')}">${escHtml(r.evento_label ?? '—')}</td>
      <td>${bitrixCell(r.id_bitrix_c2, r.link_bitrix_c2)}</td>
      <td>${bitrixCell(r.id_bitrix_evento, r.link_bitrix_evento)}</td>
    </tr>
  `).join('');
}

// ══ TABELA "POR FORMULÁRIO" ══════════════════════════════════════
//
// Realizadas sai do que já está carregado (ultimasLinhasEventos): assim a soma
// da tabela de cima sempre fecha com a de baixo e o clique filtra exatamente o
// mesmo conjunto. Vendidas vem do endpoint, porque conta cards de evento que
// não têm consulta realizada e por isso não estão na tabela de registros.
//
// Agrupa por formulário do registro — que o backend já resolve como "o do card
// de evento, ou o do C5 quando não houve cruzamento". Por isso um C5 sem card
// de evento continua aparecendo sob o formulário dele: filtrar por um form tem
// que trazer toda consulta realizada daquele form, tenha ela cruzado ou não.
//
// Sobra um balde só, pros registros sem formulário em lugar nenhum. Quem quiser
// ver especificamente os que não cruzaram usa o filtro "Sem evento" do select
// de fase, que continua existindo.
const SEM_FORM = '__sem_form__';
const ROTULO_BALDE = { [SEM_FORM]: '(sem formulário)' };

const COLS_FORMS = [
  { k: 'form',       label: 'Formulário',           type: 'text', sortable: true },
  { k: 'realizadas', label: 'Consultas Realizadas', type: 'num',  sortable: true },
  { k: 'vendidas',   label: 'Consultas Vendidas',   type: 'num',  sortable: true },
];

let linhasForms     = [];
let formSelecionado = null;   // chave do form filtrado, ou null
let vendidasPorForm = {};
let _sortKeyForms   = 'realizadas';
let _sortDirForms   = 'desc';

function montarLinhasForms() {
  const porForm = new Map();
  for (const r of ultimasLinhasEventos) {
    const chave = r.form || SEM_FORM;
    porForm.set(chave, (porForm.get(chave) || 0) + 1);
  }

  // Form que vendeu no período mas não teve nenhuma consulta realizada precisa
  // aparecer com realizadas 0 — é justamente o caso que a tabela existe pra
  // mostrar, e ele não está em ultimasLinhasEventos.
  for (const form of Object.keys(vendidasPorForm)) {
    const chave = form || SEM_FORM;
    if (!porForm.has(chave)) porForm.set(chave, 0);
  }

  linhasForms = [...porForm.entries()].map(([chave, realizadas]) => {
    const vendidas = vendidasPorForm[chave === SEM_FORM ? '' : chave] ?? 0;
    return {
      chave,
      form: ROTULO_BALDE[chave] ?? chave,
      realizadas,
      vendidas,
      balde: chave in ROTULO_BALDE,
    };
  });

  renderHeadForms();
  renderLinhasForms();
}

function sortByForms(key) {
  const col = COLS_FORMS.find(c => c.k === key);
  if (!col || !col.sortable) return;
  if (_sortKeyForms === key) {
    _sortDirForms = _sortDirForms === 'asc' ? 'desc' : 'asc';
  } else {
    _sortKeyForms = key;
    _sortDirForms = col.type === 'num' ? 'desc' : 'asc';
  }
  renderHeadForms();
  renderLinhasForms();
}

function ordenarForms(linhas) {
  const col = COLS_FORMS.find(c => c.k === _sortKeyForms);
  const dir = _sortDirForms === 'asc' ? 1 : -1;
  return linhas.slice().sort((a, b) => {
    // Os baldes ficam sempre no fim, qualquer que seja a ordenação.
    if (a.balde !== b.balde) return a.balde ? 1 : -1;
    const av = a[_sortKeyForms], bv = b[_sortKeyForms];
    if (av === null && bv === null) return 0;
    if (av === null) return 1;
    if (bv === null) return -1;
    if (col.type === 'num') return (av - bv) * dir;
    return String(av).toLowerCase().localeCompare(String(bv).toLowerCase()) * dir;
  });
}

function renderHeadForms() {
  document.getElementById('thead-forms').innerHTML = '<tr>' + COLS_FORMS.map(c => {
    const active = c.k === _sortKeyForms;
    const arrow  = active ? (_sortDirForms === 'asc' ? ' ▲' : ' ▼') : ' ▲▼';
    const cls    = [active ? 'th-active' : '', c.type === 'num' ? 'num' : ''].filter(Boolean).join(' ');
    // Cada métrica é medida na sua própria coluna de data, então as duas
    // colunas não são coorte uma da outra e dividir uma pela outra não
    // significa nada — é por isso que não existe coluna de %.
    const TIP = {
      realizadas: 'Consultas realizadas do Comercial 2 com este formulário, tenham elas cruzado com um card de evento ou não. Recorte pela data da consulta realizada.',
      vendidas:   'Fase Won com data de venda preenchida. Recorte pela DATA DA VENDA, não pela data da consulta — um evento costuma vender tudo num dia e ter as consultas nos meses seguintes. Também não é afetada pelo filtro de Vendedor, que incide sobre o card do Comercial 2.',
    };
    const tip = TIP[c.k] || 'Clique para ordenar';
    return `<th class="${cls}" title="${tip}" tabindex="0" role="button" onclick="sortByForms('${c.k}')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();sortByForms('${c.k}')}">${c.label}${arrow}</th>`;
  }).join('') + '</tr>';
}

function renderLinhasForms() {
  const tbody  = document.getElementById('tbody-forms');
  const count  = document.getElementById('forms-count');
  const linhas = ordenarForms(linhasForms);

  count.textContent = linhas.filter(l => !l.balde).length + ' formulários';

  if (!linhas.length) {
    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:24px;color:var(--muted)">Nenhum formulário no período.</td></tr>';
    return;
  }

  tbody.innerHTML = linhas.map(l => {
    const sel = l.chave === formSelecionado ? ' tr-sel' : '';
    const bal = l.balde ? ' tr-balde' : '';
    const arg = escHtml(JSON.stringify(l.chave));
    return `
    <tr class="${sel}${bal}" onclick="selecionarFormulario(${arg})" title="Filtrar a tabela abaixo por ${escHtml(l.form)}">
      <td class="cell-clip" title="${escHtml(l.form)}">${escHtml(l.form)}</td>
      <td class="num">${l.realizadas}</td>
      <td class="num">${l.vendidas}</td>
    </tr>`;
  }).join('');
}

// Clicar na linha já selecionada desmarca — é o jeito de voltar pra "todos" sem
// precisar mirar o X do chip.
function selecionarFormulario(chave) {
  formSelecionado = (chave === formSelecionado) ? null : chave;

  const chip = document.getElementById('form-chip');
  const nome = document.getElementById('form-chip-nome');
  if (formSelecionado === null) {
    chip.classList.remove('on');
  } else {
    const l = linhasForms.find(x => x.chave === formSelecionado);
    nome.textContent = 'Formulário: ' + (l ? l.form : formSelecionado);
    nome.title = nome.textContent;
    chip.classList.add('on');
  }

  renderLinhasForms();
  filtrarEventos();
}

// ── Badge ────────────────────────────────────────────────────────
function updateBadge(year, months, comercial) {
  const badge = document.getElementById('filter-label-badge');
  if (!year && !months && !comercial) {
    badge.textContent = 'TODOS OS PERÍODOS';
    return;
  }
  const parts = [];
  if (year)   parts.push(year);
  if (months) {
    const labels = months.split(',').map(n => MONTHS[Number(n) - 1]);
    parts.push(labels.join(', '));
  }
  if (comercial) parts.push(comercial);
  badge.textContent = parts.join(' · ').toUpperCase();
}

// ── Carregar dados ────────────────────────────────────────────────
async function loadDashboard(year, months, comercial) {
  const overlay = document.getElementById('loading-overlay');
  const errBan  = document.getElementById('error-banner');
  overlay.classList.remove('hidden');
  document.body.classList.add('loading');
  errBan.style.display = 'none';
  errBan.classList.remove('visible');

  let url = API_URL;
  const params = new URLSearchParams();
  if (year)      params.set('year', year);
  if (months)    params.set('months', months);
  if (comercial) params.set('comercial', comercial);
  if ([...params].length) url += '?' + params.toString();

  try {
    const res = await fetch(url, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    if (!responsaveisCarregados && Array.isArray(data.responsaveis) && data.responsaveis.length) {
      comercialDropdown.load(data.responsaveis);
      responsaveisCarregados = true;
    }

    // Dados novos: a seleção anterior pode nem existir mais no período que
    // acabou de chegar, então volta pra "todos os formulários".
    vendidasPorForm = data.vendidas_por_form || {};
    formSelecionado = null;
    document.getElementById('form-chip').classList.remove('on');

    fillEventos(data.linhas);
    montarLinhasForms();

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
  const year      = yearDropdown.getValue();
  const months    = monthDropdown.getValue();
  const comercial = comercialDropdown.getValue();
  updateBadge(year, months, comercial);
  loadDashboard(year, months, comercial);
}

function clearFilters() {
  yearDropdown.reset();
  monthDropdown.reset();
  comercialDropdown.reset();
  yearDropdown.select([NOW_YEAR]);
  monthDropdown.select([String(NOW_MONTH)]);
  updateBadge(NOW_YEAR, String(NOW_MONTH), null);
  loadDashboard(NOW_YEAR, String(NOW_MONTH), null);
}

applyFilters();
</script>
