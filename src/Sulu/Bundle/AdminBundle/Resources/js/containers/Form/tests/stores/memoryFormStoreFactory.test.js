// @flow
import MemoryFormStore from '../../stores/MemoryFormStore';
import memoryFormStoreFactory from '../../stores/memoryFormStoreFactory';
import metadataStore from '../../stores/metadataStore';
import SchemaFormStore from '../../stores/SchemaFormStore';

jest.mock('../../stores/metadataStore', () => ({
    getJsonSchema: jest.fn(),
    getSchema: jest.fn(),
}));

test('Create a MemoryFormStore with schema', (done) => {
    const schema = {
        title: {},
    };
    const jsonSchema = {
        schema: {},
    };

    const schemaPromise = Promise.resolve(schema);
    const jsonSchemaPromise = Promise.resolve(jsonSchema);

    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const memoryFormStore = memoryFormStoreFactory.createFromFormKey('test');

    expect(memoryFormStore).toBeInstanceOf(SchemaFormStore);
    setTimeout(() => {
        expect(memoryFormStore.innerFormStore).toBeInstanceOf(MemoryFormStore);
        expect(memoryFormStore.schema).toEqual(schema);
        done();
    });
});
