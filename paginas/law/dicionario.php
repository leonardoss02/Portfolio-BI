<?php $dicTo = (($_GET['to'] ?? '') === '1') ? '?to=1' : ''; ?>
<div class="dash-law dic" data-sugestoes="milestone,NVC,ciclo">

  <header class="dic-head">
    <p class="dic-eyebrow">Law</p>
    <h1 class="dic-title">Dicionário de métricas</h1>
    <p class="dic-lead">O que significa cada número do dashboard Law, em linguagem simples, e de onde vêm os dados. O filtro de período usa a data de criação do caso.</p>
    <nav class="dic-tabs" aria-label="Abas do dashboard">
      <a class="dic-tab" href="<?= $this->site ?>law<?= $dicTo ?>">Law</a>
      <a class="dic-tab" href="<?= $this->site ?>law/prioridade<?= $dicTo ?>">Alta Prioridade</a>
      <a class="dic-tab" href="<?= $this->site ?>law/dicionario<?= $dicTo ?>" aria-current="page">Dicionário</a>
    </nav>
  </header>

  <div class="dic-layout">
    <div class="dic-body">

      <section class="dic-section" id="fases">
        <h2 class="dic-section-title">Fases do processo (Bitrix)</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Milestones 1, 2 e 3 <code>fase = "Milestone 1/2/3"</code></dt>
            <dd>Representam um caso ainda <strong>em andamento</strong> dentro do processo jurídico, avançando por 3 etapas internas (marcos) depois de aplicado junto ao órgão responsável. Juntas, as 3 fases compõem o que o dashboard chama de <strong>"Em Aberto"</strong>.</dd>
          </div>
          <div class="dic-def">
            <dt>Aplicados <code>fase = "Aplicados"</code></dt>
            <dd>O caso já foi formalmente enviado/aplicado junto ao órgão responsável (USCIS) e aguarda análise.</dd>
          </div>
          <div class="dic-def">
            <dt>Aprovado USCIS <code>fase = "Aprovado USCIS"</code></dt>
            <dd>O caso foi aprovado pelo USCIS (órgão de imigração dos EUA): etapa de sucesso do processo.</dd>
          </div>
          <div class="dic-def">
            <dt>NVC <code>fase = "NVC"</code></dt>
            <dd>O caso está no <strong>National Visa Center</strong>, etapa seguinte à aprovação do USCIS no processo de visto/green card.</dd>
          </div>
        </dl>
        <p class="dic-note"><strong>Nota:</strong> nenhuma dessas 4 categorias (Em Aberto/Aplicados/Aprovados/NVC) é uma coluna separada no banco; todas são derivadas da mesma coluna <code>fase_dealV2</code> do deal no Bitrix. O dashboard só considera casos cuja fase esteja em uma dessas 6 opções; qualquer outra fase é tratada como fora do escopo do Law.</p>
      </section>

      <section class="dic-section" id="kpis">
        <h2 class="dic-section-title">Os 6 indicadores do topo</h2>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Total de Casos</h3>
            <p class="dic-term-meta"><span>Visão geral</span><span>Em Aberto + Aplicados + Aprovados + NVC</span></p>
            <p class="dic-term-desc">Quantidade total de casos ativos no funil de Law, somando as 4 categorias de status.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Soma dos 4 KPIs de status abaixo</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Alta Prioridade</h3>
            <p class="dic-term-meta"><span>Atenção imediata</span><span>Tarefa urgente em aberto</span></p>
            <p class="dic-term-desc">Casos que têm pelo menos uma tarefa marcada como <strong>prioridade alta</strong> no Bitrix e ainda não concluída: sinal de que aquele caso precisa de atenção imediata do case manager.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Deal com tarefa vinculada onde <code>prioridade = alta</code> e <code>status ≠ "Concluída"</code></p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Em Aberto</h3>
            <p class="dic-term-meta"><span>Em andamento</span><span>Milestone 1 + 2 + 3</span></p>
            <p class="dic-term-desc">Casos que ainda estão em andamento, passando pelas 3 etapas internas antes da aplicação junto ao órgão.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem de deals com <code>fase ∈ {Milestone 1, Milestone 2, Milestone 3}</code></p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Aplicados</h3>
            <p class="dic-term-meta"><span>Aguardando análise</span><span>Fase Aplicados</span></p>
            <p class="dic-term-desc">Casos já enviados ao órgão responsável, aguardando análise.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem de deals com <code>fase = "Aplicados"</code></p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Aprovados</h3>
            <p class="dic-term-meta"><span>Aprovado</span><span>Fase Aprovado USCIS</span></p>
            <p class="dic-term-desc">Casos aprovados pelo USCIS.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem de deals com <code>fase = "Aprovado USCIS"</code></p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">NVC</h3>
            <p class="dic-term-meta"><span>Etapa final</span><span>Fase NVC</span></p>
            <p class="dic-term-desc">Casos que avançaram para o National Visa Center.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem de deals com <code>fase = "NVC"</code></p></div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="graficos">
        <h2 class="dic-section-title">Como ler os gráficos</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Casos por Case Manager</dt>
            <dd>Uma barra horizontal por case manager (o advogado de atendimento do deal), dividida em 4 cores, uma para cada status (Em Aberto/Aplicados/Aprovados/NVC): mostra a carteira de casos de cada advogado(a) em cada etapa. A lista vai do case manager com mais casos para o com menos; casos sem case manager ficam de fora. Passar o mouse sobre a barra mostra os números de cada status.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Agrupado pela coluna <code>advogadoAtendimento</code> do deal</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Evolução por Período</dt>
            <dd>Mostra a quantidade de casos criados em cada período, separados pelas 4 linhas de status. O eixo de tempo usa a <strong>data de criação do caso</strong>. Pode ser visto por Dia, Semana, Mês, Ano ou <strong>Ciclo</strong>. Na visão por Ciclo, os casos criados antes de 19/05/2025 ficam de fora, porque não pertencem a nenhum ciclo.</dd>
          </div>
          <div class="dic-def">
            <dt>O que é um "Ciclo"?</dt>
            <dd>É um calendário próprio do time de Law, diferente do calendário civil: cada ciclo dura <strong>4 semanas</strong> (28 dias), começando em 19/05/2025 (Ciclo 1). A cada 4 semanas completas a partir dessa data, começa um novo ciclo.</dd>
          </div>
          <div class="dic-def">
            <dt>Milestones por Case Manager e Total por Milestone</dt>
            <dd>Detalham só os casos "Em Aberto", separando por qual dos 3 marcos internos (ML1, ML2 ou ML3) o caso está. O gráfico de rosca mostra a proporção entre os 3 marcos no total geral; a barra mostra essa mesma quebra por case manager. A rosca conta todos os casos Em Aberto; a barra deixa de fora os casos sem case manager.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="prioridade">
        <h2 class="dic-section-title">Aba Alta Prioridade</h2>
        <p class="dic-section-intro">Usa os mesmos filtros da aba Law. Os cartões Total de Casos, Em Aberto e Alta Prioridade são os mesmos da aba Law; o restante da aba mostra só os casos de alta prioridade, com exceção dos cartões ML1, ML2 e ML3.</p>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">ML1, ML2 e ML3</h3>
            <p class="dic-term-meta"><span>Em andamento</span><span>Todos os casos Em Aberto</span></p>
            <p class="dic-term-desc">Quantos casos Em Aberto estão em cada marco interno (Milestone 1, 2 ou 3). Contam todos os casos Em Aberto do recorte de filtros, não só os de alta prioridade: são os mesmos números da rosca Total por Milestone da aba Law.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem de deals com <code>fase = "Milestone 1"</code>, <code>"Milestone 2"</code> ou <code>"Milestone 3"</code>, sem nenhuma condição de tarefa ou prioridade</p></div>
            </details>
          </article>
        </div>
        <p class="dic-note is-warn"><strong>Atenção:</strong> por isso a soma de ML1, ML2 e ML3 é igual ao cartão Em Aberto, e não ao cartão Alta Prioridade.</p>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Case manager em destaque</dt>
            <dd>O case manager com mais casos de alta prioridade no recorte de filtros, com a quantidade de casos. Casos sem case manager não entram nessa conta e, em caso de empate, aparece só um nome. Sem nenhum caso de alta prioridade, o quadro avisa que não há casos no período.</dd>
          </div>
          <div class="dic-def">
            <dt>Casos Alta Prioridade</dt>
            <dd>Tabela com cada caso que tem tarefa de alta prioridade em aberto, do caso criado há mais tempo para o mais recente: ID do negócio no Bitrix, data de criação, case manager e fase. A coluna Alta Prioridade vale sempre 1, porque cada linha é um caso.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Mesma regra do cartão Alta Prioridade, ordenado por <code>criadoEm_dealV2</code> crescente</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Evolução de Casos em Prioridade</dt>
            <dd>Uma linha só, com a quantidade de casos de alta prioridade em cada período, por Dia, Semana, Mês, Ano ou Ciclo. Os casos são agrupados pela data em que foram criados, não pela data da tarefa, e contam os que hoje têm tarefa de alta prioridade em aberto. Na visão por Ciclo, os casos criados antes de 19/05/2025 ficam de fora.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="filtros">
        <h2 class="dic-section-title">Filtros</h2>
        <p class="dic-section-intro">Os mesmos nas duas abas. Os filtros se combinam entre si e valem para todos os cartões, gráficos e para a tabela.</p>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Data, Ano e Mês</dt>
            <dd>Recortam pela data de criação do caso. O intervalo de datas só vale com as duas datas preenchidas e, nesse caso, substitui Ano e Mês. A tela abre com o ano de 2026 selecionado.</dd>
          </div>
          <div class="dic-def">
            <dt>Ciclo</dt>
            <dd>Mantém só os casos criados nos ciclos escolhidos. A lista oferece apenas os ciclos que têm casos, a partir do Ciclo 1 (19/05/2025).</dd>
          </div>
          <div class="dic-def">
            <dt>Status</dt>
            <dd>Mantém só os casos dos status escolhidos: Em Aberto, Aplicados, Aprovados ou NVC.</dd>
          </div>
          <div class="dic-def">
            <dt>Milestone</dt>
            <dd>Mantém só os casos que estão nos marcos escolhidos (ML1, ML2 ou ML3). Escolher um milestone restringe o painel inteiro a casos Em Aberto: Aplicados, Aprovados e NVC ficam zerados.</dd>
          </div>
          <div class="dic-def">
            <dt>Case Manager</dt>
            <dd>Mantém só os casos dos case managers escolhidos.</dd>
          </div>
        </dl>
      </section>

    </div>
  </div>

</div>
<script>initDicionario();</script>
