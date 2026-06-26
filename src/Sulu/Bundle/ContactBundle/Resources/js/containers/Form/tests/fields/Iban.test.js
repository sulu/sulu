// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import bindValueToOnChange from 'sulu-admin-bundle/utils/TestHelper/bindValueToOnChange';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import Iban from '../../fields/Iban';

test('Pass props correctly to Iban component', async() => {
    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    render(
        bindValueToOnChange(<Iban {...fieldTypeDefaultProps} onChange={changeSpy} onFinish={finishSpy} />)
    );

    const input = screen.getByRole('textbox');

    expect(input).toBeEnabled();
    expect(input).toHaveAttribute('id', '/');
    expect(input).toHaveValue('');

    await userEvent.type(input, 'Test');
    await userEvent.tab();

    expect(changeSpy).toHaveBeenLastCalledWith('Test');
    expect(finishSpy).toHaveBeenCalledWith();
});

test('Pass disabled prop to Iban component', () => {
    render(<Iban {...fieldTypeDefaultProps} disabled={true} />);

    expect(screen.getByRole('textbox')).toBeDisabled();
});

test('Pass id prop to Iban component', () => {
    render(<Iban {...fieldTypeDefaultProps} dataPath="/test" />);

    expect(screen.getByRole('textbox')).toHaveAttribute('id', '/test');
});

test('Pass error to Iban component', () => {
    render(<Iban {...fieldTypeDefaultProps} error={{}} />);

    expect(screen.getByRole('textbox').parentElement).toHaveClass('error');
});

test('Pass value prop to Iban component', () => {
    render(<Iban {...fieldTypeDefaultProps} value="Test" />);

    expect(screen.getByRole('textbox')).toHaveValue('Test');
});
