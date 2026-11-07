<?php

/**
 * Fonte única de verdade do sistema de Gestão de Eventos (hotéis).
 *
 * Cada entidade é declarada uma vez aqui — nomes de campo, tipos, enums e
 * regras. Controller, validação, importador e o DDL derivam desta definição.
 * No sistema antigo (Apps Script) cada tela reimplementava as suas próprias
 * regras, o que produziu quatro fórmulas divergentes de "total do evento" e
 * três estratégias de vínculo conflitantes; centralizar aqui é o que impede
 * essa divergência de voltar.
 *
 * Convenção obrigatória do MRPHP: tabela singular minúscula, PK `id_{tabela}`,
 * campos `{nome}_{tabela}`. Por isso os campos são declarados SEM o sufixo e
 * ele é aplicado por HotelSchema::campo() — a convenção fica garantida por
 * construção, não por disciplina.
 */
final class HotelSchema
{
    /** Aplicado a toda entidade, no fim da lista de campos. */
    private const CAMPOS_COMUNS = [
        // Nulo quando o registro nasce na tela: o indice e UNIQUE, e o MySQL
        // aceita varios NULL mas nao varias strings vazias iguais.
        'idLegado'     => ['tipo' => 'texto',    'max' => 64, 'nulo' => true, 'interno' => true, 'rotulo' => 'Id de origem'],
        'criadoEm'     => ['tipo' => 'dataHora', 'interno' => true, 'rotulo' => 'Criado em'],
        'atualizadoEm' => ['tipo' => 'dataHora', 'interno' => true, 'rotulo' => 'Atualizado em'],
        'deletado'     => ['tipo' => 'booleano', 'padrao' => 0, 'interno' => true, 'rotulo' => 'Excluído'],
    ];

    public const STATUS_CONTRATO  = ['negociacao', 'aguardando', 'assinado', 'cancelado'];
    public const STATUS_PAGAMENTO = ['aguardando', 'pendente', 'parcial', 'pago'];
    public const STATUS_ENTREGA   = ['', 'no_prazo', 'atrasado', 'problema', 'pendente'];
    public const STATUS_LOGISTICA = ['entregue', 'pendente', 'cancelado'];

