<?php

require_once __DIR__ . '/Schema.php';

/**
 * Tradução entre as entidades lógicas e a tabela `fornecedorEvento`.
 *
 * A aplicação continua pensando em sete entidades — hotel, equipamento,
 * videomaker, transporte, frete, material e custoExtra —, que é como
 * `Schema.php` as declara e como formulários, validação, cálculos e telas
 * as manipulam.
 * No banco, todas viram linhas de uma tabela só, separadas por
 * `categoria_fornecedorEvento`.
 *
 * Esta classe é o único lugar que sabe das duas formas. Enquanto ela estiver
 * correta, trocar o repositório de arquivo por banco não muda uma linha acima.
 *
 * Três campos lógicos NÃO têm coluna, porque vêm do CRM por JOIN com `evento`:
 * `cidade`, `endereco` e, nos filhos, `dataEvento`. Guardá-los aqui criaria
 * duas versões da mesma verdade — e elas já divergem hoje, com o CRM dizendo
 * "Barueri" onde a operação diz "Alphaville".
 */
final class MapaFornecedor
{
    /** Campos comuns a toda linha, com o mesmo sentido em qualquer categoria. */
    private const COMUNS = [
        'valor'  => 'custo',
        'status' => 'status',
        'obs'    => 'obs',
    ];

    /**
     * Campo lógico => coluna física, por entidade.
     *
     * O que não aparece aqui nem em COMUNS é deliberadamente descartado —
     * ver DESCARTADOS logo abaixo.
     */
    private const MAPA = [
        'hotel' => [
            'nome'              => 'nome',
            'uf'                => 'uf',
            'capacidade'        => 'capacidade',
            'data'              => 'data',
            'tipoPag'           => 'tipoPagamento',
            'rating'            => 'avaliacao',
            'status'            => 'situacaoContrato',   // sobrepõe COMUNS
            'dataAss'           => 'dataAssinatura',
            'linkContrato'      => 'linkContrato',
            'nf'                => 'nf',
            'dataNf'            => 'dataNf',
            'oc'                => 'oc',
            'dataOc'            => 'dataOc',
            'ocNum'             => 'numeroOc',
            'quartosReserva'    => 'reservaQuarto',
            'quartosConfirmado' => 'quartoConfirmado',
            'entregaStatus'     => 'statusEntrega',
            'entregaData'       => 'dataEntrega',
            'entregaMateriais'  => 'descricaoEntrega',
            'entregaObs'        => 'obsEntrega',
            'responsavelAv'     => 'responsavelAv',
            'idEventoCrm'       => 'idEvento',
        ],

        'equipamento' => [
            'fornecedor' => 'nome',
            'contato'    => 'contato',
            'oc'         => 'oc',
            'ocNum'      => 'numeroOc',
            'dataOc'     => 'dataOc',
            'rating'     => 'avaliacao',
            'entrega'    => 'statusEntrega',
            'hora'       => 'horaEntrega',
        ],

        'videomaker' => [
            'nome'    => 'nome',
            'contato' => 'contato',
            'data'    => 'data',
            'oc'      => 'numeroOc',
            'tipo'    => 'tipoMidia',
        ],

        'transporte' => [
            'tipo'        => 'modal',
            'cia'         => 'companhia',
            'trecho'      => 'trecho',
            'dataViagem'  => 'dataViagem',
            'qtd'         => 'quantidade',
            'localizador' => 'bilhete',
            'passageiro'  => 'passageiros',
        ],

        'frete' => [
            'doc'         => 'documento',
            'dataEnvio'   => 'dataEnvio',
            'dataEntrega' => 'dataEntrega',
        ],

        'material' => [
            'qtde'   => 'quantidade',
            'avulso' => 'avulso',
        ],

        /*
         * Custo extra: o que não cabe em nenhuma das outras categorias mas
         * pesa no custo do evento. `descricao` ocupa a coluna `nome` porque
         * é o rótulo da linha — nas listagens é ela que identifica o custo,
         * do mesmo jeito que o nome do videomaker identifica o dele.
         */
        'custoExtra' => [
            'descricao' => 'nome',
            'ocNum'     => 'numeroOc',
            'data'      => 'data',
        ],
    ];

    /**
     * Campos lógicos que a tabela não guarda, e de onde a informação vem.
     *
     * Documentado em código para que a perda seja consciente: quem for
     * procurar `cidade_videomaker` no banco precisa saber que não some por
     * descuido, some porque a cidade é do evento, não do videomaker.
     */
    public const DESCARTADOS = [
        'hotel'       => ['cidade' => 'evento.cidade_evento', 'endereco' => 'evento.endereco_evento', 'nomeEvento' => 'evento.nome_evento'],
        'equipamento' => [],
        'videomaker'  => ['cidade' => 'evento.cidade_evento', 'mes' => 'derivado da data do evento'],
        'transporte'  => ['cidade' => 'evento.cidade_evento', 'dataEvento' => 'evento.data_evento'],
        'frete'       => ['cidade' => 'evento.cidade_evento', 'dataEvento' => 'evento.data_evento'],
        'material'    => ['cidade' => 'evento.cidade_evento', 'dataEvento' => 'evento.data_evento'],
        'custoExtra'  => ['cidade' => 'evento.cidade_evento', 'dataEvento' => 'evento.data_evento'],
    ];

    /** Entidades lógicas que viram linha desta tabela. */
    public static function entidades(): array
    {
        return array_keys(self::MAPA);
    }

    public static function suporta(string $ent): bool
    {
        return isset(self::MAPA[$ent]);
    }

    /** A categoria gravada na coluna discriminadora. */
    public static function categoria(string $ent): string
    {
        return $ent;
    }

    /** @return array<string,string> campo lógico => coluna física (sem sufixos) */
    public static function campos(string $ent): array
    {
        if (!isset(self::MAPA[$ent])) {
            throw new InvalidArgumentException("Entidade sem mapeamento: {$ent}");
        }
        // O específico sobrepõe o comum — é assim que `status` do hotel vira
        // `situacaoContrato` em vez de `status`.
        return self::MAPA[$ent] + self::COMUNS;
    }

    /** Coluna física completa, com o sufixo da tabela. */
    public static function coluna(string $fisico): string
    {
        return $fisico . '_fornecedorEvento';
    }

    /**
     * Data principal da linha, por categoria. É o que alimenta
     * `data_fornecedorEvento` quando a entidade não tem um campo `data`
     * próprio — sem isso, um transporte ficaria sem data nenhuma na tabela.
     */
    public static function campoData(string $ent): ?string
    {
        return match ($ent) {
            'hotel', 'videomaker', 'custoExtra' => 'data',
            'transporte'                        => 'dataViagem',
            'frete'                             => 'dataEnvio',
            'material'                          => 'dataEvento',
            default                             => null,
        };
    }

    /** Entidades que penduram num evento (todas menos o próprio hotel). */
    public static function filhos(): array
    {
        return array_values(array_diff(self::entidades(), ['hotel']));
    }
}
