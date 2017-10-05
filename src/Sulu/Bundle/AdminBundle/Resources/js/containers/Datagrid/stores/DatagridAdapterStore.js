// @flow
import type {DatagridAdapter} from '../types';

class DatagridAdapterStore {
    adapters: {[string]: DatagridAdapter};

    constructor() {
        this.clear();
    }

    clear() {
        this.adapters = {};
    }

    has(name: string) {
        return !!this.adapters[name];
    }

    add(name: string, adapter: DatagridAdapter) {
        if (name in this.adapters) {
            throw new Error('The key "' + name + '" has already been used for another adapter');
        }

        this.adapters[name] = adapter;
    }

    get(name: string): DatagridAdapter {
        if (!(name in this.adapters)) {
            throw new Error(
                'The adapter with the key "' + name + '" is not defined. ' +
                'You probably forgot to add it to the store using the "add" method.'
            );
        }

        return this.adapters[name];
    }
}

export default new DatagridAdapterStore();
