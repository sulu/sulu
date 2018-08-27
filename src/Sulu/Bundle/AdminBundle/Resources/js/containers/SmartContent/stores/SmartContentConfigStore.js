// @flow
import type {SmartContentConfigs} from '../types';

class SmartContentConfigStore {
    config: SmartContentConfigs;

    clear() {
        this.config = {};
    }

    setConfig(config: SmartContentConfigs) {
        this.config = config;
    }

    getConfig(provider: string) {
        return this.config[provider];
    }
}

export default new SmartContentConfigStore();
