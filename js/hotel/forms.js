/* ═══════════════════════════════════════════════════════════════════════
 * js/hotel/forms.js — gravação via formulário
 *
 * Uma camada só para todas as entidades. Resolve aqui, de uma vez, as três
 * armadilhas do MRPHP que quebram CRUD por fetch:
 *
 *  1. bloqueiaPostDuplicado() usa md5(serialize($_POST) . REQUEST_URI) numa
 *     janela de 5 s. Com corpo JSON o $_POST fica vazio, então dois
 *     salvamentos diferentes na mesma rota têm a MESMA impressão digital e o
 *     segundo é engolido por um redirect. Por isso o corpo vai em
 *     x-www-form-urlencoded e a URL leva um nonce (ambos em core.js).
 *  2. verificaFormToken() CONSOME o token ao validar. Toda resposta de
 *     escrita devolve um token novo, que é reinjetado no formulário — sem
 *     isso o segundo salvamento da sessão falharia.
 *  3. insert() do core devolve false em silêncio. Aqui nada é dado como
 *     salvo sem o 200: a lista só muda com o registro que o servidor
 *     devolveu.
 *
 * O oposto do original, que gravava na tela primeiro e avisava do erro
 * depois — deixando à vista um dado que não existia no servidor.
 * ═══════════════════════════════════════════════════════════════════════ */

window.GH = window.GH || {};

