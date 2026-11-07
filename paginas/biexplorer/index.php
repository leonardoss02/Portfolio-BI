<div class="dash-biexplorer">
<style>
.dash-biexplorer { display: flex; flex-direction: column; gap: 12px; padding: 16px 20px; height: calc(100vh - 0px); box-sizing: border-box; }

.bie-toolbar {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    background: var(--hdr-to); padding: 10px 14px; border-radius: var(--r);
    position: relative;
}
.bie-toolbar input#bie-nome {
    flex: 1 1 220px; min-width: 160px; padding: 6px 10px; border-radius: var(--r-sm);
    border: 1px solid var(--border); background: var(--card); color: var(--text);
}
.bie-toolbar button {
    padding: 6px 12px; border-radius: var(--r-sm); border: 1px solid var(--border);
    background: var(--card); color: var(--text); cursor: pointer; font-size: 13px;
}
.bie-toolbar button.active { background: var(--consulta); color: #fff; border-color: var(--consulta); }
#bie-dirty-badge {
    font-size: 11px; color: var(--sinal); border: 1px solid var(--sinal); border-radius: 999px;
    padding: 2px 8px;
}
#bie-dirty-badge.hidden { display: none; }

.bie-erro {
    display: none; background: var(--danger-glow); color: var(--danger);
    border: 1px solid var(--danger); border-radius: var(--r-sm); padding: 8px 12px; font-size: 13px;
}
.bie-erro.visible { display: block; }
.bie-erro.sucesso { background: var(--fecho-glow); color: var(--fecho); border-color: var(--fecho); }

.bie-layout {
    display: grid; grid-template-columns: 240px 1fr 300px; gap: 12px; flex: 1; min-height: 0;
    transition: grid-template-columns .2s ease;
}
.bie-layout.dados-collapsed { grid-template-columns: 40px 1fr 300px; }
.bie-layout.config-collapsed { grid-template-columns: 240px 1fr 40px; }
.bie-layout.dados-collapsed.config-collapsed { grid-template-columns: 40px 1fr 40px; }

.bie-panel {
    background: var(--card); border: 1px solid var(--border); border-radius: var(--r);
    box-shadow: var(--sh-sm); padding: 10px; overflow-y: auto; overflow-x: hidden;
}
.bie-panel.hidden { display: none; }

.bie-panel-header { display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 8px; }
.bie-panel-titulo { font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--muted); white-space: nowrap; }
.bie-panel-toggle {
    border: 1px solid var(--border); background: var(--card2); color: var(--text);
    border-radius: var(--r-sm); width: 22px; height: 22px; min-width: 22px; padding: 0;
    display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 13px; line-height: 1;
}
.bie-panel-toggle:hover { background: var(--border); }

.bie-panel.collapsed { padding: 10px 6px; }
.bie-panel.collapsed .bie-panel-header { flex-direction: column-reverse; gap: 8px; }
.bie-panel.collapsed .bie-panel-titulo { display: none; }
.bie-panel.collapsed .bie-panel-body { display: none; }
.bie-panel input[type="text"], .bie-panel input#bie-busca-tabela, .bie-panel input#bie-busca-coluna {
    width: 100%; box-sizing: border-box; padding: 6px 8px; margin-bottom: 8px;
    border-radius: var(--r-sm); border: 1px solid var(--border); background: var(--card2); color: var(--text);
}

.bie-lista { display: flex; flex-direction: column; gap: 2px; }
.bie-tabela-item, .bie-coluna-item {
    padding: 5px 8px; border-radius: var(--r-sm); cursor: pointer; font-size: 13px;
    display: flex; align-items: center; gap: 6px;
}
.bie-tabela-item:hover, .bie-coluna-item:hover { background: var(--card2); }
.bie-tabela-item.selected { background: var(--consulta-glow); color: var(--consulta); font-weight: 600; }
.bie-coluna-item { cursor: default; }
.bie-coluna-icone { opacity: .7; }
.bie-coluna-grupo-titulo {
    font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--muted);
    margin: 8px 0 2px; padding: 0 4px;
}
.bie-coluna-grupo-titulo:first-child { margin-top: 0; }

