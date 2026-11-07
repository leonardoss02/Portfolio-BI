/*
 * bi-dashboard.js — Acme-EUA BI · helpers compartilhados entre dashboards
 *
 * Deduplicado a partir das cópias idênticas (ou quase idênticas) presentes em
 * executive_view.html, digital_evento.html, especialistas.html e
 * especialistas_analitico.html (ver ExecutiveView/ na pasta Power Bi).
 *
 * Script clássico (sem módulos ES) — todas as funções abaixo são globais e
 * podem ser chamadas diretamente pelas views assim que este arquivo carregar.
 * Deve ser incluído como <script> normal (bloqueante), nunca defer/async,
 * pois os fragmentos de página chamam estas factories no parse inicial.
 *
 * NÃO incluído aqui (fica em cada view/página): construção de gráfico SVG,
 * sistema de tooltip de comparação de KPI (#kpi-tooltip/METRIC_META),
 * postMessage / toggle de tema (o tema agora vive uma única vez no template).
 */

// ── Multi-select dropdown (checkbox) ───────────────────────────────────────
// Une as duas variantes que existiam no projeto de origem: a versão "fixa" de
// executive_view.html (Ano/Mês, itens definidos na criação) e a versão
// "dinâmica" de digital_evento.html/especialistas.html/especialistas_analitico.html
// (Campanha/Form, populados depois via load(newItems) a partir de fetch()).
// Expor load() sempre é seguro — páginas que nunca chamam load() simplesmente
// não usam esse método, mantendo o comportamento idêntico ao de origem.
function createMultiDropdown(wrapId, btnId, listId, items, labelOf, valueOf) {
  const wrap     = document.getElementById(wrapId);
  const btn      = document.getElementById(btnId);
  const list     = document.getElementById(listId);
  const selected = new Set();
  const label0   = btn.childNodes[0]; // text node "Todos "

  function buildItems() {
    list.innerHTML = '';
    items.forEach((item, idx) => {
      const val   = String(valueOf(item, idx));
      const label = labelOf(item, idx);
      const el    = document.createElement('div');
      el.className = 'multi-dd-item' + (selected.has(val) ? ' selected' : '');
      el.innerHTML = '<span class="dd-check"></span>' + label;
      el.addEventListener('mousedown', e => {
        e.preventDefault();
        if (selected.has(val)) { selected.delete(val); el.classList.remove('selected'); }
        else                   { selected.add(val);    el.classList.add('selected'); }
        updateBtn();
      });
      list.appendChild(el);
    });
  }

  buildItems();

  function updateBtn() {
    if (selected.size === 0) {
      label0.textContent = 'Todos ';
      btn.classList.remove('has-value');
    } else {
      const labels = items
        .filter((item, idx) => selected.has(String(valueOf(item, idx))))
        .map(labelOf);
      label0.textContent = (labels.length > 2 ? labels.length + ' selecionados' : labels.join(', ')) + ' ';
      btn.classList.add('has-value');
    }
  }

  btn.addEventListener('click', () => list.classList.contains('open') ? close() : open());
  document.addEventListener('click', e => { if (!wrap.contains(e.target)) close(); });
  function open()  { list.classList.add('open');    btn.classList.add('open'); }
  function close() { list.classList.remove('open'); btn.classList.remove('open'); }

  return {
    getValue() {
      if (!selected.size) return null;
      return [...selected].sort((a, b) => {
        const na = Number(a), nb = Number(b);
        return isNaN(na) || isNaN(nb) ? String(a).localeCompare(String(b)) : na - nb;
      }).join(',');
    },
    reset(silent = false) {
      selected.clear();
      list.querySelectorAll('.multi-dd-item').forEach(el => el.classList.remove('selected'));
      updateBtn(); close();
    },
    select(vals) {
      const valSet = new Set(vals.map(String));
      items.forEach((item, idx) => {
        const v = String(valueOf(item, idx));
        if (valSet.has(v)) {
          selected.add(v);
          list.querySelectorAll('.multi-dd-item')[idx]?.classList.add('selected');
        }
      });
      updateBtn();
    },
    load(newItems) {
      items = newItems;
      selected.clear();
      buildItems();
      updateBtn();
      close();
    },
  };
}

