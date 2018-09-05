// @flow
import {mount, shallow} from 'enzyme';
import React from 'react';
import pretty from 'pretty';
import Dialog from '../Dialog';

afterEach(() => {
    if (document.body) {
        document.body.innerHTML = '';
    }
});

test('The component should render in body when open', () => {
    const body = document.body;
    const onCancel = jest.fn();
    const onConfirm = jest.fn();
    const view = mount(
        <Dialog
            title="My dialog title"
            onCancel={onCancel}
            onConfirm={onConfirm}
            cancelText="Cancel"
            confirmText="Confirm"
            open={true}
        >
            <div>My dialog content</div>
        </Dialog>
    ).render();

    expect(view).toMatchSnapshot();
    expect(pretty(body ? body.innerHTML : '')).toMatchSnapshot();
});

test('The component should render in body with disabled confirm button', () => {
    const body = document.body;
    const onCancel = jest.fn();
    const onConfirm = jest.fn();
    const view = mount(
        <Dialog
            title="My dialog title"
            onCancel={onCancel}
            onConfirm={onConfirm}
            cancelText="Cancel"
            confirmDisabled={true}
            confirmText="Confirm"
            open={true}
        >
            <div>My dialog content</div>
        </Dialog>
    ).render();

    expect(view).toMatchSnapshot();
    expect(pretty(body ? body.innerHTML : '')).toMatchSnapshot();
});

test('The component should render in body with a large class', () => {
    const body = document.body;
    const onCancel = jest.fn();
    const onConfirm = jest.fn();
    const view = mount(
        <Dialog
            title="My dialog title"
            onCancel={onCancel}
            onConfirm={onConfirm}
            cancelText="Cancel"
            confirmText="Confirm"
            open={true}
            size="large"
        >
            <div>My dialog content</div>
        </Dialog>
    ).render();

    expect(view).toMatchSnapshot();
    expect(pretty(body ? body.innerHTML : '')).toMatchSnapshot();
});

test('The component should render in body with loader instead of confirm button', () => {
    const body = document.body;
    const onCancel = jest.fn();
    const onConfirm = jest.fn();
    const view = mount(
        <Dialog
            title="My dialog title"
            onCancel={onCancel}
            onConfirm={onConfirm}
            cancelText="Cancel"
            confirmText="Confirm"
            confirmLoading={true}
            open={true}
        >
            <div>My dialog content</div>
        </Dialog>
    ).render();

    expect(view).toMatchSnapshot();
    expect(pretty(body ? body.innerHTML : '')).toMatchSnapshot();
});

test('The component should not render in body when closed', () => {
    const body = document.body;
    const onCancel = jest.fn();
    const onConfirm = jest.fn();
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
    expect(body ? body.innerHTML : '').toBe('');
});

test('The component should call the callback when the confirm button is clicked', () => {
    const onCancel = jest.fn();
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
    view.find('Button[skin="primary"]').simulate('click');
    expect(onConfirm).toBeCalled();
});

test('The component should call the callback when the cancel button is clicked', () => {
    const onConfirm = jest.fn();
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
    view.find('Button[skin="secondary"]').simulate('click');
    expect(onCancel).toBeCalled();
});
