// @flow
import {render, shallow} from 'enzyme';
import React from 'react';
import ResourceStore from '../../../stores/ResourceStore';
import Router from '../../../services/Router';
import Field from '../Field';
import fieldRegistry from '../registries/fieldRegistry';
import FormInspector from '../FormInspector';
import ResourceFormStore from '../stores/ResourceFormStore';

jest.mock('../../../services/Router', () => jest.fn());
jest.mock('../../../stores/ResourceStore', () => jest.fn());
jest.mock('../FormInspector', () => jest.fn());
jest.mock('../stores/ResourceFormStore', () => jest.fn());

jest.mock('../registries/fieldRegistry', () => ({
    get: jest.fn(),
    getOptions: jest.fn(),
}));

jest.mock('../../../utils', () => ({
    translate: (key) => key,
}));

test('Render correct label with correct field type', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));
    const successSpy = jest.fn();

    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });
    expect(render(
        <Field
            dataPath=""
            formInspector={formInspector}
            name="test"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSuccess={successSpy}
            router={undefined}
            schema={{label: 'label1', type: 'text', visible: true}}
            schemaPath=""
        />
    )).toMatchSnapshot();

    fieldRegistry.get.mockReturnValue(function DateTime() {
        return <input type="date" />;
    });
    expect(render(
        <Field
            dataPath=""
            formInspector={formInspector}
            name="test"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSuccess={successSpy}
            router={undefined}
            schema={{label: 'label2', type: 'datetime', visible: true}}
            schemaPath=""
        />
    )).toMatchSnapshot();
});

test('Render field with correct values for grid', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });
    expect(render(
        <Field
            dataPath=""
            formInspector={formInspector}
            name="test"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={{label: 'label1', type: 'text', colSpan: 8, spaceAfter: 3, visible: true}}
            schemaPath=""
        />
    )).toMatchSnapshot();
});

test('Render a required field with correct field type', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });
    expect(render(
        <Field
            dataPath=""
            formInspector={formInspector}
            name="test"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={{label: 'label1', required: true, type: 'text', visible: true}}
            schemaPath=""
        />
    )).toMatchSnapshot();
});

test('Render a field without a label', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    expect(render(
        <Field
            dataPath=""
            formInspector={formInspector}
            name="test"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={{type: 'text', visible: true}}
            schemaPath=""
        />
    )).toMatchSnapshot();
});

test('Render a field with a description', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    expect(render(
        <Field
            dataPath=""
            formInspector={formInspector}
            name="test"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={{
                description: 'Small description describing the field',
                label: 'label1',
                type: 'text',
                visible: true,
            }}
            schemaPath=""
        />
    )).toMatchSnapshot();
});

test('Render a field with an error', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });
    expect(
        render(
            <Field
                dataPath=""
                error={{keyword: 'minLength', parameters: {}}}
                formInspector={formInspector}
                name="test"
                onChange={jest.fn()}
                onFinish={jest.fn()}
                onSuccess={undefined}
                router={undefined}
                schema={{label: 'label1', type: 'text', visible: true}}
                schemaPath=""
            />
        )
    ).toMatchSnapshot();
});

test('Render a field without a const error', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });
    expect(
        render(
            <Field
                dataPath=""
                error={{keyword: 'const', parameters: {}}}
                formInspector={formInspector}
                name="test"
                onChange={jest.fn()}
                onFinish={jest.fn()}
                onSuccess={undefined}
                router={undefined}
                schema={{label: 'label1', type: 'text', visible: true}}
                schemaPath=""
            />
        )
    ).toMatchSnapshot();
});

test('Render a field with a error collection', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });
    const error = {
        ids: {
            keyword: 'minItems',
            parameters: {},
        },
    };
    expect(
        render(
            <Field
                dataPath=""
                error={error}
                formInspector={formInspector}
                name="test"
                onChange={jest.fn()}
                onFinish={jest.fn()}
                onSuccess={undefined}
                router={undefined}
                schema={{label: 'label1', type: 'text', visible: true}}
                schemaPath=""
            />
        )
    ).toMatchSnapshot();
});

