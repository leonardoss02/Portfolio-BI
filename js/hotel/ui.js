/* ═══════════════════════════════════════════════════════════════════════
 * js/hotel/ui.js — avisos, confirmação e modais
 *
 * Substitui o alert()/confirm() bloqueante do sistema antigo, que parava a
 * página inteira e, no caso de falha ao salvar, ainda avisava DEPOIS de a
 * tela já ter mostrado o dado como se tivesse sido gravado.
 * ═══════════════════════════════════════════════════════════════════════ */

window.GH = window.GH || {};

GH.ui = (function () {
  'use strict';

  var esc = GH.core.esc;

  // ── Avisos ────────────────────────────────────────────────────────────

  function toast(mensagem, tipo, ms) {
    var caixa = document.getElementById('gh-toasts');
    if (!caixa) { console.log(mensagem); return; }

    var el = document.createElement('div');
    el.className = 'gh-toast ' + (tipo || 'info');

    var texto = document.createElement('span');
    texto.textContent = mensagem;          // textContent: nunca interpreta marcação
    el.appendChild(texto);

    var fechar = document.createElement('button');
    fechar.className = 'gh-toast-fechar';
    fechar.type = 'button';
    fechar.setAttribute('aria-label', 'Fechar aviso');
    fechar.textContent = '×';
    fechar.addEventListener('click', function () { el.remove(); });
    el.appendChild(fechar);

    caixa.appendChild(el);

    // Erro não some sozinho: quem precisa agir tem de conseguir ler.
    var duracao = ms !== undefined ? ms : (tipo === 'erro' ? 0 : 4500);
    if (duracao > 0) setTimeout(function () { el.remove(); }, duracao);

    return el;
  }

  // ── Modais ────────────────────────────────────────────────────────────
  // <dialog> nativo: ESC, backdrop, retenção de foco e inert no resto da
  // página vêm prontos, sem reimplementar acessibilidade à mão.

  var abertos = 0;

  function abrir(idOuEl) {
    var dlg = typeof idOuEl === 'string' ? document.getElementById(idOuEl) : idOuEl;
    if (!dlg) return null;

    dlg._gatilho = document.activeElement;

    if (typeof dlg.showModal === 'function') {
      dlg.showModal();
    } else {
      dlg.setAttribute('open', '');   // Safari antigo
    }
    abertos++;

    dlg.addEventListener('close', function aoFechar() {
      dlg.removeEventListener('close', aoFechar);
      abertos = Math.max(0, abertos - 1);
      if (dlg._gatilho && typeof dlg._gatilho.focus === 'function') {
        dlg._gatilho.focus();          // devolve o foco a quem abriu
      }
    });

    // Busca sobre selects longos: montada aqui, na abertura, e nao no load
    // da pagina — o campo ja nasce pronto quando aparece, sem salto visual.
    if (window.GH && GH.buscaSelect) { GH.buscaSelect.aplicar(dlg); }

    // O select enfeitado fica invisivel; sem excluí-lo daqui o foco cairia
    // nele e o modal abriria com o teclado preso num elemento sem pintura.
    var primeiro = dlg.querySelector(
      '[autofocus], input:not([type=hidden]):not(.gh-busca-painel input), ' +
      'select:not(.gh-busca-nativo), textarea, .gh-busca-campo'
    );
    if (primeiro) primeiro.focus();

    return dlg;
  }

  function fechar(idOuEl) {
    var dlg = typeof idOuEl === 'string' ? document.getElementById(idOuEl) : idOuEl;
    if (!dlg) return;
    if (typeof dlg.close === 'function') { dlg.close(); } else { dlg.removeAttribute('open'); }
  }

  /** O auto-refresh consulta isto para não recarregar por cima de um formulário aberto. */
  function temModalAberto() {
    return abertos > 0 || !!document.querySelector('dialog[open]');
  }

  // ── Confirmação ───────────────────────────────────────────────────────

  function confirmar(mensagem, opcoes) {
    opcoes = opcoes || {};
    return new Promise(function (resolve) {
      var dlg = document.createElement('dialog');
      dlg.className = 'gh-dialog gh-confirm';
      dlg.innerHTML =
        '<form method="dialog" class="gh-confirm-corpo">' +
          '<p class="gh-confirm-msg">' + esc(mensagem) + '</p>' +
          '<div class="gh-confirm-acoes">' +
            '<button value="nao" class="gh-btn" type="submit">' + esc(opcoes.cancelar || 'Cancelar') + '</button>' +
            '<button value="sim" class="gh-btn gh-btn-acao" type="submit">' + esc(opcoes.confirmar || 'Confirmar') + '</button>' +
          '</div>' +
        '</form>';

      document.body.appendChild(dlg);
      abrir(dlg);

      dlg.addEventListener('close', function () {
        var ok = dlg.returnValue === 'sim';
        dlg.remove();
        resolve(ok);
      });
    });
  }

  // ── Estado de botão durante gravação ──────────────────────────────────

  function ocupado(botao, ocupadoAgora, rotuloOcupado) {
    if (!botao) return;
    if (ocupadoAgora) {
      botao.dataset.rotulo = botao.textContent;
      botao.disabled = true;
      botao.textContent = rotuloOcupado || 'Salvando...';
    } else {
      botao.disabled = false;
      if (botao.dataset.rotulo) botao.textContent = botao.dataset.rotulo;
    }
  }

  return {
    toast: toast,
    abrir: abrir,
    fechar: fechar,
    temModalAberto: temModalAberto,
    confirmar: confirmar,
    ocupado: ocupado
  };
})();