.bie-join-row {
    display: flex; align-items: center; justify-content: space-between; gap: 6px;
    padding: 6px 8px; border-radius: var(--r-sm); background: var(--card2);
    font-size: 12px; margin-bottom: 6px;
}
.bie-join-resumo { overflow-wrap: anywhere; }
#bie-join-form { margin-top: 6px; }
#bie-join-form.hidden, #bie-join-chaves.hidden { display: none; }
.bie-join-chave-row {
    display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 6px;
}
.bie-join-chave-label { font-size: 11px; color: var(--muted); flex-shrink: 0; }

.bie-central { display: flex; flex-direction: column; gap: 12px; min-width: 0; }
#bie-grid { width: 100%; height: 420px; }

#bie-sql-manual-wrap.hidden, .bie-grafico-secao.hidden { display: none; }
#bie-sql-manual-input {
    width: 100%; height: 260px; box-sizing: border-box; font-family: var(--font-mono);
    font-size: 13px; padding: 10px; border-radius: var(--r-sm); border: 1px solid var(--border);
    background: var(--card2); color: var(--text); resize: vertical;
}

.bie-grafico-secao { background: var(--card); border: 1px solid var(--border); border-radius: var(--r); padding: 12px; }
.bie-grafico-controles { display: flex; gap: 14px; margin-bottom: 8px; flex-wrap: wrap; }
.bie-grafico-controles label { font-size: 12px; color: var(--muted); display: flex; flex-direction: column; gap: 3px; }
.bie-grafico-controles select { padding: 4px 6px; border-radius: var(--r-sm); border: 1px solid var(--border); background: var(--card2); color: var(--text); }

.bie-panel details { margin-bottom: 10px; }
.bie-panel summary { cursor: pointer; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); padding: 4px 0; }
.bie-filtro-row, .bie-metrica-row, .bie-ordenacao-row {
    display: flex; align-items: center; gap: 4px; margin-bottom: 6px; flex-wrap: wrap;
}
.bie-filtro-row select, .bie-metrica-row select, .bie-ordenacao-row select,
.bie-filtro-valor input, .bie-metrica-row input {
    font-size: 12px; padding: 4px 6px; border-radius: var(--r-sm); border: 1px solid var(--border);
    background: var(--card2); color: var(--text); max-width: 110px;
}
.bie-filtro-valor { display: flex; align-items: center; gap: 4px; }
.bie-filtro-valor input { max-width: 90px; }
.bie-btn-remover {
    border: none; background: transparent; color: var(--danger); cursor: pointer; font-size: 12px; padding: 2px 4px;
}

.bie-metrica-toggle-tipo {
    font-size: 11px; padding: 3px 8px; border-radius: 999px; border: 1px solid var(--consulta);
    background: var(--consulta-glow); color: var(--consulta); cursor: pointer; flex-shrink: 0;
}
.bie-metrica-dica { font-size: 10px; color: var(--muted); flex-basis: 100%; }
.bie-metrica-row input[type="text"] { max-width: 140px; }

#bie-calc-form { margin-top: 6px; display: flex; flex-direction: column; gap: 6px; }
#bie-calc-form.hidden { display: none; }
#bie-calc-form input, #bie-calc-form textarea {
    width: 100%; box-sizing: border-box; font-size: 12px; padding: 6px 8px;
    border-radius: var(--r-sm); border: 1px solid var(--border); background: var(--card2); color: var(--text);
}
#bie-calc-expressao { font-family: var(--font-mono); height: 60px; resize: vertical; }
#bie-calc-erro { font-size: 11px; padding: 6px 8px; }