    private const ENTIDADES = [

        // ---------------------------------------------------------------
        // hotel — a raiz. Uma linha por hotel contratado para um evento;
        // cidade e data do evento são colunas dele. O mesmo hotel aparece
        // mais de uma vez quando recebe eventos em datas diferentes.
        // ---------------------------------------------------------------
        'hotel' => [
            'rotulo' => 'Hotel',
            'plural' => 'Hotéis',
            'ordem'  => 'data',
            'campos' => [
                'nome'              => ['tipo' => 'texto', 'max' => 180, 'req' => true, 'rotulo' => 'Nome do hotel'],
                'nomeEvento'        => ['tipo' => 'texto', 'max' => 255, 'rotulo' => 'Nome do evento', 'derivado' => true],
                'cidade'            => ['tipo' => 'texto', 'max' => 120, 'rotulo' => 'Cidade', 'derivado' => true],
                'uf'                => ['tipo' => 'texto', 'max' => 2, 'maiusculo' => true, 'rotulo' => 'UF'],
                'endereco'          => ['tipo' => 'texto', 'max' => 255, 'rotulo' => 'Endereço', 'derivado' => true],
                'capacidade'        => ['tipo' => 'inteiro', 'rotulo' => 'Capacidade'],
                'data'              => ['tipo' => 'data', 'rotulo' => 'Data do evento'],
                'valor'             => ['tipo' => 'decimal', 'rotulo' => 'Valor do evento'],
                'tipoPag'           => ['tipo' => 'enum', 'valores' => ['', 'antecipado', 'faturado', 'parcial', 'credito'], 'padrao' => '', 'rotulo' => 'Tipo de pagamento'],
                'rating'            => ['tipo' => 'inteiro', 'min' => 0, 'max' => 5, 'padrao' => 0, 'rotulo' => 'Avaliação'],
                'status'            => ['tipo' => 'enum', 'valores' => self::STATUS_CONTRATO, 'padrao' => 'negociacao', 'rotulo' => 'Situação do contrato'],
                'dataAss'           => ['tipo' => 'data', 'rotulo' => 'Data da assinatura'],
                'linkContrato'      => ['tipo' => 'texto', 'max' => 500, 'rotulo' => 'Link do contrato'],
                'nf'                => ['tipo' => 'booleano', 'padrao' => 0, 'rotulo' => 'NF enviada'],
                'dataNf'            => ['tipo' => 'data', 'rotulo' => 'Data de envio da NF'],
                'oc'                => ['tipo' => 'booleano', 'padrao' => 0, 'rotulo' => 'OC aberta'],
                'dataOc'            => ['tipo' => 'data', 'rotulo' => 'Data de abertura da OC'],
                'ocNum'             => ['tipo' => 'texto', 'max' => 60, 'rotulo' => 'Número(s) da OC'],
                'quartosReserva'    => ['tipo' => 'texto', 'max' => 120, 'rotulo' => 'Nº da reserva de quartos'],
                'quartosConfirmado' => ['tipo' => 'booleano', 'padrao' => 0, 'rotulo' => 'Quartos confirmados'],
                'entregaStatus'     => ['tipo' => 'enum', 'valores' => self::STATUS_ENTREGA, 'padrao' => '', 'rotulo' => 'Status da entrega de materiais'],
                'entregaData'       => ['tipo' => 'data', 'rotulo' => 'Data da entrega'],
                'entregaMateriais'  => ['tipo' => 'texto', 'max' => 255, 'rotulo' => 'Materiais entregues'],
                'entregaObs'        => ['tipo' => 'textoLongo', 'rotulo' => 'Comentário da entrega'],
                'responsavelAv'     => ['tipo' => 'enum', 'valores' => ['hotel', 'terceirizado'], 'padrao' => 'hotel', 'rotulo' => 'Responsável pelo A/V'],
                'obs'               => ['tipo' => 'textoLongo', 'rotulo' => 'Observações'],

                // O evento do CRM, e a primeira coisa que se escolhe: e ele
                // que liga o hotel aos seus custos no banco, e dele que vem
                // cidade e endereco. Obrigatorio — um hotel sem evento nao
                // consegue reencontrar os proprios filhos (ver a guarda em
                // HotelRepoMysql::exigirEvento).
                'idEventoCrm'       => ['tipo' => 'fk', 'alvo' => 'evento', 'req' => true, 'semFk' => true, 'rotulo' => 'Evento no CRM'],
            ],
        ],

        // ---------------------------------------------------------------
        // equipamento — A/V terceirizado. Era um bloco de ~10 colunas
        // embutido no hotel (fNome, fValor, fStatus...), o que travava em
        // um fornecedor por evento. Agora é 0..N por hotel.
        // ---------------------------------------------------------------
        'equipamento' => [
            'rotulo' => 'Equipamento A/V',
            'plural' => 'Equipamentos A/V',
            'pai'    => 'hotel',
            'campos' => [
                // Obrigatorio: um fornecedor de A/V so existe para um evento.
                // Sem 'req' aqui, a validacao aceitaria nulo enquanto o DDL
                // gera NOT NULL — a divergencia so apareceria na migracao.
                'idHotel'    => ['tipo' => 'fk', 'alvo' => 'hotel', 'req' => true, 'rotulo' => 'Evento'],
                'fornecedor' => ['tipo' => 'texto', 'max' => 180, 'req' => true, 'rotulo' => 'Fornecedor A/V'],
                'contato'    => ['tipo' => 'texto', 'max' => 120, 'rotulo' => 'Contato'],
                'valor'      => ['tipo' => 'decimal', 'rotulo' => 'Valor A/V'],
                'status'     => ['tipo' => 'enum', 'valores' => self::STATUS_PAGAMENTO, 'padrao' => 'aguardando', 'rotulo' => 'Status do pagamento'],
                'oc'         => ['tipo' => 'booleano', 'padrao' => 0, 'rotulo' => 'OC aberta'],
                'ocNum'      => ['tipo' => 'texto', 'max' => 60, 'rotulo' => 'Número da OC'],
                'dataOc'     => ['tipo' => 'data', 'rotulo' => 'Data da OC'],
                'rating'     => ['tipo' => 'inteiro', 'min' => 0, 'max' => 5, 'padrao' => 0, 'rotulo' => 'Avaliação'],
                'entrega'    => ['tipo' => 'enum', 'valores' => ['', 'no_prazo', 'atrasado', 'problema'], 'padrao' => '', 'rotulo' => 'Chegou no evento?'],
                'hora'       => ['tipo' => 'texto', 'max' => 60, 'rotulo' => 'Hora prevista / chegada'],
                'obs'        => ['tipo' => 'textoLongo', 'rotulo' => 'Observações'],
            ],
        ],

        // ---------------------------------------------------------------
        // videomaker
        // ---------------------------------------------------------------
        'videomaker' => [
            'rotulo' => 'Videomaker',
            'plural' => 'Videomakers',
            'pai'    => 'hotel',
            'campos' => [
                'idHotel' => ['tipo' => 'fk', 'alvo' => 'hotel', 'nulo' => true, 'rotulo' => 'Evento'],
                'nome'    => ['tipo' => 'texto', 'max' => 180, 'req' => true, 'rotulo' => 'Nome do videomaker'],
                'contato' => ['tipo' => 'texto', 'max' => 120, 'rotulo' => 'Contato'],
                'cidade'  => ['tipo' => 'texto', 'max' => 120, 'rotulo' => 'Cidade', 'derivado' => true],
                'data'    => ['tipo' => 'data', 'rotulo' => 'Data do evento'],
                'mes'     => ['tipo' => 'texto', 'max' => 20, 'rotulo' => 'Mês do evento', 'derivado' => true],
                'valor'   => ['tipo' => 'decimal', 'rotulo' => 'Valor'],
                'status'  => ['tipo' => 'enum', 'valores' => self::STATUS_PAGAMENTO, 'padrao' => 'pendente', 'rotulo' => 'Status do pagamento'],
                'oc'      => ['tipo' => 'texto', 'max' => 60, 'rotulo' => 'Número da OC'],
                'tipo'    => ['tipo' => 'enum', 'valores' => ['video', 'foto', 'video+foto'], 'padrao' => 'video', 'rotulo' => 'Tipo'],
                'obs'     => ['tipo' => 'textoLongo', 'rotulo' => 'Observações'],
            ],
        ],

        // ---------------------------------------------------------------
        // transporte — duas datas distintas: a do evento e a da viagem.
        // ---------------------------------------------------------------
        'transporte' => [
            'rotulo' => 'Transporte',
            'plural' => 'Transportes',
            'pai'    => 'hotel',
            'campos' => [
                'idHotel'     => ['tipo' => 'fk', 'alvo' => 'hotel', 'nulo' => true, 'rotulo' => 'Evento'],
                'cidade'      => ['tipo' => 'texto', 'max' => 120, 'rotulo' => 'Cidade do evento', 'derivado' => true],
                'dataEvento'  => ['tipo' => 'data', 'rotulo' => 'Data do evento', 'derivado' => true],
                'tipo'        => ['tipo' => 'enum', 'valores' => ['aviao', 'onibus', 'uber', 'outro'], 'padrao' => 'aviao', 'rotulo' => 'Tipo de transporte'],
                'cia'         => ['tipo' => 'texto', 'max' => 120, 'rotulo' => 'Companhia / Empresa'],
                'trecho'      => ['tipo' => 'texto', 'max' => 120, 'rotulo' => 'Trecho'],
                'dataViagem'  => ['tipo' => 'data', 'rotulo' => 'Data do transporte'],
                'qtd'         => ['tipo' => 'inteiro', 'min' => 0, 'padrao' => 1, 'rotulo' => 'Nº de passagens'],
                'valor'       => ['tipo' => 'decimal', 'rotulo' => 'Valor total'],
                'status'      => ['tipo' => 'enum', 'valores' => ['pendente', 'reservado', 'pago', 'cancelado'], 'padrao' => 'pendente', 'rotulo' => 'Status'],
                'localizador' => ['tipo' => 'texto', 'max' => 60, 'rotulo' => 'Bilhete / localizador'],
                'passageiro'  => ['tipo' => 'texto', 'max' => 255, 'rotulo' => 'Passageiro(s)'],
                'obs'         => ['tipo' => 'textoLongo', 'rotulo' => 'Observações'],
            ],
        ],

        // ---------------------------------------------------------------
        // frete — logística de materiais até o hotel (era "Frete G4").
        // ---------------------------------------------------------------
        'frete' => [
            'rotulo' => 'Frete',
            'plural' => 'Fretes',
            'pai'    => 'hotel',
            'campos' => [
                'idHotel'     => ['tipo' => 'fk', 'alvo' => 'hotel', 'nulo' => true, 'rotulo' => 'Evento'],
                'cidade'      => ['tipo' => 'texto', 'max' => 120, 'rotulo' => 'Cidade', 'derivado' => true],
                'dataEvento'  => ['tipo' => 'data', 'rotulo' => 'Data do evento', 'derivado' => true],
                'doc'         => ['tipo' => 'texto', 'max' => 60, 'rotulo' => 'Doc / Minuta'],
                'dataEnvio'   => ['tipo' => 'data', 'rotulo' => 'Data do frete'],
                'dataEntrega' => ['tipo' => 'data', 'rotulo' => 'Data de entrega'],
                'valor'       => ['tipo' => 'decimal', 'rotulo' => 'Valor'],
                'status'      => ['tipo' => 'enum', 'valores' => self::STATUS_LOGISTICA, 'padrao' => 'entregue', 'rotulo' => 'Status'],
                'obs'         => ['tipo' => 'textoLongo', 'rotulo' => 'Observações'],
            ],
        ],

        // ---------------------------------------------------------------
        // material — kits enviados. idHotel nulo = solicitação avulsa
        // (era o eventoId mágico 'geral' do sistema antigo).
        // ---------------------------------------------------------------
        'material' => [
            'rotulo' => 'Material',
            'plural' => 'Materiais',
            'pai'    => 'hotel',
            'campos' => [
                'idHotel'    => ['tipo' => 'fk', 'alvo' => 'hotel', 'nulo' => true, 'rotulo' => 'Evento'],
                'cidade'     => ['tipo' => 'texto', 'max' => 120, 'rotulo' => 'Cidade', 'derivado' => true],
                'dataEvento' => ['tipo' => 'data', 'rotulo' => 'Data do evento', 'derivado' => true],
                'qtde'       => ['tipo' => 'inteiro', 'min' => 0, 'padrao' => 0, 'rotulo' => 'Qtde de kits'],
                'valor'      => ['tipo' => 'decimal', 'rotulo' => 'Valor total'],
                'status'     => ['tipo' => 'enum', 'valores' => self::STATUS_LOGISTICA, 'padrao' => 'entregue', 'rotulo' => 'Status'],
                'avulso'     => ['tipo' => 'booleano', 'padrao' => 0, 'rotulo' => 'Solicitação avulsa'],
                'obs'        => ['tipo' => 'textoLongo', 'rotulo' => 'Observações'],
            ],
        ],

        /*
         * Custo extra: despesa do evento que não é hotel, A/V, videomaker,
         * transporte, frete nem material. A `descricao` é obrigatória porque
         * é o único campo que diz o que o custo é — sem ela a linha vira um
         * valor sem explicação na conta do evento.
         */
        'custoExtra' => [
            'rotulo' => 'Custo extra',
            'plural' => 'Custos extras',
            'pai'    => 'hotel',
            'campos' => [
                'idHotel'   => ['tipo' => 'fk', 'alvo' => 'hotel', 'nulo' => true, 'rotulo' => 'Evento'],
                'descricao' => ['tipo' => 'texto', 'max' => 180, 'req' => true, 'rotulo' => 'Descrição'],
                'cidade'    => ['tipo' => 'texto', 'max' => 120, 'rotulo' => 'Cidade', 'derivado' => true],
                'data'      => ['tipo' => 'data', 'rotulo' => 'Data do evento'],
                'valor'     => ['tipo' => 'decimal', 'rotulo' => 'Valor'],
                'status'    => ['tipo' => 'enum', 'valores' => self::STATUS_PAGAMENTO, 'padrao' => 'pendente', 'rotulo' => 'Status do pagamento'],
                'ocNum'     => ['tipo' => 'texto', 'max' => 60, 'rotulo' => 'Número da OC'],
                'obs'       => ['tipo' => 'textoLongo', 'rotulo' => 'Observações'],
            ],
        ],

        // ---------------------------------------------------------------
        // outro — demais fornecedores (brindes, gráfica, decoração...).
        // ---------------------------------------------------------------

        // ---------------------------------------------------------------
    ];