// ── Searchable multi-select dropdown (busca + tags removíveis) ─────────────
// Versão canônica: executive_view.html (assinatura com emptyMsg).
function createSearchDropdown(wrapId, inputId, dropdownId, clearId, tagsId, emptyMsg) {
  const wrap     = document.getElementById(wrapId);
  const input    = document.getElementById(inputId);
  const dropdown = document.getElementById(dropdownId);
  const clearBtn = document.getElementById(clearId);
  const tagsEl   = document.getElementById(tagsId);

  let options  = [];
  let selected = new Set();
  let focusIdx = -1;

  function renderTags() {
    tagsEl.innerHTML = '';
    selected.forEach(val => {
      const tag = document.createElement('span');
      tag.className = 'filter-tag';
      const txt = document.createTextNode(val + ' ');
      const rm  = document.createElement('button');
      rm.className = 'tag-remove';
      rm.setAttribute('aria-label', 'Remover ' + val);
      rm.textContent = '×';
      rm.addEventListener('click', e => {
        e.stopPropagation();
        selected.delete(val);
        renderTags(); updateClear();
      });
      tag.appendChild(txt);
      tag.appendChild(rm);
      tagsEl.appendChild(tag);
    });
  }

  function updateClear() {
    clearBtn.classList.toggle('visible', selected.size > 0 || input.value.length > 0);
  }

  function render(query) {
    const q = (query || '').toLowerCase().trim();
    const filtered = q ? options.filter(n => n.toLowerCase().includes(q)) : options;
    dropdown.innerHTML = '';
    focusIdx = -1;
    if (!filtered.length) {
      dropdown.innerHTML = `<div class="camp-empty">${emptyMsg}</div>`;
      return;
    }
    filtered.forEach(nome => {
      const el = document.createElement('div');
      el.className     = 'camp-opt' + (selected.has(nome) ? ' selected' : '');
      el.textContent   = nome;
      el.role          = 'option';
      el.dataset.value = nome;
      el.addEventListener('mousedown', e => { e.preventDefault(); toggle(nome); });
      dropdown.appendChild(el);
    });
  }

  function open()  { render(input.value); dropdown.classList.add('open'); }
  function close() { dropdown.classList.remove('open'); focusIdx = -1; }

  function toggle(nome) {
    if (selected.has(nome)) { selected.delete(nome); } else { selected.add(nome); }
    input.value = '';
    renderTags(); updateClear();
    render('');
  }

  function reset() {
    selected.clear();
    input.value = '';
    renderTags(); updateClear();
    dropdown.classList.remove('open');
  }

  input.addEventListener('focus', () => open());
  input.addEventListener('input', () => { updateClear(); render(input.value); dropdown.classList.add('open'); });
  input.addEventListener('keydown', e => {
    const items = dropdown.querySelectorAll('.camp-opt');
    if (!items.length) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      focusIdx = Math.min(focusIdx + 1, items.length - 1);
      items.forEach((el, i) => el.classList.toggle('focused', i === focusIdx));
      items[focusIdx]?.scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      focusIdx = Math.max(focusIdx - 1, 0);
      items.forEach((el, i) => el.classList.toggle('focused', i === focusIdx));
      items[focusIdx]?.scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (focusIdx >= 0 && items[focusIdx]) toggle(items[focusIdx].dataset.value);
    } else if (e.key === 'Escape') { close(); input.blur(); }
  });
  clearBtn.addEventListener('click', () => { reset(); input.focus(); });
  document.addEventListener('click', e => { if (!wrap.contains(e.target)) close(); });

  return {
    load(list) { options = list; },
    getValue()  { return selected.size > 0 ? [...selected].join(',') : null; },
    // Expostos pro drill-down por clique (ex.: linha de tabela de
    // agrupamento) — toggle() reaproveita a mesma seleção/tags do dropdown
    // de busca, getSelected() dá o Set bruto sem precisar re-parsear o CSV
    // de getValue().
    toggle,
    getSelected() { return [...selected]; },
    reset,
  };
}

// ── Date-effective helpers ──────────────────────────────────────────────────
// Versão canônica: executive_view.html.
function getDateInput(id) {
  const el = document.getElementById(id);
  if (!el || !el.value) return null;
  // Se o campo está em modo "exibição" (não é filtro ativo), ignora
  if (el.closest('.date-input-wrap')?.classList.contains('is-effective')) return null;
  return el.value;
}

