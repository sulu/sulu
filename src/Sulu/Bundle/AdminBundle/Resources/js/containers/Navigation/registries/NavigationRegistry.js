// @flow
import type {NavigationItem} from '../types';

function findById(navigationItems: Array<NavigationItem>, id: string): ?NavigationItem {
    for (const navigationItem of navigationItems) {
        if (id === navigationItem.id) {
            return navigationItem;
        }

        if (navigationItem.items) {
            const foundNavigationItem = findById(navigationItem.items, id);

            if (foundNavigationItem) {
                return foundNavigationItem;
            }
        }
    }
}

class NavigationRegistry {
    navigationItems: Array<NavigationItem>;

    constructor() {
        this.clear();
    }

    clear() {
        this.navigationItems = [];
    }

    set(navigationItems: Array<NavigationItem>) {
        this.navigationItems = navigationItems;
    }

    get(id: string): NavigationItem {
        const navigationItem = findById(this.navigationItems, id);

        if (!navigationItem) {
            throw new Error('Navigation item with id "' + id + '" not found.');
        }

        return navigationItem;
    }

    getAll(): Array<NavigationItem> {
        return this.navigationItems;
    }
}

export default new NavigationRegistry();
