// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import bindValueToOnChange from 'sulu-admin-bundle/utils/TestHelper/bindValueToOnChange';
import Bic from '../../fields/Bic';

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: ({}: any),
    ...props,
});

test('Pass props correctly to Bic component', async() => {
    const user = userEvent.setup();
    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    render(
        bindValueToOnChange(<Bic {...createProps({onChange: changeSpy, onFinish: finishSpy})} />)
    );

    const input = screen.getByRole('textbox');
    expect(input).toHaveAttribute('id', '/');
    expect(input).toBeEnabled();
    expect(input.closest('div')).not.toHaveClass('error');

    await user.type(input, 'BIC123');
    expect(changeSpy).toHaveBeenLastCalledWith('BIC123');

    await user.tab();
    expect(finishSpy).toBeCalledWith();
});

test('Pass disabled prop to Bic component', () => {
    render(
        <Bic {...createProps({disabled: true})} />
    );

    expect(screen.getByRole('textbox')).toBeDisabled();
});

test('Pass id prop to Bic component', () => {
    render(
        <Bic {...createProps({dataPath: '/test'})} />
    );

    expect(screen.getByRole('textbox')).toHaveAttribute('id', '/test');
});

test('Pass error to Bic component', () => {
    render(
        <Bic {...createProps({error: {}})} />
    );

    expect(screen.getByRole('textbox').closest('div')).toHaveClass('error');
});

test('Pass value prop to Bic component', () => {
    render(
        <Bic {...createProps({value: 'Test'})} />
    );

    expect(screen.getByDisplayValue('Test')).toBeInTheDocument();
});
