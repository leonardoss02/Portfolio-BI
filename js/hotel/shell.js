/* ═══════════════════════════════════════════════════════════════════════
 * js/hotel/shell.js — comportamentos comuns a todas as telas
 *
 * Modal de checklist, botão de atualizar e revalidação
 * periódica. Carregado pelo template, então vale para as 11 abas.
 * ═══════════════════════════════════════════════════════════════════════ */

window.GH = window.GH || {};

GH.shell = (function () {
  'use strict';

  var core = GH.core, ui = GH.ui;

  // ── Checklist ─────────────────────────────────────────────────────────

  function abrirChecklist(id) {
    core.http.get('checklist/' + encodeURIComponent(id))
      .then(function (d) { desenharChecklist(d); })
      .catch(function (e) { ui.toast(e.message, 'erro'); });
  }

  function desenharChecklist(d) {
    var chk = d.checklist;
    var cor = chk.pct === 100 ? 'var(--green)' : (chk.pct >= 60 ? 'var(--amber)' : 'var(--danger)');

    var dlg = document.createElement('dialog');
    dlg.className = 'gh-dialog';
    dlg.setAttribute('aria-labelledby', 'gh-chk-titulo');

    var cab = document.createElement('div');
    cab.className = 'gh-dialog-cab';
    var tit = document.createElement('div');
    tit.id = 'gh-chk-titulo';
    tit.className = 'gh-dialog-titulo';
    tit.textContent = (d.hotel.cidade || d.hotel.nome) + ' — ' + core.data(d.hotel.data);
    cab.appendChild(tit);

    var fechar = document.createElement('button');
    fechar.type = 'button';
    fechar.className = 'gh-btn';
    fechar.textContent = 'Fechar';
    fechar.addEventListener('click', function () { dlg.close(); });
    cab.appendChild(fechar);
    dlg.appendChild(cab);

    var corpo = document.createElement('div');
    corpo.className = 'gh-dialog-corpo';

    var resumo = document.createElement('div');
    resumo.style.font = '600 13px/1.3 var(--font-ui)';
    resumo.style.color = cor;
    resumo.textContent = chk.ok + ' de ' + chk.total + ' concluídos (' + chk.pct + '%)';
    corpo.appendChild(resumo);

    // A cor não é o único canal de informação: o texto acima já diz o mesmo.
    var barra = document.createElement('div');
    barra.className = 'gh-progresso';
    barra.setAttribute('role', 'progressbar');
    barra.setAttribute('aria-valuenow', String(chk.ok));
    barra.setAttribute('aria-valuemin', '0');
    barra.setAttribute('aria-valuemax', String(chk.total));
    barra.setAttribute('aria-label', 'Itens concluídos do checklist');
    var fill = document.createElement('div');
    fill.className = 'gh-progresso-fill';
    fill.style.width = chk.pct + '%';
    fill.style.background = cor;
    barra.appendChild(fill);
    corpo.appendChild(barra);

    var ul = document.createElement('ul');
    ul.className = 'gh-check-lista';
    chk.itens.forEach(function (i) {
      var li = document.createElement('li');
      li.className = 'gh-check ' + (i.ok ? 'ok' : 'nao');
      li.insertAdjacentHTML('afterbegin', core.icone(i.ok ? 'check' : 'x'));

      var txt = document.createElement('div');
      var rot = document.createElement('div');
      rot.className = 'gh-check-rotulo';
      rot.textContent = i.rotulo;
      var det = document.createElement('div');
      det.className = 'gh-check-detalhe';
      det.textContent = i.detalhe;
      txt.appendChild(rot);
      txt.appendChild(det);
      li.appendChild(txt);
      ul.appendChild(li);
    });
    corpo.appendChild(ul);
    dlg.appendChild(corpo);

    document.body.appendChild(dlg);
    ui.abrir(dlg);
    dlg.addEventListener('close', function () { dlg.remove(); });
  }

  // ── Popover de hover do calendário ───────────────────────────────────
  // Mesma rota checklist/{id} do modal acima (agora também com "custo"),
  // só que num popover leve que abre no mouseenter/focus do chip — sem
  // exigir clique. O clique no chip continua abrindo o modal de sempre,
  // via [data-checklist] mais abaixo: hover dá o resumo, clique dá o
  // detalhe completo.

  var calPopover  = null;
  var calTimer    = null;
  var calCache    = {};
  var calRotulosParcela = [
    ['hotel', 'Hotel'], ['equipamento', 'Equipamento A/V'], ['videomaker', 'Videomaker'],
    ['transporte', 'Transporte'], ['frete', 'Frete'], ['material', 'Materiais'],
    ['custoExtra', 'Custos extras']
  ];

  function calMontar(d) {
    calPopover.textContent = '';

    var head = document.createElement('div');
    var titulo = document.createElement('div');
    titulo.className = 'gh-cal-pop-titulo';
    titulo.textContent = d.hotel.cidade + (d.hotel.nome ? ' · ' + d.hotel.nome : '');
    var dataEl = document.createElement('div');
    dataEl.className = 'gh-cal-pop-data';
    dataEl.textContent = core.data(d.hotel.data);
    head.appendChild(titulo);
    head.appendChild(dataEl);
    calPopover.appendChild(head);

    var c   = d.checklist;
    var cor = c.pct === 100 ? 'var(--green)' : (c.pct >= 60 ? 'var(--amber)' : 'var(--danger)');

    var secChk = document.createElement('div');
    secChk.className = 'gh-cal-pop-sec';
    var overlineChk = document.createElement('div');
    overlineChk.className = 'gh-cal-pop-overline';
    var rotChk = document.createElement('span');
    rotChk.textContent = 'Checklist';
    var resumoChk = document.createElement('span');
    resumoChk.className = 'gh-cal-pop-resumo';
    resumoChk.style.color = cor;
    resumoChk.textContent = c.ok + '/' + c.total + ' · ' + c.pct + '%';
    overlineChk.appendChild(rotChk);
    overlineChk.appendChild(resumoChk);
    secChk.appendChild(overlineChk);

    var barra = document.createElement('div');
    barra.className = 'gh-progresso gh-cal-pop-barra';
    barra.setAttribute('role', 'progressbar');
    barra.setAttribute('aria-valuenow', String(c.ok));
    barra.setAttribute('aria-valuemin', '0');
    barra.setAttribute('aria-valuemax', String(c.total));
    var fill = document.createElement('div');
    fill.className = 'gh-progresso-fill';
    fill.style.width = c.pct + '%';
    fill.style.background = cor;
    barra.appendChild(fill);
    secChk.appendChild(barra);

    var ul = document.createElement('ul');
    ul.className = 'gh-cal-pop-check';
    c.itens.forEach(function (item) {
      var li = document.createElement('li');
      li.className = item.ok ? 'ok' : 'nao';
      li.insertAdjacentHTML('afterbegin', core.icone(item.ok ? 'check' : 'x'));
      var span = document.createElement('span');
      span.textContent = item.rotulo;
      li.appendChild(span);
      ul.appendChild(li);
    });
    secChk.appendChild(ul);
    calPopover.appendChild(secChk);

    var secCusto = document.createElement('div');
    secCusto.className = 'gh-cal-pop-sec';
    var overlineCusto = document.createElement('div');
    overlineCusto.className = 'gh-cal-pop-overline';
    var rotCusto = document.createElement('span');
    rotCusto.textContent = 'Custo do evento';
    overlineCusto.appendChild(rotCusto);
    secCusto.appendChild(overlineCusto);

    var tabela = document.createElement('div');
    tabela.className = 'gh-cal-pop-custo';
    calRotulosParcela.forEach(function (par) {
      var v = (d.custo.parcelas || {})[par[0]] || 0;
      if (v <= 0) return; // parcela zerada fica de fora — mesmo critério do tooltip de gráfico
      var linha = document.createElement('div');
      linha.className = 'gh-cal-pop-custo-linha';
      var rot = document.createElement('span');
      rot.textContent = par[1];
      var val = document.createElement('span');
      val.textContent = core.brl(v);
      linha.appendChild(rot);
      linha.appendChild(val);
      tabela.appendChild(linha);
    });
    var linhaTotal = document.createElement('div');
    linhaTotal.className = 'gh-cal-pop-custo-linha total';
    var rotTotal = document.createElement('span');
    rotTotal.textContent = 'Total';
    var valTotal = document.createElement('span');
    valTotal.textContent = core.brl(d.custo.total);
    linhaTotal.appendChild(rotTotal);
    linhaTotal.appendChild(valTotal);
    tabela.appendChild(linhaTotal);
    secCusto.appendChild(tabela);
    calPopover.appendChild(secCusto);
  }

  /** Abaixo do chip por padrão; vira para cima se não couber, e nunca sai da tela. */
  function calPosicionar(chip) {
    var r       = chip.getBoundingClientRect();
    var margem  = 8;
    var largura = calPopover.offsetWidth;
    var altura  = calPopover.offsetHeight;
    var vw = window.innerWidth, vh = window.innerHeight;

    var top = r.bottom + margem;
    if (top + altura > vh - margem) {
      top = r.top - altura - margem;
    }
    top = Math.max(margem, Math.min(top, vh - altura - margem));

    var left = Math.max(margem, Math.min(r.left, vw - largura - margem));

    calPopover.style.top  = top + 'px';
    calPopover.style.left = left + 'px';
  }

  function calEsconder() {
    clearTimeout(calTimer);
    if (!calPopover) return;
    calPopover.classList.remove('aberto');
    calPopover.setAttribute('aria-hidden', 'true');
    var ativo = document.querySelector('.gh-cal-chip.evento[aria-describedby]');
    if (ativo) ativo.removeAttribute('aria-describedby');
  }

  function calMostrarComDados(chip, dados) {
    calMontar(dados);
    chip.setAttribute('aria-describedby', 'gh-cal-popover');
    calPosicionar(chip);
    calPopover.classList.add('aberto');
    calPopover.setAttribute('aria-hidden', 'false');
  }

  function calMostrar(chip, atraso) {
    var id = chip.dataset.checklist;
    clearTimeout(calTimer);
    calTimer = setTimeout(function () {
      if (calCache[id]) {
        calMostrarComDados(chip, calCache[id]);
        return;
      }
      core.http.get('checklist/' + encodeURIComponent(id)).then(function (d) {
        calCache[id] = d;
        // O mouse pode ter saído do chip (ou ele perdido o foco) enquanto
        // a resposta ainda vinha — popover é passivo, não vale a pena
        // aparecer atrasado sobre outro chip.
        if (chip.matches(':hover') || document.activeElement === chip) {
          calMostrarComDados(chip, d);
        }
      }).catch(function () { /* hover é passivo: falha silenciosa, sem toast */ });
    }, atraso || 0);
  }

  function ligarHoverCalendario() {
    calPopover = document.getElementById('gh-cal-popover');
    if (!calPopover) return; // só existe em paginas/hotel/calendario.php

    document.querySelectorAll('.gh-cal-chip.evento').forEach(function (chip) {
      chip.addEventListener('mouseenter', function () { calMostrar(chip, 150); });
      chip.addEventListener('mouseleave', calEsconder);
      chip.addEventListener('focus', function () { calMostrar(chip, 0); });
      chip.addEventListener('blur', calEsconder);
      chip.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') { calEsconder(); chip.blur(); }
      });
    });
    window.addEventListener('scroll', calEsconder, true);
    window.addEventListener('resize', calEsconder);
  }

  // ── Início ────────────────────────────────────────────────────────────

  function init() {

    core.on(document.body, 'click', '[data-checklist]', function (ev, el) {
      ev.preventDefault();
      abrirChecklist(el.dataset.checklist);
    });

    ligarHoverCalendario();

    var atualizar = document.getElementById('gh-atualizar');
    if (atualizar) {
      atualizar.addEventListener('click', function () {
        ui.ocupado(this, true, 'Atualizando...');
        window.location.reload();
      });
    }

    // O botao de imprimir vive no cabecalho, montado pelo shell — a view do
    // relatorio nao tem mais onde pendurar o seu proprio onclick.
    var imprimir = document.getElementById('gh-imprimir');
    if (imprimir) {
      imprimir.addEventListener('click', function () { window.print(); });
    }
  }

  return { init: init, abrirChecklist: abrirChecklist };
})();

document.addEventListener('DOMContentLoaded', GH.shell.init);
