// @flow
import {action, computed, observable, toJS} from 'mobx';
import type {StructureStrategyInterface, TreeItem} from '../types';

function mapVisibleData(item: TreeItem, expandedItems) {
    const clonedItem = toJS(item);
    if (expandedItems.includes(clonedItem.data.id)) {
        clonedItem.children = clonedItem.children.map((childItem) => mapVisibleData(childItem, expandedItems));
    } else {
        clonedItem.children.splice(0, clonedItem.children.length);
    }

    return clonedItem;
}

export default class TreeStructureStrategy implements StructureStrategyInterface {
    @observable rawData: Array<TreeItem> = [];
    @observable expandedItems: Array<?string | number> = [];

    @computed get data(): Array<TreeItem> {
        return this.rawData.map((item) => mapVisibleData(item, this.expandedItems));
    }

    findChildrenForParentId(tree: Array<TreeItem>, parent: ?string | number): ?Array<TreeItem> {
        for (let i = 0; i < tree.length; i++) {
            const item = tree[i];
            const {data, children} = item;
            if (parent === data.id) {
                return children;
            }

            const childResult = this.findChildrenForParentId(children, parent);
            if (childResult) {
                return childResult;
            }
        }
    }

    @action getData(parent: ?string | number) {
        if (parent === undefined) {
            return this.rawData;
        }

        return this.findChildrenForParentId(this.rawData, parent);
    }

    findById(id: string | number): ?Object {
        return this.findRecursive(this.rawData, id);
    }

    findRecursive(items: Array<Object>, identifier: string | number): ?Object {
        for (const item of items) {
            // TODO do not hardcode id but use metdata instead
            if (item.data.id === identifier) {
                return item.data;
            }

            const data = this.findRecursive(item.children, identifier);
            if (data) {
                return data;
            }
        }
    }

    activate(id: ?string | number) {
        if (this.expandedItems.includes(id)) {
            return;
        }

        this.expandedItems.push(id);
    }

    deactivate(id: ?string | number) {
        const {expandedItems} = this;
        expandedItems.splice(expandedItems.findIndex((item) => item === id), 1);
    }

    enhanceItem(item: Object): TreeItem {
        return {
            data: item,
            children: [],
        };
    }

    @action clear() {
        this.rawData.splice(0, this.rawData.length);
    }
}
