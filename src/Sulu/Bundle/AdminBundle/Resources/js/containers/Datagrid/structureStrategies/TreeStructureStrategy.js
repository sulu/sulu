// @flow
import {action, observable} from 'mobx';
import type {StructureStrategyInterface} from '../types';

export default class TreeStructureStrategy implements StructureStrategyInterface {
    @observable data: Map<string | number | boolean, Array<Object>>;

    constructor() {
        this.data = new Map();
    }

    @action getData(parent: ?string | number | boolean) {
        if (!parent) {
            parent = false;
        }

        if (!this.data.get(parent)) {
            this.data.set(parent, []);
        }

        return this.data.get(parent);
    }

    clear() {
        this.data.clear();
    }
}