function setEffectiveDate(id, isoDate) {
  const el   = document.getElementById(id);
  const wrap = el?.closest('.date-input-wrap');
  if (!el || !wrap) return;
  el.value = isoDate;
  wrap.classList.add('is-effective');
}

function clearDateInput(id, clearBtnId) {
  const el   = document.getElementById(id);
  const wrap = el?.closest('.date-input-wrap');
  if (el)   el.value = '';
  if (wrap) wrap.classList.remove('is-effective');
  document.getElementById(clearBtnId)?.classList.remove('visible');
}

// Ausente em executive_view.html — portado verbatim de digital_evento.html
// (implementação idêntica em especialistas.html e especialistas_analitico.html).
function computeEffectiveDates(year, months) {
  const today    = new Date();
  const todayStr = today.toISOString().slice(0, 10);
  const y        = year ? parseInt(year) : today.getFullYear();
  if (months) {
    const arr  = months.split(',').map(Number);
    const minM = Math.min(...arr), maxM = Math.max(...arr);
    const start = `${y}-${String(minM).padStart(2, '0')}-01`;
    const isNow = y === today.getFullYear() && maxM === today.getMonth() + 1;
    const end   = isNow ? todayStr : new Date(y, maxM, 0).toISOString().slice(0, 10);
    return { start, end };
  }
  return { start: `${y}-01-01`, end: y === today.getFullYear() ? todayStr : `${y}-12-31` };
}

