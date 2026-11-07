<?php
/**
 * paginas/hotel/calendario.php — grade mensal com eventos e feriados.
 *
 * Os feriados são calculados, não listados: no original eram quinze datas
 * fixas de 2026, e a partir de 2027 o calendário apareceria sem nenhuma.
 *
 * As anotações por dia que o original guardava no localStorage não existem
 * mais — nunca chegaram a funcionar sobre o banco, e foram retiradas em vez
 * de ficarem na tela dando erro ao salvar.
 *
 * @var MRPHP  $this
 * @var array  $shell
 * @var array  $cal    ['ano','mes','dias','eventos']
 */

$ano = $cal['ano'];
$mes = $cal['mes'];
$dias = $cal['dias'];

$MESES = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
          'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

$primeiro   = sprintf('%04d-%02d-01', $ano, $mes);
$diasNoMes  = (int)date('t', strtotime($primeiro));
$diaSemana  = (int)date('w', strtotime($primeiro)); // 0 = domingo
$hoje       = date('Y-m-d');

$anterior = date('Y-n', strtotime($primeiro . ' -1 month'));
$proximo  = date('Y-n', strtotime($primeiro . ' +1 month'));
// $this nao pode entrar num "use ()" — dentro de um closure em contexto de
// objeto ele ja vem ligado, mas capturar explicitamente e erro de sintaxe.
$app = $this;
$navUrl = static function (string $aM) use ($app): string {
    [$a, $m] = explode('-', $aM);
    return hurl($app, 'calendario', ['calAno' => $a, 'calMes' => $m]);
};
?>

<div class="gh-filtros gh-cal-nav">
    <a class="gh-btn" href="<?= hesc($navUrl($anterior)) ?>" aria-label="Mês anterior">‹</a>
    <span class="gh-cal-titulo"><?= hesc($MESES[$mes]) ?> <?= hesc($ano) ?></span>
    <a class="gh-btn" href="<?= hesc($navUrl($proximo)) ?>" aria-label="Próximo mês">›</a>
    <a class="gh-btn" href="<?= hesc(hurl($this, 'calendario')) ?>">Hoje</a>

    <div class="gh-cal-legenda">
        <span><i class="gh-ponto evento"></i> Evento</span>
        <span><i class="gh-ponto feriado"></i> Feriado</span>
    </div>
</div>

