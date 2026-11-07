<?php $dicTo = (($_GET['to'] ?? '') === '1') ? '?to=1' : ''; ?>
<div class="dash-helenacastro dic" data-sugestoes="consulta,carrinho,descarte">

  <header class="dic-head">
    <p class="dic-eyebrow">Helena Castro</p>
    <h1 class="dic-title">Dicionário de métricas</h1>
    <p class="dic-lead">O que significa cada número do dashboard da Helena Castro. A tela de vendas acompanha, dia a dia, os leads em que ela é a responsável, contados pela data do último formulário, e as vendas dos cards dela, contadas pela data de venda. O foco são as consultas vendidas; os ingressos aparecem como acompanhamento secundário. A Jornada do cliente mostra o caminho dos leads pelas etapas que o sistema marca (carrinho, pagamento, abandono, descarte); ela abre nos leads da Helena Castro e pode mostrar os de qualquer responsável.</p>
    <nav class="dic-tabs" aria-label="Abas do dashboard">
      <a class="dic-tab" href="<?= $this->site ?>helenacastro<?= $dicTo ?>">Vendas diárias</a>
      <a class="dic-tab" href="<?= $this->site ?>helenacastro/jornada<?= $dicTo ?>">Jornada do cliente</a>
      <a class="dic-tab" href="<?= $this->site ?>helenacastro/dicionario<?= $dicTo ?>" aria-current="page">Dicionário</a>
    </nav>
  </header>

  <div class="dic-layout">
    <div class="dic-body">

      <section class="dic-section" id="fontes">
        <h2 class="dic-section-title">Fontes de dados</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Leads <code>leadV2</code></dt>
            <dd>Formulários captados em que Helena Castro é a responsável. Cada lead tem um tipo (consulta ou ingresso). Alimenta os leads atendidos e a conversão.
              <details class="dic-rule"><summary>Ligação com a vendedora</summary>
                <div class="dic-rule-body"><p><code>responsavel_leadV2 = 'Helena Castro'</code>, mais o filtro de Form sobre <code>formPreenchido_leadV2</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Oportunidades (deals) <code>dealV2</code></dt>
            <dd>Cards comerciais do CRM em que Helena Castro é a responsável. A fase e a data de venda dizem se o card virou consulta vendida ou ingresso vendido. Alimenta as vendas e a tabela de clientes.
              <details class="dic-rule"><summary>Ligação com a vendedora</summary>
                <div class="dic-rule-body"><p><code>responsavel_dealV2 = 'Helena Castro'</code>, mais o filtro de Form sobre <code>formPreenchido_dealV2</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Tags da jornada <code>lead_tag</code></dt>
            <dd>Marcações que o sistema grava no lead a cada passo (lead criado, entrou no carrinho, foi para o pagamento, pagamento confirmado, carrinho abandonado, lead descartado), cada uma com data, hora e uma descrição. Alimenta só a Jornada do cliente.
              <details class="dic-rule"><summary>Ligação com o lead</summary>
                <div class="dic-rule-body">
                  <p><code>lead_tag.id_leadV2 = leadV2.id_leadV2</code>; sem <code>id_leadV2</code> na tag, pelo ID do Bitrix (<code>idBitrix_lead</code> com <code>idBitrix_leadV2</code>)</p>
                  <p>Responsável: <code>responsavel_leadV2</code> desse lead (ver Responsável, na Jornada do cliente)</p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="filtros">
        <h2 class="dic-section-title">Filtros e período</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Últimos N dias, semanas ou meses</dt>
            <dd>Filtro opcional para olhar uma janela recente, por exemplo os últimos 30 dias, contando hoje. O período anda sozinho: amanhã, a mesma tela mostra os 30 dias até amanhã. Escolher Ano, Mês ou Data desliga esse filtro, e vice-versa.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Janela corrida que termina hoje e inclui hoje, resolvida no navegador e enviada como <code>data_inicio</code>/<code>data_fim</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Ano, Mês e Data</dt>
            <dd>Ao abrir, a tela de vendas e a Jornada vêm filtradas no mês atual, até hoje; o botão Limpar volta a esse ponto. O Ano e o Mês escolhidos viram um intervalo de datas contínuo, do primeiro dia do primeiro mês do primeiro ano ao último dia do último mês do último ano (ou os anos inteiros, sem mês escolhido); quando esse intervalo chega ao mês atual, ele vai só até hoje. Por isso, anos ou meses salteados levam junto os do meio, que ficam marcados no filtro: Jan e Mar viram Jan, Fev e Mar. Um mês que ainda não começou aparece zerado. Mudar uma das datas troca o Ano e o Mês pelas datas que estão nos campos; escolher Ano ou Mês de novo apaga as datas digitadas. Os números de leads usam a data do último formulário do lead, e os de vendas usam a data de venda do card.
              <details class="dic-rule"><summary>Data usada por métrica</summary>
                <div class="dic-rule-body">
                  <p><b>Leads (atendidos e de consulta):</b> <code>dataUltForm_leadV2</code></p>
                  <p><b>Vendas (consultas, ingressos e tabela de clientes):</b> <code>dataVenda_dealV2</code></p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Form</dt>
            <dd>Restringe o painel aos formulários escolhidos. Nos leads vale o formulário do lead; nas vendas, o formulário do card. A lista junta os formulários que aparecem nas duas fontes.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Leads:</b> <code>formPreenchido_leadV2 IN (...)</code></p>
                  <p><b>Vendas e tabela de clientes:</b> <code>formPreenchido_dealV2 IN (...)</code></p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Modo demonstração</dt>
            <dd>Botão no topo das telas de vendas e da Jornada que troca os números reais por números fictícios, para apresentar o painel enquanto ainda não há dados. Com ele ligado, a tela mostra uma faixa e a etiqueta "Demonstração" e os clientes aparecem como "Cliente Exemplo". Nas vendas, o filtro de Form não se aplica; na Jornada, a busca de cliente procura entre os leads fictícios. Os demais filtros continuam valendo. O endereço ganha <code>?demo=1</code>, então o link copiado já abre em demonstração, e as abas do topo levam a demonstração junto.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Números sorteados no navegador com semente na data (o mesmo dia sempre dá os mesmos números); nada é lido do banco nem gravado</p></div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="indicadores">
        <h2 class="dic-section-title">Indicadores do topo</h2>
        <p class="dic-section-intro">Os quatro números do topo da tela, sempre do período filtrado. Ao lado de cada um aparece a variação em relação ao período anterior.</p>
        <ol class="dic-steps">
          <li data-stage="aquisicao"><strong>Leads atendidos</strong><span>Formulários captados no período</span></li>
          <li data-stage="consulta"><strong>Consultas vendidas</strong><span>Reunião comercial comprada</span></li>
          <li data-stage="evento"><strong>Ingressos vendidos</strong><span>Ingresso para evento presencial</span></li>
        </ol>
        <div class="dic-list">
          <article class="dic-term" data-stage="aquisicao">
            <h3 class="dic-term-name">Leads atendidos</h3>
            <p class="dic-term-meta"><span>Aquisição</span><span>Leads</span></p>
            <p class="dic-term-desc">Todos os leads em que Helena Castro é a responsável, com último formulário preenchido dentro do período, de qualquer tipo.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem de leads com <code>dataUltForm_leadV2</code> preenchida e dentro do período</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Consultas vendidas</h3>
            <p class="dic-term-meta"><span>Consulta</span><span>Pipe digital (C11) e pipe principal</span></p>
            <p class="dic-term-desc">Consultas comerciais vendidas nos cards da Helena Castro, pela data de venda.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>faseId_dealV2 IN ('Won', 'C11:WON')</code> com <code>dataVenda_dealV2</code> preenchida e dentro do período</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Conversão em consulta</h3>
            <p class="dic-term-meta"><span>Conversão</span><span>Consultas ÷ leads de consulta</span></p>
            <p class="dic-term-desc">Consultas vendidas sobre os leads de consulta do mesmo período. Compara os totais das duas fontes; não acompanha o mesmo lead até a venda, então uma venda do período pode ter vindo de um lead de antes. A variação desta métrica é em pontos percentuais (p.p.).</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Consultas vendidas ÷ leads com <code>tipoLead_leadV2 = 'Consulta'</code> no período</p>
                <p>Zero quando não há lead de consulta no período</p>
              </div>
            </details>
          </article>
          <article class="dic-term" data-stage="evento">
            <h3 class="dic-term-name">Ingressos vendidos</h3>
            <p class="dic-term-meta"><span>Evento</span><span>Pipe de cadeiras (C9)</span></p>
            <p class="dic-term-desc">Ingressos para eventos presenciais (cadeiras) vendidos nos cards da Helena Castro, pela data de venda.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>faseId_dealV2 = 'C9:Won'</code> com <code>dataVenda_dealV2</code> preenchida e dentro do período</p></div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Variação</dt>
            <dd>Compara cada número com o período anterior de mesmo tamanho, logo antes do filtrado: nos últimos 30 dias, compara com os 30 dias anteriores. A seta para cima indica aumento e a para baixo, queda; "novo" aparece quando o período anterior foi zero. A frase abaixo dos indicadores diz quais datas foram comparadas.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p>(atual − anterior) ÷ anterior, em %; na conversão, atual − anterior, em p.p.</p>
                  <p>Período anterior: mesmo número de dias, terminando no dia anterior ao início do filtro</p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
        <p class="dic-note"><strong>Leads e vendas são registros diferentes:</strong> os leads vêm do cadastro de leads e as vendas vêm dos cards, cada um com a sua data. Uma venda do período não precisa ter vindo de um lead do mesmo período.</p>
      </section>

      <section class="dic-section" id="evolucao">
        <h2 class="dic-section-title">Evolução por período</h2>
        <p class="dic-section-intro">Os três gráficos mostram os mesmos números do topo distribuídos no tempo. Cada barra traz o próprio valor; passar o mouse (ou navegar com as setas do teclado) mostra todos os números daquele dia, semana ou mês.</p>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Agrupar por: Dia, Semana ou Mês</dt>
            <dd>Muda o agrupamento dos gráficos e da tabela de relatório, sem alterar os indicadores do topo. A semana vai de segunda a domingo.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Leads:</b> agrupados por <code>dataUltForm_leadV2</code></p>
                  <p><b>Vendas:</b> agrupadas por <code>dataVenda_dealV2</code></p>
                  <p>Dias sem movimento entram com zero</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Período incompleto</dt>
            <dd>Quando o filtro começa ou termina no meio de uma semana ou de um mês, essa semana ou esse mês fica com menos dias que o normal. O dia de hoje, e a semana e o mês em que ele cai, também estão incompletos, porque o dia ainda não terminou. A barra aparece mais clara e a tabela marca "(parcial)", para não ser comparada de igual para igual com as outras.</dd>
          </div>
          <div class="dic-def">
            <dt>Média do período</dt>
            <dd>A linha tracejada do gráfico de consultas vendidas é a média de consultas por dia, semana ou mês, conforme o agrupamento. Dias, semanas e meses incompletos, inclusive hoje, ficam fora da conta.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="relatorio">
        <h2 class="dic-section-title">Relatório por período</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Relatório por dia, semana ou mês</dt>
            <dd>Uma linha por dia (ou semana, ou mês, conforme o agrupamento), do mais recente para o mais antigo, com leads, leads de consulta, consultas vendidas, conversão e ingressos vendidos. A linha Total, fixa no rodapé, repete os indicadores do topo. Conversão aparece como "—" quando não houve lead de consulta.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Leads de consulta:</b> <code>tipoLead_leadV2 = 'Consulta'</code></p>
                  <p><b>Conversão da linha:</b> consultas vendidas ÷ leads de consulta da mesma linha</p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="clientes">
        <h2 class="dic-section-title">Tabela de clientes</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Clientes</dt>
            <dd>Uma venda por linha: as consultas e os ingressos vendidos nos cards da Helena Castro no período, das vendas mais recentes para as mais antigas, até 500 linhas. A coluna Tipo diz se a venda foi de consulta ou de ingresso, e a coluna Form mostra o formulário do card. Clicar no cabeçalho reordena e a busca filtra por nome ou e-mail.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Ingresso:</b> <code>faseId_dealV2 = 'C9:Won'</code></p>
                  <p><b>Consulta:</b> <code>faseId_dealV2 IN ('Won', 'C11:WON')</code></p>
                  <p><code>dataVenda_dealV2</code> preenchida e dentro do período, em ordem decrescente; limite de 500 linhas</p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="jornada">
        <h2 class="dic-section-title">Jornada do cliente: período e etapas</h2>
        <p class="dic-section-intro">A Jornada mostra por onde os leads passaram, a partir das tags que o sistema grava. O caminho principal tem quatro etapas; carrinho abandonado e lead descartado são as saídas.</p>
        <ol class="dic-steps">
          <li data-stage="aquisicao"><strong>Lead criado</strong><span>O lead entrou no sistema</span></li>
          <li data-stage="consulta"><strong>Entrou no carrinho</strong><span>Abriu o carrinho com um produto</span></li>
          <li data-stage="consulta"><strong>Foi para o pagamento</strong><span>Escolheu a forma de pagamento</span></li>
          <li data-stage="fecho"><strong>Pagamento confirmado</strong><span>O pagamento foi aprovado</span></li>
        </ol>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Leads do período</dt>
            <dd>O filtro de período escolhe quais leads entram na conta: os que começaram a jornada, ou seja, tiveram a primeira tag, dentro do período. A partir daí, a tela acompanha esses leads até agora: um lead que entrou no fim de outubro e pagou em novembro conta como pago no filtro de outubro. Por isso uma etapa nunca tem mais leads que a anterior, e os números de um período recente ainda crescem enquanto os leads dele avançam.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p>Leads do período: leads com a primeira tag do fluxograma (<code>MIN(dataCadastro)</code>) entre <code>data_inicio</code> e <code>data_fim</code></p>
                  <p>Etapas e setas consideram todas as tags desses leads, sem limite de data</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Ordem das tags</dt>
            <dd>As tags de um lead são lidas na ordem em que aconteceram. Quando duas caem no mesmo segundo, vale a ordem em que foram gravadas.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p><code>ORDER BY dataCadastro, id_lead_tag</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Tags fora do fluxograma</dt>
            <dd>Tags novas que o sistema passar a gravar (por exemplo, webinar assistido) não entram no fluxograma nem mudam as contagens. Aparecem numa linha abaixo dele, com quantos leads do período as têm, e na trilha do cliente, até serem incluídas no fluxograma.</dd>
          </div>
          <div class="dic-def">
            <dt>Responsável</dt>
            <dd>A Jornada abre nos leads em que Helena Castro é a responsável; o filtro aceita um ou mais responsáveis, e tirar todos mostra os leads de todo mundo. O Limpar volta para a Helena. Vale o responsável atual do lead no CRM, não o que ele tinha em cada passo: um lead que mudou de mãos aparece com o responsável de agora. Leads que ainda não chegaram ao cadastro de leads não têm responsável e só aparecem com o filtro vazio. A busca de Cliente procura só entre os leads dos responsáveis filtrados.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><code>TRIM(responsavel_leadV2) IN (...)</code> do lead ligado à tag (por <code>id_leadV2</code> ou, sem ele, pelo ID do Bitrix)</p>
                  <p>Sem responsável escolhido: todos os leads do período, inclusive os sem <code>leadV2</code></p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
        <p class="dic-note"><strong>As tags são do sistema, não da vendedora:</strong> o sistema grava as tags de todos os leads, e o filtro de responsável só escolhe de quem são os leads mostrados.</p>
      </section>

      <section class="dic-section" id="jornada-indicadores">
        <h2 class="dic-section-title">Jornada do cliente: indicadores do topo</h2>
        <div class="dic-list">
          <article class="dic-term" data-stage="aquisicao">
            <h3 class="dic-term-name">Leads na jornada</h3>
            <p class="dic-term-meta"><span>Aquisição</span><span>Base dos percentuais</span></p>
            <p class="dic-term-desc">Leads que começaram a jornada no período. É a base dos percentuais da tela.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Leads distintos (<code>idBitrix_lead</code>) com a primeira tag do fluxograma no período</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Entraram no carrinho</h3>
            <p class="dic-term-meta"><span>Carrinho</span><span>% sobre os leads na jornada</span></p>
            <p class="dic-term-desc">Leads do período que entraram no carrinho pelo menos uma vez, em qualquer momento.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Leads do período com a tag <code>carrinho-acessado</code></p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="fecho">
            <h3 class="dic-term-name">Pagamentos confirmados</h3>
            <p class="dic-term-meta"><span>Fechamento</span><span>% sobre os leads na jornada</span></p>
            <p class="dic-term-desc">Leads do período com o pagamento confirmado.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Leads do período com a tag <code>pagamento-confirmado</code></p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="sinal">
            <h3 class="dic-term-name">Abandonaram o carrinho</h3>
            <p class="dic-term-meta"><span>Abandono</span><span>% sobre quem entrou no carrinho</span></p>
            <p class="dic-term-desc">Leads do período que tiveram o carrinho abandonado pelo menos uma vez, mesmo que depois tenham voltado e pagado.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Leads do período com a tag <code>carrinho-abandonado</code> ÷ leads com <code>carrinho-acessado</code></p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Tempo até pagar</h3>
            <p class="dic-term-meta"><span>Tempo</span><span>Mediana</span></p>
            <p class="dic-term-desc">Tempo entre a primeira tag do lead e o pagamento confirmado, só entre quem pagou. É a mediana: metade dos leads pagou mais rápido que isso, metade mais devagar.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Mediana de <code>pagamento-confirmado</code> − primeira tag do fluxograma, por lead</p></div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="jornada-fluxograma">
        <h2 class="dic-section-title">Jornada do cliente: fluxograma, lista e trilha</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Etapa</dt>
            <dd>Cada caixa diz quantos leads do período chegaram àquela etapa e o percentual sobre esses leads; no carrinho abandonado, o percentual é sobre quem entrou no carrinho. "Parados aqui" são os leads que estão naquela etapa agora, sem nenhuma tag depois. Clicar na caixa lista só os leads que passaram por ela.</dd>
          </div>
          <div class="dic-def">
            <dt>Seta</dt>
            <dd>Leads que foram direto de uma etapa para a outra, com o percentual sobre quem chegou na etapa de origem. A espessura acompanha a quantidade; a cor diz o destino (verde para o pagamento, âmbar para o abandono, vermelho para o descarte, cinza para as etapas do meio); a seta tracejada é de quem voltou para uma etapa anterior. Passando o mouse aparece o tempo mediano entre as duas tags. Um lead que repete o mesmo caminho conta uma vez.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Pares de tags consecutivas do fluxograma no mesmo lead (tags fora do fluxograma são puladas); leads distintos por par</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Voltaram e entraram direto</dt>
            <dd>"Voltaram" é a seta do carrinho abandonado de volta ao carrinho: leads que abandonaram e depois abriram o carrinho de novo. "Entraram direto" são leads cuja primeira tag já foi o carrinho, sem "lead criado" antes.</dd>
          </div>
          <div class="dic-def">
            <dt>Setas principais ou todas</dt>
            <dd>O fluxograma abre só com as setas principais, para o desenho ficar legível. As pequenas aparecem em "Todas", finas e sem número; o valor delas fica na Tabela e ao passar o mouse.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Seta principal: leva pelo menos 5% dos leads que chegaram na etapa de origem ou pelo menos 0,5% dos leads do período</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Fluxograma ou Tabela</dt>
            <dd>A Tabela mostra os mesmos números do fluxograma em linhas: de onde, para onde, quantos leads, o percentual e o tempo mediano.</dd>
          </div>
          <div class="dic-def">
            <dt>Jornadas do período</dt>
            <dd>Um lead do período por linha, do mais recente para o mais antigo, com a situação e a duração, da primeira à última tag do fluxograma. Os botões filtram pela situação e clicar no cabeçalho reordena. A lista traz até 3.000 leads; os indicadores e o fluxograma contam todos.
              <details class="dic-rule"><summary>Situação</summary>
                <div class="dic-rule-body">
                  <p><b>Pagou:</b> tem <code>pagamento-confirmado</code>, mesmo com outra tag depois</p>
                  <p><b>Abandonou o carrinho:</b> a última tag do fluxograma é <code>carrinho-abandonado</code></p>
                  <p><b>Descartado:</b> a última tag do fluxograma é <code>lead-descartado</code></p>
                  <p><b>Em andamento:</b> os demais, com a etapa em que pararam</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Trilha do cliente</dt>
            <dd>Todas as tags de um lead, em ordem, com data, hora, descrição e o tempo desde a tag anterior, inclusive as que ficam fora do fluxograma. Abre clicando numa linha da lista ou pela busca de Cliente nos filtros, que procura nos leads com tag dos responsáveis filtrados, de qualquer período, por nome, e-mail, telefone ou ID do Bitrix. O caminho do cliente fica destacado no fluxograma, com o número de cada passo.</dd>
          </div>
        </dl>
      </section>

    </div>
  </div>

</div>
<script>initDicionario();</script>
