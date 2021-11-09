// @flow
import MemoryFormStore from '../../stores/MemoryFormStore';
import memoryFormStoreFactory from '../../stores/memoryFormStoreFactory';
import metadataStore from '../../stores/metadataStore';
import SchemaFormStoreDecorator from '../../stores/SchemaFormStoreDecorator';

jest.mock('../../stores/metadataStore', () => ({
    getJsonSchema: jest.fn(),
    getSchema: jest.fn(),
}));

test('Create a MemoryFormStore with schema', () => {
    const schema = {
        title: {},
    };
    const jsonSchema = {
        type: 'object',
        required: [],
    };

    const schemaPromise = Promise.resolve(schema);
    const jsonSchemaPromise = Promise.resolve(jsonSchema);

    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const metadataOptions = {test: 'value'};

    const memoryFormStore = memoryFormStoreFactory.createFromFormKey('test', {}, undefined, 'type', metadataOptions);

    expect(memoryFormStore).toBeInstanceOf(SchemaFormStoreDecorator);
    expect(metadataStore.getSchema).toBeCalledWith('test', 'type', {test: 'value'});
    expect(metadataStore.getJsonSchema).toBeCalledWith('test', 'type', {test: 'value'});
    return Promise.all([schemaPromise, jsonSchemaPromise]).then(() => {
        expect(memoryFormStore.innerFormStore).toBeInstanceOf(MemoryFormStore);
        expect(memoryFormStore.schema).toEqual(schema);
        expect(memoryFormStore.metadataOptions).toEqual(metadataOptions);
    });
});
