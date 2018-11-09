// @flow
import type {LinkTypeConfig, LinkTypeConfigs} from './types';

class LinkTypeConfigStore {
    config: LinkTypeConfigs = {};

    clear() {
        this.config = {};
    }

    setConfig(config: LinkTypeConfigs) {
        this.config = config;
    }

    getConfig(provider: string): ?LinkTypeConfig {
        const config = this.config[provider];

        return config || undefined;
    }

    getProviders(): string[] {
        return Object.keys(this.config || {});
    }
}

export default new LinkTypeConfigStore();
