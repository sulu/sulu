// @flow
import type {ComponentType} from 'react';
import type {FieldTypeProps} from '../types';

class FieldRegistry {
    fields: {[string]: ComponentType<FieldTypeProps<*>>};
    options: {[string]: Object};

    constructor() {
        this.clear();
    }

    clear() {
        this.fields = {};
        this.options = {};
    }

    add(name: string, field: ComponentType<*>, options: Object = {}) {
        if (name in this.fields) {
            throw new Error('The key "' + name + '" has already been used for another field');
        }

        this.fields[name] = field;
        this.options[name] = options;
    }

    get(name: string) {
        if (!(name in this.fields)) {
            throw new Error('There is no field with key "' + name + '" registered');
        }

        return this.fields[name];
    }

    getOptions(name: string) {
        if (!(name in this.options)) {
            throw new Error('There are no options for a field with the key "' + name + '" registered');
        }

        return this.options[name];
    }

    has(name: string) {
        return name in this.fields;
    }
}

export default new FieldRegistry();
