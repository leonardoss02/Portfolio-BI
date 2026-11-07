/*
 * js/biexplorer.js — BI Explorer (Fase 1 / MVP)
 *
 * Script clássico (sem módulos ES), mesmo papel de js/bi-dashboard.js: só
 * define funções, nada executa no parse. A orquestração (bootstrap) é
 * disparada por paginas/biexplorer/index.php chamando BiExplorer.init(...)
 * no fim do body.
 *
 * Princípio central: o servidor é a única fonte de verdade do SQL. Este
 * arquivo nunca monta texto de SQL — monta o AnalysisSpec (JSON) e manda
 * pro backend; o SQL exibido em #bie-sql-texto vem sempre de resp.sql.
 */
const BiExplorer = (function () {
    let site = '';
    let gridApi = null;
    let chart = null;
    let debounceHandle = null;

    let analiseId = null;
    let draftId = null;
    let dirty = false;

    // Mapa alias -> {tabela, colunas}. 't' é sempre a tabela base; 'j_1', 'j_2'... são
    // os joins ativos (mesmo id usado em currentAnalysis.joins[].id e como alias SQL
    // no backend). Substituiu o antigo colunasAtuais (array flat só da tabela base).
    let colunasPorAlias = {};
    let tabelasCache = []; // [{nome, tipo}] — última lista carregada de /biexplorer/tabelas
    let joinEmEdicao = null; // estado do formulário "+ Join" enquanto está aberto

    let currentAnalysis = analiseVazia();

    function analiseVazia() {
        return {
            versao: 1,
            modo: 'visual',
            tabela: null,
            joins: [],
            colunasCalculadas: [],
            campos: [],
            filtros: [],
            agrupamento: { linhas: [], metricas: [] },
            ordenacao: [],
            limite: 1000,
            grafico: { tipo: 'barra', eixoX: null, series: [] },
            sqlManual: null,
            gridState: null,
        };
    }

    /**
     * Lista combinada de [valorQualificado, label] de TODAS as fontes (tabela base +
     * cada join ativo), na ordem t, j_1, j_2... — usada por todo picker de campo.
     * Colunas da base ficam sem prefixo (retrocompatível com specs sem join); colunas
     * de join usam "alias.coluna" como valor e "tabela · coluna" como label.
     */
    function opcoesDeCampoTodasFontes() {
        const opcoes = [];
        Object.keys(colunasPorAlias).forEach(alias => {
            const fonte = colunasPorAlias[alias];
            fonte.colunas.forEach(c => {
                if (alias === 't') {
                    opcoes.push([c.nome, c.nome]);
                } else {
                    opcoes.push([alias + '.' + c.nome, fonte.tabela + ' · ' + c.nome]);
                }
            });
        });
        // colunas calculadas não vêm de /biexplorer/colunas — são definidas inline em
        // currentAnalysis.colunasCalculadas, então entram direto aqui (alias reservado 'calc').
        (currentAnalysis.colunasCalculadas || []).forEach(cc => {
            opcoes.push(['calc.' + cc.id, 'calc · ' + cc.nome]);
        });
        return opcoes;
    }

    /** Espelho em JS de resolverColunaQualificada() do backend — nunca lança, retorna null. */
    function metaDeColunaQualificada(valorQualificado) {
        const pos = valorQualificado.indexOf('.');
        const alias = pos === -1 ? 't' : valorQualificado.slice(0, pos);
        const coluna = pos === -1 ? valorQualificado : valorQualificado.slice(pos + 1);
        if (alias === 'calc') {
            const cc = (currentAnalysis.colunasCalculadas || []).find(c => c.id === coluna);
            return cc ? { nome: cc.id, tipo_logico: cc.tipoResultado } : null;
        }
        const fonte = colunasPorAlias[alias];
        if (!fonte) return null;
        return fonte.colunas.find(c => c.nome === coluna) || null;
    }

    const ICONE_TIPO = {
        texto: '🔤',
        numero: '#',
        data: '📅',
        booleano: '☑',
    };

    const OPERADORES = {
        texto: [
            ['contem', 'Contém'], ['nao_contem', 'Não contém'],
            ['comeca_com', 'Começa com'], ['termina_com', 'Termina com'],
            ['igual', 'Igual'], ['diferente', 'Diferente'], ['lista', 'Lista múltipla'],
            ['preenchido', 'Preenchido'], ['vazio', 'Vazio'],
        ],
        numero: [
            ['maior', 'Maior que'], ['menor', 'Menor que'],
            ['igual', 'Igual'], ['diferente', 'Diferente'], ['entre', 'Entre'],
            ['preenchido', 'Preenchido'], ['vazio', 'Vazio'],
        ],
        data: [
            ['hoje', 'Hoje'], ['ontem', 'Ontem'], ['esta_semana', 'Esta semana'],
            ['este_mes', 'Este mês'], ['este_ano', 'Este ano'], ['intervalo', 'Intervalo personalizado'],
            ['preenchido', 'Preenchido'], ['vazio', 'Vazio'],
        ],
        booleano: [['sim', 'Sim'], ['nao', 'Não'], ['preenchido', 'Preenchido'], ['vazio', 'Vazio']],
    };

    const FUNCOES_METRICA = ['COUNT', 'COUNT_DISTINCT', 'SUM', 'AVG', 'MIN', 'MAX', 'STDDEV'];
    const TIPOS_GRAFICO = [['barra', 'Barra'], ['linha', 'Linha'], ['area', 'Área'], ['pizza', 'Pizza'], ['rosca', 'Rosca']];
    // Espelho em JS de FUNCOES_COLUNA_CALCULADA (controle/biexplorer_controle.php) — só
    // para sugestão no autocomplete; quem realmente aplica a whitelist é o backend.
    const FUNCOES_COLUNA_CALCULADA = ['IF', 'DATEDIFF', 'MONTH', 'YEAR', 'DAY', 'CONCAT', 'ROUND', 'UPPER', 'LOWER', 'COALESCE', 'IFNULL', 'ABS'];

    let seqId = 0;
    function proximoId(prefixo) {
        seqId++;
        return prefixo + '_' + seqId;
    }

    // ── Bootstrap ───────────────────────────────────────────────────────────

    function init(opts) {
        site = opts.site;
        draftId = localStorage.getItem('biexplorer_draft_id');
        if (!draftId) {
            draftId = 'draft_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 8);
            localStorage.setItem('biexplorer_draft_id', draftId);
        }

        montarGrid();
        wireToolbar();
        wireJoins();
        wireColunasCalculadas();
        wireFiltros();
        wireAgrupamento();
        wireOrdenacao();
        wireGrafico();
        wireSqlManual();
        wirePaineis();
        carregarTabelas();

        setInterval(autosaveSeSujo, 15000);
        window.addEventListener('beforeunload', (e) => {
            if (dirty) { e.preventDefault(); e.returnValue = ''; }
        });
    }

    // ── Utilidades de UI ────────────────────────────────────────────────────

    function marcarSujo() {
        dirty = true;
        const badge = document.getElementById('bie-dirty-badge');
        if (badge) badge.classList.remove('hidden');
    }

    function limparSujo() {
        dirty = false;
        const badge = document.getElementById('bie-dirty-badge');
        if (badge) badge.classList.add('hidden');
    }

    function mostrarErro(msg) {
        const el = document.getElementById('bie-erro');
        if (!el) return;
        el.textContent = msg;
        el.classList.add('visible');
    }

    function limparErro() {
        const el = document.getElementById('bie-erro');
        if (el) el.classList.remove('visible');
    }

    function mostrarCarregando(ligado) {
        const el = document.getElementById('bie-loading');
        if (el) el.classList.toggle('hidden', !ligado);
    }

    function agendarPreview() {
        marcarSujo();
        clearTimeout(debounceHandle);
        debounceHandle = setTimeout(carregarPreview, 400);
    }

    /**
     * fetch() + parse de JSON confiando no CONTEÚDO do corpo, não no header
     * Content-Type — um proxy/config de hospedagem entre o PHP e o navegador pode
     * alterar ou não repassar esse header mesmo quando o corpo é um JSON válido
     * (foi exatamente isso que causava "Resposta inesperada (HTTP 200)" de forma
     * recorrente em produção, mesmo com o backend respondendo certo). Só mostra o
     * erro amigável quando o parse de fato falha — o que ainda pega corretamente
     * os casos reais de página de erro HTML (rate limit, falha de conexão com o
     * banco antes do roteamento etc., que começam com "<!DOCTYPE" e nunca
     * parseiam como JSON).
     */
    async function fetchJson(url, opts) {
        const res = await fetch(url, opts);
        const texto = await res.text();
        try {
            return JSON.parse(texto);
        } catch (e) {
            throw new Error(`Resposta inesperada do servidor (HTTP ${res.status}). Tente novamente em instantes.`);
        }
    }

    // ── Painel de dados: tabelas/colunas ────────────────────────────────────

    async function carregarTabelas() {
        try {
            const resp = await fetchJson(site + 'biexplorer/tabelas', { cache: 'no-store' });
            if (resp.error) { mostrarErro(resp.error); return; }
            tabelasCache = resp.tabelas || [];
            renderListaTabelas(tabelasCache);
        } catch (e) {
            mostrarErro('Falha ao carregar tabelas: ' + e.message);
        }
    }

    function renderListaTabelas(tabelas) {
        const container = document.getElementById('bie-lista-tabelas');
        const busca = document.getElementById('bie-busca-tabela');
        function render() {
            const q = (busca?.value || '').toLowerCase().trim();
            container.innerHTML = '';
            tabelas
                .filter(t => !q || t.nome.toLowerCase().includes(q))
                .forEach(t => {
                    const el = document.createElement('div');
                    el.className = 'bie-tabela-item' + (t.nome === currentAnalysis.tabela ? ' selected' : '');
                    el.innerHTML = '<span class="bie-tabela-icone">' + (t.tipo === 'view' ? '👁' : '▦') + '</span>' + t.nome;
                    el.addEventListener('click', () => selecionarTabela(t.nome));
                    container.appendChild(el);
                });
        }
        busca?.addEventListener('input', render);
        render();
    }

    async function selecionarTabela(nome) {
        if (nome === currentAnalysis.tabela) return;
        currentAnalysis = analiseVazia();
        currentAnalysis.tabela = nome;
        analiseId = null;
        document.getElementById('bie-nome').value = '';
        renderListaTabelas(tabelasCache);

        try {
            const resp = await fetchJson(site + 'biexplorer/colunas/' + encodeURIComponent(nome), { cache: 'no-store' });
            if (resp.error) { mostrarErro(resp.error); return; }
            colunasPorAlias = { t: { tabela: nome, colunas: resp.colunas || [] } };
            renderListaColunas();
            renderTodosOsSeletores();
            renderJoins();
            renderColunasCalculadas();
            limparErro();
            agendarPreview();
        } catch (e) {
            mostrarErro('Falha ao carregar colunas: ' + e.message);
        }
    }

    function renderListaColunas() {
        const container = document.getElementById('bie-lista-colunas');
        const busca = document.getElementById('bie-busca-coluna');
        if (!container) return;

        function render() {
            const q = (busca?.value || '').toLowerCase().trim();
            container.innerHTML = '';
            const aliases = Object.keys(colunasPorAlias);
            const mostrarGrupo = aliases.length > 1; // só rotula por tabela quando há join ativo
            aliases.forEach(alias => {
                const fonte = colunasPorAlias[alias];
                const filtradas = fonte.colunas.filter(c => !q || c.nome.toLowerCase().includes(q));
                if (!filtradas.length) return;
                if (mostrarGrupo) {
                    const cab = document.createElement('div');
                    cab.className = 'bie-coluna-grupo-titulo';
                    cab.textContent = fonte.tabela;
                    container.appendChild(cab);
                }
                filtradas.forEach(c => {
                    const valorQualificado = alias === 't' ? c.nome : (alias + '.' + c.nome);
                    const marcado = currentAnalysis.campos.includes(valorQualificado);
                    const label = document.createElement('label');
                    label.className = 'bie-coluna-item';
                    label.innerHTML =
                        '<input type="checkbox"' + (marcado ? ' checked' : '') + '> ' +
                        '<span class="bie-coluna-icone">' + (ICONE_TIPO[c.tipo_logico] || '') + '</span> ' +
                        '<span class="bie-coluna-nome">' + c.nome + '</span>';
                    label.querySelector('input').addEventListener('change', (e) => toggleCampo(valorQualificado, e.target.checked));
                    container.appendChild(label);
                });
            });

            const calculadasFiltradas = (currentAnalysis.colunasCalculadas || []).filter(cc => !q || cc.nome.toLowerCase().includes(q));
            if (calculadasFiltradas.length) {
                const cab = document.createElement('div');
                cab.className = 'bie-coluna-grupo-titulo';
                cab.textContent = 'Calculadas';
                container.appendChild(cab);
                calculadasFiltradas.forEach(cc => {
                    const valorQualificado = 'calc.' + cc.id;
                    const marcado = currentAnalysis.campos.includes(valorQualificado);
                    const label = document.createElement('label');
                    label.className = 'bie-coluna-item';
                    label.innerHTML =
                        '<input type="checkbox"' + (marcado ? ' checked' : '') + '> ' +
                        '<span class="bie-coluna-icone">' + (ICONE_TIPO[cc.tipoResultado] || '') + '</span> ' +
                        '<span class="bie-coluna-nome">' + cc.nome + '</span>';
                    label.querySelector('input').addEventListener('change', (e) => toggleCampo(valorQualificado, e.target.checked));
                    container.appendChild(label);
                });
            }
        }
        busca?.addEventListener('input', render);
        render();
    }

    function toggleCampo(nome, marcado) {
        const idx = currentAnalysis.campos.indexOf(nome);
        if (marcado && idx === -1) currentAnalysis.campos.push(nome);
        if (!marcado && idx !== -1) currentAnalysis.campos.splice(idx, 1);
        // só a ordenação depende de "campos" (modo sem agrupamento) — reconstruir a
        // lista inteira de colunas aqui apagaria o texto buscado em #bie-busca-coluna.
        renderOrdenacao();
        agendarPreview();
    }

    // ── Grid (AG Grid Community) ────────────────────────────────────────────

    function montarGrid() {
        const el = document.getElementById('bie-grid');
        const gridOptions = {
            columnDefs: [],
            rowData: [],
            defaultColDef: { sortable: true, filter: true, resizable: true },
            pagination: true,
            paginationPageSize: 100,
            rowGroupPanelShow: 'always',
            animateRows: false,
        };
        gridApi = agGrid.createGrid(el, gridOptions);
    }

    function atualizarGrid(colunas, linhas) {
        const defs = colunas.map(c => ({ field: c, headerName: c, enableRowGroup: true, enablePivot: true }));
        gridApi.setGridOption('columnDefs', defs);
        gridApi.setGridOption('rowData', linhas);
    }

    // ── Preview / execução ──────────────────────────────────────────────────

    async function carregarPreview() {
        if (!currentAnalysis.tabela && currentAnalysis.modo === 'visual') return;
        mostrarCarregando(true);
        limparErro();
        try {
            const resp = await fetchJson(site + 'biexplorer/preview', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ spec: currentAnalysis }),
            });

            if (resp.error) { mostrarErro(resp.error); return; }

            document.getElementById('bie-sql-texto').textContent = resp.sql || '';
            atualizarGrid(resp.colunas || [], resp.linhas || []);
            atualizarGrafico(resp.linhas || []);
        } catch (e) {
            mostrarErro('Falha ao executar consulta: ' + e.message);
        } finally {
            mostrarCarregando(false);
        }
    }

    // ── Joins (trazer colunas de outra tabela) ───────────────────────────────

    function wireJoins() {
        document.getElementById('bie-add-join')?.addEventListener('click', abrirFormularioJoin);
        document.getElementById('bie-join-cancelar')?.addEventListener('click', fecharFormularioJoin);
        document.getElementById('bie-join-confirmar')?.addEventListener('click', confirmarJoin);
        renderJoins();
    }

    function abrirFormularioJoin() {
        if (!currentAnalysis.tabela) {
            mostrarErro('Selecione uma tabela base antes de adicionar um join.');
            return;
        }
        joinEmEdicao = null;
        document.getElementById('bie-join-form')?.classList.remove('hidden');
        document.getElementById('bie-join-chaves')?.classList.add('hidden');
        renderListaTabelasParaJoin();
    }

    function fecharFormularioJoin() {
        joinEmEdicao = null;
        document.getElementById('bie-join-form')?.classList.add('hidden');
    }

    function renderListaTabelasParaJoin() {
        const container = document.getElementById('bie-join-lista-tabelas');
        const busca = document.getElementById('bie-join-busca-tabela');
        if (!container) return;
        function render() {
            const q = (busca?.value || '').toLowerCase().trim();
            container.innerHTML = '';
            tabelasCache
                .filter(t => !q || t.nome.toLowerCase().includes(q))
                .forEach(t => {
                    const el = document.createElement('div');
                    el.className = 'bie-tabela-item';
                    el.innerHTML = '<span class="bie-tabela-icone">' + (t.tipo === 'view' ? '👁' : '▦') + '</span>' + t.nome;
                    el.addEventListener('click', () => selecionarTabelaParaJoin(t.nome));
                    container.appendChild(el);
                });
        }
        busca?.addEventListener('input', render);
        render();
    }

    async function selecionarTabelaParaJoin(tabela) {
        try {
            const resp = await fetchJson(site + 'biexplorer/colunas/' + encodeURIComponent(tabela), { cache: 'no-store' });
            if (resp.error) { mostrarErro(resp.error); return; }
            const colunasBase = colunasPorAlias.t?.colunas || [];
            const colunasNovas = resp.colunas || [];
            joinEmEdicao = {
                tabela,
                colunasBase,
                colunasNovas,
                campoBase: colunasBase[0]?.nome || '',
                campoNovo: colunasNovas[0]?.nome || '',
                tipo: 'LEFT',
            };
            renderChavesJoin();
            document.getElementById('bie-join-chaves')?.classList.remove('hidden');
        } catch (e) {
            mostrarErro('Falha ao carregar colunas para join: ' + e.message);
        }
    }

    function renderChavesJoin() {
        const wrapBase = document.getElementById('bie-join-campo-base-wrap');
        const wrapNovo = document.getElementById('bie-join-campo-novo-wrap');
        const wrapTipo = document.getElementById('bie-join-tipo-wrap');
        if (!wrapBase || !wrapNovo || !wrapTipo || !joinEmEdicao) return;
        wrapBase.innerHTML = '';
        wrapNovo.innerHTML = '';
        wrapTipo.innerHTML = '';

        const selBase = criarSeletorCampo(
            joinEmEdicao.colunasBase.map(c => [c.nome, c.nome]),
            joinEmEdicao.campoBase,
            (v) => { joinEmEdicao.campoBase = v; }
        );
        wrapBase.appendChild(selBase);

        const selNovo = criarSeletorCampo(
            joinEmEdicao.colunasNovas.map(c => [c.nome, c.nome]),
            joinEmEdicao.campoNovo,
            (v) => { joinEmEdicao.campoNovo = v; }
        );
        wrapNovo.appendChild(selNovo);

        const selTipo = selectDe([['LEFT', 'LEFT JOIN'], ['INNER', 'INNER JOIN']], joinEmEdicao.tipo);
        selTipo.addEventListener('change', () => { joinEmEdicao.tipo = selTipo.value; });
        wrapTipo.appendChild(selTipo);
    }

    function confirmarJoin() {
        if (!joinEmEdicao) return;
        const id = proximoId('j');
        currentAnalysis.joins.push({
            id,
            tabela: joinEmEdicao.tabela,
            tipo: joinEmEdicao.tipo,
            condicoes: [{ campoBase: joinEmEdicao.campoBase, campoJoin: joinEmEdicao.campoNovo }],
        });
        colunasPorAlias[id] = { tabela: joinEmEdicao.tabela, colunas: joinEmEdicao.colunasNovas };
        fecharFormularioJoin();
        renderJoins();
        renderTodosOsSeletores();
        agendarPreview();
    }

    function renderJoins() {
        const container = document.getElementById('bie-joins-lista');
        if (!container) return;
        container.innerHTML = '';
        (currentAnalysis.joins || []).forEach(j => {
            const row = document.createElement('div');
            row.className = 'bie-join-row';
            const cond = (j.condicoes || [])[0] || {};
            const resumo = document.createElement('span');
            resumo.className = 'bie-join-resumo';
            resumo.innerHTML = j.tipo + ' JOIN <b>' + j.tabela + '</b> ON ' + cond.campoBase + ' = ' + cond.campoJoin;
            const btnRemover = document.createElement('button');
            btnRemover.className = 'bie-btn-remover';
            btnRemover.textContent = '✕';
            btnRemover.addEventListener('click', () => removerJoin(j.id));
            row.append(resumo, btnRemover);
            container.appendChild(row);
        });
    }

    /**
     * Remove um join e limpa qualquer referência qualificada pendente a ele em
     * campos/filtros/agrupamento/métricas (id do join é estável — nenhum outro
     * alias muda ao remover este, então não há nada para renumerar).
     */
    function removerJoin(id) {
        currentAnalysis.joins = (currentAnalysis.joins || []).filter(j => j.id !== id);
        delete colunasPorAlias[id];

        const prefixo = id + '.';
        const referenciaAlias = (valor) => typeof valor === 'string' && valor.startsWith(prefixo);

        currentAnalysis.campos = currentAnalysis.campos.filter(c => !referenciaAlias(c));
        currentAnalysis.filtros = currentAnalysis.filtros.filter(f => !referenciaAlias(f.campo));
        currentAnalysis.agrupamento.linhas = currentAnalysis.agrupamento.linhas.filter(c => !referenciaAlias(c));
        currentAnalysis.agrupamento.metricas = currentAnalysis.agrupamento.metricas.filter(m => !referenciaAlias(m.campo));
        currentAnalysis.ordenacao = currentAnalysis.ordenacao.filter(o => !referenciaAlias(o.campo));

        renderJoins();
        renderTodosOsSeletores();
        agendarPreview();
    }

    // ── Colunas calculadas ───────────────────────────────────────────────────

    function wireColunasCalculadas() {
        document.getElementById('bie-add-calc')?.addEventListener('click', abrirFormularioCalc);
        document.getElementById('bie-calc-cancelar')?.addEventListener('click', fecharFormularioCalc);
        document.getElementById('bie-calc-testar')?.addEventListener('click', testarColunaCalculada);
        document.getElementById('bie-calc-confirmar')?.addEventListener('click', confirmarColunaCalculada);

        const exprEl = document.getElementById('bie-calc-expressao');
        if (exprEl) {
            wireAutocompleteExpressao(exprEl, () => [
                ...opcoesDeCampoTodasFontes(),
                ...FUNCOES_COLUNA_CALCULADA.map(f => [f, f + '(...)']),
            ]);
        }

        renderColunasCalculadas();
    }

    function abrirFormularioCalc() {
        if (!currentAnalysis.tabela) {
            mostrarErro('Selecione uma tabela base antes de criar uma coluna calculada.');
            return;
        }
        const nomeEl = document.getElementById('bie-calc-nome');
        const exprEl = document.getElementById('bie-calc-expressao');
        if (nomeEl) nomeEl.value = '';
        if (exprEl) exprEl.value = '';
        limparErroCalc();
        renderSeletorTipoCalc();
        document.getElementById('bie-calc-form')?.classList.remove('hidden');
    }

    function fecharFormularioCalc() {
        document.getElementById('bie-calc-form')?.classList.add('hidden');
    }

    function renderSeletorTipoCalc(valorAtual) {
        const wrap = document.getElementById('bie-calc-tipo-wrap');
        if (!wrap) return;
        wrap.innerHTML = '';
        const sel = selectDe(
            [['texto', 'Texto'], ['numero', 'Número'], ['data', 'Data'], ['booleano', 'Booleano']],
            valorAtual || 'numero'
        );
        wrap.appendChild(sel);
    }

    function mostrarErroCalc(msg, sucesso = false) {
        const el = document.getElementById('bie-calc-erro');
        if (!el) return;
        el.textContent = msg;
        el.classList.add('visible');
        el.classList.toggle('sucesso', sucesso);
    }

    function limparErroCalc() {
        const el = document.getElementById('bie-calc-erro');
        if (!el) return;
        el.textContent = '';
        el.classList.remove('visible', 'sucesso');
    }

    function lerFormularioCalc() {
        return {
            nome: document.getElementById('bie-calc-nome')?.value.trim() || '',
            expressao: document.getElementById('bie-calc-expressao')?.value.trim() || '',
            tipoResultado: document.getElementById('bie-calc-tipo-wrap')?.querySelector('select')?.value || 'numero',
        };
    }

    /**
     * Executa a expressão de verdade (spec sintético, limite:1) em vez de só validar
     * tokens — a validação de tokens é deliberadamente rasa (não garante WHEN depois de
     * CASE nem aridade de função certa), só a execução real no MySQL pega esse tipo de
     * erro de sintaxe.
     */
    async function testarColunaCalculada() {
        const { nome, expressao, tipoResultado } = lerFormularioCalc();
        limparErroCalc();
        if (!expressao) {
            mostrarErroCalc('Digite uma expressão para testar.');
            return;
        }
        const specTeste = Object.assign(analiseVazia(), {
            tabela: currentAnalysis.tabela,
            joins: currentAnalysis.joins,
            colunasCalculadas: [{ id: '_teste', nome: nome || 'teste', expressao, tipoResultado }],
            campos: ['calc._teste'],
            limite: 1,
        });
        try {
            const resp = await fetchJson(site + 'biexplorer/preview', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ spec: specTeste }),
            });
            if (resp.error) {
                mostrarErroCalc(resp.error);
                return;
            }
            mostrarErroCalc('✓ Expressão válida.', true);
        } catch (e) {
            mostrarErroCalc('Falha ao testar: ' + e.message);
        }
    }

    function confirmarColunaCalculada() {
        const { nome, expressao, tipoResultado } = lerFormularioCalc();
        if (!nome || !expressao) {
            mostrarErroCalc('Preencha nome e expressão.');
            return;
        }
        currentAnalysis.colunasCalculadas = currentAnalysis.colunasCalculadas || [];
        currentAnalysis.colunasCalculadas.push({ id: proximoId('cc'), nome, expressao, tipoResultado });
        fecharFormularioCalc();
        renderColunasCalculadas();
        renderTodosOsSeletores();
        agendarPreview();
    }

    function renderColunasCalculadas() {
        const container = document.getElementById('bie-calc-lista');
        if (!container) return;
        container.innerHTML = '';
        (currentAnalysis.colunasCalculadas || []).forEach(cc => {
            const row = document.createElement('div');
            row.className = 'bie-join-row'; // mesmo estilo visual das linhas de join
            const resumo = document.createElement('span');
            resumo.className = 'bie-join-resumo';
            resumo.innerHTML = '<b>' + cc.nome + '</b> = ' + cc.expressao;
            const btnRemover = document.createElement('button');
            btnRemover.className = 'bie-btn-remover';
            btnRemover.textContent = '✕';
            btnRemover.addEventListener('click', () => removerColunaCalculada(cc.id));
            row.append(resumo, btnRemover);
            container.appendChild(row);
        });
    }

    /** Remove a coluna calculada e qualquer referência exata a ela (id não é prefixo de outras). */
    function removerColunaCalculada(id) {
        currentAnalysis.colunasCalculadas = (currentAnalysis.colunasCalculadas || []).filter(cc => cc.id !== id);
        const valorQualificado = 'calc.' + id;

        currentAnalysis.campos = currentAnalysis.campos.filter(c => c !== valorQualificado);
        currentAnalysis.filtros = currentAnalysis.filtros.filter(f => f.campo !== valorQualificado);
        currentAnalysis.agrupamento.linhas = currentAnalysis.agrupamento.linhas.filter(c => c !== valorQualificado);
        currentAnalysis.agrupamento.metricas = currentAnalysis.agrupamento.metricas.filter(m => m.campo !== valorQualificado);

        renderColunasCalculadas();
        renderTodosOsSeletores();
        agendarPreview();
    }

    // ── Filtros visuais ──────────────────────────────────────────────────────

    function wireFiltros() {
        document.getElementById('bie-add-filtro')?.addEventListener('click', () => {
            const opcoes = opcoesDeCampoTodasFontes();
            if (!opcoes.length) return;
            const valor = opcoes[0][0];
            const meta = metaDeColunaQualificada(valor);
            const tipo = meta ? meta.tipo_logico : 'texto';
            currentAnalysis.filtros.push({
                id: proximoId('f'),
                campo: valor,
                tipo,
                operador: OPERADORES[tipo][0][0],
                valor: '',
            });
            renderFiltros();
            agendarPreview();
        });
        renderFiltros();
    }

    function renderFiltros() {
        const container = document.getElementById('bie-filtros-lista');
        if (!container) return;
        container.innerHTML = '';
        currentAnalysis.filtros.forEach(f => container.appendChild(linhaFiltro(f)));
    }

    function linhaFiltro(f) {
        const row = document.createElement('div');
        row.className = 'bie-filtro-row';

        const selCampo = criarSeletorCampo(opcoesDeCampoTodasFontes(), f.campo, (novoValor) => {
            const meta = metaDeColunaQualificada(novoValor);
            f.campo = novoValor;
            f.tipo = meta ? meta.tipo_logico : 'texto';
            f.operador = OPERADORES[f.tipo][0][0];
            f.valor = '';
            renderFiltros();
            agendarPreview();
        });

        const selOperador = selectDe(OPERADORES[f.tipo] || OPERADORES.texto, f.operador);
        selOperador.addEventListener('change', () => { f.operador = selOperador.value; renderFiltros(); agendarPreview(); });

        const valorEl = campoValorFiltro(f);

        const btnRemover = document.createElement('button');
        btnRemover.className = 'bie-btn-remover';
        btnRemover.textContent = '✕';
        btnRemover.addEventListener('click', () => {
            currentAnalysis.filtros = currentAnalysis.filtros.filter(x => x.id !== f.id);
            renderFiltros();
            agendarPreview();
        });

        row.append(selCampo, selOperador, valorEl, btnRemover);
        return row;
    }

    function campoValorFiltro(f) {
        const wrap = document.createElement('span');
        wrap.className = 'bie-filtro-valor';

        if (['preenchido', 'vazio'].includes(f.operador)) {
            return wrap; // sem input de valor — "preenchido"/"vazio" não comparam com nada
        }

        if (f.tipo === 'data' && ['hoje', 'ontem', 'esta_semana', 'este_mes', 'este_ano'].includes(f.operador)) {
            return wrap; // sem input de valor — operador já define a data
        }

        if (f.tipo === 'data' && f.operador === 'intervalo') {
            if (typeof f.valor !== 'object' || f.valor === null) f.valor = { inicio: '', fim: '' };
            const inicio = document.createElement('input');
            inicio.type = 'date'; inicio.value = f.valor.inicio || '';
            inicio.addEventListener('change', () => { f.valor.inicio = inicio.value; agendarPreview(); });
            const fim = document.createElement('input');
            fim.type = 'date'; fim.value = f.valor.fim || '';
            fim.addEventListener('change', () => { f.valor.fim = fim.value; agendarPreview(); });
            wrap.append(inicio, document.createTextNode(' até '), fim);
            return wrap;
        }

        if (f.tipo === 'numero' && f.operador === 'entre') {
            if (typeof f.valor !== 'object' || f.valor === null) f.valor = { min: '', max: '' };
            const min = document.createElement('input');
            min.type = 'number'; min.value = f.valor.min ?? '';
            min.addEventListener('input', () => { f.valor.min = min.value; agendarPreview(); });
            const max = document.createElement('input');
            max.type = 'number'; max.value = f.valor.max ?? '';
            max.addEventListener('input', () => { f.valor.max = max.value; agendarPreview(); });
            wrap.append(min, document.createTextNode(' e '), max);
            return wrap;
        }

        if (f.tipo === 'texto' && f.operador === 'lista') {
            if (!Array.isArray(f.valor)) f.valor = [];
            const input = document.createElement('input');
            input.type = 'text';
            input.placeholder = 'valores separados por vírgula';
            input.value = f.valor.join(',');
            input.addEventListener('input', () => {
                f.valor = input.value.split(',').map(s => s.trim()).filter(Boolean);
                agendarPreview();
            });
            wrap.appendChild(input);
            return wrap;
        }

        if (f.tipo === 'booleano') {
            return wrap; // operador (sim/não) já define o valor
        }

        const input = document.createElement('input');
        input.type = f.tipo === 'numero' ? 'number' : 'text';
        input.value = typeof f.valor === 'string' || typeof f.valor === 'number' ? f.valor : '';
        input.addEventListener('input', () => { f.valor = input.value; agendarPreview(); });
        wrap.appendChild(input);
        return wrap;
    }

    // ── Agrupamento + métricas ───────────────────────────────────────────────

    function wireAgrupamento() {
        document.getElementById('bie-add-metrica')?.addEventListener('click', () => {
            if (!currentAnalysis.tabela) return;
            currentAnalysis.agrupamento.metricas.push({
                id: proximoId('m'),
                funcao: 'COUNT',
                campo: '*',
                alias: 'contagem',
            });
            renderMetricas();
            agendarPreview();
        });
        renderAgrupamentoLinhas();
        renderMetricas();
    }

    function renderAgrupamentoLinhas() {
        const container = document.getElementById('bie-agrupamento-linhas');
        if (!container) return;
        container.innerHTML = '';
        const aliases = Object.keys(colunasPorAlias);
        const mostrarGrupo = aliases.length > 1;
        aliases.forEach(alias => {
            const fonte = colunasPorAlias[alias];
            if (mostrarGrupo && fonte.colunas.length) {
                const cab = document.createElement('div');
                cab.className = 'bie-coluna-grupo-titulo';
                cab.textContent = fonte.tabela;
                container.appendChild(cab);
            }
            fonte.colunas.forEach(c => {
                const valorQualificado = alias === 't' ? c.nome : (alias + '.' + c.nome);
                const marcado = currentAnalysis.agrupamento.linhas.includes(valorQualificado);
                const label = document.createElement('label');
                label.className = 'bie-coluna-item';
                label.innerHTML = '<input type="checkbox"' + (marcado ? ' checked' : '') + '> ' + c.nome;
                label.querySelector('input').addEventListener('change', (e) => {
                    const arr = currentAnalysis.agrupamento.linhas;
                    const idx = arr.indexOf(valorQualificado);
                    if (e.target.checked && idx === -1) arr.push(valorQualificado);
                    if (!e.target.checked && idx !== -1) arr.splice(idx, 1);
                    // agrupamento.linhas afeta as opções de ordenação e o eixo X do gráfico —
                    // nada além disso precisa reconstruir (filtros/métricas ficam intactos).
                    renderOrdenacao();
                    renderSeletoresGrafico();
                    agendarPreview();
                });
                container.appendChild(label);
            });
        });

        if ((currentAnalysis.colunasCalculadas || []).length) {
            const cab = document.createElement('div');
            cab.className = 'bie-coluna-grupo-titulo';
            cab.textContent = 'Calculadas';
            container.appendChild(cab);
            currentAnalysis.colunasCalculadas.forEach(cc => {
                const valorQualificado = 'calc.' + cc.id;
                const marcado = currentAnalysis.agrupamento.linhas.includes(valorQualificado);
                const label = document.createElement('label');
                label.className = 'bie-coluna-item';
                label.innerHTML = '<input type="checkbox"' + (marcado ? ' checked' : '') + '> ' + cc.nome;
                label.querySelector('input').addEventListener('change', (e) => {
                    const arr = currentAnalysis.agrupamento.linhas;
                    const idx = arr.indexOf(valorQualificado);
                    if (e.target.checked && idx === -1) arr.push(valorQualificado);
                    if (!e.target.checked && idx !== -1) arr.splice(idx, 1);
                    renderOrdenacao();
                    renderSeletoresGrafico();
                    agendarPreview();
                });
                container.appendChild(label);
            });
        }
    }

    function renderMetricas() {
        const container = document.getElementById('bie-metricas-lista');
        if (!container) return;
        container.innerHTML = '';
        currentAnalysis.agrupamento.metricas.forEach((m, idx) => container.appendChild(linhaMetrica(m, idx)));
    }

    function linhaMetrica(m, idx) {
        const row = document.createElement('div');
        row.className = 'bie-metrica-row';
        const ehFormula = m.tipo === 'formula';

        const btnTipo = document.createElement('button');
        btnTipo.type = 'button';
        btnTipo.className = 'bie-metrica-toggle-tipo';
        btnTipo.textContent = ehFormula ? 'Fórmula' : 'Função';
        btnTipo.title = 'Alternar entre função (SUM/COUNT/...) e fórmula (combina outras métricas)';
        btnTipo.addEventListener('click', () => {
            if (ehFormula) {
                m.tipo = 'funcao';
                if (!m.funcao) { m.funcao = 'COUNT'; m.campo = '*'; }
            } else {
                m.tipo = 'formula';
                if (m.expressao === undefined) m.expressao = '';
            }
            renderMetricas();
            agendarPreview();
        });
        row.appendChild(btnTipo);

        if (ehFormula) {
            // só métricas ANTERIORES a esta na lista podem ser referenciadas — reforço
            // de UX; o backend detecta ciclo de verdade por grafo, não confia na ordem.
            const anteriores = currentAnalysis.agrupamento.metricas.slice(0, idx);
            const dica = document.createElement('span');
            dica.className = 'bie-metrica-dica';
            dica.textContent = anteriores.length
                ? 'Disponíveis: ' + anteriores.map(x => x.id).join(', ')
                : 'Crie outra métrica antes desta para poder referenciá-la.';

            const inputExpr = document.createElement('input');
            inputExpr.type = 'text';
            inputExpr.placeholder = 'Ex.: m1 / m2';
            inputExpr.value = m.expressao || '';
            inputExpr.addEventListener('input', () => { m.expressao = inputExpr.value; agendarPreview(); });
            wireAutocompleteExpressao(inputExpr, () => anteriores.map(x => [x.id, x.id + (x.alias ? ' (' + x.alias + ')' : '')]));

            row.append(inputExpr, dica);
        } else {
            const selFuncao = selectDe(FUNCOES_METRICA.map(f => [f, f]), m.funcao);
            selFuncao.addEventListener('change', () => {
                m.funcao = selFuncao.value;
                if (m.funcao !== 'COUNT') m.campo = opcoesDeCampoTodasFontes()[0]?.[0] || '';
                renderMetricas();
                agendarPreview();
            });

            const opcoesCampo = m.funcao === 'COUNT'
                ? [['*', '* (todas as linhas)'], ...opcoesDeCampoTodasFontes()]
                : opcoesDeCampoTodasFontes();
            const selCampo = criarSeletorCampo(opcoesCampo, m.campo, (novoValor) => { m.campo = novoValor; agendarPreview(); });

            row.append(selFuncao, selCampo);
        }

        const inputAlias = document.createElement('input');
        inputAlias.type = 'text';
        inputAlias.value = m.alias;
        inputAlias.addEventListener('input', () => {
            m.alias = inputAlias.value;
            // NUNCA chamar renderMetricas()/renderTodosOsSeletores() aqui — isso
            // recriaria este mesmo <input> a cada tecla digitada, apagando o foco e
            // qualquer caractere seguinte. Só o rótulo do gráfico precisa refletir o
            // alias em tempo real; ordenação/filtros/métricas continuam intactos.
            renderSeletoresGrafico();
            agendarPreview();
        });
        row.appendChild(inputAlias);

        const btnRemover = document.createElement('button');
        btnRemover.className = 'bie-btn-remover';
        btnRemover.textContent = '✕';
        btnRemover.addEventListener('click', () => {
            currentAnalysis.agrupamento.metricas = currentAnalysis.agrupamento.metricas.filter(x => x.id !== m.id);
            renderMetricas();
            renderOrdenacao();
            renderSeletoresGrafico();
            agendarPreview();
        });
        row.appendChild(btnRemover);

        return row;
    }

    // ── Ordenação ────────────────────────────────────────────────────────────

    function wireOrdenacao() {
        document.getElementById('bie-add-ordenacao')?.addEventListener('click', () => {
            const opcoes = colunasDisponiveisParaOrdenacao();
            if (!opcoes.length) return;
            currentAnalysis.ordenacao.push({ campo: opcoes[0], direcao: 'ASC' });
            renderOrdenacao();
            agendarPreview();
        });
        renderOrdenacao();
    }

    function colunasDisponiveisParaOrdenacao() {
        const temAgrupamento = currentAnalysis.agrupamento.linhas.length || currentAnalysis.agrupamento.metricas.length;
        if (temAgrupamento) {
            return [...currentAnalysis.agrupamento.linhas, ...currentAnalysis.agrupamento.metricas.map(m => m.alias)];
        }
        return [...currentAnalysis.campos];
    }

    function renderOrdenacao() {
        const container = document.getElementById('bie-ordenacao-lista');
        if (!container) return;
        container.innerHTML = '';
        const opcoes = colunasDisponiveisParaOrdenacao();
        currentAnalysis.ordenacao.forEach((o, idx) => {
            const row = document.createElement('div');
            row.className = 'bie-ordenacao-row';

            const selCampo = criarSeletorCampo(opcoes.map(c => [c, c]), o.campo, (novoValor) => { o.campo = novoValor; agendarPreview(); });

            const selDirecao = selectDe([['ASC', 'Crescente'], ['DESC', 'Decrescente']], o.direcao);
            selDirecao.addEventListener('change', () => { o.direcao = selDirecao.value; agendarPreview(); });

            const btnRemover = document.createElement('button');
            btnRemover.className = 'bie-btn-remover';
            btnRemover.textContent = '✕';
            btnRemover.addEventListener('click', () => {
                currentAnalysis.ordenacao.splice(idx, 1);
                renderOrdenacao();
                agendarPreview();
            });

            row.append(selCampo, selDirecao, btnRemover);
            container.appendChild(row);
        });
    }

    // ── Gráfico (ApexCharts) ─────────────────────────────────────────────────

    function wireGrafico() {
        const selTipo = document.getElementById('bie-grafico-tipo');
        selTipo?.addEventListener('change', () => {
            currentAnalysis.grafico.tipo = selTipo.value;
            renderizarApex(ultimasLinhas);
        });
        const selEixoX = document.getElementById('bie-grafico-eixox');
        selEixoX?.addEventListener('change', () => {
            currentAnalysis.grafico.eixoX = selEixoX.value;
            renderizarApex(ultimasLinhas);
        });
        const selSeries = document.getElementById('bie-grafico-series');
        selSeries?.addEventListener('change', () => {
            currentAnalysis.grafico.series = Array.from(selSeries.selectedOptions).map(o => o.value);
            renderizarApex(ultimasLinhas);
        });
    }

    let ultimasLinhas = [];

    function atualizarGrafico(linhas) {
        ultimasLinhas = linhas;
        const temAgrupamento = currentAnalysis.agrupamento.linhas.length && currentAnalysis.agrupamento.metricas.length;
        const secao = document.getElementById('bie-secao-grafico');
        if (secao) secao.classList.toggle('hidden', !temAgrupamento);
        if (!temAgrupamento) return;

        if (!currentAnalysis.grafico.eixoX) currentAnalysis.grafico.eixoX = currentAnalysis.agrupamento.linhas[0];
        if (!currentAnalysis.grafico.series.length) {
            currentAnalysis.grafico.series = [currentAnalysis.agrupamento.metricas[0].alias];
        }
        // Isto roda a cada resposta bem-sucedida de /biexplorer/preview (a cada ~400ms
        // de qualquer mudança) — NUNCA usar renderTodosOsSeletores() aqui: destruiria
        // filtros/métricas/colunas bem no meio de uma digitação do usuário em outro
        // combobox. Só as opções do próprio gráfico dependem do que mudou aqui.
        renderSeletoresGrafico();
        renderizarApex(linhas);
    }

    function renderizarApex(linhas) {
        const el = document.getElementById('bie-grafico');
        if (!el || !linhas || !linhas.length) return;
        const { tipo, eixoX, series } = currentAnalysis.grafico;
        if (!eixoX || !series.length) return;

        const categorias = linhas.map(l => l[eixoX]);
        const apexTipo = { barra: 'bar', linha: 'line', area: 'area', pizza: 'pie', rosca: 'donut' }[tipo] || 'bar';

        let options;
        if (apexTipo === 'pie' || apexTipo === 'donut') {
            options = {
                chart: { type: apexTipo, height: 320 },
                labels: categorias,
                series: linhas.map(l => Number(l[series[0]]) || 0),
            };
        } else {
            options = {
                chart: { type: apexTipo, height: 320, toolbar: { show: false } },
                xaxis: { categories: categorias },
                series: series.map(s => ({ name: s, data: linhas.map(l => Number(l[s]) || 0) })),
            };
        }

        if (chart) { chart.destroy(); }
        chart = new ApexCharts(el, options);
        chart.render();
    }

    // ── Seletores dependentes (filtros/ordenação/gráfico) ───────────────────

    function renderTodosOsSeletores() {
        renderListaColunas();
        renderAgrupamentoLinhas();
        renderFiltros();
        renderMetricas();
        renderOrdenacao();
        renderSeletoresGrafico();
    }

    function renderSeletoresGrafico() {
        const selEixoX = document.getElementById('bie-grafico-eixox');
        const selSeries = document.getElementById('bie-grafico-series');
        const selTipo = document.getElementById('bie-grafico-tipo');
        if (!selEixoX || !selSeries || !selTipo) return;

        selTipo.innerHTML = '';
        TIPOS_GRAFICO.forEach(([v, l]) => selTipo.appendChild(new Option(l, v, false, v === currentAnalysis.grafico.tipo)));

        selEixoX.innerHTML = '';
        currentAnalysis.agrupamento.linhas.forEach(c => selEixoX.appendChild(new Option(c, c, false, c === currentAnalysis.grafico.eixoX)));

        selSeries.innerHTML = '';
        currentAnalysis.agrupamento.metricas.forEach(m => {
            selSeries.appendChild(new Option(m.alias, m.alias, false, currentAnalysis.grafico.series.includes(m.alias)));
        });
    }

    function selectDe(opcoes, valorAtual) {
        const sel = document.createElement('select');
        opcoes.forEach(([v, l]) => sel.appendChild(new Option(l, v, false, v === valorAtual)));
        sel.value = valorAtual;
        return sel;
    }

    // Combobox de campo atualmente aberto/focado, se houver ({wrap, fecharERestaurar}).
    // Um único listener de document (registrado uma vez abaixo) fecha o combobox
    // certo em vez de cada instância de criarSeletorCampo() registrar o seu próprio
    // — isso evitava um vazamento de listeners: toda vez que uma linha de filtro/
    // métrica/ordenação era recriada, o listener antigo nunca era removido.
    let comboDeCampoAberto = null;
    document.addEventListener('mousedown', (e) => {
        if (comboDeCampoAberto && !comboDeCampoAberto.wrap.contains(e.target)) {
            comboDeCampoAberto.fecharERestaurar();
            comboDeCampoAberto = null;
        }
    });

    /**
     * Combobox com busca — usado para escolher CAMPO (tabelas como dealV2 têm
     * dezenas de colunas; um <select> nativo obriga a rolar a lista inteira).
     * opcoes: [[valor, label], ...]. Chama aoSelecionar(valor) ao escolher um item.
     */
    function criarSeletorCampo(opcoes, valorAtual, aoSelecionar) {
        const wrap = document.createElement('span');
        wrap.className = 'bie-campo-select';

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'bie-campo-select-input';
        input.autocomplete = 'off';
        input.placeholder = 'Buscar campo...';

        const lista = document.createElement('div');
        lista.className = 'bie-campo-select-lista hidden';

        function labelDe(valor) {
            const achado = opcoes.find(([v]) => v === valor);
            return achado ? achado[1] : (valor || '');
        }
        input.value = labelDe(valorAtual);

        function render(filtro) {
            const q = (filtro || '').toLowerCase().trim();
            lista.innerHTML = '';
            const filtradas = opcoes.filter(([, l]) => !q || l.toLowerCase().includes(q));
            if (!filtradas.length) {
                const vazio = document.createElement('div');
                vazio.className = 'bie-campo-select-vazio';
                vazio.textContent = 'Nenhum campo encontrado';
                lista.appendChild(vazio);
                return;
            }
            filtradas.forEach(([v, l]) => {
                const item = document.createElement('div');
                item.className = 'bie-campo-select-item' + (v === valorAtual ? ' selected' : '');
                item.textContent = l;
                item.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    valorAtual = v;
                    input.value = l;
                    fechar();
                    aoSelecionar(v);
                });
                lista.appendChild(item);
            });
        }

        function abrir() { render(''); lista.classList.remove('hidden'); }
        function fechar() { lista.classList.add('hidden'); }
        function fecharERestaurar() {
            fechar();
            input.value = labelDe(valorAtual);
            if (comboDeCampoAberto === instancia) comboDeCampoAberto = null;
        }

        const instancia = { wrap, fecharERestaurar };

        input.addEventListener('focus', () => { input.select(); abrir(); comboDeCampoAberto = instancia; });
        input.addEventListener('input', () => render(input.value));
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') { input.blur(); fecharERestaurar(); }
            if (e.key === 'Enter') {
                e.preventDefault();
                const primeiro = lista.querySelector('.bie-campo-select-item');
                primeiro?.dispatchEvent(new MouseEvent('mousedown'));
            }
        });

        wrap.append(input, lista);
        return wrap;
    }

    /**
     * Autocomplete inline para um <textarea>/<input> de expressão livre (coluna
     * calculada, fórmula de métrica) — sugere conforme o usuário digita, sem
     * interromper o restante da expressão já escrita. Detecta a "palavra atual"
     * sob o cursor (letras/números/_/. — cabe "j_1.coluna"), filtra as sugestões
     * por substring, e insere só naquele trecho ao aceitar. obterSugestoes() é
     * chamado a cada tecla (não uma vez só), então sempre reflete o estado atual
     * (novas colunas calculadas, joins, métricas anteriores etc.).
     */
    function wireAutocompleteExpressao(elemento, obterSugestoes) {
        const lista = document.createElement('div');
        lista.className = 'bie-campo-select-lista hidden';
        const pai = elemento.parentElement;
        if (pai && getComputedStyle(pai).position === 'static') {
            pai.style.position = 'relative';
        }
        pai?.appendChild(lista);

        function palavraAtual() {
            const cursor = elemento.selectionStart ?? elemento.value.length;
            const texto = elemento.value;
            let inicio = cursor;
            while (inicio > 0 && /[A-Za-z0-9_.]/.test(texto[inicio - 1])) inicio--;
            return { inicio, fim: cursor, texto: texto.slice(inicio, cursor) };
        }

        function render() {
            const { texto } = palavraAtual();
            if (texto.length < 1) {
                fechar();
                return;
            }
            const q = texto.toLowerCase();
            const sugestoes = obterSugestoes()
                .filter(([v, l]) => v.toLowerCase().includes(q) || l.toLowerCase().includes(q))
                .slice(0, 30);
            if (!sugestoes.length) {
                fechar();
                return;
            }
            lista.innerHTML = '';
            sugestoes.forEach(([v, l]) => {
                const item = document.createElement('div');
                item.className = 'bie-campo-select-item';
                item.textContent = l;
                item.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    inserir(v);
                });
                lista.appendChild(item);
            });
            lista.classList.remove('hidden');
        }

        function inserir(valor) {
            const { inicio, fim } = palavraAtual();
            const texto = elemento.value;
            elemento.value = texto.slice(0, inicio) + valor + texto.slice(fim);
            const novoCursor = inicio + valor.length;
            elemento.setSelectionRange(novoCursor, novoCursor);
            elemento.dispatchEvent(new Event('input')); // sincroniza com o listener já existente do campo
            fechar();
            elemento.focus();
        }

        function fechar() {
            lista.classList.add('hidden');
        }

        elemento.addEventListener('input', render);
        elemento.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                fechar();
                return;
            }
            if (!lista.classList.contains('hidden') && (e.key === 'Enter' || e.key === 'Tab')) {
                const primeiro = lista.querySelector('.bie-campo-select-item');
                if (primeiro) {
                    e.preventDefault(); // só quando a lista está aberta — não quebra Enter de quebra de linha
                    primeiro.dispatchEvent(new MouseEvent('mousedown'));
                }
            }
        });
        document.addEventListener('mousedown', (e) => {
            if (pai && !pai.contains(e.target)) fechar();
        });
    }

    // ── Aba SQL manual ───────────────────────────────────────────────────────

    function wireSqlManual() {
        document.getElementById('bie-tab-visual')?.addEventListener('click', () => trocarModo('visual'));
        document.getElementById('bie-tab-sql')?.addEventListener('click', () => trocarModo('sql'));
        document.getElementById('bie-executar-sql')?.addEventListener('click', executarSqlManual);
    }

    function trocarModo(modo) {
        currentAnalysis.modo = modo;
        document.getElementById('bie-tab-visual')?.classList.toggle('active', modo === 'visual');
        document.getElementById('bie-tab-sql')?.classList.toggle('active', modo === 'sql');
        document.getElementById('bie-painel-config')?.classList.toggle('hidden', modo === 'sql');
        document.getElementById('bie-painel-dados')?.classList.toggle('hidden', modo === 'sql');
        document.getElementById('bie-sql-manual-wrap')?.classList.toggle('hidden', modo === 'visual');
        document.getElementById('bie-secao-grafico')?.classList.toggle('hidden', modo === 'sql');
        marcarSujo();
        if (modo === 'visual') agendarPreview();
    }

    async function executarSqlManual() {
        const textarea = document.getElementById('bie-sql-manual-input');
        currentAnalysis.sqlManual = textarea.value;
        mostrarCarregando(true);
        limparErro();
        try {
            const resp = await fetchJson(site + 'biexplorer/sql', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sql: textarea.value }),
            });

            if (resp.error) { mostrarErro(resp.error); return; }
            document.getElementById('bie-sql-texto').textContent = resp.sql || '';
            atualizarGrid(resp.colunas || [], resp.linhas || []);
        } catch (e) {
            mostrarErro('Falha ao executar SQL: ' + e.message);
        } finally {
            mostrarCarregando(false);
        }
    }

    // ── Painéis recolhíveis (mais espaço pro grid de análise) ───────────────

    function wirePaineis() {
        wirePainelToggle('bie-toggle-dados', 'bie-painel-dados', 'dados-collapsed', '‹', '›');
        wirePainelToggle('bie-toggle-config', 'bie-painel-config', 'config-collapsed', '›', '‹');
    }

    function wirePainelToggle(btnId, panelId, classeLayout, iconeExpandido, iconeRecolhido) {
        const btn = document.getElementById(btnId);
        const panel = document.getElementById(panelId);
        const layout = document.querySelector('.bie-layout');
        if (!btn || !panel || !layout) return;

        const chaveLS = 'biexplorer_' + classeLayout;
        let recolhido = localStorage.getItem(chaveLS) === '1';

        function aplicar() {
            panel.classList.toggle('collapsed', recolhido);
            layout.classList.toggle(classeLayout, recolhido);
            btn.textContent = recolhido ? iconeRecolhido : iconeExpandido;
            btn.title = recolhido ? 'Expandir painel' : 'Recolher painel';
        }
        aplicar();

        btn.addEventListener('click', () => {
            recolhido = !recolhido;
            localStorage.setItem(chaveLS, recolhido ? '1' : '0');
            aplicar();
            // o grid tem ResizeObserver próprio, mas um empurrão depois da transição
            // de largura evita qualquer sobra de layout stale em navegadores mais lentos.
            setTimeout(() => gridApi?.resetRowHeights?.(), 220);
        });
    }

    // ── Toolbar: Nova / Salvar / Duplicar / Carregar / Atualizar ────────────

    function wireToolbar() {
        document.getElementById('bie-novo')?.addEventListener('click', novaAnalise);
        document.getElementById('bie-salvar')?.addEventListener('click', salvarAnalise);
        document.getElementById('bie-duplicar')?.addEventListener('click', duplicarAnalise);
        document.getElementById('bie-atualizar')?.addEventListener('click', carregarPreview);
        document.getElementById('bie-carregar-btn')?.addEventListener('click', abrirPainelCarregar);
        document.getElementById('bie-exportar-csv')?.addEventListener('click', () => gridApi?.exportDataAsCsv());
        document.getElementById('bie-nome')?.addEventListener('input', marcarSujo);
    }

    function novaAnalise() {
        currentAnalysis = analiseVazia();
        analiseId = null;
        colunasPorAlias = {};
        document.getElementById('bie-nome').value = '';
        document.getElementById('bie-lista-colunas').innerHTML = '';
        gridApi.setGridOption('rowData', []);
        gridApi.setGridOption('columnDefs', []);
        renderTodosOsSeletores();
        renderJoins();
        renderColunasCalculadas();
        document.getElementById('bie-sql-texto').textContent = '';
        limparSujo();
        limparErro();
        document.querySelectorAll('#bie-lista-tabelas .bie-tabela-item').forEach(el => el.classList.remove('selected'));
    }

    async function salvarAnalise() {
        const nome = document.getElementById('bie-nome').value.trim();
        if (!nome) { mostrarErro('Informe um nome para a análise antes de salvar.'); return; }
        try {
            const resp = await fetchJson(site + 'biexplorer/salvar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: analiseId, nome, spec: currentAnalysis }),
            });
            if (resp.error) { mostrarErro(resp.error); return; }
            analiseId = resp.id;
            limparSujo();
        } catch (e) {
            mostrarErro('Falha ao salvar: ' + e.message);
        }
    }

    async function duplicarAnalise() {
        if (!analiseId) { mostrarErro('Salve a análise antes de duplicar.'); return; }
        try {
            const resp = await fetchJson(site + 'biexplorer/duplicar/' + encodeURIComponent(analiseId), { method: 'POST' });
            if (resp.error) { mostrarErro(resp.error); return; }
            analiseId = resp.id;
            document.getElementById('bie-nome').value = resp.nome;
            marcarSujo();
        } catch (e) {
            mostrarErro('Falha ao duplicar: ' + e.message);
        }
    }

    async function abrirPainelCarregar() {
        const painel = document.getElementById('bie-carregar-painel');
        if (!painel) return;
        const abrindo = painel.classList.contains('hidden');
        painel.classList.toggle('hidden');
        if (!abrindo) return;

        try {
            const resp = await fetchJson(site + 'biexplorer/listar', { cache: 'no-store' });
            painel.innerHTML = '';
            (resp.analises || []).forEach(a => {
                const item = document.createElement('div');
                item.className = 'bie-carregar-item';
                item.textContent = a.nome + (a.descricao ? ' — ' + a.descricao : '');
                item.addEventListener('click', () => carregarAnalise(a.id));
                painel.appendChild(item);
            });
            if (!(resp.analises || []).length) {
                painel.innerHTML = '<div class="bie-carregar-vazio">Nenhuma análise salva.</div>';
            }
        } catch (e) {
            mostrarErro('Falha ao listar análises: ' + e.message);
        }
    }

    async function carregarAnalise(id) {
        try {
            const resp = await fetchJson(site + 'biexplorer/carregar/' + encodeURIComponent(id), { cache: 'no-store' });
            if (resp.error) { mostrarErro(resp.error); return; }

            analiseId = resp.id;
            currentAnalysis = Object.assign(analiseVazia(), resp.spec || {});
            document.getElementById('bie-nome').value = resp.nome || '';
            document.getElementById('bie-carregar-painel').classList.add('hidden');

            colunasPorAlias = {};
            if (currentAnalysis.tabela) {
                const colResp = await fetchJson(site + 'biexplorer/colunas/' + encodeURIComponent(currentAnalysis.tabela), { cache: 'no-store' });
                colunasPorAlias.t = { tabela: currentAnalysis.tabela, colunas: colResp.colunas || [] };
            }
            for (const j of currentAnalysis.joins || []) {
                const colResp = await fetchJson(site + 'biexplorer/colunas/' + encodeURIComponent(j.tabela), { cache: 'no-store' });
                colunasPorAlias[j.id] = { tabela: j.tabela, colunas: colResp.colunas || [] };
            }
            renderTodosOsSeletores();
            renderJoins();
            renderColunasCalculadas();
            trocarModo(currentAnalysis.modo || 'visual');
            limparSujo();
            agendarPreview();
        } catch (e) {
            mostrarErro('Falha ao carregar análise: ' + e.message);
        }
    }

    // ── Autosave ─────────────────────────────────────────────────────────────

    function autosaveSeSujo() {
        if (!dirty || !currentAnalysis.tabela) return;
        const id = analiseId || draftId;
        fetch(site + 'biexplorer/autosave', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, spec: currentAnalysis }),
        }).catch(() => {});
    }

    return { init };
})();
