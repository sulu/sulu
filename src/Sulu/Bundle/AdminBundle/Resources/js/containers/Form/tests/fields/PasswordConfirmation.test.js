// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import PasswordConfirmation from '../../fields/PasswordConfirmation';

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: ({}: any),
    ...props,
});

test('Pass error correctly to PasswordConfirmation component', async() => {
    const error = {keyword: 'required', parameters: {}};

    render(
        <PasswordConfirmation
            {...createProps()}
            error={error}
        />
    );

    await waitFor(() => {
        const [firstInput] = screen.getAllByDisplayValue('');
        expect(firstInput.parentElement).toHaveClass('error');
    });
});

test('Pass props correctly to PasswordConfirmation component', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    render(
        <PasswordConfirmation
            {...createProps()}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    const [firstInput, secondInput] = screen.getAllByDisplayValue('');

    await user.clear(firstInput);
    await user.type(firstInput, 'value');
    await user.clear(secondInput);
    await user.type(secondInput, 'value');

    await waitFor(() => {
        expect(changeSpy).toBeCalledWith('value');
    });
    expect(finishSpy).toBeCalledWith();
});
