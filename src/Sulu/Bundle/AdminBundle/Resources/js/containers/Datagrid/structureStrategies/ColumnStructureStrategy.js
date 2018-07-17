// @flow
import {action, computed, observable} from 'mobx';
import type {StructureStrategyInterface} from '../types';

function removeColumnsAfterIndex(parents, columnIndex, rawData) {
    parents.filter((parent, index) => index > columnIndex).forEach((parent) => rawData.delete(parent));
}

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
        const columnIndex = this.data.findIndex((column) => column.findIndex((item) => item.id === id) !== -1);
        removeColumnsAfterIndex(this.activeItems, columnIndex, this.rawData);
        this.rawData.set(id, []);
    }

    @action getData(parent: ?string | number) {
        return this.rawData.get(parent);
    }

    @action remove(identifier: string | number) {
        for (const column of this.rawData.values()) {
            for (const index of column.keys()) {
                // TODO do not hardcode id but use metadata instead
                const id = column[index].id;
                if (id === identifier) {
                    if (this.activeItems.includes(id)) {
                        removeColumnsAfterIndex(this.activeItems, index, this.rawData);
                    }
                    column.splice(index, 1);
                }
            }
        }
    }

    findById(identifier: string | number): ?Object {
        for (const column of this.data) {
            for (const item of column) {
                // TODO do not hardcode id but use metadata instead
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
