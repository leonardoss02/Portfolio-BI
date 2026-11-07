<?php $dicTo = (($_GET['to'] ?? '') === '1') ? '?to=1' : ''; ?>
<div class="dash-dentistas dic" data-sugestoes="lead,consulta,contrato">

  <header class="dic-head">
    <div class="dic-head-aside">
      <button class="dic-theme-btn" id="theme-btn" type="button" onclick="toggleTheme()" aria-label="Alternar tema claro/escuro" title="Alternar tema">
        <svg id="theme-icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg id="theme-icon-sun" viewBox="0 0 24 24" aria-hidden="true" style="display:none">
          <circle cx="12" cy="12" r="5"/>
          <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
          <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
          <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
          <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
        </svg>
      </button>
    </div>
    <p class="dic-eyebrow">Dentistas</p>
    <h1 class="dic-title">Dicionário de métricas</h1>
    <p class="dic-lead">Explicação de cada número exibido no painel do funil comercial de Dentistas, em linguagem simples. Entenda de onde vêm os dados e como cada métrica é calculada.</p>
    <nav class="dic-tabs" aria-label="Abas do dashboard">
      <a class="dic-tab" href="<?= $this->site ?>dentistas<?= $dicTo ?>">Visão Geral</a>
      <a class="dic-tab" href="<?= $this->site ?>dentistas/dicionario<?= $dicTo ?>" aria-current="page">Dicionário</a>
    </nav>
  </header>

  <div class="dic-layout">
    <div class="dic-body">

      <section class="dic-section" id="fontes">
        <h2 class="dic-section-title">Fontes de dados</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Oportunidades <code>dealV2</code></dt>
            <dd>Todo o painel é lido de uma única fonte: o pipe (fila de venda) dedicado ao segmento Dentistas, identificado no CRM pelo código <strong>C33</strong>. Cada card que entra nesse pipe já conta como <strong>lead</strong>; não existe mais uma etapa separada de qualificação. Total de Leads, Confirmados no evento, Consultas realizadas e Contrato assinado saem todos daqui.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="funil">
        <h2 class="dic-section-title">Métricas do funil</h2>
        <p class="dic-section-intro">O funil do painel tem quatro etapas, nesta ordem. Neste segmento, a confirmação no evento vem antes da consulta.</p>
        <ol class="dic-steps">
          <li data-stage="aquisicao"><strong>Leads</strong><span>Cards no pipe de Dentistas</span></li>
          <li data-stage="sinal"><strong>Confirmados no Evento</strong><span>Check-in registrado no card</span></li>
          <li data-stage="consulta"><strong>Consultas Realizadas</strong><span>Consulta que de fato aconteceu</span></li>
          <li data-stage="fecho"><strong>Contrato Assinado</strong><span>Fechamento do funil</span></li>
        </ol>
        <div class="dic-list">
          <article class="dic-term" data-stage="aquisicao">
            <h3 class="dic-term-name">Total de Leads</h3>
            <p class="dic-term-meta"><span>Topo do funil</span><span>Pipe de Dentistas (C33)</span></p>
            <p class="dic-term-desc">Quantidade de cards no pipe de Dentistas no período. Todo card do pipe já conta como lead: não existe mais uma triagem de qualificação separada. Contados pela data do último formulário preenchido no deal.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Cards do pipe <code>C33</code> com data do último formulário do deal (<code>dataUltForm_dealV2</code>) dentro do período</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="sinal">
            <h3 class="dic-term-name">Confirmados no Evento</h3>
            <p class="dic-term-meta"><span>Meio do funil</span><span>Check-in no CRM</span></p>
            <p class="dic-term-desc">Cliente confirmou presença/participação no evento (o CRM registra isso como "check-in" do card). Vem antes da consulta neste funil: nem todo confirmado chega a ter uma consulta realizada depois.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Cards do pipe <code>C33</code> com <code>checkin_dealV2 = '1'</code>, dentro do período (pela data do último formulário do deal)</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Consultas Realizadas</h3>
            <p class="dic-term-meta"><span>Meio do funil</span><span>Pipe C33, pela data de realização</span></p>
            <p class="dic-term-desc">Consultas que de fato aconteceram, dentro do funil dedicado a Dentistas. A data usada é a da realização, não a da venda.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Cards no pipe <code>C33</code> com data de consulta realizada preenchida, dentro do período</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="fecho">
            <h3 class="dic-term-name">Contrato Assinado</h3>
            <p class="dic-term-meta"><span>Fechamento</span><span>Fase ganha do pipe C33</span></p>
            <p class="dic-term-desc">Contrato de visto assinado: o fechamento do funil. Só conta cards que chegaram à fase de ganho do pipe com a data de assinatura do contrato preenchida, então pode aparecer zerado enquanto nenhum card chegar a essa fase.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Cards com fase <code>C33:WON</code> e data de assinatura do contrato de combo preenchida, dentro do período</p></div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>% da etapa anterior</dt>
            <dd>Abaixo de Confirmados no Evento, Consultas Realizadas e Contrato Assinado, e na coluna da direita do funil, aparece quanto aquela etapa representa da etapa imediatamente anterior. Como cada etapa é contada pela sua própria data, o percentual pode passar de 100%. Quando a etapa anterior está zerada, mostra 0%. Leads, o topo, não tem percentual ("—" no funil).
              <details class="dic-rule"><summary>Como é calculado</summary>
                <div class="dic-rule-body">
                  <p><b>Confirmados:</b> Confirmados no Evento ÷ Total de Leads</p>
                  <p><b>Consultas:</b> Consultas Realizadas ÷ Confirmados no Evento</p>
                  <p><b>Contrato:</b> Contrato Assinado ÷ Consultas Realizadas</p>
                  <p>Com 1 casa decimal</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Barras do funil</dt>
            <dd>No cartão "Funil Comercial — Dentistas", o comprimento de cada barra é proporcional ao Total de Leads, e cada etapa usa a mesma cor do seu KPI.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="graficos">
        <h2 class="dic-section-title">Gráfico e tabelas</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Evolução (mensal, semanal ou diária)</dt>
            <dd>Mostra uma métrica por vez ao longo do período filtrado, escolhida no seletor: Leads, Confirm. (Confirmados no Evento), Consultas ou Contrato. Cada métrica é contada pela mesma data e com as mesmas regras do seu KPI, e respeita todos os filtros. O grão é escolhido em Mês, Semana ou Dia: trocar o grão recarrega o painel, trocar a métrica não. Mês, semana ou dia sem nenhum registro em nenhuma das quatro métricas não aparece no eixo. Passe o mouse sobre a linha para ver o valor de cada ponto.
              <details class="dic-rule"><summary>Cada grão</summary>
                <div class="dic-rule-body">
                  <p><b>Mês:</b> um ponto por mês/ano (ex.: Set/26)</p>
                  <p><b>Semana:</b> semana começando na segunda-feira, rotulada S{número}/{ano} (<code>YEARWEEK(data, 1)</code>)</p>
                  <p><b>Dia:</b> a data do calendário (dd/mm), não o dia do mês somado entre meses</p>
                  <p><b>Rótulos nos pontos:</b> com até 8 pontos aparecem todos; com mais, só o primeiro, o último e o maior</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Leads por Anúncio e Leads por Evento</dt>
            <dd>Contam os leads (mesma regra e mesma data do Total de Leads) agrupados pelo anúncio de origem e pelo evento informados no card, do maior para o menor, com a fatia de cada linha no total da própria tabela. Cards sem anúncio ou sem evento preenchido aparecem juntos como "(não informado)". Clicar numa linha liga ou desliga aquele valor no filtro correspondente do topo e recarrega o painel inteiro; a linha "(não informado)" não é clicável.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Agrupamento:</b> <code>utmTerm_dealV2</code> (Anúncio) e <code>dentistaEvento_dealV2</code> (Evento)</p>
                  <p><b>% do total:</b> leads da linha ÷ soma dos leads da tabela</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Negócios no pipe Dentistas</dt>
            <dd>Lista os cards do pipe de Dentistas com ID Bitrix, nome, fase, evento, profissão, formação acadêmica, data do último formulário, data da consulta realizada e data de assinatura do contrato, mais o link "Abrir" para o card no Bitrix. Mostra no máximo <strong>500 cards</strong>, do último formulário mais recente para o mais antigo, e respeita todos os filtros, inclusive o período (pela data do último formulário). A busca procura por nome, fase, evento ou ID Bitrix só entre as linhas já carregadas, e "Exportar" gera CSV ou Excel com exatamente as linhas visíveis.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p>Cards do pipe <code>C33</code> com <code>dataUltForm_dealV2</code> no período, ordenados por essa data (decrescente), limite de 500</p>
                  <p>Não exige data de último formulário válida (diferente do Total de Leads)</p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
        <p class="dic-note is-warn"><strong>A tabela de negócios pode ter mais cards que o Total de Leads.</strong> Sem filtro de período, ela também lista cards com a data do último formulário vazia ou zerada, que não entram no Total de Leads. Com só a data final do intervalo preenchida, os cards com essa data zerada também aparecem na tabela.</p>
      </section>

      <section class="dic-section" id="filtros">
        <h2 class="dic-section-title">Filtros e datas</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Os filtros de período</dt>
            <dd><strong>Data</strong> (intervalo livre), <strong>Ano</strong> e <strong>Mês</strong> definem o período. Se você preencher o intervalo de datas, ele tem prioridade sobre Ano/Mês. Ao abrir, o painel vem com o ano atual marcado e Mês em Todos.</dd>
          </div>
          <div class="dic-def">
            <dt>Cada métrica tem sua própria data</dt>
            <dd>Leads e Confirmados no Evento pela <strong>data do último formulário do deal</strong>; Consultas Realizadas pela <strong>data de realização</strong>; Contrato Assinado pela <strong>data de assinatura do contrato de combo</strong>. Por isso as etapas do funil não são um recorte de um mesmo grupo de cards.</dd>
          </div>
          <div class="dic-def">
            <dt>Atualização dos números</dt>
            <dd>O resultado de cada combinação de filtros fica guardado por <strong>10 minutos</strong> para a tela abrir rápido. As listas de opções dos filtros de Evento, Anúncio, Profissão e Formação também.</dd>
          </div>
          <div class="dic-def">
            <dt>Filtro de Evento</dt>
            <dd>Filtra pelo evento/feira de origem informado no card do CRM, com busca e múltipla seleção. Afeta as <strong>4 etapas do funil</strong>, o gráfico de evolução, a tabela "Leads por Anúncio" e a tabela de negócios. A tabela <strong>"Leads por Evento" ignora este filtro</strong>: em vez de sumirem, as linhas não selecionadas ficam translúcidas, para dar para comparar e marcar mais de um evento. Por isso, com este filtro ativo, a soma dessa tabela pode ser maior que o Total de Leads.
              <details class="dic-rule"><summary>Campo no CRM</summary>
                <div class="dic-rule-body"><p><code>dentistaEvento_dealV2</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Filtro de Anúncio (UTM Term)</dt>
            <dd>Filtra pelo anúncio de origem informado no card do CRM, com busca e múltipla seleção. Afeta as <strong>4 etapas do funil</strong>, o gráfico de evolução, a tabela "Leads por Evento" e a tabela de negócios. A tabela <strong>"Leads por Anúncio" ignora este filtro</strong>: as linhas não selecionadas ficam translúcidas em vez de sumirem, e a soma dessa tabela pode ser maior que o Total de Leads.
              <details class="dic-rule"><summary>Campo no CRM</summary>
                <div class="dic-rule-body"><p><code>utmTerm_dealV2</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Filtro de Profissão</dt>
            <dd>Filtra pela profissão informada no card do CRM, com busca e múltipla seleção. Afeta as <strong>4 etapas do funil</strong>, o gráfico de evolução, as duas tabelas de agrupamento e a tabela de negócios.
              <details class="dic-rule"><summary>Campo no CRM</summary>
                <div class="dic-rule-body"><p><code>profissao_dealV2</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Filtro de Formação Acadêmica</dt>
            <dd>Filtra pela formação acadêmica informada no card do CRM, com busca e múltipla seleção. Afeta as <strong>4 etapas do funil</strong>, o gráfico de evolução, as duas tabelas de agrupamento e a tabela de negócios.
              <details class="dic-rule"><summary>Campo no CRM</summary>
                <div class="dic-rule-body"><p><code>formacaoAcademica_dealV2</code></p></div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

    </div>
  </div>

</div>
<script>initDicionario();</script>
