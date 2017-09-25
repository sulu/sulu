// @flow
import type {Adapter} from '../types';

class AdapterStore {
    adapters: {[string]: Adapter};

    constructor() {
        this.clear();
    }

    clear() {
        this.adapters = {};
    }

    add(name: string, adapter: Adapter) {
        if (name in this.adapters) {
            throw new Error('The key "' + name + '" has already been used for another adapter');
        }

        this.adapters[name] = adapter;
    }

    get(name: string): Adapter {
        if (!(name in this.adapters)) {
            throw new Error(
                'The adapter with the key "' + name + '" is not defined. ' +
                'You probably forgot to add it to the store using the "add" method.'
            );
        }

        return this.adapters[name];
    }
}

export default new AdapterStore();
