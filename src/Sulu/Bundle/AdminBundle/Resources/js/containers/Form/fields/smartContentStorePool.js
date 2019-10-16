// @flow
import {action, observable, when} from 'mobx';
import {SmartContentStore} from '../../SmartContent';

class SmartContentStorePool {
    @observable stores: Array<SmartContentStore>;

    constructor() {
        this.clear();
    }

    clear() {
        this.stores = [];
    }

    @action add(store: SmartContentStore) {
        if (this.stores.includes(store)) {
            throw new Error('Cannot add a SmartContentStore twice!');
        }

        this.stores.push(store);
    }

    @action remove(store: SmartContentStore) {
        this.stores.splice(this.stores.indexOf(store), 1);
    }

    updateExcludedIds = () => {
        this.updateRecursiveExcludedIds(this.stores);
    };

    updateRecursiveExcludedIds = (stores: Array<SmartContentStore>) => {
        if (stores.length === 0) {
            return;
        }

        const store = stores[0];
        const previousStores = [];
        for (const otherStore of this.stores) {
            if (otherStore === store) {
                break;
            }

            previousStores.push(otherStore);
        }

        if (previousStores.length === 0) {
            this.updateRecursiveExcludedIds(stores.slice(1));
            return;
        }

        when(
            () => previousStores.every((store) => !store.itemsLoading),
            (): void => {
                const excludedIds = previousStores
                    .reduce((ids, smartContentStore) => {
                        ids.push(...smartContentStore.items.map((item) => item.id));
                        return ids;
                    }, []);

                store.setExcludedIds(excludedIds);

                this.updateRecursiveExcludedIds(stores.slice(1));
            }
        );
    };
}

export default new SmartContentStorePool();
