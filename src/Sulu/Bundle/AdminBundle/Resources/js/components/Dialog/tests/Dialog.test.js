/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, shallow} from 'enzyme';
import React from 'react';
import pretty from 'pretty';
import Dialog from '../Dialog';

afterEach(() => document.body.innerHTML = '');

test('The component should render in body when open', () => {
    const body = document.body;
    const onCancel = () => {};
    const onConfirm = () => {};
    const view = mount(
        <Dialog
            title="My dialog title"
            onCancel={onCancel}
            onConfirm={onConfirm}
            cancelText="Cancel"
            confirmText="Confirm"
            open={true}
        >
            My dialog content
        </Dialog>
    ).render();

    expect(view).toMatchSnapshot();
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('The component should not render in body when closed', () => {
    const body = document.body;
    const onCancel = () => {};
    const onConfirm = () => {};
    const view = mount(
        <Dialog
            title="My dialog title"
            onCancel={onCancel}
            onConfirm={onConfirm}
            cancelText="Cancel"
            confirmText="Confirm"
            open={false}
        >
            My dialog content
        </Dialog>
    ).render();

    expect(view).toMatchSnapshot();
    expect(body.innerHTML).toBe('');
});

test('The component should call the callback when the confirm button is clicked', () => {
    const onCancel = () => {};
    const onConfirm = jest.fn();
    const view = shallow(
        <Dialog
            title="My dialog title"
            onCancel={onCancel}
            onConfirm={onConfirm}
            cancelText="Cancel"
            confirmText="Confirm"
            open={true}
        >
            My dialog content
        </Dialog>
    );

    expect(onConfirm).not.toBeCalled();
    view.find('Button[type="primary"]').simulate('click');
    expect(onConfirm).toBeCalled();
});

test('The component should call the callback when the cancel button is clicked', () => {
    const onConfirm = () => {};
    const onCancel = jest.fn();
    const view = shallow(
        <Dialog
            title="My dialog title"
            onCancel={onCancel}
            onConfirm={onConfirm}
            cancelText="Cancel"
            confirmText="Confirm"
            open={true}
        >
            My dialog content
        </Dialog>
    );

    expect(onCancel).not.toBeCalled();
    view.find('Button[type="secondary"]').simulate('click');
    expect(onCancel).toBeCalled();
});
