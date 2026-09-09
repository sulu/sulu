// @flow
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Mousetrap from 'mousetrap';
import React from 'react';
import Overlay from '../Overlay';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

afterEach(() => {
    if (Mousetrap.reset) {
        Mousetrap.reset();
    }
});

function renderOverlay(props = {}) {
    return render(
        <Overlay
            confirmText="Apply"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            title="My overlay title"
            {...props}
        >
            <p>My overlay content</p>
        </Overlay>
    );
}

test('The component should render in body when open', () => {
    const actions = [
        {title: 'Action 1', onClick: () => {}},
        {title: 'Action 2', onClick: () => {}},
    ];

    renderOverlay({
        actions,
        size: 'small',
    });

    expect(document.body).toMatchSnapshot();
});

test('The component should not render the footer where there is no onConfirm and no actions', () => {
    renderOverlay({
        actions: [],
        onConfirm: undefined,
        size: 'small',
    });

    expect(document.body).toMatchSnapshot();
});

test('The component should render with a disabled confirm button', () => {
    renderOverlay({confirmDisabled: true});

    expect(screen.getByRole('button', {name: 'Apply'})).toBeDisabled();
});

test('The component should render in body with loader instead of confirm button', () => {
    renderOverlay({confirmLoading: true});

    expect(screen.getByRole('button', {name: 'Apply'})).toBeDisabled();
});

test('The component should not render in body when closed', () => {
    renderOverlay({open: false});

    expect(screen.queryByText('My overlay title')).not.toBeInTheDocument();
});

test('The component should request to be closed when the close icon is clicked', async() => {
    const user = userEvent.setup();
    const closeSpy = jest.fn();

    renderOverlay({onClose: closeSpy});

    expect(closeSpy).not.toHaveBeenCalled();
    await user.click(screen.getByRole('button', {name: 'su-times'}));
    expect(closeSpy).toHaveBeenCalled();
});

test('The component should request to be closed when the esc key is pressed', () => {
    const closeSpy = jest.fn();
    renderOverlay({onClose: closeSpy});

    expect(closeSpy).not.toHaveBeenCalled();
    Mousetrap.trigger('esc');
    expect(closeSpy).toHaveBeenCalled();
});

test('The component should bind and unbind the esc key when overlay is opened and closed', () => {
    const closeSpy = jest.fn();
    const {rerender} = renderOverlay({onClose: closeSpy});

    expect(closeSpy).not.toHaveBeenCalled();
    Mousetrap.trigger('esc');
    expect(closeSpy).toHaveBeenCalled();
    closeSpy.mockReset();

    rerender(
        <Overlay
            confirmText="Apply"
            onClose={closeSpy}
            onConfirm={jest.fn()}
            open={false}
            title="My overlay title"
        >
            <p>My overlay content</p>
        </Overlay>
    );
    Mousetrap.trigger('esc');
    expect(closeSpy).not.toHaveBeenCalled();
    closeSpy.mockReset();

    rerender(
        <Overlay
            confirmText="Apply"
            onClose={closeSpy}
            onConfirm={jest.fn()}
            open={true}
            title="My overlay title"
        >
            <p>My overlay content</p>
        </Overlay>
    );
    Mousetrap.trigger('esc');
    expect(closeSpy).toHaveBeenCalled();
});

test('The component should call the callback when the confirm button is clicked', async() => {
    const user = userEvent.setup();
    const onConfirm = jest.fn();

    renderOverlay({
        confirmText: 'Alright mate!',
        onConfirm,
        title: 'My title',
    });

    expect(onConfirm).not.toHaveBeenCalled();
    await user.click(screen.getByRole('button', {name: 'Alright mate!'}));
    expect(onConfirm).toHaveBeenCalled();
});

test('The component should render with a warning', () => {
    renderOverlay({
        confirmText: 'Alright mate!',
        snackbarMessage: 'Something really strange happened',
        snackbarType: 'warning',
        title: 'My title',
    });

    expect(screen.getByRole('button', {name: /sulu_admin.warning/i})).toBeInTheDocument();
    expect(screen.getByText(/Something really strange happened/i)).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: /sulu_admin.error/i})).not.toBeInTheDocument();
});

test('The component should render with an error', () => {
    renderOverlay({
        confirmText: 'Alright mate!',
        snackbarMessage: 'Money transfer unsuccessful',
        snackbarType: 'error',
        title: 'My title',
    });

    expect(screen.getByRole('button', {name: /sulu_admin.error/i})).toBeInTheDocument();
    expect(screen.getByText(/Money transfer unsuccessful/i)).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: /sulu_admin.warning/i})).not.toBeInTheDocument();
});

test('The component should render with an error if type is unknown', () => {
    renderOverlay({
        confirmText: 'Alright mate!',
        snackbarMessage: 'Money transfer unsuccessful',
        title: 'My title',
    });

    expect(screen.getByRole('button', {name: /sulu_admin.error/i})).toBeInTheDocument();
    expect(screen.getByText(/Money transfer unsuccessful/i)).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: /sulu_admin.warning/i})).not.toBeInTheDocument();
});

test('The component should call the callback when the snackbar close button is clicked', async() => {
    const user = userEvent.setup();
    const onSnackbarCloseClick = jest.fn();

    renderOverlay({
        confirmText: 'Alright mate!',
        onSnackbarCloseClick,
        snackbarMessage: 'Money transfer unsuccessful',
        snackbarType: 'error',
        title: 'My title',
    });

    expect(onSnackbarCloseClick).not.toHaveBeenCalled();
    await user.click(within(screen.getByRole('button', {name: /sulu_admin.error/i}))
        .getByRole('button', {name: 'su-times'}));
    expect(onSnackbarCloseClick).toHaveBeenCalled();
});

test('The component should call the callback when the snackbar is clicked', async() => {
    const user = userEvent.setup();
    const onSnackbarClick = jest.fn();

    renderOverlay({
        confirmText: 'Alright mate!',
        onSnackbarClick,
        snackbarMessage: 'Something really strange happened',
        snackbarType: 'warning',
        title: 'My title',
    });

    expect(onSnackbarClick).not.toHaveBeenCalled();
    await user.click(screen.getByRole('button', {name: /sulu_admin.warning/i}));
    expect(onSnackbarClick).toHaveBeenCalled();
});

test('The component should render the toolbar between the header and the content', () => {
    renderOverlay({
        toolbar: <div data-testid="overlay-toolbar">toolbar content</div>,
    });

    expect(screen.getByTestId('overlay-toolbar')).toBeInTheDocument();
});

test('The component should not render a toolbar container when no toolbar is passed', () => {
    renderOverlay();

    expect(screen.queryByTestId('overlay-toolbar')).not.toBeInTheDocument();
});
