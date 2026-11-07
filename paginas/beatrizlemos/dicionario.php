<?php $dicTo = (($_GET['to'] ?? '') === '1') ? '?to=1' : ''; ?>
<div class="dash-beatrizlemos dic" data-sugestoes="combo,FP,descarte">

  <header class="dic-head">
    <p class="dic-eyebrow">Beatriz Lemos</p>
    <h1 class="dic-title">Dicionário de métricas</h1>
    <p class="dic-lead">O que significa cada número do dashboard da Beatriz Lemos. Entram só os cards em que ela é a responsável no CRM, e cada métrica aplica o período à sua própria data.</p>
    <nav class="dic-tabs" aria-label="Abas do dashboard">
      <a class="dic-tab" href="<?= $this->site ?>beatrizlemos<?= $dicTo ?>">Beatriz Lemos</a>
      <a class="dic-tab" href="<?= $this->site ?>beatrizlemos/dicionario<?= $dicTo ?>" aria-current="page">Dicionário</a>
    </nav>
  </header>

  <div class="dic-layout">
    <div class="dic-body">

      <section class="dic-section" id="fontes">
        <h2 class="dic-section-title">Fontes de dados</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Oportunidades (deals) <code>dealV2</code></dt>
            <dd>Cards comerciais do CRM. Entram só os cards em que Beatriz Lemos é a responsável; as datas de cada etapa (consulta agendada e realizada, FP, contrato do combo) dizem em que ponto do funil o card está.
              <details class="dic-rule"><summary>Ligação com a vendedora</summary>
                <div class="dic-rule-body"><p><code>responsavel_dealV2 = 'Beatriz Lemos'</code>, mais o filtro de Form sobre <code>formPreenchido_dealV2</code></p></div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="filtros">
        <h2 class="dic-section-title">Filtros e período</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Ano, Mês e Data</dt>
            <dd>Ao abrir, o painel já vem filtrado no mês atual. Ao aplicar, o Ano e o Mês escolhidos viram um intervalo de datas contínuo, do primeiro dia do primeiro mês ao último dia do último mês (ou o ano inteiro, sem mês escolhido); quando esse intervalo termina no mês atual, ou é o ano atual inteiro, ele vai só até hoje. Datas digitadas à mão têm prioridade. Cada métrica aplica esse intervalo à sua própria data, por isso os números não são recortes de um mesmo grupo de cards.
              <details class="dic-rule"><summary>Data usada por métrica</summary>
                <div class="dic-rule-body">
                  <p><b>Consultas Agendadas:</b> data de consulta agendada corrigida (ver a métrica)</p>
                  <p><b>Consultas Realizadas e Assinatura Pendente:</b> <code>dataConsultaRealizada_dealV2</code></p>
                  <p><b>Fp Realizado:</b> <code>dataFpRealizado_dealV2</code></p>
                  <p><b>Combos Vendidos:</b> <code>dataContratoComboAssinado_dealV2</code></p>
                  <p><b>Motivos de Descarte:</b> <code>dataConsultaRealizada_dealV2</code></p>
                  <p><b>Tabela de clientes:</b> qualquer uma das quatro datas (agendada corrigida, realizada, FP realizado, contrato do combo)</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Form</dt>
            <dd>Restringe todo o painel aos cards que vieram dos formulários escolhidos. A lista oferece os formulários que já aparecem em cards da Beatriz Lemos.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p><code>formPreenchido_dealV2 IN (...)</code>, com o nome exato de cada formulário (sem agrupar por tipo)</p></div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="funil">
        <h2 class="dic-section-title">Consultas e funil de vendas</h2>
        <p class="dic-section-intro">O cartão Funil de Vendas mostra três etapas, da consulta marcada ao combo assinado. Os seis números do topo da tela detalham o caminho entre elas.</p>
        <ol class="dic-steps">
          <li data-stage="consulta"><strong>Consultas agendadas</strong><span>Reunião comercial com data marcada</span></li>
          <li data-stage="consulta"><strong>Consultas realizadas</strong><span>Reunião que de fato aconteceu</span></li>
          <li data-stage="fecho"><strong>Combos vendidos</strong><span>Contrato assinado, fim do funil</span></li>
        </ol>
        <div class="dic-list">
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Consultas Agendadas</h3>
            <p class="dic-term-meta"><span>Consulta</span><span>Pipe Comercial 2 (C5)</span></p>
            <p class="dic-term-desc">Consultas do pipe Comercial 2 com data marcada dentro do período. Quando a consulta foi realizada num mês diferente do agendado, ou foi realizada sem data de agendamento, ela conta no mês em que aconteceu, para não aparecer em dois meses.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><code>LEFT(faseId_dealV2, 3) = 'C5:'</code> com data agendada corrigida preenchida e dentro do período</p>
                <p><b>Data agendada corrigida:</b> usa <code>dataConsultaRealizada_dealV2</code> quando <code>dataConsultaAgendada_dealV2</code> está vazia, ou quando o mês da realizada é diferente do mês da agendada; nos demais casos, usa <code>dataConsultaAgendada_dealV2</code></p>
              </div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Consultas a Realizar</h3>
            <p class="dic-term-meta"><span>Consulta</span><span>Agendadas menos realizadas</span></p>
            <p class="dic-term-desc">Quanto ainda falta realizar das consultas marcadas no período. É a diferença entre os dois totais do período, não uma lista de cards, e nunca aparece negativa.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>máximo(0, Consultas Agendadas − Consultas Realizadas)</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Consultas Realizadas</h3>
            <p class="dic-term-meta"><span>Consulta</span><span>Pipe Comercial 2 (C5)</span></p>
            <p class="dic-term-desc">Consultas que de fato aconteceram no período. É a porta de entrada para o fechamento do combo.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>LEFT(faseId_dealV2, 2) = 'C5'</code> com <code>dataConsultaRealizada_dealV2</code> preenchida e dentro do período</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="sinal">
            <h3 class="dic-term-name">Fp Realizado</h3>
            <p class="dic-term-meta"><span>Sinal e FP</span><span>Pipe Comercial 2 (C5)</span></p>
            <p class="dic-term-desc">Clientes que concluíram o Financial Planning (FP) no período, depois de terem a consulta realizada e o FP agendado. Não entram cards que foram para o Comercial 3, que viraram distrato ou que estão como lead sem qualificação.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><code>LEFT(faseId_dealV2, 2) = 'C5'</code></p>
                <p><code>dataConsultaRealizada_dealV2</code>, <code>dataFpAgendado_dealV2</code> e <code>dataFpRealizado_dealV2</code> preenchidas</p>
                <p><code>fase_dealV2 NOT IN ('Comercial 3', 'Distrato', 'Lead sem qualificação')</code></p>
                <p>Período sobre <code>dataFpRealizado_dealV2</code></p>
              </div>
            </details>
          </article>
          <article class="dic-term" data-stage="sinal">
            <h3 class="dic-term-name">Assinatura Pendente</h3>
            <p class="dic-term-meta"><span>Sinal e FP</span><span>Pipe Comercial 2 (C5)</span></p>
            <p class="dic-term-desc">Clientes que já fizeram a consulta e o FP e estão na fase de contrato enviado do pipe Comercial 2, aguardando a assinatura. Aqui o período é aplicado à data da consulta realizada, não à data do FP.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><code>faseId_dealV2 = 'C5:UC_0Q30H1'</code></p>
                <p><code>dataConsultaRealizada_dealV2</code>, <code>dataFpAgendado_dealV2</code> e <code>dataFpRealizado_dealV2</code> preenchidas</p>
                <p>Período sobre <code>dataConsultaRealizada_dealV2</code></p>
              </div>
            </details>
          </article>
          <article class="dic-term" data-stage="fecho">
            <h3 class="dic-term-name">Combos Vendidos</h3>
            <p class="dic-term-meta"><span>Fechamento</span><span>Pipes C1, C5, C29 e C31</span></p>
            <p class="dic-term-desc">Contratos do pacote de imigração assinados no período: a venda final do funil.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON')</code> com <code>dataContratoComboAssinado_dealV2</code> preenchida e dentro do período</p></div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>O % ao lado de cada etapa do funil</dt>
            <dd>Consultas Agendadas é a base (100%). O % de Consultas Realizadas é sobre as agendadas, e o de Combos Vendidos é sobre as realizadas. O comprimento das barras é sempre proporcional às agendadas.
              <details class="dic-rule"><summary>Como é calculado</summary>
                <div class="dic-rule-body">
                  <p><b>Consultas Realizadas:</b> Realizadas ÷ Agendadas</p>
                  <p><b>Combos Vendidos:</b> Combos ÷ Realizadas</p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
        <p class="dic-note"><strong>Mesmo cliente, meses diferentes:</strong> cada número usa a data da própria etapa. Um cliente com consulta realizada em um mês e combo assinado no mês seguinte entra em Consultas Realizadas no primeiro mês e em Combos Vendidos no segundo.</p>
        <p class="dic-note is-warn"><strong>Mês atual:</strong> com o filtro de Ano e Mês, o período do mês atual vai só até hoje. Consultas marcadas para os próximos dias do mês ainda não entram em Consultas Agendadas nem em Consultas a Realizar. Esse corte é intencional.</p>
      </section>

      <section class="dic-section" id="grafico">
        <h2 class="dic-section-title">Gráfico de período</h2>
        <p class="dic-section-intro">O gráfico Consultas Realizadas &amp; Combos Vendidos usa as mesmas regras dos números do topo, distribuídas no tempo.</p>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Conversão %</h3>
            <p class="dic-term-meta"><span>Conversão</span><span>Linha do gráfico</span></p>
            <p class="dic-term-desc">Percentual de combos vendidos sobre as consultas realizadas em cada ponto do gráfico. Compara os dois totais daquele ponto, não acompanha o mesmo cliente da consulta até o combo.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Conversão = Combos Vendidos ÷ Consultas Realizadas do mesmo agrupamento; zero quando não há consulta realizada</p></div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Ano-Mês, Dia da Semana e Dia do Mês</dt>
            <dd>Mudam só o agrupamento do gráfico, sem alterar os outros números da tela. Ano-Mês mostra um ponto por mês; Dia da Semana soma o que caiu em cada dia (segunda a domingo) dentro do período; Dia do Mês soma por dia do mês (1 a 31). Consultas são agrupadas pela data de realização e combos pela data de assinatura do contrato.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Realizadas:</b> <code>LEFT(faseId_dealV2, 2) = 'C5'</code>, agrupadas por <code>dataConsultaRealizada_dealV2</code></p>
                  <p><b>Combos:</b> mesmas fases de Combos Vendidos, agrupados por <code>dataContratoComboAssinado_dealV2</code></p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="descarte">
        <h2 class="dic-section-title">Motivos de descarte</h2>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Motivos de Descarte</h3>
            <p class="dic-term-meta"><span>Descarte</span><span>Top 15 + Outros</span></p>
            <p class="dic-term-desc">Os motivos registrados pelo Comercial 2 para descartar cards da Beatriz Lemos que tiveram consulta realizada no período, do mais para o menos frequente. Os 15 motivos mais comuns aparecem pelo nome e os demais são somados em "Outros". O % de cada barra é a participação do motivo no total de descartes da lista.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Contagem por <code>TRIM(motivoDescarteComercial2_dealV2)</code>, só com motivo preenchido</p>
                <p><code>dataConsultaRealizada_dealV2</code> preenchida e dentro do período; não filtra fase</p>
              </div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="clientes">
        <h2 class="dic-section-title">Tabela de clientes</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Clientes</dt>
            <dd>Um card por linha, com link direto para o Bitrix. Entram os cards da Beatriz Lemos em que pelo menos uma destas datas cai no período: consulta agendada, consulta realizada, FP realizado ou assinatura do combo. A lista traz no máximo 500 cards, dos contratos mais recentes para os mais antigos; clicar no cabeçalho reordena, a busca filtra por nome ou e-mail e a exportação (CSV ou Excel) leva exatamente o que está na tela.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p>Período casado com qualquer uma de: data agendada corrigida, <code>dataConsultaRealizada_dealV2</code>, <code>dataFpRealizado_dealV2</code> ou <code>dataContratoComboAssinado_dealV2</code>; sem filtro de fase</p>
                  <p>Ordem: <code>dataContratoComboAssinado_dealV2</code>, depois <code>dataConsultaRealizada_dealV2</code>, depois <code>dataConsultaAgendada_dealV2</code>, sempre da mais recente para a mais antiga; limite de 500 linhas</p>
                  <p>A coluna Data Consulta Agendada mostra a data como está no CRM, sem a correção de mês usada no filtro</p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

    </div>
  </div>

</div>
<script>initDicionario();</script>
