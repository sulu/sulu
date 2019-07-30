// @flow
import type {ConfigHook} from '../types';

class ConfigHookRegistry {
    configHooks: Array<ConfigHook>;

    constructor() {
        this.clear();
    }

    clear() {
        this.configHooks = [];
    }

    add(configHook: ConfigHook) {
        this.configHooks.push(configHook);
    }
}

export default new ConfigHookRegistry();
