// @flow
import type {ToolbarConfig} from '../types';
import ToolbarStore from './ToolbarStore';

const DEFAULT_STORE_KEY = 'default';

class ToolbarStorePool {
    stores = {};

    createStore = (key: string = DEFAULT_STORE_KEY) => {
        const toolbarStore = new ToolbarStore();

        this.stores[key] = toolbarStore;

        return toolbarStore;
    };

    hasStore = (key: string) => {
        return !!this.stores[key];
    };

    getStore = (key: string) => {
        if (this.hasStore(key)) {
            return this.stores[key];
        } else {
            throw new Error(`
                Store with the key '${key}' not found! Calling 'withToolbar' before 
                initializing the 'Toolbar' component can be a cause for this error.
            `);
        }
    };

    setToolbarConfig = (key: string = DEFAULT_STORE_KEY, config: ToolbarConfig = {}) => {
        const toolbar = this.getStore(key);

        toolbar.setConfig(config);
    };
}

export default new ToolbarStorePool();
