// @flow
import type {IObservableValue} from 'mobx';
import type {Schema} from '../types';
import MemoryFormStore from './MemoryFormStore';
import SchemaFormStoreDecorator from './SchemaFormStoreDecorator';

class MemoryFormStoreFactory {
    createFromFormKey(
        formKey: string,
        data: Object = {},
        locale: ?IObservableValue<string>,
        type: ?string,
        metadataOptions: ?Object
    ) {
        return new SchemaFormStoreDecorator(
            (schema, jsonSchema) => new MemoryFormStore(data, schema, jsonSchema, locale, metadataOptions),
            formKey,
            type,
            metadataOptions
        );
    }

    createFromSchema(schema: Schema, jsonSchema: Object, data: Object = {}) {
        return new MemoryFormStore(data, schema, jsonSchema);
    }
}

export default new MemoryFormStoreFactory();
