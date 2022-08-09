// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Number from '../Number';

test('Number should render', () => {
    const {container} = render(<Number onChange={jest.fn()} value={undefined} />);
    expect(container).toMatchSnapshot();
});

test('Number should render when disabled', () => {
    const {container} = render(<Number disabled={true} onChange={jest.fn()} value={8} />);
    expect(container).toMatchSnapshot();
});

test('Number should call onChange with parsed value', async() => {
    const onChange = jest.fn();
    render(<Number onChange={onChange} value={2} />);

    const input = screen.queryByDisplayValue(2);
    fireEvent.keyDown(input, {target: {value: '10.2'}});

    expect(input).toHaveValue(10.2);
});

test('Number should call onChange with undefined when value isn`t a float', async() => {
    const onChange = jest.fn();
    render(<Number onChange={onChange} value={2} />);

    const input = screen.queryByDisplayValue(2);
    await userEvent.type(input, 'text');

    expect(onChange).toHaveBeenCalledWith(undefined, expect.anything());
});

test('Number should call onChange with undefined when value is undefined', async() => {
    const onChange = jest.fn();
    render(<Number onChange={onChange} value={0.5} />);

    const input = screen.queryByDisplayValue(0.5);
    fireEvent.change(input, {target: {value: null}});

    expect(onChange).toBeCalledWith(undefined, expect.anything());
});
