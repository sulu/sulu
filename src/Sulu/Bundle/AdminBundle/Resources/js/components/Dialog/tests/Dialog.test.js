// @flow
import {mount, shallow} from 'enzyme';
import React from 'react';
import Dialog from '../Dialog';

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

    expect(view.find('Backdrop')).toHaveLength(1);
    expect(view.find('Backdrop').prop('open')).toEqual(true);
    expect(view.find('Dialog > Portal').at(0).render()).toMatchSnapshot();
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

    expect(view.find('Backdrop')).toHaveLength(1);
    expect(view.find('Backdrop').prop('open')).toEqual(false);
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