    // =====================================================================
    // Acesso à definição
    // =====================================================================

    /** @return string[] */
    public static function nomes(): array
    {
        return array_keys(self::ENTIDADES);
    }

    public static function existe(string $ent): bool
    {
        return isset(self::ENTIDADES[$ent]);
    }

    public static function entidade(string $ent): array
    {
        if (!isset(self::ENTIDADES[$ent])) {
            throw new InvalidArgumentException("Entidade desconhecida: {$ent}");
        }
        return self::ENTIDADES[$ent];
    }

    /** Definição dos campos, já com os comuns anexados. */
    public static function campos(string $ent): array
    {
        return self::entidade($ent)['campos'] + self::CAMPOS_COMUNS;
    }

    /**
     * Campos que aparecem em formulario — exclui controle interno (id de
     * origem, carimbos de tempo, exclusao logica, ponte com o CRM).
     */
    public static function camposDeFormulario(string $ent): array
    {
        return array_filter(
            self::campos($ent),
            // `derivado` sai junto com `interno`: sao os campos que vem do
            // evento do CRM por JOIN — cidade, endereco, data do evento. Se
            // continuassem no formulario, o usuario digitaria e o valor seria
            // descartado na gravacao, sem aviso.
            static fn(array $d): bool => empty($d['interno']) && empty($d['derivado'])
        );
    }

