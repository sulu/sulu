// @flow
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

class PluginRegistry {
    plugins: Array<Class<Plugin>>;

    constructor() {
        this.clear();
    }

    clear() {
        this.plugins = [];
    }

    add(plugin: Class<Plugin>) {
        this.plugins.push(plugin);
    }
}

export default new PluginRegistry();
