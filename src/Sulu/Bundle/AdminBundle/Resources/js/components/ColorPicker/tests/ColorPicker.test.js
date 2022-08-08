// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import ColorPicker from '../ColorPicker';

test('ColorPicker should render', () => {
    const {container} = render(<ColorPicker onChange={jest.fn()} placeholder="My placeholder" value="#abc" />);

    const icon = screen.queryByLabelText('su-square');

    fireEvent.click(icon);
    expect(container).toMatchSnapshot();
});

test('ColorPicker should disable Input when disabled', () => {
    const onIconClickSpy = jest.fn();
    render(<ColorPicker
        disabled={true}
        onChange={jest.fn()}
        onIconClick={onIconClickSpy}
        value="#abc"
    />);

    const input = screen.queryByDisplayValue('#abc');
    const icon = screen.queryByLabelText('su-square');
    fireEvent.click(icon);

    expect(input).toBeDisabled();
    expect(onIconClickSpy).not.toHaveBeenCalled();
});

test('ColorPicker should render error', () => {
    const {container} = render(<ColorPicker onChange={jest.fn()} valid={false} value="#abc" />);
    // eslint-disable-next-line testing-library/no-container, testing-library/no-node-access
    expect(container.querySelector('.error')).toBeInTheDocument();
});

test('ColorPicker should show error when invalid value is set', () => {
    const onChange = jest.fn();
    render(<ColorPicker onChange={onChange} value="#abc" />);

    const input = screen.queryByDisplayValue('#abc');

    fireEvent.change(input, {target: {value: null}});
    fireEvent.blur(input);

    expect(onChange).toHaveBeenCalledWith(undefined);

    fireEvent.change(input, {target: {value: '#ccc'}});
    fireEvent.blur(input);

    expect(input).toBeValid();
});

test('ColorPicker should trigger callbacks correctly', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    render(<ColorPicker onBlur={onBlur} onChange={onChange} value="#abc" />);

    const input = screen.queryByDisplayValue('#abc');

    // provide invalid value
    fireEvent.change(input, {target: {value: null}});
    fireEvent.blur(input);
    expect(onChange).toBeCalledWith(undefined);
    expect(onBlur).toBeCalled();

    // provide one more invalid value
    fireEvent.change(input, {target: {value: 'abc'}});
    fireEvent.blur(input);
    expect(onChange).toBeCalledWith(undefined);
    expect(onBlur).toBeCalled();

    // now add a valid value
    fireEvent.change(input, {target: {value: '#abcabc'}});
    fireEvent.blur(input);
    expect(onChange).toBeCalledWith('#abcabc');
    expect(onBlur).toBeCalled();

    expect(onBlur).toHaveBeenCalledTimes(3);
});

test('ColorPicker should call the correct callbacks when value from overlay was selected', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const {baseElement} = render(<ColorPicker onBlur={onBlur} onChange={onChange} value="#abc" />);

    const icon = screen.queryByLabelText('su-square');
    fireEvent.click(icon);

    expect(baseElement).toMatchSnapshot();

    const sketchInput = screen.queryByDisplayValue('AABBCC');
    fireEvent.change(sketchInput, {target: {value: 'cccccc'}});
    fireEvent.blur(sketchInput);

    expect(onBlur).toBeCalled();
    expect(onChange).toBeCalledWith('cccccc');
    expect(sketchInput).toHaveValue('CCCCCC');
});
