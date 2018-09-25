// @flow
import {action, computed, observable} from 'mobx';
import type {SidebarConfig, Size} from '../types';

const DEFAULT_SIZE: Size = 'medium';
const SIZES: Array<Size> = ['small', 'medium', 'large'];

class SidebarStore {
    @observable view: ?string;
    @observable props: Object;
    sizes: Array<Size>;

    @observable size: ?Size;

    constructor() {
        this.clearConfig();
    }

    @action setConfig(config: SidebarConfig) {
        this.view = config.view;
        this.props = config.props || {};
        this.sizes = config.sizes || SIZES;

        if (!this.size || !this.sizes.includes(this.size)) {
            this.setSize(config.defaultSize || DEFAULT_SIZE);
        }
    }

    @action clearConfig() {
        this.view = undefined;
        this.props = {};
        this.sizes = SIZES;
        this.size = null;
    }

    @computed get enabled(): boolean {
        return !!this.view;
    }

    @action setSize(size: Size) {
        if (!this.sizes.includes(size)) {
            throw new Error(
                'Size "' + size + '" is not supported by view. Supported: ["' + this.sizes.join('", "') + '"]'
            );
        }

        this.size = size;
    }
}

export default new SidebarStore();

export {DEFAULT_SIZE, SIZES};
