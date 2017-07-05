// @flow
import {action, observable} from 'mobx';
import type {Item} from '../types';

class ToolbarStore {
    @observable items: Array<Item> = [];

    @action setItems(items: Array<Item>) {
        this.clearItems();
        this.items.push(...items);
    }

    @action clearItems() {
        this.items.length = 0;
    }
}

export default new ToolbarStore();
