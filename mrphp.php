<?php
error_reporting(E_ALL);
if (file_exists(__DIR__ . '/extra.php')) {
    require(__DIR__ . '/extra.php');
} else {
    class Extra {}
}
class mrphp extends Extra
{
    /**
     * Em vez de propriedades de instância, vamos usar
     * propriedades estáticas para manter seu valor mesmo
     * em novas instâncias ou novos controladores.
     */
    protected static array $config    = [];
    protected static ?string $site      = null;
    protected static ?string $url       = null;
    protected static string $hashKey   = 'default_hash_key';
    protected static ?object $db        = null;
    protected static ?object $con       = null;
    protected static mixed $upload = null;

    // Estas propriedades podem continuar de instância
    // se você quiser que cada "objeto" MRPHP tenha seu próprio template, página etc.
    protected string $template = 'index';
    protected ?string $pagina = null;

    // Armazenamos as variáveis de "set()"
    protected static $vars = [];

    protected static bool $initialized = false;

    /**
     * Instância do controlador atualmente em uso
     * para que o framework saiba qual template usar
     * caso seja definido no próprio controlador.
     */
    protected ?object $controller = null;

    /**
     * Construtor: só chamará initialize() uma vez por requisição,
     * devido ao if (!self::$initialized).
     */
    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (!self::$initialized) {
            $this->initialize();
            self::$initialized = true;
        }
    }

    /**
     * Métodos mágicos para permitir que $this->site,
     * $this->config, $this->db, etc. usem as propriedades estáticas
     * de forma transparente.
     */
    public function __get(string $name): mixed
    {
        switch ($name) {
            case 'config':
                return self::$config;
            case 'site':
                return self::$site;
            case 'url':
                return self::$url;
            case 'hashKey':
                return self::$hashKey;
            case 'db':
                return self::$db;
            case 'con':
                return self::$con;
            case 'upload':
                return self::$upload;
            default:
                return null;
        }
    }

    public function __set(string $name, mixed $value): void
    {
        switch ($name) {
            case 'config':
                self::$config = $value;
                break;
            case 'site':
                self::$site   = $value;
                break;
            case 'upload':
                self::$upload = $value;
                break;
            case 'url':
                self::$url    = $value;
                break;
            case 'hashKey':
                self::$hashKey = $value;
                break;
            case 'db':
                self::$db     = $value;
                break;
            case 'con':
                self::$con    = $value;
                break;
                // Se quiser, ignore outros cases ou trate como convier
        }
    }

    /**
     * set() e setVars() continuam usando self::$vars para variáveis dinâmicas da view.
     */
    public function set(mixed $value, string $name): void
    {
        self::$vars[$name] = $value;
    }
    public function setVars(array $array): void
    {
        if (is_array($array)) {
            self::$vars = array_merge(self::$vars, $array);
        }
    }

    /**
     * setTemplate() se mantém igual, definindo o template
     * só que esse template é uma propriedade de instância,
     * pois cada objeto/controller pode querer um template distinto.
     */
    public function setTemplate(string $template): void
    {
        if ($this->controller) {
            $this->controller->template = $template;
        } else {
            $this->template = $template;
        }
    }

    public function validaInsert(array $data, array $allowedFields): array
    {
        $validatedData = array_intersect_key($data, array_flip($allowedFields));
        return $validatedData;
    }

    public function getFormToken(string $formName): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['form_tokens'][$formName] = $token;
        return $token;
    }

    public function verificaFormToken(string $formName, string $token): bool
    {
        if (isset($_SESSION['form_tokens'][$formName]) && hash_equals($_SESSION['form_tokens'][$formName], $token)) {
            unset($_SESSION['form_tokens'][$formName]);
            return true;
        }
        return false;
    }

    public function validar(array $data, array $regras): array
    {
        $erros = [];

        foreach ($regras as $campo => $regraStr) {
            $lista = explode('|', $regraStr);
            $valor = trim((string)($data[$campo] ?? ''));

            foreach ($lista as $regra) {
                $partes = explode(':', $regra, 2);
                $nome   = $partes[0];
                $param  = $partes[1] ?? null;

                $erro = match($nome) {
                    'required' => $valor === '' ? 'Campo obrigatório' : null,
                    'email'    => $valor !== '' && !filter_var($valor, FILTER_VALIDATE_EMAIL) ? 'E-mail inválido' : null,
                    'min'      => $valor !== '' && mb_strlen($valor) < (int)$param ? "Mínimo de {$param} caracteres" : null,
                    'max'      => $valor !== '' && mb_strlen($valor) > (int)$param ? "Máximo de {$param} caracteres" : null,
                    'numeric'  => $valor !== '' && !is_numeric($valor) ? 'Deve ser um número' : null,
                    'cpf'      => $valor !== '' && !$this->validaCpf($valor) ? 'CPF inválido' : null,
                    default    => null,
                };

                if ($erro !== null) {
                    $erros[$campo][] = $erro;
                }
            }
        }

        return $erros;
    }

    private function validaCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) return false;

        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int)$cpf[$i] * ($t + 1 - $i);
            }
            $digito = ((10 * $soma) % 11) % 10;
            if ((int)$cpf[$t] !== $digito) return false;
        }
        return true;
    }

    protected function initialize(): void
    {
        $this->loadConfigurations();
        $this->configurarErros();
        $this->inicializarStorage();

        // Definir o fuso horário
        date_default_timezone_set(self::$config['app']['timezone']);

        // Configurar exibição de erros
        if (self::$config['app']['display_errors']) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            error_reporting(0);
        }

        // Forçar HTTPS se necessário
        if (self::$config['app']['force_https'] && empty($_SERVER['HTTPS'])) {
            $redirectUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
            header('Location: ' . $redirectUrl, true, 301);
            exit;
        }

        // Forçar www se necessário
        if (self::$config['app']['force_www'] && strpos(($_SERVER['HTTP_HOST'] ?? ''), 'www.') !== 0) {
            $redirectScheme = self::$config['app']['force_https'] ? 'https://' : 'http://';
            $redirectUrl = $redirectScheme . 'www.' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/');
            header('Location: ' . $redirectUrl, true, 301);
            exit;
        }

        $this->setSiteUrl();
        $this->setBaseUrl();

        // Armazena em estático
        self::$upload = self::$config['app']['upload_url'] ?? '';
        if (strpos((string)self::$upload, 'http') !== 0) {
            self::$upload = self::$site . self::$upload;
        }

        self::$hashKey = self::$config['app']['hash_key'] ?? 'default_hash_key';

        // Estabelecer conexão com o banco de dados
        self::$db = $this->connectDatabase();
        self::$con = self::$db;

        // Incluir extra.php se existir

        if (file_exists(__DIR__ . '/extra.php')) {

            include_once __DIR__ . '/extra.php';
        }
    }

    public function start(): void
    {
        if (self::$config['api']['mode']) {
            $this->verificaApi();
        }
        $this->bloqueiaPostDuplicado();
        $this->controle();
        $this->renderTemplate();
    }

    private function bloqueiaPostDuplicado(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;

        $fingerprint = md5(serialize($_POST) . ($_SERVER['REQUEST_URI'] ?? ''));
        $agora = time();
        $janela = 5;

        if (isset($_SESSION['_mrphp_post_lock'])) {
            $lock = $_SESSION['_mrphp_post_lock'];
            if ($lock['fingerprint'] === $fingerprint && ($agora - $lock['time']) < $janela) {
                $referer = $_SERVER['HTTP_REFERER'] ?? self::$site;
                header('Location: ' . $referer);
                exit;
            }
        }

        $_SESSION['_mrphp_post_lock'] = ['fingerprint' => $fingerprint, 'time' => $agora];
    }

    public function verificaApi(): bool
    {
        $apiUser = self::$config['api']['user'];
        $apiPassword = self::$config['api']['password'];

        if (empty($apiUser) || empty($apiPassword)) {
            http_response_code(500);
            echo json_encode(['error' => 'CREDENCIAIS DA API NÃO FORAM CONFIGURADAS']);
            exit;
        }

        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            header('WWW-Authenticate: Basic realm="API Access"');
            header('HTTP/1.0 401 Unauthorized');
            echo json_encode(['error' => 'ACESSO NEGADO']);
            exit;
        } else {
            $user = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];

            if ($user !== $apiUser || $password !== $apiPassword) {
                header('HTTP/1.0 403 Forbidden');
                echo json_encode(['error' => 'ACESSO NEGADO']);
                exit;
            }
        }
        return true;
    }

    private function loadConfigurations(): void
    {
        $env = $this->loadEnv(__DIR__ . '/.env');


        $tempConfig = [
            'db' => [],
            'app' => [
                'env' => $env['APP_ENV'] ?? 'DEV',
                'force_https' => filter_var($env['FORCE_HTTPS'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'upload_url' => $env['UPLOAD_URL'] ?? '/uploads/',
                'hash_key' => $env['HASH_KEY'] ?? 'default_hash_key',
                'timezone' => $env['TIMEZONE'] ?? 'UTC',
                'force_www' => filter_var($env['FORCE_WWW'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'display_errors' => filter_var($env['DISPLAY_ERRORS'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'url_control' => $env['URL_CONTROL'] ?? '',
            ],
            'api' => [
                'mode' => filter_var($env['API_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'user' => $env['API_USER'] ?? '',
                'password' => $env['API_PASSWORD'] ?? '',
            ],
        ];

        // Processar as configurações de banco de dados
        foreach ($env as $key => $value) {
            if (strpos($key, 'DB_') === 0) {
                $dbKey = substr($key, 3);
                $parts = explode('_', $dbKey, 2);
                if (count($parts) == 2) {
                    $identifier = strtolower($parts[0]);
                    $configName = strtolower($parts[1]);
                    $configMap = [
                        'host' => 'host',
                        'user' => 'user',
                        'pass' => 'pass',
                        'name' => 'name',
                        'charset' => 'charset',
                    ];
                    if (isset($configMap[$configName])) {
                        $tempConfig['db'][$identifier][$configMap[$configName]] = $value;
                    }
                }
            }
        }
        // Definir valores padrão
        foreach ($tempConfig['db'] as $identifier => &$dbConfig) {
            $dbConfig = array_merge([
                'host' => '',
                'user' => '',
                'pass' => '',
                'name' => '',
                'charset' => 'utf8mb4',
            ], $dbConfig);
        }
        self::$config = $tempConfig;
    }

    private function loadEnv(string $path): array
    {
        if (!file_exists($path)) {
            throw new \Exception("Arquivo .env não encontrado em: $path");
        }

        $env = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            $delimiterPos = strpos($line, '=');
            if ($delimiterPos === false) {
                continue;
            }
            $name = trim(substr($line, 0, $delimiterPos));
            $value = trim(substr($line, $delimiterPos + 1));
            $value = trim($value, '"\'');
            $env[$name] = $value;
        }
        return $env;
    }

    /**
     * Detecta o esquema (http/https) considerando também X-Forwarded-Proto,
     * necessário quando a aplicação roda atrás de um proxy reverso (nginx, etc.)
     * que termina TLS e repassa para o PHP em HTTP puro — nesse caso
     * $_SERVER['HTTPS'] não vem preenchido mesmo a requisição original sendo HTTPS.
     */
    private function detectScheme(): string
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return 'https';
        }
        if (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
            return 'https';
        }
        return 'http';
    }

    private function setBaseUrl(): void
    {
        $scheme = $this->detectScheme();
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        self::$url = $scheme . '://' . $host . '/';
    }

    private function setSiteUrl(): void
    {
        $scheme = $this->detectScheme();
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Obtém o nome do script e normaliza barras
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

        // Obtém o diretório base
        $basePath = dirname($scriptName);
        $basePath = str_replace('\\', '/', $basePath);

        // Se o basePath contiver um caminho de disco (ex: C:/), 
        // tentamos extrair a parte relativa ao DOCUMENT_ROOT ou assumimos a raiz.
        if (preg_match('/^[a-zA-Z]:/', $basePath)) {
            $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
            if (!empty($docRoot) && strpos($basePath, $docRoot) === 0) {
                $basePath = substr($basePath, strlen($docRoot));
            } else {
                $basePath = '/';
            }
        }

        // Garante que o basePath tenha o formato correto (/caminho/)
        $basePath = trim($basePath, '/');
        $basePath = $basePath ? '/' . $basePath . '/' : '/';

        self::$site = $scheme . '://' . $host . $basePath;
    }

    protected function connectDatabase(string $connectionName = 'default'): ?object
    {
        if (!isset(self::$config['db'][$connectionName])) {
            throw new \Exception("Configuração para a conexão '$connectionName' não encontrada.");
        }
        $dbConfig = self::$config['db'][$connectionName];
        if ($dbConfig['host'] != "") {
            $db = new \mysqli(
                (string)$dbConfig['host'],
                (string)$dbConfig['user'],
                (string)$dbConfig['pass'],
                (string)$dbConfig['name']
            );
            if ($db->connect_error) {
                throw new \Exception('Erro ao conectar ao banco de dados: ' . $db->connect_error);
            }
            if (isset($dbConfig['charset'])) {
                $db->set_charset((string)$dbConfig['charset']);
            }
            return $db;
        }
        return null;
    }

    protected function controle(): void
    {
        $url = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = str_replace(basename($scriptName), '', $scriptName);
        $basePath = rtrim($scriptDir, '/');
        if ($basePath != '' && $basePath != '/' && $url !== null) {
            $url = preg_replace('#^' . preg_quote($basePath, '#') . '#', '', (string)$url);
        }
        $url = parse_url((string)$url, PHP_URL_PATH);
        $segments = explode('/', trim($url, '/'));

        $controllerName = !empty($segments[0]) ? $segments[0] : 'home';
        $method = isset($segments[1]) ? $segments[1] : 'index';
        $params = array_slice($segments, 2);

        $controllerFile = __DIR__ . "/controle/{$controllerName}_controle.php";
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            $controllerClass = $controllerName;
            if (class_exists($controllerClass)) {
                $controller = new $controllerClass();
                $this->controller = $controller;

                require_once __DIR__ . "/controle/home_controle.php";
                $homeController = new home();

                if (method_exists($controller, $method)) {
                    call_user_func_array([$controller, $method], $params);

                    if ($this->controller && method_exists($homeController, $this->controller->template . '_controle')) {
                        call_user_func_array([$homeController, $this->controller->template . '_controle'], $params);
                    }
                    return;
                } else {
                    if (!empty($method)) {
                        array_unshift($params, $method);
                    }
                    call_user_func_array([$controller, 'index'], $params);
                    if ($this->controller && method_exists($homeController, $this->controller->template . '_controle')) {
                        call_user_func_array([$homeController, $this->controller->template . '_controle'], $params);
                    }
                    return;
                }
            }
        }

        // Se o controlador não existir, verificar se URL_CONTROL está definido
        $urlControl = self::$config['app']['url_control'] ?? '';
        if (!empty($urlControl)) {
            $controllerName = $urlControl;
            $controllerFile = __DIR__ . "/controle/{$controllerName}_controle.php";
            if (file_exists($controllerFile)) {
                require_once $controllerFile;
                $controllerClass = $controllerName;
                if (class_exists($controllerClass)) {
                    $controller = new $controllerClass();
                    $this->controller = $controller;

                    $param = implode('/', $segments);
                    call_user_func_array([$controller, 'index'], [$param]);
                    return;
                }
            }
        }

        // Se nenhum controlador encontrado, exibir 404
        //http_response_code(404);
        // $this->setPagina(__DIR__ . '/paginas/error/404.php');
    }

    public function location(?string $url = null): void
    {
        header('Location: ' . $url);
        exit;
    }

    public function redirect(?string $url = null, int $statusCode = 302): void
    {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }

    public function setPagina(string $pagina): void
    {
        $_SESSION['MRPHP_PAGINA'] = $pagina;
    }


    public function getPagina(): void
    {
        $framework = $this;
        extract(self::$vars);
        if (!empty($_SESSION['MRPHP_PAGINA'])) {
            $this->pagina = (string)$_SESSION['MRPHP_PAGINA'];
            unset($_SESSION['MRPHP_PAGINA']);
            require_once __DIR__ . '/paginas/' . $this->pagina;
            return;
            //return $this->pagina;
        }
        $url = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = str_replace(basename($scriptName), '', $scriptName);
        $basePath = rtrim($scriptDir, '/');
        if ($basePath != '' && $basePath != '/' && $url !== null) {
            $url = preg_replace('#^' . preg_quote($basePath, '#') . '#', '', (string)$url);
        }
        $url = parse_url((string)$url, PHP_URL_PATH);
        $segments = explode('/', trim((string)$url, '/'));
        $page = !empty($segments[1]) ? $segments[1] : 'index';
        $folder = !empty($segments[0]) ? $segments[0] : 'home';



        $pageFile = __DIR__ . "/paginas/$folder/$page.php";
        //$this->debug($pageFile);
        if (file_exists($pageFile)) {
            require_once $pageFile;
        } else {
            require_once __DIR__ . '/paginas/error/404.php';
        }
    }

    protected function renderTemplate()
    {
        if (isset($this->controller) && isset($this->controller->template)) {
            $templateFile = __DIR__ . "/template/" . $this->controller->template . ".php";
        } else {
            $templateFile = __DIR__ . "/template/" . $this->template . ".php";
        }
        if (file_exists($templateFile)) {
            $framework = $this;
            extract(self::$vars);
            include $templateFile;
        } else {
            die("Template não encontrado.");
        }
        $this->barraDesenvolvimento();
    }

    private function barraDesenvolvimento(): void
    {
        if (strtoupper(self::$config['app']['env'] ?? '') !== 'DEV') return;

        $rota = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $php  = PHP_VERSION;

        echo <<<HTML
        <div style="position:fixed;bottom:0;left:0;right:0;background:#dc2626;color:#fff;font-family:monospace;font-size:11px;padding:5px 16px;z-index:99999;display:flex;align-items:center;gap:24px;box-shadow:0 -2px 6px rgba(0,0,0,.3);">
            <span><strong>DEV</strong></span>
            <span>&#9888; Atenção: Este é um ambiente de desenvolvimento!</span>
            <span>{$rota}</span>
            <span>PHP {$php}</span>
        </div>
        HTML;
    }

    public function upload(array $arquivo, string $dir, array $formatos = ['jpg', 'jpeg', 'png'], int $tamanho = 5242880): array
    {
        $errosHttp = [
            UPLOAD_ERR_INI_SIZE   => 'Arquivo excede o limite do servidor',
            UPLOAD_ERR_FORM_SIZE  => 'Arquivo excede o limite do formulário',
            UPLOAD_ERR_PARTIAL    => 'Upload incompleto',
            UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo enviado',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não encontrada',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao salvar arquivo temporário',
        ];

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            return ['erro' => $errosHttp[$arquivo['error']] ?? 'Erro desconhecido no upload'];
        }

        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extensao, $formatos)) {
            return ['erro' => 'Formato não permitido. Use: ' . implode(', ', $formatos)];
        }

        if ($arquivo['size'] > $tamanho) {
            $mb = number_format($tamanho / 1048576, 1);
            return ['erro' => "Arquivo muito grande. Máximo: {$mb}MB"];
        }

        $dirCompleto = rtrim(__DIR__, '/') . '/' . ltrim($dir, '/');
        if (!is_dir($dirCompleto)) {
            mkdir($dirCompleto, 0755, true);
        }

        $nome    = bin2hex(random_bytes(16)) . '.' . $extensao;
        $caminho = rtrim($dir, '/') . '/' . $nome;
        $destino = rtrim($dirCompleto, '/') . '/' . $nome;

        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            return ['erro' => 'Erro ao mover o arquivo para o destino'];
        }

        return ['nome' => $nome, 'caminho' => $caminho];
    }

    // Funções de inserção e busca
    public function insert(array $data, string $table, string $connectionName = 'default'): string|int|bool
    {
        $db = $connectionName === 'default' ? self::$db : $this->connectDatabase($connectionName);
        if (!$db) return false;
        try {
            $columns = [];
            $sql = "SHOW COLUMNS FROM `$table`";
            $result = $db->query($sql);

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $columns[] = $row['Field'];
                }
                $result->free();
            } else {
                throw new \Exception('Erro ao obter as colunas da tabela: ' . $db->error);
            }

            $data = array_intersect_key($data, array_flip($columns));
            if (empty($data)) {
                throw new \Exception("Nenhum dado válido para inserir ou atualizar na tabela '$table'.");
            }

            $idField = "id_$table";
            if (isset($data[$idField])) {
                $idValue = $data[$idField];
                unset($data[$idField]);

                $fields = array_keys($data);
                $placeholders = implode('=?, ', $fields) . '=?';

                $sql = "UPDATE `$table` SET $placeholders WHERE `$idField` = ?";
                $stmt = $db->prepare($sql);
                if (!$stmt) {
                    throw new \Exception('Erro na preparação da consulta: ' . $db->error);
                }

                $values = array_values($data);
                $values[] = $idValue;
                $types = str_repeat('s', count($values));
                $stmt->bind_param($types, ...$values);
                $stmt->execute();
                $stmt->close();

                return $idValue;
            } else {
                $fields = array_keys($data);
                $placeholders = implode(', ', array_fill(0, count($fields), '?'));

                $sql = "INSERT INTO `$table` (" . implode(', ', $fields) . ") VALUES ($placeholders)";
                $stmt = $db->prepare($sql);
                if (!$stmt) {
                    throw new \Exception('Erro na preparação da consulta: ' . $db->error);
                }

                $values = array_values($data);
                $types = str_repeat('s', count($values));
                $stmt->bind_param($types, ...$values);
                $stmt->execute();
                $insertId = $db->insert_id;
                $stmt->close();

                return $insertId;
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function find(string $table, string $field, string|int|float $value, string $connectionName = 'default'): array|bool|null
    {
        $db = $connectionName === 'default' ? self::$db : $this->connectDatabase($connectionName);
        if (!$db) return false;
        try {
            $sql = "SELECT * FROM $table WHERE $field = ? ORDER BY id_$table DESC LIMIT 1";
            $stmt = $db->prepare($sql);
            if (!$stmt) {
                throw new \Exception('Erro na preparação da consulta: ' . $db->error);
            }

            $stmt->bind_param('s', $value);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            return $data;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    // Função para sanitizar arrays
    public function filtro(array $data): array
    {
        $sanitizedData = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitizedData[$key] = $this->filtro($value);
            } else {
                $sanitizedData[$key] = trim(strip_tags((string)$value));
            }
        }
        return $sanitizedData;
    }

    public function debug(mixed $variable): void
    {
        static $highlightIncluded = false;
        if (!$highlightIncluded) {
            echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/dark.min.css">';
            echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>';
            echo '<script>hljs.highlightAll();</script>';
            $highlightIncluded = true;
        }
        $output = var_export($variable, true);
        echo '<pre><code class="language-php">' . htmlspecialchars($output) . '</code></pre>';
    }

    public function logado(string $sessao, string $redirect = 'auth'): bool
    {
        if (!isset($_SESSION[$sessao])) {
            header("Location: " . $this->site . $redirect);
            exit();
        }
        return true;
    }


    public function msg(string $mensagem, string $tipo = 'info'): void
    {
        if (!isset($_SESSION['mensagens'])) {
            $_SESSION['mensagens'] = array();
        }
        $_SESSION['mensagens'][] = array(
            'texto' => $mensagem,
            'tipo' => $tipo
        );
    }

    public function getMsg()
    {
        $mensagens = isset($_SESSION['mensagens']) ? $_SESSION['mensagens'] : array();
        unset($_SESSION['mensagens']);
        return $mensagens;
    }
    //REMOVE CARACTERES ESPECIAIS ACENTOS E ESPAÇOS DE UMA STRING
    public function removeChar(string $palavra): string
    {
        $palavra = trim($palavra);
        $palavra = strtolower($palavra);
        $a = array('á', 'â', 'ã', 'à', 'ó', 'ô', 'õ', 'ò', 'é', 'ê', 'è', 'ì', 'í', 'ç', ' ', ',', '.', ';', ':', '?', '/', '\\', '[', ']', 'Ã', 'Á', '"', '(', ')', 'Í', 'ú', '+', '%', 'Ó', 'É', 'Ò', 'È', '&', '!', '@', '#', '$', '¨', '*', 'Ç', 'ª', ' º', '|', '–', ' ', '  ', '´');
        $b = array('a', 'a', 'a', 'a', 'o', 'o', 'o', 'o', 'e', 'e', 'e', 'i', 'i', 'c', '-', '', '', '', '', '', '', '', '', '', 'a', 'a', '', '', '', 'i', 'u', '', '', 'o', 'e', 'o', 'e', '', '', '', '', '', '', '', 'c', '', '', '-', '-', '-', '-', '');
        $palavra = str_replace($a, $b, strtolower($palavra));
        $palavra = str_replace('---', '-', strtolower($palavra));
        $palavra = str_replace('--', '-', strtolower($palavra));
        $palavra = str_replace(' ', '-', strtolower($palavra));
        return $palavra;
    }
    //CONVERTE DATA PARA FORMATO DO BANCO (dd/mm/aaaa ou dd/mm/aaaa hh:mm:ss → aaaa-mm-dd)
    public function dataBd(string|int|float|null $data): string
    {
        $data = trim((string)$data);
        $partes = explode(' ', $data, 2);
        $hora   = $partes[1] ?? null;

        $dataExp = explode('/', $partes[0]);
        if (count($dataExp) === 3) {
            $partes[0] = $dataExp[2] . '-' . $dataExp[1] . '-' . $dataExp[0];
        }

        return $hora !== null ? $partes[0] . ' ' . $hora : $partes[0];
    }

    //CONVERTE DATA PARA FORMATO PADRÃO (aaaa-mm-dd ou aaaa-mm-dd hh:mm:ss → dd/mm/aaaa)
    public function data(string|int|float|null $data): string|bool
    {
        $data = trim((string)$data);
        if (empty($data)) return false;

        $partes = explode(' ', $data, 2);
        $hora   = $partes[1] ?? null;

        $dataExp = explode('-', $partes[0]);
        if (count($dataExp) === 3) {
            $partes[0] = $dataExp[2] . '/' . $dataExp[1] . '/' . $dataExp[0];
        }

        return $hora !== null ? $partes[0] . ' ' . $hora : $partes[0];
    }
    //CONVERTE VALOR PARA FORMATO AMERICANO
    public function moedaBd(string|int|float|null $valor): string
    {
        $valor = str_replace('.', '', (string)$valor);
        $valor = str_replace(',', '.', $valor);
        return $valor;
    }

    //CONVERTE VALOR PARA FORMATO AMERICANO
    public function moeda(string|int|float|null $valor): string|bool
    {
        if ($valor == 0) $valor = "0.00";
        if (empty($valor)) return false;
        $valor = number_format((float)$valor, 2, '.', ',');
        return $valor;
    }

    public function hashSenha(string $senha): string
    {
        return password_hash($senha . self::$hashKey, PASSWORD_BCRYPT);
    }

    public function verificaSenha(string $senha, string $hash): bool
    {
        return password_verify($senha . self::$hashKey, $hash);
    }

    private function inicializarStorage(): void
    {
        $base = __DIR__ . '/.mrphp';

        if (!is_dir($base)) {
            mkdir($base, 0755, true);
            file_put_contents($base . '/.htaccess', "Order deny,allow\nDeny from all\n");
            file_put_contents($base . '/index.php', "<?php exit;\n");
        }

        foreach (['rate', 'cache'] as $sub) {
            $dir = $base . '/' . $sub;
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    public function rateLimit(int $maxTentativas = 10, int $janela = 60): void
    {
        $ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rota   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $arquivo = __DIR__ . '/.mrphp/rate/' . md5($ip . $rota) . '.json';

        $agora = time();
        $dados = file_exists($arquivo)
            ? json_decode(file_get_contents($arquivo), true)
            : null;

        if (!$dados || ($agora - $dados['inicio']) >= $janela) {
            $dados = ['count' => 1, 'inicio' => $agora];
        } else {
            $dados['count']++;

            if ($dados['count'] > $maxTentativas) {
                $restante = $janela - ($agora - $dados['inicio']);
                http_response_code(429);
                $pagina = __DIR__ . '/paginas/error/429.php';
                if (file_exists($pagina)) {
                    include $pagina;
                } else {
                    echo "Muitas tentativas. Tente novamente em {$restante} segundos.";
                }
                exit;
            }
        }

        file_put_contents($arquivo, json_encode($dados), LOCK_EX);
    }

    private function configurarErros(): void
    {
        $isDev = strtoupper(self::$config['app']['env'] ?? 'DEV') === 'DEV';

        set_error_handler(function(int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) return false;
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(function(\Throwable $e) use ($isDev): void {
            http_response_code(500);
            $pagina = $isDev
                ? __DIR__ . '/paginas/error/dev.php'
                : __DIR__ . '/paginas/error/500.php';
            if (file_exists($pagina)) {
                include $pagina;
            }
            exit;
        });
    }
}