    /** 'nome' + 'hotel' => 'nome_hotel' — a convenção do MRPHP, por construção. */
    public static function campo(string $ent, string $nome): string
    {
        return $nome . '_' . $ent;
    }

    public static function pk(string $ent): string
    {
        return 'id_' . $ent;
    }

    /** Nomes de coluna reais, na ordem de declaração, com a PK à frente. */
    public static function colunas(string $ent): array
    {
        $cols = [self::pk($ent)];
        foreach (array_keys(self::campos($ent)) as $nome) {
            $cols[] = self::campo($ent, $nome);
        }
        return $cols;
    }

    public static function rotulo(string $ent, string $nome): string
    {
        return self::campos($ent)[$nome]['rotulo'] ?? $nome;
    }

    /** Entidades que penduram num hotel, para montar os agregados por evento. */
    public static function filhosDeHotel(): array
    {
        $filhos = [];
        foreach (self::ENTIDADES as $nome => $def) {
            if (($def['pai'] ?? null) === 'hotel') {
                $filhos[] = $nome;
            }
        }
        return $filhos;
    }

    // =====================================================================
    // Normalização
    // =====================================================================

    /** Registro novo, com todos os campos nos valores padrão. */
    public static function registroVazio(string $ent): array
    {
        $reg = [self::pk($ent) => null];
        foreach (self::campos($ent) as $nome => $def) {
            $reg[self::campo($ent, $nome)] = self::padrao($def);
        }
        return $reg;
    }

