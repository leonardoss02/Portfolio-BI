<?php
/*
 * paginas/hotel/partes/abas.php
 *
 * Navegação do módulo, numa barra só: logo, divisor e os grupos como
 * segmented control (mesmo componente do filtro de período, `.gh-seg`/
 * `.gh-periodo`, em vez de duas linguagens de "ativo" diferentes empilhadas).
 * As telas do grupo ativo aparecem logo abaixo, só quando o grupo tem mais
 * de uma — grupo de tela única (Gestão de eventos) é link direto, sem
 * fileira redundante repetindo a mesma opção.
 *
 * Por que saiu da sidebar: o modo embed (`?to=1`) esconde `.sidebar` por CSS
 * — regra do bi-theme.css, compartilhada pelos 16 dashboards e por isso
 * intocável aqui. Dentro do iframe não sobrava nenhuma forma de trocar de
 * tela. Aqui a navegação vive dentro do conteúdo, onde o embed não a alcança.
 *
 * Sem JavaScript todos os grupos aparecem abertos: a classe `js` só é posta
 * pelo próprio script, e é ela que autoriza o CSS a esconder os inativos.
 * Assim uma falha de script deixa a navegação feia, não inutilizável.
 *
 * Espera $navegacao e $abaAtiva, montados pelo template.
 */

/** Grupos na ordem em que aparecem, com as rotas de cada um. */
$grupos = [
    'Gestão de eventos' => ['index'],
    'Cadastros'         => ['lista', 'equipamentos', 'videomakers', 'transportes', 'frete', 'materiais', 'custosextras'],
    'Consolidação'      => ['valortotal', 'calendario', 'relatorio'],
];

// Grupo mostrado ao abrir: o que contém a tela atual.
$grupoAtivo = array_key_first($grupos);
foreach ($grupos as $nome => $rotas) {
    if (in_array($abaAtiva, $rotas, true)) {
        $grupoAtivo = $nome;
        break;
    }
}

// Os ids vêm da posição, não do nome: dispensa slug, acento e a função de
// normalização que este projeto não tem.
$indices = array_flip(array_keys($grupos));

// Só grupos com mais de uma tela precisam de painel — um grupo de tela
// única já navega pelo próprio botão do segmented control.
$temPainel = static fn(array $rotas): bool => count($rotas) > 1;
$algumPainel = (bool)array_filter($grupos, $temPainel);
?>
<div class="gh-nav">
    <div class="gh-topo">
        <div class="gh-marca">
            <div class="gh-marca-ico" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M3 21h18M5 21V7l7-4 7 4v14"/>
                    <path d="M9 21v-5h6v5"/>
                </svg>
            </div>
            <div>
                <div class="gh-marca-nome">Acme EUA</div>
                <div class="gh-marca-sub">ON THE ROAD</div>
            </div>
        </div>

        <div class="gh-divisor" aria-hidden="true"></div>

        <div class="gh-seg gh-abas-grupos" role="tablist" aria-label="Navegação do módulo">
            <?php foreach ($grupos as $nome => $rotas):
                $ativo = $nome === $grupoAtivo;
            ?>
                <?php if ($temPainel($rotas)): ?>
                    <button type="button"
                            class="gh-periodo<?= $ativo ? ' ativo' : '' ?>"
                            role="tab"
                            aria-selected="<?= $ativo ? 'true' : 'false' ?>"
                            aria-controls="gh-grupo-<?= (int)$indices[$nome] ?>"
                            data-grupo="<?= (int)$indices[$nome] ?>">
                        <?= hesc($nome) ?>
                    </button>
                <?php else:
                    // Tela única: o próprio botão do grupo é o link — uma
                    // fileira de pill abaixo repetiria a mesma opção.
                    $rota = $rotas[0];
                    if (!isset($navegacao[$rota])) {
                        continue;
                    }
                    $href = hurl($this, $rota === 'index' ? '' : $rota);
                ?>
                    <a class="gh-periodo<?= $ativo ? ' ativo' : '' ?>"
                       role="tab"
                       aria-selected="<?= $ativo ? 'true' : 'false' ?>"
                       href="<?= hesc($href) ?>">
                        <?= hesc($nome) ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="gh-topo-dir">
            <span class="gh-relogio" id="footer-clock"></span>
            <?php if (isset($shell)): ?>
                <button type="button" class="gh-btn" id="gh-atualizar">Atualizar</button>
            <?php endif; ?>
            <button class="theme-btn gh-tema-btn" id="theme-btn" type="button" onclick="toggleTheme()">
                <svg id="theme-icon-moon" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                <svg id="theme-icon-sun" viewBox="0 0 24 24" style="display:none">
                    <circle cx="12" cy="12" r="5"/>
                    <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                </svg>
                <span id="theme-label">Dark mode</span>
            </button>
        </div>
    </div>

    <?php if ($algumPainel): ?>
        <div class="gh-abas-painel">
            <?php foreach ($grupos as $nome => $rotas):
                if (!$temPainel($rotas)) {
                    continue;
                }
            ?>
                <div class="gh-abas-itens<?= $nome === $grupoAtivo ? ' ativo' : '' ?>"
                     id="gh-grupo-<?= (int)$indices[$nome] ?>"
                     role="tabpanel">
                    <?php foreach ($rotas as $rota): ?>
                        <?php
                        if (!isset($navegacao[$rota])) {
                            continue;   // rota removida da navegação: não inventa link
                        }
                        [$rotulo, $icone] = $navegacao[$rota];
                        $ehAtual = $rota === $abaAtiva;
                        $href    = hurl($this, $rota === 'index' ? '' : $rota);
                        ?>
                        <a class="gh-aba<?= $ehAtual ? ' ativa' : '' ?>"
                           href="<?= hesc($href) ?>"<?= $ehAtual ? ' aria-current="page"' : '' ?>>
                            <span class="gh-aba-ico"><?= hicone($icone, '') ?></span>
                            <?= hesc($rotulo) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($shell)): include __DIR__ . '/header.php'; endif; ?>
</div>

<script>
(function () {
    var painel = document.currentScript.previousElementSibling;
    if (!painel || !painel.classList.contains('gh-nav')) { return; }

    var grupos = painel.querySelector('.gh-abas-grupos');
    var itens  = painel.querySelector('.gh-abas-painel');

    // Só agora o CSS pode esconder grupo inativo: sem script, todos ficam
    // visíveis e a navegação continua inteira.
    if (itens) { itens.classList.add('js'); }

    grupos.querySelectorAll('button.gh-periodo').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var alvo = btn.getAttribute('data-grupo');

            grupos.querySelectorAll('.gh-periodo').forEach(function (b) {
                var eu = b === btn;
                b.classList.toggle('ativo', eu);
                b.setAttribute('aria-selected', eu ? 'true' : 'false');
            });
            if (itens) {
                itens.querySelectorAll('.gh-abas-itens').forEach(function (d) {
                    d.classList.toggle('ativo', d.id === 'gh-grupo-' + alvo);
                });
            }
        });
    });
})();
</script>
