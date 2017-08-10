// @flow
import type {ToolbarConfig} from '../types';
import {action, observable} from 'mobx';

const defaultConfig = {
    icons: [],
    locale: null,
    items: [],
    backButton: null,
};

class ToolbarStore {
    @observable config = defaultConfig;

    @action setConfig(config: ToolbarConfig) {
        this.clearConfig();
        this.config = {...defaultConfig, ...config};
    }

    @action clearConfig() {
        this.config = defaultConfig;
    }

    hasBackButtonConfig(): boolean {
        return !!this.config.backButton;
    }

    getBackButtonConfig() {
        return this.config.backButton || null;
    }

    hasItemsConfig(): boolean {
        return !!this.config.items && !!this.config.items.length;
    }

    getItemsConfig() {
        return this.config.items || [];
    }

    hasIconsConfig(): boolean {
        return !!this.config.icons && !!this.config.icons.length;
    }

    getIconsConfig() {
        return this.config.icons || [];
    }

    hasLocaleConfig(): boolean {
        return !!this.config.locale;
    }

    getLocaleConfig() {
        return this.config.locale;
    }
}

export default new ToolbarStore();
