// @flow
import type {RuleType, RuleTypes} from '../types';

class RuleRegistry {
    rules: RuleTypes;

    constructor() {
        this.clear();
    }

    clear() {
        this.rules = {};
    }

    setRules(rules: RuleTypes) {
        this.rules = rules;
    }

    get(name: string): RuleType {
        if (!(name in this.rules)) {
            throw new Error('There is no rule with key "' + name + '" registered');
        }

        return this.rules[name];
    }

    getAll(): RuleTypes {
        return this.rules;
    }
}

export default new RuleRegistry();
