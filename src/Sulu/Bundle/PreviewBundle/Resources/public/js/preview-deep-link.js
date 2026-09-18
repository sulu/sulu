/**
 * Preview deep-link bridge, running inside the Sulu preview iframe. Hovering an element with a
 * `data-sulu-preview-id` attribute shows a focus button that posts a message to the admin window,
 * which then scrolls to and expands the matching block. The UI lives in a closed Shadow DOM. The
 * admin rewrites the iframe via document.open() on every update, so everything is rebuilt each run.
 */
(function () {
    'use strict';

    // The admin is window.parent (iframe) or window.opener ("open in window"); absent means standalone.
    var adminWindow = window.opener || (window.parent !== window ? window.parent : null);
    if (!adminWindow) {
        return;
    }

    var ATTRIBUTE = 'data-sulu-preview-id';
    var MESSAGE_NAVIGATE = 'sulu.preview.navigate';

    // Target the admin explicitly (always same-origin) instead of a wildcard origin.
    function postToAdmin(message) {
        adminWindow.postMessage(message, window.location.origin);
    }

    function findAnchor(element) {
        return element instanceof Element ? element.closest('[' + ATTRIBUTE + ']') : null;
    }

    function createOverlay() {
        var host = document.createElement('div');
        host.style.cssText = 'position:static;';
        document.body.appendChild(host);

        var root = host.attachShadow({mode: 'closed'});

        var style = document.createElement('style');
        style.textContent =
            ':host { all: initial; }' +
            '.outline { position: fixed; z-index: 2147483647; pointer-events: none;' +
            ' outline: 2px solid #23a3ec; outline-offset: -2px; box-sizing: border-box;' +
            ' background: rgba(35, 163, 236, 0.08); display: none; }' +
            '.button { all: initial; position: fixed; z-index: 2147483647; pointer-events: auto;' +
            ' display: none; align-items: center; justify-content: center;' +
            ' width: 28px; height: 28px; border-radius: 4px; background: #23a3ec; cursor: pointer;' +
            ' box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3); }' +
            '.button svg { width: 16px; height: 16px; fill: #fff; }';
        root.appendChild(style);

        var outline = document.createElement('div');
        outline.className = 'outline';
        root.appendChild(outline);

        var button = document.createElement('div');
        button.className = 'button';
        button.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" ' +
            'stroke-linecap="round">' +
            '<circle cx="12" cy="12" r="7" fill="none"/>' +
            '<path d="M12 0v4"/>' +
            '<path d="M12 20v4"/>' +
            '<path d="M0 12h4"/>' +
            '<path d="M20 12h4"/>' +
            '<circle cx="12" cy="12" r="1.3" fill="#fff" stroke="none"/>' +
            '</svg>';
        root.appendChild(button);

        return {host: host, outline: outline, button: button};
    }

    function positionAt(overlay, element) {
        var rect = element.getBoundingClientRect();

        overlay.outline.style.top = rect.top + 'px';
        overlay.outline.style.left = rect.left + 'px';
        overlay.outline.style.width = rect.width + 'px';
        overlay.outline.style.height = rect.height + 'px';
        overlay.outline.style.display = 'block';

        var buttonSize = 28;
        overlay.button.style.top = Math.max(rect.top, 0) + 'px';
        overlay.button.style.left = Math.max(rect.right - buttonSize, rect.left) + 'px';
        overlay.button.style.display = 'flex';
    }

    function hide(overlay) {
        overlay.outline.style.display = 'none';
        overlay.button.style.display = 'none';
    }

    function rebuildOverlay(state) {
        var overlay = createOverlay();
        state.overlay = overlay;
        state.activeAnchor = null;

        // mouseleave on the host is not subject to the shadow-tree retargeting the window listeners see.
        overlay.host.addEventListener('mouseleave', function () {
            state.activeAnchor = null;
            hide(overlay);
        });

        overlay.button.addEventListener('click', function () {
            if (!state.activeAnchor) {
                return;
            }

            postToAdmin({type: MESSAGE_NAVIGATE, id: state.activeAnchor.getAttribute(ATTRIBUTE)});
        });
    }

    // Rebound each run: document.open() dropped the previous window listeners, so nothing stacks.
    function bindGlobalListeners(state) {
        window.addEventListener('mouseover', function (event) {
            if (!state.overlay) {
                return;
            }

            var anchor = findAnchor(event.target);
            if (!anchor) {
                return;
            }

            state.activeAnchor = anchor;
            positionAt(state.overlay, anchor);
        }, true);

        window.addEventListener('mouseout', function (event) {
            if (!state.overlay) {
                return;
            }

            var anchor = findAnchor(event.target);
            if (!anchor || anchor !== state.activeAnchor) {
                return;
            }

            // Pointer entering the button/outline retargets relatedTarget to the shadow host; without
            // this the overlay would hide the instant the pointer reaches the button.
            if (event.relatedTarget === state.overlay.host) {
                return;
            }

            var toAnchor = event.relatedTarget instanceof Element ? findAnchor(event.relatedTarget) : null;
            if (toAnchor) {
                return;
            }

            state.activeAnchor = null;
            hide(state.overlay);
        }, true);

        window.addEventListener('scroll', function () {
            if (state.activeAnchor && state.overlay) {
                positionAt(state.overlay, state.activeAnchor);
            }
        }, true);
    }

    function run() {
        var state = {overlay: null, activeAnchor: null};

        bindGlobalListeners(state);
        rebuildOverlay(state);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
