<?php $dicTo = (($_GET['to'] ?? '') === '1') ? '?to=1' : ''; ?>
<div class="dash-comercial dic" data-sugestoes="combo,distrato,cadeira">

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
    <p class="dic-eyebrow">Comercial</p>
    <h1 class="dic-title">Dicionário de métricas</h1>
    <p class="dic-lead">Explicação de cada número exibido no painel de funil comercial, em linguagem simples. Entenda de onde vêm os dados, o que separa o canal Evento do Digital e como cada métrica é calculada.</p>
    <nav class="dic-tabs" aria-label="Abas do dashboard">
      <a class="dic-tab" href="<?= $this->site ?>comercial<?= $dicTo ?>">Visão Geral</a>
      <a class="dic-tab" href="<?= $this->site ?>comercial/yoy<?= $dicTo ?>">Comparativo</a>
      <a class="dic-tab" href="<?= $this->site ?>comercial/cadeiras<?= $dicTo ?>">Cadeiras</a>
      <a class="dic-tab" href="<?= $this->site ?>comercial/comercial1<?= $dicTo ?>">Com. 1</a>
      <a class="dic-tab" href="<?= $this->site ?>comercial/comercial2<?= $dicTo ?>">Com. 2</a>
      <a class="dic-tab" href="<?= $this->site ?>comercial/comercial3<?= $dicTo ?>">Com. 3</a>
      <a class="dic-tab" href="<?= $this->site ?>comercial/dicionario<?= $dicTo ?>" aria-current="page">Dicionário</a>
    </nav>
  </header>

  <div class="dic-layout">
    <div class="dic-body">

      <section class="dic-section" id="fontes">
        <h2 class="dic-section-title">Fontes de dados</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Leads <code>leadV2</code></dt>
            <dd>Cada vez que alguém preenche um formulário — num anúncio digital ou num evento presencial — nasce um card de <strong>lead</strong> no CRM Bitrix. É a primeira etapa do funil, e a base dos números de "Total de Leads" e "Qualificados".</dd>
          </div>
          <div class="dic-def">
            <dt>Oportunidades <code>dealV2</code></dt>
            <dd>Quando o lead avança no processo comercial, é criado um card de <strong>deal</strong>, que percorre diferentes pipes (filas de venda) até ser ganho ou perdido. Todo o resto do painel — cadeiras, consultas, combos, valores e distratos — sai daqui.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="canais">
        <h2 class="dic-section-title">O que separa Evento de Digital</h2>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Canal Evento</h3>
            <p class="dic-term-meta"><span>Presencial</span><span>Tipo de lead OTR / OTW</span></p>
            <p class="dic-term-desc">Pessoas que entraram pela frente de eventos presenciais — as turnês OTR e OTW. É o canal onde existe venda de cadeira (ingresso) antes da consulta.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Cards cujo <code>tipoLead</code> é <code>OTR</code> ou <code>OTW</code></p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Canal Digital</h3>
            <p class="dic-term-meta"><span>Online</span><span>Todos os demais tipos de lead</span></p>
            <p class="dic-term-desc">Tudo o que não é evento presencial: Consulta, Venda direta, B2B, Indicação e os cards em que o tipo de lead ficou em branco. Aqui a consulta é vendida direto, sem passar por ingresso.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Cards cujo <code>tipoLead</code> <strong>não</strong> é OTR nem OTW (inclui os vazios)</p></div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Por que este painel usa "tipo de lead"</dt>
            <dd>Outros dashboards (Executive, Marketing) separam Evento de Digital pelo <strong>prefixo da campanha ou do formulário</strong> (OTR-, INA-, WEB-…). Aqui a separação é pelo campo <strong>tipo de lead</strong> preenchido no CRM. São critérios diferentes, então pequenas diferenças de número entre os painéis são esperadas — não são erro.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="funil">
        <h2 class="dic-section-title">Métricas do funil</h2>
        <div class="dic-list">
          <article class="dic-term" data-stage="aquisicao">
            <h3 class="dic-term-name">Total de Leads</h3>
            <p class="dic-term-meta"><span>Topo do funil</span><span>Leads</span></p>
            <p class="dic-term-desc">Quantidade de pessoas que preencheram formulário no período. Contadas pela data do último formulário preenchido.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem de cards de lead (<code>leadV2</code>) com data do último formulário dentro do período</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="aquisicao">
            <h3 class="dic-term-name">Leads Qualificados</h3>
            <p class="dic-term-meta"><span>Topo do funil</span></p>
            <p class="dic-term-desc">Leads marcados como qualificados pela qualificação automática. É o ponto de partida dos dois funis (Evento e Digital).</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Leads do período com <code>qualificado = "Sim"</code></p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="evento">
            <h3 class="dic-term-name">Cadeiras Vendidas (Ingressos)</h3>
            <p class="dic-term-meta"><span>Canal Evento</span><span>Pipe de cadeiras (C9)</span></p>
            <p class="dic-term-desc">Ingressos vendidos para os eventos presenciais. Aparece dentro do bloco Evento, mas é contado <strong>sem recorte por tipo de lead</strong> — igual ao relatório original do Power BI, e igual ao número do totalizador.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Cards com fase <code>C9:WON</code> e data de venda preenchida, dentro do período</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Consultas Vendidas</h3>
            <p class="dic-term-meta"><span>Evento + Digital</span><span>Pipe principal e pipe digital (C11)</span></p>
            <p class="dic-term-desc">Consultas com agendamento confirmado e pago. Contadas pela data da venda, e separadas entre Evento e Digital pelo tipo de lead.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Cards com fase <code>WON</code> ou <code>C11:WON</code> e data de venda preenchida</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Consultas Realizadas</h3>
            <p class="dic-term-meta"><span>Meio do funil</span><span>Pipe Comercial 2 (C5) · data de realização</span></p>
            <p class="dic-term-desc">Consultas que de fato aconteceram. Note que a data usada aqui é a da realização, não a da venda — por isso uma consulta vendida em um mês pode aparecer como realizada no mês seguinte.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Cards no pipe Comercial 2 (<code>C5</code>) com data de consulta realizada preenchida</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="fecho">
            <h3 class="dic-term-name">Combos Vendidos</h3>
            <p class="dic-term-meta"><span>Fechamento</span><span>"Combo" no CRM</span></p>
            <p class="dic-term-desc">Contratos de visto (combo) assinados. É o fechamento do funil. Contados pela data de assinatura do contrato. O totalizador inclui os combos da retenção; já os blocos <strong>Evento</strong> e <strong>Digital</strong> (faixas de indicadores e cabeçalho dos dois funis) deixam a retenção de fora — por isso Evento + Digital = total − Retenção.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><b>Totalizador:</b> cards com fase <code>C1:WON</code>, <code>C5:WON</code>, <code>C29:won</code> ou <code>C31:WON</code> e data do contrato de combo assinado preenchida</p>
                <p><b>Blocos Evento e Digital:</b> só as fases <code>C5:WON</code>, <code>C29:won</code> ou <code>C31:WON</code> — sem <code>C1:WON</code>, que é retenção — separadas pelo tipo de lead</p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Valor Vendido</h3>
            <p class="dic-term-meta"><span>Financeiro</span><span>Em dólar</span></p>
            <p class="dic-term-desc">Soma do valor líquido dos combos vendidos, em dólares. Segue exatamente a mesma regra de contagem dos Combos Vendidos — inclusive nos blocos Evento e Digital, que deixam de fora o valor da retenção: Evento + Digital = total − Retenção.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Soma de <code>valorComboLiquido</code> dos cards contados em Combos Vendidos (no totalizador, com <code>C1:WON</code>; nos blocos Evento e Digital, sem)</p></div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Os percentuais dos dois funis</dt>
            <dd>Nos cartões "Funil Evento" e "Funil Digital", o percentual à direita de cada linha compara aquela etapa com a <strong>etapa imediatamente acima</strong> dentro do mesmo cartão (a primeira linha não tem percentual, por não ter etapa anterior). O tamanho da barra é proporcional à primeira etapa do funil, que vale 100%.</dd>
          </div>
          <div class="dic-def">
            <dt>O gráfico de evolução</dt>
            <dd>Abaixo dos funis, três linhas acompanham o período: <strong>Ingressos</strong> (cadeiras vendidas), <strong>Consultas vendidas</strong> e <strong>Combos</strong>. São os números do totalizador, sem separar Evento de Digital — os Combos incluem a retenção. Período em que as três linhas ficam zeradas é omitido do eixo. Passar o mouse mostra os três valores daquele ponto.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p>Ingressos e consultas vendidas pela data da venda; combos pela data do contrato de combo assinado, com as mesmas fases do totalizador (<code>C1:WON</code>, <code>C5:WON</code>, <code>C29:won</code>, <code>C31:WON</code>).</p>
                  <p>O título muda para Evolução mensal, semanal ou diária conforme o botão Mês, Semana ou Dia (ver "Filtros e datas").</p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="retencao">
        <h2 class="dic-section-title">Retenção e distrato</h2>
        <div class="dic-list">
          <article class="dic-term" data-stage="fecho">
            <h3 class="dic-term-name">Retenção</h3>
            <p class="dic-term-meta"><span>Recorte do total</span><span>Pipe de retenção (C1)</span></p>
            <p class="dic-term-desc">Combos vendidos pelo time de retenção — clientes recuperados. É um <strong>recorte</strong> dos Combos Vendidos, e não uma venda à parte: esses contratos já estão somados no totalizador de combos e de valor. Eles não entram nos blocos Evento e Digital.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Cards com fase <code>C1:WON</code> e data do contrato de combo assinado preenchida</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Distratos</h3>
            <p class="dic-term-meta"><span>Perda</span><span>Fases de distrato (C5)</span></p>
            <p class="dic-term-desc">Contratos que foram assinados e depois cancelados. Aparecem como número próprio, para acompanhamento, e não entram em Combos Vendidos nem no Valor Vendido: o card distratado sai da fase de venda ganha.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Cards nas fases de distrato (<code>C5:UC_CL1DBM</code> e <code>C5:UC_AQ2QYT</code>), com data de FP realizado preenchida e data do contrato de combo assinado preenchida.</p>
                <p>O período filtra pela data do contrato de combo.</p>
              </div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="yoy">
        <h2 class="dic-section-title">Comparativo anual (a aba Comparativo)</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Como o "ano anterior" é escolhido</dt>
            <dd>A aba compara o período filtrado contra <strong>exatamente o mesmo intervalo do ano passado</strong>, e não contra o ano fechado. O fim do período nunca passa de hoje.
              <details class="dic-rule"><summary>Exemplo</summary>
                <div class="dic-rule-body">
                  <p>Com o filtro em <b>Ano = 2026</b>, no dia 09/09/2026, a comparação é <b>01/01/2026 a 09/09/2026</b> contra <b>01/01/2025 a 09/09/2025</b> — não contra 2025 inteiro.</p>
                  <p>Sem esse corte, 9 meses de 2026 apareceriam contra 12 meses de 2025 e toda métrica de volume pareceria despencando.</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>O % ao lado de cada número</dt>
            <dd>Quanto a métrica cresceu ou caiu em relação ao mesmo período do ano passado. O valor do ano anterior aparece ao lado, marcado com <strong>LY</strong>.
              <details class="dic-rule"><summary>Como é calculado</summary>
                <div class="dic-rule-body">
                  <p>(ano atual − ano anterior) ÷ ano anterior</p>
                  <p>Quando o ano anterior é <b>zero</b> e o ano atual tem valor, não existe percentual e o selo mostra <b>novo</b>. Com os dois anos zerados, o selo mostra <b>0,0%</b>.</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Distrato é o único com a cor invertida</dt>
            <dd>Em todas as métricas subir é verde e cair é vermelho. Em <strong>Distratos</strong> vale o contrário: menos distrato é resultado melhor, então a queda aparece em verde.
              <details class="dic-rule"><summary>Diferença do Power BI</summary>
                <div class="dic-rule-body">
                  <p>A <b>seta continua seguindo o número</b> (▼ quando caiu); só a cor segue a intenção da métrica.</p>
                  <p>O relatório do Power BI inverte a seta também — aqui não, porque uma seta para cima num número que caiu afirma algo falso sobre o dado.</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Esta aba não mostra leads</dt>
            <dd>O comparativo traz Ingressos, Consultas Vendidas, Consultas Realizadas, Combos, Distratos e Valor de Combos. <strong>Total de Leads e Qualificados existem só na Visão Geral.</strong>
              <details class="dic-rule"><summary>Como é calculado</summary>
                <div class="dic-rule-body"><p>Cada métrica é calculada exatamente como está descrito nas seções acima — a aba só muda o período comparado, nunca a fórmula.</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Ano e Mês funcionam como intervalo aqui</dt>
            <dd>Nesta aba os meses marcados viram <strong>um intervalo contínuo</strong>, do primeiro ao último. Na Visão Geral eles filtram exatamente os meses marcados.
              <details class="dic-rule"><summary>Exemplo</summary>
                <div class="dic-rule-body">
                  <p>Marcar <b>Jan</b> e <b>Dez</b> aqui resulta em <b>Jan a Dez</b>; na Visão Geral resulta em <b>Jan e Dez</b> só.</p>
                  <p>O intervalo realmente usado está sempre escrito na barra no alto da tela — é a primeira coisa a conferir quando os números das duas abas não baterem.</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Os dois gráficos</dt>
            <dd>A barra cheia é o mês do ano atual; a linha tracejada com marcadores é o <strong>mesmo mês do ano anterior</strong>. Passar o mouse mostra os dois valores e a variação daquele mês.
              <details class="dic-rule"><summary>Detalhes</summary>
                <div class="dic-rule-body">
                  <p>Meses sem venda <b>continuam no eixo</b> (na Visão Geral eles são omitidos) — é isso que mantém os dois anos alinhados mês a mês.</p>
                  <p>O valor exato do ano anterior está no tooltip e em <b>Ver os mesmos dados em tabela</b>, abaixo de cada gráfico.</p>
                  <p>Os gráficos são <b>Ingressos</b> e <b>Combos Totais</b>. Combos Totais inclui a retenção, então soma o mesmo que o totalizador de Combos — e não Evento + Digital.</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>O último mês pela metade</dt>
            <dd>Quando o período termina antes do fim do mês — o que acontece quase sempre, porque o fim é limitado a hoje —, a barra do último mês aparece <strong>esmaecida</strong>, o nome do mês ganha um <strong>asterisco</strong> e uma nota abaixo do gráfico diz até que dia cada ano foi contado. A comparação do mês continua justa, porque os dois anos são cortados no mesmo dia; o que a barra curta não mostra é o resto do mês.
              <details class="dic-rule"><summary>Detalhes</summary>
                <div class="dic-rule-body"><p>O asterisco no nome do mês só aparece quando os rótulos do eixo cabem na horizontal; com muitos meses (rótulos inclinados), ficam a barra esmaecida e a nota.</p></div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="cadeiras">
        <h2 class="dic-section-title">Time de Cadeiras (a aba Cadeiras)</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Quem está nesta aba</dt>
            <dd>A aba acompanha só o time que trabalha o ingresso: <strong>Patrícia Lopes, Débora Fontes e Marina Viana</strong>. O filtro de vendedor serve para olhar um de cada vez — nunca para trazer alguém de fora.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p>A lista é fixa no código. Outros responsáveis também vendem cadeira no CRM (<b>Helena Castro</b>, <b>Sistema Admin</b>, <b>Venda Direta</b>), e ficam <strong>de fora</strong> desta aba de propósito — por isso o total daqui é menor que o de Cadeiras Vendidas da Visão Geral.</p>
                  <p>Entrar ou sair alguém do time exige mudança no código.</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Aqui "evento" é só OTR</dt>
            <dd>Nesta aba, lead de evento é <strong>só tipo de lead OTR</strong>. Nas abas Visão Geral e Comparativo, o canal Evento é <strong>OTR + OTW</strong>.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p>É a regra do relatório de origem, mantida de propósito.</p>
                  <p>Consequência prática: o número de leads desta aba <b>não bate</b> com o de leads de Evento das outras duas, e não é erro.</p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
        <div class="dic-list">
          <article class="dic-term" data-stage="aquisicao">
            <h3 class="dic-term-name">Leads Qualificados</h3>
            <p class="dic-term-meta"><span>Entrada</span><span>Base da conversão</span></p>
            <p class="dic-term-desc">Leads de evento que o time recebeu e que passaram pela qualificação — é o que cada vendedor tinha em mãos para trabalhar no período.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Leads com <code>tipoLead = OTR</code> e <code>qualificado = Sim</code>, contados pela data do último formulário e atribuídos ao responsável do lead.</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="evento">
            <h3 class="dic-term-name">Cadeiras Vendidas e Conversão</h3>
            <p class="dic-term-meta"><span>Resultado</span><span>Resultado do time</span></p>
            <p class="dic-term-desc">Cadeira vendida é o ingresso fechado. A conversão diz quanto do que entrou virou venda.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><b>Cadeira:</b> cards em <code>C9:WON</code> com data de venda preenchida, pelo responsável do card.</p>
                <p><b>Conversão</b> = cadeiras ÷ leads qualificados. Cores: verde a partir de 12%, âmbar a partir de 7%, vermelho abaixo disso.</p>
                <p>O lead e a cadeira têm datas próprias: um lead de março que fecha em abril conta como lead em março e cadeira em abril. Em períodos curtos isso mexe na conversão.</p>
              </div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>A posição no ranking</dt>
            <dd>A medalha é sempre por <strong>cadeiras vendidas</strong>. Clicar num cabeçalho para ordenar a tabela por outra coluna <strong>não muda</strong> quem é o primeiro.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p>Empate é desfeito por conversão e depois por nome.</p>
                  <p>Quem não vendeu nenhuma cadeira no período <b>não recebe medalha</b> — aparece com a posição em cinza.</p>
                  <p>Vendedor sem nenhuma atividade continua na tabela, zerado: sumir da lista esconderia a informação de que ele não produziu.</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>O gráfico Performance de Vendedores</dt>
            <dd>Para cada vendedor, duas barras lado a lado — leads qualificados e cadeiras vendidas — no eixo da esquerda, e a linha de conversão no eixo da direita, em %. Os vendedores aparecem na ordem do pódio, mesmo que a tabela esteja ordenada por outra coluna.
              <details class="dic-rule"><summary>Eixos</summary>
                <div class="dic-rule-body"><p>Os dois eixos dividem as mesmas linhas de grade, mas têm escalas próprias: sem o eixo da direita, a conversão (poucas dezenas de %) ficaria colada no chão de um eixo que vai a milhares de leads.</p></div>
              </details>
            </dd>
          </div>
        </dl>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Motivos de Descarte</h3>
            <p class="dic-term-meta"><span>Perda</span><span>Top 10</span></p>
            <p class="dic-term-desc">Por que os leads do time se perderam. Mostra os 10 motivos mais frequentes; o resto não aparece.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Conta <strong>todos</strong> os leads OTR do time com motivo de descarte preenchido — <b>qualificados ou não</b>. Por isso o número aqui não é um subconjunto dos Leads Qualificados do ranking.</p>
                <p>Leads sem motivo preenchido ficam de fora.</p>
              </div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>O gráfico ao longo do tempo</dt>
            <dd>A linha mostra as cadeiras do time período a período — mês, semana ou dia, conforme o botão escolhido. Período sem nenhuma venda é <strong>omitido</strong> do eixo — na aba Comparativo é o contrário, lá o mês vazio fica para os dois anos continuarem alinhados.
              <details class="dic-rule"><summary>Período em andamento</summary>
                <div class="dic-rule-body"><p>O último ponto aparece com <b>círculo vazado e asterisco</b> quando o período corrente (o mês, a semana ou o dia de hoje) ainda não fechou, e uma nota abaixo do gráfico repete o aviso: ele conta só até hoje, então a queda no fim da linha é do calendário, não do time.</p></div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="comercial1">
        <h2 class="dic-section-title">Time Comercial 1 (a aba Com. 1)</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Dois times, um dashboard</dt>
            <dd>O Comercial 1 vende consulta por dois canais diferentes, cada um com seu próprio indicador de entrada: <strong>Fernanda Matos</strong> e <strong>Priscila Rangel</strong> trabalham o canal Evento (cadeiras → consulta); <strong>Eduardo Pires</strong> e <strong>Larissa Campos</strong> trabalham o canal Digital (leads → consulta).
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>As duas listas são fixas no código. Um vendedor novo, ou uma troca de canal, exige mudança no código — não é um filtro da tela.</p></div>
              </details>
            </dd>
          </div>
        </dl>
        <div class="dic-list">
          <article class="dic-term" data-stage="evento">
            <h3 class="dic-term-name">Total de Cadeiras</h3>
            <p class="dic-term-meta"><span>Divergência entre abas</span><span>Não é "Cadeiras Vendidas" da Visão Geral</span></p>
            <p class="dic-term-desc">Métrica só do canal Evento aqui: conta cards em fases "soltas" do funil que já têm uma data de evento atribuída — não é a mesma coisa que a cadeira vendida da Visão Geral e do Time Cadeiras.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Cards com <code>faseId</code> sem ":" e data de evento preenchida, contados pela data de evento — não pela data de venda.</p>
                <p>Fase "solta" é a que não tem dois-pontos, como <code>WON</code>, <code>LOSE</code>, <code>NEW</code>; a cadeira vendida da Visão Geral e do Time Cadeiras é <code>C9:WON</code>.</p>
              </div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Consulta Vendida</h3>
            <p class="dic-term-meta"><span>Compartilhada</span><span>A métrica que os dois canais compartilham</span></p>
            <p class="dic-term-desc">A mesma consulta vendida das outras abas do Comercial (evento + digital juntos), aqui aparece dos dois lados porque os dois canais vendem a mesma coisa no fim: uma consulta.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Cards com fase <code>WON</code> ou <code>C11:WON</code> e data de venda preenchida, contados pela data de venda.</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="aquisicao">
            <h3 class="dic-term-name">Leads Qualificados Digital</h3>
            <p class="dic-term-meta"><span>Entrada do Digital</span><span>Só do canal Digital</span></p>
            <p class="dic-term-desc">Todo lead qualificado que <strong>não</strong> é do tipo OTR — é o que sobra depois de tirar o tipo de lead OTR, então os leads OTW também entram aqui. Alimenta só o ranking e os gráficos do canal Digital.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Leads com <code>tipoLead ≠ OTR</code> (inclui OTW e os vazios) e <code>qualificado = Sim</code>, contados pela data do último formulário e atribuídos ao responsável do lead.</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Conversão</h3>
            <p class="dic-term-meta"><span>Fórmula</span><span>Última coluna das duas tabelas</span></p>
            <p class="dic-term-desc">Quanto da entrada de cada canal virou consulta vendida. No Evento, compara as consultas vendidas com o Total de Cadeiras; no Digital, com os Leads Qualificados Digital. A linha TOTAL recalcula a conversão a partir das somas do time, e não pela média das conversões individuais.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><b>Evento:</b> Consulta Vendida ÷ Total de Cadeiras.</p>
                <p><b>Digital:</b> Consulta Vendida ÷ Leads Qualificados Digital.</p>
                <p><b>TOTAL:</b> soma das consultas vendidas ÷ soma da entrada (cadeiras ou leads) dos vendedores da tabela.</p>
                <p>Sem entrada no período, a conversão fica em 0%. Acima de 999% a célula mostra <b>999%+</b>; o valor exato aparece ao passar o mouse.</p>
              </div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Aqui "digital" inclui OTW</dt>
            <dd>Nos Leads Qualificados Digital desta aba, só o tipo de lead <strong>OTR</strong> fica de fora: lead <strong>OTW</strong> conta como Digital. Nas abas Visão Geral, Comparativo e Com. 2, OTW faz parte do canal <strong>Evento</strong>. A diferença é intencional.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p>Esta aba: Digital = <code>tipoLead ≠ OTR</code> — a mesma régua da aba Cadeiras, onde evento é só <code>tipoLead = OTR</code>. Visão Geral, Comparativo e Com. 2: Evento = <code>tipoLead IN (OTR, OTW)</code>.</p>
                  <p>Consequência prática: os leads digitais desta aba não batem com os do canal Digital daquelas três abas.</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>As duas tabelas não têm medalha nem ordenação</dt>
            <dd>Cada canal tem sua tabela, sempre na mesma ordem: do maior para o menor na métrica de entrada (Total de Cadeiras no Evento, Leads Qualificados Digital no Digital). Clicar no cabeçalho não reordena, e não há medalha de 1º/2º/3º lugar como na aba Cadeiras. Vendedor sem atividade no período continua na tabela, zerado.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Empate na métrica de entrada é desfeito pela conversão e depois pelo nome. A última linha, TOTAL, soma os vendedores da tabela.</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Os gráficos por vendedor</dt>
            <dd>Em cada canal, o gráfico de barras mostra lado a lado a entrada (Cadeiras ou Leads Qualificados) e as Consultas Vendidas de cada vendedor, com a linha de conversão por cima. As barras usam o eixo da esquerda e a conversão usa o eixo da direita, em %.</dd>
          </div>
          <div class="dic-def">
            <dt>Sem motivos de descarte nesta aba</dt>
            <dd>Esta aba mostra só ranking (2 tabelas) e os gráficos de performance e de evolução no tempo, sem o bloco de motivos de descarte que a aba Time Cadeiras tem.
              <details class="dic-rule"><summary>Eixos dos gráficos</summary>
                <div class="dic-rule-body"><p>Nos gráficos "ao longo do tempo", a métrica principal de cada canal (Cadeiras / Leads Qualif.) fica no eixo esquerdo e a Consulta Vendida no eixo direito — as escalas são bem diferentes (leads chegam a milhares, consultas ficam em centenas) e um eixo só esmagaria a linha de consulta perto do zero.</p></div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="comercial2">
        <h2 class="dic-section-title">Time Comercial 2 (a aba Com. 2)</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Um time, uma tabela</dt>
            <dd>Diferente do Comercial 1 (dois times paralelos), o Comercial 2 é <strong>um único time</strong> de cinco vendedores — Paulo Nogueira, Rafael Torres, Camila Duarte, Beatriz Lemos e Juliana Prado — todos vendendo o mesmo combo pós-consulta, só cortados por canal.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>A lista é fixa no código. Um nome injetado na URL só restringe o recorte, nunca amplia — nunca aparece um vendedor de fora do time.</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Evento × Digital aqui é outra regra</dt>
            <dd>Evento e Digital não são dois times como no Comercial 1 — são um corte do <strong>mesmo</strong> vendedor pelo tipo de lead que originou o card. A regra usada é a mesma da Visão Geral e do Comparativo Anual, mas diverge da aba Time Cadeiras.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Evento = <code>tipoLead IN (OTR, OTW)</code>; o resto (inclusive sem tipoLead) é Digital. A aba Time Cadeiras usa uma regra mais estreita, só <code>tipoLead = OTR</code> — por isso os dois totais não batem se comparados lado a lado.</p></div>
              </details>
            </dd>
          </div>
        </dl>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Consulta Realizada e Combo Vendido</h3>
            <p class="dic-term-meta"><span>Fórmula</span><span>As duas métricas da tabela</span></p>
            <p class="dic-term-desc">As mesmas fórmulas da Visão Geral, aqui recortadas para os cinco vendedores do time. Consulta Realizada mede quem compareceu; Combo Vendido mede quem fechou depois — a conversão da tabela é a razão entre as duas.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><b>Consulta Realizada:</b> fase começando em <code>C5</code> com data de consulta realizada preenchida.</p>
                <p><b>Combo Vendido:</b> fase <code>C5:WON</code>, <code>C29:won</code> ou <code>C31:WON</code> com data de contrato de combo assinado preenchida — sem <code>C1:WON</code>, que é retenção e não entra em nenhum canal.</p>
              </div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>A posição muda com a ordenação</dt>
            <dd>Na aba Time Cadeiras, a medalha do 1º/2º/3º lugar é sempre pela mesma métrica (cadeiras vendidas), mesmo que a tabela esteja ordenada por outra coluna; no Comercial 1 não há medalha nem ordenação por clique. Aqui a posição segue a ordenação: com nove colunas em três grupos, não existe uma métrica única óbvia para fixar — a posição reflete sempre a coluna clicada.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Clicar em qualquer uma das nove colunas numéricas reordena a tabela e recalcula as posições 1/2/3. O padrão inicial é a Conversão Total, do maior para o menor.</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Mesma cor, opacidade diferente</dt>
            <dd>Nos dois gráficos "Performance por Vendedor" (Evento e Digital), Consultas Realizadas e Combos usam as mesmas cores nos dois cartões — o card Digital só aparece mais claro. É proposital: os cinco vendedores aparecem nos dois cortes, e a cor igual deixa claro que é a mesma pessoa vista por dois ângulos, não duas categorias diferentes.
              <details class="dic-rule"><summary>Eixos dos gráficos</summary>
                <div class="dic-rule-body"><p>Pelo mesmo motivo, os gráficos "ao longo do tempo" usam um único eixo para Evento e Digital — as duas séries têm ordem de grandeza parecida, diferente do Comercial 1 (onde Leads Qualificados e Consultas Vendidas precisam de eixos separados).</p></div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="comercial3">
        <h2 class="dic-section-title">Time Comercial 3 (a aba Com. 3) — Retenção</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>O time de Retenção</dt>
            <dd>Diferente dos outros três times (que vendem consulta ou combo pela primeira vez), o Comercial 3 <strong>recupera cancelamentos</strong> — clientes que já tinham desistido e voltam a fechar. Hoje o time é Thiago Dos Santos Ramos e Aline Barros.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>A lista é fixa no código, mesmo padrão das outras abas de time: um nome injetado na URL só restringe o recorte, nunca amplia. Outros responsáveis com atividade pontual no pipe C1 (ex.: cobertura de férias) não aparecem aqui.</p></div>
              </details>
            </dd>
          </div>
        </dl>
        <div class="dic-list">
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Dois rankings, um só vendedor</h3>
            <p class="dic-term-meta"><span>Fórmula</span><span>Agendada × Realizada, ambos culminando em Combo</span></p>
            <p class="dic-term-desc">Este time não separa Evento de Digital (não usa tipo de lead) — o corte aqui é por etapa do agendamento: quem agendou a consulta de retenção e quem de fato compareceu. As duas tabelas mostram os mesmos dois vendedores, só a métrica de entrada muda.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><b>Consulta Agendada:</b> cards no pipe Retenção (<code>C1</code>) com data de consulta agendada preenchida, contados por essa data exatamente como está no CRM — sem a correção de mês que o Executive e os painéis de vendedor aplicam a ela.</p>
                <p><b>Consulta Realizada:</b> mesmo pipe, com data de consulta realizada preenchida, contada pela data de realização.</p>
              </div>
            </details>
          </article>
          <article class="dic-term" data-stage="fecho">
            <h3 class="dic-term-name">Combo Em Retenção</h3>
            <p class="dic-term-meta"><span>Fórmula</span><span>A métrica de fechamento</span></p>
            <p class="dic-term-desc">É o mesmo "Visto" que a Visão Geral já soma dentro do totalizador de Combos — aqui reaparece sozinho, recortado só para os 2 vendedores do time. A conversão de cada ranking é essa métrica dividida pela consulta (agendada ou realizada) da própria tabela.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Fase exatamente <code>C1:WON</code> (não um prefixo — só a fase de ganho) com data de contrato de combo assinado preenchida.</p></div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>A ordenação das duas tabelas</dt>
            <dd>Cada tabela ordena por conta própria: clicar em qualquer cabeçalho (vendedor, consultas, combos ou conversão) reordena só aquela tabela, e clicar de novo inverte a ordem. As posições 1 e 2 seguem a ordenação atual, como no Comercial 2 — não há pódio fixo. Ao abrir, cada tabela vem ordenada pela sua coluna de consultas (agendadas ou realizadas), do maior para o menor.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>A última linha, TOTAL, soma os vendedores da tabela e recalcula a conversão a partir das somas (combos ÷ consultas), não pela média das conversões.</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>O gráfico Performance de Vendedores</dt>
            <dd>Para cada vendedor, três barras lado a lado — consultas agendadas, consultas realizadas e combos — no eixo da esquerda, e a linha de conversão da consulta agendada para o combo no eixo da direita, em %. Passar o mouse sobre um vendedor mostra os quatro números.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>A linha usa a conversão do ranking por Consulta Agendada (combos ÷ agendadas). A conversão pela consulta realizada aparece só na segunda tabela.</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>O gráfico Evolução ao Longo do Tempo</dt>
            <dd>Duas linhas no mesmo eixo: consultas agendadas e combos em retenção, período a período. Consultas realizadas <strong>não</strong> entram neste gráfico. Período em que as duas linhas ficam zeradas é omitido do eixo.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Agendadas pela data de consulta agendada (sem correção de mês); combos pela data do contrato de combo assinado — cada linha na sua própria data, como nos rankings. Segue o botão Mês, Semana ou Dia e marca o período em andamento (ver "Filtros e datas").</p></div>
              </details>
            </dd>
          </div>
        </dl>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Motivos de Descarte</h3>
            <p class="dic-term-meta"><span>Fórmula</span><span>Por que a retenção não emplacou</span></p>
            <p class="dic-term-desc">Top 10 motivos pelos quais um card do pipe de Retenção foi descartado, do mais para o menos frequente. Diferente da aba Time Cadeiras (onde o descarte vem dos leads), aqui o motivo já mora na própria oportunidade — não existe um lead à parte nesse pipe.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Cards no pipe Retenção (<code>C1</code>) com motivo de descarte preenchido, contados por motivo. A maioria dos descartes acontece antes de qualquer consulta ser agendada, então o período do filtro é aplicado sobre a <b>data de criação do card</b> — não sobre a data de consulta agendada dos rankings acima.</p></div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="filtros">
        <h2 class="dic-section-title">Filtros e datas</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Os seis filtros da Visão Geral e do Comparativo</dt>
            <dd><strong>Data</strong> (intervalo livre), <strong>Ano</strong> e <strong>Mês</strong> definem o período; <strong>Tipo Lead</strong>, <strong>Form</strong> e <strong>Responsável</strong> recortam o conjunto. Se você preencher o intervalo de datas, ele tem prioridade sobre Ano/Mês. Todos valem para o painel inteiro, incluindo o gráfico. Em todas as abas, a tela abre com o ano corrente marcado, e Limpar volta para ele.
              <details class="dic-rule"><summary>Exemplo</summary>
                <div class="dic-rule-body"><p>Filtrar por <b>Tipo Lead = Consulta</b>, por exemplo, zera o bloco Evento — é o comportamento esperado, não um erro.</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Os filtros das abas de time</dt>
            <dd>As abas Cadeiras, Com. 1, Com. 2 e Com. 3 têm cinco filtros: <strong>Data</strong>, <strong>Ano</strong> e <strong>Mês</strong> (mesmas regras da Visão Geral), <strong>Vendedor</strong> e <strong>Form</strong>. O Vendedor só lista as pessoas do time da aba; sem ninguém marcado, vale o time inteiro. Não existe filtro de Tipo Lead: cada aba usa a sua própria regra de canal, descrita na seção dela.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p>Um nome de fora do time, mesmo colocado à mão no endereço da página, é ignorado; se nenhum nome válido sobrar, vale o time inteiro.</p>
                  <p>No Com. 1, marcar só vendedores de um canal deixa a tabela e os gráficos do outro canal vazios.</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Mês, Semana ou Dia nos gráficos</dt>
            <dd>Os gráficos de evolução da Visão Geral e das abas Cadeiras, Com. 1, Com. 2 e Com. 3 têm os botões <strong>Mês</strong>, <strong>Semana</strong> e <strong>Dia</strong>, que mudam só o agrupamento do gráfico — totais, tabelas e rankings continuam iguais. O padrão é Mês, e Limpar os filtros volta para ele. A aba Comparativo não tem esses botões: lá o gráfico é sempre mês a mês.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Mês:</b> rótulo como <code>Jan/26</code>. <b>Semana:</b> semana de segunda a domingo (padrão ISO), rótulo como <code>S07/26</code>. <b>Dia:</b> data completa, rótulo como <code>05/03</code>.</p>
                  <p>Cada métrica é agrupada pela sua própria data, a mesma usada no período.</p>
                  <p>Nas abas Com. 1 e Com. 2, os botões dos dois gráficos de evolução andam juntos: trocar num muda o outro.</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>O último ponto em andamento</dt>
            <dd>Nos gráficos de evolução das abas Cadeiras, Com. 1, Com. 2 e Com. 3, quando o último ponto é o período corrente e ele ainda não terminou — o mês, a semana ou o dia de hoje, conforme o botão escolhido —, o ponto aparece com <strong>círculo vazado</strong>, o número ganha <strong>asterisco</strong> e uma nota abaixo do gráfico avisa que ele conta só até hoje. A queda no fim da linha é do calendário, não do time.
              <details class="dic-rule"><summary>Detalhes</summary>
                <div class="dic-rule-body">
                  <p>No Com. 1 a marca fica na linha principal de cada canal (Cadeiras ou Leads Qualificados); no Com. 2 e no Com. 3, nas duas linhas.</p>
                  <p>O gráfico da Visão Geral não tem essa marca: o último ponto pode estar incompleto sem aviso.</p>
                  <p>No Comparativo o aviso é outro (barra esmaecida), descrito na seção do Comparativo.</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Cada métrica tem sua própria data</dt>
            <dd>O período selecionado não é aplicado sobre uma data única. Cada métrica usa a data que faz sentido para ela: leads pela <strong>data do último formulário</strong>; cadeiras e consultas vendidas pela <strong>data da venda</strong>; consultas realizadas pela <strong>data de realização</strong>; combos, valores, retenção e distratos pela <strong>data do contrato de combo assinado</strong>. Por isso as etapas do funil não são um recorte de um mesmo grupo de cards. As abas de time acrescentam outras datas, descritas na seção de cada uma: data do evento no Total de Cadeiras do Com. 1, data de agendamento e data de criação do card no Com. 3.</dd>
          </div>
          <div class="dic-def">
            <dt>Atualização dos números</dt>
            <dd>O resultado de cada combinação de filtros fica guardado por <strong>10 minutos</strong> para a tela abrir rápido. Uma venda registrada agora no CRM pode levar até esse tempo para aparecer aqui.</dd>
          </div>
        </dl>
      </section>

    </div>
  </div>

</div>
<script>initDicionario();</script>
