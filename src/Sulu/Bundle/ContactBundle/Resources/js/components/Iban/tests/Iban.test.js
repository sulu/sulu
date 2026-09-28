// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import Iban from '../Iban';

test('Iban should render', () => {
    const onChange = jest.fn();
    const {asFragment} = render(<Iban onChange={onChange} value={null} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Iban should render with placeholder', () => {
    const {asFragment} = render(<Iban onChange={jest.fn()} placeholder="My placeholder" value={null} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Iban should render with value', () => {
    const {asFragment} = render(<Iban onChange={jest.fn()} value="AT61 1904 3002 3457 3201" />);

    expect(asFragment()).toMatchSnapshot();
});

test('Iban should render when disabled', () => {
    const {asFragment} = render(<Iban disabled={true} onChange={jest.fn()} value="AT61 1904 3002 3457 3201" />);

    expect(asFragment()).toMatchSnapshot();
});

test('Iban should render error', () => {
    const {asFragment} = render(<Iban onChange={jest.fn()} valid={false} value={null} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Iban should trigger callbacks correctly', async() => {
    const user = userEvent.setup();
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const {rerender} = render(<Iban onBlur={onBlur} onChange={onChange} value={null} />);
    onChange.mockImplementation((value) => {
        rerender(<Iban onBlur={onBlur} onChange={onChange} value={value} />);
    });
    const input = screen.getByRole('textbox');

    // provide invalid value
    await user.type(input, 'xxx');
    await user.tab();
    expect(onChange).toHaveBeenCalledWith('xxx');
    expect(onBlur).toHaveBeenCalled();

    // provide one more invalid value
    await user.clear(input);
    await user.type(input, 'abc');
    await user.tab();
    expect(onChange).toHaveBeenCalledWith('abc');
    expect(onBlur).toHaveBeenCalled();

    // now add a valid value
    await user.clear(input);
    await user.type(input, 'AT611904300234573201');
    await user.tab();
    expect(onChange).toHaveBeenCalledWith('AT611904300234573201');
    expect(onBlur).toHaveBeenCalled();

    expect(onBlur).toHaveBeenCalledTimes(3);
});
