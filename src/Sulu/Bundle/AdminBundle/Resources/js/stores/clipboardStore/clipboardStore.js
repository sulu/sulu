// @flow
import type {Observer} from './types';

class ClipboardStore {
    observers: {[key: string]: Array<Observer>} = {};
    storageEventListener: ?(event: StorageEvent) => void;

    updateStorageEventListener(): void {
        const activeObservers = Object.values(this.observers).flat().length;

        // listen for "storage" events to notify observers if something is copied in another browser window
        if (activeObservers > 0 && !this.storageEventListener) {
            this.storageEventListener = (event: StorageEvent) => {
                if (event.key) {
                    this.notifyObservers(event.key, event.newValue ? JSON.parse(event.newValue) : undefined);
                }
            };
            window.addEventListener('storage', this.storageEventListener);
        } else if (activeObservers === 0 && this.storageEventListener) {
            window.removeEventListener('storage', this.storageEventListener);
        }
    }

    notifyObservers(key: string, value: mixed): void {
        const observers = this.observers[key] || [];

        for (const observer of observers) {
            observer(value);
        }
    }

    get(key: string): mixed {
        const serializedValue = window.localStorage.getItem(key);

        return serializedValue ? JSON.parse(serializedValue) : undefined;
    }

    set(key: string, value: mixed) {
        if (value) {
            window.localStorage.setItem(key, JSON.stringify(value));
        } else {
            window.localStorage.removeItem(key);
        }

        this.notifyObservers(key, value);
    }

    observe(key: string, observer: Observer) {
        if (!this.observers[key]) {
            this.observers[key] = [];
        }
        this.observers[key].push(observer);
        this.updateStorageEventListener();

        // return disposer function that allows to remove the registered observer
        return () => {
            const index = this.observers[key]?.indexOf(observer);
            if (index > -1) {
                this.observers[key].splice(index, 1);
            }
            this.updateStorageEventListener();
        };
    }
}

export default new ClipboardStore();