    private static function padrao(array $def): mixed
    {
        if (array_key_exists('padrao', $def)) {
            return $def['padrao'];
        }
        if (!empty($def['nulo'])) {
            return null;
        }

        return match ($def['tipo']) {
            'inteiro', 'fk', 'data', 'dataHora' => null,
            'decimal'                           => 0.0,
            'booleano'                          => 0,
            default                             => '',
        };
    }

    /**
     * Converte a entrada crua (formulário, JSON ou planilha) para o formato
     * exato de armazenamento — que é o mesmo das futuras colunas MySQL. Por
     * isso trocar o repositório JSON pelo de banco não muda nada acima.
     *
     * @param array      $entrada valores crus, com ou sem o sufixo da entidade
     * @param array|null $atual   registro existente, na edição parcial
     */
    public static function normalizar(string $ent, array $entrada, ?array $atual = null): array
    {
        $reg = $atual ?? self::registroVazio($ent);

        foreach (self::campos($ent) as $nome => $def) {
            $col = self::campo($ent, $nome);

            // Aceita tanto 'nome' quanto 'nome_hotel' na entrada.
            if (array_key_exists($col, $entrada)) {
                $bruto = $entrada[$col];
            } elseif (array_key_exists($nome, $entrada)) {
                $bruto = $entrada[$nome];
            } else {
                continue; // ausente: preserva o que já estava
            }

            $reg[$col] = self::converter($bruto, $def);
        }

        $pk = self::pk($ent);
        if (isset($entrada[$pk]) && $entrada[$pk] !== '') {
            $reg[$pk] = (int)$entrada[$pk];
        }

        return $reg;
    }

