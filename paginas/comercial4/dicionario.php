<?php $dicTo = (($_GET['to'] ?? '') === '1') ? '?to=1' : ''; ?>
<div class="dash-comercial4 dic" data-sugestoes="vendas,milestone,CSM">

  <header class="dic-head">
    <p class="dic-eyebrow">Comercial 4</p>
    <h1 class="dic-title">Dicionário de métricas</h1>
    <p class="dic-lead">O que significa cada número do dashboard Comercial 4, em linguagem simples, e de onde vêm os dados. No filtro de período, os clientes contam pela data de criação do caso e as vendas pela data da venda do orçamento.</p>
    <nav class="dic-tabs" aria-label="Abas do dashboard">
      <a class="dic-tab" href="<?= $this->site ?>comercial4<?= $dicTo ?>">Comercial 4</a>
      <a class="dic-tab" href="<?= $this->site ?>comercial4/liga<?= $dicTo ?>">Liga Acme-EUA</a>
      <a class="dic-tab" href="<?= $this->site ?>comercial4/dicionario<?= $dicTo ?>" aria-current="page">Dicionário</a>
    </nav>
  </header>

  <div class="dic-layout">
    <div class="dic-body">

      <section class="dic-section" id="fases">
        <h2 class="dic-section-title">Fases do funil (Bitrix, funil C3)</h2>
        <p class="dic-section-intro">As 7 etapas, na ordem em que aparecem no Funil de Clientes.</p>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Aplicados <code>fase = "Aplicados"</code></dt>
            <dd>O caso já foi formalmente enviado/aplicado junto ao órgão responsável (USCIS) e aguarda análise. É a primeira etapa do funil de Comercial 4.</dd>
          </div>
          <div class="dic-def">
            <dt>Milestones 1, 2 e 3 <code>fase = "Milestone 1/2/3"</code></dt>
            <dd>Marcos internos do acompanhamento pós-venda: o caso avança por 3 etapas sequenciais depois de aplicado, até a resolução final.</dd>
          </div>
          <div class="dic-def">
            <dt>RFE/Noid Respondida <code>fase = "RFE/Noid Respondida"</code></dt>
            <dd>A resposta ao pedido de evidências (ou à intenção de negar) já foi enviada ao órgão.</dd>
          </div>
          <div class="dic-def">
            <dt>RFE/Noid Recebida <code>fase = "RFE/Noid Recebida"</code></dt>
            <dd>O órgão pediu mais evidências (RFE, Request for Evidence) ou emitiu uma intenção de negar o caso (NOID, Notice of Intent to Deny). Etapa que exige atenção do case manager.</dd>
          </div>
          <div class="dic-def">
            <dt>RFE Ajuste de status <code>fase = "RFE Ajuste de status"</code></dt>
            <dd>Etapa final do ciclo de RFE/NOID, ligada ao ajuste de status do processo.</dd>
          </div>
        </dl>
        <p class="dic-note"><strong>Nota:</strong> todas essas fases vêm da mesma coluna <code>fase_dealV2</code> do deal no Bitrix, dentro do funil identificado pelo prefixo <code>faseId_dealV2 LIKE 'C3:%'</code>. Outras fases desse mesmo funil (ex.: Distrato, Inadimplente, Legado) existem no CRM e não aparecem no Funil de Clientes de 7 etapas, mas contam no indicador Total de Clientes.</p>
      </section>

      <section class="dic-section" id="kpis">
        <h2 class="dic-section-title">Os 4 indicadores do topo</h2>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Vendas</h3>
            <p class="dic-term-meta"><span>Fechamento</span><span>Orçamentos vendidos</span></p>
            <p class="dic-term-desc">Quantidade de orçamentos vendidos, entre os clientes do funil de Comercial 4, contados pela data da venda do orçamento dentro do período filtrado.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Orçamentos com <code>etapa = "Vendido"</code> e <code>dataVenda_orcamentoV2</code> no período, vinculados a um cliente do funil <code>C3:</code></p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Total de Clientes</h3>
            <p class="dic-term-meta"><span>Visão geral</span><span>Todos os casos do funil C3</span></p>
            <p class="dic-term-desc">Quantidade de clientes/casos do funil de Comercial 4 criados no período, em qualquer fase do funil, inclusive as que não fazem parte das 7 etapas do Funil de Clientes, como Distrato, Inadimplente e Legado. Não é só a carteira ativa: o total é sempre igual ou maior que a soma das barras do funil.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem de deals com <code>faseId_dealV2 LIKE 'C3:%'</code> e <code>criadoEm_dealV2</code> no período, sem excluir nenhuma fase</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Arquiteturas a Agendar</h3>
            <p class="dic-term-meta"><span>Pendente</span><span>Fase inicial do funil</span></p>
            <p class="dic-term-desc">Casos que ainda não tiveram a reunião de arquitetura agendada.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem de deals com <code>faseId_dealV2 = "C3:NEW"</code></p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Agendadas</h3>
            <p class="dic-term-meta"><span>Pendente</span><span>Reunião de arquitetura agendada</span></p>
            <p class="dic-term-desc">Casos com a reunião de arquitetura já agendada, aguardando sua realização.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem de deals com <code>faseId_dealV2 = "C3:PREPARATION"</code></p></div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="graficos">
        <h2 class="dic-section-title">Como ler os gráficos</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Funil de Clientes</dt>
            <dd>Mostra a quantidade de clientes e o valor de assessoria (em reais) em cada uma das 7 etapas do funil, sempre na mesma ordem fixa (a das fases acima), não em ordem decrescente. O comprimento de cada barra é proporcional à primeira etapa, Aplicados: uma etapa maior que Aplicados aparece com a barra cheia. O percentual ao lado de cada barra é a quantidade da etapa dividida pela da etapa anterior na lista, e pode passar de 100%.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p>Valor de assessoria: <code>valorAssessoria_dealV2</code></p>
                  <p>Largura da barra = etapa ÷ Aplicados (no máximo 100%); percentual = etapa ÷ etapa anterior, em branco na primeira etapa ou quando a anterior é zero</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Clientes e Vendas por CSM/Case Manager/ADV ML1-3</dt>
            <dd>Ranking de pessoas por quantidade de clientes e vendas fechadas, trocando o papel exibido pelas abas: <strong>CSM</strong> (responsável comercial), <strong>Case Manager</strong> (advogado de atendimento) e <strong>ADV ML1/ML2/ML3</strong> (advogado responsável por cada milestone). Os clientes seguem o período pela data de criação do caso; as vendas, pela data da venda do orçamento. A lista é ordenada pelo número de clientes; a barra mostra os clientes e o selo ao lado, as vendas. Quem vendeu no período sem ter caso criado nele aparece com 0 clientes, e casos sem ninguém no papel escolhido ficam fora do ranking.</dd>
          </div>
          <div class="dic-def">
            <dt>Performance de Vendas por Status / por Produtos</dt>
            <dd>Quantidade de itens de orçamento vendidos (etapa "Vendido") com data da venda no período filtrado, agrupados pela fase do cliente no funil ou pelo nome do produto vendido, do maior para o menor. O painel por produto mostra só os 20 produtos com mais vendas. Por status, a fase é a atual do cliente e pode ser qualquer fase do funil C3, não só as 7 etapas. Como aqui se contam itens, e não orçamentos, a soma pode ser diferente do indicador Vendas.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Contagem distinta de itens (<code>itemOrcamentoV2</code>) de orçamentos com etapa "Vendido", agrupada por <code>fase_dealV2</code> ou por <code>nomeOriginalProduto_itemOrcamentoV2</code> (por produto, só os 20 primeiros)</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Registros de Orçamento</dt>
            <dd>Lista paginada de orçamentos dos clientes do funil de Comercial 4, com nome do cliente, IdBitrix, responsável, etapa do orçamento e fase do funil. Entram orçamentos de qualquer etapa, de clientes cujo caso foi criado no período (a tabela segue a data de criação do caso, não a data da venda). São 20 linhas por página, dos orçamentos criados mais recentemente para os mais antigos. A busca procura o texto no nome do cliente, no IdBitrix, no responsável, na etapa e na fase. Clicar num cabeçalho ordena a lista inteira, não só a página visível. O botão "Exportar" baixa em CSV ou Excel todas as linhas do recorte atual (filtros, busca e ordenação), até 5.000 linhas.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p>Nome do cliente: <code>nome_orcamentoV2</code>; IdBitrix: <code>idBitrix_orcamentoV2</code> (o ID do orçamento); responsável: <code>responsavelComercial4_dealV2</code> (CSM)</p>
                  <p>Ordem padrão: <code>criadoEm_orcamentoV2</code> decrescente</p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="filtros">
        <h2 class="dic-section-title">Filtros da aba Comercial 4</h2>
        <p class="dic-section-intro">Os filtros se combinam entre si e valem para todos os indicadores, gráficos e para a tabela desta aba.</p>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Data, Ano e Mês</dt>
            <dd>Definem o período. Total de Clientes, Arquiteturas a Agendar, Agendadas, Funil de Clientes, os clientes do ranking e a tabela de registros usam a data de criação do caso; Vendas, as vendas do ranking e a Performance de Vendas usam a data da venda do orçamento. O intervalo de datas só vale com as duas datas preenchidas e, nesse caso, substitui Ano e Mês. A tela abre com o ano de 2026 selecionado.</dd>
          </div>
          <div class="dic-def">
            <dt>Fases</dt>
            <dd>Mantém só os clientes cuja fase atual é uma das escolhidas, entre as 7 etapas do funil. Com fases escolhidas, o Total de Clientes deixa de contar as fases que ficaram de fora.</dd>
          </div>
          <div class="dic-def">
            <dt>Produtos</dt>
            <dd>Mantém só os clientes que têm ao menos um orçamento, em qualquer etapa, com um dos produtos escolhidos. O filtro escolhe clientes, não itens: na Performance por Produtos, os outros produtos vendidos a esses clientes continuam aparecendo.</dd>
          </div>
          <div class="dic-def">
            <dt>Deal</dt>
            <dd>Mantém só os casos com um dos nomes de negócio escolhidos.</dd>
          </div>
          <div class="dic-def">
            <dt>CSM, Case Manager e ADV ML1-3</dt>
            <dd>Mantêm só os casos em que a pessoa escolhida ocupa aquele papel.</dd>
          </div>
          <div class="dic-def">
            <dt>Busca da tabela</dt>
            <dd>Afeta só a tabela de Registros de Orçamento; não muda indicadores nem gráficos.</dd>
          </div>
        </dl>
        <p class="dic-note">A aba Liga Acme-EUA não usa nenhum desses filtros.</p>
      </section>

      <section class="dic-section" id="liga">
        <h2 class="dic-section-title">Liga Acme-EUA</h2>
        <p class="dic-section-intro">Campanha de upsell das CSMs. Esta aba ignora os filtros da aba Comercial 4: o período é fixo, de 18/05/2026 até hoje.</p>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Meta 1 · Vendas em valor</h3>
            <p class="dic-term-meta"><span>Fechamento</span><span>Total vendido na campanha</span></p>
            <p class="dic-term-desc">Soma, em reais, das vendas de upsell das CSMs da Liga (Simone, Patrícia, Viviane e Tânia) desde 18/05/2026, comparada com a meta de R$ 480.000. Vendas em dólar são convertidas pela cotação do dia da venda ou, se esse dia não tiver cotação, pela última cotação anterior. Venda sem valor preenchido entra na quantidade, com R$ 0. Conta a venda de qualquer cliente dessas CSMs, inclusive de negócios criados depois de 18/05.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Orçamentos com etapa "Vendido" (<code>APPROVED</code>) e <code>dataVenda_orcamentoV2</code> entre 18/05/2026 e hoje, de clientes do funil <code>C3:</code> das 4 CSMs</p>
                <p><b>Valor:</b> <code>oportunidade_orcamentoV2</code>; em USD, × câmbio do dia (MAX); sem cotação no dia, a última anterior</p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Meta 2 · Clientes mapeados</h3>
            <p class="dic-term-meta"><span>Mapeamento</span><span>Carteira com os 10 produtos trabalhados</span></p>
            <p class="dic-term-desc">Clientes da carteira da campanha em que os 10 produtos do mapeamento já foram trabalhados: nenhum produto vazio ou em "Iniciar abordagem". A meta é de 378 clientes, numa carteira de 430. Esses 430 são um número fixo, tirado do relatório do CS, e não uma contagem refeita no Bitrix; a cobertura da carteira mostrada na tela é mapeados ÷ 430.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Negócios do funil <code>C3:</code> das 4 CSMs criados antes de 18/05/2026, com os 10 status de produto do negócio preenchidos e diferentes de "Iniciar abordagem"</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Meta 3 · Conversão de oportunidades</h3>
            <p class="dic-term-meta"><span>Provisório</span><span>Negociações fechadas ÷ negociações</span></p>
            <p class="dic-term-desc">Das oportunidades da carteira que chegaram a virar negociação, quantas foram fechadas. A meta é de pelo menos 25%. Provisório: usa o status atual de cada produto, inclusive negociações anteriores a 18/05, até a regra ser confirmada com o time de CS.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>fechadas ÷ (fechadas + em negociação + declinadas + orçamento pendente parceiro), pelos 10 status de produto dos negócios da carteira</p></div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Vendas da última semana</dt>
            <dd>Vendas dos últimos 7 dias, contando hoje, da maior para a menor, com cliente, produto, CSM, data e valor. O dólar indicado é a cotação mais recente. Quando um produto do mapeamento tem a primeira venda da campanha nessa semana, ele aparece em destaque.</dd>
          </div>
          <div class="dic-def">
            <dt>Vendas acumuladas e por quinzena</dt>
            <dd>O total vendido dividido em quinzenas de 15 dias a partir de 18/05, com a quantidade e o percentual da meta de cada uma. A quinzena atual aparece como "em andamento".</dd>
          </div>
          <div class="dic-def">
            <dt>Vendas por CSM e por produto</dt>
            <dd>Por CSM: valor, quantidade de vendas e percentual do total vendido de cada CSM, da maior para a menor em valor. Por produto: quantidade, valor, percentual do total e ticket médio (valor ÷ quantidade) de cada produto, ordenado pelo número de vendas. O ticket médio só aparece no painel por produto. Produto que não é um dos 10 do mapeamento aparece marcado como "fora do mapeamento", com a barra mais clara; venda sem produto aparece como "Sem produto".</dd>
          </div>
          <div class="dic-def">
            <dt>Nota abaixo do painel por produto</dt>
            <dd>"Os produtos do mapeamento respondem por…" soma só as vendas dos 10 produtos do mapeamento: valor, quantidade e percentual do total vendido na campanha. A frase seguinte, sobre o Visto de turismo ser comercializado à parte e não integrar o mapeamento da carteira, é um texto fixo do relatório, não um número calculado.</dd>
          </div>
          <div class="dic-def">
            <dt>Evolução de clientes mapeados</dt>
            <dd>Total de clientes mapeados em cada quinzena. As quinzenas encerradas vêm das anotações do time de CS, porque o Bitrix guarda só a situação atual; a quinzena em andamento é calculada ao vivo. Quinzena sem anotação aparece com "—" e as barras em verde são as quinzenas que bateram a meta de 378. O número de partida, à esquerda da seta, é a anotação da primeira quinzena. A frase "Meta alcançada em DD/MM" usa uma data e um total fixos, anotados no relatório do CS; só o complemento ("e mantida desde então" ou "mas hoje abaixo dela") compara o número de hoje com a meta.</dd>
          </div>
          <div class="dic-def">
            <dt>Conversão de oportunidades · como o número é formado</dt>
            <dd>Os quatro quadros contam pares cliente × produto da carteira pelo status atual de cada produto: negociações fechadas, em negociação, declinadas e aguardando orçamento (orçamento pendente do parceiro). A Meta 3 é o primeiro quadro dividido pela soma dos quatro.</dd>
          </div>
        </dl>
      </section>

    </div>
  </div>

</div>
<script>initDicionario();</script>
