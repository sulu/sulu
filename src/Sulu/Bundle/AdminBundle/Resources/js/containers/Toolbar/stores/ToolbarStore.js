// @flow
import type {BackButtonType, DefaultButtonType, DropdownButtonType, ToolbarConfig} from '../types';
import {action, observable} from 'mobx';

const defaultConfig = {
    icons: [],
    locale: null,
    buttons: [],
    backButton: null,
};

class ToolbarStore {
    @observable config: ToolbarConfig = defaultConfig;

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

    getBackButtonConfig(): ?BackButtonType {
        return this.config.backButton || null;
    }

    hasButtonsConfig(): boolean {
        return !!this.config.buttons && !!this.config.buttons.length;
    }

    getButtonsConfig(): Array<DefaultButtonType | DropdownButtonType> {
        return this.config.buttons || [];
    }

    hasIconsConfig(): boolean {
        return !!this.config.icons && !!this.config.icons.length;
    }

    getIconsConfig(): Array<string> {
        return this.config.icons || [];
    }

    hasLocaleConfig(): boolean {
        return !!this.config.locale;
    }

    getLocaleConfig(): ?DropdownButtonType {
        return this.config.locale;
    }
}

export default new ToolbarStore();