    private static function converter(mixed $v, array $def): mixed
    {
        switch ($def['tipo']) {
            case 'texto':
            case 'textoLongo':
                if ($v === null && !empty($def['nulo'])) {
                    return null;
                }
                $s = trim((string)$v);
                if ($s === '' && !empty($def['nulo'])) {
                    return null;
                }
                if (!empty($def['maiusculo'])) {
                    $s = mb_strtoupper($s, 'UTF-8');
                }
                if (isset($def['max']) && mb_strlen($s, 'UTF-8') > $def['max']) {
                    $s = mb_substr($s, 0, $def['max'], 'UTF-8');
                }
                return $s;

            case 'inteiro':
                if ($v === '' || $v === null) {
                    return $def['padrao'] ?? null;
                }
                $n = (int)$v;
                if (isset($def['min'])) {
                    $n = max($def['min'], $n);
                }
                if (isset($def['max'])) {
                    $n = min($def['max'], $n);
                }
                return $n;

            case 'fk':
                if ($v === '' || $v === null || (int)$v === 0) {
                    return null;
                }
                return (int)$v;

            case 'decimal':
                return self::parseMoeda($v);

            case 'booleano':
                return self::parseBooleano($v) ? 1 : 0;

            case 'data':
                return self::parseData($v);

            case 'dataHora':
                $s = trim((string)$v);
                return $s === '' ? null : $s;

            case 'enum':
                $s = trim((string)$v);
                return in_array($s, $def['valores'], true) ? $s : ($def['padrao'] ?? $def['valores'][0]);
        }

        return $v;
    }

    // =====================================================================
    // Parsers — deliberadamente compatíveis com os dados legados
    // =====================================================================

    /**
     * Reproduz a heurística do parseVal() do Apps Script, porque os valores
     * gravados na planilha dependem dela: "36.380,21" (BR), "36,380.21" (US)
     * e o caso ambíguo "1.487", que ali significa 1487 — um único ponto com
     * exatamente 3 dígitos depois é separador de milhar, não decimal.
     * Mudar essa regra agora multiplicaria valores legados por 1000.
     */
    public static function parseMoeda(mixed $v): float
    {
        if (is_int($v) || is_float($v)) {
            return round((float)$v, 2);
        }

        $s = trim((string)$v);
        if ($s === '') {
            return 0.0;
        }

        // Remove símbolo de moeda e espaços (inclusive o não-quebrável do Sheets).
        $s = str_replace(['R$', 'r$', "\u{00A0}", ' '], '', $s);
        if ($s === '' || !preg_match('/\d/', $s)) {
            return 0.0;
        }

        $temVirgula = str_contains($s, ',');
        $temPonto   = str_contains($s, '.');

        if ($temVirgula && $temPonto) {
            $s = strrpos($s, ',') > strrpos($s, '.')
                ? str_replace(',', '.', str_replace('.', '', $s))   // BR
                : str_replace(',', '', $s);                         // US
        } elseif ($temVirgula) {
            $s = str_replace(',', '.', str_replace('.', '', $s));
        } elseif ($temPonto) {
            $partes = explode('.', $s);
            if (count($partes) === 2 && strlen($partes[1]) === 3 && $partes[0] !== '') {
                $s = str_replace('.', '', $s); // milhar: "1.487" => 1487
            }
        }

        return is_numeric($s) ? round((float)$s, 2) : 0.0;
    }

