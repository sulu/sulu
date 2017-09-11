// @flow
import type {View} from '../types';

class ViewStore {
    views: {[string]: View};

    constructor() {
        this.clear();
    }

    clear() {
        this.views = {};
    }

    add(name: string, view: View) {
        if (name in this.views) {
            throw new Error('The key "' + name + '" has already been used for another view');
        }

        this.views[name] = view;
    }

    get(name: string): View {
        return this.views[name];
    }
}

export default new ViewStore();
