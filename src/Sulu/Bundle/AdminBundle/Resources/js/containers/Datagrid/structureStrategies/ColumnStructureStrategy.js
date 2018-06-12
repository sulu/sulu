// @flow
import {action, computed, observable} from 'mobx';
import type {StructureStrategyInterface} from '../types';

export default class ColumnStructureStrategy implements StructureStrategyInterface {
    @observable rawData: Map<?string | number, Array<Object>> = new Map();

    @computed get visibleData(): Array<Object> {
        return this.data.reduce((data, items) => data.concat(...items), []);
    }

    @computed get activeItems(): Array<?string | number> {
        return Array.from(this.rawData.keys());
    }

    @computed get data(): Array<Array<Object>> {
        return Array.from(this.rawData.values());
    }

    @action getData(parent: ?string | number) {
        const parents = this.activeItems;
        const parentIndex = this.data.findIndex((column) => column.findIndex((item) => item.id === parent) !== -1);
        parents.filter((parent, index) => index > parentIndex).forEach((parent) => this.rawData.delete(parent));
        this.rawData.set(parent, []);

        return this.rawData.get(parent);
    }

    findById(identifier: string | number): ?Object {
        for (const column of this.data) {
            for (const item of column) {
                // TODO do not hardcode id but use metdata instead
                if (item.id === identifier) {
                    return item;
                }
            }
        }
    }

    @action clear() {
        this.rawData.clear();
    }

    enhanceItem(item: Object): Object {
        return item;
    }
}
