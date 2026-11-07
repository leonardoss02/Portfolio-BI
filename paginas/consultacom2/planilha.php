<div class="dash-consultacom2">

<style>
.dash-consultacom2 #thead-planilha th {
  cursor: pointer;
  user-select: none;
}
.dash-consultacom2 #thead-planilha th:hover,
.dash-consultacom2 #thead-planilha th.th-active {
  background: var(--border);
  color: var(--text);
}
.dash-consultacom2 #thead-planilha th.th-nosort {
  cursor: default;
}
.dash-consultacom2 #thead-planilha th.th-nosort:hover {
  background: var(--card2);
  color: var(--muted);
}
.dash-consultacom2 .bx-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: var(--consulta);
  font-weight: 600;
  text-decoration: none;
  font-size: 12.5px;
}
.dash-consultacom2 .bx-link:hover {
  text-decoration: underline;
}
.dash-consultacom2 .st-badge {
  display: inline-block;
  padding: 2px 9px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 600;
  white-space: nowrap;
}
.dash-consultacom2 .st-ok       { color: var(--green); background: var(--green-glow); }
.dash-consultacom2 .st-pendente { color: var(--amber); background: var(--amber-glow); }
.dash-consultacom2 .st-ok-nome   { color: var(--consulta); background: var(--consulta-glow); }
.dash-consultacom2 .td-obs      { color: var(--muted); }
/* Largura: com 9 colunas o table-layout automatico dava ~470px so pro e-mail
   e empurrava Fase Bitrix e o link pra fora da tela. Datas e rotulos em uma
   linha so, e nome/e-mail cortados com reticencias (valor inteiro no title). */
.dash-consultacom2 .tbl-wrap { overflow-x: auto; }
.dash-consultacom2 .bi-table td { white-space: nowrap; }
/* Cabecalho pode quebrar em duas linhas: o nowrap global do .bi-table th fazia
   "CONSULTA AGENDADA" sozinho reservar ~200px, mais que a data que ele rotula. */
.dash-consultacom2 .bi-table th { white-space: normal; }
.dash-consultacom2 .bi-table td.cell-clip {
  max-width: 230px;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>

<!-- ── LOADING ─────────────────────────────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>

<!-- ── HEADER ─────────────────────────────────────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo">C2</div>
    <div>
      <div class="header-title">Planilha do time</div>
      <div class="header-sub">Clientes conferidos na planilha &middot; cruzamento com a aba Consultas, por e-mail e, se não achar, por nome</div>
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
  <div class="header-badge" id="filter-label-badge">TODOS</div>
</header>

<!-- ── FILTROS ─────────────────────────────────────────────────── -->
<section class="filters is-collapsible" aria-label="Filtros do painel">
  <button class="filters-toggle" type="button" aria-expanded="true" aria-controls="filters-fields">
    <span>Filtros</span>
    <span class="dd-caret filters-toggle-caret">&#x25BE;</span>
  </button>
  <div class="filters-fields" id="filters-fields">

  <!-- Vendedor (coluna "Nome Comercial" da planilha) -->
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
<script>initFiltersToggle({ key: 'consultacom2-planilha' });</script>

<!-- ── ERRO ───────────────────────────────────────────────────── -->
<div id="error-banner" role="alert"></div>

<!-- ── CONTEÚDO PRINCIPAL ──────────────────────────────────────── -->
<main class="main" id="dashboard-main">

  <div class="bi-card">
    <div class="bi-card-header" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">
      <span class="bi-card-title">Planilha</span>
      <div style="display:flex;align-items:center;gap:10px">
        <input
          type="text"
          id="planilha-search"
          placeholder="Buscar em qualquer coluna..."
          autocomplete="off"
          oninput="filtrarPlanilha()"
          style="height:30px;padding:0 10px;border-radius:var(--r-sm,6px);border:1px solid var(--border);background:var(--card2);color:var(--text);font-family:var(--font-ui);font-size:12.5px;outline:none;min-width:240px"
        >
        <span class="bi-card-sub" id="planilha-count"></span>
      </div>
    </div>
    <div class="tbl-wrap" style="max-height:640px;overflow:auto">
      <table class="bi-table">
        <thead id="thead-planilha"></thead>
        <tbody id="tbody-planilha">
          <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--muted)">Carregando…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="last-updated" id="last-updated"></div>

</main>
</div>

