/* eslint-disable flowtype/require-valid-file-annotation */
import {render, shallow} from 'enzyme';
import React from 'react';
import Field from '../Field';
import fieldStore from '../stores/FieldStore';

jest.mock('../stores/FieldStore', () => ({
    get: jest.fn(),
}));

test('Render correct label with correct field type', () => {
    fieldStore.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });
    expect(render(<Field schema={{label: 'label1', type: 'text'}} />)).toMatchSnapshot();

    fieldStore.get.mockReturnValue(function DateTime() {
        return <input type="date" />;
    });
    expect(render(<Field schema={{label: 'label2', type: 'datetime'}} />)).toMatchSnapshot();
});

test('Call onChange callback when value of Field changes', () => {
    fieldStore.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    const changeSpy = jest.fn();
    const field = shallow(<Field schema={{label: 'label', type: 'text'}} name="test" onChange={changeSpy} />);

    field.find('Text').simulate('change', 'test value');

    expect(changeSpy).toBeCalledWith('test', 'test value');
});
