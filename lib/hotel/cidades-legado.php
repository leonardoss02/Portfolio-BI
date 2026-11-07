<?php

/**
 * Mapa cidade -> slug copiado do CIDADE_EVENTO_MAP do Apps Script.
 *
 * Serve APENAS para a importacao: e com ele que se remonta a chave
 * sintetica "Slug|yyyy-mm-dd" que o sistema antigo usava para ligar cada
 * custo ao seu evento. Depois da importacao o vinculo passa a ser a FK
 * numerica idHotel_* e este arquivo deixa de ter uso corrente.
 *
 * Preserva as duplicidades do original de proposito (ha entradas com e sem
 * acento e, notoriamente, Sao Carlos e Ribeirao Preto apontando para o
 * mesmo slug) — reproduzir o mapa exato e o que garante que as chaves
 * gravadas na planilha sejam reencontradas.
 */
return [
    'Alphaville' => 'Alphaville',
    'Rio de Janeiro' => 'RioDeJaneiro',
    'Blumenau' => 'Blumenau',
    'São José dos Campos' => 'SaoJosedosCampos',
    'Belo Horizonte' => 'BeloHorizonte',
    'Sorocaba' => 'Sorocaba',
    'Belém' => 'Belem',
    'Curitiba' => 'Curitiba',
    'Vitória' => 'Vitoria',
    'Jundiaí' => 'Jundiai',
    'São José do Rio Preto' => 'SaoJosedoRioPreto',
    'Manaus' => 'Manaus',
    'Porto Alegre' => 'PortoAlegre',
    'Goiânia' => 'Goiania',
    'Uberlândia' => 'Uberlandia',
    'Mogi das Cruzes' => 'MogidaCruzes',
    'São Carlos' => 'RibeiraoPreto',
    'Londrina' => 'Londrina',
    'Caxias do Sul' => 'CaxiasdoSul',
    'Palmas' => 'Palmas',
    'Campo Grande' => 'CampoGrande',
    'Natal' => 'Natal',
    'Porto Velho' => 'PortoVelho',
    'Salvador' => 'Salvador',
    'Florianópolis' => 'Floripa',
    'Maringá' => 'Maringa',
    'Ribeirão Preto' => 'RibeiraoPreto',
    'Fortaleza' => 'Fortaleza',
    'Recife' => 'Recife',
    'Brasília' => 'Brasilia',
    'Joinville' => 'Joinville',
    'Maceió' => 'Maceio',
    'São Paulo' => 'SaoPaulo',
    'Balneário Camboriú' => 'Balneario',
    'Cuiabá' => 'Cuiaba',
    'Uberaba' => 'Uberaba',
    'Campinas' => 'Campinas',
    'Santos' => 'Santos',
    'Juiz de Fora' => 'JuizdeFora',
    'Aracajú' => 'Aracaju',
    'Itajaí' => 'Itajai',
    'Piracicaba' => 'Piracicaba',
    'Florianopolis' => 'Floripa',
    'São Caetano do Sul' => 'SaoCaetano',
    'Guarulhos' => 'Guarulhos',
    'Brasilia' => 'Brasilia',
    'Vitoria' => 'Vitoria',
    'São Caetano' => 'SaoCaetano',
    'Duque de Caxias' => 'DuquedeCaxias',
];
