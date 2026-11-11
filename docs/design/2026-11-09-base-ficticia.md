# Base de dados fictícia para o portfólio

| | |
|---|---|
| **Data** | 09/11/2026 |
| **Branch** | `feature/base-ficticia` |
| **Status** | Implementada |

## 1. Objetivo

Hoje quem clona o repositório vê os dashboards **sem nenhum número**, porque o banco não acompanha o projeto. A meta é que qualquer pessoa consiga baixar o projeto e ver **todas as telas com dados coerentes** na própria máquina, sem ajuda e com um único comando:

```bash
git clone https://github.com/leonardoss02/Portfolio-BI.git
cd Portfolio-BI
docker compose up
# abrir http://localhost:8000 (login demo / demo)
```

Critérios de sucesso:

1. `docker compose up` sobe o projeto do zero numa máquina que só tem Docker instalado.
2. Todos os dashboards, exceto o BI Explorer (que sai do projeto), abrem com números maiores que zero nos filtros padrão.
3. Os números batem **entre** dashboards. A venda que aparece no Executivo é a mesma que vira fatura no Financeiro e caso no Jurídico.
4. Nada envelhece: daqui a anos, o filtro "ano/mês atual" continua mostrando dados.
5. Uma pessoa sem contexto entende o projeto lendo o GitHub: README, modelo de dados e código do gerador.

## 2. Escopo

**Inclui**

- Schema completo do banco (`database/schema.sql`), comentado.
- Gerador de dados fictícios em PHP (`database/seed/`).
- Ambiente Docker com PHP, Apache e MariaDB.
- Remoção do BI Explorer.
- Correções de anos fixos no código.
- Migração do NPS de Eventos e da aba "Planilha" do ConsultaCom2 do Google Sheets para tabelas do banco.
- Pequenos ajustes para o projeto rodar limpo.
- Teste de fumaça.
- Prints das telas no README.
- README e documentação do modelo de dados.

**Não inclui**

- **Autenticação dos endpoints JSON.** Hoje eles respondem sem login. Fica como tarefa separada, numa próxima sessão.
- Demo online hospedada.
- Mudanças na lógica de negócio dos dashboards além das listadas na seção 7.
- Otimização de performance.

## 3. Decisões tomadas

| Tema | Decisão | Motivo |
|---|---|---|
| Objetivo | Rodar localmente | O recrutador consegue testar de verdade |
| Ambiente | Docker Compose como caminho principal | Um comando e o mesmo resultado em qualquer máquina. Sem Docker também funciona, contra qualquer MySQL, e fica documentado como alternativa |
| Geração | Gerador PHP que roda a cada subida, com datas relativas a hoje (abordagem A) | Nunca abre vazio. O repositório não carrega um dump de vários MB, e o próprio gerador vira peça do portfólio |
| Escopo de telas | Todas, e o BI Explorer é removido | Nenhuma tela vazia para quem avalia o projeto |
| Anos fixos no código | Corrigir | Sem a correção, várias telas zeram a partir de 2027 |
| Google Sheets | Mover para tabelas do banco | Coerente com o resto do projeto, e funciona sem acesso externo |
| Prints | Incluir no README | A maioria dos visitantes só lê o GitHub |

## 4. Arquitetura e execução

### 4.1 Arquivos novos

```
docker-compose.yml
docker/
  php/Dockerfile          php:8.3-apache + mysqli + mbstring + mod_rewrite
  app.env                 .env de demonstração (login demo/demo), montado read-only
  app.dev.env             igual ao app.env, mas em modo DEV (usado só pelo teste de fumaça)
database/
  README.md               modelo de dados explicado + diagrama ER (Mermaid)
  schema.sql              DDL das 18 tabelas, comentado
  seed/
    gerar.php             ponto de entrada CLI
    config.php            volumes, taxas, pessoas, sazonalidade (único lugar para ajustar)
    src/                  um arquivo por domínio de negócio:
      Aleatorio.php         sorteios determinísticos (semente fixa)
      Calendario.php        janela de datas, sazonalidade, horários
      Base.php              as tabelas em memória antes de gravar
      Contexto.php          o que toda etapa recebe (hoje, sorteio, config, base)
      Gerador.php           a ordem das etapas
      ConstantesDoCodigo.php lê constantes dos controllers (fonte única de nomes e IDs)
      Pessoas.php           equipe interna (nomes fixos no código) e clientes fictícios
      Eventos.php           eventos presenciais e câmbio
      Marketing.php         campanhas, conjuntos, anúncios e custos
      Leads.php             leads e contatos
      VendaConsulta.php     cadeiras (C9), check-in e consulta no evento, consulta digital (C11)
      Comercial2.php        Comercial 2 (C5), retenção (C1), combos C29/C31, Serasa
      Dentistas.php         segmento Dentistas (C33)
      Financeiro.php        faturas
      PosVenda.php          pós-venda C3, jurídico, tarefas, orçamentos e campanha de metas
      Atendimento.php       conversas e mensagens do Chatwoot
      Jornada.php           tags da jornada (lead_tag)
      Fornecedores.php      Gestão de Eventos (fornecedorEvento) e custoEvento
      Pesquisas.php         NPS e planilha de consultas
      Banco.php             conexão, inserção em lote, limpeza do cache
      Verificacao.php       checagens de coerência e resumo final
tests/
  smoke.php               chama todos os endpoints e confere se há números
docs/
  design/                 esta spec
  prints/                 PNGs das telas + capturar.ps1 (ferramenta de quem mantém)
.gitattributes            força LF nos arquivos que vão para o container
```

### 4.2 Fluxo do `docker compose up`

Três serviços, usando a mesma imagem PHP:

1. **`db`**: MariaDB 11.4 com os dados em `tmpfs` (memória). Nada é persistido entre subidas. Usa o timezone `America/Sao_Paulo` e expõe a porta `3307` para quem quiser explorar os dados com um cliente SQL (DBeaver, por exemplo).
2. **`seed`**: container de uso único. Espera o `db` ficar saudável e então:
   1. aplica o `schema.sql`, que faz `DROP` e `CREATE`, por isso pode rodar quantas vezes for preciso;
   2. roda o gerador;
   3. roda as checagens de coerência;
   4. limpa `.mrphp/cache`;
   5. encerra.
3. **`app`**: Apache servindo o projeto em `http://localhost:8000`. Só sobe quando o `seed` termina com sucesso (`depends_on: condition: service_completed_successfully`).

O código do repositório é montado no container (bind mount), e o `docker/app.env` é montado por cima do `.env`. A pessoa não precisa criar nenhum arquivo.

**Decisões:**

- **Sem volume persistente.** Cada subida recria a base com dados terminando **hoje**. Quem voltar ao projeto meses depois não vê uma base parada no passado. O custo é a geração rodar a cada subida, com alvo de menos de 30 segundos.
- **Janela de dados:** de 01/01/2025 até hoje. O início fixo cobre as datas de negócio que existem no código (ver Apêndice B.4).
- **Determinismo:** a semente aleatória é fixa, então no mesmo dia sai sempre a mesma base. O gerador aceita `--hoje=AAAA-MM-DD`, com mínimo 01/06/2026, porque a campanha de metas começa em 18/05/2026.
- **Datas futuras** só onde o negócio tem futuro: eventos até 45 dias à frente, com os fornecedores desses eventos, e consultas agendadas até 30 dias à frente.
- **`app.env`:**
  - `APP_ENV=PROD` e `DISPLAY_ERRORS=false`, para o visitante nunca ver stack trace;
  - `TIMEZONE=America/Sao_Paulo`;
  - `DASH_AUTH_USER=demo` e `DASH_AUTH_PASSWORD=demo`;
  - `DB_DEFAULT_*` apontando para o `db`;
  - `DB_2_*` apontando para o mesmo banco, porque a página inicial faz `SHOW TABLES` nas duas conexões.
- **Teste de fumaça em modo DEV.** O smoke test roda com `APP_ENV=DEV`, porque nesse modo qualquer warning do PHP vira erro e aparece no teste. O compose escolhe o arquivo pela variável `APP_ENV_FILE`, cujo padrão é `docker/app.env`. Para o teste se usa `APP_ENV_FILE=./docker/app.dev.env`.
- **Portas configuráveis.** O padrão é `8000` (app) e `3307` (banco), e as variáveis `APP_PORTA` e `DB_PORTA` trocam as duas. Assim, quem já tem algo rodando nessas portas não fica travado.
- **Imagens multi-arquitetura** (`php:8.3-apache` e `mariadb:11.4`), que rodam também em Macs com chip Apple.
- **PHP puro, sem Composer**, igual ao resto do projeto. Nenhum script `.sh`.

### 4.3 Sem Docker

O gerador lê o `.env` como o resto do projeto. Com PHP 8 (`mysqli`, `mbstring`) e um MySQL/MariaDB local, por exemplo XAMPP ou Laragon, o caminho é:

```bash
cp .env.example .env              # preencher DB_DEFAULT_* e DASH_AUTH_*
php database/seed/gerar.php       # cria as tabelas e os dados
php -S localhost:8000 index.php
```

O README traz esse caminho como alternativa curta.

## 5. Schema

### 5.1 Tabelas

| Área | Tabelas | Observação |
|---|---|---|
| CRM (espelho do Bitrix) | `dealV2`, `leadV2`, `contactV2`, `taskV2`, `lead_tag`, `deal` | `deal` é legada e fica **vazia**: só existe porque o Financeiro faz `LEFT JOIN` nela |
| Marketing e eventos | `evento`, `custoMkt`, `custoEvento`, `cambio` | |
| Gestão de Eventos | `fornecedorEvento` | Uma tabela larga para as 7 categorias (hotel, equipamento, videomaker, transporte, frete, material, custoExtra), separadas por `categoria_fornecedorEvento` |
| Financeiro e pós-venda | `invoice`, `orcamentoV2`, `itemOrcamentoV2` | |
| Atendimento | `conversaCW`, `msgCW` | |
| Novas | `npsEvento`, `planilhaConsulta` | Substituem as planilhas do Google |

A lista completa de colunas está no Apêndice D.

### 5.2 Regras de tipo

- **Convenção do projeto:** tabela no singular, PK `id_{tabela}`, colunas `{campo}_{tabela}`.
- **Uma collation só: `utf8mb4_unicode_ci`.** É case-insensitive, o que importa porque o código mistura `'C9:Won'` e `'C9:WON'`, `'Won'` e `'WON'`, `'C29:won'` e `'C29:WON'`. No sistema original as tabelas do CRM eram latin1, por isso o código tem `CONVERT(... USING latin1)` em alguns joins. Esses CONVERTs continuam corretos contra utf8mb4, e o `schema.sql` explica isso num comentário.
- **Datas vazias são `NULL`**, nunca `0000-00-00`. O código trata as duas formas, e `NULL` funciona no modo estrito do MariaDB.
- **DATETIME** onde o código ou o JavaScript espera hora: `criadoEm_*`, `dataUltForm_*`, `dataVenda_dealV2`, as datas do Chatwoot e `lead_tag.dataCadastro`. O resto é **DATE**: faturas, câmbio, custos, datas do funil e `orcamentoV2.dataVenda`. Não usar TIMESTAMP, porque o sentinela do Chatwoot `1969-12-31 21:00:00` não cabe nele.
- **Valores:** `DECIMAL(12,2)`, e `cambio.valor_cambio` usa `DECIMAL(10,4)`. `custo_fornecedorEvento` **precisa** ter 2 casas, porque o módulo lê errado um valor com 3.
- **IDs do Bitrix** (`idBitrix_*`, `idEvento_dealV2`, `idEvento_leadV2`, `contactId_*`, `dealId_*`) são texto com dígitos, igual ao original. São únicos onde o código assume unicidade (`idBitrix_dealV2`, `idBitrix_leadV2`, `idBitrix_contactV2`).
- **`fase_dealV2` é `NOT NULL`.** Uma fase nula faz o card sumir dos cálculos com `NOT IN (...)`.
- **Índices** nas colunas de join e filtro: fase, evento, datas do funil, `msgCW.id_conversaCW` e os índices sugeridos para `lead_tag` (Apêndice D).