<script>
// ══════════════════════════════════════════════════════════════
//  ConsultaCom2 · aba Planilha — Dashboard JS
//  Consome: consultacom2/dadosplanilha (controller MRPHP)
//
//  As linhas vêm da planilha do time no Google Sheets. "Status" responde a
//  uma pergunta só: este cliente aparece na aba Consultas? Ou seja, tem deal
//  em fase C5, com data de consulta realizada preenchida, sob um vendedor do
//  Comercial 2.
//
//    OK        casou pelo e-mail
//    OK (nome) só o nome casou — evidência mais fraca, o e-mail do CRM diverge
//              (em geral digitação errada); o tooltip mostra qual é
//    Pendente  não achou de nenhum dos dois jeitos
//
//  O link do Bitrix nao depende do status: quem fica Pendente mas tem card em
//  C5 no CRM ganha o link mesmo assim, e o tooltip diz por que nao entrou na
//  aba Consultas (card sem data de realizacao, ou vendedor de fora do time).
//
//  createMultiDropdown é fornecida por js/bi-dashboard.js (carregado no
//  <head> pelo template, antes deste script).
// ══════════════════════════════════════════════════════════════

const API_URL = '<?= $this->site ?>consultacom2/dadosplanilha';
const EQUIPE  = ['Rafael Torres', 'Beatriz Lemos', 'Camila Duarte', 'Juliana Prado', 'Gustavo Leal'];

const comercialDropdown = createMultiDropdown('comercial-wrap', 'comercial-btn', 'comercial-list', EQUIPE, l => l, l => l);

function escHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// Tres estados: OK (casou pelo e-mail), OK (nome) (fallback — o e-mail do CRM
// diverge do da planilha, normalmente digitacao errada) e Pendente. Status null
// = planilha indisponivel, ai mostra "—" em vez de "Pendente", que seria falso.
function statusBadge(v, detalhe) {
  if (!v) return '—';
  const cls = v === 'OK' ? 'st-ok' : (v === 'Pendente' ? 'st-pendente' : 'st-ok-nome');
  const tip = detalhe ? ` title="${escHtml(detalhe)}"` : '';
  return `<span class="st-badge ${cls}"${tip}>${escHtml(v)}</span>`;
}

// ── Colunas da tabela (ordenação por clique-no-cabeçalho) ────────
// Duas datas lado a lado: a da planilha é 'text' porque vem como "seg.,
// 03/ago." (sem ano) e converter seria adivinhar; a do CRM é 'date' de
// verdade (dd/mm/aaaa), então ordena cronologicamente.
const COLS_PLANILHA = [
  { k: 'nome_cliente',      label: 'Nome do Cliente',     type: 'text', sortable: true  },
  { k: 'email',             label: 'E-mail',              type: 'text', sortable: true  },
  { k: 'data_consulta',     label: 'Consulta (Planilha)', type: 'text', sortable: true  },
  { k: 'data_consulta_crm', label: 'Consulta (CRM)',      type: 'date', sortable: true  },
  { k: 'nome_comercial',    label: 'Nome Comercial',      type: 'text', sortable: true  },
  { k: 'obs',               label: 'OBS',                 type: 'text', sortable: true  },
  { k: 'status',            label: 'Status',              type: 'text', sortable: true  },
  { k: 'link_bitrix',       label: 'Bitrix',              type: 'text', sortable: false },
];

let ultimasLinhasPlanilha = [];
let _sortKeyPlanilha = null;
let _sortDirPlanilha = 'asc';

function sortByPlanilha(key) {
  const col = COLS_PLANILHA.find(c => c.k === key);
  if (!col || !col.sortable) return;
  if (_sortKeyPlanilha === key) {
    _sortDirPlanilha = _sortDirPlanilha === 'asc' ? 'desc' : 'asc';
  } else {
    _sortKeyPlanilha = key;
    _sortDirPlanilha = 'asc';
  }
  renderHeadPlanilha();
  filtrarPlanilha();
}

function sortValuePlanilha(v, type) {
  if (v === null || v === undefined || v === '') return null;
  if (type === 'date') {
    const [d, m, y] = String(v).split('/');
    return y + m.padStart(2, '0') + d.padStart(2, '0');
  }
  return String(v).toLowerCase();
}

function ordenarPlanilha(linhas) {
  if (!_sortKeyPlanilha) return linhas;
  const col = COLS_PLANILHA.find(c => c.k === _sortKeyPlanilha);
  const dir = _sortDirPlanilha === 'asc' ? 1 : -1;
  return linhas.slice().sort((a, b) => {
    const av = sortValuePlanilha(a[_sortKeyPlanilha], col && col.type);
    const bv = sortValuePlanilha(b[_sortKeyPlanilha], col && col.type);
    if (av === null && bv === null) return 0;
    if (av === null) return 1;
    if (bv === null) return -1;
    if (av < bv) return -1 * dir;
    if (av > bv) return 1 * dir;
    return 0;
  });
}

