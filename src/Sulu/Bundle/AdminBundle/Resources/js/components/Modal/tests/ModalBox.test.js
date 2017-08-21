/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, shallow} from 'enzyme';
import ModalBox from '../ModalBox';

test('The component should render', () => {
    const onRequestClose = () => {};
    const onConfirm = () => {};
    const actions = [
        {title: 'Action 1', handleAction: () => {}},
        {title: 'Action 2', handleAction: () => {}},
    ];
    const box = render(
        <ModalBox
            title="My title"
            actions={actions}
            onRequestClose={onRequestClose}
            onConfirm={onConfirm}
            confirmText="Alright mate!" >
            <p>My modal content</p>
        </ModalBox>
    );
    expect(box).toMatchSnapshot();
});

test('The component should request to be closed when the close icon is clicked', () => {
    const onRequestClose = jest.fn();
    const onConfirm = () => {};
    const box = shallow(
        <ModalBox
            title="My title"
            onRequestClose={onRequestClose}
            onConfirm={onConfirm}
            confirmText="Alright mate!" >
            <p>My modal content</p>
        </ModalBox>
    );
    box.find('Icon').props().onClick();
    expect(onRequestClose).toBeCalled();
});

test('The component should call the callback when the confirm button is clicked', () => {
    const onRequestClose = () => {};
    const onConfirm = jest.fn();
    const box = shallow(
        <ModalBox
            title="My title"
            onRequestClose={onRequestClose}
            onConfirm={onConfirm}
            confirmText="Alright mate!" >
            <p>My modal content</p>
        </ModalBox>
    );
    box.find('.confirmButton').simulate('click');
    expect(onConfirm).toBeCalled();
});

test('The component should call the corresponding callback when an action is clicked', () => {
    const onRequestClose = () => {};
    const onConfirm = () => {};
    const actions = [
        {title: 'Action 1', handleAction: jest.fn()},
        {title: 'Action 2', handleAction: jest.fn()},
    ];
    const box = shallow(
        <ModalBox
            title="My title"
            actions={actions}
            onRequestClose={onRequestClose}
            onConfirm={onConfirm}
            confirmText="Alright mate!" >
            <p>My modal content</p>
        </ModalBox>
    );
    box.find('.actions button').first().simulate('click');
    expect(actions[0].handleAction).toBeCalled();
    expect(actions[1].handleAction).not.toBeCalled();
});
