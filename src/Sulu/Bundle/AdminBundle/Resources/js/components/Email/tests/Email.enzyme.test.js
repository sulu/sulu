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

test('Email should not set onIconClick when value is invalid', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const email = mount(<Email onBlur={onBlur} onChange={onChange} valid={false} value={null} />);

    expect(email.find('Input').prop('onIconClick')).toBeUndefined();
});

test('Email should set onIconClick when value is valid and window should be opened', () => {
    delete window.location;
    window.location = {assign: jest.fn()};

    const onChange = jest.fn();
    const onBlur = jest.fn();
    const email = mount(<Email onBlur={onBlur} onChange={onChange} valid={true} value="abc@abc.abc" />);

    const onIconClickFunction = email.find('Input').prop('onIconClick');
    expect(onIconClickFunction).toBeInstanceOf(Function);
    onIconClickFunction.call();
    expect(window.location.assign).toBeCalledWith('mailto:abc@abc.abc');
});
