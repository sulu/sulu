/**
 * Website edit button. Shows a link to the admin while the admin login cookie is set; the cookie value is the url of
 * the admin endpoint that resolves the current page. Works as a plain script and as an import in a bundle.
 * The UI lives in a closed Shadow DOM so the website styles cannot reach it.
 */
(function () {
    'use strict';

    var COOKIE_NAME = 'sulu_admin_logged_in';
    var HOST_CLASS = 'sulu-website-edit-button';

    var TRANSLATIONS = {
        en: {edit: 'Edit in admin', close: 'Close'},
        de: {edit: 'Im Admin bearbeiten', close: 'Schließen'},
    };

    var ICON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="218 60 834 834">' +
        '<path d="M780 155L852 83Q906 37 960 83L1033 157Q1068 200 1035 240L950 325z"/>' +
        '<path d="M737 200L906 370L477 801L307 631z"/>' +
        '<path d="M262 677L431 847L240 893Q218 897 218 872z"/>' +
        '</svg>';
    var ICON_URL = 'url("data:image/svg+xml,' + encodeURIComponent(ICON_SVG) + '")';

    if (window.parent !== window || document.querySelector('.' + HOST_CLASS)) {
        return;
    }

    var endpoint = getEndpoint();
    if (!endpoint) {
        return;
    }

    function getEndpoint() {
        var value = null;
        document.cookie.split(';').forEach(function (cookie) {
            var separator = cookie.indexOf('=');
            if (cookie.slice(0, separator).trim() === COOKIE_NAME) {
                value = cookie.slice(separator + 1).trim();
            }
        });

        if (!value) {
            return null;
        }

        try {
            var url = new URL(decodeURIComponent(value), window.location.href);
        } catch (error) {
            return null;
        }

        // Any subdomain can write the cookie, so it must never become a "javascript:" link.
        return url.protocol === 'https:' || url.protocol === 'http:' ? url : null;
    }

    function getTranslations() {
        var language = (document.documentElement.lang || '').split(/[-_]/)[0].toLowerCase();

        return TRANSLATIONS[language] || TRANSLATIONS.en;
    }

    // The Symfony web debug toolbar loads asynchronously and sits bottom right; ":has()" follows it.
    function addToolbarOffset() {
        var style = document.createElement('style');
        style.textContent = 'body:has(.sf-toolbar) .' + HOST_CLASS +
            ' { --sulu-website-edit-button-toolbar-offset: 36px; }';
        document.head.appendChild(style);
    }

    function init() {
        var translations = getTranslations();

        var host = document.createElement('div');
        host.className = HOST_CLASS;
        host.style.cssText = 'position:static;';
        document.body.appendChild(host);
        addToolbarOffset();

        var root = host.attachShadow({mode: 'closed'});

        var style = document.createElement('style');
        style.textContent =
            ':host { all: initial; }' +
            '.wrapper { all: initial; box-sizing: border-box; position: fixed;' +
            ' top: var(--sulu-website-edit-button-top, auto);' +
            ' right: var(--sulu-website-edit-button-right, 16px);' +
            ' bottom: calc(var(--sulu-website-edit-button-bottom, 16px)' +
            ' + var(--sulu-website-edit-button-toolbar-offset, 0px));' +
            ' left: var(--sulu-website-edit-button-left, auto);' +
            ' z-index: 2147483647; display: flex; align-items: stretch;' +
            ' height: var(--sulu-website-edit-button-height, 36px);' +
            ' border-radius: var(--sulu-website-edit-button-border-radius, 3px); overflow: hidden;' +
            ' box-shadow: 0 2px 10px rgba(0, 0, 0, .25); }' +
            '.button, .close { all: initial; box-sizing: border-box; display: flex; align-items: center;' +
            ' background: var(--sulu-website-edit-button-background, #112a46);' +
            ' color: var(--sulu-website-edit-button-color, #fff);' +
            ' font: 12px/1 "Source Sans Pro", "Helvetica Neue", Arial, sans-serif; white-space: nowrap;' +
            ' cursor: pointer; transition: background-color .15s; }' +
            '.button { gap: 8px; padding: var(--sulu-website-edit-button-padding, 0 14px); }' +
            '.close { justify-content: center; width: 28px; font-size: 16px;' +
            ' border-left: 1px solid rgba(255, 255, 255, .2); }' +
            '.button:hover, .close:hover { background: var(--sulu-website-edit-button-hover-background, #52b6ca); }' +
            '.button:focus-visible, .close:focus-visible {' +
            ' outline: 2px solid var(--sulu-website-edit-button-focus-color, #23a3ec); outline-offset: -2px; }' +
            '.icon { width: 14px; height: 14px; flex: none; background: currentColor;' +
            ' -webkit-mask: ' + ICON_URL + ' center / contain no-repeat;' +
            ' mask: ' + ICON_URL + ' center / contain no-repeat; }';
        root.appendChild(style);

        var wrapper = document.createElement('div');
        wrapper.className = 'wrapper';
        wrapper.setAttribute('part', 'wrapper');

        var button = document.createElement('a');
        button.className = 'button';
        button.setAttribute('part', 'button');
        button.setAttribute('target', '_blank');
        button.setAttribute('rel', 'noopener');
        button.setAttribute('title', translations.edit);
        // Refreshed on interaction, so client side navigation of a headless frontend edits the page now shown.
        function updateHref() {
            var url = new URL(endpoint.href);
            url.searchParams.set('url', window.location.href);
            button.setAttribute('href', url.href);
        }
        ['pointerenter', 'focus', 'click'].forEach(function (eventName) {
            button.addEventListener(eventName, updateHref);
        });
        updateHref();

        var icon = document.createElement('span');
        icon.className = 'icon';
        icon.setAttribute('part', 'icon');
        icon.setAttribute('aria-hidden', 'true');
        button.appendChild(icon);

        var text = document.createElement('span');
        text.setAttribute('part', 'label');
        text.textContent = translations.edit;
        button.appendChild(text);

        // Hides the button until the next page load.
        var close = document.createElement('button');
        close.className = 'close';
        close.setAttribute('part', 'close');
        close.setAttribute('type', 'button');
        close.setAttribute('title', translations.close);
        close.setAttribute('aria-label', translations.close);
        close.textContent = '×';
        close.addEventListener('click', function () {
            host.remove();
        });

        wrapper.appendChild(button);
        wrapper.appendChild(close);
        root.appendChild(wrapper);
    }

    if (document.body) {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})();
