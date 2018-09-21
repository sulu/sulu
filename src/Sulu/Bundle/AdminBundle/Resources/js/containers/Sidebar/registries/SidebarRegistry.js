// @flow
import type {ComponentType} from 'react';

class SidebarRegistry {
    views: {[string]: ComponentType<*>};
    disabled = [];

    constructor() {
        this.clear();
    }

    clear() {
        this.views = {};
    }

    has(name: string) {
        return !!this.views[name];
    }

    add(name: string, adapter: ComponentType<*>) {
        if (name in this.views) {
            throw new Error('The key "' + name + '" has already been used for another sidebar component');
        }

        this.views[name] = adapter;
    }

    get(name: string): ComponentType<*> {
        if (!(name in this.views)) {
            throw new Error(
                'The sidebar component with the key "' + name + '" is not defined. ' +
                'You probably forgot to add it to the store using the "add" method.'
            );
        }

        return this.views[name];
    }

    disable(name: string): void {
        this.disabled.push(name);
    }

    isDisabled(name: string): boolean {
        return this.disabled.indexOf(name) > -1;
    }
}

export default new SidebarRegistry();
