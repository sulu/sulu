// @flow
import {mount, shallow} from 'enzyme';
import React from 'react';
import Dialog from '../Dialog';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('The component should render in body when open', () => {
    const view = mount(
        <Dialog
            cancelText="Cancel"
            confirmText="Confirm"
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            title="My dialog title"
        >
            <div>My dialog content</div>
        </Dialog>
    );

    expect(view.find('Dialog > Portal').at(0).render()).toMatchSnapshot();
});

test('The component should render aligned to the left', () => {
    const view = shallow(
        <Dialog
            align="left"
            cancelText="Cancel"
            confirmText="Confirm"
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            title="My dialog title"
        >
            <div>My dialog content</div>
        </Dialog>
    );

    expect(view.find('.article').hasClass('left')).toEqual(true);
});

test('The component should render in body without cancel button', () => {
    const onConfirm = jest.fn();
    const view = mount(
        <Dialog
            confirmText="Confirm"
            onConfirm={onConfirm}
            open={true}
            title="My dialog title"
        >
            <div>My dialog content</div>
        </Dialog>
    );

    expect(view.find('Button')).toHaveLength(1);
    expect(view.find('Button[children="Confirm"]')).toHaveLength(1);
});

test('The component should render in body with disabled confirm button', () => {
    const onCancel = jest.fn();
    const onConfirm = jest.fn();
    const view = mount(
        <Dialog
            cancelText="Cancel"
            confirmDisabled={true}
            confirmText="Confirm"
            onCancel={onCancel}
            onConfirm={onConfirm}
            open={true}
            title="My dialog title"
        >
            <div>My dialog content</div>
        </Dialog>
    );

    expect(view.find('Button[children="Confirm"]').prop('disabled')).toEqual(true);
});

test('The component should render in body with a large class', () => {
    const view = mount(
        <Dialog
            cancelText="Cancel"
            confirmText="Confirm"
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            size="large"
            title="My dialog title"
        >
            <div>My dialog content</div>
        </Dialog>
    );

    expect(view.find('Dialog > Portal div.large')).toHaveLength(1);
});

test('The component should render in body with loader instead of confirm button', () => {
    const onCancel = jest.fn();
    const onConfirm = jest.fn();
    const view = mount(
        <Dialog
            cancelText="Cancel"
            confirmLoading={true}
            confirmText="Confirm"
            onCancel={onCancel}
            onConfirm={onConfirm}
            open={true}
            title="My dialog title"
        >
            <div>My dialog content</div>
        </Dialog>
    );

    expect(view.find('Button[children="Confirm"]').prop('loading')).toEqual(true);
});

test('The component should not render in body when closed', () => {
    const view = mount(
        <Dialog
            cancelText="Cancel"
            confirmText="Confirm"
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            title="My dialog title"
        >
            My dialog content
        </Dialog>
    );

    expect(view.find('Dialog > Portal')).toHaveLength(0);
});

test('The component should call the callback when the confirm button is clicked', () => {
    const onCancel = jest.fn();
    const onConfirm = jest.fn();
    const view = shallow(
        <Dialog
            cancelText="Cancel"
            confirmText="Confirm"
            onCancel={onCancel}
            onConfirm={onConfirm}
            open={true}
            title="My dialog title"
        >
            My dialog content
        </Dialog>
    );

    expect(onConfirm).not.toBeCalled();
    view.find('Button[skin="primary"]').simulate('click');
    expect(onConfirm).toBeCalled();
});

test('The component should call the callback when the cancel button is clicked', () => {
    const onConfirm = jest.fn();
    const onCancel = jest.fn();
    const view = shallow(
        <Dialog
            cancelText="Cancel"
            confirmText="Confirm"
            onCancel={onCancel}
            onConfirm={onConfirm}
            open={true}
            title="My dialog title"
        >
            My dialog content
        </Dialog>
    );

    expect(onCancel).not.toBeCalled();
    view.find('Button[skin="secondary"]').simulate('click');
    expect(onCancel).toBeCalled();
});

test('The component should render with a warning', () => {
    const onConfirm = jest.fn();
    const view = mount(
        <Dialog
            confirmText="Confirm"
            onConfirm={onConfirm}
            open={true}
            snackbarMessage="Something really strange happened"
            snackbarType="warning"
            title="My dialog title"
        >
            <div>My dialog content</div>
        </Dialog>
    );

    expect(view.find('.snackbar.warning')).toHaveLength(1);
    expect(view.find('.snackbar.warning').text()).toBe('sulu_admin.warning - Something really strange happened');
    expect(view.find('.snackbar.error')).toHaveLength(0);
});

test('The component should render with an error', () => {
    const onConfirm = jest.fn();
    const view = mount(
        <Dialog
            confirmText="Confirm"
            onConfirm={onConfirm}
            open={true}
            snackbarMessage="Money transfer unsuccessful"
            snackbarType="error"
            title="My dialog title"
        >
            <div>My dialog content</div>
        </Dialog>
    );

    expect(view.find('.snackbar.error')).toHaveLength(1);
    expect(view.find('.snackbar.error').text()).toBe('sulu_admin.error - Money transfer unsuccessful');
    expect(view.find('.snackbar.warning')).toHaveLength(0);
});

test('The component should render with an error if the type is unknown', () => {
    const onConfirm = jest.fn();
    const view = mount(
        <Dialog
            confirmText="Confirm"
            onConfirm={onConfirm}
            open={true}
            snackbarMessage="Money transfer unsuccessful"
            title="My dialog title"
        >
            <div>My dialog content</div>
        </Dialog>
    );

    expect(view.find('.snackbar.error')).toHaveLength(1);
    expect(view.find('.snackbar.error').text()).toBe('sulu_admin.error - Money transfer unsuccessful');
    expect(view.find('.snackbar.warning')).toHaveLength(0);
});

test('The component should call the callback when the snackbar close button is clicked', () => {
    const onSnackbarCloseClick = jest.fn();
    const view = mount(
        <Dialog
            confirmText="Confirm"
            onConfirm={jest.fn()}
            onSnackbarCloseClick={onSnackbarCloseClick}
            open={true}
            snackbarMessage="Money transfer unsuccessful"
            snackbarType="error"
            title="My dialog title"
        >
            My dialog content
        </Dialog>
    );

    expect(onSnackbarCloseClick).not.toBeCalled();
    view.find('.snackbar.error .su-times').simulate('click');
    expect(onSnackbarCloseClick).toBeCalled();
});

test('The component should call the callback when the snackbar is clicked', () => {
    const onSnackbarClick = jest.fn();
    const view = mount(
        <Dialog
            confirmText="Confirm"
            onConfirm={jest.fn()}
            onSnackbarClick={onSnackbarClick}
            open={true}
            snackbarMessage="Something really strange happened"
            snackbarType="warning"
            title="My dialog title"
        >
            My dialog content
        </Dialog>
    );

    expect(onSnackbarClick).not.toBeCalled();
    view.find('.snackbar.warning').simulate('click');
    expect(onSnackbarClick).toBeCalled();
});
