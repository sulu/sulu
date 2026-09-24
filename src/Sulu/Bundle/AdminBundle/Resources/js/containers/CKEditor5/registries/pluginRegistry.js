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
     * A plugin registered without a key is added to every text editor config.
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
