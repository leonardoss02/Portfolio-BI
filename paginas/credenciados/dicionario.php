<?php $dicTo = (($_GET['to'] ?? '') === '1') ? '?to=1' : ''; ?>
<div class="dash-credenciados dic" data-sugestoes="combo,distrato,descarte">

  <header class="dic-head">
    <p class="dic-eyebrow">Credenciados</p>
    <h1 class="dic-title">Dicionário de métricas</h1>
    <p class="dic-lead">Definições do funil, das datas usadas nos filtros e das regras de conversão do dashboard Credenciados. Cada indicador é filtrado pela sua própria data.</p>
    <nav class="dic-tabs" aria-label="Abas do dashboard">
      <a class="dic-tab" href="<?= $this->site ?>credenciados<?= $dicTo ?>">Credenciados</a>
      <a class="dic-tab" href="<?= $this->site ?>credenciados/dicionario<?= $dicTo ?>" aria-current="page">Dicionário</a>
    </nav>
  </header>

  <div class="dic-layout">
    <div class="dic-body">

      <section class="dic-section" id="escopo">
        <h2 class="dic-section-title">Quem entra no dashboard</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Lead credenciado <code>leadV2</code></dt>
            <dd>É todo lead cujo campo de afiliado está preenchido. Leads sem ID de afiliado ficam fora de todos os números calculados a partir dos leads.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p><code>idAfiliado_leadV2 ≠ vazio</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Deal credenciado <code>dealV2</code></dt>
            <dd>É toda oportunidade comercial cujo campo de afiliado está preenchido. As métricas de venda e pós-venda usam esta fonte.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p><code>idAfiliado_dealV2 ≠ vazio</code></p></div>
              </details>
            </dd>
          </div>
        </dl>
        <p class="dic-note"><strong>Importante:</strong> leads e deals são calculados separadamente. O dashboard não junta as duas tabelas pelo ID de afiliado, pois um credenciado pode existir em uma fonte e ainda não existir na outra.</p>
      </section>

      <section class="dic-section" id="kpis">
        <h2 class="dic-section-title">Os 9 indicadores do topo</h2>
        <div class="dic-list">
          <article class="dic-term" data-stage="aquisicao">
            <h3 class="dic-term-name">Total de Leads</h3>
            <p class="dic-term-meta"><span>Entrada do funil</span></p>
            <p class="dic-term-desc">Quantidade de leads credenciados com data de formulário no período selecionado.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Data usada: <code>dataUltForm_leadV2</code></p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="aquisicao">
            <h3 class="dic-term-name">Qualificados</h3>
            <p class="dic-term-meta"><span>Lead aprovado na qualificação</span></p>
            <p class="dic-term-desc">Leads credenciados marcados como “Sim” no campo de qualificação.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>qualificado_leadV2 = "Sim"</code></p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="evento">
            <h3 class="dic-term-name">Cadeiras Vendidas</h3>
            <p class="dic-term-meta"><span>Venda de evento</span></p>
            <p class="dic-term-desc">Deals credenciados ganhos no pipe de cadeiras.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>faseId = C9:WON</code> · data: <code>dataVenda</code></p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Consultas Vendidas</h3>
            <p class="dic-term-meta"><span>Venda de consulta</span></p>
            <p class="dic-term-desc">Deals credenciados ganhos nos pipes de venda de consulta.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>faseId ∈ {WON, C11:WON}</code> · data: <code>dataVenda</code></p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Consultas Realizadas</h3>
            <p class="dic-term-meta"><span>Consulta concluída</span></p>
            <p class="dic-term-desc">Deals do pipe Comercial 2 (C5) que possuem data de consulta realizada.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>faseId</code> começa com <code>C5</code> · data: <code>dataConsultaRealizada</code></p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="sinal">
            <h3 class="dic-term-name">FP Realizado</h3>
            <p class="dic-term-meta"><span>Financial Planning concluído</span></p>
            <p class="dic-term-desc">Deal C5 com consulta realizada, FP agendado e FP realizado, excluindo Comercial 3, Distrato e Lead sem qualificação.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Data usada: <code>dataFpRealizado_dealV2</code></p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="sinal">
            <h3 class="dic-term-name">Taxa Paga</h3>
            <p class="dic-term-meta"><span>Sinal pago</span></p>
            <p class="dic-term-desc">Quantidade de deals C5 com venda e consulta realizada, excluindo as fases de perda e distrato definidas na medida original. É uma contagem, não um percentual.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Medida original: <code>SinalPago</code></p>
                <p>Data usada: <code>dataVenda_dealV2</code></p>
              </div>
            </details>
          </article>
          <article class="dic-term" data-stage="fecho">
            <h3 class="dic-term-name">Combo Vendido</h3>
            <p class="dic-term-meta"><span>Contrato assinado</span></p>
            <p class="dic-term-desc">Deals credenciados em uma das fases de combo ganho e com contrato assinado.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>faseId ∈ {C1:WON, C5:WON, C29:won, C31:WON}</code> · data: <code>dataContratoComboAssinado</code></p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Distrato</h3>
            <p class="dic-term-meta"><span>Cancelamento após avanço</span></p>
            <p class="dic-term-desc">Deal em uma das duas fases de distrato, com FP realizado.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>faseId ∈ {C5:UC_CL1DBM, C5:UC_AQ2QYT}</code> · data: <code>dataConsultaRealizada</code></p></div>
            </details>
          </article>
        </div>
        <p class="dic-note">Datas vazias ou iguais a <code>0000-00-00</code> são tratadas como não preenchidas e não entram na respectiva métrica.</p>
      </section>

      <section class="dic-section" id="conversoes">
        <h2 class="dic-section-title">Percentuais de conversão</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Qualificação</dt>
            <dd>Qualificados ÷ Total de Leads.</dd>
          </div>
          <div class="dic-def">
            <dt>Cadeiras</dt>
            <dd>Cadeiras Vendidas ÷ Qualificados.</dd>
          </div>
          <div class="dic-def">
            <dt>Consultas Vendidas</dt>
            <dd>Consultas Vendidas ÷ Qualificados. Pode ultrapassar 100% porque cada etapa usa sua própria data.</dd>
          </div>
          <div class="dic-def">
            <dt>Consultas Realizadas</dt>
            <dd>Consultas Realizadas ÷ Consultas Vendidas.</dd>
          </div>
          <div class="dic-def">
            <dt>Combo</dt>
            <dd>Combos Vendidos ÷ Consultas Realizadas.</dd>
          </div>
        </dl>
        <p class="dic-note"><strong>Divisão por zero:</strong> quando o denominador é zero, o dashboard exibe 0%.</p>
      </section>

      <section class="dic-section" id="visuais">
        <h2 class="dic-section-title">Como ler os visuais</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Conversão por Período</dt>
            <dd>As barras mostram Qualificados, Consultas Vendidas e Combos. A linha verde é Consultas Vendidas ÷ Qualificados. A linha rosa é Combos ÷ Consultas Realizadas. O agrupamento pode ser mensal, por dia da semana, diário ou por semana ISO do ano.</dd>
          </div>
          <div class="dic-def">
            <dt>Motivos de Descarte</dt>
            <dd>Usa o motivo preenchido no lead credenciado e a data do último formulário. Exibe os 10 maiores motivos e agrupa os demais em “Outros”. O percentual ao lado de cada motivo é a sua parte no total de leads credenciados descartados no período (os que têm algum motivo preenchido), contando também os que caíram em “Outros”.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>% do motivo = leads com aquele motivo ÷ soma de todos os motivos de <code>motivoDescarte_leadV2</code> no período (motivo comparado pelo texto, sem espaços nas pontas)</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Tabela de Registros</dt>
            <dd>Lista os leads credenciados com ID, nome, telefone, data do formulário, fase, profissão e tempo de experiência, sem paginação, com rolagem interna (a barra aparece ao passar o mouse). A busca e a ordenação são feitas no navegador e não alteram os KPIs; use o botão "Exportar" para baixar em CSV ou Excel.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="filtros">
        <h2 class="dic-section-title">Filtros de período</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Ano e Mês, ou intervalo de datas</dt>
            <dd>O período pode ser escolhido por Ano e Mês ou por um intervalo de datas. Basta preencher uma das duas datas para o intervalo ter precedência: Ano e Mês passam a ser ignorados. Só com a data inicial, entram os registros daquela data em diante; só com a final, os registros até ela. Se a data inicial for posterior à final, as duas são trocadas. A tela abre com o ano de 2026 selecionado. Em qualquer caso, cada indicador aplica o período à sua própria data.</dd>
          </div>
        </dl>
      </section>

    </div>
  </div>

</div>
<script>initDicionario();</script>
