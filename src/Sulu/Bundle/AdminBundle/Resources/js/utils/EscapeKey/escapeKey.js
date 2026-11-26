// @flow
import Mousetrap from 'mousetrap';

const ESCAPE_KEY = 'esc';

// Mousetrap keeps one handler per key and unbinds it for everybody. With overlays and popovers on top of each
// other, the one that closes first would take the esc key from the one below. So their handlers are stacked:
// the topmost one handles the key, and the one below gets it again when that one is removed.
const handlers = [];

function handleEscape(event: ?KeyboardEvent) {
    // A component may have used the key before, e.g. a select closing its options. The handler of its popover is
    // gone by then, and the layer below must not react to the same keypress.
    if (event && event.defaultPrevented) {
        return;
    }

    handlers[handlers.length - 1]();
}

function updateBinding() {
    if (handlers.length > 0) {
        Mousetrap.bind(ESCAPE_KEY, handleEscape);
    } else {
        Mousetrap.unbind(ESCAPE_KEY);
    }
}

export function addEscapeHandler(handler: () => void) {
    handlers.push(handler);
    updateBinding();
}

export function removeEscapeHandler(handler: () => void) {
    const index = handlers.indexOf(handler);
    if (index === -1) {
        return;
    }

    handlers.splice(index, 1);
    updateBinding();
}
