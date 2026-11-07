<?php

/**
 * Sprite de ícones — injetado uma única vez por template/hotel.php.
 *
 * Os <symbol> ficam ocultos e são referenciados por <use href="#i-nome">,
 * tanto pelo PHP (hicone()) quanto pelo JS (GH.core.icone()). Uma definição,
 * dois consumidores.
 *
 * Traço em currentColor e fill nenhum: o ícone herda a cor do texto e
 * acompanha o tema claro/escuro sem nenhuma regra extra.
 */
?>
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
        <symbol id="i-hotel" viewBox="0 0 24 24">
            <path d="M3 21h18M5 21V7l7-4 7 4v14"/><path d="M9 21v-5h6v5"/><path d="M9 10h.01M15 10h.01"/>
        </symbol>
        <symbol id="i-av" viewBox="0 0 24 24">
            <rect x="2" y="4" width="14" height="12" rx="2"/><path d="M16 9l6-3v12l-6-3"/><path d="M7 20h6"/>
        </symbol>
        <symbol id="i-video" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="9"/><polygon points="10 9 15 12 10 15"/>
        </symbol>
        <symbol id="i-aviao" viewBox="0 0 24 24">
            <path d="M2 13l3-1 5 2 4-8 2 1-2 8 5 2 1 3-8-2-3 5-2-1 1-5-6-2z"/>
        </symbol>
        <symbol id="i-onibus" viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="12" rx="2"/><path d="M3 10h18"/><path d="M7 20v-2M17 20v-2"/><path d="M7 13h.01M17 13h.01"/>
        </symbol>
        <symbol id="i-carro" viewBox="0 0 24 24">
            <path d="M3 13l2-5a2 2 0 0 1 2-1h10a2 2 0 0 1 2 1l2 5v5H3z"/><circle cx="7.5" cy="17.5" r="1.5"/><circle cx="16.5" cy="17.5" r="1.5"/>
        </symbol>
        <symbol id="i-caminhao" viewBox="0 0 24 24">
            <rect x="1" y="6" width="13" height="10"/><path d="M14 9h4l3 3v4h-7z"/><circle cx="6" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>
        </symbol>
        <symbol id="i-caixa" viewBox="0 0 24 24">
            <path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>
        </symbol>
        <symbol id="i-calendario" viewBox="0 0 24 24">
            <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>
        </symbol>
        <symbol id="i-dinheiro" viewBox="0 0 24 24">
            <rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/>
        </symbol>
        <symbol id="i-relatorio" viewBox="0 0 24 24">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h8M8 17h5"/>
        </symbol>
        <symbol id="i-grafico" viewBox="0 0 24 24">
            <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
        </symbol>
        <symbol id="i-estrela" viewBox="0 0 24 24">
            <polygon points="12 2 15.1 8.6 22 9.5 17 14.4 18.2 21.4 12 18.1 5.8 21.4 7 14.4 2 9.5 8.9 8.6"/>
        </symbol>
        <symbol id="i-check" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></symbol>
        <symbol id="i-x" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></symbol>
        <symbol id="i-alerta" viewBox="0 0 24 24">
            <path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>
        </symbol>
        <symbol id="i-editar" viewBox="0 0 24 24">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/>
        </symbol>
        <symbol id="i-lixeira" viewBox="0 0 24 24">
            <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/>
        </symbol>
        <symbol id="i-mais" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
        <symbol id="i-busca" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></symbol>
        <symbol id="i-atualizar" viewBox="0 0 24 24">
            <path d="M21 12a9 9 0 1 1-3-6.7"/><polyline points="21 3 21 9 15 9"/>
        </symbol>
        <symbol id="i-download" viewBox="0 0 24 24">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
        </symbol>
        <symbol id="i-nota" viewBox="0 0 24 24">
            <path d="M4 4h16v12l-4 4H4z"/><path d="M20 16h-4v4"/><path d="M8 9h8M8 13h5"/>
        </symbol>
        <symbol id="i-cama" viewBox="0 0 24 24">
            <path d="M2 18V7M2 12h20v6"/><path d="M6 12V9h6v3"/><path d="M22 18v-4"/>
        </symbol>
        <symbol id="i-nf" viewBox="0 0 24 24">
            <path d="M5 3h14v18l-3-2-2 2-2-2-2 2-2-2-3 2z"/><path d="M9 8h6M9 12h6"/>
        </symbol>
        <symbol id="i-lista" viewBox="0 0 24 24">
            <path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/>
        </symbol>
        <symbol id="i-link" viewBox="0 0 24 24">
            <path d="M9 15l6-6"/><path d="M10 6l1-1a3.5 3.5 0 0 1 5 5l-1 1"/><path d="M14 18l-1 1a3.5 3.5 0 0 1-5-5l1-1"/>
        </symbol>
    </defs>
</svg>