<div class="gh-cal-layout">
    <div class="gh-painel">
        <table class="gh-cal">
            <caption class="sr-only">
                <?= hesc($MESES[$mes]) ?> de <?= hesc($ano) ?> —
                <?= hesc($cal['eventos']) ?> evento(s)
            </caption>
            <thead>
                <tr>
                    <?php foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $d): ?>
                        <th scope="col"><abbr title="<?= hesc($d) ?>"><?= hesc($d) ?></abbr></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php for ($i = 0; $i < $diaSemana; $i++): ?>
                        <td class="gh-cal-fora"></td>
                    <?php endfor; ?>

                    <?php for ($dia = 1; $dia <= $diasNoMes; $dia++): ?>
                        <?php
                        $data  = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
                        $info  = $dias[$data] ?? [];
                        $dow   = (int)date('w', strtotime($data));
                        $ehFds = $dow === 0 || $dow === 6;

                        $classes = 'gh-cal-dia';
                        if ($data === $hoje) { $classes .= ' hoje'; }
                        if ($ehFds)          { $classes .= ' fds'; }
                        if (!empty($info['feriados'])) { $classes .= ' feriado'; }
                        ?>
                        <td class="<?= hesc($classes) ?>">
                            <button type="button" class="gh-cal-btn" data-dia="<?= hesc($data) ?>"
                                    aria-label="<?= hesc($dia . ' de ' . $MESES[$mes]) ?>">
                                <span class="gh-cal-num"><?= hesc($dia) ?></span>
                            </button>

                            <?php foreach (($info['feriados'] ?? []) as $f): ?>
                                <span class="gh-cal-chip feriado" title="<?= hesc($f['nome']) ?>">
                                    <?= hesc(mb_strimwidth($f['nome'], 0, 16, '…', 'UTF-8')) ?>
                                </span>
                            <?php endforeach; ?>

                            <?php foreach (array_slice($info['eventos'] ?? [], 0, 2) as $e): ?>
                                <span class="gh-cal-chip evento" title="<?= hesc($e['nome']) ?>"
                                      data-checklist="<?= hesc($e['id']) ?>" tabindex="0" role="button">
                                    <?= hesc(mb_strimwidth($e['cidade'], 0, 16, '…', 'UTF-8')) ?>
                                </span>
                            <?php endforeach; ?>
                            <?php if (count($info['eventos'] ?? []) > 2): ?>
                                <span class="gh-cal-mais">+<?= hesc(count($info['eventos']) - 2) ?></span>
                            <?php endif; ?>
                        </td>

                        <?php if ((($diaSemana + $dia) % 7) === 0 && $dia < $diasNoMes): ?>
                            </tr><tr>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php
                    $sobra = (7 - (($diaSemana + $diasNoMes) % 7)) % 7;
                    for ($i = 0; $i < $sobra; $i++): ?>
                        <td class="gh-cal-fora"></td>
                    <?php endfor; ?>
                </tr>
            </tbody>
        </table>
    </div>

    <aside class="gh-painel gh-agenda" aria-label="Agenda do mês">
        <div class="gh-painel-cab">
            <div class="gh-painel-titulo"><?= hesc($MESES[$mes]) ?></div>
            <div class="gh-painel-sub"><?= hesc($cal['eventos']) ?> evento(s)</div>
        </div>
        <div class="gh-painel-corpo">
            <?php if (!$dias): ?>
                <div class="gh-vazio">Nada marcado neste mês.</div>
            <?php else: ?>
                <ol class="gh-agenda-lista">
                    <?php foreach ($dias as $data => $info): ?>
                        <li>
                            <div class="gh-agenda-data"><?= hesc(hdata($data)) ?></div>
                            <?php foreach (($info['feriados'] ?? []) as $f): ?>
                                <div class="gh-agenda-item feriado"><?= hesc($f['nome']) ?></div>
                            <?php endforeach; ?>
                            <?php foreach (($info['eventos'] ?? []) as $e): ?>
                                <div class="gh-agenda-item evento">
                                    <?= hesc($e['cidade']) ?>
                                    <?php if ($e['nome'] !== ''): ?>
                                        <span class="gh-ev-meta">· <?= hesc($e['nome']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    </aside>
</div>

<dialog class="gh-dialog gh-dialog-dia" id="gh-modal-dia" aria-labelledby="gh-dia-titulo">
    <div class="gh-dialog-cab">
        <h2 class="gh-dialog-titulo" id="gh-dia-titulo"></h2>
        <button type="button" class="gh-btn" data-fechar-modal aria-label="Fechar"><?= hicone('x') ?></button>
    </div>

    <div class="gh-dialog-corpo">
        <div id="gh-dia-conteudo"></div>
    </div>
</dialog>

<!-- Popover de hover: checklist + custo do evento (GH.shell liga os chips .gh-cal-chip.evento). -->
<div class="gh-cal-popover" id="gh-cal-popover" role="tooltip" aria-hidden="true"></div>

<script type="application/json" id="gh-boot"><?= hjson($boot) ?></script>
<script type="application/json" id="gh-dias"><?= hjson($dias) ?></script>

<script>
(function () {
    'use strict';

    var core = GH.core, ui = GH.ui, forms = GH.forms;

    var dias   = core.boot('gh-dias');
    var modal  = document.getElementById('gh-modal-dia');
    var titulo = document.getElementById('gh-dia-titulo');
    var corpo  = document.getElementById('gh-dia-conteudo');

    function abrirDia(data) {
        var info = dias[data] || {};
        titulo.textContent = core.data(data);

        corpo.textContent = '';
        var vazio = true;

        [['feriados', 'feriado', 'Feriado'], ['eventos', 'evento', 'Evento']]
        .forEach(function (par) {
            var lista = info[par[0]] || [];
            if (!lista.length) return;
            vazio = false;

            lista.forEach(function (item) {
                var linha = document.createElement('div');
                linha.className = 'gh-dia-item ' + par[1];

                var txt = document.createElement('span');
                txt.textContent = item.cidade || item.nome;   // textContent: sem interpretar marcação
                linha.appendChild(txt);

                corpo.appendChild(linha);
            });
        });

        if (vazio) {
            var p = document.createElement('p');
            p.className = 'gh-ev-meta';
            p.textContent = 'Nada marcado neste dia.';
            corpo.appendChild(p);
        }

        ui.abrir(modal);
    }

    core.on(document.body, 'click', '[data-dia]', function (ev, el) { abrirDia(el.dataset.dia); });
    core.on(modal, 'click', '[data-fechar-modal]', function () { ui.fechar(modal); });
})();
</script>
