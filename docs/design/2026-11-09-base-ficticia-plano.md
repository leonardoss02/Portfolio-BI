# Plano de implementação: base de dados fictícia

> **Para agentes:** SUB-SKILL OBRIGATÓRIA: use superpowers:subagent-driven-development (recomendado) ou superpowers:executing-plans para implementar este plano tarefa por tarefa. Os passos usam checkbox (`- [ ]`) para acompanhamento.

**Objetivo:** com `docker compose up`, qualquer pessoa vê todos os dashboards do Portfolio-BI com dados fictícios coerentes, que terminam no dia de hoje.

**Arquitetura:**
- Um gerador PHP (`database/seed/`) simula clientes percorrendo a jornada comercial. Ele monta as 18 tabelas em memória (`Base`) e grava tudo no MariaDB em lote.
- O Docker Compose sobe três serviços: `db` (MariaDB), `seed` (aplica o schema, gera os dados e encerra) e `app` (Apache), que só sobe depois do `seed`.
- Um teste de fumaça chama todos os endpoints e exige números maiores que zero.

**Stack:** PHP 8.3 (`php:8.3-apache`, `Random\Randomizer`, `mysqli`), MariaDB 11.4, Docker Compose, PowerShell + Edge headless (só para os prints).

**Spec:** [docs/design/2026-11-09-base-ficticia.md](2026-11-09-base-ficticia.md). Leia antes de começar. Os apêndices A a D da spec são a referência de nomes, fases, domínios de valores e colunas.

## Restrições globais

