// @flow
import {action, computed, observable} from 'mobx';
import type {StructureStrategyInterface} from '../types';

export default class FlatStructureStrategy implements StructureStrategyInterface {
    @observable data: Array<Object>;

    @computed get visibleItems() {
        return this.data;
    }

    constructor() {
        this.data = [];
    }

    @action clear(parentId: ?string | number) {
        if (parentId !== undefined) {
            throw new Error(
                'This StructureStrategy does not support nesting, therefore the parentId should not be set'
            );
        }

        this.data.splice(0, this.data.length);
    }

    remove(identifier: string | number) {
        this.data.splice(this.data.findIndex((item) => item.id === identifier), 1);
    }

    findById(identifier: string | number): ?Object {
        // TODO do not hardcode id but use metdata instead
        return this.data.find((item) => item.id === identifier);
    }

    addItem(item: Object, parentId: ?string | number): void {
        if (parentId !== undefined) {
            throw new Error(
                'This StructureStrategy does not support nesting, therefore the parentId should not be set'
            );
        }

        this.data.push(item);
    }
}
