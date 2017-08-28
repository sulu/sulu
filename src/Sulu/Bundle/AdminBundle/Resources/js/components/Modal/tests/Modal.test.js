/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, shallow} from 'enzyme';
import React from 'react';
import pretty from 'pretty';
import Modal from '../Modal';

afterEach(() => document.body.innerHTML = '');

test('The component should render in body when open', () => {
    const body = document.body;
    const onRequestClose = () => {};
    const view = mount(
        <Modal
            title="My modal title"
            onRequestClose={onRequestClose}
            confirmText="Apply"
            isOpen={true}>
            <p>My modal content</p>
        </Modal>
    ).render();

    expect(view).toMatchSnapshot();
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('The component should render in body with actions when open', () => {
    const actions = [
        {title: 'Action 1', onClick: () => {}},
        {title: 'Action 2', onClick: () => {}},
    ];
    const body = document.body;
    const onRequestClose = () => {};
    const view = mount(
        <Modal
            title="My modal title"
            onRequestClose={onRequestClose}
            confirmText="Apply"
            actions={actions}
            isOpen={true}>
            <p>My modal content</p>
        </Modal>
    ).render();

    expect(view).toMatchSnapshot();
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('The component should not render in body when closed', () => {
    const body = document.body;
    const onRequestClose = () => {};
    const view = mount(
        <Modal
            title="My modal title"
            onRequestClose={onRequestClose}
            confirmText="Apply"
            isOpen={false}>
            <p>My modal content</p>
        </Modal>
    ).render();
    expect(view).toMatchSnapshot();
    expect(body.innerHTML).toBe('');
});

test('The component should request to be closed on click on backdrop', () => {
    const requestCloseSpy = jest.fn();
    const view = shallow(
        <Modal
            title="My modal title"
            onRequestClose={requestCloseSpy}
            confirmText="Apply"
            isOpen={true}>
            <p>My modal content</p>
        </Modal>
    );
    const backdrop = view.find('Backdrop');
    expect(backdrop.length).toBe(1);

    expect(requestCloseSpy).not.toBeCalled();
    backdrop.props().onClick();
    expect(requestCloseSpy).toBeCalled();
});

test('The component should request to be closed when the close icon is clicked', () => {
    const requestCloseSpy = jest.fn();
    const view = shallow(
        <Modal
            title="My modal title"
            onRequestClose={requestCloseSpy}
            confirmText="Apply"
            isOpen={true}>
            <p>My modal content</p>
        </Modal>
    );

    expect(requestCloseSpy).not.toBeCalled();
    view.find('Icon').simulate('click');
    expect(requestCloseSpy).toBeCalled();
});

test('The component should call the callback when the confirm button is clicked', () => {
    const onRequestClose = () => {};
    const onConfirm = jest.fn();
    const view = shallow(
        <Modal
            title="My title"
            onRequestClose={onRequestClose}
            onConfirm={onConfirm}
            confirmText="Alright mate!">
            <p>My modal content</p>
        </Modal>
    );

    expect(onConfirm).not.toBeCalled();
    view.find('.confirmButton').simulate('click');
    expect(onConfirm).toBeCalled();
});
