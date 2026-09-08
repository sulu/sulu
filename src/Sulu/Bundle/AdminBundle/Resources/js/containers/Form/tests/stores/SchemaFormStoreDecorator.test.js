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

test('Should register a field validator on the inner store once it exists and remove it again', () => {
    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const removeValidatorSpy = jest.fn();
    const addFieldValidatorSpy = jest.fn().mockReturnValue(removeValidatorSpy);

    // $FlowFixMe
    const initializer = () => ({
        addFieldValidator: addFieldValidatorSpy,
    }: FormStoreInterface);

    const schemaFormStore = new SchemaFormStoreDecorator(initializer, 'test', 'type', {});
    const validator = jest.fn();
    const removeValidator = schemaFormStore.addFieldValidator('/attributes', validator);

    expect(addFieldValidatorSpy).not.toHaveBeenCalled();

    return Promise.all([schemaPromise, jsonSchemaPromise]).then(() => {
        expect(addFieldValidatorSpy).toHaveBeenCalledWith('/attributes', validator);

        removeValidator();

        expect(removeValidatorSpy).toHaveBeenCalledTimes(1);
    });
});

test('Should not register a field validator that was removed before the inner store existed', () => {
    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const addFieldValidatorSpy = jest.fn().mockReturnValue(jest.fn());

    // $FlowFixMe
    const initializer = () => ({
        addFieldValidator: addFieldValidatorSpy,
    }: FormStoreInterface);

    const schemaFormStore = new SchemaFormStoreDecorator(initializer, 'test', 'type', {});
    schemaFormStore.addFieldValidator('/attributes', jest.fn())();

    return Promise.all([schemaPromise, jsonSchemaPromise]).then(() => {
        expect(addFieldValidatorSpy).not.toHaveBeenCalled();
    });
});
