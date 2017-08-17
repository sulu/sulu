// @flow
import type {ComponentType} from 'react';

class ViewStore {
    views: {[string]: ComponentType<*>};

    constructor() {
        this.clear();
    }

    clear() {
        this.views = {};
    }

    add(name: string, view: ComponentType<*>) {
        if (name in this.views) {
            throw new Error('The key "' + name + '" has already been used for another view');
        }

        this.views[name] = view;
    }

    get(name: string): ComponentType<*> {
        return this.views[name];
    }
}

export default new ViewStore();
