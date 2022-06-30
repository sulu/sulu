// @flow
import type {View, ViewConfig} from '../types';

class ViewRegistry {
    views: {[string]: View};
    viewConfigs: {[string]: ViewConfig};

    constructor() {
        this.clear();
    }

    clear() {
        this.views = {};
        this.viewConfigs = {};
    }

    add(name: string, view: View, viewConfig?: ViewConfig) {
        if (name in this.views) {
            throw new Error('The key "' + name + '" has already been used for another view');
        }

        this.views[name] = view;
        this.viewConfigs[name] = viewConfig ? viewConfig : {};
    }

    get(name: string): View {
        if (name in this.views) {
            return this.views[name];
        }

        throw new Error('There is not view for the key "' + name + '" registered');
    }

    getConfig(name: string): ViewConfig {
        if (name in this.viewConfigs) {
            return this.viewConfigs[name];
        }

        throw new Error('There is not view config for the key "' + name + '" registered');
    }
}

export default new ViewRegistry();
