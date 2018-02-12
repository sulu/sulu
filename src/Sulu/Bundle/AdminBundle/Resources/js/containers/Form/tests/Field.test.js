// @flow
import {render, shallow} from 'enzyme';
import React from 'react';
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

test('Pass correct props to FieldTypes', () => {
    const fieldType = jest.fn();
    fieldRegistry.get.mockReturnValue(fieldType);

    const value = 7;
    const schema = {
        label: '',
        type: 'test',
        options: {
            defaultValue: 3,
        },
    };

    const field = shallow(<Field name="test" onChange={jest.fn()} schema={schema} value={value} />);

    expect(field.find(fieldType).props()).toEqual(expect.objectContaining({
        value,
        options: schema.options,
    }));
});

test('Call onChange callback when value of Field changes', () => {
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const changeSpy = jest.fn();
    const field = shallow(<Field schema={{label: 'label', type: 'text'}} name="test" onChange={changeSpy} />);

    field.find('Text').simulate('change', 'test value');

    expect(changeSpy).toBeCalledWith('test', 'test value');
});
