<?php $dicTo = (($_GET['to'] ?? '') === '1') ? '?to=1' : ''; ?>
<div class="dash-marketing dic" data-sugestoes="combo,CAC,descarte">

  <header class="dic-head">
    <p class="dic-eyebrow">Marketing</p>
    <h1 class="dic-title">Dicionário de métricas</h1>
    <p class="dic-lead">Explicação de cada número exibido no dashboard de Marketing, em linguagem simples: de onde vêm os dados e o que cada métrica representa. O filtro de período incide sobre a data própria de cada métrica (formulário, venda, consulta realizada, contrato assinado ou gasto).</p>
    <nav class="dic-tabs" aria-label="Abas do dashboard">
      <a class="dic-tab" href="<?= $this->site ?>marketing<?= $dicTo ?>">Visão Geral</a>
      <a class="dic-tab" href="<?= $this->site ?>marketing/comparativo<?= $dicTo ?>">Comparativo</a>
      <a class="dic-tab" href="<?= $this->site ?>marketing/eventos<?= $dicTo ?>">Eventos</a>
      <a class="dic-tab" href="<?= $this->site ?>marketing/desempenho<?= $dicTo ?>">Desempenho</a>
      <a class="dic-tab" href="<?= $this->site ?>marketing/descartes<?= $dicTo ?>">Descartes</a>
      <a class="dic-tab" href="<?= $this->site ?>marketing/classificacao<?= $dicTo ?>">Classificação</a>
      <a class="dic-tab" href="<?= $this->site ?>marketing/dicionario<?= $dicTo ?>" aria-current="page">Dicionário</a>
    </nav>
  </header>

  <div class="dic-layout">
    <div class="dic-body">

      <section class="dic-section" id="fontes">
        <h2 class="dic-section-title">Fontes de dados</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Leads <code>leadV2</code></dt>
            <dd>Toda vez que alguém preenche um formulário, seja num anúncio digital ou num evento presencial, é criado um card de <strong>lead</strong> no CRM Bitrix. Cada card representa uma pessoa que demonstrou interesse. Essa é a primeira etapa do funil.</dd>
          </div>
          <div class="dic-def">
            <dt>Oportunidades (deals) <code>dealV2</code></dt>
            <dd>Quando um lead avança no processo comercial, é criado um card de <strong>deal</strong>. O deal percorre diferentes pipes (filas de vendas) até ser fechado ou perdido. A maioria das métricas de vendas (cadeiras, consultas, combos) é contada a partir dos deals.</dd>
          </div>
          <div class="dic-def">
            <dt>Investimento de marketing <code>custoMkt</code></dt>
            <dd>Registra os gastos com anúncios pagos (Meta Ads e Google Ads). Cada lançamento informa data, valor, plataforma, campanha, conjunto de anúncios e anúncio. Valores em reais são convertidos para dólar pela cotação do dia registrada na tabela de câmbio.</dd>
          </div>
          <div class="dic-def">
            <dt>Conversas no WhatsApp <code>conversaCW</code></dt>
            <dd>Cada conversa iniciada no WhatsApp/Chatwoot é registrada aqui, ligada ao lead correspondente do Bitrix. Serve para identificar quais leads de fato engajaram (responderam), base da métrica "Oportunidades".</dd>
          </div>
          <div class="dic-def">
            <dt>Filtro "Últimos" (dias, semanas ou meses)</dt>
            <dd>Mostra uma janela que <strong>termina hoje e inclui hoje</strong>, igual ao filtro de data relativa do Power BI. Ele substitui os filtros de Ano, Mês e Data: ao usá-lo, esses voltam para "Todos" e o intervalo calculado aparece em dourado nos campos de Data; mexer em Ano, Mês ou Data desliga o "Últimos". Na aba Comparativo, o ano anterior é a mesma janela 12 meses antes. "Hoje" é a data do computador de quem está vendo.
              <details class="dic-rule"><summary>Exemplos com hoje em 29/09</summary>
                <div class="dic-rule-body">
                  <p><b>Últimos 3 dias:</b> 27/09 a 29/09</p>
                  <p><b>Últimas 2 semanas:</b> 16/09 a 29/09 (14 dias)</p>
                  <p><b>Últimos 3 meses:</b> 30/06 a 29/09</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Filtros de Campanha, Form, Conj. Anúncios e Anúncio</dt>
            <dd>Cada filtro é aplicado à informação equivalente de cada fonte: leads, deals e investimento. Aceitam mais de um valor ao mesmo tempo. O investimento não tem formulário, então o filtro Form é comparado com o identificador do evento registrado no lançamento de investimento. A aba Eventos tem só os filtros de Ano e Mês.
              <details class="dic-rule"><summary>Colunas filtradas</summary>
                <div class="dic-rule-body">
                  <p><b>Campanha:</b> <code>utmCampaign</code> (leads e deals) e <code>nomeCampanha_custoMkt</code></p>
                  <p><b>Form:</b> <code>formPreenchido</code> (leads e deals) e <code>identificador_custoMkt</code></p>
                  <p><b>Conj. Anúncios:</b> <code>utmContent</code> (leads e deals) e <code>nomeConjunto_custoMkt</code></p>
                  <p><b>Anúncio:</b> <code>utmTerm</code> (leads e deals) e <code>nomeAnuncio_custoMkt</code></p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
        <p class="dic-note"><strong>Fora deste dashboard:</strong> diferente do Executive, aqui não existe custo de eventos físicos (<code>custoEvento</code>): o Marketing acompanha apenas investimento em anúncios pagos. O KPI "Assessorias Vendidas" (visto em versões anteriores do relatório) também não está incluído nesta versão.</p>
      </section>

      <section class="dic-section" id="funil">
        <h2 class="dic-section-title">Métricas do funil de vendas</h2>
        <p class="dic-note"><strong>Nota sobre as porcentagens:</strong> diferente de um funil estritamente sequencial, Oportunidades, Cadeiras Vendidas e Consultas Vendidas são três desdobramentos <strong>paralelos</strong> do mesmo grupo de Leads Qualificados (cadeira = ingresso de evento; consulta = agendamento digital: produtos diferentes, não etapas uma da outra). Por isso a % de cada uma é calculada sobre <strong>Qualificados</strong>, não sobre a etapa visualmente anterior. Só as duas últimas etapas são, de fato, sequenciais: Consultas Realizadas é calculada sobre Consultas Vendidas, e Combos sobre Consultas Realizadas.</p>
        <div class="dic-list">
          <article class="dic-term" data-stage="aquisicao">
            <h3 class="dic-term-name">Total de Leads</h3>
            <p class="dic-term-meta"><span>Topo do funil</span><span>Formulários</span></p>
            <p class="dic-term-desc">Total de leads no período, contados pela data do último formulário que cada um preencheu. Quem preenche um formulário de novo passa a contar no período desse formulário mais recente.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem de leads (<code>leadV2</code>) com a data do último formulário (<code>dataUltForm_leadV2</code>) dentro do período</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="aquisicao">
            <h3 class="dic-term-name">Qualificados</h3>
            <p class="dic-term-meta"><span>Topo do funil</span><span>Qualificação automática</span></p>
            <p class="dic-term-desc">Leads marcados como qualificados pela qualificação automática. O percentual, que aparece como "% Qualificação" em algumas abas, é calculado sobre o Total de Leads.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Leads com campo <code>qualificado = "Sim"</code> ÷ Total de Leads</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="aquisicao">
            <h3 class="dic-term-name">Oportunidades</h3>
            <p class="dic-term-meta"><span>Engajamento</span><span>Leads + conversas</span></p>
            <p class="dic-term-desc">Leads que responderam pelo menos uma mensagem no WhatsApp, qualificados ou não. Mostra quem engajou de verdade, e não só quem recebeu mensagem.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Leads cujo ID do Bitrix tem ao menos <b>1 mensagem recebida</b> (tipo "incoming") numa conversa vinculada (<code>conversaCW</code> + <code>msgCW</code>), sem exigir qualificação</p>
                <p>Percentual: Oportunidades ÷ Qualificados</p>
              </div>
            </details>
          </article>
          <article class="dic-term" data-stage="evento">
            <h3 class="dic-term-name">Cadeiras Vendidas</h3>
            <p class="dic-term-meta"><span>Canal Evento</span><span>Pipe de cadeiras (C9)</span></p>
            <p class="dic-term-desc">Ingressos vendidos para eventos presenciais OTR. Um dos 2 desdobramentos possíveis a partir de Qualificados (o outro é Consulta Vendida).</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Cards no pipe de Cadeiras (<code>C9</code>) com fase Won e data de venda preenchida ÷ Qualificados</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Consultas Vendidas</h3>
            <p class="dic-term-meta"><span>Digital + Evento</span><span>Pipe digital (C11) e pipe Evento</span></p>
            <p class="dic-term-desc">Agendamentos de consulta confirmados. Exibido junto com o valor total faturado com a venda dessas consultas.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Pipe <code>C11</code> ou pipe Evento · fase Won · data de venda preenchida ÷ Qualificados</p>
                <p><b>Faturamento:</b> soma de <code>valorVendaDolar</code> dos deals nessa condição (USD)</p>
              </div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Consultas Realizadas</h3>
            <p class="dic-term-meta"><span>Meio do funil</span><span>Pipe Comercial 2 (C5)</span></p>
            <p class="dic-term-desc">Consultas que de fato aconteceram. Junto com Combos, é uma das duas etapas cuja % é calculada sobre a etapa anterior (aqui, Consultas Vendidas), pois é sequencial de verdade: a reunião só acontece depois de agendada.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Cards no pipe Comercial 2 (<code>C5</code>) com data de consulta realizada preenchida ÷ Consultas Vendidas</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="fecho">
            <h3 class="dic-term-name">Combos Vendidos</h3>
            <p class="dic-term-meta"><span>Fundo do funil</span><span>Pipes C5, C1, C29 e C31</span></p>
            <p class="dic-term-desc">Vendas fechadas: o cliente assinou o contrato do pacote. Métrica final do funil, também sequencial sobre Consultas Realizadas.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Fase <code>C1:WON</code>, <code>C5:WON</code>, <code>C29:won</code> ou <code>C31:WON</code>, com data do contrato de combo assinado preenchida</p>
                <p>Contrato distratado sai da contagem: o card muda para uma fase de distrato (<code>C5:UC_CL1DBM</code> ou <code>C5:UC_AQ2QYT</code>), que não é WON</p>
                <p>Percentual: Combos ÷ Consultas Realizadas</p>
              </div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="financeiro">
        <h2 class="dic-section-title">Métricas financeiras</h2>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Faturamento (Combos)</h3>
            <p class="dic-term-meta"><span>Receita</span><span>Em dólar</span></p>
            <p class="dic-term-desc">Soma do valor de todos os combos vendidos no período. É a receita usada no ROI; o faturamento de consultas (exibido no KPI) não entra no ROI.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Soma de <code>valorComboLiquido_dealV2</code> dos combos vendidos (USD)</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Faturamento de Cadeiras</h3>
            <p class="dic-term-meta"><span>Receita</span><span>Em dólar</span></p>
            <p class="dic-term-desc">Soma do valor de venda, em dólar, dos ingressos de evento (cadeiras) vendidos no período, contados pela data da venda. Não tem cartão próprio na Visão Geral: entra no lucro acumulado do funil e aparece logo abaixo do número de Cadeiras Vendidas na aba Descartes. Não entra no ROI.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Soma de <code>valorVendaDolar_dealV2</code> dos cards com fase <code>C9:Won</code> e data de venda (<code>dataVenda_dealV2</code>) preenchida dentro do período (USD)</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Investimento (MKT)</h3>
            <p class="dic-term-meta"><span>Custo</span><span>Em dólar</span></p>
            <p class="dic-term-desc">Total gasto com anúncios pagos (Meta e Google), em dólar. O que foi pago em dólar entra como está; o que foi pago em reais é convertido pela cotação do dia. A forma de saber se um lançamento está em dólar muda de 2025 para 2026, e lançamentos de outros anos entram como zero. Se não houver cotação registrada no dia, o gasto em reais daquele dia fica fora da soma. Este dashboard não soma custo físico de eventos: é a base única de investimento.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Tabela <code>custoMkt</code>, convertida para USD:</p>
                <p><b>2025:</b> origem <code>meta</code> = já em dólar; origem <code>google</code> = valor ÷ câmbio do dia; outras origens entram como 0</p>
                <p><b>2026:</b> contas <code>Dollar 1</code> e <code>Dollar 2</code> = já em dólar; qualquer outra conta = valor ÷ câmbio do dia; lançamento sem conta (<code>conta_custoMkt</code> nulo) entra como 0</p>
                <p><b>Outros anos:</b> entram como 0</p>
                <p><b>Câmbio:</b> maior cotação do dia na tabela <code>cambio</code> (<code>MAX(valor_cambio)</code>); sem cotação no dia, ou cotação zero, a parcela convertida daquele lançamento fica fora da soma</p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Lucro acumulado (funil)</h3>
            <p class="dic-term-meta"><span>Resultado</span><span>Funil em cascata</span></p>
            <p class="dic-term-desc">No funil em cascata, o resultado acumulado até cada etapa. Começa negativo, com todo o investimento em marketing, e vai somando a receita conforme ela aparece: cadeiras vendidas, depois consultas vendidas, depois combos. O valor na linha de Combos Vendidos é o resultado final do período.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><b>Leads, Qualificados e Oportunidades:</b> − Investimento MKT</p>
                <p><b>Cadeiras Vendidas:</b> + faturamento de cadeiras</p>
                <p><b>Consultas Vendidas e Realizadas:</b> + faturamento de consultas</p>
                <p><b>Combos Vendidos:</b> + faturamento de combos</p>
                <p>Resultado final = Fat. cadeiras + Fat. consultas + Fat. combos − Investimento MKT</p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">ROI</h3>
            <p class="dic-term-meta"><span>Resultado</span><span>Return on Investment</span></p>
            <p class="dic-term-desc">Retorno sobre o investimento em marketing. ROI de 2,0 significa que a operação dobrou o dinheiro investido.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Faturamento (Combos) ÷ Investimento MKT</p>
                <p>ROI = 1,0 → empata · &gt; 1,0 → lucro</p>
              </div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="eficiencia">
        <h2 class="dic-section-title">Métricas de eficiência</h2>
        <p class="dic-note"><strong>Nota:</strong> CPL e CAC usam sempre o <strong>Investimento MKT</strong> (não há custo físico neste dashboard).</p>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">CPL</h3>
            <p class="dic-term-meta"><span>Eficiência de aquisição</span><span>Custo por lead qualificado</span></p>
            <p class="dic-term-desc">Quanto custou, em média, cada lead qualificado.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Investimento MKT ÷ Qualificados</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">CAC Cadeira</h3>
            <p class="dic-term-meta"><span>Canal Evento</span><span>Custo por cadeira vendida</span></p>
            <p class="dic-term-desc">Quanto custou, em média, vender cada ingresso de evento.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Investimento MKT ÷ Cadeiras Vendidas</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">CAC Consulta</h3>
            <p class="dic-term-meta"><span>Eficiência comercial</span><span>Custo por consulta vendida</span></p>
            <p class="dic-term-desc">Quanto custou, em média, agendar cada consulta.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Investimento MKT ÷ Consultas Vendidas</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">CAC Combo</h3>
            <p class="dic-term-meta"><span>Eficiência comercial</span><span>Custo por combo vendido</span></p>
            <p class="dic-term-desc">Quanto custou, em média, cada venda fechada.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Investimento MKT ÷ Combos Vendidos</p></div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="breakdown">
        <h2 class="dic-section-title">Funil, tabelas e filtro por clique (aba "Visão Geral")</h2>
        <p class="dic-section-intro">A tela principal reúne os cartões do topo, o Resumo Financeiro, o funil e quatro tabelas: Categorias (Form), Campanhas, Conj. de Anúncios e Anúncios. As métricas são as das seções acima; aqui está como ler cada bloco.</p>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Abaixo de cada cartão do topo</dt>
            <dd>Qualificados mostra o percentual sobre o Total de Leads; Oportunidades e Cadeiras Vendidas mostram o percentual sobre Qualificados; Consultas Vendidas e Combos Vendidos mostram o faturamento em dólar de cada um.</dd>
          </div>
          <div class="dic-def">
            <dt>Divisão do investimento por plataforma (Resumo Financeiro)</dt>
            <dd>Ao lado do Investimento Total aparece a fatia de cada plataforma (por exemplo, Meta e Google) no investimento do período, já convertido para dólar.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Investimento convertido de cada <code>origem_custoMkt</code> ÷ Investimento total; lançamento sem origem não entra na divisão</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Funil de Vendas: Cascateamento e Painel</dt>
            <dd>O desenho do funil tem formato fixo: a largura de cada faixa não é proporcional ao número. Em Painel ficam as quantidades de cada etapa. Em Cascateamento ficam o lucro acumulado até a etapa (vermelho quando negativo, verde quando positivo) e a conversão da etapa: % Qualificação, Oportunidades, Conv. Cadeiras, Conv. Consultas Vendidas, Conv. Consultas Realizadas e Conv. de Combo, com as bases descritas em "Métricas do funil de vendas". Passar o mouse numa etapa mostra quantidade, conversão e lucro acumulado.</dd>
          </div>
          <div class="dic-def">
            <dt>Como as linhas são casadas entre tabelas</dt>
            <dd>Campanha, Conjunto de Anúncios e Anúncio não têm um ID único compartilhado entre as tabelas de leads, deals e investimento: o casamento é feito pelo <strong>nome</strong> (UTM), assumindo que o mesmo nome é usado nas 3 tabelas. Uma campanha com investimento mas ainda sem leads (ou vice-versa) aparece como linha mesmo assim.
              <details class="dic-rule"><summary>Colunas casadas</summary>
                <div class="dic-rule-body">
                  <p>Tabelas <code>leadV2</code>, <code>dealV2</code> e <code>custoMkt</code></p>
                  <p><b>Campanha:</b> <code>utmCampaign</code> ↔ <code>nomeCampanha_custoMkt</code></p>
                  <p><b>Conjunto de Anúncios:</b> <code>utmContent</code> ↔ <code>nomeConjunto_custoMkt</code></p>
                  <p><b>Anúncio:</b> <code>utmTerm</code> ↔ <code>nomeAnuncio_custoMkt</code></p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Coluna "Cliques"</dt>
            <dd>Soma de cliques registrados por linha de investimento (Meta/Google Ads), somada com os mesmos filtros de período e dimensão das demais colunas da tabela.
              <details class="dic-rule"><summary>Campo</summary>
                <div class="dic-rule-body"><p><code>clicks_custoMkt</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Colunas das tabelas</dt>
            <dd>As quatro tabelas têm as mesmas 17 colunas: Invest., ROI, Cliques, Leads, CPL, Qualificados, % Qualif., Oportunidades, Cadeiras, Conv. Cadeiras, CAC Cadeira, Cons. Vendidas, Conv. Consultas, CAC Consulta, Combos, Conv. Combo e CAC Combos. São as mesmas fórmulas dos cartões e do Resumo Financeiro, calculadas para cada linha, com uma exceção: Conv. Combo. As tabelas não têm coluna de Consultas Realizadas.</dd>
          </div>
          <div class="dic-def">
            <dt>Conv. Combo nas tabelas</dt>
            <dd>Nas tabelas, a conversão de combo é calculada sobre as <strong>Consultas Vendidas</strong> da linha. No funil e nos cartões, ela é calculada sobre as <strong>Consultas Realizadas</strong>. Por isso o Conv. Combo da linha Total não bate com a conversão de combo do funil, e isso não é erro. O mesmo vale para a tabela da aba Eventos.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Tabelas:</b> Combos ÷ Consultas Vendidas</p>
                  <p><b>Funil e cartões:</b> Combos ÷ Consultas Realizadas</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Linhas "(sem campanha)", "(sem conjunto)" e "(sem anúncio)"</dt>
            <dd>Leads, deals e investimento que não têm essa informação preenchida ficam juntos nessa linha, para que a linha Total feche com os cartões do topo. Essa linha não pode ser clicada, porque o filtro não consegue selecionar "sem valor".</dd>
          </div>
          <div class="dic-def">
            <dt>Classificação de form (Evento × Digital)</dt>
            <dd>Nos leads e deals, o form preenchido é classificado como <strong>Evento</strong> quando o nome do form começa com OTR, INA, OTW ou WEB; senão (inclusive sem form) é <strong>Digital</strong>. O investimento não tem form: ele é Evento quando o identificador do evento registrado no lançamento começa com OTR, INA, OTW ou WEB, ou quando esse identificador está vazio e o nome da campanha começa com OTR; o resto é Digital. A linha "Total" é sempre a soma direta das duas.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Leads e deals:</b> <code>LEFT(UPPER(formPreenchido), 3) IN ('OTR','INA','OTW','WEB')</code> = Evento; o resto, inclusive vazio ou nulo, = Digital</p>
                  <p><b>Investimento:</b> <code>identificador_custoMkt</code> começando com OTR/INA/OTW/WEB, ou <code>identificador_custoMkt</code> vazio e <code>nomeCampanha_custoMkt</code> começando com OTR = Evento; o resto = Digital</p>
                  <p>Lançamento de investimento sem identificador e com nome de campanha nulo não cai em nenhuma das duas classes (entra só no total dos cartões)</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Cores do ROI nas tabelas</dt>
            <dd>O ROI de cada linha aparece em vermelho abaixo de 1,0x, em âmbar de 1,0x até abaixo de 8,0x, e em verde a partir de 8,0x. A mesma régua vale na tabela da aba Eventos.</dd>
          </div>
          <div class="dic-def">
            <dt>Ordenar, ver detalhes e abrir a tabela completa</dt>
            <dd>Clicar no título de uma coluna ordena a tabela por ela (a ordem inicial é por investimento, do maior para o menor). Passar o mouse numa linha mostra as 17 métricas dela. O botão de expandir no canto do cartão abre a tabela inteira numa janela, com o nome completo de cada coluna e o ROI com duas casas decimais (na tela, uma casa).</dd>
          </div>
          <div class="dic-def">
            <dt>Filtro por clique nas tabelas</dt>
            <dd>Clicar numa linha de qualquer das quatro tabelas filtra todo o resto da tela: cartões, funil, Resumo Financeiro e as outras tabelas. Em Categorias (Form), o clique filtra pela classe Evento ou Digital. Clicar de novo na mesma linha desliga o filtro; um clique simples troca a seleção, e Ctrl+clique (Cmd no Mac) acumula linhas. Os filtros ativos aparecem como etiquetas no topo, com a opção "limpar seleção". A última tabela clicada continua mostrando o ranking de antes da seleção (etiqueta âmbar "ranking sem a seleção"), mas a linha Total dela já é o valor filtrado; as outras tabelas recebem a etiqueta vermelha "filtrado". As linhas Total e "(sem ...)" e nomes que contêm vírgula não são clicáveis.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="comparativo">
        <h2 class="dic-section-title">Comparativo anual (aba "Comparativo")</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>O que é o "ano anterior"</dt>
            <dd>É <strong>exatamente o mesmo intervalo de datas, 12 meses antes</strong>, nunca o ano inteiro. Se você está vendo 01/jan a 27/ago de 2026, a comparação é contra 01/jan a 27/ago de 2025. Por isso, quando o período escolhido inclui datas futuras (por exemplo, ao selecionar só o ano corrente), a data final é automaticamente cortada em <strong>hoje</strong> nos dois lados: comparar 8 meses deste ano contra 12 meses do ano passado faria todo número parecer estar caindo. A barra logo abaixo dos filtros mostra as duas janelas exatas que estão sendo comparadas.</dd>
          </div>
          <div class="dic-def">
            <dt>Por que às vezes subir aparece em vermelho</dt>
            <dd>A cor da variação segue o que é <strong>bom para o negócio</strong>, não o sinal do número. Em métricas de volume e receita (Leads, Cadeiras, Combos, Faturamento, ROI), subir é verde e cair é vermelho. Já em <strong>CPL e nos três CACs</strong>, que são custos por resultado, a lógica se inverte: <strong>subir é vermelho</strong> (ficou mais caro conquistar cada lead ou venda) e cair é verde. O <strong>Investimento</strong> e os <strong>Cliques</strong> (coluna da tabela) aparecem sempre em cinza, sem cor de julgamento: gastar mais não é bom nem ruim isoladamente; o que importa é o retorno que ele gerou.</dd>
          </div>
          <div class="dic-def">
            <dt>O "Evento" desta aba não é o mesmo da tabela de Categorias</dt>
            <dd>São duas réguas diferentes, de propósito. Na tela principal, Evento × Digital é decidido pelo <strong>formulário preenchido</strong>. Já na aba Comparativo, é decidido pelo <strong>nome da campanha</strong> (Evento quando começa com OTR, INA, OTW ou WEB). Como consequência, os números de "Evento" das duas telas <strong>não vão bater</strong>, e isso não é erro. Nesta aba, tudo que não é campanha de evento cai em Digital, incluindo tráfego orgânico, indicações e registros sem campanha preenchida; assim Evento + Digital sempre fecha exatamente o Total.</dd>
          </div>
          <div class="dic-def">
            <dt>Gráfico de evolução mensal</dt>
            <dd>Mostra mês a mês a métrica escolhida no seletor: linha cheia é o ano atual, linha tracejada é o mesmo período do ano anterior. Serve para responder o que os cartões do topo não respondem: <strong>em qual mês a diferença aconteceu</strong>. Cada métrica é contada pela sua própria data de referência (Cadeiras e Consultas Vendidas pela data da venda; Combos e Faturamento pela data do contrato assinado), então um mesmo card pode aparecer em meses diferentes conforme a métrica.</dd>
          </div>
          <div class="dic-def">
            <dt>Faturamento nesta aba</dt>
            <dd>É apenas o <strong>faturamento de combos</strong>, a mesma base usada pelo ROI no resto do dashboard. O faturamento de consultas não entra nesta conta.
              <details class="dic-rule"><summary>Campo</summary>
                <div class="dic-rule-body"><p><code>valorComboLiquido_dealV2</code></p></div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="eventos">
        <h2 class="dic-section-title">Performance por formulário de evento (aba "Eventos")</h2>
        <p class="dic-section-intro">Mostra só o universo Evento, pela mesma régua da tabela Categorias (Form) da Visão Geral: leads e deals com form começando com OTR, INA, OTW ou WEB, e o investimento classificado como Evento. Os filtros desta aba são só Ano e Mês.</p>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Cartões do topo e Resumo Financeiro</dt>
            <dd>As mesmas sete contagens e os mesmos Investimento Total, ROI, CPL e CACs da Visão Geral, com as mesmas fórmulas, calculados só sobre o universo Evento. Aqui os cartões não mostram percentual nem faturamento abaixo do número, e o Resumo Financeiro não mostra a divisão por plataforma.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Leads e deals:</b> <code>formPreenchido</code> começando com OTR/INA/OTW/WEB</p>
                  <p><b>Investimento:</b> <code>identificador_custoMkt</code> começando com OTR/INA/OTW/WEB, ou identificador vazio e <code>nomeCampanha_custoMkt</code> começando com OTR</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Representatividade de Combos e Consultas</dt>
            <dd>Para cada formulário, a fatia que ele representa no total de combos vendidos (Share Combos) e no total de consultas vendidas (Share Consultas) da aba, ordenado pela fatia de combos. Cada métrica tem escala própria: a maior barra de cada cor ocupa toda a sua metade do trilho, então o comprimento compara formulários dentro da mesma métrica. O percentual aparece dentro da barra quando cabe, e sempre ao passar o mouse.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Share Combos:</b> combos do formulário ÷ combos da linha Total da tabela</p>
                  <p><b>Share Consultas:</b> consultas vendidas do formulário ÷ consultas vendidas da linha Total da tabela</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Investimento (gráfico)</dt>
            <dd>Investimento em marketing de cada formulário, do maior para o menor, com a mesma conversão para dólar do Investimento (MKT).</dd>
          </div>
          <div class="dic-def">
            <dt>Performance por Formulário (tabela)</dt>
            <dd>Uma linha por formulário de evento, com as mesmas 17 colunas das tabelas da Visão Geral (inclusive Conv. Combo sobre Consultas Vendidas e as cores do ROI) e uma linha Total. Clicar no título de uma coluna ordena a tabela. O investimento é ligado ao formulário pelo identificador do evento registrado no lançamento; quando o lançamento não tem identificador, pelo nome da campanha. A ligação é pelo texto: um identificador escrito diferente do nome do formulário vira uma linha separada.
              <details class="dic-rule"><summary>Agrupamento</summary>
                <div class="dic-rule-body">
                  <p><b>Leads:</b> <code>formPreenchido_leadV2</code></p>
                  <p><b>Deals:</b> <code>formPreenchido_dealV2</code></p>
                  <p><b>Investimento:</b> <code>identificador_custoMkt</code>; se vazio, <code>nomeCampanha_custoMkt</code></p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Desempenho por Formulário</dt>
            <dd>Ranking dos formulários pela métrica escolhida no seletor: Total de Leads, Qualificados, Consultas Vendidas, Combos Vendidos ou Cadeiras Vendidas. Trocar a métrica só redesenha o gráfico.</dd>
          </div>
          <div class="dic-def">
            <dt>Clique num formulário</dt>
            <dd>Clicar numa linha da tabela ou numa barra de qualquer gráfico destaca aquele formulário em toda a aba e esmaece os outros, sem escondê-los. Cada clique acrescenta ou retira um formulário da seleção. Os cartões do topo e o Resumo Financeiro passam a mostrar só os formulários selecionados; a tabela e os gráficos continuam com todos.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>O recálculo filtra leads e deals pelo nome do formulário (<code>formPreenchido</code>) e o investimento pelo identificador do lançamento (<code>identificador_custoMkt</code>)</p></div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="desempenho">
        <h2 class="dic-section-title">Perfil do lead e evolução no tempo (aba "Desempenho")</h2>
        <p class="dic-section-intro">Os mesmos cartões e o mesmo Resumo Financeiro da Visão Geral (sem a divisão por plataforma), mais o perfil dos leads e três gráficos de evolução. Todos os blocos respeitam os filtros de período, Campanha, Form, Conj. Anúncios e Anúncio.</p>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Top Motivos de Descarte</dt>
            <dd>Os 9 motivos de descarte mais frequentes entre os leads do período. Conta qualquer lead com motivo de descarte preenchido, em qualquer etapa e de qualquer tipo. Por isso não bate com os Descartes Comercial 1 da aba Descartes, que exigem a etapa Lead Descartado e tipo de lead diferente de OTR.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Leads (<code>leadV2</code>) com <code>motivoDescarte_leadV2</code> preenchido (vazio e nulo ficam fora), pela data do último formulário; 9 maiores quantidades</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Top Profissões</dt>
            <dd>As 9 profissões mais frequentes entre os leads do período. A profissão é texto livre no CRM, sem padronização de grafia: a mesma profissão escrita de jeitos diferentes aparece em linhas separadas. Profissão vazia ou "-" fica de fora.
              <details class="dic-rule"><summary>Campo</summary>
                <div class="dic-rule-body"><p><code>profissao_leadV2</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Tempo de Experiência</dt>
            <dd>Os 10 tempos de experiência mais frequentes entre os leads do período. Também é texto livre no CRM, com a mesma ressalva de grafia (por exemplo, "10 anos ou +" e "10 anos ou mais" ficam em linhas separadas). Valor vazio ou "-" fica de fora.
              <details class="dic-rule"><summary>Campo</summary>
                <div class="dic-rule-body"><p><code>tempoExp_leadV2</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>O percentual nos rankings</dt>
            <dd>Ao lado de cada item, o percentual é a fatia dele dentro dos itens listados no ranking, não sobre o total de leads. O subtítulo do cartão mostra quantos itens aparecem e a soma deles.</dd>
          </div>
          <div class="dic-def">
            <dt>Nível de Formação</dt>
            <dd>Distribuição completa, não um ranking, dos leads do período por nível de formação acadêmica. O número no centro é o total de leads, e o percentual de cada fatia é sobre esse total. Lead com a formação em branco aparece como "(não informado)".
              <details class="dic-rule"><summary>Campo</summary>
                <div class="dic-rule-body"><p><code>formacaoAcademica_leadV2</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Evolução por Métrica (seletor)</dt>
            <dd>Escolhe a métrica dos três gráficos abaixo: Total de Leads, Qualificados, % Qualificação, Descartados, Cadeiras, Consultas Vendidas, Combos, CPL ou ROI. Descartados são os leads com motivo de descarte preenchido, a mesma regra do Top Motivos de Descarte. % Qualificação, CPL e ROI são calculados com os números de cada ponto, não como média de percentuais. Cada métrica é contada pela sua própria data.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>% Qualificação:</b> Qualificados ÷ Total de Leads do ponto</p>
                  <p><b>CPL:</b> Investimento ÷ Qualificados do ponto</p>
                  <p><b>ROI:</b> Faturamento de combos ÷ Investimento do ponto</p>
                  <p><b>Datas:</b> leads, qualificados e descartados pela data do último formulário; cadeiras e consultas pela data da venda; combos e faturamento pela data do contrato assinado; investimento pela data do gasto</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Desempenho Mensal, Semanal e Diário</dt>
            <dd>A métrica escolhida mês a mês, semana a semana (S01, S02…: semana do ano no padrão ISO, que começa na segunda-feira) e dia a dia. Os três gráficos cobrem o período filtrado inteiro, inclusive o Diário. Mês, semana ou dia sem nenhum registro não vira ponto. No Mensal, o rótulo é só o nome do mês, sem o ano. No Diário, quando o período tem mais de um mês, o eixo marca só a virada de cada mês.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="descartes">
        <h2 class="dic-section-title">Motivos de descarte (aba "Descartes")</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Descartes Comercial 1</dt>
            <dd>Conta os leads na etapa <strong>Lead Descartado</strong> cujo tipo de lead é diferente de OTR. Maiúsculas e espaços não importam nessa comparação, e o lead sem tipo de lead preenchido no CRM fica de fora. O gráfico agrupa pelo motivo do descarte. O percentual é <strong>Descartes Com1 ÷ Leads Qualificados</strong>; o denominador não recebe filtro de motivo.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p>Linhas de <code>leadV2</code> com etapa <code>Lead Descartado</code> e <code>LOWER(TRIM(tipoLead_leadV2)) &lt;&gt; 'otr'</code></p>
                  <p>Tipo de lead nulo não entra (a comparação com nulo não é verdadeira); tipo em branco entra</p>
                  <p><b>Motivo:</b> <code>motivoDescarte_leadV2</code></p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Descartes Comercial 2</dt>
            <dd>Conta os deals com motivo de descarte do Comercial 2 preenchido. O percentual é <strong>Descartes Com2 ÷ Consultas Vendidas</strong>; as consultas vendidas são contadas pela data da venda.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p>Linhas de <code>dealV2</code> com <code>motivoDescarteComercial2_dealV2</code> preenchido</p>
                  <p><b>Consultas vendidas:</b> fases <code>Won</code> ou <code>C11:WON</code>, pela data da venda</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Descartes Comercial 3</dt>
            <dd>Conta os deals com motivo de descarte preenchido e fase no pipe C1. O percentual é <strong>Descartes Com3 ÷ Descartes Com2</strong>; o denominador não recebe filtro do motivo de Com3.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Linhas de <code>dealV2</code> com <code>motivoDescarte_dealV2</code> preenchido e fase iniciada por <code>C1:</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Datas, filtros e divisão por zero</dt>
            <dd>Os três descartes e seus motivos usam a data do último formulário. Somente as Consultas Vendidas usadas no denominador de % Com2 usam a data da venda, como no relacionamento ativado no Power BI. Campanha, formulário, conjunto e anúncio são aplicados às colunas equivalentes de cada fonte. Quando o denominador é zero, o resultado exibido é <strong>0%</strong>.
              <details class="dic-rule"><summary>Colunas de data</summary>
                <div class="dic-rule-body">
                  <p><b>Descartes e motivos:</b> <code>dataUltForm</code></p>
                  <p><b>Consultas Vendidas (% Com2):</b> <code>dataVenda_dealV2</code></p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Cartões do topo desta aba</dt>
            <dd>Total de Leads, Qualificados, % Qualificação (Qualificados ÷ Total de Leads), Cadeiras Vendidas, Consultas Vendidas, Consultas Realizadas e Combos Vendidos, com as mesmas regras da Visão Geral. Abaixo de Cadeiras Vendidas aparece o faturamento de cadeiras; abaixo de Consultas Vendidas, o faturamento de consultas; abaixo de Combos Vendidos, o faturamento de combos.</dd>
          </div>
          <div class="dic-def">
            <dt>Listas de motivos</dt>
            <dd>Cada cartão (Comercial 1, 2 e 3) lista todos os motivos do período, do mais frequente para o menos frequente, com o total de descartes no cabeçalho. No Comercial 1, o lead descartado sem motivo preenchido aparece como "(não informado)".</dd>
          </div>
          <div class="dic-def">
            <dt>Gráficos Mensal, Semanal e Diário</dt>
            <dd>Mostram a métrica escolhida no seletor (Qualificados, Descartes Com1, Com2 ou Com3, ou os percentuais de cada um) mês a mês, semana a semana (S01, S02…: semana do ano no padrão ISO) e dia a dia, cobrindo o período filtrado inteiro. Só viram ponto os dias com algum registro. O percentual de cada ponto é calculado com os números daquele ponto (por exemplo, Descartes Com2 da semana ÷ Consultas Vendidas da semana), não como média.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="classificacao">
        <h2 class="dic-section-title">Digital × Evento por formulário (aba "Classificação")</h2>
        <p class="dic-section-intro">Os números da Visão Geral numa linha de nove cartões e a evolução de Combos, Consultas Vendidas e Cadeiras separada em Digital e Evento. Todos os blocos respeitam os filtros de período, Campanha, Form, Conj. Anúncios e Anúncio.</p>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Cartões do topo</dt>
            <dd>Total de Leads, Qualificados, % Qualificação, Cadeiras Vendidas, Consultas Vendidas (com o faturamento de consultas abaixo), Consultas Realizadas, Combos Vendidos (com o faturamento de combos abaixo), % Conv. Combo e Investimento. São as mesmas fórmulas da Visão Geral, somando Digital e Evento. % Conv. Combo usa a base do funil: Combos ÷ Consultas Realizadas.</dd>
          </div>
          <div class="dic-def">
            <dt>Gráficos Digital × Evento</dt>
            <dd>Três gráficos (Combos Vendidos, Consultas Vendidas e Cadeiras Vendidas), cada um com uma linha para Digital e outra para Evento. A divisão é pelo formulário do deal: Evento quando começa com OTR, INA, OTW ou WEB; todo o resto, inclusive deal sem formulário, é Digital. É a mesma régua da tabela Categorias da Visão Geral, não a do Comparativo. Cadeiras e Consultas Vendidas contam pela data da venda; Combos, pela data do contrato assinado. As regras de cada métrica são as do funil de vendas.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Classe:</b> <code>LEFT(UPPER(formPreenchido_dealV2), 3) IN ('OTR','INA','OTW','WEB')</code> = Evento; o resto = Digital</p>
                  <p><b>Cadeiras:</b> fase <code>C9:Won</code>; <b>Consultas:</b> fases <code>Won</code> ou <code>C11:WON</code>; ambas por <code>dataVenda_dealV2</code></p>
                  <p><b>Combos:</b> fases <code>C1:WON</code>, <code>C5:WON</code>, <code>C29:won</code> ou <code>C31:WON</code>, por <code>dataContratoComboAssinado_dealV2</code></p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Ano-Mês, Semana do Ano e Dia do Mês</dt>
            <dd><strong>Ano-Mês:</strong> um ponto por mês (por exemplo, Jan/26). <strong>Semana do Ano:</strong> o número da semana no padrão ISO (Sem 1 a Sem 53); a mesma semana de anos diferentes é somada num ponto só. <strong>Dia do Mês:</strong> sempre 31 pontos, e cada ponto soma aquele dia em todos os meses do período (o dia 5 junta o dia 5 de cada mês), então não é uma linha do tempo. Trocar a granularidade recarrega só os gráficos, não os cartões.</dd>
          </div>
        </dl>
      </section>

    </div>
  </div>

</div>
<script>initDicionario();</script>
