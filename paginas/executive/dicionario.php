<?php $dicTo = (($_GET['to'] ?? '') === '1') ? '?to=1' : ''; ?>
<div class="dic" data-sugestoes="combo,ROI,Serasa">

  <header class="dic-head">
    <p class="dic-eyebrow">Executive</p>
    <h1 class="dic-title">Dicionário de métricas</h1>
    <p class="dic-lead">Explicação de cada número exibido no dashboard, em linguagem simples: de onde vêm os dados e o que cada métrica representa. O filtro de período vale para a data de cada etapa: um lead conta pela data do formulário, uma venda pela data da venda e um combo pela data de assinatura do contrato. Na aba Serasa, o período vale para a data do FP realizado.</p>
    <nav class="dic-tabs" aria-label="Abas do dashboard">
      <a class="dic-tab" href="<?= $this->site ?>executive<?= $dicTo ?>">Executive</a>
      <a class="dic-tab" href="<?= $this->site ?>executive/digital<?= $dicTo ?>">Digital &times; Evento</a>
      <a class="dic-tab" href="<?= $this->site ?>executive/especialistas<?= $dicTo ?>">Especialistas</a>
      <a class="dic-tab" href="<?= $this->site ?>executive/analitico<?= $dicTo ?>">Analítico</a>
      <a class="dic-tab" href="<?= $this->site ?>executive/serasa<?= $dicTo ?>">Serasa</a>
      <a class="dic-tab" href="<?= $this->site ?>executive/dicionario<?= $dicTo ?>" aria-current="page">Dicionário</a>
    </nav>
  </header>

  <div class="dic-layout">
    <div class="dic-body">

      <section class="dic-section" id="fontes">
        <h2 class="dic-section-title">Fontes de dados</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Leads <code>leadV2</code></dt>
            <dd>Toda vez que alguém preenche um formulário — seja em um anúncio digital ou em um evento presencial — é criado um card de <strong>lead</strong> no CRM Bitrix. Cada card representa uma pessoa que demonstrou interesse. Essa é a primeira etapa do funil.</dd>
          </div>
          <div class="dic-def">
            <dt>Oportunidades (deals) <code>dealV2</code></dt>
            <dd>Quando um lead avança no processo comercial, é criado um card de <strong>deal</strong> (oportunidade). O deal percorre diferentes pipes (filas de vendas) até ser fechado ou perdido. A maioria das métricas de vendas — cadeiras, consultas, combos — são contadas a partir dos deals.</dd>
          </div>
          <div class="dic-def">
            <dt>Investimento de marketing <code>custoMkt</code></dt>
            <dd>Registra os gastos com anúncios pagos (Meta Ads e Google Ads). Cada lançamento informa: data, valor, plataforma, conta e campanha. Valores em reais são convertidos para dólares pela cotação da data exata do gasto, registrada na tabela de câmbio; um gasto em reais sem cotação cadastrada naquela data fica de fora da soma.</dd>
          </div>
          <div class="dic-def">
            <dt>Custo de eventos físicos <code>custoEvento</code></dt>
            <dd>Registra os gastos com a realização de eventos presenciais: locação de espaço, buffet, equipe, materiais, etc. Os valores são sempre em reais e convertidos para dólares pela cotação mais recente cadastrada até a data do gasto. Esse custo é somado ao investimento de marketing para calcular o investimento total de um evento.</dd>
          </div>
          <div class="dic-def">
            <dt>Câmbio <code>cambio</code></dt>
            <dd>Cotação diária do dólar em reais, usada para converter os valores lançados em reais. Como pode haver mais de um registro para o mesmo dia, o dashboard usa sempre a maior cotação do dia.
              <details class="dic-rule"><summary>Como cada valor usa o câmbio</summary>
                <div class="dic-rule-body">
                  <p>Uma cotação por data: <code>MAX(valor_cambio)</code> agrupado por <code>data_cambio</code></p>
                  <p><b>Investimento MKT:</b> cotação da data exata do gasto (<code>data_cambio = data_custoMkt</code>); sem cotação nessa data, o valor em reais conta 0</p>
                  <p><b>Custo físico:</b> cotação da última data cadastrada até a data do gasto (<code>MAX(data_cambio) &lt;= data_custoEvento</code>)</p>
                  <p><b>Serasa:</b> maior cotação da data mais recente da tabela, a mesma para todos os clientes</p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="como-funciona">
        <h2 class="dic-section-title">Como funciona o funil</h2>
        <p class="dic-section-intro">Os dois caminhos até o combo, em cinco etapas cada.</p>
        <p class="dic-steps-label" id="funil-digital">Canal Digital</p>
        <ol class="dic-steps" aria-labelledby="funil-digital">
          <li data-stage="aquisicao"><strong>Lead entra</strong><span>Formulário preenchido online</span></li>
          <li data-stage="aquisicao"><strong>Lead qualificado</strong><span>Qualificação automática</span></li>
          <li data-stage="consulta"><strong>Consulta vendida</strong><span>Pipe Consultas Digital (C11) · fase Won</span></li>
          <li data-stage="consulta"><strong>Consulta realizada</strong><span>Reunião aconteceu de fato · pipe Comercial 2 (C5)</span></li>
          <li data-stage="fecho"><strong>Combo vendido</strong><span>Contrato assinado · fase WON nos pipes C1, C5, C29 ou C31</span></li>
        </ol>
        <p class="dic-steps-label" id="funil-evento">Canal Evento (OTR)</p>
        <ol class="dic-steps" aria-labelledby="funil-evento">
          <li data-stage="aquisicao"><strong>Lead entra</strong><span>Formulário preenchido no evento</span></li>
          <li data-stage="evento"><strong>Cadeira vendida</strong><span>Ingresso para o evento confirmado · pipe C9</span></li>
          <li data-stage="consulta"><strong>Consulta vendida</strong><span>Pipe principal · fase Won</span></li>
          <li data-stage="consulta"><strong>Consulta realizada</strong><span>Reunião aconteceu de fato · pipe Comercial 2 (C5)</span></li>
          <li data-stage="fecho"><strong>Combo vendido</strong><span>Contrato assinado · fase WON nos pipes C1, C5, C29 ou C31</span></li>
        </ol>
      </section>

      <section class="dic-section" id="funil">
        <h2 class="dic-section-title">Métricas do funil de vendas</h2>
        <div class="dic-list">
          <article class="dic-term" data-stage="aquisicao">
            <h3 class="dic-term-name">Leads</h3>
            <p class="dic-term-meta"><span>Topo do funil</span><span>Formulários</span></p>
            <p class="dic-term-desc">Total de leads no período, contados pela data do último formulário que cada um preencheu. Quem preenche um formulário de novo passa a contar no período desse formulário mais recente.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem de leads (<code>leadV2</code>) com a data do último formulário (<code>dataUltForm_leadV2</code>) dentro do período</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="aquisicao">
            <h3 class="dic-term-name">Leads Qualificados</h3>
            <p class="dic-term-meta"><span>Topo do funil</span><span>Qualificação automática</span></p>
            <p class="dic-term-desc">Leads marcados como qualificados pela qualificação automática.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Leads com campo <code>qualificado = "Sim"</code> no Bitrix</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="evento">
            <h3 class="dic-term-name">Cadeiras Vendidas</h3>
            <p class="dic-term-meta"><span>Canal Evento</span><span>Pipe C9, fase Won</span></p>
            <p class="dic-term-desc">Ingressos vendidos para eventos presenciais OTR. Representa pessoas que comprometeram presença em um evento físico da Acme EUA.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Cards no pipe de Cadeiras (<code>C9</code>) com fase Won e data de venda preenchida</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Consultas Vendidas</h3>
            <p class="dic-term-meta"><span>Digital + pipe principal</span><span>Pipe C11 e pipe principal</span></p>
            <p class="dic-term-desc">Agendamentos de consulta confirmados (consultas vendidas). Soma as consultas vendidas no pipe Comercial 2 digitais (C11) e no pipe principal do CRM, onde a fase de venda é Won sem prefixo de pipe — a métrica é combinada para medir o total de reuniões marcadas.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><b>Pipe digital:</b> fase <code>C11:WON</code></p>
                <p><b>Pipe principal:</b> fase <code>Won</code> (sem prefixo de pipe)</p>
                <p>Ambos com data de venda preenchida, contados pela data de venda (<code>dataVenda_dealV2</code>)</p>
              </div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Consultas Realizadas</h3>
            <p class="dic-term-meta"><span>Meio do funil</span><span>Pipe Comercial 2 (C5)</span></p>
            <p class="dic-term-desc">Consultas que de fato aconteceram. Diferente de "vendida" (agendada), aqui confirmamos que a reunião foi realizada. Essa é a porta de entrada para o fechamento do combo.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Cards no pipe Comercial 2 (<code>C5</code>) com data de consulta realizada preenchida</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="fecho">
            <h3 class="dic-term-name">Combos Vendidos</h3>
            <p class="dic-term-meta"><span>Fundo do funil</span><span>Pipes C5, C1, C29 e C31</span></p>
            <p class="dic-term-desc">Vendas fechadas — o cliente assinou o contrato do pacote de imigração. É a métrica de conversão final do funil. Combos de retenção (clientes recuperados após cancelamento) também entram aqui.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Pipes <code>C5, C1, C29, C31</code> · fase WON</p>
                <p>Data de contrato assinado preenchida</p>
                <p>Contrato distratado sai da contagem: o card muda para uma fase de distrato (<code>C5:UC_CL1DBM</code> ou <code>C5:UC_AQ2QYT</code>), que não é WON</p>
              </div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>O % abaixo de cada etapa</dt>
            <dd>Na linha de números do funil, cada etapa a partir de Qualificados mostra a conversão em relação à etapa anterior, e cada uma tem sua própria base: Qualificados sobre os Leads, Cadeiras sobre os Qualificados, Consultas Vendidas sobre as Cadeiras, Consultas Realizadas sobre as Vendidas e Combos sobre as Realizadas. Como cada etapa é contada pela sua própria data, o percentual compara volumes do mesmo período, não as mesmas pessoas passando de uma etapa para a outra.
              <details class="dic-rule"><summary>Como é calculado</summary>
                <div class="dic-rule-body">
                  <p>Qualificados ÷ Leads · Cadeiras ÷ Qualificados · Consultas Vendidas ÷ Cadeiras</p>
                  <p>Consultas Realizadas ÷ Consultas Vendidas · Combos ÷ Consultas Realizadas</p>
                  <p>Base zero → 0%</p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="financeiro">
        <h2 class="dic-section-title">Métricas financeiras</h2>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Faturamento</h3>
            <p class="dic-term-meta"><span>Receita</span><span>Em dólar</span></p>
            <p class="dic-term-desc">Soma do valor de todos os combos vendidos no período. Representa a receita gerada pelas vendas fechadas. Exibido em dólares (USD).</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Soma de <code>valorComboLiquido_dealV2</code> dos combos vendidos (USD)</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Investimento MKT</h3>
            <p class="dic-term-meta"><span>Custo</span><span>Em dólar</span></p>
            <p class="dic-term-desc">Total gasto com anúncios pagos, em dólares. Em 2025 entram os gastos do Meta (já lançados em dólar) e do Google (lançados em reais e convertidos); outras plataformas ficam de fora. Em 2026 vale a conta: Dollar 1 e Dollar 2 já estão em dólar e qualquer outra conta é convertida de reais; lançamento sem conta não entra. Gastos de outros anos não entram. A conversão usa a cotação da data exata do gasto. Nos cartões Orgânico e Indicação o investimento fica zerado, a menos que uma campanha esteja selecionada no filtro. Não inclui custos de eventos físicos.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><b>2025:</b> origem <code>meta</code> → valor já em USD; origem <code>google</code> → valor em BRL ÷ câmbio; outras origens → 0</p>
                <p><b>2026:</b> conta <code>Dollar 1</code> ou <code>Dollar 2</code> → valor já em USD; qualquer outra conta → BRL ÷ câmbio (regra de exclusão); conta nula (<code>NULL</code>) não entra</p>
                <p><b>Outros anos:</b> 0</p>
                <p>Câmbio: maior cotação do dia (<code>MAX(valor_cambio)</code>) na data exata do gasto; sem cotação nessa data, o valor em reais conta 0</p>
                <p>Orgânico e Indicação: 0 quando nenhuma campanha está selecionada</p>
                <p>Fonte: tabela <code>custoMkt</code>; filtro Campanha compara com <code>nomeCampanha_custoMkt</code> e filtro Form com <code>identificador_custoMkt</code></p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Custo Físico</h3>
            <p class="dic-term-meta"><span>Custo</span><span>Em dólar</span></p>
            <p class="dic-term-desc">Gastos com a realização dos eventos presenciais: locação, buffet, equipe, materiais. Entra no total geral e no cartão Evento; os cartões Digital, Orgânico e Indicação ficam sem custo físico. Convertido para dólar pela cotação mais recente cadastrada até a data do gasto.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Soma de <code>valor_custoEvento</code> (BRL) ÷ câmbio, em USD</p>
                <p>Câmbio: maior cotação da última data cadastrada até a data do gasto (sem cotação no próprio dia, usa a anterior mais próxima)</p>
                <p><b>Total geral:</b> todos os custos de evento do período</p>
                <p><b>Cartão Evento:</b> custos cujo <code>identificador_custoEvento</code> aparece em lançamentos de <code>custoMkt</code> com campanha de evento (<code>OTR</code>, <code>INA</code>, <code>OTW</code>, <code>WEB</code>)</p>
                <p><b>Com filtro Form:</b> <code>identificador_custoEvento</code> igual ao form; <b>com filtro Campanha:</b> identificadores dos lançamentos de marketing dessa campanha</p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Investimento Total</h3>
            <p class="dic-term-meta"><span>Custo total</span><span>MKT + Físico</span></p>
            <p class="dic-term-desc">Soma de tudo que foi investido no período: anúncios pagos mais custos físicos dos eventos. É a base para calcular ROI e lucro.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Investimento MKT + Custo Físico</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Lucro</h3>
            <p class="dic-term-meta"><span>Resultado</span><span>Faturamento − Investimento</span></p>
            <p class="dic-term-desc">O que sobrou após descontar todos os custos do período. Um lucro negativo significa que o investimento foi maior do que a receita gerada.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Faturamento − Investimento Total</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">ROI</h3>
            <p class="dic-term-meta"><span>Resultado</span><span>Return on Investment</span></p>
            <p class="dic-term-desc">Retorno sobre o investimento. Mostra quantos dólares entraram para cada dólar investido. ROI de 2,0 significa que a operação dobrou o dinheiro investido.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Faturamento ÷ Investimento Total</p>
                <p>ROI = 1,0 → empata · &gt; 1,0 → lucro</p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Ticket Médio</h3>
            <p class="dic-term-meta"><span>Resultado</span><span>Faturamento ÷ Combos</span></p>
            <p class="dic-term-desc">Valor médio de cada venda fechada. Ajuda a entender se a operação está vendendo pacotes mais completos (ticket maior) ou mais básicos (ticket menor).</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Faturamento Total ÷ Combos Vendidos</p></div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="vistos">
        <h2 class="dic-section-title">Vistos</h2>
        <p class="dic-note"><strong>Nota:</strong> as duas tabelas de vistos não usam o filtro Responsável — mostram todos os vendedores mesmo quando um responsável está selecionado. Valem o período (pela data de assinatura do contrato), a Campanha e o Form.</p>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Tipo de Visto</h3>
            <p class="dic-term-meta"><span>Vistos</span><span>Visto do combo fechado</span></p>
            <p class="dic-term-desc">Tipo de visto de imigração escolhido no combo vendido (ex.: EB-5, E-2, L-1, entre outros). É o mesmo card do CRM usado para contar "Combos Vendidos" — combos cancelados (distrato) não entram nessa contagem. Quando o card não tem visto preenchido, ele aparece como "Não informado".</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem de combos vendidos, agrupados por Tipo de Visto (campo <code>vistoEscolhido_dealV2</code> no CRM)</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Faturamento por Visto</h3>
            <p class="dic-term-meta"><span>Vistos</span><span>Vistos e faturamento por tipo</span></p>
            <p class="dic-term-desc">Tabela com uma linha por tipo de visto: quantos vistos (combos) foram vendidos no período e quanto eles faturaram, em dólar. Vem ordenada do visto mais vendido para o menos vendido. Ajuda a ver quais vistos concentram a receita.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><b>Vistos:</b> contagem de combos vendidos do tipo de visto</p>
                <p><b>Faturamento:</b> soma de <code>valorComboLiquido_dealV2</code> desses combos</p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Vistos por Vendedor</h3>
            <p class="dic-term-meta"><span>Vistos</span><span>Vistos e ticket por vendedor</span></p>
            <p class="dic-term-desc">Tabela com uma linha por vendedor: quantos vistos (combos) cada um vendeu no período e o ticket médio dessas vendas, em dólar. Vem ordenada de quem vendeu mais para quem vendeu menos. Mostra qualquer responsável com pelo menos 1 combo vendido — não fica restrito à equipe fixa de especialistas usada na aba Especialistas. Combos sem responsável preenchido não aparecem aqui, mas contam em Faturamento por Visto.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Agrupado por Vendedor (campo <code>responsavel_dealV2</code>)</p>
                <p><b>Vistos:</b> contagem de combos vendidos · <b>Ticket Médio:</b> soma de <code>valorComboLiquido_dealV2</code> ÷ Vistos</p>
              </div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="eficiencia">
        <h2 class="dic-section-title">Métricas de eficiência</h2>
        <p class="dic-note"><strong>Nota:</strong> As métricas de CAC e CPL usam apenas o <strong>Investimento MKT</strong> como base (sem custo físico), pois refletem a eficiência do time de aquisição de leads e vendas, não os custos operacionais dos eventos.</p>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">CPL</h3>
            <p class="dic-term-meta"><span>Eficiência de aquisição</span><span>Custo por Lead Qualificado</span></p>
            <p class="dic-term-desc">Quanto custou, em média, cada lead qualificado. Mede a eficiência das campanhas de aquisição — quanto menos, melhor (desde que a qualidade seja mantida).</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Investimento MKT ÷ Leads Qualificados</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">CAC Cadeira</h3>
            <p class="dic-term-meta"><span>Canal Evento</span><span>Custo por Cadeira Vendida</span></p>
            <p class="dic-term-desc">Quanto custou, em média, vender cada ingresso de evento. Útil para avaliar se o investimento em aquisição está proporcional à quantidade de pessoas que chegam ao evento.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Investimento MKT ÷ Cadeiras Vendidas</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">CAC Consulta</h3>
            <p class="dic-term-meta"><span>Eficiência comercial</span><span>Custo por Consulta Vendida</span></p>
            <p class="dic-term-desc">Quanto custou, em média, agendar cada consulta. Reflete a eficiência do time comercial em converter leads qualificados em reuniões marcadas.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Investimento MKT ÷ Consultas Vendidas</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">CAC Combo</h3>
            <p class="dic-term-meta"><span>Eficiência de fechamento</span><span>Custo por Venda Fechada</span></p>
            <p class="dic-term-desc">Quanto custou, em média, fechar cada venda. É a métrica de eficiência mais importante — quanto menor o CAC Combo em relação ao ticket médio, mais rentável a operação.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Investimento MKT ÷ Combos Vendidos</p></div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="leitura">
        <h2 class="dic-section-title">Como ler a aba Executive</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Cartões Evento, Digital, Orgânico e Indicação</dt>
            <dd>Abaixo do painel geral, os mesmos números são repartidos em quatro cartões pela campanha de origem de cada lead, card ou gasto: campanhas de evento, orgânicas e de indicação têm prefixos próprios, e todo o resto é Digital. Quando uma campanha está selecionada no filtro, leads e cards passam a ser separados pelo formulário preenchido: formulário de evento conta em Evento, e qualquer outro formulário conta igualmente em Digital, Orgânico e Indicação. Orgânico e Indicação não têm investimento pago rastreado: o ROI aparece como traço, CAC e CPL como "Não aplicável" e, sem campanha selecionada, o Lucro desses cartões é o próprio faturamento.
              <details class="dic-rule"><summary>Regra de classificação</summary>
                <div class="dic-rule-body">
                  <p><b>Evento:</b> campanha começa com <code>OTR</code>, <code>INA</code>, <code>OTW</code> ou <code>WEB</code></p>
                  <p><b>Orgânico:</b> começa com <code>ORG</code> ou é <code>Negado Cliente</code></p>
                  <p><b>Indicação:</b> começa com <code>IND</code> ou é <code>F&amp;F</code> ou <code>MGM</code></p>
                  <p><b>Digital:</b> qualquer outra campanha; campanha nula (<code>NULL</code>) não entra em nenhum cartão, só no total geral</p>
                  <p>Leads por <code>utmCampaign_leadV2</code>, cards por <code>utmCampaign_dealV2</code>, investimento MKT por <code>nomeCampanha_custoMkt</code></p>
                  <p><b>Com campanha selecionada:</b> leads e cards pelo formulário (<code>formPreenchido_*</code> começando com <code>OTR</code>, <code>INA</code>, <code>OTW</code> ou <code>WEB</code> = Evento; qualquer outro, inclusive vazio = Digital, Orgânico e Indicação); o investimento MKT continua pelo nome da campanha</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Comparativo ao passar o mouse</dt>
            <dd>Ao passar o mouse sobre um número do painel geral ou dos cartões de segmento, aparece um comparativo daquele número com até três linhas: o período atual, o mesmo período do ano anterior (com a seta de variação) e, quando há mês ou intervalo de datas selecionado, o período imediatamente anterior de mesmo tamanho ("últ. mês"). As comparações são proporcionais ao dia: se o período atual vai até hoje, os outros períodos são cortados no mesmo dia. A seta fica verde quando a variação é boa (para CPL e CACs, cair é bom) e vermelha quando é ruim. Os números do comparativo consideram só o período: os filtros de Campanha, Form e Responsável não se aplicam a eles.
              <details class="dic-rule"><summary>Como os períodos são montados</summary>
                <div class="dic-rule-body">
                  <p><b>Atual:</b> do dia 1º do primeiro mês selecionado até o fim do último (ou até hoje, se o último for o mês corrente); sem mês, de 1º de janeiro do ano selecionado até hoje; com intervalo de datas, o próprio intervalo</p>
                  <p><b>Ano anterior:</b> o mesmo intervalo um ano antes, cortado no mesmo dia</p>
                  <p><b>Período anterior:</b> a mesma quantidade de meses (ou de dias, no intervalo de datas) imediatamente antes, cortada no mesmo dia; só aparece com mês ou intervalo selecionado</p>
                  <p>Sem seta quando a base de comparação é zero ou menor que 5 (contagens e ROI) ou US$ 500 (valores em dólar); acima de 999%, a seta aparece sem o número</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Cores do ROI e do Lucro</dt>
            <dd>ROI de 1,0x ou mais fica verde e ROI zero fica vermelho; nos cartões Evento e Digital, ROI entre 0 e 1,0x fica âmbar, e a barrinha abaixo dele enche até 2,0x, com a marca do meio indicando 1,0x (empate). Sem investimento no período, o ROI fica sem cor. Lucro negativo aparece em vermelho.</dd>
          </div>
          <div class="dic-def">
            <dt>Filtros</dt>
            <dd>Ano e Mês (por padrão, o ano e o mês atuais) ou um intervalo de datas, que só vale quando início e fim estão preenchidos. Form, Campanha e Responsável restringem leads e cards pelo formulário preenchido, pela campanha de origem e pelo responsável. O investimento MKT e o custo físico respeitam Form e Campanha, mas não o Responsável; as tabelas de vistos não usam o Responsável; e o comparativo ao passar o mouse não usa nenhum dos três.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="digital-evento">
        <h2 class="dic-section-title">Aba Digital × Evento</h2>
        <p class="dic-section-intro">O mesmo funil da aba Executive em dois lados, Evento e Digital, separados pelo formulário preenchido e não pelo nome da campanha. O painel do topo soma os dois lados.</p>
        <p class="dic-note is-warn"><strong>Atenção:</strong> nesta aba o investimento MKT e o custo físico são sempre o total do período — os filtros de Campanha, Form e Responsável valem só para leads e cards. Com algum desses filtros, CPL, CACs, Lucro e ROI comparam o funil filtrado com o investimento inteiro do período.</p>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Como os dois lados são separados</dt>
            <dd>Lead ou card com formulário de evento vai para o lado Evento; qualquer outro formulário, inclusive sem formulário, vai para Digital. Não há cartões de Orgânico e Indicação: os leads e cards dessas campanhas entram em Evento ou Digital conforme o formulário.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Evento:</b> <code>formPreenchido_leadV2</code> / <code>formPreenchido_dealV2</code> começa com <code>OTR</code>, <code>INA</code>, <code>OTW</code> ou <code>WEB</code></p>
                  <p><b>Digital:</b> qualquer outro formulário, inclusive vazio</p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
        <div class="dic-list">
          <article class="dic-term" data-stage="sinal">
            <h3 class="dic-term-name">FPs Realizados</h3>
            <p class="dic-term-meta"><span>Sinal e FP</span><span>Pipe Comercial 2 (C5)</span></p>
            <p class="dic-term-desc">Cards do pipe Comercial 2 que já fizeram o FP (Financial Planning), contados pela data em que o FP foi realizado. Só entram cards que também têm consulta realizada e FP agendado, e que não estão nas fases Comercial 3, Distrato ou Lead sem qualificação.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><code>LEFT(faseId_dealV2, 2) = 'C5'</code> com <code>dataFpRealizado_dealV2</code> no período</p>
                <p><code>dataConsultaRealizada_dealV2</code> e <code>dataFpAgendado_dealV2</code> preenchidas</p>
                <p><code>fase_dealV2 NOT IN ('Comercial 3', 'Distrato', 'Lead sem qualificação')</code></p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Investimento MKT por identificador</h3>
            <p class="dic-term-meta"><span>Custo</span><span>Em dólar</span></p>
            <p class="dic-term-desc">Gasto com anúncios separado pelo identificador do lançamento (o formulário ou evento a que o gasto está ligado): identificador de evento vai para o lado Evento; lançamento sem identificador vai para Evento quando a campanha é OTR; todo o resto vai para Digital. A conversão para dólar é a mesma do Investimento MKT da aba Executive.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><b>Evento:</b> <code>identificador_custoMkt</code> começa com <code>OTR</code>, <code>INA</code>, <code>OTW</code> ou <code>WEB</code>; ou, sem identificador, <code>nomeCampanha_custoMkt</code> começa com <code>OTR</code></p>
                <p><b>Digital:</b> os demais lançamentos</p>
                <p>Conversão para USD: mesma regra por ano e conta, e mesmo câmbio, do Investimento MKT</p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Custo Físico por lado</h3>
            <p class="dic-term-meta"><span>Custo</span><span>Em dólar</span></p>
            <p class="dic-term-desc">Custos dos eventos presenciais em cada lado. O lado Evento soma os custos de evento cujo identificador também aparece num lançamento de marketing com identificador de evento; o lado Digital não tem custo físico; o painel do topo soma todos os custos de evento do período. A conversão para dólar é a mesma do Custo Físico da aba Executive.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><b>Evento:</b> <code>identificador_custoEvento</code> presente entre os <code>identificador_custoMkt</code> que começam com <code>OTR</code>, <code>INA</code>, <code>OTW</code> ou <code>WEB</code></p>
                <p><b>Digital:</b> 0</p>
                <p><b>Painel do topo:</b> todos os custos de evento do período</p>
              </div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Funil de cada lado</dt>
            <dd>Cada lado mostra o funil em barras, proporcionais à quantidade de leads daquele lado, com a conversão sobre a etapa anterior ao lado de cada número. Aqui o funil tem uma etapa a mais, FPs Realizados, entre Consultas Realizadas e Combos — por isso o % de Combos é sobre os FPs, e não sobre as consultas realizadas como na aba Executive. O lado Digital não tem a etapa de Cadeiras (nem o CAC Cadeira): o % de Consultas Vendidas é sobre os Qualificados.
              <details class="dic-rule"><summary>Como é calculado</summary>
                <div class="dic-rule-body">
                  <p><b>Evento:</b> Qualificados ÷ Leads · Cadeiras ÷ Qualificados · Consultas Vendidas ÷ Cadeiras · Consultas Realizadas ÷ Consultas Vendidas · FPs ÷ Consultas Realizadas · Combos ÷ FPs</p>
                  <p><b>Digital:</b> Qualificados ÷ Leads · Consultas Vendidas ÷ Qualificados · Consultas Realizadas ÷ Consultas Vendidas · FPs ÷ Consultas Realizadas · Combos ÷ FPs</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Lucro e ROI nos cartões</dt>
            <dd>Investimento, Faturamento, Lucro, ROI, CPL, CACs e Ticket Médio seguem as mesmas fórmulas da aba Executive, com os custos desta aba. No cabeçalho dos lados Evento e Digital, o Lucro nunca aparece negativo: quando o custo supera o faturamento, mostra zero. No painel do topo, o Lucro pode ser negativo.</dd>
          </div>
          <div class="dic-def">
            <dt>Selo de desempenho</dt>
            <dd>Selo ao lado do nome de cada lado: Líder Absoluto para o lado com ROI e Lucro maiores, Melhor ROI ou Maior Lucro quando ele vence em só um dos dois. O Lucro comparado é o que aparece no cartão (negativo conta como zero).</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="especialistas">
        <h2 class="dic-section-title">Aba Especialistas</h2>
        <p class="dic-section-intro">Funil do pipe Comercial 2 por especialista, da consulta agendada ao combo. Sem filtro de Responsável, considera só a equipe fixa de seis especialistas: Rafael Torres, Beatriz Lemos, Camila Duarte, Paulo Nogueira, Juliana Prado e Ricardo Albuquerque.</p>
        <div class="dic-list">
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Agendadas</h3>
            <p class="dic-term-meta"><span>Consulta</span><span>Pipe Comercial 2 (C5)</span></p>
            <p class="dic-term-desc">Consultas marcadas no período. Usa uma data corrigida: se a consulta foi realizada sem data de agendamento, ou realizada num mês diferente do agendado, ela conta pela data em que foi realizada — assim a mesma consulta não aparece em dois meses.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><code>LEFT(faseId_dealV2, 2) = 'C5'</code> com data agendada corrigida no período</p>
                <p>Data corrigida = <code>dataConsultaRealizada_dealV2</code> quando <code>dataConsultaAgendada_dealV2</code> está vazia ou cai em mês diferente da realizada; senão, <code>dataConsultaAgendada_dealV2</code></p>
              </div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Realizadas</h3>
            <p class="dic-term-meta"><span>Consulta</span><span>Pipe Comercial 2 (C5)</span></p>
            <p class="dic-term-desc">Consultas que de fato aconteceram no período, pela data da realização. Mesma regra de Consultas Realizadas da aba Executive, mas só dos responsáveis considerados nesta aba.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>LEFT(faseId_dealV2, 2) = 'C5'</code> com <code>dataConsultaRealizada_dealV2</code> no período</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">A Realizar</h3>
            <p class="dic-term-meta"><span>Consulta</span><span>Agendadas − Realizadas</span></p>
            <p class="dic-term-desc">Diferença entre as consultas agendadas e as realizadas no período. Nunca fica negativa.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>máximo(0, Agendadas − Realizadas)</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="sinal">
            <h3 class="dic-term-name">FP Vendido</h3>
            <p class="dic-term-meta"><span>Sinal e FP</span><span>Pipe Comercial 2 (C5)</span></p>
            <p class="dic-term-desc">Cards com consulta realizada no período que já tiveram o FP agendado e realizado, fora das fases Comercial 3, Distrato e Lead sem qualificação. Conta pela data da consulta realizada — diferente de FPs Realizados da aba Digital × Evento, que conta pela data do FP.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><code>LEFT(faseId_dealV2, 2) = 'C5'</code> com <code>dataConsultaRealizada_dealV2</code> no período</p>
                <p><code>dataFpAgendado_dealV2</code> e <code>dataFpRealizado_dealV2</code> preenchidas</p>
                <p><code>fase_dealV2 NOT IN ('Comercial 3', 'Distrato', 'Lead sem qualificação')</code></p>
              </div>
            </details>
          </article>
          <article class="dic-term" data-stage="fecho">
            <h3 class="dic-term-name">Assinaturas</h3>
            <p class="dic-term-meta"><span>Fechamento</span><span>Contratos enviados</span></p>
            <p class="dic-term-desc">Cards que estão na fase de contrato enviado do pipe Comercial 2 (na tela, "Contratos enviados"), com consulta e FP realizados. Conta pela data da consulta realizada.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><code>faseId_dealV2 = 'C5:UC_0Q30H1'</code> com <code>dataConsultaRealizada_dealV2</code> no período</p>
                <p><code>dataFpAgendado_dealV2</code> e <code>dataFpRealizado_dealV2</code> preenchidas</p>
              </div>
            </details>
          </article>
          <article class="dic-term" data-stage="fecho">
            <h3 class="dic-term-name">Combos</h3>
            <p class="dic-term-meta"><span>Fechamento</span><span>Pipes C1, C5, C29 e C31</span></p>
            <p class="dic-term-desc">Combos vendidos no período, pela data de assinatura do contrato. Mesma regra de Combos Vendidos da aba Executive, mas só dos responsáveis considerados nesta aba.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON')</code> com <code>dataContratoComboAssinado_dealV2</code> no período</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Conversão</h3>
            <p class="dic-term-meta"><span>Conversão</span><span>Combos ÷ Realizadas</span></p>
            <p class="dic-term-desc">Quantos combos saíram para cada consulta realizada no período. Como cada número é contado pela sua própria data, compara volumes do mesmo período, não as mesmas pessoas.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Combos ÷ Realizadas (0 quando não há realizadas)</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Distrato</h3>
            <p class="dic-term-meta"><span>Perda</span><span>Rescisões com combo</span></p>
            <p class="dic-term-desc">Contratos de combo que foram assinados e depois desfeitos: cards em fase de distrato que tinham FP realizado e contrato assinado. Conta pela data da consulta realizada.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><code>faseId_dealV2 IN ('C5:UC_CL1DBM', 'C5:UC_AQ2QYT')</code> com <code>dataConsultaRealizada_dealV2</code> no período</p>
                <p><code>dataFpRealizado_dealV2</code> e <code>dataContratoComboAssinado_dealV2</code> preenchidas</p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Churn FP</h3>
            <p class="dic-term-meta"><span>Perda</span><span>Cancelamentos FP</span></p>
            <p class="dic-term-desc">Cards que fizeram o FP mas foram cancelados antes de assinar o combo: fase de distrato, FP realizado e sem contrato assinado. Conta pela data de venda.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><code>faseId_dealV2 IN ('C5:UC_CL1DBM', 'C5:UC_AQ2QYT')</code> com <code>dataVenda_dealV2</code> no período</p>
                <p><code>dataFpRealizado_dealV2</code> preenchida e <code>dataContratoComboAssinado_dealV2</code> vazia</p>
              </div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Gráfico Consultas Realizadas &amp; Combos Vendidos</dt>
            <dd>Colunas de consultas realizadas (pela data da realização) e de combos (pela data do contrato) em cada ano, mês, semana ou dia, conforme o botão escolhido; a linha mostra a conversão (combos ÷ realizadas) de cada período. Trocar a granularidade recarrega os dados.</dd>
          </div>
          <div class="dic-def">
            <dt>Tabela Performance por Especialista</dt>
            <dd>Realizadas, Combos, Conversão, Churn FP e Distrato de cada especialista, com as mesmas regras dos cartões. Só aparece quem teve ao menos uma consulta realizada no período, do que mais realizou para o que menos realizou, e a tabela lista apenas a equipe fixa de seis (ou os nomes escolhidos no filtro Especialista). A linha Total soma as linhas exibidas e recalcula a conversão.</dd>
          </div>
          <div class="dic-def">
            <dt>Filtros Especialista e Responsável</dt>
            <dd>O filtro Especialista só escolhe quais linhas aparecem na tabela; os cartões e o gráfico não mudam. O filtro Responsável muda todos os números da aba; sem ele, vale a equipe fixa de seis. Por padrão, o período é o mês atual.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="analitico">
        <h2 class="dic-section-title">Aba Analítico</h2>
        <p class="dic-section-intro">Consultas realizadas, combos e conversão de cada especialista da equipe fixa, mês a mês, separados por frente. Por padrão, mostra o ano atual, de janeiro até hoje.</p>
        <div class="dic-list">
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Realizadas por mês</h3>
            <p class="dic-term-meta"><span>Consulta</span><span>Pipe Comercial 2 (C5)</span></p>
            <p class="dic-term-desc">Consultas realizadas pelo especialista, agrupadas pelo mês (AnoMês) em que a consulta aconteceu.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>LEFT(faseId_dealV2, 2) = 'C5'</code>, agrupado pelo mês de <code>dataConsultaRealizada_dealV2</code></p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="fecho">
            <h3 class="dic-term-name">Combos por mês</h3>
            <p class="dic-term-meta"><span>Fechamento</span><span>Pipes C1, C5, C29 e C31</span></p>
            <p class="dic-term-desc">Combos vendidos pelo especialista, agrupados pelo mês em que o contrato foi assinado.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>faseId_dealV2 IN ('C1:WON', 'C5:WON', 'C29:won', 'C31:WON')</code>, agrupado pelo mês de <code>dataContratoComboAssinado_dealV2</code></p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Conversão por mês</h3>
            <p class="dic-term-meta"><span>Conversão</span><span>Combos ÷ Realizadas</span></p>
            <p class="dic-term-desc">Combos divididos pelas consultas realizadas de cada mês e do total. Como realizadas e combos são contados cada um pelo seu próprio mês, a conversão de um mês pode passar de 100%.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Combos ÷ Realizadas da linha (0 quando não há realizadas)</p></div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Frentes Evento, Digital e Total</dt>
            <dd>Evento reúne os cards com formulário de evento; Digital, todos os outros, inclusive sem formulário; Total soma as duas frentes.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Evento:</b> <code>formPreenchido_dealV2</code> começa com <code>OTR</code>, <code>INA</code>, <code>OTW</code> ou <code>WEB</code></p>
                  <p><b>Digital:</b> qualquer outro, inclusive vazio</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Bloco de cada especialista</dt>
            <dd>Um bloco por especialista, com três tabelas lado a lado (Evento, Digital e Total). Cada linha é um mês com Realizadas, Combos e Conversão; meses sem nenhum movimento naquela frente não aparecem. A linha Total soma os meses e recalcula a conversão.</dd>
          </div>
          <div class="dic-def">
            <dt>Filtros</dt>
            <dd>Só os seis especialistas da equipe fixa aparecem. O filtro Especialista escolhe quais blocos são exibidos, sem mudar os números; Responsável, Campanha, Form e o período mudam os números.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="serasa">
        <h2 class="dic-section-title">Serasa — análise de crédito</h2>
        <p class="dic-note"><strong>Nota:</strong> a consulta de crédito (Serasa) é feita após a Reunião e FP, em dois pipes comerciais — Comercial 2 (fases que começam com <code>C5</code>, o mesmo pipe de "Consultas Realizadas") e Comercial 4 (fases que começam com <code>C3</code> — etapa de execução pós-venda: Kickoff, Milestones, arquitetura do processo). A base desta aba são os cards desses dois pipes com FP realizado dentro do período: o filtro de data, ano e mês vale para a data do FP realizado, e card sem FP realizado não entra. Por padrão, vem selecionado só o ano atual. Como o número de consultas é limitado, nem todo card dessa base chega a ser consultado — por isso as métricas abaixo sempre deixam claro quando o cálculo é sobre o total da base (Total no Pipe) ou só sobre "quem já foi consultado".</p>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Total no Pipe</h3>
            <p class="dic-term-meta"><span>Base</span><span>Por pipe</span></p>
            <p class="dic-term-desc">Quantidade de cards do pipe com FP realizado no período, consultados no Serasa ou não. É a base usada para medir a cobertura.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><code>LEFT(faseId_dealV2, 2) = 'C5'</code> (Comercial 2) ou <code>'C3'</code> (Comercial 4)</p>
                <p><code>dataFpRealizado_dealV2</code> preenchida e dentro do período</p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Consulta Serasa Realizada</h3>
            <p class="dic-term-meta"><span>Consulta</span><span>Base das demais métricas</span></p>
            <p class="dic-term-desc">Indica que a consulta de crédito já foi feita para aquele card. Na tela aparece como Consultados (por pipe) e Cards Consultados no Serasa (os dois pipes somados). Usada como base de todas as outras métricas desta seção — as quantidades e percentuais abaixo (Nome Sujo, Sócio Negativado, faixas) são sempre calculados sobre os cards já consultados, não sobre o Total no Pipe.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>serasaRange_dealV2</code> preenchido (diferente de vazio)</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Cobertura</h3>
            <p class="dic-term-meta"><span>Consulta</span><span>Consultados ÷ Total no Pipe</span></p>
            <p class="dic-term-desc">Que parte da base do pipe já foi consultada no Serasa. Aparece no cabeçalho dos cartões Comercial 2 e Comercial 4.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Consultados ÷ Total no Pipe, por pipe</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Faixa Serasa (A–G)</h3>
            <p class="dic-term-meta"><span>Score</span></p>
            <p class="dic-term-desc">Classificação do score de crédito em letras: A é a melhor faixa (score mais alto), G é a pior (score mais baixo). Quanto mais perto de A, menor o risco de crédito do cliente.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Campo <code>serasaRange_dealV2</code>: <code>A</code> (melhor) → <code>G</code> (pior)</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Score Serasa</h3>
            <p class="dic-term-meta"><span>Score</span></p>
            <p class="dic-term-desc">Pontuação numérica de crédito retornada pela consulta — a mesma informação da Faixa, em número em vez de letra. O "Score Médio" de cada pipe é a média desse número entre os cards já consultados.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>serasaScore_dealV2</code></p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Nome Sujo</h3>
            <p class="dic-term-meta"><span>Risco</span><span>Qtd e % por pipe</span></p>
            <p class="dic-term-desc">Cliente com restrição de crédito ativa (nome negativado) encontrada na consulta. O percentual é sobre os cards já consultados do pipe, não sobre o Total no Pipe.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><code>nomeSujo_dealV2 = "Sim"</code></p>
                <p>% = Nome Sujo ÷ Consultas Serasa Realizadas (do pipe)</p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Sócio com Empresa Negativada</h3>
            <p class="dic-term-meta"><span>Risco</span><span>Qtd e % por pipe</span></p>
            <p class="dic-term-desc">O CPF consultado é sócio de uma empresa (CNPJ) que está negativada. Mesma regra de percentual do Nome Sujo: sobre a base de já consultados.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>socioEmpresaNegativa_dealV2 = "Sim"</code></p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Status CPF</h3>
            <p class="dic-term-meta"><span>Documentação</span><span>Situação na Receita Federal</span></p>
            <p class="dic-term-desc">Situação do CPF perante a Receita Federal no momento da consulta (ex.: Regular). Cards sem esse dado preenchido aparecem como "Não informado".</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>statusCpf_dealV2</code></p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Dívidas e Cartório (USD)</h3>
            <p class="dic-term-meta"><span>Financeiro</span><span>Em dólar</span></p>
            <p class="dic-term-desc">Total de dívidas e de protestos em cartório encontrados na consulta, somados por pipe (só cards consultados) e convertidos de reais para dólar. Veja a nota abaixo — a conversão usa uma regra diferente do resto do dashboard.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Soma em reais ÷ câmbio mais recente disponível</p>
                <p>Campos <code>valorTotalDividas_dealV2</code> e <code>valorCartorioTotal_dealV2</code></p>
              </div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Por que o valor em dólar da dívida muda mesmo sem a dívida mudar?</dt>
            <dd>Em todo o resto do dashboard, um custo em reais é convertido usando o câmbio <strong>do dia em que aquele gasto aconteceu</strong>. As dívidas e o total de cartório desta seção são diferentes: como são uma <strong>dívida ativa no Brasil</strong> (não um gasto histórico), a conversão sempre usa a <strong>cotação de dólar mais recente disponível</strong> — a mesma para todos os clientes, dos dois pipes, na consulta inteira. Por isso, o valor em dólar pode subir ou descer de um dia para o outro mesmo que a dívida em reais continue exatamente a mesma: é a cotação do dólar que está se movendo, não a dívida do cliente.</dd>
          </div>
          <div class="dic-def">
            <dt>Visão Geral</dt>
            <dd>Os dois pipes somados: Cards Consultados no Serasa, percentual de Nome Sujo e de Sócio Negativado sobre os consultados, e Dívida Total em dólar (só as dívidas, sem o cartório).</dd>
          </div>
          <div class="dic-def">
            <dt>Distribuição por Faixa e por Status CPF</dt>
            <dd>Em cada pipe, quantos cards consultados caíram em cada faixa (com o percentual sobre os consultados do pipe) e em cada situação de CPF. Faixas A e B aparecem em verde, F e G em vermelho e as demais em âmbar.</dd>
          </div>
          <div class="dic-def">
            <dt>Clientes com Consulta Serasa</dt>
            <dd>Lista card a card dos consultados no período, do FP mais recente para o mais antigo, limitada a 500 linhas (o contador mostra "500+" quando bate o limite). Dá para buscar por nome, só entre as linhas carregadas, e ordenar clicando no cabeçalho. Colunas: IdBitrix, Nome, Pipe, Fase, Fase ID, Data FP, Data Combo, Faixa, Score, Status CPF, Nome Sujo, Sócio Negativado, Dívidas (USD), Cartório (USD) e Responsável.</dd>
          </div>
        </dl>
      </section>

    </div>
  </div>

</div>
<script>initDicionario();</script>