test('Pass correct props to FieldType', () => {
    const router = new Router();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));
    const successSpy = jest.fn();

    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="date" />;
    });

    const schema = {
        label: 'Text',
        maxOccurs: 4,
        minOccurs: 2,
        type: 'text_line',
        types: {},
        visible: true,
    };
    const field = shallow(
        <Field
            dataPath="/block/0/text"
            formInspector={formInspector}
            name="text"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSuccess={successSpy}
            router={router}
            schema={schema}
            schemaPath="/text"
            showAllErrors={true}
            value="test"
        />
    );

    expect(field.find('Text').props()).toEqual(expect.objectContaining({
        dataPath: '/block/0/text',
        disabled: undefined,
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

test('Pass disabled flag to disabled FieldType', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="date" />;
    });

    const schema = {
        disabled: true,
        label: 'Text',
        maxOccurs: 4,
        minOccurs: 2,
        type: 'text_line',
        types: {},
        visible: true,
    };
    const field = shallow(
        <Field
            dataPath="/block/0/text"
            formInspector={formInspector}
            name="text"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath="/text"
            showAllErrors={true}
            value="test"
        />
    );

    expect(field.find('Text').props()).toEqual(expect.objectContaining({
        dataPath: '/block/0/text',
        disabled: true,
        formInspector,
        label: 'Text',
        maxOccurs: 4,
        minOccurs: 2,
        schemaPath: '/text',
        showAllErrors: true,
        types: {},
        value: 'test',
    }));
});

test('Merge with options from fieldRegistry before passing props to FieldType', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });
    fieldRegistry.getOptions.mockReturnValue({
        option: 'value',
    });

    const schema = {
        label: 'Text',
        maxOccurs: 4,
        minOccurs: 2,
        options: {
            anotherOption: {value: 'anotherValue'},
        },
        type: 'text_line',
        types: {},
        visible: true,
    };
    const field = shallow(
        <Field
            dataPath=""
            formInspector={formInspector}
            name="text"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            showAllErrors={true}
            value="test"
        />
    );

    expect(field.find('Text').props()).toEqual(expect.objectContaining({
        fieldTypeOptions: {
            option: 'value',
        },
        maxOccurs: 4,
        minOccurs: 2,
        schemaOptions: {
            anotherOption: {value: 'anotherValue'},
        },
        showAllErrors: true,
        types: {},
        value: 'test',
    }));
});

test('Call onChange callback when value of Field changes', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const changeSpy = jest.fn();
    const field = shallow(
        <Field
            dataPath=""
            formInspector={formInspector}
            name="test"
            onChange={changeSpy}
            onFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={{label: 'label', type: 'text', visible: true}}
            schemaPath=""
        />
    );

    field.find('Text').simulate('change', 'test value');

    expect(changeSpy).toBeCalledWith('test', 'test value');
});

test('Do not call onChange callback when value of disabled Field changes', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const changeSpy = jest.fn();
    const field = shallow(
        <Field
            dataPath=""
            formInspector={formInspector}
            name="test"
            onChange={changeSpy}
            onFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={{label: 'label', type: 'text', disabled: true}}
            schemaPath=""
        />
    );

    field.find('Text').simulate('change', 'test value');

    expect(changeSpy).not.toBeCalled();
});

test('Call onFinish callback after editing the field has finished', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const finishSpy = jest.fn();
    const field = shallow(
        <Field
            dataPath="/block/0/test"
            formInspector={formInspector}
            name="test"
            onChange={jest.fn()}
            onFinish={finishSpy}
            onSuccess={undefined}
            router={undefined}
            schema={{label: 'label', type: 'text', visible: true}}
            schemaPath="/test"
        />
    );

    field.find('Text').simulate('finish');

    expect(finishSpy).toBeCalledWith('/block/0/test', '/test');
});

test('Call onSuccess callback when field calls onSuccess', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));
    const successSpy = jest.fn();
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const finishSpy = jest.fn();
    const field = shallow(
        <Field
            dataPath="/block/0/test"
            formInspector={formInspector}
            name="test"
            onChange={jest.fn()}
            onFinish={finishSpy}
            onSuccess={successSpy}
            router={undefined}
            schema={{label: 'label', type: 'text', visible: true}}
            schemaPath="/test"
        />
    );

    field.find('Text').simulate('success');

    expect(successSpy).toBeCalled();
});

test('Do not render anything if field does not exist and onInvalid is set to ignore', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    fieldRegistry.get.mockImplementation(() => {
        throw new Error();
    });

    const field = shallow(
        <Field
            dataPath="/test"
            formInspector={formInspector}
            name="test"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={{label: 'label', type: 'not-existing', onInvalid: 'ignore'}}
            schemaPath="/test"
        />
    );

    expect(field.isEmptyRender()).toEqual(true);
});
