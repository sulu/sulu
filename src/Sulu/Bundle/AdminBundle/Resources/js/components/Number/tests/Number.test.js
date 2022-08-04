// @flow
import React from 'react';
import {render, fireEvent, screen} from '@testing-library/react';
import Number from '../Number';

test('Number should render', () => {
    const {container} = render(<Number onChange={jest.fn()} value={undefined} />);
    expect(container).toMatchSnapshot();
});

test('Number should render when disabled', () => {
    const {container} = render(<Number disabled={true} onChange={jest.fn()} value={8} />);
    expect(container).toMatchSnapshot();
});

test('Number should call onChange with parsed value', () => {
    const onChange = jest.fn();
    render(<Number onChange={onChange} value={2} />);

    const input = screen.queryByDisplayValue(2);
    fireEvent.keyDown(input, {target: {value: '10.2'}});

    expect(input).toHaveValue(10.2);
});

test('Number should call onChange with undefined when value isn`t a float', () => {
    const onChange = jest.fn();
    render(<Number onChange={onChange} value={2} />);

    const input = screen.queryByDisplayValue(2);
    fireEvent.change(input, {target: {value: 'text'}});

    expect(onChange).toHaveBeenCalledWith(undefined, expect.anything());
});

test('Number should call onChange with undefined when value is undefined', () => {
    const onChange = jest.fn();
    render(<Number onChange={onChange} value={2} />);

    const input = screen.queryByDisplayValue(2);
    fireEvent.change(input, {target: {value: undefined}});

    expect(onChange).toBeCalledWith(undefined, expect.anything());
});
