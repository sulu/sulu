// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import Email from '../Email';

test('Email should render', () => {
    const onChange = jest.fn();
    const {container} = render(<Email onChange={onChange} value={null} />);

    expect(container).toMatchSnapshot();
});

test('Email should render with placeholder', () => {
    const onChange = jest.fn();
    const {container} = render(<Email onChange={onChange} placeholder="My placeholder" value={null} />);

    expect(container).toMatchSnapshot();
});

test('Email should render with value', () => {
    const onChange = jest.fn();
    const value = 'test@test.com';
    const {container} = render(<Email onChange={onChange} value={value} />);

    expect(container).toMatchSnapshot();
});

test('Email should render when disabled', () => {
    const onChange = jest.fn();
    const value = 'test@test.com';
    const {container} = render(<Email disabled={true} onChange={onChange} value={value} />);

    expect(container).toMatchSnapshot();
});

test('Email should render null value as empty string', () => {
    const onChange = jest.fn();
    const {container} = render(<Email onChange={onChange} value={null} />);

    expect(container).toMatchSnapshot();
});

test('Email should render error', () => {
    const onChange = jest.fn();
    const {container} = render(<Email onChange={onChange} valid={false} value={null} />);

    expect(container).toMatchSnapshot();
});

test('Email should not set onIconClick when value is invalid', () => {
    delete window.location;
    window.location = {assign: jest.fn()};

    const onChange = jest.fn();
    const onBlur = jest.fn();

    render(<Email onBlur={onBlur} onChange={onChange} valid={false} value={null} />);

    const icon = screen.queryByLabelText('su-envelope');
    fireEvent.click(icon);

    expect(window.location.assign).not.toBeCalled();
});

test('Email should set onIconClick when value is valid and window should be opened', () => {
    delete window.location;
    window.location = {assign: jest.fn()};

    const onChange = jest.fn();
    const onBlur = jest.fn();

    render(<Email onBlur={onBlur} onChange={onChange} valid={true} value="abc@abc.abc" />);

    const icon = screen.queryByLabelText('su-envelope');
    fireEvent.click(icon);

    expect(window.location.assign).toBeCalledWith('mailto:abc@abc.abc');
});
