// @flow
import AbstractFieldFilterType from '../fieldFilterTypes/AbstractFieldFilterType';

class ListFieldFilterTypeRegistry {
    fieldFilterTypes: {[string]: Class<AbstractFieldFilterType<*>>};
    options: {[string]: Object};

    constructor() {
        this.clear();
    }

    clear() {
        this.fieldFilterTypes = {};
        this.options = {};
    }

    has(name: string) {
        return !!this.fieldFilterTypes[name];
    }

    add(name: string, FieldFilterType: Class<AbstractFieldFilterType<*>>, options: Object = {}) {
        if (name in this.fieldFilterTypes) {
            throw new Error('The key "' + name + '" has already been used for another field filter type');
        }

        this.fieldFilterTypes[name] = FieldFilterType;
        this.options[name] = options;
    }

    get(name: string): Class<AbstractFieldFilterType<*>> {
        if (!(name in this.fieldFilterTypes)) {
            throw new Error(
                'The list field filter type with the key "' + name + '" is not defined. ' +
                'You probably forgot to add it to the registry using the "add" method.' +
                '\n\nRegistered keys: ' + Object.keys(this.fieldFilterTypes).sort().join(', ')
            );
        }

        return this.fieldFilterTypes[name];
    }

    getOptions(name: string) {
        if (!(name in this.options)) {
            throw new Error(
                'There are no options for a field with the key "' + name + '" registered.' +
                '\n\nRegistered keys: ' + Object.keys(this.options).sort().join(', ')
            );
        }

        return this.options[name];
    }
}

export default new ListFieldFilterTypeRegistry();
