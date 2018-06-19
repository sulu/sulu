// @flow
import {action, computed, observable} from 'mobx';
import type {StructureStrategyInterface} from '../types';

export default class ColumnStructureStrategy implements StructureStrategyInterface {
    @observable rawData: Map<?string | number, Array<Object>> = new Map();

    @computed get visibleItems(): Array<Object> {
        return this.data.reduce((data, items) => data.concat(...items), []);
    }

    @computed get activeItems(): Array<?string | number> {
        return Array.from(this.rawData.keys());
    }

    @computed get data(): Array<Array<Object>> {
        return Array.from(this.rawData.values());
    }

    constructor() {
        this.rawData.set(undefined, []);
    }

    activate(id: ?string | number) {
        const parents = this.activeItems;
        const columnIndex = this.data.findIndex((column) => column.findIndex((item) => item.id === id) !== -1);
        parents.filter((parent, index) => index > columnIndex).forEach((parent) => this.rawData.delete(parent));
        this.rawData.set(id, []);
    }

    @action getData(parent: ?string | number) {
        return this.rawData.get(parent);
    }

    remove(identifier: string | number) {
        for (const column of this.rawData.values()) {
            for (const index of column.keys()) {
                // TODO do not hardcode id but use metdata instead
                if (column[index].id === identifier) {
                    column.splice(index, 1);
                }
            }
        }
    }

    findById(identifier: string | number): ?Object {
        for (const column of this.data) {
            for (const item of column) {
                // TODO do not hardcode id but use metdata instead
                if (item.id === identifier) {
                    return item;
                }
            }
        }
    }

    @action clear() {
        this.rawData.clear();
        this.rawData.set(undefined, []);
    }

    enhanceItem(item: Object): Object {
        return item;
    }
}
