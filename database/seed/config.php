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
];
