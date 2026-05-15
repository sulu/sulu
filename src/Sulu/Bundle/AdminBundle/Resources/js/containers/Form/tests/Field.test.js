// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import {observable} from 'mobx';
import Router from '../../../services/Router';
import conditionDataProviderRegistry from '../registries/conditionDataProviderRegistry';
import Field from '../Field';
import fieldRegistry from '../registries/fieldRegistry';
import FormInspector from '../FormInspector';

jest.mock('../../../services/Router/Router', () => jest.fn());

jest.mock('../registries/fieldRegistry', () => ({
    get: jest.fn(),
    getOptions: jest.fn(),
}));

function createFormInspector() {
    return new FormInspector(({isFieldModified: jest.fn()}: any));
}

function renderField(props: Object = {}) {
    return render(
        <Field
            data={{}}
            dataPath=""
            formInspector={createFormInspector()}
            name="test"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={{label: 'label', type: 'text'}}
            schemaPath=""
            {...props}
        />
    );
}

function getLatestComponentProps(componentMock: Function) {
    const calls = ((componentMock: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

beforeEach(() => {
    conditionDataProviderRegistry.clear();
    jest.clearAllMocks();
    fieldRegistry.getOptions.mockReturnValue({});
});

test('Render correct label with correct field type', () => {
    const successSpy = jest.fn();

    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const {asFragment, unmount} = renderField({
        onSuccess: successSpy,
        schema: {label: 'label1', type: 'text'},
    });

    expect(asFragment()).toMatchSnapshot();
    unmount();

    fieldRegistry.get.mockReturnValue(function DateTime() {
        return <input type="date" />;
    });

    const {asFragment: asSecondFragment} = renderField({
        onSuccess: successSpy,
        schema: {label: 'label2', type: 'datetime'},
    });

    expect(asSecondFragment()).toMatchSnapshot();
});

test('Render field with correct values for grid', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const {asFragment} = renderField({
        schema: {label: 'label1', type: 'text', colSpan: 8, spaceAfter: 3},
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Render a required field with correct field type', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const {asFragment} = renderField({
        schema: {label: 'label1', required: true, type: 'text'},
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Render a field without a label', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const {asFragment} = renderField({
        schema: {type: 'text'},
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Render a field with a description', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const {asFragment} = renderField({
        schema: {
            description: 'Small description describing the field',
            label: 'label1',
            type: 'text',
        },
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Render a field with an error', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const {asFragment} = renderField({
        error: {keyword: 'minLength', parameters: {}},
        schema: {label: 'label1', type: 'text'},
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Render a field without a const error', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const {asFragment} = renderField({
        error: {keyword: 'const', parameters: {}},
        schema: {label: 'label1', type: 'text'},
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Render a field with a error collection', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const error = {
        ids: {
            keyword: 'minItems',
            parameters: {},
        },
    };

    const {asFragment} = renderField({
        error,
        schema: {label: 'label1', type: 'text'},
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Pass correct props to FieldType', () => {
    const router = new Router();
    const formInspector = createFormInspector();
    const successSpy = jest.fn();
    const Text: any = jest.fn(() => <input type="date" />);

    fieldRegistry.get.mockReturnValue(Text);

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

    expect(getLatestComponentProps(Text)).toEqual(expect.objectContaining({
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
    const Text: any = jest.fn(() => <div data-testid="field-type" />);
    fieldRegistry.get.mockReturnValue(Text);

    const data = observable({title: 'Test'});

    renderField({
        data,
        dataPath: '/block/0/text',
        name: 'text',
        schema: {
            label: 'Text',
            type: 'text_line',
            visibleCondition: 'title != "Test"',
        },
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
    const Text: any = jest.fn(() => <div data-testid="field-type" />);
    fieldRegistry.get.mockReturnValue(Text);

    renderField({
        data: {test: 'Test'},
        dataPath: '/block/0/text',
        name: 'text',
        schema: {
            label: 'Text',
            type: 'text_line',
            visibleCondition: '__test == "Test"',
        },
        schemaPath: '/text',
        showAllErrors: true,
        value: 'test',
    });

    expect(screen.getByTestId('field-type')).toBeInTheDocument();
});

test('Pass disabled flag to FieldType if disabledCondition evaluates to true', () => {
    const Text: any = jest.fn(() => <input type="date" />);
    fieldRegistry.get.mockReturnValue(Text);

    const data = observable({title: 'Test'});

    renderField({
        data,
        dataPath: '/block/0/text',
        name: 'text',
        schema: {
            disabledCondition: 'title == "Test"',
            label: 'Text',
            type: 'text_line',
        },
        schemaPath: '/text',
        showAllErrors: true,
        value: 'test',
    });

    expect(getLatestComponentProps(Text).disabled).toEqual(true);

    act(() => {
        data.title = 'Change title!';
    });

    expect(getLatestComponentProps(Text).disabled).toEqual(false);
});

test('Pass disabled flag to FieldType if disabledCondition with conditionDataProvider evaluates to true', () => {
    conditionDataProviderRegistry.add((data) => ({__test: data.test}));
    const Text: any = jest.fn(() => <input type="date" />);
    fieldRegistry.get.mockReturnValue(Text);

    renderField({
        data: {test: 'Test'},
        dataPath: '/block/0/text',
        name: 'text',
        schema: {
            disabledCondition: '__test == "Test"',
            label: 'Text',
            type: 'text_line',
        },
        schemaPath: '/text',
        showAllErrors: true,
        value: 'test',
    });

    expect(getLatestComponentProps(Text).disabled).toEqual(true);
});

test('Merge with options from fieldRegistry before passing props to FieldType', () => {
    const Text: any = jest.fn(() => <input type="text" />);
    fieldRegistry.get.mockReturnValue(Text);
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
        name: 'text',
        schema,
        showAllErrors: true,
        value: 'test',
    });

    expect(getLatestComponentProps(Text)).toEqual(expect.objectContaining({
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
    const Text: any = jest.fn(() => <input type="text" />);
    fieldRegistry.get.mockReturnValue(Text);

    const changeSpy = jest.fn();
    renderField({
        name: 'test',
        onChange: changeSpy,
        schema: {label: 'label', type: 'text'},
    });

    getLatestComponentProps(Text).onChange('test value', {isDefaultValue: true});

    expect(changeSpy).toBeCalledWith('test', 'test value', {isDefaultValue: true});
});

test('Do not call onChange callback when value of disabled Field changes', () => {
    const Text: any = jest.fn(() => <input type="text" />);
    fieldRegistry.get.mockReturnValue(Text);

    const changeSpy = jest.fn();
    renderField({
        data: {title: 'Test'},
        name: 'test',
        onChange: changeSpy,
        schema: {label: 'label', type: 'text', disabledCondition: 'title == "Test"'},
    });

    getLatestComponentProps(Text).onChange('test value');

    expect(changeSpy).not.toBeCalled();
});

test('Call onFinish callback after editing the field has finished', () => {
    const Text: any = jest.fn(() => <input type="text" />);
    fieldRegistry.get.mockReturnValue(Text);

    const finishSpy = jest.fn();
    renderField({
        dataPath: '/block/0/test',
        name: 'test',
        onFinish: finishSpy,
        schema: {label: 'label', type: 'text'},
        schemaPath: '/test',
    });

    getLatestComponentProps(Text).onFinish();

    expect(finishSpy).toBeCalledWith('/block/0/test', '/test');
});

test('Call onSuccess callback when field calls onSuccess', () => {
    const successSpy = jest.fn();
    const Text: any = jest.fn(() => <input type="text" />);
    fieldRegistry.get.mockReturnValue(Text);

    const finishSpy = jest.fn();
    renderField({
        dataPath: '/block/0/test',
        name: 'test',
        onFinish: finishSpy,
        onSuccess: successSpy,
        schema: {label: 'label', type: 'text'},
        schemaPath: '/test',
    });

    getLatestComponentProps(Text).onSuccess();

    expect(successSpy).toBeCalled();
});

test('Do not render anything if field does not exist and onInvalid is set to ignore', () => {
    fieldRegistry.get.mockImplementation(() => {
        throw new Error();
    });

    const {container} = renderField({
        dataPath: '/test',
        name: 'test',
        schema: {label: 'label', type: 'not-existing', onInvalid: 'ignore'},
        schemaPath: '/test',
    });

    expect(container).toBeEmptyDOMElement();
});

test('Call onFocus callback when Field gets focus', () => {
    const Text: any = jest.fn(() => <input type="text" />);
    fieldRegistry.get.mockReturnValue(Text);

    const formInspector = createFormInspector();
    const initialProps = {
        data: {},
        dataPath: '/title',
        formInspector,
        name: 'test',
        onChange: jest.fn(),
        onFinish: jest.fn(),
        onSuccess: undefined,
        router: undefined,
        schema: {label: 'label', type: 'text'},
        schemaPath: '/schema/title',
        value: 'test value',
    };

    const {rerender} = renderField(initialProps);

    const target = new EventTarget();
    const dispatchEventSpy = jest.spyOn(target, 'dispatchEvent');

    getLatestComponentProps(Text).onFocus(target);

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

    const onChangeMock = jest.fn();
    rerender(
        <Field
            {...initialProps}
            onChange={onChangeMock}
        />
    );

    dispatchedEvent.detail.setValue('new value');
    expect(onChangeMock).toHaveBeenCalledWith('test', 'new value', undefined);
});