### 5.3 Exceções à convenção (mantidas e comentadas)

O código lê estes nomes literalmente, então renomear quebraria as telas:

- `lead_tag` tem colunas sem sufixo: `idBitrix_lead`, `id_leadV2`, `tag`, `descricao`, `dataCadastro`.
- `msgCW.id_conversaCW` e `custoEvento.id_evento` são FKs sem sufixo.
- `invoice.id_empresa` é sempre 1.
- `ballancedQB_invoice` tem dois L.
- `custoEvento` tem **exatamente** as 7 colunas esperadas. O módulo de Gestão de Eventos descobre as colunas com `SHOW COLUMNS` e recusa qualquer coluna extra (`lib/hotel/RepoCustoEvento.php`).
- `evento.id_evento` precisa ser PRIMARY KEY, porque uma consulta do módulo de Gestão de Eventos agrupa por ela.

## 6. Modelo de geração

### 6.1 Ideia central

O gerador **simula pessoas, não linhas soltas**. Cada cliente fictício percorre a jornada e deixa rastro em todas as tabelas, como aconteceria num CRM real.

```
Campanha (custoMkt) ─► Lead (leadV2) ─► qualificado?
                                         │
     ┌───────────── Evento (OTR) ────────┴────── Digital ─────────────┐
     ▼                                                                ▼
 Cadeira C9:WON ─► check-in no evento ─► Consulta WON          Consulta C11:WON
                                         └──────────┬──────────┘
                                                    ▼
                  Comercial 2 (C5): agendada ─► realizada ─► sinal ─► FP ─► COMBO C5:WON
                       │ perdido → Retenção (C1) → parte vira C1:WON          │
                       ▼                                                      ▼
                  motivo de descarte                          Faturas (invoice) + Pós-venda C3
                                                              (milestones, CSM, upsell/orçamentos)
```

As chaves que ligam as tabelas de uma mesma pessoa:

- `contactId` liga os cards dela entre si.
- `idEvento` liga o lead e os cards ao evento.
- `utmCampaign`, `utmContent` e `utmTerm` repetem `nomeCampanha`, `nomeConjunto` e `nomeAnuncio` do `custoMkt`.
- `formPreenchido` repete `identificador_custoMkt`, que é igual a `nomeForm_evento`.
- `idLeadBitrix_conversaCW` liga a conversa ao lead.
- `idDealBitrix_invoice` liga a fatura ao card.
- `dealId_orcamentoV2` liga o orçamento ao card C3.
- `lead_tag.idBitrix_lead` liga as tags ao lead.

### 6.2 Linha do tempo

- **Período:** de 01/01/2025 até hoje.
- **Crescimento gradual:** 2026 tem cerca de 30% mais volume que 2025, para o comparativo ano contra ano mostrar evolução.
- **Sazonalidade:** queda em dezembro e janeiro, fim de semana mais fraco.
- **Eventos presenciais:** cerca de 1 por semana, em cidades da lista `lib/hotel/cidades-legado.php`. O palestrante é um dos vendedores com aba de eventos. Cerca de 4 eventos ficam no futuro, para alimentar a prévia "próximo evento" e os alertas da Gestão de Eventos. Há também alguns webinars (prefixo `WEB`) e eventos "1:1", que exercitam o filtro de exclusão do relatório de Eventos.
- **Câmbio:** uma linha por dia corrido, fins de semana incluídos, em passeio aleatório entre R$ 5,20 e R$ 5,90 por dólar. Todo `custoMkt` em BRL precisa de cotação na mesma data.

### 6.3 Volumes aproximados (para cerca de 23 meses)

| Item | Quantidade |
|---|---|
| Leads | 20 a 30 mil |
| Cadeiras vendidas | ~1.500 |
| Consultas vendidas | 1.600 a 2.600 |
| Combos vendidos | 380 a 600 |
| Cards no CRM (todas as pipelines) | ~7 mil |
| Faturas | ~5 mil |
| Conversas no Chatwoot / mensagens | ~10 mil / ~60 mil |
| Tags da jornada | ~30 mil |
| Eventos | ~100, com ~6 fornecedores cada |
| Linhas de custo de marketing | ~17 mil |
| Respostas de NPS | ~1.500 |
| Carteira da campanha de metas (CS) | ~430 clientes |

A base completa fica na casa de 150 mil linhas.

**A carteira da campanha de metas inclui clientes "legados".** Os combos dentro da janela não chegam a 430 antes de 18/05/2026. Por isso, o pós-venda completa a carteira com cards C3 de clientes que fecharam em 2024, antes da janela de dados, como numa empresa que já tinha clientes. Esses cards só aparecem na campanha de metas, porque os outros filtros de período começam em 2025.

### 6.4 Taxas e valores (todos em `config.php`)

| Etapa | Taxa aproximada |
|---|---|
| Lead → qualificado | 45% |
| Qualificado de evento → cadeira | 30% |
| Cadeira → check-in | 70% |
| Check-in → consulta vendida no evento | 35% |
| Qualificado digital, orgânico ou indicação → consulta | 22% |
| Consulta vendida → realizada | 85% |
| Realizada → FP agendado | 65% (e 85% dos agendados se realizam) |
| FP realizado → combo | 50% |
| Perdido no C5 → retenção (C1) | 30%, e 20% destes fecham `C1:WON` |

Valores:

