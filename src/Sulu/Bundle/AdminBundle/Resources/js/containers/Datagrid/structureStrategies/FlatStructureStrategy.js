// @flow
import {action, observable} from 'mobx';
import type {StructureStrategyInterface} from '../types';

export default class FlatStructureStrategy implements StructureStrategyInterface {
    @observable data: Array<Object>;

    constructor() {
        this.data = [];
    }

    getData() {
        return this.data;
    }

    @action clear() {
        this.data.splice(0, this.data.length);
    }

    enhanceItem(item: Object): Object {
        return item;
    }
}
