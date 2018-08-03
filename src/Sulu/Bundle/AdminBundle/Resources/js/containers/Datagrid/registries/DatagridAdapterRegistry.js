// @flow
import AbstractAdapter from '../adapters/AbstractAdapter';

class DatagridAdapterRegistry {
    adapters: {[string]: typeof AbstractAdapter};
    options: {[string]: Object};

    constructor() {
        this.clear();
    }

    clear() {
        this.adapters = {};
        this.options = {};
    }

    has(name: string) {
        return !!this.adapters[name];
    }

    add(name: string, Adapter: typeof AbstractAdapter, options: Object = {}) {
        if (name in this.adapters) {
            throw new Error('The key "' + name + '" has already been used for another datagrid adapter');
        }

        this.adapters[name] = Adapter;
        this.options[name] = options;
    }

    get(name: string): typeof AbstractAdapter {
        if (!(name in this.adapters)) {
            throw new Error(
                'The datagrid adapter with the key "' + name + '" is not defined. ' +
                'You probably forgot to add it to the store using the "add" method.'
            );
        }

        return this.adapters[name];
    }

    getOptions(name: string) {
        if (!(name in this.options)) {
            throw new Error('There are no options for a datagrid adapter with the key "' + name + '" registered');
        }

        return this.options[name];
    }
}

export default new DatagridAdapterRegistry();