- **Combo:** US$ 15 a 25 mil líquidos (o código projeta com US$ 20 mil).
- **Cadeira:** R$ 100.
- **Consulta:** R$ 690.
- **ROI médio dos eventos:** entre 2x e 6x. Eventos individuais variam, e alguns fecham sem combo nenhum.

Cada etapa tem um prazo (dias entre consulta e FP, entre FP e contrato) e uma chance de parar. Quem para recebe uma fase de perda e um motivo de descarte, sorteado entre mais de 16 motivos para preencher o "Top 15 + Outros".

### 6.5 Casos de borda gerados de propósito

Para nenhum ramo do código ficar sem dado:

- `C5:UC_0Q30H1`: contrato enviado com assinatura pendente.
- Distratos (`C5:UC_CL1DBM`, `C5:UC_AQ2QYT`), com e sem data de contrato. Os que não têm data de contrato e têm `dataVenda` alimentam o *churn* de FP.
- Cards com as fases excluídas: "Distrato", "Comercial 3", "Lead sem qualificação".
- Consultas agendadas e não realizadas no mês atual, para "Consultas a realizar" sair maior que zero.
- Consultas realizadas em mês diferente do agendado, para a regra de data agendada corrigida.
- Consultas que estão no momento entre etapas: a agendar, agendada, FP agendado e FP não agendado.
- Faturas nos 4 status: Em dia (pagas com saldo 0 e abertas), Atrasado, Inadimplente e Congelado. Parte dos pagamentos vem com mais de 29 dias de atraso ("resgatado"). Cerca de 28% das faturas ficam sem card vinculado, como na produção.
- Conversas sem primeira resposta, nula ou com o sentinela `1969-12-31 21:00:00`.
- Leads que existem nas tags da jornada mas não no `leadV2` (cerca de 3%).
- Alguns `tipoLead` vazios e nulos, e alguns `formacaoAcademica` vazios.
- Eventos sem nenhum card (aparecem com zeros).

### 6.6 Segmentos específicos

- **Credenciados:** cerca de 8% dos leads e cards com `idAfiliado` preenchido.
- **Dentistas (`C33`):** só a partir de 31/08/2026, com `dentistaEvento`, `utmTerm`, `profissao` e `formacaoAcademica`.
- **Jurídico e CS (`C3`):** todo combo gera um card C3 que avança por arquitetura, milestones, aplicados, RFE e aprovado, com case manager, ADVs e CSM.
- **Campanha de metas (Liga do CS):**
  - ~430 cards C3 das 4 CSMs (IDs `271`, `453`, `725`, `727`) criados antes de 18/05/2026, completados com clientes legados de 2024 (seção 6.3).
  - `dataJson_dealV2` com os 10 campos de produto, dos quais ~390 estão totalmente mapeados.
  - Orçamentos vendidos (`Vendido` + `APPROVED`) desde 18/05/2026 até a última semana, em BRL e USD.
- **Retenção (`C1`):** time Comercial 3, com agendadas, realizadas, combos e descartes.
- **Jornada do cliente:** sequências de tags que seguem as probabilidades do modo demonstração já existente em `paginas/helenacastro/jornada.php`.

### 6.7 Identidade

- **Equipe interna:** exatamente os nomes fixos no código, cada um no seu papel (Apêndice A). Precisam bater letra por letra, porque o código compara strings em PHP.
- **Clientes:** nomes brasileiros combinados de listas (nome + sobrenome). E-mails só no domínio reservado `@exemplo.test` (o TLD `.test` não existe na internet), telefones fictícios. Companhias de transporte, hotéis e fornecedores têm nomes inventados.
- Nenhuma marca, pessoa ou domínio real. A marca continua "Acme EUA".

## 7. Mudanças no código

A regra é mexer o mínimo. A lógica de negócio dos dashboards não muda.

### 7.1 Remover o BI Explorer

- Apagar `controle/biexplorer_controle.php`, `paginas/biexplorer/`, `template/biexplorer.php` e `js/biexplorer.js` (cerca de 3.400 linhas).
- `controle/home_controle.php`: remover o hook `biexplorer_controle()`.
- `paginas/error/500.php` e `paginas/error/429.php`: tirar `'/biexplorer/'` da lista de prefixos de API.
- Reescrever os comentários que citam o BI Explorer em `js/bi-dashboard.js`, `lib/hotel/RepoJson.php`, `controle/hotel_controle.php` e `js/hotel/core.js`.

### 7.2 Anos fixos

| Onde | Antes | Depois |
|---|---|---|
| Conversão do custo de marketing (Executivo, Marketing, Eventos) | `YEAR(...) = 2025` / `= 2026` | `<= 2025` / `>= 2026` |
| Listas de ano nos filtros (todas as views com array fixo) | `['2024','2025','2026']` e variações | de 2025 até o ano atual |
| Ano padrão no Jurídico, CS (Comercial 4) e Credenciados | `'2026'` | ano corrente |

**Ficam como estão**, porque são datas de negócio e não "o ano atual":

- `LIGA_INICIO` (18/05/2026) e as constantes históricas da campanha de metas;
- `CICLO_EPOCH` do Jurídico (19/05/2025);
- o início do segmento Dentistas (31/08/2026).

### 7.3 Google Sheets → banco

- **`controle/npseventos_controle.php`:** `carregarPlanilhaComprou()` e `carregarPlanilhaNaoComprou()` passam a ler `npsEvento` com SQL. Devolvem as linhas **no mesmo formato de hoje**, então os cálculos, o JSON e a tela não mudam. Saem as URLs, o download via cURL, o parser de CSV e o fallback de cache da planilha.
- **`controle/consultacom2_controle.php`:** a mesma troca em `carregarPlanilha()`, lendo `planilhaConsulta`. Os campos `nome_norm` e `email_norm` continuam calculados em PHP, e a data passa a ser formatada a partir de uma DATE.
- O indicador `planilha_indisponivel` continua existindo e passa a significar "tabela vazia".
- Os dicionários de métricas dessas telas deixam de citar "planilha do Google".

