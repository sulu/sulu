/**
 * Preview deep-link bridge.
 *
 * Runs inside the Sulu preview iframe. Hovering an element carrying a
 * `data-sulu-preview-id` attribute (rendered via the `sulu_preview_deep_link()`
 * Twig function) shows a focus button; clicking it posts a message to the
 * parent admin window so it can scroll to and expand the matching block.
 *
 * Vanilla JS, no dependencies. UI lives in a closed Shadow DOM so host page
 * styles (box-sizing, resets, cascades) can never leak in or out.
 *
 * The admin rewrites the same iframe document on every preview update via
 * document.open()/write()/close(), which re-executes this script while the
 * window object (and its listeners) survives. To avoid stacking a fresh set of
 * listeners on every keystroke, the global mouse/scroll listeners are bound once
 * per window and read the current overlay from shared state; only the overlay -
 * wiped together with the old document body - is rebuilt on each run.
 */
(function () {
    'use strict';

    // Either embedded in the admin's preview iframe (window.parent) or opened via the preview's
    // "open in window" button, which admin opens with window.open() (window.opener). Neither
    // means this is a standalone visit with no admin to bridge to.
    var adminWindow = window.opener || (window.parent !== window ? window.parent : null);
    if (!adminWindow) {
        return;
    }

    var ATTRIBUTE = 'data-sulu-preview-id';
    var MESSAGE_NAVIGATE = 'sulu.preview.navigate';
    var MESSAGE_READY = 'sulu.preview.ready';
    var STATE_KEY = '__suluPreviewDeepLink';

    // The preview iframe/window is always same-origin with the admin, so target it explicitly
    // instead of falling back to a wildcard origin when document.referrer is unavailable
    // (e.g. under a strict Referrer-Policy).
    function postToAdmin(message) {
        adminWindow.postMessage(message, window.location.origin);
    }

    function findAnchor(element) {
        return element instanceof Element ? element.closest('[' + ATTRIBUTE + ']') : null;
    }

    function collectKnownIds() {
        var ids = [];
        document.querySelectorAll('[' + ATTRIBUTE + ']').forEach(function (element) {
            ids.push(element.getAttribute(ATTRIBUTE));
        });

        return ids;
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

        // Events targeting shadow-tree content never reach the window-level listeners as anything
        // but the retargeted host, so leaving the button/outline is detected here, directly on the
        // host, where mouseleave isn't subject to that retargeting.
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

    // Bound once per window: window listeners survive document.open(), so they are not re-added on
    // each preview update. They read the current overlay from shared state.
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

            // The overlay lives in a closed shadow tree appended to <body>, so once the pointer
            // reaches the button/outline the browser retargets relatedTarget to the shadow host
            // (it has no data-sulu-preview-id ancestor). Without this check the overlay would hide
            // itself the instant the pointer arrives at the button.
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
        var state = window[STATE_KEY];
        if (!state) {
            state = {overlay: null, activeAnchor: null};
            window[STATE_KEY] = state;
            bindGlobalListeners(state);
        }

        rebuildOverlay(state);

        postToAdmin({type: MESSAGE_READY, ids: collectKnownIds()});
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