GH.forms = (function () {
  'use strict';

  var core = GH.core, ui = GH.ui;
  var salvando = false;

  /** O auto-refresh consulta isto para não recarregar durante uma gravação. */
  function estaSalvando() { return salvando; }

  // ── Token ─────────────────────────────────────────────────────────────

  function token() {
    var el = document.getElementById('gh-token');
    return el ? el.value : '';
  }

  function trocarToken(novo) {
    if (!novo) return;
    var el = document.getElementById('gh-token');
    if (el) el.value = novo;
  }

  // ── Campos ────────────────────────────────────────────────────────────

  function serializar(form) {
    var dados = {};
    Array.prototype.forEach.call(form.elements, function (el) {
      if (!el.name || el.disabled) return;
      if (el.type === 'checkbox')      { dados[el.name] = el.checked ? '1' : '0'; }
      else if (el.type === 'radio')    { if (el.checked) dados[el.name] = el.value; }
      else                             { dados[el.name] = el.value; }
    });
    return dados;
  }

  function hidratar(form, registro) {
    Array.prototype.forEach.call(form.elements, function (el) {
      if (!el.name || !(el.name in registro)) return;
      var v = registro[el.name];
      if (el.type === 'checkbox')   { el.checked = v === 1 || v === '1' || v === true; }
      else if (el.type === 'radio') { el.checked = String(el.value) === String(v); }
      else                          { el.value = v === null || v === undefined ? '' : v; }
    });
  }

  function limpar(form) {
    form.reset();
    // reset() devolve os valores do HTML, não os esvazia: campos de edição
    // anterior sobreviveriam num "novo registro".
    Array.prototype.forEach.call(form.elements, function (el) {
      if (!el.name || el.type === 'hidden' && el.name === '_token') return;
      if (el.type === 'checkbox')   { el.checked = el.defaultChecked; }
      else if (el.tagName === 'SELECT') { el.selectedIndex = 0; }
      else if (el.type !== 'hidden')    { el.value = ''; }
      else if (el.name !== '_token')    { el.value = ''; }
    });
    limparErros(form);
  }

  // ── Erros por campo ───────────────────────────────────────────────────

  function limparErros(form) {
    form.querySelectorAll('.is-invalid').forEach(function (el) {
      el.classList.remove('is-invalid');
      el.removeAttribute('aria-invalid');
      el.removeAttribute('aria-describedby');
    });
    form.querySelectorAll('.gh-erro-campo').forEach(function (el) { el.remove(); });
    var geral = form.querySelector('.gh-erro-geral');
    if (geral) geral.remove();
  }

  function mostrarErros(form, erros) {
    limparErros(form);
    var primeiro = null;

    Object.keys(erros).forEach(function (campo) {
      var el = form.elements[campo];
      if (!el) return;

      el.classList.add('is-invalid');
      el.setAttribute('aria-invalid', 'true');

      var id = 'erro-' + campo;
      el.setAttribute('aria-describedby', id);

      var span = document.createElement('span');
      span.className = 'gh-erro-campo';
      span.id = id;
      span.textContent = erros[campo][0];
      (el.closest('.gh-campo') || el.parentNode).appendChild(span);

      if (!primeiro) primeiro = el;
    });

    if (primeiro) primeiro.focus();
    return primeiro;
  }

  function mostrarErroGeral(form, mensagem) {
    var geral = form.querySelector('.gh-erro-geral');
    if (!geral) {
      geral = document.createElement('div');
      geral.className = 'gh-erro-geral';
      geral.setAttribute('role', 'alert');
      form.insertBefore(geral, form.firstChild);
    }
    geral.textContent = mensagem;
  }

  // ── Gravação ──────────────────────────────────────────────────────────

  /**
   * O custo do CRM (`custoEvento`) acompanha cada gravação. Quando essa
   * escrita falha, o salvamento do fornecedor não é desfeito — mas o silêncio
   * seria pior que o erro: foi divergência descoberta tarde que gerou toda a
   * conciliação de setembro/2026. Por isso o aviso aparece, e fica na tela
   * mais tempo que o toast comum.
   */
  function avisarCrm(res) {
    if (!res || !res.avisos || !res.avisos.length) return;
    res.avisos.forEach(function (msg) {
      ui.toast(msg, 'erro', 12000);
    });
  }

  /**
   * @param {HTMLFormElement} form
   * @param {string}          ent     nome da entidade
   * @param {object}          opcoes  { aoSalvar, botao }
   */
  function salvar(form, ent, opcoes) {
    opcoes = opcoes || {};
    var botao = opcoes.botao || form.querySelector('[type=submit]');

    var dados = serializar(form);
    dados._token = token();

    salvando = true;
    ui.ocupado(botao, true);
    limparErros(form);

    return core.http.post('salvar/' + encodeURIComponent(ent), dados)
      .catch(function (e) {
        // 419 = token consumido ou substituído. Acontece sem culpa de
        // ninguém: getFormToken() sobrescreve o token guardado na sessão a
        // cada página carregada, então abrir uma segunda aba invalida a
        // primeira. O servidor manda um token novo junto com o erro — repetir
        // uma única vez transforma uma falha confusa em recuperação
        // silenciosa. Só uma tentativa, e só para 419.
        if (e.status !== 419 || !e.corpo || !e.corpo.token) {
          throw e;
        }
        trocarToken(e.corpo.token);
        dados._token = e.corpo.token;
        return core.http.post('salvar/' + encodeURIComponent(ent), dados);
      })
      .then(function (res) {
        trocarToken(res.token);
        ui.toast(res.novo ? 'Registro criado.' : 'Alterações salvas.', 'ok');
        avisarCrm(res);
        if (opcoes.aoSalvar) opcoes.aoSalvar(res.registro, res.novo);
        return res;
      })
      .catch(function (e) {
        // O servidor devolve um token novo junto com o erro; sem trocá-lo, a
        // segunda tentativa falharia por token consumido em vez de pelo
        // problema real.
        if (e.corpo && e.corpo.token) trocarToken(e.corpo.token);

        if (e.status === 422 && e.erros) {
          mostrarErros(form, e.erros);
          mostrarErroGeral(form, 'Confira os campos destacados.');
        } else {
          // O modal não fecha e nada é perdido: quem escreveu pode tentar
          // de novo sem redigitar.
          mostrarErroGeral(form, e.message);
          ui.toast(e.message, 'erro');
        }
        throw e;
      })
      .finally(function () {
        salvando = false;
        ui.ocupado(botao, false);
      });
  }

  function excluir(ent, id, opcoes) {
    opcoes = opcoes || {};

    return ui.confirmar(opcoes.mensagem || 'Excluir este registro?', { confirmar: 'Excluir' })
      .then(function (ok) {
        if (!ok) return null;

        salvando = true;
        var enviar = function (tk) {
          return core.http.post('excluir/' + encodeURIComponent(ent), { id: id, _token: tk });
        };

        return enviar(token())
          .catch(function (e) {
            // Mesma recuperacao do salvar: token substituido por outra aba.
            if (e.status !== 419 || !e.corpo || !e.corpo.token) { throw e; }
            trocarToken(e.corpo.token);
            return enviar(e.corpo.token);
          })
          .then(function (res) {
            trocarToken(res.token);
            ui.toast('Registro excluído.', 'ok');
            avisarCrm(res);
            if (opcoes.aoExcluir) opcoes.aoExcluir(id);
            return res;
          })
          .catch(function (e) {
            if (e.corpo && e.corpo.token) trocarToken(e.corpo.token);
            ui.toast(e.message, 'erro');
            throw e;
          })
          .finally(function () { salvando = false; });
      });
  }

  /** Carrega um registro do servidor para edição. */
  function carregar(ent, id) {
    return core.http.get('registro/' + encodeURIComponent(ent) + '/' + encodeURIComponent(id))
      .then(function (res) { return res.registro; });
  }

  return {
    serializar: serializar,
    hidratar: hidratar,
    limpar: limpar,
    salvar: salvar,
    excluir: excluir,
    carregar: carregar,
    estaSalvando: estaSalvando,
    limparErros: limparErros,
    mostrarErros: mostrarErros
  };
})();
