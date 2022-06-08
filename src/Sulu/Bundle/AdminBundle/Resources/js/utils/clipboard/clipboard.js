// @flow
import type {Observer} from './types';

class Clipboard {
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

    set(key: string, value: mixed) {
        if (value) {
            window.localStorage.setItem(key, JSON.stringify(value));
        } else {
            window.localStorage.removeItem(key);
        }

        this.notifyObservers(key, value);
    }

    observe(key: string, observer: Observer, invokeImmediately?: boolean) {
        if (!this.observers[key]) {
            this.observers[key] = [];
        }
        this.observers[key].push(observer);
        this.updateStorageEventListener();

        if (invokeImmediately) {
            const currentValue = window.localStorage.getItem(key);
            observer(currentValue ? JSON.parse(currentValue) : undefined);
        }

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

export default new Clipboard();
