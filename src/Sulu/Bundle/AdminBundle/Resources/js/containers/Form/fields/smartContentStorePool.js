// @flow
import {action, computed, observable, when} from 'mobx';
import {SmartContentStore} from '../../SmartContent';

class SmartContentStorePool {
    @observable entries: Array<{|excludeDuplicates: boolean, store: SmartContentStore|}>;

    @computed get stores(): Array<SmartContentStore> {
        return this.entries.map((entry) => entry.store);
    }

    constructor() {
        this.clear();
    }

    clear() {
        this.entries = [];
    }

    @action add(store: SmartContentStore, excludeDuplicates: boolean) {
        if (this.stores.includes(store)) {
            throw new Error('Cannot add a SmartContentStore twice!');
        }

        this.entries.push({store, excludeDuplicates});
    }

    @action remove(store: SmartContentStore) {
        this.entries.splice(this.stores.indexOf(store), 1);
    }

    findEntryByStore(store: SmartContentStore) {
        return this.entries.find((entry) => entry.store === store);
    }

    updateExcludedIds = () => {
        this.updateRecursiveExcludedIds(this.stores);
    };

    findPreviousStores(store: SmartContentStore) {
        const previousStores = [];
        for (const otherStore of this.stores) {
            if (otherStore === store) {
                break;
            }

            if (otherStore.provider !== store.provider) {
                continue;
            }

            previousStores.push(otherStore);
        }

        return previousStores;
    }

    updateRecursiveExcludedIds = (stores: Array<SmartContentStore>) => {
        if (stores.length === 0) {
            return;
        }

        const store = stores[0];
        const entry = this.findEntryByStore(store);

        if (!entry) {
            throw new Error('There was no entry found for the store! This should not happen and is likely a bug.');
        }

        if (!entry.excludeDuplicates) {
            this.updateRecursiveExcludedIds(stores.slice(1));
            return;
        }

        const previousStores = this.findPreviousStores(store);

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
