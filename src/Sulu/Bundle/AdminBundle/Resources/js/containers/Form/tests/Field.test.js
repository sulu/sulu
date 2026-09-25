// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import {observable} from 'mobx';
import ResourceStore from '../../../stores/ResourceStore';
import Router from '../../../services/Router';
import conditionDataProviderRegistry from '../registries/conditionDataProviderRegistry';
import Field from '../Field';
import fieldRegistry from '../registries/fieldRegistry';
import FormInspector from '../FormInspector';
import ResourceFormStore from '../stores/ResourceFormStore';

let fieldTypeProps = [];

jest.mock('../../../services/Router/Router', () => jest.fn());

jest.mock('../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.locale = observableOptions?.locale;
}));

jest.mock('../FormInspector', () => jest.fn(function(resourceFormStore) {
    this.locale = resourceFormStore.locale;
}));

jest.mock('../stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.locale = resourceStore.locale;
}));

jest.mock('../registries/fieldRegistry', () => ({
    get: jest.fn(),
    getOptions: jest.fn(),
}));

jest.mock('../../../utils/Translator');

function createFormInspector() {
    return new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));
}

function createFieldType(type = 'text') {
    return jest.fn((props) => {
        fieldTypeProps.push(props);

        return <input data-testid="field-type" disabled={props.disabled} type={type} />;
    });
}

function mockFieldType(type = 'text') {
    const FieldType = createFieldType(type);
    fieldRegistry.get.mockReturnValue(FieldType);

    return FieldType;
}

function getFieldTypeProps() {
    return fieldTypeProps[fieldTypeProps.length - 1];
}

function createFieldProps(props: Object = {}) {
    return {
        data: {},
        dataPath: '',
        formInspector: createFormInspector(),
        name: 'test',
        onChange: jest.fn(),
        onFinish: jest.fn(),
        onSuccess: undefined,
        router: undefined,
        schema: {label: 'label1', type: 'text'},
        schemaPath: '',
        ...props,
    };
}

function renderField(props: Object = {}) {
    return render(<Field {...createFieldProps(props)} />);
}

beforeEach(() => {
    jest.clearAllMocks();
    fieldTypeProps = [];
    conditionDataProviderRegistry.clear();
    mockFieldType();
    fieldRegistry.getOptions.mockReturnValue(undefined);
});

test('Render correct label with correct field type', () => {
    mockFieldType('text');
    const {unmount} = renderField({schema: {label: 'label1', type: 'text'}});

    expect(screen.getByText('label1')).toBeInTheDocument();
    expect(screen.getByTestId('field-type')).toHaveAttribute('type', 'text');

    unmount();

    mockFieldType('date');
    renderField({schema: {label: 'label2', type: 'datetime'}});

    expect(screen.getByText('label2')).toBeInTheDocument();
    expect(screen.getByTestId('field-type')).toHaveAttribute('type', 'date');
});

test('Render field with correct values for grid', () => {
    renderField({schema: {label: 'label1', type: 'text', colSpan: 8, spaceAfter: 3}});

    expect(screen.getByText('label1').closest('.item')).toHaveClass('colSpan-8', 'space-after-3');
});

test('Render a required field with correct field type', () => {
    renderField({schema: {label: 'label1', required: true, type: 'text'}});

    expect(screen.getByText('label1 *')).toBeInTheDocument();
    expect(screen.getByTestId('field-type')).toHaveAttribute('type', 'text');
});

test('Render a field without a label', () => {
    renderField({schema: {type: 'text'}});

    expect(screen.queryByText('label1')).not.toBeInTheDocument();
    expect(screen.getByTestId('field-type')).toBeInTheDocument();
});

test('Render a field with a description', () => {
    renderField({
        schema: {
            description: 'Small description describing the field',
            label: 'label1',
            type: 'text',
        },
    });

    expect(screen.getByText('label1')).toBeInTheDocument();
    expect(screen.getByText('Small description describing the field')).toBeInTheDocument();
});

test('Render a field with an error', () => {
    renderField({
        error: {keyword: 'minLength', parameters: {}},
        schema: {label: 'label1', type: 'text'},
    });

    expect(screen.getByText('sulu_admin.error_minlength')).toBeInTheDocument();
});

