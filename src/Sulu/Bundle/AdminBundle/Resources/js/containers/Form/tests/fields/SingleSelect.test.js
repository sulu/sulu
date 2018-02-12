// @flow
import React from 'react';
import {shallow} from 'enzyme';
import SingleSelect from '../../fields/SingleSelect';

test('Pass props correctly to SingleSelect', () => {
    const options = {
        values: {
            'mr': 'Mister',
            'ms': 'Miss',
        },
    };
    const singleSelect = shallow(<SingleSelect onChange={jest.fn()} options={options} value="test" />);

    expect(singleSelect.prop('value')).toBe('test');
    expect(singleSelect.find('Option').at(0).props()).toEqual(expect.objectContaining({
        value: 'mr',
        children: 'Mister',
    }));
    expect(singleSelect.find('Option').at(1).props()).toEqual(expect.objectContaining({
        value: 'ms',
        children: 'Miss',
    }));
});

test('Set default value if no value is passed', () => {
    const options = {
        default_value: 'mr',
        values: {
            'mr': 'Mister',
            'ms': 'Miss',
        },
    };
    const singleSelect = shallow(<SingleSelect onChange={jest.fn()} options={options} value={undefined} />);

    expect(singleSelect.prop('value')).toBe('mr');
});

test('Throw error if no options are passed', () => {
    expect(() => shallow(<SingleSelect onChange={jest.fn()} value={undefined} />)).toThrow(/"values"/);
});

test('Throw error if no value option is passed', () => {
    expect(() => shallow(<SingleSelect onChange={jest.fn()} value={undefined} />)).toThrow(/"values"/);
});
