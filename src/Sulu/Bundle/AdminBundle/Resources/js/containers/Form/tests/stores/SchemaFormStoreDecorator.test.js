// @flow
import metadataStore from '../../stores/metadataStore';
import SchemaFormStoreDecorator from '../../stores/SchemaFormStoreDecorator';

jest.mock('../../stores/metadataStore', () => ({
    getJsonSchema: jest.fn(),
    getSchema: jest.fn(),
}));

test('Initialize a SchemaFormStore', () => {
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

    const store = {
        change: jest.fn(),
    };
    // $FlowFixMe
    const initializerSpy = jest.fn().mockReturnValue(store);
    const schemaFormStore = new SchemaFormStoreDecorator(initializerSpy, 'test');

    return Promise.all([schemaPromise, jsonSchemaPromise]).then(() => {
        expect(initializerSpy).toBeCalledWith(schema, jsonSchema);
        expect(schemaFormStore.innerFormStore).toEqual(store);
    });
});
