// @flow
import Mousetrap from 'mousetrap';
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Overlay from '../Overlay';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

beforeEach(() => {
    Mousetrap.reset();
});

test('The component should render in body when open', () => {
    const actions = [
        {title: 'Action 1', onClick: () => {}},
        {title: 'Action 2', onClick: () => {}},
    ];

    render(
        <Overlay
            actions={actions}
            confirmText="Apply"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            size="small"
            title="My overlay title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(document.body).toMatchSnapshot();
});

test('The component should not render the footer where there is no onConfirm and no actions', () => {
    render(
        <Overlay
            actions={[]}
            confirmText="Apply"
            onClose={jest.fn()}
            onConfirm={undefined}
            open={true}
            size="small"
            title="My overlay title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(document.body).toMatchSnapshot();
});

test('The component should render with a disabled confirm button', () => {
    render(
        <Overlay
            confirmDisabled={true}
            confirmText="Apply"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            title="My overlay title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(screen.getByRole('button', {name: 'Apply'})).toBeDisabled();
});

test('The component should render in body with loader instead of confirm button', () => {
    render(
        <Overlay
            confirmLoading={true}
            confirmText="Apply"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            title="My overlay title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(screen.getByRole('button', {name: 'Apply'})).toHaveClass('loading');
});

test('The component should not render in body when closed', () => {
    render(
        <Overlay
            confirmText="Apply"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            title="My overlay title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(screen.queryByText('My overlay content')).not.toBeInTheDocument();
});

test('The component should request to be closed when the close icon is clicked', async() => {
    const closeSpy = jest.fn();
    const user = userEvent.setup();

    render(
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

    expect(closeSpy).not.toBeCalled();

    await user.click(screen.getByRole('button', {name: 'su-times'}));

    expect(closeSpy).toBeCalled();
});

test('The component should request to be closed when the esc key is pressed', () => {
    const closeSpy = jest.fn();

    render(
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

    expect(closeSpy).not.toBeCalled();

    Mousetrap.trigger('esc');

    expect(closeSpy).toBeCalled();
});

test('The component should bind and unbind the esc key when overlay is opened and closed', () => {
    const closeSpy = jest.fn();

    const {rerender} = render(
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

    expect(closeSpy).not.toBeCalled();

    Mousetrap.trigger('esc');
    expect(closeSpy).toBeCalled();

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
    expect(closeSpy).not.toBeCalled();

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
    expect(closeSpy).toBeCalled();
});

test('The component should call the callback when the confirm button is clicked', async() => {
    const onConfirm = jest.fn();
    const user = userEvent.setup();

    render(
        <Overlay
            confirmText="Alright mate!"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={true}
            title="My title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(onConfirm).not.toBeCalled();

    await user.click(screen.getByRole('button', {name: 'Alright mate!'}));

    expect(onConfirm).toBeCalled();
});

test('The component should render with a warning', () => {
    render(
        <Overlay
            confirmText="Alright mate!"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            snackbarMessage="Something really strange happened"
            snackbarType="warning"
            title="My title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    const warningSnackbar = document.querySelector('.snackbar.warning');
    const errorSnackbar = document.querySelector('.snackbar.error');

    expect(warningSnackbar).not.toBeNull();
    expect(warningSnackbar).toHaveTextContent('sulu_admin.warning - Something really strange happened');
    expect(errorSnackbar).toBeNull();
});

test('The component should render with an error', () => {
    render(
        <Overlay
            confirmText="Alright mate!"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            snackbarMessage="Money transfer unsuccessful"
            snackbarType="error"
            title="My title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    const errorSnackbar = document.querySelector('.snackbar.error');
    const warningSnackbar = document.querySelector('.snackbar.warning');

    expect(errorSnackbar).not.toBeNull();
    expect(errorSnackbar).toHaveTextContent('sulu_admin.error - Money transfer unsuccessful');
    expect(warningSnackbar).toBeNull();
});

test('The component should render with an error if type is unknown', () => {
    render(
        <Overlay
            confirmText="Alright mate!"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            snackbarMessage="Money transfer unsuccessful"
            title="My title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    const errorSnackbar = document.querySelector('.snackbar.error');
    const warningSnackbar = document.querySelector('.snackbar.warning');

    expect(errorSnackbar).not.toBeNull();
    expect(errorSnackbar).toHaveTextContent('sulu_admin.error - Money transfer unsuccessful');
    expect(warningSnackbar).toBeNull();
});

test('The component should call the callback when the snackbar close button is clicked', async() => {
    const onSnackbarCloseClick = jest.fn();
    const user = userEvent.setup();

    render(
        <Overlay
            confirmText="Alright mate!"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            onSnackbarCloseClick={onSnackbarCloseClick}
            open={true}
            snackbarMessage="Money transfer unsuccessful"
            snackbarType="error"
            title="My title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(onSnackbarCloseClick).not.toBeCalled();

    const closeIcon = document.querySelector('.snackbar.error .su-times');
    if (!closeIcon) {
        throw new Error('Expected snackbar close icon to be rendered');
    }

    await user.click(closeIcon);

    expect(onSnackbarCloseClick).toBeCalled();
});

test('The component should call the callback when the snackbar is clicked', async() => {
    const onSnackbarClick = jest.fn();
    const user = userEvent.setup();

    render(
        <Overlay
            confirmText="Alright mate!"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            onSnackbarClick={onSnackbarClick}
            open={true}
            snackbarMessage="Something really strange happened"
            snackbarType="warning"
            title="My title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(onSnackbarClick).not.toBeCalled();

    const warningSnackbar = document.querySelector('.snackbar.warning');
    if (!warningSnackbar) {
        throw new Error('Expected warning snackbar to be rendered');
    }

    await user.click(warningSnackbar);

    expect(onSnackbarClick).toBeCalled();
});
