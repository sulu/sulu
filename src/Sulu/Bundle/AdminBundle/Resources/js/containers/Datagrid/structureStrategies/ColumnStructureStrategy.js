// @flow
import {action, computed, observable} from 'mobx';
import type {ColumnItem, StructureStrategyInterface} from '../types';

function removeColumnsAfterIndex(parents, columnIndex, rawData) {
    parents.filter((parent, index) => index > columnIndex).forEach((parent) => rawData.delete(parent));
}

export default class ColumnStructureStrategy implements StructureStrategyInterface {
    @observable rawData: Map<?string | number, Array<ColumnItem>> = new Map();

    @computed get visibleItems(): Array<ColumnItem> {
        return this.data.reduce((data, items) => data.concat(...items), []);
    }

    @computed get activeItems(): Array<?string | number> {
        return Array.from(this.rawData.keys());
    }

    @computed get data(): Array<Array<ColumnItem>> {
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
        for (const columnIndex of this.activeItems.keys()) {
            const columnParentId = this.activeItems[columnIndex];
            if (!columnParentId) {
                continue;
            }

            const column = this.rawData.get(columnParentId);
            if (!column) {
                continue;
            }

            for (const index of column.keys()) {
                // TODO do not hardcode id but use metadata instead
                const id = column[index].id;
                if (id === identifier) {
                    if (this.activeItems.includes(id)) {
                        removeColumnsAfterIndex(this.activeItems, columnIndex, this.rawData);
                    }
                    column.splice(index, 1);

                    if (column.length === 0) {
                        const columnParent = this.findById(columnParentId);
                        if (columnParent) {
                            columnParent.hasChildren = false;
                        }
                    }
                }
            }
        }
    }

    findById(identifier: string | number): ?ColumnItem {
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

    enhanceItem(item: ColumnItem): ColumnItem {
        return item;
    }
}
