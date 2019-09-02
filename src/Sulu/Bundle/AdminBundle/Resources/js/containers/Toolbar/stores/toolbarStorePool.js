// @flow
import type {ToolbarConfig} from '../types';
import ToolbarStore from './ToolbarStore';

export const DEFAULT_STORE_KEY = 'default';

class ToolbarStorePool {
    stores = {};

    createStore = (key: string) => {
        if (this.hasStore(key)) {
            throw new Error('The store with the key "' + key + '" already exists.');
        }

        const toolbarStore = new ToolbarStore();

        this.stores[key] = toolbarStore;

        return toolbarStore;
    };

    destroyStore = (key: string) => {
        if (!this.hasStore(key)) {
            throw new Error(
                'The store you want to destroy with the key "' + key + '" does not exist!'
            );
        }

        this.stores[key].destroy();
        this.stores[key] = null;
    };

    hasStore = (key: string) => {
        return !!this.stores[key];
    };

    getStore = (key: string) => {
        if (!this.hasStore(key)) {
            throw new Error(
                'Store with the key "' + key + '" not found! Calling "withToolbar" before ' +
                'initializing the "Toolbar" component can be a cause for this error.'
            );
        }

        return this.stores[key];
    };

    setToolbarConfig = (key: string, config: ToolbarConfig) => {
        const toolbar = this.getStore(key);

        toolbar.setConfig(config);
    };
}

export default new ToolbarStorePool();
