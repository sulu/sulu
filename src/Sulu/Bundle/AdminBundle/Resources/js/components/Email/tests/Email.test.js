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
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const onIconClickSpy = jest.fn();

    render(<Email onBlur={onBlur} onChange={onChange} onIconClick={onIconClickSpy} valid={false} value={null} />);

    const icon = screen.queryByLabelText('su-envelope');
    fireEvent.click(icon);

    expect(onIconClickSpy).not.toBeCalled();
});
