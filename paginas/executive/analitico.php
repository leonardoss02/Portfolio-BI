<div class="dash-analitico">

<div id="loading-overlay" class="hidden"></div>

<div class="page">

  <!-- HEADER -->
  <div class="header">
    <div class="header-left">
      <div class="logo">MR</div>
      <div>
        <div class="header-title">Analítico por Especialista</div>
        <div class="header-sub">Realizadas · Combos · Conversão · por Frente e AnoMês</div>
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
      <div class="last-updated" id="last-updated">—</div>
      <div class="tab-switch">
        <a class="tab-switch-btn" href="<?= $this->site ?>executive<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Executive</a>
        <a class="tab-switch-btn" href="<?= $this->site ?>executive/digital<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Digital &times; Evento</a>
        <a class="tab-switch-btn" href="<?= $this->site ?>executive/especialistas<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Especialistas</a>
        <a class="tab-switch-btn active" href="<?= $this->site ?>executive/analitico<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Analítico</a>
        <a class="tab-switch-btn" href="<?= $this->site ?>executive/serasa<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Serasa</a>
        <a class="tab-switch-btn" href="<?= $this->site ?>executive/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
      </div>
    </div>
  </div>

  <!-- FILTER BAR -->
  <div class="filter-bar is-collapsible">
    <button class="filters-toggle" type="button" aria-expanded="true" aria-controls="filters-fields">
      <span>Filtros</span>
      <span class="dd-caret filters-toggle-caret">&#x25BE;</span>
    </button>
    <div class="filters-fields" id="filters-fields">

    <!-- Especialista -->
    <div class="filter-pair">
      <span class="filter-label">Especialista</span>
      <div class="multi-dd-wrap" id="esp-wrap">
        <button class="multi-dd-btn" id="esp-btn" type="button">Todos <span class="dd-caret">▾</span></button>
        <div class="multi-dd-list" id="esp-list"></div>
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
      <div class="multi-dd-wrap" id="form-wrap">
        <button class="multi-dd-btn" id="form-btn" type="button">Todos <span class="dd-caret">▾</span></button>
        <div class="multi-dd-list" id="form-list"></div>
      </div>
    </div>

    <!-- Campanha -->
    <div class="filter-pair">
      <span class="filter-label">Campanha</span>
      <div class="multi-dd-wrap" id="camp-wrap">
        <button class="multi-dd-btn" id="camp-btn" type="button">Todos <span class="dd-caret">▾</span></button>
        <div class="multi-dd-list" id="camp-list"></div>
      </div>
    </div>

    <!-- Responsável -->
    <div class="filter-pair">
      <span class="filter-label">Responsável</span>
      <div class="camp-wrap" id="resp-wrap">
        <div class="camp-input-row">
          <span class="camp-icon">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
          </span>
          <input id="resp-input" class="camp-input" type="text" placeholder="Pesquisar responsável..." autocomplete="off">
          <button class="camp-clear" id="resp-clear" tabindex="-1">&#x2715;</button>
        </div>
        <div class="camp-tags" id="resp-tags"></div>
        <div class="camp-dropdown" id="resp-dropdown"></div>
      </div>
    </div>

    <button class="btn-apply" onclick="applyFilters()">Aplicar</button>
    <button class="btn-clear"  onclick="clearFilters()">Limpar</button>

    </div>
  </div>
  <script>initFiltersToggle({ key: 'executive-analitico' });</script>

  <!-- FRENTE HEADER -->
  <div class="frente-header">
    <div class="frente-badge frente-ev">Evento</div>
    <div class="frente-badge frente-dg">Digital</div>
    <div class="frente-badge frente-tot">Total</div>
  </div>

  <!-- CONTEÚDO PRINCIPAL -->
  <div id="main-content"></div>

</div>

<script>
const API_URL = '<?= $this->site ?>executive/analiticoDados';
const MONTHS  = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const YEARS   = anosDesde(2025);

