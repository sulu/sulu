// @flow
import type {Node} from 'react';
import {action, autorun, computed, observable} from 'mobx';
import type {Button, Select, ToolbarConfig, ToolbarItemConfig} from '../types';

const SHOW_SUCCESS_DURATION = 1500;

export default class ToolbarStore {
    @observable config: ToolbarConfig = {};
    showSuccessDisposer: () => void;

    constructor() {
        this.showSuccessDisposer = autorun(() => {
            const {showSuccess} = this.config;
            if (showSuccess && showSuccess.get()) {
                setTimeout(action(() => {
                    showSuccess.set(false);
                }), SHOW_SUCCESS_DURATION);
            }
        });
    }

    destroy() {
        this.clearConfig();
        this.showSuccessDisposer();
    }

    @action setConfig(config: ToolbarConfig) {
        this.config = config;
    }

    @action clearConfig() {
        this.config = {};
    }

    @computed get disableAll(): boolean {
        return !!this.config.disableAll;
    }

    @computed get errors(): Array<*> {
        if (!this.config.errors) {
            return [];
        }

        return this.config.errors;
    }

    @computed get showSuccess(): boolean {
        if (!this.config.showSuccess) {
            return false;
        }

        return this.config.showSuccess.get();
    }

    hasBackButtonConfig(): boolean {
        return !!this.config.backButton;
    }

    getBackButtonConfig(): ?Button {
        return this.config.backButton || null;
    }

    hasItemsConfig(): boolean {
        return !!this.config.items && !!this.config.items.length;
    }

    getItemsConfig(): Array<ToolbarItemConfig> {
        return this.config.items || [];
    }

    hasIconsConfig(): boolean {
        return !!this.config.icons && !!this.config.icons.length;
    }

    getIconsConfig(): Array<Node> {
        return this.config.icons || [];
    }

    hasLocaleConfig(): boolean {
        return !!this.config.locale;
    }

    getLocaleConfig(): ?Select {
        return this.config.locale;
    }
}
