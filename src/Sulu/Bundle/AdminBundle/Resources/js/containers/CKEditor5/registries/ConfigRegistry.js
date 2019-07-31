// @flow
import type {Config} from '../types';

class ConfigRegistry {
    configs: Array<Config>;

    constructor() {
        this.clear();
    }

    clear() {
        this.configs = [];
    }

    add(config: Config) {
        this.configs.push(config);
    }
}

export default new ConfigRegistry();
