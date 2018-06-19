// @flow
import {action, computed, observable} from 'mobx';
import type {StructureStrategyInterface, TreeItem} from '../types';

function flattenData(items: Array<TreeItem>, data: Array<Object> = []) {
    data.push(...items.map((item) => item.data));

    for (const item of items) {
        flattenData(item.children, data);
    }

    return data;
}

function findRecursive(items: Array<Object>, identifier: string | number): ?Object {
    for (const item of items) {
        // TODO do not hardcode id but use metdata instead
        if (item.data.id === identifier) {
            return item.data;
        }

        const data = findRecursive(item.children, identifier);
        if (data) {
            return data;
        }
    }
}

function removeRecursive(items: Array<TreeItem>, identifier: string | number): boolean {
    for (const index of items.keys()) {
        const item = items[index];
        if (item.data.id === identifier) {
            items.splice(index, 1);
            return true;
        }

        const removed = removeRecursive(item.children, identifier);

        if (removed && item.children.length === 0) {
            item.hasChildren = false;
            return true;
        }
    }

    return false;
}

function findChildrenForParentId(tree: Array<TreeItem>, parent: ?string | number): ?Array<TreeItem> {
    for (let i = 0; i < tree.length; i++) {
        const item = tree[i];
        const {data, children} = item;
        if (parent === data.id) {
            return children;
        }

        const childResult = findChildrenForParentId(children, parent);
        if (childResult) {
            return childResult;
        }
    }
}

export default class TreeStructureStrategy implements StructureStrategyInterface {
    @observable data: Array<TreeItem> = [];

    @computed get visibleItems(): Array<Object> {
        return flattenData(this.data);
    }

    @action getData(parent: ?string | number) {
        if (parent === undefined) {
            return this.data;
        }

        return findChildrenForParentId(this.data, parent);
    }

    remove(identifier: string | number) {
        removeRecursive(this.data, identifier);
    }

    findById(id: string | number): ?Object {
        return findRecursive(this.data, id);
    }

    deactivate(id: ?string | number) {
        const data = this.getData(id);
        if (data) {
            data.splice(0, data.length);
        }
    }

    enhanceItem(item: Object): TreeItem {
        return {
            data: item,
            // TODO do not hardcode hasChildren but use metadata instead
            hasChildren: item.hasChildren,
            children: [],
        };
    }

    @action clear() {
        this.data.splice(0, this.data.length);
    }
}
