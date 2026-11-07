# BI Comercial: dashboards em PHP sobre um CRM

Plataforma de dashboards de BI de uma consultoria de imigração para os EUA. Cobre leads, funil comercial, marketing, eventos presenciais, financeiro, atendimento e o pós-venda jurídico. Os dados vêm do CRM, sincronizado num banco MySQL/MariaDB, e cada dashboard é uma tela web com filtros, KPIs, gráficos e tabelas.

São 22 controllers, 88 views e cerca de 73 mil linhas de PHP, além de um design system próprio em CSS. Não há build, npm nem Composer.

> **Sobre esta cópia.** É uma versão de portfólio. A marca ("Acme EUA"), os nomes de pessoas, os domínios e os IDs de serviços externos são fictícios. Não há credenciais nem dados de clientes. O banco de dados não acompanha o repositório.

## Dashboards

| Área | Rota | O que mostra |
|------|------|--------------|
| Executivo | `/executive` | Funil comercial completo, custo de marketing e de eventos convertido para USD, ROI, especialistas, analítico e consulta de crédito |
| Comercial | `/comercial` | Funil Evento × Digital × Retenção, comparativo ano × ano anterior e uma visão por time de vendas |
| Marketing | `/marketing` | Funil por campanha, conjunto e anúncio; desempenho; comparativo anual; motivos de descarte |
| Eventos | `/eventos` | Pipeline e consolidado por evento presencial, com o funil em fluxograma |
| Financeiro | `/financeiro` | Recebimentos e inadimplência a partir das faturas |
| Atendimento | `/chatwoot` | Conversas do time comercial: volume, vendas originadas na conversa, tempo de 1ª resposta, duração e status |
| Jurídico | `/law` | Casos pós-venda por responsável e etapa, com fila de alta prioridade |
| Customer Success | `/comercial4` | Funil do pós-venda (vendas, clientes, agendamentos) e uma campanha de metas da equipe com produtos de parceiros |
| Credenciados, Dentistas, NPS | `/credenciados`, `/dentistas`, `/npseventos` | Recortes por segmento e pesquisa de satisfação dos eventos |
| Vendedores | `/rafaeltorres`, `/julianaprado`, … | Um painel por vendedor. O `/helenacastro` acompanha o dia a dia e traz a **Jornada do cliente**, um fluxograma de coorte montado a partir das etapas que o CRM registra |
| Gestão de Eventos | `/hotel` | Sistema operacional (não é um dashboard): hotéis, equipamentos, transporte e custos de cada evento |
| BI Explorer | `/biexplorer` | Exploração livre: o usuário escolhe tabela, colunas e joins, e o sistema monta o SQL somente leitura |

Cada dashboard tem um **dicionário de métricas** (`/{dashboard}/dicionario`). É uma página estática que explica cada número em linguagem de negócio, com a regra técnica recolhida logo abaixo.

## Stack

- **PHP 8** sobre o MRPHP, um framework MVC de entrada única e sem dependências: roteamento por URL, templates e acesso a banco
- **MySQL/MariaDB** via `mysqli`, sempre com prepared statements
- **JavaScript puro**, com Chart.js e ApexCharts carregados por CDN
- **CSS próprio** (`css/bi-theme.css`), com tokens de cor, tema claro e escuro e os componentes comuns (KPI, card, tabela, filtros recolhíveis, dicionário)

## Arquitetura

```
index.php                       entrada única
  └── MRPHP                     carrega .env, conecta ao banco, resolve a rota
        ├── controle/{nome}_controle.php    index() desenha a tela; dados() devolve JSON
        ├── template/{nome}.php             cabeçalho, menu e tema do dashboard
        └── paginas/{nome}/{metodo}.php     view: filtros, KPIs, gráficos (fetch no JSON)
```

Cada dashboard é um controller. A tela carrega uma vez, e o JavaScript chama o próprio controller (`/{nome}/dados?year=…&months=…`) a cada mudança de filtro. O SQL e o cálculo das métricas ficam em métodos privados do controller. Os dashboards não compartilham camada entre si, então um dashboard pode mudar sem afetar os outros. A exceção é a Gestão de Eventos, que grava no banco e por isso tem camadas próprias em `lib/hotel/` (repositório, schema declarativo, cálculo e importador).

## Decisões técnicas

**Filtro de data que usa índice.** O filtro de ano e mês não vira `YEAR(col) = ?` no SQL. O PHP converte a seleção num intervalo literal (`col >= ? AND col <= ?`), e o banco consegue usar o índice da coluna. Quando a "coluna" é uma expressão calculada, o filtro antigo continua valendo.

**Cache em arquivo por endpoint.** A resposta JSON fica em cache com chave derivada dos filtros. As partes que não dependem do filtro do usuário têm chave fixa e prazo mais longo.

**Menos idas ao banco.** KPIs que leem a mesma tabela com o mesmo filtro saem de uma só consulta, com `SUM(CASE WHEN …)` ou `UNION ALL`.

**Join entre tipos diferentes.** Um `INT` comparado a um `VARCHAR` de outra collation faz o MySQL ignorar o índice, e o `EXPLAIN` mostra "Range checked for each record". A correção, sem mexer no schema, é `CONVERT(coluna USING latin1)` no lado inteiro do join.

**Regras de dados do CRM tratadas no SQL:**
- datas `0000-00-00` contam como vazias (`IS NOT NULL` sozinho deixa passar);
- a tabela de câmbio tem várias cotações por dia, então é deduplicada com `MAX` antes do join para não inflar somas;
- custos em BRL são convertidos para USD pela cotação do dia.

**Segurança:**
- todo valor de filtro entra por `bind_param`, inclusive listas (`IN (?,?,…)`);
- as páginas exigem Basic Auth, e o embed em site parceiro só é liberado quando três condições valem juntas: `?to=1`, cabeçalho `Sec-Fetch-Dest: iframe` (o navegador envia e o JavaScript não falsifica) e origem do `Referer` na lista permitida;
- o endpoint de debug `/sql/query` aceita só leitura: lista de comandos permitidos, uma instrução por requisição, rate limit e token comparado com `hash_equals`;
- o único módulo que grava no banco usa exclusão lógica, nunca `DELETE`.

## Como rodar

Requer PHP 8 com a extensão `mysqli`.

```bash
cp .env.example .env     # preencha DASH_AUTH_USER e DASH_AUTH_PASSWORD (login das páginas)
php -S 127.0.0.1:8000 index.php
```

Sem banco configurado, as telas dos dashboards e os dicionários abrem, mas os números não carregam. A página inicial e a Gestão de Eventos dependem do banco para abrir. Para ver uma tela completa sem banco, use o modo demonstração, que gera dados fictícios no próprio navegador:

- `http://127.0.0.1:8000/helenacastro?demo=1`
- `http://127.0.0.1:8000/helenacastro/jornada?demo=1`

O schema do banco (tabelas de leads, oportunidades, custos de marketing, câmbio etc.) não está incluído nesta cópia.

## Estrutura

```
controle/     controllers, um por dashboard
paginas/      views (fragmentos HTML/PHP) e dicionários de métricas
template/     um template por dashboard: cabeçalho, menu, tema
css/          bi-theme.css (design system) e hotel.css
js/           bi-dashboard.js (dropdowns, formatadores, dicionário) e os scripts do módulo de eventos
lib/hotel/    camadas do módulo Gestão de Eventos
mrphp.php     núcleo do framework
index.php     ponto de entrada
```
