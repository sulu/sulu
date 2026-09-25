// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import MissingTypeDialog from '../MissingTypeDialog';

jest.mock('../../../utils/Translator');

const types = {
    homepage: {key: 'homepage', title: 'Homepage'},
};

afterEach(() => {
    if (document.body) {
        document.body.innerHTML = '';
    }
});

test('Should render a Dialog', () => {
    render(<MissingTypeDialog onCancel={jest.fn()} onConfirm={jest.fn()} open={true} types={types} />);

    expect(screen.getByTestId('backdrop')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.missing_type_dialog_title')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.missing_type_dialog_description')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.please_choose')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeDisabled();
    expect(screen.getByRole('button', {name: 'sulu_admin.cancel'})).toBeInTheDocument();
});

test('Should call onCancel callback if user chooses not to change type', async() => {
    const user = userEvent.setup();
    const cancelSpy = jest.fn();

    render(<MissingTypeDialog onCancel={cancelSpy} onConfirm={jest.fn()} open={true} types={types} />);

    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));

    expect(cancelSpy).toHaveBeenCalledWith();
});

test('Should call onConfirm callback with chosen type if user chooses to change type', async() => {
    const user = userEvent.setup();
    const confirmSpy = jest.fn();

    render(<MissingTypeDialog onCancel={jest.fn()} onConfirm={confirmSpy} open={true} types={types} />);

    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeDisabled();

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByText('Homepage'));

    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeEnabled();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(confirmSpy).toHaveBeenCalledWith('homepage');
});