### 7.4 Ajustes pequenos

- `.gitignore`: abrir uma exceção para `database/schema.sql` (hoje `*.sql` é ignorado).
- `.gitattributes`: forçar LF.
- `template/index.php`: remover a referência a `css/app.css`, que não existe.
- `paginas/error/404.php` está vazio: criar uma página 404 simples, no estilo do projeto.

### 7.5 README

- Seção "Como rodar" no topo: pré-requisito, os 3 passos, login e a lista de URLs para visitar.
- "Problemas comuns": porta 8000 ou 3307 ocupada, Docker fechado, como recriar a base.
- Prints das telas.
- Caminho alternativo sem Docker.
- Como rodar o teste de fumaça.
- Sai "o banco não acompanha o repositório" e "o schema não está incluído". Saem também a linha do BI Explorer e a menção ao Google Sheets.
- Contagens atualizadas: 21 controllers e cerca de 71 mil linhas.

## 8. Verificação

### 8.1 Teste de fumaça (`tests/smoke.php`)

Roda com `docker compose exec app php tests/smoke.php [--hoje=AAAA-MM-DD]` e:

- chama **todos os endpoints de dados** com os mesmos parâmetros padrão que as telas usam;
- confere HTTP 200, JSON válido sem chave de erro e **métricas-chave maiores que zero** (Apêndice C lista as métricas por dashboard);
- abre cada página HTML com login e espera 200;
- imprime uma tabela legível, por exemplo: `✔ executive/dados  leads=812 combos=41 investimento=…`.

### 8.2 Prova de que nada envelhece

Gerar com `--hoje=2027-03-15` e rodar o smoke test com a mesma data. Esse teste confirma que:

- o custo de marketing de 2027 sai maior que zero;
- as listas de ano incluem 2027;
- todas as métricas-chave continuam maiores que zero.

### 8.3 Checagens do gerador (`Verificacao.php`)

Ao terminar, o gerador imprime a contagem de cada etapa do funil e verifica:

- combos ≤ consultas realizadas, e consultas realizadas ≤ consultas vendidas, por canal;
- `custoEvento.valor` = soma de `fornecedorEvento.custo` por evento;
- toda data de `custoMkt` em BRL tem cotação no `cambio`;
- todo card C5 tem `fase_dealV2` preenchida;
- nomes da equipe interna batem com as listas fixas do código.

Se uma checagem falhar, ele para com uma mensagem clara e o `docker compose up` não sobe o `app`.

### 8.4 Prints

- 8 telas no tema claro, a 1440 px de largura: Executivo, Comercial, Marketing, Eventos, Financeiro, Atendimento, Jornada do cliente e Gestão de Eventos.
- Captura com Edge em modo headless, pelo script `docs/prints/capturar.ps1`. É PowerShell porque o Edge já vem no Windows e a máquina de quem mantém o projeto não tem PHP fora do Docker.
- Os PNGs ficam commitados em `docs/prints/`. O visitante não precisa rodar o script.

## 9. Legibilidade (requisito)

- **README:** "Como rodar" logo no topo e "Problemas comuns".
- **`database/README.md`:** o que cada tabela representa em linguagem de negócio, o que significa cada pipeline (`C5` = Comercial 2 etc.) e diagrama ER em Mermaid (o GitHub desenha sozinho).
- **`schema.sql` comentado** tabela por tabela, incluindo o porquê das escolhas herdadas do CRM.
- **Gerador organizado por domínio de negócio:** funções curtas e comentários que explicam a regra de negócio, não a sintaxe.
- **Parâmetros num lugar só:** `config.php`.
- **Nomes em português**, seguindo a convenção do projeto.

## 10. Riscos e mitigação

| Risco | Mitigação |
|---|---|
| Joins com `CONVERT(... USING latin1)` contra colunas utf8mb4 darem "Illegal mix of collations" | O smoke test cobre todos os endpoints. Se algum falhar, a coluna envolvida volta para latin1, com comentário no schema |
| Strings comparadas em PHP (nomes, campanhas) não baterem byte a byte | Nomes vêm de uma única fonte (`config.php`), conferida contra o código pela checagem 8.3 |
| Montar o `app.env` sobre o `.env` criar um arquivo vazio no repositório do host (comportamento do Docker no Windows) | Verificar na implementação. Se acontecer, copiar o código para dentro da imagem em vez de usar bind mount |
| Edge headless não aceitar login embutido na URL para os prints | Usar Playwright via `npx`, como ferramenta só de quem mantém o projeto |
| Clone no Windows converter `.env`, `.sql` e `.php` para CRLF | `.gitattributes` com `eol=lf` |
| Cache em `.mrphp/cache` mostrar números antigos depois de regerar | O `seed` limpa o cache sempre |
| Geração passar de 30 segundos | Inserção em lote (`INSERT` com várias linhas) dentro de transação |

---

## Apêndice A: equipe interna (nomes fixos no código)

| Papel | Nomes | Onde o código usa |
|---|---|---|
| Comercial 2 / especialistas (C5) | Rafael Torres, Beatriz Lemos, Camila Duarte, Juliana Prado, Paulo Nogueira, Ricardo Albuquerque, Gustavo Leal | Executivo (especialistas, analítico), Comercial 2, ConsultaCom2, painéis por vendedor |
| Ex-membros com atividade no CRM | Ana Moraes, Sônia Teles, Cláudia Rocha | Comercial 2 |
| Vendedora com painel diário e Jornada | Helena Castro | `helenacastro` |
| Responsáveis extras na Jornada | Sistema Admin, Venda Direta | Jornada, vendas de cadeira |
| Cadeiras (C9, leads OTR) | Patrícia Lopes, Débora Fontes, Marina Viana | Comercial (Cadeiras) |
| Comercial 1 Evento | Fernanda Matos, Priscila Rangel | Comercial 1 |
| Comercial 1 Digital | Eduardo Pires, Larissa Campos | Comercial 1 |
| Comercial 3 (Retenção, C1) | Thiago Dos Santos Ramos, Aline Barros | Comercial 3 |
| Atendimento (Chatwoot) | `Helena`, `Eduardo Pires`, `Priscila Rangel`, `Fernanda matos` (com "m" minúsculo, exatamente assim) | `conversaCW.responsavel` |
| CSMs da campanha de metas | ID 271 Simone, 453 Patrícia, 725 Viviane, 727 Tânia (o nome completo é inventado e começa pelo primeiro nome) | Comercial 4 (Liga) |
| Case managers e ADVs | Livres. Inventar 4 case managers e 6 ADVs, sem vírgula no nome | Jurídico, Comercial 4 |

