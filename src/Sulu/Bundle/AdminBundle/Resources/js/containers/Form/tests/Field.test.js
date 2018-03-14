// @flow
import {render, shallow} from 'enzyme';
import React from 'react';
import {observable} from 'mobx';
import Field from '../Field';
import fieldRegistry from '../registries/FieldRegistry';

jest.mock('../registries/FieldRegistry', () => ({
    get: jest.fn(),
    getOptions: jest.fn(),
}));

jest.mock('../../../utils', () => ({
    translate: (key) => key,
}));

test('Render correct label with correct field type', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });
    expect(render(
        <Field name="test" onChange={jest.fn()} onFinish={jest.fn()} schema={{label: 'label1', type: 'text'}} />
    )).toMatchSnapshot();

    fieldRegistry.get.mockReturnValue(function DateTime() {
        return <input type="date" />;
    });
    expect(render(
        <Field
            name="test"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schema={{label: 'label2', type: 'datetime'}}
        />
    )).toMatchSnapshot();
});

test('Render a required field with correct field type', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });
    expect(render(
        <Field
            name="test"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schema={{label: 'label1', required: true, type: 'text'}}
        />
    )).toMatchSnapshot();
});

test('Render a field with an error', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });
    expect(
        render(
            <Field
                error={{keyword: 'minLength', parameters: {}}}
                name="test"
                onChange={jest.fn()}
                onFinish={jest.fn()}
                schema={{label: 'label1', type: 'text'}}
            />
        )
    ).toMatchSnapshot();
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
    expect(
        render(
            <Field
                error={error}
                name="test"
                onChange={jest.fn()}
                onFinish={jest.fn()}
                schema={{label: 'label1', type: 'text'}}
            />
        )
    ).toMatchSnapshot();
});

test('Pass correct props to FieldType', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="date" />;
    });

    const locale = observable.box('de');
    const schema = {
        label: 'Text',
        maxOccurs: 4,
        minOccurs: 2,
        type: 'text_line',
        types: {},
    };
    const field = shallow(
        <Field
            locale={locale}
            name="text"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schema={schema}
            showAllErrors={true}
            value="test"
        />
    );

    expect(field.find('Text').props()).toEqual(expect.objectContaining({
        locale: locale,
        maxOccurs: 4,
        minOccurs: 2,
        showAllErrors: true,
        types: {},
        value: 'test',
    }));
});

test('Merge with options from fieldRegistry before passing props to FieldType', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });
    fieldRegistry.getOptions.mockReturnValue({
        option: 'value',
    });

    const locale = observable.box('de');
    const schema = {
        label: 'Text',
        maxOccurs: 4,
        minOccurs: 2,
        options: {
            anotherOption: 'anotherValue',
        },
        type: 'text_line',
        types: {},
    };
    const field = shallow(
        <Field
            locale={locale}
            name="text"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schema={schema}
            showAllErrors={true}
            value="test"
        />
    );

    expect(field.find('Text').props()).toEqual(expect.objectContaining({
        fieldOptions: {
            option: 'value',
        },
        locale: locale,
        maxOccurs: 4,
        minOccurs: 2,
        options: {
            anotherOption: 'anotherValue',
        },
        showAllErrors: true,
        types: {},
        value: 'test',
    }));
});

test('Call onChange callback when value of Field changes', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const changeSpy = jest.fn();
    const field = shallow(
        <Field
            locale={undefined}
            name="test"
            onChange={changeSpy}
            onFinish={jest.fn()}
            schema={{label: 'label', type: 'text'}}
        />
    );

    field.find('Text').simulate('change', 'test value');

    expect(changeSpy).toBeCalledWith('test', 'test value');
});

test('Call onFinish callback after editing the field has finished', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const finishSpy = jest.fn();
    const field = shallow(
        <Field
            locale={undefined}
            name="test"
            onChange={jest.fn()}
            onFinish={finishSpy}
            schema={{label: 'label', type: 'text'}}
        />
    );

    field.find('Text').simulate('finish');

    expect(finishSpy).toBeCalledWith('test');
});
