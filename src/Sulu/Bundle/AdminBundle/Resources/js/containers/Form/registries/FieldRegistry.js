// @flow
import type {ComponentType} from 'react';
import type {FieldTypeProps} from '../../../types';

class FieldRegistry {
    // TODO add correct props when possible
    // see https://github.com/facebook/flow/issues/5703
    fields: {[string]: ComponentType<FieldTypeProps<*>>};

    constructor() {
        this.clear();
    }

    clear() {
        this.fields = {};
    }

    add(name: string, field: ComponentType<*>) {
        if (name in this.fields) {
            throw new Error('The key "' + name + '" has already been used for another field');
        }

        this.fields[name] = field;
    }

    get(name: string) {
        if (!(name in this.fields)) {
            throw new Error('There is no field with key "' + name + '" registered');
        }

        return this.fields[name];
    }
}

export default new FieldRegistry();
