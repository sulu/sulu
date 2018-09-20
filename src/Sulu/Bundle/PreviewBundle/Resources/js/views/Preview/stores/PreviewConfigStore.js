// @flow
import {action, computed, observable} from 'mobx';
import queryString from 'query-string';

export type PreviewRouteName = 'start' | 'render' | 'update' | 'update-context' | 'stop';
export type PreviewMode = 'auto' | 'on_request' | 'off';

type PreviewConfigStoreConfig = {
    routes: { [PreviewRouteName]: string },
    debounceDelay: number,
    mode: PreviewMode,
};

class PreviewConfigStore {
    @observable config: PreviewConfigStoreConfig;

    @action setConfig(config: PreviewConfigStoreConfig) {
        this.config = config;
    }

    @computed get debounceDelay(): ?number {
        if (!this.config) {
            return null;
        }

        return this.config.debounceDelay;
    }

    @computed get mode(): ?PreviewMode {
        if (!this.config) {
            return null;
        }

        return this.config.mode;
    }

    generateRoute(name: PreviewRouteName, options: Object): ?string {
        if (!this.config) {
            return null;
        }

        return this.config.routes[name] + '?' + queryString.stringify(options);
    }
}

export default new PreviewConfigStore();