    /** Aceita o que a planilha produz: true, 'TRUE', 'true', 1, '1', 'Sim'. */
    public static function parseBooleano(mixed $v): bool
    {
        if (is_bool($v)) {
            return $v;
        }
        if (is_int($v) || is_float($v)) {
            return (int)$v === 1;
        }

        $s = mb_strtolower(trim((string)$v), 'UTF-8');
        return in_array($s, ['1', 'true', 'sim', 'x', 'on', 'enviada', 'aberta'], true);
    }

    /**
     * Devolve 'YYYY-MM-DD' ou null. Trata os formatos que aparecem nos dados:
     * ISO, ISO com hora e dd/mm/yyyy. Zero-date do MySQL e strings cruas do
     * Sheets ("Mon Jan 15 2026 ... GMT-0300") viram null — a segunda é lixo
     * conhecido de célula com fórmula quebrada.
     */
    public static function parseData(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }

        $s = trim((string)$v);
        if ($s === '' || str_starts_with($s, '0000-00-00')) {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m)) {
            return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? "{$m[1]}-{$m[2]}-{$m[3]}" : null;
        }

        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})#', $s, $m)) {
            return checkdate((int)$m[2], (int)$m[1], (int)$m[3]) ? "{$m[3]}-{$m[2]}-{$m[1]}" : null;
        }

        // Sobra o formato cru do JS Date ("Mon Jan 15 2026 00:00:00 GMT-0300"),
        // que o Sheets devolve quando a célula não é uma data de verdade.
        // Dois cuidados: o strtotime do PHP, ao ver um dia-da-semana que não
        // corresponde à data, AVANÇA para o próximo dia com aquele nome (Jan 15
        // de 2026 é quinta, então "Mon Jan 15 2026" viraria dia 19); e manter o
        // fuso na string faz a data escorregar um dia conforme o TZ do servidor.
        // Por isso reduzimos a "Mês Dia Ano" antes de interpretar.
        $limpo = preg_replace('/^(Mon|Tue|Wed|Thu|Fri|Sat|Sun)[a-z]*,?\s+/i', '', $s) ?? $s;
        if (preg_match('/^([A-Za-z]{3,})\s+(\d{1,2})\s+(\d{4})/', $limpo, $m)) {
            $limpo = "{$m[1]} {$m[2]} {$m[3]}";
        }

        $ts = strtotime($limpo);
        return $ts === false ? null : date('Y-m-d', $ts);
    }

    // =====================================================================
    // Validação
    // =====================================================================

    /**
     * @return array<string,string[]> erros por nome de coluna; vazio = válido
     */
    public static function validar(string $ent, array $reg): array
    {
        $erros = [];

        foreach (self::campos($ent) as $nome => $def) {
            $col = self::campo($ent, $nome);
            $v   = $reg[$col] ?? null;

            if (!empty($def['req'])) {
                $vazio = $v === null || $v === '' || ($def['tipo'] === 'fk' && !$v);
                if ($vazio) {
                    $erros[$col][] = self::rotulo($ent, $nome) . ' é obrigatório.';
                    continue;
                }
            }

            if ($def['tipo'] === 'enum' && $v !== null && !in_array((string)$v, $def['valores'], true)) {
                $erros[$col][] = 'Valor inválido para ' . self::rotulo($ent, $nome) . '.';
            }

            if ($def['tipo'] === 'decimal' && $v < 0) {
                $erros[$col][] = self::rotulo($ent, $nome) . ' não pode ser negativo.';
            }
        }

        return $erros;
    }
}
