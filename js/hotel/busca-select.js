/* ═══════════════════════════════════════════════════════════════════════
 * js/hotel/busca-select.js — busca sobre um <select> longo
 *
 * O vínculo de evento é um <select> com 70 e poucas opções. Achar
 * "Uberlândia" ali exige rolar a lista inteira, e o casamento por primeira
 * letra do select nativo quase ninguém usa.
 *
 * A decisão que sustenta o resto: **o <select> nativo continua no
 * formulário e continua sendo a fonte da verdade.** O componente só pinta
 * por cima e escreve de volta nele. Assim `forms.js` serializa como sempre,
 * o `required` do HTML continua valendo, e sem JavaScript o campo segue
 * funcionando — feio, mas inteiro.
 *
 * Enhancement roda na abertura do modal (ui.abrir), não no load da página:
 * o campo já nasce montado quando aparece, sem salto visual.
 * ═══════════════════════════════════════════════════════════════════════ */

window.GH = window.GH || {};

GH.buscaSelect = (function () {
    'use strict';

    /** Minúsculas sem acento, para o termo casar "goiania" com "Goiânia". */
    function dobrar(s) {
        return String(s || '').toLowerCase()
            .replace(/[áàãâä]/g, 'a').replace(/[éèêë]/g, 'e').replace(/[íìîï]/g, 'i')
            .replace(/[óòõôö]/g, 'o').replace(/[úùûü]/g, 'u')
            .replace(/ç/g, 'c').replace(/ñ/g, 'n');
    }

    /** Destaca o trecho casado sem interpretar marcação do rótulo. */
    function pintar(texto, termo) {
        var i = termo ? dobrar(texto).indexOf(termo) : -1;
        if (i < 0) { return GH.core.esc(texto); }
        return GH.core.esc(texto.slice(0, i)) +
               '<b>' + GH.core.esc(texto.slice(i, i + termo.length)) + '</b>' +
               GH.core.esc(texto.slice(i + termo.length));
    }

    /**
     * Enfeita um <select> com busca. Idempotente: chamar de novo no mesmo
     * elemento não duplica nada.
     */
    function enfeitar(sel) {
        if (!sel || sel._ghBusca) { return; }
        sel._ghBusca = true;

        var vazioRotulo = (sel.options[0] && !sel.options[0].value)
            ? sel.options[0].textContent.trim()
            : 'Selecione...';

        var wrap = document.createElement('div');
        wrap.className = 'gh-busca-sel';
        sel.parentNode.insertBefore(wrap, sel);
        wrap.appendChild(sel);

        // Fora da ordem de tabulação e do leitor de tela: quem responde por
        // ele agora é o botão. Sem isto, o foco do modal cairia no invisível.
        sel.classList.add('gh-busca-nativo');
        sel.setAttribute('tabindex', '-1');
        sel.setAttribute('aria-hidden', 'true');

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'gh-busca-campo';
        btn.setAttribute('aria-haspopup', 'listbox');
        btn.setAttribute('aria-expanded', 'false');
        if (sel.id) { btn.setAttribute('aria-labelledby', 'lbl-' + sel.id); }
        btn.innerHTML = '<span class="txt"></span>' +
            '<svg class="seta" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
            'stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';

        var painel = document.createElement('div');
        painel.className = 'gh-busca-painel';
        painel.innerHTML =
            '<input type="text" autocomplete="off" spellcheck="false" ' +
            'placeholder="Buscar por cidade, hotel ou data...">' +
            '<div class="gh-busca-lista" role="listbox"></div>' +
            '<div class="gh-busca-conta"></div>';

        wrap.appendChild(btn);
        wrap.appendChild(painel);

        var q     = painel.querySelector('input');
        var lista = painel.querySelector('.gh-busca-lista');
        var conta = painel.querySelector('.gh-busca-conta');
        var foco  = -1;
        var vis   = [];

        function desenhar() {
            var termo = dobrar(q.value.trim());
            var html  = '';
            vis = [];

            for (var i = 0; i < sel.options.length; i++) {
                var op = sel.options[i];
                if (!op.value) { continue; }            // o placeholder não entra
                var rot = op.textContent.trim();
                if (termo && dobrar(rot).indexOf(termo) < 0) { continue; }
                vis.push(i);
                html += '<div class="gh-busca-op' + (i === sel.selectedIndex ? ' sel' : '') +
                        '" data-i="' + i + '" role="option" aria-selected="' +
                        (i === sel.selectedIndex) + '">' + pintar(rot, termo) + '</div>';
            }

            lista.innerHTML = html || '<div class="gh-busca-vazio">Nenhum resultado.</div>';
            conta.textContent = vis.length + ' de ' + contarOpcoes() +
                (vis.length === 1 ? ' opção' : ' opções');
            foco = -1;
        }

        function contarOpcoes() {
            var n = 0;
            for (var i = 0; i < sel.options.length; i++) { if (sel.options[i].value) { n++; } }
            return n;
        }

        function rotular() {
            var vazio = !sel.value;
            var op    = sel.options[sel.selectedIndex];
            btn.classList.toggle('vazio', vazio);
            btn.querySelector('.txt').textContent =
                vazio ? vazioRotulo : (op ? op.textContent.trim() : vazioRotulo);
        }

        function abrirPainel() {
            wrap.classList.add('aberto');
            btn.setAttribute('aria-expanded', 'true');
            q.value = '';
            desenhar();
            q.focus();
        }

        function fecharPainel() {
            wrap.classList.remove('aberto');
            btn.setAttribute('aria-expanded', 'false');
        }

        function escolher(i) {
            sel.selectedIndex = i;
            // change: o formulário e quem observa o campo precisam saber.
            sel.dispatchEvent(new Event('change', { bubbles: true }));
            rotular();
            fecharPainel();
            btn.focus();
        }

        function mover(passo) {
            if (!vis.length) { return; }
            foco += passo;
            if (foco < 0) { foco = vis.length - 1; }
            if (foco >= vis.length) { foco = 0; }
            var ops = lista.querySelectorAll('.gh-busca-op');
            for (var i = 0; i < ops.length; i++) { ops[i].classList.toggle('foco', i === foco); }
            if (ops[foco]) { ops[foco].scrollIntoView({ block: 'nearest' }); }
        }

        btn.addEventListener('click', function () {
            wrap.classList.contains('aberto') ? fecharPainel() : abrirPainel();
        });
        btn.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                abrirPainel();
            }
        });

        q.addEventListener('input', desenhar);
        q.addEventListener('keydown', function (e) {
            if (e.key === 'Escape')    { e.stopPropagation(); fecharPainel(); btn.focus(); return; }
            if (e.key === 'Enter')     { e.preventDefault(); if (foco >= 0) { escolher(vis[foco]); } return; }
            if (e.key === 'ArrowDown') { e.preventDefault(); mover(1);  return; }
            if (e.key === 'ArrowUp')   { e.preventDefault(); mover(-1); return; }
        });

        lista.addEventListener('click', function (e) {
            var op = e.target.closest('.gh-busca-op');
            if (op) { escolher(parseInt(op.getAttribute('data-i'), 10)); }
        });

        // Clique fora fecha. O listener é do documento porque o painel
        // extrapola o campo; dentro do <dialog> isso continua valendo.
        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) { fecharPainel(); }
        });

        // Quem alterar o select por fora (o modal preenchendo para edição)
        // não sabe do botão; este ouvinte mantém o rótulo em dia.
        sel.addEventListener('change', rotular);

        rotular();
    }

    /** Enfeita todo select[data-busca] dentro de uma raiz (documento ou modal). */
    function aplicar(raiz) {
        var alvos = (raiz || document).querySelectorAll('select[data-busca]');
        for (var i = 0; i < alvos.length; i++) { enfeitar(alvos[i]); }
    }

    return { aplicar: aplicar, enfeitar: enfeitar };
})();