- PHP 8.3, sem Composer e sem npm. MariaDB 11.4. Imagens `php:8.3-apache` e `mariadb:11.4`, ambas multi-arquitetura.
- Arquivos, classes, funções e comentários em português. Tabelas novas seguem a convenção `campo_tabela`.
- Todas as tabelas usam `utf8mb4_unicode_ci`. Data vazia é `NULL`, nunca `0000-00-00`.
- A janela de dados vai de `2025-01-01` até hoje, no fuso `America/Sao_Paulo`. `--hoje` aceita no mínimo `2026-06-01`.
- A semente aleatória fica fixa em `2026` (no `config.php`). Toda aleatoriedade passa por `Aleatorio`; nunca chamar `rand()` ou `mt_rand()` diretamente.
- E-mails só no domínio `@exemplo.com`. A marca é "Acme EUA". Os nomes da equipe são exatamente os do código (Apêndice A da spec).
- Chaves de linha que começam com `_` são internas do gerador e não vão para o banco.
- Não criar scripts `.sh`. O `.gitattributes` força LF.
- Commits na branch `feature/base-ficticia`, com autor `Leonardo Silva <leonqt02@gmail.com>`. Toda mensagem termina com `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- Testes unitários rodam com `docker compose run --rm --no-deps seed php tests/rodar.php [arquivo]` (antes da Tarefa 2, com `docker run`; veja a Tarefa 1).
- O teste de fumaça roda com o app em modo DEV:

  ```bash
  APP_ENV_FILE=./docker/app.dev.env docker compose up -d --build
  docker compose exec app php tests/smoke.php
  ```

## Foco de revisão

Situações que a spec implica e que um usuário real vai encontrar. Cada uma tem teste ou verificação na tarefa dona:

1. **Porta 8000 ou 3307 já ocupada na máquina da pessoa.** Esperado: `APP_PORTA` e `DB_PORTA` trocam as portas sem editar arquivo. É a Tarefa 2, passo de verificação de portas.
2. **Rodar `docker compose up` de novo com os containers já existentes.** Esperado: o seed roda outra vez, recria tudo sem erro de "tabela já existe", e os dados terminam no novo "hoje". Tarefas 2 e 20.
3. **Clone no Windows com `core.autocrlf=true`, que deixa `.env` e `.sql` com CRLF.** Esperado: tudo funciona. Tarefa 2, com o teste `lerEnv aceita CRLF` e o `.gitattributes`.
4. **Uma checagem de coerência falhar.** Esperado: o `app` não sobe e a saída diz qual checagem falhou e como recriar a base. Tarefa 17, com o teste `falha de checagem encerra com código 1 e mensagem`.
5. **Abrir o projeto no dia 1º do mês.** As telas por vendedor, a do Atendimento e a do ConsultaCom2 filtram "do dia 1º até hoje". Esperado: mesmo assim mostram números maiores que zero. Tarefas 9, 13 e 16, com testes usando `--hoje=2026-12-01`.

---

### Tarefa 0: instalar o Docker Desktop (feita pelo usuário)

Esta máquina não tem Docker nem PHP. O resto do plano depende do Docker.

- [ ] **Passo 1:** instalar. No PowerShell: `winget install -e --id Docker.DockerDesktop`, aceitar o UAC e reiniciar se o instalador pedir. Abrir o Docker Desktop uma vez e aceitar os termos.
- [ ] **Passo 2:** verificar com `docker compose version` e `docker run --rm hello-world`.
  Esperado: a versão aparece e a mensagem "Hello from Docker!" é impressa.

### Tarefa 1: runner de testes e núcleo do gerador

**Arquivos:**
- Criar:
  - `tests/rodar.php`
  - `tests/lib/afirmar.php`
  - `database/seed/src/Aleatorio.php`
  - `database/seed/src/Calendario.php`
  - `database/seed/src/Base.php`
  - `database/seed/config.php` (apenas as chaves `semente`, `inicio`, `escala` e `calendario` por enquanto)
- Testes: `tests/seed/AleatorioTeste.php`, `tests/seed/CalendarioTeste.php`, `tests/seed/BaseTeste.php`

**Interfaces produzidas:**

```php
// tests/lib/afirmar.php
function afirmar(bool $condicao, string $mensagem): void          // lança FalhaDeTeste
function afirmarIgual(mixed $esperado, mixed $obtido, string $mensagem = ''): void
function afirmarEntre(float $min, float $max, float $obtido, string $mensagem = ''): void
```

Cada `tests/**/*Teste.php` faz `return ['nome do teste' => function () { ... }, ...];`.

O `tests/rodar.php [arquivos...]` roda os arquivos indicados ou, sem argumento, todos os `tests/seed/*Teste.php` e `tests/estaticos/*Teste.php`. Ele imprime `✔ arquivo › nome` ou `✘ arquivo › nome — mensagem`, e sai com código 1 se algum teste falhar.

```php
final class Aleatorio {
    public function __construct(int $semente)                     // Random\Randomizer(new Random\Engine\Mt19937($semente))
    public function inteiro(int $min, int $max): int
    public function decimal(float $min, float $max, int $casas = 2): float
    public function chance(float $p): bool                        // p em [0, 1]
    public function um(array $itens): mixed
    public function ponderado(array $pesos): int|string           // ['a' => 3, 'b' => 1]
    public function normal(float $media, float $desvio): float     // Box-Muller
}

final class Calendario {
    public function __construct(DateTimeImmutable $inicio, DateTimeImmutable $hoje, array $config)
    public function hoje(): DateTimeImmutable
    public function dias(): Generator                              // DateTimeImmutable de $inicio até $hoje, inclusive
    public function peso(DateTimeImmutable $dia): float
    public function momento(DateTimeImmutable $dia, Aleatorio $s, int $horaMin = 8, int $horaMax = 21): DateTimeImmutable
}

final class Base {
    public function inserir(string $tabela, array $linha): int     // define "id_{$tabela}" sequencial a partir de 1 e devolve o id
    public function atualizar(string $tabela, int $id, array $campos): void
    public function linhas(string $tabela): array                  // [id => linha]
    public function linha(string $tabela, int $id): array
    public function total(string $tabela): int
    public function novoIdBitrix(string $serie): string            // séries: deal 100001+, lead 500001+, contato 300001+, orcamento 70001+, conversa 1+, clienteQb 4001+
}
```

O `peso()` multiplica três fatores, todos lidos de `config['calendario']`:
- **crescimento:** `1 + 0.35 * mesesDesdeInicio / 24`;
- **mês:** dezembro `0.75`, janeiro `0.80`, os demais `1.0`;
- **dia da semana:** sábado e domingo `0.45`.

- [ ] **Passo 1:** escrever os testes que falham.
  - `AleatorioTeste`: "mesma semente gera a mesma sequência" (duas instâncias com semente 2026, 20 chamadas de `inteiro(1, 1000)`, sequências iguais); "ponderado respeita pesos" (10.000 sorteios de `['a' => 3, 'b' => 1]` dão `a` entre 70% e 80%).
  - `CalendarioTeste`: "fim de semana pesa menos que dia útil" (2026-11-07, sábado, < 2026-11-09, segunda); "dezembro pesa menos que novembro"; "2026-06-10 pesa mais que 2025-06-10"; "dias() começa em 2025-01-01 e termina em hoje"; "momento() fica entre 08:00 e 21:59".
  - `BaseTeste`: "ids sequenciais por tabela"; "novoIdBitrix('deal') começa em 100001 e não repete"; "atualizar altera só os campos pedidos".
- [ ] **Passo 2:** rodar e confirmar a falha.
  Comando: `docker run --rm -v "${PWD}:/app" -w /app php:8.3-cli php tests/rodar.php`
  Esperado: falha com "Class \"Aleatorio\" not found".
- [ ] **Passo 3:** implementar `afirmar.php`, `rodar.php`, `Aleatorio`, `Calendario` e `Base` com as assinaturas acima. O autoload fica simples: `rodar.php` faz `require` de todo `database/seed/src/*.php`.
- [ ] **Passo 4:** rodar de novo. Esperado: todos os testes com ✔ e código de saída 0.
- [ ] **Passo 5:** commit: `git add tests database/seed && git commit` com a mensagem "Adiciona runner de testes e núcleo do gerador".

### Tarefa 2: ambiente Docker, schema e esqueleto que sobe vazio

**Arquivos:**
- Criar:
  - `docker/php/Dockerfile`
  - `docker-compose.yml`
  - `docker/app.env`
  - `docker/app.dev.env`
  - `.gitattributes`
  - `database/schema.sql`
  - `database/seed/src/Banco.php`
  - `database/seed/src/Contexto.php`
  - `database/seed/src/Gerador.php`
  - `database/seed/gerar.php`
  - `tests/smoke.php`
  - `tests/smoke/endpoints.php`
- Modificar: `.gitignore` (acrescentar `!database/schema.sql` depois de `*.sql`)
- Teste: `tests/seed/BancoTeste.php`

**Interfaces produzidas:**

```php
final class Contexto {
    public array $campanhas = [];                                   // catálogo preenchido por Marketing (Tarefa 6)
    public function __construct(
        public readonly DateTimeImmutable $hoje,
        public readonly Aleatorio $sorteio,
        public readonly Calendario $calendario,
        public readonly array $config,
        public readonly Base $base,
    ) {}
    public static function criar(string $hoje, array $config): self  // valida hoje >= 2026-06-01
}

final class Gerador {
    public const ETAPAS = [];                                       // as tarefas seguintes acrescentam, em ordem: 'eventos' => [Eventos::class, 'gerar'], ...
    public static function rodar(Contexto $ctx, ?string $ate = null): void   // roda as etapas em ordem; para depois de $ate
}

final class Banco {
    public static function lerEnv(string $arquivo): array
    public static function conectar(array $env): mysqli             // DB_DEFAULT_*, utf8mb4, até 30 tentativas com 1 s de intervalo
    public static function aplicarSchema(mysqli $db, string $arquivoSql): void
    public static function gravar(mysqli $db, Base $base, int $lote = 500): void   // INSERT de várias linhas em transação, usando montarInsert
    public static function montarInsert(string $tabela, array $linhas): string     // função pura: ignora chaves com '_'; colunas = união das chaves; ausente vira NULL
    public static function limparCache(string $dirCache): int       // apaga *.json; devolve quantos apagou
}
```

O `gerar.php [--hoje=AAAA-MM-DD] [--escala=1.0] [--sem-banco]` segue esta ordem:
1. carrega o config e cria o `Contexto`;
2. roda `Gerador::rodar`;
3. roda `Verificacao::checar` (a partir da Tarefa 17);
4. com banco: `conectar`, `aplicarSchema`, `gravar` e `limparCache('.mrphp/cache')`;
5. imprime o tempo total.

O `tests/smoke/endpoints.php` devolve uma lista de entradas neste formato:

```php
[
    'url'    => '/executive/dados?year={ano}&months={mes}',
    'tipo'   => 'json' | 'html',
    'exigir' => ['caminho.no.json' => '>0', ...],
]
```

Os placeholders são `{ano}`, `{mes}` (sem zero à esquerda), `{hoje}`, `{inicioMes}`, `{ha29dias}` e `{anoAnterior}`, todos calculados a partir de `--hoje`. Em `exigir`, o caminho é sempre a chave real que o método devolve para aquela métrica. Para descobri-la, leia o array passado ao `jsonOut` do método. Isso vale para todas as tarefas seguintes.

O `tests/smoke.php [--hoje=…] [--base=http://localhost] [--apenas=grupo]` faz Basic Auth com `demo:demo` e confere em cada entrada:
- HTTP 200;
- no caso de JSON: que decodifica, que não tem chave `error`/`erro` no topo e que cada caminho de `exigir` é verdadeiro.

Ele imprime `✔ url  chave=valor …` ou `✘ url — motivo` e sai com código 1 se houver falha.

**Decisões fixas:**
- **Dockerfile:**
  - `FROM php:8.3-apache`;
  - `docker-php-ext-install mysqli`;
  - `a2enmod rewrite`;
  - `AllowOverride All` em `/etc/apache2/apache2.conf`;
  - `date.timezone=America/Sao_Paulo` num `.ini`.
- **Compose:**
  - a imagem `portfolio-bi/php` é construída uma vez e usada por `seed` e `app`;
  - `db` roda em `tmpfs: /var/lib/mysql`, com `TZ=America/Sao_Paulo`, banco `acme_bi`, usuário `acme` e senha `acme`;
  - o healthcheck do `db` é `healthcheck.sh --connect --innodb_initialized`;
  - as portas são `${DB_PORTA:-3307}:3306` e `${APP_PORTA:-8000}:80`;
  - `seed` e `app` montam `./:/var/www/html` e `${APP_ENV_FILE:-./docker/app.env}:/var/www/html/.env:ro`;
  - o comando do `seed` é `php -d memory_limit=1024M database/seed/gerar.php`;
  - `app` usa `depends_on: seed: condition: service_completed_successfully`.
  - **Se o MariaDB não subir em tmpfs,** trocar por um volume nomeado `db-dados`. O seed recria tudo de qualquer jeito.
- **`app.env`:**
  - tem todas as chaves do `.env.example`;
  - `DB_DEFAULT_*` e `DB_2_*` apontam para `db`/`acme`/`acme`/`acme_bi`;
  - `APP_ENV=PROD`, `DISPLAY_ERRORS=false`, `TIMEZONE=America/Sao_Paulo`, `HASH_KEY=portfolio-demo`;
  - `DASH_AUTH_USER=demo`, `DASH_AUTH_PASSWORD=demo`.
  - O `app.dev.env` é igual, com `APP_ENV=DEV` e `DISPLAY_ERRORS=true`.
- **`.gitattributes`:** `* text=auto eol=lf` e `*.png binary`. Depois de criar, rodar `git add --renormalize .`.
- **`schema.sql`:**
  - `DROP TABLE IF EXISTS` seguido de `CREATE TABLE` para as 18 tabelas do Apêndice D da spec, com os tipos e índices da seção 5.2 e um comentário por tabela;
  - `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`.
- **`endpoints.php` nesta tarefa:**
  - todas as páginas HTML e todos os endpoints de dados de todos os controllers, menos o `biexplorer`, sem `exigir`;
  - a lista de rotas vem dos métodos públicos de cada `controle/*_controle.php`;
  - os parâmetros de cada endpoint são os do `fetch` da view correspondente; padrões: `year={ano}`, `year={ano}&months={mes}`, ou `data_inicio={inicioMes}&data_fim={hoje}` para os painéis por vendedor e para especialistas/analítico;
  - `helenacastro/dados` usa `data_inicio={ha29dias}`;
  - `financeiro/parcelas` e `helenacastro/trilha` ficam para as tarefas 12 e 14.

Passos:

- [ ] **Passo 1:** escrever os testes que falham em `BancoTeste`:
  - "lerEnv aceita CRLF, comentários e aspas" (`"A=1\r\n# x\r\nB=\"dois\"\r\n"` → `['A' => '1', 'B' => 'dois']`);
  - "montarInsert ignora chaves com _": a SQL gerada não contém `_interno`;
  - "montarInsert preenche coluna ausente com NULL": duas linhas com chaves diferentes geram as mesmas colunas, com `NULL` onde falta valor.
- [ ] **Passo 2:** rodar com `docker run` (como na Tarefa 1). Esperado: FAIL.
- [ ] **Passo 3:** implementar `Banco` (incluindo `montarInsert`), `Contexto`, `Gerador` (com `ETAPAS` vazio), `gerar.php` e `schema.sql`.
- [ ] **Passo 4:** rodar os testes unitários de novo. Esperado: PASS.
- [ ] **Passo 5:** escrever os arquivos do Docker, o `.gitattributes` e o `.gitignore`. Subir com `APP_ENV_FILE=./docker/app.dev.env docker compose up -d --build`.
  Esperado: o `seed` sai com código 0, `docker compose ps` mostra o `app` rodando, e `http://localhost:8000/` pede login e, com `demo:demo`, lista as 18 tabelas.
- [ ] **Passo 6:** escrever `tests/smoke.php` e `tests/smoke/endpoints.php`. Rodar `docker compose exec app php tests/smoke.php`.
  Esperado: todas as entradas com ✔. **Todo 500 aqui indica coluna ou tabela faltando no schema:** corrigir o `schema.sql` até zerar as falhas.
- [ ] **Passo 7:** verificar as portas (Foco 1). Rodar `docker compose down`, depois `APP_PORTA=8081 DB_PORTA=3308 docker compose up -d` e conferir que `curl -s -o /dev/null -w "%{http_code}" http://localhost:8081/` devolve `401`. Rodar `docker compose down` no fim.
- [ ] **Passo 8:** verificar a segunda subida (Foco 2). Rodar `docker compose up -d` duas vezes seguidas. Esperado: na segunda, o `seed` roda de novo e sai com 0, sem erro de tabela existente.
- [ ] **Passo 9:** verificar que o host ficou limpo. `git status` não pode mostrar `.env` criado na raiz. Se a montagem do `app.env` tiver criado o arquivo, aplicar a mitigação da spec (seção 10).
- [ ] **Passo 10:** commit com a mensagem "Adiciona ambiente Docker, schema e esqueleto do gerador".

### Tarefa 3: remover o BI Explorer e pequenos ajustes

**Arquivos:**
- Apagar: `controle/biexplorer_controle.php`, `paginas/biexplorer/`, `template/biexplorer.php`, `js/biexplorer.js`
- Modificar:
  - `controle/home_controle.php` (remover o método `biexplorer_controle`, por volta da linha 65)
  - `paginas/error/500.php:9` e `paginas/error/429.php:4` (`$prefixosApi = ['/sql/'];`)
  - os comentários em `js/bi-dashboard.js:535`, `lib/hotel/RepoJson.php:18`, `controle/hotel_controle.php:1070` e `js/hotel/core.js:5` (reescrever sem citar o BI Explorer)
  - `template/index.php:11` (remover o `<link>` de `css/app.css`)
  - `paginas/error/404.php` (página simples: título "Página não encontrada", link para `/`, usando o `css/bi-theme.css`)
- Teste: `tests/estaticos/BiExplorerTeste.php`

- [ ] **Passo 1:** escrever o teste que falha: "nenhum arquivo fora de docs/ cita biexplorer". Ele varre `controle/`, `paginas/`, `template/`, `js/`, `css/`, `lib/` e `README.md` com `stripos($conteudo, 'biexplorer') === false`. O README entra nesta checagem só a partir da Tarefa 19; até lá, excluir o README da varredura.
- [ ] **Passo 2:** rodar. Esperado: FAIL, listando os arquivos.
- [ ] **Passo 3:** aplicar as remoções e edições acima.
- [ ] **Passo 4:** rodar o teste unitário (PASS) e depois o smoke. Esperado: tudo ✔ e `http://localhost:8000/rota-inexistente` mostrando a página 404.
- [ ] **Passo 5:** commit com a mensagem "Remove o BI Explorer e corrige página 404 e CSS inexistente".

### Tarefa 4: equipe, clientes e leitura de constantes do código

**Arquivos:**
- Criar: `database/seed/src/ConstantesDoCodigo.php`, `database/seed/src/Pessoas.php`, `tests/seed/apoio.php`
- Modificar: `database/seed/config.php` (chaves `equipe`, `clientes`, `motivosDescarte`, `vistos`, `cidadesExtras`)
- Teste: `tests/seed/PessoasTeste.php`

**Interfaces produzidas:**

```php
final class ConstantesDoCodigo {
    public static function ler(string $controller, string $constante): mixed
    // faz require de mrphp.php e controle/{$controller}_controle.php (uma vez só) e lê via ReflectionClass
}

final class Pessoas {
    public static function equipe(array $config, string $papel): array
    public static function novoCliente(Contexto $ctx): array        // ['primeiroNome','sobrenome','nome','email','telefone']
}

// tests/seed/apoio.php
function contextoDeTeste(string $hoje = '2026-11-09', float $escala = 0.1): Contexto
function gerarAte(?string $etapa, string $hoje = '2026-11-09', float $escala = 0.1): Contexto  // null = todas as etapas; memoriza por (etapa, hoje, escala)
```

Os papéis de `config['equipe']` (valores do Apêndice A da spec) são:
- `especialistas` (6), `comercial2` (5), `ex_comercial2`, `helena` (`'Helena Castro'`);
- `cadeiras`, `comercial1_evento`, `comercial1_digital`, `comercial3`;
- `atendimento` (com `'Fernanda matos'`);
- `csm` (no formato `['271' => 'Simone Arantes', '453' => 'Patrícia Moura', '725' => 'Viviane Correia', '727' => 'Tânia Freitas']`);
- `csm_outros` (2 nomes com IDs `'300'` e `'301'`);
- `case_managers` (4), `advs` (6);
- `palestrantes` (Camila Duarte, Juliana Prado, Rafael Torres, Beatriz Lemos);
- `jornada_extra` (`'Sistema Admin'`), `venda_direta` (`'Venda Direta'`).

Os demais blocos de `config`:
- `clientes`: listas de 120 primeiros nomes e 120 sobrenomes brasileiros comuns.
- `motivosDescarte`: 18 textos.
- `vistos`: `['EB-2 NIW', 'EB-5', 'E-2', 'L-1', 'O-1']`.

- [ ] **Passo 1:** escrever os testes que falham:
  - "equipe bate com as constantes do código". Para cada vendedor, `ConstantesDoCodigo::ler($c, 'RESPONSAVEL')` está em `especialistas` ou é `helena`, com `$c` em `beatrizlemos`, `camiladuarte`, `julianaprado`, `rafaeltorres`, `helenacastro`. Também `ler('chatwoot', 'CW_TEAM') === atendimento`, `ler('consultacom2', 'EQUIPE')` contido em `comercial2 ∪ especialistas`, `ler('comercial', 'EQUIPE_CADEIRAS') === cadeiras` e `array_keys(ler('comercial4', 'LIGA_CSM')) === array_keys(csm)`, com cada nome completo de `csm` começando pelo primeiro nome da constante.
  - "novoCliente usa @exemplo.com e não repete e-mail em 5.000 chamadas".
  - "nenhum nome da equipe contém vírgula".
- [ ] **Passo 2:** rodar `docker compose run --rm --no-deps seed php tests/rodar.php tests/seed/PessoasTeste.php`. Esperado: FAIL.
- [ ] **Passo 3:** implementar `ConstantesDoCodigo`, `Pessoas` e `apoio.php`, e preencher o `config`. O e-mail segue o formato `{primeiro}.{sobrenome}{n}@exemplo.com`, sem acento e em minúsculas. O telefone segue `+55 (DD) 9XXXX-XXXX`, com o DDD tirado de uma lista fixa.
- [ ] **Passo 4:** rodar de novo. Esperado: PASS.
- [ ] **Passo 5:** commit com a mensagem "Adiciona equipe interna e gerador de clientes fictícios".

### Tarefa 5: eventos e câmbio

**Arquivos:**
- Criar: `database/seed/src/Eventos.php`
- Modificar: `Gerador::ETAPAS` (acrescentar `'eventos'`), `config.php` (bloco `eventos`), `tests/smoke/endpoints.php`
- Teste: `tests/seed/EventosTeste.php`

**Interfaces:** `Eventos::gerar(Contexto $ctx): void` preenche `evento` e `cambio`.

**Regras:**
- **Eventos presenciais ("On The Road"):** 1 por semana, sempre no sábado, de 2025-01-04 até hoje + 45 dias.
  - Cidade: sorteada das chaves de `lib/hotel/cidades-legado.php`, evitando repetir a mesma cidade em menos de 8 semanas.
  - Palestrante: rodízio entre os `palestrantes`.
  - `nome_evento`: "On The Road {Cidade}".
  - `nomeForm_evento`: "OTR {Cidade} {AAAA-MM-DD}" (único).
  - `local_evento`: "Hotel {sobrenome sorteado} {Cidade}".
  - `endereco_evento`: rua fictícia.
  - `status_evento = 1`, `deletado_evento = 0`.
- **Webinars:** 1 a cada 13 semanas, `nome_evento = 'Webinar'`, `nomeForm_evento` "WEB Imigração {AAAA-MM-DD}", cidade "Online".
- **"1:1":** 1 a cada 13 semanas, com deslocamento de 6 semanas em relação aos webinars, nome "1:1 {Cidade}" e form "INA {Cidade} {AAAA-MM-DD}".
- **Câmbio:** uma linha por dia corrido, de 2025-01-01 até hoje + 45 dias. Passeio aleatório entre 5.20 e 5.90, partindo de 5.45, com passo `normal(0, 0.02)` e o valor rebatido nos limites. Valor com 4 casas.

Passos:

- [ ] **Passo 1:** escrever os testes que falham:
  - "há eventos futuros entre 1 e 45 dias e pelo menos 2 passados";
  - "nomeForm_evento é único e começa com OTR, WEB ou INA";
  - "palestrante sempre é um dos 4 palestrantes";
  - "câmbio tem uma linha por dia sem buracos e fica entre 5.20 e 5.90".
- [ ] **Passo 2:** rodar. Esperado: FAIL.
- [ ] **Passo 3:** implementar `Eventos::gerar` e registrar a etapa.
- [ ] **Passo 4:** rodar os testes. Esperado: PASS.
- [ ] **Passo 5:** no smoke, exigir `eventos/dados` → quantidade de eventos `>0` e `eventos/preview` → 3 eventos (2 passados e 1 futuro). Use a chave que o método devolve para cada métrica, lendo o `jsonOut` do método. Subir de novo e rodar o smoke. Esperado: ✔.
- [ ] **Passo 6:** commit com a mensagem "Gera eventos presenciais e câmbio diário".

### Tarefa 6: marketing (campanhas e custos)

**Arquivos:**
- Criar: `database/seed/src/Marketing.php`
- Modificar: `Gerador::ETAPAS` (`'marketing'`), `config.php` (bloco `marketing`), smoke
- Teste: `tests/seed/MarketingTeste.php`

**Interfaces:**

```php
final class Marketing {
    public static function gerar(Contexto $ctx): void              // preenche $ctx->campanhas e a tabela custoMkt
    public static function campanhaPara(Contexto $ctx, DateTimeImmutable $dia, string $classe): ?array
}
```

Cada campanha do catálogo tem este formato:

```php
['nome' => string, 'classe' => 'evento'|'digital'|'organico'|'indicacao', 'form' => string, 'idEvento' => ?int,
 'inicio' => 'Y-m-d', 'fim' => 'Y-m-d', 'conjuntos' => [['nome' => string, 'anuncios' => [string, ...]], ...]]
```

**Regras:**
- **Campanhas de evento:** uma por evento (incluindo webinar e "1:1").
  - `nome = form = nomeForm_evento`.
  - Ativa de `data − 30 dias` até `data`.
  - 2 conjuntos ("Interesse imigração", "Público semelhante") com 1 anúncio cada ("Vídeo palestrante", "Carrossel depoimentos").
- **Digitais:** 4 campanhas sempre ativas: "Consulta Digital - Meta", "Consulta Digital - Google", "Remarketing Consulta", "EB-2 NIW Profissionais".
  - Cada uma com 2 conjuntos × 2 anúncios.
  - O form de todas é `consulta-digital`.
- **Orgânico e indicação** (sem custo): "ORG Site", "ORG Instagram", "Negado Cliente", "IND Clientes", "F&F", "MGM". Os forms são iguais aos nomes.
- **`custoMkt`:** uma linha por anúncio por dia ativo, até hoje.
  - `origem`: `meta` em metade das campanhas e `google` na outra.
  - `conta`: `meta` usa `'Dollar 1'` (alternando com `'Dollar 2'` por campanha) e `google` usa `'Conta BRL'`, em todos os anos. Isso satisfaz as duas regras de moeda do código.
  - `valor`: USD de 10 a 35 por dia por anúncio em campanhas de evento e de 15 a 70 nas digitais, nas contas Dollar. Nas contas BRL, o mesmo valor convertido pelo câmbio do dia.
  - `clicks`: de 20 a 140.
  - `identificador`: o form da campanha. Nas campanhas de evento, 10% das linhas ficam com `identificador` vazio (testa o fallback "campanha começa com OTR").
  - `nomeCampanha`, `nomeConjunto` e `nomeAnuncio` vêm do catálogo.

Passos:

- [ ] **Passo 1:** escrever os testes que falham:
  - "toda data de custoMkt em Conta BRL tem câmbio no mesmo dia";
  - "todo evento tem campanha com custo";
  - "campanhaPara('digital') sempre encontra campanha em qualquer dia da janela";
  - "volume de custoMkt na escala 1.0 fica entre 14.000 e 26.000 linhas" (estimar multiplicando a contagem da escala 0.1 por 10).
- [ ] **Passo 2:** rodar. Esperado: FAIL.
- [ ] **Passo 3:** implementar e registrar a etapa.
- [ ] **Passo 4:** rodar. Esperado: PASS.
- [ ] **Passo 5:** no smoke, exigir `marketing/dados` → custo `>0` e cliques `>0`, e `executive/dados` → custo de marketing `>0`. Subir e rodar. Esperado: ✔.
- [ ] **Passo 6:** commit com a mensagem "Gera campanhas e custos de marketing".

### Tarefa 7: leads e contatos

**Arquivos:**
- Criar: `database/seed/src/Leads.php`
- Modificar: `Gerador::ETAPAS` (`'leads'`), `config.php` (blocos `volumes.leadsPorDia` e `taxas.lead`), smoke
- Teste: `tests/seed/LeadsTeste.php`

**Interfaces:** `Leads::gerar(Contexto $ctx): void` preenche `contactV2` e `leadV2`. Cada linha de `leadV2` leva estas chaves internas: `_canal`, `_idContato` (`idBitrix_contactV2`), `_campanha` (o nome) e `_dia` (`Y-m-d`).

**Regras:**
- **Volume diário:** `round(config.volumes.leadsPorDia × calendario.peso(dia) × escala)`, com `leadsPorDia = 36` como ponto de partida. Ajuste para cair entre 20 e 30 mil leads na escala 1.0.
- **Canal:** evento 45%, digital 40%, orgânico 8%, indicação 7%. Os leads de evento só existem quando há uma campanha de evento ativa no dia; sem campanha, o lead vai para digital.
- **Campos derivados da campanha:**
  - `utmCampaign`, `utmContent` e `utmTerm`: campanha, conjunto e anúncio sorteados da campanha;
  - `formPreenchido`: o form da campanha;
  - `idEvento_leadV2`: o `id_evento` da campanha, como string;
  - `tipoLead`: em evento, `OTR` (90%) ou `OTW`; em digital, `Consulta` (70%), `Venda direta`, `B2B`, vazio (3%) ou NULL (3%); em indicação, `Indicação`.
- **Qualificação e descarte:**
  - `qualificado`: `'Sim'` com 45% de chance, senão `'Não'`;
  - 25% dos não qualificados recebem `etapa = 'Lead Descartado'` e um motivo de descarte.
- **Responsável:**
  - lead de evento: um dos 3 da equipe `cadeiras`;
  - lead digital: `helena` (25%) ou `comercial1_digital`;
  - outros canais: `jornada_extra`.
- **Afiliado:** 8% dos leads recebem `idAfiliado` no formato `AF{3 dígitos}`.
- **Perfil:**
  - `profissao`: lista de 12 profissões, mais `'-'` em 5% e vazio em 5%;
  - `tempoExp`: `'1 a 3 anos'`, `'3 a 5 anos'`, `'5 a 10 anos'`, `'10 anos ou mais'`;
  - `formacaoAcademica`: 5 níveis, vazio em 4%.
- **Datas e contato:** `dataUltForm` recebe `calendario.momento(dia)`. Um contato por lead.

Passos:

- [ ] **Passo 1:** escrever os testes que falham:
  - "leads entre 20.000 e 30.000 na escala 1.0" (estimar a partir da escala 0.1);
  - "proporção de canais dentro de ±5 pontos";
  - "qualificados entre 40% e 50%";
  - "todo lead de evento tem idEvento e form que começa com OTR, WEB ou INA";
  - "idBitrix_leadV2 é único e só tem dígitos";
  - "existem leads de afiliado, leads descartados e tipoLead vazio e nulo".
- [ ] **Passo 2:** rodar. Esperado: FAIL.
- [ ] **Passo 3:** implementar e registrar a etapa.
- [ ] **Passo 4:** rodar. Esperado: PASS.
- [ ] **Passo 5:** no smoke, exigir:
  - `executive/dados` → leads e qualificados `>0`;
  - `credenciados/dados` → leads `>0`;
  - `marketing/descartesDados` → Com1 `>0`;
  - `marketing/desempenhoDados` → lista de profissões não vazia;
  - `comercial/cadeirasDados` → qualificados `>0`.
  Rodar. Esperado: ✔.
- [ ] **Passo 6:** commit com a mensagem "Gera leads e contatos".

### Tarefa 8: venda de cadeira e de consulta (C9, funil de evento, C11)

**Arquivos:**
- Criar: `database/seed/src/VendaConsulta.php`
- Modificar: `Gerador::ETAPAS` (`'vendaConsulta'`), `config.php` (`taxas.venda`, `valores`), smoke
- Teste: `tests/seed/VendaConsultaTeste.php`

**Interfaces:** `VendaConsulta::gerar(Contexto $ctx): void` insere cards em `dealV2`. Todo card leva:
- copiados do lead: `contactId_dealV2`, `nome`, `email`, `telefone`, `tipoLead`, `formPreenchido`, `utm*`, `idEvento_dealV2`, `idAfiliado`, `profissao`, `formacaoAcademica`;
- `criadoEm` e `dataUltForm`;
- `fase_dealV2` não vazia;
- as chaves internas `_idLead` e `_canal`.

**Regras** (as fases estão no Apêndice B.1 da spec):
- **Cadeira:** qualificado de evento compra cadeira com 30% de chance.
  - O card é `C9:WON`.
  - `dataVenda`: entre a data do lead e a data do evento, até hoje.
  - `valorVenda = 100`, `valorVendaDolar = 100 / câmbio`.
  - `responsavel`: o responsável do lead (80%) ou `venda_direta`.
  - 5% das cadeiras vão para a `helena`.
  - Eventos futuros já têm cadeiras vendidas.
- **Check-in:** quem tem cadeira de evento já passado faz check-in com 70% de chance.
  - Gera **um** card no funil de evento, sem prefixo: `checkin = '1'`, `dataEvento = data_evento`, `valorVenda = 100` em 90% dos casos.
  - `responsavel`: um dos `comercial1_evento`.
  - Com 35% de chance a consulta é vendida no evento: `faseId = 'WON'` e `dataVenda` = data do evento, com `valorVendaDolar = 690 / câmbio`. Nos outros casos, `faseId` sorteado entre `LOSE`, `FINAL_INVOICE`, `UC_C07YVI` e `NEW`.
- **Consulta digital:** qualificado digital, orgânico ou de indicação compra com 22% de chance.
  - O card é `C11:WON`, com `dataVenda` = lead + 0 a 10 dias, até hoje.
  - `responsavel`: o do lead (`helena` ou `comercial1_digital`).
  - `valorVendaDolar = 690 / câmbio`.
- **Helena Castro:** os cards dela usam exatamente `'C9:WON'` e `'C11:WON'`. A collation case-insensitive cobre o `'C9:Won'` do código.

Passos:

- [ ] **Passo 1:** escrever os testes que falham:
  - "todo card tem contactId de um contato existente e fase não vazia";
  - "check-in só em cards sem prefixo e de evento passado";
  - "um único card do funil de evento por contato e evento";
  - "consultas vendidas: WON e C11:WON têm dataVenda";
  - "taxa cadeira/qualificado de evento entre 26% e 34%";
  - "consultas vendidas entre 1.600 e 2.600 na escala 1.0" (estimar a partir da escala 0.1);
  - "helena tem pelo menos 1 cadeira e 1 consulta nos últimos 30 dias".
- [ ] **Passo 2:** rodar. Esperado: FAIL.
- [ ] **Passo 3:** implementar e registrar a etapa.
- [ ] **Passo 4:** rodar. Esperado: PASS.
- [ ] **Passo 5:** no smoke, exigir:
  - `executive/dados` → cadeiras e consultas `>0`;
  - `eventos/dados` → cadeiras, check-ins e consultas vendidas `>0`;
  - `comercial/comercial1Dados` → cad e cv `>0`;
  - `helenacastro/dados` → leads, consultas vendidas e ingressos vendidos `>0`;
  - `{camiladuarte,julianaprado,rafaeltorres}/eventosDados` → ingressos `>0`.
  Rodar. Esperado: ✔.
- [ ] **Passo 6:** commit com a mensagem "Gera vendas de cadeira e de consulta".

### Tarefa 9: Comercial 2 (C5), retenção (C1) e combos

**Arquivos:**
- Criar: `database/seed/src/Comercial2.php`
- Modificar: `Gerador::ETAPAS` (`'comercial2'`), `config.php` (`taxas.c5`, `taxas.c1`, `valores.combo`), smoke
- Teste: `tests/seed/Comercial2Teste.php`

**Interfaces:** `Comercial2::gerar(Contexto $ctx): void` cria um card C5 para cada consulta vendida (`WON` ou `C11:WON`). Ele também cria os cards C1 e alguns C29/C31.

**Regras do card C5:**
- Copia contato, origem e evento do card de venda.
- `responsavel`: sorteado entre `comercial2 ∪ especialistas`, mais 3% para `ex_comercial2`.

**Etapas do C5**, sempre interrompidas no que passar de hoje. A etapa que o card alcançou define `faseId` e `fase`.
1. Sem agendamento (5% ficam assim): `C5:NEW` / "A agendar".
2. `dataConsultaAgendada` = venda + 1 a 14 dias. Até 30 dias no futuro é permitido.
3. Realizada com 85% de chance:
   - `dataConsultaRealizada` = agendada + 0 a 3 dias;
   - em 8% dos casos cai no mês seguinte ao agendado;
   - sem realização: `C5:EXECUTING` / "Consulta agendada".
4. Sinal: 70% das realizadas recebem `dataVenda` = realizada + 0 a 5 dias.
5. FP: 65% das realizadas recebem `dataFpAgendado` = realizada + 3 a 15 dias. Destas, 85% recebem `dataFpRealizado` = agendado + 0 a 5 dias.
6. Desfecho depois do FP realizado:
   - 50% combo: `C5:WON` / "Contrato assinado", `dataContratoComboAssinado` = FP + 2 a 20 dias, `valorComboLiquido` normal(20000, 2500) limitado a 15.000–25.000, `vistoEscolhido`;
   - 10%: `C5:UC_0Q30H1` / "Contrato enviado";
   - 25% perdido: `C5:LOSE`, com `fase` entre "Comercial 3" (60%), "Distrato" e "Lead sem qualificação", `motivoDescarteComercial2` e `dataUltForm` = data do FP;
   - o restante fica aguardando: "FP realizado".
7. Distrato depois do combo: 5% dos combos viram `C5:UC_CL1DBM` ou `C5:UC_AQ2QYT` / "Distrato", mantendo a data de contrato. Mais 2% dos cards com FP realizado, `dataVenda` e sem contrato também viram distrato (alimentam o *churn* de FP).
8. Serasa: 70% dos cards com FP realizado recebem `serasaRange` (A–G), `serasaScore` (300–950), `statusCpf`, `nomeSujo`, `socioEmpresaNegativa`, `valorTotalDividas` e `valorCartorioTotal` (BRL).
9. Perdidos sem consulta realizada também recebem `motivoDescarteComercial2` (10% dos não realizados).

**Retenção (C1):** 30% dos C5 perdidos como "Comercial 3" ganham um card C1.
- `responsavel`: um dos `comercial3`; `criadoEm` = perda + 1 a 5 dias.
- `dataConsultaAgendada` em 80% dos cards e `dataConsultaRealizada` em 60%.
- 20% fecham `C1:WON`, com data de contrato e `valorComboLiquido`.
- Os demais ficam `C1:LOSE` com `motivoDescarte_dealV2` (preenchido) e `dataUltForm`, ou `C1:NEW`.

**C29 e C31:** 4% dos leads de indicação e B2B geram um card `C29:WON` ou `C31:WON` direto, com data de contrato e valor de combo. Esses cards **nunca** recebem `dataFpRealizado`.

**Garantia de dia 1º (Foco 5).** Depois de gerar tudo, para cada um dos 4 vendedores do `comercial2` com painel, garantir no intervalo de 1º do mês até hoje pelo menos 1 card C5 realizado e 1 agendado e não realizado. Se faltar, ajustar as datas de um card existente desse vendedor para dentro do intervalo.

Passos:

- [ ] **Passo 1:** escrever os testes que falham:
  - "todo C5 tem fase preenchida";
  - "realizadas ≤ vendidas e combos ≤ realizadas, por canal (evento/digital)";
  - "nenhum C31 tem dataFpRealizado";
  - "existem C5:UC_0Q30H1, distratos com e sem contrato, as 3 fases excluídas, e realizadas em mês diferente do agendado";
  - "existem C1:WON e C1:LOSE com motivoDescarte";
  - "dia 1º: com hoje=2026-12-01, cada vendedor com painel tem ≥1 realizada e ≥1 agendada não realizada em 2026-12-01" (use `gerarAte('comercial2', '2026-12-01')`);
  - "combos entre 380 e 600 na escala 1.0" (estimar a partir da escala 0.1).
- [ ] **Passo 2:** rodar. Esperado: FAIL.
- [ ] **Passo 3:** implementar e registrar a etapa.
- [ ] **Passo 4:** rodar. Esperado: PASS.
- [ ] **Passo 5:** no smoke, exigir:
  - `executive/dados` → combos e faturamento `>0`;
  - `executive/especialistasDados` → realizadas, FP vendido, contrato enviado, combos e distrato `>0`;
  - `executive/analiticoDados` → realizadas `>0`;
  - `executive/serasaDados` → consultados `>0`;
  - `comercial/dados` → combos de evento, combos digitais e retenção `>0`;
  - `comercial/yoyDados` → os dois períodos `>0`;
  - `comercial/comercial2Dados` e `comercial/comercial3Dados` → `>0`;
  - `marketing/descartesDados` → Com2 e Com3 `>0`;
  - `eventos/dados` → consultas realizadas, FP vendido, combos e faturamento `>0`;
  - `credenciados/dados` → consultas realizadas e combos `>0`;
  - `consultacom2/dados` e `consultacom2/dadoseventos` → linhas `>0`;
  - os 4 vendedores em `dados` → agendadas, realizadas, a realizar e combos `>0`.
  Rodar. Esperado: ✔.
- [ ] **Passo 6:** commit com a mensagem "Gera o funil do Comercial 2, retenção e combos".

### Tarefa 10: Dentistas (C33)

**Arquivos:**
- Criar: `database/seed/src/Dentistas.php`
- Modificar: `Gerador::ETAPAS` (`'dentistas'`), `config.php` (`dentistas`), smoke
- Teste: `tests/seed/DentistasTeste.php`

**Interfaces:** `Dentistas::gerar(Contexto $ctx): void` insere os cards C33 (sem `leadV2`).

**Regras:**
- De 2026-08-31 até hoje, 4 cards por dia útil × `peso` × `escala`.
- `dentistaEvento`: "Encontro de Dentistas {Cidade}" (6 cidades).
- `utmTerm`: 4 anúncios.
- `profissao`: "Cirurgião-dentista", "Ortodontista" ou "Implantodontista".
- `formacaoAcademica`: dos 5 níveis.
- `dataUltForm` = `criadoEm`.
- `checkin = '1'` em 60% dos cards.
- `dataConsultaRealizada` em 30%.
- 10% viram `C33:WON` com data de contrato. O resto fica `C33:NEW`.
- Responsável: `especialistas`.

Passos:

- [ ] **Passo 1:** escrever os testes que falham: "nenhum C33 antes de 2026-08-31"; "existem check-ins, consultas realizadas e C33:WON com contrato".
- [ ] **Passo 2:** rodar. Esperado: FAIL.
- [ ] **Passo 3:** implementar e registrar a etapa.
- [ ] **Passo 4:** rodar. Esperado: PASS.
- [ ] **Passo 5:** no smoke, exigir `dentistas/dados` → leads, confirmados, consultas e contratos `>0`, e listas de filtro não vazias. Rodar. Esperado: ✔.
- [ ] **Passo 6:** commit com a mensagem "Gera o segmento Dentistas".

### Tarefa 11: pós-venda, Jurídico e campanha de metas (C3, taskV2, orçamentos)

**Arquivos:**
- Criar: `database/seed/src/PosVenda.php`
- Modificar: `Gerador::ETAPAS` (`'posVenda'`), `config.php` (`posVenda`, `liga`), smoke
- Teste: `tests/seed/PosVendaTeste.php`

**Interfaces:** `PosVenda::gerar(Contexto $ctx): void` preenche `dealV2` (cards C3), `taskV2`, `orcamentoV2` e `itemOrcamentoV2`. Lê os produtos, os IDs de status e `LIGA_INICIO` com `ConstantesDoCodigo::ler('comercial4', …)`.

**Card C3:** todo combo (`C5:WON`, `C1:WON`, `C29:WON`, `C31:WON`, inclusive os que viraram distrato) gera um card C3.
- `criadoEm` = contrato + 1 a 7 dias, até hoje.
- Copia contato, nome, e-mail, Serasa e `dataFpRealizado` do card de origem. Exceção: cards vindos de C31 não recebem `dataFpRealizado`.
- `advogadoAtendimento`: um dos `case_managers` (95%) ou vazio. `advMl1`, `advMl2` e `advMl3` vêm dos `advs`.
- `valorAssessoria`: R$ 8.000 a 30.000.

**Etapa do C3 pela idade do card (dias desde `criadoEm`):**

| Idade | `faseId` / `fase` |
|---|---|
| até 10 dias | `C3:NEW` / "Arquitetura a agendar" |
| 11 a 25 | `C3:PREPARATION` / "Arquitetura agendada" |
| 26 a 60 | `C3:UC_ML1` / "Milestone 1" |
| 61 a 100 | "Milestone 2" |
| 101 a 140 | "Milestone 3" |
| 141 a 200 | "Aplicados" |
| mais de 200 | sorteio entre "RFE/Noid Recebida", "RFE/Noid Respondida", "RFE Ajuste de status", "Aprovado USCIS" e "NVC" |

Distribuição extra: 3% "Distrato", 2% "Inadimplente" e 2% "Legado". Os `faseId` livres usam o padrão `C3:UC_*`.

**CSM e carteira da Liga:**
- **Clientes legados:** se houver menos de `config.liga.carteira = 430` cards C3 criados antes de `LIGA_INICIO`, completar a diferença com cards C3 "legados".
  - `criadoEm` sorteado em 2024, com contato e cliente novos.
  - Fase pela mesma regra de idade.
  - Sem card de venda de origem e sem faturas.
  - O comentário no código explica que são clientes de antes da janela de dados (spec, seção 6.3).
- Os C3 criados antes de `LIGA_INICIO` são ordenados por `criadoEm`. Os 430 mais recentes recebem um CSM da Liga (`responsavelComercial4Id` em `271`, `453`, `725` ou `727`, distribuídos igualmente, e `responsavelComercial4` com o nome completo).
- Os demais cards recebem `csm_outros`.
- **`dataJson_dealV2` da carteira:** JSON com as 10 chaves de `LIGA_PRODUTOS`, cada uma com um ID de opção sorteado de `LIGA_STATUS_IDS[status]`.
  - 390 cards ficam totalmente mapeados: nenhum `iniciar`. Os status seguem os pesos fechada 12, em_negociacao 10, declinada 8, orcamento_parceiro 4, abordagem 20, sem_oportunidade 30, interesse_futuro 16.
  - Os outros 40 cards têm de 1 a 3 chaves com `iniciar`.
  - Cards fora da carteira: `dataJson = NULL`.

**Tarefas (`taskV2`):** 35% dos C3 com fase do Jurídico recebem de 1 a 3 tarefas.
- `priority`: 2 em 30% das tarefas, 1 nas outras.
- `status`: `Concluída` (60%), `Pendente` ou `Em andamento`.

**Orçamentos:**
- Carteira da Liga: cada card tem de 0 a 3 orçamentos, criados de `LIGA_INICIO` até hoje.
  - `etapa`/`etapaId`: `Vendido`/`APPROVED` (45%), `Em negociação`/`NEGOTIATION` ou `Perdido`/`DECLINED`.
  - Vendido recebe `dataVenda` (DATE) entre a criação e hoje. Os demais ficam com `dataVenda` NULL.
  - Pelo menos 8 vendas caem nos últimos 7 dias.
- Outros C3 de 2026: de 0 a 1 orçamento nas mesmas regras.
- `oportunidade`: R$ 2.000 a 15.000. `moedaId`: `BRL` (80%) ou `USD`.
- Cada orçamento tem 1 ou 2 itens. `nomeOriginalProduto`: um produto de `LIGA_PRODUTOS` (95%) ou "Visto de turismo" (fora do mapeamento).
- Ligações: `dealId` = `idBitrix_dealV2` do C3; `contactId` = contato do card, ou vazio em 5%; `nome` = nome do cliente.

Passos:

- [ ] **Passo 1:** escrever os testes que falham:
  - "todo combo tem exatamente um C3";
  - "cards legados só existem se faltarem para a carteira, e todos têm criadoEm em 2024";
  - "carteira da Liga: 430 cards com CSM 271/453/725/727 e criadoEm < LIGA_INICIO";
  - "390 cards da carteira estão totalmente mapeados";
  - "dataJson é JSON válido ou NULL";
  - "vendidos têm etapa Vendido, etapaId APPROVED e dataVenda; não vendidos têm dataVenda NULL";
  - "há vendas nos últimos 7 dias";
  - "há tarefa de prioridade 2 não concluída em caso do Jurídico".
- [ ] **Passo 2:** rodar. Esperado: FAIL.
- [ ] **Passo 3:** implementar e registrar a etapa.
- [ ] **Passo 4:** rodar. Esperado: PASS.
- [ ] **Passo 5:** no smoke, exigir:
  - `law/dados` → total e alta prioridade `>0`;
  - `law/prioridadeDados` → linhas `>0`;
  - `comercial4/dados` → clientes, a agendar, agendadas e vendas `>0`;
  - `comercial4/ligaDados` → carteira entre 400 e 460, mapeados entre 370 e 410, vendas da semana `>0`.
  Rodar. Esperado: ✔.
- [ ] **Passo 6:** commit com a mensagem "Gera pós-venda, Jurídico e campanha de metas do CS".

### Tarefa 12: Financeiro (faturas)

**Arquivos:**
- Criar: `database/seed/src/Financeiro.php`
- Modificar: `Gerador::ETAPAS` (`'financeiro'`), `config.php` (`financeiro`), smoke (acrescentar `financeiro/parcelas?customer={clienteQb}`, com o primeiro `customerQB` que tiver fatura no ano)
- Teste: `tests/seed/FinanceiroTeste.php`

**Interfaces:** `Financeiro::gerar(Contexto $ctx): void` preenche `invoice`.

**Regras:**
- **Faturas por combo:** cada combo (exceto distratos) gera uma entrada de 20% do `valorComboLiquido` mais de 3 a 10 parcelas mensais com o restante.
  - Vencimentos mensais a partir da data do contrato.
  - `customerQB`: um `novoIdBitrix('clienteQb')` por contato. `nomeQB`: nome do cliente.
  - `idQB`: sequencial em texto. `id_empresa = 1`.
  - `idDealBitrix`: o `idBitrix_dealV2` do combo.
- **Status, a partir do vencimento:**
  - vencida e paga (75% das vencidas): `status = 'Em dia'`, `ballancedQB = 0`, `dataQBPagamento` = vencimento + `normal(2, 5)` dias, com 10% pagando mais de 29 dias depois;
  - vencida e não paga: `Atrasado` (até 60 dias), `Inadimplente` (mais de 60 dias) ou `Congelado` (8%); `ballancedQB = amountQB`, ou metade em 10% dos casos, com pagamento parcial;
  - a vencer: `Em dia`, `ballancedQB = amountQB`, `dataQBPagamento` NULL.
- **Faturas avulsas:** 28% das faturas são avulsas, com `idDealBitrix` NULL e clientes próprios.

Passos:

- [ ] **Passo 1:** escrever os testes que falham:
  - "existem os 4 status";
  - "toda fatura paga tem saldo 0 e dataQBPagamento";
  - "há pagamentos com mais de 29 dias de atraso";
  - "entre 24% e 32% das faturas sem card";
  - "datas de fatura não têm hora".
- [ ] **Passo 2:** rodar. Esperado: FAIL.
- [ ] **Passo 3:** implementar e registrar a etapa.
- [ ] **Passo 4:** rodar. Esperado: PASS.
- [ ] **Passo 5:** no smoke, exigir `financeiro/dados` → recebido, a receber, atrasado e inadimplente `>0`, e `financeiro/parcelas` → linhas `>0`. Rodar. Esperado: ✔.
- [ ] **Passo 6:** commit com a mensagem "Gera faturas do Financeiro".

### Tarefa 13: Atendimento (Chatwoot)

**Arquivos:**
- Criar: `database/seed/src/Atendimento.php`
- Modificar: `Gerador::ETAPAS` (`'atendimento'`), `config.php` (`atendimento`), smoke
- Teste: `tests/seed/AtendimentoTeste.php`

**Interfaces:** `Atendimento::gerar(Contexto $ctx): void` preenche `conversaCW` e `msgCW`.

**Regras:**
- **Quem gera conversa:** 40% dos leads.
- **Campos da conversa:**
  - `idCw`: `novoIdBitrix('conversa')` convertido para inteiro.
  - `idLeadBitrix`: o `idBitrix_leadV2` do lead, como número.
  - `responsavel`: um dos 4 de `atendimento` (90%) ou "Carla Nunes", que fica fora do time e é filtrada pelo código.
  - `status`: `resolved` (55%), `open` (25%), `pending` ou `snoozed`.
  - `rotulos`: inclui `venda-ingresso` se o lead tem cadeira, `venda-consulta` se tem consulta vendida, e às vezes `lead-quente`. Separados por vírgula.
  - `canal`: `Channel::Whatsapp` (85%) ou `Channel::Instagram`.
  - `nomeRemetenteMeta`: nome do lead. `telefoneRemetenteMeta`: telefone do lead.
- **Horários:**
  - `criadoEm`: momento do lead + 0 a 120 minutos.
  - `primeiraRespostaEm`: `criadoEm` + 1 a 240 minutos. Fica NULL em 3% e recebe o sentinela `1969-12-31 21:00:00` em 2%.
  - `ultimaAtividadeEm`: `criadoEm` + 1 a 72 horas, até hoje.
- **Mensagens (`msgCW`):** de 2 a 10 por conversa, alternando `incoming` e `outgoing`, com algumas usando `'0'`/`'1'`. 75% das conversas têm pelo menos uma mensagem recebida.
- **Garantia de dia 1º (Foco 5):** pelo menos 5 conversas válidas do time em hoje.

Passos:

- [ ] **Passo 1:** escrever os testes que falham:
  - "toda conversa aponta para um lead existente";
  - "existem resposta nula, sentinela e os 4 status";
  - "primeiraRespostaEm ≥ criadoEm quando válida";
  - "dia 1º: com hoje=2026-12-01 há ≥5 conversas válidas do time em 2026-12-01".
- [ ] **Passo 2:** rodar. Esperado: FAIL.
- [ ] **Passo 3:** implementar e registrar a etapa.
- [ ] **Passo 4:** rodar. Esperado: PASS.
- [ ] **Passo 5:** no smoke, exigir:
  - `chatwoot/dados` → total, ingressos, consultas, abertas, resolvidas e TRM `>0`;
  - `chatwoot/serie` → não vazia;
  - `eventos/dados` → oportunidades `>0`;
  - `marketing/dados` → oportunidades `>0`.
  Rodar. Esperado: ✔.
- [ ] **Passo 6:** commit com a mensagem "Gera conversas e mensagens do Atendimento".

### Tarefa 14: Jornada do cliente (`lead_tag`)

**Arquivos:**
- Criar: `database/seed/src/Jornada.php`
- Modificar: `Gerador::ETAPAS` (`'jornada'`), `config.php` (`jornada`), smoke (acrescentar `helenacastro/trilha?id={leadComTags}`)
- Teste: `tests/seed/JornadaTeste.php`

**Interfaces:** `Jornada::gerar(Contexto $ctx): void` preenche `lead_tag`.

**Regras:**
- **Quem entra:** os leads com `responsavel` em `helena`, `jornada_extra` ou `comercial1_digital`, mais 30% dos leads de evento.
- **Sequência de tags:** `lead-criado` no momento do lead, depois a cadeia de Markov do modo demonstração (`paginas/helenacastro/jornada.php`, função `proximaDemo`), com as mesmas probabilidades e atrasos.
  - Se o lead **tem** cadeira ou consulta vendida, a cadeia é forçada até `carrinho-acessado` → `carrinho-pagamento` → `pagamento-confirmado`, com a confirmação no momento da venda.
  - Se **não tem**, a transição para `pagamento-confirmado` vira `carrinho-abandonado`.
  - Tags depois de hoje são descartadas.
- **`descricao`:** textos como os do demo ("Consulta de imigração · R$ 690,00", "Escolheu Pix", "Motivo: …").
- **Tag fora do catálogo:** 5% dos leads recebem `webinar-assistido`.
- **Leads só na jornada:** mais 3% de leads com `idBitrix_lead` novo, sem linha em `leadV2` e com `id_leadV2` NULL.
- **Campos:** `idBitrix_lead` (int) = `idBitrix_leadV2`; `id_leadV2` = id interno do lead; `dataCadastro` com hora.

Passos:

- [ ] **Passo 1:** escrever os testes que falham:
  - "todo dataCadastro tem hora";
  - "lead com venda termina em pagamento-confirmado";
  - "lead sem venda nunca tem pagamento-confirmado";
  - "existem leads só na jornada e tag fora do catálogo";
  - "abandono/carrinho entre 35% e 65%".
- [ ] **Passo 2:** rodar. Esperado: FAIL.
- [ ] **Passo 3:** implementar e registrar a etapa.
- [ ] **Passo 4:** rodar. Esperado: PASS.
- [ ] **Passo 5:** no smoke, exigir:
  - `helenacastro/jornadaDados` → leads, carrinho e pagos `>0`;
  - `helenacastro/jornadaResponsaveis` → ≥2 nomes;
  - `helenacastro/trilha` → tags `>0`.
  Rodar. Esperado: ✔.
- [ ] **Passo 6:** commit com a mensagem "Gera a jornada do cliente".

### Tarefa 15: Gestão de Eventos (`fornecedorEvento` e `custoEvento`)

**Arquivos:**
- Criar: `database/seed/src/Fornecedores.php`
- Modificar: `Gerador::ETAPAS` (`'fornecedores'`), `config.php` (`fornecedores`), smoke (páginas `/hotel*` com `contem` = nome de um hotel gerado)
- Teste: `tests/seed/FornecedoresTeste.php`

**Interfaces:** `Fornecedores::gerar(Contexto $ctx): void` preenche `fornecedorEvento` e `custoEvento`. As colunas e enums vêm de `lib/hotel/MapaFornecedor.php` e `lib/hotel/Schema.php`, lidos pela implementação para conferir nomes.

**Regras:**
- **Abrangência:** todo evento presencial (não webinar e não "1:1") ganha **uma** linha `categoria = 'hotel'`.
- **Hotel:**
  - `idEvento`; `data` = `data_evento`; `nome` = `local_evento`; `uf`; `capacidade` (80–300); `custo` (R$ 5.000–15.000, 2 casas; os filhos ficam entre R$ 300 e R$ 3.000 cada, para o ROI médio cair entre 2x e 6x);
  - `tipoPagamento`; `avaliacao` (3–5 em evento passado, 0 em futuro);
  - `situacaoContrato`: `assinado` em evento passado; `negociacao`, `aguardando` ou `assinado` em futuro; `cancelado` em 2%;
  - `dataAssinatura` = data − 20 a 60 dias; `nf`/`dataNf` e `oc`/`dataOc`/`numeroOc` em 90% dos passados; `quartoConfirmado`;
  - `statusEntrega`: `no_prazo` (80%), `atrasado` ou `problema`;
  - `responsavelAv`: `hotel` (70%) ou `terceirizado`.
- **Filhos, com o mesmo `idEvento`:**
  - equipamento: 1 ou 2, só quando terceirizado;
  - videomaker: 1;
  - transporte: de 1 a 3, modal `aviao` ou `onibus`;
  - frete: 1;
  - material: 1;
  - custoExtra: de 0 a 2;
  - cada um com `custo` > 0 e status do enum da sua categoria.
- **Datas futuras:** pelo menos 1 evento entre 0 e 7 dias à frente e 1 entre 8 e 30 dias à frente têm hotel.
- **Colunas comuns:** `idLegado = NULL`; `criadoEm` e `atualizadoEm` = data de assinatura; `deletado = 0`.
- **`custoEvento`:** uma linha por evento, com `id_evento`, `nome_custoEvento` = nome do evento, `identificador_custoEvento` = `nomeForm_evento`, `data_custoEvento` = data do evento, `valor_custoEvento` = soma dos `custo` do evento e `id_ordemCompra = 0`.
  - Eventos sem hotel (webinar e "1:1") não têm linha. O custo deles vem só do marketing.

Passos:

- [ ] **Passo 1:** escrever os testes que falham:
  - "um hotel por evento presencial";
  - "custoEvento.valor = soma dos custos do evento";
  - "custos com no máximo 2 casas";
  - "enums válidos por categoria";
  - "há hotel entre 0–7 e 8–30 dias à frente".
- [ ] **Passo 2:** rodar. Esperado: FAIL.
- [ ] **Passo 3:** implementar e registrar a etapa.
- [ ] **Passo 4:** rodar. Esperado: PASS.
- [ ] **Passo 5:** no smoke, exigir:
  - páginas `/hotel`, `/hotel/lista`, `/hotel/valortotal` e as demais com 200 e contendo um nome de hotel;
  - `eventos/dados` → custo físico `>0` e nenhum evento do ano com ROI nulo;
  - `executive/dados` → custo físico `>0`.
  Rodar. Esperado: ✔.
- [ ] **Passo 6:** commit com a mensagem "Gera fornecedores e custos físicos dos eventos".

### Tarefa 16: NPS e planilha de consultas no banco

**Arquivos:**
- Criar: `database/seed/src/Pesquisas.php`
- Modificar:
  - `Gerador::ETAPAS` (`'pesquisas'`), `config.php` (`nps`, `planilha`)
  - `controle/npseventos_controle.php`
  - `controle/consultacom2_controle.php`
  - `paginas/npseventos/dicionario.php` e `paginas/consultacom2/*.php` (trechos que citam "planilha do Google")
  - smoke
- Teste: `tests/seed/PesquisasTeste.php`

**Interfaces:** `Pesquisas::gerar(Contexto $ctx): void` preenche `npsEvento` e `planilhaConsulta`.

**Regras do gerador:**
- **`npsEvento`:** de 12 a 20 respostas por evento presencial passado.
  - `tipo`: `comprou` (80%) ou `nao_comprou`.
  - `respondidoEm`: data do evento + 0 a 3 dias, com hora. `cidade` = `cidade_evento`. `nome`: cliente fictício.
  - Notas, arredondadas para inteiro e limitadas a 0–10: `normal(9, 1)` para quem comprou e `normal(7, 1.8)` para quem não comprou.
  - Quem comprou recebe `atendimento` e `fatorFechamento`, sorteado entre 5 textos.
  - Quem não comprou recebe `motivoNaoFechou` (5 textos), `interessePosEvento` (os 4 valores da spec) e `chanceImigrar`.
- **`planilhaConsulta`:** cobre os cards C5 realizados no mês atual e no anterior por `ler('consultacom2', 'EQUIPE')`.
  - 85% deles entram na planilha. Destes, 90% com o e-mail igual ao do card e 10% com o e-mail digitado errado mas o mesmo nome (geram "OK (nome)").
  - Mais 3 linhas de pessoas que não existem no CRM.
  - `nomeComercial` = responsável do card; `dataConsulta` = data realizada; `obs` vazia ou um texto curto.
  - **Garantia de dia 1º (Foco 5):** pelo menos 1 linha com `dataConsulta` = hoje.

**Mudança no `npseventos_controle.php`:**
- `carregarPlanilhaComprou()` e `carregarPlanilhaNaoComprou()` passam a `return $this->carregarRespostas('comprou')` e `return $this->carregarRespostas('nao_comprou')`.
- Novo método `private function carregarRespostas(string $tipo): array`. Ele devolve `['linhas' => [...], 'indisponivel' => bool]`, com linhas **no mesmo formato** que `parsePlanilhaGenerica` produzia:
  - `timestamp`: `DateTime`;
  - `nome`, `cidade` e os textos: `string`;
  - as notas: `float|null`;
  - as mesmas chaves de hoje.
- `indisponivel` é true quando a consulta não devolve linhas.
- Remover `SHEET_*_CSV_URL`, `CSV_ESCAPE`, `carregarPlanilhaGenerica`, `desidratarLinhas`, `reidratarLinhas`, `baixarCsv`, `parsePlanilha*Csv`, `parsePlanilhaGenerica` e `parseTimestamp`, além das constantes de cache que só elas usavam.
- Manter `normalizarTexto`, usado pelos filtros, e atualizar o docblock do topo.

**Mudança no `consultacom2_controle.php`:**
- `carregarPlanilha()` lê `planilhaConsulta` e devolve `['linhas' => [...], 'indisponivel' => bool]`.
- As linhas usam as chaves `nome_cliente`, `nome_norm` (via `nomeNorm()`), `email`, `email_norm` (via `emailNorm()`), `data_consulta` (formatada como `d/m/Y`), `nome_comercial` e `obs`.
- Remover `SHEET_CSV_URL`, `baixarCsv`, `parsePlanilhaCsv` e o marcador de falha. Atualizar o comentário sobre a planilha vir "como texto e sem ano".

Passos:

- [ ] **Passo 1:** escrever os testes que falham:
  - "notas entre 0 e 10";
  - "planilha tem linhas que casam por e-mail, linhas que casam só pelo nome e linhas fora do CRM";
  - "dia 1º: com hoje=2026-12-01 a planilha tem linha de 2026-12-01".
- [ ] **Passo 2:** rodar. Esperado: FAIL.
- [ ] **Passo 3:** implementar o gerador e registrar a etapa.
- [ ] **Passo 4:** rodar. Esperado: PASS.
- [ ] **Passo 5:** escrever as exigências do smoke **antes** de mexer nos controllers:
  - `npseventos/dados` → respostas de quem comprou e de quem não comprou `>0`, médias entre 0 e 10;
  - `consultacom2/dadosplanilha` → linhas `>0` e `planilha_indisponivel === false`;
  - `consultacom2/dados` → existem linhas com status `OK`, `OK (nome)` e `Pendente`.
  Rodar. Esperado: FAIL nos dois controllers, que ainda tentam o Google.
- [ ] **Passo 6:** aplicar as mudanças nos dois controllers e nos dicionários.
- [ ] **Passo 7:** rodar o smoke. Esperado: ✔. Rodar também `grep -rn "docs.google.com" controle/`. Esperado: nada encontrado.
- [ ] **Passo 8:** commit com a mensagem "Move NPS e planilha de consultas do Google Sheets para o banco".

### Tarefa 17: verificação, resumo e cobertura completa do smoke

**Arquivos:**
- Criar: `database/seed/src/Verificacao.php`
- Modificar: `database/seed/gerar.php`, `tests/smoke/endpoints.php`
- Teste: `tests/seed/VerificacaoTeste.php`

**Interfaces:**

```php
final class Verificacao {
    public static function checar(Contexto $ctx): array             // mensagens de falha; vazio = ok
    public static function resumo(Base $base): string               // contagem por tabela + funil (leads → qualificados → consultas vendidas → realizadas → FP → combos)
}
```

**Checagens** (spec, seção 8.3):
- combos ≤ realizadas ≤ vendidas, por canal;
- `custoEvento` = soma dos fornecedores;
- toda data BRL de `custoMkt` tem câmbio;
- todo C5 tem `fase`;
- a equipe bate com as constantes do código (reaproveitar a lógica do `PessoasTeste`, movendo-a para `Verificacao`, e fazer o teste chamar a `Verificacao`);
- toda tabela do schema, exceto `deal`, tem pelo menos 1 linha.

**Saída do `gerar.php`** (Foco 4): em caso de falha, imprime `✘ <mensagem>` para cada uma, mais a linha "A base não foi gravada. Corrija o gerador ou rode `docker compose up --build` de novo.", e sai com código 1, antes de tocar no banco.

Passos:

- [ ] **Passo 1:** escrever os testes que falham:
  - "base completa passa em todas as checagens" (`gerarAte(null)` na escala 0.1);
  - "falha de checagem encerra com código 1 e mensagem": corromper um `custoEvento` na `Base`, rodar `checar()` e esperar exatamente 1 mensagem citando `custoEvento`; depois rodar `php database/seed/gerar.php --sem-banco --hoje=2026-11-09` com uma variável de ambiente `SEED_CORROMPER=custoEvento` (gancho só para teste, documentado no topo do `gerar.php`), esperando código 1 e `✘` na saída.
- [ ] **Passo 2:** rodar. Esperado: FAIL.
- [ ] **Passo 3:** implementar e chamar no `gerar.php`.
- [ ] **Passo 4:** rodar. Esperado: PASS.
- [ ] **Passo 5:** completar o smoke. Conferir linha a linha o Apêndice C da spec e garantir que cada métrica-chave tem `exigir`.
- [ ] **Passo 6:** medir o tempo. Rodar `docker compose up -d --build`; em seguida, `docker compose logs seed` precisa mostrar o resumo e o tempo total abaixo de 30 s. Se passar disso, aumentar o lote do `gravar` ou desligar `unique_checks` durante a carga.
- [ ] **Passo 7:** commit com a mensagem "Adiciona checagens de coerência e resumo do gerador".

### Tarefa 18: anos fixos no código

**Arquivos:**
- Modificar (SQL):
  - `controle/executive_controle.php`: linhas 1245, 1246, 1250, 1251, 1542, 1543, 1549, 1550, 1861, 1862, 1866 e 1867
  - `controle/marketing_controle.php`: linhas 1125–1131, 1180–1186, 1264–1270, 2036–2042 e 2741–2747
  - `controle/eventos_controle.php`: linhas 969–975
- Modificar (JS):
  - `js/bi-dashboard.js` (nova função)
  - todos os `const YEARS = [...]` em `paginas/**` (lista via `grep -rn "const YEARS" paginas`)
  - `paginas/law/index.php:769`, `paginas/law/prioridade.php:553`, `paginas/comercial4/index.php:844` e `paginas/credenciados/index.php:227` e `:229`
- Testes: `tests/estaticos/AnosTeste.php`, mais o grupo `envelhecimento` no smoke

**Interfaces produzidas:** `function anosDesde(primeiro)` em `js/bi-dashboard.js`, que devolve as strings de `primeiro` até o ano atual do navegador.

**Regras:**
- No SQL, `YEAR(…) = 2025` vira `<= 2025` e `YEAR(…) = 2026` vira `>= 2026`. Nada mais muda na expressão.
- Nas views, `const YEARS = anosDesde(2025);`.
- `select(['2026'])` vira `select([String(new Date().getFullYear())])`.
- O `year: '2026'` do Credenciados vira o ano corrente.
- Não mexer em `LIGA_*`, `CICLO_EPOCH`, nem no início do segmento Dentistas.

Passos:

- [ ] **Passo 1:** escrever os testes que falham:
  - `AnosTeste`: "nenhuma view tem lista fixa de anos" (nenhum arquivo em `paginas/` casa com `/const\s+YEARS\s*=\s*\[/`); "nenhum ano padrão fixo" (nada casa com `/select\(\[\s*'20\d\d'\s*\]\)/` ou `/year:\s*'20\d\d'/`); "nenhum `YEAR(cm.data_custoMkt) = 202`" em `controle/`; "bi-dashboard.js define anosDesde".
  - Smoke, grupo `envelhecimento` (só roda com `--apenas=envelhecimento`):
    - `executive/dados?year=2027&months=1,2,3` → custo de marketing `>0`;
    - `marketing/dados?year=2027` → custo `>0`;
    - `eventos/dados?year=2027` → investimento em marketing `>0`.
- [ ] **Passo 2:** rodar os dois testes. Comandos:
  - `docker compose run --rm --no-deps seed php tests/rodar.php tests/estaticos/AnosTeste.php`
  - `docker compose run --rm seed php -d memory_limit=1024M database/seed/gerar.php --hoje=2027-03-15`
  - `docker compose exec app php tests/smoke.php --hoje=2027-03-15 --apenas=envelhecimento`
  Esperado: FAIL nos dois.
- [ ] **Passo 3:** aplicar as mudanças no SQL e no JS.
- [ ] **Passo 4:** rodar de novo os dois. Esperado: PASS. Depois, regerar com a data de hoje (`docker compose up -d`) e rodar o smoke completo. Esperado: ✔.
- [ ] **Passo 5:** commit com a mensagem "Remove anos fixos dos filtros e da regra de custo de marketing".

### Tarefa 19: documentação e prints

**Arquivos:**
- Criar: `database/README.md`, `docs/prints/capturar.ps1`, `docs/prints/*.png`
- Modificar: `README.md`, `.env.example` (só o comentário de `DB_2`, se citar `find(...,'2')` sem uso)
- Teste: `tests/estaticos/BiExplorerTeste.php` (incluir o `README.md` na varredura)

**`database/README.md` contém:**
- para que serve a base;
- uma tabela com cada tabela, a área e o significado em linguagem de negócio;
- a tabela das pipelines (Apêndice B.1);
- um diagrama `erDiagram` em Mermaid com as ligações da spec (seção 6.1), mais `taskV2 → dealV2`, `npsEvento → evento`, `itemOrcamentoV2 → orcamentoV2` e `msgCW → conversaCW`;
- as exceções à convenção (spec, seção 5.3);
- como regerar (`docker compose up`, ou `php database/seed/gerar.php` sem Docker) e como ajustar volumes no `config.php`.

**`README.md`:**
- logo depois do título, a seção "Como rodar": pré-requisito Docker Desktop, os 3 passos, login `demo`/`demo`, e uma tabela "o que visitar" com 8 URLs;
- os prints (2 por linha, em tabela);
- "Problemas comuns":
  - porta ocupada (`APP_PORTA`/`DB_PORTA`);
  - Docker fechado;
  - "o app não subiu" (ver `docker compose logs seed`);
  - recriar a base;
  - conectar um cliente SQL em `localhost:3307` com `acme`/`acme`;
- "Sem Docker" (spec, seção 4.3);
- "Testes" (`tests/rodar.php` e `tests/smoke.php`);
- atualizar a tabela de dashboards (sem a linha do BI Explorer), as contagens (21 controllers; recontar views e linhas com `find`/`wc`) e as frases sobre o banco não acompanhar o repositório, o schema não estar incluído e a planilha do Google.

**`capturar.ps1`:**
- recebe `-Porta 8000`;
- localiza o `msedge.exe` em `Program Files (x86)` ou `Program Files`;
- para cada par (arquivo, rota) da lista, roda `msedge --headless=new --disable-gpu --hide-scrollbars --window-size=1440,1800 --virtual-time-budget=20000 --screenshot=<arquivo> "http://demo:demo@localhost:<porta><rota>"`.

As 8 rotas e arquivos são:

| Arquivo | Rota |
|---|---|
| `executivo.png` | `/executive` |
| `comercial.png` | `/comercial` |
| `marketing.png` | `/marketing` |
| `eventos.png` | `/eventos` |
| `financeiro.png` | `/financeiro` |
| `atendimento.png` | `/chatwoot` |
| `jornada.png` | `/helenacastro/jornada` |
| `gestao-eventos.png` | `/hotel` |

Passos:

- [ ] **Passo 1:** incluir o `README.md` no `BiExplorerTeste` e rodar. Esperado: FAIL (o README ainda cita o BI Explorer).
- [ ] **Passo 2:** escrever o `database/README.md` e o `README.md`.
- [ ] **Passo 3:** rodar o teste. Esperado: PASS.
- [ ] **Passo 4:** com o projeto no ar (`docker compose up -d`, modo PROD), rodar `powershell -File docs/prints/capturar.ps1`.
  Esperado: 8 PNGs com mais de 50 KB cada. Abrir cada um e conferir que mostra números e gráficos, e não a tela de login ou de carregamento. Se o login embutido na URL falhar, aplicar a mitigação da spec (seção 10).
- [ ] **Passo 5:** conferir os links do README. Comando: `grep -oE "\]\([^)]+\)" README.md database/README.md`. Cada arquivo local citado deve existir.
- [ ] **Passo 6:** commit com a mensagem "Documenta o modelo de dados, o ambiente e adiciona prints".

### Tarefa 20: verificação final como visitante

- [ ] **Passo 1:** testar um clone limpo.
  - Rodar `git clone <caminho local do repositório> "$env:TEMP\portfolio-teste"`, entrar na pasta e rodar `git checkout feature/base-ficticia`.
  - Subir com `APP_PORTA=8090 DB_PORTA=3390 docker compose -p portfolio-teste up -d --build`.
  - Esperado: o `seed` termina com 0 e `http://localhost:8090` abre com `demo`/`demo`.
- [ ] **Passo 2:** rodar o smoke contra o clone: `docker compose -p portfolio-teste exec app php tests/smoke.php`. Esperado: todas as linhas com ✔.
- [ ] **Passo 3:** testar a segunda subida (Foco 2). Rodar `docker compose -p portfolio-teste up -d` de novo e depois o smoke. Esperado: ✔.
- [ ] **Passo 4:** limpar. Rodar `docker compose -p portfolio-teste down` e apagar a pasta temporária.
- [ ] **Passo 5:** rodar a suíte completa no repositório de trabalho: `docker compose run --rm --no-deps seed php tests/rodar.php`. Esperado: tudo ✔.
- [ ] **Passo 6:** conferir o histórico com `git log --format='%an <%ae>' feature/base-ficticia ^main | sort -u`. Esperado: só `Leonardo Silva <leonqt02@gmail.com>`.
- [ ] **Passo 7:** pedir a revisão final do branch, depois apresentar ao usuário as opções de merge ou PR (superpowers:finishing-a-development-branch).
