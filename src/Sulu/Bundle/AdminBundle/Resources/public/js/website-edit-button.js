/**
 * Website edit button, rendered by sulu_admin_edit_button(). Shows a link to the admin while the admin login cookie
 * is set. The UI lives in a closed Shadow DOM so the website styles cannot reach it.
 */
(function () {
    'use strict';

    var script = document.currentScript;
    if (!script || window.parent !== window) {
        return;
    }

    var url = script.getAttribute('data-url');
    var label = script.getAttribute('data-label') || '';
    var cookieName = script.getAttribute('data-cookie');
    if (!url || !cookieName || !hasCookie(cookieName)) {
        return;
    }

    var ICON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="218 60 834 834">' +
        '<path d="M780 155L852 83Q906 37 960 83L1033 157Q1068 200 1035 240L950 325z"/>' +
        '<path d="M737 200L906 370L477 801L307 631z"/>' +
        '<path d="M262 677L431 847L240 893Q218 897 218 872z"/>' +
        '</svg>';
    var ICON_URL = 'url("data:image/svg+xml,' + encodeURIComponent(ICON_SVG) + '")';

    function hasCookie(name) {
        return document.cookie.split(';').some(function (cookie) {
            return cookie.trim().indexOf(name + '=') === 0;
        });
    }

    function init() {
        var host = document.createElement('div');
        host.className = 'sulu-website-edit-button';
        host.style.cssText = 'position:static;';
        document.body.appendChild(host);

        var root = host.attachShadow({mode: 'closed'});

        var style = document.createElement('style');
        style.textContent =
            ':host { all: initial; }' +
            '.button { all: initial; box-sizing: border-box; position: fixed;' +
            ' top: var(--sulu-website-edit-button-top, auto);' +
            ' right: var(--sulu-website-edit-button-right, 16px);' +
            ' bottom: var(--sulu-website-edit-button-bottom, 16px);' +
            ' left: var(--sulu-website-edit-button-left, auto);' +
            ' z-index: 2147483647; display: flex; align-items: center; gap: 8px;' +
            ' height: var(--sulu-website-edit-button-height, 36px);' +
            ' padding: var(--sulu-website-edit-button-padding, 0 14px);' +
            ' border-radius: var(--sulu-website-edit-button-border-radius, 3px);' +
            ' background: var(--sulu-website-edit-button-background, #112a46);' +
            ' color: var(--sulu-website-edit-button-color, #fff);' +
            ' font: 12px/1 "Source Sans Pro", "Helvetica Neue", Arial, sans-serif; white-space: nowrap;' +
            ' box-shadow: 0 2px 10px rgba(0, 0, 0, .25); cursor: pointer; transition: background-color .15s; }' +
            '.button:hover { background: var(--sulu-website-edit-button-hover-background, #52b6ca); }' +
            '.button:focus-visible { outline: 2px solid var(--sulu-website-edit-button-focus-color, #23a3ec);' +
            ' outline-offset: 2px; }' +
            '.icon { width: 14px; height: 14px; flex: none; background: currentColor;' +
            ' -webkit-mask: ' + ICON_URL + ' center / contain no-repeat;' +
            ' mask: ' + ICON_URL + ' center / contain no-repeat; }';
        root.appendChild(style);

        var button = document.createElement('a');
        button.className = 'button';
        button.setAttribute('part', 'button');
        button.setAttribute('href', url);
        button.setAttribute('target', '_blank');
        button.setAttribute('rel', 'noopener');
        button.setAttribute('title', label);

        var icon = document.createElement('span');
        icon.className = 'icon';
        icon.setAttribute('part', 'icon');
        icon.setAttribute('aria-hidden', 'true');
        button.appendChild(icon);

        var text = document.createElement('span');
        text.setAttribute('part', 'label');
        text.textContent = label;
        button.appendChild(text);

        root.appendChild(button);
    }

    if (document.body) {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})();
