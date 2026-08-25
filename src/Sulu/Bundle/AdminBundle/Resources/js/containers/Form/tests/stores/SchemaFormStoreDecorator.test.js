// @flow
import metadataStore from '../../stores/metadataStore';
import SchemaFormStoreDecorator from '../../stores/SchemaFormStoreDecorator';
import type {FormStoreInterface} from '../../types';

jest.mock('../../stores/metadataStore', () => ({
    getJsonSchema: jest.fn(),
    getSchema: jest.fn(),
}));

test('Call given initializer with correct properties', () => {
    const schema = {title: {}};
    const schemaPromise = Promise.resolve(schema);
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchema = {schema: {}};
    const jsonSchemaPromise = Promise.resolve(jsonSchema);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const mockedStore = jest.fn();
    // $FlowFixMe
    const initializerSpy = jest.fn().mockReturnValue(mockedStore);
    const schemaFormStore = new SchemaFormStoreDecorator(initializerSpy, 'test', 'type', {});

    expect(metadataStore.getSchema).toHaveBeenCalledWith('test', 'type', {});
    expect(metadataStore.getJsonSchema).toHaveBeenCalledWith('test', 'type', {});

    return Promise.all([schemaPromise, jsonSchemaPromise]).then(() => {
        expect(initializerSpy).toHaveBeenCalledWith(schema, jsonSchema);
        expect(schemaFormStore.innerFormStore).toEqual(mockedStore);
    });
});

test('Forward method calls after inner formstore was initialized', () => {
    const schema = {title: {}};
    const schemaPromise = Promise.resolve(schema);
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchema = {schema: {}};
    const jsonSchemaPromise = Promise.resolve(jsonSchema);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const changeSpy = jest.fn();
    const changeTypeSpy = jest.fn();
    const changeMultipleSpy = jest.fn();
    const destroySpy = jest.fn();
    const finishFieldSpy = jest.fn();

    // $FlowFixMe
    const initializer = () => ({
        change: changeSpy,
        changeType: changeTypeSpy,
        changeMultiple: changeMultipleSpy,
        destroy: destroySpy,
        finishField: finishFieldSpy,
    }: FormStoreInterface);

    const schemaFormStore = new SchemaFormStoreDecorator(initializer, 'test', 'type', {});

    schemaFormStore.change('data-path', 'value', {isServerValue: true});
    schemaFormStore.changeType('new-type', {isServerValue: true});
    schemaFormStore.changeMultiple({propertyName: 'propertyValue'}, {isServerValue: true});
    schemaFormStore.destroy();
    schemaFormStore.finishField('data-path-123');

    expect(changeSpy).not.toHaveBeenCalled();
    expect(changeTypeSpy).not.toHaveBeenCalled();
    expect(changeMultipleSpy).not.toHaveBeenCalled();
    expect(destroySpy).not.toHaveBeenCalled();
    expect(finishFieldSpy).not.toHaveBeenCalled();

    return Promise.all([schemaPromise, jsonSchemaPromise]).then(() => {
        expect(changeSpy).toHaveBeenCalledWith('data-path', 'value', {isServerValue: true});
        expect(changeTypeSpy).toHaveBeenCalledWith('new-type', {isServerValue: true});
        expect(changeMultipleSpy).toHaveBeenCalledWith({propertyName: 'propertyValue'}, {isServerValue: true});
        expect(destroySpy).toHaveBeenCalledWith();
        expect(finishFieldSpy).toHaveBeenCalledWith('data-path-123');
    });
});
