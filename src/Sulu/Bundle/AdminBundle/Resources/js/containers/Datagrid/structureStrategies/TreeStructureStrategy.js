// @flow
import {action, observable} from 'mobx';
import type {StructureStrategyInterface, TreeItem} from '../types';

export default class TreeStructureStrategy implements StructureStrategyInterface {
    @observable data: Array<TreeItem> = [];

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
            return this.data;
        }

        return this.findChildrenForParentId(this.data, parent);
    }

    findById(id: string | number): ?Object {
        return this.findRecursive(this.data, id);
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

    enhanceItem(item: Object): Object {
        return {
            data: item,
            children: [],
        };
    }

    @action clear() {
        this.data.splice(0, this.data.length);
    }
}