// ── Período relativo "Últimos N [dias|semanas|meses]" ──────────────────────
// Espelha o slicer de data relativa do Power BI: janela corrida que termina
// hoje e inclui hoje. É resolvido aqui e enviado como data_inicio/data_fim —
// o backend não conhece o conceito. Datas montadas no horário local:
// toISOString() converte para UTC e, depois das 21h no Brasil, já devolve o
// dia seguinte.
function isoLocal(d) {
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

function computeRelativeRange(n, unit, today = new Date()) {
  n = parseInt(n, 10);
  if (!Number.isFinite(n) || n < 1) return null;
  const y = today.getFullYear(), m = today.getMonth(), d = today.getDate();
  let start;
  if (unit === 'dias')         start = new Date(y, m, d - (n - 1));
  else if (unit === 'semanas') start = new Date(y, m, d - (7 * n - 1));
  else if (unit === 'meses') {
    // hoje − N meses, com o dia limitado ao fim do mês (31/03 − 1 mês = 28/02), + 1 dia
    const ultimo = new Date(y, m - n + 1, 0).getDate();
    start = new Date(y, m - n, Math.min(d, ultimo) + 1);
  } else return null;
  return { start: isoLocal(start), end: isoLocal(new Date(y, m, d)) };
}

const REL_UNIDADES = {
  dias:    { um: 'Último dia',    varios: n => `Últimos ${n} dias` },
  semanas: { um: 'Última semana', varios: n => `Últimas ${n} semanas` },
  meses:   { um: 'Último mês',    varios: n => `Últimos ${n} meses` },
};

// Liga o grupo "Últimos [N] [unidade]" da barra de filtros. Um filtro
// substitui o outro: ativar o relativo zera os dropdowns de `conflicts`
// (Ano/Mês, pares [dropdown, listId]) e pinta o intervalo nos campos de
// Data em modo exibição (.is-effective, que getDateInput ignora); clicar
// num item desses dropdowns ou editar uma dessas datas desliga o relativo.
// O botão de limpar de cada data segue a convenção `{id}-clear`.
function createRelativePeriod({ numId, unitId, dateIds = ['data-inicio', 'data-fim'], conflicts = [] }) {
  const num   = document.getElementById(numId);
  const unit  = document.getElementById(unitId);
  const group = num.closest('.rel-period-group');

  const qtd = () => { const v = parseInt(num.value, 10); return Number.isFinite(v) && v >= 1 ? v : null; };

  function pintarDatas(range) {
    dateIds.forEach((id, i) => {
      const wrap = document.getElementById(id)?.closest('.date-input-wrap');
      if (range) {
        setEffectiveDate(id, i === 0 ? range.start : range.end);
        wrap?.classList.add('is-rel');
        document.getElementById(id + '-clear')?.classList.remove('visible');
      } else if (wrap?.classList.contains('is-rel')) {
        clearDateInput(id, id + '-clear');
        wrap.classList.remove('is-rel');
      }
    });
  }

  function sync() {
    const ativo = qtd() !== null;
    group?.classList.toggle('is-active', ativo);
    if (ativo) conflicts.forEach(([dd]) => dd.reset());
    pintarDatas(ativo ? computeRelativeRange(qtd(), unit.value) : null);
  }

  function desligar() {
    if (!num.value) return;
    num.value = '';
    sync();
  }

  num.addEventListener('input', sync);
  unit.addEventListener('change', sync);

  // O handler do item (createMultiDropdown) roda antes e não para a
  // propagação — a seleção que o usuário acabou de fazer é preservada.
  conflicts.forEach(([, listId]) => {
    document.getElementById(listId)?.addEventListener('mousedown', e => {
      if (e.target.closest('.multi-dd-item')) desligar();
    });
  });

  // Editar uma data pintada pelo relativo: ela vira filtro real, a outra
  // (que era só exibição) é limpa e o relativo desliga.
  dateIds.forEach(id => {
    const el = document.getElementById(id);
    el?.addEventListener('change', () => {
      const wrap = el.closest('.date-input-wrap');
      if (!wrap?.classList.contains('is-rel')) return;
      wrap.classList.remove('is-effective', 'is-rel');
      desligar();
      document.getElementById(id + '-clear')?.classList.toggle('visible', !!el.value);
    });
  });

  sync(); // valor restaurado pelo navegador (voltar/recarregar) já nasce coerente

  return {
    isActive: () => qtd() !== null,
    // Recalcula na hora: aba aberta de um dia para o outro não envia data velha.
    getRange() {
      if (qtd() === null) return null;
      const r = computeRelativeRange(qtd(), unit.value);
      pintarDatas(r);
      return r;
    },
    label() {
      const n = qtd(), u = REL_UNIDADES[unit.value];
      if (n === null || !u) return null;
      return n === 1 ? u.um : u.varios(n);
    },
    reset() {
      num.value = '';
      unit.selectedIndex = 0;
      sync();
    },
  };
}

// ── Formatadores ────────────────────────────────────────────────────────────
// Versão canônica: executive_view.html.
// fBRL: nome mantido por fidelidade ao código-fonte, mas o formato é USD ("$ "),
// não reais — ver discrepância reportada (digital_evento.html usa 2 casas decimais
// em vez de 0; mantivemos aqui a versão de executive_view.html, sem decimais).
function fBRL(val) {
  const n = Number(val) || 0;
  return '$ ' + n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function fNum(val) {
  return (Number(val) || 0).toLocaleString('pt-BR');
}

function fLucro(val) {
  const n = Number(val) || 0;
  return n < 0 ? '-' + fBRL(-n) : fBRL(n);
}

function fROI(val) {
  const n = Number(val) || 0;
  return n.toFixed(2).replace(',', '.') + 'x';
}

// Ausente em executive_view.html. Recebe uma fração 0–1 já calculada e formata
// como percentual (1 casa) — usada em especialistas.html/especialistas_analitico.html.
function fPct(val) {
  return (Number(val) * 100).toFixed(1) + '%';
}

// Versão de digital_evento.html: recebe numerador/denominador brutos e calcula
// a razão internamente — assinatura incompatível com fPct(val) acima, por isso
// mantida como função própria (as duas coexistiam sob o mesmo nome "fPct" em
// páginas diferentes na origem; aqui foram separadas para não quebrar nenhuma).
function fPctRatio(num, den) {
  if (!den) return '0%';
  return (num / den * 100).toFixed(1) + '%';
}

// Ausente em executive_view.html, especialistas.html e especialistas_analitico.html
// — portado verbatim de digital_evento.html (única página que o define).
function fBar(num, den) {
  if (!den) return '0%';
  return Math.min(100, num / den * 100).toFixed(1) + '%';
}

// ── Export de tabela (CSV / Excel) ──────────────────────────────────────────
// Gerado no navegador a partir das linhas já carregadas/filtradas/ordenadas na
// tela (exporta a tabela "como está"), não de uma nova consulta ao servidor —
// por isso não é a mesma abordagem de hotel_controle.php::exportar() (que gera
// o CSV no servidor porque a exportação de lá é da base completa do período,
// não do que está renderizado). O CSV segue a mesma convenção pt-BR de lá:
// BOM + ';' como delimitador (a vírgula já é separador decimal no Excel pt-BR).
const BOM = String.fromCharCode(0xFEFF); // sem ele o Excel abre UTF-8 como Latin-1 e os acentos viram lixo.

function csvCell(val) {
  const s = val === null || val === undefined ? '' : String(val);
  return /[;"\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
}

function downloadBlob(blob, filename) {
  const url = URL.createObjectURL(blob);
  const a   = document.createElement('a');
  a.href     = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

// columns: [{ k: 'campo', label: 'Rótulo' }, ...] — mesmo formato de COLS_CLIENTES/COLS_CONSULTAS.
function exportRowsAsCsv(columns, rows, filename) {
  const linhas = [columns.map(c => csvCell(c.label)).join(';')];
  rows.forEach(r => linhas.push(columns.map(c => csvCell(r[c.k])).join(';')));
  downloadBlob(new Blob([BOM + linhas.join('\r\n')], { type: 'text/csv;charset=utf-8;' }), filename + '.csv');
}

// Truque padrão sem dependências: uma <table> HTML servida como
// application/vnd.ms-excel com extensão .xls — o Excel abre normalmente.
function exportRowsAsExcel(columns, rows, filename) {
  const esc  = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const head = '<tr>' + columns.map(c => `<th>${esc(c.label)}</th>`).join('') + '</tr>';
  const body = rows.map(r => '<tr>' + columns.map(c => `<td>${esc(r[c.k])}</td>`).join('') + '</tr>').join('');
  const html = '<html><head><meta charset="UTF-8"></head><body><table border="1">' + head + body + '</table></body></html>';
  downloadBlob(new Blob([BOM + html], { type: 'application/vnd.ms-excel' }), filename + '.xls');
}

// Botão "Exportar ▾" com menu de formatos — mesma lógica de abrir/fechar dos
// dropdowns acima (fecha ao clicar fora). onFormat(fmt) recebe 'csv'|'excel'.
function createExportMenu(wrapId, btnId, listId, onFormat) {
  const wrap = document.getElementById(wrapId);
  const btn  = document.getElementById(btnId);
  const list = document.getElementById(listId);
  function open()  { list.classList.add('open');    btn.classList.add('open'); }
  function close() { list.classList.remove('open'); btn.classList.remove('open'); }
  btn.addEventListener('click', () => list.classList.contains('open') ? close() : open());
  document.addEventListener('click', e => { if (!wrap.contains(e.target)) close(); });
  list.querySelectorAll('[data-fmt]').forEach(el => {
    el.addEventListener('click', () => { close(); onFormat(el.dataset.fmt); });
  });
}

// ── Painel de filtros recolhível ───────────────────────────────────────────
// Nasceu inline no marketing e virou padrão dos dashboards. A view entrega o
// markup pronto dentro da barra — <button class="filters-toggle"> seguido de
// <div class="filters-fields"> com os campos — e chama esta função.
//
// O markup vem do HTML, e não é injetado aqui, de propósito: com o padrão
// recolhido, criar o wrapper depois da pintura faria os campos aparecerem e
// sumirem. A view já nasce com class="filters is-collapsible", então o CSS
// posiciona tudo antes do primeiro frame; se este script não carregar, a
// barra fica aberta e usável, apenas sem o botão — degradação segura.
//
// opts:
//   key              sufixo da chave de localStorage — uma por view
//   sel              seletor do container (default: '.filters, .filter-bar')
//   open             estado quando não há preferência salva (default: false)
//   collapseOnApply  recolher ao clicar em .btn-apply (default: true)
//   onToggle         callback(aberto) a cada mudança, inclusive na carga
//   badgeId          id do pill de resumo dos filtros (default: 'filter-label-badge')
function initFiltersToggle(opts) {
  opts = opts || {};
  const box = document.querySelector(opts.sel || '.filters, .filter-bar');
  if (!box || box.dataset.filtersToggle) return null;   // idempotente
  const btn    = box.querySelector('.filters-toggle');
  const fields = box.querySelector('.filters-fields');
  if (!btn || !fields) return null;
  box.dataset.filtersToggle = '1';
  box.classList.add('is-collapsible');                  // rede de segurança

  // Pill de resumo dos filtros ativos (ex.: "2025 · JAN, FEV"), fora da
  // barra de filtros, no header. Só existe em parte das views — quando
  // ausente, vira no-op. Escondido com o painel aberto para não repetir
  // a mesma informação duas vezes na tela.
  const badge = document.getElementById(opts.badgeId || 'filter-label-badge');

  // Storage é tratado como opcional de propósito: os dashboards rodam dentro
  // de <iframe> de origem parceira (?to=1), e com storage de terceiro bloqueado
  // (ITP do Safari, particionamento do Chrome) estes acessos LANÇAM. Sem o
  // try/catch a exceção mataria o resto do <script> da view junto.
  const chave = 'bi_filters_open:' + (opts.key || location.pathname);
  function lembrado() { try { return localStorage.getItem(chave); } catch (e) { return null; } }
  function lembrar(v) { try { localStorage.setItem(chave, v); } catch (e) {} }

  function set(aberto) {
    box.classList.toggle('collapsed', !aberto);
    btn.setAttribute('aria-expanded', String(aberto));
    lembrar(aberto ? '1' : '0');
    if (badge) badge.classList.toggle('is-hidden', aberto);
    if (opts.onToggle) opts.onToggle(aberto);
  }

  btn.addEventListener('click', () => set(box.classList.contains('collapsed')));

  if (opts.collapseOnApply !== false) {
    // Delegação pela classe, escopada no container: a maioria das views usa
    // onclick="applyFilters()" sem id no botão, e há .btn-apply fora de barra
    // de filtro em outras telas (biexplorer) que não podem disparar isto.
    box.addEventListener('click', e => {
      if (!e.target.closest('.btn-apply')) return;
      set(false);
      btn.focus();   // display:none no wrapper jogaria o foco para o <body>
    });
  }

  const salvo = lembrado();
  set(salvo === null ? !!opts.open : salvo === '1');

  return {
    open:   () => set(true),
    close:  () => set(false),
    toggle: () => set(box.classList.contains('collapsed')),
    isOpen: () => !box.classList.contains('collapsed'),
  };
}

// ── Dicionário de métricas ─────────────────────────────────────────────────
// Monta o sumário lateral e a busca de paginas/{dash}/dicionario.php a partir
// das <section class="dic-section"> da página, gera âncora por métrica e
// abre todas as regras técnicas na impressão. Sem fetch e sem storage: o
// dicionário continua estático. CSS: seção 18 de bi-theme.css (.dic-*).
// A view chama initDicionario() logo depois do fragmento.
function initDicionario(opts) {
  opts = opts || {};
  var root = document.querySelector(opts.root || '.dic');
  if (!root) return;
  var layout = root.querySelector('.dic-layout');
  var body   = root.querySelector('.dic-body');
  if (!layout || !body) return;

  var ITEMS   = '.dic-term, .dic-def, .dic-note, .dic-steps';
  var COUNTED = '.dic-term, .dic-def';

  function norm(s) {
    return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  }
  var usedIds = {};
  [].forEach.call(document.querySelectorAll('[id]'), function (el) { usedIds[el.id] = true; });
  function makeId(text, fallback) {
    var base = norm(text).replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || fallback;
    var id = base, n = 2;
    while (usedIds[id]) id = base + '-' + n++;
    usedIds[id] = true;
    return id;
  }

  var sections = [].slice.call(body.querySelectorAll('.dic-section'));
  sections.forEach(function (sec) {
    var h = sec.querySelector('.dic-section-title');
    if (!sec.id) sec.id = makeId(h ? h.textContent : '', 'secao');
  });

  // Âncora por métrica: id pelo nome + "#" ao lado do título
  [].forEach.call(body.querySelectorAll('.dic-term'), function (term) {
    var name = term.querySelector('.dic-term-name');
    var label = name ? name.textContent.trim() : '';
    if (!term.id) term.id = makeId(label, 'metrica');
    if (name && !name.querySelector('.dic-anchor')) {
      var a = document.createElement('a');
      a.className = 'dic-anchor';
      a.href = '#' + term.id;
      a.textContent = '#';
      a.setAttribute('aria-label', 'Link para ' + label);
      name.appendChild(a);
    }
  });

  // Coluna lateral: busca + sumário
  var side = document.createElement('aside');
  side.className = 'dic-side';
  side.setAttribute('aria-label', 'Navegação do dicionário');
  side.innerHTML =
    '<div class="dic-search">' +
      '<label class="dic-search-label" for="dic-q">Buscar no dicionário</label>' +
      '<div class="dic-search-field">' +
        '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><line x1="16.5" y1="16.5" x2="21" y2="21"/></svg>' +
        '<input id="dic-q" class="dic-search-input" type="search" placeholder="Métrica ou termo" autocomplete="off" spellcheck="false">' +
      '</div>' +
      '<p class="dic-status" role="status" aria-live="polite"></p>' +
    '</div>' +
    '<nav class="dic-toc" aria-label="Seções do dicionário">' +
      '<p class="dic-toc-title">Nesta página</p>' +
      '<ol class="dic-toc-list"></ol>' +
    '</nav>';
  layout.insertBefore(side, body);
  layout.classList.add('has-side');

  var input   = side.querySelector('.dic-search-input');
  var status  = side.querySelector('.dic-status');
  var tocList = side.querySelector('.dic-toc-list');
  var tocNav  = side.querySelector('.dic-toc');

  var toc = {};
  sections.forEach(function (sec) {
    var h = sec.querySelector('.dic-section-title');
    var li = document.createElement('li');
    var a = document.createElement('a');
    var label = document.createElement('span');
    var count = document.createElement('span');
    a.className = 'dic-toc-link';
    a.href = '#' + sec.id;
    label.textContent = h ? h.textContent.trim() : sec.id;
    count.className = 'dic-toc-count';
    a.appendChild(label);
    a.appendChild(count);
    li.appendChild(a);
    tocList.appendChild(li);
    toc[sec.id] = { li: li, a: a, count: count };
  });

  // Índice de busca: texto "visível" (nome + descrição) separado da regra técnica
  var items = [].slice.call(body.querySelectorAll(ITEMS)).map(function (el) {
    var rule = el.querySelector('.dic-rule');
    var clone = el.cloneNode(true);
    var r = clone.querySelector('.dic-rule');
    if (r) r.parentNode.removeChild(r);
    return {
      el: el,
      sec: el.closest('.dic-section'),
      rule: rule,
      head: norm(clone.textContent),
      ruleText: rule ? norm(rule.textContent) : '',
      counted: el.matches(COUNTED)
    };
  });
  var lists = [].slice.call(body.querySelectorAll('.dic-list'));
  var stepLabels = [].slice.call(body.querySelectorAll('.dic-steps-label'));

  var empty = document.createElement('div');
  empty.className = 'dic-empty';
  empty.hidden = true;
  body.appendChild(empty);
  var sugestoes = (root.getAttribute('data-sugestoes') || '').split(',')
    .map(function (s) { return s.trim(); }).filter(Boolean);

  function renderEmpty(q) {
    empty.textContent = '';
    var p = document.createElement('p');
    p.textContent = 'Nada encontrado para “' + q + '”.';
    empty.appendChild(p);
    if (sugestoes.length) {
      var wrap = document.createElement('div');
      wrap.className = 'dic-empty-sugs';
      var lbl = document.createElement('span');
      lbl.textContent = 'Tente:';
      wrap.appendChild(lbl);
      sugestoes.forEach(function (s) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'dic-sug';
        b.textContent = s;
        b.addEventListener('click', function () { input.value = s; aplicar(); input.focus(); });
        wrap.appendChild(b);
      });
      empty.appendChild(wrap);
    }
  }

  function aplicar() {
    var q = input.value.trim();
    var tokens = norm(q).split(/\s+/).filter(Boolean);
    var total = 0, visiveis = 0, porSecao = {}, secaoVisivel = {};

    items.forEach(function (it) {
      var noTexto = tokens.every(function (t) { return it.head.indexOf(t) !== -1; });
      var naRegra = !noTexto && tokens.every(function (t) {
        return (it.head + ' ' + it.ruleText).indexOf(t) !== -1;
      });
      var mostra = noTexto || naRegra;
      it.el.hidden = !mostra;
      if (it.rule) {
        if (naRegra && !it.rule.open) { it.rule.open = true; it.rule.setAttribute('data-dic-auto', ''); }
        else if (!naRegra && it.rule.hasAttribute('data-dic-auto')) { it.rule.open = false; it.rule.removeAttribute('data-dic-auto'); }
      }
      if (!mostra) return;
      visiveis++;
      secaoVisivel[it.sec.id] = true;
      if (it.counted) { total++; porSecao[it.sec.id] = (porSecao[it.sec.id] || 0) + 1; }
    });

    lists.forEach(function (l) { l.hidden = !l.querySelector(':scope > :not([hidden])'); });
    stepLabels.forEach(function (l) { var n = l.nextElementSibling; l.hidden = !!(n && n.hidden); });
    sections.forEach(function (sec) {
      var vis = !!secaoVisivel[sec.id];
      sec.hidden = !vis;
      toc[sec.id].li.hidden = !vis;
      toc[sec.id].count.textContent = porSecao[sec.id] ? porSecao[sec.id] : '';
    });

    tocNav.hidden = !visiveis;
    if (!tokens.length) status.textContent = '';
    else status.textContent = total === 1 ? '1 resultado' : total + ' resultados';
    empty.hidden = !(tokens.length && !visiveis);
    if (!empty.hidden) renderEmpty(q);
    espiar();
  }

  input.addEventListener('input', aplicar);
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && input.value) { e.preventDefault(); input.value = ''; aplicar(); }
  });

  // Seção ativa no sumário, pela posição de rolagem
  function scrollPai(el) {
    for (var p = el.parentElement; p && p !== document.body; p = p.parentElement) {
      var oy = getComputedStyle(p).overflowY;
      if (oy === 'auto' || oy === 'scroll') return p;
    }
    return null;
  }
  var scroller = scrollPai(root);
  var alvoRolagem = scroller || window;
  var ativo = null;

  function espiar() {
    var vis = sections.filter(function (s) { return !s.hidden; });
    if (!vis.length) return;
    var offset = parseInt(getComputedStyle(body).getPropertyValue('--dic-offset'), 10) || 0;
    var topo = (scroller ? scroller.getBoundingClientRect().top : 0) + offset + 8;
    var atual = vis[0];
    vis.forEach(function (s) { if (s.getBoundingClientRect().top <= topo) atual = s; });
    var el = scroller || document.documentElement;
    if (el.scrollTop + el.clientHeight >= el.scrollHeight - 2) atual = vis[vis.length - 1];
    if (atual === ativo) return;
    if (ativo && toc[ativo.id]) { toc[ativo.id].a.classList.remove('is-active'); toc[ativo.id].a.removeAttribute('aria-current'); }
    ativo = atual;
    var link = toc[ativo.id].a;
    link.classList.add('is-active');
    link.setAttribute('aria-current', 'true');
    if (tocList.scrollWidth > tocList.clientWidth) tocList.scrollLeft = link.offsetLeft - 16;
  }
  var pendente = false;
  alvoRolagem.addEventListener('scroll', function () {
    if (pendente) return;
    pendente = true;
    requestAnimationFrame(function () { pendente = false; espiar(); });
  }, { passive: true });
  window.addEventListener('resize', espiar);

  // Âncora na URL: abre a regra e destaca a métrica
  var destacado = null;
  function abrirAlvo(rolar) {
    var id = decodeURIComponent(location.hash.slice(1));
    var el = id && document.getElementById(id);
    if (destacado) destacado.classList.remove('is-target');
    destacado = null;
    if (!el || !root.contains(el)) return;
    if (el.classList.contains('dic-term')) {
      destacado = el;
      el.classList.add('is-target');
      var r = el.querySelector('.dic-rule');
      if (r) r.open = true;
    }
    if (rolar) el.scrollIntoView({ block: 'start' });
  }
  window.addEventListener('hashchange', function () { abrirAlvo(false); });

  // Impressão: abre todas as regras e devolve como estavam
  var abertasNaImpressao = [];
  window.addEventListener('beforeprint', function () {
    abertasNaImpressao = [].filter.call(root.querySelectorAll('.dic-rule'), function (d) { return !d.open; });
    abertasNaImpressao.forEach(function (d) { d.open = true; });
  });
  window.addEventListener('afterprint', function () {
    abertasNaImpressao.forEach(function (d) { d.open = false; });
    abertasNaImpressao = [];
  });

  aplicar();
  abrirAlvo(true);   // os ids das métricas nascem aqui, depois do pulo nativo do navegador
  // A troca para a Fira Sans reflui o texto e desloca a âncora; se ninguém
  // rolou até a fonte chegar, reposiciona.
  if (location.hash && document.fonts && document.fonts.ready) {
    var rolagem0 = (scroller || document.documentElement).scrollTop;
    document.fonts.ready.then(function () {
      if ((scroller || document.documentElement).scrollTop === rolagem0) abrirAlvo(true);
    });
  }
  var reduzir = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (scroller && !reduzir) scroller.style.scrollBehavior = 'smooth';
}
