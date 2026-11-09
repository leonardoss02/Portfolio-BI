-- =============================================================================
-- Schema do banco do Portfolio BI (dados fictícios)
--
-- Recria as 18 tabelas que os dashboards leem. Rodar este arquivo apaga tudo:
-- o gerador (database/seed/gerar.php) aplica o schema e em seguida grava os
-- dados.
--
-- Convenções herdadas do sistema original:
--   * tabela no singular, PK id_{tabela}, colunas {campo}_{tabela};
--   * as tabelas que terminam em V2 são o espelho do CRM (Bitrix24);
--   * IDs do Bitrix são texto com dígitos (ex.: '100231'), como no CRM.
--
-- Collation: tudo em utf8mb4_unicode_ci, que é case-insensitive. Isso importa
-- porque o código compara fases escritas de jeitos diferentes ('C9:WON' e
-- 'C9:Won', 'C29:won'). No sistema original as tabelas do CRM eram latin1,
-- por isso alguns joins usam CONVERT(... USING latin1): contra utf8mb4 eles
-- continuam corretos.
--
-- Datas vazias são NULL, nunca '0000-00-00'. O código aceita as duas formas.
-- =============================================================================

SET NAMES utf8mb4;

DROP TABLE IF EXISTS dealV2, leadV2, contactV2, taskV2, lead_tag, deal,
    evento, custoMkt, custoEvento, cambio, fornecedorEvento,
    invoice, orcamentoV2, itemOrcamentoV2, conversaCW, msgCW,
    npsEvento, planilhaConsulta;

-- -----------------------------------------------------------------------------
-- CRM
-- -----------------------------------------------------------------------------

