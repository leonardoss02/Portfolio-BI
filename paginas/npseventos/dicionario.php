<?php $dicTo = (($_GET['to'] ?? '') === '1') ? '?to=1' : ''; ?>
<div class="dash-npseventos dic" data-sugestoes="NPS,indicação,motivo">

  <header class="dic-head">
    <p class="dic-eyebrow">NPS Eventos</p>
    <h1 class="dic-title">Dicionário de métricas</h1>
    <p class="dic-lead">Explicação de cada número exibido no dashboard de NPS Eventos, em linguagem simples. O período do filtro incide sobre a data em que a pessoa respondeu a pesquisa (não sobre a data do evento em si).</p>
    <nav class="dic-tabs" aria-label="Abas do dashboard">
      <a class="dic-tab" href="<?= $this->site ?>npseventos<?= $dicTo ?>">Dashboard</a>
      <a class="dic-tab" href="<?= $this->site ?>npseventos/dicionario<?= $dicTo ?>" aria-current="page">Dicionário</a>
    </nav>
  </header>

  <div class="dic-layout">
    <div class="dic-body">

      <section class="dic-section" id="fontes">
        <h2 class="dic-section-title">Fontes de dados</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Compradores de Consulta <code>tabela npsEvento</code></dt>
            <dd>Respostas da pesquisa pós-evento enviadas por quem comprou consulta no evento On The Road. Pergunta de indicação: "O quanto você indicaria a Acme-EUA para amigos ou familiares?".</dd>
          </div>
          <div class="dic-def">
            <dt>Não Compradores <code>tabela npsEvento</code></dt>
            <dd>Respostas de quem participou do evento mas não fechou a consulta. Pergunta de indicação diferente da anterior: "O quanto você indicaria o evento On The Road para um amigo ou familiar?". Também traz o motivo de não ter fechado e o interesse em imigrar após o evento.</dd>
          </div>
        </dl>
        <p class="dic-note">As notas e médias das duas planilhas nunca são misturadas: elas fazem perguntas diferentes de indicação, então cada bloco do dashboard (Fecharam / Não Fecharam) é sempre calculado e mostrado separadamente. A única soma é a da contagem: o KPI Total de Respostas junta as respostas das duas planilhas, e os percentuais de Fecharam Consulta e Não Fecharam são calculados sobre essa soma.</p>
        <p class="dic-note"><strong>Como as médias são calculadas.</strong> Toda média do painel (círculo de NPS, barras de avaliação, indicação e chance de imigrar) considera só as respostas com nota numérica. Resposta em branco ou com texto no lugar da nota não entra na conta. As médias são arredondadas para 1 casa decimal.</p>
      </section>

      <section class="dic-section" id="kpis">
        <h2 class="dic-section-title">Indicadores do topo</h2>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Total de Respostas</h3>
            <p class="dic-term-meta"><span>Volume</span><span>Soma das duas planilhas</span></p>
            <p class="dic-term-desc">Quantas respostas da pesquisa existem no período e nas cidades filtrados, somando quem fechou e quem não fechou consulta. É o mesmo número do selo "N RESPOSTAS" no topo da tela.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Linhas da planilha de compradores + linhas da planilha de não compradores, depois dos filtros (linha totalmente em branco é ignorada)</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Fecharam Consulta</h3>
            <p class="dic-term-meta"><span>Volume</span><span>Quantidade e %</span></p>
            <p class="dic-term-desc">Quantas respostas vieram da planilha de quem comprou consulta e, entre parênteses, quanto isso representa do Total de Respostas.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Respostas de compradores ÷ (compradores + não compradores), exibido como % inteiro</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Não Fecharam</h3>
            <p class="dic-term-meta"><span>Volume</span><span>Quantidade e %</span></p>
            <p class="dic-term-desc">Quantas respostas vieram da planilha de quem não fechou consulta e, entre parênteses, quanto isso representa do Total de Respostas.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Respostas de não compradores ÷ (compradores + não compradores), exibido como % inteiro</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Chance Média Imigrar</h3>
            <p class="dic-term-meta"><span>Pós-evento</span><span>Só quem não fechou</span></p>
            <p class="dic-term-desc">Nota média de 0 a 10 para a chance de iniciar um processo de imigração nos próximos 12 meses. Essa pergunta só existe na planilha de quem não fechou consulta. É o mesmo número da barra "Chance de imigrar nos próximos 12 meses" do bloco Pós-evento.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Média de "Qual a chance de iniciar um processo de imigração nos próximos 12 meses?" (planilha de não compradores)</p></div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="nps">
        <h2 class="dic-section-title">Círculo de NPS</h2>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">NPS (círculo grande)</h3>
            <p class="dic-term-meta"><span>NPS</span><span>Experiência Geral</span></p>
            <p class="dic-term-desc">O número em destaque no círculo é a média da nota que os participantes deram para a experiência geral no evento (nota de 0 a 10). Não é a conta clássica de "% promotores − % detratores".</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Média de "Como você avalia sua experiência geral no evento?", separada por quem fechou e quem não fechou consulta</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Score de indicação</h3>
            <p class="dic-term-meta"><span>Indicação</span><span>Texto abaixo do círculo</span></p>
            <p class="dic-term-desc">Média (0 a 10) da pergunta de indicação de cada planilha: "indicaria a Acme-EUA" para quem fechou, "indicaria o evento OTR" para quem não fechou. É uma métrica de apoio ao círculo, não substitui a Experiência Geral.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Média de "O quanto você indicaria..." de cada planilha</p></div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Cor do círculo</dt>
            <dd>A cor do círculo muda de acordo com a média de Experiência Geral, para dar um alerta visual rápido. Sem nenhuma nota no período, o círculo fica neutro e mostra "—".
              <details class="dic-rule"><summary>Faixas de 0 a 10</summary>
                <div class="dic-rule-body">
                  <p><b>Índigo (excelente):</b> nota ≥ 9,0</p>
                  <p><b>Verde (bom):</b> nota ≥ 7,5</p>
                  <p><b>Âmbar (atenção):</b> nota ≥ 5,0</p>
                  <p><b>Vermelho (crítico):</b> nota &lt; 5,0</p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="avaliacoes">
        <h2 class="dic-section-title">Avaliações do evento</h2>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Barras de avaliação (0 a 10)</h3>
            <p class="dic-term-meta"><span>Avaliação</span><span>6 aspectos do evento</span></p>
            <p class="dic-term-desc">Média das notas dadas para cada aspecto específico do evento (Local, Coffee Break, Palestrante, Recepção, Brindes e Abordagem Comercial), sempre separadas entre quem fechou consulta e quem não fechou. Dá para comparar visualmente onde a experiência de cada grupo diverge.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Média de cada pergunta "Como você avalia..." da planilha correspondente</p>
                <p>Comprimento da barra = média ÷ 10; sem nota no período, o valor aparece como "—"</p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Atendimento Comercial</h3>
            <p class="dic-term-meta"><span>Avaliação</span><span>Só quem fechou consulta</span></p>
            <p class="dic-term-desc">Avaliação específica do atendimento comercial recebido. Só existe na planilha de quem comprou consulta, porque quem não fechou não passou por essa etapa.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Média de "Como você avalia o atendimento comercial?" (planilha de compradores)</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Indicaria Acme-EUA / Indicaria o Evento OTR</h3>
            <p class="dic-term-meta"><span>Indicação</span><span>Última barra de cada cartão</span></p>
            <p class="dic-term-desc">A última barra de cada cartão de avaliações, separada das demais por uma linha tracejada, mostra a nota média de indicação daquele grupo: "Indicaria Acme-EUA" no cartão de quem fechou e "Indicaria o Evento OTR" no de quem não fechou. É o mesmo número do Score de indicação mostrado abaixo do círculo de NPS.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Média da pergunta de indicação de cada planilha (mesmo cálculo do Score de indicação)</p></div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="rankings">
        <h2 class="dic-section-title">Motivos e fatores (rankings)</h2>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Por que Fechou?</h3>
            <p class="dic-term-meta"><span>Ranking</span><span>Top 5</span></p>
            <p class="dic-term-desc">Os 5 motivos mais citados por quem fechou consulta, para explicar por que decidiu seguir em frente. Quanto maior a barra, mais pessoas responderam aquele motivo. O item no topo aparece em verde.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem de respostas de "Qual foi o principal fator para fechar a consulta?", agrupadas por texto igual</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Por que Não Fechou?</h3>
            <p class="dic-term-meta"><span>Ranking</span><span>Top 5</span></p>
            <p class="dic-term-desc">Os 5 motivos mais citados por quem não fechou consulta. O item no topo aparece em vermelho: é o motivo que a gestão comercial deveria atacar primeiro.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem de respostas de "Qual foi o principal motivo para não fechar a consulta?", agrupadas por texto igual</p></div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="pos-evento">
        <h2 class="dic-section-title">Pós-evento (só quem não fechou)</h2>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Interesse em morar nos EUA</h3>
            <p class="dic-term-meta"><span>Pós-evento</span><span>Top 3 respostas</span></p>
            <p class="dic-term-desc">Depois do evento, como o interesse da pessoa em morar nos EUA mudou (aumentou muito, aumentou, permaneceu igual, diminuiu). Mostra as 3 respostas mais frequentes com a contagem de pessoas.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem de respostas de "Após o evento, seu interesse em morar nos EUA"</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Chance de imigrar nos próximos 12 meses</h3>
            <p class="dic-term-meta"><span>Pós-evento</span><span>Média de 0 a 10</span></p>
            <p class="dic-term-desc">Nota média de 0 a 10 para "qual a chance de você iniciar um processo de imigração nos próximos 12 meses". A barra vai do vermelho (chance baixa) ao verde (chance alta), com um marcador na posição da média.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Média de "Qual a chance de iniciar um processo de imigração nos próximos 12 meses?"</p></div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="filtros">
        <h2 class="dic-section-title">Filtros e atualização</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Data (intervalo)</dt>
            <dd>Filtra pela data em que a pessoa respondeu a pesquisa. Quando o intervalo está preenchido (só o início, só o fim ou os dois), ele <strong>tem prioridade</strong>: Ano e Mês passam a ser ignorados.</dd>
          </div>
          <div class="dic-def">
            <dt>Ano e Mês</dt>
            <dd>Também pela data da resposta, com múltipla seleção. A lista de anos mostra só os anos que têm alguma resposta nas planilhas. Ao abrir, nada vem marcado: o painel mostra todas as respostas.</dd>
          </div>
          <div class="dic-def">
            <dt>Cidade</dt>
            <dd>Cidade em que a pessoa participou do On The Road, com busca e múltipla seleção, juntando as cidades das duas planilhas. A comparação ignora acentos, maiúsculas e minúsculas e espaços repetidos, então grafias diferentes da mesma cidade contam juntas; na lista aparece a primeira grafia encontrada. Cidade vale junto com qualquer filtro de período.</dd>
          </div>
          <div class="dic-def">
            <dt>Respostas sem data válida</dt>
            <dd>Respostas com a data e hora vazia ou fora do formato do Google Forms aparecem quando nenhum filtro de período está em uso, mas saem assim que Data, Ano ou Mês são preenchidos.</dd>
          </div>
          <div class="dic-def">
            <dt>Selos no topo da tela</dt>
            <dd>"N RESPOSTAS" repete o Total de Respostas. O outro selo mostra os filtros de Cidade, Ano e Mês aplicados, ou "TODOS OS PERÍODOS" quando nenhum deles está marcado.</dd>
          </div>
          <div class="dic-def">
            <dt>Atualização dos números</dt>
            <dd>O resultado de cada combinação de filtros fica guardado por <strong>10 minutos</strong>. Por isso uma resposta nova pode levar até 10 minutos para aparecer no painel.</dd>
          </div>
          <div class="dic-def">
            <dt>Planilha indisponível</dt>
            <dd>Se o Google não entregar uma das planilhas (link sem acesso, serviço fora do ar), o painel usa a última leitura guardada dela, mesmo que tenha mais de 10 minutos, sem mostrar aviso. Só quando não existe nenhuma leitura guardada aparece o aviso "Uma das planilhas de origem está temporariamente indisponível", e os números daquela planilha ficam vazios. Esse resultado incompleto não fica guardado: a tela se recupera assim que a planilha volta.
              <details class="dic-rule"><summary>Detalhe técnico</summary>
                <div class="dic-rule-body"><p>Depois de uma falha de download, a próxima tentativa só acontece após 1 minuto; até lá vale a última leitura guardada (ou o aviso, se não houver nenhuma)</p></div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

    </div>
  </div>

</div>
<script>initDicionario();</script>
