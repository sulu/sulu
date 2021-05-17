// @flow
import MemoryFormStore from './MemoryFormStore';
import SchemaFormStoreDecorator from './SchemaFormStoreDecorator';
import type {IObservableValue} from 'mobx/lib/mobx';
import type {Schema} from '../types';

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
