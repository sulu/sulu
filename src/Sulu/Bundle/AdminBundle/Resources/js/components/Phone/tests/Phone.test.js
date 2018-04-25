// @flow
import React from 'react';
import {render, mount} from 'enzyme';
import Phone from '../Phone';

test('Phone should render', () => {
    const onChange = jest.fn();
    expect(mount(<Phone value={null} onChange={onChange} />)).toMatchSnapshot();
});

test('Phone should render with placeholder', () => {
    const onChange = jest.fn();
    expect(render(<Phone value={null} placeholder="My placeholder" onChange={onChange} />)).toMatchSnapshot();
});

test('Phone should render with value', () => {
    const onChange = jest.fn();
    const value = 'test@test.com';
    expect(render(<Phone value={value} onChange={onChange} />)).toMatchSnapshot();
});

test('Phone should render null value as empty string', () => {
    const onChange = jest.fn();
    expect(render(<Phone value={null} onChange={onChange} />)).toMatchSnapshot();
});

test('Phone should render error', () => {
    const onChange = jest.fn();
    expect(render(<Phone value={null} onChange={onChange} valid={false} />)).toMatchSnapshot();
});

test('Phone should trigger callbacks correctly', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const phone = mount(<Phone value={null} onChange={onChange} onBlur={onBlur} />);

    phone.find('Input').instance().props.onChange('+123', {target: {value: '+123'}});
    phone.find('Input').instance().props.onBlur();
    phone.update();
    expect(onChange).toBeCalledWith('+123', {target: {value: '+123'}});
    expect(onBlur).toBeCalled();

    expect(onBlur).toHaveBeenCalledTimes(1);
    expect(onChange).toHaveBeenCalledTimes(1);
});

test('Phone should not set onIconClick when value is not set', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const phone = mount(<Phone value={null} onChange={onChange} onBlur={onBlur} />);

    expect(phone.find('Input').prop('onIconClick')).toBeUndefined();
});

test('Phone should set onIconClick when value is set', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const phone = mount(<Phone value={'+123'} onChange={onChange} onBlur={onBlur} />);

    expect(phone.find('Input').prop('onIconClick')).toBeInstanceOf(Function);
});

test('Phone should set onIconClick when value is valid and window should be opened', () => {
    global.window.location.assign = jest.fn();

    const onChange = jest.fn();
    const onBlur = jest.fn();
    const email = mount(<Phone value={'+123'} onChange={onChange} onBlur={onBlur} />);

    const onIconClickFunction = email.find('Input').prop('onIconClick');
    expect(onIconClickFunction).toBeInstanceOf(Function);
    onIconClickFunction.call();
    expect(global.window.location.assign).toBeCalledWith('tel:+123');
});