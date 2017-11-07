// @flow
import type {
    AdapterConfigs,
    AdapterConfig,
    DatagridAdapter,
    PaginationType,
} from '../types';

class DatagridAdapterRegistry {
    adapters: AdapterConfigs;

    constructor() {
        this.clear();
    }

    clear() {
        this.adapters = {};
    }

    has(name: string) {
        return !!this.adapters[name];
    }

    add(name: string, Adapter: DatagridAdapter, paginationType: PaginationType = 'pagination') {
        if (name in this.adapters) {
            throw new Error('The key "' + name + '" has already been used for another datagrid adapter');
        }

        this.adapters[name] = {
            Adapter,
            paginationType,
        };
    }

    get(name: string): AdapterConfig {
        if (!(name in this.adapters)) {
            throw new Error(
                'The datagrid adapter with the key "' + name + '" is not defined. ' +
                'You probably forgot to add it to the store using the "add" method.'
            );
        }

        return this.adapters[name];
    }
}

export default new DatagridAdapterRegistry();
