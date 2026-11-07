<?php $dicTo = (($_GET['to'] ?? '') === '1') ? '?to=1' : ''; ?>
<div class="dash-chatwoot dic" data-sugestoes="TRM,ingresso,resolvidas">

  <header class="dic-head">
    <p class="dic-eyebrow">Chatwoot</p>
    <h1 class="dic-title">Dicionário de métricas</h1>
    <p class="dic-lead">O que cada número deste dashboard significa, em linguagem simples. Este painel acompanha o uso do Chatwoot pelo time comercial, e a data de referência de todos os filtros é a data de criação da conversa.</p>
    <nav class="dic-tabs" aria-label="Abas do dashboard">
      <a class="dic-tab" href="<?= $this->site ?>chatwoot<?= $dicTo ?>">Chatwoot</a>
      <a class="dic-tab" href="<?= $this->site ?>chatwoot/dicionario<?= $dicTo ?>" aria-current="page">Dicionário</a>
    </nav>
  </header>

  <div class="dic-layout">
    <div class="dic-body">

      <section class="dic-section" id="escopo">
        <h2 class="dic-section-title">Escopo do dashboard</h2>
        <p class="dic-note"><strong>Time comercial.</strong> Todos os números consideram apenas as conversas atribuídas ao time comercial (Helena, Eduardo Pires, Priscila Rangel e Fernanda matos). Conversas de outras equipes ficam de fora. A data de referência de todos os filtros é a <em>data de criação</em> da conversa.</p>
        <p class="dic-note"><strong>Conversas sem primeira resposta.</strong> Saem de <em>todos</em> os números apenas as conversas com a data de primeira resposta vazia ou igual à data-padrão que o Chatwoot grava quando ninguém respondeu (<code>1969-12-31 21:00:00</code>). Outras datas-lixo de primeira resposta que existem na tabela (anos como 0020 ou 9992) não são removidas aqui: essas conversas continuam contando em Total de Leads, vendas, status e Duração Média, e só ficam de fora da média do TRM.</p>
      </section>

      <section class="dic-section" id="fontes">
        <h2 class="dic-section-title">Fonte de dados</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Conversas do Chatwoot <code>conversaCW</code></dt>
            <dd>Cada linha é uma conversa do Chatwoot (atendimento por WhatsApp e outros canais), com responsável, status, rótulos, canal, o cliente do outro lado e as datas de criação, primeira resposta e última atividade. O dashboard lê essa tabela apenas para consulta, nunca altera nada.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="volume">
        <h2 class="dic-section-title">Volume e vendas</h2>
        <div class="dic-list">
          <article class="dic-term" data-stage="aquisicao">
            <h3 class="dic-term-name">Total de Leads</h3>
            <p class="dic-term-meta"><span>Volume</span><span>Conversas distintas</span></p>
            <p class="dic-term-desc">Quantas conversas diferentes o time recebeu no período. Cada conversa conta uma vez, mesmo com várias mensagens.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem distinta de conversas</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="evento">
            <h3 class="dic-term-name">Ingressos Vendidos</h3>
            <p class="dic-term-meta"><span>Venda</span><span>Rótulo "venda-ingresso"</span></p>
            <p class="dic-term-desc">Conversas marcadas com o rótulo de venda de ingresso. É o time etiquetando no Chatwoot que aquela conversa virou uma venda de ingresso.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Conversas cujo rótulo contém <code>venda-ingresso</code></p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Consultas Vendidas</h3>
            <p class="dic-term-meta"><span>Venda</span><span>Rótulo "venda-consulta"</span></p>
            <p class="dic-term-desc">Conversas marcadas com o rótulo de venda de consulta.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Conversas cujo rótulo contém <code>venda-consulta</code></p></div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="tempo">
        <h2 class="dic-section-title">Tempo de atendimento</h2>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">TRM — Tempo de 1ª Resposta</h3>
            <p class="dic-term-meta"><span>Tempo</span><span>Média em minutos</span></p>
            <p class="dic-term-desc">Em média, quantos minutos o time levou para dar a primeira resposta depois que a conversa foi aberta. Quanto menor, melhor.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Média de (1ª resposta − criação) em minutos</p>
                <p>Só entram conversas com 1ª resposta igual ou posterior à criação e diferença de até 525.600 minutos (1 ano)</p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Duração Média</h3>
            <p class="dic-term-meta"><span>Tempo</span><span>Média em horas</span></p>
            <p class="dic-term-desc">Em média, quantas horas se passaram entre a criação da conversa e a última atividade nela. Dá uma noção de quanto tempo a conversa fica ativa.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Média de (última atividade − criação) em horas inteiras por conversa</p>
                <p>Só entram conversas com última atividade igual ou posterior à criação</p>
              </div>
            </details>
          </article>
        </div>
        <p class="dic-note"><strong>Sobre datas inválidas.</strong> Na média do TRM só entram conversas cuja primeira resposta veio depois da criação, dentro de uma janela realista (até 1 ano). Isso descarta as datas-lixo de primeira resposta que o escopo do painel não remove (anos como 0020 ou 9992).</p>
      </section>

      <section class="dic-section" id="status">
        <h2 class="dic-section-title">Status das conversas</h2>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Conversas em Aberto</h3>
            <p class="dic-term-meta"><span>Status</span><span>Não finalizadas</span></p>
            <p class="dic-term-desc">Conversas que ainda estão abertas (não foram finalizadas). Quanto menor, melhor: indica menos pendências.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Conversas com status <code>open</code></p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Conversas Resolvidas</h3>
            <p class="dic-term-meta"><span>Status</span><span>Finalizadas</span></p>
            <p class="dic-term-desc">Conversas que o time marcou como resolvidas/finalizadas.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Conversas com status <code>resolved</code></p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">% Resolvidas / % Em aberto</h3>
            <p class="dic-term-meta"><span>Proporção</span><span>Linhas dos gráficos</span></p>
            <p class="dic-term-desc">A fatia das conversas de cada responsável (ou período) que está resolvida ou em aberto. As duas fatias não somam 100%, porque o total também inclui as conversas Pendentes e Adiadas, que não têm KPI próprio.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><b>% Resolvidas:</b> resolvidas ÷ total</p>
                <p><b>% Em aberto:</b> em aberto ÷ total</p>
                <p><b>Total:</b> todas as conversas, inclusive as de status <code>pending</code> e <code>snoozed</code></p>
              </div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="comparativo">
        <h2 class="dic-section-title">Comparativo, gráficos e tabelas</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Comparativo MoM (mês vs. mês anterior)</dt>
            <dd>Abaixo de cada KPI aparece "mês ant.:" com o valor de <strong>um único mês</strong> de comparação e a variação percentual. Esse mês é sempre o anterior ao <strong>menor</strong> mês selecionado: com Mês = Mar, compara com Fev; com Jan, compara com Dez do ano anterior. Com vários meses marcados, o resultado dos meses escolhidos juntos é comparado com esse mês só (por exemplo, Mar a Mai contra Fev). Com Mês = Todos, o período inteiro selecionado (o ano todo, ou todo o histórico se Ano também estiver em Todos) é comparado com o mês anterior ao mês atual do calendário. Os filtros de Responsável e Status valem também para o mês de comparação. A seta fica <strong>verde</strong> quando o resultado melhorou e <strong>vermelha</strong> quando piorou, lembrando que, para TRM, Duração e Conversas em aberto, <em>menor é melhor</em>. Sem valor no mês de comparação, aparece só o "mês ant." sem seta.
              <details class="dic-rule"><summary>Como o mês de comparação é escolhido</summary>
                <div class="dic-rule-body">
                  <p><b>Mês:</b> o anterior ao menor mês selecionado; com Mês = Todos, o anterior ao mês atual do calendário</p>
                  <p><b>Ano:</b> o ano selecionado (o mais antigo, se houver mais de um); com Ano = Todos, o ano atual. Janeiro volta para dezembro do ano anterior</p>
                  <p><b>Variação:</b> (atual − mês anterior) ÷ mês anterior; o valor do mês anterior aparece arredondado para número inteiro</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Evolução das Conversas por responsável</dt>
            <dd>Apesar do título, não é uma linha do tempo: cada grupo de barras é um responsável, com os números do período filtrado, do responsável com mais conversas para o com menos. As barras mostram Conversas, Ingressos e Consultas (escala da esquerda, em contagem) e as linhas mostram % Resolvidas e % Em aberto (escala da direita, de 0 a 100%).</dd>
          </div>
          <div class="dic-def">
            <dt>Evolução pelo período</dt>
            <dd>O gráfico de evolução pode ser visto por ano, mês, dia da semana ou dia do mês, usando o botão de alternância. As barras mostram volumes (conversas, ingressos, consultas) e as linhas mostram os percentuais.
              <details class="dic-rule"><summary>Cada opção do botão</summary>
                <div class="dic-rule-body">
                  <p><b>Ano:</b> um ponto por ano de criação, com todo o histórico; ignora os filtros de Ano e Mês (Responsável e Status continuam valendo)</p>
                  <p><b>Mês:</b> um ponto por mês/ano dentro do período filtrado</p>
                  <p><b>Dia da Semana:</b> de domingo a sábado, somando todas as semanas do período filtrado</p>
                  <p><b>Dia:</b> dia do mês (1 a 31), somando todos os meses do período filtrado</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Tabela Analítica por responsável</dt>
            <dd>Uma linha por responsável, do que tem mais conversas para o que tem menos. Conversas, TRM, Duração média, Ingressos e Consultas seguem as mesmas regras dos KPIs. <strong>Criado em (recente)</strong> é a data e hora da conversa mais nova criada no período para aquele responsável; <strong>Última atividade</strong> é a última atividade mais recente entre essas conversas.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Criado em (recente):</b> maior data de criação das conversas do responsável no período</p>
                  <p><b>Última atividade:</b> maior data de última atividade das mesmas conversas</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Analítico por responsável</dt>
            <dd>Na tabela, clique em um responsável para expandir e ver, conversa a conversa, o cliente (nome ou, sem nome, o telefone), status, canal, datas de criação, 1ª resposta e última atividade, tempo de resposta e duração. Os rótulos não aparecem por extenso: as colunas Ingressos e Consultas mostram <strong>1</strong> quando a conversa tem o rótulo de venda correspondente e <strong>0</strong> quando não tem. O TRM da linha fica em branco quando a 1ª resposta não passa na regra do TRM. A lista mostra no máximo as <strong>500 conversas mais recentes</strong> do período (pela data de criação), com um aviso quando esse limite é atingido.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Ingressos:</b> 1 se o rótulo contém <code>venda-ingresso</code>, senão 0</p>
                  <p><b>Consultas:</b> 1 se o rótulo contém <code>venda-consulta</code>, senão 0</p>
                  <p><b>Status:</b> código do Chatwoot (<code>open</code>, <code>resolved</code>, <code>pending</code>, <code>snoozed</code>)</p>
                  <p>Ordenado da criação mais recente para a mais antiga, limite de 500 linhas</p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="filtros">
        <h2 class="dic-section-title">Filtros</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Ano e Mês</dt>
            <dd>Definem o período pela data de criação da conversa, com múltipla seleção. Ao abrir, o painel vem com o <strong>ano e o mês atuais</strong> marcados. "Limpar" volta tudo para Todos, ou seja, todo o histórico.</dd>
          </div>
          <div class="dic-def">
            <dt>Responsável</dt>
            <dd>Restringe a um ou mais nomes do time comercial. Vale para os KPIs, o comparativo, os dois gráficos, a tabela e o analítico.</dd>
          </div>
          <div class="dic-def">
            <dt>Status</dt>
            <dd>Restringe pelo status da conversa: Em aberto, Resolvidas, Pendentes ou Adiadas, com múltipla seleção. Pendentes e Adiadas existem só aqui no filtro, sem KPI próprio, mas entram no Total de Leads e no total usado nos percentuais.
              <details class="dic-rule"><summary>Códigos no Chatwoot</summary>
                <div class="dic-rule-body"><p>Em aberto = <code>open</code>, Resolvidas = <code>resolved</code>, Pendentes = <code>pending</code>, Adiadas = <code>snoozed</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Atualização dos números</dt>
            <dd>O resultado de cada combinação de filtros fica guardado por <strong>10 minutos</strong> para a tela abrir rápido.</dd>
          </div>
        </dl>
      </section>

    </div>
  </div>

</div>
<script>initDicionario();</script>
