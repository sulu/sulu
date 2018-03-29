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

    set(navigationItems: Array<NavigationItem>) {
        this.navigationItems = navigationItems;
    }

    get(id: string): NavigationItem {
        const navigationItem = this.findById(this.navigationItems, id);

        if (!navigationItem) {
            throw new Error('Navigation item with id "' + id + '" not found.');
        }

        return navigationItem;
    }

    getAll(): Array<NavigationItem> {
        return this.navigationItems;
    }

    findById(navigationItems: Array<NavigationItem>, id: string): ?NavigationItem {
        for (const navigationItem of navigationItems) {
            if (id === navigationItem.id) {
                return navigationItem;
            }

            if (navigationItem.items) {
                const foundNavigationItem = this.findById(navigationItem.items, id);

                if (foundNavigationItem) {
                    return foundNavigationItem;
                }
            }
        }
    }
}

export default new NavigationRegistry();