test('Render a field without a const error', () => {
    renderField({
        error: {keyword: 'const', parameters: {}},
        schema: {label: 'label1', type: 'text'},
    });

    expect(screen.getByText('label1')).toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.error_const')).not.toBeInTheDocument();
});

test('Render a field with a error collection', () => {
    const error = {
        ids: {
            keyword: 'minItems',
            parameters: {},
        },
    };

    renderField({
        error,
        schema: {label: 'label1', type: 'text'},
    });

    expect(screen.getByText('sulu_admin.error_minitems')).toBeInTheDocument();
});

test('Pass correct props to FieldType', () => {
    const router = new Router();
    const formInspector = createFormInspector();
    const successSpy = jest.fn();

    const schema = {
        label: 'Text',
        maxOccurs: 4,
        minOccurs: 2,
        type: 'text_line',
        types: {},
    };

    const data = {
        title: 'Test',
    };

    renderField({
        data,
        dataPath: '/block/0/text',
        formInspector,
        name: 'text',
        onSuccess: successSpy,
        router,
        schema,
        schemaPath: '/text',
        showAllErrors: true,
        value: 'test',
    });

    expect(getFieldTypeProps()).toEqual(expect.objectContaining({
        data,
        dataPath: '/block/0/text',
        disabled: false,
        formInspector,
        label: 'Text',
        maxOccurs: 4,
        minOccurs: 2,
        onSuccess: successSpy,
        router,
        schemaPath: '/text',
        showAllErrors: true,
        types: {},
        value: 'test',
    }));
});

test('Do not render anything if visibleCondition evaluates to false', () => {
    const schema = {
        label: 'Text',
        type: 'text_line',
        visibleCondition: 'title != "Test"',
    };

    const data = observable({title: 'Test'});

    renderField({
        data,
        dataPath: '/block/0/text',
        name: 'text',
        schema,
        schemaPath: '/text',
        showAllErrors: true,
        value: 'test',
    });

    expect(screen.queryByTestId('field-type')).not.toBeInTheDocument();

    act(() => {
        data.title = 'Changed title!';
    });

    expect(screen.getByTestId('field-type')).toBeInTheDocument();
});

test('Render the field if visibleCondition with conditionDataProvider evaluates to true', () => {
    conditionDataProviderRegistry.add((data) => ({__test: data.test}));

    const schema = {
        label: 'Text',
        type: 'text_line',
        visibleCondition: '__test == "Test"',
    };

    renderField({
        data: {test: 'Test'},
        dataPath: '/block/0/text',
        name: 'text',
        schema,
        schemaPath: '/text',
        showAllErrors: true,
        value: 'test',
    });

    expect(screen.getByTestId('field-type')).toBeInTheDocument();
});

test('Pass disabled flag to FieldType if disabledCondition evaluates to true', () => {
    const schema = {
        disabledCondition: 'title == "Test"',
        label: 'Text',
        type: 'text_line',
    };

    const data = observable({title: 'Test'});

    renderField({
        data,
        dataPath: '/block/0/text',
        name: 'text',
        schema,
        schemaPath: '/text',
        showAllErrors: true,
        value: 'test',
    });

    expect(getFieldTypeProps().disabled).toEqual(true);
    expect(screen.getByTestId('field-type')).toBeDisabled();

    act(() => {
        data.title = 'Change title!';
    });

    expect(getFieldTypeProps().disabled).toEqual(false);
    expect(screen.getByTestId('field-type')).toBeEnabled();
});

test('Pass disabled flag to FieldType if disabledCondition with conditionDataProvider evaluates to true', () => {
    conditionDataProviderRegistry.add((data) => ({__test: data.test}));

    const schema = {
        disabledCondition: '__test == "Test"',
        label: 'Text',
        type: 'text_line',
    };

    renderField({
        data: {test: 'Test'},
        dataPath: '/block/0/text',
        name: 'text',
        schema,
        schemaPath: '/text',
        showAllErrors: true,
        value: 'test',
    });

    expect(getFieldTypeProps().disabled).toEqual(true);
});

