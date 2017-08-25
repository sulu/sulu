/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount} from 'enzyme';
import pretty from 'pretty';
import ClickModal from '../ClickModal';

jest.mock('../../../services/DOM/afterElementsRendered');

afterEach(() => document.body.innerHTML = '');

test('The modal should initially not be rendered', () => {
    const body = document.body;
    const view = mount(
        <ClickModal
            clickElement={<button>Open modal</button>}
            title="My modal title"
            confirmText="Apply">
            <p>My modal content</p>
        </ClickModal>
    ).render();
    expect(view).toMatchSnapshot();
    expect(body.innerHTML).toBe('');
});

test('The modal should be rendered when the button got clicked', () => {
    const body = document.body;
    const view = mount(
        <ClickModal
            clickElement={<button>Open modal</button>}
            title="My modal title"
            confirmText="Apply">
            <p>My modal content</p>
        </ClickModal>
    );
    view.render();

    expect(body.innerHTML).toBe('');
    view.find('button').simulate('click');
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('The modal should be gone when the modal requests to be closed', () => {
    const view = mount(
        <ClickModal
            clickElement={<button>Open modal</button>}
            title="My modal title"
            confirmText="Apply">
            <p>My modal content</p>
        </ClickModal>
    );
    view.find('button').simulate('click');
    view.find('Modal').props().onRequestClose();
    expect(view.find('Modal').props().isOpen).toBe(false);
});

test('The modal should be gone and call the confirm callback when the modal is confirmed', () => {
    const onConfirm = jest.fn();
    const view = mount(
        <ClickModal
            clickElement={<button>Open modal</button>}
            title="My modal title"
            onConfirm={onConfirm}
            confirmText="Apply">
            <p>My modal content</p>
        </ClickModal>
    );
    view.find('button').simulate('click');
    view.find('Modal').props().onConfirm();
    expect(view.find('Modal').props().isOpen).toBe(false);
    expect(onConfirm).toBeCalled();
});
