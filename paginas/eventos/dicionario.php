<?php $dicTo = (($_GET['to'] ?? '') === '1') ? '?to=1' : ''; ?>
<div class="dash-eventos dic" data-sugestoes="combo,consulta,ROI">

  <header class="dic-head">
    <p class="dic-eyebrow">Eventos</p>
    <h1 class="dic-title">Dicionário de métricas</h1>
    <p class="dic-lead">O que significa cada número do dashboard de Eventos. O período do filtro incide sempre sobre a data do evento: leads, vendas e custos entram no evento a que pertencem, seja qual for a data de cada um.</p>
    <nav class="dic-tabs" aria-label="Abas do dashboard">
      <a class="dic-tab" href="<?= $this->site ?>eventos<?= $dicTo ?>">Pipeline</a>
      <a class="dic-tab" href="<?= $this->site ?>eventos/detalhamento<?= $dicTo ?>">Consolidado</a>
      <a class="dic-tab" href="<?= $this->site ?>eventos/relatorio<?= $dicTo ?>">Relatório</a>
      <a class="dic-tab" href="<?= $this->site ?>eventos/dicionario<?= $dicTo ?>" aria-current="page">Dicionário</a>
    </nav>
  </header>

  <div class="dic-layout">
    <div class="dic-body">

      <section class="dic-section" id="fontes">
        <h2 class="dic-section-title">Fontes de dados</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Evento <code>evento</code></dt>
            <dd>Cada linha é um evento cadastrado no CRM, em geral um evento presencial (OTR) da Acme EUA, com nome, cidade, data e status. É o centro do dashboard: leads, vendas e custos são sempre agrupados por evento. O cadastro também tem registros que não são evento presencial (como atendimentos 1:1 e o registro "Webinar"): o Pipeline e o Consolidado contam esses registros; só o Relatório os tira. Eventos marcados como excluídos no cadastro nunca entram.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Sempre <code>deletado_evento = 0</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Leads <code>leadV2</code></dt>
            <dd>Formulários preenchidos ligados a um evento. Cada lead pode estar qualificado ou não e pode ter conversado pelo WhatsApp, o que o torna uma "oportunidade".
              <details class="dic-rule"><summary>Ligação com o evento</summary>
                <div class="dic-rule-body"><p><code>idEvento_leadV2 = id_evento</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Oportunidades (deals) <code>dealV2</code></dt>
            <dd>Cards comerciais ligados a um evento. A fase atual e as datas de cada etapa (venda, consulta, FP, contrato) dizem em que ponto do funil o card está.
              <details class="dic-rule"><summary>Ligação com o evento</summary>
                <div class="dic-rule-body"><p><code>idEvento_dealV2 = id_evento</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Conversas <code>conversaCW · msgCW</code></dt>
            <dd>Conversas do Chatwoot (WhatsApp) e suas mensagens. Um lead vira oportunidade quando existe pelo menos uma mensagem enviada por ele.</dd>
          </div>
          <div class="dic-def">
            <dt>Investimento de marketing <code>custoMkt</code></dt>
            <dd>Gastos com anúncios pagos (Meta e Google), ligados ao evento pelo formulário. Entra todo gasto com o identificador do formulário do evento, seja qual for a data do gasto. Valores em reais são convertidos para dólar pela cotação do dia do gasto.
              <details class="dic-rule"><summary>Ligação com o evento e conversão</summary>
                <div class="dic-rule-body">
                  <p><code>identificador_custoMkt = nomeForm_evento</code>, câmbio pela tabela <code>cambio</code> (maior cotação do dia, <code>MAX(valor_cambio)</code> na mesma data do gasto)</p>
                  <p><b>2025:</b> <code>origem_custoMkt = 'meta'</code> já em dólar; <code>'google'</code> em reais, dividido pelo câmbio</p>
                  <p><b>2026:</b> <code>conta_custoMkt IN ('Dollar 1','Dollar 2')</code> já em dólar; qualquer outra conta em reais, dividida pelo câmbio</p>
                  <p>Gastos de outros anos, ou de 2025 com outra origem, entram como zero. Gasto em reais num dia sem cotação cadastrada fica fora da soma.</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Custo físico do evento <code>custoEvento</code></dt>
            <dd>Gastos com a realização do evento (locação, buffet, equipe, materiais), ligados diretamente ao evento. Sempre em reais, convertidos para dólar pela cotação do dia do gasto ou, se esse dia ainda não tiver cotação, pela última cotação anterior a ele.
              <details class="dic-rule"><summary>Ligação com o evento e conversão</summary>
                <div class="dic-rule-body">
                  <p><code>custoEvento.id_evento = evento.id_evento</code></p>
                  <p>Câmbio: <code>MAX(valor_cambio)</code> da data de câmbio mais recente que seja igual ou anterior a <code>data_custoEvento</code></p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
        <p class="dic-note"><strong>Atualização.</strong> Os números ficam guardados por até 10 minutos depois de calculados, então uma mudança no CRM pode levar esse tempo para aparecer. A lista de leads que conversaram no Chatwoot (usada em Oportunidades) e o quadro Últimos eventos &amp; próximo são renovados a cada 30 minutos.</p>
      </section>

      <section class="dic-section" id="filtros">
        <h2 class="dic-section-title">Período e filtros</h2>
        <p class="dic-section-intro">Pipeline e Consolidado têm os mesmos filtros. O Relatório não tem filtros na tela (ver a seção da aba Relatório).</p>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Data do evento, Ano e Mês</dt>
            <dd>Escolhem os eventos pela data em que aconteceram; todo o resto (leads, vendas, custos) acompanha o evento, seja qual for a data de cada registro. Ao abrir, a aba já vem filtrada no ano atual, e Limpar volta para o ano atual. Dá para marcar vários anos e vários meses; mês escolhido sem ano pega aquele mês em todos os anos. As datas digitadas (de e até, inclusive) têm prioridade sobre Ano e Mês.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p>Período sempre sobre <code>data_evento</code></p>
                  <p><b>Datas digitadas:</b> <code>data_evento &gt;= início 00:00:00</code> e <code>&lt;= fim 23:59:59</code></p>
                  <p><b>Ano e Mês:</b> viram intervalos do primeiro dia do primeiro mês ao último dia do último mês escolhido, em cada ano</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Evento</dt>
            <dd>Busca por nome e permite escolher um ou mais eventos. A lista mostra todos os eventos cadastrados, de qualquer período, no formato "Nome — Mês/Ano". Com algum evento escolhido, Ano, Mês e datas são ignorados: a tela mostra só os eventos escolhidos.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Só um tipo de filtro vai para a consulta, nesta ordem de prioridade: <code>eventos</code>, depois <code>data_inicio</code>/<code>data_fim</code>, depois <code>ano</code>/<code>mes</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Filtro de cada aba</dt>
            <dd>Cada aba guarda o próprio filtro: ao trocar de aba, ela abre de novo no ano atual. O quadro Últimos eventos &amp; próximo, no Pipeline, ignora todos os filtros.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="funil">
        <h2 class="dic-section-title">Funil de conversão</h2>
        <p class="dic-section-intro">As cinco etapas, da captação ao contrato. A cor de cada etapa é a mesma dos cartões do dashboard.</p>
        <ol class="dic-steps">
          <li data-stage="aquisicao"><strong>Leads</strong><span>Formulários ligados ao evento</span></li>
          <li data-stage="evento"><strong>Cadeiras e presença</strong><span>Ingresso comprado e check-in no dia</span></li>
          <li data-stage="consulta"><strong>Consultas</strong><span>Reunião comercial vendida, marcada e realizada</span></li>
          <li data-stage="sinal"><strong>Sinal e FP</strong><span>Entrada paga e Financial Planning (FP)</span></li>
          <li data-stage="fecho"><strong>Combos</strong><span>Contrato assinado, fim do funil</span></li>
        </ol>
        <div class="dic-list">
          <article class="dic-term" data-stage="aquisicao">
            <h3 class="dic-term-name">Total de Leads e Qualificados</h3>
            <p class="dic-term-meta"><span>Aquisição</span><span>Leads</span></p>
            <p class="dic-term-desc">Quantos leads (cards de lead criados pelos formulários) estão ligados ao evento, e quantos deles foram marcados como qualificados pela qualificação automática. Um lead que preencheu mais de um formulário conta uma vez só.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Contagem de leads do evento, sem filtro pela data do formulário. Qualificados: <code>qualificado_leadV2 = 'Sim'</code></p>
              </div>
            </details>
          </article>
          <article class="dic-term" data-stage="aquisicao">
            <h3 class="dic-term-name">Oportunidades</h3>
            <p class="dic-term-meta"><span>Aquisição</span><span>Leads + conversas</span></p>
            <p class="dic-term-desc">Leads do evento, qualificados ou não, que de fato conversaram: enviaram pelo menos uma mensagem no WhatsApp.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Leads cujo ID do Bitrix tem ao menos uma mensagem recebida (<code>tipo_msgCW IN ('0','incoming')</code>) numa conversa ligada a ele</p>
                <p>Vínculo <code>idBitrix_leadV2 = idLeadBitrix_conversaCW</code>, só para ID numérico; a conversa pode ser de qualquer data</p>
              </div>
            </details>
          </article>
          <article class="dic-term" data-stage="evento">
            <h3 class="dic-term-name">Cadeiras Vendidas</h3>
            <p class="dic-term-meta"><span>Evento</span><span>Pipe de cadeiras (C9)</span></p>
            <p class="dic-term-desc">Ingressos vendidos para o evento presencial. Na aba Consolidado a coluna se chama Ingressos.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>faseId_dealV2 = 'C9:WON'</code> com data de venda preenchida</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="evento">
            <h3 class="dic-term-name">Pessoas em Sala</h3>
            <p class="dic-term-meta"><span>Evento</span><span>Check-in</span></p>
            <p class="dic-term-desc">Quantas pessoas com ingresso comprado compareceram de fato ao evento.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>checkin_dealV2 = '1'</code> e fase sem prefixo de pipe (fase "solta": <code>faseId_dealV2</code> preenchido e sem <code>:</code>)</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Consultas Vendidas</h3>
            <p class="dic-term-meta"><span>Consulta</span><span>Pipe digital (C11) e pipe principal</span></p>
            <p class="dic-term-desc">Consultas comerciais compradas por leads do evento, pelo canal digital ou pelo pipe do evento. Na aba Consolidado e no quadro de últimos eventos a coluna se chama Consultas.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>faseId_dealV2 IN ('WON','C11:WON')</code> com data de venda preenchida</p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="consulta">
            <h3 class="dic-term-name">Consultas a agendar, agendadas e realizadas</h3>
            <p class="dic-term-meta"><span>Consulta</span><span>Pipe Comercial 2 (C5)</span></p>
            <p class="dic-term-desc">As três situações da consulta: ainda sem data, com data marcada mas ainda sem registro de realização (a data marcada pode já ter passado), e já realizada. Um card conta em no máximo uma das três.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><b>A agendar:</b> fase <code>C5:NEW</code>, sem data agendada nem realizada</p>
                <p><b>Agendadas:</b> pipe <code>C5</code>, data agendada preenchida, realizada vazia</p>
                <p><b>Realizadas:</b> pipe <code>C5</code>, data de consulta realizada preenchida</p>
              </div>
            </details>
          </article>
          <article class="dic-term" data-stage="sinal">
            <h3 class="dic-term-name">Sinais Pagos</h3>
            <p class="dic-term-meta"><span>Sinal e FP</span><span>Pipe Comercial 2 (C5)</span></p>
            <p class="dic-term-desc">Clientes que pagaram a entrada depois da consulta, comprometendo-se com a compra. Na aba Consolidado a coluna se chama SinalPago.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Pipe <code>C5</code> com data de venda e de consulta realizada preenchidas, fora das fases de perda e distrato <code>C5:UC_AQ2QYT</code>, <code>C5:LOSE</code> e <code>C5:UC_CL1DBM</code></p></div>
            </details>
          </article>
          <article class="dic-term" data-stage="sinal">
            <h3 class="dic-term-name">FP agendado, não agendado, perdido e vendido</h3>
            <p class="dic-term-meta"><span>Sinal e FP</span><span>Pipe Comercial 2 (C5)</span></p>
            <p class="dic-term-desc">As etapas do Financial Planning (FP), que vem depois do sinal pago. Cards que caíram em Comercial 3, Distrato ou Lead sem qualificação saem de Agendado, Não agendado e Vendido; se já tinham FP realizado, contam em FP Perdido.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p><b>Agendado:</b> consulta realizada, FP marcado e ainda não realizado, fora das fases perdidas</p>
                <p><b>Não agendado:</b> data de venda e consulta realizada preenchidas, FP ainda sem data, fora das fases perdidas</p>
                <p><b>Perdido:</b> FP realizado, mas o card caiu em fase perdida</p>
                <p><b>Vendido:</b> consulta realizada, FP agendado e realizado, fora das fases perdidas</p>
                <p>Todos no pipe <code>C5</code>. Fases perdidas aqui: <code>fase_dealV2 IN ('Comercial 3','Distrato','Lead sem qualificação')</code></p>
              </div>
            </details>
          </article>
          <article class="dic-term" data-stage="fecho">
            <h3 class="dic-term-name">Combos Vendidos</h3>
            <p class="dic-term-meta"><span>Fechamento</span><span>Pipes C1, C5, C29 e C31</span></p>
            <p class="dic-term-desc">Contratos do pacote de imigração assinados: a venda final do funil.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p><code>faseId_dealV2 IN ('C1:WON','C5:WON','C29:WON','C31:WON')</code> com data de contrato de combo assinado preenchida</p></div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="financeiro">
        <h2 class="dic-section-title">Custos, ROI e projeção</h2>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Investimento MKT, Custo Físico e Custo Total</h3>
            <p class="dic-term-meta"><span>Custo</span><span>Em dólar</span></p>
            <p class="dic-term-desc">Investimento MKT é o gasto com anúncios pagos; Custo Físico, o gasto com a estrutura do evento. Custo Total é a soma dos dois. No Pipeline aparecem como Campanha e Operacional; no Consolidado e no Relatório, como Custo MKT e Custo Físico. O cartão Investimento do Consolidado e a coluna Invest. do quadro de últimos eventos mostram o Custo Total.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Custo Total = Investimento MKT + Custo Físico, em dólar</p>
                <p>Valores em reais convertidos pela maior cotação do dia do gasto; no Custo Físico, se o dia não tiver cotação, vale a última anterior (ver Fontes de dados)</p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Faturamento</h3>
            <p class="dic-term-meta"><span>Financeiro</span><span>Em dólar</span></p>
            <p class="dic-term-desc">Soma do valor líquido de todos os combos vendidos ligados aos eventos do filtro, seja qual for a data de assinatura do contrato. No Pipeline aparece como Faturamento Atual.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Soma de <code>valorComboLiquido_dealV2</code> dos combos vendidos</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">ROI</h3>
            <p class="dic-term-meta"><span>Financeiro</span><span>Multiplicador</span></p>
            <p class="dic-term-desc">Quantas vezes o faturamento cobre o custo total do evento. Um ROI de 8x quer dizer que cada dólar investido voltou como 8 dólares em vendas.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>ROI = Faturamento ÷ Custo Total</p>
                <p>Sem custo total, não há ROI: as tabelas do Consolidado e do Relatório mostram "—"; os cartões de ROI do Pipeline e do Consolidado mostram 0.00x</p>
                <p>Nos cartões e nas linhas de total, é o faturamento somado ÷ o custo somado, não a média dos ROIs dos eventos</p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Projeção de Faturamento e ROI Projetado</h3>
            <p class="dic-term-meta"><span>Financeiro</span><span>Estimativa</span></p>
            <p class="dic-term-desc">Estimativa do faturamento total do evento: o que já foi vendido mais o potencial ainda aberto no funil (FP agendado e consultas agendadas ou a agendar). Cada negócio aberto vale um valor fixo de US$ 20.000, com peso de 75% para FP agendado e de 25% para consultas.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Projeção = Faturamento + 75% × FP Agendado × US$ 20.000 + 25% × Consultas Agendadas × US$ 20.000 + 25% × Consultas a Agendar × US$ 20.000</p>
                <p>ROI Projetado = Projeção ÷ Custo Total, sem valor quando o custo total é zero</p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Lucro</h3>
            <p class="dic-term-meta"><span>Financeiro</span><span>Em dólar</span></p>
            <p class="dic-term-desc">Resultado líquido do evento. Nunca aparece negativo: quando o custo supera o faturamento, o lucro é mostrado como zero. Aparece na aba Consolidado.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Lucro = máximo(0, Faturamento − Custo Total)</p>
                <p>No cartão Lucro e na linha Total, a conta é feita sobre os totais (faturamento somado − custo somado), por isso pode ficar abaixo da soma dos lucros da coluna</p>
              </div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="outras">
        <h2 class="dic-section-title">Outras métricas</h2>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Taxa de Combo</h3>
            <p class="dic-term-meta"><span>Conversão</span><span>Bloco Fechamento</span></p>
            <p class="dic-term-desc">Percentual das consultas realizadas que viraram combo vendido: a conversão final do funil do evento. No Pipeline aparece na caixa de Combos Vendidos, como "% das realizadas", com uma barra.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Taxa de Combo = Combos Vendidos ÷ Consultas Realizadas, zero quando não há consulta realizada</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Conversão Qualificados → Cadeiras</h3>
            <p class="dic-term-meta"><span>Conversão</span><span>Seta do funil</span></p>
            <p class="dic-term-desc">O percentual na seta entre Aquisição e Evento: quantas cadeiras foram vendidas para cada 100 leads qualificados.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Cadeiras Vendidas ÷ Qualificados, somados em todos os eventos do filtro; zero quando não há qualificado</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Conversão Sala → Consultas</h3>
            <p class="dic-term-meta"><span>Conversão</span><span>Seta do funil</span></p>
            <p class="dic-term-desc">O percentual na seta entre Evento e Consulta: quantas consultas foram vendidas para cada 100 pessoas em sala. Pode passar de 100%, porque Consultas Vendidas não exige check-in (inclui as vendidas pelo canal digital).</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Consultas Vendidas ÷ Pessoas em Sala, somadas em todos os eventos do filtro; zero quando não há check-in</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Motivo de Descarte (Comercial 2)</h3>
            <p class="dic-term-meta"><span>Descarte</span><span>Top 8</span></p>
            <p class="dic-term-desc">Os motivos mais comuns pelos quais cards de evento foram descartados no comercial. Ajuda a ver padrões de objeção. O motivo mais frequente fica destacado em vermelho.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Contagem por <code>motivoDescarteComercial2_dealV2</code>, só <code>tipoLead_dealV2 = 'otr'</code> com motivo preenchido, nos cards dos eventos do filtro</p></div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="pipeline">
        <h2 class="dic-section-title">Aba Pipeline: como ler</h2>
        <p class="dic-section-intro">Os números do funil e dos cartões somam todos os eventos do filtro. As métricas estão nas seções acima; aqui, o que mais aparece na tela.</p>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Funil de conversão</dt>
            <dd>Desenhado como fluxograma. Na linha de cima, uma caixa por bloco (Aquisição, Evento, Consulta, Sinal e FP, Fechamento) com o número principal e os de apoio. As setas levam de um bloco ao seguinte: quanto mais grossa, maior o volume da etapa de destino, e as etiquetas trazem as conversões Qualificados → Cadeiras e Sala → Consultas. Na linha de baixo ficam as consultas e os FPs ainda em aberto e o FP perdido, ligados ao bloco de onde saem. Verde marca a venda final e vermelho, o que se perdeu. Passe o mouse numa caixa para ver a definição e deixar só as setas dela acesas. Em telas estreitas a etiqueta da seta mostra só o percentual.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Espessura da seta proporcional à raiz quadrada do número da etapa de destino; a mais grossa é a da maior etapa (em geral, Cadeiras Vendidas)</p>
                <p>As métricas do funil não formam uma coorte (cada uma usa a própria data e o próprio pipe), por isso as setas sem etiqueta não têm percentual</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Cartão Custos (USD)</dt>
            <dd>Mostra o Custo Total no topo e, abaixo, quanto dele foi Campanha (Investimento MKT) e quanto foi Operacional (Custo Físico), com o percentual de cada um e uma barra dividida nas mesmas cores.
              <details class="dic-rule"><summary>Como é calculado</summary>
                <div class="dic-rule-body"><p>Percentual = parte ÷ Custo Total</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Cartão Faturamento e ROI</dt>
            <dd>À esquerda, o Faturamento Atual e a Projeção de Faturamento, com uma barra que mostra quanto da projeção já foi realizado. À direita, o ROI atual e o ROI Projetado.
              <details class="dic-rule"><summary>Como é calculado</summary>
                <div class="dic-rule-body"><p>% já realizado = Faturamento ÷ Projeção de Faturamento (a barra para em 100%)</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Cores do ROI no Pipeline</dt>
            <dd>Verde a partir de 10x, âmbar de 5x até abaixo de 10x, vermelho abaixo de 5x ou sem custo. Vale para os dois ROIs do cartão e para a tabela Performance por Evento.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p><b>Verde:</b> ROI ≥ 10. <b>Âmbar:</b> ROI ≥ 5. <b>Vermelho:</b> abaixo de 5 ou sem ROI</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Últimos eventos &amp; próximo</dt>
            <dd>Os dois últimos eventos já realizados e o próximo agendado, do mais antigo para o próximo, que fica destacado com o selo PRÓXIMO. Ignora todos os filtros da tela. Colunas: Invest. (Custo Total), Qualif. (Qualificados), Cadeiras (Cadeiras Vendidas), Consultas (Consultas Vendidas) e Combos, com as mesmas regras do funil.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Os 2 eventos mais recentes com <code>data_evento</code> até hoje e o 1 mais próximo com <code>data_evento</code> depois de hoje, sempre com <code>deletado_evento = 0</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Tabela Performance por Evento</dt>
            <dd>Uma linha por evento que tem custo registrado (eventos sem custo ficam de fora, porque não têm ROI), do maior para o menor ROI, com Faturamento, ROI(x) e ROI Projetado. A barra ao lado do ROI compara com o maior ROI da tabela, e a estrela marca o evento de maior faturamento. A linha Total soma todos os eventos do filtro, inclusive os que ficaram fora da lista por não ter custo.</dd>
          </div>
          <div class="dic-def">
            <dt>Contador de eventos</dt>
            <dd>O selo "N eventos" no topo conta todos os eventos do filtro, inclusive os que ainda não têm lead, venda ou custo.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="consolidado">
        <h2 class="dic-section-title">Aba Consolidado</h2>
        <p class="dic-section-intro">Uma tabela com uma linha por evento, com os mesmos números do Pipeline, e oito cartões de resumo no topo. Usa os mesmos filtros do Pipeline.</p>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Cartões do topo</dt>
            <dd>Oportunidades, Ingressos (Cadeiras Vendidas), Consultas (Consultas Vendidas), Combos, Investimento (Custo Total), Faturamento, Lucro e ROI, somando todos os eventos do filtro.</dd>
          </div>
          <div class="dic-def">
            <dt>Colunas da tabela</dt>
            <dd>Evento (nome, mês e ano), Custo MKT, Custo Físico, Custo Total, Ingressos, Consultas (vendidas), SinalPago (Sinais Pagos), Combos, Faturamento, Lucro, ROI(x), Projeção Faturamento e ROI Projetado. Cada coluna segue a regra da métrica de mesmo nome neste dicionário. Valores em dólar sem centavos, exceto a Projeção Faturamento; ROI(x) e ROI Projetado com duas casas, sem o "x".</dd>
          </div>
          <div class="dic-def">
            <dt>Quais eventos aparecem</dt>
            <dd>Todos os eventos do filtro, inclusive os que ainda não têm lead, venda ou custo: aparecem zerados, com ROI "—".</dd>
          </div>
          <div class="dic-def">
            <dt>Ordenação</dt>
            <dd>A tabela abre em ordem de data do evento, do mais antigo para o mais recente. Clicar no título de uma coluna ordena por ela: números começam do maior para o menor, e Evento em ordem alfabética; clicar de novo inverte. Evento sem ROI fica no fim quando a ordem é do maior para o menor. A linha Total fica sempre por último.</dd>
          </div>
          <div class="dic-def">
            <dt>Linha Total</dt>
            <dd>Soma custos, contagens e faturamento de todos os eventos do filtro. ROI, ROI Projetado e Lucro da linha Total são recalculados sobre esses totais, e não somados nem tirados da média das linhas.</dd>
          </div>
          <div class="dic-def">
            <dt>Cores da tabela</dt>
            <dd>Faturamento e Lucro em verde quando positivos e em cinza quando zero. A etiqueta do ROI(x) usa uma régua diferente da do Pipeline: verde quando o ROI já é igual ao ROI Projetado (nada mais em aberto no funil), âmbar quando ainda está abaixo do projetado, vermelho quando é zero (custo sem faturamento) e sem cor quando não há custo.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Verde:</b> ROI ≥ ROI Projetado − 0,005</p>
                  <p><b>Âmbar:</b> ROI acima de zero e abaixo do ROI Projetado</p>
                  <p><b>Vermelho:</b> ROI ≤ 0</p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="relatorio">
        <h2 class="dic-section-title">Aba Relatório</h2>
        <p class="dic-section-intro">Documento para imprimir ou salvar em PDF (A4 paisagem), em dólar. Usa os mesmos números do Consolidado e acrescenta CPL e CAC, calculados na própria página.</p>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">CPL</h3>
            <p class="dic-term-meta"><span>Eficiência</span><span>Só custo de marketing</span></p>
            <p class="dic-term-desc">Quanto custou, em marketing, cada lead do evento. O custo físico fica de fora, porque não capta lead.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>CPL = Investimento MKT ÷ Total de Leads; "—" quando não há lead</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">CAC Cadeira</h3>
            <p class="dic-term-meta"><span>Eficiência</span><span>Custo total</span></p>
            <p class="dic-term-desc">Quanto custou cada cadeira vendida, considerando o custo total do evento (marketing e estrutura).</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>CAC Cadeira = Custo Total ÷ Cadeiras Vendidas; "—" quando não há cadeira vendida</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">CAC Consulta</h3>
            <p class="dic-term-meta"><span>Eficiência</span><span>Custo total</span></p>
            <p class="dic-term-desc">Quanto custou cada consulta vendida, considerando o custo total do evento.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>CAC Consulta = Custo Total ÷ Consultas Vendidas; "—" quando não há consulta vendida</p></div>
            </details>
          </article>
        </div>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Filtro do relatório</dt>
            <dd>A página não tem filtros na tela: lê o filtro do endereço da página, com a mesma prioridade do Pipeline (eventos escolhidos, depois datas, depois Ano e Mês). Aberta pela aba ou pelo menu, o endereço vem sem filtro e o relatório traz todos os eventos de todos os períodos.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Parâmetros aceitos na URL: <code>eventos</code>, <code>data_inicio</code>/<code>data_fim</code> ou <code>ano</code>/<code>mes</code>, nessa ordem de prioridade</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Registros fora do relatório</dt>
            <dd>Registros do cadastro que não são evento presencial ficam de fora: nomes que começam com "1:1" ou "1x1" e o registro chamado exatamente "Webinar". Cartões, tabelas e totais são recalculados só com os eventos que ficaram, por isso os totais do relatório podem ficar abaixo dos do Consolidado. O rodapé lista o que saiu, com o custo e os leads desses registros.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p>Nome do evento casando com <code>^(1:1|1x1)\b|^webinar$</code>, sem diferenciar maiúsculas e minúsculas</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Página 1: resumo</dt>
            <dd>Cabeçalho com o período e o número de eventos e 13 cartões: Custo MKT, Custo Físico, Custo Total, Leads, CPL, Qualificados, Cadeiras, CAC Cadeira, Consultas Vend., CAC Consulta, Combos, Faturamento e ROI.</dd>
          </div>
          <div class="dic-def">
            <dt>Gasto e faturado por mês do evento</dt>
            <dd>Custo MKT, Custo Físico, Custo Total, Faturamento e ROI agrupados pelo mês em que o evento aconteceu, com linha TOTAL. Com mais de um ano no filtro, vira uma tabela por ano, lado a lado, cada uma com Subtotal.</dd>
          </div>
          <div class="dic-def">
            <dt>Destaques do período</dt>
            <dd>O evento campeão em seis quesitos: Melhor CPL, Melhor CAC cadeira e Melhor CAC consulta (os menores), Maior faturamento, Melhor ROI e Pior ROI. Só disputa quem tem o que medir: CPL exige investimento de marketing e leads; os CACs e o Pior ROI exigem custo total; Maior faturamento e Melhor ROI exigem valor acima de zero. No Pior ROI, evento com custo e sem faturamento conta como ROI zero.</dd>
          </div>
          <div class="dic-def">
            <dt>Página 2 em diante: consolidado por evento</dt>
            <dd>Uma linha por evento com Custo MKT, Custo Físico, Custo Total, Leads, CPL, Qualif., Cadeiras, CAC Cadeira, Cons. Vend., CAC Consulta, Combos, Faturamento e ROI(x). Ordem: mês do evento e, dentro do mês, o maior custo total primeiro. Com mais de um ano, as linhas se agrupam por ano, com subtotal; no fim vem a linha TOTAL. O cabeçalho se repete em cada página impressa.</dd>
          </div>
          <div class="dic-def">
            <dt>ROI no relatório</dt>
            <dd>A etiqueta de ROI de cada evento usa as mesmas cores da aba Consolidado. Nas linhas de Subtotal e TOTAL, o ROI aparece sem cor, recalculado como faturamento somado ÷ custo somado.</dd>
          </div>
          <div class="dic-def">
            <dt>Rodapé e botão Baixar PDF</dt>
            <dd>O rodapé resume as fórmulas de CPL, CAC e ROI, lembra que o mês é o de realização do evento e lista os registros excluídos. O botão Baixar PDF abre a caixa de impressão do navegador: escolha Salvar como PDF, em A4 paisagem. O documento sai sempre em fundo claro, sem menu, filtros e abas, com o resumo na página 1 e a tabela a partir da página 2.</dd>
          </div>
        </dl>
      </section>

    </div>
  </div>

</div>
<script>initDicionario();</script>
