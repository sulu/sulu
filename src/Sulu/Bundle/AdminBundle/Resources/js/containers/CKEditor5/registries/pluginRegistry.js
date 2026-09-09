// @flow
import {Plugin} from '@ckeditor/ckeditor5-core';
import {normalizeKeys} from '../utils';

type RegisteredPlugin = {|
    keys: ?Array<string>,
    plugin: Class<typeof Plugin>,
|};

class PluginRegistry {
    registeredPlugins: Array<RegisteredPlugin>;

    constructor() {
        this.clear();
    }

    clear() {
        this.registeredPlugins = [];
    }

    /**
     * Registers a plugin for the given tags or features. A plugin registered without any key is added to every
     * text editor config, a plugin registered with keys is only added if at least one of them is enabled.
     */
    add(plugin: Class<typeof Plugin>, keys: ?string | Array<string> = undefined) {
        this.registeredPlugins.push({keys: normalizeKeys(keys), plugin});
    }

    get keys(): Array<string> {
        return this.registeredPlugins.reduce((keys, registeredPlugin) => {
            return registeredPlugin.keys ? [...keys, ...registeredPlugin.keys] : keys;
        }, []);
    }

    getPlugins(enabledKeys: Array<string>): Array<Class<typeof Plugin>> {
        const plugins = this.registeredPlugins
            .filter(({keys}) => !keys || keys.some((key) => enabledKeys.includes(key)))
            .map(({plugin}) => plugin);

        return [...new Set(plugins)];
    }
}

export default new PluginRegistry();
