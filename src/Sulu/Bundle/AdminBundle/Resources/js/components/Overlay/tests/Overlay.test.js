/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, shallow} from 'enzyme';
import React from 'react';
import pretty from 'pretty';
import Overlay from '../Overlay';

afterEach(() => document.body.innerHTML = '');

test('The component should render in body when open', () => {
    const body = document.body;
    const onRequestClose = () => {};
    const view = mount(
        <Overlay
            title="My overlay title"
            onRequestClose={onRequestClose}
            confirmText="Apply"
            isOpen={true}>
            <p>My overlay content</p>
        </Overlay>
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
        <Overlay
            title="My overlay title"
            onRequestClose={onRequestClose}
            confirmText="Apply"
            actions={actions}
            isOpen={true}>
            <p>My overlay content</p>
        </Overlay>
    ).render();

    expect(view).toMatchSnapshot();
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('The component should not render in body when closed', () => {
    const body = document.body;
    const onRequestClose = () => {};
    const view = mount(
        <Overlay
            title="My overlay title"
            onRequestClose={onRequestClose}
            confirmText="Apply"
            isOpen={false}>
            <p>My overlay content</p>
        </Overlay>
    ).render();
    expect(view).toMatchSnapshot();
    expect(body.innerHTML).toBe('');
});

test('The component should request to be closed on click on backdrop', () => {
    const requestCloseSpy = jest.fn();
    const view = shallow(
        <Overlay
            title="My overlay title"
            onRequestClose={requestCloseSpy}
            confirmText="Apply"
            isOpen={true}>
            <p>My overlay content</p>
        </Overlay>
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
        <Overlay
            title="My overlay title"
            onRequestClose={requestCloseSpy}
            confirmText="Apply"
            isOpen={true}>
            <p>My overlay content</p>
        </Overlay>
    );

    expect(requestCloseSpy).not.toBeCalled();
    view.find('Icon').simulate('click');
    expect(requestCloseSpy).toBeCalled();
});

test('The component should call the callback when the confirm button is clicked', () => {
    const onRequestClose = () => {};
    const onConfirm = jest.fn();
    const view = shallow(
        <Overlay
            title="My title"
            onRequestClose={onRequestClose}
            onConfirm={onConfirm}
            confirmText="Alright mate!">
            <p>My overlay content</p>
        </Overlay>
    );

    expect(onConfirm).not.toBeCalled();
    view.find('.confirmButton').simulate('click');
    expect(onConfirm).toBeCalled();
});
