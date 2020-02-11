// @flow
import React from 'react';
import {render, mount} from 'enzyme';
import Email from '../Email';

test('Email should render', () => {
    const onChange = jest.fn();
    expect(render(<Email onChange={onChange} value={null} />)).toMatchSnapshot();
});

test('Email should render with placeholder', () => {
    const onChange = jest.fn();
    expect(render(<Email onChange={onChange} placeholder="My placeholder" value={null} />)).toMatchSnapshot();
});

test('Email should render with value', () => {
    const onChange = jest.fn();
    const value = 'test@test.com';
    expect(render(<Email onChange={onChange} value={value} />)).toMatchSnapshot();
});

test('Email should render when disabled', () => {
    const onChange = jest.fn();
    const value = 'test@test.com';
    expect(render(<Email disabled={true} onChange={onChange} value={value} />)).toMatchSnapshot();
});

test('Email should render null value as empty string', () => {
    const onChange = jest.fn();
    expect(render(<Email onChange={onChange} value={null} />)).toMatchSnapshot();
});

test('Email should render error', () => {
    const onChange = jest.fn();
    expect(render(<Email onChange={onChange} valid={false} value={null} />)).toMatchSnapshot();
});

test('Email should render error when invalid value is set', () => {
    const onChange = jest.fn();
    const email = mount(<Email onChange={onChange} value={null} />);

    // check if showError is set correctly
    email.find('Input').instance().props.onChange('xxx', {target: {value: 'xxx'}});
    email.find('Input').instance().props.onBlur();
    email.update();
    expect(email.instance().showError).toBe(true);

    expect(render(email)).toMatchSnapshot();

    // now add a valid value
    email.find('Input').instance().props.onChange('test@test.com', {target: {value: 'test@test.com'}});
    email.find('Input').instance().props.onBlur();
    email.update();
    expect(email.instance().showError).toBe(false);

    expect(render(email)).toMatchSnapshot();
});

test('Email should trigger callbacks correctly', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const email = mount(<Email onBlur={onBlur} onChange={onChange} value={null} />);

    // provide invalid value
    email.find('Input').instance().props.onChange('xxx', {target: {value: 'xxx'}});
    email.find('Input').instance().props.onBlur();
    email.update();
    expect(onChange).toBeCalledWith(undefined);
    expect(onBlur).toBeCalled();

    // provide one more invalid value
    email.find('Input').instance().props.onChange('abc', {target: {value: 'abc'}});
    email.find('Input').instance().props.onBlur();
    email.update();
    expect(onChange).toBeCalledWith(undefined);
    expect(onBlur).toBeCalled();

    // now add a valid value
    email.find('Input').instance().props.onChange('test@test.com', {target: {value: 'test@test.com'}});
    email.find('Input').instance().props.onBlur();
    email.update();
    expect(onChange).toBeCalledWith('test@test.com');
    expect(onBlur).toBeCalled();

    expect(onBlur).toHaveBeenCalledTimes(3);
});

test('Email should not set onIconClick when value is invalid', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const email = mount(<Email onBlur={onBlur} onChange={onChange} value={null} />);

    expect(email.find('Input').prop('onIconClick')).toBeUndefined();
});

test('Email should set onIconClick when value is valid and window should be opened', () => {
    delete window.location;
    window.location = {assign: jest.fn()};

    const onChange = jest.fn();
    const onBlur = jest.fn();
    const email = mount(<Email onBlur={onBlur} onChange={onChange} value="abc@abc.abc" />);

    const onIconClickFunction = email.find('Input').prop('onIconClick');
    expect(onIconClickFunction).toBeInstanceOf(Function);
    onIconClickFunction.call();
    expect(window.location.assign).toBeCalledWith('mailto:abc@abc.abc');
});
