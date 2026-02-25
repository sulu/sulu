// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import MissingTypeDialog from '../MissingTypeDialog';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Should render a Dialog', () => {
    const types = {
        homepage: {key: 'homepage', title: 'Homepage'},
    };

    render(
        <MissingTypeDialog
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            types={types}
        />
    );

    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeDisabled();
});

test('Should call onCancel callback if user chooses not to change type', async() => {
    const user = userEvent.setup();
    const cancelSpy = jest.fn();
    const types = {
        homepage: {key: 'homepage', title: 'Homepage'},
    };

    render(
        <MissingTypeDialog
            onCancel={cancelSpy}
            onConfirm={jest.fn()}
            open={true}
            types={types}
        />
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
        <MissingTypeDialog
            onCancel={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            types={types}
        />
    );

    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeDisabled();
    await user.click(screen.getByRole('button', {name: /sulu_admin\.please_choose/}));
    await user.click(screen.getByRole('button', {name: 'Homepage'}));

    await waitFor(() => {
        expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeEnabled();
    });

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(confirmSpy).toBeCalledWith('homepage');
});
