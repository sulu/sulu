// @flow
import type {ComponentType} from 'react';
import type {FieldFilterTypeProps} from '../types';

class ListFieldFilterTypeRegistry {
    fieldFilterTypes: {[string]: ComponentType<FieldFilterTypeProps<*>>};

    constructor() {
        this.clear();
    }

    clear() {
        this.fieldFilterTypes = {};
    }

    has(name: string) {
        return !!this.fieldFilterTypes[name];
    }

    add(name: string, Type: ComponentType<FieldFilterTypeProps<*>>) {
        if (name in this.fieldFilterTypes) {
            throw new Error('The key "' + name + '" has already been used for another field filter type');
        }

        this.fieldFilterTypes[name] = Type;
    }

    get(name: string): ComponentType<FieldFilterTypeProps<*>>{
        if (!(name in this.fieldFilterTypes)) {
            throw new Error(
                'The list field filter type with the key "' + name + '" is not defined. ' +
                'You probably forgot to add it to the registry using the "add" method.'
            );
        }

        return this.fieldFilterTypes[name];
    }
}

export default new ListFieldFilterTypeRegistry();
