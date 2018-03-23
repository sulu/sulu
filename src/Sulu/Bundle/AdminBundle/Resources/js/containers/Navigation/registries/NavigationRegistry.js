// @flow
import type {NavigationItem} from '../types';

class NavigationRegistry {
    navigationItems: Array<NavigationItem>;

    constructor() {
        this.clear();
    }

    clear() {
        this.navigationItems = [];
    }

    set(navigation: Array<NavigationItem>) {
        this.navigationItems = navigation;
    }

    get(): Array<NavigationItem> {
        return this.navigationItems;
    }
}

export default new NavigationRegistry();
