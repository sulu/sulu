// @flow
import {Plugin} from '@ckeditor/ckeditor5-core';

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
