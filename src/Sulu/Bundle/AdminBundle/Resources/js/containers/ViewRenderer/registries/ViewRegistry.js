// @flow
import type {View} from '../types';

class ViewRegistry {
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
        if (name in this.views) {
            return this.views[name];
        }

        throw new Error('There is not view for the key "' + name + '" registered');
    }
}

export default new ViewRegistry();
