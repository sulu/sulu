// @flow
import type {IObservableValue} from 'mobx';
import type {RawSchema} from '../types';
import MemoryFormStore from './MemoryFormStore';
import SchemaFormStore from './SchemaFormStore';

class MemoryFormStoreFactory {
    createFromFormKey(formKey: string, data: Object = {}, locale: ?IObservableValue<string>) {
        return new SchemaFormStore(
            (schema, jsonSchema) => new MemoryFormStore(data, schema, jsonSchema, locale),
            formKey
        );
    }

    createFromSchema(schema: RawSchema, jsonSchema: Object, data: Object = {}) {
        return new MemoryFormStore(data, schema, jsonSchema);
    }
}

export default new MemoryFormStoreFactory();
