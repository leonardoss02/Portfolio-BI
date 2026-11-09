<?php
/**
 * Parâmetros do gerador de dados fictícios.
 *
 * É o único lugar para ajustar volumes, taxas de conversão, pessoas e
 * sazonalidade. O código em src/ só lê daqui.
 */

return [
    // Mesma semente = mesma base (no mesmo dia).
    'semente' => 2026,

    // Primeiro dia com dados. Cobre as datas de negócio fixas no código
    // (ciclo do Jurídico em 19/05/2025, campanha de metas em 18/05/2026).
    'inicio' => '2025-01-01',

    // Multiplica todos os volumes. Os testes usam 0.1 para rodar rápido.
    'escala' => 1.0,

    'calendario' => [
        // Quanto o movimento cresce em 24 meses (0.35 = 35% a mais).
        'crescimento24Meses' => 0.35,
        // Peso por mês (1 = janeiro). Meses ausentes valem 1.0.
        'pesoMes' => [12 => 0.75, 1 => 0.80],
        // Sábado e domingo têm menos da metade do movimento.
        'pesoFimDeSemana' => 0.45,
    ],

    // -------------------------------------------------------------------------
    // Equipe interna. Estes nomes estão escritos no código dos dashboards e
    // precisam bater letra por letra (tests/seed/PessoasTeste.php confere).
    // -------------------------------------------------------------------------
    'equipe' => [
        // controle/executive_controle.php (ESPECIALISTAS_ANALITICO)
        'especialistas' => ['Rafael Torres', 'Paulo Nogueira', 'Camila Duarte', 'Juliana Prado', 'Beatriz Lemos', 'Ricardo Albuquerque'],
        // controle/comercial_controle.php (EQUIPE_COMERCIAL2)
        'comercial2' => ['Paulo Nogueira', 'Rafael Torres', 'Camila Duarte', 'Beatriz Lemos', 'Juliana Prado'],
        // Só no time do ConsultaCom2 (controle/consultacom2_controle.php, EQUIPE)
        'comercial2_outros' => ['Gustavo Leal'],
        // Saíram do time, mas ainda têm cards no CRM
        'ex_comercial2' => ['Ana Moraes', 'Sônia Teles', 'Cláudia Rocha'],
        // controle/helenacastro_controle.php (RESPONSAVEL)
        'helena' => 'Helena Castro',
        // controle/comercial_controle.php
        'cadeiras' => ['Patrícia Lopes', 'Débora Fontes', 'Marina Viana'],
        'comercial1_evento' => ['Fernanda Matos', 'Priscila Rangel'],
        'comercial1_digital' => ['Eduardo Pires', 'Larissa Campos'],
        'comercial3' => ['Thiago Dos Santos Ramos', 'Aline Barros'],
        // controle/chatwoot_controle.php (CW_TEAM). "Fernanda matos" com m minúsculo é assim no código.
        'atendimento' => ['Helena', 'Eduardo Pires', 'Priscila Rangel', 'Fernanda matos'],
        // Atende conversas, mas fica fora do time que o dashboard mostra
        'atendimento_fora' => 'Carla Nunes',
        // CSMs da campanha de metas (controle/comercial4_controle.php, LIGA_CSM): ID do Bitrix => nome completo
        'csm' => [271 => 'Simone Arantes', 453 => 'Patrícia Moura', 725 => 'Viviane Correia', 727 => 'Tânia Freitas'],
        // CSMs fora da campanha de metas
        'csm_outros' => [300 => 'Rogério Antunes', 301 => 'Luciana Prates'],
        // Jurídico (nomes livres, sem vírgula)
        'case_managers' => ['Renata Siqueira', 'Marcelo Quintela', 'Carolina Dias', 'Felipe Barreto'],
        'advs' => ['Bruno Teixeira', 'Isabela Rocha', 'Gabriel Menezes', 'Natália Faria', 'Otávio Lins', 'Vanessa Coelho'],
        // Quem dá a palestra nos eventos presenciais
        'palestrantes' => ['Camila Duarte', 'Juliana Prado', 'Rafael Torres', 'Beatriz Lemos'],
        // Vendedores com painel próprio (controle/{nome}_controle.php)
        'vendedoresComPainel' => ['Rafael Torres', 'Juliana Prado', 'Camila Duarte', 'Beatriz Lemos'],
        'jornada_extra' => 'Sistema Admin',
        'venda_direta' => 'Venda Direta',
    ],

    // Clientes fictícios: nome + sobrenome sorteados destas listas.
    'clientes' => [
        'nomes' => [
            'Ana', 'Bruno', 'Carla', 'Daniel', 'Eduarda', 'Fábio', 'Gabriela', 'Henrique', 'Isabela', 'João',
            'Karina', 'Lucas', 'Mariana', 'Nicolas', 'Olívia', 'Pedro', 'Quésia', 'Rafaela', 'Samuel', 'Tatiana',
            'Ulisses', 'Valéria', 'Wagner', 'Yasmin', 'André', 'Beatriz', 'Caio', 'Débora', 'Elias', 'Fernanda',
            'Gustavo', 'Helena', 'Igor', 'Júlia', 'Leonardo', 'Letícia', 'Marcelo', 'Natália', 'Otávio', 'Paula',
            'Renato', 'Sabrina', 'Thiago', 'Vanessa', 'Vinícius', 'Aline', 'Alexandre', 'Amanda', 'Antônio', 'Bianca',
            'Camila', 'Carlos', 'Cecília', 'Cristiano', 'Daniela', 'Diego', 'Elaine', 'Emerson', 'Fabiana', 'Felipe',
            'Flávia', 'Giovana', 'Guilherme', 'Heloísa', 'Hugo', 'Ingrid', 'Jéssica', 'Jorge', 'Juliano', 'Larissa',
            'Laura', 'Luana', 'Luciana', 'Luiz', 'Manuela', 'Marcos', 'Mateus', 'Melissa', 'Miguel', 'Mônica',
            'Murilo', 'Nathalia', 'Patrícia', 'Priscila', 'Raquel', 'Regina', 'Ricardo', 'Roberta', 'Rodrigo', 'Rosana',
            'Sandra', 'Sérgio', 'Silvia', 'Simone', 'Sofia', 'Tânia', 'Tiago', 'Valentina', 'Vitor', 'Viviane',
            'Adriana', 'Alberto', 'Alice', 'Arthur', 'Bernardo', 'Bruna', 'Cláudio', 'Denise', 'Eduardo', 'Elisa',
            'Fernando', 'Gisele', 'Heitor', 'Joana', 'Leandro', 'Lívia', 'Maurício', 'Nelson', 'Paulo', 'Renata',
        ],
        'sobrenomes' => [
            'Silva', 'Santos', 'Oliveira', 'Souza', 'Rodrigues', 'Ferreira', 'Alves', 'Pereira', 'Lima', 'Gomes',
            'Costa', 'Ribeiro', 'Martins', 'Carvalho', 'Almeida', 'Lopes', 'Soares', 'Fernandes', 'Vieira', 'Barbosa',
            'Rocha', 'Dias', 'Nascimento', 'Andrade', 'Moreira', 'Nunes', 'Marques', 'Machado', 'Mendes', 'Freitas',
            'Cardoso', 'Ramos', 'Gonçalves', 'Santana', 'Teixeira', 'Araújo', 'Pinto', 'Correia', 'Cavalcanti', 'Monteiro',
            'Moura', 'Batista', 'Campos', 'Cunha', 'Duarte', 'Farias', 'Fonseca', 'Guimarães', 'Leite', 'Macedo',
            'Medeiros', 'Miranda', 'Nogueira', 'Pacheco', 'Peixoto', 'Prado', 'Queiroz', 'Rezende', 'Sales', 'Sampaio',
            'Siqueira', 'Tavares', 'Toledo', 'Vasconcelos', 'Xavier', 'Azevedo', 'Barros', 'Brito', 'Castro', 'Coelho',
            'Dantas', 'Esteves', 'Figueiredo', 'Garcia', 'Henriques', 'Jardim', 'Lacerda', 'Lins', 'Magalhães', 'Matos',
            'Meireles', 'Neves', 'Novaes', 'Pires', 'Porto', 'Quintela', 'Rangel', 'Reis', 'Sá', 'Serra',
            'Telles', 'Valente', 'Viana', 'Aguiar', 'Amaral', 'Antunes', 'Bastos', 'Bezerra', 'Borges', 'Brandão',
            'Calixto', 'Camargo', 'Chaves', 'Couto', 'Cruz', 'Diniz', 'Estrela', 'Falcão', 'Franco', 'Galvão',
            'Godoy', 'Holanda', 'Leal', 'Louzada', 'Maia', 'Muniz', 'Paiva', 'Pimentel', 'Rios', 'Torres',
        ],
    ],

    // Motivos de descarte (mais de 15, para aparecer o "Top 15 + Outros").
    'motivosDescarte' => [
        'Sem interesse no momento', 'Telefone inválido', 'Fora do perfil', 'Já é cliente',
        'Sem condição financeira', 'Não respondeu após 5 tentativas', 'Procurou outro escritório',
        'Quer apenas informação', 'Idade fora do perfil', 'Sem formação superior', 'Desistiu de imigrar',
        'Prazo muito longo', 'Achou o valor alto', 'Problema de documentação', 'Cônjuge não concorda',
        'Vai tentar sozinho', 'Lead duplicado', 'Contato errado',
    ],

    // Vistos escolhidos nos combos
    'vistos' => ['EB-2 NIW', 'EB-5', 'E-2', 'L-1', 'O-1'],

    // -------------------------------------------------------------------------
    // Eventos e câmbio
    // -------------------------------------------------------------------------
    'eventos' => [
        'primeiroSabado' => '2025-01-04',
        // Os próximos eventos já existem (vendendo cadeira) até N dias à frente.
        'diasFuturos' => 45,
        'semanasSemRepetirCidade' => 8,
        // Um webinar a cada 13 semanas; o 1:1 cai 6 semanas depois do webinar.
        'intervaloWebinar' => 13,
        'deslocamento1a1' => 6,
        // Cidades do "On The Road" e a UF de cada uma. Todas existem em
        // lib/hotel/cidades-legado.php (sem as grafias duplicadas sem acento).
        'cidades' => [
            'Alphaville' => 'SP', 'Rio de Janeiro' => 'RJ', 'Blumenau' => 'SC', 'São José dos Campos' => 'SP',
            'Belo Horizonte' => 'MG', 'Sorocaba' => 'SP', 'Belém' => 'PA', 'Curitiba' => 'PR', 'Vitória' => 'ES',
            'Jundiaí' => 'SP', 'São José do Rio Preto' => 'SP', 'Manaus' => 'AM', 'Porto Alegre' => 'RS',
            'Goiânia' => 'GO', 'Uberlândia' => 'MG', 'Mogi das Cruzes' => 'SP', 'São Carlos' => 'SP',
            'Londrina' => 'PR', 'Caxias do Sul' => 'RS', 'Palmas' => 'TO', 'Campo Grande' => 'MS', 'Natal' => 'RN',
            'Porto Velho' => 'RO', 'Salvador' => 'BA', 'Florianópolis' => 'SC', 'Maringá' => 'PR',
            'Ribeirão Preto' => 'SP', 'Fortaleza' => 'CE', 'Recife' => 'PE', 'Brasília' => 'DF', 'Joinville' => 'SC',
            'Maceió' => 'AL', 'São Paulo' => 'SP', 'Balneário Camboriú' => 'SC', 'Cuiabá' => 'MT', 'Uberaba' => 'MG',
            'Campinas' => 'SP', 'Santos' => 'SP', 'Juiz de Fora' => 'MG', 'Aracajú' => 'SE', 'Itajaí' => 'SC',
            'Piracicaba' => 'SP', 'São Caetano do Sul' => 'SP', 'Guarulhos' => 'SP', 'Duque de Caxias' => 'RJ',
        ],
    ],

    // -------------------------------------------------------------------------
    // Marketing
    // -------------------------------------------------------------------------
    'marketing' => [
        // A campanha de cada evento roda nos N dias antes dele.
        'diasDivulgacaoEvento' => 30,
        // Gasto por anúncio por dia, em USD (contas em reais recebem o valor convertido).
        'gastoDiarioEvento' => [10, 35],
        'gastoDiarioDigital' => [15, 70],
        'cliquesDiarios' => [20, 140],
        // Parte das linhas de evento chega sem identificador (o código cai no nome da campanha).
        'chanceSemIdentificador' => 0.10,
        'conjuntosEvento' => [
            ['nome' => 'Interesse imigração', 'anuncios' => ['Vídeo palestrante']],
            ['nome' => 'Público semelhante', 'anuncios' => ['Carrossel depoimentos']],
        ],
        'formDigital' => 'consulta-digital',
        'digitais' => [
            ['nome' => 'Consulta Digital - Meta', 'origem' => 'meta', 'conjuntos' => [
                ['nome' => 'Profissionais 30-45', 'anuncios' => ['Vídeo depoimento', 'Imagem EUA']],
                ['nome' => 'Interesse EUA', 'anuncios' => ['Carrossel vistos', 'Vídeo especialista']],
            ]],
            ['nome' => 'Consulta Digital - Google', 'origem' => 'google', 'conjuntos' => [
                ['nome' => 'Pesquisa visto americano', 'anuncios' => ['Texto visto', 'Texto green card']],
                ['nome' => 'Pesquisa morar nos EUA', 'anuncios' => ['Texto morar EUA', 'Texto imigração']],
            ]],
            ['nome' => 'Remarketing Consulta', 'origem' => 'meta', 'conjuntos' => [
                ['nome' => 'Visitantes do site', 'anuncios' => ['Lembrete consulta', 'Prova social']],
                ['nome' => 'Engajados Instagram', 'anuncios' => ['Reels caso de sucesso', 'Story oferta']],
            ]],
            ['nome' => 'EB-2 NIW Profissionais', 'origem' => 'google', 'conjuntos' => [
                ['nome' => 'Engenheiros e TI', 'anuncios' => ['Texto EB-2 NIW', 'Display EB-2 NIW']],
                ['nome' => 'Saúde e pesquisa', 'anuncios' => ['Texto NIW saúde', 'Display NIW saúde']],
            ]],
        ],
        // Sem custo: chegam por canais próprios.
        'organicas' => ['ORG Site', 'ORG Instagram', 'Negado Cliente'],
        'indicacoes' => ['IND Clientes', 'F&F', 'MGM'],
    ],

    // -------------------------------------------------------------------------
    // Leads
    // -------------------------------------------------------------------------
    'leads' => [
        // Leads num dia de peso 1 (o calendário aumenta ou diminui).
        'porDia' => 36,
        // Canal de origem (pesos).
        'canais' => ['evento' => 45, 'digital' => 40, 'organico' => 8, 'indicacao' => 7],
        'chanceQualificado' => 0.45,
        // Dos não qualificados, quantos são descartados com motivo.
        'chanceDescarteNaoQualificado' => 0.25,
        'chanceAfiliado' => 0.08,
        // No digital, parte dos leads vai para a Helena Castro (vendedora com painel próprio).
        'chanceHelenaNoDigital' => 0.25,
        // Tipo de lead por canal (pesos). "(nulo)" vira NULL no banco.
        'tipoLeadPorCanal' => [
            'evento' => ['OTR' => 90, 'OTW' => 10],
            'digital' => ['Consulta' => 70, 'Venda direta' => 12, 'B2B' => 12, '' => 3, '(nulo)' => 3],
            'organico' => ['Consulta' => 100],
            'indicacao' => ['Indicação' => 100],
        ],
        'profissoes' => [
            'Engenheiro(a)', 'Médico(a)', 'Advogado(a)', 'Analista de sistemas', 'Administrador(a)',
            'Enfermeiro(a)', 'Professor(a)', 'Empresário(a)', 'Contador(a)', 'Arquiteto(a)',
            'Dentista', 'Designer',
        ],
        'temposExperiencia' => ['1 a 3 anos', '3 a 5 anos', '5 a 10 anos', '10 anos ou mais'],
        'formacoes' => ['Ensino médio', 'Graduação', 'Pós-graduação', 'Mestrado', 'Doutorado'],
    ],

    // -------------------------------------------------------------------------
    // Primeira venda: cadeira do evento e consulta
    // -------------------------------------------------------------------------
    'venda' => [
        // Qualificado do On The Road que compra a cadeira.
        'chanceCadeira' => 0.30,
        // Quem tem cadeira de evento passado e faz check-in.
        'chanceCheckin' => 0.70,
        // Quem fez check-in e compra a consulta no próprio evento.
        'chanceConsultaNoEvento' => 0.35,
        // Qualificado digital, orgânico, de indicação, webinar ou 1:1 que compra a consulta.
        'chanceConsultaDigital' => 0.22,
        'diasAteConsultaDigital' => 10,
        // Parte das cadeiras é vendida pela Helena Castro ou como "Venda Direta".
        'chanceCadeiraHelena' => 0.05,
        'chanceVendaDireta' => 0.15,
        // Card de check-in com o valor do ingresso preenchido.
        'chanceValorIngresso' => 0.90,
        'valorCadeiraBrl' => 100,
        'valorConsultaBrl' => 690,
    ],

    // -------------------------------------------------------------------------
    // Comercial 2 (C5): da consulta vendida ao combo
    // -------------------------------------------------------------------------
    'c5' => [
        // Quem atende a consulta (pesos).
        'responsaveis' => [
            'Paulo Nogueira' => 17, 'Rafael Torres' => 17, 'Camila Duarte' => 17, 'Beatriz Lemos' => 17,
            'Juliana Prado' => 17, 'Ricardo Albuquerque' => 6, 'Gustavo Leal' => 6,
            'Ana Moraes' => 1, 'Sônia Teles' => 1, 'Cláudia Rocha' => 1,
        ],
        'chanceSemAgendamento' => 0.05,
        'diasAteAgendamento' => 14,
        'chanceRealizada' => 0.85,
        // Parte das consultas é realizada no mês seguinte ao agendado.
        'chanceRealizadaNoMesSeguinte' => 0.08,
        'chanceMotivoNaoCompareceu' => 0.10,
        'chanceSinal' => 0.70,
        'chanceFpAgendado' => 0.65,
        'chanceFpRealizado' => 0.85,
        // Realizou a consulta, não agendou FP e desistiu (com motivo).
        'chancePerdaSemFp' => 0.40,
        'chanceSerasa' => 0.70,
        // Depois do FP realizado (pesos).
        'desfechoFp' => ['combo' => 50, 'enviado' => 10, 'perdido' => 25, 'aguardando' => 15],
        'fasesDePerda' => ['Comercial 3' => 60, 'Distrato' => 20, 'Lead sem qualificação' => 20],
        'chanceDistratoAposCombo' => 0.06,
        'chanceDistratoSemContrato' => 0.10,
        // Lead de indicação ou B2B que fecha combo direto em C29/C31.
        'chanceComboOutroPipe' => 0.02,
    ],

    // Retenção (C1): tenta recuperar quem perdeu como "Comercial 3"
    'c1' => [
        'chanceRetencao' => 0.30,
        'chanceAgendada' => 0.80,
        'chanceRealizada' => 0.60,
        'chanceCombo' => 0.20,
    ],

    // -------------------------------------------------------------------------
    // Segmento Dentistas (C33), existe desde 31/08/2026 (data fixa no código)
    // -------------------------------------------------------------------------
    'dentistas' => [
        'inicio' => '2026-08-31',
        'porDiaUtil' => 4,
        'chanceCheckin' => 0.60,
        'chanceConsulta' => 0.30,
        // Das consultas realizadas, quantas viram contrato (≈10% do total).
        'chanceContratoAposConsulta' => 0.35,
        'cidades' => ['São Paulo', 'Curitiba', 'Belo Horizonte', 'Porto Alegre', 'Goiânia', 'Recife'],
        'anuncios' => ['Vídeo implantodontia EUA', 'Carrossel validação de diploma', 'Depoimento dentista', 'Live encontro'],
        'profissoes' => ['Cirurgião-dentista', 'Ortodontista', 'Implantodontista'],
    ],

    // -------------------------------------------------------------------------
    // Campanha de metas do CS ("Liga"). Início, tamanho da carteira, produtos e
    // IDs de status vêm de controle/comercial4_controle.php.
    // -------------------------------------------------------------------------
    'liga' => [
        // Clientes da carteira com todos os produtos mapeados (a meta do código é 378).
        'mapeados' => 390,
        // Status sorteados para cada produto de um cliente mapeado (pesos).
        'pesosStatus' => [
            'fechada' => 12, 'em_negociacao' => 10, 'declinada' => 8, 'orcamento_parceiro' => 4,
            'abordagem' => 20, 'sem_oportunidade' => 30, 'interesse_futuro' => 16,
        ],
        'vendasMinimasNaSemana' => 8,
    ],

    // Atendimento (Chatwoot)
    'atendimento' => [
        'chanceConversa' => 0.40,
        'chanceSemResposta' => 0.03,
        'chanceSentinela' => 0.02,
        'chanceMensagemRecebida' => 0.75,
        'minimoConversasHoje' => 6,
    ],

    // Faturas (USD)
    'financeiro' => [
        'chancePaga' => 0.75,
        'chancePagaComAtraso' => 0.10,
        'chanceCongelada' => 0.08,
        'proporcaoSemCard' => 0.28,
    ],

    // Reais por 1 dólar: passeio aleatório diário entre o mínimo e o máximo.
    'cambio' => [
        'inicial' => 5.45,
        'minimo' => 5.20,
        'maximo' => 5.90,
        'passo' => 0.02,
    ],
];