-- Cards (negócios) do CRM. Um cliente tem vários cards, um por pipeline:
-- C9 cadeira do evento, sem prefixo = funil do evento (check-in e consulta
-- vendida no evento), C11 consulta digital, C5 Comercial 2, C1 retenção,
-- C29/C31 outros combos, C3 pós-venda e jurídico, C33 dentistas.
CREATE TABLE dealV2 (
    id_dealV2                        INT AUTO_INCREMENT PRIMARY KEY,
    idBitrix_dealV2                  VARCHAR(20)  NOT NULL,
    contactId_dealV2                 VARCHAR(20)  NULL COMMENT 'liga os cards da mesma pessoa (contactV2.idBitrix)',
    nome_dealV2                      VARCHAR(180) NULL,
    email_dealV2                     VARCHAR(180) NULL,
    telefone_dealV2                  VARCHAR(40)  NULL,
    responsavel_dealV2               VARCHAR(120) NULL,
    responsavelComercial4_dealV2     VARCHAR(120) NULL COMMENT 'CSM do pós-venda',
    responsavelComercial4Id_dealV2   VARCHAR(10)  NULL COMMENT 'ID do CSM no Bitrix (ex.: 271)',
    advogadoAtendimento_dealV2       VARCHAR(120) NULL COMMENT 'case manager do jurídico',
    advMl1_dealV2                    VARCHAR(120) NULL,
    advMl2_dealV2                    VARCHAR(120) NULL,
    advMl3_dealV2                    VARCHAR(120) NULL,
    faseId_dealV2                    VARCHAR(50)  NOT NULL COMMENT 'ID da etapa no Bitrix: C5:WON, C9:WON, WON...',
    fase_dealV2                      VARCHAR(120) NOT NULL COMMENT 'nome da etapa; nunca nulo, senão o card some dos NOT IN',
    tipoLead_dealV2                  VARCHAR(40)  NULL,
    formPreenchido_dealV2            VARCHAR(180) NULL,
    utmCampaign_dealV2               VARCHAR(180) NULL,
    utmContent_dealV2                VARCHAR(180) NULL,
    utmTerm_dealV2                   VARCHAR(180) NULL,
    idEvento_dealV2                  VARCHAR(100) NULL COMMENT 'evento.id_evento como texto',
    idAfiliado_dealV2                VARCHAR(40)  NULL COMMENT 'credenciado que indicou',
    checkin_dealV2                   CHAR(1)      NULL COMMENT '''1'' = compareceu ao evento',
    dentistaEvento_dealV2            VARCHAR(180) NULL,
    profissao_dealV2                 VARCHAR(120) NULL,
    formacaoAcademica_dealV2         VARCHAR(120) NULL,
    vistoEscolhido_dealV2            VARCHAR(20)  NULL,
    criadoEm_dealV2                  DATETIME     NOT NULL,
    dataUltForm_dealV2               DATETIME     NULL,
    dataEvento_dealV2                DATE         NULL,
    dataVenda_dealV2                 DATETIME     NULL,
    dataConsultaAgendada_dealV2      DATE         NULL,
    dataConsultaRealizada_dealV2     DATE         NULL,
    dataFpAgendado_dealV2            DATE         NULL,
    dataFpRealizado_dealV2           DATE         NULL,
    dataContratoComboAssinado_dealV2 DATE         NULL,
    valorVenda_dealV2                DECIMAL(12,2) NULL COMMENT 'BRL',
    valorVendaDolar_dealV2           DECIMAL(12,2) NULL COMMENT 'USD',
    valorComboLiquido_dealV2         DECIMAL(12,2) NULL COMMENT 'USD',
    valorAssessoria_dealV2           DECIMAL(12,2) NULL COMMENT 'BRL',
    motivoDescarte_dealV2            VARCHAR(180) NOT NULL DEFAULT '',
    motivoDescarteComercial2_dealV2  VARCHAR(180) NULL,
    serasaRange_dealV2               VARCHAR(2)   NULL,
    serasaScore_dealV2               INT          NULL,
    statusCpf_dealV2                 VARCHAR(40)  NULL,
    nomeSujo_dealV2                  VARCHAR(3)   NULL,
    socioEmpresaNegativa_dealV2      VARCHAR(3)   NULL,
    valorTotalDividas_dealV2         DECIMAL(12,2) NULL COMMENT 'BRL',
    valorCartorioTotal_dealV2        DECIMAL(12,2) NULL COMMENT 'BRL',
    dataJson_dealV2                  LONGTEXT     NULL COMMENT 'campos extras do Bitrix em JSON (campanha de metas do CS)',
    UNIQUE KEY uq_dealV2_idBitrix (idBitrix_dealV2),
    KEY idx_dealV2_faseId (faseId_dealV2),
    KEY idx_dealV2_idEvento (idEvento_dealV2),
    KEY idx_dealV2_contactId (contactId_dealV2),
    KEY idx_dealV2_responsavel (responsavel_dealV2),
    KEY idx_dealV2_criadoEm (criadoEm_dealV2),
    KEY idx_dealV2_dataVenda (dataVenda_dealV2),
    KEY idx_dealV2_dataConsultaRealizada (dataConsultaRealizada_dealV2),
    KEY idx_dealV2_dataContrato (dataContratoComboAssinado_dealV2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Leads: cada formulário preenchido. Origem nas UTMs e no formulário.
CREATE TABLE leadV2 (
    id_leadV2                INT AUTO_INCREMENT PRIMARY KEY,
    idBitrix_leadV2          VARCHAR(20)  NOT NULL,
    nome_leadV2              VARCHAR(180) NULL,
    email_leadV2             VARCHAR(180) NULL,
    telefoneTexto_leadV2     VARCHAR(40)  NULL,
    responsavel_leadV2       VARCHAR(120) NULL,
    formPreenchido_leadV2    VARCHAR(180) NULL,
    tipoLead_leadV2          VARCHAR(40)  NULL,
    qualificado_leadV2       VARCHAR(3)   NULL COMMENT 'Sim / Não',
    etapa_leadV2             VARCHAR(60)  NULL,
    motivoDescarte_leadV2    VARCHAR(180) NULL,
    utmCampaign_leadV2       VARCHAR(180) NULL,
    utmContent_leadV2        VARCHAR(180) NULL,
    utmTerm_leadV2           VARCHAR(180) NULL,
    idEvento_leadV2          VARCHAR(100) NULL,
    idAfiliado_leadV2        VARCHAR(40)  NULL,
    profissao_leadV2         VARCHAR(120) NULL,
    tempoExp_leadV2          VARCHAR(40)  NULL,
    formacaoAcademica_leadV2 VARCHAR(120) NOT NULL DEFAULT '',
    dataUltForm_leadV2       DATETIME     NULL,
    UNIQUE KEY uq_leadV2_idBitrix (idBitrix_leadV2),
    KEY idx_leadV2_dataUltForm (dataUltForm_leadV2),
    KEY idx_leadV2_idEvento (idEvento_leadV2),
    KEY idx_leadV2_responsavel (responsavel_leadV2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contactV2 (
    id_contactV2           INT AUTO_INCREMENT PRIMARY KEY,
    idBitrix_contactV2     VARCHAR(20)  NOT NULL,
    primeiroNome_contactV2 VARCHAR(90)  NULL,
    sobrenome_contactV2    VARCHAR(90)  NULL,
    UNIQUE KEY uq_contactV2_idBitrix (idBitrix_contactV2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarefas dos cards. priority 2 = alta (fila de alta prioridade do Jurídico).
CREATE TABLE taskV2 (
    id_taskV2       INT AUTO_INCREMENT PRIMARY KEY,
    idDeal_taskV2   VARCHAR(20) NOT NULL COMMENT 'dealV2.idBitrix',
    priority_taskV2 INT         NOT NULL DEFAULT 1,
    status_taskV2   VARCHAR(40) NULL,
    KEY idx_taskV2_idDeal (idDeal_taskV2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Histórico de tags do lead (Jornada do cliente). Exceção à convenção: as
-- colunas não têm sufixo, porque o código as lê assim.
CREATE TABLE lead_tag (
    id_lead_tag   INT AUTO_INCREMENT PRIMARY KEY,
    idBitrix_lead INT          NOT NULL,
    id_leadV2     INT          NULL COMMENT 'NULL quando o lead ainda não foi espelhado em leadV2',
    tag           VARCHAR(60)  NOT NULL,
    descricao     VARCHAR(255) NULL,
    dataCadastro  DATETIME     NOT NULL COMMENT 'sempre com hora: a tela divide data e hora',
    KEY idx_lead_tag_tag (tag, idBitrix_lead, dataCadastro),
    KEY idx_lead_tag_lead (idBitrix_lead, dataCadastro, id_lead_tag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela legada, sempre vazia. Existe só porque o Financeiro faz LEFT JOIN nela.
CREATE TABLE deal (
    id_deal       INT AUTO_INCREMENT PRIMARY KEY,
    idBitrix_deal VARCHAR(20) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Marketing e eventos
-- -----------------------------------------------------------------------------

-- Eventos presenciais ("On The Road"), webinars e encontros 1:1.
-- id_evento precisa ser PRIMARY KEY: o módulo de Gestão de Eventos agrupa por ela.
CREATE TABLE evento (
    id_evento          INT AUTO_INCREMENT PRIMARY KEY,
    nome_evento        VARCHAR(180) NOT NULL,
    data_evento        DATE         NOT NULL,
    cidade_evento      VARCHAR(120) NULL,
    local_evento       VARCHAR(180) NULL,
    endereco_evento    VARCHAR(255) NULL,
    palestrante_evento VARCHAR(120) NULL,
    nomeForm_evento    VARCHAR(180) NULL COMMENT 'formulário do evento = custoMkt.identificador',
    status_evento      TINYINT      NOT NULL DEFAULT 1,
    deletado_evento    TINYINT      NOT NULL DEFAULT 0,
    KEY idx_evento_data (data_evento),
    KEY idx_evento_nomeForm (nomeForm_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gasto diário por anúncio. Moeda: contas 'Dollar 1'/'Dollar 2' em USD,
-- as demais em BRL (convertidas pelo câmbio do mesmo dia).
CREATE TABLE custoMkt (
    id_custoMkt            INT AUTO_INCREMENT PRIMARY KEY,
    data_custoMkt          DATE          NOT NULL COMMENT 'sem hora: o join com cambio é por igualdade',
    valor_custoMkt         DECIMAL(12,2) NOT NULL,
    origem_custoMkt        VARCHAR(20)   NOT NULL COMMENT 'meta / google',
    conta_custoMkt         VARCHAR(40)   NULL,
    identificador_custoMkt VARCHAR(180)  NULL COMMENT 'formulário (igual a evento.nomeForm)',
    nomeCampanha_custoMkt  VARCHAR(180)  NULL,
    nomeConjunto_custoMkt  VARCHAR(180)  NULL,
    nomeAnuncio_custoMkt   VARCHAR(180)  NULL,
    clicks_custoMkt        INT           NULL,
    KEY idx_custoMkt_data (data_custoMkt),
    KEY idx_custoMkt_identificador (identificador_custoMkt),
    KEY idx_custoMkt_campanha (nomeCampanha_custoMkt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Custo físico de cada evento (BRL) = soma dos fornecedores do evento.
-- Precisa ter EXATAMENTE estas 7 colunas: o módulo de Gestão de Eventos
-- descobre as colunas com SHOW COLUMNS e recusa qualquer outra.
-- id_evento não tem sufixo: o código lê assim.
CREATE TABLE custoEvento (
    id_custoEvento            INT AUTO_INCREMENT PRIMARY KEY,
    id_evento                 INT           NOT NULL,
    nome_custoEvento          VARCHAR(180)  NULL,
    identificador_custoEvento VARCHAR(180)  NULL,
    data_custoEvento          DATE          NULL,
    valor_custoEvento         DECIMAL(12,2) NOT NULL DEFAULT 0,
    id_ordemCompra            INT           NOT NULL DEFAULT 0,
    KEY idx_custoEvento_evento (id_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cotação do dólar em reais, uma linha por dia corrido.
CREATE TABLE cambio (
    id_cambio    INT AUTO_INCREMENT PRIMARY KEY,
    data_cambio  DATE          NOT NULL,
    valor_cambio DECIMAL(10,4) NOT NULL COMMENT 'reais por 1 dólar',
    KEY idx_cambio_data (data_cambio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Gestão de Eventos
-- -----------------------------------------------------------------------------

-- Uma tabela larga para as 7 categorias de fornecedor (hotel, equipamento,
-- videomaker, transporte, frete, material, custoExtra). Cada categoria usa só
-- algumas colunas; as outras ficam NULL. O mapeamento está em
-- lib/hotel/MapaFornecedor.php. Valores em BRL, sempre com 2 casas.
CREATE TABLE fornecedorEvento (
    id_fornecedorEvento               INT AUTO_INCREMENT PRIMARY KEY,
    categoria_fornecedorEvento        VARCHAR(20)   NOT NULL,
    idEvento_fornecedorEvento         INT           NULL COMMENT 'evento.id_evento',
    data_fornecedorEvento             DATE          NULL,
    nome_fornecedorEvento             VARCHAR(180)  NULL,
    custo_fornecedorEvento            DECIMAL(12,2) NULL DEFAULT 0,
    status_fornecedorEvento           VARCHAR(20)   NULL,
    obs_fornecedorEvento              TEXT          NULL,
    contato_fornecedorEvento          VARCHAR(120)  NULL,
    avaliacao_fornecedorEvento        TINYINT       NULL DEFAULT 0,
    uf_fornecedorEvento               VARCHAR(2)    NULL,
    capacidade_fornecedorEvento       INT           NULL,
    tipoPagamento_fornecedorEvento    VARCHAR(20)   NULL,
    situacaoContrato_fornecedorEvento VARCHAR(20)   NULL,
    dataAssinatura_fornecedorEvento   DATE          NULL,
    linkContrato_fornecedorEvento     VARCHAR(500)  NULL,
    nf_fornecedorEvento               TINYINT(1)    NULL DEFAULT 0,
    dataNf_fornecedorEvento           DATE          NULL,
    oc_fornecedorEvento               TINYINT(1)    NULL DEFAULT 0,
    dataOc_fornecedorEvento           DATE          NULL,
    numeroOc_fornecedorEvento         VARCHAR(60)   NULL,
    reservaQuarto_fornecedorEvento    VARCHAR(120)  NULL,
    quartoConfirmado_fornecedorEvento TINYINT(1)    NULL DEFAULT 0,
    statusEntrega_fornecedorEvento    VARCHAR(20)   NULL,
    dataEntrega_fornecedorEvento      DATE          NULL,
    descricaoEntrega_fornecedorEvento VARCHAR(255)  NULL,
    obsEntrega_fornecedorEvento       TEXT          NULL,
    responsavelAv_fornecedorEvento    VARCHAR(20)   NULL,
    horaEntrega_fornecedorEvento      VARCHAR(60)   NULL,
    tipoMidia_fornecedorEvento        VARCHAR(20)   NULL,
    modal_fornecedorEvento            VARCHAR(20)   NULL,
    companhia_fornecedorEvento        VARCHAR(120)  NULL,
    trecho_fornecedorEvento           VARCHAR(120)  NULL,
    dataViagem_fornecedorEvento       DATE          NULL,
    quantidade_fornecedorEvento       INT           NULL,
    bilhete_fornecedorEvento          VARCHAR(60)   NULL,
    passageiros_fornecedorEvento      VARCHAR(255)  NULL,
    documento_fornecedorEvento        VARCHAR(60)   NULL,
    dataEnvio_fornecedorEvento        DATE          NULL,
    avulso_fornecedorEvento           TINYINT(1)    NULL DEFAULT 0,
    idLegado_fornecedorEvento         VARCHAR(64)   NULL,
    criadoEm_fornecedorEvento         DATETIME      NULL,
    atualizadoEm_fornecedorEvento     DATETIME      NULL,
    deletado_fornecedorEvento         TINYINT(1)    NOT NULL DEFAULT 0,
    UNIQUE KEY uq_fornecedorEvento_idLegado (idLegado_fornecedorEvento),
    KEY idx_fornecedorEvento_categoria (categoria_fornecedorEvento, deletado_fornecedorEvento, data_fornecedorEvento),
    KEY idx_fornecedorEvento_evento (idEvento_fornecedorEvento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Financeiro e pós-venda
-- -----------------------------------------------------------------------------

-- Faturas (espelho do QuickBooks), em USD. Fatura paga = 'Em dia' com saldo 0.
-- Exceções herdadas: id_empresa sem sufixo e "ballanced" com dois L.
CREATE TABLE invoice (
    id_invoice              INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa              INT           NOT NULL DEFAULT 1,
    idQB_invoice            VARCHAR(20)   NOT NULL,
    customerQB_invoice      VARCHAR(20)   NOT NULL COMMENT 'só dígitos',
    nomeQB_invoice          VARCHAR(255)  NULL,
    amountQB_invoice        DECIMAL(12,2) NOT NULL,
    ballancedQB_invoice     DECIMAL(12,2) NOT NULL COMMENT 'saldo em aberto',
    dueDateQB_invoice       DATE          NULL,
    dataQBPagamento_invoice DATE          NULL,
    status_invoice          VARCHAR(20)   NOT NULL COMMENT 'Em dia / Atrasado / Inadimplente / Congelado',
    idDealBitrix_invoice    VARCHAR(20)   NULL COMMENT 'dealV2.idBitrix; NULL em faturas sem card',
    KEY idx_invoice_customer (customerQB_invoice),
    KEY idx_invoice_due (dueDateQB_invoice),
    KEY idx_invoice_pagamento (dataQBPagamento_invoice)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orçamentos de upsell do pós-venda (produtos de parceiros).
-- Vendido = etapa 'Vendido' E etapaId 'APPROVED'.
CREATE TABLE orcamentoV2 (
    id_orcamentoV2           INT AUTO_INCREMENT PRIMARY KEY,
    idBitrix_orcamentoV2     VARCHAR(20)   NOT NULL,
    dealId_orcamentoV2       VARCHAR(20)   NOT NULL COMMENT 'dealV2.idBitrix do card C3',
    contactId_orcamentoV2    VARCHAR(20)   NULL,
    nome_orcamentoV2         VARCHAR(180)  NULL,
    etapa_orcamentoV2        VARCHAR(60)   NULL,
    etapaId_orcamentoV2      VARCHAR(30)   NULL,
    dataVenda_orcamentoV2    DATE          NULL,
    criadoEm_orcamentoV2     DATETIME      NULL,
    oportunidade_orcamentoV2 DECIMAL(12,2) NULL,
    moedaId_orcamentoV2      VARCHAR(3)    NULL COMMENT 'BRL / USD',
    KEY idx_orcamentoV2_deal (dealId_orcamentoV2),
    KEY idx_orcamentoV2_venda (dataVenda_orcamentoV2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Itens do orçamento. id_orcamentoV2 é a PK interna do orçamento, não o ID do Bitrix.
CREATE TABLE itemOrcamentoV2 (
    id_itemOrcamentoV2                  INT AUTO_INCREMENT PRIMARY KEY,
    id_orcamentoV2                      INT          NOT NULL,
    nomeOriginalProduto_itemOrcamentoV2 VARCHAR(180) NULL,
    KEY idx_itemOrcamentoV2_orcamento (id_orcamentoV2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Atendimento (espelho do Chatwoot)
-- -----------------------------------------------------------------------------

-- Conversas. primeiraRespostaEm usa DATETIME (não TIMESTAMP): o Chatwoot grava
-- '1969-12-31 21:00:00' quando não houve resposta, e isso não cabe em TIMESTAMP.
CREATE TABLE conversaCW (
    id_conversaCW                INT AUTO_INCREMENT PRIMARY KEY,
    idCw_conversaCW              INT          NOT NULL,
    idLeadBitrix_conversaCW      BIGINT       NULL COMMENT 'leadV2.idBitrix como número',
    responsavel_conversaCW       VARCHAR(120) NULL,
    status_conversaCW            VARCHAR(20)  NULL,
    rotulos_conversaCW           TEXT         NULL COMMENT 'rótulos separados por vírgula',
    canal_conversaCW             VARCHAR(60)  NULL,
    nomeRemetenteMeta_conversaCW VARCHAR(180) NULL,
    telefoneRemetenteMeta_conversaCW VARCHAR(40) NULL,
    criadoEm_conversaCW          DATETIME     NULL,
    primeiraRespostaEm_conversaCW DATETIME    NULL,
    ultimaAtividadeEm_conversaCW DATETIME     NULL,
    KEY idx_conversaCW_criadoEm (criadoEm_conversaCW),
    KEY idx_conversaCW_lead (idLeadBitrix_conversaCW)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mensagens. id_conversaCW sem sufixo (o código lê assim).
-- tipo: '0'/'incoming' = recebida, '1'/'outgoing' = enviada.
CREATE TABLE msgCW (
    id_msgCW       INT AUTO_INCREMENT PRIMARY KEY,
    id_conversaCW  INT         NOT NULL,
    tipo_msgCW     VARCHAR(10) NOT NULL,
    criadoEm_msgCW DATETIME    NULL,
    KEY idx_msgCW_conversa (id_conversaCW)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Pesquisas (no sistema original vinham de planilhas do Google)
-- -----------------------------------------------------------------------------

-- Respostas da pesquisa de satisfação dos eventos. Notas de 0 a 10.
CREATE TABLE npsEvento (
    id_npsEvento                 INT AUTO_INCREMENT PRIMARY KEY,
    idEvento_npsEvento           INT          NULL,
    tipo_npsEvento               VARCHAR(12)  NOT NULL COMMENT 'comprou / nao_comprou',
    respondidoEm_npsEvento       DATETIME     NOT NULL,
    nome_npsEvento               VARCHAR(180) NULL,
    cidade_npsEvento             VARCHAR(120) NULL,
    expGeral_npsEvento           TINYINT      NULL,
    local_npsEvento              TINYINT      NULL,
    coffee_npsEvento             TINYINT      NULL,
    palestrante_npsEvento        TINYINT      NULL,
    recepcao_npsEvento           TINYINT      NULL,
    brindes_npsEvento            TINYINT      NULL,
    abordagem_npsEvento          TINYINT      NULL,
    atendimento_npsEvento        TINYINT      NULL,
    indicacao_npsEvento          TINYINT      NULL,
    chanceImigrar_npsEvento      TINYINT      NULL,
    fatorFechamento_npsEvento    VARCHAR(180) NULL,
    motivoNaoFechou_npsEvento    VARCHAR(180) NULL,
    interessePosEvento_npsEvento VARCHAR(60)  NULL,
    KEY idx_npsEvento_tipo (tipo_npsEvento, respondidoEm_npsEvento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Planilha em que o time do Comercial 2 registra as consultas realizadas.
-- O ConsultaCom2 cruza estas linhas com o CRM (por e-mail e, se falhar, por nome).
CREATE TABLE planilhaConsulta (
    id_planilhaConsulta            INT AUTO_INCREMENT PRIMARY KEY,
    nomeCliente_planilhaConsulta   VARCHAR(180) NULL,
    email_planilhaConsulta         VARCHAR(180) NULL,
    dataConsulta_planilhaConsulta  DATE         NULL,
    nomeComercial_planilhaConsulta VARCHAR(120) NULL,
    obs_planilhaConsulta           VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
