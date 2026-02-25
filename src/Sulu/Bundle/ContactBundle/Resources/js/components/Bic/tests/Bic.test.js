// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import bindValueToOnChange from 'sulu-admin-bundle/utils/TestHelper/bindValueToOnChange';
import Bic from '../Bic';

test('Bic should pass default props', () => {
    const onChange = jest.fn();
    const {asFragment} = render(<Bic onChange={onChange} value={null} />);

    expect(asFragment()).toMatchSnapshot();
    expect(screen.getByRole('textbox')).toBeEnabled();
    expect(screen.getByRole('textbox')).toHaveAttribute('type', 'text');
    expect(screen.getByLabelText('su-earth')).toBeInTheDocument();
});

test('Bic should pass placeholder', () => {
    render(<Bic onChange={jest.fn()} placeholder="My placeholder" value={null} />);

    expect(screen.getByRole('textbox')).toHaveAttribute('placeholder', 'My placeholder');
});

test('Bic should pass value', () => {
    render(<Bic onChange={jest.fn()} value="BBBBCCLLXXX" />);

    expect(screen.getByDisplayValue('BBBBCCLLXXX')).toBeInTheDocument();
});

test('Bic should pass disabled', () => {
    render(<Bic disabled={true} onChange={jest.fn()} value="BBBBCCLLXXX" />);

    expect(screen.getByRole('textbox')).toBeDisabled();
});

test('Bic should pass invalid state', () => {
    render(<Bic onChange={jest.fn()} valid={false} value={null} />);

    expect(screen.getByRole('textbox').closest('div')).toHaveClass('error');
});

test('Bic should trigger callbacks correctly', async() => {
    const user = userEvent.setup();
    const onChange = jest.fn();
    const onBlur = jest.fn();
    render(bindValueToOnChange(<Bic onBlur={onBlur} onChange={onChange} value={null} />));

    const input = screen.getByRole('textbox');

    await user.type(input, 'BBBBCCLLXXX');
    expect(onChange).toHaveBeenLastCalledWith('BBBBCCLLXXX');

    await user.tab();
    expect(onBlur).toHaveBeenCalledTimes(1);
});
