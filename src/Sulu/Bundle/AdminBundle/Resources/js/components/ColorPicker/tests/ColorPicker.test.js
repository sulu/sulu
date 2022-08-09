// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ColorPicker from '../ColorPicker';

test('ColorPicker should render', async() => {
    const {container} = render(<ColorPicker onChange={jest.fn()} placeholder="My placeholder" value="#abc" />);

    const icon = screen.queryByLabelText('su-square');

    await userEvent.click(icon);
    expect(container).toMatchSnapshot();
});

test('ColorPicker should disable Input when disabled', async() => {
    const onIconClickSpy = jest.fn();
    render(<ColorPicker
        disabled={true}
        onChange={jest.fn()}
        onIconClick={onIconClickSpy}
        value="#abc"
    />);

    const input = screen.queryByDisplayValue('#abc');
    const icon = screen.queryByLabelText('su-square');
    await userEvent.click(icon);

    expect(input).toBeDisabled();
    expect(onIconClickSpy).not.toHaveBeenCalled();
});

test('ColorPicker should render error', () => {
    const {container} = render(<ColorPicker onChange={jest.fn()} valid={false} value="#abc" />);
    // eslint-disable-next-line testing-library/no-container, testing-library/no-node-access
    expect(container.querySelector('.error')).toBeInTheDocument();
});

test('ColorPicker should show error when invalid value is set', async() => {
    const onChange = jest.fn();
    render(<ColorPicker onChange={onChange} value="#abc" />);

    const input = screen.queryByDisplayValue('#abc');

    await userEvent.type(input, 'xxx');
    fireEvent.blur(input);

    expect(onChange).toHaveBeenCalledWith(undefined);

    await userEvent.type(input, '#ccc');
    fireEvent.blur(input);

    expect(input).toBeValid();
});

test('ColorPicker should trigger callbacks correctly', async() => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    render(<ColorPicker onBlur={onBlur} onChange={onChange} value="#abc" />);

    const input = screen.queryByDisplayValue('#abc');

    // provide invalid value
    await userEvent.type(input, 'xxx');
    fireEvent.blur(input);
    expect(onChange).toBeCalledWith(undefined);
    expect(onBlur).toBeCalled();

    // provide one more invalid value
    await userEvent.type(input, 'abc');
    fireEvent.blur(input);
    expect(onChange).toBeCalledWith(undefined);
    expect(onBlur).toBeCalled();

    // now add a valid value
    await userEvent.type(input, '#abc');
    fireEvent.blur(input);
    expect(onChange).toBeCalledWith('#abc');
    expect(onBlur).toBeCalled();

    expect(onBlur).toHaveBeenCalledTimes(3);
});

test('ColorPicker should call the correct callbacks when value from overlay was selected', async() => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const {baseElement} = render(<ColorPicker onBlur={onBlur} onChange={onChange} value="#abc" />);

    const icon = screen.queryByLabelText('su-square');
    await userEvent.click(icon);

    expect(baseElement).toMatchSnapshot();

    const sketchInput = screen.queryByDisplayValue('AABBCC');
    await userEvent.type(sketchInput, 'cccccc');
    fireEvent.blur(sketchInput);

    expect(onBlur).toBeCalled();
    expect(onChange).toBeCalledWith('cccccc');
    expect(sketchInput).toHaveValue('CCCCCC');
});
