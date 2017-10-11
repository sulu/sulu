// @flow
import type {ComponentType} from 'react';

class FieldRegistry {
    fields: {[string]: ComponentType<*>};

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
        return this.fields[name];
    }
}

export default new FieldRegistry();
