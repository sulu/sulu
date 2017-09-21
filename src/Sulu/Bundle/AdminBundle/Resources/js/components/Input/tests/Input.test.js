/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, shallow} from 'enzyme';
import Input from '../Input';

test('Input should render', () => {
    const onChange = () => {};
    expect(render(<Input onChange={onChange} />)).toMatchSnapshot();
});

test('Input should render with icon', () => {
    const onChange = () => {};
    expect(render(<Input icon="my-icon" onChange={onChange} />)).toMatchSnapshot();
});

test('Input should render with type', () => {
    const onChange = () => {};
    expect(render(<Input type="password" onChange={onChange} />)).toMatchSnapshot();
});

test('Input should render with placeholder', () => {
    const onChange = () => {};
    expect(render(<Input placeholder="My placeholder" onChange={onChange} />)).toMatchSnapshot();
});

test('Input should render with value', () => {
    const onChange = () => {};
    expect(render(<Input value="My value" onChange={onChange} />)).toMatchSnapshot();
});

test('Input should render null value as empty string', () => {
    const onChange = () => {};
    expect(render(<Input value={null} onChange={onChange} />)).toMatchSnapshot();
});

test('Input should call the callback when the input changes', () => {
    const onChange = jest.fn();
    const input = shallow(<Input value="My value" onChange={onChange} />);
    input.find('input').simulate('change', {currentTarget: {value: 'my-value'}});
    expect(onChange).toHaveBeenCalledWith('my-value');
});