// ── Instanciar dropdowns ──────────────────────────────────────
const ESPECIALISTAS_LIST = ['Rafael Torres', 'Paulo Nogueira', 'Camila Duarte', 'Juliana Prado', 'Beatriz Lemos', 'Ricardo Albuquerque'];
const espDropdown    = createMultiDropdown('esp-wrap',   'esp-btn',   'esp-list',   ESPECIALISTAS_LIST, l => l, l => l);
const yearDropdown   = createMultiDropdown('year-wrap',  'year-btn',  'year-list',  YEARS,  l => l, l => l);
const monthDropdown  = createMultiDropdown('month-wrap', 'month-btn', 'month-list', MONTHS, l => l, (l,i) => i+1);

const NOW_YEAR  = String(new Date().getFullYear());
yearDropdown.select([NOW_YEAR]);

const campDropdown  = createMultiDropdown('camp-wrap', 'camp-btn', 'camp-list', [], l => l, l => l);
const formDropdown  = createMultiDropdown('form-wrap', 'form-btn', 'form-list', [], l => l, l => l);
const respDropdown  = createSearchDropdown('resp-wrap', 'resp-input', 'resp-dropdown', 'resp-clear', 'resp-tags');

// ── Carregar opções dos filtros ───────────────────────────────
async function loadFiltros() {
  try {
    const [campanhas, forms, resps] = await Promise.all([
      fetch('<?= $this->site ?>executive/campanhas').then(r => r.json()),
      fetch('<?= $this->site ?>executive/forms').then(r => r.json()),
      fetch('<?= $this->site ?>executive/responsaveis').then(r => r.json()),
    ]);
    campDropdown.load(campanhas.map ? campanhas.map(c => c.nome ?? c) : campanhas);
    formDropdown.load(forms.map    ? forms.map(f => f.nome ?? f)     : forms);
    respDropdown.load(resps.map    ? resps.map(r => r.nome ?? r)     : resps);
  } catch (e) {
    console.warn('Não foi possível carregar opções de filtros:', e);
  }
}

// ── Filtros ───────────────────────────────────────────────────
function applyFilters() {
  const year       = yearDropdown.getValue();
  const months     = monthDropdown.getValue();
  const dataInicio = getDateInput('data-inicio');
  const dataFim    = getDateInput('data-fim');
  if (!dataInicio && !dataFim) {
    const eff = computeEffectiveDates(year, months);
    setEffectiveDate('data-inicio', eff.start);
    setEffectiveDate('data-fim', eff.end);
  }
  const apiInicio = dataInicio || document.getElementById('data-inicio')?.value || null;
  const apiFim    = dataFim    || document.getElementById('data-fim')?.value    || null;
  updateBadge(espDropdown.getValue(), year, months, campDropdown.getValue(), formDropdown.getValue(), respDropdown.getValue());
  loadData(
    year,
    months,
    campDropdown.getValue(),
    formDropdown.getValue(),
    respDropdown.getValue(),
    apiInicio,
    apiFim
  );
}

function clearFilters() {
  espDropdown.reset();
  yearDropdown.reset();
  monthDropdown.reset();
  campDropdown.reset();
  formDropdown.reset();
  respDropdown.reset();
  clearDateInput('data-inicio', 'data-inicio-clear');
  clearDateInput('data-fim',    'data-fim-clear');
  updateBadge(null, null, null, null, null, null);
  loadData(null, null, null, null, null, null, null);
}

// ── Badge do período selecionado ──────────────────────────────
function updateBadge(especialista, year, months, campanha, form, responsavel) {
  const badge = document.getElementById('filter-label-badge');
  if (!especialista && !year && !months && !campanha && !form && !responsavel) {
    badge.textContent = 'TODOS OS PERÍODOS';
    return;
  }
  const parts = [];
  if (especialista) parts.push('Esp: ' + especialista);
  if (responsavel)  parts.push('Resp: ' + responsavel);
  if (form)         parts.push('Form: ' + form);
  if (campanha)     parts.push(campanha);
  if (year)         parts.push(year);
  if (months) {
    const nums   = months.split(',').map(Number);
    const labels = nums.map(n => MONTHS[n - 1]);
    parts.push(labels.join(', '));
  }
  badge.textContent = parts.join(' · ').toUpperCase();
}

