// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import Number from '../Number';

test('Number should render', () => {
    expect(render(<Number value={null} onChange={jest.fn()} />)).toMatchSnapshot();
});

test('Number should call onChange with parsed value', () => {
    const onChange = jest.fn();
    const number = shallow(<Number value={null} onChange={onChange} />);

    const event = {};
    number.find('Input').simulate('change', '10.2', event);
});

test('Number should call onChange with null when value isn`t a float', () => {
    const onChange = jest.fn();
    const number = shallow(<Number value={null} onChange={onChange} />);

    const event = {};
    number.find('Input').simulate('change', 'xxx', event);

    expect(onChange).toBeCalledWith(null, event);
});

test('Number should call onChange with null when value is null', () => {
    const onChange = jest.fn();
    const number = shallow(<Number value={null} onChange={onChange} />);

    const event = {};
    number.find('Input').simulate('change', null, event);

    expect(onChange).toBeCalledWith(null, event);
});
