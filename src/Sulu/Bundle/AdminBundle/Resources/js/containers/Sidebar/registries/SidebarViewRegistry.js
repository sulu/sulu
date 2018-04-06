// @flow
import type {SidebarView} from '../types';

class SidebarViewRegistry {
    views: {[string]: SidebarView};

    constructor() {
        this.clear();
    }

    clear() {
        this.views = {};
    }

    has(name: string) {
        return !!this.views[name];
    }

    add(name: string, adapter: SidebarView) {
        if (name in this.views) {
            throw new Error('The key "' + name + '" has already been used for another sidebar view');
        }

        this.views[name] = adapter;
    }

    get(name: string): SidebarView {
        if (!(name in this.views)) {
            throw new Error(
                'The sidebar view with the key "' + name + '" is not defined. ' +
                'You probably forgot to add it to the store using the "add" method.'
            );
        }

        return this.views[name];
    }
}

export default new SidebarViewRegistry();
