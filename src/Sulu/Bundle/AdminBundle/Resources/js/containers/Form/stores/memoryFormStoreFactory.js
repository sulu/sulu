// @flow
import type {IObservableValue} from 'mobx';
import MemoryFormStore from './MemoryFormStore';
import SchemaFormStore from './SchemaFormStore';

class MemoryFormStoreFactory {
    createFromFormKey(formKey: string, data: Object = {}, locale: ?IObservableValue<string>) {
        return new SchemaFormStore(
            (schema, jsonSchema) => new MemoryFormStore(data, schema, jsonSchema, locale),
            formKey
        );
    }
}

export default new MemoryFormStoreFactory();
