<?php $dicTo = (($_GET['to'] ?? '') === '1') ? '?to=1' : ''; ?>
<div class="dash-financeiro dic" data-sugestoes="recebido,inadimplente,vencimento">

  <header class="dic-head">
    <p class="dic-eyebrow">Financeiro</p>
    <h1 class="dic-title">Dicionário de métricas</h1>
    <p class="dic-lead">O que cada número deste dashboard significa e como é calculado, em linguagem simples. Recebido e Resgatado seguem a data de pagamento; os saldos seguem a data de vencimento.</p>
    <nav class="dic-tabs" aria-label="Abas do dashboard">
      <a class="dic-tab" href="<?= $this->site ?>financeiro<?= $dicTo ?>">Financeiro</a>
      <a class="dic-tab" href="<?= $this->site ?>financeiro/dicionario<?= $dicTo ?>" aria-current="page">Dicionário</a>
    </nav>
  </header>

  <div class="dic-layout">
    <div class="dic-body">

      <section class="dic-section" id="fontes">
        <h2 class="dic-section-title">Fontes de dados</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Faturas do QuickBooks <code>invoice</code></dt>
            <dd>Cada linha é uma fatura (parcela) sincronizada do QuickBooks: valor cobrado, valor já pago, data de vencimento, data em que foi paga e o status atual (Em dia, Atrasado, Inadimplente ou Congelado). Só entram as faturas da empresa de código 1: faturas das outras empresas do grupo nunca aparecem no dashboard, qualquer que seja o filtro.
              <details class="dic-rule"><summary>Recorte de empresa</summary>
                <div class="dic-rule-body"><p>Todas as consultas do dashboard filtram <code>id_empresa = 1</code></p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Negócios do Bitrix <code>dealV2</code></dt>
            <dd>A fatura do QuickBooks não guarda email nem telefone do cliente. Ao expandir um cliente, esses dados de cada parcela vêm do negócio correspondente no Bitrix. Quando a fatura não tem negócio vinculado nessa tabela, os campos ficam em branco: não existe busca de reserva no cadastro antigo de negócios. Cerca de 28% das faturas não têm negócio vinculado.
              <details class="dic-rule"><summary>Ligação com a fatura</summary>
                <div class="dic-rule-body">
                  <p><code>dealV2.idBitrix_dealV2 = invoice.idDealBitrix_invoice</code>; email e telefone vêm só de <code>dealV2</code></p>
                  <p>A consulta também liga a tabela antiga <code>deal</code>, mas nenhum campo é lido dela</p>
                </div>
              </details>
            </dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="metricas">
        <h2 class="dic-section-title">Métricas</h2>
        <div class="dic-list">
          <article class="dic-term">
            <h3 class="dic-term-name">Total Recebido</h3>
            <p class="dic-term-meta"><span>Financeiro</span><span>Por data de pagamento</span></p>
            <p class="dic-term-desc">Quanto já entrou de fato, somando o valor pago de cada fatura que teve algum pagamento no período. O número abaixo do valor ("faturas pagas") conta essas faturas, inclusive as pagas só em parte.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Soma de <code>valor − saldo</code> das faturas com data de pagamento dentro do período (filtra pela <b>data de pagamento</b>)</p>
                <p><code>amountQB − ballancedQB</code>, só faturas com <code>ballancedQB &lt; amountQB</code></p>
              </div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Resgatado (+30d)</h3>
            <p class="dic-term-meta"><span>Financeiro</span><span>Pago com atraso &gt; 29 dias</span></p>
            <p class="dic-term-desc">Valor que entrou no período referente a faturas pagas mais de 29 dias depois do vencimento, ou seja, com 30 dias ou mais de atraso, qualquer que seja o status da fatura. O número abaixo do valor ("faturas resgatadas") conta essas faturas.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Mesma soma do recebido, mas só faturas onde <code>data de pagamento − data de vencimento &gt; 29</code>; o status da fatura não é considerado</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">A Receber (Em Dia)</h3>
            <p class="dic-term-meta"><span>Financeiro</span><span>Status Em dia</span></p>
            <p class="dic-term-desc">Saldo que ainda vai vencer no futuro: parcelas que estão dentro do prazo, ainda sem problema. Só entram faturas com saldo em aberto.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Soma do <b>saldo</b> das faturas com status "Em dia" e saldo maior que zero, filtrando pela <b>data de vencimento</b> dentro do período</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Saldo Atrasado</h3>
            <p class="dic-term-meta"><span>Financeiro</span><span>Status Atrasado</span></p>
            <p class="dic-term-desc">Já passou da data de vencimento, mas ainda não foi classificado como inadimplente pela régua de cobrança.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Soma do <b>saldo</b> das faturas com status "Atrasado", pela data de vencimento</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Saldo Inadimplente</h3>
            <p class="dic-term-meta"><span>Financeiro</span><span>Status Inadimplente</span></p>
            <p class="dic-term-desc">Faturas vencidas há tempo suficiente para entrar na régua de inadimplência.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Soma do <b>saldo</b> das faturas com status "Inadimplente", pela data de vencimento</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Saldo Congelado</h3>
            <p class="dic-term-meta"><span>Financeiro</span><span>Status Congelado</span></p>
            <p class="dic-term-desc">Saldo das faturas com status Congelado. Não tem cartão próprio: entra no Saldo Total Vencido, na linha Saldo Vencido do gráfico e no status dos clientes da tabela.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body"><p>Soma do <b>saldo</b> das faturas com status "Congelado", pela data de vencimento</p></div>
            </details>
          </article>
          <article class="dic-term">
            <h3 class="dic-term-name">Saldo Total Vencido</h3>
            <p class="dic-term-meta"><span>Financeiro</span><span>Atrasado + Inadimplente + Congelado</span></p>
            <p class="dic-term-desc">Visão geral de tudo que já venceu e ainda não foi pago, somando as três categorias de problema. O percentual abaixo do valor ("inadimplente") é a parte do total vencido que já está inadimplente.</p>
            <details class="dic-rule"><summary>Regra técnica</summary>
              <div class="dic-rule-body">
                <p>Saldo Atrasado + Saldo Inadimplente + Saldo Congelado</p>
                <p>% inadimplente = Saldo Inadimplente ÷ Saldo Total Vencido</p>
              </div>
            </details>
          </article>
        </div>
      </section>

      <section class="dic-section" id="regras">
        <h2 class="dic-section-title">Regras de período</h2>
        <p class="dic-section-intro">Duas datas diferentes, dois recortes diferentes.</p>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Recebido e Resgatado</dt>
            <dd>Filtrados pela <strong>data de pagamento</strong>: respondem "quanto entrou no mês X".</dd>
          </div>
          <div class="dic-def">
            <dt>Em Dia, Atrasado, Inadimplente e Congelado</dt>
            <dd>Filtrados pela <strong>data de vencimento</strong>: respondem "quanto vencia no mês X e em que situação está".</dd>
          </div>
        </dl>
        <p class="dic-note">Por isso os dois grupos de KPI não somam entre si: um olha o caixa que entrou, o outro olha a carteira em aberto.</p>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Status do cliente na tabela</dt>
            <dd>Se o cliente tiver ao menos uma parcela Inadimplente, ele aparece como Inadimplente; senão, se tiver alguma Congelada, aparece como Congelado; senão, se tiver alguma Atrasada, aparece como Atrasado; caso contrário, Em dia.</dd>
          </div>
        </dl>
      </section>

      <section class="dic-section" id="tela">
        <h2 class="dic-section-title">Como ler a tela</h2>
        <dl class="dic-list">
          <div class="dic-def">
            <dt>Número abaixo de cada cartão</dt>
            <dd>Nos cinco primeiros cartões, é a quantidade de faturas que formam o valor (faturas pagas, resgatadas, em dia, atrasadas ou inadimplentes). No Saldo Total Vencido, é o percentual inadimplente.</dd>
          </div>
          <div class="dic-def">
            <dt>Filtros</dt>
            <dd>Ano (a tela abre no ano atual) e Mês definem o período. Cliente procura por parte do nome; ID QB procura pelo número da fatura ou do cliente no QuickBooks. As buscas trazem todas as faturas do cliente encontrado e sempre se combinam com o período: uma fatura fora do período escolhido não aparece.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body">
                  <p><b>Cliente:</b> clientes com alguma fatura em que <code>nomeQB_invoice</code> contém o texto</p>
                  <p><b>ID QB:</b> clientes com <code>idQB_invoice</code> ou <code>customerQB_invoice</code> igual ao valor digitado</p>
                </div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Recebido, A Receber, Saldo Vencido e Resgatado por Mês</dt>
            <dd>Quatro linhas mês a mês. Valor Recebido e Resgatado usam o mês do pagamento; Valor A Receber (saldo Em dia) e Saldo Vencido usam o mês do vencimento. Saldo Vencido soma Atrasado, Inadimplente e Congelado, como o cartão Saldo Total Vencido. Os rótulos estão em milhares de dólares, e um mês sem valor numa linha fica sem ponto nela.
              <details class="dic-rule"><summary>Regra técnica</summary>
                <div class="dic-rule-body"><p><b>Saldo Vencido:</b> soma do saldo com <code>status_invoice IN ('Atrasado','Inadimplente','Congelado')</code>, agrupada pelo mês do vencimento</p></div>
              </details>
            </dd>
          </div>
          <div class="dic-def">
            <dt>Tabela de Clientes</dt>
            <dd>Uma linha por cliente do QuickBooks com fatura vencendo no período, com Status, Parcelas (quantidade de faturas), Valor Total, Valor Pago e Saldo. A tabela usa só a data de vencimento: o Valor Pago aqui é o que já foi pago dessas faturas, qualquer que seja a data do pagamento, e por isso pode não bater com o Total Recebido. A ordem inicial é do maior para o menor saldo; clicar num cabeçalho reordena. A busca acima da tabela procura por nome ou status, só nas linhas já carregadas, e não muda cartões nem gráfico. O botão "Exportar" baixa a tabela como está na tela, em CSV ou Excel.</dd>
          </div>
          <div class="dic-def">
            <dt>Detalhe das parcelas</dt>
            <dd>Clicar num cliente abre as parcelas dele com vencimento no período, da mais recente para a mais antiga: ID QB, email, telefone, valor da parcela, valor pago, saldo, data de pagamento, data de vencimento e status. Os filtros Cliente e ID QB não se aplicam aqui: mesmo que a busca tenha apontado uma única fatura, o detalhe mostra todas as parcelas do cliente no período. O botão "Baixar Analítico (CSV)" baixa essas parcelas.</dd>
          </div>
        </dl>
      </section>

    </div>
  </div>

</div>
<script>initDicionario();</script>
