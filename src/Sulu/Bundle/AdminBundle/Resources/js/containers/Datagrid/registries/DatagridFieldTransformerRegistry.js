// @flow
import type {FieldTransformer} from '../types';

class FieldTransformerRegistry {
    fieldTransformes: {[string]: FieldTransformer};

    constructor() {
        this.clear();
    }

    clear() {
        this.fieldTransformes = {};
    }

    has(name: string) {
        return !!this.fieldTransformes[name];
    }

    add(name: string, Type: FieldTransformer) {
        if (name in this.fieldTransformes) {
            throw new Error('The key "' + name + '" has already been used for another field transformer');
        }

        this.fieldTransformes[name] = Type;
    }

    get(name: string): FieldTransformer {
        if (!(name in this.fieldTransformes)) {
            throw new Error(
                'The datagrid field transformer with the key "' + name + '" is not defined. ' +
                'You probably forgot to add it to the registry using the "add" method.'
            );
        }

        return this.fieldTransformes[name];
    }
}

export default new FieldTransformerRegistry();
