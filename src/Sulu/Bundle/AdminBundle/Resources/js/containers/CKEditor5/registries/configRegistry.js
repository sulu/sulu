// @flow
import {normalizeKeys} from '../utils';
import type {Config} from '../types';

type RegisteredConfig = {|
    config: Config,
    keys: ?Array<string>,
    priority: number,
|};

class ConfigRegistry {
    registeredConfigs: Array<RegisteredConfig>;

    constructor() {
        this.clear();
    }

    clear() {
        this.registeredConfigs = [];
    }

    /**
     * A config registered without a key applies to every text editor config. Higher priority is applied first.
     */
    add(config: Config, keys: ?string | Array<string> = undefined, priority: number = 0) {
        this.registeredConfigs.push({config, keys: normalizeKeys(keys), priority});
    }

    get keys(): Array<string> {
        return this.registeredConfigs.reduce((keys, registeredConfig) => {
            return registeredConfig.keys ? [...keys, ...registeredConfig.keys] : keys;
        }, []);
    }

    getConfigs(enabledKeys: Array<string>): Array<Config> {
        return this.registeredConfigs
            .filter(({keys}) => !keys || keys.some((key) => enabledKeys.includes(key)))
            .sort((registeredConfig1, registeredConfig2) => registeredConfig2.priority - registeredConfig1.priority)
            .map(({config}) => config);
    }
}

export default new ConfigRegistry();
