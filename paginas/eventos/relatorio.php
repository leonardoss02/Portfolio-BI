<?php
/*
 * paginas/eventos/relatorio.php
 *
 * Relatório imprimível do dashboard Eventos (rota /eventos/relatorio).
 * Página 1 = resumo executivo (KPIs + gasto/mês + destaques);
 * páginas 2+ = tabela consolidada por evento, cabeçalho repetido.
 *
 * Consome o MESMO endpoint da aba Consolidado (/eventos/dados) — não há
 * SQL nem método de cálculo novo no controller. CPL e os dois CAC são
 * derivados aqui no JS, então nenhum dashboard existente muda de número.
 *
 * O PDF sai pela caixa de impressão do navegador (window.print() →
 * "Salvar como PDF"): o projeto não tem npm/composer, e a folha
 * @media print entrega fidelidade total ao bi-theme.css sem dependência.
 *
 * Filtros chegam pela querystring, na mesma precedência exclusiva de
 * paginas/eventos/detalhamento.php: eventos > data_inicio/data_fim > ano/mes.
 * A whitelist abaixo existe para não repassar parâmetro arbitrário ao endpoint.
 */

$relQuery = [];
if (!empty($_GET['eventos'])) {
    $relQuery['eventos'] = (string)$_GET['eventos'];
} elseif (!empty($_GET['data_inicio']) || !empty($_GET['data_fim'])) {
    if (!empty($_GET['data_inicio'])) { $relQuery['data_inicio'] = (string)$_GET['data_inicio']; }
    if (!empty($_GET['data_fim']))    { $relQuery['data_fim']    = (string)$_GET['data_fim']; }
} else {
    if (!empty($_GET['ano'])) { $relQuery['ano'] = (string)$_GET['ano']; }
    if (!empty($_GET['mes'])) { $relQuery['mes'] = (string)$_GET['mes']; }
}

$relEmbed = (($_GET['to'] ?? '') === '1') ? '?to=1' : '';
?>
<div class="dash-eventos-rel">

<style>
/* ══════════════════════════════════════════════════════════════
 * RELATÓRIO DE EVENTOS — folha de estilo de documento/impressão.
 * Escopada em .dash-eventos-rel para não vazar para os outros
 * dashboards (bi-theme.css é compartilhado por todos os 14).
 * Tipografia em pt: a mesma medida vale na tela e no PDF.
 * ══════════════════════════════════════════════════════════════ */


.dash-eventos-rel { background: var(--bg); padding: 20px 0 40px; }

/* ── Barra de ações (só na tela) ───────────────────────────────── */
.dash-eventos-rel .rel-actions {
  width: 277mm; max-width: calc(100% - 24px); margin: 0 auto 14px;
  display: flex; align-items: center; gap: 10px;
}
.dash-eventos-rel .rel-btn-pdf {
  font-family: var(--font-ui); font-size: 13px; font-weight: 700; letter-spacing: .02em;
  color: #fff; background: var(--red); border: 0; border-radius: var(--r);
  padding: 10px 18px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
  box-shadow: var(--sh-sm); transition: filter .12s, transform .12s;
}
.dash-eventos-rel .rel-btn-pdf:hover { filter: brightness(1.08); transform: translateY(-1px); }
.dash-eventos-rel .rel-btn-pdf svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.2; }
.dash-eventos-rel .rel-hint { font-family: var(--font-ui); font-size: 12px; color: var(--muted); }

/* ── Folha ─────────────────────────────────────────────────────── */
.dash-eventos-rel .rel-sheet {
  width: 277mm; max-width: calc(100% - 24px); margin: 0 auto 18px;
  background: var(--card); color: var(--text);
  border: 1px solid var(--border); border-radius: var(--r);
  box-shadow: var(--sh); padding: 11mm 10mm;
}

/* ── Cabeçalho do documento ────────────────────────────────────── */
.dash-eventos-rel .rel-head {
  display: flex; align-items: flex-start; justify-content: space-between;
  gap: 16px; border-bottom: 2.5pt solid var(--red); padding-bottom: 7pt; margin-bottom: 10pt;
}
.dash-eventos-rel .rel-head-id { display: flex; align-items: center; gap: 9pt; }
.dash-eventos-rel .rel-logo {
  width: 26pt; height: 26pt; border-radius: 5pt; background: var(--red); color: #fff;
  font-family: var(--font-mono); font-weight: 700; font-size: 11pt;
  display: flex; align-items: center; justify-content: center; flex: none;
}
.dash-eventos-rel .rel-title  { font-size: 15pt; font-weight: 700; line-height: 1.15; letter-spacing: -.01em; }
.dash-eventos-rel .rel-sub    { font-size: 8.5pt; color: var(--muted); margin-top: 2pt; }
.dash-eventos-rel .rel-meta   { text-align: right; font-size: 8pt; color: var(--muted); line-height: 1.6; white-space: nowrap; }
.dash-eventos-rel .rel-meta b { color: var(--text); font-weight: 600; }

