// @flow
import React from 'react';
import {render, mount, shallow} from 'enzyme';
import Input from '../Input';

test('Input should render', () => {
    const onChange = jest.fn();
    expect(render(<Input onBlur={jest.fn()} onChange={onChange} value="My value" />)).toMatchSnapshot();
});

test('Input should render with invalid value', () => {
    const onChange = jest.fn();
    expect(render(<Input onBlur={jest.fn()} onChange={onChange} valid={false} value="My value" />)).toMatchSnapshot();
});

test('Input should render with icon', () => {
    const onChange = jest.fn();
    expect(render(<Input icon="su-pen" onBlur={jest.fn()} onChange={onChange} value="My value" />)).toMatchSnapshot();
});

test('Input should render with type', () => {
    const onChange = jest.fn();
    expect(render(
        <Input onBlur={jest.fn()} onChange={onChange} type="password" value="My value" />
    )).toMatchSnapshot();
});

test('Input should render with placeholder', () => {
    const onChange = jest.fn();
    expect(render(
        <Input onBlur={jest.fn()} onChange={onChange} placeholder="My placeholder" value="My value" />
    )).toMatchSnapshot();
});

test('Input should render with value', () => {
    const onChange = jest.fn();
    expect(render(<Input onBlur={jest.fn()} onChange={onChange} value="My value" />)).toMatchSnapshot();
});

test('Input should render null value as empty string', () => {
    const onChange = jest.fn();
    expect(render(<Input onBlur={jest.fn()} onChange={onChange} value={null} />)).toMatchSnapshot();
});

test('Input should call the callback when the input changes', () => {
    const onChange = jest.fn();
    const input = shallow(<Input onBlur={jest.fn()} onChange={onChange} value="My value" />);
    const event = {currentTarget: {value: 'my-value'}};
    input.find('input').simulate('change', event);
    expect(onChange).toHaveBeenCalledWith('my-value', event);
});

test('Input should call the callback with undefined if the input is removed', () => {
    const onChange = jest.fn();
    const input = shallow(<Input onBlur={jest.fn()} onChange={onChange} value="My value" />);
    const event = {currentTarget: {value: ''}};
    input.find('input').simulate('change', event);
    expect(onChange).toHaveBeenCalledWith(undefined, event);
});

test('Input should call the callback when icon was clicked', () => {
    const onChange = jest.fn();
    const handleIconClick = jest.fn();
    const input = mount(<Input icon="su-pen" onChange={onChange} onIconClick={handleIconClick} value="My value" />);
    input.find('Icon').simulate('click');
    expect(handleIconClick).toHaveBeenCalled();
});

test('Input should render with a loader', () => {
    const onChange = jest.fn();
    expect(render(<Input loading={true} onBlur={jest.fn()} onChange={onChange} value={null} />)).toMatchSnapshot();
});
