// @flow
import React from 'react';
import userEvent from '@testing-library/user-event';
import {render, screen} from '@testing-library/react';
import MissingTypeDialog from '../MissingTypeDialog';

test('Should render a Dialog', () => {
    const types = {
        homepage: {key: 'homepage', title: 'Homepage'},
    };

    render(
        <MissingTypeDialog onCancel={jest.fn()} onConfirm={jest.fn()} open={true} types={types} />
    );

    expect(screen.getByText('sulu_admin.missing_type_dialog_title')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.cancel'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeDisabled();
});

test('Should call onCancel callback if user chooses not to change type', async() => {
    const user = userEvent.setup();
    const cancelSpy = jest.fn();
    const types = {
        homepage: {key: 'homepage', title: 'Homepage'},
    };

    render(
        <MissingTypeDialog onCancel={cancelSpy} onConfirm={jest.fn()} open={true} types={types} />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));

    expect(cancelSpy).toBeCalledWith();
});

test('Should call onConfirm callback with chosen type if user chooses to change type', async() => {
    const user = userEvent.setup();
    const confirmSpy = jest.fn();
    const types = {
        homepage: {key: 'homepage', title: 'Homepage'},
    };

    render(
        <MissingTypeDialog onCancel={jest.fn()} onConfirm={confirmSpy} open={true} types={types} />
    );

    const confirmButton = screen.getByRole('button', {name: 'sulu_admin.ok'});
    expect(confirmButton).toBeDisabled();
    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByText('Homepage'));
    expect(confirmButton).toBeEnabled();
    await user.click(confirmButton);

    expect(confirmSpy).toBeCalledWith('homepage');
});
