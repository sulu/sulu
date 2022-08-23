// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ColorPicker from '../ColorPicker';
import bindValueToOnChange from '../../../utils/TestHelper/bindValueToOnChange';

test('ColorPicker should render', async() => {
    const {baseElement} = render(<ColorPicker onChange={jest.fn()} placeholder="My placeholder" value="#abc" />);

    const icon = screen.queryByLabelText('su-square');

    await userEvent.click(icon);
    expect(baseElement).toMatchSnapshot();
});

test('ColorPicker should disable Input when disabled', async() => {
    render(<ColorPicker
        disabled={true}
        onChange={jest.fn()}
        value="#abc"
    />);

    const input = screen.queryByDisplayValue('#abc');
    const icon = screen.queryByLabelText('su-square');
    await userEvent.click(icon);

    expect(input).toBeDisabled();
});

test('ColorPicker should render error', () => {
    const {container} = render(<ColorPicker onChange={jest.fn()} valid={false} value="#abc" />);
    // eslint-disable-next-line testing-library/no-container
    expect(container.querySelector('.error')).toBeInTheDocument();
});

test('ColorPicker should show error when invalid value is set', async() => {
    const onChange = jest.fn();
    render(<ColorPicker onChange={onChange} value="#abc" />);

    const input = screen.queryByDisplayValue('#abc');

    await userEvent.type(input, 'xxx');

    expect(onChange).toHaveBeenCalledWith(undefined);

    await userEvent.type(input, '#ccc');

    expect(input).toBeValid();
});

test('ColorPicker should trigger callbacks correctly', async() => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    render(bindValueToOnChange(<ColorPicker onBlur={onBlur} onChange={onChange} value="#abc" />));

    const input = screen.queryByDisplayValue('#abc');

    // provide invalid value
    await userEvent.clear(input);
    await userEvent.type(input, 'xxx');
    expect(onChange).toBeCalledWith(undefined);

    // provide one more invalid value
    await userEvent.clear(input);
    await userEvent.type(input, 'abc');
    expect(onChange).toBeCalledWith(undefined);

    // now add a valid value
    await userEvent.clear(input);
    await userEvent.type(input, '#abc');
    expect(onChange).toBeCalledWith('#abc');

    await userEvent.tab(); // tab away from input
    expect(onBlur).toBeCalled();
});

test('ColorPicker should call the correct callbacks when value from overlay was selected', async() => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    render((<ColorPicker onBlur={onBlur} onChange={onChange} value="#abc" />));

    const icon = screen.queryByLabelText('su-square');
    await userEvent.click(icon);

    const overlayInput = screen.queryByDisplayValue('AABBCC');
    await userEvent.clear(overlayInput);
    await userEvent.type(overlayInput, 'cccccc');

    // wait for "react-color" component to fire callback: https://github.com/casesandberg/react-color/issues/516
    await new Promise((resolve) => setTimeout(resolve, 100));

    expect(overlayInput).toHaveValue('cccccc');
    expect(onChange).toBeCalledWith('#cccccc');
});