Os palestrantes de evento são Camila Duarte, Juliana Prado, Rafael Torres e Beatriz Lemos.

## Apêndice B: fases e domínios de valores

### B.1 Pipelines (`faseId_dealV2`)

| Pipeline | Fases | Significado |
|---|---|---|
| sem prefixo | `NEW`, `UC_C07YVI`, `UC_OSVR3K`, `FINAL_INVOICE`, `WON`, `LOSE` | Funil de evento: check-in (`checkin='1'`), `dataEvento`, consulta vendida no evento (`WON` + `dataVenda`). `valorVenda=100` marca "possibilidade" no painel da Beatriz |
| `C9` | `C9:NEW`, `C9:WON`, `C9:LOSE` | Cadeiras (ingresso do evento) |
| `C11` | `C11:NEW`, `C11:WON`, `C11:LOSE` | Consulta digital |
| `C5` | `C5:NEW`, `C5:EXECUTING`, `C5:UC_0Q30H1` (contrato enviado), `C5:WON`, `C5:LOSE`, `C5:UC_CL1DBM` e `C5:UC_AQ2QYT` (distrato) | Comercial 2: consulta → sinal → FP → combo |
| `C1` | `C1:NEW`, `C1:WON`, `C1:LOSE` | Retenção (Comercial 3) |
| `C29`, `C31` | `C29:WON`, `C31:WON` | Combos de outros pipes. **Cards C31 não recebem `dataFpRealizado`**, porque `LEFT(faseId,2)='C3'` os contaria como Comercial 4 no Serasa |
| `C3` | `C3:NEW` (arquitetura a agendar), `C3:PREPARATION` (agendada), `C3:UC_*` | Pós-venda, Jurídico e CS |
| `C33` | `C33:NEW`, `C33:WON` | Dentistas |

Não usar categorias C50 a C59, porque `LEFT(faseId,2)='C5'` as pegaria.

**Fases em texto (`fase_dealV2`)**

- C5: "Consulta agendada", "Consulta realizada", "FP agendado", "FP realizado", "Contrato enviado", "Contrato assinado", e as excluídas "Comercial 3", "Distrato" e "Lead sem qualificação".
- C3 (o Jurídico lê só o texto):
  - Jurídico: "Milestone 1", "Milestone 2", "Milestone 3", "Aplicados", "Aprovado USCIS", "NVC";
  - funil do CS: "Aplicados", "Milestone 1", "Milestone 2", "Milestone 3", "RFE/Noid Respondida", "RFE/Noid Recebida", "RFE Ajuste de status";
  - outras: "Distrato", "Inadimplente", "Legado".

### B.2 Outros domínios

- **`tipoLead`:** `OTR`, `OTW`, `Consulta`, `Venda direta`, `B2B`, `Indicação`, além de vazio e nulo.
- **Prefixo de campanha e de formulário** (3 primeiras letras):
  - Evento: `OTR`, `INA`, `OTW`, `WEB`.
  - Orgânico: `ORG`, ou exatamente "Negado Cliente".
  - Indicação: `IND`, ou exatamente "F&F" e "MGM".
  - Qualquer outro prefixo conta como digital.
- **`qualificado_leadV2`:** `Sim` ou `Não`. **`etapa_leadV2`:** inclui `Lead Descartado`.
- **`custoMkt`:**
  - `origem` é `meta` ou `google`.
  - Em 2025, `meta` está em USD e `google` em BRL.
  - A partir de 2026, `conta` é `Dollar 1` ou `Dollar 2` (USD), ou uma conta em reais não nula (BRL).
- **`status_invoice`:** `Em dia`, `Atrasado`, `Inadimplente`, `Congelado`. Uma fatura paga é `Em dia` com saldo 0.
- **Chatwoot:**
  - `status`: `open`, `resolved`, `pending`, `snoozed`;
  - `rotulos` contém `venda-ingresso` ou `venda-consulta`;
  - `canal`: `Channel::Whatsapp`, `Channel::Instagram`;
  - `msgCW.tipo`: `0`/`incoming` (recebida) e `1`/`outgoing` (enviada).
- **`taskV2`:** `priority=2` significa alta. `status` é `Concluída`, `Pendente` ou `Em andamento`.
- **`orcamentoV2`:**
  - vendido = `etapa='Vendido'` **e** `etapaId='APPROVED'`;
  - outras etapas: em negociação, perdido;
  - `moedaId` é `BRL` ou `USD`;
  - os nomes dos produtos são os 10 da campanha de metas, mais alguns fora do mapeamento.
- **Campanha de metas:** os 10 campos `UF_CRM_*` do `dataJson_dealV2` e o mapa de IDs de opção para status (`iniciar`, `abordagem`, `sem_oportunidade`, `em_negociacao`, `orcamento_parceiro`, `declinada`, `fechada`, `interesse_futuro`, `fechado_comercial`) estão em `controle/comercial4_controle.php`. O gerador lê os mesmos valores dali.
- **Tags da jornada:** `lead-criado`, `carrinho-acessado`, `carrinho-pagamento`, `pagamento-confirmado`, `carrinho-abandonado`, `lead-descartado`. Também aparece uma tag fora do catálogo: `webinar-assistido`.
- **Serasa:**
  - `serasaRange` de `A` a `G`;
  - `nomeSujo` e `socioEmpresaNegativa` são `Sim` ou `Não`;
  - `statusCpf` é `Regular`, `Pendente` ou vazio;
  - dívidas em BRL.
