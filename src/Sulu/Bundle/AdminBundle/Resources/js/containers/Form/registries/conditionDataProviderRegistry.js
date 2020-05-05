// @flow
import type {ConditionDataProvider} from '../types';

class ConditionDataProviderRegistry {
    conditionDataProviders: Array<ConditionDataProvider>;

    constructor() {
        this.clear();
    }

    clear() {
        this.conditionDataProviders = [];
    }

    add(conditionDataProvider: ConditionDataProvider) {
        this.conditionDataProviders.push(conditionDataProvider);
    }

    getAll() {
        return this.conditionDataProviders;
    }
}

export default new ConditionDataProviderRegistry();
