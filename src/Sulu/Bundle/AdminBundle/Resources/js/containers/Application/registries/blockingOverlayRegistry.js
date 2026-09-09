// @flow
import type {ComponentType} from 'react';

// Overlays a bundle renders on top of the whole application, e.g. to make the user complete a
// mandatory step before anything else. Every registered component decides on its own whether it is
// currently open, because only the bundle owning it knows the condition.
class BlockingOverlayRegistry {
    overlays: {[string]: ComponentType<*>};

    constructor() {
        this.clear();
    }

    clear() {
        this.overlays = {};
    }

    has(name: string) {
        return !!this.overlays[name];
    }

    add(name: string, overlay: ComponentType<*>) {
        if (name in this.overlays) {
            throw new Error('The key "' + name + '" has already been used for another blocking overlay');
        }

        this.overlays[name] = overlay;
    }

    getAll(): Array<{name: string, overlay: ComponentType<*>}> {
        return Object.keys(this.overlays).map((name) => ({name, overlay: this.overlays[name]}));
    }
}

export default new BlockingOverlayRegistry();
