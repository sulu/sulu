// @flow
import type {Navigation} from '../types';

class NavigationRegistry {
    navigation: Array<Navigation>;

    constructor() {
        this.clear();
    }

    clear() {
        this.navigation = [];
    }

    set(navigation: Array<Navigation>) {
        this.navigation = navigation;
    }

    get(): Array<Navigation> {
        return this.navigation;
    }
}

export default new NavigationRegistry();
