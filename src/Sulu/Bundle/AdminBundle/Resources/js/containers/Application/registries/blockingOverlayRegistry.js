// @flow
import type {ComponentType} from 'react';

type Entry = {
    isBlocking: () => boolean,
    overlay: ComponentType<*>,
};

// Overlays a bundle renders on top of the whole application, e.g. to make the user complete a
// mandatory step before anything else. Every registered overlay decides on its own whether it is
// currently open and whether it blocks the rest of the application, because only the bundle
// owning it knows the condition. "isBlocking" is called from Application's render, so it must
// read the observables it depends on synchronously to stay reactive.
class BlockingOverlayRegistry {
    overlays: {[string]: Entry};

    constructor() {
        this.clear();
    }

    clear() {
        this.overlays = {};
    }

    has(name: string) {
        return !!this.overlays[name];
    }

    add(name: string, overlay: ComponentType<*>, isBlocking: () => boolean = () => false) {
        if (name in this.overlays) {
            throw new Error('The key "' + name + '" has already been used for another blocking overlay');
        }

        this.overlays[name] = {isBlocking, overlay};
    }

    getAll(): Array<{name: string, overlay: ComponentType<*>}> {
        return Object.keys(this.overlays).map((name) => ({name, overlay: this.overlays[name].overlay}));
    }

    isAnyBlocking(): boolean {
        return Object.keys(this.overlays).some((name) => this.overlays[name].isBlocking());
    }
}

export default new BlockingOverlayRegistry();