/* ── Bloco de KPIs ─────────────────────────────────────────────── */
.dash-eventos-rel .rel-kpis {
  display: grid; grid-template-columns: repeat(7, 1fr); gap: 5pt; margin-bottom: 10pt;
}
.dash-eventos-rel .rel-kpi {
  border: .75pt solid var(--border); border-left: 2.5pt solid var(--kc, var(--muted));
  border-radius: 4pt; background: var(--card2); padding: 5pt 6pt; min-width: 0;
}
.dash-eventos-rel .rel-kpi-l {
  font-size: 6.4pt; font-weight: 700; letter-spacing: .05em; text-transform: uppercase;
  color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.dash-eventos-rel .rel-kpi-v {
  font-family: var(--font-mono); font-weight: 700; font-size: 12.5pt; line-height: 1.15;
  color: var(--kc, var(--text)); margin-top: 2.5pt; white-space: nowrap;
  font-variant-numeric: tabular-nums;
}
.dash-eventos-rel .k-red   { --kc: var(--red); }
.dash-eventos-rel .k-blue  { --kc: var(--consulta); }
.dash-eventos-rel .k-green { --kc: var(--fecho); }
.dash-eventos-rel .k-gold  { --kc: var(--gold); }
.dash-eventos-rel .k-amber { --kc: var(--amber); }

/* ── Banda de apoio (mês + destaques) ──────────────────────────── */
.dash-eventos-rel .rel-band {
  display: grid; grid-template-columns: 1.35fr 1fr; gap: 8pt; align-items: start;
}
.dash-eventos-rel .rel-card {
  border: .75pt solid var(--border); border-radius: 5pt; background: var(--card); overflow: hidden;
}
.dash-eventos-rel .rel-card-h {
  font-size: 7.2pt; font-weight: 700; letter-spacing: .07em; text-transform: uppercase;
  color: var(--muted); background: var(--card2);
  padding: 5pt 7pt; border-bottom: .75pt solid var(--border);
}
.dash-eventos-rel .rel-card-b { padding: 0; }

/* Multi-ano: as tabelas de ano vão LADO A LADO em largura total. Empilhadas,
   o resumo media 990px contra 718px de página útil (A4 paisagem, margem
   10mm) e o PDF ganhava uma página 2 quase vazia. Lado a lado também é a
   leitura melhor: compara 2025 × 2026 mês a mês. */
.dash-eventos-rel .rel-band.multi-ano { grid-template-columns: 1fr; }
.dash-eventos-rel .rel-mensal-grid {
  display: grid; grid-auto-flow: column; grid-auto-columns: 1fr;
}
.dash-eventos-rel .rel-mensal-grid > div + div { border-left: .75pt solid var(--border); }

/* Destaques em 3 colunas quando a banda ocupa a largura toda. A faixa
   alternada viraria xadrez num grid, então some e quem separa é a borda. */
.dash-eventos-rel .rel-band.multi-ano .rel-hl-grid {
  display: grid; grid-template-columns: repeat(3, 1fr);
}
.dash-eventos-rel .rel-band.multi-ano .rel-hl-grid .hl-row:nth-child(even) { background: transparent; }
.dash-eventos-rel .rel-band.multi-ano .rel-hl-grid .hl-row { border-right: .5pt solid var(--border); }
.dash-eventos-rel .rel-band.multi-ano .rel-hl-grid .hl-row:nth-child(3n) { border-right: 0; }

/* ── Tabelas do relatório ──────────────────────────────────────── */
.dash-eventos-rel table.rel { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; }
.dash-eventos-rel table.rel thead th {
  background: #1a2035; color: #fff; font-family: var(--font-ui);
  font-size: 6.7pt; font-weight: 700; letter-spacing: .03em; text-transform: uppercase;
  padding: 4.5pt 3pt; text-align: right; white-space: nowrap; vertical-align: bottom;
}
.dash-eventos-rel table.rel thead th:first-child { text-align: left; padding-left: 5pt; }
.dash-eventos-rel table.rel tbody td {
  font-family: var(--font-mono); font-size: 7.4pt; font-weight: 500;
  padding: 3.1pt 3pt; text-align: right; white-space: nowrap;
  border-bottom: .5pt solid var(--border);
}
.dash-eventos-rel table.rel tbody td:first-child {
  font-family: var(--font-ui); font-weight: 500; text-align: left; padding-left: 5pt;
  max-width: 64mm; overflow: hidden; text-overflow: ellipsis;
}
/* Zebra por CLASSE, não nth-child: o agrupamento por ano insere linhas de
   grupo/subtotal no tbody, e nth-child passaria a colorir essas linhas e a
   quebrar o alternado. Classe explícita é também a convenção do projeto
   (.mini-table .row-odd/.row-even em bi-theme.css). */
.dash-eventos-rel table.rel tbody tr.rel-zebra { background: var(--card2); }

/* Cabeçalho de grupo (ano) */
.dash-eventos-rel table.rel tbody tr.rel-grp td {
  font-family: var(--font-ui); font-weight: 800; font-size: 7.4pt;
  letter-spacing: .1em; text-transform: uppercase; text-align: left;
  background: #dfe4ee; color: #1a2035; padding: 3.8pt 5pt;
  border-top: 1pt solid #1a2035; border-bottom: .5pt solid var(--border);
}

/* Subtotal do ano */
.dash-eventos-rel table.rel tbody tr.rel-sub td {
  font-weight: 800; background: #eef1f6;
  border-top: .75pt solid var(--border); border-bottom: .75pt solid var(--border);
}
.dash-eventos-rel table.rel tbody tr.rel-sub td:first-child {
  font-family: var(--font-ui); letter-spacing: .03em;
}
.dash-eventos-rel table.rel tbody tr.rel-total td {
  font-weight: 800; font-size: 7.6pt; background: #e8ebf2;
  border-top: 1.2pt solid #1a2035; border-bottom: 1.2pt solid #1a2035;
}
.dash-eventos-rel table.rel tbody tr.rel-total td:first-child { font-family: var(--font-ui); letter-spacing: .04em; }

/* Tabela do card mensal — um pouco mais folgada */
.dash-eventos-rel .rel-card table.rel tbody td { font-size: 7.1pt; padding: 2.7pt 5pt; }
.dash-eventos-rel .rel-card table.rel thead th { font-size: 6.4pt; padding: 4.5pt 6pt; }

.dash-eventos-rel .c-red   { color: var(--red); }
.dash-eventos-rel .c-blue  { color: var(--consulta); }
.dash-eventos-rel .c-green { color: var(--fecho); }
.dash-eventos-rel .c-muted { color: var(--muted); }

/* ── ROI ───────────────────────────────────────────────────────── */
.dash-eventos-rel .roi-tag {
  display: inline-block; min-width: 26pt; text-align: center; border-radius: 3pt;
  padding: 1pt 3pt; font-family: var(--font-mono); font-weight: 700; font-size: 7.1pt;
}
.dash-eventos-rel .rt-green  { background: var(--green-glow);  color: var(--green); }
.dash-eventos-rel .rt-amber  { background: var(--amber-glow);  color: var(--amber); }
.dash-eventos-rel .rt-danger { background: var(--danger-glow); color: var(--danger); }

/* ── Destaques ─────────────────────────────────────────────────── */
.dash-eventos-rel .hl-row {
  display: flex; align-items: baseline; gap: 6pt;
  padding: 4.1pt 7pt; border-bottom: .5pt solid var(--border);
}
.dash-eventos-rel .hl-row:last-child { border-bottom: 0; }
.dash-eventos-rel .hl-row:nth-child(even) { background: var(--card2); }
.dash-eventos-rel .hl-l { font-size: 7pt; font-weight: 700; color: var(--muted); white-space: nowrap; }
.dash-eventos-rel .hl-dots { flex: 1; border-bottom: .5pt dotted var(--border); transform: translateY(-2pt); }
.dash-eventos-rel .hl-n {
  font-size: 7pt; font-weight: 600; max-width: 34mm;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.dash-eventos-rel .hl-v {
  font-family: var(--font-mono); font-size: 7.2pt; font-weight: 700;
  white-space: nowrap; min-width: 15mm; text-align: right;
}

/* ── Título de seção da tabela ─────────────────────────────────── */
.dash-eventos-rel .rel-sec {
  display: flex; align-items: baseline; justify-content: space-between;
  gap: 10pt; margin-bottom: 6pt;
}
.dash-eventos-rel .rel-sec-t { font-size: 10.5pt; font-weight: 700; letter-spacing: -.01em; }
.dash-eventos-rel .rel-sec-s { font-size: 7.4pt; color: var(--muted); }

/* ── Rodapé / nota de exclusão ─────────────────────────────────── */
.dash-eventos-rel .rel-foot {
  margin-top: 8pt; padding-top: 6pt; border-top: .75pt solid var(--border);
  font-size: 6.9pt; color: var(--muted); line-height: 1.55;
}
.dash-eventos-rel .rel-foot b { color: var(--text); font-weight: 600; }

/* ══════════════════════════════════════════════════════════════
 * IMPRESSÃO
 * ══════════════════════════════════════════════════════════════ */
@page { size: A4 landscape; margin: 10mm; }

@media print {
  /* O tema é html.dark via localStorage (template/eventos.php:39-44).
     Imprimir em dark queima tinta e fica ilegível — força superfície clara
     reafirmando os tokens de :root também no seletor do dark. */
  html.dark {
    --bg: #ffffff; --card: #ffffff; --card2: #f6f8fb;
    --border: #dde2ec; --text: #293244; --muted: #6b7488;
  }
  html, body { background: #fff !important; }

  /* Sem isto o Chrome descarta os fundos e a ZEBRA DESAPARECE do PDF. */
  * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

  .sidebar, .sidebar-footer, .filters, .tab-switch, .header, .theme-btn,
  #loading-overlay, #error-banner,
  .dash-eventos-rel .rel-actions { display: none !important; }

  .app-shell { display: block !important; height: auto !important; overflow: visible !important; }
  .content   { overflow: visible !important; padding: 0 !important; }

  .dash-eventos-rel { background: #fff; padding: 0; }
  .dash-eventos-rel .rel-sheet {
    width: auto; max-width: none; margin: 0; padding: 0;
    border: 0; border-radius: 0; box-shadow: none; background: #fff;
  }

  /* Página 1 = resumo; a tabela abre na página 2. */
  .dash-eventos-rel .rel-sheet-resumo { break-after: page; }

  thead { display: table-header-group; }   /* cabeçalho repetido em toda página */
  tr    { break-inside: avoid; }
  /* Um cabeçalho de ano sozinho no pé da página não ajuda ninguém. */
  .dash-eventos-rel table.rel tbody tr.rel-grp { break-after: avoid; }
  .dash-eventos-rel .rel-kpi { break-inside: avoid; }
  /* O card mensal cresceu (2 anos = ~26 linhas) e não cabe mais no resto da
     página 1. Com break-inside:avoid ele pulava INTEIRO para a página
     seguinte e deixava meia página em branco; agora flui. O overflow:hidden
     usado no arredondamento da tela também torna a caixa monolítica na
     paginação do Chrome, então volta a visible na impressão. */
  .dash-eventos-rel .rel-card { break-inside: auto; overflow: visible; }
}
</style>

<!-- ── LOADING / ERRO ─────────────────────────────────────────── -->
<div id="loading-overlay" class="hidden"></div>
<div id="error-banner" role="alert"></div>

<!-- ── HEADER (só tela — oculto na impressão) ─────────────────── -->
<header class="header">
  <div class="header-left">
    <div class="logo">ME</div>
    <div>
      <div class="header-title">Relatório de Eventos</div>
      <div class="header-sub">Documento imprimível &middot; A4 paisagem</div>
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
    <div class="tab-switch">
      <a class="tab-switch-btn" href="<?= $this->site ?>eventos<?= $relEmbed ?>">Pipeline</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>eventos/detalhamento<?= $relEmbed ?>">Consolidado</a>
      <a class="tab-switch-btn active" href="<?= $this->site ?>eventos/relatorio<?= $relEmbed ?>">Relatório</a>
      <a class="tab-switch-btn" href="<?= $this->site ?>eventos/dicionario<?= $relEmbed ?>">Dicionário</a>
    </div>
  </div>
</header>

<!-- ── AÇÕES (só tela) ────────────────────────────────────────── -->
<div class="rel-actions">
  <button class="rel-btn-pdf" type="button" onclick="window.print()">
    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
      <path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
      <rect x="6" y="14" width="12" height="8"/>
    </svg>
    Baixar PDF
  </button>
  <span class="rel-hint">Abre a caixa de impressão — escolha <b>Salvar como PDF</b>, A4 paisagem.</span>
</div>

<!-- ── FOLHA 1 · RESUMO ───────────────────────────────────────── -->
<section class="rel-sheet rel-sheet-resumo">
  <header class="rel-head">
    <div class="rel-head-id">
      <div class="rel-logo">ME</div>
      <div>
        <div class="rel-title">Relatório de Eventos</div>
        <div class="rel-sub" id="rel-sub">—</div>
      </div>
    </div>
    <div class="rel-meta" id="rel-meta">—</div>
  </header>

  <div class="rel-kpis" id="rel-kpis"></div>

  <div class="rel-band" id="rel-band">
    <div class="rel-card">
      <div class="rel-card-h">Gasto e faturado por mês do evento</div>
      <div class="rel-card-b" id="rel-mensal"></div>
    </div>
    <div class="rel-card">
      <div class="rel-card-h">Destaques do período</div>
      <div class="rel-card-b rel-hl-grid" id="rel-destaques"></div>
    </div>
  </div>

  <div class="rel-foot" id="rel-foot-1"></div>
</section>

<!-- ── FOLHA 2+ · TABELA ──────────────────────────────────────── -->
<section class="rel-sheet">
  <div class="rel-sec">
    <div class="rel-sec-t">Consolidado por evento</div>
    <div class="rel-sec-s" id="rel-sec-sub">—</div>
  </div>
  <table class="rel">
    <thead id="rel-thead"></thead>
    <tbody id="rel-tbody"></tbody>
  </table>
  <div class="rel-foot" id="rel-foot-2"></div>
</section>

<script>
const API     = '<?= $this->site ?>eventos/dados';
const FILTROS = <?= json_encode($relQuery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

/* ══════════════════════════════════════════════════════════════
 * RELATÓRIO
 * ══════════════════════════════════════════════════════════════ */

// Exclui do relatório o que não é evento presencial de verdade.
// Padrão ANCORADO de propósito: "Webinar Miami" (um evento real que
// venha a existir) não casa; só o registro genérico "Webinar" casa.
// O rodapé sempre declara o que saiu, então um falso positivo fica visível.
const NAO_EVENTO = /^(1:1|1x1)\b|^webinar$/i;

const MESES_ABREV = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const MESES_CHEIO = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

function esc(s) {
  return String(s ?? '').replace(/[&<>"]/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[c]));
}

// CPL sobre custo de MKT (o custo físico do evento não capta lead);
// CAC sobre custo total (a venda depende do evento inteiro).
const cpl     = e => (e.total_leads        > 0 ? e.investimento_mkt / e.total_leads        : null);
const cacCad  = e => (e.cadeiras           > 0 ? e.custo_total      / e.cadeiras           : null);
const cacCons = e => (e.consultas_vendidas > 0 ? e.custo_total      / e.consultas_vendidas : null);

// Denominador zero nunca vira 0 nem Infinity — vira travessão.
const fUSD2 = v => (v == null ? '—' : '$ ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
const fUSD0 = v => (v == null ? '—' : fBRL(v));
const fRoi  = v => (v == null ? '—' : Number(v).toFixed(2) + 'x');

// Mesma semântica de cor da aba Consolidado (detalhamento.php:195-200):
// verde = retorno já realizado, âmbar = ainda há potencial, vermelho = sem retorno.
function roiTagClass(roi, proj) {
  if (roi == null) return '';
  if (roi <= 0) return 'rt-danger';
  if (proj != null && roi >= proj - 0.005) return 'rt-green';
  return 'rt-amber';
}
const roiTag = (roi, proj) =>
  `<span class="roi-tag ${roiTagClass(roi, proj)}">${roi == null ? '—' : Number(roi).toFixed(2)}</span>`;

const dataDe = e => { const t = new Date(String(e.data).replace(' ', 'T')); return isNaN(t) ? null : t; };
const mesDe  = e => { const t = dataDe(e); return t ? t.getMonth() : null; };
const anoDe  = e => { const t = dataDe(e); return t ? t.getFullYear() : null; };

// Agrupa PARTICIONANDO a lista: todo item cai em algum grupo, e chave nula
// (data inválida) vira um grupo próprio no fim. Filtrar por chaves conhecidas
// faria a linha desaparecer da tabela e continuar somando no total — número
// errado sem ninguém perceber.
const SEM_CHAVE = '\u0000sem';

// Chave de ordenação ano+mês. Evento sem data vai para o fim de forma
// determinística (NaN numa comparação devolveria 0 e deixaria a ordem solta).
const ordDe = e => {
  const a = anoDe(e), m = mesDe(e);
  return (a == null || m == null) ? Infinity : a * 12 + m;
};

function agruparPor(lista, keyFn) {
  const mapa = new Map();
  lista.forEach(e => {
    const k  = keyFn(e);
    const kk = (k == null) ? SEM_CHAVE : k;
    if (!mapa.has(kk)) mapa.set(kk, []);
    mapa.get(kk).push(e);
  });
  const grupos = [...mapa.keys()]
    .filter(k => k !== SEM_CHAVE)
    .sort((a, b) => a - b)
    .map(k => ({ chave: k, itens: mapa.get(k) }));
  if (mapa.has(SEM_CHAVE)) {
    grupos.push({ chave: null, itens: mapa.get(SEM_CHAVE) });
  }
  return grupos;
}

/* ── Totais recalculados sobre as linhas que sobraram ────────────
   Nunca reaproveitar data.totais: ele inclui os eventos excluídos. */
const CAMPOS_SOMA = ['total_leads','qualificados','cadeiras','consultas_vendidas','combos',
                     'faturamento','investimento_mkt','custo_fisico','custo_total'];

function somar(lista) {
  const t = {};
  CAMPOS_SOMA.forEach(c => { t[c] = lista.reduce((s, e) => s + (Number(e[c]) || 0), 0); });
  t.roi           = t.custo_total ? t.faturamento / t.custo_total : null;
  t.roi_projetado = null;
  t.qt_eventos    = lista.length;
  return t;
}

/* ── Colunas da tabela consolidada ──────────────────────────────── */
const COLS = [
  { l: 'Evento',        cell: e => esc(e.nome_formatado || e.nome) },
  { l: 'Custo MKT',     cls: 'c-red',   cell: e => fUSD0(e.investimento_mkt) },
  { l: 'Custo Físico',  cls: 'c-red',   cell: e => fUSD0(e.custo_fisico) },
  { l: 'Custo Total',   cls: 'c-red',   cell: e => fUSD0(e.custo_total) },
  { l: 'Leads',                         cell: e => fNum(e.total_leads) },
  { l: 'CPL',           cls: 'c-red',   cell: e => fUSD2(cpl(e)) },
  { l: 'Qualif.',                       cell: e => fNum(e.qualificados) },
  { l: 'Cadeiras',      cls: 'c-blue',  cell: e => fNum(e.cadeiras) },
  { l: 'CAC Cadeira',   cls: 'c-red',   cell: e => fUSD0(cacCad(e)) },
  { l: 'Cons. Vend.',   cls: 'c-blue',  cell: e => fNum(e.consultas_vendidas) },
  { l: 'CAC Consulta',  cls: 'c-red',   cell: e => fUSD0(cacCons(e)) },
  { l: 'Combos',        cls: 'c-green', cell: e => fNum(e.combos) },
  { l: 'Faturamento',   cls: 'c-green', cell: e => fUSD0(e.faturamento) },
  { l: 'ROI(x)',                        cell: e => roiTag(e.roi, e.roi_projetado),
    tot: t => '<b>' + fRoi(t.roi) + '</b>' },
];

/* ── KPIs do topo ───────────────────────────────────────────────── */
function renderKpis(t) {
  const kpis = [
    { l: 'Custo MKT',      v: fUSD0(t.investimento_mkt), c: 'k-red'   },
    { l: 'Custo Físico',   v: fUSD0(t.custo_fisico),     c: 'k-red'   },
    { l: 'Custo Total',    v: fUSD0(t.custo_total),      c: 'k-red'   },
    { l: 'Leads',          v: fNum(t.total_leads),       c: 'k-blue'  },
    { l: 'CPL',            v: fUSD2(cpl(t)),             c: 'k-amber' },
    { l: 'Qualificados',   v: fNum(t.qualificados),      c: 'k-blue'  },
    { l: 'Cadeiras',       v: fNum(t.cadeiras),          c: 'k-blue'  },
    { l: 'CAC Cadeira',    v: fUSD0(cacCad(t)),          c: 'k-amber' },
    { l: 'Consultas Vend.',v: fNum(t.consultas_vendidas),c: 'k-blue'  },
    { l: 'CAC Consulta',   v: fUSD0(cacCons(t)),         c: 'k-amber' },
    { l: 'Combos',         v: fNum(t.combos),            c: 'k-green' },
    { l: 'Faturamento',    v: fUSD0(t.faturamento),      c: 'k-gold'  },
    { l: 'ROI',            v: fRoi(t.roi),               c: 'k-green' },
  ];
  document.getElementById('rel-kpis').innerHTML = kpis.map(k =>
    `<div class="rel-kpi ${k.c}"><div class="rel-kpi-l">${k.l}</div><div class="rel-kpi-v">${k.v}</div></div>`
  ).join('');
}

/* ── Gasto/faturado por mês do evento ───────────────────────────── */
function renderMensal(lista, t) {
  const anos    = agruparPor(lista, anoDe);
  const agrupar = anos.length > 1;

  const band = document.getElementById('rel-band');
  if (band) { band.classList.toggle('multi-ano', agrupar); }

  const linhasDoAno = (doAno) => {
    let z = 0;
    return agruparPor(doAno, mesDe).map(g => {
      const s = somar(g.itens);
      return `<tr class="${(z++ % 2) ? 'rel-zebra' : ''}">
        <td>${g.chave == null ? 'Sem data' : MESES_CHEIO[g.chave]}</td>
        <td class="c-red">${fUSD0(s.investimento_mkt)}</td>
        <td class="c-red">${fUSD0(s.custo_fisico)}</td>
        <td class="c-red">${fUSD0(s.custo_total)}</td>
        <td class="c-green">${fUSD0(s.faturamento)}</td>
        <td><b>${fRoi(s.roi)}</b></td>
      </tr>`;
    }).join('');
  };

  const totalRow = (s, label, cls) => `<tr class="${cls}">
    <td>${label}</td>
    <td>${fUSD0(s.investimento_mkt)}</td>
    <td>${fUSD0(s.custo_fisico)}</td>
    <td>${fUSD0(s.custo_total)}</td>
    <td>${fUSD0(s.faturamento)}</td>
    <td>${fRoi(s.roi)}</td>
  </tr>`;

  const cabecalho = (primeira) => `<thead><tr>
    <th>${primeira}</th><th>Custo MKT</th><th>Custo Físico</th>
    <th>Custo Total</th><th>Faturamento</th><th>ROI</th>
  </tr></thead>`;

  // Um ano só: layout original, tabela única com TOTAL no pé.
  if (!agrupar) {
    document.getElementById('rel-mensal').innerHTML =
      `<table class="rel">${cabecalho('Mês')}<tbody>` +
      linhasDoAno(lista) + totalRow(t, 'TOTAL', 'rel-total') +
      `</tbody></table>`;
    return;
  }

  // O ano vira o rótulo da 1ª coluna, o que dispensa a linha de cabeçalho
  // de grupo. O total geral não se repete aqui: Custo MKT/Físico/Total,
  // Faturamento e ROI do período já são chips de KPI no topo da página.
  const colunas = anos.map(g => {
    const rotulo = g.chave == null ? 'Sem data' : String(g.chave);
    return `<div><table class="rel">${cabecalho(rotulo)}<tbody>` +
           linhasDoAno(g.itens) +
           totalRow(somar(g.itens), 'Subtotal', 'rel-sub') +
           `</tbody></table></div>`;
  }).join('');

  document.getElementById('rel-mensal').innerHTML =
    `<div class="rel-mensal-grid">${colunas}</div>`;
}

/* ── Destaques ──────────────────────────────────────────────────── */
function renderDestaques(lista) {
  // Só entra no ranking quem tem denominador válido — evento sem lead
  // não é "o melhor CPL do período".
  const melhor = (fn, dir) => {
    const c = lista.map(e => ({ e, v: fn(e) })).filter(x => x.v != null && isFinite(x.v));
    if (!c.length) return null;
    return c.reduce((a, b) => (dir * (b.v - a.v) > 0 ? b : a));
  };


  // "Melhor" exige verba investida: um evento sem custo nenhum daria
  // CPL e CAC de $ 0,00 e venceria todos os rankings sem ter mérito.
  const cplRank     = e => (e.investimento_mkt > 0 ? cpl(e)     : null);
  const cacCadRank  = e => (e.custo_total      > 0 ? cacCad(e)  : null);
  const cacConsRank = e => (e.custo_total      > 0 ? cacCons(e) : null);

  const itens = [
    { l: "Melhor CPL",         r: melhor(cplRank,     -1), f: fUSD2 },
    { l: "Melhor CAC cadeira", r: melhor(cacCadRank,  -1), f: fUSD0 },
    { l: "Melhor CAC consulta",r: melhor(cacConsRank, -1), f: fUSD0 },
    { l: 'Maior faturamento', r: melhor(e => e.faturamento > 0 ? e.faturamento : null, 1), f: fUSD0 },
    { l: 'Melhor ROI',        r: melhor(e => e.roi > 0 ? e.roi : null,  1), f: fRoi },
    { l: 'Pior ROI',          r: melhor(e => e.custo_total > 0 ? (e.roi ?? 0) : null, -1), f: fRoi },
  ];

  document.getElementById('rel-destaques').innerHTML = itens.map(it => `
    <div class="hl-row">
      <span class="hl-l">${it.l}</span>
      <span class="hl-dots"></span>
      <span class="hl-n">${it.r ? esc(it.r.e.nome) : '—'}</span>
      <span class="hl-v">${it.r ? it.f(it.r.v) : '—'}</span>
    </div>`).join('');
}

/* ── Tabela consolidada ─────────────────────────────────────────── */
function renderTabela(lista, t) {
  document.getElementById('rel-thead').innerHTML =
    '<tr>' + COLS.map(c => `<th>${c.l}</th>`).join('') + '</tr>';

  const linha = (e, z) =>
    `<tr class="${z ? 'rel-zebra' : ''}">` +
    COLS.map(c => `<td class="${c.cls || ''}">${c.cell(e)}</td>`).join('') + '</tr>';

  // A primeira célula é o rótulo; as demais reusam a mesma função de célula
  // das linhas de evento, para total e linha nunca divergirem de formato.
  const totalRow = (s, label, cls) => `<tr class="${cls}">` + COLS.map((c, i) =>
    i === 0 ? `<td>${label}</td>` : `<td class="">${c.tot ? c.tot(s) : c.cell(s)}</td>`
  ).join('') + '</tr>';

  const anos    = agruparPor(lista, anoDe);
  const agrupar = anos.length > 1;

  let corpo = '';
  if (!agrupar) {
    corpo = lista.map((e, i) => linha(e, i % 2)).join('');
  } else {
    anos.forEach(g => {
      const rotulo = g.chave == null ? 'Sem data' : String(g.chave);
      const n      = g.itens.length;
      corpo += `<tr class="rel-grp"><td colspan="${COLS.length}">${rotulo} · ${n} evento${n === 1 ? '' : 's'}</td></tr>`;
      corpo += g.itens.map((e, i) => linha(e, i % 2)).join('');
      corpo += totalRow(somar(g.itens), 'Subtotal ' + rotulo, 'rel-sub');
    });
  }

  document.getElementById('rel-tbody').innerHTML = corpo + totalRow(t, 'TOTAL', 'rel-total');
}

/* ── Texto do período filtrado ──────────────────────────────────── */
function descreverPeriodo(f) {
  if (f.eventos)                      return 'Eventos selecionados';
  if (f.data_inicio || f.data_fim) {
    const d = s => s ? s.split('-').reverse().join('/') : '…';
    return d(f.data_inicio) + ' a ' + d(f.data_fim);
  }
  const anos  = f.year   ? String(f.year).split(',').join(', ') : 'Todos os anos';
  if (!f.months) return anos;
  const nums = String(f.months).split(',').map(Number)
                 .filter(n => n >= 1 && n <= 12).sort((a, b) => a - b);
  if (!nums.length) return anos;
  const ms = nums.map(n => MESES_ABREV[n - 1]);
  // Faixa "Jan–Ago" só se a seleção for contígua; senão lista os meses,
  // para não sugerir um período maior do que o filtrado.
  const contiguo = nums.length > 2 && (nums[nums.length - 1] - nums[0]) === nums.length - 1;
  return anos + ' · ' + (contiguo ? ms[0] + '–' + ms[ms.length - 1] : ms.join(', '));
}

/* ── Render principal ───────────────────────────────────────────── */
function render(data) {
  const todos     = data.eventos || [];
  const excluidos = todos.filter(e => NAO_EVENTO.test(String(e.nome || '').trim()));
  const lista     = todos.filter(e => !NAO_EVENTO.test(String(e.nome || '').trim()))
                         .sort((a, b) => (ordDe(a) - ordDe(b)) || (b.custo_total - a.custo_total));
  const t         = somar(lista);

  const periodo = descreverPeriodo(data.filtros || {});
  document.getElementById('rel-sub').textContent =
    periodo + ' · ' + t.qt_eventos + ' evento' + (t.qt_eventos === 1 ? '' : 's');
  document.getElementById('rel-meta').innerHTML =
    'Acme EUA · <b>BI Eventos</b><br>Gerado em ' +
    new Date().toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) +
    '<br>Valores em <b>USD</b>';
  document.getElementById('rel-sec-sub').textContent =
    periodo + ' · ' + t.qt_eventos + ' eventos · ordenado por mês e custo total';

  renderKpis(t);
  renderMensal(lista, t);
  renderDestaques(lista);
  renderTabela(lista, t);

  // Nota de exclusão: a divergência com a aba Consolidado fica auditável
  // no próprio documento em vez de virar pergunta na reunião.
  let nota = '<b>Metodologia.</b> CPL = custo de marketing ÷ leads. ' +
    'CAC cadeira e CAC consulta = custo total (marketing + físico) ÷ cadeiras e ÷ consultas vendidas. ' +
    'ROI = faturamento ÷ custo total. Métrica sem denominador aparece como “—”. ' +
    'O mês é o mês de realização do evento.';
  if (excluidos.length) {
    const s = somar(excluidos);
    nota += '<br><b>Exclusões.</b> ' + excluidos.length + ' registro' + (excluidos.length === 1 ? '' : 's') +
      ' fora do relatório por não ser evento presencial (' +
      excluidos.map(e => esc(e.nome)).join(', ') + '), somando ' + fUSD0(s.custo_total) +
      ' de custo e ' + fNum(s.total_leads) + ' leads. ' +
      'Por isso os totais aqui ficam abaixo dos da aba Consolidado.';
  }
  document.getElementById('rel-foot-1').innerHTML = nota;
  document.getElementById('rel-foot-2').innerHTML = nota;
}

/* ── Carga ──────────────────────────────────────────────────────── */
function erroRel(msg) {
  document.getElementById('error-banner').textContent = msg;
}

async function carregar() {
  const params = new URLSearchParams(FILTROS).toString();
  document.getElementById('error-banner').textContent = '';
  document.getElementById('loading-overlay').classList.remove('hidden');
  document.body.classList.add('loading');
  try {
    const res  = await fetch(API + (params ? '?' + params : ''), { cache: 'no-store' });
    const data = await res.json();
    if (data.error) { erroRel('Erro: ' + data.error); return; }
    render(data);
  } catch (e) {
    erroRel('Falha ao carregar: ' + e.message);
  } finally {
    document.getElementById('loading-overlay').classList.add('hidden');
    document.body.classList.remove('loading');
  }
}

carregar();
</script>

</div>
