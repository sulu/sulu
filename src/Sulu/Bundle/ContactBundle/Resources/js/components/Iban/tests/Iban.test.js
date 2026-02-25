// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import bindValueToOnChange from 'sulu-admin-bundle/utils/TestHelper/bindValueToOnChange';
import Iban from '../Iban';

test('Iban should pass default props', () => {
    const onChange = jest.fn();
    const {asFragment} = render(<Iban onChange={onChange} value={null} />);

    expect(asFragment()).toMatchSnapshot();
    expect(screen.getByRole('textbox')).toBeEnabled();
    expect(screen.getByRole('textbox')).toHaveAttribute('type', 'text');
    expect(screen.getByLabelText('su-credit-card')).toBeInTheDocument();
});

test('Iban should pass placeholder', () => {
    render(<Iban onChange={jest.fn()} placeholder="My placeholder" value={null} />);

    expect(screen.getByRole('textbox')).toHaveAttribute('placeholder', 'My placeholder');
});

test('Iban should pass value', () => {
    render(<Iban onChange={jest.fn()} value="AT61 1904 3002 3457 3201" />);

    expect(screen.getByDisplayValue('AT61 1904 3002 3457 3201')).toBeInTheDocument();
});

test('Iban should pass disabled', () => {
    render(<Iban disabled={true} onChange={jest.fn()} value="AT61 1904 3002 3457 3201" />);

    expect(screen.getByRole('textbox')).toBeDisabled();
});

test('Iban should pass invalid state', () => {
    render(<Iban onChange={jest.fn()} valid={false} value={null} />);

    expect(screen.getByRole('textbox').closest('div')).toHaveClass('error');
});

test('Iban should trigger callbacks correctly', async() => {
    const user = userEvent.setup();
    const onChange = jest.fn();
    const onBlur = jest.fn();
    render(bindValueToOnChange(<Iban onBlur={onBlur} onChange={onChange} value={null} />));

    const input = screen.getByRole('textbox');

    await user.type(input, 'AT611904300234573201');
    expect(onChange).toHaveBeenLastCalledWith('AT611904300234573201');

    await user.tab();
    expect(onBlur).toHaveBeenCalledTimes(1);
});
