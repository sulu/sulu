// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {observable} from 'mobx';
import ResourceStore from '../../../stores/ResourceStore';
import Router from '../../../services/Router';
import conditionDataProviderRegistry from '../registries/conditionDataProviderRegistry';
import Field from '../Field';
import fieldRegistry from '../registries/fieldRegistry';
import FormInspector from '../FormInspector';
import ResourceFormStore from '../stores/ResourceFormStore';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

beforeEach(() => {
    conditionDataProviderRegistry.clear();
    jest.clearAllMocks();
});

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

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

const createFormInspector = () => new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

const renderField = (props = {}) => {
    const defaultProps = {
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
    };

    return render(<Field {...(defaultProps: any)} {...(props: any)} />);
};

test('Render correct label with correct field type', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });
    const {asFragment: textFragment} = renderField();
    expect(textFragment()).toMatchSnapshot();

    fieldRegistry.get.mockReturnValue(function DateTime() {
        return <input type="date" />;
    });
    const {asFragment: dateFragment} = renderField({schema: {label: 'label2', type: 'datetime'}});
    expect(dateFragment()).toMatchSnapshot();
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

test('Render required field with correct field type', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const {asFragment} = renderField({
        schema: {label: 'label1', required: true, type: 'text'},
    });
    expect(asFragment()).toMatchSnapshot();
});

test('Render field without label', () => {
    const {asFragment} = renderField({schema: {type: 'text'}});
    expect(asFragment()).toMatchSnapshot();
});

test('Render field with description', () => {
    const {asFragment} = renderField({
        schema: {
            description: 'Small description describing the field',
            label: 'label1',
            type: 'text',
        },
    });
    expect(asFragment()).toMatchSnapshot();
});

test('Render field with error', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const {asFragment} = renderField({
        error: {keyword: 'minLength', parameters: {}},
    });
    expect(asFragment()).toMatchSnapshot();
});

test('Render field without const error', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const {asFragment} = renderField({
        error: {keyword: 'const', parameters: {}},
    });
    expect(asFragment()).toMatchSnapshot();
});

test('Render field with error collection', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const {asFragment} = renderField({
        error: {
            ids: {
                keyword: 'minItems',
                parameters: {},
            },
        },
    });
    expect(asFragment()).toMatchSnapshot();
});

