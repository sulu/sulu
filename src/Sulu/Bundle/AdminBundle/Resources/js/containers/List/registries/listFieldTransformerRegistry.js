// @flow
import type {FieldTransformer} from '../types';

class ListFieldTransformerRegistry {
    fieldTransformers: {[string]: FieldTransformer};

    constructor() {
        this.clear();
    }

    clear() {
        this.fieldTransformers = {};
    }

    has(name: string) {
        return !!this.fieldTransformers[name];
    }

    add(name: string, Type: FieldTransformer) {
        if (name in this.fieldTransformers) {
            throw new Error('The key "' + name + '" has already been used for another field transformer');
        }

        this.fieldTransformers[name] = Type;
    }

    get(name: string): FieldTransformer {
        if (!(name in this.fieldTransformers)) {
            throw new Error(
                'The list field transformer with the key "' + name + '" is not defined. ' +
                'You probably forgot to add it to the registry using the "add" method.'
            );
        }

        return this.fieldTransformers[name];
    }
}

export default new ListFieldTransformerRegistry();