- **`vistoEscolhido`:** `EB-2 NIW`, `EB-5`, `E-2`, `L-1`, `O-1`.
- **Gestão de Eventos:** os enums ficam em `lib/hotel/Schema.php` (situação do contrato, pagamento, entrega, logística, modal). Os valores são exatamente esses, em minúsculas.
- **NPS:**
  - notas de 0 a 10;
  - `interessePosEvento` é "Aumentou muito", "Aumentou", "Permaneceu igual" ou "Diminuiu".

### B.3 Formatos

- `checkin_dealV2` é a string `'1'`.
- `responsavelComercial4Id_dealV2` é a string `'271'` (e assim para os outros IDs).
- `dataJson_dealV2` é JSON válido ou `NULL`, nunca string vazia.
- `lead_tag.dataCadastro` sempre tem hora.
- `customerQB_invoice` só tem dígitos.

### B.4 Datas de negócio fixas no código

| Data | Constante | Efeito na geração |
|---|---|---|
| 19/05/2025 | `CICLO_EPOCH` do Jurídico | Os casos C3 começam depois dela |
| 18/05/2026 | `LIGA_INICIO` | A carteira tem `criadoEm` antes dessa data, e as vendas da campanha começam nela |
| 03/05/2026 | (câmbio da Liga) | Já coberto pela cotação diária |
| 31/08/2026 | Início do segmento Dentistas | Os cards C33 começam nessa data |

## Apêndice C: o que cada dashboard precisa para mostrar dados

"Preenchida" significa data não nula. "P" é o período do filtro.

| Dashboard | Filtro padrão | Métricas-chave no smoke test | Condições mínimas |
|---|---|---|---|
| Executivo | Ano e mês atuais (comparações: mesmo período do ano anterior e mês anterior) | leads, qualificados, cadeiras, consultas, combos, investimento, ROI | Leads por `dataUltForm`; cadeira `C9:WON` por `dataVenda`; consulta `WON`/`C11:WON` por `dataVenda`; realizada C5 por `dataConsultaRealizada`; combo `C1/C5/C29/C31:WON` por data do contrato; custo de marketing com câmbio na mesma data; custo físico pelo `custoEvento` de eventos com prefixo de evento |
| Comercial | Ano atual | combos por canal, YoY, ranking dos times | Canal pelo `tipoLead` (`OTR`/`OTW` = evento); ano anterior com dados; leads OTR das vendedoras de cadeira; C1 com `criadoEm` |
| Marketing | Ano atual | custo, cliques, leads por campanha, oportunidades | Nomes de campanha, conjunto e anúncio idênticos em `custoMkt`, `leadV2` e `dealV2`; conversas com mensagem recebida ligadas a leads |
| Eventos | Ano atual | qtd de eventos, cadeiras, check-ins, combos, ROI | Todos os cards do evento com `idEvento`; `custoMkt.identificador = nomeForm_evento`; `custoEvento` > 0 em todo evento; 2 eventos passados e 1 futuro para a prévia |
| Financeiro | Ano atual | recebido, a receber, atrasado | Faturas com pagamento e vencimento no ano, nos 4 status |
| Atendimento | Ano e mês atuais (compara com o mês anterior) | conversas, ingressos, consultas, TRM | 4 responsáveis do time; primeira resposta válida; dados no mês atual e no anterior |
| Jurídico | Ano atual | casos por status, alta prioridade | Fases do Jurídico em C3, `criadoEm` em P, tarefa `priority=2` não concluída |
| Customer Success | Ano atual | clientes C3, funil, vendas, carteira da Liga | Seção 6.6 |
| Credenciados | Ano atual | leads e cards de afiliado | `idAfiliado` preenchido |
| Dentistas | Ano atual | leads C33, check-ins, contratos | C33 desde 31/08/2026 |
| NPS Eventos | Ano atual | respostas, médias | `npsEvento` com respostas no ano |
| ConsultaCom2 | Ano e mês atuais | consultas do time, status da planilha | C5 com consulta realizada no mês pelo time; `planilhaConsulta` dos últimos 2 meses com acertos por e-mail, acertos só pelo nome e pendentes; vendas `WON` no mês |
| Vendedores (4) | Do dia 1º do mês até hoje | agendadas, realizadas, FP, combos | Cards C5 do vendedor no mês corrente, incluindo agendadas não realizadas; eventos em que o vendedor foi palestrante |
| Helena Castro | Últimos 30 dias | leads, consultas e cadeiras vendidas | Leads e cards da Helena no período e nos 30 dias anteriores |
| Jornada do cliente | Mês atual, responsável Helena Castro | leads, carrinho, pagos | Tags com hora; vários responsáveis |
| Gestão de Eventos | Todos os períodos | hotéis, custos, alertas | 1 hotel por evento; filhos de todas as categorias; eventos entre 0 e 7 dias e entre 8 e 30 dias à frente |

## Apêndice D: colunas por tabela

Toda tabela tem PK `id_{tabela} INT AUTO_INCREMENT`, salvo indicação contrária. Os tipos entre parênteses são o tamanho sugerido.

