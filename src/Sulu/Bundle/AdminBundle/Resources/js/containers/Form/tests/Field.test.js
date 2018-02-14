// @flow
import {render, shallow} from 'enzyme';
import React from 'react';
import {observable} from 'mobx';
import Field from '../Field';
import fieldRegistry from '../registries/FieldRegistry';

jest.mock('../registries/FieldRegistry', () => ({
    get: jest.fn(),
}));

test('Render correct label with correct field type', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });
    expect(render(<Field name="test" onChange={jest.fn()} schema={{label: 'label1', type: 'text'}} />))
        .toMatchSnapshot();

    fieldRegistry.get.mockReturnValue(function DateTime() {
        return <input type="date" />;
    });
    expect(render(<Field name="test" onChange={jest.fn()} schema={{label: 'label2', type: 'datetime'}} />))
        .toMatchSnapshot();
});

test('Render a required field with correct field type', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });
    expect(render(<Field name="test" onChange={jest.fn()} schema={{label: 'label1', required: true, type: 'text'}} />))
        .toMatchSnapshot();
});

test('Pass correct props to FieldType', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="date" />;
    });

    const locale = observable('de');
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
            schema={schema}
            value="test"
        />
    );

    expect(field.find('Text').props()).toEqual(expect.objectContaining({
        locale: locale,
        maxOccurs: 4,
        minOccurs: 2,
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
        <Field schema={{label: 'label', type: 'text'}} locale={undefined} name="test" onChange={changeSpy} />
    );

    field.find('Text').simulate('change', 'test value');

    expect(changeSpy).toBeCalledWith('test', 'test value');
});
