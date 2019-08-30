// @flow
import type {ComponentType} from 'react';
import type {RuleTypeProps} from '../types';

class RuleTypeRegistry {
    ruleTypes: {[string]: ComponentType<RuleTypeProps>};

    constructor() {
        this.clear();
    }

    clear() {
        this.ruleTypes = {};
    }

    add(name: string, rule: ComponentType<RuleTypeProps>) {
        if (name in this.ruleTypes) {
            throw new Error('The key "' + name + '" has already been used for another rule type');
        }

        this.ruleTypes[name] = rule;
    }

    get(name: string) {
        if (!(name in this.ruleTypes)) {
            throw new Error('There is no rule type with key "' + name + '" registered');
        }

        return this.ruleTypes[name];
    }

    has(name: string) {
        return name in this.ruleTypes;
    }
}

export default new RuleTypeRegistry();