.bie-campo-select { position: relative; display: inline-block; }
.bie-campo-select-input {
    font-size: 12px; padding: 4px 6px; border-radius: var(--r-sm); border: 1px solid var(--border);
    background: var(--card2); color: var(--text); width: 130px; box-sizing: border-box;
}
.bie-campo-select-lista {
    position: absolute; top: 100%; left: 0; z-index: 30; margin-top: 2px;
    background: var(--card); border: 1px solid var(--border); border-radius: var(--r-sm);
    box-shadow: var(--sh-lg); min-width: 220px; max-height: 220px; overflow-y: auto;
}
.bie-campo-select-lista.hidden { display: none; }
.bie-campo-select-item { padding: 6px 10px; font-size: 12px; cursor: pointer; }
.bie-campo-select-item:hover { background: var(--card2); }
.bie-campo-select-item.selected { background: var(--consulta-glow); color: var(--consulta); font-weight: 600; }
.bie-campo-select-vazio { padding: 8px 10px; font-size: 12px; color: var(--muted); }
#bie-sql-texto {
    font-family: var(--font-mono); font-size: 12px; white-space: pre-wrap; word-break: break-word;
    background: var(--card2); padding: 8px; border-radius: var(--r-sm); max-height: 220px; overflow: auto;
}

#bie-carregar-painel {
    position: absolute; top: 100%; right: 14px; margin-top: 4px; z-index: 20;
    background: var(--card); border: 1px solid var(--border); border-radius: var(--r);
    box-shadow: var(--sh-lg); min-width: 260px; max-height: 320px; overflow-y: auto;
}
#bie-carregar-painel.hidden { display: none; }
.bie-carregar-item { padding: 8px 12px; font-size: 13px; cursor: pointer; border-bottom: 1px solid var(--border); }
.bie-carregar-item:hover { background: var(--card2); }
.bie-carregar-vazio { padding: 12px; font-size: 12px; color: var(--muted); }

#bie-loading {
    position: fixed; top: 0; left: 0; right: 0; height: 3px; background: var(--consulta); z-index: 50;
    animation: bie-loading-pulse 1s infinite ease-in-out;
}
#bie-loading.hidden { display: none; }
@keyframes bie-loading-pulse { 0%, 100% { opacity: .4; } 50% { opacity: 1; } }
</style>

<header class="bie-toolbar">
    <input type="text" id="bie-nome" placeholder="Nome da análise...">
    <button id="bie-novo" type="button">Nova</button>
    <button id="bie-salvar" type="button">Salvar</button>
    <button id="bie-duplicar" type="button">Duplicar</button>
    <button id="bie-carregar-btn" type="button">Carregar ▾</button>
    <button id="bie-atualizar" type="button">Atualizar</button>
    <button id="bie-exportar-csv" type="button">Exportar CSV</button>
    <button id="bie-tab-visual" type="button" class="active">Visual</button>
    <button id="bie-tab-sql" type="button">SQL</button>
    <span id="bie-dirty-badge" class="hidden">não salvo</span>
    <div id="bie-carregar-painel" class="hidden"></div>
</header>

<div id="bie-erro" class="bie-erro"></div>

