// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ColorPicker from '../ColorPicker';
import bindValueToOnChange from '../../../utils/TestHelper/bindValueToOnChange';

test('ColorPicker should render', async() => {
    const user = userEvent.setup();
    const {baseElement} = render(<ColorPicker onChange={jest.fn()} placeholder="My placeholder" value="#abc" />);

    const icon = screen.queryByLabelText('su-square');

    await user.click(icon);
    expect(baseElement).toMatchSnapshot();
});

test('ColorPicker should disable Input when disabled', async() => {
    const user = userEvent.setup();
    render(<ColorPicker
        disabled={true}
        onChange={jest.fn()}
        value="#abc"
    />);

    const input = screen.queryByDisplayValue('#abc');
    const icon = screen.queryByLabelText('su-square');
    await user.click(icon);

    expect(input).toBeDisabled();
});

test('ColorPicker should render error', () => {
    const {container} = render(<ColorPicker onChange={jest.fn()} valid={false} value="#abc" />);
    // eslint-disable-next-line testing-library/no-container
    expect(container.querySelector('.error')).toBeInTheDocument();
});

test('ColorPicker should show error when invalid value is set', async() => {
    const user = userEvent.setup();
    const onChange = jest.fn();
    render(<ColorPicker onChange={onChange} value="#abc" />);

    const input = screen.queryByDisplayValue('#abc');

    await user.type(input, 'xxx');

    expect(onChange).toHaveBeenCalledWith(undefined);

    await user.type(input, '#ccc');

    expect(input).toBeValid();
});

test('ColorPicker should trigger callbacks correctly', async() => {
    const user = userEvent.setup();
    const onChange = jest.fn();
    const onBlur = jest.fn();
    render(bindValueToOnChange(<ColorPicker onBlur={onBlur} onChange={onChange} value="#abc" />));

    const input = screen.queryByDisplayValue('#abc');

    // provide invalid value
    await user.clear(input);
    await user.type(input, 'xxx');
    expect(onChange).toHaveBeenCalledWith(undefined);

    // provide one more invalid value
    await user.clear(input);
    await user.type(input, 'abc');
    expect(onChange).toHaveBeenCalledWith(undefined);

    // now add a valid value
    await user.clear(input);
    await user.type(input, '#abc');
    expect(onChange).toHaveBeenCalledWith('#abc');

    await user.tab(); // tab away from input
    expect(onBlur).toHaveBeenCalled();
});

test('ColorPicker should call the correct callbacks when value from overlay was selected', async() => {
    const user = userEvent.setup();
    const onChange = jest.fn();
    const onBlur = jest.fn();
    render((<ColorPicker onBlur={onBlur} onChange={onChange} value="#abc" />));

    const icon = screen.queryByLabelText('su-square');
    await user.click(icon);

    const overlayInput = screen.queryByDisplayValue('AABBCC');
    await user.clear(overlayInput);
    await user.type(overlayInput, 'cccccc');

    expect(overlayInput).toHaveValue('cccccc');
    await waitFor(() => expect(onChange).toHaveBeenCalledWith('#cccccc'));
});
