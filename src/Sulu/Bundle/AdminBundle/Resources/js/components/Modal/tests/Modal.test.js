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
            isOpen={true} >
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
            isOpen={false} >
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
            isOpen={true} >
            <p>My modal content</p>
        </Modal>
    );
    const backdrop = view.find('Backdrop');
    expect(backdrop.length).toBe(1);

    expect(requestCloseSpy).not.toBeCalled();
    backdrop.props().onClick();
    expect(requestCloseSpy).toBeCalled();
});