// ── Render ────────────────────────────────────────────────────
function renderMiniTable(rows, totais, theadClass) {
  if (!rows || rows.length === 0) {
    return `<div class="mini-empty">Sem dados para o período</div>`;
  }

  const tbody = rows.map((r, i) => `
    <tr class="${i % 2 === 0 ? 'row-odd' : 'row-even'}">
      <td class="td-period">${r.periodo}</td>
      <td class="td-num">${fNum(r.realizadas)}</td>
      <td class="td-green">${fNum(r.combos)}</td>
      <td class="td-amber">${fPct(r.conversao)}</td>
    </tr>`).join('');

  const tfoot = `<tr>
    <td class="tfoot-label">Total</td>
    <td class="td-num">${fNum(totais.realizadas)}</td>
    <td class="td-green">${fNum(totais.combos)}</td>
    <td class="td-amber">${fPct(totais.conversao)}</td>
  </tr>`;

  return `<table class="mini-table">
    <thead><tr class="${theadClass}">
      <th>AnoMês</th><th>Realizadas</th><th>Combos</th><th>Conversão</th>
    </tr></thead>
    <tbody>${tbody}</tbody>
    <tfoot>${tfoot}</tfoot>
  </table>`;
}

function renderContent(especialistas) {
  const content = document.getElementById('main-content');

  const espSel     = espDropdown.getValue();
  const espList    = espSel ? espSel.split(',') : null;
  const displayed  = espList ? especialistas.filter(e => espList.includes(e.nome)) : especialistas;

  if (!displayed || displayed.length === 0) {
    content.innerHTML = '<div class="error-box">Nenhum dado encontrado para o período selecionado.</div>';
    return;
  }

  content.innerHTML = displayed.map((esp, idx) => `
    ${idx > 0 ? '<div class="esp-divider"></div>' : ''}
    <div class="esp-section">
      <div class="esp-name-row">${esp.nome}</div>
      <div class="esp-grid">
        <div class="mini-card">${renderMiniTable(esp.frentes.Evento,  esp.totais.Evento,  'thead-ev')}</div>
        <div class="mini-card">${renderMiniTable(esp.frentes.Digital, esp.totais.Digital, 'thead-dg')}</div>
        <div class="mini-card">${renderMiniTable(esp.frentes.Total,   esp.totais.Total,   'thead-tot')}</div>
      </div>
    </div>`).join('');
}

// ── Fetch ─────────────────────────────────────────────────────
async function loadData(year, months, campanha, form, responsavel, dataInicio, dataFim) {
  const overlay = document.getElementById('loading-overlay');
  overlay?.classList.remove('hidden');
  document.body.classList.add('loading');

  try {
    const params = new URLSearchParams();
    if (year)        params.set('year',        year);
    if (months)      params.set('months',      months);
    if (campanha)    params.set('campanha',    campanha);
    if (form)        params.set('form',        form);
    if (responsavel) params.set('responsavel', responsavel);
    if (dataInicio)  params.append('data_inicio', dataInicio);
    if (dataFim)     params.append('data_fim',    dataFim);

    const text = await fetch(`${API_URL}?${params}`).then(r => r.text());

    let data;
    try { data = JSON.parse(text); }
    catch (e) { throw new Error('Resposta inválida:\n' + text.substring(0, 300)); }

    renderContent(data.especialistas);

    if (data.meta?.generated) {
      document.getElementById('last-updated').textContent =
        'Atualizado: ' + new Date(data.meta.generated.replace(' ','T')).toLocaleString('pt-BR');
    }
  } catch (err) {
    document.getElementById('main-content').innerHTML =
      `<div class="error-box">⚠️ Erro ao carregar dados: ${err.message}</div>`;
  } finally {
    overlay?.classList.add('hidden');
    document.body.classList.remove('loading');
  }
}

// ── Init ──────────────────────────────────────────────────────
loadFiltros();
applyFilters();

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
</script>

</div>