<div class="bie-layout">
    <aside id="bie-painel-dados" class="bie-panel">
        <div class="bie-panel-header">
            <span class="bie-panel-titulo">Dados</span>
            <button id="bie-toggle-dados" type="button" class="bie-panel-toggle" title="Recolher painel">‹</button>
        </div>
        <div class="bie-panel-body">
            <input type="text" id="bie-busca-tabela" placeholder="Buscar tabela...">
            <div id="bie-lista-tabelas" class="bie-lista"></div>
            <hr style="border-color:var(--border); margin:10px 0;">
            <input type="text" id="bie-busca-coluna" placeholder="Buscar coluna...">
            <div id="bie-lista-colunas" class="bie-lista"></div>
        </div>
    </aside>

    <main class="bie-central">
        <div id="bie-grid" class="ag-theme-quartz"></div>

        <div id="bie-sql-manual-wrap" class="hidden">
            <textarea id="bie-sql-manual-input" placeholder="SELECT ..."></textarea>
            <button id="bie-executar-sql" type="button" class="btn-apply">Executar</button>
        </div>

        <section id="bie-secao-grafico" class="bie-grafico-secao hidden">
            <div class="bie-grafico-controles">
                <label>Tipo <select id="bie-grafico-tipo"></select></label>
                <label>Eixo X <select id="bie-grafico-eixox"></select></label>
                <label>Séries <select id="bie-grafico-series" multiple size="3"></select></label>
            </div>
            <div id="bie-grafico"></div>
        </section>
    </main>

    <aside id="bie-painel-config" class="bie-panel">
        <div class="bie-panel-header">
            <span class="bie-panel-titulo">Configuração</span>
            <button id="bie-toggle-config" type="button" class="bie-panel-toggle" title="Recolher painel">›</button>
        </div>
        <div class="bie-panel-body">
            <details open>
                <summary>Joins</summary>
                <div id="bie-joins-lista"></div>
                <div id="bie-join-form" class="hidden">
                    <input type="text" id="bie-join-busca-tabela" placeholder="Buscar tabela...">
                    <div id="bie-join-lista-tabelas" class="bie-lista"></div>
                    <div id="bie-join-chaves" class="hidden">
                        <div class="bie-join-chave-row">
                            <span class="bie-join-chave-label">Base</span>
                            <span id="bie-join-campo-base-wrap"></span>
                        </div>
                        <div class="bie-join-chave-row">
                            <span class="bie-join-chave-label">Nova tabela</span>
                            <span id="bie-join-campo-novo-wrap"></span>
                        </div>
                        <div class="bie-join-chave-row">
                            <span class="bie-join-chave-label">Tipo</span>
                            <span id="bie-join-tipo-wrap"></span>
                        </div>
                        <button id="bie-join-confirmar" type="button" class="btn-apply">Confirmar join</button>
                        <button id="bie-join-cancelar" type="button" class="btn-clear">Cancelar</button>
                    </div>
                </div>
                <button id="bie-add-join" type="button" class="btn-clear">+ Join</button>
            </details>
            <details open>
                <summary>Colunas calculadas</summary>
                <div id="bie-calc-lista"></div>
                <div id="bie-calc-form" class="hidden">
                    <input type="text" id="bie-calc-nome" placeholder="Nome (ex.: dias_para_fechar)">
                    <textarea id="bie-calc-expressao" placeholder="Ex.: DATEDIFF(dataFechamento, dataAbertura)"></textarea>
                    <div class="bie-join-chave-row">
                        <span class="bie-join-chave-label">Tipo do resultado</span>
                        <span id="bie-calc-tipo-wrap"></span>
                    </div>
                    <div id="bie-calc-erro" class="bie-erro"></div>
                    <button id="bie-calc-testar" type="button" class="btn-clear">Testar</button>
                    <button id="bie-calc-confirmar" type="button" class="btn-apply">Confirmar</button>
                    <button id="bie-calc-cancelar" type="button" class="btn-clear">Cancelar</button>
                </div>
                <button id="bie-add-calc" type="button" class="btn-clear">+ Coluna calculada</button>
            </details>
            <details open>
                <summary>Filtros</summary>
                <div id="bie-filtros-lista"></div>
                <button id="bie-add-filtro" type="button" class="btn-clear">+ Filtro</button>
            </details>
            <details open>
                <summary>Agrupar por</summary>
                <div id="bie-agrupamento-linhas"></div>
            </details>
            <details open>
                <summary>Métricas</summary>
                <div id="bie-metricas-lista"></div>
                <button id="bie-add-metrica" type="button" class="btn-clear">+ Métrica</button>
            </details>
            <details>
                <summary>Ordenação</summary>
                <div id="bie-ordenacao-lista"></div>
                <button id="bie-add-ordenacao" type="button" class="btn-clear">+ Ordenação</button>
            </details>
            <details>
                <summary>SQL gerado</summary>
                <pre id="bie-sql-texto"></pre>
            </details>
        </div>
    </aside>
</div>

<div id="bie-loading" class="hidden"></div>
</div>

<script>
BiExplorer.init({ site: '<?= $this->site ?>' });
</script>
