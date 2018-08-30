// @flow
import {action, computed, observable} from 'mobx';
import type {ColumnItem, StructureStrategyInterface} from '../types';

function removeColumnsAfterIndex(parentIds, columnIndex: number, rawData) {
    parentIds.filter((parentId, index) => index > columnIndex).forEach((parentId) => rawData.delete(parentId));
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

    @action clear(parentId: ?string | number) {
        if (!parentId) {
            this.rawData.clear();
            this.rawData.set(parentId, []);
        }

        removeColumnsAfterIndex(this.activeItems, this.activeItems.indexOf(parentId), this.rawData);
        const column = this.rawData.get(parentId);
        if (column && column.length > 0) {
            column.splice(0, column.length);
        }
    }

    addItem(item: Object, parentId: ?string | number) {
        let column = this.rawData.get(parentId);
        if (!column) {
            column = [];
            this.rawData.set(parentId, column);
        }

        column.push(item);

        if (!item._embedded) {
            return;
        }

        const resourceKey = Object.keys(item._embedded)[0];
        const childItems = item._embedded[resourceKey];

        if (Array.isArray(childItems) && !this.rawData.has(item.id)) {
            this.rawData.set(item.id, []);
            childItems.forEach((childItem) => {
                this.addItem(childItem, item.id);
            });
        }
    }
}