function renderHeadPlanilha() {
  document.getElementById('thead-planilha').innerHTML = '<tr>' + COLS_PLANILHA.map(c => {
    const active = c.k === _sortKeyPlanilha;
    const arrow  = c.sortable ? (active ? (_sortDirPlanilha === 'asc' ? ' ▲' : ' ▼') : ' ▲▼') : '';
    const cls    = [active ? 'th-active' : '', c.sortable ? '' : 'th-nosort'].filter(Boolean).join(' ');
    return `<th class="${cls}" title="${c.sortable ? 'Clique para ordenar' : ''}" tabindex="0" role="button" aria-label="${c.sortable ? 'Ordenar por ' + c.label : c.label}" onclick="sortByPlanilha('${c.k}')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();sortByPlanilha('${c.k}')}">${c.label}${arrow}</th>`;
  }).join('') + '</tr>';
}
renderHeadPlanilha();

function fillPlanilha(linhas) {
  ultimasLinhasPlanilha = linhas || [];
  const searchInput = document.getElementById('planilha-search');
  if (searchInput) searchInput.value = '';
  renderLinhasPlanilha(ordenarPlanilha(ultimasLinhasPlanilha));
}

// ── Busca client-side em todas as colunas (as linhas já vêm carregadas) ─
function filtrarPlanilha() {
  const termo = (document.getElementById('planilha-search').value || '').trim().toLowerCase();
  const base = termo
    ? ultimasLinhasPlanilha.filter(r => [
        r.nome_cliente, r.email, r.data_consulta, r.data_consulta_crm,
        r.nome_comercial, r.obs, r.status, r.status_detalhe,
      ].filter(Boolean).join(' ').toLowerCase().includes(termo))
    : ultimasLinhasPlanilha;
  renderLinhasPlanilha(ordenarPlanilha(base), !!termo);
}

function renderLinhasPlanilha(linhas, filtrado = false) {
  const tbody = document.getElementById('tbody-planilha');
  const count = document.getElementById('planilha-count');
  if (!linhas || !linhas.length) {
    count.textContent = filtrado ? '0 linhas' : '';
    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--muted)">${filtrado ? 'Nenhuma linha encontrada para essa busca.' : 'Nenhuma linha na planilha.'}</td></tr>`;
    return;
  }
  const ok      = linhas.filter(r => r.status === 'OK').length;
  const porNome = linhas.filter(r => r.status && r.status !== 'OK' && r.status !== 'Pendente').length;
  const pend    = linhas.length - ok - porNome;
  count.textContent = `${linhas.length} linhas · ${ok} OK`
    + (porNome ? ` · ${porNome} por nome` : '')
    + ` · ${pend} pendentes`;
  tbody.innerHTML = linhas.map(r => `
    <tr>
      <td class="cell-clip" title="${escHtml(r.nome_cliente || '')}">${escHtml(r.nome_cliente || '—')}</td>
      <td class="cell-clip" title="${escHtml(r.email || '')}">${escHtml(r.email || '—')}</td>
      <td>${escHtml(r.data_consulta || '—')}</td>
      <td>${escHtml(r.data_consulta_crm || '—')}</td>
      <td>${escHtml(r.nome_comercial || '—')}</td>
      <td class="td-obs cell-clip" title="${escHtml(r.obs || '')}">${escHtml(r.obs || '—')}</td>
      <td>${statusBadge(r.status, r.status_detalhe)}</td>
      <td>${r.link_bitrix ? `<a class="bx-link" href="${escHtml(r.link_bitrix)}" target="_blank" rel="noopener">Abrir ↗</a>` : '—'}</td>
    </tr>
  `).join('');
}

// ── Badge ────────────────────────────────────────────────────────
function updateBadge(comercial) {
  document.getElementById('filter-label-badge').textContent =
    comercial ? comercial.toUpperCase() : 'TODOS';
}

// ── Carregar dados ────────────────────────────────────────────────
async function loadDashboard(comercial) {
  const overlay = document.getElementById('loading-overlay');
  const errBan  = document.getElementById('error-banner');
  overlay.classList.remove('hidden');
  document.body.classList.add('loading');
  errBan.style.display = 'none';
  errBan.classList.remove('visible');

  let url = API_URL;
  const params = new URLSearchParams();
  if (comercial) params.set('comercial', comercial);
  if ([...params].length) url += '?' + params.toString();

  try {
    const res = await fetch(url, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    // Sem a planilha não há o que listar — avisa em vez de mostrar tabela vazia
    // como se ela realmente não tivesse linhas.
    if (data.planilha_indisponivel) {
      throw new Error('não foi possível ler a planilha do time (verifique o compartilhamento do link).');
    }

    fillPlanilha(data.linhas);

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
  const comercial = comercialDropdown.getValue();
  updateBadge(comercial);
  loadDashboard(comercial);
}

function clearFilters() {
  comercialDropdown.reset();
  updateBadge(null);
  loadDashboard(null);
}

applyFilters();
</script>
