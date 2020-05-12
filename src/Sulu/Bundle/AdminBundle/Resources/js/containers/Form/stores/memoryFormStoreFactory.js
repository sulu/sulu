// @flow
import type {IObservableValue} from 'mobx';
import type {RawSchema} from '../types';
import MemoryFormStore from './MemoryFormStore';
import SchemaFormStoreDecorator from './SchemaFormStoreDecorator';

class MemoryFormStoreFactory {
    createFromFormKey(formKey: string, data: Object = {}, locale: ?IObservableValue<string>, metadataOptions: ?Object) {
        return new SchemaFormStoreDecorator(
            (schema, jsonSchema) => new MemoryFormStore(data, schema, jsonSchema, locale, metadataOptions),
            formKey,
            metadataOptions
        );
    }

    createFromSchema(schema: RawSchema, jsonSchema: Object, data: Object = {}) {
        return new MemoryFormStore(data, schema, jsonSchema);
    }
}

export default new MemoryFormStoreFactory();
