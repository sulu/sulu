// @flow
import {computed} from 'mobx';
import type {TeaserProviderOptions} from '../types';

class TeaserProviderRegistry {
    teaserProviders: {[string]: TeaserProviderOptions};

    constructor() {
        this.clear();
    }

    clear() {
        this.teaserProviders = {};
    }

    @computed get keys(): Array<string> {
        return Object.keys(this.teaserProviders);
    }

    add(name: string, teaserProviderOption: TeaserProviderOptions) {
        if (name in this.teaserProviders) {
            throw new Error('The key "' + name + '" has already been used for another TeaserProvider');
        }

        this.teaserProviders[name] = teaserProviderOption;
    }

    get(name: string) {
        if (!(name in this.teaserProviders)) {
            throw new Error('There is no TeaserProvider with key "' + name + '" registered');
        }

        return this.teaserProviders[name];
    }
}

export default new TeaserProviderRegistry();