test('Merge with options from fieldRegistry before passing props to FieldType', () => {
    fieldRegistry.getOptions.mockReturnValue({
        option: 'value',
    });

    const schema = {
        label: 'Text',
        maxOccurs: 4,
        minOccurs: 2,
        options: {
            anotherOption: {name: 'anotherOption', value: 'anotherValue'},
        },
        type: 'text_line',
        types: {},
    };

    renderField({
        schema,
        showAllErrors: true,
        value: 'test',
    });

    expect(getFieldTypeProps()).toEqual(expect.objectContaining({
        fieldTypeOptions: {
            option: 'value',
        },
        maxOccurs: 4,
        minOccurs: 2,
        schemaOptions: {
            anotherOption: {name: 'anotherOption', value: 'anotherValue'},
        },
        showAllErrors: true,
        types: {},
        value: 'test',
    }));
});

test('Call onChange callback when value of Field changes', () => {
    const changeSpy = jest.fn();
    renderField({
        name: 'test',
        onChange: changeSpy,
        schema: {label: 'label', type: 'text'},
    });

    getFieldTypeProps().onChange('test value', {isDefaultValue: true});

    expect(changeSpy).toHaveBeenCalledWith('test', 'test value', {isDefaultValue: true});
});

test('Do not call onChange callback when value of disabled Field changes', () => {
    const changeSpy = jest.fn();
    renderField({
        data: {title: 'Test'},
        name: 'test',
        onChange: changeSpy,
        schema: {label: 'label', type: 'text', disabledCondition: 'title == "Test"'},
    });

    getFieldTypeProps().onChange('test value');

    expect(changeSpy).not.toHaveBeenCalled();
});

test('Call onFinish callback after editing the field has finished', () => {
    const finishSpy = jest.fn();
    renderField({
        dataPath: '/block/0/test',
        name: 'test',
        onFinish: finishSpy,
        schema: {label: 'label', type: 'text'},
        schemaPath: '/test',
    });

    getFieldTypeProps().onFinish();

    expect(finishSpy).toHaveBeenCalledWith('/block/0/test', '/test');
});

test('Call onSuccess callback when field calls onSuccess', () => {
    const successSpy = jest.fn();
    renderField({
        dataPath: '/block/0/test',
        name: 'test',
        onSuccess: successSpy,
        schema: {label: 'label', type: 'text'},
        schemaPath: '/test',
    });

    getFieldTypeProps().onSuccess();

    expect(successSpy).toHaveBeenCalled();
});

test('Do not render anything if field does not exist and onInvalid is set to ignore', () => {
    fieldRegistry.get.mockImplementation(() => {
        throw new Error();
    });

    renderField({
        dataPath: '/test',
        name: 'test',
        schema: {label: 'label', type: 'not-existing', onInvalid: 'ignore'},
        schemaPath: '/test',
    });

    expect(screen.queryByTestId('field-type')).not.toBeInTheDocument();
});

test('Call onFocus callback when Field gets focus', () => {
    const formInspector = createFormInspector();
    const initialOnChange = jest.fn();
    const onChangeMock = jest.fn();

    const fieldProps = createFieldProps({
        dataPath: '/title',
        formInspector,
        name: 'test',
        onChange: initialOnChange,
        schema: {label: 'label', type: 'text'},
        schemaPath: '/schema/title',
        value: 'test value',
    });

    const {rerender} = render(<Field {...fieldProps} />);

    const target = new EventTarget();
    const dispatchEventSpy = jest.spyOn(target, 'dispatchEvent');

    getFieldTypeProps().onFocus(target);

    expect(dispatchEventSpy).toHaveBeenCalled();

    const dispatchedEvent = dispatchEventSpy.mock.calls[0][0];

    expect(dispatchedEvent.type).toBe('sulu.focus');
    expect(dispatchedEvent.bubbles).toBe(true);
    expect(dispatchedEvent.detail).toEqual({
        schemaType: 'text',
        setValue: expect.any(Function),
        getValue: expect.any(Function),
        schemaPath: '/schema/title',
        dataPath: '/title',
        formInspector,
    });

    expect(dispatchedEvent.detail.getValue()).toBe('test value');

    rerender(<Field {...fieldProps} onChange={onChangeMock} />);
    dispatchedEvent.detail.setValue('new value');

    expect(onChangeMock).toHaveBeenCalledWith('test', 'new value', undefined);
});