- **`dealV2`**
  - Identificação: `idBitrix` (20, único), `contactId` (20), `nome`, `email`, `telefone`.
  - Responsáveis: `responsavel`, `responsavelComercial4`, `responsavelComercial4Id` (10), `advogadoAtendimento`, `advMl1`, `advMl2`, `advMl3`.
  - Fase: `faseId` (50), `fase` (NOT NULL).
  - Origem: `tipoLead`, `formPreenchido`, `utmCampaign`, `utmContent`, `utmTerm`, `idEvento` (100), `idAfiliado`.
  - Perfil: `checkin` CHAR(1), `dentistaEvento`, `profissao`, `formacaoAcademica`, `vistoEscolhido`.
  - Datas: `criadoEm` DATETIME NOT NULL, `dataUltForm` DATETIME, `dataEvento` DATE, `dataVenda` DATETIME, `dataConsultaAgendada`, `dataConsultaRealizada`, `dataFpAgendado`, `dataFpRealizado` e `dataContratoComboAssinado` (DATE).
  - Valores: `valorVenda`, `valorVendaDolar`, `valorComboLiquido`, `valorAssessoria` (BRL).
  - Descarte: `motivoDescarte` (NOT NULL DEFAULT ''), `motivoDescarteComercial2`.
  - Serasa: `serasaRange` (2), `serasaScore` INT, `statusCpf`, `nomeSujo`, `socioEmpresaNegativa`, `valorTotalDividas`, `valorCartorioTotal`.
  - `dataJson` LONGTEXT.
- **`leadV2`**
  - Identificação: `idBitrix` (20, único), `nome`, `email`, `telefoneTexto`.
  - Funil: `responsavel`, `formPreenchido`, `tipoLead`, `qualificado`, `etapa`, `motivoDescarte`.
  - Origem: `utmCampaign`, `utmContent`, `utmTerm`, `idEvento` (100), `idAfiliado`.
  - Perfil: `profissao`, `tempoExp`, `formacaoAcademica` (NOT NULL DEFAULT '').
  - Data: `dataUltForm` DATETIME.
- **`contactV2`**: `idBitrix` (único), `primeiroNome`, `sobrenome`.
- **`taskV2`**: `idDeal` (20), `priority` INT, `status`.
- **`lead_tag`**: `id_lead_tag` (PK), `idBitrix_lead` INT, `id_leadV2` INT NULL, `tag`, `descricao`, `dataCadastro` DATETIME. Índices em `(tag, idBitrix_lead, dataCadastro)` e `(idBitrix_lead, dataCadastro, id_lead_tag)`.
- **`deal`**: `id_deal` (PK), `idBitrix_deal`. Fica vazia.
- **`evento`**: `id_evento` (PK), `nome`, `data` DATE, `cidade`, `local`, `endereco`, `palestrante`, `nomeForm`, `status` TINYINT, `deletado` TINYINT DEFAULT 0.
- **`custoMkt`**: `data` DATE, `valor`, `origem`, `conta`, `identificador`, `nomeCampanha`, `nomeConjunto`, `nomeAnuncio`, `clicks` INT.
- **`custoEvento`**: `id_custoEvento` (PK), `id_evento` INT, `nome_custoEvento`, `identificador_custoEvento`, `data_custoEvento` DATE, `valor_custoEvento`, `id_ordemCompra` INT DEFAULT 0. Nenhuma outra coluna.
- **`cambio`**: `data` DATE, `valor` DECIMAL(10,4).
- **`fornecedorEvento`**
  - Comuns: `categoria`, `idEvento` INT NULL, `data` DATE, `nome`, `custo` DECIMAL(12,2), `status`, `obs`, `contato`, `avaliacao` TINYINT, `idLegado` (único), `criadoEm`, `atualizadoEm`, `deletado` (DEFAULT 0).
  - Hotel: `uf`, `capacidade`, `tipoPagamento`, `situacaoContrato`, `dataAssinatura`, `linkContrato`, `nf`, `dataNf`, `oc`, `dataOc`, `numeroOc`, `reservaQuarto`, `quartoConfirmado`, `statusEntrega`, `dataEntrega`, `descricaoEntrega`, `obsEntrega`, `responsavelAv`.
  - Equipamento e videomaker: `horaEntrega`, `tipoMidia`.
  - Transporte: `modal`, `companhia`, `trecho`, `dataViagem`, `quantidade`, `bilhete`, `passageiros`.
  - Frete e material: `documento`, `dataEnvio`, `avulso`.
  - Todas as colunas fora a PK são anuláveis ou têm default. A lista vem do mapeamento em `lib/hotel/MapaFornecedor.php`.
- **`invoice`**: `id_empresa` INT, `idQB`, `customerQB`, `nomeQB`, `amountQB`, `ballancedQB`, `dueDateQB` DATE, `dataQBPagamento` DATE, `status`, `idDealBitrix`.
- **`orcamentoV2`**: `idBitrix`, `dealId`, `contactId`, `nome`, `etapa`, `etapaId`, `dataVenda` DATE, `criadoEm` DATETIME, `oportunidade`, `moedaId` (3).
- **`itemOrcamentoV2`**: `id_orcamentoV2` INT (FK interna), `nomeOriginalProduto_itemOrcamentoV2`.
- **`conversaCW`**: `idCw` INT, `idLeadBitrix` BIGINT, `responsavel`, `status`, `rotulos` TEXT, `canal`, `nomeRemetenteMeta`, `telefoneRemetenteMeta`, `criadoEm`, `primeiraRespostaEm` e `ultimaAtividadeEm` (DATETIME).
- **`msgCW`**: `id_conversaCW` INT (com índice), `tipo_msgCW` VARCHAR(10), `criadoEm_msgCW` DATETIME.
- **`npsEvento`** (nova)
  - Identificação: `idEvento` INT, `tipo` (`comprou` ou `nao_comprou`), `respondidoEm` DATETIME, `nome`, `cidade`.
  - Notas, TINYINT NULL de 0 a 10: `expGeral`, `local`, `coffee`, `palestrante`, `recepcao`, `brindes`, `abordagem`, `atendimento`, `indicacao`, `chanceImigrar`.
  - Textos: `fatorFechamento`, `motivoNaoFechou`, `interessePosEvento`.
- **`planilhaConsulta`** (nova): `nomeCliente`, `email`, `dataConsulta` DATE, `nomeComercial`, `obs`.
