// @flow
import type {Plugin} from 'ckeditor5';

class PluginRegistry {
    plugins: Array<Class<typeof Plugin>>;

    constructor() {
        this.clear();
    }

    clear() {
        this.plugins = [];
    }

    add(plugin: Class<typeof Plugin>) {
        this.plugins.push(plugin);
    }
}

export default new PluginRegistry();
