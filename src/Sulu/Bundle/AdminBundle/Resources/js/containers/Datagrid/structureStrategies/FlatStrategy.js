// @flow
import {observable} from 'mobx';
import type {StructureStrategyInterface} from '../types';

export default class FlatStrategy implements StructureStrategyInterface {
    @observable data: Array<Object>;

    constructor() {
        this.data = [];
    }

    clear() {
        this.data.splice(0, this.data.length);
    }
}
