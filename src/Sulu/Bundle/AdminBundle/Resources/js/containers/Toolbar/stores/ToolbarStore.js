// @flow
import type {Node} from 'react';
import {action, autorun, computed, observable} from 'mobx';
import log from 'loglevel';
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

    @computed get warnings(): Array<*> {
        if (!this.config.warnings) {
            return [];
        }

        return this.config.warnings;
    }

    @computed get showSuccess(): boolean {
        if (!this.config.showSuccess) {
            return false;
        }

        return this.config.showSuccess.get();
    }

    // @deprecated
    hasBackButtonConfig(): boolean {
        log.warn(
            'The "hasBackButtonConfig" method is deprecated since 2.1 and will be removed. ' +
            'Use the "getBackButtonConfig" method instead.'
        );
        return !!this.config.backButton;
    }

    getBackButtonConfig(): ?Button {
        return this.config.backButton || null;
    }

    // @deprecated
    hasItemsConfig(): boolean {
        log.warn(
            'The "hasItemsConfig" method is deprecated since 2.1 and will be removed. ' +
            'Use the "getItemsConfig" method instead.'
        );
        return !!this.config.items && !!this.config.items.length;
    }

    getItemsConfig(): Array<ToolbarItemConfig<*>> {
        return this.config.items || [];
    }

    // @deprecated
    hasIconsConfig(): boolean {
        log.warn(
            'The "hasiconsConfig" method is deprecated since 2.1 and will be removed. ' +
            'Use the "getIconsConfig" method instead.'
        );
        return !!this.config.icons && !!this.config.icons.length;
    }

    getIconsConfig(): Array<Node> {
        return this.config.icons || [];
    }

    // @deprecated
    hasLocaleConfig(): boolean {
        log.warn(
            'The "hasLocaleConfig" method is deprecated since 2.1 and will be removed. ' +
            'Use the "getLocaleConfig" method instead.'
        );
        return !!this.config.locale;
    }

    getLocaleConfig(): ?Select<string> {
        return this.config.locale;
    }
}
