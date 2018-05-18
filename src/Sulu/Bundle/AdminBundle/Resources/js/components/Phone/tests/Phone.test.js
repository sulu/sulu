// @flow
import React from 'react';
import {render, mount} from 'enzyme';
import Phone from '../Phone';

test('Phone should render', () => {
    const onChange = jest.fn();
    expect(render(<Phone onChange={onChange} value={null} />)).toMatchSnapshot();
});

test('Phone should render with placeholder', () => {
    const onChange = jest.fn();
    expect(render(<Phone onChange={onChange} placeholder="My placeholder" value={null} />)).toMatchSnapshot();
});

test('Phone should render with value', () => {
    const onChange = jest.fn();
    const value = 'test@test.com';
    expect(render(<Phone onChange={onChange} value={value} />)).toMatchSnapshot();
});

test('Phone should render null value as empty string', () => {
    const onChange = jest.fn();
    expect(render(<Phone onChange={onChange} value={null} />)).toMatchSnapshot();
});

test('Phone should render error', () => {
    const onChange = jest.fn();
    expect(render(<Phone onChange={onChange} valid={false} value={null} />)).toMatchSnapshot();
});

test('Phone should trigger callbacks correctly', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const phone = mount(<Phone onBlur={onBlur} onChange={onChange} value={null} />);

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
    const phone = mount(<Phone onBlur={onBlur} onChange={onChange} value={null} />);

    expect(phone.find('Input').prop('onIconClick')).toBeUndefined();
});

test('Phone should set onIconClick when value is set', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const phone = mount(<Phone onBlur={onBlur} onChange={onChange} value={'+123'} />);

    expect(phone.find('Input').prop('onIconClick')).toBeInstanceOf(Function);
});

test('Phone should set onIconClick when value is valid and window should be opened', () => {
    window.location.assign = jest.fn();

    const onChange = jest.fn();
    const onBlur = jest.fn();
    const email = mount(<Phone onBlur={onBlur} onChange={onChange} value={'+123'} />);

    const onIconClickFunction = email.find('Input').prop('onIconClick');
    expect(onIconClickFunction).toBeInstanceOf(Function);
    onIconClickFunction.call();
    expect(window.location.assign).toBeCalledWith('tel:+123');
});
