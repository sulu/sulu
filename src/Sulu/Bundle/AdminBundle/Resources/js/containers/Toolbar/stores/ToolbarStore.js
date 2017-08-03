// @flow
import type {ToolbarConfig} from '../types';
import {action, observable} from 'mobx';

const defaultConfig = {
    buttons: [],
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

    getButtonsConfig() {
        return this.config.buttons;
    }
}

export default new ToolbarStore();
