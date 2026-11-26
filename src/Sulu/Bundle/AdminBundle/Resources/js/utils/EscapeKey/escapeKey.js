// @flow
import Mousetrap from 'mousetrap';

const ESCAPE_KEY = 'esc';

// Mousetrap keeps one handler per key and unbinds it for everybody. With overlays and popovers on top of each
// other, the one that closes first would take the esc key from the one below. So their handlers are stacked:
// the topmost one is bound, and the one below is bound again when it is removed.
const handlers = [];

export function addEscapeHandler(handler: () => void) {
    handlers.push(handler);
    Mousetrap.bind(ESCAPE_KEY, handler);
}

export function removeEscapeHandler(handler: () => void) {
    const index = handlers.indexOf(handler);
    if (index === -1) {
        return;
    }

    handlers.splice(index, 1);

    if (handlers.length > 0) {
        Mousetrap.bind(ESCAPE_KEY, handlers[handlers.length - 1]);
    } else {
        Mousetrap.unbind(ESCAPE_KEY);
    }
}
