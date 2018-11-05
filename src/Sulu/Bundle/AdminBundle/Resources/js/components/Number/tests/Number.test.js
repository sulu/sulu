// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import Number from '../Number';

test('Number should render', () => {
    expect(render(<Number onChange={jest.fn()} value={undefined} />)).toMatchSnapshot();
});

test('Number should render when disabled', () => {
    expect(render(<Number disabled={true} onChange={jest.fn()} value={8} />)).toMatchSnapshot();
});

test('Number should call onChange with parsed value', () => {
    const onChange = jest.fn();
    const number = shallow(<Number onChange={onChange} value={undefined} />);

    const event = {};
    number.find('Input').simulate('change', '10.2', event);
});

test('Number should call onChange with undefined when value isn`t a float', () => {
    const onChange = jest.fn();
    const number = shallow(<Number onChange={onChange} value={undefined} />);

    const event = {};
    number.find('Input').simulate('change', 'xxx', event);

    expect(onChange).toBeCalledWith(undefined, event);
});

test('Number should call onChange with undefined when value is undefined', () => {
    const onChange = jest.fn();
    const number = shallow(<Number onChange={onChange} value={undefined} />);

    const event = {};
    number.find('Input').simulate('change', undefined, event);

    expect(onChange).toBeCalledWith(undefined, event);
});