test('Pass correct props to FieldType', () => {
    const router = new Router();
    const successSpy = jest.fn();
    const TextMock = jest.fn(() => null);
    fieldRegistry.get.mockReturnValue(TextMock);

    const schema = {
        label: 'Text',
        maxOccurs: 4,
        minOccurs: 2,
        type: 'text_line',
        types: {},
    };
    const data = {title: 'Test'};
    const formInspector = createFormInspector();

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

    expect(getLatestMockProps(TextMock)).toEqual(expect.objectContaining({
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

test('Do not render if visibleCondition evaluates false, and render after data change', () => {
    const TextMock = jest.fn(() => null);
    fieldRegistry.get.mockReturnValue(TextMock);
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

    expect(TextMock).toHaveBeenCalledTimes(0);
    act(() => {
        data.title = 'Changed title!';
    });
    expect(TextMock).toHaveBeenCalledTimes(1);
});

test('Render field if visibleCondition with conditionDataProvider evaluates true', () => {
    conditionDataProviderRegistry.add((data) => ({__test: data.test}));
    const TextMock = jest.fn(() => null);
    fieldRegistry.get.mockReturnValue(TextMock);

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

    expect(TextMock).toHaveBeenCalledTimes(1);
});

test('Pass disabled flag to FieldType when disabledCondition evaluates true', () => {
    const TextMock = jest.fn(() => null);
    fieldRegistry.get.mockReturnValue(TextMock);
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

    expect(getLatestMockProps(TextMock).disabled).toEqual(true);

    act(() => {
        data.title = 'Change title!';
    });
    expect(getLatestMockProps(TextMock).disabled).toEqual(false);
});

test('Pass disabled flag to FieldType when disabledCondition with conditionDataProvider evaluates true', () => {
    conditionDataProviderRegistry.add((data) => ({__test: data.test}));
    const TextMock = jest.fn(() => null);
    fieldRegistry.get.mockReturnValue(TextMock);

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

    expect(getLatestMockProps(TextMock).disabled).toEqual(true);
});

test('Merge options from fieldRegistry before passing props to FieldType', () => {
    const TextMock = jest.fn(() => null);
    fieldRegistry.get.mockReturnValue(TextMock);
    fieldRegistry.getOptions.mockReturnValue({option: 'value'});

    renderField({
        name: 'text',
        schema: {
            label: 'Text',
            maxOccurs: 4,
            minOccurs: 2,
            options: {
                anotherOption: {name: 'anotherOption', value: 'anotherValue'},
            },
            type: 'text_line',
            types: {},
        },
        showAllErrors: true,
        value: 'test',
    });

    expect(getLatestMockProps(TextMock)).toEqual(expect.objectContaining({
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

test('Call onChange callback when value changes', () => {
    const TextMock = jest.fn(() => null);
    fieldRegistry.get.mockReturnValue(TextMock);
    const changeSpy = jest.fn();

    renderField({
        name: 'test',
        onChange: changeSpy,
        schema: {label: 'label', type: 'text'},
    });

    getLatestMockProps(TextMock).onChange('test value', {isDefaultValue: true});
    expect(changeSpy).toBeCalledWith('test', 'test value', {isDefaultValue: true});
});

test('Do not call onChange callback when disabled field changes', () => {
    const TextMock = jest.fn(() => null);
    fieldRegistry.get.mockReturnValue(TextMock);
    const changeSpy = jest.fn();

    renderField({
        data: {title: 'Test'},
        name: 'test',
        onChange: changeSpy,
        schema: {label: 'label', type: 'text', disabledCondition: 'title == "Test"'},
    });

    getLatestMockProps(TextMock).onChange('test value');
    expect(changeSpy).not.toBeCalled();
});

test('Call onFinish callback after editing has finished', () => {
    const TextMock = jest.fn(() => null);
    fieldRegistry.get.mockReturnValue(TextMock);
    const finishSpy = jest.fn();

    renderField({
        dataPath: '/block/0/test',
        name: 'test',
        onFinish: finishSpy,
        schemaPath: '/test',
        schema: {label: 'label', type: 'text'},
    });

    getLatestMockProps(TextMock).onFinish('/block/0/test', '/test');
    expect(finishSpy).toBeCalledWith('/block/0/test', '/test');
    expect(finishSpy).toBeCalledWith('/block/0/test', '/test');
});

test('Call onSuccess callback when field calls onSuccess', () => {
    const TextMock = jest.fn(() => null);
    fieldRegistry.get.mockReturnValue(TextMock);
    const successSpy = jest.fn();

    renderField({
        onSuccess: successSpy,
        schema: {label: 'label', type: 'text'},
    });

    getLatestMockProps(TextMock).onSuccess();
    expect(successSpy).toBeCalled();
});

test('Do not render anything if field does not exist and onInvalid is ignore', () => {
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

test('Call onFocus callback when field gets focus', () => {
    const TextMock = jest.fn(() => null);
    fieldRegistry.get.mockReturnValue(TextMock);
    const onChangeMock = jest.fn();

    renderField({
        dataPath: '/title',
        name: 'test',
        onChange: onChangeMock,
        schema: {label: 'label', type: 'text'},
        schemaPath: '/schema/title',
        value: 'test value',
    });

    const target = new EventTarget();
    const dispatchEventSpy = jest.spyOn(target, 'dispatchEvent');

    getLatestMockProps(TextMock).onFocus(target);

    expect(dispatchEventSpy).toHaveBeenCalled();
    const dispatchedEvent = getLatestMockProps(dispatchEventSpy);

    expect(dispatchedEvent.type).toBe('sulu.focus');
    expect(dispatchedEvent.bubbles).toBe(true);
    expect(dispatchedEvent.detail).toEqual({
        schemaType: 'text',
        setValue: expect.any(Function),
        getValue: expect.any(Function),
        schemaPath: '/schema/title',
        dataPath: '/title',
        formInspector: expect.any(FormInspector),
    });
    expect(dispatchedEvent.detail.getValue()).toBe('test value');

    dispatchedEvent.detail.setValue('new value');
    expect(onChangeMock).toHaveBeenCalledWith('test', 'new value', undefined);
});
