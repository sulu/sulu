/**
 * Preview deep-link bridge, running inside the Sulu preview iframe. Hovering an element with a
 * `data-sulu-preview-id` attribute shows an edit button that posts a message to the admin window,
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
    var HIDE_DELAY = 200;

    // The pencil of the design, used as a mask so the icon takes the button color.
    var ICON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="218 60 834 834">' +
        '<path d="M780 155L852 83Q906 37 960 83L1033 157Q1068 200 1035 240L950 325z"/>' +
        '<path d="M737 200L906 370L477 801L307 631z"/>' +
        '<path d="M262 677L431 847L240 893Q218 897 218 872z"/>' +
        '</svg>';
    var ICON_URL = 'url("data:image/svg+xml,' + encodeURIComponent(ICON_SVG) + '")';

    // Target the admin explicitly (always same-origin) instead of a wildcard origin.
    function postToAdmin(message) {
        adminWindow.postMessage(message, window.location.origin);
    }

    function findAnchor(element) {
        return element instanceof Element ? element.closest('[' + ATTRIBUTE + ']') : null;
    }

    function createOverlay() {
        var host = document.createElement('div');
        host.className = 'sulu-preview-deep-link';
        host.style.cssText = 'position:static;';
        document.body.appendChild(host);

        var root = host.attachShadow({mode: 'closed'});

        var style = document.createElement('style');
        // Projects restyle the overlay with the --sulu-preview-deep-link-* custom properties (they inherit into
        // the shadow tree) or with ::part(outline) and ::part(button) on the host's sulu-preview-deep-link class.
        style.textContent =
            ':host { all: initial; }' +
            '.outline { --border-color: var(--sulu-preview-deep-link-border-color, #23a3ec);' +
            ' position: fixed; z-index: 2147483647; pointer-events: none; box-sizing: border-box;' +
            ' border-radius: var(--sulu-preview-deep-link-border-radius, 0);' +
            ' outline: var(--sulu-preview-deep-link-border-width, 2px) solid var(--border-color);' +
            ' outline-offset: calc(var(--sulu-preview-deep-link-border-width, 2px) * -1);' +
            ' background: var(--sulu-preview-deep-link-background,' +
            ' color-mix(in srgb, var(--border-color) 8%, transparent)); display: none; }' +
            '.button { all: initial; position: fixed; z-index: 2147483647; pointer-events: auto;' +
            ' display: none; align-items: center; justify-content: center;' +
            ' width: var(--sulu-preview-deep-link-button-width, 28px);' +
            ' height: var(--sulu-preview-deep-link-button-height, 22px);' +
            ' border-radius: var(--sulu-preview-deep-link-button-border-radius, 4px 4px 4px 0); cursor: pointer;' +
            ' margin-bottom: var(--sulu-preview-deep-link-button-gap, 4px);' +
            ' background: var(--sulu-preview-deep-link-button-background, #112a46);' +
            ' color: var(--sulu-preview-deep-link-button-color, #fff); }' +
            '.icon { height: 64%; aspect-ratio: 1; background: currentColor;' +
            ' -webkit-mask: var(--sulu-preview-deep-link-button-icon, ' + ICON_URL + ') center / contain no-repeat;' +
            ' mask: var(--sulu-preview-deep-link-button-icon, ' + ICON_URL + ') center / contain no-repeat; }';
        root.appendChild(style);

        var outline = document.createElement('div');
        outline.className = 'outline';
        outline.setAttribute('part', 'outline');
        root.appendChild(outline);

        var button = document.createElement('div');
        button.className = 'button';
        button.setAttribute('part', 'button');

        var icon = document.createElement('div');
        icon.className = 'icon';
        icon.setAttribute('part', 'icon');
        button.appendChild(icon);
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

        // The button sits above the top left corner, a gap away so it does not touch the outline. Without room
        // above the element it moves inside, the same gap away from the corner.
        overlay.button.style.display = 'flex';
        var buttonHeight = overlay.button.offsetHeight;
        var gap = parseFloat(getComputedStyle(overlay.button).marginBottom) || 0;
        var above = rect.top >= buttonHeight + gap;
        overlay.button.style.top = (above ? rect.top - buttonHeight - gap : Math.max(rect.top, 0) + gap) + 'px';
        overlay.button.style.left = (above ? rect.left : rect.left + gap) + 'px';
    }

    function hide(overlay) {
        overlay.outline.style.display = 'none';
        overlay.button.style.display = 'none';
    }

    // The button sits outside the element, so the pointer may cross a few pixels of page on its way there.
    function scheduleHide(state) {
        clearTimeout(state.hideTimer);
        state.hideTimer = setTimeout(function () {
            cancelSwitch(state);
            state.activeAnchor = null;
            hide(state.overlay);
        }, HIDE_DELAY);
    }

    // Leaving a nested element upwards crosses its ancestor on the way to the button, so a switch to an ancestor
    // waits for the pointer to settle there.
    function switchAnchor(state, anchor) {
        if (state.pendingAnchor === anchor) {
            return;
        }

        clearTimeout(state.switchTimer);
        state.pendingAnchor = anchor;
        state.switchTimer = setTimeout(function () {
            state.pendingAnchor = null;
            state.activeAnchor = anchor;
            positionAt(state.overlay, anchor);
        }, HIDE_DELAY);
    }

    function cancelSwitch(state) {
        clearTimeout(state.switchTimer);
        state.pendingAnchor = null;
    }

    function rebuildOverlay(state) {
        var overlay = createOverlay();
        state.overlay = overlay;
        state.activeAnchor = null;

        overlay.host.addEventListener('mouseenter', function () {
            clearTimeout(state.hideTimer);
            cancelSwitch(state);
        });
        // mouseleave on the host is not subject to the shadow-tree retargeting the window listeners see.
        overlay.host.addEventListener('mouseleave', function () {
            scheduleHide(state);
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

            clearTimeout(state.hideTimer);

            if (state.activeAnchor && anchor !== state.activeAnchor && anchor.contains(state.activeAnchor)) {
                switchAnchor(state, anchor);

                return;
            }

            cancelSwitch(state);
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

            scheduleHide(state);
        }, true);

        window.addEventListener('scroll', function () {
            if (state.activeAnchor && state.overlay) {
                positionAt(state.overlay, state.activeAnchor);
            }
        }, true);
    }

    function run() {
        var state = {overlay: null, activeAnchor: null, hideTimer: null, switchTimer: null, pendingAnchor: null};

        bindGlobalListeners(state);
        rebuildOverlay(state);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
